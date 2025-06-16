// api/backup-endpoints.php
class MT_Backup_API {
    
    public function register_routes() {
        // Create backup endpoint
        register_rest_route('mobility-trailblazers/v1', '/admin/create-backup', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_create_backup'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'reason' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'type' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'full',
                    'enum' => array('full', 'partial')
                )
            )
        ));
        
        // Get backup history endpoint
        register_rest_route('mobility-trailblazers/v1', '/backup-history', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_backup_history'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'page' => array(
                    'default' => 1,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    }
                ),
                'per_page' => array(
                    'default' => 100,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0 && $param <= 200;
                    }
                )
            )
        ));
        
        // Restore backup endpoint
        register_rest_route('mobility-trailblazers/v1', '/admin/restore-backup', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_restore_backup'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'backup_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    }
                ),
                'type' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => array('votes', 'scores')
                )
            )
        ));
    }
    
    /**
     * Check if user has admin permissions
     */
    public function check_admin_permission() {
        return current_user_can('manage_options');
    }
    
    /**
     * Handle create backup request
     */
    public function handle_create_backup($request) {
        $backup_manager = new \MobilityTrailblazers\VoteBackupManager();
        $reason = $request->get_param('reason') ?: 'Manual backup';
        $type = $request->get_param('type');
        
        global $wpdb;
        
        // Get all active votes for backup
        $where_conditions = array('is_active' => 1);
        
        // Create backup
        $result = $backup_manager->bulk_backup($where_conditions, $reason);
        
        if (is_wp_error($result)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $result->get_error_message()
            ), 400);
        }
        
        // Get updated statistics
        $stats = $backup_manager->get_backup_statistics();
        
        return new WP_REST_Response(array(
            'success' => true,
            'votes_backed_up' => $result['votes'],
            'scores_backed_up' => $result['scores'],
            'total_backed_up' => $result['count'],
            'storage_size' => $stats['storage_size'],
            'message' => sprintf(__('Successfully backed up %d items', 'mobility-trailblazers'), $result['count'])
        ), 200);
    }
    
    /**
     * Get backup history
     */
    public function get_backup_history($request) {
        global $wpdb;
        
        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $offset = ($page - 1) * $per_page;
        
        // Get combined backup history
        $query = "
            SELECT 
                'vote' as type,
                history_id,
                candidate_id,
                jury_member_id,
                backed_up_at,
                backup_reason,
                restored_at,
                1 as items_count
            FROM {$wpdb->prefix}mt_votes_history
            
            UNION ALL
            
            SELECT 
                'score' as type,
                history_id,
                candidate_id,
                jury_member_id,
                backed_up_at,
                backup_reason,
                restored_at,
                1 as items_count
            FROM {$wpdb->prefix}mt_candidate_scores_history
            
            ORDER BY backed_up_at DESC
            LIMIT %d OFFSET %d
        ";
        
        $backups = $wpdb->get_results($wpdb->prepare($query, $per_page, $offset));
        
        // Get total count
        $total_query = "
            SELECT COUNT(*) FROM (
                SELECT history_id FROM {$wpdb->prefix}mt_votes_history
                UNION ALL
                SELECT history_id FROM {$wpdb->prefix}mt_candidate_scores_history
            ) as combined
        ";
        $total = $wpdb->get_var($total_query);
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'backups' => $backups,
                'total' => intval($total),
                'pages' => ceil($total / $per_page),
                'current_page' => $page
            )
        ), 200);
    }
    
    /**
     * Handle restore backup request
     */
    public function handle_restore_backup($request) {
        $backup_manager = new \MobilityTrailblazers\VoteBackupManager();
        
        $backup_id = $request->get_param('backup_id');
        $type = $request->get_param('type');
        
        // Restore the backup
        $result = $backup_manager->restore_from_backup($backup_id, $type);
        
        if (is_wp_error($result)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $result->get_error_message()
            ), 400);
        }
        
        // Log the restoration
        $audit_logger = new \MT_Vote_Audit_Logger();
        $audit_logger->log_reset(array(
            'reset_type' => 'restore',
            'reset_reason' => sprintf('Restored from backup #%d', $backup_id),
            'votes_affected' => 1
        ));
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => __('Backup successfully restored', 'mobility-trailblazers')
        ), 200);
    }
} 