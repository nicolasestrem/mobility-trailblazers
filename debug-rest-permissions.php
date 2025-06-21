<?php
/**
 * Debug REST API Permissions
 * 
 * This script tests REST API permissions directly
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Check if user is logged in and has admin capabilities
if (!current_user_can('manage_options')) {
    wp_die('Access denied. Admin privileges required.');
}

echo "<h1>REST API Permissions Debug</h1>";

// Test 1: Check current user
echo "<h2>1. Current User Information</h2>";
$current_user = wp_get_current_user();
echo "<p><strong>User ID:</strong> " . $current_user->ID . "</p>";
echo "<p><strong>Username:</strong> " . $current_user->user_login . "</p>";
echo "<p><strong>Email:</strong> " . $current_user->user_email . "</p>";
echo "<p><strong>Roles:</strong> " . implode(', ', $current_user->roles) . "</p>";

// Test 2: Check capabilities
echo "<h2>2. User Capabilities</h2>";
echo "<p><strong>Can submit evaluations:</strong> " . (current_user_can('mt_submit_evaluations') ? 'Yes' : 'No') . "</p>";
echo "<p><strong>Is administrator:</strong> " . (current_user_can('manage_options') ? 'Yes' : 'No') . "</p>";
echo "<p><strong>Is logged in:</strong> " . (is_user_logged_in() ? 'Yes' : 'No') . "</p>";

// Test 3: Check jury member profile
echo "<h2>3. Jury Member Profile</h2>";
$jury_member = mt_get_jury_member_by_user_id($current_user->ID);
if ($jury_member) {
    echo "<p style='color: green;'>✅ Jury member profile found: " . $jury_member->post_title . " (ID: " . $jury_member->ID . ")</p>";
} else {
    echo "<p style='color: red;'>❌ No jury member profile found for current user</p>";
}

// Test 4: Test permission callback directly
echo "<h2>4. Permission Callback Test</h2>";
if (class_exists('MT_REST_API')) {
    $rest_api = new MT_REST_API();
    
    // Create a mock request object
    $mock_request = new stdClass();
    $mock_request->get_param = function($param) {
        if ($param === 'nonce') {
            return wp_create_nonce('mt_jury_nonce');
        }
        return null;
    };
    
    // Test the permission callback
    $result = $rest_api->check_jury_permissions($mock_request);
    echo "<p><strong>Permission callback result:</strong> " . ($result ? 'Allowed' : 'Denied') . "</p>";
} else {
    echo "<p style='color: red;'>❌ MT_REST_API class not found</p>";
}

// Test 5: Test nonce generation and verification
echo "<h2>5. Nonce Test</h2>";
$nonce = wp_create_nonce('mt_jury_nonce');
echo "<p><strong>Generated nonce:</strong> $nonce</p>";
echo "<p><strong>Nonce verification:</strong> " . (wp_verify_nonce($nonce, 'mt_jury_nonce') ? 'Valid' : 'Invalid') . "</p>";

// Test 6: Test REST API endpoint directly
echo "<h2>6. Direct REST API Test</h2>";
$rest_url = rest_url('mobility-trailblazers/v1/jury-dashboard');
echo "<p><strong>REST URL:</strong> $rest_url</p>";

// Make a direct request
$response = wp_remote_post($rest_url, array(
    'headers' => array(
        'Content-Type' => 'application/json',
        'X-WP-Nonce' => wp_create_nonce('wp_rest')
    ),
    'body' => json_encode(array(
        'nonce' => wp_create_nonce('mt_jury_nonce')
    )),
    'timeout' => 10
));

if (is_wp_error($response)) {
    echo "<p style='color: red;'>❌ Request failed: " . $response->get_error_message() . "</p>";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $headers = wp_remote_retrieve_headers($response);
    
    echo "<p><strong>Status Code:</strong> $status_code</p>";
    echo "<p><strong>Response Headers:</strong></p>";
    echo "<pre>" . print_r($headers, true) . "</pre>";
    echo "<p><strong>Response Body:</strong></p>";
    echo "<pre>" . htmlspecialchars($body) . "</pre>";
}

// Test 7: Check if REST API is properly registered
echo "<h2>7. REST API Registration Check</h2>";
$rest_server = rest_get_server();
$routes = $rest_server->get_routes();

$our_routes = array();
foreach ($routes as $route => $handlers) {
    if (strpos($route, 'mobility-trailblazers') !== false) {
        $our_routes[$route] = $handlers;
    }
}

if (empty($our_routes)) {
    echo "<p style='color: red;'>❌ No Mobility Trailblazers REST routes found!</p>";
} else {
    echo "<p style='color: green;'>✅ Found " . count($our_routes) . " Mobility Trailblazers routes:</p>";
    foreach ($our_routes as $route => $handlers) {
        echo "<p>- $route</p>";
        foreach ($handlers as $method => $handler) {
            echo "<p style='margin-left: 20px;'>  $method: " . (isset($handler['permission_callback']) ? 'Has permission callback' : 'No permission callback') . "</p>";
        }
    }
}

echo "<hr>";
echo "<p><strong>Debug completed.</strong></p>";
?> 