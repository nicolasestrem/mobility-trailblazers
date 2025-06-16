<?php
namespace MobilityTrailblazers;

/**
 * Vote Reset Manager Class
 * 
 * Handles all vote reset operations including individual resets,
 * bulk resets, and phase transitions.
 *
 * @package MobilityTrailblazers
 * @subpackage Includes
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VoteResetManager {
    
    /**
     * Backup manager instance
     * 
     * @var VoteBackupManager
     */
    private $backup_manager;
    
    /**
     * Audit logger instance
     * 
     * @var VoteAuditLogger
     */
    private $audit_logger;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->backup_manager = new VoteBackupManager();
        $this->audit_logger = new VoteAuditLogger();
    }
    
    /**
     * Reset vote for individual candidate by jury member
     * 
     * @param int $candidate_id
     * @param int $jury_member_id
     * @param string $reason Optional reason for reset
     * @return array|WP_Error Success array or error
     */
    public function reset_individual_vote($candidate_id, $jury_member_id, $reason = '') {
        global $wpdb;
        
        // Validate permissions
        if (!$this->can_reset_vote($jury_member_id, $candidate_id)) {
            return new WP_Error('permission_denied', __('You do not have permission to reset this vote', 'mobility-trailblazers'));
        }
        
        // Check if voting is locked
        if ($this->is_voting_locked($candidate_id)) {
            return new WP_Error('voting_locked', __('Voting is currently locked for this phase', 'mobility-trailblazers'));
        }
        
        // Begin transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Backup current vote
            $backup_result = $this->backup_manager->backup_vote($candidate_id, $jury_member_id, 'individual_reset');
            
            if (is_wp_error($backup_result)) {
                throw new Exception($backup_result->get_error_message());
            }
            
            // Soft delete the vote from mt_votes table
            $votes_result = $wpdb->update(
                $wpdb->prefix . 'mt_votes',
                array(
                    'is_active' => false,
                    'reset_at' => current_time('mysql'),
                    'reset_by' => get_current_user_id()
                ),
                array(
                    'candidate_id' => $candidate_id,
                    'jury_member_id' => $jury_member_id,
                    'is_active' => true
                ),
                array('%d', '%s', '%d'),
                array('%d', '%d', '%d')
            );
            
            // Soft delete from mt_candidate_scores table
            $scores_result = $wpdb->update(
                $wpdb->prefix . 'mt_candidate_scores',
                array(
                    'is_active' => false,
                    'reset_at' => current_time('mysql'),
                    'reset_by' => get_current_user_id()
                ),
                array(
                    'candidate_id' => $candidate_id,
                    'jury_member_id' => $jury_member_id,
                    'is_active' => true
                ),
                array('%d', '%s', '%d'),
                array('%d', '%d', '%d')
            );
            
            $total_affected = ($votes_result !== false ? $votes_result : 0) + 
                            ($scores_result !== false ? $scores_result : 0);
            
            if ($total_affected === 0) {
                throw new Exception(__('No active votes found to reset', 'mobility-trailblazers'));
            }
            
            // Log the action
            $this->audit_logger->log_reset(array(
                'reset_type' => 'individual',
                'initiated_by' => get_current_user_id(),
                'affected_candidate_id' => $candidate_id,
                'affected_user_id' => $jury_member_id,
                'reset_reason' => $reason,
                'votes_affected' => $total_affected,
                'timestamp' => current_time('mysql')
            ));
            
            // Clear cache
            $this->clear_vote_cache($candidate_id, $jury_member_id);
            
            $wpdb->query('COMMIT');
            
            // Trigger action for external processing
            do_action('mt_vote_reset', $candidate_id, $jury_member_id, 'individual');
            
            return array(
                'success' => true,
                'message' => __('Vote successfully reset', 'mobility-trailblazers'),
                'backup_id' => $backup_result,
                'votes_affected' => $total_affected
            );
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('reset_failed', $e->getMessage());
        }
    }
    
    /**
     * Bulk reset votes with various scopes
     * 
     * @param string $reset_scope Type of bulk reset
     * @param array $options Additional options
     * @return array|WP_Error Success array or error
     */
    public function bulk_reset_votes($reset_scope, $options = array()) {
        global $wpdb;
        
        // Validate admin permissions
        if (!current_user_can('manage_options')) {
            return new WP_Error('permission_denied', __('Admin access required', 'mobility-trailblazers'));
        }
        
        $wpdb->query('START TRANSACTION');
        
        try {
            $where_conditions = array('is_active' => true);
            $backup_reason = "bulk_reset_{$reset_scope}";
            
            switch ($reset_scope) {
                case 'phase_transition':
                    $where_conditions['voting_phase'] = $options['from_phase'] ?? get_option('mt_current_voting_phase', 'phase_1');
                    $backup_reason = sprintf(
                        'Phase transition from %s to %s',
                        $options['from_phase'] ?? 'current',
                        $options['to_phase'] ?? 'next'
                    );
                    break;
                    
                case 'all_user_votes':
                    if (empty($options['user_id'])) {
                        throw new Exception(__('User ID required for user vote reset', 'mobility-trailblazers'));
                    }
                    $where_conditions['jury_member_id'] = $options['user_id'];
                    break;
                    
                case 'all_candidate_votes':
                    if (empty($options['candidate_id'])) {
                        throw new Exception(__('Candidate ID required for candidate vote reset', 'mobility-trailblazers'));
                    }
                    $where_conditions['candidate_id'] = $options['candidate_id'];
                    break;
                    
                case 'full_reset':
                    // No additional conditions - reset all active votes
                    if (!isset($options['confirm']) || $options['confirm'] !== true) {
                        throw new Exception(__('Full reset requires explicit confirmation', 'mobility-trailblazers'));
                    }
                    break;
                    
                default:
                    throw new Exception(__('Invalid reset scope', 'mobility-trailblazers'));
            }
            
            // Backup all affected votes
            $backup_result = $this->backup_manager->bulk_backup($where_conditions, $backup_reason);
            
            if (is_wp_error($backup_result)) {
                throw new Exception($backup_result->get_error_message());
            }
            
            // Build WHERE clause for reset
            $where_parts = array();
            $where_values = array();
            
            foreach ($where_conditions as $field => $value) {
                if ($field === 'voting_phase') {
                    // Skip voting_phase as it's not in the votes tables
                    continue;
                }
                $where_parts[] = "$field = %s";
                $where_values[] = $value;
            }
            
            if (empty($where_parts)) {
                $where_parts[] = "is_active = %d";
                $where_values[] = 1;
            }
            
            $where_clause = implode(' AND ', $where_parts);
            
            // Reset votes in mt_votes table
            $votes_query = $wpdb->prepare(
                "UPDATE {$wpdb->prefix}mt_votes 
                 SET is_active = 0, 
                     reset_at = %s, 
                     reset_by = %d 
                 WHERE $where_clause",
                array_merge(
                    array(current_time('mysql'), get_current_user_id()),
                    $where_values
                )
            );
            
            $votes_affected = $wpdb->query($votes_query);
            
            // Reset scores in mt_candidate_scores table
            $scores_query = $wpdb->prepare(
                "UPDATE {$wpdb->prefix}mt_candidate_scores 
                 SET is_active = 0, 
                     reset_at = %s, 
                     reset_by = %d 
                 WHERE $where_clause",
                array_merge(
                    array(current_time('mysql'), get_current_user_id()),
                    $where_values
                )
            );
            
            $scores_affected = $wpdb->query($scores_query);
            
            $total_affected = ($votes_affected !== false ? $votes_affected : 0) + 
                            ($scores_affected !== false ? $scores_affected : 0);
            
            // Log the bulk action
            $this->audit_logger->log_reset(array(
                'reset_type' => "bulk_{$reset_scope}",
                'initiated_by' => get_current_user_id(),
                'reset_reason' => $options['reason'] ?? "Bulk reset: {$reset_scope}",
                'votes_affected' => $total_affected,
                'voting_phase' => $options['from_phase'] ?? null,
                'affected_user_id' => $options['user_id'] ?? null,
                'affected_candidate_id' => $options['candidate_id'] ?? null
            ));
            
            // Update voting phase if this is a phase transition
            if ($reset_scope === 'phase_transition' && !empty($options['to_phase'])) {
                update_option('mt_current_voting_phase', $options['to_phase']);
                update_option('mt_phase_transition_date', current_time('mysql'));
            }
            
            // Send notifications if requested
            if (!empty($options['notify_jury']) && $options['notify_jury'] === true) {
                $this->send_reset_notifications($reset_scope, $options);
            }
            
            // Clear all relevant caches
            $this->clear_all_vote_caches();
            
            $wpdb->query('COMMIT');
            
            // Trigger action for external processing
            do_action('mt_bulk_vote_reset', $reset_scope, $total_affected, $options);
            
            return array(
                'success' => true,
                'votes_reset' => $total_affected,
                'votes_affected' => $votes_affected,
                'scores_affected' => $scores_affected,
                'backup_count' => $backup_result['count'] ?? 0,
                'message' => sprintf(
                    __('%d votes have been reset', 'mobility-trailblazers'),
                    $total_affected
                )
            );
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('bulk_reset_failed', $e->getMessage());
        }
    }
    
    /**
     * Check if user can reset a specific vote
     * 
     * @param int $user_id
     * @param int $candidate_id
     * @return bool
     */
    private function can_reset_vote($user_id, $candidate_id) {
        // Admins can always reset
        if (user_can($user_id, 'manage_options')) {
            return true;
        }
        
        // Jury members can only reset their own votes
        global $wpdb;
        $has_vote = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes 
             WHERE jury_member_id = %d 
             AND candidate_id = %d 
             AND is_active = 1",
            $user_id,
            $candidate_id
        ));
        
        // Also check in scores table
        if (!$has_vote) {
            $has_vote = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores 
                 WHERE jury_member_id = %d 
                 AND candidate_id = %d 
                 AND is_active = 1",
                $user_id,
                $candidate_id
            ));
        }
        
        return $has_vote > 0 && get_current_user_id() == $user_id;
    }
    
    /**
     * Check if voting is locked
     * 
     * @param int $candidate_id Optional specific candidate
     * @return bool
     */
    private function is_voting_locked($candidate_id = null) {
        // Check if current voting phase is locked
        $current_phase = get_option('mt_current_voting_phase', 'phase_1');
        $phase_status = get_option("mt_voting_phase_{$current_phase}_status", 'open');
        
        // Apply filter for custom locking logic
        return apply_filters('mt_is_voting_locked', $phase_status === 'locked', $candidate_id, $current_phase);
    }
    
    /**
     * Clear vote cache for specific candidate and jury member
     * 
     * @param int $candidate_id
     * @param int $jury_member_id
     * @return void
     */
    private function clear_vote_cache($candidate_id, $jury_member_id) {
        // Clear Redis cache if available
        if (defined('WP_REDIS_HOST')) {
            $cache_key = "mt_vote_{$candidate_id}_{$jury_member_id}";
            wp_cache_delete($cache_key, 'mobility_trailblazers');
            
            // Clear related keys
            wp_cache_delete("mt_candidate_score_{$candidate_id}", 'mobility_trailblazers');
            wp_cache_delete("mt_jury_progress_{$jury_member_id}", 'mobility_trailblazers');
        }
        
        // Clear WordPress transients
        delete_transient("mt_candidate_scores_{$candidate_id}");
        delete_transient("mt_jury_progress_{$jury_member_id}");
        delete_transient("mt_voting_stats");
    }
    
    /**
     * Clear all voting-related caches
     * 
     * @return void
     */
    private function clear_all_vote_caches() {
        // Clear all voting-related caches
        if (defined('WP_REDIS_HOST')) {
            wp_cache_flush_group('mobility_trailblazers');
        }
        
        // Clear all transients with our prefix
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mt_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_mt_%'");
        
        // Clear any page caches
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }
    }
    
    /**
     * Send reset notifications
     * 
     * @param string $reset_type
     * @param array $options
     * @return void
     */
    private function send_reset_notifications($reset_type, $options = array()) {
        // Get jury members
        $jury_members = get_users(array(
            'role' => 'mt_jury_member',
            'fields' => array('ID', 'user_email', 'display_name')
        ));
        
        if (empty($jury_members)) {
            return;
        }
        
        // Prepare email content based on reset type
        $subject = '';
        $message = '';
        
        switch ($reset_type) {
            case 'phase_transition':
                $subject = __('Voting Phase Transition - Action Required', 'mobility-trailblazers');
                $message = sprintf(
                    __('Dear %s,\n\nThe voting has moved from %s to %s. Please log in to submit your evaluations for the new phase.\n\nBest regards,\nMobility Trailblazers Team', 'mobility-trailblazers'),
                    '%display_name%',
                    $options['from_phase'] ?? 'previous phase',
                    $options['to_phase'] ?? 'next phase'
                );
                break;
                
            case 'full_reset':
                $subject = __('Voting System Reset - Action Required', 'mobility-trailblazers');
                $message = sprintf(
                    __('Dear %s,\n\nThe voting system has been reset. All previous votes have been archived. Please log in to submit new evaluations.\n\nBest regards,\nMobility Trailblazers Team', 'mobility-trailblazers'),
                    '%display_name%'
                );
                break;
        }
        
        // Send emails
        foreach ($jury_members as $member) {
            $personalized_message = str_replace('%display_name%', $member->display_name, $message);
            wp_mail($member->user_email, $subject, $personalized_message);
        }
        
        // Log notification sending
        do_action('mt_reset_notifications_sent', $reset_type, count($jury_members));
    }
    
    /**
     * Get reset statistics
     * 
     * @return array
     */
    public function get_reset_statistics() {
        global $wpdb;
        
        $stats = array();
        
        // Total resets
        $stats['total_resets'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vote_reset_logs"
        );
        
        // Resets by type
        $stats['by_type'] = $wpdb->get_results(
            "SELECT reset_type, COUNT(*) as count 
             FROM {$wpdb->prefix}vote_reset_logs 
             GROUP BY reset_type"
        );
        
        // Recent resets (last 30 days)
        $stats['recent_resets'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vote_reset_logs 
             WHERE reset_timestamp > %s",
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));
        
        // Total votes affected
        $stats['total_votes_affected'] = $wpdb->get_var(
            "SELECT SUM(votes_affected) FROM {$wpdb->prefix}vote_reset_logs"
        );
        
        return $stats;
    }
}