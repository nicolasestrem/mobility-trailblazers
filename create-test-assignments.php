<?php
/**
 * Create and Verify Test Assignments for Jury Dashboard
 *
 * This script checks for candidates, jury members, and assignments,
 * creates test data if missing, and outputs a summary.
 * It is idempotent and safe to run multiple times.
 */

// Load WordPress
if (!defined('ABSPATH')) {
    require_once 'wp-config.php';
    require_once 'wp-load.php';
}

echo "=== Create and Verify Test Assignments ===\n\n";

// Check if user is logged in and has admin permissions
$current_user = wp_get_current_user();
if (!$current_user->ID || !current_user_can('manage_options')) {
    echo "❌ Please log in as an administrator to run this script.\n";
    exit;
}

echo "✅ User authenticated: " . $current_user->user_login . "\n\n";

global $wpdb;

// Step 1: Candidates
$candidates = get_posts(array(
    'post_type' => 'mt_candidate',
    'post_status' => 'publish',
    'numberposts' => -1
));
echo "Total candidates found: " . count($candidates) . "\n";
if (empty($candidates)) {
    echo "❌ No candidates found. Creating test candidates...\n";
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
            foreach ($candidate_data['meta'] as $key => $value) {
                update_post_meta($post_id, $key, $value);
            }
        } else {
            echo "❌ Failed to create candidate: " . $candidate_data['title'] . "\n";
        }
    }
    // Refresh candidates list
    $candidates = get_posts(array(
        'post_type' => 'mt_candidate',
        'post_status' => 'publish',
        'numberposts' => -1
    ));
}

// Step 2: Jury Members
$jury_members = get_posts(array(
    'post_type' => 'mt_jury_member',
    'post_status' => 'publish',
    'numberposts' => -1
));
echo "Total jury members found: " . count($jury_members) . "\n";
if (empty($jury_members)) {
    echo "❌ No jury members found. Creating test jury members...\n";
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
            foreach ($jury_data['meta'] as $key => $value) {
                update_post_meta($post_id, $key, $value);
            }
        } else {
            echo "❌ Failed to create jury member: " . $jury_data['title'] . "\n";
        }
    }
    // Refresh jury members list
    $jury_members = get_posts(array(
        'post_type' => 'mt_jury_member',
        'post_status' => 'publish',
        'numberposts' => -1
    ));
}

// Step 3: Assignments
$table = $wpdb->prefix . 'mt_jury_assignments';
$assignments = $wpdb->get_results("SELECT * FROM $table");
echo "Total assignments found: " . count($assignments) . "\n";
if (empty($assignments)) {
    echo "❌ No assignments found. Creating test assignments...\n";
    if (!empty($candidates) && !empty($jury_members)) {
        foreach ($jury_members as $jury_member) {
            foreach ($candidates as $candidate) {
                // Use INSERT IGNORE to avoid duplicates
                $wpdb->query($wpdb->prepare(
                    "INSERT IGNORE INTO $table (jury_member_id, candidate_id, assigned_date, status) VALUES (%d, %d, %s, %s)",
                    $jury_member->ID,
                    $candidate->ID,
                    current_time('mysql'),
                    'assigned'
                ));
            }
        }
        echo "✅ Test assignments created.\n";
    } else {
        echo "❌ Cannot create assignments: Missing candidates or jury members\n";
    }
    // Refresh assignments list
    $assignments = $wpdb->get_results("SELECT * FROM $table");
}

// Step 4: Output Summary

echo "\n=== Summary ===\n";
echo "✅ Candidates: " . count($candidates) . "\n";
echo "✅ Jury Members: " . count($jury_members) . "\n";
echo "✅ Assignments: " . count($assignments) . "\n";

if (!empty($jury_members)) {
    $test_jury = $jury_members[0];
    $jury_assignments = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE jury_member_id = %d", $test_jury->ID));
    echo "Jury member '" . $test_jury->post_title . "' has " . count($jury_assignments) . " assigned candidates\n";
}

echo "\n=== Next Steps ===\n";
echo "1. Clear your browser cache\n";
echo "2. Test the jury dashboard widget/shortcode\n";
echo "3. Check if candidates are now loading\n";
echo "4. If issues persist, run the Elementor webpack fix script\n";

echo "\n🎉 Test data creation and verification complete!\n"; 