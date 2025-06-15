<?php
/**
 * Voting Results View
 *
 * @package MobilityTrailblazers
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check permissions
if (!current_user_can('mt_view_all_evaluations')) {
    wp_die(__('You do not have permission to access this page.', 'mobility-trailblazers'));
}

global $wpdb;

// Get filter parameters
$filter_category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$filter_phase = isset($_GET['phase']) ? sanitize_text_field($_GET['phase']) : '';

// Build query
$query = "SELECT 
    c.ID,
    c.post_title as candidate_name,
    COUNT(DISTINCT v.jury_member_id) as vote_count,
    AVG(v.rating) as avg_rating,
    COUNT(DISTINCT s.jury_member_id) as evaluation_count,
    AVG(s.total_score) as avg_score
FROM {$wpdb->posts} c
LEFT JOIN {$wpdb->prefix}mt_votes v ON c.ID = v.candidate_id
LEFT JOIN {$wpdb->prefix}mt_candidate_scores s ON c.ID = s.candidate_id
WHERE c.post_type = 'mt_candidate' 
AND c.post_status = 'publish'";

// Add filters if needed
if ($filter_category || $filter_phase) {
    $query .= " AND c.ID IN (
        SELECT object_id FROM {$wpdb->term_relationships} tr
        INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
        WHERE 1=1";
    
    if ($filter_category) {
        $query .= $wpdb->prepare(" AND t.slug = %s AND tt.taxonomy = 'mt_category'", $filter_category);
    }
    if ($filter_phase) {
        $query .= $wpdb->prepare(" AND t.slug = %s AND tt.taxonomy = 'mt_phase'", $filter_phase);
    }
    
    $query .= ")";
}

$query .= " GROUP BY c.ID ORDER BY avg_score DESC, vote_count DESC";

$results = $wpdb->get_results($query);
?>

<div class="wrap">
    <h1><?php _e('Voting Results', 'mobility-trailblazers'); ?></h1>
    
    <!-- Filters -->
    <div class="tablenav top">
        <form method="get" action="">
            <input type="hidden" name="page" value="mt-voting-results" />
            
            <div class="alignleft actions">
                <label for="filter-category" class="screen-reader-text"><?php _e('Filter by category', 'mobility-trailblazers'); ?></label>
                <select name="category" id="filter-category">
                    <option value=""><?php _e('All Categories', 'mobility-trailblazers'); ?></option>
                    <?php
                    $categories = get_terms(array(
                        'taxonomy' => 'mt_category',
                        'hide_empty' => false
                    ));
                    foreach ($categories as $category) {
                        printf(
                            '<option value="%s" %s>%s</option>',
                            esc_attr($category->slug),
                            selected($filter_category, $category->slug, false),
                            esc_html($category->name)
                        );
                    }
                    ?>
                </select>
                
                <label for="filter-phase" class="screen-reader-text"><?php _e('Filter by phase', 'mobility-trailblazers'); ?></label>
                <select name="phase" id="filter-phase">
                    <option value=""><?php _e('All Phases', 'mobility-trailblazers'); ?></option>
                    <?php
                    $phases = get_terms(array(
                        'taxonomy' => 'mt_phase',
                        'hide_empty' => false
                    ));
                    foreach ($phases as $phase) {
                        printf(
                            '<option value="%s" %s>%s</option>',
                            esc_attr($phase->slug),
                            selected($filter_phase, $phase->slug, false),
                            esc_html($phase->name)
                        );
                    }
                    ?>
                </select>
                
                <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'mobility-trailblazers'); ?>" />
            </div>
            
            <div class="alignright">
                <a href="<?php echo wp_nonce_url(admin_url('admin-ajax.php?action=mt_export_results'), 'mt_export_nonce'); ?>" class="button">
                    <?php _e('Export Results', 'mobility-trailblazers'); ?>
                </a>
            </div>
        </form>
    </div>
    
    <!-- Results Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="column-rank"><?php _e('Rank', 'mobility-trailblazers'); ?></th>
                <th scope="col" class="column-candidate"><?php _e('Candidate', 'mobility-trailblazers'); ?></th>
                <th scope="col" class="column-category"><?php _e('Category', 'mobility-trailblazers'); ?></th>
                <th scope="col" class="column-votes"><?php _e('Votes', 'mobility-trailblazers'); ?></th>
                <th scope="col" class="column-rating"><?php _e('Avg Rating', 'mobility-trailblazers'); ?></th>
                <th scope="col" class="column-evaluations"><?php _e('Evaluations', 'mobility-trailblazers'); ?></th>
                <th scope="col" class="column-score"><?php _e('Avg Score', 'mobility-trailblazers'); ?></th>
                <th scope="col" class="column-actions"><?php _e('Actions', 'mobility-trailblazers'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($results) : ?>
                <?php $rank = 1; ?>
                <?php foreach ($results as $result) : ?>
                    <?php
                    $categories = wp_get_post_terms($result->ID, 'mt_category', array('fields' => 'names'));
                    $category_names = '-';
                    if (!is_wp_error($categories) && !empty($categories)) {
                        $category_names = implode(', ', $categories);
                    }
                    ?>
                    <tr>
                        <td class="column-rank"><?php echo $rank++; ?></td>
                        <td class="column-candidate">
                            <strong>
                                <a href="<?php echo get_edit_post_link($result->ID); ?>">
                                    <?php echo esc_html($result->candidate_name); ?>
                                </a>
                            </strong>
                        </td>
                        <td class="column-category"><?php echo esc_html($category_names); ?></td>
                        <td class="column-votes"><?php echo intval($result->vote_count); ?></td>
                        <td class="column-rating">
                            <?php echo $result->avg_rating ? number_format($result->avg_rating, 2) . '/10' : '-'; ?>
                        </td>
                        <td class="column-evaluations"><?php echo intval($result->evaluation_count); ?></td>
                        <td class="column-score">
                            <?php echo $result->avg_score ? number_format($result->avg_score, 2) . '/25' : '-'; ?>
                        </td>
                        <td class="column-actions">
                            <a href="<?php echo admin_url('admin.php?page=mt-candidate-details&candidate_id=' . $result->ID); ?>" class="button button-small">
                                <?php _e('Details', 'mobility-trailblazers'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="8"><?php _e('No results found.', 'mobility-trailblazers'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Summary Statistics -->
    <div class="mt-results-summary" style="margin-top: 30px;">
        <h2><?php _e('Summary Statistics', 'mobility-trailblazers'); ?></h2>
        <?php
        $total_votes = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes");
        $total_evaluations = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores");
        $participating_jury = $wpdb->get_var("SELECT COUNT(DISTINCT jury_member_id) FROM {$wpdb->prefix}mt_votes");
        ?>
        <ul>
            <li><?php printf(__('Total Votes Cast: %d', 'mobility-trailblazers'), $total_votes); ?></li>
            <li><?php printf(__('Total Evaluations Completed: %d', 'mobility-trailblazers'), $total_evaluations); ?></li>
            <li><?php printf(__('Participating Jury Members: %d', 'mobility-trailblazers'), $participating_jury); ?></li>
            <li><?php printf(__('Total Candidates: %d', 'mobility-trailblazers'), count($results)); ?></li>
        </ul>
    </div>
</div> 