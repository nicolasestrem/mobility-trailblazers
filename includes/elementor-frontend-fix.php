<?php
/**
 * Fix Elementor Frontend Configuration
 * 
 * This file ensures elementorFrontendConfig is always available
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fix Elementor frontend config issue
 */
add_action('wp_enqueue_scripts', function() {
    // Only run if Elementor is active
    if (!did_action('elementor/loaded')) {
        return;
    }
    
    // Add inline script before elementor-frontend loads
    wp_add_inline_script('jquery', '
        // Ensure elementorFrontendConfig exists
        if (typeof window.elementorFrontendConfig === "undefined") {
            window.elementorFrontendConfig = {
                environmentMode: {
                    edit: false,
                    wpPreview: false,
                    isScriptDebug: false
                },
                i18n: {
                    shareButtonsTooltip: "Share"
                },
                is_rtl: false,
                breakpoints: {
                    xs: 0,
                    sm: 480,
                    md: 768,
                    lg: 1025,
                    xl: 1440,
                    xxl: 1600
                },
                responsive: {
                    breakpoints: {
                        mobile: {
                            label: "Mobile",
                            value: 767,
                            default_value: 767,
                            direction: "max",
                            is_enabled: true
                        },
                        mobile_extra: {
                            label: "Mobile Extra",
                            value: 880,
                            default_value: 880,
                            direction: "max",
                            is_enabled: false
                        },
                        tablet: {
                            label: "Tablet",
                            value: 1024,
                            default_value: 1024,
                            direction: "max",
                            is_enabled: true
                        },
                        tablet_extra: {
                            label: "Tablet Extra",
                            value: 1200,
                            default_value: 1200,
                            direction: "max",
                            is_enabled: false
                        },
                        laptop: {
                            label: "Laptop",
                            value: 1366,
                            default_value: 1366,
                            direction: "max",
                            is_enabled: false
                        },
                        widescreen: {
                            label: "Widescreen",
                            value: 2400,
                            default_value: 2400,
                            direction: "min",
                            is_enabled: false
                        }
                    }
                },
                version: "3.29.2",
                is_static: false,
                experimentalFeatures: {
                    e_optimized_assets_loading: true,
                    e_optimized_css_loading: true,
                    additional_custom_breakpoints: true,
                    container: true,
                    e_swiper_latest: true,
                    e_nested_atomic_repeaters: true,
                    e_optimized_control_loading: true,
                    e_onboarding: true,
                    e_css_smooth_scroll: true,
                    home_screen: true,
                    "landing-pages": true,
                    e_lazyload: true
                },
                urls: {
                    assets: "' . plugins_url('assets/', 'elementor/elementor.php') . '"
                },
                swiperClass: "swiper",
                settings: {
                    page: [],
                    editorPreferences: []
                },
                kit: {
                    active_breakpoints: ["viewport_mobile", "viewport_tablet"],
                    global_image_lightbox: "yes",
                    lightbox_enable_counter: "yes",
                    lightbox_enable_fullscreen: "yes",
                    lightbox_enable_zoom: "yes",
                    lightbox_enable_share: "yes",
                    lightbox_title_src: "title",
                    lightbox_description_src: "description"
                },
                post: {
                    id: ' . (is_singular() ? get_the_ID() : 0) . ',
                    title: "' . (is_singular() ? get_the_title() : '') . '",
                    excerpt: "",
                    featuredImage: false
                }
            };
        }
    ', 'before');
}, 5);

/**
 * Alternative method using wp_head
 */
add_action('wp_head', function() {
    if (!did_action('elementor/loaded')) {
        return;
    }
    ?>
    <script>
    // Backup method to ensure elementorFrontendConfig exists
    (function() {
        if (typeof window.elementorFrontendConfig === 'undefined') {
            window.elementorFrontendConfig = {
                environmentMode: { edit: false, wpPreview: false, isScriptDebug: false },
                i18n: { shareButtonsTooltip: "Share" },
                is_rtl: false,
                breakpoints: { xs: 0, sm: 480, md: 768, lg: 1025, xl: 1440, xxl: 1600 },
                responsive: { breakpoints: {} },
                version: "3.29.2",
                is_static: false,
                experimentalFeatures: {},
                urls: { assets: "<?php echo plugins_url('assets/', 'elementor/elementor.php'); ?>" },
                settings: { page: [], editorPreferences: [] },
                kit: {},
                post: { id: <?php echo is_singular() ? get_the_ID() : 0; ?> }
            };
        }
    })();
    </script>
    <?php
}, 1);

/**
 * Force Elementor scripts to load in correct order
 */
add_action('wp_enqueue_scripts', function() {
    if (!did_action('elementor/loaded') || is_admin()) {
        return;
    }
    
    // Check if we're on a page with Elementor content
    if (!is_singular()) {
        return;
    }
    
    $post_id = get_the_ID();
    if (!$post_id) {
        return;
    }
    
    // Check if page was built with Elementor
    $document = class_exists('\Elementor\Plugin') ? \Elementor\Plugin::$instance->documents->get($post_id) : null;
    if (!$document || !$document->is_built_with_elementor()) {
        return;
    }
    
    // Ensure jQuery loads first
    wp_enqueue_script('jquery');
    
    // Force elementor-frontend dependencies
    if (wp_script_is('elementor-frontend', 'registered')) {
        wp_enqueue_script('elementor-frontend');
    }
}, 999);