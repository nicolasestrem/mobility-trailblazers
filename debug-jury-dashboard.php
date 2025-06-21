<?php
/**
 * Debug Jury Dashboard - Comprehensive Testing
 */

// Load WordPress
require_once 'wp-config.php';
require_once 'wp-load.php';

echo "=== Jury Dashboard Debug ===\n\n";

// Check if user is logged in
$current_user = wp_get_current_user();
echo "Current user: " . ($current_user->ID ? $current_user->user_login : 'Not logged in') . "\n";

if (!$current_user->ID) {
    echo "No user logged in. Please log in first.\n";
    exit;
}

// Check if user is a jury member
$jury_member = mt_get_jury_member_by_user_id($current_user->ID);
echo "Jury member found: " . ($jury_member ? 'Yes (ID: ' . $jury_member->ID . ')' : 'No') . "\n";

if (!$jury_member) {
    echo "User is not a jury member. Cannot access dashboard.\n";
    exit;
}

// Check assignments for this jury member
global $wpdb;
$table = $wpdb->prefix . 'mt_jury_assignments';
$assignments = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table} WHERE jury_member_id = %d AND is_active = 1",
    $jury_member->ID
));

echo "\n=== Assignments for Jury Member {$jury_member->ID} ===\n";
echo "Total assignments: " . count($assignments) . "\n";

if (empty($assignments)) {
    echo "No assignments found for this jury member.\n";
    echo "This explains why no candidates are showing in the dashboard.\n";
} else {
    echo "Sample assignments:\n";
    foreach (array_slice($assignments, 0, 5) as $assignment) {
        $candidate = get_post($assignment->candidate_id);
        echo "- Candidate ID: {$assignment->candidate_id}, Name: " . ($candidate ? $candidate->post_title : 'Unknown') . "\n";
    }
}

// Test the utility function
echo "\n=== Testing mt_get_assigned_candidates() ===\n";
$assigned_candidates = mt_get_assigned_candidates($jury_member->ID);
echo "Function returned: " . count($assigned_candidates) . " candidates\n";

if (!empty($assigned_candidates)) {
    echo "Sample candidates:\n";
    foreach (array_slice($assigned_candidates, 0, 3) as $candidate) {
        echo "- ID: {$candidate->ID}, Name: {$candidate->post_title}\n";
    }
}

// Test AJAX handler directly
echo "\n=== Testing AJAX Handler ===\n";
try {
    // Simulate AJAX request
    $_POST['action'] = 'mt_get_jury_dashboard_data';
    $_POST['mt_jury_nonce'] = wp_create_nonce('mt_jury_nonce');
    
    // Capture output
    ob_start();
    
    // Call the AJAX handler
    $ajax_handler = new \MobilityTrailblazers\Ajax\MT_Evaluation_Ajax();
    $ajax_handler->get_jury_dashboard_data();
    
    $output = ob_get_clean();
    
    if (empty($output)) {
        echo "AJAX handler completed without output (this is normal for AJAX responses)\n";
    } else {
        echo "AJAX handler output: " . $output . "\n";
    }
    
} catch (Exception $e) {
    echo "AJAX handler error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

// Check if autoloader is working
echo "\n=== Testing Autoloader ===\n";
try {
    $test_class = new \MobilityTrailblazers\Ajax\MT_Evaluation_Ajax();
    echo "Autoloader is working correctly - AJAX class loaded successfully\n";
} catch (Exception $e) {
    echo "Autoloader error: " . $e->getMessage() . "\n";
}

// Check database table structure
echo "\n=== Database Table Check ===\n";
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
if ($table_exists) {
    echo "Table {$table} exists\n";
    
    // Check table structure
    $columns = $wpdb->get_results("DESCRIBE {$table}");
    echo "Table columns:\n";
    foreach ($columns as $column) {
        echo "- {$column->Field}: {$column->Type} {$column->Key}\n";
    }
    
    // Check total assignments
    $total_assignments = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    echo "Total assignments in database: {$total_assignments}\n";
    
} else {
    echo "Table {$table} does not exist!\n";
}

// Check for any PHP errors
echo "\n=== PHP Error Check ===\n";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    echo "Error log file: {$error_log}\n";
    $recent_errors = shell_exec("tail -n 20 {$error_log} 2>/dev/null");
    if ($recent_errors) {
        echo "Recent errors:\n{$recent_errors}\n";
    } else {
        echo "No recent errors found\n";
    }
} else {
    echo "Error log not accessible\n";
}

echo "\n=== Debug Complete ===\n"; 