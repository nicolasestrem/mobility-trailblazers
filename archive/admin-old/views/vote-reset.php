<?php
/**
 * Vote Reset Management Template
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get statistics
global $wpdb;

$votes_table = $wpdb->prefix . 'mt_votes';
$scores_table = $wpdb->prefix . 'mt_candidate_scores';
$backups_table = $wpdb->prefix . 'mt_vote_backups';
$logs_table = $wpdb->prefix . 'vote_reset_logs';

// Get vote statistics
$total_votes = $wpdb->get_var("SELECT COUNT(*) FROM $votes_table WHERE is_active = 1");
$total_evaluations = $wpdb->get_var("SELECT COUNT(*) FROM $scores_table WHERE is_active = 1");
$total_backups = $wpdb->get_var("SELECT COUNT(*) FROM $backups_table");
$total_resets = $wpdb->get_var("SELECT COUNT(*) FROM $logs_table");

// Get recent reset logs
$recent_logs = $wpdb->get_results("
    SELECT l.*, u.display_name 
    FROM $logs_table l
    LEFT JOIN {$wpdb->users} u ON l.performed_by = u.ID
    ORDER BY l.created_at DESC
    LIMIT 10
");
?>

<div class="wrap">
    <h1><?php _e('Vote Reset Management', 'mobility-trailblazers'); ?></h1>
    
    <div class="notice notice-warning">
        <p>
            <strong><?php _e('Warning:', 'mobility-trailblazers'); ?></strong>
            <?php _e('Vote reset operations cannot be undone without restoring from backups. Please proceed with caution.', 'mobility-trailblazers'); ?>
        </p>
    </div>
    
    <!-- Statistics -->
    <div class="mt-stats-row">
        <div class="mt-stat-box">
            <h3><?php _e('Active Votes', 'mobility-trailblazers'); ?></h3>
            <p class="mt-stat-number"><?php echo number_format($total_votes); ?></p>
        </div>
        
        <div class="mt-stat-box">
            <h3><?php _e('Active Evaluations', 'mobility-trailblazers'); ?></h3>
            <p class="mt-stat-number"><?php echo number_format($total_evaluations); ?></p>
        </div>
        
        <div class="mt-stat-box">
            <h3><?php _e('Total Backups', 'mobility-trailblazers'); ?></h3>
            <p class="mt-stat-number"><?php echo number_format($total_backups); ?></p>
        </div>
        
        <div class="mt-stat-box">
            <h3><?php _e('Reset Operations', 'mobility-trailblazers'); ?></h3>
            <p class="mt-stat-number"><?php echo number_format($total_resets); ?></p>
        </div>
    </div>
    
    <!-- Reset Options -->
    <div class="mt-reset-options">
        <h2><?php _e('Reset Options', 'mobility-trailblazers'); ?></h2>
        
        <!-- Individual Vote Reset -->
        <div class="mt-reset-section">
            <h3><?php _e('Individual Vote Reset', 'mobility-trailblazers'); ?></h3>
            <p><?php _e('Reset votes for a specific candidate-jury combination.', 'mobility-trailblazers'); ?></p>
            
            <div class="mt-reset-form">
                <div class="mt-form-row">
                    <div class="mt-form-group">
                        <label for="individual-candidate"><?php _e('Select Candidate', 'mobility-trailblazers'); ?></label>
                        <select id="individual-candidate" class="mt-select2">
                            <option value=""><?php _e('Choose a candidate...', 'mobility-trailblazers'); ?></option>
                            <?php
                            $candidates = get_posts(array(
                                'post_type' => 'mt_candidate',
                                'posts_per_page' => -1,
                                'orderby' => 'title',
                                'order' => 'ASC',
                            ));
                            
                            foreach ($candidates as $candidate) {
                                echo '<option value="' . $candidate->ID . '">' . esc_html($candidate->post_title) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="mt-form-group">
                        <label for="individual-jury"><?php _e('Select Jury Member', 'mobility-trailblazers'); ?></label>
                        <select id="individual-jury" class="mt-select2">
                            <option value=""><?php _e('Choose a jury member...', 'mobility-trailblazers'); ?></option>
                            <?php
                            $jury_members = get_posts(array(
                                'post_type' => 'mt_jury_member',
                                'posts_per_page' => -1,
                                'orderby' => 'title',
                                'order' => 'ASC',
                            ));
                            
                            foreach ($jury_members as $jury) {
                                echo '<option value="' . $jury->ID . '">' . esc_html($jury->post_title) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <div class="mt-form-group">
                    <label for="individual-reason"><?php _e('Reason for Reset', 'mobility-trailblazers'); ?></label>
                    <textarea id="individual-reason" rows="3" placeholder="<?php _e('Please provide a reason for this reset...', 'mobility-trailblazers'); ?>"></textarea>
                </div>
                
                <button type="button" class="button button-primary mt-reset-btn" data-action="individual">
                    <?php _e('Reset Individual Vote', 'mobility-trailblazers'); ?>
                </button>
            </div>
        </div>
        
        <!-- Bulk Candidate Reset -->
        <div class="mt-reset-section">
            <h3><?php _e('Bulk Candidate Reset', 'mobility-trailblazers'); ?></h3>
            <p><?php _e('Reset all votes for a specific candidate.', 'mobility-trailblazers'); ?></p>
            
            <div class="mt-reset-form">
                <div class="mt-form-group">
                    <label for="bulk-candidate"><?php _e('Select Candidate', 'mobility-trailblazers'); ?></label>
                    <select id="bulk-candidate" class="mt-select2">
                        <option value=""><?php _e('Choose a candidate...', 'mobility-trailblazers'); ?></option>
                        <?php
                        foreach ($candidates as $candidate) {
                            $vote_count = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM $scores_table WHERE candidate_id = %d AND is_active = 1",
                                $candidate->ID
                            ));
                            
                            if ($vote_count > 0) {
                                echo '<option value="' . $candidate->ID . '">' . 
                                     esc_html($candidate->post_title) . 
                                     ' (' . sprintf(_n('%d vote', '%d votes', $vote_count, 'mobility-trailblazers'), $vote_count) . ')' .
                                     '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                
                <div class="mt-form-group">
                    <label for="bulk-candidate-reason"><?php _e('Reason for Reset', 'mobility-trailblazers'); ?></label>
                    <textarea id="bulk-candidate-reason" rows="3" placeholder="<?php _e('Please provide a reason for this reset...', 'mobility-trailblazers'); ?>"></textarea>
                </div>
                
                <button type="button" class="button button-primary mt-reset-btn" data-action="bulk-candidate">
                    <?php _e('Reset All Votes for Candidate', 'mobility-trailblazers'); ?>
                </button>
            </div>
        </div>
        
        <!-- Bulk Jury Reset -->
        <div class="mt-reset-section">
            <h3><?php _e('Bulk Jury Member Reset', 'mobility-trailblazers'); ?></h3>
            <p><?php _e('Reset all votes by a specific jury member.', 'mobility-trailblazers'); ?></p>
            
            <div class="mt-reset-form">
                <div class="mt-form-group">
                    <label for="bulk-jury"><?php _e('Select Jury Member', 'mobility-trailblazers'); ?></label>
                    <select id="bulk-jury" class="mt-select2">
                        <option value=""><?php _e('Choose a jury member...', 'mobility-trailblazers'); ?></option>
                        <?php
                        foreach ($jury_members as $jury) {
                            $vote_count = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM $scores_table WHERE jury_member_id = %d AND is_active = 1",
                                $jury->ID
                            ));
                            
                            if ($vote_count > 0) {
                                echo '<option value="' . $jury->ID . '">' . 
                                     esc_html($jury->post_title) . 
                                     ' (' . sprintf(_n('%d evaluation', '%d evaluations', $vote_count, 'mobility-trailblazers'), $vote_count) . ')' .
                                     '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                
                <div class="mt-form-group">
                    <label for="bulk-jury-reason"><?php _e('Reason for Reset', 'mobility-trailblazers'); ?></label>
                    <textarea id="bulk-jury-reason" rows="3" placeholder="<?php _e('Please provide a reason for this reset...', 'mobility-trailblazers'); ?>"></textarea>
                </div>
                
                <button type="button" class="button button-primary mt-reset-btn" data-action="bulk-jury">
                    <?php _e('Reset All Votes by Jury Member', 'mobility-trailblazers'); ?>
                </button>
            </div>
        </div>
        
        <!-- Phase Transition Reset -->
        <div class="mt-reset-section">
            <h3><?php _e('Phase Transition Reset', 'mobility-trailblazers'); ?></h3>
            <p><?php _e('Reset votes when transitioning between award phases.', 'mobility-trailblazers'); ?></p>
            
            <div class="mt-reset-form">
                <div class="mt-form-group">
                    <label for="phase-transition"><?php _e('Transition To', 'mobility-trailblazers'); ?></label>
                    <select id="phase-transition">
                        <option value=""><?php _e('Select phase...', 'mobility-trailblazers'); ?></option>
                        <option value="screening"><?php _e('Screening Phase', 'mobility-trailblazers'); ?></option>
                        <option value="evaluation"><?php _e('Evaluation Phase', 'mobility-trailblazers'); ?></option>
                        <option value="selection"><?php _e('Selection Phase', 'mobility-trailblazers'); ?></option>
                        <option value="announcement"><?php _e('Announcement Phase', 'mobility-trailblazers'); ?></option>
                    </select>
                </div>
                
                <div class="mt-form-group">
                    <label>
                        <input type="checkbox" id="phase-notify-jury" checked />
                        <?php _e('Notify jury members about phase transition', 'mobility-trailblazers'); ?>
                    </label>
                </div>
                
                <div class="mt-form-group">
                    <label for="phase-reason"><?php _e('Additional Notes', 'mobility-trailblazers'); ?></label>
                    <textarea id="phase-reason" rows="3" placeholder="<?php _e('Optional notes about this phase transition...', 'mobility-trailblazers'); ?>"></textarea>
                </div>
                
                <button type="button" class="button button-primary mt-reset-btn" data-action="phase-transition">
                    <?php _e('Reset for Phase Transition', 'mobility-trailblazers'); ?>
                </button>
            </div>
        </div>
        
        <!-- Full System Reset -->
        <div class="mt-reset-section mt-danger-zone">
            <h3><?php _e('Full System Reset', 'mobility-trailblazers'); ?></h3>
            <p class="mt-warning-text">
                <strong><?php _e('DANGER:', 'mobility-trailblazers'); ?></strong>
                <?php _e('This will reset ALL votes and evaluations in the system. A full backup will be created automatically.', 'mobility-trailblazers'); ?>
            </p>
            
            <div class="mt-reset-form">
                <div class="mt-form-group">
                    <label>
                        <input type="checkbox" id="full-reset-confirm" />
                        <?php _e('I understand this will reset ALL votes and evaluations', 'mobility-trailblazers'); ?>
                    </label>
                </div>
                
                <div class="mt-form-group">
                    <label>
                        <input type="checkbox" id="full-reset-notify" />
                        <?php _e('Send notification emails to all jury members', 'mobility-trailblazers'); ?>
                    </label>
                </div>
                
                <div class="mt-form-group">
                    <label for="full-reset-reason"><?php _e('Reason for Full Reset', 'mobility-trailblazers'); ?></label>
                    <textarea id="full-reset-reason" rows="3" placeholder="<?php _e('Please provide a detailed reason for this full system reset...', 'mobility-trailblazers'); ?>" required></textarea>
                </div>
                
                <button type="button" class="button button-danger mt-reset-btn" data-action="full-system" disabled>
                    <?php _e('Perform Full System Reset', 'mobility-trailblazers'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Recent Reset History -->
    <div class="mt-reset-history">
        <h2><?php _e('Recent Reset History', 'mobility-trailblazers'); ?></h2>
        
        <?php if (!empty($recent_logs)) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Date/Time', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Reset Type', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Performed By', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Reason', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Backup', 'mobility-trailblazers'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_logs as $log) : ?>
                        <tr>
                            <td><?php echo esc_html(mt_format_date($log->created_at, 'Y-m-d H:i:s')); ?></td>
                            <td>
                                <?php
                                $type_labels = array(
                                    'individual' => __('Individual Vote', 'mobility-trailblazers'),
                                    'bulk_candidate' => __('Bulk Candidate', 'mobility-trailblazers'),
                                    'bulk_jury' => __('Bulk Jury Member', 'mobility-trailblazers'),
                                    'phase_transition' => __('Phase Transition', 'mobility-trailblazers'),
                                    'full_system' => __('Full System', 'mobility-trailblazers'),
                                );
                                
                                echo isset($type_labels[$log->reset_type]) ? $type_labels[$log->reset_type] : $log->reset_type;
                                ?>
                            </td>
                            <td><?php echo esc_html($log->display_name); ?></td>
                            <td><?php echo esc_html($log->reason); ?></td>
                            <td>
                                <?php if ($log->backup_created) : ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                <?php else : ?>
                                    <span class="dashicons dashicons-minus" style="color: #999;"></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <p>
                <a href="<?php echo admin_url('admin.php?page=mt-import-export&tab=backups'); ?>" class="button">
                    <?php _e('View All Backups', 'mobility-trailblazers'); ?>
                </a>
            </p>
        <?php else : ?>
            <p><?php _e('No reset operations have been performed yet.', 'mobility-trailblazers'); ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="mt-reset-confirm-modal" class="mt-modal" style="display: none;">
    <div class="mt-modal-content">
        <h2><?php _e('Confirm Reset Operation', 'mobility-trailblazers'); ?></h2>
        
        <div class="mt-modal-message"></div>
        
        <div class="mt-modal-details">
            <h4><?php _e('This operation will:', 'mobility-trailblazers'); ?></h4>
            <ul class="mt-operation-details"></ul>
        </div>
        
        <div class="mt-modal-actions">
            <button type="button" class="button button-primary" id="mt-confirm-reset">
                <?php _e('Confirm Reset', 'mobility-trailblazers'); ?>
            </button>
            <button type="button" class="button mt-modal-close">
                <?php _e('Cancel', 'mobility-trailblazers'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Progress Modal -->
<div id="mt-reset-progress-modal" class="mt-modal" style="display: none;">
    <div class="mt-modal-content">
        <h2><?php _e('Processing Reset Operation', 'mobility-trailblazers'); ?></h2>
        
        <div class="mt-progress-bar">
            <div class="mt-progress-fill" style="width: 0%;"></div>
        </div>
        
        <div class="mt-progress-status">
            <p class="mt-progress-message"><?php _e('Initializing...', 'mobility-trailblazers'); ?></p>
            <p class="mt-progress-details"></p>
        </div>
    </div>
</div>

<style>
.mt-stats-row {
    display: flex;
    gap: 20px;
    margin: 20px 0;
}

.mt-stat-box {
    flex: 1;
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    text-align: center;
}

.mt-stat-box h3 {
    margin: 0 0 10px 0;
    color: #23282d;
}

.mt-stat-number {
    font-size: 32px;
    font-weight: 600;
    color: #0073aa;
    margin: 0;
}

.mt-reset-options {
    margin-top: 30px;
}

.mt-reset-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin-bottom: 20px;
}

.mt-reset-section h3 {
    margin-top: 0;
}

.mt-danger-zone {
    border-color: #dc3232;
    background: #fff5f5;
}

.mt-danger-zone h3 {
    color: #dc3232;
}

.mt-warning-text {
    color: #dc3232;
    font-weight: 500;
}

.mt-reset-form {
    margin-top: 15px;
}

.mt-form-row {
    display: flex;
    gap: 15px;
}

.mt-form-group {
    margin-bottom: 15px;
}

.mt-form-row .mt-form-group {
    flex: 1;
}

.mt-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.mt-form-group select,
.mt-form-group textarea {
    width: 100%;
}

.mt-form-group textarea {
    resize: vertical;
}

.button-danger {
    background: #dc3232;
    border-color: #dc3232;
    color: #fff;
}

.button-danger:hover {
    background: #a00;
    border-color: #a00;
}

.button-danger:disabled {
    background: #f5a5a5 !important;
    border-color: #f5a5a5 !important;
    color: #fff !important;
    cursor: not-allowed !important;
}

.mt-reset-history {
    margin-top: 40px;
}

.mt-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mt-modal-content {
    background: #fff;
    padding: 30px;
    max-width: 600px;
    width: 90%;
    box-shadow: 0 5px 30px rgba(0,0,0,0.3);
}

.mt-modal-content h2 {
    margin-top: 0;
}

.mt-modal-message {
    margin: 20px 0;
    font-size: 16px;
}

.mt-modal-details {
    background: #f8f9fa;
    border: 1px solid #ddd;
    padding: 15px;
    margin: 20px 0;
}

.mt-modal-details h4 {
    margin-top: 0;
}

.mt-operation-details {
    margin: 10px 0 0 20px;
}

.mt-modal-actions {
    margin-top: 30px;
    text-align: right;
}

.mt-modal-actions .button {
    margin-left: 10px;
}

.mt-progress-bar {
    width: 100%;
    height: 30px;
    background: #f0f0f0;
    border-radius: 15px;
    overflow: hidden;
    margin: 20px 0;
}

.mt-progress-fill {
    height: 100%;
    background: #0073aa;
    transition: width 0.3s ease;
}

.mt-progress-status {
    text-align: center;
}

.mt-progress-message {
    font-size: 16px;
    font-weight: 500;
}

.mt-progress-details {
    color: #666;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Enable/disable full reset button
    $('#full-reset-confirm').on('change', function() {
        $('.mt-reset-btn[data-action="full-system"]').prop('disabled', !$(this).is(':checked'));
    });
    
    // Handle reset button clicks
    $('.mt-reset-btn').on('click', function() {
        var action = $(this).data('action');
        var data = {};
        var message = '';
        var details = [];
        
        switch (action) {
            case 'individual':
                data.candidate_id = $('#individual-candidate').val();
                data.jury_member_id = $('#individual-jury').val();
                data.reason = $('#individual-reason').val();
                
                if (!data.candidate_id || !data.jury_member_id) {
                    alert('<?php _e('Please select both a candidate and jury member.', 'mobility-trailblazers'); ?>');
                    return;
                }
                
                message = '<?php _e('Are you sure you want to reset the vote for this candidate-jury combination?', 'mobility-trailblazers'); ?>';
                details = [
                    '<?php _e('Reset the evaluation score', 'mobility-trailblazers'); ?>',
                    '<?php _e('Create a backup of the current data', 'mobility-trailblazers'); ?>',
                    '<?php _e('Log this operation for audit purposes', 'mobility-trailblazers'); ?>'
                ];
                break;
                
            case 'bulk-candidate':
                data.candidate_id = $('#bulk-candidate').val();
                data.reason = $('#bulk-candidate-reason').val();
                
                if (!data.candidate_id) {
                    alert('<?php _e('Please select a candidate.', 'mobility-trailblazers'); ?>');
                    return;
                }
                
                message = '<?php _e('Are you sure you want to reset ALL votes for this candidate?', 'mobility-trailblazers'); ?>';
                details = [
                    '<?php _e('Reset all evaluation scores for this candidate', 'mobility-trailblazers'); ?>',
                    '<?php _e('Create a backup of all affected data', 'mobility-trailblazers'); ?>',
                    '<?php _e('Notify affected jury members (if enabled)', 'mobility-trailblazers'); ?>',
                    '<?php _e('Log this operation for audit purposes', 'mobility-trailblazers'); ?>'
                ];
                break;
                
            case 'bulk-jury':
                data.jury_member_id = $('#bulk-jury').val();
                data.reason = $('#bulk-jury-reason').val();
                
                if (!data.jury_member_id) {
                    alert('<?php _e('Please select a jury member.', 'mobility-trailblazers'); ?>');
                    return;
                }
                
                message = '<?php _e('Are you sure you want to reset ALL votes by this jury member?', 'mobility-trailblazers'); ?>';
                details = [
                    '<?php _e('Reset all evaluations submitted by this jury member', 'mobility-trailblazers'); ?>',
                    '<?php _e('Create a backup of all affected data', 'mobility-trailblazers'); ?>',
                    '<?php _e('Send notification to the jury member', 'mobility-trailblazers'); ?>',
                    '<?php _e('Log this operation for audit purposes', 'mobility-trailblazers'); ?>'
                ];
                break;
                
            case 'phase-transition':
                data.new_phase = $('#phase-transition').val();
                data.notify_jury = $('#phase-notify-jury').is(':checked');
                data.reason = $('#phase-reason').val();
                
                if (!data.new_phase) {
                    alert('<?php _e('Please select a phase to transition to.', 'mobility-trailblazers'); ?>');
                    return;
                }
                
                message = '<?php _e('Are you sure you want to reset votes for phase transition?', 'mobility-trailblazers'); ?>';
                details = [
                    '<?php _e('Archive current phase evaluations', 'mobility-trailblazers'); ?>',
                    '<?php _e('Create a comprehensive backup', 'mobility-trailblazers'); ?>',
                    '<?php _e('Reset evaluation status for new phase', 'mobility-trailblazers'); ?>',
                    '<?php _e('Update system phase setting', 'mobility-trailblazers'); ?>'
                ];
                
                if (data.notify_jury) {
                    details.push('<?php _e('Send notification emails to all jury members', 'mobility-trailblazers'); ?>');
                }
                break;
                
            case 'full-system':
                data.notify_jury = $('#full-reset-notify').is(':checked');
                data.reason = $('#full-reset-reason').val();
                
                if (!data.reason) {
                    alert('<?php _e('Please provide a reason for the full system reset.', 'mobility-trailblazers'); ?>');
                    return;
                }
                
                message = '<?php _e('WARNING: This will reset ALL votes and evaluations in the system. Are you absolutely sure?', 'mobility-trailblazers'); ?>';
                details = [
                    '<?php _e('Create a complete system backup', 'mobility-trailblazers'); ?>',
                    '<?php _e('Reset ALL candidate evaluations', 'mobility-trailblazers'); ?>',
                    '<?php _e('Reset ALL voting data', 'mobility-trailblazers'); ?>',
                    '<?php _e('Clear all assignment statistics', 'mobility-trailblazers'); ?>',
                    '<?php _e('Log this critical operation', 'mobility-trailblazers'); ?>'
                ];
                
                if (data.notify_jury) {
                    details.push('<?php _e('Send notification emails to all jury members', 'mobility-trailblazers'); ?>');
                }
                break;
        }
        
        // Show confirmation modal
        $('#mt-reset-confirm-modal .mt-modal-message').text(message);
        $('#mt-reset-confirm-modal .mt-operation-details').html(
            details.map(function(detail) {
                return '<li>' + detail + '</li>';
            }).join('')
        );
        
        $('#mt-reset-confirm-modal').show().data('action', action).data('params', data);
    });
    
    // Handle confirmation
    $('#mt-confirm-reset').on('click', function() {
        var modal = $('#mt-reset-confirm-modal');
        var action = modal.data('action');
        var params = modal.data('params');
        
        modal.hide();
        
        // Show progress modal
        $('#mt-reset-progress-modal').show();
        $('.mt-progress-fill').css('width', '0%');
        $('.mt-progress-message').text('<?php _e('Initializing reset operation...', 'mobility-trailblazers'); ?>');
        
        // Perform reset via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mt_reset_' + action.replace('-', '_'),
                nonce: '<?php echo wp_create_nonce('mt_vote_reset'); ?>',
                ...params
            },
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                
                xhr.addEventListener('progress', function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = evt.loaded / evt.total * 100;
                        $('.mt-progress-fill').css('width', percentComplete + '%');
                    }
                }, false);
                
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    $('.mt-progress-fill').css('width', '100%');
                    $('.mt-progress-message').text('<?php _e('Reset operation completed successfully!', 'mobility-trailblazers'); ?>');
                    $('.mt-progress-details').text(response.data.message || '');
                    
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $('#mt-reset-progress-modal').hide();
                    alert(response.data.message || '<?php _e('An error occurred during the reset operation.', 'mobility-trailblazers'); ?>');
                }
            },
            error: function() {
                $('#mt-reset-progress-modal').hide();
                alert('<?php _e('An error occurred. Please try again.', 'mobility-trailblazers'); ?>');
            }
        });
    });
    
    // Close modals
    $('.mt-modal-close, .mt-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).closest('.mt-modal').hide();
        }
    });
    
    // Initialize Select2 if available
    if ($.fn.select2) {
        $('.mt-select2').select2({
            width: '100%'
        });
    }
});
</script> 