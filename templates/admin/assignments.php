<?php
/**
 * Admin Assignments Page Template - Enhanced Version
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get assignment repository and service
$assignment_repo = new \MobilityTrailblazers\Repositories\MT_Assignment_Repository();
$assignment_service = new \MobilityTrailblazers\Services\MT_Assignment_Service();
$evaluation_service = new \MobilityTrailblazers\Services\MT_Evaluation_Service();

// Get statistics
$total_candidates = wp_count_posts('mt_candidate')->publish;
$total_jury = wp_count_posts('mt_jury_member')->publish;
$total_assignments = $assignment_repo->count();
$average_per_jury = $total_jury > 0 ? round($total_assignments / $total_jury, 1) : 0;

// Get all candidates and jury members
$candidates = get_posts(array(
    'post_type' => 'mt_candidate',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
    'post_status' => 'publish'
));

$jury_members = get_posts(array(
    'post_type' => 'mt_jury_member',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
    'post_status' => 'publish'
));

// Get assignments grouped by jury member
$assignments_by_jury = array();
$all_assignments = $assignment_repo->find_all();
foreach ($all_assignments as $assignment) {
    if (!isset($assignments_by_jury[$assignment->jury_member_id])) {
        $assignments_by_jury[$assignment->jury_member_id] = array();
    }
    $assignments_by_jury[$assignment->jury_member_id][] = $assignment;
}

// Handle form submissions
if (isset($_POST['action']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'mt_assignments')) {
    if ($_POST['action'] === 'auto_assign') {
        $method = sanitize_text_field($_POST['method']);
        $candidates_per_jury = intval($_POST['candidates_per_jury']);
        
        $result = $assignment_service->auto_assign($method, $candidates_per_jury);
        
        if ($result) {
            echo '<div class="notice notice-success"><p>' . __('Auto-assignment completed successfully!', 'mobility-trailblazers') . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . __('Auto-assignment failed. Please check the logs.', 'mobility-trailblazers') . '</p></div>';
        }
    }
}
?>

<!-- Debug Section -->
<div style="background: #f0f0f0; padding: 10px; margin: 20px 0; border: 1px solid #ccc;">
    <h3>Debug Information</h3>
    <p>Page: <?php echo esc_html($_GET['page'] ?? 'unknown'); ?></p>
    <p>Current User Can Manage: <?php echo current_user_can('manage_options') ? 'Yes' : 'No'; ?></p>
    <p>AJAX URL: <?php echo admin_url('admin-ajax.php'); ?></p>
    <p>Nonce: <?php echo wp_create_nonce('mt_admin_nonce'); ?></p>
    <button onclick="testAjax()">Test AJAX</button>
</div>

<script>
function testAjax() {
    console.log('Testing AJAX...');
    jQuery.ajax({
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        type: 'POST',
        data: {
            action: 'mt_auto_assign',
            nonce: '<?php echo wp_create_nonce('mt_admin_nonce'); ?>',
            method: 'balanced',
            candidates_per_jury: 5
        },
        success: function(response) {
            console.log('AJAX Success:', response);
            alert('AJAX Success: ' + JSON.stringify(response));
        },
        error: function(xhr, status, error) {
            console.log('AJAX Error:', {xhr, status, error});
            alert('AJAX Error: ' + xhr.responseText);
        }
    });
}
</script>

<div class="wrap">
    <h1><?php _e('Assignment Management', 'mobility-trailblazers'); ?></h1>
    <input type="hidden" id="mt_admin_nonce" value="<?php echo wp_create_nonce('mt_admin_nonce'); ?>" />
    
    <!-- Statistics Dashboard -->
    <div class="mt-stats-dashboard">
        <div class="mt-stat-card">
            <div class="mt-stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="mt-stat-content">
                <h3><?php _e('Total Candidates', 'mobility-trailblazers'); ?></h3>
                <p class="mt-stat-number"><?php echo esc_html($total_candidates); ?></p>
            </div>
        </div>
        <div class="mt-stat-card">
            <div class="mt-stat-icon">
                <span class="dashicons dashicons-businessperson"></span>
            </div>
            <div class="mt-stat-content">
                <h3><?php _e('Jury Members', 'mobility-trailblazers'); ?></h3>
                <p class="mt-stat-number"><?php echo esc_html($total_jury); ?></p>
            </div>
        </div>
        <div class="mt-stat-card">
            <div class="mt-stat-icon">
                <span class="dashicons dashicons-admin-links"></span>
            </div>
            <div class="mt-stat-content">
                <h3><?php _e('Total Assignments', 'mobility-trailblazers'); ?></h3>
                <p class="mt-stat-number"><?php echo esc_html($total_assignments); ?></p>
            </div>
        </div>
        <div class="mt-stat-card">
            <div class="mt-stat-icon">
                <span class="dashicons dashicons-chart-pie"></span>
            </div>
            <div class="mt-stat-content">
                <h3><?php _e('Avg. per Jury', 'mobility-trailblazers'); ?></h3>
                <p class="mt-stat-number"><?php echo esc_html($average_per_jury); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="mt-action-bar">
        <button id="mt-auto-assign-btn" class="button button-primary">
            <span class="dashicons dashicons-randomize"></span>
            <?php _e('Auto-Assign', 'mobility-trailblazers'); ?>
        </button>
        <button id="mt-manual-assign-btn" class="button">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php _e('Manual Assignment', 'mobility-trailblazers'); ?>
        </button>
        <button id="mt-bulk-actions-btn" class="button">
            <span class="dashicons dashicons-admin-generic"></span>
            <?php _e('Bulk Actions', 'mobility-trailblazers'); ?>
        </button>
        <button id="mt-export-btn" class="button">
            <span class="dashicons dashicons-download"></span>
            <?php _e('Export', 'mobility-trailblazers'); ?>
        </button>
        <?php if (current_user_can('manage_options')) : ?>
        <button id="mt-clear-all-btn" class="button button-link-delete">
            <span class="dashicons dashicons-trash"></span>
            <?php _e('Clear All', 'mobility-trailblazers'); ?>
        </button>
        <?php endif; ?>
    </div>
    
    <!-- Search and Filters -->
    <div class="mt-filters">
        <div class="mt-search-box">
            <input type="text" id="mt-assignment-search" placeholder="<?php esc_attr_e('Search assignments...', 'mobility-trailblazers'); ?>">
        </div>
        <div class="mt-filter-group">
            <select id="mt-filter-jury">
                <option value=""><?php _e('All Jury Members', 'mobility-trailblazers'); ?></option>
                <?php foreach ($jury_members as $jury) : ?>
                    <option value="<?php echo esc_attr($jury->ID); ?>"><?php echo esc_html($jury->post_title); ?></option>
                <?php endforeach; ?>
            </select>
            <select id="mt-filter-status">
                <option value=""><?php _e('All Statuses', 'mobility-trailblazers'); ?></option>
                <option value="pending"><?php _e('Pending', 'mobility-trailblazers'); ?></option>
                <option value="completed"><?php _e('Completed', 'mobility-trailblazers'); ?></option>
            </select>
        </div>
    </div>
    
    <!-- Bulk Actions Dropdown (Hidden by default) -->
    <div id="mt-bulk-actions-container" class="mt-bulk-actions" style="display: none;">
        <select id="mt-bulk-action-select">
            <option value=""><?php _e('Select Bulk Action', 'mobility-trailblazers'); ?></option>
            <option value="remove"><?php _e('Remove Selected Assignments', 'mobility-trailblazers'); ?></option>
            <option value="reassign"><?php _e('Reassign to Another Jury Member', 'mobility-trailblazers'); ?></option>
            <option value="export"><?php _e('Export Selected', 'mobility-trailblazers'); ?></option>
        </select>
        <button id="mt-apply-bulk-action" class="button"><?php _e('Apply', 'mobility-trailblazers'); ?></button>
        <button id="mt-cancel-bulk-action" class="button"><?php _e('Cancel', 'mobility-trailblazers'); ?></button>
    </div>
    
    <!-- Assignments Table -->
    <table class="wp-list-table widefat fixed striped mt-assignments-table">
        <thead>
            <tr>
                <td class="check-column" style="display: none;">
                    <input type="checkbox" id="mt-select-all-assignments">
                </td>
                <th><?php _e('Jury Member', 'mobility-trailblazers'); ?></th>
                <th><?php _e('Candidate', 'mobility-trailblazers'); ?></th>
                <th><?php _e('Category', 'mobility-trailblazers'); ?></th>
                <th><?php _e('Assigned', 'mobility-trailblazers'); ?></th>
                <th><?php _e('Status', 'mobility-trailblazers'); ?></th>
                <th><?php _e('Progress', 'mobility-trailblazers'); ?></th>
                <th><?php _e('Actions', 'mobility-trailblazers'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($assignments)) : ?>
                <?php foreach ($assignments as $assignment) : 
                    $jury = get_post($assignment->jury_member_id);
                    $candidate = get_post($assignment->candidate_id);
                    
                    if (!$jury || !$candidate) continue;
                    
                    // Get evaluation status
                    $evaluation = $evaluation_repo->find_by_jury_and_candidate(
                        $assignment->jury_member_id,
                        $assignment->candidate_id
                    );
                    
                    $status = $evaluation ? $evaluation->status : 'pending';
                    $progress = $evaluation && $evaluation->status === 'completed' ? 100 : ($evaluation ? 50 : 0);
                    
                    // Get category
                    $categories = wp_get_post_terms($candidate->ID, 'mt_award_category');
                    $category_name = !empty($categories) ? $categories[0]->name : __('Uncategorized', 'mobility-trailblazers');
                ?>
                    <tr data-assignment-id="<?php echo esc_attr($assignment->id); ?>">
                        <td class="check-column" style="display: none;">
                            <input type="checkbox" class="mt-assignment-checkbox" value="<?php echo esc_attr($assignment->id); ?>" 
                                   data-jury-id="<?php echo esc_attr($assignment->jury_member_id); ?>"
                                   data-candidate-id="<?php echo esc_attr($assignment->candidate_id); ?>">
                        </td>
                        <td>
                            <strong><?php echo esc_html($jury->post_title); ?></strong>
                            <?php
                            $user_id = get_post_meta($jury->ID, '_mt_user_id', true);
                            if ($user_id) {
                                $user = get_user_by('ID', $user_id);
                                if ($user) {
                                    echo '<br><small>' . esc_html($user->user_email) . '</small>';
                                }
                            }
                            ?>
                        </td>
                        <td>
                            <strong><?php echo esc_html($candidate->post_title); ?></strong>
                            <?php
                            $organization = get_post_meta($candidate->ID, '_mt_organization', true);
                            if ($organization) {
                                echo '<br><small>' . esc_html($organization) . '</small>';
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html($category_name); ?></td>
                        <td>
                            <?php 
                            $assigned_date = !empty($assignment->assigned_at) 
                                ? date_i18n(get_option('date_format'), strtotime($assignment->assigned_at))
                                : __('N/A', 'mobility-trailblazers');
                            echo esc_html($assigned_date);
                            ?>
                        </td>
                        <td>
                            <span class="mt-status mt-status-<?php echo esc_attr($status); ?>">
                                <?php echo esc_html(ucfirst($status)); ?>
                            </span>
                        </td>
                        <td>
                            <div class="mt-progress-bar">
                                <div class="mt-progress-fill" style="width: <?php echo esc_attr($progress); ?>%"></div>
                            </div>
                            <span class="mt-progress-text"><?php echo esc_html($progress); ?>%</span>
                        </td>
                        <td>
                            <button class="button button-small mt-remove-assignment" 
                                    data-assignment-id="<?php echo esc_attr($assignment->id); ?>"
                                    data-jury="<?php echo esc_attr($jury->post_title); ?>"
                                    data-candidate="<?php echo esc_attr($candidate->post_title); ?>">
                                <?php _e('Remove', 'mobility-trailblazers'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="8" class="no-items"><?php _e('No assignments found.', 'mobility-trailblazers'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Auto-Assignment Modal -->
<div id="mt-auto-assign-modal" class="mt-modal" style="display: none;">
    <div class="mt-modal-content">
        <h2><?php _e('Auto-Assignment Configuration', 'mobility-trailblazers'); ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field('mt_assignments'); ?>
            <input type="hidden" name="action" value="auto_assign">
            
            <div class="mt-form-group">
                <label for="assignment_method"><?php _e('Assignment Method', 'mobility-trailblazers'); ?></label>
                <select name="method" id="assignment_method" class="widefat">
                    <option value="balanced"><?php _e('Balanced - Distributes candidates evenly in order', 'mobility-trailblazers'); ?></option>
                    <option value="random"><?php _e('Random - Randomly assigns candidates to jury members', 'mobility-trailblazers'); ?></option>
                </select>
                <p class="description"><?php _e('Balanced: First X candidates distributed round-robin. Random: Random selection from all candidates.', 'mobility-trailblazers'); ?></p>
            </div>
            
            <div class="mt-form-group">
                <label for="candidates_per_jury"><?php _e('Candidates per Jury Member', 'mobility-trailblazers'); ?></label>
                <input type="number" name="candidates_per_jury" id="candidates_per_jury" value="5" min="1" max="20" class="widefat">
                <p class="description"><?php _e('Each jury member will evaluate this many candidates.', 'mobility-trailblazers'); ?></p>
            </div>
            
            <div class="mt-modal-actions">
                <button type="submit" class="button button-primary"><?php _e('Run Auto-Assignment', 'mobility-trailblazers'); ?></button>
                <button type="button" class="button mt-modal-close"><?php _e('Cancel', 'mobility-trailblazers'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Manual Assignment Modal -->
<div id="mt-manual-assign-modal" class="mt-modal" style="display: none;">
    <div class="mt-modal-content">
        <h2><?php _e('Manual Assignment', 'mobility-trailblazers'); ?></h2>
        <form id="mt-manual-assignment-form">
            <div class="mt-form-group">
                <label for="manual_jury_member"><?php _e('Jury Member', 'mobility-trailblazers'); ?></label>
                <select name="jury_member_id" id="manual_jury_member" class="widefat" required>
                    <option value=""><?php _e('Select Jury Member', 'mobility-trailblazers'); ?></option>
                    <?php foreach ($jury_members as $jury) : ?>
                    <option value="<?php echo esc_attr($jury->ID); ?>">
                        <?php echo esc_html($jury->post_title); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mt-form-group">
                <label for="manual_candidates"><?php _e('Select Candidates', 'mobility-trailblazers'); ?></label>
                <div class="mt-candidates-checklist">
                    <?php foreach ($candidates as $candidate) : ?>
                    <label class="mt-candidate-checkbox">
                        <input type="checkbox" name="candidate_ids[]" value="<?php echo esc_attr($candidate->ID); ?>">
                        <?php echo esc_html($candidate->post_title); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="mt-modal-actions">
                <button type="submit" class="button button-primary"><?php _e('Assign Selected', 'mobility-trailblazers'); ?></button>
                <button type="button" class="button mt-modal-close"><?php _e('Cancel', 'mobility-trailblazers'); ?></button>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    console.log('Inline script running...');
    
    // Direct event binding as fallback
    $(document).on('click', '#mt-auto-assign-btn', function(e) {
        e.preventDefault();
        console.log('Fallback handler: Auto-assign clicked');
        $('#mt-auto-assign-modal').show();
    });
    
    $(document).on('click', '.mt-modal-close', function(e) {
        e.preventDefault();
        console.log('Fallback handler: Close modal clicked');
        $('.mt-modal').hide();
    });
    
    // Test if mt_admin exists
    if (typeof mt_admin !== 'undefined') {
        console.log('mt_admin is available:', mt_admin);
    } else {
        console.error('mt_admin is NOT available!');
    }
});
</script>