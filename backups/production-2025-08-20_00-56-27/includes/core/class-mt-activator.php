<?php
// GPL 2.0 or later. See LICENSE. Copyright (c) 2025 Nicolas Estrem

/**
 * Plugin Activator
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

namespace MobilityTrailblazers\Core;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Activator
 *
 * Handles plugin activation
 */
class MT_Activator {
    
    /**
     * Activate the plugin
     *
     * @return void
     */
    public function activate() {
        // Create database tables
        $this->create_tables();
        
        // Setup roles and capabilities
        $roles = new MT_Roles();
        $roles->add_roles();
        $roles->add_capabilities();
        
        // Create default options
        $this->create_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation flag
        set_transient('mt_activation_redirect', true, 30);
    }
    
    /**
     * Create database tables
     *
     * @return void
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Evaluations table
        $evaluations_table = $wpdb->prefix . 'mt_evaluations';
        $evaluations_sql = "CREATE TABLE IF NOT EXISTS $evaluations_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            jury_member_id bigint(20) NOT NULL,
            candidate_id bigint(20) NOT NULL,
            courage_score tinyint(2) NOT NULL DEFAULT 0,
            innovation_score tinyint(2) NOT NULL DEFAULT 0,
            implementation_score tinyint(2) NOT NULL DEFAULT 0,
            relevance_score tinyint(2) NOT NULL DEFAULT 0,
            visibility_score tinyint(2) NOT NULL DEFAULT 0,
            total_score decimal(5,2) NOT NULL DEFAULT 0,
            comments longtext,
            status varchar(20) NOT NULL DEFAULT 'draft',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_evaluation (jury_member_id, candidate_id),
            KEY idx_candidate (candidate_id),
            KEY idx_jury_member (jury_member_id),
            KEY idx_status (status)
        ) $charset_collate;";
        
        // Jury assignments table
        $assignments_table = $wpdb->prefix . 'mt_jury_assignments';
        $assignments_sql = "CREATE TABLE IF NOT EXISTS $assignments_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            jury_member_id bigint(20) NOT NULL,
            candidate_id bigint(20) NOT NULL,
            assigned_at datetime DEFAULT CURRENT_TIMESTAMP,
            assigned_by bigint(20) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_assignment (jury_member_id, candidate_id),
            KEY idx_jury_member (jury_member_id),
            KEY idx_candidate (candidate_id)
        ) $charset_collate;";
        
        // Audit log table
        $audit_log_table = $wpdb->prefix . 'mt_audit_log';
        $audit_log_sql = "CREATE TABLE IF NOT EXISTS $audit_log_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            action varchar(255) NOT NULL,
            object_type varchar(100) NOT NULL,
            object_id bigint(20) NOT NULL,
            details longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_action (action),
            KEY idx_object (object_type, object_id),
            KEY idx_created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($evaluations_sql);
        dbDelta($assignments_sql);
        dbDelta($audit_log_sql);
        
        // Update database version
        update_option('mt_db_version', MT_VERSION);
    }
    
    /**
     * Create default options
     *
     * @return void
     */
    private function create_options() {
        // General settings
        add_option('mt_settings', [
            'enable_jury_system' => true,
            'candidates_per_jury' => 5,
            'evaluation_deadline' => '',
            'show_results_publicly' => false
        ]);
        

        
        // Evaluation criteria weights (all equal by default)
        add_option('mt_criteria_weights', [
            'courage' => 1,
            'innovation' => 1,
            'implementation' => 1,
            'relevance' => 1,
            'visibility' => 1
        ]);
        
        // Dashboard customization settings
        add_option('mt_dashboard_settings', [
            'header_style' => 'gradient',
            'primary_color' => '#667eea',
            'secondary_color' => '#764ba2',
            'progress_bar_style' => 'rounded',
            'show_welcome_message' => 1,
            'show_progress_bar' => 1,
            'show_stats_cards' => 1,
            'show_search_filter' => 1,
            'card_layout' => 'grid',
            'intro_text' => '',
            'header_image_url' => ''
        ]);
        
        // Candidate presentation settings
        add_option('mt_candidate_presentation', [
            'profile_layout' => 'side-by-side',
            'photo_style' => 'rounded',
            'photo_size' => 'medium',
            'show_organization' => 1,
            'show_position' => 1,
            'show_category' => 1,
            'show_innovation_summary' => 1,
            'show_full_bio' => 1,
            'form_style' => 'cards',
            'scoring_style' => 'slider',
            'enable_animations' => 1,
            'enable_hover_effects' => 1
        ]);
    }
} 
