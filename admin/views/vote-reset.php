<?php
/**
 * Vote Reset Management View
 *
 * @package MobilityTrailblazers
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check permissions
if (!current_user_can('mt_manage_voting')) {
    wp_die(__('You do not have permission to access this page.', 'mobility-trailblazers'));
}

global $wpdb;

// Initialize vote reset manager
$reset_manager = class_exists('MT_Vote_Reset_Manager') ? new MT_Vote_Reset_Manager() : null;

// Get statistics
$total_votes = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes WHERE is_active = 1");
$total_evaluations = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores WHERE is_active = 1");
$total_candidates = wp_count_posts('mt_candidate');
$candidate_count = (is_object($total_candidates) && isset($total_candidates->publish)) ? $total_candidates->publish : 0;
$total_jury = wp_count_posts('mt_jury');
$jury_count = (is_object($total_jury) && isset($total_jury->publish)) ? $total_jury->publish : 0;

// Get recent reset activity
$recent_resets = $wpdb->get_results("
    SELECT * FROM {$wpdb->prefix}vote_reset_logs 
    ORDER BY reset_timestamp DESC 
    LIMIT 10
");

// Get candidates with vote counts
$candidates_with_votes = $wpdb->get_results("
    SELECT 
        c.ID,
        c.post_title as candidate_name,
        COUNT(DISTINCT v.jury_member_id) as vote_count,
        COUNT(DISTINCT s.jury_member_id) as evaluation_count
    FROM {$wpdb->posts} c
    LEFT JOIN {$wpdb->prefix}mt_votes v ON c.ID = v.candidate_id AND v.is_active = 1
    LEFT JOIN {$wpdb->prefix}mt_candidate_scores s ON c.ID = s.candidate_id AND s.is_active = 1
    WHERE c.post_type = 'mt_candidate' 
    AND c.post_status = 'publish'
    GROUP BY c.ID
    ORDER BY c.post_title
");

// Get jury members with vote counts
$jury_with_votes = $wpdb->get_results("
    SELECT 
        j.ID,
        j.post_title as jury_name,
        COUNT(DISTINCT v.candidate_id) as votes_cast,
        COUNT(DISTINCT s.candidate_id) as evaluations_completed
    FROM {$wpdb->posts} j
    LEFT JOIN {$wpdb->prefix}mt_votes v ON j.ID = v.jury_member_id AND v.is_active = 1
    LEFT JOIN {$wpdb->prefix}mt_candidate_scores s ON j.ID = s.jury_member_id AND s.is_active = 1
    WHERE j.post_type = 'mt_jury' 
    AND j.post_status = 'publish'
    GROUP BY j.ID
    ORDER BY j.post_title
");
?>

<div class="wrap">
    <h1><?php _e('Vote Reset Management', 'mobility-trailblazers'); ?></h1>
    
    <?php if (!$reset_manager): ?>
        <div class="notice notice-error">
            <p><?php _e('Vote Reset Manager is not available. Please check if the required files are present.', 'mobility-trailblazers'); ?></p>
        </div>
        <?php return; ?>
    <?php endif; ?>
    
    <!-- Statistics Overview -->
    <div class="mt-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="mt-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h3 style="margin: 0 0 10px 0; color: #1d2327;"><?php echo number_format($total_votes); ?></h3>
            <p style="margin: 0; color: #646970;"><?php _e('Active Votes', 'mobility-trailblazers'); ?></p>
        </div>
        <div class="mt-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h3 style="margin: 0 0 10px 0; color: #1d2327;"><?php echo number_format($total_evaluations); ?></h3>
            <p style="margin: 0; color: #646970;"><?php _e('Active Evaluations', 'mobility-trailblazers'); ?></p>
        </div>
        <div class="mt-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h3 style="margin: 0 0 10px 0; color: #1d2327;"><?php echo $candidate_count; ?></h3>
            <p style="margin: 0; color: #646970;"><?php _e('Candidates', 'mobility-trailblazers'); ?></p>
        </div>
        <div class="mt-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h3 style="margin: 0 0 10px 0; color: #1d2327;"><?php echo $jury_count; ?></h3>
            <p style="margin: 0; color: #646970;"><?php _e('Jury Members', 'mobility-trailblazers'); ?></p>
        </div>
    </div>
    
    <!-- Reset Actions -->
    <div class="mt-reset-actions" style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
        
        <!-- Individual Reset -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle"><?php _e('Individual Vote Reset', 'mobility-trailblazers'); ?></h2>
            </div>
            <div class="inside">
                <form id="mt-individual-reset-form">
                    <?php wp_nonce_field('mt_individual_reset', 'mt_individual_reset_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="reset-candidate"><?php _e('Candidate', 'mobility-trailblazers'); ?></label>
                            </th>
                            <td>
                                <select id="reset-candidate" name="candidate_id" required>
                                    <option value=""><?php _e('Select Candidate', 'mobility-trailblazers'); ?></option>
                                    <?php foreach ($candidates_with_votes as $candidate): ?>
                                        <option value="<?php echo $candidate->ID; ?>">
                                            <?php echo esc_html($candidate->candidate_name); ?> 
                                            (<?php printf(__('%d votes, %d evaluations', 'mobility-trailblazers'), $candidate->vote_count, $candidate->evaluation_count); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="reset-jury"><?php _e('Jury Member', 'mobility-trailblazers'); ?></label>
                            </th>
                            <td>
                                <select id="reset-jury" name="jury_member_id" required>
                                    <option value=""><?php _e('Select Jury Member', 'mobility-trailblazers'); ?></option>
                                    <?php foreach ($jury_with_votes as $jury): ?>
                                        <option value="<?php echo $jury->ID; ?>">
                                            <?php echo esc_html($jury->jury_name); ?> 
                                            (<?php printf(__('%d votes cast', 'mobility-trailblazers'), $jury->votes_cast); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="reset-reason"><?php _e('Reason', 'mobility-trailblazers'); ?></label>
                            </th>
                            <td>
                                <textarea id="reset-reason" name="reason" rows="3" class="large-text" 
                                          placeholder="<?php esc_attr_e('Optional reason for this reset...', 'mobility-trailblazers'); ?>"></textarea>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary">
                            <?php _e('Reset Individual Vote', 'mobility-trailblazers'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
        
        <!-- Bulk Reset Options -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle"><?php _e('Bulk Reset Options', 'mobility-trailblazers'); ?></h2>
            </div>
            <div class="inside">
                
                <!-- Reset All Votes for Candidate -->
                <div class="mt-bulk-option" style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #ddd;">
                    <h4><?php _e('Reset All Votes for Candidate', 'mobility-trailblazers'); ?></h4>
                    <form id="mt-candidate-reset-form">
                        <?php wp_nonce_field('mt_candidate_reset', 'mt_candidate_reset_nonce'); ?>
                        <p>
                            <select name="candidate_id" required style="width: 100%; margin-bottom: 10px;">
                                <option value=""><?php _e('Select Candidate', 'mobility-trailblazers'); ?></option>
                                <?php foreach ($candidates_with_votes as $candidate): ?>
                                    <option value="<?php echo $candidate->ID; ?>">
                                        <?php echo esc_html($candidate->candidate_name); ?> 
                                        (<?php printf(__('%d votes', 'mobility-trailblazers'), $candidate->vote_count); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                        <p>
                            <button type="submit" class="button button-secondary mt-bulk-reset-btn" 
                                    data-confirm="<?php esc_attr_e('Are you sure you want to reset ALL votes for this candidate?', 'mobility-trailblazers'); ?>">
                                <?php _e('Reset Candidate Votes', 'mobility-trailblazers'); ?>
                            </button>
                        </p>
                    </form>
                </div>
                
                <!-- Reset All Votes by Jury Member -->
                <div class="mt-bulk-option" style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #ddd;">
                    <h4><?php _e('Reset All Votes by Jury Member', 'mobility-trailblazers'); ?></h4>
                    <form id="mt-jury-reset-form">
                        <?php wp_nonce_field('mt_jury_reset', 'mt_jury_reset_nonce'); ?>
                        <p>
                            <select name="jury_member_id" required style="width: 100%; margin-bottom: 10px;">
                                <option value=""><?php _e('Select Jury Member', 'mobility-trailblazers'); ?></option>
                                <?php foreach ($jury_with_votes as $jury): ?>
                                    <option value="<?php echo $jury->ID; ?>">
                                        <?php echo esc_html($jury->jury_name); ?> 
                                        (<?php printf(__('%d votes', 'mobility-trailblazers'), $jury->votes_cast); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                        <p>
                            <button type="submit" class="button button-secondary mt-bulk-reset-btn" 
                                    data-confirm="<?php esc_attr_e('Are you sure you want to reset ALL votes by this jury member?', 'mobility-trailblazers'); ?>">
                                <?php _e('Reset Jury Member Votes', 'mobility-trailblazers'); ?>
                            </button>
                        </p>
                    </form>
                </div>
                
                <!-- Phase Transition Reset -->
                <div class="mt-bulk-option" style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #ddd;">
                    <h4><?php _e('Phase Transition Reset', 'mobility-trailblazers'); ?></h4>
                    <form id="mt-phase-reset-form">
                        <?php wp_nonce_field('mt_phase_reset', 'mt_phase_reset_nonce'); ?>
                        <p>
                            <label>
                                <input type="checkbox" name="send_notifications" value="1" checked>
                                <?php _e('Send email notifications to jury members', 'mobility-trailblazers'); ?>
                            </label>
                        </p>
                        <p>
                            <button type="submit" class="button button-secondary mt-bulk-reset-btn" 
                                    data-confirm="<?php esc_attr_e('This will reset all votes for phase transition. Continue?', 'mobility-trailblazers'); ?>">
                                <?php _e('Phase Transition Reset', 'mobility-trailblazers'); ?>
                            </button>
                        </p>
                    </form>
                </div>
                
                <!-- Full System Reset -->
                <div class="mt-bulk-option">
                    <h4 style="color: #d63638;"><?php _e('Full System Reset', 'mobility-trailblazers'); ?></h4>
                    <p style="color: #646970; font-style: italic;">
                        <?php _e('This will reset ALL votes and evaluations. Use with extreme caution!', 'mobility-trailblazers'); ?>
                    </p>
                    <form id="mt-full-reset-form">
                        <?php wp_nonce_field('mt_full_reset', 'mt_full_reset_nonce'); ?>
                        <p>
                            <label>
                                <input type="checkbox" name="confirm_full_reset" value="1" required>
                                <?php _e('I understand this will delete ALL voting data', 'mobility-trailblazers'); ?>
                            </label>
                        </p>
                        <p>
                            <label>
                                <input type="checkbox" name="send_notifications" value="1" checked>
                                <?php _e('Send email notifications to jury members', 'mobility-trailblazers'); ?>
                            </label>
                        </p>
                        <p>
                            <button type="submit" class="button button-primary button-danger mt-bulk-reset-btn" 
                                    style="background: #d63638; border-color: #d63638;"
                                    data-confirm="<?php esc_attr_e('WARNING: This will permanently reset ALL votes and evaluations. Are you absolutely sure?', 'mobility-trailblazers'); ?>">
                                <?php _e('FULL SYSTEM RESET', 'mobility-trailblazers'); ?>
                            </button>
                        </p>
                    </form>
                </div>
                
            </div>
        </div>
    </div>
    
    <!-- Recent Reset Activity -->
    <div class="postbox">
        <div class="postbox-header">
            <h2 class="hndle"><?php _e('Recent Reset Activity', 'mobility-trailblazers'); ?></h2>
        </div>
        <div class="inside">
            <?php if (empty($recent_resets)): ?>
                <p><?php _e('No recent reset activity.', 'mobility-trailblazers'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Date', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Type', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Initiated By', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Affected', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Votes Reset', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Reason', 'mobility-trailblazers'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_resets as $reset): ?>
                            <tr>
                                <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($reset->reset_timestamp)); ?></td>
                                <td>
                                    <span class="mt-reset-type mt-reset-<?php echo esc_attr($reset->reset_type); ?>">
                                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $reset->reset_type))); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $user = get_user_by('id', $reset->initiated_by);
                                    echo $user ? esc_html($user->display_name) : __('Unknown', 'mobility-trailblazers');
                                    ?>
                                </td>
                                <td>
                                    <?php if ($reset->affected_candidate_id): ?>
                                        <?php echo esc_html(get_the_title($reset->affected_candidate_id)); ?>
                                    <?php endif; ?>
                                    <?php if ($reset->affected_user_id): ?>
                                        <?php 
                                        $affected_user = get_user_by('id', $reset->affected_user_id);
                                        echo $affected_user ? esc_html($affected_user->display_name) : '';
                                        ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($reset->votes_affected); ?></td>
                                <td><?php echo esc_html($reset->reset_reason ?: '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Export/Backup Options -->
    <div class="postbox">
        <div class="postbox-header">
            <h2 class="hndle"><?php _e('Backup & Export', 'mobility-trailblazers'); ?></h2>
        </div>
        <div class="inside">
            <p><?php _e('Before performing bulk resets, consider creating a backup of current voting data.', 'mobility-trailblazers'); ?></p>
            <p>
                <a href="<?php echo wp_nonce_url(admin_url('admin-ajax.php?action=mt_export_votes'), 'mt_export_nonce'); ?>" 
                   class="button button-secondary">
                    <?php _e('Export Current Votes', 'mobility-trailblazers'); ?>
                </a>
                <a href="<?php echo wp_nonce_url(admin_url('admin-ajax.php?action=mt_export_evaluations'), 'mt_export_nonce'); ?>" 
                   class="button button-secondary">
                    <?php _e('Export Current Evaluations', 'mobility-trailblazers'); ?>
                </a>
                <button type="button" id="mt-create-backup" class="button button-secondary">
                    <?php _e('Create Full Backup', 'mobility-trailblazers'); ?>
                </button>
            </p>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="mt-loading-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 4px;">
        <p><?php _e('Processing...', 'mobility-trailblazers'); ?></p>
        <div class="spinner is-active"></div>
    </div>
</div>

<style>
.mt-reset-type {
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}
.mt-reset-individual { background: #e1f5fe; color: #01579b; }
.mt-reset-bulk { background: #fff3e0; color: #e65100; }
.mt-reset-phase { background: #f3e5f5; color: #4a148c; }
.mt-reset-full { background: #ffebee; color: #b71c1c; }
</style>

<script>
jQuery(document).ready(function($) {
    // Handle individual reset
    $('#mt-individual-reset-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=mt_reset_individual_vote';
        
        showLoading();
        
        $.post(ajaxurl, formData, function(response) {
            hideLoading();
            if (response.success) {
                alert('<?php esc_js_e('Vote reset successfully!', 'mobility-trailblazers'); ?>');
                location.reload();
            } else {
                alert('<?php esc_js_e('Error: ', 'mobility-trailblazers'); ?>' + response.data);
            }
        });
    });
    
    // Handle bulk resets
    $('.mt-bulk-reset-btn').on('click', function(e) {
        e.preventDefault();
        
        var confirmMessage = $(this).data('confirm');
        if (!confirm(confirmMessage)) {
            return;
        }
        
        var form = $(this).closest('form');
        var formData = form.serialize();
        
        // Determine action based on form ID
        if (form.attr('id') === 'mt-candidate-reset-form') {
            formData += '&action=mt_reset_candidate_votes';
        } else if (form.attr('id') === 'mt-jury-reset-form') {
            formData += '&action=mt_reset_jury_votes';
        } else if (form.attr('id') === 'mt-phase-reset-form') {
            formData += '&action=mt_reset_phase_transition';
        } else if (form.attr('id') === 'mt-full-reset-form') {
            formData += '&action=mt_reset_full_system';
        }
        
        console.log('Form ID:', form.attr('id'));
        console.log('Form Data:', formData);
        
        showLoading();
        
        $.post(ajaxurl, formData, function(response) {
            hideLoading();
            console.log('Response:', response);
            if (response.success) {
                alert('<?php esc_js_e('Reset completed successfully!', 'mobility-trailblazers'); ?>');
                location.reload();
            } else {
                alert('<?php esc_js_e('Error: ', 'mobility-trailblazers'); ?>' + (response.data || 'Unknown error'));
            }
        }).fail(function(xhr, status, error) {
            hideLoading();
            console.error('AJAX Error:', xhr.responseText);
            alert('<?php esc_js_e('AJAX Error: ', 'mobility-trailblazers'); ?>' + error);
        });
    });
    
    // Handle backup creation
    $('#mt-create-backup').on('click', function() {
        showLoading();
        
        $.post(ajaxurl, {
            action: 'mt_create_full_backup',
            nonce: '<?php echo wp_create_nonce('mt_backup_nonce'); ?>'
        }, function(response) {
            hideLoading();
            if (response.success) {
                alert('<?php esc_js_e('Backup created successfully!', 'mobility-trailblazers'); ?>');
            } else {
                alert('<?php esc_js_e('Error creating backup: ', 'mobility-trailblazers'); ?>' + response.data);
            }
        });
    });
    
    function showLoading() {
        $('#mt-loading-overlay').show();
    }
    
    function hideLoading() {
        $('#mt-loading-overlay').hide();
    }
});
</script> 