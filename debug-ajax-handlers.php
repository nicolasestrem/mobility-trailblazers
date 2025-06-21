<?php
/**
 * Debug AJAX Handlers Registration
 * 
 * This script tests if the AJAX handlers are properly registered
 * and if the jury dashboard AJAX action is available.
 */

// Load WordPress
require_once('wp-load.php');

echo "<h1>AJAX Handlers Debug</h1>\n";

// Check if user is logged in
if (!is_user_logged_in()) {
    echo "<p>❌ User is not logged in. Please log in first.</p>\n";
    exit;
}

echo "<h2>1. AJAX Actions Registration</h2>\n";

// Check if the AJAX action is registered
$ajax_actions = array(
    'mt_get_jury_dashboard_data',
    'mt_submit_vote',
    'mt_get_candidate_details'
);

foreach ($ajax_actions as $action) {
    if (has_action("wp_ajax_{$action}") || has_action("wp_ajax_nopriv_{$action}")) {
        echo "✅ AJAX action '{$action}' is registered\n";
    } else {
        echo "❌ AJAX action '{$action}' is NOT registered\n";
    }
}

echo "<h2>2. Test AJAX Request</h2>\n";

// Create a test AJAX request
$nonce = wp_create_nonce('mt_jury_nonce');
$ajax_url = admin_url('admin-ajax.php');

echo "<p>Testing AJAX request to: {$ajax_url}</p>\n";
echo "<p>Action: mt_get_jury_dashboard_data</p>\n";
echo "<p>Nonce: {$nonce}</p>\n";

// Make a test request
$response = wp_remote_post($ajax_url, array(
    'body' => array(
        'action' => 'mt_get_jury_dashboard_data',
        'nonce' => $nonce,
        'user_id' => get_current_user_id()
    ),
    'timeout' => 30
));

if (is_wp_error($response)) {
    echo "<p>❌ AJAX request failed: " . $response->get_error_message() . "</p>\n";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    echo "<p>Status Code: {$status_code}</p>\n";
    echo "<p>Response Type: " . wp_remote_retrieve_header($response, 'content-type') . "</p>\n";
    
    if ($status_code === 200) {
        // Try to decode JSON
        $json_data = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "<p>✅ Response is valid JSON</p>\n";
            echo "<pre>" . print_r($json_data, true) . "</pre>\n";
        } else {
            echo "<p>❌ Response is not valid JSON</p>\n";
            echo "<p>JSON Error: " . json_last_error_msg() . "</p>\n";
            echo "<p>Response preview (first 500 chars):</p>\n";
            echo "<pre>" . htmlspecialchars(substr($body, 0, 500)) . "...</pre>\n";
        }
    } else {
        echo "<p>❌ AJAX request returned status code: {$status_code}</p>\n";
        echo "<p>Response preview (first 500 chars):</p>\n";
        echo "<pre>" . htmlspecialchars(substr($body, 0, 500)) . "...</pre>\n";
    }
}

echo "<h2>3. Check AJAX Handler Class</h2>\n";

// Check if the AJAX handler class exists
if (class_exists('MT_AJAX_Handlers')) {
    echo "✅ MT_AJAX_Handlers class exists\n";
    
    // Check if it's instantiated
    global $wp_filter;
    $ajax_hooks = array();
    
    foreach ($wp_filter as $hook_name => $hook_obj) {
        if (strpos($hook_name, 'wp_ajax_') === 0) {
            $ajax_hooks[] = $hook_name;
        }
    }
    
    echo "<p>Registered AJAX hooks:</p>\n";
    echo "<ul>\n";
    foreach ($ajax_hooks as $hook) {
        echo "<li>{$hook}</li>\n";
    }
    echo "</ul>\n";
    
} else {
    echo "❌ MT_AJAX_Handlers class does not exist\n";
}

echo "<h2>4. Manual AJAX Handler Test</h2>\n";

// Try to manually instantiate the AJAX handler
try {
    require_once(ABSPATH . 'wp-content/plugins/mobility-trailblazers-staging/includes/class-mt-ajax-handlers.php');
    $ajax_handler = new MT_AJAX_Handlers();
    echo "✅ AJAX handler instantiated successfully\n";
    
    // Check if the method exists
    if (method_exists($ajax_handler, 'get_jury_dashboard_data')) {
        echo "✅ get_jury_dashboard_data method exists\n";
    } else {
        echo "❌ get_jury_dashboard_data method does not exist\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error instantiating AJAX handler: " . $e->getMessage() . "\n";
} 