<?php
/**
 * Naming Convention Fix Script for Mobility Trailblazers Plugin
 * 
 * This script helps automate the process of fixing naming conventions
 * Run from plugin root directory: php naming-convention-fix.php
 * 
 * IMPORTANT: Always backup your files before running this script!
 */

class MTNamingConventionFixer {
    
    private $plugin_dir;
    private $changes_log = [];
    private $dry_run = true;
    
    public function __construct($plugin_dir, $dry_run = true) {
        $this->plugin_dir = rtrim($plugin_dir, '/');
        $this->dry_run = $dry_run;
        
        if ($this->dry_run) {
            echo "=== DRY RUN MODE - No files will be modified ===\n\n";
        }
    }
    
    /**
     * Run all naming convention fixes
     */
    public function run() {
        echo "Starting Mobility Trailblazers Naming Convention Fixes...\n\n";
        
        // Create compatibility file
        $this->createCompatibilityFile();
        
        // Fix PHP function names
        $this->fixPHPFunctionNames();
        
        // Fix CSS class names
        $this->fixCSSClassNames();
        
        // Fix JavaScript naming
        $this->fixJavaScriptNaming();
        
        // Generate report
        $this->generateReport();
    }
    
    /**
     * Create compatibility file for deprecated functions
     */
    private function createCompatibilityFile() {
        echo "Creating compatibility file...\n";
        
        $content = <<<'PHP'
<?php
/**
 * Backward compatibility functions for Mobility Trailblazers
 * 
 * This file contains deprecated function wrappers to maintain
 * backward compatibility during the transition to new naming conventions.
 * 
 * @package MobilityTrailblazers
 * @since 1.0.6
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get jury nomenclature (base or plural)
 * 
 * @deprecated 1.0.6 Use mt_get_jury_nomenclature() instead
 * @param bool $plural Whether to return plural form
 * @return string
 */
function get_jury_nomenclature($plural = false) {
    _deprecated_function(__FUNCTION__, '1.0.6', 'mt_get_jury_nomenclature');
    return mt_get_jury_nomenclature($plural);
}

/**
 * Get jury member user meta key
 * 
 * @deprecated 1.0.6 Use mt_get_jury_member_meta_key() instead
 * @return string
 */
function get_jury_member_meta_key() {
    _deprecated_function(__FUNCTION__, '1.0.6', 'mt_get_jury_member_meta_key');
    return mt_get_jury_member_meta_key();
}

/**
 * Get evaluation criteria
 * 
 * @deprecated 1.0.6 Use mt_get_evaluation_criteria() instead
 * @return array
 */
function MT_get_evaluation_criteria() {
    _deprecated_function(__FUNCTION__, '1.0.6', 'mt_get_evaluation_criteria');
    return mt_get_evaluation_criteria();
}

PHP;
        
        $file_path = $this->plugin_dir . '/includes/mt-compatibility-functions.php';
        
        if (!$this->dry_run) {
            file_put_contents($file_path, $content);
            echo "Created: $file_path\n";
        } else {
            echo "Would create: $file_path\n";
        }
        
        $this->changes_log[] = [
            'type' => 'file_created',
            'file' => $file_path,
            'description' => 'Created compatibility file for deprecated functions'
        ];
        
        echo "\n";
    }
    
