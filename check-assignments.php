<?php
/**
 * Check Assignments and Create Test Data
 * 
 * This script checks if there are assignments in the database and creates test data if needed
 */

// Load WordPress
if (!defined('ABSPATH')) {
    require_once 'wp-config.php';
    require_once 'wp-load.php';
}

echo "=== Check Assignments and Create Test Data ===\n\n";

// Check if user is logged in and has admin permissions
$current_user = wp_get_current_user();
if (!$current_user->ID || !current_user_can('manage_options')) {
    echo "❌ Please log in as an administrator to run this script.\n";
    exit;
}

echo "✅ User authenticated: " . $current_user->user_login . "\n\n";

echo "=== Step 1: Check Database Tables ===\n";

global $wpdb;

// Check if tables exist
$tables = array(
    $wpdb->prefix . 'mt_jury_assignments',
    $wpdb->prefix . 'mt_evaluations',
    $wpdb->prefix . 'mt_candidates',
    $wpdb->prefix . 'mt_jury_members'
);

foreach ($tables as $table) {
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
        echo "✅ Table exists: $table\n";
    } else {
        echo "❌ Table missing: $table\n";
    }
}

echo "\n=== Step 2: Check Candidates ===\n";

// Check for candidates
$candidates = get_posts(array(
    'post_type' => 'mt_candidate',
    'post_status' => 'publish',
    'numberposts' => -1
));

echo "Total candidates found: " . count($candidates) . "\n";

if (empty($candidates)) {
    echo "❌ No candidates found. Creating test candidates...\n";
    
    // Create test candidates
    $test_candidates = array(
        array(
            'title' => 'Test Candidate 1',
            'content' => 'This is a test candidate for the Mobility Trailblazers award.',
            'meta' => array(
                'mt_candidate_company' => 'Test Company 1',
                'mt_candidate_position' => 'CEO',
                'mt_candidate_email' => 'candidate1@test.com',
                'mt_candidate_phone' => '+1234567890',
                'mt_candidate_website' => 'https://test1.com',
                'mt_candidate_linkedin' => 'https://linkedin.com/in/test1',
                'mt_candidate_category' => 'startup'
            )
        ),
        array(
            'title' => 'Test Candidate 2',
            'content' => 'Another test candidate for the Mobility Trailblazers award.',
            'meta' => array(
                'mt_candidate_company' => 'Test Company 2',
                'mt_candidate_position' => 'CTO',
                'mt_candidate_email' => 'candidate2@test.com',
                'mt_candidate_phone' => '+1234567891',
                'mt_candidate_website' => 'https://test2.com',
                'mt_candidate_linkedin' => 'https://linkedin.com/in/test2',
                'mt_candidate_category' => 'scaleup'
            )
        ),
        array(
            'title' => 'Test Candidate 3',
            'content' => 'Third test candidate for the Mobility Trailblazers award.',
            'meta' => array(
                'mt_candidate_company' => 'Test Company 3',
                'mt_candidate_position' => 'Founder',
                'mt_candidate_email' => 'candidate3@test.com',
                'mt_candidate_phone' => '+1234567892',
                'mt_candidate_website' => 'https://test3.com',
                'mt_candidate_linkedin' => 'https://linkedin.com/in/test3',
                'mt_candidate_category' => 'startup'
            )
        )
    );
    
    foreach ($test_candidates as $candidate_data) {
        $post_id = wp_insert_post(array(
            'post_title' => $candidate_data['title'],
            'post_content' => $candidate_data['content'],
            'post_type' => 'mt_candidate',
            'post_status' => 'publish'
        ));
        
        if ($post_id) {
            echo "✅ Created candidate: " . $candidate_data['title'] . " (ID: $post_id)\n";
            
            // Add meta data
            foreach ($candidate_data['meta'] as $key => $value) {
                update_post_meta($post_id, $key, $value);
            }
        } else {
            echo "❌ Failed to create candidate: " . $candidate_data['title'] . "\n";
        }
    }
} else {
    echo "✅ Candidates found. Listing first 5:\n";
    foreach (array_slice($candidates, 0, 5) as $candidate) {
        echo "  - " . $candidate->post_title . " (ID: " . $candidate->ID . ")\n";
    }
}

echo "\n=== Step 3: Check Jury Members ===\n";

// Check for jury members
$jury_members = get_posts(array(
    'post_type' => 'mt_jury_member',
    'post_status' => 'publish',
    'numberposts' => -1
));

echo "Total jury members found: " . count($jury_members) . "\n";

