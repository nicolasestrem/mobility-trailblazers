<?php
/**
 * Assignment Management Template - Enhanced Version
 * File: /wp-content/plugins/mobility-trailblazers/templates/assignment-template.php
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
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
?>

<div id="mt-assignment-interface" class="mt-assignment-interface">
    <div class="mt-assignment-container">
        
        <!-- Enhanced Header with Gradient -->
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

        <!-- Live Status Card -->
        <div class="mt-status-card">
            <div class="mt-status-content">
                <div class="mt-status-icon pulse">
                    <span class="mt-status-dot"></span>
                </div>
                <div class="mt-status-info">
                    <strong>System Status: OPERATIONAL</strong>
                    <span class="mt-separator">•</span>
                    <span>Last sync: <?php echo date('H:i:s'); ?></span>
                    <span class="mt-separator">•</span>
                    <span><?php echo $total_jury; ?> active jury members</span>
                </div>
            </div>
        </div>

        <!-- Enhanced Statistics Dashboard -->
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
                <div class="mt-stat-trend">
                    <span class="trend-up">↑ 12%</span>
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
                <div class="mt-stat-trend">
                    <span class="trend-stable">→ 0%</span>
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
                <div class="mt-stat-trend">
                    <span class="trend-up">↑ 24%</span>
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
                <div class="mt-stat-progress">
                    <div class="mt-mini-progress">
                        <div class="mt-mini-progress-fill" style="width: <?php echo $completion_rate; ?>%"></div>
                    </div>
                </div>
            </div>

            <div class="mt-stat-card mt-stat-warning">
                <div class="mt-stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2C13.3132 2 14.6136 2.25866 15.8268 2.7612C17.0401 3.26375 18.1425 4.00035 19.0711 4.92893C19.9997 5.85752 20.7362 6.95991 21.2388 8.17317C21.7413 9.38642 22 10.6868 22 12C22 14.6522 20.9464 17.1957 19.0711 19.0711C17.1957 20.9464 14.6522 22 12 22C10.6868 22 9.38642 21.7413 8.17317 21.2388C6.95991 20.7362 5.85752 19.9997 4.92893 19.0711C3.05357 17.1957 2 14.6522 2 12C2 9.34784 3.05357 6.8043 4.92893 4.92893C6.8043 3.05357 9.34784 2 12 2ZM12 17C12.2652 17 12.5196 17.1054 12.7071 17.2929C12.8946 17.4804 13 17.7348 13 18C13 18.2652 12.8946 18.5196 12.7071 18.7071C12.5196 18.8946 12.2652 19 12 19C11.7348 19 11.4804 18.8946 11.2929 18.7071C11.1054 18.5196 11 18.2652 11 18C11 17.7348 11.1054 17.4804 11.2929 17.2929C11.4804 17.1054 11.7348 17 12 17ZM12 7C12.2652 7 12.5196 7.10536 12.7071 7.29289C12.8946 7.48043 13 7.73478 13 8V14C13 14.2652 12.8946 14.5196 12.7071 14.7071C12.5196 14.8946 12.2652 15 12 15C11.7348 15 11.4804 14.8946 11.2929 14.7071C11.1054 14.5196 11 14.2652 11 14V8C11 7.73478 11.1054 7.48043 11.2929 7.29289C11.4804 7.10536 11.7348 7 12 7Z" fill="currentColor"/>
                    </svg>
                </div>
                <div class="mt-stat-content">
                    <span class="mt-stat-number mt-stat-avg-per-jury"><?php echo number_format($avg_per_jury, 1); ?></span>
                    <span class="mt-stat-label">Avg. per Jury</span>
                </div>
                <div class="mt-stat-info-text">
                    <span class="info-text">Target: 8-15</span>
                </div>
            </div>
        </div>

        <!-- Quick Actions Bar -->
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
                            <path d="M4 10C4 13.3137 6.68629 16 10 16C12.2958 16 14.2729 14.6176 15.2145 12.6479M16 10C16 6.68629 13.3137 4 10 4C7.70416 4 5.72708 5.38235 4.78549 7.35206M1 7H7M7 7V1M7 7L4 4M19 13H13M13 13V19M13 13L16 16" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                    <span class="mt-action-label">Refresh</span>
                    <span class="mt-action-desc">Update data</span>
                </button>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="mt-filter-controls">
            <div class="mt-filter-header">
                <h3>Filters & Search</h3>
            </div>
            
            <div class="mt-filter-grid">
                <div class="mt-filter-group">
                    <label>Stage</label>
                    <select id="mt-stage-filter" class="mt-filter-select">
                        <option value="">All Stages</option>
                        <option value="longlist">Longlist (~200)</option>
                        <option value="shortlist" selected>Shortlist (50)</option>
                        <option value="finalist">Finalist (25)</option>
                    </select>
                </div>

                <div class="mt-filter-group">
                    <label>Category</label>
                    <select id="mt-category-filter" class="mt-filter-select">
                        <option value="">All Categories</option>
                        <option value="established-companies">Established Companies</option>
                        <option value="startups-new-makers">Start-ups & New Makers</option>
                        <option value="infrastructure-politics-public">Infrastructure/Politics/Public</option>
                    </select>
                </div>

                <div class="mt-filter-group">
                    <label>Assignment</label>
                    <select id="mt-assignment-filter" class="mt-filter-select">
                        <option value="">All Status</option>
                        <option value="assigned">Assigned</option>
                        <option value="unassigned">Unassigned</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Main Assignment Grid -->
        <div class="mt-assignment-main-grid">
            <!-- Candidates Panel -->
            <div class="mt-panel mt-candidates-panel">
                <div class="mt-panel-header">
                    <div class="mt-panel-title">
                        <h3>Candidates</h3>
                        <span class="mt-count-badge mt-candidates-count"><?php echo $total_candidates; ?></span>
                    </div>
                    <div class="mt-panel-actions">
                        <button id="mt-select-all-candidates" class="mt-btn-small">Select All</button>
                        <button id="mt-clear-selection" class="mt-btn-small mt-btn-ghost">Clear</button>
                    </div>
                </div>
                
                <div class="mt-panel-search">
                    <div class="mt-search-input-wrapper">
                        <svg class="mt-search-icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M7 13C10.3137 13 13 10.3137 13 7C13 3.68629 10.3137 1 7 1C3.68629 1 1 3.68629 1 7C1 10.3137 3.68629 13 7 13Z" stroke="currentColor" stroke-width="2"/>
                            <path d="M15 15L11 11" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <input type="text" id="mt-candidates-search" placeholder="Search candidates..." class="mt-search-input">
                    </div>
                </div>

                <div class="mt-category-tags">
                    <button class="mt-tag mt-tag-active" data-category="">All</button>
                    <button class="mt-tag" data-category="established-companies">Established</button>
                    <button class="mt-tag" data-category="startups-new-makers">Start-ups</button>
                    <button class="mt-tag" data-category="infrastructure-politics-public">Public</button>
                </div>

                <div class="mt-panel-content">
                    <div id="mt-candidates-list" class="mt-items-list">
                        <!-- Candidates will be loaded here -->
                        <div class="mt-loading-placeholder">
                            <div class="mt-spinner"></div>
                            <p>Loading candidates...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Jury Panel -->
            <div class="mt-panel mt-jury-panel">
                <div class="mt-panel-header">
                    <div class="mt-panel-title">
                        <h3>Jury Members</h3>
                        <span class="mt-count-badge"><?php echo $total_jury; ?></span>
                    </div>
                    <div class="mt-panel-actions">
                        <button id="mt-matrix-view-btn" class="mt-btn-small">Matrix</button>
                        <button id="mt-balance-btn" class="mt-btn-small mt-btn-ghost">Balance</button>
                    </div>
                </div>

                <div class="mt-panel-search">
                    <div class="mt-search-input-wrapper">
                        <svg class="mt-search-icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M7 13C10.3137 13 13 10.3137 13 7C13 3.68629 10.3137 1 7 1C3.68629 1 1 3.68629 1 7C1 10.3137 3.68629 13 7 13Z" stroke="currentColor" stroke-width="2"/>
                            <path d="M15 15L11 11" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <input type="text" id="mt-jury-search" placeholder="Search jury members..." class="mt-search-input">
                    </div>
                </div>

                <div class="mt-panel-content">
                    <div id="mt-jury-list" class="mt-items-list">
                        <!-- Jury members will be loaded here -->
                        <div class="mt-loading-placeholder">
                            <div class="mt-spinner"></div>
                            <p>Loading jury members...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Management Section -->
        <div class="mt-data-management">
            <div class="mt-section-header">
                <h3>Data Management</h3>
                <p>Export, sync, and manage your assignment data</p>
            </div>
            
            <div class="mt-data-actions">
                <button id="mt-export-assignments-btn" class="mt-data-btn">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M10 1V11M10 11L7 8M10 11L13 8" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <span>Export Assignments</span>
                </button>
                
                <button id="mt-sync-system-btn" class="mt-data-btn">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M4 10C4 13.3137 6.68629 16 10 16C12.2958 16 14.2729 14.6176 15.2145 12.6479" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <span>Sync System</span>
                </button>
                
                <button id="mt-view-progress-btn" class="mt-data-btn">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M9 4H11V11H9V4ZM9 15H11V17H9V15Z" fill="currentColor"/>
                    </svg>
                    <span>View Progress</span>
                </button>
                
                <button id="mt-reset-assignments-btn" class="mt-data-btn mt-data-btn-danger">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M3 6H17M8 6V4C8 3.44772 8.44772 3 9 3H11C11.5523 3 12 3.44772 12 4V6M10 11V16" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <span>Reset All</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Enhanced Auto-Assignment Modal -->
    <div id="mt-auto-assign-modal" class="mt-modal">
        <div class="mt-modal-backdrop"></div>
        <div class="mt-modal-container">
            <div class="mt-modal-content">
                <div class="mt-modal-header">
                    <h3 class="mt-modal-title">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M12 2L13.5 7L19 8L15.5 11.5L17 17L12 14L7 17L8.5 11.5L5 8L10.5 7L12 2Z" fill="currentColor"/>
                        </svg>
                        Intelligent Auto-Assignment
                    </h3>
                    <button class="mt-modal-close">&times;</button>
                </div>
                
                <div class="mt-modal-body">
                    <div class="mt-form-section">
                        <label class="mt-form-label">Candidates per Jury Member</label>
                        <div class="mt-input-group">
                            <input type="number" id="mt-candidates-per-jury" class="mt-form-input" value="<?php echo floor($total_candidates / max($total_jury, 1)); ?>" min="1" max="25">
                            <div class="mt-input-help">
                                Recommended: 8-15 candidates per jury member
                                <br>
                                <small>Current: <?php echo $total_candidates; ?> candidates ÷ <?php echo $total_jury; ?> jury = <?php echo floor($total_candidates / max($total_jury, 1)); ?> per member</small>
                            </div>
                        </div>
                    </div>

                    <div class="mt-form-section">
                        <label class="mt-form-label">Distribution Algorithm</label>
                        <div class="mt-algorithm-grid">
                            <div class="mt-algorithm-card selected" data-algorithm="balanced">
                                <div class="mt-algorithm-icon">⚖️</div>
                                <h4>Balanced</h4>
                                <p>Equal distribution across all jury members</p>
                            </div>
                            <div class="mt-algorithm-card" data-algorithm="random">
                                <div class="mt-algorithm-icon">🎲</div>
                                <h4>Random</h4>
                                <p>Random assignment with balance</p>
                            </div>
                            <div class="mt-algorithm-card" data-algorithm="expertise">
                                <div class="mt-algorithm-icon">🎯</div>
                                <h4>Expertise</h4>
                                <p>Match expertise with categories</p>
                            </div>
                            <div class="mt-algorithm-card" data-algorithm="category">
                                <div class="mt-algorithm-icon">📊</div>
                                <h4>Category</h4>
                                <p>Ensure category diversity</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-form-section">
                        <label class="mt-form-label">Additional Options</label>
                        <div class="mt-checkbox-group">
                            <label class="mt-checkbox">
                                <input type="checkbox" id="mt-balance-categories" checked>
                                <span>Balance category representation</span>
                            </label>
                            <label class="mt-checkbox">
                                <input type="checkbox" id="mt-match-expertise">
                                <span>Match jury expertise with candidate categories</span>
                            </label>
                            <label class="mt-checkbox">
                                <input type="checkbox" id="mt-clear-existing">
                                <span>Clear existing assignments first</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-loading-state" id="mt-assignment-loading">
                        <div class="mt-spinner-large"></div>
                        <p>Processing assignments...</p>
                        <div class="mt-progress-bar">
                            <div class="mt-progress-fill" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <div class="mt-modal-footer">
                    <button class="mt-btn mt-btn-ghost mt-modal-close">Cancel</button>
                    <button id="mt-execute-auto-assign" class="mt-btn mt-btn-primary">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M8 1L10 5L14 6L11 9L12 13L8 11L4 13L5 9L2 6L6 5L8 1Z" fill="currentColor"/>
                        </svg>
                        Execute Auto-Assignment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notifications Container -->
    <div id="mt-toast-container"></div>
</div>

<style>
/* Modern CSS Variables */
:root {
    --mt-primary: #2c5282;
    --mt-primary-dark: #1a365d;
    --mt-primary-light: #3182ce;
    --mt-secondary: #ed8936;
    --mt-accent: #38b2ac;
    --mt-success: #38a169;
    --mt-error: #e53e3e;
    --mt-warning: #d69e2e;
    --mt-info: #3182ce;
    --mt-light: #f7fafc;
    --mt-dark: #1a202c;
    --mt-gray-50: #f9fafb;
    --mt-gray-100: #f3f4f6;
    --mt-gray-200: #e5e7eb;
    --mt-gray-300: #d1d5db;
    --mt-gray-400: #9ca3af;
    --mt-gray-500: #6b7280;
    --mt-gray-600: #4b5563;
    --mt-gray-700: #374151;
    --mt-gray-800: #1f2937;
    --mt-gray-900: #111827;
    --mt-shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --mt-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    --mt-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --mt-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --mt-shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    --mt-radius: 0.5rem;
    --mt-radius-lg: 0.75rem;
    --mt-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Base Styles */
.mt-assignment-interface {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    color: var(--mt-gray-800);
    line-height: 1.6;
    background: var(--mt-gray-50);
    min-height: 100vh;
    padding: 1rem;
}

.mt-assignment-interface * {
    box-sizing: border-box;
}

.mt-assignment-container {
    max-width: 1400px;
    margin: 0 auto;
}

/* Enhanced Header */
.mt-assignment-header {
    background: linear-gradient(135deg, var(--mt-primary) 0%, var(--mt-primary-dark) 100%);
    color: white;
    padding: 2rem;
    border-radius: var(--mt-radius-lg);
    margin-bottom: 2rem;
    box-shadow: var(--mt-shadow-xl);
}

.mt-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.mt-logo-section {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.mt-logo-icon {
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.mt-assignment-header h1 {
    margin: 0;
    font-size: 1.875rem;
    font-weight: 700;
    letter-spacing: -0.025em;
}

.mt-subtitle {
    margin: 0.25rem 0 0;
    font-size: 0.875rem;
    opacity: 0.9;
}

.mt-phase-indicator {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    padding: 0.625rem 1.25rem;
    border-radius: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.mt-phase-label {
    font-size: 0.75rem;
    opacity: 0.8;
    margin-right: 0.5rem;
}

.mt-phase-value {
    font-weight: 600;
}

/* Status Card */
.mt-status-card {
    background: white;
    border-radius: var(--mt-radius);
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--mt-shadow);
    border-left: 4px solid var(--mt-success);
}

.mt-status-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.mt-status-icon {
    position: relative;
}

.mt-status-dot {
    width: 12px;
    height: 12px;
    background: var(--mt-success);
    border-radius: 50%;
    display: block;
}

.mt-status-icon.pulse .mt-status-dot {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(56, 161, 105, 0.4);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(56, 161, 105, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(56, 161, 105, 0);
    }
}

.mt-status-info {
    font-size: 0.875rem;
    color: var(--mt-gray-600);
}

.mt-separator {
    color: var(--mt-gray-300);
    margin: 0 0.5rem;
}

/* Statistics Dashboard */
.mt-stats-dashboard {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.25rem;
    margin-bottom: 2rem;
}

.mt-stat-card {
    background: white;
    border-radius: var(--mt-radius-lg);
    padding: 1.5rem;
    box-shadow: var(--mt-shadow);
    position: relative;
    overflow: hidden;
    transition: var(--mt-transition);
}

.mt-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--mt-shadow-lg);
}

.mt-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: currentColor;
}

