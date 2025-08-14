<?php
/**
 * Debug script to check Prof. Dr. Uwe Schneidewind's imported data
 * 
 * Usage: Run this from WordPress admin or via WP-CLI
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Security check - require admin capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'mobility-trailblazers'));
}

// Verify nonce if accessed via admin
if (isset($_GET['_wpnonce']) && !wp_verify_nonce($_GET['_wpnonce'], 'mt_debug_access')) {
    wp_die(__('Security check failed.', 'mobility-trailblazers'));
}

?>
<div class="wrap">
    <h1>Check Import: Prof. Dr. Uwe Schneidewind</h1>
    
    <?php
    // Find the candidate by name
    $args = [
        'post_type' => 'mt_candidate',
        'post_status' => 'any',
        'posts_per_page' => -1,
        's' => 'Uwe Schneidewind',
        'orderby' => 'title',
        'order' => 'ASC'
    ];
    
    $query = new WP_Query($args);
    
    if (!$query->have_posts()) {
        echo '<div class="notice notice-error"><p>Prof. Dr. Uwe Schneidewind not found in candidates.</p></div>';
        
        // Try alternative search by meta
        $args2 = [
            'post_type' => 'mt_candidate',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => '_mt_candidate_id',
                    'value' => '2',
                    'compare' => '='
                ],
                [
                    'key' => '_mt_organization',
                    'value' => 'Wuppertal',
                    'compare' => 'LIKE'
                ]
            ]
        ];
        
        $query2 = new WP_Query($args2);
        if ($query2->have_posts()) {
            echo '<div class="notice notice-warning"><p>Found by meta search:</p></div>';
            $query = $query2;
        }
    }
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            ?>
            
            <div class="card" style="background: white; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
                <h2><?php the_title(); ?> (ID: <?php echo $post_id; ?>)</h2>
                
                <h3>Expected Data from CSV:</h3>
                <table class="wp-list-table widefat fixed striped">
                    <tr>
                        <th>Field</th>
                        <th>Expected Value</th>
                    </tr>
                    <tr>
                        <td>ID</td>
                        <td>2</td>
                    </tr>
                    <tr>
                        <td>Name</td>
                        <td>Prof. Dr. Uwe Schneidewind</td>
                    </tr>
                    <tr>
                        <td>Organisation</td>
                        <td>Stadt Wuppertal</td>
                    </tr>
                    <tr>
                        <td>Position</td>
                        <td>Oberbürgermeister</td>
                    </tr>
                    <tr>
                        <td>LinkedIn</td>
                        <td>https://linkedin.com/in/uwe-schneidewind</td>
                    </tr>
                    <tr>
                        <td>Website</td>
                        <td>https://wuppertal.de</td>
                    </tr>
                    <tr>
                        <td>Article</td>
                        <td>https://example.com/article-schneidewind</td>
                    </tr>
                    <tr>
                        <td>Category</td>
                        <td>Gov</td>
                    </tr>
                    <tr>
                        <td>Status</td>
                        <td>Top 50: Yes</td>
                    </tr>
                </table>
                
                <h3>Actual Meta Data in Database:</h3>
                <?php
                // Define all meta keys to check
                $meta_keys = [
                    '_mt_candidate_id' => 'Candidate ID',
                    '_mt_organization' => 'Organization',
                    '_mt_position' => 'Position',
                    '_mt_linkedin_url' => 'LinkedIn URL',
                    '_mt_website_url' => 'Website URL',
                    '_mt_article_url' => 'Article URL',
                    '_mt_description_full' => 'Full Description',
                    '_mt_category_type' => 'Category',
                    '_mt_top_50_status' => 'Top 50 Status',
                    '_mt_evaluation_courage' => 'Mut & Pioniergeist',
                    '_mt_evaluation_innovation' => 'Innovationsgrad',
                    '_mt_evaluation_implementation' => 'Umsetzungskraft',
                    '_mt_evaluation_relevance' => 'Relevanz',
                    '_mt_evaluation_visibility' => 'Sichtbarkeit',
                    '_mt_evaluation_personality' => 'Persönlichkeit'
                ];
                
                $missing_fields = [];
                $empty_fields = [];
                $filled_fields = [];
                ?>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 30%;">Meta Key</th>
                            <th style="width: 20%;">Label</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 40%;">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($meta_keys as $key => $label) {
                        $value = get_post_meta($post_id, $key, true);
                        $exists = metadata_exists('post', $post_id, $key);
                        
                        if (!$exists) {
                            $status = '<span style="color: red;">MISSING</span>';
                            $missing_fields[] = $key;
                        } elseif (empty($value)) {
                            $status = '<span style="color: orange;">EMPTY</span>';
                            $empty_fields[] = $key;
                        } else {
                            $status = '<span style="color: green;">OK</span>';
                            $filled_fields[] = $key;
                        }
                        
                        // Truncate long values for display
                        $display_value = $value;
                        if (strlen($display_value) > 100) {
                            $display_value = substr($display_value, 0, 100) . '...';
                        }
                        ?>
                        <tr>
                            <td><code><?php echo esc_html($key); ?></code></td>
                            <td><?php echo esc_html($label); ?></td>
                            <td><?php echo $status; ?></td>
                            <td><?php echo esc_html($display_value); ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
                
                <h3>Summary:</h3>
                <ul>
                    <li><strong>Filled fields:</strong> <?php echo count($filled_fields); ?> / <?php echo count($meta_keys); ?></li>
                    <li><strong>Empty fields:</strong> <?php echo count($empty_fields); ?></li>
                    <li><strong>Missing fields:</strong> <?php echo count($missing_fields); ?></li>
                </ul>
                
                <?php if (!empty($missing_fields)): ?>
                    <div class="notice notice-error">
                        <p><strong>Missing fields:</strong></p>
                        <ul>
                            <?php foreach ($missing_fields as $field): ?>
                                <li><code><?php echo esc_html($field); ?></code></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($empty_fields)): ?>
                    <div class="notice notice-warning">
                        <p><strong>Empty fields:</strong></p>
                        <ul>
                            <?php foreach ($empty_fields as $field): ?>
                                <li><code><?php echo esc_html($field); ?></code></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <h3>Post Content (Description):</h3>
                <div style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd;">
                    <?php 
                    $content = get_the_content();
                    if (empty($content)) {
                        echo '<em>No content stored in post_content field.</em>';
                    } else {
                        echo '<pre>' . esc_html($content) . '</pre>';
                    }
                    ?>
                </div>
                
                <h3>All Post Meta (Raw):</h3>
                <details>
                    <summary>Click to expand all meta data</summary>
                    <pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 400px; overflow-y: auto;">
<?php 
$all_meta = get_post_meta($post_id);
print_r($all_meta);
?>
                    </pre>
                </details>
            </div>
            
            <?php
        }
        wp_reset_postdata();
    } else {
        ?>
        <div class="notice notice-error">
            <p>No candidates found. The import may have failed or the candidate wasn't imported.</p>
        </div>
        
        <h3>Debug: List all mt_candidate posts:</h3>
        <?php
        $all_candidates = get_posts([
            'post_type' => 'mt_candidate',
            'post_status' => 'any',
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
        
        if ($all_candidates) {
            echo '<ul>';
            foreach ($all_candidates as $candidate) {
                $cand_id = get_post_meta($candidate->ID, '_mt_candidate_id', true);
                $org = get_post_meta($candidate->ID, '_mt_organization', true);
                echo '<li>';
                echo '<strong>' . esc_html($candidate->post_title) . '</strong> (Post ID: ' . $candidate->ID . ')';
                if ($cand_id) echo ' - Candidate ID: ' . esc_html($cand_id);
                if ($org) echo ' - Org: ' . esc_html($org);
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No candidates found in the system.</p>';
        }
    }
    ?>
</div>