<?php
/**
 * Database management class
 *
 * @package MobilityTrailblazers
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
     * Database version
     *
     * @var string
     */
    private $db_version = '1.0.3';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Check if database needs updating
        add_action('plugins_loaded', array($this, 'check_db_version'));
    }
    
    /**
     * Check database version and update if needed
     */
    public function check_db_version() {
        $current_version = get_option('mt_db_version', '0');
        
        if (version_compare($current_version, $this->db_version, '<')) {
            $this->create_tables();
            update_option('mt_db_version', $this->db_version);
            
            // Fire database updated action
            do_action('mt_database_updated', $current_version, $this->db_version);
        }
    }
    
    /**
     * Create plugin database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Include upgrade functions
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Create votes table
        $this->create_votes_table($charset_collate);
        
        // Create candidate scores table
        $this->create_candidate_scores_table($charset_collate);
        
        // Create evaluations table
        $this->create_evaluations_table($charset_collate);
        
        // Create vote reset logs table
        $this->create_vote_reset_logs_table($charset_collate);
        
        // Create vote backups table
        $this->create_vote_backups_table($charset_collate);
    }
    
    /**
     * Create votes table
     *
     * @param string $charset_collate Database charset collation
     */
    private function create_votes_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_votes';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            candidate_id bigint(20) UNSIGNED NOT NULL,
            jury_member_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            criteria_scores longtext,
            total_score decimal(5,2) DEFAULT 0,
            notes longtext,
            is_active tinyint(1) DEFAULT 1,
            reset_at datetime DEFAULT NULL,
            reset_by bigint(20) UNSIGNED DEFAULT NULL,
            voting_phase varchar(50) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY candidate_id (candidate_id),
            KEY jury_member_id (jury_member_id),
            KEY user_id (user_id),
            KEY is_active (is_active),
            KEY voting_phase (voting_phase),
            UNIQUE KEY unique_vote (candidate_id, jury_member_id, voting_phase)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create candidate scores table
     *
     * @param string $charset_collate Database charset collation
     */
    private function create_candidate_scores_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_candidate_scores';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            candidate_id bigint(20) UNSIGNED NOT NULL,
            jury_member_id bigint(20) UNSIGNED NOT NULL,
            courage_score int(2) DEFAULT 0,
            innovation_score int(2) DEFAULT 0,
            implementation_score int(2) DEFAULT 0,
            relevance_score int(2) DEFAULT 0,
            visibility_score int(2) DEFAULT 0,
            total_score int(3) DEFAULT 0,
            evaluation_round varchar(50) DEFAULT 'initial',
            evaluation_date datetime DEFAULT CURRENT_TIMESTAMP,
            comments longtext,
            is_active tinyint(1) DEFAULT 1,
            reset_at datetime DEFAULT NULL,
            reset_by bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY candidate_id (candidate_id),
            KEY jury_member_id (jury_member_id),
            KEY evaluation_round (evaluation_round),
            KEY is_active (is_active),
            UNIQUE KEY unique_evaluation (candidate_id, jury_member_id, evaluation_round)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Create trigger for automatic total_score calculation
        $this->create_score_trigger();
    }
    
    /**
     * Create evaluations table
     *
     * @param string $charset_collate Database charset collation
     */
    private function create_evaluations_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_evaluations';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            candidate_id bigint(20) UNSIGNED NOT NULL,
            jury_member_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            courage_score decimal(3,1) NOT NULL DEFAULT '0.0',
            innovation_score decimal(3,1) NOT NULL DEFAULT '0.0',
            implementation_score decimal(3,1) NOT NULL DEFAULT '0.0',
            relevance_score decimal(3,1) NOT NULL DEFAULT '0.0',
            visibility_score decimal(3,1) NOT NULL DEFAULT '0.0',
            total_score decimal(4,1) NOT NULL DEFAULT '0.0',
            notes longtext,
            status varchar(20) NOT NULL DEFAULT 'draft',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            submitted_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY candidate_id (candidate_id),
            KEY jury_member_id (jury_member_id),
            KEY user_id (user_id),
            KEY status (status),
            KEY created_at (created_at),
            KEY submitted_at (submitted_at),
            UNIQUE KEY unique_evaluation (candidate_id, jury_member_id)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Create triggers for automatic total_score calculation
        $this->create_evaluation_triggers();
    }
    
    /**
     * Create evaluation triggers
     */
    private function create_evaluation_triggers() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_evaluations';
        
        // Note: dbDelta doesn't support triggers, so we need to check if they exist first
        $triggers_exist = $wpdb->get_var("SHOW TRIGGERS LIKE '$table_name'");
        
        if (!$triggers_exist) {
            // Drop existing triggers if they exist (just in case)
            $wpdb->query("DROP TRIGGER IF EXISTS calculate_evaluation_total_score");
            $wpdb->query("DROP TRIGGER IF EXISTS update_evaluation_total_score");
            
            // Create trigger for INSERT
            $sql = "CREATE TRIGGER calculate_evaluation_total_score 
                    BEFORE INSERT ON $table_name 
                    FOR EACH ROW 
                    SET NEW.total_score = NEW.courage_score + NEW.innovation_score + 
                                         NEW.implementation_score + NEW.relevance_score + 
                                         NEW.visibility_score";
            
            $wpdb->query($sql);
            
            // Create trigger for UPDATE
            $sql = "CREATE TRIGGER update_evaluation_total_score 
                    BEFORE UPDATE ON $table_name 
                    FOR EACH ROW 
                    SET NEW.total_score = NEW.courage_score + NEW.innovation_score + 
                                         NEW.implementation_score + NEW.relevance_score + 
                                         NEW.visibility_score";
            
            $wpdb->query($sql);
        }
    }
    
    /**
     * Create vote reset logs table
     *
     * @param string $charset_collate Database charset collation
     */
    private function create_vote_reset_logs_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vote_reset_logs';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            reset_type varchar(50) NOT NULL,
            affected_data longtext,
            reason text,
            performed_by bigint(20) UNSIGNED NOT NULL,
            ip_address varchar(45),
            user_agent text,
            backup_created tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY reset_type (reset_type),
            KEY performed_by (performed_by),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create vote backups table
     *
     * @param string $charset_collate Database charset collation
     */
    private function create_vote_backups_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_vote_backups';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            backup_type varchar(50) NOT NULL,
            backup_data longtext NOT NULL,
            backup_reason text,
            created_by bigint(20) UNSIGNED NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY backup_type (backup_type),
            KEY created_by (created_by),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create database trigger for automatic score calculation
     */
    private function create_score_trigger() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_candidate_scores';
        
        // Drop existing trigger if it exists
        $wpdb->query("DROP TRIGGER IF EXISTS calculate_total_score");
        
        // Create trigger for INSERT
        $sql = "CREATE TRIGGER calculate_total_score 
                BEFORE INSERT ON $table_name 
                FOR EACH ROW 
                SET NEW.total_score = NEW.courage_score + NEW.innovation_score + 
                                     NEW.implementation_score + NEW.relevance_score + 
                                     NEW.visibility_score";
        
        $wpdb->query($sql);
        
        // Drop existing trigger if it exists
        $wpdb->query("DROP TRIGGER IF EXISTS update_total_score");
        
        // Create trigger for UPDATE
        $sql = "CREATE TRIGGER update_total_score 
                BEFORE UPDATE ON $table_name 
                FOR EACH ROW 
                SET NEW.total_score = NEW.courage_score + NEW.innovation_score + 
                                     NEW.implementation_score + NEW.relevance_score + 
                                     NEW.visibility_score";
        
        $wpdb->query($sql);
    }
    
    /**
     * Drop plugin tables
     */
    public function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'mt_votes',
            $wpdb->prefix . 'mt_candidate_scores',
            $wpdb->prefix . 'mt_evaluations',
            $wpdb->prefix . 'vote_reset_logs',
            $wpdb->prefix . 'mt_vote_backups'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Delete database version option
        delete_option('mt_db_version');
    }
    
    /**
     * Get table name with prefix
     *
     * @param string $table Table name without prefix
     * @return string Full table name with prefix
     */
    public static function get_table_name($table) {
        global $wpdb;
        
        $tables = array(
            'votes' => $wpdb->prefix . 'mt_votes',
            'scores' => $wpdb->prefix . 'mt_candidate_scores',
            'evaluations' => $wpdb->prefix . 'mt_evaluations',
            'reset_logs' => $wpdb->prefix . 'vote_reset_logs',
            'backups' => $wpdb->prefix . 'mt_vote_backups'
        );
        
        return isset($tables[$table]) ? $tables[$table] : '';
    }
} 