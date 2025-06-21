<?php
/**
 * Create test assignments for jury dashboard
 */

// Load WordPress
require_once 'wp-config.php';
require_once 'wp-load.php';

echo "=== Creating Test Assignments ===\n\n";

// Check if we have candidates
$candidates = get_posts(array(
    'post_type' => 'mt_candidate',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'fields' => 'ids'
));

echo "Found " . count($candidates) . " published candidates\n";

if (empty($candidates)) {
    echo "No candidates found. Please create some candidates first.\n";
    exit;
}

// Check if we have jury members
$jury_members = get_posts(array(
    'post_type' => 'mt_jury_member',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'fields' => 'ids'
));

echo "Found " . count($jury_members) . " published jury members\n";

if (empty($jury_members)) {
    echo "No jury members found. Please create some jury members first.\n";
    exit;
}

// Check current assignments
global $wpdb;
$table = $wpdb->prefix . 'mt_jury_assignments';
$current_assignments = $wpdb->get_var("SELECT COUNT(*) FROM $table");
echo "Current assignments in database: $current_assignments\n";

if ($current_assignments > 0) {
    echo "Assignments already exist. Do you want to clear them first? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    
    if (trim($line) === 'y') {
        $wpdb->query("TRUNCATE TABLE $table");
        echo "Cleared existing assignments.\n";
    } else {
        echo "Keeping existing assignments.\n";
        exit;
    }
}

// Create assignments using the service
try {
    $service = new \MobilityTrailblazers\Services\MT_Assignment_Service();
    
    // Create assignments - assign each candidate to 2-3 jury members
    $assignments_created = 0;
    
    foreach ($candidates as $candidate_id) {
        // Randomly select 2-3 jury members for each candidate
        $num_jury = rand(2, min(3, count($jury_members)));
        $selected_jury = array_rand(array_flip($jury_members), $num_jury);
        
        if (!is_array($selected_jury)) {
            $selected_jury = array($selected_jury);
        }
        
        foreach ($selected_jury as $jury_member_id) {
            $result = $service->create_assignment($jury_member_id, $candidate_id);
            if ($result) {
                $assignments_created++;
                echo "Assigned candidate $candidate_id to jury member $jury_member_id\n";
            }
        }
    }
    
    echo "\nCreated $assignments_created assignments successfully!\n";
    
    // Verify assignments
    $final_count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    echo "Total assignments in database: $final_count\n";
    
    // Test the utility function
    if (!empty($jury_members)) {
        $test_jury = $jury_members[0];
        $assigned_candidates = mt_get_assigned_candidates($test_jury);
        echo "Test: Jury member $test_jury has " . count($assigned_candidates) . " assigned candidates\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n"; 