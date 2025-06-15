<?php
/**
 * Elementor Evaluation Stats Widget - BULLETPROOF VERSION
 * File: includes/elementor/class-evaluation-stats-widget.php
 * 
 * This version includes extensive error handling and fallbacks
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Ensure Elementor is loaded
if (!class_exists('\Elementor\Widget_Base')) {
    return;
}

/**
 * Evaluation Stats Widget
 */
class MT_Evaluation_Stats_Widget extends \Elementor\Widget_Base {
    
    /**
     * Get widget name
     */
    public function get_name() {
        return 'mt_evaluation_stats';
    }
    
    /**
     * Get widget title
     */
    public function get_title() {
        return esc_html__('MT Evaluation Statistics', 'mobility-trailblazers');
    }
    
    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-counter';
    }
    
    /**
     * Get widget categories
     */
    public function get_categories() {
        return ['general', 'mobility-trailblazers'];
    }
    
    /**
     * Get widget keywords
     */
    public function get_keywords() {
        return ['statistics', 'evaluation', 'stats', 'mobility', 'trailblazers'];
    }
    
    /**
     * Register widget controls
     */
    protected function register_controls() {
        try {
            // Content Section
            $this->start_controls_section(
                'content_section',
                [
                    'label' => esc_html__('Statistics Settings', 'mobility-trailblazers'),
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
            );
            
            $this->add_control(
                'stats_type',
                [
                    'label' => esc_html__('Statistics Type', 'mobility-trailblazers'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'default' => 'overview',
                    'options' => [
                        'overview' => esc_html__('Overview', 'mobility-trailblazers'),
                        'personal' => esc_html__('Personal Stats (Jury Only)', 'mobility-trailblazers'),
                        'leaderboard' => esc_html__('Top Candidates', 'mobility-trailblazers'),
                    ],
                ]
            );
            
            $this->add_control(
                'show_progress_bar',
                [
                    'label' => esc_html__('Show Progress Bar', 'mobility-trailblazers'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'label_on' => esc_html__('Show', 'mobility-trailblazers'),
                    'label_off' => esc_html__('Hide', 'mobility-trailblazers'),
                    'return_value' => 'yes',
                    'default' => 'yes',
                ]
            );
            
            $this->add_control(
                'show_icons',
                [
                    'label' => esc_html__('Show Icons', 'mobility-trailblazers'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'label_on' => esc_html__('Show', 'mobility-trailblazers'),
                    'label_off' => esc_html__('Hide', 'mobility-trailblazers'),
                    'return_value' => 'yes',
                    'default' => 'yes',
                ]
            );
            
            $this->end_controls_section();
            
            // Style Section
            $this->start_controls_section(
                'style_section',
                [
                    'label' => esc_html__('Style', 'mobility-trailblazers'),
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                ]
            );
            
            $this->add_control(
                'stats_background',
                [
                    'label' => esc_html__('Background Color', 'mobility-trailblazers'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .mt-stats-box' => 'background-color: {{VALUE}};',
                    ],
                ]
            );
            
            $this->add_control(
                'text_color',
                [
                    'label' => esc_html__('Text Color', 'mobility-trailblazers'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .mt-stat-number' => 'color: {{VALUE}};',
                        '{{WRAPPER}} .mt-stats-box i' => 'color: {{VALUE}};',
                    ],
                ]
            );
            
            $this->add_control(
                'progress_color',
                [
                    'label' => esc_html__('Progress Bar Color', 'mobility-trailblazers'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#2c5282',
                    'selectors' => [
                        '{{WRAPPER}} .mt-progress-fill' => 'background-color: {{VALUE}};',
                    ],
                    'condition' => [
                        'show_progress_bar' => 'yes',
                    ],
                ]
            );
            
            $this->end_controls_section();
            
        } catch (Exception $e) {
            error_log('MT Widget Controls Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Render widget output
     */
    protected function render() {
        try {
            $settings = $this->get_settings_for_display();
            
            // Check if in editor
            if ($this->is_elementor_editor()) {
                $this->render_editor_preview();
                return;
            }
            
            // Check permissions for personal stats
            if ($settings['stats_type'] === 'personal' && !is_user_logged_in()) {
                $this->render_login_required();
                return;
            }
            
            // Render based on type
            echo '<div class="mt-elementor-widget-wrapper mt-stats-widget">';
            
            switch ($settings['stats_type']) {
                case 'overview':
                    $this->render_overview_stats($settings);
                    break;
                case 'personal':
                    $this->render_personal_stats($settings);
                    break;
                case 'leaderboard':
                    $this->render_leaderboard($settings);
                    break;
                default:
                    $this->render_overview_stats($settings);
                    break;
            }
            
            echo '</div>';
            
        } catch (Exception $e) {
            error_log('MT Widget Render Error: ' . $e->getMessage());
            echo '<div class="mt-widget-error">Widget error occurred. Please check logs.</div>';
        }
    }
    
    /**
     * Check if we're in Elementor editor
     */
    private function is_elementor_editor() {
        return (
            class_exists('\Elementor\Plugin') && 
            \Elementor\Plugin::$instance->editor->is_edit_mode()
        );
    }
    
    /**
     * Get evaluation statistics - with multiple fallbacks
     */
    private function get_evaluation_statistics() {
        global $wpdb;
        
        try {
            // Try the function first
            if (function_exists('mt_get_evaluation_statistics')) {
                $stats = mt_get_evaluation_statistics();
                if (is_array($stats) && !empty($stats)) {
                    return $stats;
                }
            }
            
            // Fallback: Calculate directly
            $stats = array();
            
            // Total candidates with error handling
            $stats['total_candidates'] = (int) $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->posts} 
                WHERE post_type = 'mt_candidate' AND post_status = 'publish'
            ");
            
            // Active jury members
            $stats['active_jury_members'] = (int) $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->posts} 
                WHERE post_type = 'mt_jury' AND post_status = 'publish'
            ");
            
            // Total evaluations
            $table_scores = $wpdb->prefix . 'mt_candidate_scores';
            if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_scores))) {
                $stats['total_evaluations'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM `$table_scores`");
                
                // Unique evaluations (candidate-jury pairs)
                $stats['unique_evaluations'] = (int) $wpdb->get_var("
                    SELECT COUNT(DISTINCT CONCAT(candidate_id, '-', jury_member_id)) 
                    FROM `$table_scores`
                ");
            } else {
                $stats['total_evaluations'] = 0;
                $stats['unique_evaluations'] = 0;
            }
            
            // Completion percentage with safety checks
            if ($stats['total_candidates'] > 0 && $stats['active_jury_members'] > 0) {
                $expected_evaluations = $stats['total_candidates'] * $stats['active_jury_members'];
                $stats['completion_percentage'] = $expected_evaluations > 0 
                    ? round(($stats['unique_evaluations'] / $expected_evaluations) * 100, 1)
                    : 0;
            } else {
                $stats['completion_percentage'] = 0;
            }
            
            return $stats;
            
        } catch (Exception $e) {
            error_log('MT Stats Error: ' . $e->getMessage());
            return array(
                'total_candidates' => 0,
                'total_evaluations' => 0,
                'active_jury_members' => 0,
                'completion_percentage' => 0
            );
        }
    }
    
    /**
     * Get user evaluation count - simplified to use global function
     */
    private function get_user_evaluation_count($user_id) {
        // Just use the global function
        return function_exists('mt_get_user_evaluation_count') 
            ? (int) mt_get_user_evaluation_count($user_id) 
            : 0;
    }
    
    /**
     * Get user assignments count - with fallback
     */
    private function get_user_assignments_count($user_id) {
        global $wpdb;
        
        try {
            // Try the function first
            if (function_exists('mt_get_user_assignments_count')) {
                return (int) mt_get_user_assignments_count($user_id);
            }
            
            // Fallback: Get jury post for this user, then count assignments
            $jury_post_id = $wpdb->get_var($wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key = '_mt_jury_user_id' AND meta_value = %s",
                $user_id
            ));
            
            if ($jury_post_id) {
                return (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->postmeta} pm
                    JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                    WHERE pm.meta_key = '_mt_assigned_jury_member' 
                    AND pm.meta_value = %d
                    AND p.post_status = 'publish'
                    AND p.post_type = 'mt_candidate'",
                    $jury_post_id
                ));
            }
            
            return 0;
            
        } catch (Exception $e) {
            error_log('MT User Assignments Count Error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Render overview statistics
     */
    private function render_overview_stats($settings) {
        // Get stats with fallback
        $stats = $this->get_evaluation_statistics();
        ?>
        <div class="mt-stats-grid">
            <div class="mt-stats-box">
                <?php if ($settings['show_icons'] === 'yes'): ?>
                    <div class="mt-stat-icon">
                        <i class="eicon-user-circle-o" aria-hidden="true"></i>
                    </div>
                <?php endif; ?>
                <div class="mt-stat-number"><?php echo number_format($stats['total_candidates']); ?></div>
                <div class="mt-stat-label"><?php echo esc_html__('Total Candidates', 'mobility-trailblazers'); ?></div>
            </div>
            
            <div class="mt-stats-box">
                <?php if ($settings['show_icons'] === 'yes'): ?>
                    <div class="mt-stat-icon">
                        <i class="eicon-check" aria-hidden="true"></i>
                    </div>
                <?php endif; ?>
                <div class="mt-stat-number"><?php echo number_format($stats['total_evaluations']); ?></div>
                <div class="mt-stat-label"><?php echo esc_html__('Evaluations Completed', 'mobility-trailblazers'); ?></div>
            </div>
            
            <div class="mt-stats-box">
                <?php if ($settings['show_icons'] === 'yes'): ?>
                    <div class="mt-stat-icon">
                        <i class="eicon-user" aria-hidden="true"></i>
                    </div>
                <?php endif; ?>
                <div class="mt-stat-number"><?php echo number_format($stats['active_jury_members']); ?></div>
                <div class="mt-stat-label"><?php echo esc_html__('Active Jury Members', 'mobility-trailblazers'); ?></div>
            </div>
        </div>
        
        <?php if ($settings['show_progress_bar'] === 'yes'): ?>
            <div class="mt-progress-container">
                <div class="mt-progress-bar" role="progressbar" aria-valuenow="<?php echo $stats['completion_percentage']; ?>" aria-valuemin="0" aria-valuemax="100">
                    <div class="mt-progress-fill" style="width: <?php echo $stats['completion_percentage']; ?>%"></div>
                </div>
                <div class="mt-progress-text">
                    <?php echo $stats['completion_percentage']; ?>% <?php echo esc_html__('Complete', 'mobility-trailblazers'); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php $this->render_inline_styles(); ?>
        <?php
    }
    
    /**
     * Render personal statistics
     */
    private function render_personal_stats($settings) {
        $user_id = get_current_user_id();
        $evaluations = $this->get_user_evaluation_count($user_id);
        $assignments = $this->get_user_assignments_count($user_id);
        
        ?>
        <div class="mt-personal-stats">
            <h4><?php echo esc_html__('Your Progress', 'mobility-trailblazers'); ?></h4>
            <p class="mt-progress-info">
                <?php echo sprintf(
                    esc_html__('Evaluated: %1$d / %2$d', 'mobility-trailblazers'), 
                    $evaluations, 
                    $assignments
                ); ?>
            </p>
            
            <?php if ($settings['show_progress_bar'] === 'yes' && $assignments > 0): ?>
                <?php $percentage = round(($evaluations / $assignments) * 100); ?>
                <div class="mt-progress-bar" role="progressbar" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                    <div class="mt-progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                </div>
                <div class="mt-progress-text">
                    <?php echo $percentage; ?>% <?php echo esc_html__('Complete', 'mobility-trailblazers'); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <?php $this->render_personal_styles(); ?>
        <?php
    }
    
    /**
     * Render leaderboard
     */
    private function render_leaderboard($settings) {
        // Try shortcode first
        if (shortcode_exists('mt_voting_results')) {
            echo do_shortcode('[mt_voting_results type="top" limit="10"]');
            return;
        }
        
        // Fallback: Simple leaderboard
        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        
        if (!$wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_scores))) {
            echo '<p>' . esc_html__('Evaluation system not yet configured.', 'mobility-trailblazers') . '</p>';
            return;
        }
        
        try {
            $results = $wpdb->get_results("
                SELECT c.post_title as candidate_name, AVG(s.total_score) as avg_score
                FROM `$table_scores` s
                JOIN {$wpdb->posts} c ON s.candidate_id = c.ID
                WHERE c.post_status = 'publish' AND c.post_type = 'mt_candidate'
                GROUP BY s.candidate_id
                HAVING AVG(s.total_score) > 0
                ORDER BY avg_score DESC
                LIMIT 10
            ");
            
            if ($results && !empty($results)) {
                echo '<div class="mt-leaderboard">';
                echo '<h4>' . esc_html__('Top Candidates', 'mobility-trailblazers') . '</h4>';
                echo '<ol class="mt-leaderboard-list">';
                foreach ($results as $result) {
                    echo '<li class="mt-leaderboard-item">';
                    echo '<span class="mt-candidate-name">' . esc_html($result->candidate_name) . '</span>';
                    echo '<span class="mt-candidate-score">' . number_format($result->avg_score, 1) . '</span>';
                    echo '</li>';
                }
                echo '</ol>';
                echo '</div>';
            } else {
                echo '<p>' . esc_html__('No evaluation results available yet.', 'mobility-trailblazers') . '</p>';
            }
            
        } catch (Exception $e) {
            error_log('MT Leaderboard Error: ' . $e->getMessage());
            echo '<p>' . esc_html__('Unable to load leaderboard at this time.', 'mobility-trailblazers') . '</p>';
        }
    }
    
    /**
     * Render login required message
     */
    private function render_login_required() {
        ?>
        <div class="mt-elementor-login-required">
            <div class="mt-login-icon">
                <i class="eicon-lock" aria-hidden="true"></i>
            </div>
            <h4><?php echo esc_html__('Login Required', 'mobility-trailblazers'); ?></h4>
            <p><?php echo esc_html__('Please log in to view personal statistics.', 'mobility-trailblazers'); ?></p>
            <?php if (wp_login_url()): ?>
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="mt-login-button">
                    <?php echo esc_html__('Login', 'mobility-trailblazers'); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render editor preview
     */
    private function render_editor_preview() {
        $settings = $this->get_settings_for_display();
        ?>
        <div class="mt-elementor-preview">
            <div class="mt-preview-icon">
                <i class="eicon-counter" aria-hidden="true"></i>
            </div>
            <h3><?php echo esc_html__('Evaluation Statistics', 'mobility-trailblazers'); ?></h3>
            <p>
                <?php echo esc_html__('Type:', 'mobility-trailblazers'); ?> 
                <strong><?php echo esc_html(ucfirst($settings['stats_type'])); ?></strong>
            </p>
            <p class="mt-preview-note">
                <?php echo esc_html__('Statistics will be displayed here on the frontend.', 'mobility-trailblazers'); ?>
            </p>
        </div>
        
        <?php $this->render_preview_styles(); ?>
        <?php
    }
    
    /**
     * Render inline styles for the widget
     */
    private function render_inline_styles() {
        ?>
        <style>
        .mt-stats-widget .mt-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .mt-stats-widget .mt-stats-box {
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-left: 4px solid #2c5282;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }
        
        .mt-stats-widget .mt-stats-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        .mt-stats-widget .mt-stat-icon i {
            font-size: 2.5rem;
            color: #2c5282;
            margin-bottom: 15px;
            display: block;
        }
        
        .mt-stats-widget .mt-stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c5282;
            display: block;
            line-height: 1;
            margin: 10px 0;
        }
        
        .mt-stats-widget .mt-stat-label {
            color: #718096;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 5px;
            font-weight: 600;
        }
        
        .mt-stats-widget .mt-progress-container {
            margin-top: 30px;
            text-align: center;
        }
        
        .mt-stats-widget .mt-progress-bar {
            width: 100%;
            height: 20px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 10px;
            position: relative;
        }
        
        .mt-stats-widget .mt-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #2c5282 0%, #38b2ac 100%);
            transition: width 0.6s ease;
            border-radius: 10px;
        }
        
        .mt-stats-widget .mt-progress-text {
            font-weight: 600;
            color: #2c5282;
            font-size: 1.1rem;
        }
        
        .mt-stats-widget .mt-leaderboard-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .mt-stats-widget .mt-leaderboard-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            margin: 10px 0;
            background: #f8fafc;
            border-radius: 8px;
            border-left: 3px solid #2c5282;
        }
        
        .mt-stats-widget .mt-candidate-name {
            font-weight: 600;
            color: #2d3748;
        }
        
        .mt-stats-widget .mt-candidate-score {
            font-weight: 700;
            color: #2c5282;
            background: #e6fffa;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.9rem;
        }
        </style>
        <?php
    }
    
    /**
     * Render personal stats styles
     */
    private function render_personal_styles() {
        ?>
        <style>
        .mt-stats-widget .mt-personal-stats {
            background: #f7fafc;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            text-align: center;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .mt-stats-widget .mt-personal-stats h4 {
            color: #2c5282;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .mt-stats-widget .mt-progress-info {
            font-size: 1.1rem;
            color: #4a5568;
            margin-bottom: 20px;
        }
        </style>
        <?php
    }
    
    /**
     * Render preview styles
     */
    private function render_preview_styles() {
        ?>
        <style>
        .mt-elementor-preview {
            padding: 40px 20px;
            background: #f7f7f7;
            border: 2px dashed #ddd;
            text-align: center;
            border-radius: 8px;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        
        .mt-elementor-preview .mt-preview-icon i {
            font-size: 48px;
            color: #999;
            margin-bottom: 20px;
        }
        
        .mt-elementor-preview h3 {
            color: #333;
            margin: 0 0 10px 0;
        }
        
        .mt-elementor-preview p {
            color: #666;
            margin: 5px 0;
        }
        
        .mt-elementor-preview .mt-preview-note {
            font-size: 12px;
            font-style: italic;
        }
        
        .mt-elementor-login-required {
            text-align: center;
            padding: 40px 20px;
            background: #f0f8ff;
            border: 1px solid #bee3f8;
            border-radius: 8px;
        }
        
        .mt-elementor-login-required .mt-login-icon i {
            font-size: 48px;
            color: #3182ce;
            margin-bottom: 20px;
        }
        
        .mt-elementor-login-required h4 {
            color: #2c5282;
            margin-bottom: 10px;
        }
        
        .mt-login-button {
            display: inline-block;
            padding: 10px 20px;
            background: #2c5282;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
            transition: background 0.3s ease;
        }
        
        .mt-login-button:hover {
            background: #2a4365;
            color: white;
        }
        </style>
        <?php
    }
    
    /**
     * Render widget output on frontend
     */
    protected function content_template() {
        // This is used for live editing in Elementor
        ?>
        <div class="mt-elementor-preview">
            <div class="mt-preview-icon">
                <i class="eicon-counter" aria-hidden="true"></i>
            </div>
            <h3><?php echo esc_html__('Evaluation Statistics', 'mobility-trailblazers'); ?></h3>
            <p><?php echo esc_html__('Configure settings and preview on frontend', 'mobility-trailblazers'); ?></p>
        </div>
        <?php
    }
}