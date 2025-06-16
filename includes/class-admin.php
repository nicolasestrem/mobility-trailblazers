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
        // Add main menu
        add_menu_page(
            __('Mobility Trailblazers', 'mobility-trailblazers'),
            __('Mobility Trailblazers', 'mobility-trailblazers'),
            'manage_options',
            'mt-award-system',
            array($this, 'admin_dashboard'),
            'dashicons-awards',
            30
        );

        // Add submenu items
        add_submenu_page(
            'mt-award-system',
            __('Dashboard', 'mobility-trailblazers'),
            __('Dashboard', 'mobility-trailblazers'),
            'manage_options',
            'mt-award-system',
            array($this, 'admin_dashboard')
        );

        // Jury Evaluation
        add_submenu_page(
            'mt-award-system',
            __('Jury Evaluation', 'mobility-trailblazers'),
            __('Evaluation', 'mobility-trailblazers'),
            'mt_access_jury_dashboard',
            'mt-jury-evaluation',
            array($this, 'jury_evaluation_page')
        );

        // Assignment Management
        add_submenu_page(
            'mt-award-system',
            __('Jury Assignments', 'mobility-trailblazers'),
            __('Assignments', 'mobility-trailblazers'),
            'manage_options',
            'mt-assignments',
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
        // Call the enhanced enqueue function
        $this->enqueue_assignment_assets();
        
        // Enqueue required scripts and styles (fallback)
        wp_enqueue_script('mt-assignment-js', MT_PLUGIN_URL . 'assets/js/assignment.js', array('jquery'), MT_PLUGIN_VERSION, true);
        wp_enqueue_style('mt-assignment-css', MT_PLUGIN_URL . 'assets/css/assignment.css', array(), MT_PLUGIN_VERSION);

        // Get initial data
        $total_candidates = wp_count_posts('mt_candidate')->publish;
        $total_jury = wp_count_posts('mt_jury')->publish;
        
        global $wpdb;
        $assigned_count = $wpdb->get_var("
            SELECT COUNT(DISTINCT post_id) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_mt_assigned_jury_member' 
            AND meta_value != ''
        ");

        $completion_rate = $total_candidates > 0 ? ($assigned_count / $total_candidates) * 100 : 0;
        $avg_per_jury = $total_jury > 0 ? $assigned_count / $total_jury : 0;

        // Get current phase
        $current_phase = get_option('mt_current_phase', 'preparation');
        $phase_names = array(
            'preparation' => __('Preparation', 'mobility-trailblazers'),
            'candidate_collection' => __('Candidate Collection', 'mobility-trailblazers'),
            'jury_evaluation' => __('Jury Evaluation', 'mobility-trailblazers'),
            'public_voting' => __('Public Voting', 'mobility-trailblazers'),
            'final_selection' => __('Final Selection', 'mobility-trailblazers'),
            'award_ceremony' => __('Award Ceremony', 'mobility-trailblazers'),
            'post_award' => __('Post Award', 'mobility-trailblazers')
        );

        // Load the assignment template
        require_once MT_PLUGIN_PATH . 'templates/assignment-template.php';
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
     * Enhanced assignment assets enqueue function
     */
    public function enqueue_assignment_assets() {
        // Enqueue enhanced CSS
        wp_enqueue_style(
            'mt-assignment-style',
            MT_PLUGIN_URL . 'assets/css/assignment.css',
            array(),
            '2.0.0'
        );
        
        // Enqueue enhanced JS
        wp_enqueue_script(
            'mt-assignment-script',
            MT_PLUGIN_URL . 'assets/js/assignment.js',
            array('jquery'),
            '2.0.0',
            true
        );
        
        // Prepare candidates data
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        // Prepare data for JavaScript
        $candidates_data = array();
        foreach ($candidates as $candidate) {
            $jury_id = get_post_meta($candidate->ID, '_mt_assigned_jury_member', true);
            $categories = wp_get_post_terms($candidate->ID, 'mt_category');
            
            $candidates_data[] = array(
                'id' => $candidate->ID,
                'name' => $candidate->post_title,
                'company' => get_post_meta($candidate->ID, '_mt_company', true),
                'position' => get_post_meta($candidate->ID, '_mt_position', true),
                'category' => !empty($categories) ? $categories[0]->slug : '',
                'assigned' => !empty($jury_id),
                'jury_member_id' => $jury_id
            );
        }
        
        $jury_data = array();
        foreach ($jury_members as $jury) {
            // Count assignments
            $assignments = get_posts(array(
                'post_type' => 'mt_candidate',
                'meta_query' => array(
                    array(
                        'key' => '_mt_assigned_jury_member',
                        'value' => $jury->ID
                    )
                ),
                'posts_per_page' => -1
            ));
            
            $jury_data[] = array(
                'id' => $jury->ID,
                'name' => $jury->post_title,
                'position' => get_post_meta($jury->ID, '_mt_position', true),
                'expertise' => get_post_meta($jury->ID, '_mt_expertise', true),
                'assignments' => count($assignments),
                'maxAssignments' => 15,
                'role' => get_post_meta($jury->ID, '_mt_jury_role', true)
            );
        }
        
        wp_localize_script('mt-assignment-script', 'mt_assignment_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_nonce'),
            'candidates' => $candidates_data,
            'jury_members' => $jury_data
        ));
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