<?php
/**
 * Test AJAX Endpoint
 * 
 * This script tests the AJAX endpoint directly
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Check if user is logged in and has admin capabilities
if (!current_user_can('manage_options')) {
    wp_die('Access denied. Admin privileges required.');
}

echo "<h1>AJAX Endpoint Test</h1>";

// Test 1: Check if AJAX handlers are registered
echo "<h2>1. AJAX Handler Registration</h2>";
$ajax_actions = array(
    'mt_get_jury_dashboard_data',
    'mt_get_candidate_evaluation',
    'mt_save_evaluation'
);

foreach ($ajax_actions as $action) {
    if (has_action('wp_ajax_' . $action)) {
        echo "<p style='color: green;'>✅ <strong>$action</strong> is registered</p>";
    } else {
        echo "<p style='color: red;'>❌ <strong>$action</strong> is NOT registered</p>";
    }
}

// Test 2: Check if user has jury permissions
echo "<h2>2. User Permissions</h2>";
$current_user = wp_get_current_user();
echo "<p><strong>Current User:</strong> " . $current_user->user_login . " (ID: " . $current_user->ID . ")</p>";
echo "<p><strong>User Roles:</strong> " . implode(', ', $current_user->roles) . "</p>";
echo "<p><strong>Can submit evaluations:</strong> " . (current_user_can('mt_submit_evaluations') ? 'Yes' : 'No') . "</p>";

// Test 3: Check jury member profile
echo "<h2>3. Jury Member Profile</h2>";
$jury_member = mt_get_jury_member_by_user_id($current_user->ID);
if ($jury_member) {
    echo "<p style='color: green;'>✅ Jury member profile found: " . $jury_member->post_title . " (ID: " . $jury_member->ID . ")</p>";
} else {
    echo "<p style='color: red;'>❌ No jury member profile found for current user</p>";
}

// Test 4: Test AJAX endpoint directly
echo "<h2>4. Direct AJAX Test</h2>";
if ($jury_member) {
    // Create a test request
    $_POST['action'] = 'mt_get_jury_dashboard_data';
    $_POST['nonce'] = wp_create_nonce('mt_jury_nonce');
    
    // Capture output
    ob_start();
    
    // Call the AJAX handler directly
    if (class_exists('MT_AJAX_Handlers')) {
        $ajax_handler = new MT_AJAX_Handlers();
        $ajax_handler->get_jury_dashboard_data();
    } else {
        echo "MT_AJAX_Handlers class not found";
    }
    
    $output = ob_get_clean();
    
    echo "<p><strong>AJAX Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
} else {
    echo "<p style='color: orange;'>⚠️ Skipping AJAX test - no jury member profile</p>";
}

// Test 5: Check assigned candidates
echo "<h2>5. Assigned Candidates</h2>";
if ($jury_member) {
    $assigned_candidates = mt_get_assigned_candidates($jury_member->ID);
    echo "<p><strong>Total assigned candidates:</strong> " . count($assigned_candidates) . "</p>";
    
    if (empty($assigned_candidates)) {
        echo "<p style='color: orange;'>⚠️ No candidates assigned to this jury member</p>";
        
        // Check if there are any candidates in the system
        $total_candidates = wp_count_posts('mt_candidate')->publish;
        echo "<p><strong>Total candidates in system:</strong> $total_candidates</p>";
        
        // Check if there are any jury members in the system
        $total_jury = wp_count_posts('mt_jury_member')->publish;
        echo "<p><strong>Total jury members in system:</strong> $total_jury</p>";
    } else {
        echo "<p style='color: green;'>✅ Candidates found:</p>";
        echo "<ul>";
        foreach (array_slice($assigned_candidates, 0, 5) as $candidate_id) {
            $candidate = get_post($candidate_id);
            if ($candidate) {
                echo "<li>" . $candidate->post_title . " (ID: $candidate_id)</li>";
            }
        }
        if (count($assigned_candidates) > 5) {
            echo "<li>... and " . (count($assigned_candidates) - 5) . " more</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p style='color: red;'>❌ Cannot check assigned candidates - no jury member profile</p>";
}

// Test 6: Check nonce generation
echo "<h2>6. Nonce Generation</h2>";
$nonce = wp_create_nonce('mt_jury_nonce');
echo "<p><strong>Generated nonce:</strong> $nonce</p>";
echo "<p><strong>Nonce verification:</strong> " . (wp_verify_nonce($nonce, 'mt_jury_nonce') ? 'Valid' : 'Invalid') . "</p>";

echo "<hr>";
echo "<p><strong>Test completed.</strong></p>";
?> 