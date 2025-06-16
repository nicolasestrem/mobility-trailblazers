<?php
namespace MobilityTrailblazers;

/**
 * Vote Backup Manager Class
 * 
 * Handles backup and restoration of votes
 *
 * @package MobilityTrailblazers
 * @subpackage Includes
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VoteBackupManager {
    
    /**
     * Backup a vote
     * 
     * @param int $candidate_id
     * @param int $jury_member_id
     * @param string $reason
     * @return int|WP_Error Backup ID or error
     */
    public function backup_vote($candidate_id, $jury_member_id, $reason = '') {
        global $wpdb;
        
        try {
            // Get the vote data
            $vote = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}mt_votes 
                 WHERE candidate_id = %d 
                 AND jury_member_id = %d 
                 AND is_active = 1",
                $candidate_id,
                $jury_member_id
            ));
            
            if (!$vote) {
                return new \WP_Error('no_vote', __('No active vote found to backup', 'mobility-trailblazers'));
            }
            
            // Create backup
            $result = $wpdb->insert(
                $wpdb->prefix . 'mt_vote_backups',
                array(
                    'candidate_id' => $candidate_id,
                    'jury_member_id' => $jury_member_id,
                    'vote_round' => $vote->vote_round,
                    'score' => $vote->score,
                    'comments' => $vote->comments,
                    'backup_reason' => $reason,
                    'backup_date' => current_time('mysql'),
                    'backup_by' => get_current_user_id()
                ),
                array('%d', '%d', '%d', '%f', '%s', '%s', '%s', '%d')
            );
            
            if ($result === false) {
                throw new \Exception(__('Failed to create vote backup', 'mobility-trailblazers'));
            }
            
            return $wpdb->insert_id;
            
        } catch (\Exception $e) {
            return new \WP_Error('backup_failed', $e->getMessage());
        }
    }
    
    /**
     * Restore a vote from backup
     * 
     * @param int $backup_id
     * @return bool|WP_Error Success or error
     */
    public function restore_vote($backup_id) {
        global $wpdb;
        
        try {
            // Get the backup data
            $backup = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}mt_vote_backups WHERE id = %d",
                $backup_id
            ));
            
            if (!$backup) {
                return new \WP_Error('no_backup', __('Backup not found', 'mobility-trailblazers'));
            }
            
            // Begin transaction
            $wpdb->query('START TRANSACTION');
            
            // Delete any existing active vote
            $wpdb->delete(
                $wpdb->prefix . 'mt_votes',
                array(
                    'candidate_id' => $backup->candidate_id,
                    'jury_member_id' => $backup->jury_member_id,
                    'is_active' => 1
                ),
                array('%d', '%d', '%d')
            );
            
            // Restore the vote
            $result = $wpdb->insert(
                $wpdb->prefix . 'mt_votes',
                array(
                    'candidate_id' => $backup->candidate_id,
                    'jury_member_id' => $backup->jury_member_id,
                    'vote_round' => $backup->vote_round,
                    'score' => $backup->score,
                    'comments' => $backup->comments,
                    'is_active' => 1,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%d', '%d', '%d', '%f', '%s', '%d', '%s', '%s')
            );
            
            if ($result === false) {
                throw new \Exception(__('Failed to restore vote', 'mobility-trailblazers'));
            }
            
            // Log the restoration
            mt_log_action(
                'vote_restored',
                sprintf(
                    'Restored vote for candidate %d by jury member %d from backup %d',
                    $backup->candidate_id,
                    $backup->jury_member_id,
                    $backup_id
                )
            );
            
            $wpdb->query('COMMIT');
            return true;
            
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            return new \WP_Error('restore_failed', $e->getMessage());
        }
    }
    
    /**
     * Get vote backup history
     * 
     * @param array $args Query arguments
     * @return array Backup records
     */
    public function get_backup_history($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'candidate_id' => null,
            'jury_member_id' => null,
            'limit' => 10,
            'offset' => 0,
            'orderby' => 'backup_date',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        $values = array();
        
        if ($args['candidate_id']) {
            $where[] = 'candidate_id = %d';
            $values[] = $args['candidate_id'];
        }
        
        if ($args['jury_member_id']) {
            $where[] = 'jury_member_id = %d';
            $values[] = $args['jury_member_id'];
        }
        
        $where_clause = implode(' AND ', $where);
        
        $query = $wpdb->prepare(
            "SELECT b.*, 
                    c.post_title as candidate_name,
                    u.display_name as jury_member_name,
                    bu.display_name as backup_by_name
             FROM {$wpdb->prefix}mt_vote_backups b
             LEFT JOIN {$wpdb->posts} c ON b.candidate_id = c.ID
             LEFT JOIN {$wpdb->users} u ON b.jury_member_id = u.ID
             LEFT JOIN {$wpdb->users} bu ON b.backup_by = bu.ID
             WHERE $where_clause
             ORDER BY {$args['orderby']} {$args['order']}
             LIMIT %d OFFSET %d",
            array_merge($values, array($args['limit'], $args['offset']))
        );
        
        return $wpdb->get_results($query);
    }
}