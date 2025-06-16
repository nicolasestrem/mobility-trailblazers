<?php
/**
 * Assignment Management System
 * File: /wp-content/plugins/mobility-trailblazers/templates/assignment-management.php
 */

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

// Get current statistics
$total_candidates = wp_count_posts('mt_candidate')->publish;
$total_jury = wp_count_posts('mt_jury')->publish;

global $wpdb;
$assigned_count = $wpdb->get_var("
    SELECT COUNT(DISTINCT post_id) 
    FROM {$wpdb->postmeta} 
    WHERE meta_key = '_mt_assigned_jury_member' 
    AND meta_value != ''
");

$completion_rate = $total_candidates > 0 ? ($assigned_count / $total_candidates) * 100 : 0;
$avg_per_jury = $total_jury > 0 ? $assigned_count / $total_jury : 0;

// Get current phase
$current_phase = get_option('mt_current_phase', 'preparation');
$phase_names = array(
    'preparation' => 'Preparation',
    'candidate_collection' => 'Candidate Collection',
    'jury_evaluation' => 'Jury Evaluation',
    'public_voting' => 'Public Voting',
    'final_selection' => 'Final Selection',
    'award_ceremony' => 'Award Ceremony',
    'post_award' => 'Post Award'
);

// Enqueue required scripts and styles
wp_enqueue_script('jquery');
wp_enqueue_script('mt-assignment', plugins_url('assets/js/assignment.js', dirname(__FILE__)), array('jquery'), '1.0.0', true);
wp_enqueue_style('mt-assignment', plugins_url('assets/css/assignment.css', dirname(__FILE__)), array(), '1.0.0');

// Add inline script for initial data
$initial_data = array(
    'totalCandidates' => $total_candidates,
    'totalJury' => $total_jury,
    'assignedCount' => $assigned_count,
    'completionRate' => number_format($completion_rate, 1) . '%',
    'avgPerJury' => number_format($avg_per_jury, 1),
    'currentPhase' => $phase_names[$current_phase] ?? $current_phase,
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('mt_assignment_nonce')
);

wp_add_inline_script('mt-assignment', 'var mtAssignmentData = ' . json_encode($initial_data) . ';', 'before');
?>

<div id="mt-assignment-interface" class="mt-assignment-interface">
    <div class="mt-assignment-container">
        <!-- Header Section -->
        <div class="mt-assignment-header">
            <div class="mt-header-content">
                <div class="mt-logo-section">
                    <div class="mt-logo-icon">
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none">
                            <path d="M20 5L25 15L35 16L27.5 23L30 33L20 28L10 33L12.5 23L5 16L15 15L20 5Z" fill="currentColor" opacity="0.9"/>
                        </svg>
                    </div>
                    <div>
                        <h1>Jury Assignment System</h1>
                        <p class="mt-subtitle">Mobility Trailblazers 2025 - Award Management Platform</p>
                    </div>
                </div>
                <div class="mt-phase-indicator">
                    <span class="mt-phase-label">Current Phase:</span>
                    <span class="mt-phase-value"><?php echo esc_html($phase_names[$current_phase] ?? $current_phase); ?></span>
                </div>
            </div>
        </div>

        <!-- Status Card -->
        <div class="mt-status-card">
            <div class="mt-status-content">
                <div class="mt-status-icon pulse">
                    <span class="mt-status-dot"></span>
                </div>
                <div class="mt-status-info">
                    <strong>System Status: OPERATIONAL</strong>
                    <span class="mt-separator">•</span>
                    <span>Last sync: <span id="mt-last-sync"><?php echo date('H:i:s'); ?></span></span>
                    <span class="mt-separator">•</span>
                    <span><?php echo $total_jury; ?> active jury members</span>
                </div>
            </div>
        </div>

        <!-- Statistics Dashboard -->
        <div class="mt-stats-dashboard">
            <div class="mt-stat-card mt-stat-primary">
                <div class="mt-stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13M16 3.13C16.8604 3.3503 17.623 3.8507 18.1676 4.55231C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89317 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88M13 7C13 9.20914 11.2091 11 9 11C6.79086 11 5 9.20914 5 7C5 4.79086 6.79086 3 9 3C11.2091 3 13 4.79086 13 7Z" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </div>
                <div class="mt-stat-content">
                    <span class="mt-stat-number mt-stat-total-candidates"><?php echo $total_candidates; ?></span>
                    <span class="mt-stat-label">Total Candidates</span>
                </div>
            </div>

            <div class="mt-stat-card mt-stat-secondary">
                <div class="mt-stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2L13.09 8.26L19 7L15.45 11.82L21 16L14.81 16.12L13.72 22L9 17.27L4.28 22L3.19 16.12L-3 16L2.55 11.82L-1 7L4.91 8.26L6 2H12Z" fill="currentColor"/>
                    </svg>
                </div>
                <div class="mt-stat-content">
                    <span class="mt-stat-number mt-stat-total-jury"><?php echo $total_jury; ?></span>
                    <span class="mt-stat-label">Jury Members</span>
                </div>
            </div>

            <div class="mt-stat-card mt-stat-success">
                <div class="mt-stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M9 11L12 14L22 4M21 12V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H16" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </div>
                <div class="mt-stat-content">
                    <span class="mt-stat-number mt-stat-assigned-count"><?php echo $assigned_count; ?></span>
                    <span class="mt-stat-label">Assignments Made</span>
                </div>
            </div>

            <div class="mt-stat-card mt-stat-info">
                <div class="mt-stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                        <path d="M12 6V12L16 14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="mt-stat-content">
                    <span class="mt-stat-number mt-stat-completion-rate"><?php echo number_format($completion_rate, 1); ?>%</span>
                    <span class="mt-stat-label">Completion Rate</span>
                </div>
            </div>

            <div class="mt-stat-card mt-stat-warning">
                <div class="mt-stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2C13.3132 2 14.6136 2.25866 15.8268 2.7612C17.0401 3.26375 18.1425 4.00035 19.0711 4.92893C19.9997 5.85752 20.7362 6.95991 21.2388 8.17317C21.7413 9.38642 22 10.6868 22 12C22 14.6522 20.9464 17.1957 19.0711 19.0711C17.1957 20.9464 14.6522 22 12 22C10.6868 22 9.38642 21.7413 8.17317 21.2388C6.95991 20.7362 5.85752 19.9997 4.92893 19.0711C3.05357 17.1957 2 14.6522 2 12C2 9.34784 3.05357 6.8043 4.92893 4.92893C6.8043 3.05357 9.34784 2 12 2Z" fill="currentColor"/>
                    </svg>
                </div>
                <div class="mt-stat-content">
                    <span class="mt-stat-number mt-stat-avg-per-jury"><?php echo number_format($avg_per_jury, 1); ?></span>
                    <span class="mt-stat-label">Avg. per Jury</span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-quick-actions">
            <div class="mt-actions-header">
                <h3>Quick Actions</h3>
                <span class="mt-actions-subtitle">Manage your assignments efficiently</span>
            </div>
            
            <div class="mt-actions-grid">
                <button id="mt-auto-assign-btn" class="mt-action-btn mt-action-primary">
                    <div class="mt-action-icon">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M10 1L12.5 6L18 7L14 11L15 17L10 14L5 17L6 11L2 7L7.5 6L10 1Z" fill="currentColor"/>
                        </svg>
                    </div>
                    <span class="mt-action-label">Auto-Assign</span>
                    <span class="mt-action-desc">Smart distribution</span>
                </button>

                <button id="mt-manual-assign-btn" class="mt-action-btn mt-action-secondary" disabled>
                    <div class="mt-action-icon">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M7 7H13M7 10H13M7 13H10M5 3H15C16.1046 3 17 3.89543 17 5V15C17 16.1046 16.1046 17 15 17H5C3.89543 17 3 16.1046 3 15V5C3 3.89543 3.89543 3 5 3Z" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                    <span class="mt-action-label">Manual Assign</span>
                    <span class="mt-action-desc"><span class="mt-selected-candidates-count">0</span> → <span class="mt-selected-jury-name">None</span></span>
                </button>

                <button id="mt-export-btn" class="mt-action-btn mt-action-info">
                    <div class="mt-action-icon">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M10 1V11M10 11L7 8M10 11L13 8M3 11V16C3 16.5304 3.21071 17.0391 3.58579 17.4142C3.96086 17.7893 4.46957 18 5 18H15C15.5304 18 16.0391 17.7893 16.4142 17.4142C16.7893 17.0391 17 16.5304 17 16V11" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                    <span class="mt-action-label">Export Data</span>
                    <span class="mt-action-desc">Download CSV</span>
                </button>

                <button id="mt-refresh-btn" class="mt-action-btn mt-action-default">
                    <div class="mt-action-icon">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M14 8C14 11.3137 11.3137 14 8 14C5.68629 14 3.72708 12.6176 2.78549 10.6479" stroke="currentColor" stroke-width="2"/>
                            <path d="M2 8C2 4.68629 4.68629 2 8 2C10.2958 2 12.2729 3.38235 13.2145 5.35206" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                    <span class="mt-action-label">Refresh</span>
                    <span class="mt-action-desc">Update data</span>
                </button>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="mt-assignment-main-grid">
            <!-- Candidates Panel -->
            <div class="mt-panel mt-candidates-panel">
                <div class="mt-panel-header">
                    <h3>Candidates</h3>
                    <div class="mt-panel-actions">
                        <div class="mt-search-wrapper">
                            <input type="text" id="mt-candidates-search" class="mt-search-input" placeholder="Search candidates...">
                            <span class="mt-search-icon">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                    <path d="M7.5 13C10.5376 13 13 10.5376 13 7.5C13 4.46243 10.5376 2 7.5 2C4.46243 2 2 4.46243 2 7.5C2 10.5376 4.46243 13 7.5 13Z" stroke="currentColor" stroke-width="2"/>
                                    <path d="M12 12L14 14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </span>
                        </div>
                        <div class="mt-filter-group">
                            <select id="mt-stage-filter" class="mt-filter-select">
                                <option value="">All Stages</option>
                                <option value="new">New</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                            </select>
                            <select id="mt-category-filter" class="mt-filter-select">
                                <option value="">All Categories</option>
                                <option value="established">Established Companies</option>
                                <option value="startup">Start-ups & New Makers</option>
                                <option value="infrastructure">Infrastructure/Politics/Public</option>
                            </select>
                            <select id="mt-assignment-filter" class="mt-filter-select">
                                <option value="">All Assignments</option>
                                <option value="assigned">Assigned</option>
                                <option value="unassigned">Unassigned</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="mt-panel-content">
                    <div id="mt-candidates-list" class="mt-candidates-list">
                        <!-- Candidates will be loaded here dynamically -->
                    </div>
                </div>
            </div>

            <!-- Jury Panel -->
            <div class="mt-panel mt-jury-panel">
                <div class="mt-panel-header">
                    <h3>Jury Members</h3>
                    <div class="mt-panel-actions">
                        <div class="mt-search-wrapper">
                            <input type="text" id="mt-jury-search" class="mt-search-input" placeholder="Search jury members...">
                            <span class="mt-search-icon">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                    <path d="M7.5 13C10.5376 13 13 10.5376 13 7.5C13 4.46243 10.5376 2 7.5 2C4.46243 2 2 4.46243 2 7.5C2 10.5376 4.46243 13 7.5 13Z" stroke="currentColor" stroke-width="2"/>
                                    <path d="M12 12L14 14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="mt-panel-content">
                    <div id="mt-jury-list" class="mt-jury-list">
                        <!-- Jury members will be loaded here dynamically -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast Container -->
        <div id="mt-toast-container" class="mt-toast-container"></div>

        <!-- Auto-Assign Modal -->
        <div id="mt-auto-assign-modal" class="mt-modal">
            <div class="mt-modal-backdrop"></div>
            <div class="mt-modal-container">
                <div class="mt-modal-content">
                    <div class="mt-modal-header">
                        <h3 class="mt-modal-title">Auto-Assign Candidates</h3>
                        <button class="mt-modal-close">&times;</button>
                    </div>
                    <div class="mt-modal-body">
                        <div class="mt-form-group">
                            <label for="mt-candidates-per-jury">Candidates per Jury Member</label>
                            <input type="number" id="mt-candidates-per-jury" class="mt-input" min="1" max="20" value="8">
                        </div>
                        <div class="mt-form-group">
                            <label>Assignment Algorithm</label>
                            <div class="mt-algorithm-options">
                                <div class="mt-algorithm-card" data-algorithm="balanced">
                                    <h4>Balanced Distribution</h4>
                                    <p>Evenly distribute candidates across all jury members</p>
                                </div>
                                <div class="mt-algorithm-card" data-algorithm="expertise">
                                    <h4>Expertise Matching</h4>
                                    <p>Match candidates with jury members based on expertise</p>
                                </div>
                                <div class="mt-algorithm-card" data-algorithm="random">
                                    <h4>Random Assignment</h4>
                                    <p>Randomly assign candidates to jury members</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-form-group">
                            <label class="mt-checkbox">
                                <input type="checkbox" id="mt-balance-categories" checked>
                                <span>Balance categories across jury members</span>
                            </label>
                            <label class="mt-checkbox">
                                <input type="checkbox" id="mt-match-expertise" checked>
                                <span>Match jury expertise with candidate categories</span>
                            </label>
                            <label class="mt-checkbox">
                                <input type="checkbox" id="mt-clear-existing">
                                <span>Clear existing assignments</span>
                            </label>
                        </div>
                    </div>
                    <div class="mt-modal-footer">
                        <button class="mt-btn mt-btn-ghost mt-modal-close">Cancel</button>
                        <button id="mt-execute-auto-assign" class="mt-btn mt-btn-primary">
                            Execute Auto-Assignment
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
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