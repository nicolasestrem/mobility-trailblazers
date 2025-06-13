<?php
/**
 * Assignment Management Template - FIXED VERSION
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
    <!-- Header -->
    <div class="mt-assignment-header">
        <h1>🏆 Jury Assignment System</h1>
        <p>Advanced Assignment Interface v3.2 - Mobility Trailblazers 2025</p>
    </div>

    <!-- Status Banner -->
    <div class="mt-status-banner">
        <span class="icon">✅</span>
        <div>
            <strong>System Status: OPERATIONAL</strong> | Last check: <?php echo date('H:i:s'); ?> | 
            Active Phase: <?php echo esc_html($phase_names[$current_phase] ?? 'Unknown'); ?>
        </div>
    </div>

    <!-- Statistics Grid -->
    <div class="mt-statistics">
        <div class="mt-stat-card">
            <div class="mt-stat-value mt-stat-total-candidates"><?php echo $total_candidates; ?></div>
            <div class="mt-stat-label">Total Candidates</div>
        </div>
        <div class="mt-stat-card">
            <div class="mt-stat-value"><?php echo $total_jury; ?></div>
            <div class="mt-stat-label">Jury Members</div>
        </div>
        <div class="mt-stat-card">
            <div class="mt-stat-value mt-stat-assigned-candidates"><?php echo $assigned_count; ?></div>
            <div class="mt-stat-label">Total Assignments</div>
        </div>
        <div class="mt-stat-card">
            <div class="mt-stat-value mt-stat-completion-rate"><?php echo number_format($completion_rate, 1); ?>%</div>
            <div class="mt-stat-label">Completion Rate</div>
        </div>
        <div class="mt-stat-card">
            <div class="mt-stat-value mt-stat-avg-per-jury"><?php echo number_format($avg_per_jury, 1); ?></div>
            <div class="mt-stat-label">Avg Per Jury</div>
        </div>
    </div>

    <!-- Selection Info -->
    <div class="mt-selection-info" style="display: none;">
        <strong>Selection:</strong> <span class="mt-selected-count">0</span> candidates selected, 
        jury member: <span class="mt-selected-jury-name">None</span>
    </div>

    <!-- Assignment Controls -->
    <div class="mt-assignment-controls">
        <h3>🔧 Assignment Tools</h3>
        
        <!-- Filter Controls -->
        <div class="mt-filters">
            <div class="mt-filter-group">
                <label>Search:</label>
                <input type="text" id="mt-search-input" placeholder="Search candidates...">
            </div>
            <div class="mt-filter-group">
                <label>Stage:</label>
                <select id="mt-stage-filter">
                    <option value="">All Stages</option>
                    <option value="round1">Round 1</option>
                    <option value="round2">Round 2</option>
                    <option value="final">Final</option>
                </select>
            </div>
            <div class="mt-filter-group">
                <label>Category:</label>
                <select id="mt-category-filter">
                    <option value="">All Categories</option>
                    <option value="established-companies">Established Companies</option>
                    <option value="startups-new-makers">Start-ups & New Makers</option>
                    <option value="infrastructure-politics-public">Infrastructure/Politics/Public</option>
                </select>
            </div>
            <div class="mt-filter-group">
                <label>Status:</label>
                <select id="mt-assignment-filter">
                    <option value="">All Candidates</option>
                    <option value="assigned">Assigned</option>
                    <option value="unassigned">Unassigned</option>
                </select>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-controls-row">
            <button id="mt-select-all-btn" class="mt-btn mt-btn-secondary">
                ✅ Select All Visible
            </button>
            <button id="mt-clear-selection-btn" class="mt-btn mt-btn-secondary" disabled>
                🗑️ Clear Selection
            </button>
            <button id="mt-manual-assign-btn" class="mt-btn mt-btn-primary" disabled>
                👥 Assign Selected
            </button>
            <button id="mt-auto-assign-btn" class="mt-btn mt-btn-success">
                ⚡ Auto-Assign All
            </button>
            <button id="mt-clear-all-btn" class="mt-btn mt-btn-danger">
                🗑️ Clear All Assignments
            </button>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="mt-assignment-grid">
        <!-- Candidates Section -->
        <div class="mt-candidates-section">
            <h3 id="mt-candidates-header">
                📋 Candidates (<?php echo $total_candidates; ?>)
                <span class="count"></span>
            </h3>
            
            <!-- Candidates List Container - FIXED ID -->
            <div id="mt-candidates-list">
                <div style="text-align: center; padding: 20px; color: #718096;">
                    Loading candidates...
                </div>
            </div>
        </div>

        <!-- Jury Members Section -->
        <div class="mt-jury-section">
            <h3>👨‍⚖️ Jury Members (<?php echo $total_jury; ?>)</h3>
            
            <!-- Jury Members List Container - FIXED ID -->
            <div id="mt-jury-members-list">
                <div style="text-align: center; padding: 20px; color: #718096;">
                    Loading jury members...
                </div>
            </div>
        </div>
    </div>

    <!-- Export Options -->
    <div class="mt-assignment-controls" style="margin-top: 30px;">
        <h3>📤 Data Management</h3>
        <div class="mt-controls-row">
            <button id="mt-export-assignments-btn" class="mt-btn mt-btn-primary">
                📊 Export Assignments
            </button>
            <button id="mt-export-matrix-btn" class="mt-btn mt-btn-secondary">
                📋 Export Assignment Matrix
            </button>
            <button id="mt-refresh-data-btn" class="mt-btn mt-btn-secondary">
                🔄 Refresh Data
            </button>
        </div>
    </div>
</div>

<!-- Auto-Assign Modal (Hidden by default) -->
<div id="mt-auto-assign-modal" class="mt-modal" style="display: none;">
    <div class="mt-modal-content">
        <div class="mt-modal-header">
            <h3>⚡ Auto-Assignment Configuration</h3>
            <button class="mt-close-btn">&times;</button>
        </div>
        <div class="mt-modal-body">
            <p>Configure automatic assignment parameters:</p>
            
            <div class="mt-form-group">
                <label>Assignment Algorithm:</label>
                <div class="mt-algorithm-options">
                    <div class="mt-algorithm-option selected" data-algorithm="balanced">
                        <strong>Balanced Distribution</strong>
                        <p>Evenly distribute candidates across all jury members</p>
                    </div>
                    <div class="mt-algorithm-option" data-algorithm="category-match">
                        <strong>Category Matching</strong>
                        <p>Match candidates to jury members based on expertise</p>
                    </div>
                    <div class="mt-algorithm-option" data-algorithm="random">
                        <strong>Random Assignment</strong>
                        <p>Randomly assign candidates to available jury members</p>
                    </div>
                </div>
            </div>
            
            <div class="mt-form-group">
                <label>Candidates per Jury Member:</label>
                <input type="number" id="mt-candidates-per-jury" value="21" min="1" max="50">
            </div>
        </div>
        <div class="mt-modal-footer">
            <button id="mt-execute-auto-assign" class="mt-btn mt-btn-success">
                ⚡ Execute Auto-Assignment
            </button>
            <button class="mt-btn mt-btn-secondary mt-close-btn">
                Cancel
            </button>
        </div>
    </div>
</div>

<style>
/* Assignment Grid Layout */
.mt-assignment-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin: 20px 0;
}

