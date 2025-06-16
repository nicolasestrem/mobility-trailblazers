<?php
namespace MobilityTrailblazers;

class Frontend {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Initialize frontend functionality
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('wp_head', array($this, 'add_frontend_meta'));
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        // Frontend styles
        wp_enqueue_style(
            'mt-frontend-css',
            MT_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            MT_PLUGIN_VERSION
        );

        // Frontend scripts
        wp_enqueue_script(
            'mt-frontend-js',
            MT_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            MT_PLUGIN_VERSION,
            true
        );

        // Localize script
        wp_localize_script('mt-frontend-js', 'mtFrontend', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_frontend_nonce')
        ));
    }

    public function add_frontend_meta() {
        // Add frontend meta tags
    }
} 