<?php
/**
 * Database Updater Class
 * 
 * Handles database schema updates and migrations
 * 
 * @package MobilityTrailblazers
 * @since 2.5.0
 */

namespace MobilityTrailblazers;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Database Updater
 */
class DatabaseUpdater {
    
    /**
     * Current database version
     */
    const DB_VERSION = '2.5.0';
    
    /**
     * Option name for storing database version
     */
    const VERSION_OPTION = 'mt_database_version';
    
    /**
     * Run database updates
     */
    public static function run_updates() {
        $current_version = get_option(self::VERSION_OPTION, '0');
        
        if (version_compare($current_version, self::DB_VERSION, '<')) {
            self::update_database();
            update_option(self::VERSION_OPTION, self::DB_VERSION);
        }
    }
    
    /**
     * Update database schema
     */
    private static function update_database() {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create/update tables
        self::create_vote_backups_table($charset_collate);
        self::update_votes_table();
        self::update_candidate_scores_table();
        self::create_vote_reset_logs_table($charset_collate);
        self::create_jury_sync_log_table($charset_collate);
        
        // Add indexes
        self::add_indexes();
        
        // Run any data migrations
        self::migrate_data();
    }
    
    /**
     * Create vote backups table
     */
    private static function create_vote_backups_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_vote_backups';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            backup_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            backup_reason varchar(255) DEFAULT NULL,
            backup_type varchar(50) DEFAULT 'manual',
            candidate_id bigint(20) UNSIGNED DEFAULT NULL,
            jury_member_id bigint(20) UNSIGNED DEFAULT NULL,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            criteria_scores text DEFAULT NULL,
            total_score decimal(10,2) DEFAULT NULL,
            notes text DEFAULT NULL,
            evaluation_data longtext DEFAULT NULL,
            created_by bigint(20) UNSIGNED DEFAULT NULL,
            restoration_date datetime DEFAULT NULL,
            restored_by bigint(20) UNSIGNED DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_backup_date (backup_date),
            KEY idx_candidate_jury (candidate_id, jury_member_id),
            KEY idx_backup_type (backup_type),
            KEY idx_created_by (created_by)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Update votes table
     */
    private static function update_votes_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_votes';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return;
        }
        
        // Add missing columns
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        $existing_columns = array();
        
        foreach ($columns as $column) {
            $existing_columns[] = $column->Field;
        }
        
        // Add is_active column
        if (!in_array('is_active', $existing_columns)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN is_active tinyint(1) NOT NULL DEFAULT 1");
        }
        
        // Add reset_at column
        if (!in_array('reset_at', $existing_columns)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN reset_at datetime DEFAULT NULL");
        }
        
        // Add reset_by column
        if (!in_array('reset_by', $existing_columns)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN reset_by bigint(20) UNSIGNED DEFAULT NULL");
        }
        
        // Add voting_phase column
        if (!in_array('voting_phase', $existing_columns)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN voting_phase varchar(50) DEFAULT NULL");
        }
    }
    
    /**
     * Update candidate scores table
     */
    private static function update_candidate_scores_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_candidate_scores';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return;
        }
        
        // Add missing columns
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        $existing_columns = array();
        
        foreach ($columns as $column) {
            $existing_columns[] = $column->Field;
        }
        
        // Add is_active column
        if (!in_array('is_active', $existing_columns)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN is_active tinyint(1) NOT NULL DEFAULT 1");
        }
        
        // Add reset_at column
        if (!in_array('reset_at', $existing_columns)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN reset_at datetime DEFAULT NULL");
        }
        
        // Add reset_by column
        if (!in_array('reset_by', $existing_columns)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN reset_by bigint(20) UNSIGNED DEFAULT NULL");
        }
    }
    
    /**
     * Create vote reset logs table
     */
    private static function create_vote_reset_logs_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vote_reset_logs';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            reset_type varchar(50) NOT NULL,
            affected_data longtext DEFAULT NULL,
            reason text DEFAULT NULL,
            performed_by bigint(20) UNSIGNED NOT NULL,
            initiated_by_role varchar(100) DEFAULT NULL,
            affected_candidate_id bigint(20) UNSIGNED DEFAULT NULL,
            affected_jury_member_id bigint(20) UNSIGNED DEFAULT NULL,
            voting_phase varchar(50) DEFAULT NULL,
            votes_affected int(11) DEFAULT 0,
            scores_affected int(11) DEFAULT 0,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            backup_created tinyint(1) DEFAULT 1,
            backup_id bigint(20) UNSIGNED DEFAULT NULL,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_reset_type (reset_type),
            KEY idx_performed_by (performed_by),
            KEY idx_affected_candidate (affected_candidate_id),
            KEY idx_affected_jury (affected_jury_member_id),
            KEY idx_created_at (created_at),
            KEY idx_backup_id (backup_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create jury sync log table
     */
    private static function create_jury_sync_log_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_jury_sync_log';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            sync_type varchar(50) NOT NULL,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            jury_post_id bigint(20) UNSIGNED DEFAULT NULL,
            action varchar(50) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'success',
            details text DEFAULT NULL,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_sync_type (sync_type),
            KEY idx_user_id (user_id),
            KEY idx_jury_post_id (jury_post_id),
            KEY idx_created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Add indexes for performance
     */
    private static function add_indexes() {
        global $wpdb;
        
        // Votes table indexes
        $votes_table = $wpdb->prefix . 'mt_votes';
        if ($wpdb->get_var("SHOW TABLES LIKE '$votes_table'") == $votes_table) {
            // Check and add indexes if they don't exist
            self::add_index_if_not_exists($votes_table, 'idx_is_active', 'is_active');
            self::add_index_if_not_exists($votes_table, 'idx_voting_phase', 'voting_phase');
            self::add_index_if_not_exists($votes_table, 'idx_reset_at', 'reset_at');
            self::add_index_if_not_exists($votes_table, 'idx_composite_active_scores', 'is_active, total_score');
        }
        
        // Candidate scores table indexes
        $scores_table = $wpdb->prefix . 'mt_candidate_scores';
        if ($wpdb->get_var("SHOW TABLES LIKE '$scores_table'") == $scores_table) {
            self::add_index_if_not_exists($scores_table, 'idx_scores_is_active', 'is_active');
            self::add_index_if_not_exists($scores_table, 'idx_scores_reset_at', 'reset_at');
            self::add_index_if_not_exists($scores_table, 'idx_composite_active_total', 'is_active, total_score');
        }
    }
    
    /**
     * Add index if it doesn't exist
     */
    private static function add_index_if_not_exists($table, $index_name, $columns) {
        global $wpdb;
        
        $index_exists = $wpdb->get_var("SHOW INDEX FROM $table WHERE Key_name = '$index_name'");
        
        if (!$index_exists) {
            $wpdb->query("ALTER TABLE $table ADD INDEX $index_name ($columns)");
        }
    }
    
    /**
     * Migrate existing data if needed
     */
    private static function migrate_data() {
        global $wpdb;
        
        // Example: Set is_active to 1 for all existing records without the field
        $votes_table = $wpdb->prefix . 'mt_votes';
        if ($wpdb->get_var("SHOW TABLES LIKE '$votes_table'") == $votes_table) {
            $wpdb->query("UPDATE $votes_table SET is_active = 1 WHERE is_active IS NULL");
        }
        
        $scores_table = $wpdb->prefix . 'mt_candidate_scores';
        if ($wpdb->get_var("SHOW TABLES LIKE '$scores_table'") == $scores_table) {
            $wpdb->query("UPDATE $scores_table SET is_active = 1 WHERE is_active IS NULL");
        }
    }
    
    /**
     * Check if update is needed
     */
    public static function needs_update() {
        $current_version = get_option(self::VERSION_OPTION, '0');
        return version_compare($current_version, self::DB_VERSION, '<');
    }
    
    /**
     * Get update status
     */
    public static function get_update_status() {
        global $wpdb;
        
        $status = array(
            'current_version' => get_option(self::VERSION_OPTION, '0'),
            'target_version' => self::DB_VERSION,
            'needs_update' => self::needs_update(),
            'tables' => array()
        );
        
        // Check tables
        $tables = array(
            'mt_vote_backups',
            'mt_votes',
            'mt_candidate_scores',
            'vote_reset_logs',
            'mt_jury_sync_log'
        );
        
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
            
            $status['tables'][$table] = array(
                'exists' => $exists,
                'name' => $table_name
            );
            
            if ($exists) {
                $status['tables'][$table]['rows'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            }
        }
        
        return $status;
    }
} 