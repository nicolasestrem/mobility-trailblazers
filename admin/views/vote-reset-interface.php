<?php
/**
 * Admin Vote Reset Interface
 * 
 * @package MobilityTrailblazers
 * @subpackage Admin/Views
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current voting statistics
$current_phase = get_option('mt_current_voting_phase', 'phase_1');
$phase_status = get_option("mt_voting_phase_{$current_phase}_status", 'open');
$total_votes = $this->get_total_active_votes();
$total_candidates = wp_count_posts('mt_candidate')->publish;
$total_jury = count(get_users(array('role' => 'mt_jury_member')));

// Get recent reset logs
global $wpdb;
$recent_resets = $wpdb->get_results("
    SELECT * FROM {$wpdb->prefix}vote_reset_logs 
    ORDER BY reset_timestamp DESC 
    LIMIT 5
");
?>

<div class="wrap mt-vote-reset-page">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-backup" style="font-size: 36px; width: 36px; height: 36px; margin-right: 10px;"></span>
        <?php _e('Vote Reset Management', 'mobility-trailblazers'); ?>
    </h1>
    
    <hr class="wp-header-end">
    
    <?php if (isset($_GET['reset_success'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Votes have been successfully reset.', 'mobility-trailblazers'); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Overview Cards -->
    <div class="mt-admin-cards-grid">
        <div class="mt-admin-card mt-status-card">
            <span class="dashicons dashicons-chart-pie"></span>
            <div class="mt-card-content">
                <h3><?php echo number_format($total_votes); ?></h3>
                <p><?php _e('Active Votes', 'mobility-trailblazers'); ?></p>
            </div>
        </div>
        
        <div class="mt-admin-card mt-status-card">
            <span class="dashicons dashicons-groups"></span>
            <div class="mt-card-content">
                <h3><?php echo number_format($total_candidates); ?></h3>
                <p><?php _e('Total Candidates', 'mobility-trailblazers'); ?></p>
            </div>
        </div>
        
        <div class="mt-admin-card mt-status-card">
            <span class="dashicons dashicons-admin-users"></span>
            <div class="mt-card-content">
                <h3><?php echo number_format($total_jury); ?></h3>
                <p><?php _e('Jury Members', 'mobility-trailblazers'); ?></p>
            </div>
        </div>
        
        <div class="mt-admin-card mt-status-card">
            <span class="dashicons dashicons-flag"></span>
            <div class="mt-card-content">
                <h3><?php echo esc_html(ucfirst($current_phase)); ?></h3>
                <p><?php _e('Current Phase', 'mobility-trailblazers'); ?></p>
                <span class="mt-phase-status mt-status-<?php echo esc_attr($phase_status); ?>">
                    <?php echo esc_html(ucfirst($phase_status)); ?>
                </span>
            </div>
        </div>
    </div>
    
    <div class="mt-admin-grid mt-reset-grid">
        <!-- Phase Transition Reset -->
        <div class="mt-admin-card">
            <h2>
                <span class="dashicons dashicons-controls-forward"></span>
                <?php _e('Phase Transition Reset', 'mobility-trailblazers'); ?>
            </h2>
            <p class="description">
                <?php _e('Use this when transitioning between voting phases (e.g., from 200 candidates to 50, or 50 to 25). All votes from the current phase will be archived and jury members can start fresh evaluations.', 'mobility-trailblazers'); ?>
            </p>
            
            <div class="mt-phase-info">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Current Phase:', 'mobility-trailblazers'); ?></th>
                        <td>
                            <strong><?php echo esc_html($current_phase); ?></strong>
                            <?php if ($phase_status === 'locked'): ?>
                                <span class="dashicons dashicons-lock" title="<?php esc_attr_e('Phase is locked', 'mobility-trailblazers'); ?>"></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Active Votes:', 'mobility-trailblazers'); ?></th>
                        <td><strong><?php echo number_format($total_votes); ?></strong></td>
                    </tr>
                    <tr>
                        <th><?php _e('Next Phase:', 'mobility-trailblazers'); ?></th>
                        <td>
                            <?php 
                            $next_phase = ($current_phase === 'phase_1') ? 'phase_2' : 'phase_3';
                            echo '<strong>' . esc_html($next_phase) . '</strong>';
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
            
            <input type="hidden" id="current-voting-phase" value="<?php echo esc_attr($current_phase); ?>">
            <input type="hidden" id="next-voting-phase" value="<?php echo esc_attr($next_phase); ?>">
            
            <p class="mt-button-wrapper">
                <button type="button" 
                        id="mt-bulk-reset-phase" 
                        class="button button-primary button-hero"
                        <?php echo $phase_status === 'locked' ? 'disabled' : ''; ?>>
                    <span class="dashicons dashicons-update-alt"></span>
                    <?php _e('Transition to Next Phase', 'mobility-trailblazers'); ?>
                </button>
            </p>
            
            <?php if ($phase_status === 'locked'): ?>
                <p class="mt-warning-text">
                    <span class="dashicons dashicons-info"></span>
                    <?php _e('Please unlock the current phase before performing a phase transition.', 'mobility-trailblazers'); ?>
                </p>
            <?php endif; ?>
        </div>
        
        <!-- Targeted Resets -->
        <div class="mt-admin-card">
            <h2>
                <span class="dashicons dashicons-admin-users"></span>
                <?php _e('Targeted Resets', 'mobility-trailblazers'); ?>
            </h2>
            <p class="description">
                <?php _e('Reset votes for specific jury members or candidates. Use this for corrections or when a jury member needs to re-evaluate.', 'mobility-trailblazers'); ?>
            </p>
            
            <div class="mt-reset-options">
                <!-- Reset by Jury Member -->
                <div class="mt-reset-option">
                    <h3><?php _e('Reset by Jury Member', 'mobility-trailblazers'); ?></h3>
                    <p class="description"><?php _e('Remove all votes from a specific jury member', 'mobility-trailblazers'); ?></p>
                    
                    <select id="reset-by-user" class="mt-select-field">
                        <option value=""><?php _e('Select a jury member...', 'mobility-trailblazers'); ?></option>
                        <?php
                        $jury_members = get_users(array('role' => 'mt_jury_member'));
                        foreach ($jury_members as $member):
                            $vote_count = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes 
                                WHERE jury_member_id = %d AND is_active = 1",
                                $member->ID
                            ));
                        ?>
                            <option value="<?php echo $member->ID; ?>">
                                <?php echo esc_html($member->display_name); ?> 
                                (<?php echo sprintf(_n('%d vote', '%d votes', $vote_count, 'mobility-trailblazers'), $vote_count); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="button" class="button button-secondary mt-reset-user-votes" disabled>
                        <span class="dashicons dashicons-trash"></span>
                        <?php _e('Reset User Votes', 'mobility-trailblazers'); ?>
                    </button>
                </div>
                
                <!-- Reset by Candidate -->
                <div class="mt-reset-option">
                    <h3><?php _e('Reset by Candidate', 'mobility-trailblazers'); ?></h3>
                    <p class="description"><?php _e('Remove all votes for a specific candidate', 'mobility-trailblazers'); ?></p>
                    
                    <select id="reset-by-candidate" class="mt-select-field">
                        <option value=""><?php _e('Select a candidate...', 'mobility-trailblazers'); ?></option>
                        <?php
                        $candidates = get_posts(array(
                            'post_type' => 'mt_candidate',
                            'posts_per_page' => -1,
                            'orderby' => 'title',
                            'order' => 'ASC'
                        ));
                        foreach ($candidates as $candidate):
                            $vote_count = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes 
                                WHERE candidate_id = %d AND is_active = 1",
                                $candidate->ID
                            ));
                        ?>
                            <option value="<?php echo $candidate->ID; ?>">
                                <?php echo esc_html($candidate->post_title); ?>
                                (<?php echo sprintf(_n('%d vote', '%d votes', $vote_count, 'mobility-trailblazers'), $vote_count); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="button" class="button button-secondary mt-reset-candidate-votes" disabled>
                        <span class="dashicons dashicons-trash"></span>
                        <?php _e('Reset Candidate Votes', 'mobility-trailblazers'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Individual Vote Resets -->
        <div class="mt-admin-card">
            <h2>
                <span class="dashicons dashicons-admin-page"></span>
                <?php _e('Individual Vote Resets', 'mobility-trailblazers'); ?>
            </h2>
            <p class="description">
                <?php _e('Jury members can reset their individual votes directly from the evaluation interface. This allows them to re-evaluate specific candidates.', 'mobility-trailblazers'); ?>
            </p>
            
            <div class="mt-info-box">
                <h4><?php _e('How it works:', 'mobility-trailblazers'); ?></h4>
                <ol>
                    <li><?php _e('Jury members see a "Reset Vote" button on candidates they have already evaluated', 'mobility-trailblazers'); ?></li>
                    <li><?php _e('Clicking the button removes their vote for that specific candidate', 'mobility-trailblazers'); ?></li>
                    <li><?php _e('They can then submit a new evaluation', 'mobility-trailblazers'); ?></li>
                    <li><?php _e('All reset actions are logged for transparency', 'mobility-trailblazers'); ?></li>
                </ol>
            </div>
            
            <p class="mt-button-wrapper">
                <a href="<?php echo admin_url('admin.php?page=mt-jury-dashboard'); ?>" class="button button-secondary">
                    <span class="dashicons dashicons-visibility"></span>
                    <?php _e('View Jury Dashboard', 'mobility-trailblazers'); ?>
                </a>
            </p>
        </div>
        
        <!-- Backup Management -->
        <div class="mt-admin-card">
            <h2>
                <span class="dashicons dashicons-backup"></span>
                <?php _e('Backup Management', 'mobility-trailblazers'); ?>
            </h2>
            <p class="description">
                <?php _e('Create manual backups of all voting data or manage existing backups. Backups are automatically created before any reset operation.', 'mobility-trailblazers'); ?>
            </p>
            
            <div class="mt-backup-stats">
                <?php
                $backup_manager = new MT_Vote_Backup_Manager();
                $stats = $backup_manager->get_backup_statistics();
                ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Total Backups:', 'mobility-trailblazers'); ?></th>
                        <td><strong><?php echo number_format($stats['total_backups']); ?></strong></td>
                    </tr>
                    <tr>
                        <th><?php _e('Recent Backups (7 days):', 'mobility-trailblazers'); ?></th>
                        <td><strong><?php echo number_format($stats['recent_backups']); ?></strong></td>
                    </tr>
                    <tr>
                        <th><?php _e('Storage Size:', 'mobility-trailblazers'); ?></th>
                        <td><strong><?php echo esc_html($stats['storage_size']); ?></strong></td>
                    </tr>
                    <tr>
                        <th><?php _e('Restorations:', 'mobility-trailblazers'); ?></th>
                        <td><strong><?php echo number_format($stats['restorations']); ?></strong></td>
                    </tr>
                </table>
            </div>
            
            <div class="mt-backup-actions">
                <p class="mt-button-wrapper">
                    <button type="button" 
                            id="mt-create-backup" 
                            class="button button-primary">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Create Full Backup Now', 'mobility-trailblazers'); ?>
                    </button>
                    
                    <button type="button" 
                            id="mt-export-backups" 
                            class="button button-secondary">
                        <span class="dashicons dashicons-media-spreadsheet"></span>
                        <?php _e('Export Backup History', 'mobility-trailblazers'); ?>
                    </button>
                    
                    <button type="button" 
                            id="mt-view-backups" 
                            class="button button-secondary">
                        <span class="dashicons dashicons-list-view"></span>
                        <?php _e('View All Backups', 'mobility-trailblazers'); ?>
                    </button>
                </p>
                
                <p class="description">
                    <span class="dashicons dashicons-info"></span>
                    <?php 
                    $retention_days = apply_filters('mt_vote_backup_retention_days', 365);
                    printf(
                        __('Backups are automatically retained for %d days. Restored backups are kept indefinitely.', 'mobility-trailblazers'),
                        $retention_days
                    ); 
                    ?>
                </p>
            </div>
        </div>
        
        <!-- Full System Reset - Danger Zone -->
        <div class="mt-admin-card mt-danger-zone">
            <h2 class="mt-danger-title">
                <span class="dashicons dashicons-warning"></span>
                <?php _e('Danger Zone - Full System Reset', 'mobility-trailblazers'); ?>
            </h2>
            
            <div class="mt-danger-content">
                <p class="mt-danger-warning">
                    <strong><?php _e('WARNING:', 'mobility-trailblazers'); ?></strong>
                    <?php _e('This will permanently delete ALL votes from the system. This action cannot be undone!', 'mobility-trailblazers'); ?>
                </p>
                
                <div class="mt-danger-effects">
                    <h4><?php _e('This will:', 'mobility-trailblazers'); ?></h4>
                    <ul>
                        <li><?php _e('Delete all jury evaluations and scores', 'mobility-trailblazers'); ?></li>
                        <li><?php _e('Reset all candidate rankings to zero', 'mobility-trailblazers'); ?></li>
                        <li><?php _e('Clear all voting history', 'mobility-trailblazers'); ?></li>
                        <li><?php _e('Create a backup before deletion', 'mobility-trailblazers'); ?></li>
                    </ul>
                </div>
                
                <p class="mt-button-wrapper">
                    <button type="button" 
                            id="mt-bulk-reset-all" 
                            class="button button-danger button-large">
                        <span class="dashicons dashicons-trash"></span>
                        <?php _e('Delete All Votes', 'mobility-trailblazers'); ?>
                    </button>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Reset History -->
    <div class="mt-admin-card mt-full-width mt-reset-history-card">
        <h2>
            <span class="dashicons dashicons-backup"></span>
            <?php _e('Recent Reset Activity', 'mobility-trailblazers'); ?>
            <button type="button" 
                    id="mt-view-reset-history" 
                    class="button button-secondary button-small mt-float-right">
                <span class="dashicons dashicons-list-view"></span>
                <?php _e('View Full History', 'mobility-trailblazers'); ?>
            </button>
        </h2>
        
        <?php if ($recent_resets): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Date/Time', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Type', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Initiated By', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Affected', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Votes', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Reason', 'mobility-trailblazers'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_resets as $log): 
                        $user = get_user_by('id', $log->initiated_by);
                        $type_labels = array(
                            'individual' => __('Individual', 'mobility-trailblazers'),
                            'bulk_user' => __('User Bulk', 'mobility-trailblazers'),
                            'bulk_candidate' => __('Candidate Bulk', 'mobility-trailblazers'),
                            'phase_transition' => __('Phase Transition', 'mobility-trailblazers'),
                            'full_reset' => __('Full Reset', 'mobility-trailblazers')
                        );
                    ?>
                        <tr>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->reset_timestamp))); ?></td>
                            <td>
                                <span class="mt-reset-type mt-reset-type-<?php echo esc_attr($log->reset_type); ?>">
                                    <?php echo esc_html($type_labels[$log->reset_type] ?? $log->reset_type); ?>
                                </span>
                            </td>
                            <td><?php echo $user ? esc_html($user->display_name) : __('System', 'mobility-trailblazers'); ?></td>
                            <td>
                                <?php 
                                if ($log->affected_user_id) {
                                    $affected_user = get_user_by('id', $log->affected_user_id);
                                    echo $affected_user ? esc_html($affected_user->display_name) : '-';
                                } elseif ($log->affected_candidate_id) {
                                    $candidate = get_post($log->affected_candidate_id);
                                    echo $candidate ? esc_html($candidate->post_title) : '-';
                                } elseif ($log->voting_phase) {
                                    echo esc_html($log->voting_phase);
                                } else {
                                    echo __('All', 'mobility-trailblazers');
                                }
                                ?>
                            </td>
                            <td><?php echo number_format($log->votes_affected); ?></td>
                            <td><?php echo esc_html($log->reset_reason ?: '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="mt-no-activity"><?php _e('No reset activity recorded yet.', 'mobility-trailblazers'); ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Reset History Modal -->
<div id="reset-history-modal" class="mt-modal" style="display: none;">
    <div class="mt-modal-overlay"></div>
    <div class="mt-modal-dialog">
        <div class="mt-modal-content">
            <div class="mt-modal-header">
                <h2><?php _e('Vote Reset History', 'mobility-trailblazers'); ?></h2>
                <button type="button" class="mt-modal-close" data-dismiss="modal">
                    <span class="dashicons dashicons-no"></span>
                </button>
            </div>
            <div class="mt-modal-body">
                <div id="reset-history-content">
                    <div class="mt-loading">
                        <span class="spinner is-active"></span>
                        <p><?php _e('Loading history...', 'mobility-trailblazers'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom Styles for Vote Reset Page -->
<style>
.mt-vote-reset-page {
    max-width: 1400px;
}

.mt-admin-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0 30px;
}

.mt-admin-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    padding: 20px;
    position: relative;
}

.mt-status-card {
    text-align: center;
    padding: 30px 20px;
}

.mt-status-card .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #007cba;
    display: block;
    margin: 0 auto 15px;
}

.mt-status-card h3 {
    font-size: 32px;
    margin: 0 0 5px;
    color: #1e1e1e;
}

.mt-status-card p {
    margin: 0;
    color: #50575e;
    font-size: 14px;
}

.mt-phase-status {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    margin-top: 8px;
    text-transform: uppercase;
}

.mt-status-open {
    background: #d4f4dd;
    color: #00a32a;
}

.mt-status-locked {
    background: #f0f0f1;
    color: #50575e;
}

.mt-reset-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.mt-admin-card h2 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 0;
}

.mt-admin-card h2 .dashicons {
    color: #50575e;
}

.mt-phase-info {
    background: #f6f7f7;
    padding: 15px;
    border-radius: 4px;
    margin: 20px 0;
}

.mt-phase-info .form-table {
    margin: 0;
}

.mt-phase-info .form-table th {
    padding: 10px 10px 10px 0;
    width: 120px;
}

.mt-phase-info .form-table td {
    padding: 10px;
}

.mt-button-wrapper {
    margin-top: 20px;
}

.button-hero {
    font-size: 16px !important;
    line-height: 28px !important;
    height: auto !important;
    padding: 8px 16px !important;
}

.mt-warning-text {
    color: #d63638;
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 10px;
}

.mt-reset-options {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.mt-reset-option h3 {
    margin: 0 0 8px;
    font-size: 16px;
}

.mt-reset-option .description {
    margin: 0 0 15px;
}

.mt-select-field {
    min-width: 250px;
    margin-right: 10px;
}

.mt-danger-zone {
    border-color: #d63638;
    background: #fcf0f1;
}

.mt-danger-title {
    color: #d63638;
}

.mt-danger-warning {
    background: #fff;
    border-left: 4px solid #d63638;
    padding: 12px;
    margin: 15px 0;
}

.mt-danger-effects {
    margin: 20px 0;
}

.mt-danger-effects ul {
    list-style: disc;
    margin-left: 20px;
}

.button-danger {
    background: #d63638 !important;
    border-color: #d63638 !important;
    color: #fff !important;
}

.button-danger:hover {
    background: #a02222 !important;
    border-color: #a02222 !important;
}

.mt-full-width {
    grid-column: 1 / -1;
}

.mt-float-right {
    float: right;
}

.mt-reset-type {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}

.mt-reset-type-individual {
    background: #dfe8ff;
    color: #2271b1;
}

.mt-reset-type-phase_transition {
    background: #f0f6fc;
    color: #0073aa;
}

.mt-reset-type-full_reset {
    background: #fcf0f1;
    color: #d63638;
}

.mt-info-box {
    background: #f0f6fc;
    border-left: 4px solid #2271b1;
    padding: 15px;
    margin: 20px 0;
}

.mt-info-box h4 {
    margin: 0 0 10px;
}

.mt-info-box ol {
    margin: 10px 0 0 20px;
}

.mt-no-activity {
    text-align: center;
    color: #50575e;
    padding: 40px;
}

/* Modal Styles */
.mt-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mt-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
}

