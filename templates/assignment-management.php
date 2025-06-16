<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check user permissions
if (!current_user_can('mt_manage_awards')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Get all jury members
$jury_members = get_users(['role' => 'mt_jury_member']);

// Get all candidates
$candidates = get_posts([
    'post_type' => 'mt_candidate',
    'posts_per_page' => -1,
    'post_status' => 'publish'
]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mt_assignment_nonce'])) {
    if (wp_verify_nonce($_POST['mt_assignment_nonce'], 'mt_assignment_action')) {
        $jury_id = intval($_POST['jury_member']);
        $candidate_ids = isset($_POST['candidates']) ? array_map('intval', $_POST['candidates']) : [];
        
        // Update jury member's assigned candidates
        update_user_meta($jury_id, 'mt_assigned_candidates', $candidate_ids);
        
        // Log the assignment
        $audit_logger = new \MobilityTrailblazers\VoteAuditLogger();
        $audit_logger->log_action(
            'assignment_update',
            sprintf(
                'Updated assignments for jury member %s. Assigned candidates: %s',
                get_user_by('id', $jury_id)->display_name,
                implode(', ', array_map(function($id) {
                    return get_the_title($id);
                }, $candidate_ids))
            )
        );
        
        echo '<div class="notice notice-success"><p>' . __('Assignments updated successfully.') . '</p></div>';
    }
}
?>

<div class="wrap">
    <h1><?php _e('Assignment Management', 'mobility-trailblazers'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('mt_assignment_action', 'mt_assignment_nonce'); ?>
        
        <div class="mt-assignment-form">
            <div class="mt-form-row">
                <label for="jury_member"><?php _e('Select Jury Member:', 'mobility-trailblazers'); ?></label>
                <select name="jury_member" id="jury_member" required>
                    <option value=""><?php _e('-- Select Jury Member --', 'mobility-trailblazers'); ?></option>
                    <?php foreach ($jury_members as $member): ?>
                        <option value="<?php echo esc_attr($member->ID); ?>">
                            <?php echo esc_html($member->display_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mt-form-row">
                <label><?php _e('Select Candidates to Assign:', 'mobility-trailblazers'); ?></label>
                <div class="mt-candidates-grid">
                    <?php foreach ($candidates as $candidate): ?>
                        <div class="mt-candidate-item">
                            <label>
                                <input type="checkbox" 
                                       name="candidates[]" 
                                       value="<?php echo esc_attr($candidate->ID); ?>"
                                       class="mt-candidate-checkbox">
                                <?php echo esc_html($candidate->post_title); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="mt-form-actions">
                <button type="submit" class="button button-primary">
                    <?php _e('Save Assignments', 'mobility-trailblazers'); ?>
                </button>
            </div>
        </div>
    </form>
</div>

<style>
.mt-assignment-form {
    max-width: 1200px;
    margin: 20px 0;
    background: #fff;
    padding: 20px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.mt-form-row {
    margin-bottom: 20px;
}

.mt-form-row label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
}

.mt-form-row select {
    width: 100%;
    max-width: 400px;
}

.mt-candidates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
    margin-top: 10px;
}

.mt-candidate-item {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    border: 1px solid #dee2e6;
}

.mt-candidate-item label {
    display: flex;
    align-items: center;
    margin: 0;
    font-weight: normal;
}

.mt-candidate-item input[type="checkbox"] {
    margin-right: 8px;
}

.mt-form-actions {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #dee2e6;
}

@media screen and (max-width: 782px) {
    .mt-candidates-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Load assigned candidates when jury member is selected
    $('#jury_member').on('change', function() {
        var juryId = $(this).val();
        if (!juryId) return;
        
        // Reset all checkboxes
        $('.mt-candidate-checkbox').prop('checked', false);
        
        // Get assigned candidates for selected jury member
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mt_get_assigned_candidates',
                jury_id: juryId,
                nonce: '<?php echo wp_create_nonce('mt_get_assigned_candidates'); ?>'
            },
            success: function(response) {
                if (response.success && response.data) {
                    response.data.forEach(function(candidateId) {
                        $('input[value="' + candidateId + '"]').prop('checked', true);
                    });
                }
            }
        });
    });
});
</script> 