<?php
/**
 * Maintenance Tools for system operations
 *
 * @package MobilityTrailblazers
 * @since 2.3.0
 */

namespace MobilityTrailblazers\Admin;

use MobilityTrailblazers\Core\MT_Logger;
use MobilityTrailblazers\Core\MT_Database_Upgrade;
use MobilityTrailblazers\Repositories\MT_Evaluation_Repository;
use MobilityTrailblazers\Repositories\MT_Assignment_Repository;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Maintenance_Tools
 *
 * Provides maintenance and system operation tools
 */
class MT_Maintenance_Tools {
    
    /**
     * Available maintenance operations
     *
     * @var array
     */
    private $operations = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->register_operations();
    }
    
    /**
     * Register available operations
     *
     * @return void
     */
    private function register_operations() {
        $this->operations = [
            'database' => [
                'title' => __('Database Operations', 'mobility-trailblazers'),
                'operations' => [
                    'optimize_all' => [
                        'title' => __('Optimize Database Tables', 'mobility-trailblazers'),
                        'description' => __('Optimize all plugin database tables for better performance', 'mobility-trailblazers'),
                        'dangerous' => false,
                        'callback' => [$this, 'optimize_tables']
                    ],
                    'repair_tables' => [
                        'title' => __('Repair Database Tables', 'mobility-trailblazers'),
                        'description' => __('Check and repair corrupted database tables', 'mobility-trailblazers'),
                        'dangerous' => true,
                        'callback' => [$this, 'repair_tables']
                    ],
                    'clean_orphaned' => [
                        'title' => __('Clean Orphaned Data', 'mobility-trailblazers'),
                        'description' => __('Remove orphaned evaluations and assignments', 'mobility-trailblazers'),
                        'dangerous' => true,
                        'callback' => [$this, 'repair_orphaned_data']
                    ],
                    'sync_evaluations' => [
                        'title' => __('Sync Evaluations', 'mobility-trailblazers'),
                        'description' => __('Synchronize evaluations with assignments', 'mobility-trailblazers'),
                        'dangerous' => false,
                        'callback' => [$this, 'sync_evaluations']
                    ],
                    'cleanup_old_data' => [
                        'title' => __('Cleanup Old Data', 'mobility-trailblazers'),
                        'description' => __('Remove old logs and temporary data', 'mobility-trailblazers'),
                        'dangerous' => true,
                        'callback' => [$this, 'cleanup_old_data']
                    ],
                    'rebuild_indexes' => [
                        'title' => __('Rebuild Indexes', 'mobility-trailblazers'),
                        'description' => __('Rebuild database indexes for better query performance', 'mobility-trailblazers'),
                        'dangerous' => false,
                        'callback' => [$this, 'rebuild_indexes']
                    ],
                    'run_migrations' => [
                        'title' => __('Run Database Migrations', 'mobility-trailblazers'),
                        'description' => __('Execute pending database migrations', 'mobility-trailblazers'),
                        'dangerous' => true,
                        'callback' => [$this, 'run_migrations']
                    ]
                ]
            ],
            'cache' => [
                'title' => __('Cache Operations', 'mobility-trailblazers'),
                'operations' => [
                    'clear_all' => [
                        'title' => __('Clear All Caches', 'mobility-trailblazers'),
                        'description' => __('Clear all plugin caches and transients', 'mobility-trailblazers'),
                        'dangerous' => false,
                        'callback' => [$this, 'clear_all_caches']
                    ],
                    'clear_transients' => [
                        'title' => __('Clear Transients', 'mobility-trailblazers'),
                        'description' => __('Clear all plugin transients', 'mobility-trailblazers'),
                        'dangerous' => false,
                        'callback' => [$this, 'clear_transients']
                    ],
                    'regenerate_indexes' => [
                        'title' => __('Regenerate Cache Indexes', 'mobility-trailblazers'),
                        'description' => __('Regenerate cache indexes for faster lookups', 'mobility-trailblazers'),
                        'dangerous' => false,
                        'callback' => [$this, 'regenerate_cache_indexes']
                    ],
                    'clear_object_cache' => [
                        'title' => __('Clear Object Cache', 'mobility-trailblazers'),
                        'description' => __('Flush WordPress object cache', 'mobility-trailblazers'),
                        'dangerous' => false,
                        'callback' => [$this, 'clear_object_cache']
                    ],
                    'clear_plugin_cache' => [
                        'title' => __('Clear Plugin Cache', 'mobility-trailblazers'),
                        'description' => __('Clear all plugin-specific cached data', 'mobility-trailblazers'),
                        'dangerous' => false,
                        'callback' => [$this, 'clear_plugin_cache']
                    ]
                ]
            ],
            'import_export' => [
                'title' => __('Import/Export Operations', 'mobility-trailblazers'),
                'operations' => [
                    'export_all' => [
                        'title' => __('Export All Data', 'mobility-trailblazers'),
                        'description' => __('Export all plugin data to JSON', 'mobility-trailblazers'),
                        'dangerous' => false,
                        'callback' => [$this, 'export_all_data']
                    ],
                    'backup_tables' => [
                        'title' => __('Create Backup', 'mobility-trailblazers'),
                        'description' => __('Create a complete backup of all plugin data', 'mobility-trailblazers'),
                        'dangerous' => false,
                        'callback' => [$this, 'create_backup']
                    ],
                    'import_data' => [
                        'title' => __('Import Data', 'mobility-trailblazers'),
                        'description' => __('Import data from backup file', 'mobility-trailblazers'),
                        'dangerous' => true,
                        'callback' => [$this, 'restore_backup'],
                        'requires_file' => true
                    ]
                ]
            ],
            'reset' => [
                'title' => __('Reset Operations', 'mobility-trailblazers'),
                'operations' => [
                    'reset_evaluations' => [
                        'title' => __('Reset All Evaluations', 'mobility-trailblazers'),
                        'description' => __('Delete all evaluations (cannot be undone)', 'mobility-trailblazers'),
                        'dangerous' => true,
                        'requires_confirmation' => true,
                        'callback' => [$this, 'reset_evaluations']
                    ],
                    'reset_assignments' => [
                        'title' => __('Reset All Assignments', 'mobility-trailblazers'),
                        'description' => __('Delete all jury assignments (cannot be undone)', 'mobility-trailblazers'),
                        'dangerous' => true,
                        'requires_confirmation' => true,
                        'callback' => [$this, 'reset_assignments']
                    ],
                    'factory_reset' => [
                        'title' => __('Factory Reset', 'mobility-trailblazers'),
                        'description' => __('Reset plugin to initial state (deletes ALL data)', 'mobility-trailblazers'),
                        'dangerous' => true,
                        'requires_confirmation' => true,
                        'requires_password' => true,
                        'callback' => [$this, 'factory_reset']
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Get all operations
     *
     * @return array Operations
     */
    public function get_operations() {
        return $this->operations;
    }
    
    /**
     * Execute operation
     *
     * @param string $category Operation category
     * @param string $operation Operation name
     * @param array $params Optional parameters
     * @return array Result
     */
    public function execute_operation($category, $operation, $params = []) {
        $result = [
            'success' => false,
            'message' => '',
            'data' => []
        ];
        
        // Check if operation exists
        if (!isset($this->operations[$category]['operations'][$operation])) {
            $result['message'] = __('Operation not found', 'mobility-trailblazers');
            return $result;
        }
        
        $op_info = $this->operations[$category]['operations'][$operation];
        
        // Check for dangerous operations
        if ($op_info['dangerous']) {
            if (!isset($params['confirm']) || !$params['confirm']) {
                $result['message'] = __('Dangerous operation requires confirmation', 'mobility-trailblazers');
                $result['requires_confirmation'] = true;
                return $result;
            }
        }
        
        // Check for password requirement
        if (isset($op_info['requires_password']) && $op_info['requires_password']) {
            if (!isset($params['password']) || !$this->verify_admin_password($params['password'])) {
                $result['message'] = __('Invalid password', 'mobility-trailblazers');
                $result['requires_password'] = true;
                return $result;
            }
        }
        
        // Log operation
        $this->log_operation($category, $operation);
        
        // Execute callback
        try {
            if (is_callable($op_info['callback'])) {
                $operation_result = call_user_func($op_info['callback'], $params);
                
                if (is_array($operation_result)) {
                    $result = array_merge($result, $operation_result);
                } else {
                    $result['success'] = (bool) $operation_result;
                    $result['message'] = $result['success'] ? 
                        __('Operation completed successfully', 'mobility-trailblazers') :
                        __('Operation failed', 'mobility-trailblazers');
                }
            } else {
                $result['message'] = __('Operation callback not found', 'mobility-trailblazers');
            }
        } catch (\Exception $e) {
            $result['message'] = sprintf(
                __('Operation error: %s', 'mobility-trailblazers'),
                $e->getMessage()
            );
            MT_Logger::error('Maintenance operation error', ['error' => $e->getMessage()]);
        }
        
        return $result;
    }
    
    /**
     * Optimize database tables
     *
     * @param array $params Parameters
     * @return array Result
     */
    private function optimize_tables($params = []) {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'mt_evaluations',
            $wpdb->prefix . 'mt_jury_assignments',
            $wpdb->prefix . 'mt_votes',
            $wpdb->prefix . 'mt_candidate_scores',
            $wpdb->prefix . 'mt_vote_backups',
            $wpdb->prefix . 'vote_reset_logs',
            $wpdb->prefix . 'mt_error_log'
        ];
        
        $results = [];
        $success = true;
        
        foreach ($tables as $table) {
            // Check if table exists
            if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) === $table) {
                $result = $wpdb->query("OPTIMIZE TABLE `$table`");
                $results[$table] = $result !== false;
                
                if ($result === false) {
                    $success = false;
                }
            }
        }
        
        return [
            'success' => $success,
            'message' => $success ? 
                __('Tables optimized successfully', 'mobility-trailblazers') :
                __('Some tables could not be optimized', 'mobility-trailblazers'),
            'data' => ['tables' => $results]
        ];
    }
    
    /**
     * Repair database tables
     *
     * @param array $params Parameters
     * @return array Result
     */
    private function repair_tables($params = []) {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'mt_evaluations',
            $wpdb->prefix . 'mt_jury_assignments',
            $wpdb->prefix . 'mt_votes',
            $wpdb->prefix . 'mt_candidate_scores',
            $wpdb->prefix . 'mt_vote_backups',
            $wpdb->prefix . 'vote_reset_logs',
            $wpdb->prefix . 'mt_error_log'
        ];
        
        $results = [];
        $success = true;
        
        foreach ($tables as $table) {
            // Check if table exists
            if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) === $table) {
                $check = $wpdb->get_row("CHECK TABLE `$table`");
                
                if ($check && $check->Msg_text !== 'OK') {
                    $repair = $wpdb->query("REPAIR TABLE `$table`");
                    $results[$table] = [
                        'status' => $repair !== false ? 'repaired' : 'failed',
                        'message' => $check->Msg_text
                    ];
                    
                    if ($repair === false) {
                        $success = false;
                    }
                } else {
                    $results[$table] = ['status' => 'ok'];
                }
            }
        }
        
        return [
            'success' => $success,
            'message' => $success ? 
                __('Tables repaired successfully', 'mobility-trailblazers') :
                __('Some tables could not be repaired', 'mobility-trailblazers'),
            'data' => ['tables' => $results]
        ];
    }
    
    /**
     * Repair orphaned data
     *
     * @param array $params Parameters
     * @return array Result
     */
    private function repair_orphaned_data($params = []) {
        global $wpdb;
        
        // Find and delete orphaned evaluations
        $orphaned_evaluations = $wpdb->query(
            "DELETE e FROM {$wpdb->prefix}mt_evaluations e
             WHERE NOT EXISTS (
                 SELECT 1 FROM {$wpdb->prefix}mt_jury_assignments a 
                 WHERE a.jury_member_id = e.jury_member_id 
                 AND a.candidate_id = e.candidate_id
             )"
        );
        
        // Find and delete orphaned assignments (jury member doesn't exist)
        $orphaned_assignments = $wpdb->query(
            "DELETE a FROM {$wpdb->prefix}mt_jury_assignments a
             WHERE NOT EXISTS (
                 SELECT 1 FROM {$wpdb->posts} p 
                 WHERE p.ID = a.jury_member_id 
                 AND p.post_type = 'mt_jury_member'
                 AND p.post_status = 'publish'
             )"
        );
        
        // Find and delete orphaned assignments (candidate doesn't exist)
        $orphaned_candidates = $wpdb->query(
            "DELETE a FROM {$wpdb->prefix}mt_jury_assignments a
             WHERE NOT EXISTS (
                 SELECT 1 FROM {$wpdb->posts} p 
                 WHERE p.ID = a.candidate_id 
                 AND p.post_type = 'mt_candidate'
                 AND p.post_status = 'publish'
             )"
        );
        
        $total_deleted = $orphaned_evaluations + $orphaned_assignments + $orphaned_candidates;
        
        return [
            'success' => true,
            'message' => sprintf(
                __('Removed %d orphaned records', 'mobility-trailblazers'),
                $total_deleted
            ),
            'data' => [
                'orphaned_evaluations' => $orphaned_evaluations,
                'orphaned_assignments_jury' => $orphaned_assignments,
                'orphaned_assignments_candidate' => $orphaned_candidates
            ]
        ];
    }
    
    /**
     * Sync evaluations with assignments
     *
     * @param array $params Parameters
     * @return array Result
     */
    private function sync_evaluations($params = []) {
        $evaluation_repo = new MT_Evaluation_Repository();
        $stats = $evaluation_repo->sync_with_assignments();
        
        return [
            'success' => true,
            'message' => sprintf(
                __('Sync completed. Found %d orphaned evaluations, deleted %d.', 'mobility-trailblazers'),
                $stats['orphaned_found'],
                $stats['orphaned_deleted']
            ),
            'data' => $stats
        ];
    }
    
    /**
     * Cleanup old data
     *
     * @param array $params Parameters
     * @return array Result
     */
    private function cleanup_old_data($params = []) {
        global $wpdb;
        
        $days_old = isset($params['days']) ? intval($params['days']) : 30;
        $deleted = [];
        
        // Clean old error logs
        $table_name = $wpdb->prefix . 'mt_error_log';
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name) {
            $deleted['error_logs'] = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM $table_name WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
                    $days_old
                )
            );
        }
        
        // Clean old transients
        $deleted['transients'] = $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_mt_%' 
             OR option_name LIKE '_transient_timeout_mt_%'"
        );
        
        // Clean old audit logs
        $audit_log = get_option('mt_debug_script_audit', []);
        if (count($audit_log) > 100) {
            $audit_log = array_slice($audit_log, 0, 100);
            update_option('mt_debug_script_audit', $audit_log);
            $deleted['audit_logs'] = count($audit_log) - 100;
        }
        
        return [
            'success' => true,
            'message' => __('Old data cleaned successfully', 'mobility-trailblazers'),
            'data' => $deleted
        ];
    }
    
    /**
     * Rebuild database indexes
     *
     * @param array $params Parameters
     * @return array Result
     */
    private function rebuild_indexes($params = []) {
        global $wpdb;
        
        $indexes_rebuilt = 0;
        
        // Rebuild indexes for evaluations table
        $table = $wpdb->prefix . 'mt_evaluations';
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) === $table) {
            // Drop and recreate indexes
            $wpdb->query("ALTER TABLE `$table` DROP INDEX IF EXISTS idx_jury_candidate");
            $wpdb->query("ALTER TABLE `$table` ADD INDEX idx_jury_candidate (jury_member_id, candidate_id)");
            $indexes_rebuilt++;
            
            $wpdb->query("ALTER TABLE `$table` DROP INDEX IF EXISTS idx_status");
            $wpdb->query("ALTER TABLE `$table` ADD INDEX idx_status (status)");
            $indexes_rebuilt++;
        }
        
        // Rebuild indexes for assignments table
        $table = $wpdb->prefix . 'mt_jury_assignments';
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) === $table) {
            $wpdb->query("ALTER TABLE `$table` DROP INDEX IF EXISTS idx_jury_member");
            $wpdb->query("ALTER TABLE `$table` ADD INDEX idx_jury_member (jury_member_id)");
            $indexes_rebuilt++;
            
            $wpdb->query("ALTER TABLE `$table` DROP INDEX IF EXISTS idx_candidate");
            $wpdb->query("ALTER TABLE `$table` ADD INDEX idx_candidate (candidate_id)");
            $indexes_rebuilt++;
        }
        
        return [
            'success' => true,
            'message' => sprintf(
                __('%d indexes rebuilt successfully', 'mobility-trailblazers'),
                $indexes_rebuilt
            ),
            'data' => ['indexes_rebuilt' => $indexes_rebuilt]
        ];
    }
    
    /**
     * Run database migrations
     *
     * @param array $params Parameters
     * @return array Result
     */
    private function run_migrations($params = []) {
        MT_Database_Upgrade::run();
        
        return [
            'success' => true,
            'message' => __('Database migrations completed', 'mobility-trailblazers'),
            'data' => [
                'current_version' => get_option('mt_db_version'),
                'plugin_version' => MT_VERSION
            ]
        ];
    }
    
    /**
     * Clear all caches
     *
     * @param array $params Parameters
     * @return array Result
     */
    private function clear_all_caches($params = []) {
        global $wpdb;
        
        // Clear transients
        $transients_deleted = $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_mt_%' 
             OR option_name LIKE '_transient_timeout_mt_%'"
        );
        
        // Clear object cache
        wp_cache_flush();
        
        // Clear any custom caches
        do_action('mt_clear_caches');
        
        return [
            'success' => true,
            'message' => __('All caches cleared successfully', 'mobility-trailblazers'),
            'data' => [
                'transients_deleted' => $transients_deleted,
                'object_cache_flushed' => true
            ]
        ];
    }
    
    /**
     * Clear object cache
     *
     * @param array $params Parameters
     * @return array Result
     */
    private function clear_object_cache($params = []) {
        wp_cache_flush();
        
        return [
            'success' => true,
            'message' => __('Object cache cleared successfully', 'mobility-trailblazers')
        ];
    }
    
    /**
     * Clear plugin cache
     *
     * @param array $params Parameters
     * @return array Result
     */
    private function clear_plugin_cache($params = []) {
        // Clear plugin-specific transients
        delete_transient('mt_evaluation_stats');
        delete_transient('mt_candidate_stats');
        delete_transient('mt_jury_stats');
        delete_transient('mt_assignment_stats');
        
        // Clear plugin options cache
        wp_cache_delete('mt_plugin_settings', 'options');
        wp_cache_delete('mt_cached_data', 'options');
        
        return [
            'success' => true,
            'message' => __('Plugin cache cleared successfully', 'mobility-trailblazers')
        ];
    }
    
    /**
     * Clear transients
     *
     * @param array $params Parameters
     * @return array Result
     */
    private function clear_transients($params = []) {
        global $wpdb;
        
        $deleted = $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_mt_%' 
             OR option_name LIKE '_transient_timeout_mt_%'"
        );
        
        return [
            'success' => true,
            'message' => sprintf(
                __('%d transients deleted', 'mobility-trailblazers'),
                $deleted
            ),
            'data' => ['deleted' => $deleted]
        ];
    }
    
    /**
     * Regenerate cache indexes
     *
     * @param array $params Parameters
     * @return array Result
     */
    private function regenerate_cache_indexes($params = []) {
        // Clear existing caches
        $this->clear_all_caches();
        
        // Regenerate common queries
        $evaluation_repo = new MT_Evaluation_Repository();
        $assignment_repo = new MT_Assignment_Repository();
        
        // Cache statistics
        $evaluation_repo->get_statistics();
        $assignment_repo->get_statistics();
        
        return [
            'success' => true,
            'message' => __('Cache indexes regenerated', 'mobility-trailblazers'),
            'data' => []
        ];
    }
    
    /**
     * Export all data
     *
     * @param array $params Parameters
     * @return array Result
     */
    private function export_all_data($params = []) {
        global $wpdb;
        
        $export_data = [
            'version' => MT_VERSION,
            'timestamp' => current_time('mysql'),
            'site_url' => get_site_url(),
            'data' => []
        ];
        
        // Export evaluations
        $export_data['data']['evaluations'] = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}mt_evaluations"
        );
        
        // Export assignments
        $export_data['data']['assignments'] = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}mt_jury_assignments"
        );
        
        // Export candidates
        $candidates = get_posts([
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);
        $export_data['data']['candidates'] = $candidates;
        
        // Export jury members
        $jury_members = get_posts([
            'post_type' => 'mt_jury_member',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);
        $export_data['data']['jury_members'] = $jury_members;
        
        // Save to file
        $upload_dir = wp_upload_dir();
        $filename = 'mt-export-' . date('Y-m-d-His') . '.json';
        $filepath = $upload_dir['basedir'] . '/mt-exports/' . $filename;
        
        // Create directory if it doesn't exist
        wp_mkdir_p($upload_dir['basedir'] . '/mt-exports/');
        
        // Save file
        $result = file_put_contents($filepath, wp_json_encode($export_data, JSON_PRETTY_PRINT));
        
        if ($result !== false) {
            return [
                'success' => true,
                'message' => __('Data exported successfully', 'mobility-trailblazers'),
                'data' => [
                    'filename' => $filename,
                    'filepath' => $filepath,
                    'url' => $upload_dir['baseurl'] . '/mt-exports/' . $filename,
                    'size' => filesize($filepath)
                ]
            ];
        }
        
        return [
            'success' => false,
            'message' => __('Failed to export data', 'mobility-trailblazers')
        ];
    }
    
    /**
     * Create backup
     *
     * @param array $params Parameters
     * @return array Result
     */
    private function create_backup($params = []) {
        // Use export functionality for backup
        return $this->export_all_data($params);
    }
    
    /**
     * Restore backup
     *
     * @param array $params Parameters
     * @return array Result
     */
    private function restore_backup($params = []) {
        // This would require file upload handling
        // Implementation depends on UI requirements
        
        return [
            'success' => false,
            'message' => __('Restore functionality requires file upload interface', 'mobility-trailblazers'),
            'requires_file' => true
        ];
    }
    
    /**
     * Reset evaluations
     *
     * @param array $params Parameters
     * @return array Result
     */
    private function reset_evaluations($params = []) {
        global $wpdb;
        
        $deleted = $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}mt_evaluations");
        
        return [
            'success' => $deleted !== false,
            'message' => $deleted !== false ?
                __('All evaluations deleted', 'mobility-trailblazers') :
                __('Failed to delete evaluations', 'mobility-trailblazers')
        ];
    }
    
    /**
     * Reset assignments
     *
     * @param array $params Parameters
     * @return array Result
     */
    private function reset_assignments($params = []) {
        if (MT_Database_Upgrade::clear_assignments()) {
            return [
                'success' => true,
                'message' => __('All assignments cleared', 'mobility-trailblazers')
            ];
        }
        
        return [
            'success' => false,
            'message' => __('Failed to clear assignments', 'mobility-trailblazers')
        ];
    }
    
    /**
     * Factory reset
     *
     * @param array $params Parameters
     * @return array Result
     */
    private function factory_reset($params = []) {
        global $wpdb;
        
        // Delete all evaluations
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}mt_evaluations");
        
        // Delete all assignments
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}mt_jury_assignments");
        
        // Delete all candidates
        $candidates = get_posts([
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);
        
        foreach ($candidates as $candidate) {
            wp_delete_post($candidate->ID, true);
        }
        
        // Delete all jury members
        $jury_members = get_posts([
            'post_type' => 'mt_jury_member',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);
        
        foreach ($jury_members as $member) {
            wp_delete_post($member->ID, true);
        }
        
        // Delete all options
        $options = [
            'mt_settings',
            'mt_criteria_weights',
            'mt_dashboard_settings',
            'mt_db_version',
            'mt_debug_script_audit'
        ];
        
        foreach ($options as $option) {
            delete_option($option);
        }
        
        // Clear all transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_mt_%' 
             OR option_name LIKE '_transient_timeout_mt_%'"
        );
        
        return [
            'success' => true,
            'message' => __('Plugin reset to factory state', 'mobility-trailblazers')
        ];
    }
    
    /**
     * Verify admin password
     *
     * @param string $password Password to verify
     * @return bool
     */
    private function verify_admin_password($password) {
        $user = wp_get_current_user();
        return wp_check_password($password, $user->user_pass, $user->ID);
    }
    
    /**
     * Log operation
     *
     * @param string $category Category
     * @param string $operation Operation
     * @return void
     */
    private function log_operation($category, $operation) {
        $log_data = [
            'category' => $category,
            'operation' => $operation,
            'user_id' => get_current_user_id(),
            'user_login' => wp_get_current_user()->user_login,
            'timestamp' => current_time('mysql'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        MT_Logger::info('Maintenance operation: ' . $category . '/' . $operation, $log_data);
    }
}
