<?php
/**
 * Database Handler
 *
 * @package MobilityTrailblazers
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Database
 * Handles database table creation and management
 */
class MT_Database {
    
    /**
     * Create all plugin database tables
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Voting table with reset support
        $table_votes = $wpdb->prefix . 'mt_votes';
        $sql_votes = "CREATE TABLE $table_votes (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            candidate_id bigint(20) NOT NULL,
            jury_member_id bigint(20) NOT NULL,
            vote_round tinyint(1) NOT NULL DEFAULT 1,
            rating tinyint(2) NOT NULL,
            comments text,
            vote_date datetime DEFAULT CURRENT_TIMESTAMP,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            reset_at datetime NULL,
            reset_by bigint(20) NULL,
            voting_phase varchar(50) DEFAULT 'phase_1',
            PRIMARY KEY (id),
            UNIQUE KEY unique_vote (candidate_id, jury_member_id, vote_round),
            KEY candidate_idx (candidate_id),
            KEY jury_idx (jury_member_id),
            KEY active_idx (is_active),
            KEY phase_idx (voting_phase)
        ) $charset_collate;";

        // Evaluation criteria scores table with reset support
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        $sql_scores = "CREATE TABLE $table_scores (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            candidate_id bigint(20) NOT NULL,
            jury_member_id bigint(20) NOT NULL,
            courage_score tinyint(2) NOT NULL DEFAULT 0,
            innovation_score tinyint(2) NOT NULL DEFAULT 0,
            implementation_score tinyint(2) NOT NULL DEFAULT 0,
            mobility_relevance_score tinyint(2) NOT NULL DEFAULT 0,
            visibility_score tinyint(2) NOT NULL DEFAULT 0,
            total_score decimal(4,2) DEFAULT 0,
            evaluation_round tinyint(1) NOT NULL DEFAULT 1,
            evaluation_date datetime DEFAULT CURRENT_TIMESTAMP,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            reset_at datetime NULL,
            reset_by bigint(20) NULL,
            voting_phase varchar(50) DEFAULT 'phase_1',
            PRIMARY KEY (id),
            UNIQUE KEY unique_evaluation (candidate_id, jury_member_id, evaluation_round),
            KEY candidate_idx (candidate_id),
            KEY jury_idx (jury_member_id),
            KEY active_idx (is_active),
            KEY phase_idx (voting_phase)
        ) $charset_collate;";

        // Vote reset logs table
        $table_reset_logs = $wpdb->prefix . 'vote_reset_logs';
        $sql_reset_logs = "CREATE TABLE $table_reset_logs (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            reset_type varchar(50) NOT NULL,
            initiated_by bigint(20) NOT NULL,
            affected_candidate_id bigint(20) NULL,
            affected_user_id bigint(20) NULL,
            reset_reason text,
            votes_affected int(11) DEFAULT 0,
            reset_timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            backup_id bigint(20) NULL,
            PRIMARY KEY (id),
            KEY type_idx (reset_type),
            KEY user_idx (initiated_by),
            KEY timestamp_idx (reset_timestamp)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_votes);
        dbDelta($sql_scores);
        dbDelta($sql_reset_logs);
    }
    
    /**
     * Drop all plugin database tables
     */
    public static function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'mt_votes',
            $wpdb->prefix . 'mt_candidate_scores',
            $wpdb->prefix . 'vote_reset_logs'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }
    
    /**
     * Check if tables exist
     */
    public static function tables_exist() {
        global $wpdb;
        
        $votes_table = $wpdb->prefix . 'mt_votes';
        $scores_table = $wpdb->prefix . 'mt_candidate_scores';
        
        $votes_exists = $wpdb->get_var("SHOW TABLES LIKE '$votes_table'") === $votes_table;
        $scores_exists = $wpdb->get_var("SHOW TABLES LIKE '$scores_table'") === $scores_table;
        
        return $votes_exists && $scores_exists;
    }
    
    /**
     * Update existing tables to add new columns for vote reset functionality
     */
    public static function update_tables_for_reset() {
        global $wpdb;
        
        $votes_table = $wpdb->prefix . 'mt_votes';
        $scores_table = $wpdb->prefix . 'mt_candidate_scores';
        
        // Check if columns exist and add them if they don't
        $votes_columns = $wpdb->get_col("DESCRIBE $votes_table");
        
        if (!in_array('is_active', $votes_columns)) {
            $wpdb->query("ALTER TABLE $votes_table ADD COLUMN is_active tinyint(1) NOT NULL DEFAULT 1");
            $wpdb->query("ALTER TABLE $votes_table ADD INDEX active_idx (is_active)");
        }
        
        if (!in_array('reset_at', $votes_columns)) {
            $wpdb->query("ALTER TABLE $votes_table ADD COLUMN reset_at datetime NULL");
        }
        
        if (!in_array('reset_by', $votes_columns)) {
            $wpdb->query("ALTER TABLE $votes_table ADD COLUMN reset_by bigint(20) NULL");
        }
        
        if (!in_array('voting_phase', $votes_columns)) {
            $wpdb->query("ALTER TABLE $votes_table ADD COLUMN voting_phase varchar(50) DEFAULT 'phase_1'");
            $wpdb->query("ALTER TABLE $votes_table ADD INDEX phase_idx (voting_phase)");
        }
        
        // Update scores table
        $scores_columns = $wpdb->get_col("DESCRIBE $scores_table");
        
        if (!in_array('is_active', $scores_columns)) {
            $wpdb->query("ALTER TABLE $scores_table ADD COLUMN is_active tinyint(1) NOT NULL DEFAULT 1");
            $wpdb->query("ALTER TABLE $scores_table ADD INDEX active_idx (is_active)");
        }
        
        if (!in_array('reset_at', $scores_columns)) {
            $wpdb->query("ALTER TABLE $scores_table ADD COLUMN reset_at datetime NULL");
        }
        
        if (!in_array('reset_by', $scores_columns)) {
            $wpdb->query("ALTER TABLE $scores_table ADD COLUMN reset_by bigint(20) NULL");
        }
        
        if (!in_array('voting_phase', $scores_columns)) {
            $wpdb->query("ALTER TABLE $scores_table ADD COLUMN voting_phase varchar(50) DEFAULT 'phase_1'");
            $wpdb->query("ALTER TABLE $scores_table ADD INDEX phase_idx (voting_phase)");
        }
        
        // Create reset logs table if it doesn't exist
        $reset_logs_table = $wpdb->prefix . 'vote_reset_logs';
        $reset_logs_exists = $wpdb->get_var("SHOW TABLES LIKE '$reset_logs_table'") === $reset_logs_table;
        
        if (!$reset_logs_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql_reset_logs = "CREATE TABLE $reset_logs_table (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                reset_type varchar(50) NOT NULL,
                initiated_by bigint(20) NOT NULL,
                affected_candidate_id bigint(20) NULL,
                affected_user_id bigint(20) NULL,
                reset_reason text,
                votes_affected int(11) DEFAULT 0,
                reset_timestamp datetime DEFAULT CURRENT_TIMESTAMP,
                backup_id bigint(20) NULL,
                PRIMARY KEY (id),
                KEY type_idx (reset_type),
                KEY user_idx (initiated_by),
                KEY timestamp_idx (reset_timestamp)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql_reset_logs);
        }
    }
} 