if (empty($jury_members)) {
    echo "❌ No jury members found. Creating test jury members...\n";
    
    // Create test jury members
    $test_jury_members = array(
        array(
            'title' => 'Test Jury Member 1',
            'content' => 'This is a test jury member for the Mobility Trailblazers award.',
            'meta' => array(
                'mt_jury_member_company' => 'Jury Company 1',
                'mt_jury_member_position' => 'Director',
                'mt_jury_member_email' => 'jury1@test.com',
                'mt_jury_member_phone' => '+1234567893',
                'mt_jury_member_linkedin' => 'https://linkedin.com/in/jury1'
            )
        ),
        array(
            'title' => 'Test Jury Member 2',
            'content' => 'Another test jury member for the Mobility Trailblazers award.',
            'meta' => array(
                'mt_jury_member_company' => 'Jury Company 2',
                'mt_jury_member_position' => 'Manager',
                'mt_jury_member_email' => 'jury2@test.com',
                'mt_jury_member_phone' => '+1234567894',
                'mt_jury_member_linkedin' => 'https://linkedin.com/in/jury2'
            )
        )
    );
    
    foreach ($test_jury_members as $jury_data) {
        $post_id = wp_insert_post(array(
            'post_title' => $jury_data['title'],
            'post_content' => $jury_data['content'],
            'post_type' => 'mt_jury_member',
            'post_status' => 'publish'
        ));
        
        if ($post_id) {
            echo "✅ Created jury member: " . $jury_data['title'] . " (ID: $post_id)\n";
            
            // Add meta data
            foreach ($jury_data['meta'] as $key => $value) {
                update_post_meta($post_id, $key, $value);
            }
        } else {
            echo "❌ Failed to create jury member: " . $jury_data['title'] . "\n";
        }
    }
} else {
    echo "✅ Jury members found. Listing first 5:\n";
    foreach (array_slice($jury_members, 0, 5) as $jury_member) {
        echo "  - " . $jury_member->post_title . " (ID: " . $jury_member->ID . ")\n";
    }
}

echo "\n=== Step 4: Check Assignments ===\n";

// Check for assignments
$assignments = $wpdb->get_results("
    SELECT * FROM {$wpdb->prefix}mt_jury_assignments 
    ORDER BY id DESC 
    LIMIT 10
");

echo "Total assignments found: " . count($assignments) . "\n";

if (empty($assignments)) {
    echo "❌ No assignments found. Creating test assignments...\n";
    
    // Get candidates and jury members
    $candidates = get_posts(array(
        'post_type' => 'mt_candidate',
        'post_status' => 'publish',
        'numberposts' => -1
    ));
    
    $jury_members = get_posts(array(
        'post_type' => 'mt_jury_member',
        'post_status' => 'publish',
        'numberposts' => -1
    ));
    
    if (!empty($candidates) && !empty($jury_members)) {
        // Create assignments
        foreach ($jury_members as $jury_member) {
            foreach ($candidates as $candidate) {
                $result = $wpdb->insert(
                    $wpdb->prefix . 'mt_jury_assignments',
                    array(
                        'jury_member_id' => $jury_member->ID,
                        'candidate_id' => $candidate->ID,
                        'assigned_date' => current_time('mysql'),
                        'status' => 'assigned'
                    ),
                    array('%d', '%d', '%s', '%s')
                );
                
                if ($result) {
                    echo "✅ Created assignment: Jury " . $jury_member->post_title . " -> Candidate " . $candidate->post_title . "\n";
                } else {
                    echo "❌ Failed to create assignment: Jury " . $jury_member->post_title . " -> Candidate " . $candidate->post_title . "\n";
                }
            }
        }
    } else {
        echo "❌ Cannot create assignments: Missing candidates or jury members\n";
    }
} else {
    echo "✅ Assignments found. Listing first 5:\n";
    foreach (array_slice($assignments, 0, 5) as $assignment) {
        $jury_member = get_post($assignment->jury_member_id);
        $candidate = get_post($assignment->candidate_id);
        
        echo "  - Jury: " . ($jury_member ? $jury_member->post_title : 'Unknown') . 
             " -> Candidate: " . ($candidate ? $candidate->post_title : 'Unknown') . 
             " (Status: " . $assignment->status . ")\n";
    }
}

echo "\n=== Step 5: Test Jury Dashboard Data ===\n";

// Test the jury dashboard data retrieval
if (class_exists('MT_Jury_System')) {
    $jury_system = new MT_Jury_System();
    
    // Test getting assignments for a jury member
    if (!empty($jury_members)) {
        $test_jury_member = $jury_members[0];
        $assignments = $jury_system->get_assignments_for_jury_member($test_jury_member->ID);
        
        echo "Assignments for jury member '" . $test_jury_member->post_title . "': " . count($assignments) . "\n";
        
        if (!empty($assignments)) {
            echo "Sample assignment data:\n";
            $sample = $assignments[0];
            echo "  - Assignment ID: " . $sample->id . "\n";
            echo "  - Candidate ID: " . $sample->candidate_id . "\n";
            echo "  - Status: " . $sample->status . "\n";
        }
    }
}

echo "\n=== Summary ===\n";
echo "✅ Database tables checked\n";
echo "✅ Candidates verified/created\n";
echo "✅ Jury members verified/created\n";
echo "✅ Assignments verified/created\n";
echo "✅ Jury dashboard data tested\n";

echo "\n=== Next Steps ===\n";
echo "1. Clear your browser cache\n";
echo "2. Test the jury dashboard widget/shortcode\n";
echo "3. Check if candidates are now loading\n";
echo "4. If issues persist, run the Elementor webpack fix script\n";

echo "\n🎉 Assignment check and test data creation completed!\n"; 