<?php
/**
 * Test auto-assign functionality
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

// Set current user as admin
wp_set_current_user(1);

// Create service instance
$service = new \MobilityTrailblazers\Services\MT_Assignment_Service();

// Test auto-assign with 2 candidates per jury member
echo "Testing auto-assign with balanced method, 2 candidates per jury...\n";
$result = $service->auto_assign('balanced', 2);

if ($result) {
    echo "SUCCESS: Auto-assignment completed\n";
    
    // Check how many assignments were created
    global $wpdb;
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_jury_assignments");
    echo "Total assignments in database: " . $count . "\n";
} else {
    echo "FAILED: Auto-assignment failed\n";
    $errors = $service->get_errors();
    if (!empty($errors)) {
        echo "Errors:\n";
        foreach ($errors as $error) {
            echo "- " . $error . "\n";
        }
    }
}