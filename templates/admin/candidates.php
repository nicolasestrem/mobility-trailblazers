<?php
/**
 * Candidates Management Template
 *
 * @package MobilityTrailblazers
 * @since 2.0.11
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get candidates
$paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$per_page = 20;

$args = [
    'post_type' => 'mt_candidate',
    'posts_per_page' => $per_page,
    'paged' => $paged,
    'post_status' => ['publish', 'draft'],
    'orderby' => 'title',
    'order' => 'ASC'
];

// Filter by category if specified
if (isset($_GET['category']) && $_GET['category']) {
    $args['tax_query'] = [
        [
            'taxonomy' => 'mt_award_category',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['category'])
        ]
    ];
}

// Filter by status if specified
if (isset($_GET['status']) && $_GET['status']) {
    $args['post_status'] = sanitize_text_field($_GET['status']);
}

$candidates_query = new WP_Query($args);
$total_items = $candidates_query->found_posts;
$total_pages = ceil($total_items / $per_page);

// Get all categories for filter
$categories = get_terms([
    'taxonomy' => 'mt_award_category',
    'hide_empty' => false
]);

// Get evaluation repository for scores
$evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Candidates Management', 'mobility-trailblazers'); ?></h1>
    <a href="<?php echo admin_url('post-new.php?post_type=mt_candidate'); ?>" class="page-title-action"><?php _e('Add New', 'mobility-trailblazers'); ?></a>
    
    <?php if (isset($_GET['message'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($_GET['message']); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" action="">
                <input type="hidden" name="page" value="mt-candidates">
                
                <select name="category" id="filter-by-category">
                    <option value=""><?php _e('All Categories', 'mobility-trailblazers'); ?></option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo esc_attr($category->slug); ?>" <?php selected(isset($_GET['category']) ? $_GET['category'] : '', $category->slug); ?>>
                            <?php echo esc_html($category->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <?php wp_nonce_field('mt_filter_candidates', 'mt_filter_nonce'); ?>
                <select name="status" id="filter-by-status">
                    <option value=""><?php _e('All Statuses', 'mobility-trailblazers'); ?></option>
                    <option value="publish" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'publish'); ?>><?php _e('Published', 'mobility-trailblazers'); ?></option>
                    <option value="draft" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'draft'); ?>><?php _e('Draft', 'mobility-trailblazers'); ?></option>
                </select>
                
                <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'mobility-trailblazers'); ?>">
            </form>
        </div>
        
        <div class="alignleft actions bulkactions">
            <button type="button" class="button" id="bulk-actions-toggle">
                <span class="dashicons dashicons-admin-tools"></span>
                <?php _e('Bulk Actions', 'mobility-trailblazers'); ?>
            </button>
            
            <div id="bulk-actions-container" style="display: none;">
                <select id="bulk-action-selector">
                    <option value=""><?php _e('Select Action', 'mobility-trailblazers'); ?></option>
                    <option value="publish"><?php _e('Publish', 'mobility-trailblazers'); ?></option>
                    <option value="draft"><?php _e('Set to Draft', 'mobility-trailblazers'); ?></option>
                    <option value="trash"><?php _e('Move to Trash', 'mobility-trailblazers'); ?></option>
                    <?php if (current_user_can('delete_posts')): ?>
                        <option value="delete"><?php _e('Delete Permanently', 'mobility-trailblazers'); ?></option>
                    <?php endif; ?>
                    <option value="add_category"><?php _e('Add Category', 'mobility-trailblazers'); ?></option>
                    <option value="remove_category"><?php _e('Remove Category', 'mobility-trailblazers'); ?></option>
                    <option value="export"><?php _e('Export Selected', 'mobility-trailblazers'); ?></option>
                </select>
                
                <select id="bulk-category-selector" style="display: none;">
                    <option value=""><?php _e('Select Category', 'mobility-trailblazers'); ?></option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo esc_attr($category->slug); ?>">
                            <?php echo esc_html($category->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <button type="button" class="button button-primary" id="apply-bulk-action">
                    <?php _e('Apply', 'mobility-trailblazers'); ?>
                </button>
                
                <span class="bulk-selection-info">
                    <span class="displaying-num">0 <?php _e('items selected', 'mobility-trailblazers'); ?></span>
                </span>
            </div>
        </div>
        
        <div class="tablenav-pages">
            <span class="displaying-num"><?php echo sprintf(_n('%s item', '%s items', $total_items, 'mobility-trailblazers'), number_format_i18n($total_items)); ?></span>
            <?php
            echo paginate_links([
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'total' => $total_pages,
                'current' => $paged
            ]);
            ?>
        </div>
    </div>
    
    <!-- Candidates Table -->
    <table class="wp-list-table widefat fixed striped posts">
        <thead>
            <tr>
                <td class="manage-column column-cb check-column">
                    <input type="checkbox" id="cb-select-all-1" style="display: none;">
                </td>
                <th scope="col" class="manage-column column-title column-primary"><?php _e('Name', 'mobility-trailblazers'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Organization', 'mobility-trailblazers'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Categories', 'mobility-trailblazers'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Status', 'mobility-trailblazers'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Avg Score', 'mobility-trailblazers'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Evaluations', 'mobility-trailblazers'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Actions', 'mobility-trailblazers'); ?></th>
            </tr>
        </thead>
        
        <tbody>
            <?php if ($candidates_query->have_posts()): ?>
                <?php while ($candidates_query->have_posts()): $candidates_query->the_post(); ?>
                    <?php
                    $candidate_id = get_the_ID();
                    $organization = get_post_meta($candidate_id, '_mt_organization', true);
                    $categories = wp_get_post_terms($candidate_id, 'mt_award_category', ['fields' => 'names']);
                    $avg_score = $evaluation_repo->get_average_score_for_candidate($candidate_id);
                    $evaluations = $evaluation_repo->get_by_candidate($candidate_id);
                    $eval_count = count($evaluations);
                    ?>
                    <tr>
                        <th scope="row" class="check-column">
                            <input type="checkbox" class="candidate-checkbox" name="candidate[]" value="<?php echo esc_attr($candidate_id); ?>" style="display: none;">
                        </th>
                        <td class="title column-title column-primary">
                            <strong>
                                <a href="<?php echo get_edit_post_link($candidate_id); ?>" class="row-title">
                                    <?php the_title(); ?>
                                </a>
                            </strong>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo get_edit_post_link($candidate_id); ?>"><?php _e('Edit', 'mobility-trailblazers'); ?></a> |
                                </span>
                                <span class="view">
                                    <a href="<?php echo get_permalink($candidate_id); ?>"><?php _e('View', 'mobility-trailblazers'); ?></a> |
                                </span>
                                <span class="trash">
                                    <a href="<?php echo get_delete_post_link($candidate_id); ?>" class="submitdelete"><?php _e('Trash', 'mobility-trailblazers'); ?></a>
                                </span>
                            </div>
                        </td>
                        <td><?php echo esc_html($organization); ?></td>
                        <td><?php echo esc_html(implode(', ', $categories)); ?></td>
                        <td>
                            <?php
                            $status = get_post_status();
                            $status_label = '';
                            switch ($status) {
                                case 'publish':
                                    $status_label = '<span class="status-badge status-publish">' . esc_html__('Published', 'mobility-trailblazers') . '</span>';
                                    break;
                                case 'draft':
                                    $status_label = '<span class="status-badge status-draft">' . esc_html__('Draft', 'mobility-trailblazers') . '</span>';
                                    break;
                                default:
                                    $status_label = '<span class="status-badge">' . ucfirst($status) . '</span>';
                            }
                            echo esc_html($status_label);
                            ?>
                        </td>
                        <td>
                            <?php if ($avg_score > 0): ?>
                                <strong><?php echo number_format($avg_score, 1); ?></strong>/100
                            <?php else: ?>
                                <span class="description">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($eval_count > 0): ?>
                                <a href="<?php echo admin_url('admin.php?page=mt-evaluations&candidate_id=' . $candidate_id); ?>">
                                    <?php echo esc_html($eval_count); ?>
                                </a>
                            <?php else: ?>
                                0
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=mt-assignments&candidate_id=' . $candidate_id); ?>" class="button button-small">
                                <?php _e('View Assignments', 'mobility-trailblazers'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="no-items"><?php _e('No candidates found.', 'mobility-trailblazers'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
        
        <tfoot>
            <tr>
                <td class="manage-column column-cb check-column">
                    <input type="checkbox" id="cb-select-all-2" style="display: none;">
                </td>
                <th scope="col" class="manage-column column-title column-primary"><?php _e('Name', 'mobility-trailblazers'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Organization', 'mobility-trailblazers'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Categories', 'mobility-trailblazers'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Status', 'mobility-trailblazers'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Avg Score', 'mobility-trailblazers'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Evaluations', 'mobility-trailblazers'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Actions', 'mobility-trailblazers'); ?></th>
            </tr>
        </tfoot>
    </table>
    
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <span class="displaying-num"><?php echo sprintf(_n('%s item', '%s items', $total_items, 'mobility-trailblazers'), number_format_i18n($total_items)); ?></span>
            <?php
            echo paginate_links([
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'total' => $total_pages,
                'current' => $paged
            ]);
            ?>
        </div>
    </div>
</div>

<style>
.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}
.status-publish {
    background-color: #d4edda;
    color: #155724;
}
.status-draft {
    background-color: #fff3cd;
    color: #856404;
}
.bulk-selection-info {
    margin-left: 10px;
    font-style: italic;
}
#bulk-actions-container {
    display: inline-block;
    margin-left: 10px;
}
.candidate-checkbox:checked {
    display: block !important;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Bulk operations for candidates
    var bulkMode = false;
    var selectedCandidates = [];
    
    // Toggle bulk mode
    $('#bulk-actions-toggle').on('click', function() {
        bulkMode = !bulkMode;
        
        if (bulkMode) {
            $('#bulk-actions-container').show();
            $('.candidate-checkbox, #cb-select-all-1, #cb-select-all-2').show();
            $(this).addClass('button-primary');
        } else {
            $('#bulk-actions-container').hide();
            $('.candidate-checkbox, #cb-select-all-1, #cb-select-all-2').hide().prop('checked', false);
            $(this).removeClass('button-primary');
            selectedCandidates = [];
            updateSelectionCount();
        }
    });
    
    // Handle select all
    $('#cb-select-all-1, #cb-select-all-2').on('change', function() {
        var isChecked = $(this).prop('checked');
        $('.candidate-checkbox').prop('checked', isChecked);
        $('#cb-select-all-1, #cb-select-all-2').prop('checked', isChecked);
        updateSelectedCandidates();
    });
    
    // Handle individual checkbox
    $('.candidate-checkbox').on('change', function() {
        updateSelectedCandidates();
    });
    
    // Update selected candidates array
    function updateSelectedCandidates() {
        selectedCandidates = [];
        $('.candidate-checkbox:checked').each(function() {
            selectedCandidates.push($(this).val());
        });
        updateSelectionCount();
    }
    
    // Update selection count display
    function updateSelectionCount() {
        $('.bulk-selection-info .displaying-num').text(selectedCandidates.length + ' ' + (selectedCandidates.length === 1 ? 'item selected' : 'items selected'));
    }
    
    // Show/hide category selector based on action
    $('#bulk-action-selector').on('change', function() {
        var action = $(this).val();
        if (action === 'add_category' || action === 'remove_category') {
            $('#bulk-category-selector').show();
        } else {
            $('#bulk-category-selector').hide();
        }
    });
    
    // Apply bulk action
    $('#apply-bulk-action').on('click', function() {
        var action = $('#bulk-action-selector').val();
        var category = $('#bulk-category-selector').val();
        
        if (!action) {
            alert('<?php _e('Please select an action.', 'mobility-trailblazers'); ?>');
            return;
        }
        
        if (selectedCandidates.length === 0) {
            alert('<?php _e('Please select at least one candidate.', 'mobility-trailblazers'); ?>');
            return;
        }
        
        if ((action === 'add_category' || action === 'remove_category') && !category) {
            alert('<?php _e('Please select a category.', 'mobility-trailblazers'); ?>');
            return;
        }
        
        // Confirm destructive actions
        if (action === 'trash' || action === 'delete') {
            var confirmMsg = action === 'delete' ? 
                '<?php _e('Are you sure you want to permanently delete the selected candidates? This cannot be undone.', 'mobility-trailblazers'); ?>' :
                '<?php _e('Are you sure you want to move the selected candidates to trash?', 'mobility-trailblazers'); ?>';
            
            if (!confirm(confirmMsg)) {
                return;
            }
        }
        
        // Handle export separately
        if (action === 'export') {
            var form = $('<form>', {
                method: 'POST',
                action: ajaxurl
            });
            
            form.append($('<input>', {
                type: 'hidden',
                name: 'action',
                value: 'mt_bulk_candidate_action'
            }));
            
            form.append($('<input>', {
                type: 'hidden',
                name: 'bulk_action',
                value: 'export'
            }));
            
            form.append($('<input>', {
                type: 'hidden',
                name: 'nonce',
                value: mt_admin_vars.nonce
            }));
            
            $.each(selectedCandidates, function(i, id) {
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'candidate_ids[]',
                    value: id
                }));
            });
            
            $('body').append(form);
            form.submit();
            return;
        }
        
        // Show loading
        $(this).prop('disabled', true).text('<?php _e('Processing...', 'mobility-trailblazers'); ?>');
        
        // Prepare data
        var data = {
            action: 'mt_bulk_candidate_action',
            bulk_action: action,
            candidate_ids: selectedCandidates,
            nonce: mt_admin_vars.nonce
        };
        
        if (category) {
            data.category = category;
        }
        
        // Send AJAX request
        $.post(ajaxurl, data, function(response) {
            if (response.success) {
                // Show success message
                var message = $('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>');
                $('.wrap h1').after(message);
                
                // Reload page after 2 seconds
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                alert(response.data || '<?php _e('An error occurred.', 'mobility-trailblazers'); ?>');
                $('#apply-bulk-action').prop('disabled', false).text('<?php _e('Apply', 'mobility-trailblazers'); ?>');
            }
        }).fail(function() {
            alert('<?php _e('An error occurred. Please try again.', 'mobility-trailblazers'); ?>');
            $('#apply-bulk-action').prop('disabled', false).text('<?php _e('Apply', 'mobility-trailblazers'); ?>');
        });
    });
});
</script> 