.mt-stat-primary { color: var(--mt-primary); }
.mt-stat-secondary { color: var(--mt-secondary); }
.mt-stat-success { color: var(--mt-success); }
.mt-stat-info { color: var(--mt-info); }
.mt-stat-warning { color: var(--mt-warning); }

.mt-stat-icon {
    width: 40px;
    height: 40px;
    background: currentColor;
    opacity: 0.1;
    border-radius: var(--mt-radius);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.mt-stat-icon svg {
    width: 24px;
    height: 24px;
    opacity: 10;
}

.mt-stat-number {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
    display: block;
    color: var(--mt-gray-900);
    margin-bottom: 0.25rem;
}

.mt-stat-label {
    font-size: 0.875rem;
    color: var(--mt-gray-500);
    font-weight: 500;
}

.mt-stat-trend {
    position: absolute;
    top: 1rem;
    right: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.trend-up { color: var(--mt-success); }
.trend-down { color: var(--mt-error); }
.trend-stable { color: var(--mt-gray-400); }

.mt-stat-progress {
    margin-top: 1rem;
}

.mt-mini-progress {
    height: 4px;
    background: var(--mt-gray-200);
    border-radius: 2px;
    overflow: hidden;
}

.mt-mini-progress-fill {
    height: 100%;
    background: currentColor;
    transition: width 0.3s ease;
}

.mt-stat-info-text {
    font-size: 0.75rem;
    color: var(--mt-gray-500);
    margin-top: 0.5rem;
}

/* Quick Actions */
.mt-quick-actions {
    background: white;
    border-radius: var(--mt-radius-lg);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--mt-shadow);
}

.mt-actions-header {
    margin-bottom: 1.5rem;
}

.mt-actions-header h3 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--mt-gray-900);
}

