<?php
/**
 * Vote Audit Logger Class
 * 
 * Handles logging and tracking of all vote reset operations
 * for accountability and transparency.
 *
 * @package MobilityTrailblazers
 * @subpackage Includes
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class MT_Vote_Audit_Logger {
    
    /**
     * Table name for reset logs
     * 
     * @var string
     */
    private $table_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'vote_reset_logs';
    }
    
    /**
     * Log a reset operation
     * 
     * @param array $data Reset operation data
     * @return int|false Insert ID on success, false on failure
     */
    public function log_reset($data) {
        global $wpdb;
        
        // Prepare log data
        $log_data = array(
            'reset_type' => $data['reset_type'],
            'initiated_by' => $data['initiated_by'] ?? get_current_user_id(),
            'initiated_by_role' => $this->get_user_role($data['initiated_by'] ?? get_current_user_id()),
            'affected_user_id' => $data['affected_user_id'] ?? null,
            'affected_candidate_id' => $data['affected_candidate_id'] ?? null,
            'voting_phase' => $data['voting_phase'] ?? $this->get_current_phase(),
            'votes_affected' => $data['votes_affected'] ?? 0,
            'reset_reason' => $data['reset_reason'] ?? '',
            'reset_timestamp' => current_time('mysql'),
            'ip_address' => $this->get_user_ip(),
            'user_agent' => $this->get_user_agent()
        );
        
        // Format for database
        $formats = array(
            '%s', // reset_type
            '%d', // initiated_by
            '%s', // initiated_by_role
            '%d', // affected_user_id
            '%d', // affected_candidate_id
            '%s', // voting_phase
            '%d', // votes_affected
            '%s', // reset_reason
            '%s', // reset_timestamp
            '%s', // ip_address
            '%s'  // user_agent
        );
        
        // Insert log entry
        $result = $wpdb->insert($this->table_name, $log_data, $formats);
        
        if ($result === false) {
            error_log('MT Vote Audit: Failed to log reset operation - ' . $wpdb->last_error);
            return false;
        }
        
        // Trigger action for external logging
        do_action('mt_vote_reset_logged', $wpdb->insert_id, $log_data);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get reset history with pagination
     * 
     * @param int $page Current page number
     * @param int $per_page Items per page
     * @param array $filters Optional filters
     * @return array
     */
    public function get_reset_history($page = 1, $per_page = 20, $filters = array()) {
        global $wpdb;
        
        $offset = ($page - 1) * $per_page;
        
        // Base query
        $query = "SELECT 
                    r.*,
                    u1.display_name as initiated_by_name,
                    u1.user_email as initiated_by_email,
                    u2.display_name as affected_user_name,
                    c.post_title as candidate_name
                  FROM {$this->table_name} r
                  LEFT JOIN {$wpdb->users} u1 ON r.initiated_by = u1.ID
                  LEFT JOIN {$wpdb->users} u2 ON r.affected_user_id = u2.ID
                  LEFT JOIN {$wpdb->posts} c ON r.affected_candidate_id = c.ID";
        
        // Apply filters
        $where_clauses = array();
        $where_values = array();
        
        if (!empty($filters['reset_type'])) {
            $where_clauses[] = "r.reset_type = %s";
            $where_values[] = $filters['reset_type'];
        }
        
        if (!empty($filters['initiated_by'])) {
            $where_clauses[] = "r.initiated_by = %d";
            $where_values[] = $filters['initiated_by'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_clauses[] = "r.reset_timestamp >= %s";
            $where_values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_clauses[] = "r.reset_timestamp <= %s";
            $where_values[] = $filters['date_to'];
        }
        
        if (!empty($where_clauses)) {
            $query .= " WHERE " . implode(" AND ", $where_clauses);
        }
        
        $query .= " ORDER BY r.reset_timestamp DESC LIMIT %d OFFSET %d";
        $where_values[] = $per_page;
        $where_values[] = $offset;
        
        // Get results
        $results = $wpdb->get_results($wpdb->prepare($query, $where_values));
        
        // Get total count
        $count_query = "SELECT COUNT(*) FROM {$this->table_name} r";
        if (!empty($where_clauses)) {
            $count_query .= " WHERE " . implode(" AND ", $where_clauses);
            $total = $wpdb->get_var($wpdb->prepare($count_query, array_slice($where_values, 0, -2)));
        } else {
            $total = $wpdb->get_var($count_query);
        }
        
        return array(
            'data' => $results,
            'total' => intval($total),
            'pages' => ceil($total / $per_page),
            'current_page' => $page,
            'per_page' => $per_page
        );
    }
    
    /**
     * Get statistics about reset operations
     * 
     * @param string $period Time period (day, week, month, year)
     * @return array
     */
    public function get_reset_statistics($period = 'month') {
        global $wpdb;
        
        $date_format = $this->get_date_format($period);
        $date_limit = $this->get_date_limit($period);
        
        // Get reset counts by type
        $type_stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                reset_type,
                COUNT(*) as count,
                SUM(votes_affected) as total_votes
            FROM {$this->table_name}
            WHERE reset_timestamp >= %s
            GROUP BY reset_type
        ", $date_limit));
        
        // Get reset counts by user
        $user_stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                r.initiated_by,
                u.display_name,
                COUNT(*) as reset_count,
                SUM(r.votes_affected) as votes_affected
            FROM {$this->table_name} r
            LEFT JOIN {$wpdb->users} u ON r.initiated_by = u.ID
            WHERE r.reset_timestamp >= %s
            GROUP BY r.initiated_by
            ORDER BY reset_count DESC
            LIMIT 10
        ", $date_limit));
        
        // Get timeline data
        $timeline_stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE_FORMAT(reset_timestamp, %s) as period,
                COUNT(*) as reset_count,
                SUM(votes_affected) as votes_affected
            FROM {$this->table_name}
            WHERE reset_timestamp >= %s
            GROUP BY period
            ORDER BY reset_timestamp ASC
        ", $date_format, $date_limit));
        
        return array(
            'by_type' => $type_stats,
            'by_user' => $user_stats,
            'timeline' => $timeline_stats,
            'period' => $period
        );
    }
    
    /**
     * Get the last reset date
     * 
     * @return string|null
     */
    public static function get_last_reset_date() {
        global $wpdb;
        
        $last_reset = $wpdb->get_var("
            SELECT reset_timestamp 
            FROM {$wpdb->prefix}vote_reset_logs 
            ORDER BY reset_timestamp DESC 
            LIMIT 1
        ");
        
        if ($last_reset) {
            return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_reset));
        }
        
        return null;
    }
    
    /**
     * Display recent reset activity
     * 
     * @param int $limit Number of items to display
     * @return void
     */
    public static function display_recent_resets($limit = 5) {
        $logger = new self();
        $history = $logger->get_reset_history(1, $limit);
        
        if (empty($history['data'])) {
            echo '<p class="mt-no-activity">' . __('No reset activity recorded yet.', 'mobility-trailblazers') . '</p>';
            return;
        }
        
        $type_labels = array(
            'individual' => __('Individual', 'mobility-trailblazers'),
            'bulk_user' => __('User Bulk', 'mobility-trailblazers'),
            'bulk_candidate' => __('Candidate Bulk', 'mobility-trailblazers'),
            'phase_transition' => __('Phase Transition', 'mobility-trailblazers'),
            'full_reset' => __('Full Reset', 'mobility-trailblazers')
        );
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Date/Time', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Type', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('User', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Votes', 'mobility-trailblazers'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history['data'] as $log): ?>
                <tr>
                    <td><?php echo esc_html(date_i18n('M j, Y g:i a', strtotime($log->reset_timestamp))); ?></td>
                    <td>
                        <span class="mt-reset-type mt-reset-type-<?php echo esc_attr($log->reset_type); ?>">
                            <?php echo esc_html($type_labels[$log->reset_type] ?? $log->reset_type); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html($log->initiated_by_name ?: __('System', 'mobility-trailblazers')); ?></td>
                    <td><?php echo number_format($log->votes_affected); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Clean old logs
     * 
     * @param int $days Number of days to keep
     * @return int Number of rows deleted
     */
    public function clean_old_logs($days = 365) {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $deleted = $wpdb->query($wpdb->prepare("
            DELETE FROM {$this->table_name}
            WHERE reset_timestamp < %s
        ", $cutoff_date));
        
        return $deleted;
    }
    
    /**
     * Export logs to CSV
     * 
     * @param array $filters Optional filters
     * @return string CSV content
     */
    public function export_to_csv($filters = array()) {
        $history = $this->get_reset_history(1, 99999, $filters);
        
        // CSV headers
        $csv = "Date/Time,Type,Initiated By,Initiated By Email,Affected User,Affected Candidate,Votes Affected,Reason,IP Address\n";
        
        // Add data rows
        foreach ($history['data'] as $log) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s","%d","%s","%s"' . "\n",
                $log->reset_timestamp,
                $log->reset_type,
                $log->initiated_by_name ?: 'System',
                $log->initiated_by_email ?: '',
                $log->affected_user_name ?: '',
                $log->candidate_name ?: '',
                $log->votes_affected,
                str_replace('"', '""', $log->reset_reason),
                $log->ip_address
            );
        }
        
        return $csv;
    }
    
    /**
     * Get user role for logging
     * 
     * @param int $user_id
     * @return string
     */
    private function get_user_role($user_id) {
        if (!$user_id) {
            return 'system';
        }
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return 'unknown';
        }
        
        if (in_array('administrator', $user->roles)) {
            return 'admin';
        } elseif (in_array('mt_jury_member', $user->roles)) {
            return 'jury_member';
        }
        
        return 'other';
    }
    
    /**
     * Get current voting phase
     * 
     * @return string
     */
    private function get_current_phase() {
        return get_option('mt_current_voting_phase', 'phase_1');
    }
    
    /**
     * Get user IP address
     * 
     * @return string
     */
    private function get_user_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    
    /**
     * Get user agent string
     * 
     * @return string
     */
    private function get_user_agent() {
        return isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '';
    }
    
    /**
     * Get date format based on period
     * 
     * @param string $period
     * @return string
     */
    private function get_date_format($period) {
        switch ($period) {
            case 'day':
                return '%Y-%m-%d %H:00:00';
            case 'week':
            case 'month':
                return '%Y-%m-%d';
            case 'year':
                return '%Y-%m';
            default:
                return '%Y-%m-%d';
        }
    }
    
    /**
     * Get date limit based on period
     * 
     * @param string $period
     * @return string
     */
    private function get_date_limit($period) {
        switch ($period) {
            case 'day':
                return date('Y-m-d H:i:s', strtotime('-1 day'));
            case 'week':
                return date('Y-m-d H:i:s', strtotime('-1 week'));
            case 'month':
                return date('Y-m-d H:i:s', strtotime('-1 month'));
            case 'year':
                return date('Y-m-d H:i:s', strtotime('-1 year'));
            default:
                return date('Y-m-d H:i:s', strtotime('-1 month'));
        }
    }
    
    /**
     * Check if user has excessive reset activity
     * 
     * @param int $user_id
     * @param int $threshold Number of resets to trigger warning
     * @param string $period Time period to check
     * @return bool
     */
    public function check_excessive_resets($user_id, $threshold = 10, $period = 'day') {
        global $wpdb;
        
        $date_limit = $this->get_date_limit($period);
        
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$this->table_name}
            WHERE initiated_by = %d 
            AND reset_timestamp >= %s
        ", $user_id, $date_limit));
        
        return $count >= $threshold;
    }
}