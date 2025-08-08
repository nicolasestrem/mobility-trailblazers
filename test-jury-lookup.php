<?php
// Security check
if (!defined('ABSPATH')) {
    die('Direct access forbidden.');
}

/**
 * Jury Member Lookup Test
 * 
 * This script tests the jury member lookup and assignment verification process.
 */

// Load WordPress
require_once('wp-config.php');

echo '<h1>' . esc_html__('Jury Member Lookup Test', 'mobility-trailblazers') . '</h1>\n';

// Get current user
$current_user_id = get_current_user_id();
echo "<p><strong>Current User ID:</strong> {$current_user_id}</p>\n";

// Check user roles
$user = wp_get_current_user();
echo "<p><strong>User Roles:</strong> " . implode(', ', $user->roles) . "</p>\n";

// Check user capabilities
echo "<p><strong>User Capabilities:</strong></p>\n";
echo "<ul>\n";
if (current_user_can('mt_submit_evaluations')) {
    echo "<li style='color: green;'>✓ mt_submit_evaluations</li>\n";
} else {
    echo "<li style='color: red;'>✗ mt_submit_evaluations</li>\n";
}
if (current_user_can('mt_evaluate_candidates')) {
    echo "<li style='color: green;'>✓ mt_evaluate_candidates</li>\n";
} else {
    echo "<li style='color: red;'>✗ mt_evaluate_candidates</li>\n";
}
echo "</ul>\n";

// Test jury member lookup
echo '<h2>' . esc_html__('Jury Member Lookup Test', 'mobility-trailblazers') . '</h2>\n';

$args = [
    'post_type' => 'mt_jury_member',
    'meta_key' => '_mt_user_id',
    'meta_value' => $current_user_id,
    'posts_per_page' => 1,
    'post_status' => 'publish'
];

echo "<p><strong>Lookup Args:</strong> " . print_r($args, true) . "</p>\n";

$jury_members = get_posts($args);
echo "<p><strong>Found Jury Members:</strong> " . count($jury_members) . "</p>\n";

if (!empty($jury_members)) {
    $jury_member = $jury_members[0];
    echo "<p><strong>Jury Member ID:</strong> {$jury_member->ID}</p>\n";
    echo "<p><strong>Jury Member Title:</strong> {$jury_member->post_title}</p>\n";
    
    // Check all meta for this jury member
    $all_meta = get_post_meta($jury_member->ID);
    echo "<p><strong>All Meta for Jury Member:</strong></p>\n";
    echo "<ul>\n";
    foreach ($all_meta as $key => $values) {
        echo "<li>{$key}: " . implode(', ', $values) . "</li>\n";
    }
    echo "</ul>\n";
    
    // Test assignment lookup
    echo '<h2>' . esc_html__('Assignment Test', 'mobility-trailblazers') . '</h2>\n';
    
    global $wpdb;
    $assignments_table = $wpdb->prefix . 'mt_jury_assignments';
    
    // Get all assignments for this jury member
    $assignments = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$assignments_table} WHERE jury_member_id = %d",
        $jury_member->ID
    ));
    
    echo "<p><strong>Total Assignments for Jury Member:</strong> " . count($assignments) . "</p>\n";
    
    if (!empty($assignments)) {
        echo "<p><strong>First 5 Assignments:</strong></p>\n";
        echo "<ul>\n";
        $count = 0;
        foreach ($assignments as $assignment) {
            if ($count >= 5) break;
            echo "<li>Assignment ID: {$assignment->id}, Candidate ID: {$assignment->candidate_id}</li>\n";
            $count++;
        }
        echo "</ul>\n";
        
        // Test specific assignment check
        $test_candidate_id = $assignments[0]->candidate_id;
        echo "<p><strong>Testing Assignment Check for Candidate ID:</strong> {$test_candidate_id}</p>\n";
        
        $assignment_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$assignments_table} 
             WHERE jury_member_id = %d AND candidate_id = %d",
            $jury_member->ID,
            $test_candidate_id
        ));
        
        echo "<p><strong>Assignment Exists:</strong> " . ($assignment_exists > 0 ? 'Yes' : 'No') . " (Count: {$assignment_exists})</p>\n";
    }
    
} else {
    echo "<p style='color: red;'>✗ No jury member found for user ID {$current_user_id}</p>\n";
    
    // Let's check what jury members exist and their meta
    echo '<h3>' . esc_html__('All Jury Members:', 'mobility-trailblazers') . '</h3>\n';
    $all_jury_members = get_posts([
        'post_type' => 'mt_jury_member',
        'posts_per_page' => -1,
        'post_status' => 'any'
    ]);
    
    echo "<p><strong>Total Jury Members:</strong> " . count($all_jury_members) . "</p>\n";
    
    if (!empty($all_jury_members)) {
        echo "<p><strong>First 5 Jury Members and their user_id meta:</strong></p>\n";
        echo "<ul>\n";
        $count = 0;
        foreach ($all_jury_members as $jm) {
            if ($count >= 5) break;
            $user_id_meta = get_post_meta($jm->ID, '_mt_user_id', true);
            echo "<li>ID: {$jm->ID}, Title: {$jm->post_title}, User ID Meta: {$user_id_meta}</li>\n";
            $count++;
        }
        echo "</ul>\n";
    }
}

echo '<h2>' . esc_html__('Test Complete', 'mobility-trailblazers') . '</h2>\n';
?> 