<?php
// Security check
if (!defined('ABSPATH')) {
    die('Direct access forbidden.');
}

/**
 * Database Fix Script for Mobility Trailblazers
 * 
 * This script can be run directly to fix database issues.
 * Place this file in the plugin directory and run it via browser or command line.
 * 
 * Usage: 
 * - Via browser: https://yoursite.com/wp-content/plugins/mobility-trailblazers/fix-database.php
 * - Via command line: php fix-database.php
 */

// WordPress is already loaded via AJAX
// No need to require wp-load.php

// Security check
if (!current_user_can('manage_options')) {
    die('Access denied. You must be an administrator.');
}

echo '<h1>' . esc_html__('Mobility Trailblazers Database Fix', 'mobility-trailblazers') . '</h1>\n';

// Check if plugin is active
if (!defined('MT_VERSION')) {
    die('Mobility Trailblazers plugin is not active.');
}

echo "<p>Plugin version: " . MT_VERSION . "</p>\n";

// Run database upgrade
echo '<h2>' . esc_html__('Running Database Upgrade...', 'mobility-trailblazers') . '</h2>\n';

try {
    // Force database upgrade
    \MobilityTrailblazers\Core\MT_Database_Upgrade::force_upgrade();
    echo "<p style='color: green;'>✓ Database upgrade completed successfully!</p>\n";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database upgrade failed: " . $e->getMessage() . "</p>\n";
}

// Check database tables
echo '<h2>' . esc_html__('Checking Database Tables...', 'mobility-trailblazers') . '</h2>\n';

global $wpdb;

// Check evaluations table
$evaluations_table = $wpdb->prefix . 'mt_evaluations';
$eval_exists = $wpdb->get_var("SHOW TABLES LIKE '$evaluations_table'") === $evaluations_table;

if ($eval_exists) {
    $eval_columns = $wpdb->get_col("SHOW COLUMNS FROM $evaluations_table");
    echo "<p>✓ Evaluations table exists with columns: " . implode(', ', $eval_columns) . "</p>\n";
} else {
    echo "<p style='color: red;'>✗ Evaluations table does not exist!</p>\n";
}

// Check assignments table
$assignments_table = $wpdb->prefix . 'mt_jury_assignments';
$assign_exists = $wpdb->get_var("SHOW TABLES LIKE '$assignments_table'") === $assignments_table;

if ($assign_exists) {
    $assign_columns = $wpdb->get_col("SHOW COLUMNS FROM $assignments_table");
    echo "<p>✓ Assignments table exists with columns: " . implode(', ', $assign_columns) . "</p>\n";
    
    // Check for required columns
    $required_columns = ['id', 'jury_member_id', 'candidate_id', 'assigned_at', 'assigned_by'];
    $missing_columns = array_diff($required_columns, $assign_columns);
    
    if (empty($missing_columns)) {
        echo "<p style='color: green;'>✓ All required columns exist in assignments table.</p>\n";
    } else {
        echo "<p style='color: orange;'>⚠ Missing columns: " . implode(', ', $missing_columns) . "</p>\n";
    }
} else {
    echo "<p style='color: red;'>✗ Assignments table does not exist!</p>\n";
}

// Test assignment repository
echo '<h2>' . esc_html__('Testing Assignment Repository...', 'mobility-trailblazers') . '</h2>\n';

try {
    $assignment_repo = new \MobilityTrailblazers\Repositories\MT_Assignment_Repository();
    $assignments = $assignment_repo->find_all(['limit' => 5]);
    echo "<p>✓ Assignment repository working. Found " . count($assignments) . " assignments.</p>\n";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Assignment repository error: " . $e->getMessage() . "</p>\n";
}

// Test assignment service
echo '<h2>' . esc_html__('Testing Assignment Service...', 'mobility-trailblazers') . '</h2>\n';

try {
    $assignment_service = new \MobilityTrailblazers\Services\MT_Assignment_Service();
    
    // Check if auto_assign method exists
    if (method_exists($assignment_service, 'auto_assign')) {
        echo "<p>✓ auto_assign method exists in assignment service.</p>\n";
    } else {
        echo "<p style='color: red;'>✗ auto_assign method missing from assignment service!</p>\n";
    }
    
    echo "<p>✓ Assignment service working.</p>\n";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Assignment service error: " . $e->getMessage() . "</p>\n";
}

echo '<h2>' . esc_html__('Database Fix Complete!', 'mobility-trailblazers') . '</h2>\n';
echo '<p>' . esc_html__('If you still see errors, try:', 'mobility-trailblazers') . '</p>\n';
echo "<ul>\n";
echo "<li>Clear any caching plugins</li>\n";
echo "<li>Deactivate and reactivate the plugin</li>\n";
echo "<li>Check the WordPress error logs</li>\n";
echo "</ul>\n";

echo "<p><a href='" . admin_url('admin.php?page=mobility-trailblazers') . "'>← Back to Mobility Trailblazers Admin</a></p>\n";
?> 