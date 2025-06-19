<?php
/**
 * Elementor Integration - FIXED VERSION
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Elementor_Integration
 * Handles Elementor Pro widgets integration
 */
class MT_Elementor_Integration {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Check if Elementor is active
        if (!did_action('elementor/loaded')) {
            return;
        }
        
        // Register widgets
        add_action('elementor/widgets/widgets_registered', array($this, 'register_widgets'));
        
        // Register widget categories
        add_action('elementor/elements/categories_registered', array($this, 'register_categories'));
        
        // Enqueue widget scripts
        add_action('elementor/frontend/after_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Enqueue editor scripts
        add_action('elementor/editor/after_enqueue_scripts', array($this, 'enqueue_editor_scripts'));
        
        // Register dynamic tags
        add_action('elementor/dynamic_tags/register_tags', array($this, 'register_dynamic_tags'));

        // FIXED: Removed problematic REST API filters
        // These were causing the 403 errors
        
        // Add capabilities
        add_action('admin_init', array($this, 'add_elementor_capabilities'));

        // Register REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        // Handle block editor assets
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
        add_action('enqueue_block_assets', array($this, 'enqueue_block_assets'));

        // Add compatibility for deprecated core/edit-post
        add_action('admin_enqueue_scripts', array($this, 'add_editor_compatibility'));
        
        // FIXED: Add proper REST API authentication handling
        add_filter('rest_authentication_errors', array($this, 'allow_rest_api_for_elementor'), 20);
    }
    
    /**
     * Allow REST API access for Elementor endpoints
     */
    public function allow_rest_api_for_elementor($result) {
        // If there's already an error, return it
        if (!empty($result)) {
            return $result;
        }
        // Check if this is a REST API request
        if (!defined('REST_REQUEST') || !REST_REQUEST) {
            return $result;
        }
        // Get the current route
        $rest_route = $GLOBALS['wp']->query_vars['rest_route'] ?? '';
        // List of routes that Elementor needs
        $elementor_routes = array(
            '/wp/v2/blocks',
            '/wp/v2/global-styles',
            '/wp/v2/global-styles/themes',
            '/wp/v2/global-styles/',
            '/elementor/v1/',
            '/wp/v2/users/me',
            '/wp/v2/types',
            '/wp/v2/taxonomies',
            '/wp-site-health/v1/tests/background-updates',
            '/wp-site-health/v1/tests/loopback-requests',
            '/wp-site-health/v1/directory-sizes'
        );
        // Check if current route is needed by Elementor
        foreach ($elementor_routes as $allowed_route) {
            if (strpos($rest_route, $allowed_route) === 0) {
                // Allow access if user is logged in and can edit posts
                if (is_user_logged_in() && current_user_can('edit_posts')) {
                    return null; // Allow access
                }
            }
        }
        return $result;
    }
    
    /**
     * Register widget categories
     *
     * @param \Elementor\Elements_Manager $elements_manager
     */
    public function register_categories($elements_manager) {
        $elements_manager->add_category(
            'mobility-trailblazers',
            array(
                'title' => __('Mobility Trailblazers', 'mobility-trailblazers'),
                'icon' => 'fa fa-trophy',
            )
        );
    }
    
    /**
     * Register widgets
     *
     * @param \Elementor\Widgets_Manager $widgets_manager
     */
    public function register_widgets($widgets_manager) {
        // Include widget files
        require_once MT_PLUGIN_DIR . 'includes/elementor/widgets/candidates-grid.php';
        require_once MT_PLUGIN_DIR . 'includes/elementor/widgets/jury-dashboard.php';
        require_once MT_PLUGIN_DIR . 'includes/elementor/widgets/voting-form.php';
        require_once MT_PLUGIN_DIR . 'includes/elementor/widgets/registration-form.php';
        require_once MT_PLUGIN_DIR . 'includes/elementor/widgets/evaluation-stats.php';
        require_once MT_PLUGIN_DIR . 'includes/elementor/widgets/winners-display.php';
        require_once MT_PLUGIN_DIR . 'includes/elementor/widgets/jury-members.php';
        require_once MT_PLUGIN_DIR . 'includes/elementor/widgets/candidate-profile.php';
        
        // Register widgets using the new API
        $widgets_manager->register(new \MT_Candidates_Grid_Widget());
        $widgets_manager->register(new \MT_Jury_Dashboard_Widget());
        $widgets_manager->register(new \MT_Voting_Form_Widget());
        $widgets_manager->register(new \MT_Registration_Form_Widget());
        $widgets_manager->register(new \MT_Evaluation_Stats_Widget());
        $widgets_manager->register(new \MT_Winners_Display_Widget());
        $widgets_manager->register(new \MT_Jury_Widget());
        $widgets_manager->register(new \MT_Candidate_Profile_Widget());
    }
    
