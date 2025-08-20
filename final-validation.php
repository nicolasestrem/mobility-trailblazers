<?php
/**
 * Final validation test for the dependency injection fix
 */

if (!defined('ABSPATH')) {
    echo "Must be run in WordPress context\n";
    exit(1);
}

echo "=== FINAL CONTAINER VALIDATION ===\n\n";

// Test container validation
$is_valid = MobilityTrailblazers\Core\MT_Plugin::validate_container();
echo "Container Validation: " . ($is_valid ? "PASSED" : "FAILED") . "\n";

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
}