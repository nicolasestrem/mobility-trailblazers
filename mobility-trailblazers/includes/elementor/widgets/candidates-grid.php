<?php
/**
 * Candidates Grid Elementor Widget
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Candidates_Grid_Widget
 */
class MT_Candidates_Grid_Widget extends \Elementor\Widget_Base {
    
    /**
     * Get widget name
     *
     * @return string Widget name
     */
    public function get_name() {
        return 'mt-candidates-grid';
    }
    
    /**
     * Get widget title
     *
     * @return string Widget title
     */
    public function get_title() {
        return __('Candidates Grid', 'mobility-trailblazers');
    }
    
    /**
     * Get widget icon
     *
     * @return string Widget icon
     */
    public function get_icon() {
        return 'eicon-posts-grid';
    }
    
    /**
     * Get widget categories
     *
     * @return array Widget categories
     */
    public function get_categories() {
        return array('mobility-trailblazers');
    }
    
    /**
     * Get widget keywords
     *
     * @return array Widget keywords
     */
    public function get_keywords() {
        return array('candidates', 'grid', 'mobility', 'trailblazers', 'awards');
    }
    
    /**
     * Register widget controls
     */
    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __('Content', 'mobility-trailblazers'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'category',
            array(
                'label' => __('Category', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->get_category_options(),
                'default' => array(),
            )
        );
        
        $this->add_control(
            'status',
            array(
                'label' => __('Status', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'approved',
                'options' => array(
                    'all' => __('All', 'mobility-trailblazers'),
                    'approved' => __('Approved', 'mobility-trailblazers'),
                    'pending' => __('Pending', 'mobility-trailblazers'),
                    'winner' => __('Winners', 'mobility-trailblazers'),
                ),
            )
        );
        
        $this->add_control(
            'limit',
            array(
                'label' => __('Number of Candidates', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 12,
                'min' => 1,
                'max' => 100,
            )
        );
        
        $this->add_control(
            'orderby',
            array(
                'label' => __('Order By', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'title',
                'options' => array(
                    'title' => __('Name', 'mobility-trailblazers'),
                    'date' => __('Date', 'mobility-trailblazers'),
                    'menu_order' => __('Menu Order', 'mobility-trailblazers'),
                    'rand' => __('Random', 'mobility-trailblazers'),
                    'meta_value_num' => __('Score', 'mobility-trailblazers'),
                ),
            )
        );
        
        $this->add_control(
            'order',
            array(
                'label' => __('Order', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'ASC',
                'options' => array(
                    'ASC' => __('Ascending', 'mobility-trailblazers'),
                    'DESC' => __('Descending', 'mobility-trailblazers'),
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Layout Section
        $this->start_controls_section(
            'layout_section',
            array(
                'label' => __('Layout', 'mobility-trailblazers'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_responsive_control(
            'columns',
            array(
                'label' => __('Columns', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '3',
                'tablet_default' => '2',
                'mobile_default' => '1',
                'options' => array(
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .mt-candidates-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
                ),
            )
        );
        
        $this->add_control(
            'show_filters',
            array(
                'label' => __('Show Filters', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'mobility-trailblazers'),
                'label_off' => __('No', 'mobility-trailblazers'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'show_pagination',
            array(
                'label' => __('Show Pagination', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'mobility-trailblazers'),
                'label_off' => __('No', 'mobility-trailblazers'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );
        
        $this->end_controls_section();
        
        // Style Section - Card
        $this->start_controls_section(
            'card_style_section',
            array(
                'label' => __('Card', 'mobility-trailblazers'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            array(
                'name' => 'card_background',
                'label' => __('Background', 'mobility-trailblazers'),
                'types' => array('classic', 'gradient'),
                'selector' => '{{WRAPPER}} .mt-candidate-card',
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name' => 'card_border',
                'label' => __('Border', 'mobility-trailblazers'),
                'selector' => '{{WRAPPER}} .mt-candidate-card',
            )
        );
        
        $this->add_control(
            'card_border_radius',
            array(
                'label' => __('Border Radius', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .mt-candidate-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            array(
                'name' => 'card_box_shadow',
                'label' => __('Box Shadow', 'mobility-trailblazers'),
                'selector' => '{{WRAPPER}} .mt-candidate-card',
            )
        );
        
        $this->add_responsive_control(
            'card_padding',
            array(
                'label' => __('Padding', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .mt-candidate-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Style Section - Typography
        $this->start_controls_section(
            'typography_section',
            array(
                'label' => __('Typography', 'mobility-trailblazers'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'name_typography',
                'label' => __('Name Typography', 'mobility-trailblazers'),
                'selector' => '{{WRAPPER}} .mt-candidate-name',
            )
        );
        
        $this->add_control(
            'name_color',
            array(
                'label' => __('Name Color', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .mt-candidate-name' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'company_typography',
                'label' => __('Company Typography', 'mobility-trailblazers'),
                'selector' => '{{WRAPPER}} .mt-candidate-company',
            )
        );
        
        $this->add_control(
            'company_color',
            array(
                'label' => __('Company Color', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .mt-candidate-company' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->end_controls_section();
    }
    
    /**
     * Get category options
     *
     * @return array
     */
    private function get_category_options() {
        $options = array();
        $categories = get_terms(array(
            'taxonomy' => 'mt_category',
            'hide_empty' => false,
        ));
        
        foreach ($categories as $category) {
            $options[$category->slug] = $category->name;
        }
        
        return $options;
    }
    
    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Build shortcode attributes
        $atts = array(
            'category' => is_array($settings['category']) ? implode(',', $settings['category']) : '',
            'status' => $settings['status'],
            'limit' => $settings['limit'],
            'columns' => $settings['columns'],
            'orderby' => $settings['orderby'],
            'order' => $settings['order'],
            'show_filters' => $settings['show_filters'],
            'show_pagination' => $settings['show_pagination'],
        );
        
        // Generate shortcode
        $shortcode = '[mt_candidates';
        foreach ($atts as $key => $value) {
            if (!empty($value)) {
                $shortcode .= ' ' . $key . '="' . esc_attr($value) . '"';
            }
        }
        $shortcode .= ']';
        
        // Output shortcode
        echo do_shortcode($shortcode);
    }
    
    /**
     * Render widget output in the editor
     */
    protected function content_template() {
        ?>
        <#
        var shortcode = '[mt_candidates';
        
        if (settings.category && settings.category.length) {
            shortcode += ' category="' + settings.category.join(',') + '"';
        }
        if (settings.status) {
            shortcode += ' status="' + settings.status + '"';
        }
        if (settings.limit) {
            shortcode += ' limit="' + settings.limit + '"';
        }
        if (settings.columns) {
            shortcode += ' columns="' + settings.columns + '"';
        }
        if (settings.orderby) {
            shortcode += ' orderby="' + settings.orderby + '"';
        }
        if (settings.order) {
            shortcode += ' order="' + settings.order + '"';
        }
        if (settings.show_filters) {
            shortcode += ' show_filters="' + settings.show_filters + '"';
        }
        if (settings.show_pagination) {
            shortcode += ' show_pagination="' + settings.show_pagination + '"';
        }
        
        shortcode += ']';
        #>
        <div class="mt-elementor-preview">
            <p><?php _e('Candidates Grid will be displayed here.', 'mobility-trailblazers'); ?></p>
            <p><code>{{{ shortcode }}}</code></p>
        </div>
        <?php
    }
} 