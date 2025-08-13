<?php
/**
 * Fix existing candidates by re-parsing their evaluation criteria
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="wrap">
    <h1>Fix Existing Evaluation Criteria</h1>
    
    <?php
    // Handle the fix action
    if (isset($_POST['fix_evaluations']) && wp_verify_nonce($_POST['_wpnonce'], 'fix_evaluations')) {
        echo '<div class="notice notice-info"><p>Processing fix request...</p></div>';
        
        $candidate_id = isset($_POST['candidate_id']) ? intval($_POST['candidate_id']) : 0;
        
        if ($candidate_id) {
            // Get the candidate
            $candidate = get_post($candidate_id);
            if ($candidate && $candidate->post_type === 'mt_candidate') {
                // Get the full description
                $description = get_post_meta($candidate_id, '_mt_description_full', true);
                
                echo '<div class="notice notice-info"><p>Found candidate: ' . esc_html($candidate->post_title) . '</p></div>';
                
                if (!empty($description)) {
                    echo '<div class="notice notice-info"><p>Description length: ' . strlen($description) . ' characters</p></div>';
                    
                    // Parse the evaluation criteria
                    $criteria = \MobilityTrailblazers\Admin\MT_Import_Handler::parse_evaluation_criteria($description);
                    
                    echo '<div class="notice notice-info"><p>Parsed criteria: <pre>' . print_r($criteria, true) . '</pre></p></div>';
                    
                    // Save each criterion
                    $updated = 0;
                    $saved_fields = [];
                    foreach ($criteria as $key => $value) {
                        if (!empty($value)) {
                            update_post_meta($candidate_id, $key, $value);
                            $updated++;
                            $saved_fields[] = $key;
                        }
                    }
                    
                    if ($updated > 0) {
                        echo '<div class="notice notice-success"><p>Saved fields: ' . implode(', ', $saved_fields) . '</p></div>';
                    }
                    
                    // Also fix Top 50 status if needed
                    $status = get_post_meta($candidate_id, '_mt_top_50_status', true);
                    if ($status === 'no') {
                        // Check if it should be yes based on the candidate
                        $cand_id = get_post_meta($candidate_id, '_mt_candidate_id', true);
                        if (in_array($cand_id, ['1', '2'])) { // These are marked as Top 50 in CSV
                            update_post_meta($candidate_id, '_mt_top_50_status', 'yes');
                            echo '<div class="notice notice-info"><p>Also fixed Top 50 status to "yes"</p></div>';
                        }
                    }
                    
                    echo '<div class="notice notice-success"><p>Successfully updated ' . $updated . ' evaluation criteria fields for ' . esc_html($candidate->post_title) . '</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>No description found for this candidate.</p></div>';
                }
            } else {
                echo '<div class="notice notice-error"><p>Invalid candidate ID.</p></div>';
            }
        } else {
            // Fix all candidates
            $candidates = get_posts([
                'post_type' => 'mt_candidate',
                'post_status' => 'any',
                'posts_per_page' => -1
            ]);
            
            $total_fixed = 0;
            $total_candidates = 0;
            
            foreach ($candidates as $candidate) {
                $description = get_post_meta($candidate->ID, '_mt_description_full', true);
                
                if (!empty($description)) {
                    // Parse the evaluation criteria
                    $criteria = \MobilityTrailblazers\Admin\MT_Import_Handler::parse_evaluation_criteria($description);
                    
                    // Save each criterion
                    $updated = 0;
                    foreach ($criteria as $key => $value) {
                        if (!empty($value)) {
                            update_post_meta($candidate->ID, $key, $value);
                            $updated++;
                        }
                    }
                    
                    if ($updated > 0) {
                        $total_fixed++;
                    }
                    
                    // Fix Top 50 status based on CSV data
                    $cand_id = get_post_meta($candidate->ID, '_mt_candidate_id', true);
                    if (in_array($cand_id, ['1', '2'])) { // These are Top 50: Yes in CSV
                        update_post_meta($candidate->ID, '_mt_top_50_status', 'yes');
                    }
                }
                $total_candidates++;
            }
            
            echo '<div class="notice notice-success"><p>Processed ' . $total_candidates . ' candidates. Fixed evaluation criteria for ' . $total_fixed . ' candidates.</p></div>';
        }
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
    
    <div class="card" style="background: white; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
        <h2>Candidates with Descriptions</h2>
        
        <p>The following candidates have descriptions that can be re-parsed for evaluation criteria:</p>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Organization</th>
                    <th>Has Description</th>
                    <th>Missing Criteria</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($candidates as $candidate) {
                    $has_description = get_post_meta($candidate->ID, '_mt_description_full', true) ? 'Yes' : 'No';
                    $org = get_post_meta($candidate->ID, '_mt_organization', true);
                    
                    // Check which criteria are missing
                    $missing = [];
                    $criteria_keys = [
                        '_mt_evaluation_courage',
                        '_mt_evaluation_innovation',
                        '_mt_evaluation_implementation',
                        '_mt_evaluation_relevance',
                        '_mt_evaluation_visibility',
                        '_mt_evaluation_personality'
                    ];
                    
                    foreach ($criteria_keys as $key) {
                        if (!get_post_meta($candidate->ID, $key, true)) {
                            $missing[] = str_replace('_mt_evaluation_', '', $key);
                        }
                    }
                    ?>
                    <tr>
                        <td><?php echo $candidate->ID; ?></td>
                        <td><strong><?php echo esc_html($candidate->post_title); ?></strong></td>
                        <td><?php echo esc_html($org); ?></td>
                        <td><?php echo $has_description; ?></td>
                        <td>
                            <?php 
                            if (empty($missing)) {
                                echo '<span style="color: green;">None - All OK</span>';
                            } else {
                                echo '<span style="color: red;">' . count($missing) . ' missing: ' . implode(', ', $missing) . '</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if (!empty($missing)): ?>
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field('fix_evaluations'); ?>
                                    <input type="hidden" name="candidate_id" value="<?php echo $candidate->ID; ?>">
                                    <button type="submit" name="fix_evaluations" class="button button-small">Fix This</button>
                                </form>
                            <?php else: ?>
                                <span style="color: green;">✓</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
        
        <h3>Bulk Actions</h3>
        <form method="post">
            <?php wp_nonce_field('fix_evaluations'); ?>
            <p>
                <button type="submit" name="fix_evaluations" class="button button-primary">
                    Fix All Candidates with Missing Criteria
                </button>
            </p>
            <p class="description">
                This will re-parse the description field for all candidates and extract the evaluation criteria using the updated regex patterns.
            </p>
        </form>
    </div>
    
    <div class="card" style="background: white; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
        <h2>Test Specific Candidate</h2>
        
        <p>Enter a candidate Post ID to see their description and parsed criteria:</p>
        
        <?php
        if (isset($_GET['test_id'])) {
            $test_id = intval($_GET['test_id']);
            $candidate = get_post($test_id);
            
            if ($candidate && $candidate->post_type === 'mt_candidate') {
                $description = get_post_meta($test_id, '_mt_description_full', true);
                ?>
                <h3><?php echo esc_html($candidate->post_title); ?></h3>
                
                <h4>Full Description:</h4>
                <div style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd; margin: 10px 0;">
                    <pre style="white-space: pre-wrap;"><?php echo esc_html($description); ?></pre>
                </div>
                
                <h4>Parsed Criteria:</h4>
                <?php
                if (!empty($description)) {
                    $parsed = \MobilityTrailblazers\Admin\MT_Import_Handler::parse_evaluation_criteria($description);
                    ?>
                    <table class="wp-list-table widefat fixed striped">
                        <tr>
                            <th>Field</th>
                            <th>Value</th>
                        </tr>
                        <?php foreach ($parsed as $key => $value): ?>
                            <tr>
                                <td><code><?php echo esc_html($key); ?></code></td>
                                <td><?php echo !empty($value) ? esc_html(substr($value, 0, 100) . '...') : '<span style="color: red;">EMPTY</span>'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    <?php
                } else {
                    echo '<p style="color: red;">No description found for this candidate.</p>';
                }
            } else {
                echo '<p style="color: red;">Invalid candidate ID or not a candidate post type.</p>';
            }
        }
        ?>
        
        <form method="get">
            <input type="hidden" name="page" value="mt-fix-evaluations">
            <p>
                <label>Candidate Post ID: 
                    <input type="number" name="test_id" value="<?php echo isset($_GET['test_id']) ? intval($_GET['test_id']) : ''; ?>">
                </label>
                <button type="submit" class="button">Test Parsing</button>
            </p>
        </form>
    </div>
</div>