<?php
/**
 * Enhanced Assignment Management Template
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

// Get categories for filter
$categories = get_terms(array(
    'taxonomy' => 'mt_category',
    'hide_empty' => false
));
?>

<div class="wrap">
    <div id="mt-assignment-interface">
        <div class="mt-assignment-container">
            
            <!-- Header -->
            <div class="mt-assignment-header">
                <h1>🏆 Jury Assignment System</h1>
                <p>Advanced Assignment Interface v3.2 - Mobility Trailblazers 2025</p>
            </div>

            <!-- Status Banner -->
            <div class="mt-status-banner">
                <span class="icon">✅</span>
                <div>
                    <strong>System Status: HEALTHY</strong> | Last check: <?php echo date('H:i:s'); ?> | 
                    Active Phase: <?php echo esc_html($phase_names[$current_phase] ?? $current_phase); ?>
                </div>
            </div>

            <!-- Statistics Grid -->
            <div class="mt-stats-grid">
                <div class="mt-stat-card">
                    <span class="mt-stat-number mt-stat-total-candidates"><?php echo $total_candidates; ?></span>
                    <div class="mt-stat-label">Total Candidates</div>
                </div>
                <div class="mt-stat-card">
                    <span class="mt-stat-number mt-stat-total-jury"><?php echo $total_jury; ?></span>
                    <div class="mt-stat-label">Jury Members</div>
                </div>
                <div class="mt-stat-card">
                    <span class="mt-stat-number mt-stat-assigned-count"><?php echo $assigned_count; ?></span>
                    <div class="mt-stat-label">Total Assignments</div>
                </div>
                <div class="mt-stat-card">
                    <span class="mt-stat-number mt-stat-completion-rate"><?php echo number_format($completion_rate, 1); ?>%</span>
                    <div class="mt-stat-label">Completion Rate</div>
                </div>
                <div class="mt-stat-card">
                    <span class="mt-stat-number mt-stat-avg-per-jury"><?php echo number_format($avg_per_jury, 1); ?></span>
                    <div class="mt-stat-label">Avg Per Jury</div>
                </div>
            </div>

            <!-- Assignment Controls -->
            <div class="mt-assignment-controls">
                <h3>🔧 Assignment Tools</h3>
                
                <div class="mt-controls-row">
                    <button id="mt-auto-assign-btn" class="mt-btn mt-btn-success">
                        ⚡ Auto-Assign
                    </button>
                    <button id="mt-manual-assign-btn" class="mt-btn mt-btn-warning" disabled>
                        👥 Assign Selected (<span class="mt-selected-candidates-count">0</span> → <span class="mt-selected-jury-name">None</span>)
                    </button>
                    <button id="mt-export-btn" class="mt-btn mt-btn-primary">
                        📊 Export Data
                    </button>
                    <button id="mt-import-btn" class="mt-btn mt-btn-secondary">
                        📥 Import Data
                    </button>
                    <button id="mt-refresh-btn" class="mt-btn mt-btn-secondary">
                        🔄 Refresh Data
                    </button>
                </div>

                <div class="mt-controls-row">
                    <div class="mt-control-group">
                        <label>Stage Filter:</label>
                        <select id="mt-stage-filter">
                            <option value="">All Stages</option>
                            <option value="longlist">Longlist (~200)</option>
                            <option value="shortlist" selected>Shortlist (50)</option>
                            <option value="finalist">Finalist (25)</option>
                        </select>
                    </div>
                    <div class="mt-control-group">
                        <label>Category Filter:</label>
                        <select id="mt-category-filter">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo esc_attr($category->slug); ?>">
                                    <?php echo esc_html($category->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mt-control-group">
                        <label>Assignment Status:</label>
                        <select id="mt-assignment-filter">
                            <option value="">All Candidates</option>
                            <option value="assigned">Assigned</option>
                            <option value="unassigned">Unassigned</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Main Assignment Grid -->
            <div class="mt-assignment-grid">
                <!-- Candidates Panel -->
                <div class="mt-candidates-panel">
                    <div class="mt-panel-header">
                        <h3>📋 Candidates (<span class="mt-candidates-count"><?php echo $total_candidates; ?></span>)</h3>
                        <div style="display: flex; gap: 10px;">
                            <button id="mt-select-all-candidates" class="mt-btn mt-btn-secondary">Select All</button>
                            <button id="mt-clear-selection" class="mt-btn mt-btn-secondary">Clear</button>
                        </div>
                    </div>
                    <div class="mt-panel-content">
                        <input type="text" id="mt-candidates-search" class="mt-search-box" placeholder="Search candidates...">
                        
                        <div class="mt-filters">
                            <button class="mt-filter-tag active" data-category="">All</button>
                            <?php foreach ($categories as $category): ?>
                                <button class="mt-filter-tag" data-category="<?php echo esc_attr($category->slug); ?>">
                                    <?php echo esc_html($category->name); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <div id="mt-candidates-list">
                            <div class="mt-loading">
                                <div class="mt-spinner"></div>
                                <p>Loading candidates...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Jury Panel -->
                <div class="mt-jury-panel">
                    <div class="mt-panel-header">
                        <h3>👨‍⚖️ Jury Members (<?php echo $total_jury; ?>)</h3>
                        <div style="display: flex; gap: 10px;">
                            <button id="mt-matrix-view-btn" class="mt-btn mt-btn-secondary">
                                📊 Matrix View
                            </button>
                            <button id="mt-health-check-btn" class="mt-btn mt-btn-secondary">
                                💚 Health Check
                            </button>
                        </div>
                    </div>
                    <div class="mt-panel-content">
                        <input type="text" id="mt-jury-search" class="mt-search-box" placeholder="Search jury members...">
                        
                        <div id="mt-jury-list">
                            <div class="mt-loading">
                                <div class="mt-spinner"></div>
                                <p>Loading jury members...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Management Section -->
            <div class="mt-assignment-controls" style="margin-top: 20px;">
                <h3>📊 Data Management</h3>
                <div class="mt-controls-row">
                    <button id="mt-export-assignments-btn" class="mt-btn mt-btn-primary">
                        📊 Export Assignments
                    </button>
                    <button id="mt-sync-system-btn" class="mt-btn mt-btn-secondary">
                        🔄 Sync System
                    </button>
                    <button id="mt-view-progress-btn" class="mt-btn mt-btn-warning">
                        📈 View Progress Data
                    </button>
                    <button id="mt-reset-assignments-btn" class="mt-btn mt-btn-danger">
                        ⚠️ Reset All Assignments
                    </button>
                </div>
            </div>

            <!-- Quick Actions Bar -->
            <div class="mt-quick-actions-bar">
                <h4>Quick Actions</h4>
                <div class="mt-quick-actions">
                    <button class="mt-quick-action" data-action="assign-unassigned">
                        <span class="dashicons dashicons-admin-users"></span>
                        <span>Assign All Unassigned</span>
                    </button>
                    <button class="mt-quick-action" data-action="balance-assignments">
                        <span class="dashicons dashicons-performance"></span>
                        <span>Balance Assignments</span>
                    </button>
                    <button class="mt-quick-action" data-action="generate-report">
                        <span class="dashicons dashicons-analytics"></span>
                        <span>Generate Report</span>
                    </button>
                    <button class="mt-quick-action" data-action="email-jury">
                        <span class="dashicons dashicons-email"></span>
                        <span>Email All Jury</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Auto-Assignment Modal -->
        <div id="mt-auto-assign-modal" class="mt-assignment-modal">
            <div class="mt-modal-content">
                <div class="mt-modal-header">
                    <h3 class="mt-modal-title">
                        🤖 Intelligent Auto-Assignment Configuration
                    </h3>
                    <button class="mt-close-btn">&times;</button>
                </div>
                
                <div class="mt-modal-body">
                    <div class="mt-control-group">
                        <label for="mt-candidates-per-jury">Candidates per Jury Member:</label>
                        <input type="number" id="mt-candidates-per-jury" value="<?php echo floor($total_candidates / max($total_jury, 1)); ?>" min="1" max="25">
                        <small style="color: #718096; display: block; margin-top: 5px;">
                            Recommended: 8-15 candidates per jury member (Current: <?php echo $total_candidates; ?> candidates ÷ <?php echo $total_jury; ?> jury = <?php echo floor($total_candidates / max($total_jury, 1)); ?>)
                        </small>
                    </div>

                    <div class="mt-control-group" style="margin-top: 20px;">
                        <label>Distribution Algorithm:</label>
                        <div class="mt-algorithm-options">
                            <div class="mt-algorithm-option selected" data-algorithm="balanced">
                                <strong>Balanced Distribution</strong>
                                <p>Equal distribution across all jury members</p>
                            </div>
                            <div class="mt-algorithm-option" data-algorithm="random">
                                <strong>Random Distribution</strong>
                                <p>Random assignment maintaining balance</p>
                            </div>
                            <div class="mt-algorithm-option" data-algorithm="expertise">
                                <strong>Expertise-Based Matching</strong>
                                <p>Match jury expertise with candidate categories</p>
                            </div>
                            <div class="mt-algorithm-option" data-algorithm="category">
                                <strong>Category-Balanced</strong>
                                <p>Ensure category representation per jury member</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-control-group" style="margin-top: 20px;">
                        <label>Optimization Options:</label>
                        <div class="mt-optimization-checkboxes">
                            <div class="mt-checkbox-item">
                                <input type="checkbox" id="mt-balance-categories" checked>
                                <label for="mt-balance-categories">Balance category representation</label>
                            </div>
                            <div class="mt-checkbox-item">
                                <input type="checkbox" id="mt-match-expertise">
                                <label for="mt-match-expertise">Match jury expertise with candidate categories</label>
                            </div>
                            <div class="mt-checkbox-item">
                                <input type="checkbox" id="mt-avoid-conflicts">
                                <label for="mt-avoid-conflicts">Avoid conflicts of interest</label>
                            </div>
                            <div class="mt-checkbox-item">
                                <input type="checkbox" id="mt-clear-existing">
                                <label for="mt-clear-existing">Clear existing assignments first</label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-assignment-preview" style="margin-top: 20px;">
                        <h4>Assignment Preview</h4>
                        <div id="mt-preview-content" class="mt-preview-box">
                            <p>Configure settings above to preview assignment distribution</p>
                        </div>
                    </div>

                    <div class="mt-loading" id="mt-assignment-loading">
                        <div class="mt-spinner"></div>
                        <p>Processing assignments...</p>
                    </div>
                </div>

                <div class="mt-modal-footer">
                    <button class="mt-btn mt-btn-secondary mt-close-btn">Cancel</button>
                    <button id="mt-preview-assignments" class="mt-btn mt-btn-primary">Preview</button>
                    <button id="mt-execute-auto-assign" class="mt-btn mt-btn-success">
                        ✅ Execute Auto-Assignment
                    </button>
                </div>
            </div>
        </div>

        <!-- Matrix View Modal -->
        <div id="mt-matrix-view-modal" class="mt-assignment-modal">
            <div class="mt-modal-content mt-modal-wide">
                <div class="mt-modal-header">
                    <h3 class="mt-modal-title">
                        📊 Assignment Matrix View
                    </h3>
                    <button class="mt-close-matrix-btn mt-close-btn">&times;</button>
                </div>
                <div class="mt-modal-body">
                    <div id="mt-matrix-container">
                        <!-- Matrix will be loaded here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Health Check Modal -->
        <div id="mt-health-check-modal" class="mt-assignment-modal">
            <div class="mt-modal-content">
                <div class="mt-modal-header">
                    <h3 class="mt-modal-title">
                        💚 Assignment Health Check
                    </h3>
                    <button class="mt-close-health-btn mt-close-btn">&times;</button>
                </div>
                <div class="mt-modal-body">
                    <div id="mt-health-check-results">
                        <!-- Health check results will be loaded here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification Container -->
        <div id="mt-notification-container"></div>
    </div>
</div>

<!-- Additional styles for algorithm options and other elements -->
<style>
.mt-algorithm-options {
    display: grid;
    gap: 12px;
}

.mt-algorithm-option {
    padding: 15px;
    border: 2px solid #e9ecef;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.mt-algorithm-option:hover {
    border-color: #5e72e4;
    background: #f6f9fc;
}

.mt-algorithm-option.selected {
    border-color: #5e72e4;
    background: #f0f9ff;
}

.mt-algorithm-option strong {
    display: block;
    color: #32325d;
    margin-bottom: 5px;
}

.mt-algorithm-option p {
    margin: 0;
    color: #8898aa;
    font-size: 0.875rem;
}

.mt-optimization-checkboxes {
    display: grid;
    gap: 10px;
}

.mt-checkbox-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.mt-checkbox-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
}

.mt-checkbox-item label {
    color: #32325d;
    font-weight: normal;
    cursor: pointer;
    margin: 0;
}

.mt-preview-box {
    background: #f6f9fc;
    padding: 15px;
    border-radius: 4px;
    max-height: 200px;
    overflow-y: auto;
}

.mt-modal-wide {
    max-width: 90%;
    width: 1200px;
}

.mt-loading.show {
    display: block;
}

.mt-health-success {
    background: #d4f1f4;
    color: #065f46;
    padding: 15px;
    border-radius: 6px;
    margin: 15px 0;
    font-weight: 600;
}

.mt-health-issues {
    display: grid;
    gap: 10px;
    margin: 15px 0;
}

.mt-health-issue {
    padding: 12px;
    border-radius: 6px;
    font-size: 0.875rem;
}

.mt-health-issue.mt-health-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.mt-health-issue.mt-health-warning {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
}

.mt-issue-details {
    margin-top: 5px;
    font-size: 0.8rem;
    opacity: 0.8;
}

.mt-matrix-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.mt-matrix-table th,
.mt-matrix-table td {
    padding: 10px;
    text-align: center;
    border: 1px solid #e9ecef;
}

.mt-matrix-table th {
    background: #f6f9fc;
    font-weight: 600;
    color: #32325d;
}

.mt-matrix-table td {
    background: white;
}

.mt-matrix-cell {
    font-weight: 600;
}

.mt-matrix-total {
    background: #f0f9ff !important;
    font-weight: 700;
    color: #5e72e4;
}
</style>