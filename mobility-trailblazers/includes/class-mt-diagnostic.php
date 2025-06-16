<?php
/**
 * Diagnostic handler class
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Diagnostic
 * Handles system diagnostics and health checks
 */
class MT_Diagnostic {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add diagnostic admin notice if issues found
        add_action('admin_notices', array($this, 'show_diagnostic_notices'));
        
        // Schedule diagnostic checks
        add_action('init', array($this, 'schedule_diagnostic_checks'));
        add_action('mt_diagnostic_check', array($this, 'run_diagnostic_check'));
        
        // Add REST endpoint for diagnostics
        add_action('rest_api_init', array($this, 'register_diagnostic_endpoint'));
    }
    
    /**
     * Show diagnostic notices in admin
     */
    public function show_diagnostic_notices() {
        // Only show on our plugin pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'mt-') === false) {
            return;
        }
        
        // Check if user can view diagnostics
        if (!current_user_can('mt_manage_awards')) {
            return;
        }
        
        // Get cached diagnostic results
        $issues = get_transient('mt_diagnostic_issues');
        
        if (!empty($issues)) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong><?php _e('Mobility Trailblazers System Issues Detected:', 'mobility-trailblazers'); ?></strong>
                </p>
                <ul>
                    <?php foreach ($issues as $issue) : ?>
                        <li><?php echo esc_html($issue); ?></li>
                    <?php endforeach; ?>
                </ul>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=mt-diagnostic'); ?>" class="button">
                        <?php _e('View Full Diagnostic Report', 'mobility-trailblazers'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Schedule diagnostic checks
     */
    public function schedule_diagnostic_checks() {
        if (!wp_next_scheduled('mt_diagnostic_check')) {
            wp_schedule_event(time(), 'daily', 'mt_diagnostic_check');
        }
    }
    
    /**
     * Run diagnostic check
     */
    public function run_diagnostic_check() {
        $results = $this->get_diagnostic_results();
        $issues = array();
        
        foreach ($results as $category => $checks) {
            foreach ($checks as $check) {
                if ($check['status'] === 'error' || $check['status'] === 'warning') {
                    $issues[] = $check['message'];
                }
            }
        }
        
        // Cache issues for 12 hours
        set_transient('mt_diagnostic_issues', $issues, 12 * HOUR_IN_SECONDS);
        
        // Log diagnostic run
        mt_log('Diagnostic check completed', 'info', array(
            'issues_found' => count($issues),
        ));
    }
    
    /**
     * Register diagnostic REST endpoint
     */
    public function register_diagnostic_endpoint() {
        register_rest_route('mobility-trailblazers/v1', '/diagnostic', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_diagnostic_endpoint'),
            'permission_callback' => function() {
                return current_user_can('mt_manage_awards');
            },
        ));
    }
    
    /**
     * Get diagnostic endpoint
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_diagnostic_endpoint($request) {
        $results = $this->get_diagnostic_results();
        return new WP_REST_Response($results);
    }
    
    /**
     * Get diagnostic results
     *
     * @return array Diagnostic results
     */
    public function get_diagnostic_results() {
        $results = array(
            'system' => $this->check_system_requirements(),
            'database' => $this->check_database(),
            'post_types' => $this->check_post_types(),
            'taxonomies' => $this->check_taxonomies(),
            'roles' => $this->check_roles(),
            'files' => $this->check_files(),
            'data_integrity' => $this->check_data_integrity(),
            'performance' => $this->check_performance(),
        );
        
        return $results;
    }
    
    /**
     * Check system requirements
     *
     * @return array System check results
     */
    private function check_system_requirements() {
        $checks = array();
        
        // PHP Version
        $php_version = phpversion();
        $php_required = '7.4';
        $php_recommended = '8.2';
        
        if (version_compare($php_version, $php_required, '<')) {
            $checks[] = array(
                'name' => 'PHP Version',
                'status' => 'error',
                'message' => sprintf(__('PHP %s or higher required. You have %s.', 'mobility-trailblazers'), $php_required, $php_version),
            );
        } elseif (version_compare($php_version, $php_recommended, '<')) {
            $checks[] = array(
                'name' => 'PHP Version',
                'status' => 'warning',
                'message' => sprintf(__('PHP %s recommended. You have %s.', 'mobility-trailblazers'), $php_recommended, $php_version),
            );
        } else {
            $checks[] = array(
                'name' => 'PHP Version',
                'status' => 'success',
                'message' => sprintf(__('PHP %s', 'mobility-trailblazers'), $php_version),
            );
        }
        
        // WordPress Version
        $wp_version = get_bloginfo('version');
        $wp_required = '5.8';
        
        if (version_compare($wp_version, $wp_required, '<')) {
            $checks[] = array(
                'name' => 'WordPress Version',
                'status' => 'error',
                'message' => sprintf(__('WordPress %s or higher required. You have %s.', 'mobility-trailblazers'), $wp_required, $wp_version),
            );
        } else {
            $checks[] = array(
                'name' => 'WordPress Version',
                'status' => 'success',
                'message' => sprintf(__('WordPress %s', 'mobility-trailblazers'), $wp_version),
            );
        }
        
        // Memory Limit
        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        $memory_required = 256 * MB_IN_BYTES;
        
        if ($memory_limit < $memory_required) {
            $checks[] = array(
                'name' => 'Memory Limit',
                'status' => 'warning',
                'message' => sprintf(__('Memory limit of %s recommended. You have %s.', 'mobility-trailblazers'), size_format($memory_required), size_format($memory_limit)),
            );
        } else {
            $checks[] = array(
                'name' => 'Memory Limit',
                'status' => 'success',
                'message' => sprintf(__('Memory limit: %s', 'mobility-trailblazers'), size_format($memory_limit)),
            );
        }
        
        // Max Execution Time
        $max_execution_time = ini_get('max_execution_time');
        
        if ($max_execution_time > 0 && $max_execution_time < 60) {
            $checks[] = array(
                'name' => 'Max Execution Time',
                'status' => 'warning',
                'message' => sprintf(__('Max execution time of 60 seconds recommended. You have %d seconds.', 'mobility-trailblazers'), $max_execution_time),
            );
        } else {
            $checks[] = array(
                'name' => 'Max Execution Time',
                'status' => 'success',
                'message' => sprintf(__('Max execution time: %s', 'mobility-trailblazers'), $max_execution_time == 0 ? __('Unlimited', 'mobility-trailblazers') : $max_execution_time . ' ' . __('seconds', 'mobility-trailblazers')),
            );
        }
        
        return $checks;
    }
    
    /**
     * Check database
     *
     * @return array Database check results
     */
    private function check_database() {
        global $wpdb;
        $checks = array();
        
        // Check required tables
        $required_tables = array(
            'mt_votes' => $wpdb->prefix . 'mt_votes',
            'mt_candidate_scores' => $wpdb->prefix . 'mt_candidate_scores',
            'vote_reset_logs' => $wpdb->prefix . 'vote_reset_logs',
            'mt_vote_backups' => $wpdb->prefix . 'mt_vote_backups',
        );
        
        foreach ($required_tables as $name => $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
            
            if ($exists) {
                // Check table structure
                $columns = $wpdb->get_results("SHOW COLUMNS FROM $table");
                $column_count = count($columns);
                
                $checks[] = array(
                    'name' => sprintf(__('Table: %s', 'mobility-trailblazers'), $name),
                    'status' => 'success',
                    'message' => sprintf(__('Table exists with %d columns', 'mobility-trailblazers'), $column_count),
                );
            } else {
                $checks[] = array(
                    'name' => sprintf(__('Table: %s', 'mobility-trailblazers'), $name),
                    'status' => 'error',
                    'message' => __('Table does not exist', 'mobility-trailblazers'),
                );
            }
        }
        
        // Check database version
        $db_version = get_option('mt_db_version', '0');
        $current_version = '1.0.2';
        
        if (version_compare($db_version, $current_version, '<')) {
            $checks[] = array(
                'name' => 'Database Version',
                'status' => 'warning',
                'message' => sprintf(__('Database update required. Current: %s, Required: %s', 'mobility-trailblazers'), $db_version, $current_version),
            );
        } else {
            $checks[] = array(
                'name' => 'Database Version',
                'status' => 'success',
                'message' => sprintf(__('Database version: %s', 'mobility-trailblazers'), $db_version),
            );
        }
        
        return $checks;
    }
    
    /**
     * Check post types
     *
     * @return array Post type check results
     */
    private function check_post_types() {
        $checks = array();
        
        $required_post_types = array(
            'mt_candidate' => __('Candidates', 'mobility-trailblazers'),
            'mt_jury' => __('Jury Members', 'mobility-trailblazers'),
            'mt_backup' => __('Backups', 'mobility-trailblazers'),
        );
        
        foreach ($required_post_types as $post_type => $label) {
            if (post_type_exists($post_type)) {
                $count = wp_count_posts($post_type);
                $total = isset($count->publish) ? $count->publish : 0;
                
                $checks[] = array(
                    'name' => $label,
                    'status' => 'success',
                    'message' => sprintf(__('Registered (%d published)', 'mobility-trailblazers'), $total),
                );
            } else {
                $checks[] = array(
                    'name' => $label,
                    'status' => 'error',
                    'message' => __('Not registered', 'mobility-trailblazers'),
                );
            }
        }
        
        return $checks;
    }
    
    /**
     * Check taxonomies
     *
     * @return array Taxonomy check results
     */
    private function check_taxonomies() {
        $checks = array();
        
        $required_taxonomies = array(
            'mt_category' => __('Categories', 'mobility-trailblazers'),
            'mt_phase' => __('Phases', 'mobility-trailblazers'),
            'mt_status' => __('Statuses', 'mobility-trailblazers'),
            'mt_award_year' => __('Award Years', 'mobility-trailblazers'),
        );
        
        foreach ($required_taxonomies as $taxonomy => $label) {
            if (taxonomy_exists($taxonomy)) {
                $terms = get_terms(array(
                    'taxonomy' => $taxonomy,
                    'hide_empty' => false,
                ));
                
                $count = is_array($terms) ? count($terms) : 0;
                
                $checks[] = array(
                    'name' => $label,
                    'status' => 'success',
                    'message' => sprintf(__('Registered (%d terms)', 'mobility-trailblazers'), $count),
                );
            } else {
                $checks[] = array(
                    'name' => $label,
                    'status' => 'error',
                    'message' => __('Not registered', 'mobility-trailblazers'),
                );
            }
        }
        
        return $checks;
    }
    
    /**
     * Check roles
     *
     * @return array Role check results
     */
    private function check_roles() {
        $checks = array();
        
        $required_roles = array(
            'mt_award_admin' => __('MT Award Admin', 'mobility-trailblazers'),
            'mt_jury_member' => __('MT Jury Member', 'mobility-trailblazers'),
        );
        
        foreach ($required_roles as $role => $label) {
            $role_obj = get_role($role);
            
            if ($role_obj) {
                $users = get_users(array('role' => $role));
                $count = count($users);
                
                $checks[] = array(
                    'name' => $label,
                    'status' => 'success',
                    'message' => sprintf(__('Role exists (%d users)', 'mobility-trailblazers'), $count),
                );
            } else {
                $checks[] = array(
                    'name' => $label,
                    'status' => 'error',
                    'message' => __('Role does not exist', 'mobility-trailblazers'),
                );
            }
        }
        
        // Check capabilities
        $admin_role = get_role('administrator');
        $required_caps = array('mt_manage_awards', 'mt_submit_evaluations', 'mt_reset_votes');
        $missing_caps = array();
        
        foreach ($required_caps as $cap) {
            if (!$admin_role || !isset($admin_role->capabilities[$cap])) {
                $missing_caps[] = $cap;
            }
        }
        
        if (empty($missing_caps)) {
            $checks[] = array(
                'name' => __('Admin Capabilities', 'mobility-trailblazers'),
                'status' => 'success',
                'message' => __('All capabilities assigned', 'mobility-trailblazers'),
            );
        } else {
            $checks[] = array(
                'name' => __('Admin Capabilities', 'mobility-trailblazers'),
                'status' => 'warning',
                'message' => sprintf(__('Missing capabilities: %s', 'mobility-trailblazers'), implode(', ', $missing_caps)),
            );
        }
        
        return $checks;
    }
    
    /**
     * Check files
     *
     * @return array File check results
     */
    private function check_files() {
        $checks = array();
        
        // Check critical files
        $critical_files = array(
            'includes/class-database.php' => __('Database Class', 'mobility-trailblazers'),
            'includes/class-post-types.php' => __('Post Types Class', 'mobility-trailblazers'),
            'includes/class-taxonomies.php' => __('Taxonomies Class', 'mobility-trailblazers'),
            'includes/class-roles.php' => __('Roles Class', 'mobility-trailblazers'),
        );
        
        foreach ($critical_files as $file => $label) {
            $file_path = MT_PLUGIN_DIR . $file;
            
            if (file_exists($file_path)) {
                $checks[] = array(
                    'name' => $label,
                    'status' => 'success',
                    'message' => __('File exists', 'mobility-trailblazers'),
                );
            } else {
                $checks[] = array(
                    'name' => $label,
                    'status' => 'error',
                    'message' => __('File missing', 'mobility-trailblazers'),
                );
            }
        }
        
        // Check asset files
        $asset_files = array(
            'assets/admin.css' => __('Admin CSS', 'mobility-trailblazers'),
            'assets/frontend.css' => __('Frontend CSS', 'mobility-trailblazers'),
            'assets/admin.js' => __('Admin JS', 'mobility-trailblazers'),
        );
        
        foreach ($asset_files as $file => $label) {
            $file_path = MT_PLUGIN_DIR . $file;
            
            if (file_exists($file_path)) {
                $size = filesize($file_path);
                $checks[] = array(
                    'name' => $label,
                    'status' => 'success',
                    'message' => sprintf(__('File exists (%s)', 'mobility-trailblazers'), size_format($size)),
                );
            } else {
                $checks[] = array(
                    'name' => $label,
                    'status' => 'warning',
                    'message' => __('File missing', 'mobility-trailblazers'),
                );
            }
        }
        
        // Check upload directory
        $upload_dir = wp_upload_dir();
        $mt_upload_dir = $upload_dir['basedir'] . '/mobility-trailblazers';
        
        if (is_dir($mt_upload_dir)) {
            if (is_writable($mt_upload_dir)) {
                $checks[] = array(
                    'name' => __('Upload Directory', 'mobility-trailblazers'),
                    'status' => 'success',
                    'message' => __('Directory exists and is writable', 'mobility-trailblazers'),
                );
            } else {
                $checks[] = array(
                    'name' => __('Upload Directory', 'mobility-trailblazers'),
                    'status' => 'warning',
                    'message' => __('Directory exists but is not writable', 'mobility-trailblazers'),
                );
            }
        } else {
            // Try to create it
            if (wp_mkdir_p($mt_upload_dir)) {
                $checks[] = array(
                    'name' => __('Upload Directory', 'mobility-trailblazers'),
                    'status' => 'success',
                    'message' => __('Directory created successfully', 'mobility-trailblazers'),
                );
            } else {
                $checks[] = array(
                    'name' => __('Upload Directory', 'mobility-trailblazers'),
                    'status' => 'warning',
                    'message' => __('Directory does not exist', 'mobility-trailblazers'),
                );
            }
        }
        
        return $checks;
    }
    
    /**
     * Check data integrity
     *
     * @return array Data integrity check results
     */
    private function check_data_integrity() {
        global $wpdb;
        $checks = array();
        
        // Check for orphaned evaluations
        $table_name = $wpdb->prefix . 'mt_candidate_scores';
        $orphaned_evaluations = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM $table_name s
            LEFT JOIN {$wpdb->posts} c ON s.candidate_id = c.ID
            LEFT JOIN {$wpdb->posts} j ON s.jury_member_id = j.ID
            WHERE (c.ID IS NULL OR j.ID IS NULL) AND s.is_active = 1
        ");
        
        if ($orphaned_evaluations > 0) {
            $checks[] = array(
                'name' => __('Orphaned Evaluations', 'mobility-trailblazers'),
                'status' => 'warning',
                'message' => sprintf(__('%d orphaned evaluations found', 'mobility-trailblazers'), $orphaned_evaluations),
            );
        } else {
            $checks[] = array(
                'name' => __('Orphaned Evaluations', 'mobility-trailblazers'),
                'status' => 'success',
                'message' => __('No orphaned evaluations', 'mobility-trailblazers'),
            );
        }
        
        // Check for duplicate evaluations
        $duplicates = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM (
                SELECT candidate_id, jury_member_id, evaluation_round, COUNT(*) as count
                FROM $table_name
                WHERE is_active = 1
                GROUP BY candidate_id, jury_member_id, evaluation_round
                HAVING count > 1
            ) as duplicates
        ");
        
        if ($duplicates > 0) {
            $checks[] = array(
                'name' => __('Duplicate Evaluations', 'mobility-trailblazers'),
                'status' => 'warning',
                'message' => sprintf(__('%d duplicate evaluations found', 'mobility-trailblazers'), $duplicates),
            );
        } else {
            $checks[] = array(
                'name' => __('Duplicate Evaluations', 'mobility-trailblazers'),
                'status' => 'success',
                'message' => __('No duplicate evaluations', 'mobility-trailblazers'),
            );
        }
        
        // Check for invalid scores
        $invalid_scores = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM $table_name
            WHERE is_active = 1
            AND (
                courage_score < 0 OR courage_score > 10 OR
                innovation_score < 0 OR innovation_score > 10 OR
                implementation_score < 0 OR implementation_score > 10 OR
                relevance_score < 0 OR relevance_score > 10 OR
                visibility_score < 0 OR visibility_score > 10
            )
        ");
        
        if ($invalid_scores > 0) {
            $checks[] = array(
                'name' => __('Invalid Scores', 'mobility-trailblazers'),
                'status' => 'error',
                'message' => sprintf(__('%d evaluations with invalid scores', 'mobility-trailblazers'), $invalid_scores),
            );
        } else {
            $checks[] = array(
                'name' => __('Invalid Scores', 'mobility-trailblazers'),
                'status' => 'success',
                'message' => __('All scores are valid', 'mobility-trailblazers'),
            );
        }
        
        // Check for unlinked jury users
        $unlinked_jury = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_mt_user_id'
            WHERE p.post_type = 'mt_jury' 
            AND p.post_status = 'publish'
            AND (pm.meta_value IS NULL OR pm.meta_value = '')
        ");
        
        if ($unlinked_jury > 0) {
            $checks[] = array(
                'name' => __('Unlinked Jury Members', 'mobility-trailblazers'),
                'status' => 'info',
                'message' => sprintf(__('%d jury members without user accounts', 'mobility-trailblazers'), $unlinked_jury),
            );
        } else {
            $checks[] = array(
                'name' => __('Unlinked Jury Members', 'mobility-trailblazers'),
                'status' => 'success',
                'message' => __('All jury members have user accounts', 'mobility-trailblazers'),
            );
        }
        
        return $checks;
    }
    
    /**
     * Check performance
     *
     * @return array Performance check results
     */
    private function check_performance() {
        global $wpdb;
        $checks = array();
        
        // Check database size
        $table_sizes = array();
        $tables = array(
            'mt_votes',
            'mt_candidate_scores',
            'vote_reset_logs',
            'mt_vote_backups',
        );
        
        $total_size = 0;
        
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $size = $wpdb->get_row("
                SELECT 
                    table_name,
                    round(((data_length + index_length) / 1024 / 1024), 2) as size_mb,
                    table_rows
                FROM information_schema.TABLES 
                WHERE table_schema = '" . DB_NAME . "'
                AND table_name = '$table_name'
            ");
            
            if ($size) {
                $table_sizes[$table] = $size;
                $total_size += $size->size_mb;
            }
        }
        
        if ($total_size > 100) {
            $checks[] = array(
                'name' => __('Database Size', 'mobility-trailblazers'),
                'status' => 'warning',
                'message' => sprintf(__('Total size: %s MB (consider optimization)', 'mobility-trailblazers'), $total_size),
            );
        } else {
            $checks[] = array(
                'name' => __('Database Size', 'mobility-trailblazers'),
                'status' => 'success',
                'message' => sprintf(__('Total size: %s MB', 'mobility-trailblazers'), $total_size),
            );
        }
        
        // Check for missing indexes
        $votes_table = $wpdb->prefix . 'mt_votes';
        $indexes = $wpdb->get_results("SHOW INDEX FROM $votes_table");
        $index_columns = wp_list_pluck($indexes, 'Column_name');
        
        $required_indexes = array('candidate_id', 'jury_member_id', 'is_active');
        $missing_indexes = array_diff($required_indexes, $index_columns);
        
        if (!empty($missing_indexes)) {
            $checks[] = array(
                'name' => __('Database Indexes', 'mobility-trailblazers'),
                'status' => 'warning',
                'message' => sprintf(__('Missing indexes on: %s', 'mobility-trailblazers'), implode(', ', $missing_indexes)),
            );
        } else {
            $checks[] = array(
                'name' => __('Database Indexes', 'mobility-trailblazers'),
                'status' => 'success',
                'message' => __('All required indexes present', 'mobility-trailblazers'),
            );
        }
        
        // Check backup retention
        $backup_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_vote_backups");
        $oldest_backup = $wpdb->get_var("SELECT MIN(created_at) FROM {$wpdb->prefix}mt_vote_backups");
        
        if ($backup_count > 1000) {
            $checks[] = array(
                'name' => __('Backup Retention', 'mobility-trailblazers'),
                'status' => 'warning',
                'message' => sprintf(__('%d backups stored (consider cleanup)', 'mobility-trailblazers'), $backup_count),
            );
        } else {
            $checks[] = array(
                'name' => __('Backup Retention', 'mobility-trailblazers'),
                'status' => 'success',
                'message' => sprintf(__('%d backups stored', 'mobility-trailblazers'), $backup_count),
            );
        }
        
        // Check cron jobs
        $next_diagnostic = wp_next_scheduled('mt_diagnostic_check');
        
        if ($next_diagnostic) {
            $checks[] = array(
                'name' => __('Scheduled Tasks', 'mobility-trailblazers'),
                'status' => 'success',
                'message' => sprintf(__('Next diagnostic check: %s', 'mobility-trailblazers'), mt_format_date($next_diagnostic)),
            );
        } else {
            $checks[] = array(
                'name' => __('Scheduled Tasks', 'mobility-trailblazers'),
                'status' => 'warning',
                'message' => __('Diagnostic check not scheduled', 'mobility-trailblazers'),
            );
        }
        
        return $checks;
    }
    
    /**
     * Get system information
     *
     * @return array System information
     */
    public function get_system_info() {
        global $wpdb;
        
        $info = array(
            'plugin_version' => MT_PLUGIN_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => phpversion(),
            'mysql_version' => $wpdb->db_version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'timezone' => wp_timezone_string(),
            'debug_mode' => WP_DEBUG ? 'Enabled' : 'Disabled',
            'multisite' => is_multisite() ? 'Yes' : 'No',
            'active_theme' => wp_get_theme()->get('Name'),
            'active_plugins' => $this->get_active_plugins(),
        );
        
        return $info;
    }
    
    /**
     * Get active plugins
     *
     * @return array Active plugins
     */
    private function get_active_plugins() {
        $active_plugins = get_option('active_plugins', array());
        $plugins = array();
        
        foreach ($active_plugins as $plugin) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
            $plugins[] = $plugin_data['Name'] . ' v' . $plugin_data['Version'];
        }
        
        return $plugins;
    }
    
    /**
     * Export diagnostic report
     *
     * @return string Diagnostic report
     */
    public function export_diagnostic_report() {
        $results = $this->get_diagnostic_results();
        $system_info = $this->get_system_info();
        
        $report = "=== Mobility Trailblazers Diagnostic Report ===\n";
        $report .= "Generated: " . current_time('Y-m-d H:i:s') . "\n\n";
        
        // System Information
        $report .= "== System Information ==\n";
        foreach ($system_info as $key => $value) {
            if (is_array($value)) {
                $report .= ucwords(str_replace('_', ' ', $key)) . ":\n";
                foreach ($value as $item) {
                    $report .= "  - " . $item . "\n";
                }
            } else {
                $report .= ucwords(str_replace('_', ' ', $key)) . ": " . $value . "\n";
            }
        }
        $report .= "\n";
        
        // Diagnostic Results
        foreach ($results as $category => $checks) {
            $report .= "== " . ucwords(str_replace('_', ' ', $category)) . " ==\n";
            
            foreach ($checks as $check) {
                $status_icon = $check['status'] === 'success' ? '[OK]' : 
                              ($check['status'] === 'warning' ? '[WARN]' : '[FAIL]');
                
                $report .= $status_icon . " " . $check['name'] . ": " . $check['message'] . "\n";
            }
            
            $report .= "\n";
        }
        
        return $report;
    }
} 