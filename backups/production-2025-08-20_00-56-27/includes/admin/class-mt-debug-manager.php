<?php
/**
 * Debug Manager for controlling debug script access
 *
 * @package MobilityTrailblazers
 * @since 2.3.0
 */

namespace MobilityTrailblazers\Admin;

use MobilityTrailblazers\Core\MT_Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Debug_Manager
 *
 * Manages debug script execution and access control
 */
class MT_Debug_Manager {
    
    /**
     * Allowed scripts based on environment
     *
     * @var array
     */
    private $allowed_scripts = [];
    
    /**
     * Current environment
     *
     * @var string
     */
    private $environment;
    
    /**
     * Script categories and their scripts
     *
     * @var array
     */
    private $script_categories = [
        'generators' => [
            'title' => 'Test Data Generators',
            'description' => 'Scripts for generating test data',
            'scripts' => [
                'fake-candidates-generator.php' => [
                    'title' => 'Fake Candidates Generator',
                    'description' => 'Generate test candidate data',
                    'dangerous' => false,
                    'environments' => ['development', 'staging']
                ],
                'generate-sample-profiles.php' => [
                    'title' => 'Sample Profiles Generator',
                    'description' => 'Generate sample profile data',
                    'dangerous' => false,
                    'environments' => ['development', 'staging']
                ]
            ]
        ],
        'migrations' => [
            'title' => 'Migration Tools',
            'description' => 'Database and data migration scripts',
            'scripts' => [
                'migrate-candidate-profiles.php' => [
                    'title' => 'Candidate Profile Migration',
                    'description' => 'Migrate candidate profile data structure',
                    'dangerous' => true,
                    'environments' => ['development', 'staging', 'production'],
                    'requires_backup' => true
                ],
                'migrate-jury-posts.php' => [
                    'title' => 'Jury Posts Migration',
                    'description' => 'Migrate jury member posts',
                    'dangerous' => true,
                    'environments' => ['development', 'staging', 'production'],
                    'requires_backup' => true
                ]
            ]
        ],
        'diagnostics' => [
            'title' => 'Diagnostic Scripts',
            'description' => 'System diagnostic and check scripts',
            'scripts' => [
                'check-jury-status.php' => [
                    'title' => 'Check Jury Status',
                    'description' => 'Verify jury member status and assignments',
                    'dangerous' => false,
                    'environments' => ['development', 'staging', 'production']
                ],
                'test-db-connection.php' => [
                    'title' => 'Test Database Connection',
                    'description' => 'Test database connectivity and tables',
                    'dangerous' => false,
                    'environments' => ['development', 'staging', 'production']
                ],
                'check-schneidewind-import.php' => [
                    'title' => 'Check Specific Import',
                    'description' => 'Verify specific candidate import',
                    'dangerous' => false,
                    'environments' => ['development', 'staging', 'production']
                ],
                'performance-test.php' => [
                    'title' => 'Performance Test',
                    'description' => 'Run performance diagnostics',
                    'dangerous' => false,
                    'environments' => ['development', 'staging']
                ]
            ]
        ],
        'repairs' => [
            'title' => 'Repair Utilities',
            'description' => 'Scripts for fixing data and structure issues',
            'scripts' => [
                'fix-database.php' => [
                    'title' => 'Fix Database Structure',
                    'description' => 'Repair database structure issues',
                    'dangerous' => true,
                    'environments' => ['development', 'staging', 'production'],
                    'requires_confirmation' => true
                ],
                'fix-assignments.php' => [
                    'title' => 'Fix Assignments',
                    'description' => 'Repair assignment data issues',
                    'dangerous' => true,
                    'environments' => ['development', 'staging', 'production'],
                    'requires_confirmation' => true
                ]
            ]
        ],
        'imports' => [
            'title' => 'Import Utilities',
            'description' => 'Data import scripts',
            'scripts' => [
                'jury-import.php' => [
                    'title' => 'Jury Member Import',
                    'description' => 'Import jury member data',
                    'dangerous' => true,
                    'environments' => ['development', 'staging']
                ],
                'test-import-handler.php' => [
                    'title' => 'Test Import Handler',
                    'description' => 'Test import functionality',
                    'dangerous' => false,
                    'environments' => ['development']
                ]
            ]
        ],
        'testing' => [
            'title' => 'Testing Scripts',
            'description' => 'Scripts for testing functionality',
            'scripts' => [
                'test-profile-system.php' => [
                    'title' => 'Test Profile System',
                    'description' => 'Test profile functionality',
                    'dangerous' => false,
                    'environments' => ['development', 'staging']
                ],
                'test-evaluation-parsing.php' => [
                    'title' => 'Test Evaluation Parsing',
                    'description' => 'Test evaluation parsing logic',
                    'dangerous' => false,
                    'environments' => ['development']
                ]
            ]
        ]
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->environment = $this->detect_environment();
        $this->setup_allowed_scripts();
    }
    