.mt-modal-dialog {
    position: relative;
    background: #fff;
    max-width: 90%;
    width: 1000px;
    max-height: 80vh;
    overflow: hidden;
    box-shadow: 0 3px 30px rgba(0,0,0,0.2);
}

.mt-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
}

.mt-modal-header h2 {
    margin: 0;
}

.mt-modal-close {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    color: #666;
}

.mt-modal-close:hover {
    color: #000;
}

.mt-modal-body {
    padding: 20px;
    overflow-y: auto;
    max-height: calc(80vh - 60px);
}

.mt-loading {
    text-align: center;
    padding: 40px;
}

/* Responsive */
@media screen and (max-width: 782px) {
    .mt-reset-grid {
        grid-template-columns: 1fr;
    }
    
    .mt-select-field {
        width: 100%;
        margin-bottom: 10px;
    }
    
    .mt-reset-option button {
        width: 100%;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Enable/disable buttons based on selection
    $('#reset-by-user').on('change', function() {
        $('.mt-reset-user-votes').prop('disabled', !$(this).val());
    });
    
    $('#reset-by-candidate').on('change', function() {
        $('.mt-reset-candidate-votes').prop('disabled', !$(this).val());
    });
    
    // Close modal
    $('.mt-modal-close, .mt-modal-overlay').on('click', function() {
        $('#reset-history-modal').fadeOut();
    });
});
</script>