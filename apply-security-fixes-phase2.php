<?php
/**
 * Security Fix Script - Phase 2
 * Addresses critical issues found in manual security scan
 * 
 * Run this script from the plugin root directory:
 * php apply-security-fixes-phase2.php
 */

// Define plugin path
$plugin_path = __DIR__;

// Color codes for output
$red = "\033[31m";
$green = "\033[32m";
$yellow = "\033[33m";
$reset = "\033[0m";

echo "{$green}========================================{$reset}\n";
echo "{$green}Mobility Trailblazers Security Fix - Phase 2{$reset}\n";
echo "{$green}========================================{$reset}\n\n";

// Track fixes
$fixes_applied = 0;
$files_fixed = [];
$errors = [];

/**
 * Fix 1: Add ABSPATH checks to debug files
 */
function fix_abspath_checks($plugin_path) {
    global $fixes_applied, $files_fixed, $errors, $green, $yellow, $red, $reset;
    
    $debug_files = [
        'debug/fix-database.php',
        'debug/jury-import-simple.php',
        'debug/test-db-connection.php',
        'test-jury-lookup.php'
    ];
    
    $abspath_check = "<?php\n// Security check\nif (!defined('ABSPATH')) {\n    die('Direct access forbidden.');\n}\n";
    
    foreach ($debug_files as $file) {
        $file_path = $plugin_path . '/' . $file;
        
        if (file_exists($file_path)) {
            $content = file_get_contents($file_path);
            
            // Check if ABSPATH check already exists
            if (strpos($content, "defined('ABSPATH')") === false) {
                // Remove existing <?php tag if at beginning
                if (strpos($content, '<?php') === 0) {
                    $content = substr($content, 5);
                    $content = $abspath_check . $content;
                } else {
                    // Add at the beginning
                    $content = $abspath_check . "\n" . $content;
                }
                
                if (file_put_contents($file_path, $content)) {
                    echo "{$green}✓{$reset} Added ABSPATH check to: {$file}\n";
                    $fixes_applied++;
                    $files_fixed[] = $file;
                } else {
                    echo "{$red}✗{$reset} Failed to fix: {$file}\n";
                    $errors[] = "Failed to write to {$file}";
                }
            } else {
                echo "{$yellow}→{$reset} ABSPATH check already exists in: {$file}\n";
            }
        } else {
            echo "{$yellow}→{$reset} File not found: {$file}\n";
        }
    }
}

/**
 * Fix 2: Add nonce verification to templates that process user input
 */
function fix_nonce_verification($plugin_path) {
    global $fixes_applied, $files_fixed, $errors, $green, $yellow, $red, $reset;
    
    // Templates that need nonce checks
    $templates_to_fix = [
        'templates/admin/candidates.php' => [
            'search_pattern' => "if (isset(\$_GET['status']))",
            'replacement' => "// Verify nonce for filtering\nif (isset(\$_GET['mt_filter_nonce']) && !wp_verify_nonce(\$_GET['mt_filter_nonce'], 'mt_filter_candidates')) {\n    // Invalid nonce, ignore filters\n    \$_GET = array();\n}\n\nif (isset(\$_GET['status']))"
        ],
        'templates/admin/evaluations.php' => [
            'search_pattern' => "if (isset(\$_GET['candidate_id']))",
            'replacement' => "// Verify nonce for filtering\nif (isset(\$_GET['mt_filter_nonce']) && !wp_verify_nonce(\$_GET['mt_filter_nonce'], 'mt_filter_evaluations')) {\n    // Invalid nonce, ignore filters\n    \$_GET = array();\n}\n\nif (isset(\$_GET['candidate_id']))"
        ]
    ];
    
    foreach ($templates_to_fix as $file => $fix) {
        $file_path = $plugin_path . '/' . $file;
        
        if (file_exists($file_path)) {
            $content = file_get_contents($file_path);
            
            // Check if nonce verification already exists
            if (strpos($content, 'wp_verify_nonce') === false && strpos($content, $fix['search_pattern']) !== false) {
                $content = str_replace($fix['search_pattern'], $fix['replacement'], $content);
                
                if (file_put_contents($file_path, $content)) {
                    echo "{$green}✓{$reset} Added nonce verification to: {$file}\n";
                    $fixes_applied++;
                    $files_fixed[] = $file;
                } else {
                    echo "{$red}✗{$reset} Failed to fix: {$file}\n";
                    $errors[] = "Failed to write to {$file}";
                }
            } else {
                echo "{$yellow}→{$reset} Nonce verification already exists or pattern not found in: {$file}\n";
            }
        }
    }
}

/**
 * Fix 3: Add escaping to unescaped outputs
 */
