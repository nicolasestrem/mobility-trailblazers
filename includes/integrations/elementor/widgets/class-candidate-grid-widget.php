<?php
namespace MobilityTrailblazers\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class CandidateGridWidget extends Widget_Base {
    /**
     * Get widget name
     */
    public function get_name() {
        return 'mt_candidate_grid';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return __('Candidate Grid', 'mobility-trailblazers');
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-posts-grid';
    }

    /**
     * Get widget categories
     */
    public function get_categories() {
        return ['mobility-trailblazers'];
    }

    /**
     * Register widget controls
     */
    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'mobility-trailblazers'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        // Category control
        $this->add_control(
            'category',
            [
                'label' => __('Category', 'mobility-trailblazers'),
                'type' => Controls_Manager::SELECT2,
                'options' => $this->get_category_options(),
                'multiple' => true,
                'label_block' => true,
            ]
        );

        // Columns control
        $this->add_responsive_control(
            'columns',
            [
                'label' => __('Columns', 'mobility-trailblazers'),
                'type' => Controls_Manager::SELECT,
                'default' => '3',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
                'selectors' => [
                    '{{WRAPPER}} .mt-candidate-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
                ],
            ]
        );

        // Limit control
        $this->add_control(
            'limit',
            [
                'label' => __('Number of Candidates', 'mobility-trailblazers'),
                'type' => Controls_Manager::NUMBER,
                'default' => 9,
                'min' => 1,
                'max' => 100,
            ]
        );

        // Show voting control
        $this->add_control(
            'show_voting',
            [
                'label' => __('Show Voting', 'mobility-trailblazers'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'mobility-trailblazers'),
                'label_off' => __('No', 'mobility-trailblazers'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Style', 'mobility-trailblazers'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        // Add style controls here
        $this->add_control(
            'card_background_color',
            [
                'label' => __('Card Background', 'mobility-trailblazers'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mt-candidate-card' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Get categories for the select control
     */
    private function get_category_options() {
        $categories = get_terms([
            'taxonomy' => 'mt_category',
            'hide_empty' => true,
        ]);

        $options = [];
        if (!is_wp_error($categories)) {
            foreach ($categories as $category) {
                $options[$category->slug] = $category->name;
            }
        }

        return $options;
    }

    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();

        // Get candidates
        $args = [
            'post_type' => 'mt_candidate',
            'posts_per_page' => $settings['limit'],
            'post_status' => 'publish',
        ];

        if (!empty($settings['category'])) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'mt_category',
                    'field' => 'slug',
                    'terms' => $settings['category'],
                ],
            ];
        }

        $candidates = get_posts($args);

        // Start output
        echo '<div class="mt-candidate-grid">';

        foreach ($candidates as $candidate) {
            $this->render_candidate_card($candidate, $settings);
        }

        echo '</div>';
    }

    /**
     * Render individual candidate card
     */
    private function render_candidate_card($candidate, $settings) {
        $thumbnail = get_the_post_thumbnail_url($candidate->ID, 'medium');
        $company = get_post_meta($candidate->ID, 'company', true);
        $position = get_post_meta($candidate->ID, 'position', true);
        ?>
        <div class="mt-candidate-card">
            <?php if ($thumbnail) : ?>
                <div class="mt-candidate-image">
                    <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($candidate->post_title); ?>">
                </div>
            <?php endif; ?>
            
            <div class="mt-candidate-content">
                <h3 class="mt-candidate-title"><?php echo esc_html($candidate->post_title); ?></h3>
                <?php if ($company) : ?>
                    <div class="mt-candidate-company"><?php echo esc_html($company); ?></div>
                <?php endif; ?>
                <?php if ($position) : ?>
                    <div class="mt-candidate-position"><?php echo esc_html($position); ?></div>
                <?php endif; ?>
                
                <?php if ($settings['show_voting'] === 'yes') : ?>
                    <div class="mt-candidate-voting">
                        <?php echo do_shortcode('[mt_voting_form candidate_id="' . $candidate->ID . '"]'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
} 