.mt-actions-subtitle {
    font-size: 0.875rem;
    color: var(--mt-gray-500);
}

.mt-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.mt-action-btn {
    background: var(--mt-gray-50);
    border: 2px solid var(--mt-gray-200);
    border-radius: var(--mt-radius);
    padding: 1rem;
    text-align: center;
    cursor: pointer;
    transition: var(--mt-transition);
    position: relative;
    overflow: hidden;
}

.mt-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--mt-shadow-md);
}

.mt-action-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.mt-action-primary { 
    background: var(--mt-primary);
    color: white;
    border-color: var(--mt-primary);
}

.mt-action-secondary { 
    background: var(--mt-secondary);
    color: white;
    border-color: var(--mt-secondary);
}

.mt-action-info { 
    background: var(--mt-info);
    color: white;
    border-color: var(--mt-info);
}

.mt-action-icon {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.75rem;
}

.mt-action-label {
    display: block;
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.mt-action-desc {
    font-size: 0.75rem;
    opacity: 0.8;
}

/* Filter Controls */
.mt-filter-controls {
    background: white;
    border-radius: var(--mt-radius-lg);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--mt-shadow);
}

.mt-filter-header {
    margin-bottom: 1rem;
}

.mt-filter-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--mt-gray-900);
}

.mt-filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.mt-filter-group label {
    display: block;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--mt-gray-600);
    margin-bottom: 0.25rem;
}