    /**
     * Detect current environment
     *
     * @return string Environment type
     */
    private function detect_environment() {
        if (defined('MT_ENVIRONMENT')) {
            return MT_ENVIRONMENT;
        }
        
        if (defined('WP_ENVIRONMENT_TYPE')) {
            return WP_ENVIRONMENT_TYPE;
        }
        
        // Check common development indicators
        $site_url = get_site_url();
        if (strpos($site_url, 'localhost') !== false ||
            strpos($site_url, '.local') !== false ||
            strpos($site_url, '.test') !== false ||
            strpos($site_url, 'staging') !== false) {
            return 'development';
        }
        
        return 'production';
    }
    
    /**
     * Setup allowed scripts based on environment
     *
     * @return void
     */
    private function setup_allowed_scripts() {
        foreach ($this->script_categories as $category => $data) {
            foreach ($data['scripts'] as $script => $info) {
                if (in_array($this->environment, $info['environments'])) {
                    $this->allowed_scripts[$category][$script] = $info;
                }
            }
        }
    }
    
    /**
     * Check if environment is production
     *
     * @return bool
     */
    public function is_production() {
        return $this->environment === 'production';
    }
    
    /**
     * Get current environment
     *
     * @return string
     */
    public function get_environment() {
        return $this->environment;
    }
    
    /**
     * Get allowed script categories
     *
     * @return array
     */
    public function get_script_categories() {
        $categories = [];
        
        foreach ($this->script_categories as $key => $category) {
            if (isset($this->allowed_scripts[$key]) && !empty($this->allowed_scripts[$key])) {
                $categories[$key] = [
                    'title' => $category['title'],
                    'description' => $category['description'],
                    'scripts' => $this->allowed_scripts[$key]
                ];
            }
        }
        
        return $categories;
    }
    
