<?php
/**
 * Admin Menus functionality for Mobility Trailblazers
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Admin_Menus
 * 
 * Handles admin menu creation and management
 */
class MT_Admin_Menus {
    
    /**
     * Initialize the admin menus
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menus'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Add admin menus
     */
    public function add_admin_menus() {
        // Main menu page
        add_menu_page(
            __('Mobility Trailblazers', 'mobility-trailblazers'),
            __('Mobility Trailblazers', 'mobility-trailblazers'),
            'manage_options',
            'mobility-trailblazers',
            array($this, 'render_main_page'),
            'dashicons-awards',
            30
        );
        
        // Submissions submenu
        add_submenu_page(
            'mobility-trailblazers',
            __('Submissions', 'mobility-trailblazers'),
            __('Submissions', 'mobility-trailblazers'),
            'manage_options',
            'mt-submissions',
            array($this, 'render_submissions_page')
        );
        
        // Voting submenu
        add_submenu_page(
            'mobility-trailblazers',
            __('Voting', 'mobility-trailblazers'),
            __('Voting', 'mobility-trailblazers'),
            'manage_options',
            'mt-voting',
            array($this, 'render_voting_page')
        );
        
        // Reports submenu
        add_submenu_page(
            'mobility-trailblazers',
            __('Reports', 'mobility-trailblazers'),
            __('Reports', 'mobility-trailblazers'),
            'manage_options',
            'mt-reports',
            array($this, 'render_reports_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'mobility-trailblazers',
            __('Settings', 'mobility-trailblazers'),
            __('Settings', 'mobility-trailblazers'),
            'manage_options',
            'mt-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('mt_settings_group', 'mt_settings');
        
        add_settings_section(
            'mt_general_section',
            __('General Settings', 'mobility-trailblazers'),
            array($this, 'render_general_section'),
            'mt-settings'
        );
        
        add_settings_field(
            'voting_enabled',
            __('Enable Voting', 'mobility-trailblazers'),
            array($this, 'render_voting_enabled_field'),
            'mt-settings',
            'mt_general_section'
        );
    }
    
    /**
     * Render main admin page
     */
    public function render_main_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Mobility Trailblazers', 'mobility-trailblazers'); ?></h1>
            <div class="mt-dashboard">
                <div class="mt-dashboard-widget">
                    <h3><?php _e('Quick Stats', 'mobility-trailblazers'); ?></h3>
                    <p><?php _e('Total Submissions:', 'mobility-trailblazers'); ?> <strong><?php echo $this->get_submission_count(); ?></strong></p>
                    <p><?php _e('Total Votes:', 'mobility-trailblazers'); ?> <strong><?php echo $this->get_vote_count(); ?></strong></p>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render submissions page
     */
    public function render_submissions_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Submissions', 'mobility-trailblazers'); ?></h1>
            <p><?php _e('Manage competition submissions here.', 'mobility-trailblazers'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Render voting page
     */
    public function render_voting_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Voting', 'mobility-trailblazers'); ?></h1>
            <p><?php _e('Manage voting system here.', 'mobility-trailblazers'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Render reports page
     */
    public function render_reports_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Reports', 'mobility-trailblazers'); ?></h1>
            <p><?php _e('View competition reports and analytics here.', 'mobility-trailblazers'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Settings', 'mobility-trailblazers'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('mt_settings_group');
                do_settings_sections('mt-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render general settings section
     */
    public function render_general_section() {
        echo '<p>' . __('Configure general plugin settings.', 'mobility-trailblazers') . '</p>';
    }
    
    /**
     * Render voting enabled field
     */
    public function render_voting_enabled_field() {
        $options = get_option('mt_settings');
        $voting_enabled = isset($options['voting_enabled']) ? $options['voting_enabled'] : 1;
        ?>
        <input type="checkbox" name="mt_settings[voting_enabled]" value="1" <?php checked($voting_enabled, 1); ?> />
        <label><?php _e('Enable voting functionality', 'mobility-trailblazers'); ?></label>
        <?php
    }
    
    /**
     * Get submission count
     */
    private function get_submission_count() {
        $count = wp_count_posts('mt_submission');
        return $count->publish + $count->pending + $count->draft;
    }
    
    /**
     * Get vote count
     */
    private function get_vote_count() {
        global $wpdb;
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes");
        return $count ?: 0;
    }
} 