.mt-filter-select {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--mt-gray-300);
    border-radius: var(--mt-radius);
    font-size: 0.875rem;
    transition: var(--mt-transition);
    background: white;
}

.mt-filter-select:focus {
    outline: none;
    border-color: var(--mt-primary);
    box-shadow: 0 0 0 3px rgba(44, 82, 130, 0.1);
}

/* Main Grid */
.mt-assignment-main-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.mt-panel {
    background: white;
    border-radius: var(--mt-radius-lg);
    box-shadow: var(--mt-shadow);
    overflow: hidden;
}

.mt-panel-header {
    background: var(--mt-gray-50);
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--mt-gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.mt-panel-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.mt-panel-title h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--mt-gray-900);
}

.mt-count-badge {
    background: var(--mt-primary);
    color: white;
    padding: 0.125rem 0.5rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.mt-panel-actions {
    display: flex;
    gap: 0.5rem;
}

.mt-btn-small {
    padding: 0.375rem 0.75rem;
    border: none;
    border-radius: var(--mt-radius);
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    transition: var(--mt-transition);
    background: var(--mt-primary);
    color: white;
}

.mt-btn-ghost {
    background: transparent;
    color: var(--mt-gray-600);
    border: 1px solid var(--mt-gray-300);
}

.mt-btn-small:hover {
    transform: translateY(-1px);
    box-shadow: var(--mt-shadow-sm);
}

.mt-panel-search {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--mt-gray-200);
}

