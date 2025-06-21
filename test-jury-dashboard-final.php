<?php
/**
 * Final Test: Jury Dashboard AJAX
 * Tests the jury dashboard AJAX functionality with the fixed nonce and script localization
 */

// Load WordPress
require_once('../../../wp-load.php');

// Ensure user is logged in as a jury member
if (!is_user_logged_in()) {
    wp_die('Please log in as a jury member first.');
}

$current_user = wp_get_current_user();
echo "<h1>Jury Dashboard AJAX Test</h1>";
echo "<p><strong>Current User:</strong> {$current_user->display_name} ({$current_user->user_login})</p>";

// Check if user has jury role
$has_jury_role = in_array('mt_jury_member', $current_user->roles);
echo "<p><strong>Has Jury Role:</strong> " . ($has_jury_role ? '✅ Yes' : '❌ No') . "</p>";

// Get jury member profile
$jury_member = mt_get_jury_member_by_user_id($current_user->ID);
if ($jury_member) {
    echo "<p><strong>Jury Member Profile:</strong> ✅ Found (ID: {$jury_member->ID})</p>";
} else {
    echo "<p><strong>Jury Member Profile:</strong> ❌ Not found</p>";
}

// Check script localization
echo "<h2>Script Localization Check</h2>";
echo "<p>Checking if mt_jury_ajax object is properly localized...</p>";

// Simulate the script localization
$nonce = wp_create_nonce('mt_jury_nonce');
$ajax_url = admin_url('admin-ajax.php');
$rest_url = rest_url();

echo "<p><strong>Nonce:</strong> {$nonce}</p>";
echo "<p><strong>AJAX URL:</strong> {$ajax_url}</p>";
echo "<p><strong>REST URL:</strong> {$rest_url}</p>";

// Test AJAX action registration
echo "<h2>AJAX Action Registration</h2>";
$action = 'mt_get_jury_dashboard_data';
if (has_action("wp_ajax_{$action}")) {
    echo "<p style='color: green;'>✅ AJAX action '{$action}' is registered</p>";
    
    // Get handlers
    global $wp_filter;
    $handlers = $wp_filter["wp_ajax_{$action}"] ?? null;
    if ($handlers) {
        echo "<p><strong>Handlers:</strong></p><ul>";
        foreach ($handlers as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                if (is_array($callback['function'])) {
                    $class = is_object($callback['function'][0]) ? get_class($callback['function'][0]) : $callback['function'][0];
                    $method = $callback['function'][1];
                    echo "<li>Priority {$priority}: {$class}::{$method}</li>";
                } else {
                    echo "<li>Priority {$priority}: " . $callback['function'] . "</li>";
                }
            }
        }
        echo "</ul>";
    }
} else {
    echo "<p style='color: red;'>❌ AJAX action '{$action}' is NOT registered</p>";
}

// Test AJAX request
echo "<h2>AJAX Request Test</h2>";
echo "<p>Testing the actual AJAX request...</p>";

// Set up the request
$_POST = array(
    'action' => 'mt_get_jury_dashboard_data',
    'nonce' => $nonce
);

// Capture output
ob_start();

// Make the request
try {
    do_action("wp_ajax_{$action}");
    $response = ob_get_clean();
    
    echo "<p style='color: green;'>✅ AJAX request completed</p>";
    echo "<p><strong>Response:</strong></p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 400px; overflow-y: auto;'>";
    echo htmlspecialchars($response);
    echo "</pre>";
    
    // Try to decode JSON
    $json_data = json_decode($response, true);
    if ($json_data) {
        echo "<p style='color: green;'>✅ Response is valid JSON</p>";
        
        if (isset($json_data['success']) && $json_data['success']) {
            echo "<p style='color: green;'>✅ AJAX request was successful</p>";
            
            if (isset($json_data['data']['candidates'])) {
                $candidate_count = count($json_data['data']['candidates']);
                echo "<p><strong>Candidates returned:</strong> {$candidate_count}</p>";
                
                if ($candidate_count > 0) {
                    echo "<p style='color: green;'>✅ Candidates are being returned!</p>";
                    echo "<p><strong>First candidate:</strong> " . $json_data['data']['candidates'][0]['title'] . "</p>";
                } else {
                    echo "<p style='color: orange;'>⚠️ No candidates returned (this might be expected if none are assigned)</p>";
                }
            }
        } else {
            echo "<p style='color: red;'>❌ AJAX request failed</p>";
            if (isset($json_data['data']['message'])) {
                echo "<p><strong>Error:</strong> " . $json_data['data']['message'] . "</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ Response is not valid JSON</p>";
    }
    
} catch (Exception $e) {
    $response = ob_get_clean();
    echo "<p style='color: red;'>❌ Exception occurred: " . $e->getMessage() . "</p>";
    echo "<p><strong>Response:</strong></p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    echo htmlspecialchars($response);
    echo "</pre>";
}

// Check assignments
echo "<h2>Assignment Check</h2>";
if ($jury_member) {
    $assigned_candidates = mt_get_assigned_candidates($jury_member->ID);
    echo "<p><strong>Assigned Candidates:</strong> " . count($assigned_candidates) . "</p>";
    
    if (!empty($assigned_candidates)) {
        echo "<p><strong>Candidate IDs:</strong> " . implode(', ', $assigned_candidates) . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Cannot check assignments - no jury member profile</p>";
}

// Summary
echo "<h2>Summary</h2>";
echo "<p>If you see '✅ Candidates are being returned!' above, then the jury dashboard should now work correctly.</p>";
echo "<p>Please refresh the jury dashboard page and check if candidates are now displayed.</p>";

echo "<hr>";
echo "<p><a href='javascript:history.back()'>← Go Back</a></p>";
?> 