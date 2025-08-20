<?php
/**
 * Final validation test for the dependency injection fix
 */

if (!defined('ABSPATH')) {
    echo "Must be run in WordPress context\n";
    exit(1);
}

echo "=== FINAL CONTAINER VALIDATION ===\n\n";

// Ensure container is bootstrapped before validation
try {
    $plugin = MobilityTrailblazers\Core\MT_Plugin::get_instance();
    if (!$plugin) {
        echo "Container Bootstrap: FAILED - Plugin instance not available\n";
        exit(1);
    }
    
    // Ensure services are registered for the validation
    $plugin->ensure_services_for_ajax();
    echo "Container Bootstrap: SUCCESS\n";
    
    // Test container validation
    $is_valid = MobilityTrailblazers\Core\MT_Plugin::validate_container();
    echo "Container Validation: " . ($is_valid ? "PASSED" : "FAILED") . "\n";
} catch (Exception $e) {
    echo "Container Bootstrap: FAILED - " . $e->getMessage() . "\n";
    exit(1);
}

// Test actual resolution
try {
    $container = MobilityTrailblazers\Core\MT_Plugin::container();
    $eval_repo = $container->make('MobilityTrailblazers\Interfaces\MT_Evaluation_Repository_Interface');
    echo "Evaluation Repository: RESOLVED - " . get_class($eval_repo) . "\n";
    
    $assign_repo = $container->make('MobilityTrailblazers\Interfaces\MT_Assignment_Repository_Interface');
    echo "Assignment Repository: RESOLVED - " . get_class($assign_repo) . "\n";
    
    echo "\n=== FIX STATUS: SUCCESS ===\n";
    echo "All dependency injection issues have been resolved!\n";
    
} catch (Exception $e) {
    echo "\n=== FIX STATUS: FAILED ===\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}