.mt-search-input-wrapper {
    position: relative;
}

.mt-search-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--mt-gray-400);
}

.mt-search-input {
    width: 100%;
    padding: 0.5rem 0.75rem 0.5rem 2.5rem;
    border: 1px solid var(--mt-gray-300);
    border-radius: var(--mt-radius);
    font-size: 0.875rem;
    transition: var(--mt-transition);
}

.mt-search-input:focus {
    outline: none;
    border-color: var(--mt-primary);
    box-shadow: 0 0 0 3px rgba(44, 82, 130, 0.1);
}

.mt-category-tags {
    padding: 0 1.5rem 1rem;
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.mt-tag {
    padding: 0.375rem 0.75rem;
    border: 1px solid var(--mt-gray-300);
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    transition: var(--mt-transition);
    background: white;
    color: var(--mt-gray-600);
}

.mt-tag:hover {
    border-color: var(--mt-primary);
    color: var(--mt-primary);
}

.mt-tag-active {
    background: var(--mt-primary);
    color: white;
    border-color: var(--mt-primary);
}

.mt-panel-content {
    max-height: 600px;
    overflow-y: auto;
}

.mt-items-list {
    padding: 1rem;
}

.mt-loading-placeholder {
    text-align: center;
    padding: 3rem;
    color: var(--mt-gray-500);
}

.mt-spinner {
    width: 32px;
    height: 32px;
    border: 3px solid var(--mt-gray-200);
    border-top-color: var(--mt-primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Data Management */
.mt-data-management {
    background: white;
    border-radius: var(--mt-radius-lg);
    padding: 2rem;
    box-shadow: var(--mt-shadow);
}

.mt-section-header {
    margin-bottom: 1.5rem;
}

.mt-section-header h3 {
    margin: 0 0 0.25rem;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--mt-gray-900);
}

.mt-section-header p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--mt-gray-500);
}

