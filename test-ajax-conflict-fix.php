<?php
/**
 * Test AJAX Conflict Fix
 * 
 * This script tests if the AJAX conflict has been resolved
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Check if user is logged in and has admin capabilities
if (!current_user_can('manage_options')) {
    wp_die('Access denied. Admin privileges required.');
}

echo "<h1>AJAX Conflict Fix Test</h1>";

// Test 1: Check AJAX handler registration
echo "<h2>1. AJAX Handler Registration Check</h2>";
$ajax_actions = array(
    'mt_get_jury_dashboard_data',
    'mt_get_candidate_evaluation',
    'mt_save_evaluation'
);

foreach ($ajax_actions as $action) {
    if (has_action('wp_ajax_' . $action)) {
        echo "<p style='color: green;'>✅ <strong>$action</strong> is registered</p>";
        
        // Check how many handlers are registered
        global $wp_filter;
        $handlers = $wp_filter['wp_ajax_' . $action] ?? null;
        if ($handlers) {
            $handler_count = count($handlers->callbacks);
            echo "<p style='margin-left: 20px;'>  Handler count: $handler_count</p>";
            
            if ($handler_count > 1) {
                echo "<p style='color: orange; margin-left: 20px;'>⚠️ Multiple handlers detected - this might cause conflicts</p>";
            } else {
                echo "<p style='color: green; margin-left: 20px;'>✅ Single handler - no conflicts</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ <strong>$action</strong> is NOT registered</p>";
    }
}

// Test 2: Check which classes are handling the AJAX
echo "<h2>2. AJAX Handler Class Check</h2>";
global $wp_filter;

foreach ($ajax_actions as $action) {
    $hook_name = 'wp_ajax_' . $action;
    $handlers = $wp_filter[$hook_name] ?? null;
    
    if ($handlers) {
        echo "<p><strong>$action:</strong></p>";
        foreach ($handlers->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                if (is_array($callback['function'])) {
                    $class = is_object($callback['function'][0]) ? get_class($callback['function'][0]) : $callback['function'][0];
                    $method = $callback['function'][1];
                    echo "<p style='margin-left: 20px;'>  Class: $class, Method: $method</p>";
                } else {
                    echo "<p style='margin-left: 20px;'>  Function: " . $callback['function'] . "</p>";
                }
            }
        }
    }
}

// Test 3: Test AJAX endpoint directly
echo "<h2>3. Direct AJAX Test</h2>";
$current_user = wp_get_current_user();
$jury_member = mt_get_jury_member_by_user_id($current_user->ID);

if ($jury_member) {
    // Create a test request
    $_POST['action'] = 'mt_get_jury_dashboard_data';
    $_POST['nonce'] = wp_create_nonce('mt_jury_nonce');
    
    // Capture output
    ob_start();
    
    // Call the AJAX handler directly
    do_action('wp_ajax_mt_get_jury_dashboard_data');
    
    $output = ob_get_clean();
    
    echo "<p><strong>AJAX Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
    // Check if response is JSON
    if (strpos($output, '{') === 0 || strpos($output, '[') === 0) {
        echo "<p style='color: green;'>✅ Response appears to be JSON</p>";
    } else {
        echo "<p style='color: red;'>❌ Response is not JSON (contains HTML)</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ No jury member profile found for current user</p>";
}

// Test 4: Check for any remaining conflicts
echo "<h2>4. Conflict Check</h2>";
$conflicts_found = false;

foreach ($ajax_actions as $action) {
    $hook_name = 'wp_ajax_' . $action;
    $handlers = $wp_filter[$hook_name] ?? null;
    
    if ($handlers && count($handlers->callbacks) > 1) {
        echo "<p style='color: red;'>❌ <strong>$action</strong> has multiple handlers - CONFLICT DETECTED</p>";
        $conflicts_found = true;
    } else {
        echo "<p style='color: green;'>✅ <strong>$action</strong> has single handler - no conflict</p>";
    }
}

if (!$conflicts_found) {
    echo "<p style='color: green; font-weight: bold;'>🎉 No AJAX conflicts detected!</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>⚠️ AJAX conflicts detected - needs further investigation</p>";
}

echo "<hr>";
echo "<p><strong>Test completed.</strong></p>";
?> 