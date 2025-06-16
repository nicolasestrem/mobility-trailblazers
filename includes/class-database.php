<?php
namespace MobilityTrailblazers;

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
 * Class Database
 * Handles database table creation and management
 */
class Database {
    
    /**
     * Create all plugin database tables
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Drop existing constraints first
        self::drop_existing_constraints();

        // Votes table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mt_votes (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            candidate_id bigint(20) NOT NULL,
            jury_member_id bigint(20) NOT NULL,
            vote_round int(11) NOT NULL DEFAULT 1,
            score decimal(5,2) NOT NULL,
            comments text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY candidate_id (candidate_id),
            KEY jury_member_id (jury_member_id),
            KEY vote_round (vote_round)
        ) $charset_collate;";

        // Vote reset logs table
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vote_reset_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            initiated_by bigint(20) NOT NULL,
            affected_user_id bigint(20) DEFAULT NULL,
            reset_type varchar(50) NOT NULL,
            reset_reason text NOT NULL,
            reset_timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY initiated_by (initiated_by),
            KEY affected_user_id (affected_user_id)
        ) $charset_collate;";

        // Vote audit log table
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mt_vote_audit_log (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            action varchar(50) NOT NULL,
            details text,
            user_id bigint(20) NOT NULL,
            timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        // Candidate scores table for jury evaluations
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mt_candidate_scores (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            candidate_id bigint(20) NOT NULL,
            jury_member_id bigint(20) NOT NULL,
            courage_score tinyint(2) NOT NULL DEFAULT 0 COMMENT 'Mut & Pioniergeist (1-10)',
            innovation_score tinyint(2) NOT NULL DEFAULT 0 COMMENT 'Innovationsgrad (1-10)',
            implementation_score tinyint(2) NOT NULL DEFAULT 0 COMMENT 'Umsetzungskraft & Wirkung (1-10)',
            relevance_score tinyint(2) NOT NULL DEFAULT 0 COMMENT 'Relevanz für Mobilitätswende (1-10)',
            visibility_score tinyint(2) NOT NULL DEFAULT 0 COMMENT 'Vorbildfunktion & Sichtbarkeit (1-10)',
            total_score decimal(4,2) DEFAULT 0 COMMENT 'Calculated total score (max 50)',
            evaluation_round tinyint(1) NOT NULL DEFAULT 1,
            evaluation_date datetime DEFAULT CURRENT_TIMESTAMP,
            comments text,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_evaluation (candidate_id, jury_member_id, evaluation_round),
            KEY candidate_id (candidate_id),
            KEY jury_member_id (jury_member_id),
            KEY total_score (total_score),
            KEY evaluation_round (evaluation_round)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Add unique constraint if it doesn't exist
        $wpdb->query("ALTER TABLE {$wpdb->prefix}mt_votes ADD UNIQUE KEY IF NOT EXISTS unique_vote (candidate_id, jury_member_id, vote_round)");
    }
    
    /**
     * Drop all plugin database tables
     */
    public static function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'mt_votes',
            $wpdb->prefix . 'mt_candidate_scores',
            $wpdb->prefix . 'vote_reset_logs',
            $wpdb->prefix . 'mt_vote_audit_log'
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
        $audit_log_table = $wpdb->prefix . 'mt_vote_audit_log';
        
        $votes_exists = $wpdb->get_var("SHOW TABLES LIKE '$votes_table'") === $votes_table;
        $scores_exists = $wpdb->get_var("SHOW TABLES LIKE '$scores_table'") === $scores_table;
        $audit_log_exists = $wpdb->get_var("SHOW TABLES LIKE '$audit_log_table'") === $audit_log_table;
        
        return $votes_exists && $scores_exists && $audit_log_exists;
    }
    
    /**
     * Update existing tables to add new columns for vote reset functionality
     */
    public static function update_tables_for_reset() {
        global $wpdb;
        
        // Drop existing constraints first
        self::drop_existing_constraints();

        // Add vote_round column if it doesn't exist
        $wpdb->query("ALTER TABLE {$wpdb->prefix}mt_votes ADD COLUMN IF NOT EXISTS vote_round int(11) NOT NULL DEFAULT 1");

        // Add unique constraint
        $wpdb->query("ALTER TABLE {$wpdb->prefix}mt_votes ADD UNIQUE KEY IF NOT EXISTS unique_vote (candidate_id, jury_member_id, vote_round)");
    }

    /**
     * Drop existing constraints
     */
    private static function drop_existing_constraints() {
        global $wpdb;

        // Drop foreign key constraints
        $wpdb->query("ALTER TABLE {$wpdb->prefix}vote_reset_logs DROP FOREIGN KEY IF EXISTS {$wpdb->prefix}vote_reset_logs_ibfk_1");
        $wpdb->query("ALTER TABLE {$wpdb->prefix}vote_reset_logs DROP FOREIGN KEY IF EXISTS {$wpdb->prefix}vote_reset_logs_ibfk_2");

        // Drop unique key if exists
        $wpdb->query("ALTER TABLE {$wpdb->prefix}mt_votes DROP INDEX IF EXISTS unique_vote");
    }
} 