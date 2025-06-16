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
    
    /**
     * Bulk backup votes based on conditions
     * 
     * @param array $where_conditions Conditions for selecting votes to backup
     * @param string $reason Reason for the backup
     * @return array|WP_Error Success array with counts or error
     */
    public function bulk_backup($where_conditions = array(), $reason = '') {
        global $wpdb;
        
        try {
            $wpdb->query('START TRANSACTION');
            
            $votes_backed_up = 0;
            $scores_backed_up = 0;
            
            // Build WHERE clause
            $where_parts = array();
            $where_values = array();
            
            foreach ($where_conditions as $field => $value) {
                if ($field === 'voting_phase') {
                    // Skip voting_phase as it's not in the database tables
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
            
            // Backup votes from mt_votes table
            $votes_query = $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}mt_votes WHERE $where_clause",
                $where_values
            );
            
            $votes = $wpdb->get_results($votes_query);
            
            foreach ($votes as $vote) {
                $result = $wpdb->insert(
                    $wpdb->prefix . 'mt_vote_backups',
                    array(
                        'candidate_id' => $vote->candidate_id,
                        'jury_member_id' => $vote->jury_member_id,
                        'vote_round' => $vote->vote_round ?? 1,
                        'score' => $vote->score ?? 0,
                        'comments' => $vote->comments ?? '',
                        'backup_reason' => $reason,
                        'backup_date' => current_time('mysql'),
                        'backup_by' => get_current_user_id(),
                        'original_vote_id' => $vote->id ?? null
                    ),
                    array('%d', '%d', '%d', '%f', '%s', '%s', '%s', '%d', '%d')
                );
                
                if ($result !== false) {
                    $votes_backed_up++;
                }
            }
            
            // Backup scores from mt_candidate_scores table
            $scores_query = $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}mt_candidate_scores WHERE $where_clause",
                $where_values
            );
            
            $scores = $wpdb->get_results($scores_query);
            
            foreach ($scores as $score) {
                $result = $wpdb->insert(
                    $wpdb->prefix . 'mt_vote_backups',
                    array(
                        'candidate_id' => $score->candidate_id,
                        'jury_member_id' => $score->jury_member_id,
                        'vote_round' => $score->vote_round ?? 1,
                        'score' => $score->score ?? 0,
                        'comments' => $score->comments ?? '',
                        'backup_reason' => $reason . ' (score)',
                        'backup_date' => current_time('mysql'),
                        'backup_by' => get_current_user_id(),
                        'original_score_id' => $score->id ?? null
                    ),
                    array('%d', '%d', '%d', '%f', '%s', '%s', '%s', '%d', '%d')
                );
                
                if ($result !== false) {
                    $scores_backed_up++;
                }
            }
            
            $total_backed_up = $votes_backed_up + $scores_backed_up;
            
            if ($total_backed_up === 0) {
                throw new \Exception(__('No items found to backup', 'mobility-trailblazers'));
            }
            
            // Log the bulk backup action
            if (function_exists('mt_log_action')) {
                mt_log_action(
                    'bulk_backup_created',
                    sprintf(
                        'Bulk backup created: %d votes, %d scores. Reason: %s',
                        $votes_backed_up,
                        $scores_backed_up,
                        $reason
                    )
                );
            }
            
            $wpdb->query('COMMIT');
            
            return array(
                'success' => true,
                'votes' => $votes_backed_up,
                'scores' => $scores_backed_up,
                'count' => $total_backed_up,
                'message' => sprintf(
                    __('Successfully backed up %d votes and %d scores', 'mobility-trailblazers'),
                    $votes_backed_up,
                    $scores_backed_up
                )
            );
            
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            return new \WP_Error('bulk_backup_failed', $e->getMessage());
        }
    }
    
    /**
     * Get backup statistics
     * 
     * @return array Statistics about backups
     */
    public function get_backup_statistics() {
        global $wpdb;
        
        // Get total backup count
        $total_backups = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mt_vote_backups"
        );
        
        // Get backup count by reason
        $backup_reasons = $wpdb->get_results(
            "SELECT backup_reason, COUNT(*) as count 
             FROM {$wpdb->prefix}mt_vote_backups 
             GROUP BY backup_reason 
             ORDER BY count DESC"
        );
        
        // Get recent backup activity (last 30 days)
        $recent_activity = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mt_vote_backups 
             WHERE backup_date >= %s",
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));
        
        // Calculate storage size (approximate)
        $storage_size = $wpdb->get_var(
            "SELECT 
                SUM(
                    LENGTH(COALESCE(comments, '')) + 
                    LENGTH(COALESCE(backup_reason, '')) + 
                    50
                ) as total_size
             FROM {$wpdb->prefix}mt_vote_backups"
        );
        
        // Get backup count by user
        $backup_by_user = $wpdb->get_results(
            "SELECT u.display_name, COUNT(b.id) as backup_count
             FROM {$wpdb->prefix}mt_vote_backups b
             LEFT JOIN {$wpdb->users} u ON b.backup_by = u.ID
             GROUP BY b.backup_by, u.display_name
             ORDER BY backup_count DESC
             LIMIT 10"
        );
        
        return array(
            'total_backups' => intval($total_backups),
            'recent_activity' => intval($recent_activity),
            'storage_size' => $this->format_bytes(intval($storage_size)),
            'storage_size_bytes' => intval($storage_size),
            'backup_reasons' => $backup_reasons,
            'backup_by_user' => $backup_by_user,
            'last_updated' => current_time('mysql')
        );
    }
    
    /**
     * Format bytes to human readable format
     * 
     * @param int $bytes
     * @return string
     */
    private function format_bytes($bytes) {
        if ($bytes === 0) {
            return '0 B';
        }
        
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $base = log($bytes, 1024);
        
        return round(pow(1024, $base - floor($base)), 2) . ' ' . $units[floor($base)];
    }
    
    /**
     * Restore from backup by backup ID and type
     * 
     * @param int $backup_id The backup ID to restore from
     * @param string $type The type of backup ('votes' or 'scores')
     * @return bool|WP_Error Success or error
     */
    public function restore_from_backup($backup_id, $type) {
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
            
            if ($type === 'votes' || $type === 'both') {
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
            }
            
            if ($type === 'scores' || $type === 'both') {
                // Delete any existing active score
                $wpdb->delete(
                    $wpdb->prefix . 'mt_candidate_scores',
                    array(
                        'candidate_id' => $backup->candidate_id,
                        'jury_member_id' => $backup->jury_member_id,
                        'is_active' => 1
                    ),
                    array('%d', '%d', '%d')
                );
                
                // Restore the score
                $result = $wpdb->insert(
                    $wpdb->prefix . 'mt_candidate_scores',
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
                    throw new \Exception(__('Failed to restore score', 'mobility-trailblazers'));
                }
            }
            
            // Log the restoration
            if (function_exists('mt_log_action')) {
                mt_log_action(
                    'backup_restored',
                    sprintf(
                        'Restored %s for candidate %d by jury member %d from backup %d',
                        $type,
                        $backup->candidate_id,
                        $backup->jury_member_id,
                        $backup_id
                    )
                );
            }
            
            $wpdb->query('COMMIT');
            return true;
            
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            return new \WP_Error('restore_failed', $e->getMessage());
        }
    }
}