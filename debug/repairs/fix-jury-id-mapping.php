<?php
/**
 * Fix Jury ID Mapping
 * 
 * This script fixes the jury_member_id values in wp_mt_jury_assignments and wp_mt_evaluations
 * by converting them from post IDs to user IDs
 *
 * @package MobilityTrailblazers
 * @since 2.3.3
 */

// Load WordPress
require_once(dirname(__FILE__) . '/../../../../../wp-load.php');

// Security check - allow CLI execution
if (!defined('WP_CLI') && php_sapi_name() !== 'cli' && !current_user_can('manage_options')) {
    wp_die('Unauthorized access');
}

global $wpdb;

echo '<div style="max-width: 1200px; margin: 20px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
echo '<h2>Fix Jury ID Mapping</h2>';

// Step 1: Build mapping between jury post IDs and user IDs
echo '<h3>Step 1: Building ID Mapping</h3>';
echo '<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">';
echo '<tr style="background: #f4f4f4;"><th>User ID</th><th>User Login</th><th>Jury Post ID</th><th>Post Title</th></tr>';

// Get mapping of jury posts to users
$mapping_query = "
    SELECT DISTINCT
        u.ID as user_id,
        u.user_login,
        p.ID as jury_post_id,
        p.post_title
    FROM wp_users u
    JOIN wp_usermeta um ON u.ID = um.user_id
    JOIN wp_posts p ON (
        LOWER(REPLACE(REPLACE(REPLACE(p.post_title, 'Prof. Dr. ', ''), 'Dr. ', ''), 'Prof. em. Dr. Dr. h.c. ', '')) 
        LIKE CONCAT('%', LOWER(TRIM(u.display_name)), '%')
        OR LOWER(p.post_title) LIKE CONCAT('%', LOWER(u.user_login), '%')
    )
    WHERE um.meta_key = 'wp_capabilities' 
    AND um.meta_value LIKE '%mt_jury%'
    AND p.post_type = 'mt_jury_member'
    ORDER BY u.ID
";

$mappings = $wpdb->get_results($mapping_query);
$post_to_user_map = [];

foreach ($mappings as $map) {
    if (!isset($post_to_user_map[$map->jury_post_id])) {
        $post_to_user_map[$map->jury_post_id] = $map->user_id;
        echo '<tr>';
        echo '<td>' . esc_html($map->user_id) . '</td>';
        echo '<td>' . esc_html($map->user_login) . '</td>';
        echo '<td>' . esc_html($map->jury_post_id) . '</td>';
        echo '<td>' . esc_html($map->post_title) . '</td>';
        echo '</tr>';
    }
}

echo '</table>';
echo '<p>Found ' . count($post_to_user_map) . ' jury member mappings.</p>';

// Step 2: Update jury_user_id in postmeta
echo '<h3>Step 2: Updating Post Meta</h3>';
$updated_meta = 0;
foreach ($post_to_user_map as $post_id => $user_id) {
    update_post_meta($post_id, 'jury_user_id', $user_id);
    $updated_meta++;
}
echo '<p>✅ Updated jury_user_id for ' . $updated_meta . ' jury posts.</p>';

// Step 3: Fix wp_mt_jury_assignments
echo '<h3>Step 3: Fixing Jury Assignments Table</h3>';

// Get current assignments with post IDs
$assignments = $wpdb->get_results("SELECT DISTINCT jury_member_id FROM wp_mt_jury_assignments");
$fixed_assignments = 0;

foreach ($assignments as $assignment) {
    if (isset($post_to_user_map[$assignment->jury_member_id])) {
        $result = $wpdb->update(
            'wp_mt_jury_assignments',
            ['jury_member_id' => $post_to_user_map[$assignment->jury_member_id]],
            ['jury_member_id' => $assignment->jury_member_id],
            ['%d'],
            ['%d']
        );
        if ($result !== false) {
            $fixed_assignments += $result;
        }
    }
}

echo '<p>✅ Fixed ' . $fixed_assignments . ' jury assignments.</p>';

// Step 4: Fix wp_mt_evaluations
echo '<h3>Step 4: Fixing Evaluations Table</h3>';

// Get current evaluations with post IDs
$evaluations = $wpdb->get_results("SELECT DISTINCT jury_member_id FROM wp_mt_evaluations");
$fixed_evaluations = 0;

foreach ($evaluations as $evaluation) {
    if (isset($post_to_user_map[$evaluation->jury_member_id])) {
        $result = $wpdb->update(
            'wp_mt_evaluations',
            ['jury_member_id' => $post_to_user_map[$evaluation->jury_member_id]],
            ['jury_member_id' => $evaluation->jury_member_id],
            ['%d'],
            ['%d']
        );
        if ($result !== false) {
            $fixed_evaluations += $result;
        }
    }
}

echo '<p>✅ Fixed ' . $fixed_evaluations . ' evaluations.</p>';

// Step 5: Verification
echo '<h3>Step 5: Verification</h3>';

// Check assignments
$verify_assignments = $wpdb->get_row("
    SELECT 
        COUNT(DISTINCT ja.jury_member_id) as jury_count,
        COUNT(DISTINCT ja.candidate_id) as candidate_count,
        COUNT(*) as total_assignments
    FROM wp_mt_jury_assignments ja
    JOIN wp_users u ON ja.jury_member_id = u.ID
");

echo '<p><strong>Jury Assignments:</strong><br>';
echo 'Jury Members: ' . $verify_assignments->jury_count . '<br>';
echo 'Candidates: ' . $verify_assignments->candidate_count . '<br>';
echo 'Total Assignments: ' . $verify_assignments->total_assignments . '</p>';

// Check evaluations
$verify_evaluations = $wpdb->get_row("
    SELECT 
        COUNT(DISTINCT e.jury_member_id) as jury_count,
        COUNT(DISTINCT e.candidate_id) as candidate_count,
        COUNT(*) as total_evaluations
    FROM wp_mt_evaluations e
    JOIN wp_users u ON e.jury_member_id = u.ID
");

echo '<p><strong>Evaluations:</strong><br>';
echo 'Jury Members: ' . $verify_evaluations->jury_count . '<br>';
echo 'Candidates: ' . $verify_evaluations->candidate_count . '<br>';
echo 'Total Evaluations: ' . $verify_evaluations->total_evaluations . '</p>';

// Show sample assignments
echo '<h3>Sample Assignments (Top 5 Jury Members)</h3>';
echo '<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">';
echo '<tr style="background: #f4f4f4;"><th>Jury Member</th><th>Assigned Candidates</th></tr>';

$sample_assignments = $wpdb->get_results("
    SELECT 
        u.display_name,
        COUNT(ja.candidate_id) as candidate_count
    FROM wp_users u
    JOIN wp_mt_jury_assignments ja ON u.ID = ja.jury_member_id
    GROUP BY u.ID
    ORDER BY candidate_count DESC
    LIMIT 5
");

foreach ($sample_assignments as $sample) {
    echo '<tr>';
    echo '<td>' . esc_html($sample->display_name) . '</td>';
    echo '<td>' . esc_html($sample->candidate_count) . '</td>';
    echo '</tr>';
}

echo '</table>';

echo '<div style="padding: 15px; background: #e8f5e9; border-left: 4px solid #4caf50; margin: 20px 0;">';
echo '<strong>✅ Fix Complete!</strong><br>';
echo 'The jury member IDs have been successfully converted from post IDs to user IDs.<br>';
echo 'Jury members should now be able to see their assigned candidates in the dashboard.';
echo '</div>';

echo '</div>';