<?php
/**
 * Admin Evaluations Page Template
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get evaluation repository
$evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();

// Get filters
$filter_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$filter_jury = isset($_GET['jury_member']) ? intval($_GET['jury_member']) : 0;
$filter_candidate = isset($_GET['candidate']) ? intval($_GET['candidate']) : 0;

// Build query args
$args = [];
if ($filter_status) {
    $args['status'] = $filter_status;
}
if ($filter_jury) {
    $args['jury_member_id'] = $filter_jury;
}
if ($filter_candidate) {
    $args['candidate_id'] = $filter_candidate;
}

// Get evaluations
$evaluations = $evaluation_repo->find_all($args);

// Get jury members and candidates for filters
$jury_members = get_posts([
    'post_type' => 'mt_jury_member',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC'
]);

$candidates = get_posts([
    'post_type' => 'mt_candidate',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC'
]);
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Evaluations', 'mobility-trailblazers'); ?></h1>
    
    <hr class="wp-header-end">
    
    <!-- Filters -->
    <div class="tablenav top">
        <form method="get" action="">
            <input type="hidden" name="page" value="mt-evaluations">
            
            <div class="alignleft actions">
                <select name="status" id="filter-status">
                    <option value=""><?php _e('All Statuses', 'mobility-trailblazers'); ?></option>
                    <option value="draft" <?php selected($filter_status, 'draft'); ?>><?php _e('Draft', 'mobility-trailblazers'); ?></option>
                    <option value="completed" <?php selected($filter_status, 'completed'); ?>><?php _e('Completed', 'mobility-trailblazers'); ?></option>
                </select>
                
                <select name="jury_member" id="filter-jury">
                    <option value=""><?php _e('All Jury Members', 'mobility-trailblazers'); ?></option>
                    <?php foreach ($jury_members as $jury) : ?>
                        <option value="<?php echo esc_attr($jury->ID); ?>" <?php selected($filter_jury, $jury->ID); ?>>
                            <?php echo esc_html($jury->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="candidate" id="filter-candidate">
                    <option value=""><?php _e('All Candidates', 'mobility-trailblazers'); ?></option>
                    <?php foreach ($candidates as $candidate) : ?>
                        <option value="<?php echo esc_attr($candidate->ID); ?>" <?php selected($filter_candidate, $candidate->ID); ?>>
                            <?php echo esc_html($candidate->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'mobility-trailblazers'); ?>">
            </div>
        </form>
        
        <!-- Bulk Actions -->
        <div class="alignleft actions bulkactions">
            <label for="bulk-action-selector-top" class="screen-reader-text"><?php _e('Select bulk action', 'mobility-trailblazers'); ?></label>
            <select name="action" id="bulk-action-selector-top">
                <option value="-1"><?php _e('Bulk Actions', 'mobility-trailblazers'); ?></option>
                <option value="approve"><?php _e('Approve', 'mobility-trailblazers'); ?></option>
                <option value="reject"><?php _e('Reject', 'mobility-trailblazers'); ?></option>
                <option value="reset-to-draft"><?php _e('Reset to Draft', 'mobility-trailblazers'); ?></option>
                <option value="delete"><?php _e('Delete', 'mobility-trailblazers'); ?></option>
            </select>
            <input type="button" id="doaction" class="button action" value="<?php esc_attr_e('Apply', 'mobility-trailblazers'); ?>">
        </div>
    </div>
    
    <!-- Evaluations Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <td id="cb" class="manage-column column-cb check-column">
                    <label class="screen-reader-text" for="cb-select-all-1"><?php _e('Select All', 'mobility-trailblazers'); ?></label>
                    <input id="cb-select-all-1" type="checkbox">
                </td>
                <th><?php _e('ID', 'mobility-trailblazers'); ?></th>
                <th><?php _e('Jury Member', 'mobility-trailblazers'); ?></th>
                <th><?php _e('Candidate', 'mobility-trailblazers'); ?></th>
                <th><?php _e('Total Score', 'mobility-trailblazers'); ?></th>
                <th><?php _e('Status', 'mobility-trailblazers'); ?></th>
                <th><?php _e('Date', 'mobility-trailblazers'); ?></th>
                <th><?php _e('Actions', 'mobility-trailblazers'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($evaluations)) : ?>
                <?php foreach ($evaluations as $evaluation) : 
                    $jury_member = get_post($evaluation->jury_member_id);
                    $candidate = get_post($evaluation->candidate_id);
                ?>
                    <tr>
                        <th scope="row" class="check-column">
                            <label class="screen-reader-text" for="cb-select-<?php echo esc_attr($evaluation->id); ?>">
                                <?php printf(__('Select evaluation %s', 'mobility-trailblazers'), $evaluation->id); ?>
                            </label>
                            <input id="cb-select-<?php echo esc_attr($evaluation->id); ?>" type="checkbox" name="evaluation[]" value="<?php echo esc_attr($evaluation->id); ?>">
                        </th>
                        <td><?php echo esc_html($evaluation->id); ?></td>
                        <td>
                            <?php if ($jury_member) : ?>
                                <a href="<?php echo get_edit_post_link($jury_member->ID); ?>">
                                    <?php echo esc_html($jury_member->post_title); ?>
                                </a>
                            <?php else : ?>
                                <?php _e('Unknown', 'mobility-trailblazers'); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($candidate) : ?>
                                <a href="<?php echo get_edit_post_link($candidate->ID); ?>">
                                    <?php echo esc_html($candidate->post_title); ?>
                                </a>
                            <?php else : ?>
                                <?php _e('Unknown', 'mobility-trailblazers'); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo esc_html($evaluation->total_score); ?></strong> / 50
                        </td>
                        <td>
                            <span class="status-<?php echo esc_attr($evaluation->status); ?>">
                                <?php echo esc_html(ucfirst($evaluation->status)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($evaluation->updated_at))); ?></td>
                        <td>
                            <button class="button view-details" data-evaluation-id="<?php echo esc_attr($evaluation->id); ?>">
                                <?php _e('View Details', 'mobility-trailblazers'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="8"><?php _e('No evaluations found.', 'mobility-trailblazers'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td class="manage-column column-cb check-column">
                    <label class="screen-reader-text" for="cb-select-all-2"><?php _e('Select All', 'mobility-trailblazers'); ?></label>
                    <input id="cb-select-all-2" type="checkbox">
                </td>
                <th><?php _e('ID', 'mobility-trailblazers'); ?></th>
                <th><?php _e('Jury Member', 'mobility-trailblazers'); ?></th>
                <th><?php _e('Candidate', 'mobility-trailblazers'); ?></th>
                <th><?php _e('Total Score', 'mobility-trailblazers'); ?></th>
                <th><?php _e('Status', 'mobility-trailblazers'); ?></th>
                <th><?php _e('Date', 'mobility-trailblazers'); ?></th>
                <th><?php _e('Actions', 'mobility-trailblazers'); ?></th>
            </tr>
        </tfoot>
    </table>
    
    <!-- Bottom Bulk Actions -->
    <div class="tablenav bottom">
        <div class="alignleft actions bulkactions">
            <label for="bulk-action-selector-bottom" class="screen-reader-text"><?php _e('Select bulk action', 'mobility-trailblazers'); ?></label>
            <select name="action2" id="bulk-action-selector-bottom">
                <option value="-1"><?php _e('Bulk Actions', 'mobility-trailblazers'); ?></option>
                <option value="approve"><?php _e('Approve', 'mobility-trailblazers'); ?></option>
                <option value="reject"><?php _e('Reject', 'mobility-trailblazers'); ?></option>
                <option value="reset-to-draft"><?php _e('Reset to Draft', 'mobility-trailblazers'); ?></option>
                <option value="delete"><?php _e('Delete', 'mobility-trailblazers'); ?></option>
            </select>
            <input type="button" id="doaction2" class="button action" value="<?php esc_attr_e('Apply', 'mobility-trailblazers'); ?>">
        </div>
    </div>
</div>

<!-- Evaluation Details Modal -->
<div id="evaluation-details-modal" style="display:none;">
    <div class="evaluation-details-content">
        <!-- Content will be loaded via AJAX -->
    </div>
</div>

<style>
.status-draft { color: #996800; }
.status-completed { color: #46b450; }
.status-approved { color: #46b450; font-weight: bold; }
.status-rejected { color: #dc3232; }
</style>

<script>
jQuery(document).ready(function($) {
    // View details functionality
    $('.view-details').on('click', function() {
        var evaluationId = $(this).data('evaluation-id');
        // TODO: Implement AJAX call to load evaluation details
        alert('View evaluation ' + evaluationId + ' details - To be implemented');
    });
    
    // Select all checkboxes
    $('#cb-select-all-1, #cb-select-all-2').on('click', function() {
        var checked = $(this).prop('checked');
        $('input[name="evaluation[]"]').prop('checked', checked);
        $('#cb-select-all-1, #cb-select-all-2').prop('checked', checked);
    });
    
    // Individual checkbox click
    $('input[name="evaluation[]"]').on('click', function() {
        var allChecked = $('input[name="evaluation[]"]').length === $('input[name="evaluation[]"]:checked').length;
        $('#cb-select-all-1, #cb-select-all-2').prop('checked', allChecked);
    });
    
    // Bulk actions
    $('#doaction, #doaction2').on('click', function() {
        var action = $(this).prev('select').val();
        if (action === '-1') {
            alert('<?php _e('Please select a bulk action', 'mobility-trailblazers'); ?>');
            return;
        }
        
        var selected = [];
        $('input[name="evaluation[]"]:checked').each(function() {
            selected.push($(this).val());
        });
        
        if (selected.length === 0) {
            alert('<?php _e('Please select at least one evaluation', 'mobility-trailblazers'); ?>');
            return;
        }
        
        var confirmMessage = '';
        switch(action) {
            case 'approve':
                confirmMessage = '<?php _e('Are you sure you want to approve the selected evaluations?', 'mobility-trailblazers'); ?>';
                break;
            case 'reject':
                confirmMessage = '<?php _e('Are you sure you want to reject the selected evaluations?', 'mobility-trailblazers'); ?>';
                break;
            case 'reset-to-draft':
                confirmMessage = '<?php _e('Are you sure you want to reset the selected evaluations to draft?', 'mobility-trailblazers'); ?>';
                break;
            case 'delete':
                confirmMessage = '<?php _e('Are you sure you want to delete the selected evaluations? This action cannot be undone.', 'mobility-trailblazers'); ?>';
                break;
        }
        
        if (!confirm(confirmMessage)) {
            return;
        }
        
        // Perform bulk action via AJAX
        $.ajax({
            url: mt_admin.url,
            type: 'POST',
            data: {
                action: 'mt_bulk_evaluation_action',
                bulk_action: action,
                evaluation_ids: selected,
                nonce: mt_admin.nonce
            },
            beforeSend: function() {
                $('#doaction, #doaction2').prop('disabled', true).val('<?php _e('Processing...', 'mobility-trailblazers'); ?>');
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message || '<?php _e('Bulk action completed successfully', 'mobility-trailblazers'); ?>');
                    location.reload();
                } else {
                    alert(response.data || '<?php _e('An error occurred', 'mobility-trailblazers'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('An error occurred. Please try again.', 'mobility-trailblazers'); ?>');
            },
            complete: function() {
                $('#doaction, #doaction2').prop('disabled', false).val('<?php _e('Apply', 'mobility-trailblazers'); ?>');
            }
        });
    });
});
</script> 