@media (max-width: 968px) {
    .mt-assignment-grid {
        grid-template-columns: 1fr;
    }
}

/* Modal Styles */
.mt-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mt-modal-content {
    background: #fff;
    border-radius: 8px;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.mt-modal-header {
    padding: 20px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.mt-modal-header h3 {
    margin: 0;
    color: #2d3748;
}

.mt-close-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #718096;
}

.mt-modal-body {
    padding: 20px;
}

.mt-modal-footer {
    padding: 20px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

/* Form Styles */
.mt-form-group {
    margin-bottom: 20px;
}

.mt-form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2d3748;
}

.mt-algorithm-options {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.mt-algorithm-option {
    border: 2px solid #e2e8f0;
    border-radius: 6px;
    padding: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.mt-algorithm-option:hover {
    border-color: #4299e1;
}

.mt-algorithm-option.selected {
    border-color: #38a169;
    background: #f0fff4;
}

.mt-form-group input[type="number"] {
    width: 100px;
    padding: 8px 12px;
    border: 1px solid #cbd5e0;
    border-radius: 4px;
    font-size: 14px;
}

/* Selection info visibility */
.mt-selection-info:not([style*="display: none"]) {
    display: block !important;
}
</style>

<script>
// Show selection info when candidates are selected
jQuery(document).ready(function($) {
    function updateSelectionInfoVisibility() {
        const selectionInfo = $('.mt-selection-info');
        const selectedCount = parseInt($('.mt-selected-count').text()) || 0;
        const hasJurySelected = $('.mt-selected-jury-name').text() !== 'None';
        
        if (selectedCount > 0 || hasJurySelected) {
            selectionInfo.show();
        } else {
            selectionInfo.hide();
        }
    }
    
    // Monitor for changes in selection
    const observer = new MutationObserver(updateSelectionInfoVisibility);
    observer.observe(document.querySelector('.mt-selection-info') || document.body, {
        childList: true,
        subtree: true,
        characterData: true
    });
    
    // Initial check
    setTimeout(updateSelectionInfoVisibility, 1000);
});
</script>