.mt-data-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.mt-data-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    border: 1px solid var(--mt-gray-300);
    border-radius: var(--mt-radius);
    background: white;
    color: var(--mt-gray-700);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: var(--mt-transition);
}

.mt-data-btn:hover {
    border-color: var(--mt-primary);
    color: var(--mt-primary);
    transform: translateY(-1px);
    box-shadow: var(--mt-shadow-sm);
}

.mt-data-btn svg {
    width: 20px;
    height: 20px;
}

.mt-data-btn-danger {
    border-color: var(--mt-error);
    color: var(--mt-error);
}

.mt-data-btn-danger:hover {
    background: var(--mt-error);
    color: white;
}

/* Modal Styles */
.mt-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 100000;
}

.mt-modal.show {
    display: block;
}

.mt-modal-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.mt-modal-container {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    display: flex;
}

.mt-modal-content {
    background: white;
    border-radius: var(--mt-radius-lg);
    box-shadow: var(--mt-shadow-xl);
    width: 100%;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.mt-modal-header {
    background: var(--mt-gray-50);
    padding: 1.5rem;
    border-bottom: 1px solid var(--mt-gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.mt-modal-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--mt-gray-900);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.mt-modal-title svg {
    color: var(--mt-primary);
}

.mt-modal-close {
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    border-radius: var(--mt-radius);
    font-size: 1.5rem;
    color: var(--mt-gray-400);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--mt-transition);
}

.mt-modal-close:hover {
    background: var(--mt-gray-100);
    color: var(--mt-gray-600);
}

.mt-modal-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
}

