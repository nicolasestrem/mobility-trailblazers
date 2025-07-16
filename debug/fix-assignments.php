<?php
/**
 * Assignment Fix Script
 * 
 * This script checks for missing assignments and can create them if needed.
 * Run this from the WordPress admin or via WP-CLI.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load WordPress
require_once('../../../wp-load.php');

// Check if user has admin permissions
if (!current_user_can('manage_options')) {
    wp_die('Insufficient permissions');
}

echo '<h1>' . esc_html__('Assignment Fix Script', 'mobility-trailblazers') . '</h1>';

// Get all jury members
$jury_members = get_posts([
    'post_type' => 'mt_jury_member',
    'numberposts' => -1,
    'post_status' => 'publish'
]);

// Get all candidates
$candidates = get_posts([
    'post_type' => 'mt_candidate',
    'numberposts' => -1,
    'post_status' => 'publish'
]);

echo '<h2>' . esc_html__('Current Status', 'mobility-trailblazers') . '</h2>';
echo "<p>Found " . count($jury_members) . " jury members and " . count($candidates) . " candidates.</p>";

// Check assignments
$assignment_repo = new \MobilityTrailblazers\Repositories\MT_Assignment_Repository();

echo '<h2>' . esc_html__('Assignment Analysis', 'mobility-trailblazers') . '</h2>';

$missing_assignments = [];
$total_assignments = 0;

foreach ($jury_members as $jury_member) {
    $jury_assignments = $assignment_repo->get_by_jury_member($jury_member->ID);
    $total_assignments += count($jury_assignments);
    
    echo "<h3>Jury Member: " . esc_html($jury_member->post_title) . " (ID: " . $jury_member->ID . ")</h3>";
    echo "<p>Current assignments: " . count($jury_assignments) . "</p>";
    
    if (count($jury_assignments) === 0) {
        echo "<p style='color: red;'>⚠️ No assignments found!</p>";
        $missing_assignments[] = $jury_member->ID;
    } else {
        echo "<ul>";
        foreach ($jury_assignments as $assignment) {
            $candidate = get_post($assignment->candidate_id);
            echo "<li>Assigned to: " . ($candidate ? esc_html($candidate->post_title) : 'Unknown Candidate') . " (ID: " . $assignment->candidate_id . ")</li>";
        }
        echo "</ul>";
    }
}

echo '<h2>' . esc_html__('Summary', 'mobility-trailblazers') . '</h2>';
echo "<p>Total assignments: " . $total_assignments . "</p>";
echo "<p>Jury members without assignments: " . count($missing_assignments) . "</p>";

// Check specific assignment mentioned in error log
echo '<h2>' . esc_html__('Specific Assignment Check', 'mobility-trailblazers') . '</h2>';
$specific_jury_id = 1039;
$specific_candidate_id = 997;

$has_assignment = $assignment_repo->exists($specific_jury_id, $specific_candidate_id);
echo "<p>Jury Member 1039 → Candidate 997: " . ($has_assignment ? "✅ Assigned" : "❌ Not Assigned") . "</p>";

if (!$has_assignment) {
    echo "<p style='color: red;'>This is the assignment mentioned in the error log!</p>";
    
    // Check if the jury member and candidate exist
    $jury_exists = get_post($specific_jury_id);
    $candidate_exists = get_post($specific_candidate_id);
    
    echo "<p>Jury Member 1039 exists: " . ($jury_exists ? "✅ Yes" : "❌ No") . "</p>";
    echo "<p>Candidate 997 exists: " . ($candidate_exists ? "✅ Yes" : "❌ No") . "</p>";
    
    if ($jury_exists && $candidate_exists) {
        echo '<h3>' . esc_html__('Fix Options', 'mobility-trailblazers') . '</h3>';
        echo "<form method='post'>";
        echo "<input type='hidden' name='action' value='create_assignment'>";
        echo "<input type='hidden' name='jury_id' value='$specific_jury_id'>";
        echo "<input type='hidden' name='candidate_id' value='$specific_candidate_id'>";
        echo "<button type='submit' style='background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer;'>" . esc_html__('Create Assignment', 'mobility-trailblazers') . "</button>";
        echo "</form>";
    }
}

// Handle form submission
if (isset($_POST['action']) && $_POST['action'] === 'create_assignment') {
    $jury_id = intval($_POST['jury_id']);
    $candidate_id = intval($_POST['candidate_id']);
    
    $assignment_service = new \MobilityTrailblazers\Services\MT_Assignment_Service();
    $result = $assignment_service->create_assignment($jury_id, $candidate_id);
    
    if ($result) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border: 1px solid #c3e6cb;'>";
        echo "✅ Assignment created successfully!";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border: 1px solid #f5c6cb;'>";
        echo "❌ Failed to create assignment.";
        echo "</div>";
    }
}

// Show all assignments in database
echo '<h2>' . esc_html__('All Assignments in Database', 'mobility-trailblazers') . '</h2>';
global $wpdb;
$assignments = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mt_jury_assignments ORDER BY jury_member_id, candidate_id");

if ($assignments) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Jury Member ID</th><th>Jury Member Name</th><th>Candidate ID</th><th>Candidate Name</th><th>Created</th></tr>";
    
    foreach ($assignments as $assignment) {
        $jury_name = get_the_title($assignment->jury_member_id);
        $candidate_name = get_the_title($assignment->candidate_id);
        
        echo "<tr>";
        echo "<td>" . $assignment->id . "</td>";
        echo "<td>" . $assignment->jury_member_id . "</td>";
        echo "<td>" . esc_html($jury_name) . "</td>";
        echo "<td>" . $assignment->candidate_id . "</td>";
        echo "<td>" . esc_html($candidate_name) . "</td>";
        echo "<td>" . $assignment->created_at . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No assignments found in database.</p>";
}

echo '<h2>' . esc_html__('Database Table Info', 'mobility-trailblazers') . '</h2>';
$table_name = $wpdb->prefix . 'mt_jury_assignments';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

if ($table_exists) {
    $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo "<p>Table exists: ✅ Yes</p>";
    echo "<p>Row count: $row_count</p>";
    
    // Show table structure
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    echo '<h3>' . esc_html__('Table Structure', 'mobility-trailblazers') . '</h3>';
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column->Field . "</td>";
        echo "<td>" . $column->Type . "</td>";
        echo "<td>" . $column->Null . "</td>";
        echo "<td>" . $column->Key . "</td>";
        echo "<td>" . $column->Default . "</td>";
        echo "<td>" . $column->Extra . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ Assignment table does not exist!</p>";
}

echo "<hr>";
echo "<p><em>Script completed at " . current_time('mysql') . "</em></p>";
?> 