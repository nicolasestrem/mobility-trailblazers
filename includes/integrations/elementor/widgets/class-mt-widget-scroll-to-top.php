<?php
/**
 * Mobility Trailblazers - Scroll to Top Elementor Widget
 *
 * @package MobilityTrailblazers
 * @since 2.5.30
 */

namespace MobilityTrailblazers\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Scroll to Top Widget
 */
class MT_Widget_Scroll_To_Top extends Widget_Base {

    /**
     * Get widget name
     */
    public function get_name() {
        return 'mt-scroll-to-top';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return __('MT Scroll to Top', 'mobility-trailblazers');
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-arrow-up';
    }

    /**
     * Get widget categories
     */
    public function get_categories() {
        return ['mobility-trailblazers'];
    }

    /**
     * Get widget keywords
     */
    public function get_keywords() {
        return ['scroll', 'top', 'button', 'back to top', 'mobility'];
    }

    /**
     * Register widget controls
     */
    protected function register_controls() {
        
        // Content Tab
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('Settings', 'mobility-trailblazers'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'enable_button',
            [
                'label' => __('Enable Scroll to Top', 'mobility-trailblazers'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'mobility-trailblazers'),
                'label_off' => __('No', 'mobility-trailblazers'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'position',
            [
                'label' => __('Position', 'mobility-trailblazers'),
                'type' => Controls_Manager::SELECT,
                'default' => 'bottom-right',
                'options' => [
                    'bottom-right' => __('Bottom Right', 'mobility-trailblazers'),
                    'bottom-left' => __('Bottom Left', 'mobility-trailblazers'),
                    'top-right' => __('Top Right', 'mobility-trailblazers'),
                    'top-left' => __('Top Left', 'mobility-trailblazers'),
                ],
                'condition' => [
                    'enable_button' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offset_x',
            [
                'label' => __('Horizontal Offset', 'mobility-trailblazers'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 20,
                ],
                'condition' => [
                    'enable_button' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'offset_y',
            [
                'label' => __('Vertical Offset', 'mobility-trailblazers'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 20,
                ],
                'condition' => [
                    'enable_button' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'scroll_threshold',
            [
                'label' => __('Show After Scroll (px)', 'mobility-trailblazers'),
                'description' => __('Button appears after scrolling this many pixels', 'mobility-trailblazers'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 100,
                        'max' => 1000,
                        'step' => 50,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 300,
                ],
                'condition' => [
                    'enable_button' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Tab - Button
        $this->start_controls_section(
            'section_button_style',
            [
                'label' => __('Button Style', 'mobility-trailblazers'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'enable_button' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'button_size',
            [
                'label' => __('Size', 'mobility-trailblazers'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 40,
                        'max' => 120,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 60,
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'button_background',
                'label' => __('Background', 'mobility-trailblazers'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .mt-scroll-to-top-widget',
                'fields_options' => [
                    'background' => [
                        'default' => 'gradient',
                    ],
                    'color' => [
                        'default' => '#667eea',
                    ],
                    'color_b' => [
                        'default' => '#764ba2',
                    ],
                    'gradient_type' => [
                        'default' => 'linear',
                    ],
                    'gradient_angle' => [
                        'default' => [
                            'unit' => 'deg',
                            'size' => 135,
                        ],
                    ],
                ],
            ]
        );

        $this->add_control(
            'button_color',
            [
                'label' => __('Icon Color', 'mobility-trailblazers'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .mt-scroll-to-top-widget' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .mt-scroll-to-top-widget svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'button_border',
                'label' => __('Border', 'mobility-trailblazers'),
                'selector' => '{{WRAPPER}} .mt-scroll-to-top-widget',
            ]
        );

        $this->add_control(
            'button_border_radius',
            [
                'label' => __('Border Radius', 'mobility-trailblazers'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => 12,
                    'right' => 12,
                    'bottom' => 12,
                    'left' => 12,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .mt-scroll-to-top-widget' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_box_shadow',
                'label' => __('Box Shadow', 'mobility-trailblazers'),
                'selector' => '{{WRAPPER}} .mt-scroll-to-top-widget',
                'fields_options' => [
                    'box_shadow_type' => [
                        'default' => 'yes',
                    ],
                    'box_shadow' => [
                        'default' => [
                            'horizontal' => 0,
                            'vertical' => 8,
                            'blur' => 24,
                            'spread' => 0,
                            'color' => 'rgba(102, 126, 234, 0.25)',
                        ],
                    ],
                ],
            ]
        );

        $this->end_controls_section();

        // Style Tab - Icon
        $this->start_controls_section(
            'section_icon_style',
            [
                'label' => __('Icon Style', 'mobility-trailblazers'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'enable_button' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'icon_size',
            [
                'label' => __('Icon Size', 'mobility-trailblazers'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 16,
                        'max' => 50,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 24,
                ],
                'selectors' => [
                    '{{WRAPPER}} .mt-scroll-to-top-widget svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Tab - Animation
        $this->start_controls_section(
            'section_animation_style',
            [
                'label' => __('Animation', 'mobility-trailblazers'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'enable_button' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'animation_duration',
            [
                'label' => __('Animation Duration (ms)', 'mobility-trailblazers'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 100,
                        'max' => 1000,
                        'step' => 50,
                    ],
                ],
                'default' => [
                    'size' => 300,
                ],
            ]
        );

        $this->add_control(
            'hover_animation',
            [
                'label' => __('Hover Animation', 'mobility-trailblazers'),
                'type' => Controls_Manager::SELECT,
                'default' => 'lift',
                'options' => [
                    'none' => __('None', 'mobility-trailblazers'),
                    'lift' => __('Lift Up', 'mobility-trailblazers'),
                    'scale' => __('Scale', 'mobility-trailblazers'),
                    'rotate' => __('Rotate', 'mobility-trailblazers'),
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output on the frontend
     */
    protected function render() {
        $settings = $this->get_settings_for_display();

        if ($settings['enable_button'] !== 'yes') {
            return;
        }

        // Get position settings
        $position = $settings['position'];
        $offset_x = $settings['offset_x']['size'] ?? 20;
        $offset_y = $settings['offset_y']['size'] ?? 20;
        $scroll_threshold = $settings['scroll_threshold']['size'] ?? 300;
        $button_size = $settings['button_size']['size'] ?? 60;
        $icon_size = $settings['icon_size']['size'] ?? 24;
        $animation_duration = $settings['animation_duration']['size'] ?? 300;
        $hover_animation = $settings['hover_animation'] ?? 'lift';

        // Calculate position styles
        $position_styles = $this->get_position_styles($position, $offset_x, $offset_y);

        // Generate unique ID for this widget instance
        $widget_id = 'mt-scroll-to-top-' . $this->get_id();

        ?>
        <div class="mt-scroll-to-top-container" data-widget-id="<?php echo esc_attr($widget_id); ?>">
            <style>
                #<?php echo esc_attr($widget_id); ?> {
                    position: fixed !important;
                    <?php echo $position_styles; ?>
                    width: <?php echo esc_attr($button_size); ?>px !important;
                    height: <?php echo esc_attr($button_size); ?>px !important;
                    z-index: 2147483647 !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    opacity: 0 !important;
                    visibility: hidden !important;
                    transform: translateY(20px) scale(0.8) !important;
                    transition: all <?php echo esc_attr($animation_duration); ?>ms cubic-bezier(0.25, 0.46, 0.45, 0.94) !important;
                    cursor: pointer !important;
                    border: none !important;
                    user-select: none !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    box-sizing: border-box !important;
                }

                #<?php echo esc_attr($widget_id); ?>.show {
                    opacity: 1 !important;
                    visibility: visible !important;
                    transform: translateY(0) scale(1) !important;
                }

                <?php if ($hover_animation === 'lift'): ?>
                #<?php echo esc_attr($widget_id); ?>:hover {
                    transform: translateY(-3px) scale(1.05) !important;
                }
                <?php elseif ($hover_animation === 'scale'): ?>
                #<?php echo esc_attr($widget_id); ?>:hover {
                    transform: scale(1.1) !important;
                }
                <?php elseif ($hover_animation === 'rotate'): ?>
                #<?php echo esc_attr($widget_id); ?>:hover {
                    transform: rotate(180deg) !important;
                }
                <?php endif; ?>

                #<?php echo esc_attr($widget_id); ?> svg {
                    width: <?php echo esc_attr($icon_size); ?>px !important;
                    height: <?php echo esc_attr($icon_size); ?>px !important;
                    fill: currentColor !important;
                    transition: transform 0.2s ease !important;
                }
            </style>

            <button id="<?php echo esc_attr($widget_id); ?>" class="mt-scroll-to-top-widget" type="button" aria-label="<?php esc_attr_e('Scroll to top of page', 'mobility-trailblazers'); ?>" title="<?php esc_attr_e('Scroll to top', 'mobility-trailblazers'); ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M7.41 15.41L12 10.83l4.59 4.58L18 14l-6-6-6 6z"/>
                </svg>
                <span class="sr-only"><?php esc_html_e('Scroll to top of page', 'mobility-trailblazers'); ?></span>
            </button>
        </div>

        <script>
        (function() {
            const button = document.getElementById('<?php echo esc_js($widget_id); ?>');
            const threshold = <?php echo esc_js($scroll_threshold); ?>;
            let isVisible = false;

            function updateVisibility() {
                const shouldShow = window.scrollY > threshold;
                
                if (shouldShow && !isVisible) {
                    button.classList.add('show');
                    button.setAttribute('aria-hidden', 'false');
                    isVisible = true;
                } else if (!shouldShow && isVisible) {
                    button.classList.remove('show');
                    button.setAttribute('aria-hidden', 'true');
                    isVisible = false;
                }
            }

            function scrollToTop() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }

            // Event listeners
            window.addEventListener('scroll', updateVisibility);
            button.addEventListener('click', scrollToTop);

            // Initial check
            updateVisibility();
        })();
        </script>
        <?php
    }

    /**
     * Get position styles based on position setting
     */
    private function get_position_styles($position, $offset_x, $offset_y) {
        switch ($position) {
            case 'bottom-left':
                return "bottom: {$offset_y}px !important; left: {$offset_x}px !important;";
            case 'top-right':
                return "top: {$offset_y}px !important; right: {$offset_x}px !important;";
            case 'top-left':
                return "top: {$offset_y}px !important; left: {$offset_x}px !important;";
            case 'bottom-right':
            default:
                return "bottom: {$offset_y}px !important; right: {$offset_x}px !important;";
        }
    }

    /**
     * Render widget output in the editor
     */
    protected function content_template() {
        ?>
        <# 
        if (settings.enable_button !== 'yes') {
            return;
        }

        var position = settings.position || 'bottom-right';
        var offset_x = settings.offset_x.size || 20;
        var offset_y = settings.offset_y.size || 20;
        var button_size = settings.button_size.size || 60;
        var icon_size = settings.icon_size.size || 24;

        var positionStyle = '';
        switch(position) {
            case 'bottom-left':
                positionStyle = 'bottom: ' + offset_y + 'px; left: ' + offset_x + 'px;';
                break;
            case 'top-right':
                positionStyle = 'top: ' + offset_y + 'px; right: ' + offset_x + 'px;';
                break;
            case 'top-left':
                positionStyle = 'top: ' + offset_y + 'px; left: ' + offset_x + 'px;';
                break;
            default:
                positionStyle = 'bottom: ' + offset_y + 'px; right: ' + offset_x + 'px;';
        }
        #>
        <div class="mt-scroll-to-top-container">
            <div class="mt-scroll-to-top-widget show" style="position: relative; {{{ positionStyle }}} width: {{{ button_size }}}px; height: {{{ button_size }}}px; display: flex; align-items: center; justify-content: center; border-radius: 12px; cursor: pointer;">
                <svg style="width: {{{ icon_size }}}px; height: {{{ icon_size }}}px; fill: currentColor;" viewBox="0 0 24 24">
                    <path d="M7.41 15.41L12 10.83l4.59 4.58L18 14l-6-6-6 6z"/>
                </svg>
            </div>
            <div style="text-align: center; margin-top: 10px; font-size: 12px; color: #666;">
                <?php esc_html_e('Scroll to Top Button (Preview)', 'mobility-trailblazers'); ?>
            </div>
        </div>
        <?php
    }
}