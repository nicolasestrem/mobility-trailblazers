<?php
/**
 * Direct fix for evaluation criteria - simpler version
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Auto-run the fix when page loads
$fix_results = [];

// Get Prof. Dr. Uwe Schneidewind specifically
$candidates = get_posts([
    'post_type' => 'mt_candidate',
    'post_status' => 'any',
    'posts_per_page' => -1,
    'meta_query' => [
        [
            'key' => '_mt_candidate_id',
            'value' => '2',
            'compare' => '='
        ]
    ]
]);

if (empty($candidates)) {
    // Try by name
    $candidates = get_posts([
        'post_type' => 'mt_candidate',
        'post_status' => 'any',
        'posts_per_page' => -1,
        's' => 'Schneidewind'
    ]);
}

?>
<div class="wrap">
    <h1>Direct Fix - Evaluation Criteria</h1>
    
    <?php
    if (!empty($candidates)) {
        foreach ($candidates as $candidate) {
            $description = get_post_meta($candidate->ID, '_mt_description_full', true);
            
            echo '<div class="card" style="background: white; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">';
            echo '<h2>' . esc_html($candidate->post_title) . ' (ID: ' . $candidate->ID . ')</h2>';
            
            if (!empty($description)) {
                echo '<h3>Description Found:</h3>';
                echo '<div style="background: #f5f5f5; padding: 10px; margin: 10px 0; max-height: 200px; overflow-y: auto;">';
                echo '<pre style="white-space: pre-wrap;">' . esc_html($description) . '</pre>';
                echo '</div>';
                
                // Test parsing with the current regex
                echo '<h3>Testing Current Regex Patterns:</h3>';
                
                // Direct regex test
                $all_labels = 'Mut\s*&\s*Pioniergeist:|Innovationsgrad:|Umsetzungskraft\s*&\s*Wirkung:|Relevanz\s*für\s*die\s*Mobilitätswende:|Vorbildfunktion\s*&\s*Sichtbarkeit:|Persönlichkeit\s*&\s*Motivation:';
                
                $patterns = [
                    '_mt_evaluation_courage' => '/Mut\s*&\s*Pioniergeist:\s*(.+?)(?=' . $all_labels . '|$)/isu',
                    '_mt_evaluation_innovation' => '/Innovationsgrad:\s*(.+?)(?=' . $all_labels . '|$)/isu',
                    '_mt_evaluation_implementation' => '/Umsetzungskraft\s*&\s*Wirkung:\s*(.+?)(?=' . $all_labels . '|$)/isu',
                    '_mt_evaluation_relevance' => '/Relevanz\s*für\s*die\s*Mobilitätswende:\s*(.+?)(?=' . $all_labels . '|$)/isu',
                    '_mt_evaluation_visibility' => '/Vorbildfunktion\s*&\s*Sichtbarkeit:\s*(.+?)(?=' . $all_labels . '|$)/isu',
                    '_mt_evaluation_personality' => '/Persönlichkeit\s*&\s*Motivation:\s*(.+?)(?=' . $all_labels . '|$)/isu'
                ];
                
                $results = [];
                foreach ($patterns as $field => $pattern) {
                    if (preg_match($pattern, $description, $matches)) {
                        $results[$field] = trim($matches[1]);
                        $results[$field] = preg_replace('/\s+/', ' ', $results[$field]);
                        $results[$field] = trim($results[$field], " \t\n\r\0\x0B.,;:");
                    } else {
                        $results[$field] = '';
                    }
                }
                
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead><tr><th>Field</th><th>Status</th><th>Extracted Value</th><th>Current DB Value</th></tr></thead>';
                echo '<tbody>';
                
                foreach ($results as $field => $value) {
                    $current_value = get_post_meta($candidate->ID, $field, true);
                    $status = !empty($value) ? '<span style="color: green;">✓ Parsed</span>' : '<span style="color: red;">✗ Failed</span>';
                    $db_status = !empty($current_value) ? '<span style="color: green;">Has Value</span>' : '<span style="color: red;">Empty</span>';
                    
                    echo '<tr>';
                    echo '<td><code>' . esc_html($field) . '</code></td>';
                    echo '<td>' . $status . '</td>';
                    echo '<td>' . esc_html(substr($value, 0, 50)) . (!empty($value) && strlen($value) > 50 ? '...' : '') . '</td>';
                    echo '<td>' . $db_status . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody></table>';
                
                // Now save the parsed values if requested
                if (isset($_GET['do_fix']) && $_GET['do_fix'] === 'yes') {
                    echo '<h3>Saving Parsed Values:</h3>';
                    $saved_count = 0;
                    
                    foreach ($results as $field => $value) {
                        if (!empty($value)) {
                            update_post_meta($candidate->ID, $field, $value);
                            echo '<div class="notice notice-success inline"><p>Saved ' . esc_html($field) . '</p></div>';
                            $saved_count++;
                        }
                    }
                    
                    // Also fix Top 50 status
                    $cand_id = get_post_meta($candidate->ID, '_mt_candidate_id', true);
                    if ($cand_id === '2') {
                        update_post_meta($candidate->ID, '_mt_top_50_status', 'yes');
                        echo '<div class="notice notice-success inline"><p>Updated Top 50 status to YES</p></div>';
                    }
                    
                    echo '<div class="notice notice-success"><p><strong>Saved ' . $saved_count . ' evaluation criteria fields!</strong></p></div>';
                    echo '<p><a href="' . admin_url('admin.php?page=mt-check-import') . '" class="button button-primary">Check Import Results</a></p>';
                } else {
                    // Show fix button
                    $fix_url = add_query_arg(['page' => 'mt-direct-fix', 'do_fix' => 'yes'], admin_url('admin.php'));
                    echo '<p style="margin-top: 20px;">';
                    echo '<a href="' . esc_url($fix_url) . '" class="button button-primary button-large">Apply Fix - Save These Values</a>';
                    echo '</p>';
                }
                
            } else {
                echo '<div class="notice notice-error"><p>No description found for this candidate.</p></div>';
            }
            
            echo '</div>';
        }
    } else {
        echo '<div class="notice notice-error"><p>Could not find Prof. Dr. Uwe Schneidewind in the database.</p></div>';
        
        // Show all candidates for debugging
        echo '<h3>All Candidates in Database:</h3>';
        $all = get_posts([
            'post_type' => 'mt_candidate',
            'post_status' => 'any',
            'posts_per_page' => 20
        ]);
        
        echo '<ul>';
        foreach ($all as $cand) {
            $cand_id = get_post_meta($cand->ID, '_mt_candidate_id', true);
            echo '<li>' . esc_html($cand->post_title) . ' (Post ID: ' . $cand->ID . ', Candidate ID: ' . esc_html($cand_id) . ')</li>';
        }
        echo '</ul>';
    }
    ?>
</div>