function fix_unescaped_outputs($plugin_path) {
    global $fixes_applied, $files_fixed, $errors, $green, $yellow, $red, $reset;
    
    $escaping_fixes = [
        'templates/admin/candidates.php' => [
            ['line' => 176, 'old' => 'value="<?php echo $candidate_id; ?>"', 'new' => 'value="<?php echo esc_attr($candidate_id); ?>"'],
            ['line' => 212, 'old' => 'echo $status_label;', 'new' => 'echo esc_html($status_label);'],
            ['line' => 225, 'old' => '<?php echo $eval_count; ?>', 'new' => '<?php echo esc_html($eval_count); ?>']
        ],
        'templates/admin/dashboard-widget.php' => [
            ['line' => 39, 'old' => '<h3><?php echo $candidate_count; ?></h3>', 'new' => '<h3><?php echo esc_html($candidate_count); ?></h3>'],
            ['line' => 43, 'old' => '<h3><?php echo $jury_count; ?></h3>', 'new' => '<h3><?php echo esc_html($jury_count); ?></h3>'],
            ['line' => 47, 'old' => '<h3><?php echo $evaluation_count; ?></h3>', 'new' => '<h3><?php echo esc_html($evaluation_count); ?></h3>']
        ],
        'templates/admin/diagnostics.php' => [
            ['line' => 381, 'old' => 'style="color: <?php echo $info[\'exists\'] ? \'green\' : \'red\'; ?>;"', 'new' => 'style="color: <?php echo esc_attr($info[\'exists\'] ? \'green\' : \'red\'); ?>;"'],
            ['line' => 382, 'old' => '<?php echo $info[\'exists\'] ? \'✓\' : \'✗\'; ?>', 'new' => '<?php echo esc_html($info[\'exists\'] ? \'✓\' : \'✗\'); ?>']
        ]
    ];
    
    foreach ($escaping_fixes as $file => $fixes) {
        $file_path = $plugin_path . '/' . $file;
        
        if (file_exists($file_path)) {
            $content = file_get_contents($file_path);
            $fixed = false;
            
            foreach ($fixes as $fix) {
                if (strpos($content, $fix['old']) !== false) {
                    $content = str_replace($fix['old'], $fix['new'], $content);
                    $fixed = true;
                    $fixes_applied++;
                }
            }
            
            if ($fixed) {
                if (file_put_contents($file_path, $content)) {
                    echo "{$green}✓{$reset} Fixed escaping in: {$file}\n";
                    $files_fixed[] = $file;
                } else {
                    echo "{$red}✗{$reset} Failed to write: {$file}\n";
                    $errors[] = "Failed to write to {$file}";
                }
            } else {
                echo "{$yellow}→{$reset} No escaping fixes needed in: {$file}\n";
            }
        }
    }
}

/**
 * Fix 4: Add nonce fields to forms
 */
function add_nonce_fields($plugin_path) {
    global $fixes_applied, $files_fixed, $errors, $green, $yellow, $red, $reset;
    
    // Add nonce field generators to filter forms
    $forms_to_fix = [
        'templates/admin/candidates.php' => [
            'search' => '<select name="status"',
            'add_before' => '<?php wp_nonce_field(\'mt_filter_candidates\', \'mt_filter_nonce\'); ?>' . "\n                "
        ],
        'templates/admin/evaluations.php' => [
            'search' => '<select name="candidate_id"',
            'add_before' => '<?php wp_nonce_field(\'mt_filter_evaluations\', \'mt_filter_nonce\'); ?>' . "\n                "
        ]
    ];
    
    foreach ($forms_to_fix as $file => $fix) {
        $file_path = $plugin_path . '/' . $file;
        
        if (file_exists($file_path)) {
            $content = file_get_contents($file_path);
            
            // Check if nonce field already exists
            if (strpos($content, 'wp_nonce_field') === false && strpos($content, $fix['search']) !== false) {
                $content = str_replace($fix['search'], $fix['add_before'] . $fix['search'], $content);
                
                if (file_put_contents($file_path, $content)) {
                    echo "{$green}✓{$reset} Added nonce field to: {$file}\n";
                    $fixes_applied++;
                    $files_fixed[] = $file;
                } else {
                    echo "{$red}✗{$reset} Failed to add nonce field to: {$file}\n";
                    $errors[] = "Failed to write to {$file}";
                }
            } else {
                echo "{$yellow}→{$reset} Nonce field already exists in: {$file}\n";
            }
        }
    }
}

/**
 * Fix 5: Update plugin version
 */
function update_plugin_version($plugin_path) {
    global $fixes_applied, $files_fixed, $green, $reset;
    
    $main_file = $plugin_path . '/mobility-trailblazers.php';
    
    if (file_exists($main_file)) {
        $content = file_get_contents($main_file);
        
        // Update version from 2.0.14 to 2.0.15
        $content = str_replace('Version: 2.0.14', 'Version: 2.0.15', $content);
        $content = str_replace("'version' => '2.0.14'", "'version' => '2.0.15'", $content);
        
        if (file_put_contents($main_file, $content)) {
            echo "{$green}✓{$reset} Updated plugin version to 2.0.15\n";
            $fixes_applied++;
            $files_fixed[] = 'mobility-trailblazers.php';
        }
    }
}

// Run all fixes
echo "{$yellow}Applying security fixes...{$reset}\n\n";

fix_abspath_checks($plugin_path);
echo "\n";

fix_nonce_verification($plugin_path);
echo "\n";

fix_unescaped_outputs($plugin_path);
echo "\n";

add_nonce_fields($plugin_path);
echo "\n";

update_plugin_version($plugin_path);
echo "\n";

// Summary
echo "{$green}========================================{$reset}\n";
echo "{$green}Security Fix Summary{$reset}\n";
echo "{$green}========================================{$reset}\n";
echo "Fixes Applied: {$fixes_applied}\n";
echo "Files Fixed: " . count(array_unique($files_fixed)) . "\n";

if (count($errors) > 0) {
    echo "\n{$red}Errors encountered:{$reset}\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
}

echo "\n{$yellow}Next Steps:{$reset}\n";
echo "1. Run the security scanner again to verify fixes\n";
echo "2. Test the plugin functionality\n";
echo "3. Check that admin pages still work correctly\n";
echo "4. Verify AJAX operations are functional\n";

echo "\n{$green}Security fix script completed!{$reset}\n";