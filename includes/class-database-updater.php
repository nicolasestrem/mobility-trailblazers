<?php
/**
 * Database Updater Class
 * 
 * Handles database version management and updates
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

namespace MobilityTrailblazers;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * DatabaseUpdater Class
 */
class DatabaseUpdater {
    
    /**
     * Current database version
     */
    const CURRENT_VERSION = '2.5.0';
    
    /**
     * Option name for storing database version
     */
    const VERSION_OPTION = 'mt_database_version';
    
    /**
     * Run database updates if needed
     */
    public static function run_updates() {
        $installed_version = get_option(self::VERSION_OPTION, '0');
        
        if (version_compare($installed_version, self::CURRENT_VERSION, '<')) {
            self::perform_updates($installed_version);
            update_option(self::VERSION_OPTION, self::CURRENT_VERSION);
        }
    }
    
    /**
     * Get update status
     * 
     * @return array
     */
    public static function get_update_status() {
        $installed_version = get_option(self::VERSION_OPTION, '0');
        
        return array(
            'current_version' => $installed_version,
            'required_version' => self::CURRENT_VERSION,
            'needs_update' => version_compare($installed_version, self::CURRENT_VERSION, '<')
        );
    }
    
    /**
     * Perform updates based on version
     * 
     * @param string $installed_version
     */
    private static function perform_updates($installed_version) {
        global $wpdb;
        
        // Version 2.0.0 - Add vote backup functionality
        if (version_compare($installed_version, '2.0.0', '<')) {
            self::update_to_2_0_0();
        }
        
        // Version 2.5.0 - Add reset functionality columns
        if (version_compare($installed_version, '2.5.0', '<')) {
            self::update_to_2_5_0();
        }
    }
    
    /**
     * Update to version 2.0.0
     */
    private static function update_to_2_0_0() {
        global $wpdb;
        
        // Create vote backups table
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mt_vote_backups (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            vote_id BIGINT(20) UNSIGNED NOT NULL,
            candidate_id BIGINT(20) UNSIGNED NOT NULL,
            voter_id BIGINT(20) UNSIGNED NOT NULL,
            jury_member_id BIGINT(20) UNSIGNED NULL,
            vote_value INT(11) NOT NULL,
            vote_phase VARCHAR(50) NOT NULL,
            voting_round INT(11) DEFAULT 1,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent TEXT,
            vote_date DATETIME NOT NULL,
            backup_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            backup_type ENUM('vote', 'score') DEFAULT 'vote',
            backup_reason TEXT,
            comments TEXT,
            PRIMARY KEY (id),
            KEY idx_vote_id (vote_id),
            KEY idx_candidate_voter (candidate_id, voter_id),
            KEY idx_backup_date (backup_date),
            KEY idx_backup_type (backup_type)
        ) $charset_collate";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Update to version 2.5.0
     */
    private static function update_to_2_5_0() {
        global $wpdb;
        
        // Add columns to votes table
        $votes_table = $wpdb->prefix . 'mt_votes';
        
        // Check if columns exist before adding
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $votes_table");
        $existing_columns = wp_list_pluck($columns, 'Field');
        
        if (!in_array('is_active', $existing_columns)) {
            $wpdb->query("ALTER TABLE $votes_table ADD COLUMN is_active TINYINT(1) DEFAULT 1");
        }
        
        if (!in_array('reset_at', $existing_columns)) {
            $wpdb->query("ALTER TABLE $votes_table ADD COLUMN reset_at DATETIME DEFAULT NULL");
        }
        
        if (!in_array('reset_by', $existing_columns)) {
            $wpdb->query("ALTER TABLE $votes_table ADD COLUMN reset_by BIGINT(20) UNSIGNED DEFAULT NULL");
        }
        
        if (!in_array('voting_phase', $existing_columns)) {
            $wpdb->query("ALTER TABLE $votes_table ADD COLUMN voting_phase VARCHAR(50) DEFAULT 'phase_1'");
        }
        
        // Add indexes
        $wpdb->query("CREATE INDEX idx_is_active ON $votes_table (is_active)");
        $wpdb->query("CREATE INDEX idx_reset_at ON $votes_table (reset_at)");
        $wpdb->query("CREATE INDEX idx_voting_phase ON $votes_table (voting_phase)");
        
        // Add columns to candidate scores table
        $scores_table = $wpdb->prefix . 'mt_candidate_scores';
        
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $scores_table");
        $existing_columns = wp_list_pluck($columns, 'Field');
        
        if (!in_array('is_active', $existing_columns)) {
            $wpdb->query("ALTER TABLE $scores_table ADD COLUMN is_active TINYINT(1) DEFAULT 1");
        }
        
        if (!in_array('reset_at', $existing_columns)) {
            $wpdb->query("ALTER TABLE $scores_table ADD COLUMN reset_at DATETIME DEFAULT NULL");
        }
        
        if (!in_array('reset_by', $existing_columns)) {
            $wpdb->query("ALTER TABLE $scores_table ADD COLUMN reset_by BIGINT(20) UNSIGNED DEFAULT NULL");
        }
        
        // Add indexes
        $wpdb->query("CREATE INDEX idx_scores_active ON $scores_table (is_active)");
        $wpdb->query("CREATE INDEX idx_scores_reset ON $scores_table (reset_at)");
        
        // Create jury sync log table
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mt_jury_sync_log (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            jury_post_id BIGINT(20) UNSIGNED DEFAULT NULL,
            action VARCHAR(50) NOT NULL,
            status VARCHAR(20) NOT NULL,
            details TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_jury_post_id (jury_post_id),
            KEY idx_action (action),
            KEY idx_created_at (created_at)
        ) $charset_collate";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Force a specific update
     * 
     * @param string $version
     */
    public static function force_update($version) {
        $method_name = 'update_to_' . str_replace('.', '_', $version);
        
        if (method_exists(__CLASS__, $method_name)) {
            self::$method_name();
            update_option(self::VERSION_OPTION, $version);
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if a table exists
     * 
     * @param string $table_name
     * @return bool
     */
    private static function table_exists($table_name) {
        global $wpdb;
        $query = $wpdb->prepare("SHOW TABLES LIKE %s", $table_name);
        return $wpdb->get_var($query) === $table_name;
    }
    
    /**
     * Check if a column exists
     * 
     * @param string $table_name
     * @param string $column_name
     * @return bool
     */
    private static function column_exists($table_name, $column_name) {
        global $wpdb;
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        $existing_columns = wp_list_pluck($columns, 'Field');
        return in_array($column_name, $existing_columns);
    }
}