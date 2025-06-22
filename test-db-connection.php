<?php
/**
 * Database Connection Test
 * 
 * This script tests the database connection and table structure
 * for the Mobility Trailblazers plugin.
 */

// Load WordPress
require_once('wp-config.php');

// Test database connection
global $wpdb;

echo "<h1>Mobility Trailblazers Database Test</h1>\n";

// Check if we can connect to the database
if ($wpdb->check_connection()) {
    echo "<p style='color: green;'>✓ Database connection successful</p>\n";
} else {
    echo "<p style='color: red;'>✗ Database connection failed</p>\n";
    exit;
}

// Check evaluations table
$evaluations_table = $wpdb->prefix . 'mt_evaluations';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$evaluations_table}'") === $evaluations_table;

if ($table_exists) {
    echo "<p style='color: green;'>✓ Evaluations table exists</p>\n";
    
    // Check table structure
    $columns = $wpdb->get_results("SHOW COLUMNS FROM {$evaluations_table}");
    echo "<h3>Evaluations Table Structure:</h3>\n";
    echo "<ul>\n";
    foreach ($columns as $column) {
        echo "<li>{$column->Field} - {$column->Type}</li>\n";
    }
    echo "</ul>\n";
    
    // Check row count
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$evaluations_table}");
    echo "<p>Total evaluations: {$count}</p>\n";
} else {
    echo "<p style='color: red;'>✗ Evaluations table does not exist</p>\n";
}

// Check assignments table
$assignments_table = $wpdb->prefix . 'mt_jury_assignments';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$assignments_table}'") === $assignments_table;

if ($table_exists) {
    echo "<p style='color: green;'>✓ Assignments table exists</p>\n";
    
    // Check table structure
    $columns = $wpdb->get_results("SHOW COLUMNS FROM {$assignments_table}");
    echo "<h3>Assignments Table Structure:</h3>\n";
    echo "<ul>\n";
    foreach ($columns as $column) {
        echo "<li>{$column->Field} - {$column->Type}</li>\n";
    }
    echo "</ul>\n";
    
    // Check row count
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$assignments_table}");
    echo "<p>Total assignments: {$count}</p>\n";
} else {
    echo "<p style='color: red;'>✗ Assignments table does not exist</p>\n";
}

// Check post types
$candidates = get_posts([
    'post_type' => 'mt_candidate',
    'posts_per_page' => -1,
    'post_status' => 'any'
]);

echo "<h3>Candidates:</h3>\n";
echo "<p>Total candidates: " . count($candidates) . "</p>\n";

$jury_members = get_posts([
    'post_type' => 'mt_jury_member',
    'posts_per_page' => -1,
    'post_status' => 'any'
]);

echo "<h3>Jury Members:</h3>\n";
echo "<p>Total jury members: " . count($jury_members) . "</p>\n";

// Test assignment creation
echo "<h3>Testing Assignment Creation:</h3>\n";
if (!empty($candidates) && !empty($jury_members)) {
    $candidate = $candidates[0];
    $jury_member = $jury_members[0];
    
    $test_data = [
        'jury_member_id' => $jury_member->ID,
        'candidate_id' => $candidate->ID,
        'assigned_at' => current_time('mysql'),
        'assigned_by' => 1
    ];
    
    $result = $wpdb->insert($assignments_table, $test_data, ['%d', '%d', '%s', '%d']);
    
    if ($result) {
        echo "<p style='color: green;'>✓ Test assignment created successfully</p>\n";
        // Clean up
        $wpdb->delete($assignments_table, ['jury_member_id' => $jury_member->ID, 'candidate_id' => $candidate->ID], ['%d', '%d']);
        echo "<p>Test assignment cleaned up</p>\n";
    } else {
        echo "<p style='color: red;'>✗ Test assignment creation failed</p>\n";
        echo "<p>Error: " . $wpdb->last_error . "</p>\n";
    }
} else {
    echo "<p style='color: orange;'>⚠ Cannot test assignment creation - no candidates or jury members found</p>\n";
}

// Test evaluation creation
echo "<h3>Testing Evaluation Creation:</h3>\n";
if (!empty($candidates) && !empty($jury_members)) {
    $candidate = $candidates[0];
    $jury_member = $jury_members[0];
    
    $test_data = [
        'jury_member_id' => $jury_member->ID,
        'candidate_id' => $candidate->ID,
        'courage_score' => 5,
        'innovation_score' => 6,
        'implementation_score' => 7,
        'relevance_score' => 8,
        'visibility_score' => 9,
        'total_score' => 7.0,
        'comments' => 'Test evaluation',
        'status' => 'draft',
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql')
    ];
    
    // Generate format specifiers dynamically
    $formats = [];
    foreach ($test_data as $key => $value) {
        switch ($key) {
            case 'jury_member_id':
            case 'candidate_id':
            case 'courage_score':
            case 'innovation_score':
            case 'implementation_score':
            case 'relevance_score':
            case 'visibility_score':
                $formats[] = '%d';
                break;
            case 'total_score':
                $formats[] = '%f';
                break;
            default:
                $formats[] = '%s';
        }
    }
    
    $result = $wpdb->insert($evaluations_table, $test_data, $formats);
    
    if ($result) {
        echo "<p style='color: green;'>✓ Test evaluation created successfully</p>\n";
        // Clean up
        $wpdb->delete($evaluations_table, ['jury_member_id' => $jury_member->ID, 'candidate_id' => $candidate->ID], ['%d', '%d']);
        echo "<p>Test evaluation cleaned up</p>\n";
    } else {
        echo "<p style='color: red;'>✗ Test evaluation creation failed</p>\n";
        echo "<p>Error: " . $wpdb->last_error . "</p>\n";
    }
} else {
    echo "<p style='color: orange;'>⚠ Cannot test evaluation creation - no candidates or jury members found</p>\n";
}

echo "<h3>Database Test Complete</h3>\n";
?> 