.mt-form-section {
    margin-bottom: 1.5rem;
}

.mt-form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--mt-gray-700);
    margin-bottom: 0.5rem;
}

.mt-form-input {
    width: 100%;
    padding: 0.625rem 0.875rem;
    border: 1px solid var(--mt-gray-300);
    border-radius: var(--mt-radius);
    font-size: 0.875rem;
    transition: var(--mt-transition);
}

.mt-form-input:focus {
    outline: none;
    border-color: var(--mt-primary);
    box-shadow: 0 0 0 3px rgba(44, 82, 130, 0.1);
}

.mt-input-help {
    margin-top: 0.5rem;
    font-size: 0.75rem;
    color: var(--mt-gray-500);
    line-height: 1.5;
}

.mt-algorithm-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.mt-algorithm-card {
    padding: 1rem;
    border: 2px solid var(--mt-gray-200);
    border-radius: var(--mt-radius);
    text-align: center;
    cursor: pointer;
    transition: var(--mt-transition);
}

.mt-algorithm-card:hover {
    border-color: var(--mt-primary);
    transform: translateY(-2px);
    box-shadow: var(--mt-shadow);
}

.mt-algorithm-card.selected {
    border-color: var(--mt-primary);
    background: var(--mt-gray-50);
}

.mt-algorithm-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.mt-algorithm-card h4 {
    margin: 0 0 0.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--mt-gray-900);
}

.mt-algorithm-card p {
    margin: 0;
    font-size: 0.75rem;
    color: var(--mt-gray-500);
}

.mt-checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.mt-checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-size: 0.875rem;
    color: var(--mt-gray-700);
}

.mt-checkbox input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: var(--mt-primary);
}

.mt-loading-state {
    display: none;
    text-align: center;
    padding: 2rem;
}

.mt-loading-state.show {
    display: block;
}

