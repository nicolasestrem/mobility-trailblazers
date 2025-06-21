<?php
/**
 * Debug Jury Dashboard AJAX
 * 
 * This script tests the jury dashboard AJAX functionality
 * to identify why it's returning HTML instead of JSON.
 */

// Load WordPress
require_once('wp-load.php');

echo "<h1>Jury Dashboard AJAX Debug</h1>\n";

// Check if user is logged in
if (!is_user_logged_in()) {
    echo "<p>❌ User is not logged in. Please log in first.</p>\n";
    exit;
}

$current_user = wp_get_current_user();
echo "<p>✅ User logged in: {$current_user->user_login} (ID: {$current_user->ID})</p>\n";

echo "<h2>1. Check User Permissions</h2>\n";

// Check if user has jury member role
if (in_array('mt-jury-member', $current_user->roles)) {
    echo "✅ User has mt-jury-member role\n";
} else {
    echo "❌ User does not have mt-jury-member role. Current roles: " . implode(', ', $current_user->roles) . "\n";
}

// Check if user can submit evaluations
if (current_user_can('mt_submit_evaluations')) {
    echo "✅ User can submit evaluations\n";
} else {
    echo "❌ User cannot submit evaluations\n";
}

echo "<h2>2. Check Jury Member Profile</h2>\n";

// Check if jury member profile exists
$jury_member = mt_get_jury_member_by_user_id($current_user->ID);
if ($jury_member) {
    echo "✅ Jury member profile found (ID: {$jury_member->ID})\n";
} else {
    echo "❌ Jury member profile not found\n";
}

echo "<h2>3. Check AJAX Handler Registration</h2>\n";

// Check if AJAX action is registered
if (has_action('wp_ajax_mt_get_jury_dashboard_data')) {
    echo "✅ AJAX action 'mt_get_jury_dashboard_data' is registered\n";
} else {
    echo "❌ AJAX action 'mt_get_jury_dashboard_data' is NOT registered\n";
}

echo "<h2>4. Test AJAX Request</h2>\n";

// Create nonce
$nonce = wp_create_nonce('mt_jury_nonce');
echo "<p>Nonce created: {$nonce}</p>\n";

// Test AJAX request
$ajax_url = admin_url('admin-ajax.php');
echo "<p>AJAX URL: {$ajax_url}</p>\n";

// Make the request
$response = wp_remote_post($ajax_url, array(
    'body' => array(
        'action' => 'mt_get_jury_dashboard_data',
        'nonce' => $nonce
    ),
    'timeout' => 30,
    'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
));

if (is_wp_error($response)) {
    echo "<p>❌ AJAX request failed: " . $response->get_error_message() . "</p>\n";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $content_type = wp_remote_retrieve_header($response, 'content-type');
    
    echo "<p>Status Code: {$status_code}</p>\n";
    echo "<p>Content-Type: {$content_type}</p>\n";
    
    if ($status_code === 200) {
        // Check if response is JSON
        $json_data = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "<p>✅ Response is valid JSON</p>\n";
            echo "<pre>" . print_r($json_data, true) . "</pre>\n";
        } else {
            echo "<p>❌ Response is not valid JSON</p>\n";
            echo "<p>JSON Error: " . json_last_error_msg() . "</p>\n";
            echo "<p>Response preview (first 1000 chars):</p>\n";
            echo "<pre>" . htmlspecialchars(substr($body, 0, 1000)) . "...</pre>\n";
        }
    } else {
        echo "<p>❌ AJAX request returned status code: {$status_code}</p>\n";
        echo "<p>Response preview (first 1000 chars):</p>\n";
        echo "<pre>" . htmlspecialchars(substr($body, 0, 1000)) . "...</pre>\n";
    }
}

echo "<h2>5. Check AJAX Handler Class</h2>\n";

// Check if the class exists and is loaded
if (class_exists('MT_AJAX_Handlers')) {
    echo "✅ MT_AJAX_Handlers class exists\n";
    
    // Try to instantiate
    try {
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
} else {
    echo "❌ MT_AJAX_Handlers class does not exist\n";
}

echo "<h2>6. Check WordPress Hooks</h2>\n";

// Check all registered AJAX hooks
global $wp_filter;
$ajax_hooks = array();

foreach ($wp_filter as $hook_name => $hook_obj) {
    if (strpos($hook_name, 'wp_ajax_') === 0) {
        $ajax_hooks[] = $hook_name;
    }
}

echo "<p>Registered AJAX hooks:</p>\n";
if (empty($ajax_hooks)) {
    echo "<p>❌ No AJAX hooks found</p>\n";
} else {
    echo "<ul>\n";
    foreach ($ajax_hooks as $hook) {
        $highlight = (strpos($hook, 'mt_get_jury_dashboard_data') !== false) ? ' style="color: green; font-weight: bold;"' : '';
        echo "<li{$highlight}>{$hook}</li>\n";
    }
    echo "</ul>\n";
}

echo "<h2>7. Manual AJAX Handler Test</h2>\n";

// Try to manually call the AJAX handler method
if (class_exists('MT_AJAX_Handlers') && $jury_member) {
    try {
        // Set up the POST data
        $_POST['nonce'] = $nonce;
        $_POST['action'] = 'mt_get_jury_dashboard_data';
        
        // Capture output
        ob_start();
        
        $ajax_handler = new MT_AJAX_Handlers();
        $ajax_handler->get_jury_dashboard_data();
        
        $output = ob_get_clean();
        
        if (!empty($output)) {
            echo "<p>✅ Manual AJAX handler call produced output</p>\n";
            echo "<pre>" . htmlspecialchars($output) . "</pre>\n";
        } else {
            echo "<p>❌ Manual AJAX handler call produced no output</p>\n";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error in manual AJAX handler call: " . $e->getMessage() . "</p>\n";
    }
} else {
    echo "<p>❌ Cannot test manual AJAX handler - missing class or jury member profile</p>\n";
}

echo "<h2>8. Check for Output Buffering Issues</h2>\n";

// Check if there's any output before the AJAX response
if (ob_get_level() > 0) {
    echo "<p>⚠️ Output buffering is active (level: " . ob_get_level() . ")</p>\n";
} else {
    echo "<p>✅ No output buffering active</p>\n";
}

echo "<h2>9. Check for Fatal Errors</h2>\n";

// Check WordPress debug log
$debug_log = WP_CONTENT_DIR . '/debug.log';
if (file_exists($debug_log)) {
    $log_content = file_get_contents($debug_log);
    $recent_errors = array_slice(explode("\n", $log_content), -20);
    
    echo "<p>Recent debug log entries:</p>\n";
    echo "<pre>" . htmlspecialchars(implode("\n", $recent_errors)) . "</pre>\n";
} else {
    echo "<p>No debug log found</p>\n";
} 