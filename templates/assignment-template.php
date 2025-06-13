<?php
/**
 * Assignment Management Template
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
                        <option value="established-companies">Established Companies</option>
                        <option value="startups-new-makers">Start-ups & New Makers</option>
                        <option value="infrastructure-politics-public">Infrastructure/Politics/Public</option>
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

        <!-- Assignment Grid -->
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
                        <button class="mt-filter-tag" data-category="established-companies">Established</button>
                        <button class="mt-filter-tag" data-category="startups-new-makers">Start-ups</button>
                        <button class="mt-filter-tag" data-category="infrastructure-politics-public">Politics/Public</button>
                    </div>

                    <div id="mt-candidates-list">
                        <!-- Candidates will be loaded here via JavaScript -->
                        <div style="text-align: center; padding: 20px; color: #718096;">
                            Loading candidates...
                        </div>
                    </div>
                </div>
            </div>

            <!-- Jury Panel -->
            <div class="mt-jury-panel">
                <div class="mt-panel-header">
                    <h3>👨‍⚖️ Jury Members (<?php echo $total_jury; ?>)</h3>
                    <div style="display: flex; gap: 10px;">
                        <button id="mt-matrix-view-btn" class="mt-btn mt-btn-secondary">📊 Matrix View</button>
                        <button id="mt-health-check-btn" class="mt-btn mt-btn-secondary">🏥 Health Check</button>
                    </div>
                </div>
                <div class="mt-panel-content">
                    <input type="text" id="mt-jury-search" class="mt-search-box" placeholder="Search jury members...">
                    
                    <div id="mt-jury-list">
                        <!-- Jury members will be loaded here via JavaScript -->
                        <div style="text-align: center; padding: 20px; color: #718096;">
                            Loading jury members...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Options -->
        <div class="mt-assignment-controls" style="margin-top: 30px;">
            <h3>📤 Data Management</h3>
            <div class="mt-export-options">
                <button id="mt-export-assignments-btn" class="mt-btn mt-btn-primary">Export Assignments</button>
                <button id="mt-sync-system-btn" class="mt-btn mt-btn-secondary">Sync System</button>
                <button id="mt-view-progress-btn" class="mt-btn mt-btn-warning">View Progress Data</button>
                <button id="mt-reset-assignments-btn" class="mt-btn mt-btn-secondary">Reset All Assignments</button>
            </div>
        </div>
    </div>

    <!-- Auto-Assignment Modal -->
    <div id="mt-auto-assign-modal" class="mt-assignment-modal">
        <div class="mt-modal-content">
            <div class="mt-modal-header">
                <h3 class="mt-modal-title">🤖 Intelligent Auto-Assignment Configuration</h3>
                <button class="mt-close-btn">&times;</button>
            </div>
            
            <div class="mt-control-group">
                <label for="mt-candidates-per-jury">Candidates per Jury Member:</label>
                <input type="number" id="mt-candidates-per-jury" value="<?php echo floor($total_candidates / max($total_jury, 1)); ?>" min="1" max="25">
                <small style="color: var(--mt-gray); display: block; margin-top: 5px;">
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
                        <input type="checkbox" id="mt-clear-existing">
                        <label for="mt-clear-existing">Clear existing assignments first</label>
                    </div>
                </div>
            </div>

            <div class="mt-loading" id="mt-assignment-loading">
                <div class="mt-spinner"></div>
                <p>Processing assignments...</p>
            </div>

            <div style="display: flex; gap: 15px; margin-top: 30px; justify-content: flex-end;">
                <button class="mt-btn mt-btn-secondary mt-close-btn">Cancel</button>
                <button id="mt-execute-auto-assign" class="mt-btn mt-btn-success">⚡ Execute Auto-Assignment</button>
            </div>
        </div>
    </div>

    <!-- Notification Container -->
    <div id="mt-notification-container" style="position: fixed; top: 20px; right: 20px; z-index: 100001;"></div>
</div>

<script>
// Add notification system
function showNotification(message, type = 'info') {
    const container = document.getElementById('mt-notification-container');
    if (!container) return;
    
    const notification = document.createElement('div');
    notification.className = `mt-notification mt-notification-${type}`;
    notification.style.cssText = `
        background: ${type === 'success' ? '#38a169' : type === 'error' ? '#e53e3e' : type === 'warning' ? '#d69e2e' : '#2c5282'};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        max-width: 300px;
        animation: slideIn 0.3s ease;
    `;
    
    notification.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: white; font-size: 18px; cursor: pointer; margin-left: 10px;">&times;</button>
        </div>
    `;
    
    container.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Add CSS for notifications
const notificationCSS = `
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
`;

const style = document.createElement('style');
style.textContent = notificationCSS;
document.head.appendChild(style);
</script>