.mt-spinner-large {
    width: 48px;
    height: 48px;
    border: 4px solid var(--mt-gray-200);
    border-top-color: var(--mt-primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

.mt-progress-bar {
    height: 8px;
    background: var(--mt-gray-200);
    border-radius: 4px;
    overflow: hidden;
    margin-top: 1rem;
}

.mt-progress-fill {
    height: 100%;
    background: var(--mt-primary);
    transition: width 0.3s ease;
}

.mt-modal-footer {
    background: var(--mt-gray-50);
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--mt-gray-200);
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

.mt-btn {
    padding: 0.625rem 1.25rem;
    border: none;
    border-radius: var(--mt-radius);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: var(--mt-transition);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.mt-btn-primary {
    background: var(--mt-primary);
    color: white;
}

.mt-btn-primary:hover {
    background: var(--mt-primary-dark);
    transform: translateY(-1px);
    box-shadow: var(--mt-shadow);
}

.mt-btn-ghost {
    background: transparent;
    color: var(--mt-gray-600);
    border: 1px solid var(--mt-gray-300);
}

.mt-btn-ghost:hover {
    background: var(--mt-gray-50);
    border-color: var(--mt-gray-400);
}

/* Toast Notifications */
#mt-toast-container {
    position: fixed;
    top: 1rem;
    right: 1rem;
    z-index: 100001;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.mt-toast {
    background: white;
    border-radius: var(--mt-radius);
    box-shadow: var(--mt-shadow-lg);
    padding: 1rem 1.5rem;
    min-width: 300px;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.mt-toast-success {
    border-left: 4px solid var(--mt-success);
}

.mt-toast-error {
    border-left: 4px solid var(--mt-error);
}

.mt-toast-info {
    border-left: 4px solid var(--mt-info);
}

/* Responsive Design */
@media (max-width: 1024px) {
    .mt-assignment-main-grid {
        grid-template-columns: 1fr;
    }
    
    .mt-stats-dashboard {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .mt-header-content {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .mt-stats-dashboard {
        grid-template-columns: 1fr;
    }
    
    .mt-actions-grid {
        grid-template-columns: 1fr;
    }
    
    .mt-algorithm-grid {
        grid-template-columns: 1fr;
    }
    
    .mt-data-actions {
        flex-direction: column;
    }
    
    .mt-data-btn {
        width: 100%;
        justify-content: center;
    }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
    .mt-assignment-interface {
        background: var(--mt-gray-900);
        color: var(--mt-gray-100);
    }
    
    .mt-panel,
    .mt-stat-card,
    .mt-quick-actions,
    .mt-filter-controls,
    .mt-data-management,
    .mt-modal-content {
        background: var(--mt-gray-800);
        color: var(--mt-gray-100);
    }
    
    .mt-panel-header,
    .mt-modal-header,
    .mt-modal-footer {
        background: var(--mt-gray-900);
        border-color: var(--mt-gray-700);
    }
    
    .mt-search-input,
    .mt-filter-select,
    .mt-form-input {
        background: var(--mt-gray-700);
        border-color: var(--mt-gray-600);
        color: var(--mt-gray-100);
    }
    
    .mt-btn-ghost,
    .mt-tag,
    .mt-data-btn {
        background: var(--mt-gray-700);
        border-color: var(--mt-gray-600);
        color: var(--mt-gray-100);
    }
    
    .mt-btn-ghost:hover,
    .mt-tag:hover,
    .mt-data-btn:hover {
        background: var(--mt-gray-600);
        border-color: var(--mt-gray-500);
    }
}

/* Print Styles */
@media print {
    .mt-quick-actions,
    .mt-filter-controls,
    .mt-data-management,
    .mt-panel-actions,
    .mt-panel-search,
    .mt-category-tags {
        display: none;
    }
    
    .mt-assignment-main-grid {
        grid-template-columns: 1fr;
    }
    
    .mt-panel-content {
        max-height: none;
    }
}
</style>

<script>
// Enhanced notification system
function showNotification(message, type = 'info') {
    const container = document.getElementById('mt-toast-container');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = `mt-toast mt-toast-${type}`;
    
    const icon = document.createElement('span');
    icon.style.fontSize = '1.25rem';
    switch(type) {
        case 'success':
            icon.textContent = '✓';
            icon.style.color = 'var(--mt-success)';
            break;
        case 'error':
            icon.textContent = '✕';
            icon.style.color = 'var(--mt-error)';
            break;
        case 'warning':
            icon.textContent = '⚠';
            icon.style.color = 'var(--mt-warning)';
            break;
        default:
            icon.textContent = 'ℹ';
            icon.style.color = 'var(--mt-info)';
    }
    
    const messageEl = document.createElement('span');
    messageEl.textContent = message;
    
    const closeBtn = document.createElement('button');
    closeBtn.style.cssText = 'margin-left: auto; background: none; border: none; font-size: 1rem; cursor: pointer; color: var(--mt-gray-400);';
    closeBtn.textContent = '×';
    closeBtn.onclick = () => toast.remove();
    
    toast.appendChild(icon);
    toast.appendChild(messageEl);
    toast.appendChild(closeBtn);
    
    container.appendChild(toast);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (toast.parentElement) {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }
    }, 5000);
}

// Add slide out animation
const styleSheet = document.createElement('style');
styleSheet.textContent = `
@keyframes slideOut {
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}
`;
document.head.appendChild(styleSheet);

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Demo: Show welcome notification
    showNotification('Assignment system ready', 'success');
});
</script>