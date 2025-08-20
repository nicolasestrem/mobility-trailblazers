<?php
// Load WordPress
require_once('/var/www/html/wp-load.php');

global $wpdb;

$candidate_id = 4882;

// Check wp_mt_candidates table
$candidates_table = $wpdb->prefix . 'mt_candidates';
$candidate_data = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$candidates_table} WHERE post_id = %d",
    $candidate_id
));

echo "=== Data from wp_mt_candidates table ===\n";
if ($candidate_data) {
    echo "Found in table!\n";
    echo "Description sections length: " . strlen($candidate_data->description_sections) . "\n";
    $sections = json_decode($candidate_data->description_sections, true);
    if ($sections && isset($sections['ueberblick'])) {
        echo "Überblick content: " . substr($sections['ueberblick'], 0, 200) . "...\n";
    }
} else {
    echo "NOT found in wp_mt_candidates table\n";
}

echo "\n=== Data from postmeta ===\n";
$overview = get_post_meta($candidate_id, '_mt_overview', true);
echo "_mt_overview length: " . strlen($overview) . "\n";
echo "_mt_overview content: " . substr($overview, 0, 200) . "...\n";

echo "\n=== Post content ===\n";
$post = get_post($candidate_id);
echo "post_content length: " . strlen($post->post_content) . "\n";
echo "post_content: " . substr($post->post_content, 0, 200) . "\n";