<?php
/**
 * Test Profile System
 * 
 * This script tests the enhanced candidate profile system
 *
 * @package MobilityTrailblazers
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Only run if accessed by admin
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

/**
 * Run profile system tests
 */
function mt_test_profile_system() {
    global $wpdb;
    
    echo '<div class="wrap">';
    echo '<h1>Profile System Test Results</h1>';
    
    $tests_passed = 0;
    $tests_failed = 0;
    
    echo '<div class="card">';
    echo '<h2>1. Testing Meta Fields Registration</h2>';
    
    // Test 1: Check if meta fields are registered
    $test_candidate_id = wp_insert_post([
        'post_title' => 'Test Candidate ' . time(),
        'post_type' => 'mt_candidate',
        'post_status' => 'draft'
    ]);
    
    if ($test_candidate_id) {
        echo '<p>✅ Test candidate created (ID: ' . $test_candidate_id . ')</p>';
        $tests_passed++;
        
        // Test saving meta fields
        update_post_meta($test_candidate_id, '_mt_display_name', 'Prof. Dr. Test User');
        update_post_meta($test_candidate_id, '_mt_overview', '<p>Test overview content</p>');
        update_post_meta($test_candidate_id, '_mt_evaluation_criteria', '<p>Test evaluation criteria</p>');
        update_post_meta($test_candidate_id, '_mt_personality_motivation', '<p>Test personality content</p>');
        
        // Verify fields were saved
        $display_name = get_post_meta($test_candidate_id, '_mt_display_name', true);
        $overview = get_post_meta($test_candidate_id, '_mt_overview', true);
        $eval = get_post_meta($test_candidate_id, '_mt_evaluation_criteria', true);
        $personality = get_post_meta($test_candidate_id, '_mt_personality_motivation', true);
        
        if ($display_name === 'Prof. Dr. Test User') {
            echo '<p>✅ Display name field working correctly</p>';
            $tests_passed++;
        } else {
            echo '<p>❌ Display name field failed</p>';
            $tests_failed++;
        }
        
        if ($overview === '<p>Test overview content</p>') {
            echo '<p>✅ Overview field working correctly</p>';
            $tests_passed++;
        } else {
            echo '<p>❌ Overview field failed</p>';
            $tests_failed++;
        }
        
        if ($eval === '<p>Test evaluation criteria</p>') {
            echo '<p>✅ Evaluation criteria field working correctly</p>';
            $tests_passed++;
        } else {
            echo '<p>❌ Evaluation criteria field failed</p>';
            $tests_failed++;
        }
        
        if ($personality === '<p>Test personality content</p>') {
            echo '<p>✅ Personality field working correctly</p>';
            $tests_passed++;
        } else {
            echo '<p>❌ Personality field failed</p>';
            $tests_failed++;
        }
        
        // Clean up test candidate
        wp_delete_post($test_candidate_id, true);
        echo '<p>🧹 Test candidate cleaned up</p>';
        
    } else {
        echo '<p>❌ Failed to create test candidate</p>';
        $tests_failed++;
    }
    
    echo '</div>';
    
    // Test 2: Check template file
    echo '<div class="card">';
    echo '<h2>2. Testing Template Files</h2>';
    
    $template_path = MT_PLUGIN_DIR . 'templates/frontend/single/single-mt_candidate.php';
    if (file_exists($template_path)) {
        echo '<p>✅ Single candidate template exists</p>';
        $tests_passed++;
        
        // Check if template is readable
        if (is_readable($template_path)) {
            echo '<p>✅ Template file is readable</p>';
            $tests_passed++;
        } else {
            echo '<p>❌ Template file is not readable</p>';
            $tests_failed++;
        }
    } else {
        echo '<p>❌ Single candidate template missing at: ' . $template_path . '</p>';
        $tests_failed++;
    }
    
    echo '</div>';
    
    // Test 3: Check CSS file
    echo '<div class="card">';
    echo '<h2>3. Testing CSS Integration</h2>';
    
    $css_file = MT_PLUGIN_DIR . 'assets/css/frontend.css';
    if (file_exists($css_file)) {
        echo '<p>✅ Frontend CSS file exists</p>';
        $tests_passed++;
        
        // Check if CSS contains our profile styles
        $css_content = file_get_contents($css_file);
        if (strpos($css_content, '.mt-candidate-profile-page') !== false) {
            echo '<p>✅ Profile CSS classes found</p>';
            $tests_passed++;
        } else {
            echo '<p>❌ Profile CSS classes not found</p>';
            $tests_failed++;
        }
        
        if (strpos($css_content, '#006a7a') !== false) {
            echo '<p>✅ Teal color scheme found</p>';
            $tests_passed++;
        } else {
            echo '<p>❌ Teal color scheme not found</p>';
            $tests_failed++;
        }
        
        if (strpos($css_content, '#ff6b35') !== false) {
            echo '<p>✅ Orange accent color found</p>';
            $tests_passed++;
        } else {
            echo '<p>❌ Orange accent color not found</p>';
            $tests_failed++;
        }
    } else {
        echo '<p>❌ Frontend CSS file missing</p>';
        $tests_failed++;
    }
    
    echo '</div>';
    
    // Test 4: Check existing candidates
    echo '<div class="card">';
    echo '<h2>4. Checking Existing Candidates</h2>';
    
    $candidates = get_posts([
        'post_type' => 'mt_candidate',
        'posts_per_page' => 5,
        'post_status' => 'any'
    ]);
    
    echo '<p>Found ' . count($candidates) . ' existing candidates</p>';
    
    if (!empty($candidates)) {
        echo '<table class="widefat">';
        echo '<thead><tr>';
        echo '<th>Candidate</th>';
        echo '<th>Display Name</th>';
        echo '<th>Overview</th>';
        echo '<th>Eval Criteria</th>';
        echo '<th>Personality</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($candidates as $candidate) {
            $has_display = get_post_meta($candidate->ID, '_mt_display_name', true) ? '✅' : '❌';
            $has_overview = get_post_meta($candidate->ID, '_mt_overview', true) ? '✅' : '❌';
            $has_eval = get_post_meta($candidate->ID, '_mt_evaluation_criteria', true) ? '✅' : '❌';
            $has_personality = get_post_meta($candidate->ID, '_mt_personality_motivation', true) ? '✅' : '❌';
            
            echo '<tr>';
            echo '<td>' . esc_html($candidate->post_title) . '</td>';
            echo '<td class="text-center">' . $has_display . '</td>';
            echo '<td class="text-center">' . $has_overview . '</td>';
            echo '<td class="text-center">' . $has_eval . '</td>';
            echo '<td class="text-center">' . $has_personality . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '<p class="description">✅ = Has content, ❌ = Empty/Missing</p>';
    }
    
    echo '</div>';
    
    // Test 5: Check shortcode
    echo '<div class="card">';
    echo '<h2>5. Testing Shortcode</h2>';
    
    if (shortcode_exists('mt_candidate_profile')) {
        echo '<p>✅ Shortcode [mt_candidate_profile] is registered</p>';
        $tests_passed++;
    } else {
        echo '<p>❌ Shortcode [mt_candidate_profile] not found</p>';
        $tests_failed++;
    }
    
    echo '</div>';
    
    // Summary
    echo '<div class="card">';
    echo '<h2>Test Summary</h2>';
    echo '<p><strong>Tests Passed:</strong> ' . $tests_passed . '</p>';
    echo '<p><strong>Tests Failed:</strong> ' . $tests_failed . '</p>';
    
    if ($tests_failed === 0) {
        echo '<div class="notice notice-success inline">';
        echo '<p>✅ All tests passed! The profile system is working correctly.</p>';
        echo '</div>';
    } else {
        echo '<div class="notice notice-error inline">';
        echo '<p>❌ Some tests failed. Please check the details above.</p>';
        echo '</div>';
    }
    
    echo '</div>';
    
    // Actions
    echo '<div class="card">';
    echo '<h2>Next Steps</h2>';
    echo '<p>';
    echo '<a href="' . admin_url('admin.php?page=mt-migrate-profiles') . '" class="button button-primary">Run Migration</a> ';
    echo '<a href="' . admin_url('edit.php?post_type=mt_candidate') . '" class="button">View Candidates</a> ';
    echo '<a href="' . admin_url('admin.php?page=mobility-trailblazers') . '" class="button">Back to Dashboard</a>';
    echo '</p>';
    echo '</div>';
    
    echo '</div>';
}

// Run the tests
mt_test_profile_system();
?>
