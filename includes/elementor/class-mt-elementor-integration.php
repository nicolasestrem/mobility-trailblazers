<?php
/**
 * Elementor Integration
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

        // Add REST API permissions
        add_filter('rest_authentication_errors', array($this, 'rest_authentication_errors'));
        add_filter('rest_pre_dispatch', array($this, 'rest_pre_dispatch'), 10, 3);
        add_filter('rest_global_styles_collection_params', array($this, 'add_global_styles_permissions'));
        add_filter('rest_blocks_collection_params', array($this, 'add_blocks_permissions'));
        
        // Add capabilities
        add_action('admin_init', array($this, 'add_elementor_capabilities'));

        // Register REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        // Handle block editor assets
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
        add_action('enqueue_block_assets', array($this, 'enqueue_block_assets'));

        // Add compatibility for deprecated core/edit-post
        add_action('admin_enqueue_scripts', array($this, 'add_editor_compatibility'));
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
        $widgets_manager->register(new \MT_Jury_Members_Widget());
        $widgets_manager->register(new \MT_Candidate_Profile_Widget());
    }
    
    /**
     * Enqueue scripts for Elementor editor
     */
    public function enqueue_scripts() {
        // Ensure jQuery is loaded
        wp_enqueue_script('jquery');
        
        // Add authentication check handling
        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                // Handle authentication check
                if (typeof wp !== "undefined" && wp.authCheck) {
                    var $authCheck = $("#wp-auth-check-wrap");
                    if ($authCheck.length) {
                        wp.authCheck.interval = 180; // Reduce check frequency
                        wp.authCheck.init();
                    }
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
        
        // Localize script
        wp_localize_script('mt-elementor-compat', 'mt_elementor', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_elementor_nonce'),
            'is_editor' => \Elementor\Plugin::$instance->editor->is_edit_mode(),
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
     * Add permissions for global styles
     */
    public function add_global_styles_permissions($params) {
        $params['permission_callback'] = function() {
            return current_user_can('edit_posts');
        };
        return $params;
    }

    /**
     * Add permissions for blocks
     */
    public function add_blocks_permissions($params) {
        $params['permission_callback'] = function() {
            return current_user_can('edit_posts');
        };
        return $params;
    }

    /**
     * Handle REST API authentication errors
     */
    public function rest_authentication_errors($error) {
        if (!empty($error)) {
            return $error;
        }

        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_not_logged_in',
                'You are not currently logged in.',
                array('status' => 401)
            );
        }

        return $error;
    }

    /**
     * Handle REST API pre-dispatch
     */
    public function rest_pre_dispatch($result, $server, $request) {
        if (!empty($result)) {
            return $result;
        }

        $route = $request->get_route();
        if (strpos($route, '/wp/v2/blocks') !== false || 
            strpos($route, '/wp/v2/global-styles') !== false || 
            strpos($route, '/wp/v2/global-styles/themes') !== false) {
            if (!current_user_can('edit_posts')) {
                return new WP_Error(
                    'rest_forbidden',
                    'Sorry, you are not allowed to do that.',
                    array('status' => 403)
                );
            }
        }

        return $result;
    }

    /**
     * Add Elementor capabilities to roles
     */
    public function add_elementor_capabilities() {
        $roles = array('administrator', 'editor');
        
        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                $role->add_cap('edit_posts');
                $role->add_cap('edit_pages');
                $role->add_cap('edit_others_posts');
                $role->add_cap('edit_others_pages');
                $role->add_cap('edit_published_posts');
                $role->add_cap('edit_published_pages');
                $role->add_cap('publish_posts');
                $role->add_cap('publish_pages');
                $role->add_cap('read');
                $role->add_cap('read_private_pages');
                $role->add_cap('read_private_posts');
                $role->add_cap('delete_posts');
                $role->add_cap('delete_pages');
                $role->add_cap('delete_others_posts');
                $role->add_cap('delete_others_pages');
                $role->add_cap('delete_published_posts');
                $role->add_cap('delete_published_pages');
                $role->add_cap('manage_categories');
                $role->add_cap('moderate_comments');
                $role->add_cap('upload_files');
                $role->add_cap('export');
                $role->add_cap('import');
                $role->add_cap('list_users');
                $role->add_cap('edit_theme_options');
                $role->add_cap('elementor');
                $role->add_cap('elementor_edit_document');
                $role->add_cap('elementor_edit_template');
                $role->add_cap('elementor_edit_global');
                $role->add_cap('elementor_edit_kit');
                $role->add_cap('elementor_edit_site_settings');
                $role->add_cap('elementor_edit_theme_settings');
                $role->add_cap('elementor_edit_global_settings');
                $role->add_cap('elementor_edit_kit_settings');
                $role->add_cap('elementor_edit_global_widget');
                $role->add_cap('elementor_edit_global_widget_settings');
                $role->add_cap('elementor_edit_global_widget_template');
                $role->add_cap('elementor_edit_global_widget_kit');
                $role->add_cap('elementor_edit_global_widget_site_settings');
                $role->add_cap('elementor_edit_global_widget_theme_settings');
                $role->add_cap('elementor_edit_global_widget_global_settings');
                $role->add_cap('elementor_edit_global_widget_kit_settings');
            }
        }
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        // Existing routes
        register_rest_route('elementor/v1', '/globals', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_globals'),
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ));

        register_rest_route('elementor/v1', '/kit-elements-defaults', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_kit_elements_defaults'),
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ));

        register_rest_route('elementor/v1', '/global-widget/templates', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_global_widget_templates'),
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ));

        register_rest_route('elementor/v1', '/site-navigation/recent-posts', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_recent_posts'),
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ));
    }

    /**
     * Get globals
     */
    public function get_globals() {
        return rest_ensure_response(array(
            'success' => true,
            'data' => array()
        ));
    }

    /**
     * Get kit elements defaults
     */
    public function get_kit_elements_defaults() {
        return rest_ensure_response(array(
            'success' => true,
            'data' => array()
        ));
    }

    /**
     * Get global widget templates
     */
    public function get_global_widget_templates() {
        return rest_ensure_response(array(
            'success' => true,
            'data' => array()
        ));
    }

    /**
     * Get recent posts
     */
    public function get_recent_posts() {
        $posts = get_posts(array(
            'post_type' => 'any',
            'posts_per_page' => 6,
            'post_status' => 'publish'
        ));

        return rest_ensure_response(array(
            'success' => true,
            'data' => array_map(function($post) {
                return array(
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'type' => $post->post_type,
                    'date' => $post->post_date,
                    'edit_link' => get_edit_post_link($post->ID)
                );
            }, $posts)
        ));
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