    /**
     * Enqueue scripts for Elementor editor
     */
    public function enqueue_scripts() {
        // Ensure jQuery is loaded
        wp_enqueue_script('jquery');
        // Add authentication check handling with proper error checking
        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                // Handle authentication check
                if (typeof wp !== "undefined" && wp.authCheck) {
                    $(document).on("heartbeat-tick", function(e, data) {
                        if (data && data["wp-auth-check"]) {
                            var $authCheck = $("#wp-auth-check-wrap");
                            if ($authCheck.length && $authCheck.is(":visible")) {
                                if (typeof wp.authCheck.init === "function") {
                                    wp.authCheck.init();
                                }
                            }
                        }
                    });
                }
            });
        ');

        // Enqueue Elementor compatibility script
        wp_enqueue_script(
            'mt-elementor-compat',
            MT_PLUGIN_URL . 'assets/js/elementor-compat.js',
            array('jquery', 'elementor-frontend', 'elementor-editor'),
            MT_PLUGIN_VERSION,
            true
        );
        
        // Enqueue jury dashboard CSS
        wp_enqueue_style(
            'mt-jury-dashboard',
            MT_PLUGIN_URL . 'assets/jury-dashboard.css',
            array(),
            MT_PLUGIN_VERSION
        );
        
        // Enqueue jury dashboard JavaScript
        wp_enqueue_script(
            'mt-jury-dashboard',
            MT_PLUGIN_URL . 'assets/jury-dashboard.js',
            array('jquery'),
            MT_PLUGIN_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('mt-elementor-compat', 'mt_elementor', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_elementor_nonce'),
            'is_editor' => \Elementor\Plugin::$instance->editor->is_edit_mode(),
            'site_navigation' => array(
                'settings' => array(
                    'enabled' => true,
                    'show_recent_posts' => true,
                    'show_templates' => true
                )
            )
        ));
    }
    
    /**
     * Enqueue editor scripts
     */
    public function enqueue_editor_scripts() {
        wp_enqueue_script(
            'mt-elementor-editor',
            MT_PLUGIN_URL . 'assets/js/elementor-editor.js',
            array('jquery', 'elementor-editor'),
            MT_PLUGIN_VERSION,
            true
        );
        
        wp_localize_script('mt-elementor-editor', 'mt_elementor_editor', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_elementor_editor_nonce'),
        ));
    }
    
    /**
     * Register dynamic tags
     *
     * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager
     */
    public function register_dynamic_tags($dynamic_tags_manager) {
        // Include dynamic tag files
        require_once MT_PLUGIN_DIR . 'includes/elementor/tags/candidate-data.php';
        require_once MT_PLUGIN_DIR . 'includes/elementor/tags/jury-data.php';
        
        // Register tags
        $dynamic_tags_manager->register_tag('MT_Candidate_Data_Tag');
        $dynamic_tags_manager->register_tag('MT_Jury_Data_Tag');
    }
    
    /**
     * Check if Elementor Pro is active
     *
     * @return bool
     */
    public static function is_elementor_pro_active() {
        return defined('ELEMENTOR_PRO_VERSION');
    }
    
    /**
     * Add Elementor capabilities to roles
     */
    public function add_elementor_capabilities() {
        $roles = array('administrator', 'editor', 'mt_award_admin');
        
        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                // Basic WordPress capabilities
                $role->add_cap('edit_posts');
                $role->add_cap('edit_pages');
                $role->add_cap('edit_others_posts');
                $role->add_cap('edit_others_pages');
                $role->add_cap('edit_published_posts');
                $role->add_cap('edit_published_pages');
                $role->add_cap('publish_posts');
                $role->add_cap('publish_pages');
                $role->add_cap('read');
                $role->add_cap('upload_files');
                $role->add_cap('edit_theme_options');
                
                // Elementor specific capabilities
                if (defined('ELEMENTOR_VERSION')) {
                    $role->add_cap('elementor');
                }
            }
        }
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        // Your existing REST route registrations here
    }

    /**
     * Add compatibility for deprecated core/edit-post
     */
    public function add_editor_compatibility() {
        if (!is_admin()) {
            return;
        }

        wp_add_inline_script('wp-data', '
            (function() {
                if (typeof wp !== "undefined" && wp.data && wp.data.select) {
                    // Add compatibility for deprecated core/edit-post
                    const originalSelect = wp.data.select;
                    wp.data.select = function(storeName) {
                        if (storeName === "core/edit-post") {
                            return wp.data.select("core/editor");
                        }
                        return originalSelect.apply(this, arguments);
                    };
                }
            })();
        ');
    }

    /**
     * Enqueue block editor assets
     */
    public function enqueue_block_editor_assets() {
        // Add compatibility script for core/edit-post
        wp_add_inline_script('wp-data', '
            (function() {
                if (typeof wp !== "undefined" && wp.data && wp.data.select) {
                    const originalSelect = wp.data.select;
                    wp.data.select = function(storeName) {
                        if (storeName === "core/edit-post") {
                            return wp.data.select("core/editor");
                        }
                        return originalSelect.apply(this, arguments);
                    };
                }
            })();
        ');
    }

    /**
     * Enqueue block assets
     */
    public function enqueue_block_assets() {
        // Enqueue Elementor admin CSS properly for block editor
        if (is_admin()) {
            wp_enqueue_style(
                'elementor-admin',
                ELEMENTOR_URL . 'assets/css/admin.min.css',
                array(),
                ELEMENTOR_VERSION
            );
        }
    }
}