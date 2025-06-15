<?php
/**
 * Plugin Activator
 *
 * @package MobilityTrailblazers
 * @subpackage Includes
 */

namespace MobilityTrailblazers;

/**
 * Fired during plugin activation
 */
class Activator {
    
    /**
     * Activate the plugin
     *
     * @return void
     */
    public static function activate() {
        // Create database tables
        self::create_database_tables();
        
        // Set up user roles and capabilities
        self::setup_roles();
        
        // Set default options
        self::set_default_options();
        
        // Create default terms
        self::create_default_terms();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log activation
        error_log('Mobility Trailblazers plugin activated successfully');
    }
    
    /**
     * Create database tables
     *
     * @return void
     */
    private static function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Votes table
        $votes_table = $wpdb->prefix . 'mt_votes';
        $votes_sql = "CREATE TABLE $votes_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            candidate_id bigint(20) NOT NULL,
            jury_member_id bigint(20) NOT NULL,
            vote_round tinyint(1) NOT NULL DEFAULT 1,
            rating tinyint(2) NOT NULL,
            comments text,
            vote_date datetime DEFAULT CURRENT_TIMESTAMP,
            is_active boolean DEFAULT TRUE,
            reset_at timestamp NULL DEFAULT NULL,
            reset_by bigint(20) unsigned DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_vote (candidate_id, jury_member_id, vote_round),
            KEY candidate_idx (candidate_id),
            KEY jury_idx (jury_member_id),
            KEY vote_round_idx (vote_round),
            KEY vote_date_idx (vote_date),
            KEY idx_active_votes (is_active, candidate_id, jury_member_id)
        ) $charset_collate;";
        
        // Candidate scores table
        $scores_table = $wpdb->prefix . 'mt_candidate_scores';
        $scores_sql = "CREATE TABLE $scores_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            candidate_id bigint(20) NOT NULL,
            jury_member_id bigint(20) NOT NULL,
            courage_score tinyint(2) NOT NULL DEFAULT 0,
            innovation_score tinyint(2) NOT NULL DEFAULT 0,
            implementation_score tinyint(2) NOT NULL DEFAULT 0,
            relevance_score tinyint(2) NOT NULL DEFAULT 0,
            visibility_score tinyint(2) NOT NULL DEFAULT 0,
            total_score decimal(4,2) DEFAULT 0,
            evaluation_round tinyint(1) NOT NULL DEFAULT 1,
            evaluation_date datetime DEFAULT CURRENT_TIMESTAMP,
            comments text,
            is_active boolean DEFAULT TRUE,
            reset_at timestamp NULL DEFAULT NULL,
            reset_by bigint(20) unsigned DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_evaluation (candidate_id, jury_member_id, evaluation_round),
            KEY candidate_idx (candidate_id),
            KEY jury_idx (jury_member_id),
            KEY total_score_idx (total_score),
            KEY evaluation_round_idx (evaluation_round),
            KEY idx_active_scores (is_active, candidate_id, jury_member_id)
        ) $charset_collate;";
        
        // Vote reset logs table
        $reset_logs_table = $wpdb->prefix . 'vote_reset_logs';
        $reset_logs_sql = "CREATE TABLE $reset_logs_table (
            id int AUTO_INCREMENT PRIMARY KEY,
            reset_type enum('individual', 'bulk_user', 'bulk_candidate', 'phase_transition', 'full_reset') NOT NULL,
            initiated_by bigint(20) unsigned NOT NULL,
            initiated_by_role enum('jury_member', 'admin', 'system') NOT NULL,
            affected_user_id bigint(20) unsigned DEFAULT NULL,
            affected_candidate_id bigint(20) unsigned DEFAULT NULL,
            voting_phase varchar(50) DEFAULT NULL,
            votes_affected int NOT NULL DEFAULT 0,
            reset_reason text,
            reset_timestamp timestamp DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45),
            user_agent text,
            INDEX idx_reset_timestamp (reset_timestamp),
            INDEX idx_initiated_by (initiated_by)
        ) $charset_collate;";
        
        // Vote history table
        $votes_history_table = $wpdb->prefix . 'mt_votes_history';
        $votes_history_sql = "CREATE TABLE $votes_history_table (
            history_id int AUTO_INCREMENT PRIMARY KEY,
            original_vote_id mediumint(9) NOT NULL,
            candidate_id bigint(20) NOT NULL,
            jury_member_id bigint(20) NOT NULL,
            vote_round tinyint(1) NOT NULL,
            rating tinyint(2) NOT NULL,
            comments text,
            vote_date datetime,
            voting_phase varchar(50),
            backed_up_at timestamp DEFAULT CURRENT_TIMESTAMP,
            backup_reason varchar(100),
            restored_at timestamp NULL DEFAULT NULL,
            INDEX idx_original_vote (original_vote_id),
            INDEX idx_backup_time (backed_up_at),
            INDEX idx_restored_at (restored_at)
        ) $charset_collate;";
        
        // Candidate scores history table
        $scores_history_table = $wpdb->prefix . 'mt_candidate_scores_history';
        $scores_history_sql = "CREATE TABLE $scores_history_table (
            history_id int AUTO_INCREMENT PRIMARY KEY,
            original_score_id mediumint(9) NOT NULL,
            candidate_id bigint(20) NOT NULL,
            jury_member_id bigint(20) NOT NULL,
            courage_score tinyint(2) NOT NULL DEFAULT 0,
            innovation_score tinyint(2) NOT NULL DEFAULT 0,
            implementation_score tinyint(2) NOT NULL DEFAULT 0,
            relevance_score tinyint(2) NOT NULL DEFAULT 0,
            visibility_score tinyint(2) NOT NULL DEFAULT 0,
            total_score decimal(4,2) DEFAULT 0,
            evaluation_round tinyint(1) NOT NULL DEFAULT 1,
            evaluation_date datetime,
            comments text,
            voting_phase varchar(50),
            backed_up_at timestamp DEFAULT CURRENT_TIMESTAMP,
            backup_reason varchar(100),
            restored_at timestamp NULL DEFAULT NULL,
            INDEX idx_original_score (original_score_id),
            INDEX idx_backup_time (backed_up_at),
            INDEX idx_restored_at (restored_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($votes_sql);
        dbDelta($scores_sql);
        dbDelta($reset_logs_sql);
        dbDelta($votes_history_sql);
        dbDelta($scores_history_sql);
    }
    
    /**
     * Set up user roles and capabilities
     *
     * @return void
     */
    private static function setup_roles() {
        // Add jury member role
        add_role(
            'mt_jury_member',
            __('Jury Member', 'mobility-trailblazers'),
            array(
                'read' => true,
                'mt_evaluate_candidates' => true,
                'mt_view_assignments' => true,
                'mt_reset_own_votes' => true,
            )
        );
        
        // Add award admin role
        add_role(
            'mt_award_admin',
            __('Award Administrator', 'mobility-trailblazers'),
            array(
                'read' => true,
                'edit_posts' => true,
                'edit_others_posts' => true,
                'publish_posts' => true,
                'manage_categories' => true,
                'mt_manage_awards' => true,
                'mt_manage_jury' => true,
                'mt_manage_candidates' => true,
                'mt_view_reports' => true,
                'mt_reset_votes' => true,
            )
        );
        
        // Add capabilities to administrator
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('mt_manage_awards');
            $admin_role->add_cap('mt_manage_jury');
            $admin_role->add_cap('mt_manage_candidates');
            $admin_role->add_cap('mt_view_reports');
            $admin_role->add_cap('mt_reset_votes');
            $admin_role->add_cap('mt_evaluate_candidates');
        }
    }
    
    /**
     * Set default plugin options
     *
     * @return void
     */
    private static function set_default_options() {
        $defaults = array(
            'mt_current_voting_phase' => 'phase_1',
            'mt_voting_phase_phase_1_status' => 'open',
            'mt_voting_phase_phase_2_status' => 'closed',
            'mt_voting_phase_phase_3_status' => 'closed',
            'mt_plugin_version' => MT_VERSION,
            'mt_jury_assignment_algorithm' => 'balanced',
            'mt_evaluation_criteria_weights' => array(
                'courage' => 20,
                'innovation' => 20,
                'implementation' => 20,
                'relevance' => 20,
                'visibility' => 20
            ),
            'mt_backup_retention_days' => 365,
            'mt_email_notifications_enabled' => true,
        );
        
        foreach ($defaults as $option => $value) {
            if (false === get_option($option)) {
                add_option($option, $value);
            }
        }
    }
    
    /**
     * Create default taxonomy terms
     *
     * @return void
     */
    private static function create_default_terms() {
        // Create default categories
        $categories = array(
            'infrastructure' => __('Infrastructure/Politics/Public', 'mobility-trailblazers'),
            'startups' => __('Startups/New Makers', 'mobility-trailblazers'),
            'established' => __('Established Companies', 'mobility-trailblazers'),
        );
        
        foreach ($categories as $slug => $name) {
            if (!term_exists($slug, 'mt_category')) {
                wp_insert_term($name, 'mt_category', array('slug' => $slug));
            }
        }
        
        // Create default phases
        $phases = array(
            'phase_1' => __('Phase 1 (200 to 50)', 'mobility-trailblazers'),
            'phase_2' => __('Phase 2 (50 to 25)', 'mobility-trailblazers'),
            'phase_3' => __('Phase 3 (25 to Winners)', 'mobility-trailblazers'),
        );
        
        foreach ($phases as $slug => $name) {
            if (!term_exists($slug, 'mt_phase')) {
                wp_insert_term($name, 'mt_phase', array('slug' => $slug));
            }
        }
        
        // Create default statuses
        $statuses = array(
            'active' => __('Active', 'mobility-trailblazers'),
            'inactive' => __('Inactive', 'mobility-trailblazers'),
            'pending' => __('Pending', 'mobility-trailblazers'),
            'completed' => __('Completed', 'mobility-trailblazers'),
        );
        
        foreach ($statuses as $slug => $name) {
            if (!term_exists($slug, 'mt_status')) {
                wp_insert_term($name, 'mt_status', array('slug' => $slug));
            }
        }
    }
} 