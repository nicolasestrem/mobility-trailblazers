<?php
/**
 * REST API Handler
 *
 * @package MobilityTrailblazers
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_REST_API
 * Handles all REST API endpoints
 */
class MT_REST_API {
    
    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Backup routes
        register_rest_route('mt/v1', '/backup/create', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_create_backup'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));
        
        register_rest_route('mt/v1', '/backup/history', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_backup_history'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));
        
        register_rest_route('mt/v1', '/backup/restore', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_restore_backup'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));
        
        // Vote reset routes
        register_rest_route('mt/v1', '/vote/reset', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_reset_vote'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));
        
        register_rest_route('mt/v1', '/vote/bulk-reset', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_bulk_reset'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));
        
        register_rest_route('mt/v1', '/vote/reset-history', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_reset_history'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));
    }
    
    /**
     * Check if user has admin permissions
     */
    public function check_admin_permission() {
        return current_user_can('manage_options') || current_user_can('mt_manage_awards');
    }
    
    /**
     * Handle create backup request
     */
    public function handle_create_backup($request) {
        try {
            // Get parameters
            $type = $request->get_param('type');
            $description = $request->get_param('description');
            
            // Validate type
            if (!in_array($type, array('full', 'votes', 'evaluations', 'assignments'))) {
                return new WP_Error('invalid_type', 'Invalid backup type', array('status' => 400));
            }
            
            // Create backup based on type
            $backup_data = array();
            
            if ($type === 'full' || $type === 'votes') {
                $backup_data['votes'] = $this->get_all_votes();
            }
            
            if ($type === 'full' || $type === 'evaluations') {
                $backup_data['evaluations'] = $this->get_all_evaluations();
            }
            
            if ($type === 'full' || $type === 'assignments') {
                $backup_data['assignments'] = $this->get_all_assignments();
            }
            
            // Save backup
            $backup_id = $this->save_backup($backup_data, $type, $description);
            
            if (!$backup_id) {
                return new WP_Error('backup_failed', 'Failed to create backup', array('status' => 500));
            }
            
            // Log the backup
            if (class_exists('MT_Vote_Audit_Logger')) {
                MT_Vote_Audit_Logger::log('backup_created', array(
                    'backup_id' => $backup_id,
                    'type' => $type,
                    'description' => $description,
                    'user_id' => get_current_user_id()
                ));
            }
            
            return rest_ensure_response(array(
                'success' => true,
                'backup_id' => $backup_id,
                'message' => __('Backup created successfully', 'mobility-trailblazers')
            ));
            
        } catch (Exception $e) {
            return new WP_Error('backup_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Get backup history
     */
    public function get_backup_history($request) {
        $page = $request->get_param('page') ?: 1;
        $per_page = $request->get_param('per_page') ?: 10;
        $type = $request->get_param('type');
        
        $args = array(
            'post_type' => 'mt_backup',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'date',
            'order' => 'DESC',
            'post_status' => 'private'
        );
        
        if ($type) {
            $args['meta_query'] = array(
                array(
                    'key' => 'backup_type',
                    'value' => $type
                )
            );
        }
        
        $backups = get_posts($args);
        $total = wp_count_posts('mt_backup')->private;
        
        $items = array();
        foreach ($backups as $backup) {
            $items[] = array(
                'id' => $backup->ID,
                'date' => $backup->post_date,
                'type' => get_post_meta($backup->ID, 'backup_type', true),
                'description' => get_post_meta($backup->ID, 'description', true),
                'size' => get_post_meta($backup->ID, 'backup_size', true),
                'created_by' => get_the_author_meta('display_name', $backup->post_author)
            );
        }
        
        return rest_ensure_response(array(
            'items' => $items,
            'total' => $total,
            'pages' => ceil($total / $per_page),
            'page' => $page
        ));
    }
    
    /**
     * Handle restore backup request
     */
    public function handle_restore_backup($request) {
        $backup_id = $request->get_param('backup_id');
        
        if (!$backup_id) {
            return new WP_Error('missing_backup_id', 'Backup ID is required', array('status' => 400));
        }
        
        // Get backup data
        $backup = get_post($backup_id);
        if (!$backup || $backup->post_type !== 'mt_backup') {
            return new WP_Error('invalid_backup', 'Invalid backup ID', array('status' => 404));
        }
        
        // Restore backup
        $backup_data = get_post_meta($backup_id, 'backup_data', true);
        if (!$backup_data) {
            return new WP_Error('no_backup_data', 'No backup data found', array('status' => 404));
        }
        
        try {
            $restored = $this->restore_backup_data($backup_data);
            
            // Log the restore
            if (class_exists('MT_Vote_Audit_Logger')) {
                MT_Vote_Audit_Logger::log('backup_restored', array(
                    'backup_id' => $backup_id,
                    'restored_items' => $restored,
                    'user_id' => get_current_user_id()
                ));
            }
            
            return rest_ensure_response(array(
                'success' => true,
                'restored' => $restored,
                'message' => __('Backup restored successfully', 'mobility-trailblazers')
            ));
            
        } catch (Exception $e) {
            return new WP_Error('restore_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * Handle reset vote request
     */
    public function handle_reset_vote($request) {
        $vote_id = $request->get_param('vote_id');
        $reason = $request->get_param('reason');
        
        if (!$vote_id) {
            return new WP_Error('missing_vote_id', 'Vote ID is required', array('status' => 400));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'mt_votes';
        
        // Get vote details before deletion
        $vote = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $vote_id
        ));
        
        if (!$vote) {
            return new WP_Error('vote_not_found', 'Vote not found', array('status' => 404));
        }
        
        // Delete the vote
        $deleted = $wpdb->delete($table_name, array('id' => $vote_id));
        
        if ($deleted === false) {
            return new WP_Error('delete_failed', 'Failed to delete vote', array('status' => 500));
        }
        
        // Log the reset
        if (class_exists('MT_Vote_Audit_Logger')) {
            MT_Vote_Audit_Logger::log('vote_reset', array(
                'vote_id' => $vote_id,
                'candidate_id' => $vote->candidate_id,
                'jury_member_id' => $vote->jury_member_id,
                'reason' => $reason,
                'user_id' => get_current_user_id()
            ));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Vote reset successfully', 'mobility-trailblazers')
        ));
    }
    
    /**
     * Handle bulk reset request
     */
    public function handle_bulk_reset($request) {
        $type = $request->get_param('type');
        $candidate_id = $request->get_param('candidate_id');
        $reason = $request->get_param('reason');
        
        if (!in_array($type, array('candidate', 'all'))) {
            return new WP_Error('invalid_type', 'Invalid reset type', array('status' => 400));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'mt_votes';
        
        if ($type === 'candidate' && !$candidate_id) {
            return new WP_Error('missing_candidate', 'Candidate ID is required', array('status' => 400));
        }
        
        // Perform reset
        if ($type === 'candidate') {
            $deleted = $wpdb->delete($table_name, array('candidate_id' => $candidate_id));
        } else {
            $deleted = $wpdb->query("TRUNCATE TABLE $table_name");
        }
        
        if ($deleted === false) {
            return new WP_Error('reset_failed', 'Failed to reset votes', array('status' => 500));
        }
        
        // Log the reset
        if (class_exists('MT_Vote_Audit_Logger')) {
            MT_Vote_Audit_Logger::log('bulk_reset', array(
                'type' => $type,
                'candidate_id' => $candidate_id,
                'affected_rows' => $deleted,
                'reason' => $reason,
                'user_id' => get_current_user_id()
            ));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'affected' => $deleted,
            'message' => sprintf(__('%d votes reset successfully', 'mobility-trailblazers'), $deleted)
        ));
    }
    
    /**
     * Get reset history
     */
    public function get_reset_history($request) {
        $page = $request->get_param('page') ?: 1;
        $per_page = $request->get_param('per_page') ?: 20;
        
        if (!class_exists('MT_Vote_Audit_Logger')) {
            return rest_ensure_response(array(
                'items' => array(),
                'total' => 0,
                'pages' => 0,
                'page' => $page
            ));
        }
        
        $logs = MT_Vote_Audit_Logger::get_logs(array(
            'action' => array('vote_reset', 'bulk_reset'),
            'page' => $page,
            'per_page' => $per_page
        ));
        
        return rest_ensure_response(array(
            'items' => $logs['items'],
            'total' => $logs['total'],
            'pages' => $logs['pages'],
            'page' => $page
        ));
    }
    
    // Helper methods
    
    /**
     * Get all votes
     */
    private function get_all_votes() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mt_votes");
    }
    
    /**
     * Get all evaluations
     */
    private function get_all_evaluations() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mt_candidate_scores");
    }
    
    /**
     * Get all assignments
     */
    private function get_all_assignments() {
        $assignments = array();
        
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        foreach ($jury_members as $jury) {
            $assigned = get_post_meta($jury->ID, 'assigned_candidates', true);
            if ($assigned) {
                $assignments[$jury->ID] = $assigned;
            }
        }
        
        return $assignments;
    }
    
    /**
     * Save backup
     */
    private function save_backup($data, $type, $description) {
        $backup_post = array(
            'post_title' => sprintf('Backup - %s - %s', $type, current_time('mysql')),
            'post_type' => 'mt_backup',
            'post_status' => 'private',
            'post_content' => ''
        );
        
        $backup_id = wp_insert_post($backup_post);
        
        if ($backup_id) {
            update_post_meta($backup_id, 'backup_data', $data);
            update_post_meta($backup_id, 'backup_type', $type);
            update_post_meta($backup_id, 'description', $description);
            update_post_meta($backup_id, 'backup_size', strlen(serialize($data)));
        }
        
        return $backup_id;
    }
    
    /**
     * Restore backup data
     */
    private function restore_backup_data($data) {
        global $wpdb;
        $restored = array();
        
        // Restore votes
        if (isset($data['votes'])) {
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}mt_votes");
            foreach ($data['votes'] as $vote) {
                $wpdb->insert($wpdb->prefix . 'mt_votes', (array) $vote);
            }
            $restored['votes'] = count($data['votes']);
        }
        
        // Restore evaluations
        if (isset($data['evaluations'])) {
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}mt_candidate_scores");
            foreach ($data['evaluations'] as $evaluation) {
                $wpdb->insert($wpdb->prefix . 'mt_candidate_scores', (array) $evaluation);
            }
            $restored['evaluations'] = count($data['evaluations']);
        }
        
        // Restore assignments
        if (isset($data['assignments'])) {
            foreach ($data['assignments'] as $jury_id => $candidates) {
                update_post_meta($jury_id, 'assigned_candidates', $candidates);
            }
            $restored['assignments'] = count($data['assignments']);
        }
        
        return $restored;
    }
} 