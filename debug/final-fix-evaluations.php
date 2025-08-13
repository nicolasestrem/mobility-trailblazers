<?php
/**
 * Final fix for evaluation criteria - run once
 * 
 * Access this file directly via:
 * http://localhost:8080/wp-content/plugins/mobility-trailblazers/debug/final-fix-evaluations.php
 */

// Load WordPress
require_once(dirname(__FILE__) . '/../../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

// Get all candidates with descriptions
$candidates = get_posts([
    'post_type' => 'mt_candidate',
    'post_status' => 'any',
    'posts_per_page' => -1,
    'meta_query' => [
        [
            'key' => '_mt_description_full',
            'value' => '',
            'compare' => '!='
        ]
    ]
]);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Evaluation Criteria</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        h1 { color: #333; }
        .candidate { background: #f5f5f5; padding: 15px; margin: 15px 0; border-left: 4px solid #00736C; }
        .success { color: green; }
        .error { color: red; }
        .field { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #00736C; color: white; }
        .button { background: #00736C; color: white; padding: 10px 20px; text-decoration: none; display: inline-block; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Fix Evaluation Criteria for All Candidates</h1>
    
    <?php
    $total_fixed = 0;
    
    foreach ($candidates as $candidate) {
        $description = get_post_meta($candidate->ID, '_mt_description_full', true);
        
        echo '<div class="candidate">';
        echo '<h3>' . esc_html($candidate->post_title) . ' (ID: ' . $candidate->ID . ')</h3>';
        
        if (!empty($description)) {
            // Parse using the fixed function
            $criteria = \MobilityTrailblazers\Admin\MT_Enhanced_Profile_Importer::parse_evaluation_criteria($description);
            
            echo '<table>';
            echo '<tr><th>Field</th><th>Status</th><th>Value</th></tr>';
            
            $fields_updated = 0;
            foreach ($criteria as $field => $value) {
                $current = get_post_meta($candidate->ID, $field, true);
                $needs_update = empty($current) && !empty($value);
                
                if ($needs_update) {
                    update_post_meta($candidate->ID, $field, $value);
                    $fields_updated++;
                    echo '<tr>';
                    echo '<td>' . $field . '</td>';
                    echo '<td class="success">✓ Updated</td>';
                    echo '<td>' . substr($value, 0, 50) . '...</td>';
                    echo '</tr>';
                } elseif (!empty($value)) {
                    echo '<tr>';
                    echo '<td>' . $field . '</td>';
                    echo '<td>Already Set</td>';
                    echo '<td>' . substr($value, 0, 50) . '...</td>';
                    echo '</tr>';
                }
            }
            
            // Fix Top 50 status
            $cand_id = get_post_meta($candidate->ID, '_mt_candidate_id', true);
            $current_status = get_post_meta($candidate->ID, '_mt_top_50_status', true);
            
            // These candidate IDs are marked as Top 50 in the CSV
            if (in_array($cand_id, ['1', '2']) && $current_status !== 'yes') {
                update_post_meta($candidate->ID, '_mt_top_50_status', 'yes');
                echo '<tr><td>Top 50 Status</td><td class="success">✓ Updated to YES</td><td>-</td></tr>';
                $fields_updated++;
            }
            
            echo '</table>';
            
            if ($fields_updated > 0) {
                echo '<p class="success"><strong>Updated ' . $fields_updated . ' fields</strong></p>';
                $total_fixed++;
            } else {
                echo '<p>No updates needed - all fields already set correctly</p>';
            }
        } else {
            echo '<p class="error">No description found</p>';
        }
        
        echo '</div>';
    }
    
    echo '<hr>';
    echo '<h2>Summary</h2>';
    echo '<p><strong>Total candidates processed:</strong> ' . count($candidates) . '</p>';
    echo '<p><strong>Candidates updated:</strong> ' . $total_fixed . '</p>';
    
    echo '<p><a href="' . admin_url('admin.php?page=mobility-trailblazers') . '" class="button">Back to Dashboard</a></p>';
    ?>
</body>
</html>