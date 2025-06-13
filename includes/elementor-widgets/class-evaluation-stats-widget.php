<?php
/**
 * Elementor Evaluation Stats Widget
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
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
        return __('MT Evaluation Statistics', 'mobility-trailblazers');
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
     * Register widget controls
     */
    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Statistics Settings', 'mobility-trailblazers'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'stats_type',
            [
                'label' => __('Statistics Type', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'overview',
                'options' => [
                    'overview' => __('Overview', 'mobility-trailblazers'),
                    'personal' => __('Personal Stats (Jury Only)', 'mobility-trailblazers'),
                    'leaderboard' => __('Top Candidates', 'mobility-trailblazers'),
                ],
            ]
        );
        
        $this->add_control(
            'show_progress_bar',
            [
                'label' => __('Show Progress Bar', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'show_icons',
            [
                'label' => __('Show Icons', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Style', 'mobility-trailblazers'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'stats_background',
            [
                'label' => __('Background Color', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mt-stats-box' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'progress_color',
            [
                'label' => __('Progress Bar Color', 'mobility-trailblazers'),
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
    }
    
    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Check if in editor
        if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            $this->render_editor_preview();
            return;
        }
        
        // Check permissions for personal stats
        if ($settings['stats_type'] === 'personal' && !is_user_logged_in()) {
            echo '<div class="mt-elementor-login-required">';
            echo '<p>' . __('Please log in to view personal statistics.', 'mobility-trailblazers') . '</p>';
            echo '</div>';
            return;
        }
        
        // Render based on type
        echo '<div class="mt-elementor-widget-wrapper">';
        
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
        }
        
        echo '</div>';
    }
    
    /**
     * Render overview statistics
     */
    private function render_overview_stats($settings) {
        // Get stats
        $stats = mt_get_evaluation_statistics();
        
        ?>
        <div class="mt-stats-grid">
            <div class="mt-stats-box">
                <?php if ($settings['show_icons'] === 'yes'): ?>
                    <i class="eicon-user-circle-o"></i>
                <?php endif; ?>
                <div class="mt-stat-number"><?php echo number_format($stats['total_candidates']); ?></div>
                <div class="mt-stat-label"><?php _e('Total Candidates', 'mobility-trailblazers'); ?></div>
            </div>
            
            <div class="mt-stats-box">
                <?php if ($settings['show_icons'] === 'yes'): ?>
                    <i class="eicon-check"></i>
                <?php endif; ?>
                <div class="mt-stat-number"><?php echo number_format($stats['total_evaluations']); ?></div>
                <div class="mt-stat-label"><?php _e('Evaluations Completed', 'mobility-trailblazers'); ?></div>
            </div>
            
            <div class="mt-stats-box">
                <?php if ($settings['show_icons'] === 'yes'): ?>
                    <i class="eicon-user"></i>
                <?php endif; ?>
                <div class="mt-stat-number"><?php echo number_format($stats['active_jury_members']); ?></div>
                <div class="mt-stat-label"><?php _e('Active Jury Members', 'mobility-trailblazers'); ?></div>
            </div>
        </div>
        
        <?php if ($settings['show_progress_bar'] === 'yes'): ?>
            <div class="mt-progress-container">
                <div class="mt-progress-bar">
                    <div class="mt-progress-fill" style="width: <?php echo $stats['completion_percentage']; ?>%"></div>
                </div>
                <div class="mt-progress-text"><?php echo $stats['completion_percentage']; ?>% <?php _e('Complete', 'mobility-trailblazers'); ?></div>
            </div>
        <?php endif;
    }
    
    /**
     * Render personal statistics
     */
    private function render_personal_stats($settings) {
        $user_id = get_current_user_id();
        $evaluations = mt_get_user_evaluation_count($user_id);
        $assignments = mt_get_user_assignments_count($user_id);
        
        echo '<div class="mt-personal-stats">';
        echo '<h4>' . __('Your Progress', 'mobility-trailblazers') . '</h4>';
        echo '<p>' . sprintf(__('Evaluated: %d / %d', 'mobility-trailblazers'), $evaluations, $assignments) . '</p>';
        
        if ($settings['show_progress_bar'] === 'yes' && $assignments > 0) {
            $percentage = round(($evaluations / $assignments) * 100);
            echo '<div class="mt-progress-bar">';
            echo '<div class="mt-progress-fill" style="width: ' . $percentage . '%"></div>';
            echo '</div>';
        }
        echo '</div>';
    }
    
    /**
     * Render leaderboard
     */
    private function render_leaderboard($settings) {
        echo do_shortcode('[mt_voting_results type="top" limit="10"]');
    }
    
    /**
     * Render editor preview
     */
    private function render_editor_preview() {
        $settings = $this->get_settings_for_display();
        ?>
        <div class="mt-elementor-preview">
            <i class="eicon-counter" style="font-size: 48px; color: #999; margin-bottom: 20px;"></i>
            <h3><?php _e('Evaluation Statistics', 'mobility-trailblazers'); ?></h3>
            <p><?php _e('Type:', 'mobility-trailblazers'); ?> <strong><?php echo ucfirst($settings['stats_type']); ?></strong></p>
            <p style="font-size: 12px; margin-top: 10px;">
                <?php _e('Statistics will be displayed here.', 'mobility-trailblazers'); ?>
            </p>
        </div>
        <?php
    }
}