    /**
     * Fix PHP function names
     */
    private function fixPHPFunctionNames() {
        echo "Fixing PHP function names...\n";
        
        $replacements = [
            [
                'file' => 'includes/mt-utility-functions.php',
                'replacements' => [
                    // Fix missing mt_ prefix
                    '/function get_jury_nomenclature\(/' => 'function mt_get_jury_nomenclature(',
                    '/function get_jury_member_meta_key\(/' => 'function mt_get_jury_member_meta_key(',
                    // Fix uppercase MT_
                    '/function MT_get_evaluation_criteria\(/' => 'function mt_get_evaluation_criteria(',
                ]
            ]
        ];
        
        // Also need to update all function calls
        $function_call_replacements = [
            // Update function calls throughout the codebase
            '/get_jury_nomenclature\(/' => 'mt_get_jury_nomenclature(',
            '/get_jury_member_meta_key\(/' => 'mt_get_jury_member_meta_key(',
            '/MT_get_evaluation_criteria\(/' => 'mt_get_evaluation_criteria(',
        ];
        
        // Process specific file replacements
        foreach ($replacements as $file_config) {
            $this->processFileReplacements($file_config['file'], $file_config['replacements']);
        }
        
        // Process function calls in all PHP files
        $this->processDirectoryReplacements('includes', '*.php', $function_call_replacements);
        $this->processDirectoryReplacements('admin', '*.php', $function_call_replacements);
        $this->processDirectoryReplacements('templates', '*.php', $function_call_replacements);
        
        echo "\n";
    }
    
    /**
     * Fix CSS class names
     */
    private function fixCSSClassNames() {
        echo "Fixing CSS class names...\n";
        
        $css_replacements = [
            // Fix missing mt- prefix
            '/\.jury-dashboard/' => '.mt-jury-dashboard',
            '/\.dashboard-header/' => '.mt-dashboard__header',
            '/\.dashboard-stats/' => '.mt-dashboard__stats',
            '/\.admin-dashboard/' => '.mt-admin-dashboard',
            '/\.candidate-card/' => '.mt-candidate-card',
            '/\.evaluation-form/' => '.mt-evaluation-form',
            
            // Fix incorrect BEM structure
            '/\.mt_dashboard_header/' => '.mt-dashboard__header',
            '/\.mt-button\.primary/' => '.mt-button--primary',
            '/\.mt-stats \.row/' => '.mt-stats__row',
            
            // Fix uppercase
            '/\.MT-button/' => '.mt-button',
            '/\.MT-notice/' => '.mt-notice',
        ];
        
        // Process CSS files
        $this->processDirectoryReplacements('assets', '*.css', $css_replacements);
        
        // Also update class names in PHP/HTML files
        $html_replacements = [
            '/"jury-dashboard"/' => '"mt-jury-dashboard"',
            "/'jury-dashboard'/" => "'mt-jury-dashboard'",
            '/"dashboard-header"/' => '"mt-dashboard__header"',
            '/"dashboard-stats"/' => '"mt-dashboard__stats"',
            '/"candidate-card"/' => '"mt-candidate-card"',
            '/"MT-button"/' => '"mt-button"',
        ];
        
        $this->processDirectoryReplacements('templates', '*.php', $html_replacements);
        $this->processDirectoryReplacements('admin/views', '*.php', $html_replacements);
        
        echo "\n";
    }
    
    /**
     * Fix JavaScript naming conventions
     */
    private function fixJavaScriptNaming() {
        echo "Fixing JavaScript naming conventions...\n";
        
        $js_replacements = [
            // Variable names (be careful with these - context matters)
            '/candidate_id(?![a-zA-Z])/' => 'candidateId',
            '/evaluation_data(?![a-zA-Z])/' => 'evaluationData',
            '/ajax_url(?![a-zA-Z])/' => 'ajaxUrl',
            
            // Function names
            '/function get_candidate_data/' => 'function getCandidateData',
            '/function save_evaluation_draft/' => 'function saveEvaluationDraft',
            '/function handle_ajax_error/' => 'function handleAjaxError',
            
            // Constants (convert to uppercase)
            '/const apiEndpoint/' => 'const API_ENDPOINT',
            '/const maxScore/' => 'const MAX_SCORE',
        ];
        
        $this->processDirectoryReplacements('assets', '*.js', $js_replacements);
        
        echo "\n";
    }
    
