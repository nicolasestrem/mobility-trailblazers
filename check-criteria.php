<?php
require_once('/var/www/html/wp-load.php');

global $wpdb;
$candidate_id = 4882;

// Check database table
$candidates_table = $wpdb->prefix . 'mt_candidates';
$candidate_data = $wpdb->get_row($wpdb->prepare(
    "SELECT description_sections FROM {$candidates_table} WHERE post_id = %d",
    $candidate_id
));

echo "=== Database Table (wp_mt_candidates) ===\n";
if ($candidate_data && $candidate_data->description_sections) {
    $sections = json_decode($candidate_data->description_sections, true);
    echo "Has description_sections: Yes\n";
    echo "Keys in sections: " . implode(', ', array_keys($sections)) . "\n\n";
    
    foreach ($sections as $key => $value) {
        echo "$key: " . substr($value, 0, 50) . "...\n";
    }
} else {
    echo "No description_sections data\n";
}

echo "\n=== Post Meta Fields ===\n";
echo "_mt_evaluation_criteria: " . substr(get_post_meta($candidate_id, '_mt_evaluation_criteria', true), 0, 100) . "...\n";
echo "_mt_criterion_courage: " . get_post_meta($candidate_id, '_mt_criterion_courage', true) . "\n";
echo "_mt_criterion_innovation: " . get_post_meta($candidate_id, '_mt_criterion_innovation', true) . "\n";
echo "_mt_criterion_implementation: " . get_post_meta($candidate_id, '_mt_criterion_implementation', true) . "\n";
echo "_mt_criterion_relevance: " . get_post_meta($candidate_id, '_mt_criterion_relevance', true) . "\n";
echo "_mt_criterion_visibility: " . get_post_meta($candidate_id, '_mt_criterion_visibility', true) . "\n";