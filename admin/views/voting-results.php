<?php
/**
 * Voting Results Admin View
 *
 * @package MobilityTrailblazers
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get current user
$current_user = wp_get_current_user();

// Get filter parameters
$filter_category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$filter_jury = isset($_GET['jury_member']) ? intval($_GET['jury_member']) : 0;
$filter_phase = isset($_GET['phase']) ? sanitize_text_field($_GET['phase']) : '';
$filter_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$sort_by = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'total_score';
$sort_order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'desc';

// Get categories
$categories = get_terms(array(
    'taxonomy' => 'mt_category',
    'hide_empty' => false
));

// Get phases
$phases = get_terms(array(
    'taxonomy' => 'mt_phase',
    'hide_empty' => false
));

// Get jury members
$jury_members = get_posts(array(
    'post_type' => 'mt_jury',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC'
));

// Build query
global $wpdb;
$votes_table = $wpdb->prefix . 'mt_votes';
$scores_table = $wpdb->prefix . 'mt_candidate_scores';

// Base query
$query = "
    SELECT DISTINCT
        c.ID as candidate_id,
        c.post_title as candidate_name,
        pm_company.meta_value as company_name,
        pm_location.meta_value as location,
        GROUP_CONCAT(DISTINCT t.name) as categories,
        COUNT(DISTINCT v.jury_member_id) as vote_count,
        AVG(v.total_score) as avg_score,
        MAX(v.total_score) as max_score,
        MIN(v.total_score) as min_score,
        SUM(CASE WHEN v.total_score >= 40 THEN 1 ELSE 0 END) as high_scores
    FROM {$wpdb->posts} c
    LEFT JOIN {$wpdb->postmeta} pm_company ON c.ID = pm_company.post_id AND pm_company.meta_key = '_mt_company_name'
    LEFT JOIN {$wpdb->postmeta} pm_location ON c.ID = pm_location.post_id AND pm_location.meta_key = '_mt_location'
    LEFT JOIN {$wpdb->term_relationships} tr ON c.ID = tr.object_id
    LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'mt_category'
    LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
    LEFT JOIN {$votes_table} v ON c.ID = v.candidate_id AND v.is_active = 1
    WHERE c.post_type = 'mt_candidate' AND c.post_status = 'publish'
";

// Apply filters
$where_conditions = array();

if ($filter_category) {
    $where_conditions[] = $wpdb->prepare("t.slug = %s", $filter_category);
}

if ($filter_jury) {
    $where_conditions[] = $wpdb->prepare("v.jury_member_id = %d", $filter_jury);
}

if ($filter_phase) {
    $phase_candidates = get_posts(array(
        'post_type' => 'mt_candidate',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'mt_phase',
                'field' => 'slug',
                'terms' => $filter_phase
            )
        ),
        'fields' => 'ids'
    ));
    if (!empty($phase_candidates)) {
        $where_conditions[] = "c.ID IN (" . implode(',', $phase_candidates) . ")";
    }
}

if (!empty($where_conditions)) {
    $query .= " AND " . implode(" AND ", $where_conditions);
}

$query .= " GROUP BY c.ID";

// Apply sorting
$allowed_sorts = array('candidate_name', 'company_name', 'vote_count', 'avg_score', 'max_score', 'high_scores');
if (in_array($sort_by, $allowed_sorts)) {
    $query .= " ORDER BY {$sort_by} " . ($sort_order === 'asc' ? 'ASC' : 'DESC');
}

// Execute query
$results = $wpdb->get_results($query);

// Get statistics
$total_candidates = count($results);
$total_evaluations = $wpdb->get_var("SELECT COUNT(*) FROM {$votes_table} WHERE is_active = 1");
$avg_completion = $total_candidates > 0 ? round(($total_evaluations / ($total_candidates * count($jury_members))) * 100, 1) : 0;
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Voting Results', 'mobility-trailblazers'); ?></h1>
    
    <div class="mt-admin-header">
        <div class="mt-stats-row">
            <div class="mt-stat-box">
                <span class="mt-stat-number"><?php echo number_format($total_candidates); ?></span>
                <span class="mt-stat-label"><?php _e('Total Candidates', 'mobility-trailblazers'); ?></span>
            </div>
            <div class="mt-stat-box">
                <span class="mt-stat-number"><?php echo number_format($total_evaluations); ?></span>
                <span class="mt-stat-label"><?php _e('Total Evaluations', 'mobility-trailblazers'); ?></span>
            </div>
            <div class="mt-stat-box">
                <span class="mt-stat-number"><?php echo $avg_completion; ?>%</span>
                <span class="mt-stat-label"><?php _e('Average Completion', 'mobility-trailblazers'); ?></span>
            </div>
            <div class="mt-stat-box">
                <span class="mt-stat-number"><?php echo count($jury_members); ?></span>
                <span class="mt-stat-label"><?php _e('Active Jury Members', 'mobility-trailblazers'); ?></span>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="mt-filters-section">
        <form method="get" action="">
            <input type="hidden" name="page" value="mt-voting-results">
            
            <div class="mt-filter-row">
                <div class="mt-filter-item">
                    <label for="category"><?php _e('Category', 'mobility-trailblazers'); ?></label>
                    <select name="category" id="category">
                        <option value=""><?php _e('All Categories', 'mobility-trailblazers'); ?></option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo esc_attr($category->slug); ?>" <?php selected($filter_category, $category->slug); ?>>
                                <?php echo esc_html($category->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mt-filter-item">
                    <label for="jury_member"><?php _e('Jury Member', 'mobility-trailblazers'); ?></label>
                    <select name="jury_member" id="jury_member">
                        <option value=""><?php _e('All Jury Members', 'mobility-trailblazers'); ?></option>
                        <?php foreach ($jury_members as $jury): ?>
                            <option value="<?php echo esc_attr($jury->ID); ?>" <?php selected($filter_jury, $jury->ID); ?>>
                                <?php echo esc_html($jury->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mt-filter-item">
                    <label for="phase"><?php _e('Phase', 'mobility-trailblazers'); ?></label>
                    <select name="phase" id="phase">
                        <option value=""><?php _e('All Phases', 'mobility-trailblazers'); ?></option>
                        <?php foreach ($phases as $phase): ?>
                            <option value="<?php echo esc_attr($phase->slug); ?>" <?php selected($filter_phase, $phase->slug); ?>>
                                <?php echo esc_html($phase->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mt-filter-item">
                    <label for="sort"><?php _e('Sort By', 'mobility-trailblazers'); ?></label>
                    <select name="sort" id="sort">
                        <option value="avg_score" <?php selected($sort_by, 'avg_score'); ?>><?php _e('Average Score', 'mobility-trailblazers'); ?></option>
                        <option value="vote_count" <?php selected($sort_by, 'vote_count'); ?>><?php _e('Vote Count', 'mobility-trailblazers'); ?></option>
                        <option value="max_score" <?php selected($sort_by, 'max_score'); ?>><?php _e('Highest Score', 'mobility-trailblazers'); ?></option>
                        <option value="candidate_name" <?php selected($sort_by, 'candidate_name'); ?>><?php _e('Candidate Name', 'mobility-trailblazers'); ?></option>
                        <option value="company_name" <?php selected($sort_by, 'company_name'); ?>><?php _e('Company Name', 'mobility-trailblazers'); ?></option>
                    </select>
                </div>
                
                <div class="mt-filter-item">
                    <label for="order"><?php _e('Order', 'mobility-trailblazers'); ?></label>
                    <select name="order" id="order">
                        <option value="desc" <?php selected($sort_order, 'desc'); ?>><?php _e('Descending', 'mobility-trailblazers'); ?></option>
                        <option value="asc" <?php selected($sort_order, 'asc'); ?>><?php _e('Ascending', 'mobility-trailblazers'); ?></option>
                    </select>
                </div>
                
                <div class="mt-filter-actions">
                    <button type="submit" class="button button-primary"><?php _e('Apply Filters', 'mobility-trailblazers'); ?></button>
                    <a href="?page=mt-voting-results" class="button"><?php _e('Reset', 'mobility-trailblazers'); ?></a>
                    <button type="button" class="button" id="export-results"><?php _e('Export Results', 'mobility-trailblazers'); ?></button>
                </div>
            </div>
        </form>
    </div>

    <!-- Results Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Candidate', 'mobility-trailblazers'); ?></th>
                <th><?php _e('Company', 'mobility-trailblazers'); ?></th>
                <th><?php _e('Location', 'mobility-trailblazers'); ?></th>
                <th><?php _e('Categories', 'mobility-trailblazers'); ?></th>
                <th><?php _e('Evaluations', 'mobility-trailblazers'); ?></th>
                <th><?php _e('Avg Score', 'mobility-trailblazers'); ?></th>
                <th><?php _e('Score Range', 'mobility-trailblazers'); ?></th>
                <th><?php _e('High Scores', 'mobility-trailblazers'); ?></th>
                <th><?php _e('Actions', 'mobility-trailblazers'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($results)): ?>
                <tr>
                    <td colspan="9" class="no-items"><?php _e('No voting results found.', 'mobility-trailblazers'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($results as $result): ?>
                    <tr>
                        <td>
                            <strong>
                                <a href="<?php echo get_edit_post_link($result->candidate_id); ?>">
                                    <?php echo esc_html($result->candidate_name); ?>
                                </a>
                            </strong>
                        </td>
                        <td><?php echo esc_html($result->company_name ?: '-'); ?></td>
                        <td><?php echo esc_html($result->location ?: '-'); ?></td>
                        <td><?php echo esc_html($result->categories ?: '-'); ?></td>
                        <td>
                            <span class="mt-evaluation-count">
                                <?php echo intval($result->vote_count); ?> / <?php echo count($jury_members); ?>
                            </span>
                            <?php
                            $completion_rate = count($jury_members) > 0 ? ($result->vote_count / count($jury_members)) * 100 : 0;
                            ?>
                            <div class="mt-progress-bar">
                                <div class="mt-progress-fill" style="width: <?php echo $completion_rate; ?>%"></div>
                            </div>
                        </td>
                        <td>
                            <?php if ($result->avg_score): ?>
                                <strong><?php echo number_format($result->avg_score, 1); ?></strong> / 50
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($result->min_score && $result->max_score): ?>
                                <?php echo number_format($result->min_score, 1); ?> - <?php echo number_format($result->max_score, 1); ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($result->high_scores > 0): ?>
                                <span class="mt-high-score-badge"><?php echo intval($result->high_scores); ?></span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="button button-small view-details" data-candidate="<?php echo $result->candidate_id; ?>">
                                <?php _e('View Details', 'mobility-trailblazers'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Details Modal -->
<div id="mt-details-modal" class="mt-modal" style="display: none;">
    <div class="mt-modal-content">
        <span class="mt-modal-close">&times;</span>
        <h2><?php _e('Evaluation Details', 'mobility-trailblazers'); ?></h2>
        <div id="mt-details-content">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<style>
.mt-admin-header {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.mt-stats-row {
    display: flex;
    gap: 20px;
}

.mt-stat-box {
    flex: 1;
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 4px;
}

.mt-stat-number {
    display: block;
    font-size: 32px;
    font-weight: 600;
    color: #2271b1;
}

.mt-stat-label {
    display: block;
    font-size: 14px;
    color: #646970;
    margin-top: 5px;
}

.mt-filters-section {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ccd0d4;
}

.mt-filter-row {
    display: flex;
    gap: 15px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.mt-filter-item {
    flex: 1;
    min-width: 150px;
}

.mt-filter-item label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.mt-filter-item select {
    width: 100%;
}

.mt-filter-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.mt-evaluation-count {
    font-weight: 600;
}

.mt-progress-bar {
    width: 100%;
    height: 8px;
    background: #f0f0f1;
    border-radius: 4px;
    margin-top: 5px;
    overflow: hidden;
}

.mt-progress-fill {
    height: 100%;
    background: #2271b1;
    transition: width 0.3s ease;
}

.mt-high-score-badge {
    background: #00a32a;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.mt-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.mt-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 800px;
    max-height: 80vh;
    overflow-y: auto;
    position: relative;
}

.mt-modal-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.mt-modal-close:hover,
.mt-modal-close:focus {
    color: black;
}

.no-items {
    text-align: center;
    padding: 40px !important;
    color: #646970;
}
</style>

<script>
jQuery(document).ready(function($) {
    // View details
    $('.view-details').on('click', function() {
        var candidateId = $(this).data('candidate');
        var modal = $('#mt-details-modal');
        var content = $('#mt-details-content');
        
        content.html('<p>Loading...</p>');
        modal.show();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mt_get_candidate_details',
                candidate_id: candidateId,
                nonce: '<?php echo wp_create_nonce('mt_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    content.html(response.data.html);
                } else {
                    content.html('<p>Error loading details.</p>');
                }
            }
        });
    });
    
    // Close modal
    $('.mt-modal-close, .mt-modal').on('click', function(e) {
        if (e.target === this) {
            $('.mt-modal').hide();
        }
    });
    
    // Export results
    $('#export-results').on('click', function() {
        var params = new URLSearchParams(window.location.search);
        params.set('action', 'mt_export_voting_results');
        params.set('nonce', '<?php echo wp_create_nonce('mt_export_nonce'); ?>');
        
        window.location.href = ajaxurl + '?' + params.toString();
    });
});
</script> 