    /**
     * Check if script is allowed
     *
     * @param string $script Script filename
     * @return bool
     */
    public function is_script_allowed($script) {
        foreach ($this->allowed_scripts as $category => $scripts) {
            if (isset($scripts[$script])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get script info
     *
     * @param string $script Script filename
     * @return array|null Script info or null if not found
     */
    public function get_script_info($script) {
        foreach ($this->allowed_scripts as $category => $scripts) {
            if (isset($scripts[$script])) {
                return array_merge($scripts[$script], ['category' => $category]);
            }
        }
        
        return null;
    }
    
    /**
     * Execute debug script
     *
     * @param string $script Script filename
     * @param array $params Parameters to pass to script
     * @return array Execution result
     */
    public function execute_script($script, $params = []) {
        $result = [
            'success' => false,
            'message' => '',
            'output' => '',
            'errors' => []
        ];
        
        // Check if script is allowed
        if (!$this->is_script_allowed($script)) {
            $result['message'] = __('Script not allowed in current environment', 'mobility-trailblazers');
            return $result;
        }
        
        // Get script info
        $info = $this->get_script_info($script);
        if (!$info) {
            $result['message'] = __('Script information not found', 'mobility-trailblazers');
            return $result;
        }
        
        // Check for dangerous operations
        if ($info['dangerous'] && $this->is_production()) {
            if (!isset($params['confirm']) || !$params['confirm']) {
                $result['message'] = __('Dangerous operation requires confirmation', 'mobility-trailblazers');
                $result['requires_confirmation'] = true;
                return $result;
            }
        }
        
        // Check for backup requirement
        if (isset($info['requires_backup']) && $info['requires_backup']) {
            if (!isset($params['backup_confirmed']) || !$params['backup_confirmed']) {
                $result['message'] = __('This operation requires backup confirmation', 'mobility-trailblazers');
                $result['requires_backup'] = true;
                return $result;
            }
        }
        
        // Build script path
        $script_path = $this->get_script_path($script, $info['category']);
        
        if (!file_exists($script_path)) {
            $result['message'] = __('Script file not found', 'mobility-trailblazers');
            return $result;
        }
        
        // Log execution attempt
        $this->log_script_execution($script, $info);
        
        // Start output buffering
        ob_start();
        $error_handler_set = false;
        
        try {
            // Set error handler
            set_error_handler(function($severity, $message, $file, $line) use (&$result) {
                $result['errors'][] = [
                    'severity' => $severity,
                    'message' => $message,
                    'file' => $file,
                    'line' => $line
                ];
            });
            $error_handler_set = true;
            
            // Execute script
            $script_result = include $script_path;
            
            // Get output (make sure $result is still an array)
            $output_content = ob_get_contents();
            
            if (!is_array($result)) {
                // Script may have overwritten $result, restore it
                $result = [
                    'success' => false,
                    'message' => '',
                    'output' => '',
                    'errors' => []
                ];
            }
            // Sanitize HTML output to prevent XSS
            $result['output'] = wp_kses_post($output_content);
            
            // Check result
            if ($script_result === false) {
                $result['message'] = __('Script execution failed', 'mobility-trailblazers');
            } else {
                $result['success'] = true;
                $result['message'] = __('Script executed successfully', 'mobility-trailblazers');
                
                if (is_array($script_result) || is_object($script_result)) {
                    $result['data'] = $script_result;
                }
            }
            
        } catch (\Exception $e) {
            // Get output before cleaning buffer
            $output_content = ob_get_contents();
            // Sanitize HTML output to prevent XSS
            $result['output'] = wp_kses_post($output_content);
            
            $result['message'] = sprintf(
                __('Script execution error: %s', 'mobility-trailblazers'),
                $e->getMessage()
            );
            $result['errors'][] = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
            
        } finally {
            // Clean up - end buffer without discarding content
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            if ($error_handler_set) {
                restore_error_handler();
            }
        }
        
        return $result;
    }
    
    /**
     * Get script path
     *
     * @param string $script Script filename
     * @param string $category Script category
     * @return string Full script path
     */
    private function get_script_path($script, $category) {
        // Map categories to directories
        $directory_map = [
            'generators' => 'generators',
            'migrations' => 'migrations',
            'diagnostics' => 'diagnostics',
            'repairs' => 'repairs',
            'imports' => '',
            'testing' => ''
        ];
        
        $base_dir = MT_PLUGIN_DIR . 'debug/';
        
        if (isset($directory_map[$category]) && !empty($directory_map[$category])) {
            return $base_dir . $directory_map[$category] . '/' . $script;
        }
        
        return $base_dir . $script;
    }
    
    /**
     * Log script execution
     *
     * @param string $script Script name
     * @param array $info Script info
     * @return void
     */
    private function log_script_execution($script, $info) {
        $log_data = [
            'script' => $script,
            'category' => $info['category'] ?? 'unknown',
            'user_id' => get_current_user_id(),
            'user_login' => wp_get_current_user()->user_login,
            'environment' => $this->environment,
            'timestamp' => current_time('mysql'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        // Log to custom logger
        MT_Logger::info('Debug script execution: ' . $script, $log_data);
        
        // Also save to options for audit trail
        $audit_log = get_option('mt_debug_script_audit', []);
        array_unshift($audit_log, $log_data);
        
        // Keep only last 100 entries
        $audit_log = array_slice($audit_log, 0, 100);
        
        update_option('mt_debug_script_audit', $audit_log);
    }
    
    /**
     * Get script execution audit log
     *
     * @param int $limit Number of entries to retrieve
     * @return array Audit log entries
     */
    public function get_audit_log($limit = 50) {
        $audit_log = get_option('mt_debug_script_audit', []);
        return array_slice($audit_log, 0, $limit);
    }
    
    /**
     * Clear audit log
     *
     * @return bool Success
     */
    public function clear_audit_log() {
        return update_option('mt_debug_script_audit', []);
    }
    
    /**
     * Get deprecated scripts
     *
     * @return array List of deprecated scripts
     */
    public function get_deprecated_scripts() {
        return [
            'test-regex-debug.php',
            'fix-existing-evaluations.php',
            'direct-fix-evaluations.php',
            'final-fix-evaluations.php',
            'test-evaluation-parsing.php'
        ];
    }
}
