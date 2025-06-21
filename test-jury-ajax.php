<?php
/**
 * Test jury dashboard AJAX functionality
 */

// Load WordPress
require_once 'wp-config.php';
require_once 'wp-load.php';

echo "=== Testing Jury Dashboard AJAX ===\n\n";

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
    echo "User is not a jury member. Cannot test AJAX.\n";
    exit;
}

// Test the AJAX handler directly
echo "\n=== Testing AJAX Handler ===\n";

// Simulate AJAX request
$_POST['action'] = 'mt_get_jury_dashboard_data';
$_POST['nonce'] = wp_create_nonce('mt_jury_nonce');

// Check if the AJAX handler exists
if (has_action('wp_ajax_mt_get_jury_dashboard_data')) {
    echo "AJAX handler is registered.\n";
    
    // Test the handler
    try {
        // Get the handler function
        global $wp_filter;
        $handlers = $wp_filter['wp_ajax_mt_get_jury_dashboard_data'] ?? null;
        
        if ($handlers) {
            echo "Found " . count($handlers) . " handler(s).\n";
            
            // Test the evaluation AJAX handler
            $evaluation_ajax = new \MobilityTrailblazers\Ajax\MT_Evaluation_Ajax();
            
            // Test the method directly
            $result = $evaluation_ajax->get_jury_dashboard_data();
            echo "AJAX handler executed successfully.\n";
            
        } else {
            echo "No handlers found for mt_get_jury_dashboard_data.\n";
        }
        
    } catch (Exception $e) {
        echo "Error testing AJAX handler: " . $e->getMessage() . "\n";
    }
} else {
    echo "AJAX handler is NOT registered.\n";
}

// Test the utility function
echo "\n=== Testing Utility Function ===\n";
$assigned_candidates = mt_get_assigned_candidates($jury_member->ID);
echo "Assigned candidates: " . count($assigned_candidates) . "\n";

if (!empty($assigned_candidates)) {
    echo "Candidate IDs: " . implode(', ', $assigned_candidates) . "\n";
    
    // Test getting candidate data
    foreach ($assigned_candidates as $candidate_id) {
        $candidate = get_post($candidate_id);
        if ($candidate) {
            echo "  - Candidate $candidate_id: " . $candidate->post_title . "\n";
        }
    }
}

// Test AJAX URL and nonce
echo "\n=== Testing AJAX Configuration ===\n";
$ajax_url = admin_url('admin-ajax.php');
echo "AJAX URL: $ajax_url\n";

$nonce = wp_create_nonce('mt_jury_nonce');
echo "Nonce: $nonce\n";

// Test if nonce is valid
if (wp_verify_nonce($nonce, 'mt_jury_nonce')) {
    echo "Nonce is valid.\n";
} else {
    echo "Nonce is invalid.\n";
}

echo "\n=== Test Complete ===\n"; 