<?php
/**
 * Test REST API
 * 
 * Test script to check if the REST API is working correctly
 */

echo "<h1>REST API Test</h1>\n";

// Check if user is logged in
if (!is_user_logged_in()) {
    echo "<p>❌ User is not logged in</p>\n";
    exit;
}

echo "<p>✅ User logged in: " . wp_get_current_user()->user_login . "</p>\n";

// Test 1: Check REST API base URL
echo "<h2>1. REST API Base URL</h2>\n";
$rest_url = rest_url();
echo "<p>REST URL: {$rest_url}</p>\n";

// Test 2: Check if our namespace is registered
echo "<h2>2. Check Registered Routes</h2>\n";
$routes = rest_get_server()->get_routes();
$our_routes = array();

foreach ($routes as $route => $handlers) {
    if (strpos($route, 'mobility-trailblazers') !== false) {
        $our_routes[] = $route;
    }
}

if (empty($our_routes)) {
    echo "<p>❌ No mobility-trailblazers routes found</p>\n";
} else {
    echo "<p>✅ Found " . count($our_routes) . " mobility-trailblazers routes:</p>\n";
    echo "<ul>\n";
    foreach ($our_routes as $route) {
        echo "<li>{$route}</li>\n";
    }
    echo "</ul>\n";
}

// Test 3: Test the jury dashboard endpoint
echo "<h2>3. Test Jury Dashboard Endpoint</h2>\n";
$nonce = wp_create_nonce('mt_jury_nonce');
$endpoint_url = $rest_url . 'mobility-trailblazers/v1/jury-dashboard';
echo "<p>Endpoint URL: {$endpoint_url}</p>\n";

$response = wp_remote_post($endpoint_url, array(
    'body' => array(
        'nonce' => $nonce
    ),
    'timeout' => 10
));

if (is_wp_error($response)) {
    echo "<p>❌ Request failed: " . $response->get_error_message() . "</p>\n";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $content_type = wp_remote_retrieve_header($response, 'content-type');
    
    echo "<p>Status Code: {$status_code}</p>\n";
    echo "<p>Content-Type: {$content_type}</p>\n";
    
    if ($status_code === 200) {
        $json_data = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "<p>✅ Response is valid JSON</p>\n";
            echo "<pre>" . print_r($json_data, true) . "</pre>\n";
        } else {
            echo "<p>❌ Response is not valid JSON</p>\n";
            echo "<p>Response: " . htmlspecialchars(substr($body, 0, 500)) . "</p>\n";
        }
    } else {
        echo "<p>❌ Request returned status code: {$status_code}</p>\n";
        echo "<p>Response: " . htmlspecialchars(substr($body, 0, 500)) . "</p>\n";
    }
}

// Test 4: Check if REST API is enabled
echo "<h2>4. REST API Status</h2>\n";
if (rest_enabled()) {
    echo "<p>✅ REST API is enabled</p>\n";
} else {
    echo "<p>❌ REST API is disabled</p>\n";
}

// Test 5: Check for any REST API filters
echo "<h2>5. REST API Filters</h2>\n";
global $wp_filter;
$rest_filters = array();

foreach ($wp_filter as $hook_name => $hook_obj) {
    if (strpos($hook_name, 'rest_') === 0) {
        $rest_filters[] = $hook_name;
    }
}

if (empty($rest_filters)) {
    echo "<p>No REST API filters found</p>\n";
} else {
    echo "<p>Found " . count($rest_filters) . " REST API filters:</p>\n";
    echo "<ul>\n";
    foreach (array_slice($rest_filters, 0, 10) as $filter) {
        echo "<li>{$filter}</li>\n";
    }
    if (count($rest_filters) > 10) {
        echo "<li>... and " . (count($rest_filters) - 10) . " more</li>\n";
    }
    echo "</ul>\n";
} 