<?php
/**
 * Main Plugin Class
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

namespace MobilityTrailblazers\Core;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Plugin
 *
 * Main plugin class that initializes all components
 */
class MT_Plugin {
    
    /**
     * Plugin instance
     *
     * @var MT_Plugin
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     *
     * @return MT_Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Prevent direct instantiation
    }
    
    /**
     * Initialize the plugin
     *
     * @return void
     */
    public function init() {
        // Check for database upgrades
        MT_Database_Upgrade::run();
        
        // Register post types
        $post_types = new MT_Post_Types();
        $post_types->init();
        
        // Register taxonomies
        $taxonomies = new MT_Taxonomies();
        $taxonomies->init();
        
        // Setup roles and capabilities
        $roles = new MT_Roles();
        add_action('init', [$roles, 'add_capabilities']);
        
        // Initialize admin
        if (is_admin()) {
            $admin = new \MobilityTrailblazers\Admin\MT_Admin();
            $admin->init();
        }
        
        // Initialize AJAX handlers - Always initialize, not just during AJAX requests
        $this->init_ajax_handlers();
        
        // Register shortcodes
        $shortcodes = new MT_Shortcodes();
        $shortcodes->init();
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }
    
    /**
     * Initialize AJAX handlers
     *
     * @return void
     */
    private function init_ajax_handlers() {
        // Evaluation AJAX
        $evaluation_ajax = new \MobilityTrailblazers\Ajax\MT_Evaluation_Ajax();
        $evaluation_ajax->init();
        
        // Assignment AJAX
        $assignment_ajax = new \MobilityTrailblazers\Ajax\MT_Assignment_Ajax();
        $assignment_ajax->init();
        
        // Admin AJAX
        $admin_ajax = new \MobilityTrailblazers\Ajax\MT_Admin_Ajax();
        $admin_ajax->init();
    }
    
    /**
     * Enqueue frontend assets
     *
     * @return void
     */
    public function enqueue_frontend_assets() {
        // Styles
        wp_enqueue_style(
            'mt-frontend',
            MT_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            MT_VERSION
        );
        
        // Scripts
        wp_enqueue_script(
            'mt-frontend',
            MT_PLUGIN_URL . 'assets/js/frontend.js',
            ['jquery'],
            MT_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('mt-frontend', 'mt_ajax', [
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_ajax_nonce')
        ]);
    }
    
    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     * @return void
     */
    public function enqueue_admin_assets($hook) {
        // Check if we're on our plugin pages - be more inclusive
        $is_plugin_page = false;
        
        // Check various patterns
        if (strpos($hook, 'mobility-trailblazers') !== false ||
            strpos($hook, 'mt-') !== false ||
            (isset($_GET['page']) && strpos($_GET['page'], 'mt-') === 0) ||
            (isset($_GET['page']) && strpos($_GET['page'], 'mobility-trailblazers') !== false)) {
            $is_plugin_page = true;
        }
        
        if (!$is_plugin_page) {
            return;
        }
        
        // Styles
        wp_enqueue_style(
            'mt-admin',
            MT_PLUGIN_URL . 'assets/css/admin.css',
            [],
            MT_VERSION
        );
        
        // Scripts
        wp_enqueue_script(
            'mt-admin',
            MT_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'wp-util'],
            MT_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('mt-admin', 'mt_admin', [
            'url' => admin_url('admin-ajax.php'),
            'ajax_url' => admin_url('admin-ajax.php'), // Add both for compatibility
            'nonce' => wp_create_nonce('mt_admin_nonce'),
            'strings' => [
                'confirm_delete' => __('Are you sure you want to delete this?', 'mobility-trailblazers'),
                'saving' => __('Saving...', 'mobility-trailblazers'),
                'saved' => __('Saved!', 'mobility-trailblazers'),
                'error' => __('An error occurred. Please try again.', 'mobility-trailblazers')
            ]
        ]);
    }
} 