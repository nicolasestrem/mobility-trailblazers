// class-vote-reset-manager.php
class MT_Vote_Reset_Manager {
    
    private $backup_manager;
    private $audit_logger;
    
    public function __construct() {
        $this->backup_manager = new MT_Vote_Backup_Manager();
        $this->audit_logger = new MT_Vote_Audit_Logger();
    }
    
    /**
     * Reset vote for individual candidate by jury member
     */
    public function reset_individual_vote($candidate_id, $jury_member_id, $reason = '') {
        global $wpdb;
        
        // Validate permissions
        if (!$this->can_reset_vote($jury_member_id, $candidate_id)) {
            return new WP_Error('permission_denied', 'You do not have permission to reset this vote');
        }
        
        // Check if voting is locked
        if ($this->is_voting_locked($candidate_id)) {
            return new WP_Error('voting_locked', 'Voting is currently locked for this phase');
        }
        
        // Begin transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Backup current vote
            $backup_result = $this->backup_manager->backup_vote($candidate_id, $jury_member_id, 'individual_reset');
            
            if (is_wp_error($backup_result)) {
                throw new Exception($backup_result->get_error_message());
            }
            
            // Soft delete the vote
            $result = $wpdb->update(
                'jury_votes',
                array(
                    'is_active' => false,
                    'reset_at' => current_time('mysql'),
                    'reset_by' => get_current_user_id()
                ),
                array(
                    'candidate_id' => $candidate_id,
                    'jury_member_id' => $jury_member_id,
                    'is_active' => true
                )
            );
            
            if ($result === false) {
                throw new Exception('Failed to reset vote');
            }
            
            // Log the action
            $this->audit_logger->log_reset(array(
                'reset_type' => 'individual',
                'initiated_by' => get_current_user_id(),
                'affected_candidate_id' => $candidate_id,
                'affected_user_id' => $jury_member_id,
                'reset_reason' => $reason,
                'votes_affected' => $result
            ));
            
            // Clear cache
            $this->clear_vote_cache($candidate_id, $jury_member_id);
            
            $wpdb->query('COMMIT');
            
            return array(
                'success' => true,
                'message' => 'Vote successfully reset',
                'backup_id' => $backup_result
            );
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('reset_failed', $e->getMessage());
        }
    }
    
    /**
     * Bulk reset votes with various scopes
     */
    public function bulk_reset_votes($reset_scope, $options = array()) {
        global $wpdb;
        
        // Validate admin permissions
        if (!current_user_can('manage_options')) {
            return new WP_Error('permission_denied', 'Admin access required');
        }
        
        $wpdb->query('START TRANSACTION');
        
        try {
            $where_conditions = array('is_active' => true);
            $backup_reason = "bulk_reset_{$reset_scope}";
            
            switch ($reset_scope) {
                case 'phase_transition':
                    $where_conditions['voting_phase'] = $options['from_phase'];
                    $backup_reason = "phase_transition_{$options['from_phase']}_to_{$options['to_phase']}";
                    break;
                    
                case 'all_user_votes':
                    $where_conditions['jury_member_id'] = $options['user_id'];
                    break;
                    
                case 'all_candidate_votes':
                    $where_conditions['candidate_id'] = $options['candidate_id'];
                    break;
                    
                case 'full_reset':
                    // No additional conditions - reset all active votes
                    if (!isset($options['confirm']) || $options['confirm'] !== true) {
                        throw new Exception('Full reset requires explicit confirmation');
                    }
                    break;
                    
                default:
                    throw new Exception('Invalid reset scope');
            }
            
            // Backup all affected votes
            $backup_result = $this->backup_manager->bulk_backup($where_conditions, $backup_reason);
            
            if (is_wp_error($backup_result)) {
                throw new Exception($backup_result->get_error_message());
            }
            
            // Perform the reset
            $where_sql = array();
            foreach ($where_conditions as $field => $value) {
                $where_sql[] = $wpdb->prepare("{$field} = %s", $value);
            }
            $where_clause = implode(' AND ', $where_sql);
            
            $query = "UPDATE jury_votes 
                     SET is_active = 0, 
                         reset_at = %s, 
                         reset_by = %d 
                     WHERE {$where_clause}";
                     
            $affected_rows = $wpdb->query($wpdb->prepare(
                $query,
                current_time('mysql'),
                get_current_user_id()
            ));
            
            // Log the bulk action
            $this->audit_logger->log_reset(array(
                'reset_type' => "bulk_{$reset_scope}",
                'initiated_by' => get_current_user_id(),
                'reset_reason' => $options['reason'] ?? "Bulk reset: {$reset_scope}",
                'votes_affected' => $affected_rows,
                'voting_phase' => $options['from_phase'] ?? null
            ));
            
            // Clear all relevant caches
            $this->clear_all_vote_caches();
            
            $wpdb->query('COMMIT');
            
            return array(
                'success' => true,
                'votes_reset' => $affected_rows,
                'backup_count' => $backup_result['count'],
                'message' => sprintf('%d votes have been reset', $affected_rows)
            );
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('bulk_reset_failed', $e->getMessage());
        }
    }
    
    private function can_reset_vote($user_id, $candidate_id) {
        // Admins can always reset
        if (user_can($user_id, 'manage_options')) {
            return true;
        }
        
        // Jury members can only reset their own votes
        global $wpdb;
        $has_vote = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM jury_votes 
             WHERE jury_member_id = %d 
             AND candidate_id = %d 
             AND is_active = 1",
            $user_id,
            $candidate_id
        ));
        
        return $has_vote > 0;
    }
    
    private function is_voting_locked($candidate_id = null) {
        // Check if current voting phase is locked
        $current_phase = get_option('mt_current_voting_phase', 'open');
        $phase_status = get_option("mt_voting_phase_{$current_phase}_status", 'open');
        
        return $phase_status === 'locked';
    }
    
    private function clear_vote_cache($candidate_id, $jury_member_id) {
        // Clear Redis cache if available
        if (defined('WP_REDIS_HOST')) {
            $cache_key = "mt_vote_{$candidate_id}_{$jury_member_id}";
            wp_cache_delete($cache_key, 'mobility_trailblazers');
        }
        
        // Clear WordPress transients
        delete_transient("mt_candidate_scores_{$candidate_id}");
        delete_transient("mt_jury_progress_{$jury_member_id}");
    }
    
    private function clear_all_vote_caches() {
        // Clear all voting-related caches
        if (defined('WP_REDIS_HOST')) {
            wp_cache_flush_group('mobility_trailblazers');
        }
        
        // Clear all transients with our prefix
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mt_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_mt_%'");
    }
}