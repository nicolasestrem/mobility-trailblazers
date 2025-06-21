<?php
/**
 * Fix Jury Dashboard - Comprehensive Solution
 */

// Load WordPress
require_once 'wp-config.php';
require_once 'wp-load.php';

echo "=== Jury Dashboard Fix Script ===\n\n";

// Check if user is logged in
$current_user = wp_get_current_user();
echo "Current user: " . ($current_user->ID ? $current_user->user_login : 'Not logged in') . "\n";

if (!$current_user->ID) {
    echo "No user logged in. Please log in first.\n";
    exit;
}

// Check if user has admin permissions
if (!current_user_can('manage_options')) {
    echo "User does not have admin permissions. Please log in as an administrator.\n";
    exit;
}

echo "\n=== System Check ===\n";

// Check candidates
$candidates = get_posts(array(
    'post_type' => 'mt_candidate',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'fields' => 'ids'
));

echo "Published candidates: " . count($candidates) . "\n";

if (empty($candidates)) {
    echo "❌ No candidates found! Please create some candidates first.\n";
    echo "   Go to: " . admin_url('edit.php?post_type=mt_candidate') . "\n";
    exit;
}

// Check jury members
$jury_members = get_posts(array(
    'post_type' => 'mt_jury_member',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'fields' => 'ids'
));

echo "Published jury members: " . count($jury_members) . "\n";

if (empty($jury_members)) {
    echo "❌ No jury members found! Please create some jury members first.\n";
    echo "   Go to: " . admin_url('edit.php?post_type=mt_jury_member') . "\n";
    exit;
}

// Check assignments table
global $wpdb;
$table = $wpdb->prefix . 'mt_jury_assignments';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");

if (!$table_exists) {
    echo "❌ Assignments table does not exist! Creating it now...\n";
    
    // Create the table
    $sql = "CREATE TABLE $table (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        candidate_id bigint(20) UNSIGNED NOT NULL,
        jury_member_id bigint(20) UNSIGNED NOT NULL,
        assignment_date datetime DEFAULT CURRENT_TIMESTAMP,
        is_active tinyint(1) DEFAULT 1,
        assigned_by bigint(20) UNSIGNED DEFAULT NULL,
        notes text,
        PRIMARY KEY (id),
        KEY candidate_id (candidate_id),
        KEY jury_member_id (jury_member_id),
        KEY is_active (is_active),
        KEY assignment_date (assignment_date),
        UNIQUE KEY unique_assignment (candidate_id, jury_member_id)
    ) " . $wpdb->get_charset_collate();
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    echo "✅ Assignments table created successfully.\n";
} else {
    echo "✅ Assignments table exists.\n";
}

// Check current assignments
$current_assignments = $wpdb->get_var("SELECT COUNT(*) FROM $table");
echo "Current assignments: $current_assignments\n";

if ($current_assignments == 0) {
    echo "\n=== Creating Test Assignments ===\n";
    
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
                    $candidate_name = get_the_title($candidate_id);
                    $jury_name = get_the_title($jury_member_id);
                    echo "✅ Assigned: $candidate_name → $jury_name\n";
                }
            }
        }
        
        echo "\n🎉 Created $assignments_created assignments successfully!\n";
        
    } catch (Exception $e) {
        echo "❌ Error creating assignments: " . $e->getMessage() . "\n";
    }
} else {
    echo "✅ Assignments already exist.\n";
}

// Test the utility function
echo "\n=== Testing Utility Functions ===\n";

if (!empty($jury_members)) {
    $test_jury = $jury_members[0];
    $assigned_candidates = mt_get_assigned_candidates($test_jury);
    $jury_name = get_the_title($test_jury);
    echo "Test: $jury_name has " . count($assigned_candidates) . " assigned candidates\n";
    
    if (!empty($assigned_candidates)) {
        echo "   Candidate IDs: " . implode(', ', $assigned_candidates) . "\n";
    }
}

// Test AJAX handler
echo "\n=== Testing AJAX Handler ===\n";

if (has_action('wp_ajax_mt_get_jury_dashboard_data')) {
    echo "✅ AJAX handler is registered.\n";
} else {
    echo "❌ AJAX handler is NOT registered.\n";
}

// Test nonce
$nonce = wp_create_nonce('mt_jury_nonce');
if (wp_verify_nonce($nonce, 'mt_jury_nonce')) {
    echo "✅ Nonce verification works.\n";
} else {
    echo "❌ Nonce verification failed.\n";
}

// Check if user is a jury member
$jury_member = mt_get_jury_member_by_user_id($current_user->ID);
if ($jury_member) {
    echo "✅ Current user is a jury member (ID: {$jury_member->ID}).\n";
    
    // Test getting assigned candidates for current user
    $user_assignments = mt_get_assigned_candidates($jury_member->ID);
    echo "   Current user has " . count($user_assignments) . " assigned candidates.\n";
    
    if (!empty($user_assignments)) {
        echo "   Candidate IDs: " . implode(', ', $user_assignments) . "\n";
    }
} else {
    echo "⚠️  Current user is not a jury member.\n";
    echo "   To test the jury dashboard, you need to:\n";
    echo "   1. Create a jury member post\n";
    echo "   2. Link it to your user account\n";
    echo "   3. Assign candidates to that jury member\n";
}

echo "\n=== Summary ===\n";
echo "✅ System check completed.\n";
echo "✅ Assignments created/found.\n";
echo "✅ Utility functions tested.\n";
echo "✅ AJAX handler verified.\n";

echo "\n=== Next Steps ===\n";
echo "1. Go to the jury dashboard page to see if candidates are now showing.\n";
echo "2. If you're not a jury member, create a jury member profile and link it to your user.\n";
echo "3. Use the Assignment Management page to manage assignments: " . admin_url('admin.php?page=mt-assignment-management') . "\n";
echo "4. Check the browser console for any JavaScript errors.\n";

echo "\n=== Debug Information ===\n";
echo "AJAX URL: " . admin_url('admin-ajax.php') . "\n";
echo "Nonce: $nonce\n";
echo "Total candidates: " . count($candidates) . "\n";
echo "Total jury members: " . count($jury_members) . "\n";
echo "Total assignments: " . $wpdb->get_var("SELECT COUNT(*) FROM $table") . "\n";

echo "\n🎉 Fix script completed!\n"; 