    /**
     * Process replacements in a specific file
     */
    private function processFileReplacements($file_path, $replacements) {
        $full_path = $this->plugin_dir . '/' . $file_path;
        
        if (!file_exists($full_path)) {
            echo "Warning: File not found: $full_path\n";
            return;
        }
        
        $content = file_get_contents($full_path);
        $original_content = $content;
        $changes_made = [];
        
        foreach ($replacements as $pattern => $replacement) {
            $count = 0;
            $content = preg_replace($pattern, $replacement, $content, -1, $count);
            
            if ($count > 0) {
                $changes_made[] = "$count occurrences of '$pattern' replaced";
            }
        }
        
        if ($content !== $original_content) {
            if (!$this->dry_run) {
                file_put_contents($full_path, $content);
                echo "Updated: $file_path\n";
            } else {
                echo "Would update: $file_path\n";
            }
            
            foreach ($changes_made as $change) {
                echo "  - $change\n";
            }
            
            $this->changes_log[] = [
                'type' => 'file_modified',
                'file' => $file_path,
                'changes' => $changes_made
            ];
        }
    }
    
    /**
     * Process replacements in all files in a directory
     */
    private function processDirectoryReplacements($directory, $pattern, $replacements) {
        $full_path = $this->plugin_dir . '/' . $directory;
        
        if (!is_dir($full_path)) {
            echo "Warning: Directory not found: $full_path\n";
            return;
        }
        
        $files = glob($full_path . '/' . $pattern);
        
        foreach ($files as $file) {
            $relative_path = str_replace($this->plugin_dir . '/', '', $file);
            $this->processFileReplacements($relative_path, $replacements);
        }
        
        // Also process subdirectories
        $subdirs = glob($full_path . '/*', GLOB_ONLYDIR);
        foreach ($subdirs as $subdir) {
            $relative_subdir = str_replace($this->plugin_dir . '/', '', $subdir);
            $this->processDirectoryReplacements($relative_subdir, $pattern, $replacements);
        }
    }
    
    /**
     * Generate a report of all changes
     */
    private function generateReport() {
        echo "\n=== NAMING CONVENTION FIX REPORT ===\n\n";
        
        $files_created = 0;
        $files_modified = 0;
        
        foreach ($this->changes_log as $change) {
            if ($change['type'] === 'file_created') {
                $files_created++;
            } elseif ($change['type'] === 'file_modified') {
                $files_modified++;
            }
        }
        
        echo "Summary:\n";
        echo "- Files created: $files_created\n";
        echo "- Files modified: $files_modified\n";
        echo "- Total changes: " . count($this->changes_log) . "\n\n";
        
        if (!$this->dry_run) {
            // Save detailed log
            $log_content = "Naming Convention Fix Log\n";
            $log_content .= "Date: " . date('Y-m-d H:i:s') . "\n\n";
            
            foreach ($this->changes_log as $change) {
                $log_content .= "File: " . $change['file'] . "\n";
                $log_content .= "Type: " . $change['type'] . "\n";
                
                if (isset($change['changes'])) {
                    $log_content .= "Changes:\n";
                    foreach ($change['changes'] as $detail) {
                        $log_content .= "  - $detail\n";
                    }
                }
                
                $log_content .= "\n";
            }
            
            file_put_contents($this->plugin_dir . '/naming-fix-log.txt', $log_content);
            echo "Detailed log saved to: naming-fix-log.txt\n";
        }
        
        echo "\n";
        
        if ($this->dry_run) {
            echo "This was a DRY RUN. No files were modified.\n";
            echo "To apply changes, run with --apply flag.\n";
        } else {
            echo "Changes have been applied.\n";
            echo "IMPORTANT: Test thoroughly before committing!\n";
        }
    }
}

// Command line execution
if (php_sapi_name() === 'cli') {
    $dry_run = !in_array('--apply', $argv);
    
    $plugin_dir = __DIR__;
    
    echo "Mobility Trailblazers Naming Convention Fixer\n";
    echo "============================================\n\n";
    
    if (!file_exists($plugin_dir . '/mobility-trailblazers.php')) {
        echo "Error: This script must be run from the plugin root directory.\n";
        exit(1);
    }
    
    $fixer = new MTNamingConventionFixer($plugin_dir, $dry_run);
    $fixer->run();
} else {
    echo "This script must be run from the command line.\n";
}