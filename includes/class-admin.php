<?php
namespace MobilityTrailblazers;

class Admin {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Initialize admin functionality
        add_action('admin_menu', array($this, 'register_admin_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function register_admin_menus() {
        // Main menu
        add_menu_page(
            __('MT Award System', 'mobility-trailblazers'),
            __('MT Awards', 'mobility-trailblazers'),
            'mt_manage_awards',
            'mt-award-system',
            array($this, 'admin_dashboard'),
            'dashicons-awards',
            30
        );
        
        // Dashboard submenu (rename the first item)
        add_submenu_page(
            'mt-award-system',
            __('Dashboard', 'mobility-trailblazers'),
            __('Dashboard', 'mobility-trailblazers'),
            'mt_manage_awards',
            'mt-award-system',
            array($this, 'admin_dashboard')
        );
        
        // Candidates submenu
        add_submenu_page(
            'mt-award-system',
            __('Candidates', 'mobility-trailblazers'),
            __('Candidates', 'mobility-trailblazers'),
            'edit_mt_candidates',
            'edit.php?post_type=mt_candidate'
        );
        
        // Jury Members submenu
        add_submenu_page(
            'mt-award-system',
            __('Jury Members', 'mobility-trailblazers'),
            __('Jury Members', 'mobility-trailblazers'),
            'edit_mt_jurys',
            'edit.php?post_type=mt_jury'
        );
        
        // Jury Evaluation page (for jury members)
        add_submenu_page(
            'mt-award-system',
            __('Jury Evaluation', 'mobility-trailblazers'),
            __('Jury Evaluation', 'mobility-trailblazers'),
            'mt_submit_evaluations',
            'mt-jury-evaluation',
            array($this, 'jury_evaluation_page')
        );
        
        // Assignment Management
        add_submenu_page(
            'mt-award-system',
            __('Assignment Management', 'mobility-trailblazers'),
            __('Assignments', 'mobility-trailblazers'),
            'mt_manage_assignments',
            'mt-assignment-management',
            array($this, 'assignment_management_page')
        );
        
        // Voting Results
        add_submenu_page(
            'mt-award-system',
            __('Voting Results', 'mobility-trailblazers'),
            __('Results', 'mobility-trailblazers'),
            'mt_view_all_evaluations',
            'mt-voting-results',
            array($this, 'voting_results_page')
        );
        
        // Vote Reset Management
        add_submenu_page(
            'mt-award-system',
            __('Vote Reset Management', 'mobility-trailblazers'),
            __('Vote Reset', 'mobility-trailblazers'),
            'mt_manage_voting',
            'mt-vote-reset',
            array($this, 'vote_reset_page')
        );
        
        // Settings
        add_submenu_page(
            'mt-award-system',
            __('Settings', 'mobility-trailblazers'),
            __('Settings', 'mobility-trailblazers'),
            'mt_manage_awards',
            'mt-settings',
            array($this, 'settings_page')
        );
        
        // Diagnostic
        add_submenu_page(
            'mt-award-system',
            __('System Diagnostic', 'mobility-trailblazers'),
            __('Diagnostic', 'mobility-trailblazers'),
            'manage_options',
            'mt-diagnostic',
            array($this, 'diagnostic_page')
        );
        
        // Add Jury Dashboard menu for jury members
        if (\MobilityTrailblazers\Roles::is_jury_member() && !current_user_can('manage_options')) {
            add_menu_page(
                __('Jury Dashboard', 'mobility-trailblazers'),
                __('Jury Dashboard', 'mobility-trailblazers'),
                'mt_access_jury_dashboard',
                'mt-jury-dashboard',
                array($this, 'jury_dashboard_redirect'),
                'dashicons-clipboard',
                25
            );
        }
    }

    public function admin_dashboard() {
        ?>
        <div class="wrap">
            <h1><?php _e('Mobility Trailblazers Award System', 'mobility-trailblazers'); ?></h1>
            
            <?php 
            // Temporary diagnostic display
            if (function_exists('mt_display_diagnostic')) {
                mt_display_diagnostic();
            }
            ?>
            
            <div class="mt-dashboard-widgets">
                <div class="mt-widget">
                    <h2><?php _e('System Overview', 'mobility-trailblazers'); ?></h2>
                    <ul>
                        <li><?php 
                            $candidate_count = wp_count_posts('mt_candidate');
                            $candidate_total = (is_object($candidate_count) && isset($candidate_count->publish)) ? $candidate_count->publish : 0;
                            printf(__('Total Candidates: %d', 'mobility-trailblazers'), $candidate_total); 
                        ?></li>
                        <li><?php 
                            $jury_count = wp_count_posts('mt_jury');
                            $jury_total = (is_object($jury_count) && isset($jury_count->publish)) ? $jury_count->publish : 0;
                            printf(__('Total Jury Members: %d', 'mobility-trailblazers'), $jury_total); 
                        ?></li>
                        <li><?php 
                            $total_votes = function_exists('mt_get_total_active_votes') ? mt_get_total_active_votes() : 0;
                            printf(__('Total Votes: %d', 'mobility-trailblazers'), $total_votes); 
                        ?></li>
                        <li><?php 
                            $total_evaluations = function_exists('mt_get_total_evaluations') ? mt_get_total_evaluations() : 0;
                            printf(__('Total Evaluations: %d', 'mobility-trailblazers'), $total_evaluations); 
                        ?></li>
                    </ul>
                </div>
                
                <div class="mt-widget">
                    <h2><?php _e('Recent Activity', 'mobility-trailblazers'); ?></h2>
                    <?php $this->display_recent_activity(); ?>
                </div>
                
                <div class="mt-widget">
                    <h2><?php _e('Quick Actions', 'mobility-trailblazers'); ?></h2>
                    <p>
                        <a href="<?php echo admin_url('post-new.php?post_type=mt_candidate'); ?>" class="button button-primary">
                            <?php _e('Add New Candidate', 'mobility-trailblazers'); ?>
                        </a>
                        <a href="<?php echo admin_url('post-new.php?post_type=mt_jury'); ?>" class="button">
                            <?php _e('Add Jury Member', 'mobility-trailblazers'); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }

    public function jury_evaluation_page() {
        // Load the jury evaluation template
        require_once MT_PLUGIN_PATH . 'templates/jury-evaluation.php';
    }

    public function assignment_management_page() {
        // Load the assignment management template
        require_once MT_PLUGIN_PATH . 'templates/assignment-management.php';
    }

    public function voting_results_page() {
        // Load the voting results template
        require_once MT_PLUGIN_PATH . 'templates/voting-results.php';
    }

    public function vote_reset_page() {
        // Load the vote reset template
        require_once MT_PLUGIN_PATH . 'templates/vote-reset.php';
    }

    public function settings_page() {
        // Load the settings template
        require_once MT_PLUGIN_PATH . 'templates/settings.php';
    }

    public function diagnostic_page() {
        // Load the diagnostic template
        require_once MT_PLUGIN_PATH . 'templates/diagnostic.php';
    }

    public function jury_dashboard_redirect() {
        // Redirect to the jury dashboard
        wp_redirect(admin_url('admin.php?page=mt-jury-dashboard'));
        exit;
    }

    private function display_recent_activity() {
        // Get recent activity from the database
        global $wpdb;
        $table_name = $wpdb->prefix . 'mt_vote_audit_log';
        
        $activities = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT 10"
        );
        
        if ($activities) {
            echo '<ul class="mt-activity-list">';
            foreach ($activities as $activity) {
                echo '<li>';
                echo esc_html($activity->action);
                echo ' - ';
                echo esc_html($activity->details);
                echo ' <small>(' . esc_html($activity->timestamp) . ')</small>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>' . __('No recent activity found.', 'mobility-trailblazers') . '</p>';
        }
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_scripts($hook) {
        // Admin styles
        wp_enqueue_style(
            'mt-admin-css',
            MT_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MT_PLUGIN_VERSION
        );

        // Admin scripts
        wp_enqueue_script(
            'mt-admin-js',
            MT_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            MT_PLUGIN_VERSION,
            true
        );

        // Localize script
        wp_localize_script('mt-admin-js', 'mtAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_admin_nonce')
        ));
    }
} 