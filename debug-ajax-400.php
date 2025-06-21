<?php
/**
 * Debug AJAX 400 Error
 * 
 * Detailed test to identify what's causing the 400 Bad Request error
 */

echo "<h1>AJAX 400 Error Debug</h1>\n";

// Check if user is logged in
if (!is_user_logged_in()) {
    echo "<p>❌ User is not logged in</p>\n";
    exit;
}

echo "<p>✅ User logged in: " . wp_get_current_user()->user_login . "</p>\n";

// Test 1: Check nonce creation
echo "<h2>1. Nonce Test</h2>\n";
$nonce = wp_create_nonce('mt_jury_nonce');
echo "<p>Nonce created: {$nonce}</p>\n";
echo "<p>Nonce verification: " . (wp_verify_nonce($nonce, 'mt_jury_nonce') ? '✅ Valid' : '❌ Invalid') . "</p>\n";

// Test 2: Test with different request formats
echo "<h2>2. Request Format Tests</h2>\n";

$ajax_url = admin_url('admin-ajax.php');
echo "<p>AJAX URL: {$ajax_url}</p>\n";

// Test 2a: Basic request with action only
echo "<h3>Test 2a: Action only</h3>\n";
$response = wp_remote_post($ajax_url, array(
    'body' => array(
        'action' => 'mt_get_jury_dashboard_data'
    ),
    'timeout' => 10
));

if (is_wp_error($response)) {
    echo "<p>❌ Request failed: " . $response->get_error_message() . "</p>\n";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    echo "<p>Status Code: {$status_code}</p>\n";
    echo "<p>Response: " . htmlspecialchars(substr($body, 0, 200)) . "</p>\n";
}

// Test 2b: Request with action and nonce
echo "<h3>Test 2b: Action + nonce</h3>\n";
$response = wp_remote_post($ajax_url, array(
    'body' => array(
        'action' => 'mt_get_jury_dashboard_data',
        'nonce' => $nonce
    ),
    'timeout' => 10
));

if (is_wp_error($response)) {
    echo "<p>❌ Request failed: " . $response->get_error_message() . "</p>\n";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    echo "<p>Status Code: {$status_code}</p>\n";
    echo "<p>Response: " . htmlspecialchars(substr($body, 0, 200)) . "</p>\n";
}

// Test 2c: Request with different nonce action
echo "<h3>Test 2c: Different nonce action</h3>\n";
$alt_nonce = wp_create_nonce('mt_ajax_nonce');
$response = wp_remote_post($ajax_url, array(
    'body' => array(
        'action' => 'mt_get_jury_dashboard_data',
        'nonce' => $alt_nonce
    ),
    'timeout' => 10
));

if (is_wp_error($response)) {
    echo "<p>❌ Request failed: " . $response->get_error_message() . "</p>\n";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    echo "<p>Status Code: {$status_code}</p>\n";
    echo "<p>Response: " . htmlspecialchars(substr($body, 0, 200)) . "</p>\n";
}

// Test 3: Check AJAX handler method directly
echo "<h2>3. Direct Method Test</h2>\n";

if (class_exists('MT_AJAX_Handlers')) {
    try {
        // Set up POST data
        $_POST['action'] = 'mt_get_jury_dashboard_data';
        $_POST['nonce'] = $nonce;
        
        // Capture output
        ob_start();
        
        $ajax_handler = new MT_AJAX_Handlers();
        $ajax_handler->get_jury_dashboard_data();
        
        $output = ob_get_clean();
        
        if (!empty($output)) {
            echo "<p>✅ Direct method call produced output</p>\n";
            echo "<pre>" . htmlspecialchars($output) . "</pre>\n";
        } else {
            echo "<p>❌ Direct method call produced no output</p>\n";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error in direct method call: " . $e->getMessage() . "</p>\n";
    }
} else {
    echo "<p>❌ MT_AJAX_Handlers class not found</p>\n";
}

// Test 4: Check WordPress AJAX handling
echo "<h2>4. WordPress AJAX Debug</h2>\n";

// Check if DOING_AJAX is set
echo "<p>DOING_AJAX constant: " . (defined('DOING_AJAX') ? (DOING_AJAX ? 'true' : 'false') : 'not defined') . "</p>\n";

// Check if admin-ajax.php is being called
echo "<p>Current script: " . $_SERVER['SCRIPT_NAME'] . "</p>\n";

// Test 5: Check for any output before AJAX response
echo "<h2>5. Output Buffer Check</h2>\n";

if (ob_get_level() > 0) {
    echo "<p>⚠️ Output buffering is active (level: " . ob_get_level() . ")</p>\n";
    $buffer_content = ob_get_contents();
    if (!empty($buffer_content)) {
        echo "<p>⚠️ Buffer contains: " . htmlspecialchars(substr($buffer_content, 0, 200)) . "</p>\n";
    }
} else {
    echo "<p>✅ No output buffering active</p>\n";
}

// Test 6: Check for any fatal errors or warnings
echo "<h2>6. Error Check</h2>\n";

// Check if there are any PHP errors
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    $log_content = file_get_contents($error_log);
    $recent_errors = array_slice(explode("\n", $log_content), -10);
    
    echo "<p>Recent error log entries:</p>\n";
    echo "<pre>" . htmlspecialchars(implode("\n", $recent_errors)) . "</pre>\n";
} else {
    echo "<p>No error log found or accessible</p>\n";
} 