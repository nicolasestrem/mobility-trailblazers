<?php
/**
 * Test Jury Dashboard AJAX Handler
 * 
 * This script tests the jury dashboard AJAX functionality directly
 */

// Load WordPress
require_once('wp-load.php');

echo "<h1>Jury Dashboard AJAX Test</h1>\n";

// Check if user is logged in
if (!is_user_logged_in()) {
    echo "<p>❌ User is not logged in. Please log in first.</p>\n";
    exit;
}

$current_user = wp_get_current_user();
echo "<p>✅ User logged in: {$current_user->user_login} (ID: {$current_user->ID})</p>\n";

// Check if user is a jury member
$jury_member = mt_get_jury_member_by_user_id($current_user->ID);
if (!$jury_member) {
    echo "<p>❌ No jury member profile found for current user</p>\n";
    exit;
}

echo "<p>✅ Jury member profile found: {$jury_member->post_title} (ID: {$jury_member->ID})</p>\n";

// Check assigned candidates
$assigned_candidates = mt_get_assigned_candidates($jury_member->ID);
echo "<p>✅ Assigned candidates: " . count($assigned_candidates) . "</p>\n";

if (!empty($assigned_candidates)) {
    echo "<p>Candidate IDs: " . implode(', ', array_slice($assigned_candidates, 0, 5)) . "...</p>\n";
}

// Test AJAX handler directly
echo "<h2>Testing AJAX Handler Directly</h2>\n";

// Set up POST data
$_POST['action'] = 'mt_get_jury_dashboard_data';
$_POST['nonce'] = wp_create_nonce('mt_jury_nonce');

// Capture output
ob_start();

// Call the AJAX handler directly
if (class_exists('MobilityTrailblazers\Ajax\MT_Evaluation_Ajax')) {
    try {
        $evaluation_ajax = new \MobilityTrailblazers\Ajax\MT_Evaluation_Ajax();
        $evaluation_ajax->get_jury_dashboard_data();
        
        $output = ob_get_clean();
        
        echo "<p><strong>AJAX Handler Output:</strong></p>\n";
        echo "<pre>" . htmlspecialchars($output) . "</pre>\n";
        
        // Try to decode JSON
        $json_data = json_decode($output, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "<p style='color: green;'>✅ Response is valid JSON</p>\n";
            
            if (isset($json_data['success']) && $json_data['success']) {
                echo "<p style='color: green;'>✅ AJAX handler returned success</p>\n";
                
                if (isset($json_data['data']['stats'])) {
                    $stats = $json_data['data']['stats'];
                    echo "<p><strong>Stats:</strong></p>\n";
                    echo "<ul>\n";
                    echo "<li>Total assigned: {$stats['total_assigned']}</li>\n";
                    echo "<li>Completed: {$stats['completed']}</li>\n";
                    echo "<li>Drafts: {$stats['drafts']}</li>\n";
                    echo "<li>Completion rate: {$stats['completion_rate']}%</li>\n";
                    echo "</ul>\n";
                }
                
                if (isset($json_data['data']['candidates'])) {
                    $candidates = $json_data['data']['candidates'];
                    echo "<p><strong>Candidates returned: " . count($candidates) . "</strong></p>\n";
                    
                    if (!empty($candidates)) {
                        echo "<p>First candidate: {$candidates[0]['title']}</p>\n";
                    }
                }
            } else {
                echo "<p style='color: red;'>❌ AJAX handler returned error</p>\n";
                if (isset($json_data['data']['message'])) {
                    echo "<p>Error message: {$json_data['data']['message']}</p>\n";
                }
            }
        } else {
            echo "<p style='color: red;'>❌ Response is not valid JSON</p>\n";
            echo "<p>JSON Error: " . json_last_error_msg() . "</p>\n";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error calling AJAX handler: " . $e->getMessage() . "</p>\n";
    }
} else {
    echo "<p style='color: red;'>❌ MT_Evaluation_Ajax class not found</p>\n";
}

// Test AJAX action registration
echo "<h2>Testing AJAX Action Registration</h2>\n";

if (has_action('wp_ajax_mt_get_jury_dashboard_data')) {
    echo "<p style='color: green;'>✅ AJAX action 'mt_get_jury_dashboard_data' is registered</p>\n";
    
    // Check handler details
    global $wp_filter;
    $handlers = $wp_filter['wp_ajax_mt_get_jury_dashboard_data'] ?? null;
    
    if ($handlers) {
        echo "<p>Handler details:</p>\n";
        foreach ($handlers->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                if (is_array($callback['function'])) {
                    $class = is_object($callback['function'][0]) ? get_class($callback['function'][0]) : $callback['function'][0];
                    $method = $callback['function'][1];
                    echo "<p>  - Class: $class, Method: $method</p>\n";
                } else {
                    echo "<p>  - Function: " . $callback['function'] . "</p>\n";
                }
            }
        }
    }
} else {
    echo "<p style='color: red;'>❌ AJAX action 'mt_get_jury_dashboard_data' is NOT registered</p>\n";
}

