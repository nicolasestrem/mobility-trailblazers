<?php
/**
 * Debug REST URL Generation
 * 
 * This script tests REST URL generation and REST API registration
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Check if user is logged in and has admin capabilities
if (!current_user_can('manage_options')) {
    wp_die('Access denied. Admin privileges required.');
}

echo "<h1>REST URL Debug Information</h1>";

// Test 1: Check if REST API is enabled
echo "<h2>1. REST API Status</h2>";
$rest_enabled = get_option('permalink_structure') !== '';
echo "<p><strong>REST API Enabled:</strong> " . ($rest_enabled ? 'Yes' : 'No') . "</p>";
echo "<p><strong>Permalink Structure:</strong> " . get_option('permalink_structure') . "</p>";

// Test 2: Test rest_url() function
echo "<h2>2. REST URL Function Test</h2>";
$rest_url = rest_url();
echo "<p><strong>rest_url():</strong> " . $rest_url . "</p>";

// Test 3: Test with different paths
echo "<h2>3. REST URL with Different Paths</h2>";
echo "<p><strong>rest_url('mobility-trailblazers/v1/jury-dashboard'):</strong> " . rest_url('mobility-trailblazers/v1/jury-dashboard') . "</p>";
echo "<p><strong>rest_url('wp/v2/'):</strong> " . rest_url('wp/v2/') . "</p>";

// Test 4: Check if our REST API is registered
echo "<h2>4. REST API Registration Check</h2>";
$rest_server = rest_get_server();
$routes = $rest_server->get_routes();

$our_routes = array();
foreach ($routes as $route => $handlers) {
    if (strpos($route, 'mobility-trailblazers') !== false) {
        $our_routes[$route] = $handlers;
    }
}

if (empty($our_routes)) {
    echo "<p style='color: red;'><strong>No Mobility Trailblazers REST routes found!</strong></p>";
} else {
    echo "<p><strong>Found " . count($our_routes) . " Mobility Trailblazers routes:</strong></p>";
    foreach ($our_routes as $route => $handlers) {
        echo "<p>- $route</p>";
    }
}

// Test 5: Check if REST API is accessible
echo "<h2>5. REST API Accessibility Test</h2>";
$test_url = rest_url('wp/v2/posts');
echo "<p><strong>Testing URL:</strong> $test_url</p>";

$response = wp_remote_get($test_url);
if (is_wp_error($response)) {
    echo "<p style='color: red;'><strong>Error accessing REST API:</strong> " . $response->get_error_message() . "</p>";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    echo "<p><strong>Response Code:</strong> $status_code</p>";
    if ($status_code === 200) {
        echo "<p style='color: green;'><strong>REST API is accessible!</strong></p>";
    } else {
        echo "<p style='color: orange;'><strong>REST API returned status code: $status_code</strong></p>";
    }
}

// Test 6: Check our specific endpoint
echo "<h2>6. Our Endpoint Test</h2>";
$our_url = rest_url('mobility-trailblazers/v1/jury-dashboard');
echo "<p><strong>Our endpoint URL:</strong> $our_url</p>";

$our_response = wp_remote_post($our_url, array(
    'headers' => array(
        'Content-Type' => 'application/json',
    ),
    'body' => json_encode(array(
        'nonce' => wp_create_nonce('mt_jury_nonce')
    ))
));

if (is_wp_error($our_response)) {
    echo "<p style='color: red;'><strong>Error accessing our endpoint:</strong> " . $our_response->get_error_message() . "</p>";
} else {
    $status_code = wp_remote_retrieve_response_code($our_response);
    $body = wp_remote_retrieve_body($our_response);
    echo "<p><strong>Response Code:</strong> $status_code</p>";
    echo "<p><strong>Response Body:</strong></p>";
    echo "<pre>" . htmlspecialchars($body) . "</pre>";
}

// Test 7: Check JavaScript localization
echo "<h2>7. JavaScript Localization Test</h2>";
$ajax_data = array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'rest_url' => rest_url(),
    'nonce' => wp_create_nonce('mt_jury_nonce'),
);

echo "<p><strong>Localized Data:</strong></p>";
echo "<pre>" . htmlspecialchars(print_r($ajax_data, true)) . "</pre>";

// Test 8: Check if REST API is properly initialized
echo "<h2>8. REST API Initialization Check</h2>";
if (did_action('rest_api_init')) {
    echo "<p style='color: green;'><strong>REST API has been initialized</strong></p>";
} else {
    echo "<p style='color: red;'><strong>REST API has NOT been initialized!</strong></p>";
}

// Test 9: Check our plugin's REST API registration
echo "<h2>9. Plugin REST API Registration</h2>";
if (class_exists('MT_REST_API')) {
    echo "<p style='color: green;'><strong>MT_REST_API class exists</strong></p>";
    
    // Check if our endpoint is registered
    $rest_server = rest_get_server();
    $routes = $rest_server->get_routes();
    
    $endpoint_found = false;
    foreach ($routes as $route => $handlers) {
        if ($route === '/mobility-trailblazers/v1/jury-dashboard') {
            $endpoint_found = true;
            break;
        }
    }
    
    if ($endpoint_found) {
        echo "<p style='color: green;'><strong>Our jury dashboard endpoint is registered</strong></p>";
    } else {
        echo "<p style='color: red;'><strong>Our jury dashboard endpoint is NOT registered!</strong></p>";
    }
} else {
    echo "<p style='color: red;'><strong>MT_REST_API class does not exist!</strong></p>";
}

echo "<hr>";
echo "<p><strong>Debug completed.</strong></p>";
?> 