<?php
/**
 * Vote Backup Manager Class
 * 
 * Handles backup operations for votes before reset operations,
 * ensuring data recovery capabilities and audit compliance.
 *
 * @package MobilityTrailblazers
 * @subpackage Includes
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class MT_Vote_Backup_Manager {
    
    /**
     * Table names
     * 
     * @var array
     */
    private $tables;
    
    /**
     * Maximum backup retention days
     * 
     * @var int
     */
    private $retention_days = 365;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        
        $this->tables = array(
            'votes' => $wpdb->prefix . 'mt_votes',
            'votes_history' => $wpdb->prefix . 'mt_votes_history',
            'scores' => $wpdb->prefix . 'mt_candidate_scores',
            'scores_history' => $wpdb->prefix . 'mt_candidate_scores_history'
        );
        
        // Allow filtering of retention days
        $this->retention_days = apply_filters('mt_vote_backup_retention_days', $this->retention_days);
    }
    
    /**
     * Backup a single vote
     * 
     * @param int $candidate_id
     * @param int $jury_member_id
     * @param string $reason Backup reason
     * @return int|WP_Error Backup ID on success, WP_Error on failure
     */
    public function backup_vote($candidate_id, $jury_member_id, $reason = '') {
        global $wpdb;
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            $backup_ids = array();
            
            // Backup from votes table
            $vote = $wpdb->get_row($wpdb->prepare("
                SELECT * FROM {$this->tables['votes']}
                WHERE candidate_id = %d 
                AND jury_member_id = %d 
                AND is_active = 1
                ORDER BY id DESC
                LIMIT 1
            ", $candidate_id, $jury_member_id), ARRAY_A);
            
            if ($vote) {
                $backup_ids['vote'] = $this->backup_single_record($vote, 'votes', $reason);
                if (is_wp_error($backup_ids['vote'])) {
                    throw new Exception($backup_ids['vote']->get_error_message());
                }
            }
            
            // Backup from scores table
            $score = $wpdb->get_row($wpdb->prepare("
                SELECT * FROM {$this->tables['scores']}
                WHERE candidate_id = %d 
                AND jury_member_id = %d 
                AND is_active = 1
                ORDER BY id DESC
                LIMIT 1
            ", $candidate_id, $jury_member_id), ARRAY_A);
            
            if ($score) {
                $backup_ids['score'] = $this->backup_single_record($score, 'scores', $reason);
                if (is_wp_error($backup_ids['score'])) {
                    throw new Exception($backup_ids['score']->get_error_message());
                }
            }
            
            // If no records found
            if (empty($backup_ids)) {
                throw new Exception(__('No active votes found to backup', 'mobility-trailblazers'));
            }
            
            $wpdb->query('COMMIT');
            
            // Trigger action for external processing
            do_action('mt_vote_backed_up', $backup_ids, $candidate_id, $jury_member_id);
            
            return $backup_ids;
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('backup_failed', $e->getMessage());
        }
    }
    
    /**
     * Bulk backup votes based on conditions
     * 
     * @param array $where_conditions Conditions for selecting votes
     * @param string $reason Backup reason
     * @return array|WP_Error Array with backup count on success, WP_Error on failure
     */
    public function bulk_backup($where_conditions, $reason = '') {
        global $wpdb;
        
        $wpdb->query('START TRANSACTION');
        
        try {
            $backed_up = array(
                'votes' => 0,
                'scores' => 0,
                'count' => 0
            );
            
            // Build WHERE clause
            $where_parts = array();
            $where_values = array();
            
            foreach ($where_conditions as $field => $value) {
                if ($value !== null) {
                    $where_parts[] = "$field = %s";
                    $where_values[] = $value;
                }
            }
            
            if (empty($where_parts)) {
                throw new Exception(__('No conditions specified for bulk backup', 'mobility-trailblazers'));
            }
            
            $where_clause = implode(' AND ', $where_parts);
            
            // Backup votes
            $votes_query = "SELECT * FROM {$this->tables['votes']} WHERE $where_clause";
            $votes = $wpdb->get_results($wpdb->prepare($votes_query, $where_values), ARRAY_A);
            
            foreach ($votes as $vote) {
                $result = $this->backup_single_record($vote, 'votes', $reason);
                if (!is_wp_error($result)) {
                    $backed_up['votes']++;
                }
            }
            
            // Backup scores
            $scores_query = "SELECT * FROM {$this->tables['scores']} WHERE $where_clause";
            $scores = $wpdb->get_results($wpdb->prepare($scores_query, $where_values), ARRAY_A);
            
            foreach ($scores as $score) {
                $result = $this->backup_single_record($score, 'scores', $reason);
                if (!is_wp_error($result)) {
                    $backed_up['scores']++;
                }
            }
            
            $backed_up['count'] = $backed_up['votes'] + $backed_up['scores'];
            
            $wpdb->query('COMMIT');
            
            // Log bulk backup operation
            do_action('mt_bulk_backup_completed', $backed_up, $where_conditions, $reason);
            
            return $backed_up;
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('bulk_backup_failed', $e->getMessage());
        }
    }
    
    /**
     * Backup a single record
     * 
     * @param array $record Record data
     * @param string $type 'votes' or 'scores'
     * @param string $reason Backup reason
     * @return int|WP_Error Insert ID on success, WP_Error on failure
     */
    private function backup_single_record($record, $type, $reason) {
        global $wpdb;
        
        $table = $this->tables[$type . '_history'];
        
        // Prepare backup data based on type
        if ($type === 'votes') {
            $backup_data = array(
                'original_vote_id' => $record['id'],
                'candidate_id' => $record['candidate_id'],
                'jury_member_id' => $record['jury_member_id'],
                'vote_round' => $record['vote_round'] ?? 1,
                'rating' => $record['rating'] ?? 0,
                'comments' => $record['comments'] ?? '',
                'vote_date' => $record['vote_date'] ?? $record['created_at'],
                'voting_phase' => get_option('mt_current_voting_phase', 'phase_1'),
                'backed_up_at' => current_time('mysql'),
                'backup_reason' => $reason
            );
            
            $formats = array('%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s');
            
        } else { // scores
            $backup_data = array(
                'original_score_id' => $record['id'],
                'candidate_id' => $record['candidate_id'],
                'jury_member_id' => $record['jury_member_id'],
                'courage_score' => $record['courage_score'] ?? 0,
                'innovation_score' => $record['innovation_score'] ?? 0,
                'implementation_score' => $record['implementation_score'] ?? 0,
                'relevance_score' => $record['relevance_score'] ?? 0,
                'visibility_score' => $record['visibility_score'] ?? 0,
                'total_score' => $record['total_score'] ?? 0,
                'evaluation_round' => $record['evaluation_round'] ?? 1,
                'evaluation_date' => $record['evaluation_date'] ?? $record['created_at'],
                'comments' => $record['comments'] ?? '',
                'voting_phase' => get_option('mt_current_voting_phase', 'phase_1'),
                'backed_up_at' => current_time('mysql'),
                'backup_reason' => $reason
            );
            
            $formats = array('%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s');
        }
        
        $result = $wpdb->insert($table, $backup_data, $formats);
        
        if ($result === false) {
            return new WP_Error('backup_insert_failed', $wpdb->last_error);
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Restore votes from backup
     * 
     * @param int $backup_id Backup record ID
     * @param string $type 'votes' or 'scores'
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function restore_from_backup($backup_id, $type) {
        global $wpdb;
        
        if (!in_array($type, array('votes', 'scores'))) {
            return new WP_Error('invalid_type', __('Invalid backup type specified', 'mobility-trailblazers'));
        }
        
        $history_table = $this->tables[$type . '_history'];
        $main_table = $this->tables[$type];
        
        // Get backup record
        $backup = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM $history_table WHERE history_id = %d
        ", $backup_id), ARRAY_A);
        
        if (!$backup) {
            return new WP_Error('backup_not_found', __('Backup record not found', 'mobility-trailblazers'));
        }
        
        $wpdb->query('START TRANSACTION');
        
        try {
            // Prepare data for restoration
            if ($type === 'votes') {
                $restore_data = array(
                    'candidate_id' => $backup['candidate_id'],
                    'jury_member_id' => $backup['jury_member_id'],
                    'vote_round' => $backup['vote_round'],
                    'rating' => $backup['rating'],
                    'comments' => $backup['comments'],
                    'vote_date' => $backup['vote_date'],
                    'is_active' => 1,
                    'created_at' => current_time('mysql')
                );
                
                $formats = array('%d', '%d', '%d', '%d', '%s', '%s', '%d', '%s');
                
            } else { // scores
                $restore_data = array(
                    'candidate_id' => $backup['candidate_id'],
                    'jury_member_id' => $backup['jury_member_id'],
                    'courage_score' => $backup['courage_score'],
                    'innovation_score' => $backup['innovation_score'],
                    'implementation_score' => $backup['implementation_score'],
                    'relevance_score' => $backup['relevance_score'],
                    'visibility_score' => $backup['visibility_score'],
                    'total_score' => $backup['total_score'],
                    'evaluation_round' => $backup['evaluation_round'],
                    'evaluation_date' => $backup['evaluation_date'],
                    'comments' => $backup['comments'],
                    'is_active' => 1,
                    'created_at' => current_time('mysql')
                );
                
                $formats = array('%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%d', '%s', '%s', '%d', '%s');
            }
            
            // Check if active record already exists
            $existing = $wpdb->get_var($wpdb->prepare("
                SELECT id FROM $main_table 
                WHERE candidate_id = %d 
                AND jury_member_id = %d 
                AND is_active = 1
            ", $backup['candidate_id'], $backup['jury_member_id']));
            
            if ($existing) {
                // Deactivate existing record first
                $wpdb->update(
                    $main_table,
                    array('is_active' => 0, 'reset_at' => current_time('mysql')),
                    array('id' => $existing),
                    array('%d', '%s'),
                    array('%d')
                );
            }
            
            // Insert restored record
            $result = $wpdb->insert($main_table, $restore_data, $formats);
            
            if ($result === false) {
                throw new Exception($wpdb->last_error);
            }
            
            // Mark backup as restored
            $wpdb->update(
                $history_table,
                array('restored_at' => current_time('mysql')),
                array('history_id' => $backup_id),
                array('%s'),
                array('%d')
            );
            
            $wpdb->query('COMMIT');
            
            // Log restoration
            do_action('mt_vote_restored', $wpdb->insert_id, $backup_id, $type);
            
            return true;
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('restore_failed', $e->getMessage());
        }
    }
    
    /**
     * Get backup history for a specific vote
     * 
     * @param int $candidate_id
     * @param int $jury_member_id
     * @param string $type 'votes', 'scores', or 'all'
     * @return array
     */
    public function get_backup_history($candidate_id, $jury_member_id, $type = 'all') {
        global $wpdb;
        
        $history = array();
        
        if ($type === 'votes' || $type === 'all') {
            $votes_history = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    history_id,
                    'vote' as type,
                    rating as value,
                    vote_date as original_date,
                    backed_up_at,
                    backup_reason,
                    restored_at
                FROM {$this->tables['votes_history']}
                WHERE candidate_id = %d AND jury_member_id = %d
                ORDER BY backed_up_at DESC
            ", $candidate_id, $jury_member_id));
            
            $history = array_merge($history, $votes_history);
        }
        
        if ($type === 'scores' || $type === 'all') {
            $scores_history = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    history_id,
                    'score' as type,
                    total_score as value,
                    evaluation_date as original_date,
                    backed_up_at,
                    backup_reason,
                    restored_at
                FROM {$this->tables['scores_history']}
                WHERE candidate_id = %d AND jury_member_id = %d
                ORDER BY backed_up_at DESC
            ", $candidate_id, $jury_member_id));
            
            $history = array_merge($history, $scores_history);
        }
        
        // Sort by backup date
        usort($history, function($a, $b) {
            return strtotime($b->backed_up_at) - strtotime($a->backed_up_at);
        });
        
        return $history;
    }
    
    /**
     * Get backup statistics
     * 
     * @return array
     */
    public function get_backup_statistics() {
        global $wpdb;
        
        $stats = array();
        
        // Total backups
        $stats['total_vote_backups'] = $wpdb->get_var("
            SELECT COUNT(*) FROM {$this->tables['votes_history']}
        ");
        
        $stats['total_score_backups'] = $wpdb->get_var("
            SELECT COUNT(*) FROM {$this->tables['scores_history']}
        ");
        
        $stats['total_backups'] = $stats['total_vote_backups'] + $stats['total_score_backups'];
        
        // Backups by reason
        $stats['by_reason'] = $wpdb->get_results("
            SELECT 
                backup_reason,
                COUNT(*) as count
            FROM (
                SELECT backup_reason FROM {$this->tables['votes_history']}
                UNION ALL
                SELECT backup_reason FROM {$this->tables['scores_history']}
            ) as combined
            GROUP BY backup_reason
            ORDER BY count DESC
        ");
        
        // Recent backups (last 7 days)
        $seven_days_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
        $stats['recent_backups'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM (
                SELECT history_id FROM {$this->tables['votes_history']} WHERE backed_up_at > %s
                UNION ALL
                SELECT history_id FROM {$this->tables['scores_history']} WHERE backed_up_at > %s
            ) as recent
        ", $seven_days_ago, $seven_days_ago));
        
        // Storage size estimate
        $stats['storage_size'] = $this->estimate_backup_size();
        
        // Restoration count
        $stats['restorations'] = $wpdb->get_var("
            SELECT COUNT(*) FROM (
                SELECT history_id FROM {$this->tables['votes_history']} WHERE restored_at IS NOT NULL
                UNION ALL
                SELECT history_id FROM {$this->tables['scores_history']} WHERE restored_at IS NOT NULL
            ) as restored
        ");
        
        return $stats;
    }
    
    /**
     * Clean old backups based on retention policy
     * 
     * @param int $days Override retention days (optional)
     * @return array Deletion counts
     */
    public function clean_old_backups($days = null) {
        global $wpdb;
        
        $retention_days = $days ?: $this->retention_days;
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        $deleted = array(
            'votes' => 0,
            'scores' => 0
        );
        
        // Don't delete restored backups
        $deleted['votes'] = $wpdb->query($wpdb->prepare("
            DELETE FROM {$this->tables['votes_history']}
            WHERE backed_up_at < %s
            AND restored_at IS NULL
        ", $cutoff_date));
        
        $deleted['scores'] = $wpdb->query($wpdb->prepare("
            DELETE FROM {$this->tables['scores_history']}
            WHERE backed_up_at < %s
            AND restored_at IS NULL
        ", $cutoff_date));
        
        $deleted['total'] = $deleted['votes'] + $deleted['scores'];
        
        // Log cleanup
        do_action('mt_backup_cleanup_completed', $deleted, $retention_days);
        
        return $deleted;
    }
    
    /**
     * Export backups to file
     * 
     * @param string $format 'json' or 'csv'
     * @param array $filters Optional filters
     * @return string|WP_Error File path on success, WP_Error on failure
     */
    public function export_backups($format = 'json', $filters = array()) {
        global $wpdb;
        
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/mt-backups';
        
        // Create directory if it doesn't exist
        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
        }
        
        $filename = 'vote-backups-' . date('Y-m-d-His') . '.' . $format;
        $filepath = $export_dir . '/' . $filename;
        
        try {
            // Get all backup data
            $data = array(
                'votes' => $wpdb->get_results("SELECT * FROM {$this->tables['votes_history']}", ARRAY_A),
                'scores' => $wpdb->get_results("SELECT * FROM {$this->tables['scores_history']}", ARRAY_A),
                'export_date' => current_time('mysql'),
                'export_version' => MT_PLUGIN_VERSION
            );
            
            if ($format === 'json') {
                $json = wp_json_encode($data, JSON_PRETTY_PRINT);
                if (file_put_contents($filepath, $json) === false) {
                    throw new Exception(__('Failed to write export file', 'mobility-trailblazers'));
                }
            } else { // CSV
                $this->export_to_csv($filepath, $data);
            }
            
            return $filepath;
            
        } catch (Exception $e) {
            return new WP_Error('export_failed', $e->getMessage());
        }
    }
    
    /**
     * Export data to CSV format
     * 
     * @param string $filepath
     * @param array $data
     * @return void
     */
    private function export_to_csv($filepath, $data) {
        $handle = fopen($filepath, 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Export votes history
        fputcsv($handle, array('=== VOTES HISTORY ==='));
        if (!empty($data['votes'])) {
            fputcsv($handle, array_keys($data['votes'][0]));
            foreach ($data['votes'] as $row) {
                fputcsv($handle, $row);
            }
        }
        
        fputcsv($handle, array()); // Empty line
        
        // Export scores history
        fputcsv($handle, array('=== SCORES HISTORY ==='));
        if (!empty($data['scores'])) {
            fputcsv($handle, array_keys($data['scores'][0]));
            foreach ($data['scores'] as $row) {
                fputcsv($handle, $row);
            }
        }
        
        fclose($handle);
    }
    
    /**
     * Estimate backup storage size
     * 
     * @return string Formatted size
     */
    private function estimate_backup_size() {
        global $wpdb;
        
        // Get table sizes
        $result = $wpdb->get_row($wpdb->prepare("
            SELECT 
                SUM(data_length + index_length) as size
            FROM information_schema.TABLES 
            WHERE table_schema = %s 
            AND table_name IN (%s, %s)
        ", DB_NAME, $this->tables['votes_history'], $this->tables['scores_history']));
        
        $bytes = $result->size ?: 0;
        
        // Format size
        $units = array('B', 'KB', 'MB', 'GB');
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Verify backup integrity
     * 
     * @param int $backup_id
     * @param string $type
     * @return bool|WP_Error
     */
    public function verify_backup_integrity($backup_id, $type) {
        global $wpdb;
        
        $table = $this->tables[$type . '_history'];
        
        $backup = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM $table WHERE history_id = %d
        ", $backup_id));
        
        if (!$backup) {
            return new WP_Error('backup_not_found', __('Backup record not found', 'mobility-trailblazers'));
        }
        
        // Check required fields
        $required_fields = ($type === 'votes') 
            ? array('candidate_id', 'jury_member_id', 'rating')
            : array('candidate_id', 'jury_member_id', 'total_score');
            
        foreach ($required_fields as $field) {
            if (empty($backup->$field)) {
                return new WP_Error('integrity_check_failed', 
                    sprintf(__('Required field %s is missing or empty', 'mobility-trailblazers'), $field)
                );
            }
        }
        
        return true;
    }
}