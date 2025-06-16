<?php
/**
 * Elementor Evaluation Stats Widget
 * File: includes/integrations/elementor/widgets/class-evaluation-stats-widget.php
 *
 * @package MobilityTrailblazers
 * @since 1.0.0
 */

namespace MobilityTrailblazers\Integrations\Elementor\Widgets;

use MobilityTrailblazers\Core\Statistics;
use MobilityTrailblazers\Core\JuryMember;
use MobilityTrailblazers\Core\Evaluation;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Evaluation Stats Widget
 */
class EvaluationStatsWidget extends \Elementor\Widget_Base {
    
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
        return ['statistics', 'evaluation', 'stats', 'mobility', 'trailblazers', 'counter'];
    }
    
    /**
     * Register widget controls
     */
    protected function register_controls() {
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
                    'progress' => esc_html__('Evaluation Progress', 'mobility-trailblazers'),
                ],
            ]
        );
        
        $this->add_control(
            'leaderboard_limit',
            [
                'label' => esc_html__('Number of Top Candidates', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 10,
                'min' => 5,
                'max' => 50,
                'condition' => [
                    'stats_type' => 'leaderboard',
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
                'conditions' => [
                    'relation' => 'or',
                    'terms' => [
                        [
                            'name' => 'stats_type',
                            'value' => 'personal',
                        ],
                        [
                            'name' => 'stats_type',
                            'value' => 'progress',
                        ],
                    ],
                ],
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
        
        $this->add_control(
            'stats_layout',
            [
                'label' => esc_html__('Layout', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'grid',
                'options' => [
                    'grid' => esc_html__('Grid', 'mobility-trailblazers'),
                    'list' => esc_html__('List', 'mobility-trailblazers'),
                ],
                'condition' => [
                    'stats_type' => 'overview',
                ],
            ]
        );
        
        $this->add_control(
            'grid_columns',
            [
                'label' => esc_html__('Grid Columns', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '4',
                'options' => [
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
                'condition' => [
                    'stats_type' => 'overview',
                    'stats_layout' => 'grid',
                ],
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
                    '{{WRAPPER}} .mt-stat-item' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'stats_border_color',
            [
                'label' => esc_html__('Border Color', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mt-stats-box' => 'border-color: {{VALUE}};',
                    '{{WRAPPER}} .mt-stat-item' => 'border-left-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'number_color',
            [
                'label' => esc_html__('Number Color', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mt-stat-number' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'text_color',
            [
                'label' => esc_html__('Text Color', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mt-stat-label' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .mt-stat-description' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'icon_color',
            [
                'label' => esc_html__('Icon Color', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mt-stat-icon i' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'show_icons' => 'yes',
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
        
        $this->add_control(
            'progress_background',
            [
                'label' => esc_html__('Progress Bar Background', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#e2e8f0',
                'selectors' => [
                    '{{WRAPPER}} .mt-progress-bar' => 'background-color: {{VALUE}};',
                ],
                'condition' => [
                    'show_progress_bar' => 'yes',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'number_typography',
                'label' => esc_html__('Number Typography', 'mobility-trailblazers'),
                'selector' => '{{WRAPPER}} .mt-stat-number',
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'label_typography',
                'label' => esc_html__('Label Typography', 'mobility-trailblazers'),
                'selector' => '{{WRAPPER}} .mt-stat-label',
            ]
        );
        
        $this->add_control(
            'box_padding',
            [
                'label' => esc_html__('Box Padding', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .mt-stats-box, {{WRAPPER}} .mt-stat-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'box_border_radius',
            [
                'label' => esc_html__('Border Radius', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .mt-stats-box, {{WRAPPER}} .mt-stat-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
            case 'progress':
                $this->render_progress_stats($settings);
                break;
        }
        
        echo '</div>';
        
        // Add widget styles
        $this->render_widget_styles($settings);
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
     * Render overview statistics
     */
    private function render_overview_stats($settings) {
        $statistics = new Statistics();
        $stats = $statistics->get_overview_stats();
        
        $layout_class = $settings['stats_layout'] === 'grid' ? 'mt-stats-grid' : 'mt-stats-list';
        $columns_class = $settings['stats_layout'] === 'grid' ? 'columns-' . $settings['grid_columns'] : '';
        
        ?>
        <div class="<?php echo esc_attr($layout_class . ' ' . $columns_class); ?>">
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
            
            <div class="mt-stats-box">
                <?php if ($settings['show_icons'] === 'yes'): ?>
                    <div class="mt-stat-icon">
                        <i class="eicon-sync" aria-hidden="true"></i>
                    </div>
                <?php endif; ?>
                <div class="mt-stat-number"><?php echo $stats['completion_rate']; ?>%</div>
                <div class="mt-stat-label"><?php echo esc_html__('Completion Rate', 'mobility-trailblazers'); ?></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render personal statistics
     */
    private function render_personal_stats($settings) {
        $user_id = get_current_user_id();
        $jury_member = new JuryMember();
        $statistics = new Statistics();
        
        if (!$jury_member->is_jury_member($user_id)) {
            echo '<p>' . esc_html__('You are not registered as a jury member.', 'mobility-trailblazers') . '</p>';
            return;
        }
        
        $personal_stats = $statistics->get_jury_member_stats($user_id);
        
        ?>
        <div class="mt-personal-stats">
            <h4><?php echo esc_html__('Your Evaluation Progress', 'mobility-trailblazers'); ?></h4>
            
            <div class="mt-stats-summary">
                <div class="mt-stat-item">
                    <span class="mt-stat-label"><?php echo esc_html__('Assigned:', 'mobility-trailblazers'); ?></span>
                    <span class="mt-stat-value"><?php echo $personal_stats['assigned']; ?></span>
                </div>
                <div class="mt-stat-item">
                    <span class="mt-stat-label"><?php echo esc_html__('Evaluated:', 'mobility-trailblazers'); ?></span>
                    <span class="mt-stat-value"><?php echo $personal_stats['evaluated']; ?></span>
                </div>
                <div class="mt-stat-item">
                    <span class="mt-stat-label"><?php echo esc_html__('Pending:', 'mobility-trailblazers'); ?></span>
                    <span class="mt-stat-value"><?php echo $personal_stats['pending']; ?></span>
                </div>
            </div>
            
            <?php if ($settings['show_progress_bar'] === 'yes' && $personal_stats['assigned'] > 0): ?>
                <div class="mt-progress-container">
                    <div class="mt-progress-bar" role="progressbar" 
                         aria-valuenow="<?php echo $personal_stats['completion_percentage']; ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                        <div class="mt-progress-fill" style="width: <?php echo $personal_stats['completion_percentage']; ?>%"></div>
                    </div>
                    <div class="mt-progress-text">
                        <?php echo $personal_stats['completion_percentage']; ?>% <?php echo esc_html__('Complete', 'mobility-trailblazers'); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render leaderboard
     */
    private function render_leaderboard($settings) {
        $statistics = new Statistics();
        $limit = intval($settings['leaderboard_limit']);
        $results = $statistics->get_top_candidates($limit);
        
        if (empty($results)) {
            echo '<p>' . esc_html__('No evaluation results available yet.', 'mobility-trailblazers') . '</p>';
            return;
        }
        
        ?>
        <div class="mt-leaderboard">
            <h4><?php echo esc_html__('Top Candidates', 'mobility-trailblazers'); ?></h4>
            <ol class="mt-leaderboard-list">
                <?php foreach ($results as $index => $result): ?>
                <li class="mt-leaderboard-item">
                    <span class="mt-rank"><?php echo $index + 1; ?></span>
                    <span class="mt-candidate-name"><?php echo esc_html($result->candidate_name); ?></span>
                    <span class="mt-candidate-score">
                        <?php echo number_format($result->avg_score, 1); ?>/50
                        <small>(<?php echo $result->evaluation_count; ?> <?php echo esc_html__('evaluations', 'mobility-trailblazers'); ?>)</small>
                    </span>
                </li>
                <?php endforeach; ?>
            </ol>
        </div>
        <?php
    }
    
    /**
     * Render progress statistics
     */
    private function render_progress_stats($settings) {
        $statistics = new Statistics();
        $progress = $statistics->get_evaluation_progress();
        
        ?>
        <div class="mt-progress-stats">
            <h4><?php echo esc_html__('Evaluation Progress by Category', 'mobility-trailblazers'); ?></h4>
            
            <?php foreach ($progress as $category => $data): ?>
            <div class="mt-category-progress">
                <h5><?php echo esc_html($category); ?></h5>
                <div class="mt-progress-info">
                    <span><?php echo sprintf(
                        esc_html__('%d of %d evaluated', 'mobility-trailblazers'),
                        $data['evaluated'],
                        $data['total']
                    ); ?></span>
                    <span class="mt-percentage"><?php echo $data['percentage']; ?>%</span>
                </div>
                <?php if ($settings['show_progress_bar'] === 'yes'): ?>
                <div class="mt-progress-bar">
                    <div class="mt-progress-fill" style="width: <?php echo $data['percentage']; ?>%"></div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
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
            <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="mt-login-button">
                <?php echo esc_html__('Login', 'mobility-trailblazers'); ?>
            </a>
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
            
            <?php if ($settings['stats_type'] === 'overview'): ?>
            <div class="mt-preview-demo">
                <div class="mt-demo-stat">
                    <span class="mt-demo-number">150</span>
                    <span class="mt-demo-label"><?php echo esc_html__('Total Candidates', 'mobility-trailblazers'); ?></span>
                </div>
                <div class="mt-demo-stat">
                    <span class="mt-demo-number">450</span>
                    <span class="mt-demo-label"><?php echo esc_html__('Evaluations', 'mobility-trailblazers'); ?></span>
                </div>
                <div class="mt-demo-stat">
                    <span class="mt-demo-number">25</span>
                    <span class="mt-demo-label"><?php echo esc_html__('Jury Members', 'mobility-trailblazers'); ?></span>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render widget styles
     */
    private function render_widget_styles($settings) {
        ?>
        <style>
        /* Statistics Widget Styles */
        .mt-stats-widget .mt-stats-grid {
            display: grid;
            gap: 20px;
            margin: 20px 0;
        }
        
        .mt-stats-widget .mt-stats-grid.columns-2 {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .mt-stats-widget .mt-stats-grid.columns-3 {
            grid-template-columns: repeat(3, 1fr);
        }
        
        .mt-stats-widget .mt-stats-grid.columns-4 {
            grid-template-columns: repeat(4, 1fr);
        }
        
        .mt-stats-widget .mt-stats-box {
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-left: 4px solid #2c5282;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .mt-stats-widget .mt-stats-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        .mt-stats-widget .mt-stat-icon i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            display: block;
        }
        
        .mt-stats-widget .mt-stat-number {
            font-size: 2.5rem;
            font-weight: 700;
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
        
        /* Personal Stats */
        .mt-stats-widget .mt-personal-stats {
            background: #f7fafc;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            text-align: center;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .mt-stats-widget .mt-stats-summary {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
        }
        
        .mt-stats-widget .mt-stat-item {
            text-align: center;
        }
        
        .mt-stats-widget .mt-stat-item .mt-stat-value {
            display: block;
            font-size: 2rem;
            font-weight: 700;
            color: #2c5282;
            margin-top: 5px;
        }
        
        /* Progress Bar */
        .mt-stats-widget .mt-progress-container {
            margin-top: 20px;
        }
        
        .mt-stats-widget .mt-progress-bar {
            width: 100%;
            height: 20px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 10px;
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
            text-align: center;
        }
        
        /* Leaderboard */
        .mt-stats-widget .mt-leaderboard-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .mt-stats-widget .mt-leaderboard-item {
            display: flex;
            align-items: center;
            padding: 15px;
            margin: 10px 0;
            background: #f8fafc;
            border-radius: 8px;
            border-left: 3px solid #2c5282;
        }
        
        .mt-stats-widget .mt-rank {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c5282;
            width: 40px;
            text-align: center;
        }
        
        .mt-stats-widget .mt-candidate-name {
            flex: 1;
            font-weight: 600;
            color: #2d3748;
            margin: 0 15px;
        }
        
        .mt-stats-widget .mt-candidate-score {
            font-weight: 700;
            color: #2c5282;
            background: #e6fffa;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.9rem;
        }
        
        .mt-stats-widget .mt-candidate-score small {
            font-weight: 400;
            color: #718096;
            display: block;
            font-size: 0.75rem;
        }
        
        /* Progress Stats */
        .mt-stats-widget .mt-category-progress {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
        }
        
        .mt-stats-widget .mt-category-progress h5 {
            margin: 0 0 10px;
            color: #2d3748;
        }
        
        .mt-stats-widget .mt-progress-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: #718096;
        }
        
        .mt-stats-widget .mt-percentage {
            font-weight: 700;
            color: #2c5282;
        }
        
        /* Editor Preview */
        .mt-elementor-preview .mt-preview-demo {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            justify-content: center;
        }
        
        .mt-elementor-preview .mt-demo-stat {
            text-align: center;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 8px;
        }
        
        .mt-elementor-preview .mt-demo-number {
            display: block;
            font-size: 24px;
            font-weight: 700;
            color: #2c5282;
            margin-bottom: 5px;
        }
        
        .mt-elementor-preview .mt-demo-label {
            font-size: 12px;
            color: #666;
        }
        
        /* Login Required */
        .mt-elementor-login-required {
            text-align: center;
            padding: 40px 20px;
            background: #f0f8ff;
            border: 1px solid #bee3f8;
            border-radius: 8px;
        }
        
        .mt-login-icon i {
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .mt-stats-widget .mt-stats-grid {
                grid-template-columns: 1fr;
            }
            
            .mt-stats-widget .mt-stats-summary {
                flex-direction: column;
                gap: 15px;
            }
        }
        </style>
        <?php
    }
}