// Test AJAX URL
echo "<h2>Testing AJAX URL</h2>\n";
$ajax_url = admin_url('admin-ajax.php');
echo "<p>AJAX URL: $ajax_url</p>\n";

// Test actual AJAX request
echo "<h2>Testing Actual AJAX Request</h2>\n";

$response = wp_remote_post($ajax_url, array(
    'body' => array(
        'action' => 'mt_get_jury_dashboard_data',
        'nonce' => wp_create_nonce('mt_jury_nonce')
    ),
    'timeout' => 30
));

if (is_wp_error($response)) {
    echo "<p style='color: red;'>❌ AJAX request failed: " . $response->get_error_message() . "</p>\n";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $content_type = wp_remote_retrieve_header($response, 'content-type');
    
    echo "<p>Status Code: $status_code</p>\n";
    echo "<p>Content-Type: $content_type</p>\n";
    
    if ($status_code === 200) {
        // Try to decode JSON
        $json_data = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "<p style='color: green;'>✅ AJAX request returned valid JSON</p>\n";
            
            if (isset($json_data['success']) && $json_data['success']) {
                echo "<p style='color: green;'>✅ AJAX request successful</p>\n";
                
                if (isset($json_data['data']['stats'])) {
                    $stats = $json_data['data']['stats'];
                    echo "<p><strong>Stats from AJAX:</strong></p>\n";
                    echo "<ul>\n";
                    echo "<li>Total assigned: {$stats['total_assigned']}</li>\n";
                    echo "<li>Completed: {$stats['completed']}</li>\n";
                    echo "<li>Drafts: {$stats['drafts']}</li>\n";
                    echo "<li>Completion rate: {$stats['completion_rate']}%</li>\n";
                    echo "</ul>\n";
                }
            } else {
                echo "<p style='color: orange;'>⚠️ AJAX request returned error</p>\n";
                if (isset($json_data['data']['message'])) {
                    echo "<p>Error message: {$json_data['data']['message']}</p>\n";
                }
            }
        } else {
            echo "<p style='color: red;'>❌ AJAX request returned invalid JSON</p>\n";
            echo "<p>JSON Error: " . json_last_error_msg() . "</p>\n";
            echo "<p>Response preview (first 500 chars):</p>\n";
            echo "<pre>" . htmlspecialchars(substr($body, 0, 500)) . "...</pre>\n";
        }
    } else {
        echo "<p style='color: red;'>❌ AJAX request returned status code: $status_code</p>\n";
        echo "<p>Response preview (first 500 chars):</p>\n";
        echo "<pre>" . htmlspecialchars(substr($body, 0, 500)) . "...</pre>\n";
    }
}

echo "<h2>Summary</h2>\n";
echo "<p>If the AJAX handler is working correctly, you should see:</p>\n";
echo "<ul>\n";
echo "<li>✅ Valid JSON response</li>\n";
echo "<li>✅ Success status</li>\n";
echo "<li>✅ Correct stats and candidate count</li>\n";
echo "</ul>\n";
echo "<p>If you see HTML instead of JSON, there's still an issue with the AJAX routing.</p>\n"; 