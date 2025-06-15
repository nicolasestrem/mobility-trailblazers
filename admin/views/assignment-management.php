<?php
/**
 * Assignment Management View
 *
 * @package MobilityTrailblazers
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check permissions
if (!current_user_can('mt_manage_assignments')) {
    wp_die(__('You do not have permission to access this page.', 'mobility-trailblazers'));
}
?>

<div class="wrap">
    <h1><?php _e('Assignment Management', 'mobility-trailblazers'); ?></h1>
    
    <!-- Assignment Statistics -->
    <div class="mt-assignment-stats">
        <h2><?php _e('Assignment Overview', 'mobility-trailblazers'); ?></h2>
        <div id="assignment-stats-container">
            <p><?php _e('Loading statistics...', 'mobility-trailblazers'); ?></p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-assignment-actions">
        <h2><?php _e('Quick Actions', 'mobility-trailblazers'); ?></h2>
        <p>
            <button class="button button-primary" id="mt-auto-assign-btn">
                <?php _e('Auto-Assign Candidates', 'mobility-trailblazers'); ?>
            </button>
            <button class="button" id="mt-clear-all-assignments">
                <?php _e('Clear All Assignments', 'mobility-trailblazers'); ?>
            </button>
            <button class="button" id="mt-export-btn">
                <?php _e('Export Assignments', 'mobility-trailblazers'); ?>
            </button>
            <button class="button" id="mt-refresh-data">
                <?php _e('Refresh Data', 'mobility-trailblazers'); ?>
            </button>
        </p>
    </div>

    <!-- Drag and Drop Assignment Interface -->
    <div class="mt-drag-drop-interface">
        <div class="mt-assignment-columns">
            
            <!-- Unassigned Candidates Column -->
            <div class="mt-column mt-candidates-column">
                <div class="mt-column-header">
                    <h3><?php _e('Candidates', 'mobility-trailblazers'); ?></h3>
                    <div class="mt-candidates-controls">
                        <input type="text" id="mt-candidates-search" placeholder="<?php esc_attr_e('Search candidates...', 'mobility-trailblazers'); ?>" />
                        <select id="mt-category-filter">
                            <option value=""><?php _e('All Categories', 'mobility-trailblazers'); ?></option>
                            <option value="established-companies"><?php _e('Established Companies', 'mobility-trailblazers'); ?></option>
                            <option value="startups-new-makers"><?php _e('Start-ups & New Makers', 'mobility-trailblazers'); ?></option>
                            <option value="infrastructure-politics-public"><?php _e('Infrastructure / Politics / Public', 'mobility-trailblazers'); ?></option>
                        </select>
                        <select id="mt-assignment-filter">
                            <option value=""><?php _e('All Candidates', 'mobility-trailblazers'); ?></option>
                            <option value="assigned"><?php _e('Assigned', 'mobility-trailblazers'); ?></option>
                            <option value="unassigned"><?php _e('Unassigned', 'mobility-trailblazers'); ?></option>
                        </select>
                    </div>
                    <div class="mt-candidates-count-display">
                        <span class="mt-candidates-count">0</span> <?php _e('candidates', 'mobility-trailblazers'); ?>
                    </div>
                </div>
                
                <div id="mt-candidates-list" class="mt-candidates-list droppable-area">
                    <div class="mt-loading"><?php _e('Loading candidates...', 'mobility-trailblazers'); ?></div>
                </div>
                
                <div class="mt-candidates-actions">
                    <button type="button" id="mt-select-all-candidates" class="button">
                        <?php _e('Select All', 'mobility-trailblazers'); ?>
                    </button>
                    <button type="button" id="mt-clear-selection" class="button">
                        <?php _e('Clear Selection', 'mobility-trailblazers'); ?>
                    </button>
                </div>
            </div>

            <!-- Assignment Actions Column -->
            <div class="mt-assignment-actions-column">
                <div class="mt-assignment-controls">
                    <h3><?php _e('Assignment Actions', 'mobility-trailblazers'); ?></h3>
                    
                    <div class="mt-selection-info">
                        <p><?php _e('Selected Candidates:', 'mobility-trailblazers'); ?> <span class="mt-selected-candidates-count">0</span></p>
                        <p><?php _e('Selected Jury Member:', 'mobility-trailblazers'); ?> <span class="mt-selected-jury-name">None</span></p>
                    </div>
                    
                    <div class="mt-manual-assignment">
                        <button type="button" id="mt-manual-assign-btn" class="button button-primary" disabled>
                            <?php _e('Assign Selected', 'mobility-trailblazers'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Jury Members Columns -->
            <div class="mt-jury-columns" id="mt-jury-list">
                <div class="mt-loading"><?php _e('Loading jury members...', 'mobility-trailblazers'); ?></div>
            </div>
            
        </div>
    </div>

    <!-- Manual Assignment (Fallback) -->
    <div class="mt-manual-assignment">
        <h2><?php _e('Manual Assignment (Alternative Method)', 'mobility-trailblazers'); ?></h2>
        
        <div class="assignment-form">
            <label for="jury-select"><?php _e('Select Jury Member:', 'mobility-trailblazers'); ?></label>
            <select id="jury-select" class="widefat">
                <option value=""><?php _e('Choose a jury member...', 'mobility-trailblazers'); ?></option>
                <?php
                $jury_members = get_posts(array(
                    'post_type' => 'mt_jury',
                    'posts_per_page' => -1,
                    'orderby' => 'title',
                    'order' => 'ASC'
                ));
                
                foreach ($jury_members as $jury) {
                    echo '<option value="' . esc_attr($jury->ID) . '">' . esc_html($jury->post_title) . '</option>';
                }
                ?>
            </select>
            
            <div id="candidate-selection" style="display: none; margin-top: 20px;">
                <h3><?php _e('Select Candidates to Assign:', 'mobility-trailblazers'); ?></h3>
                <div id="candidate-list">
                    <!-- Candidates will be loaded here via AJAX -->
                </div>
                
                <p style="margin-top: 20px;">
                    <button class="button button-primary" id="save-assignment-btn">
                        <?php _e('Save Assignment', 'mobility-trailblazers'); ?>
                    </button>
                </p>
            </div>
        </div>
    </div>

    <!-- Current Assignments Table -->
    <div class="mt-current-assignments">
        <h2><?php _e('Current Assignments', 'mobility-trailblazers'); ?></h2>
        <div id="assignments-table-container">
            <!-- Assignment table will be loaded here -->
        </div>
    </div>
</div>

<!-- Auto Assignment Modal -->
<div id="mt-auto-assign-modal" class="mt-modal" style="display: none;">
    <div class="mt-modal-content">
        <div class="mt-modal-header">
            <h3><?php _e('Auto Assignment Settings', 'mobility-trailblazers'); ?></h3>
            <span class="mt-close-btn">&times;</span>
        </div>
        <div class="mt-modal-body">
            <p><?php _e('Configure automatic assignment parameters:', 'mobility-trailblazers'); ?></p>
            
            <label for="mt-candidates-per-jury">
                <?php _e('Candidates per Jury Member:', 'mobility-trailblazers'); ?>
            </label>
            <input type="number" id="mt-candidates-per-jury" value="10" min="1" max="50" />
            
            <label>
                <input type="checkbox" id="mt-balance-categories" checked />
                <?php _e('Balance categories across jury members', 'mobility-trailblazers'); ?>
            </label>
            
            <label>
                <input type="checkbox" id="mt-match-expertise" />
                <?php _e('Match jury expertise with candidate categories', 'mobility-trailblazers'); ?>
            </label>
            
            <label>
                <input type="checkbox" id="mt-clear-existing" />
                <?php _e('Clear existing assignments first', 'mobility-trailblazers'); ?>
            </label>
            
            <div class="mt-algorithm-selection">
                <p><?php _e('Assignment Algorithm:', 'mobility-trailblazers'); ?></p>
                <div class="mt-algorithm-option selected" data-algorithm="balanced">
                    <strong><?php _e('Balanced Distribution', 'mobility-trailblazers'); ?></strong>
                    <p><?php _e('Evenly distribute candidates among jury members', 'mobility-trailblazers'); ?></p>
                </div>
                <div class="mt-algorithm-option" data-algorithm="random">
                    <strong><?php _e('Random Assignment', 'mobility-trailblazers'); ?></strong>
                    <p><?php _e('Randomly assign candidates to jury members', 'mobility-trailblazers'); ?></p>
                </div>
            </div>
        </div>
        <div class="mt-modal-footer">
            <button type="button" id="mt-execute-auto-assign" class="button button-primary">
                <?php _e('Execute Assignment', 'mobility-trailblazers'); ?>
            </button>
            <button type="button" class="button mt-close-btn">
                <?php _e('Cancel', 'mobility-trailblazers'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Notification Container -->
<div id="mt-notifications"></div>

<style>
.mt-drag-drop-interface {
    margin: 20px 0;
    background: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
}

.mt-assignment-columns {
    display: flex;
    gap: 20px;
    min-height: 600px;
}

.mt-column {
    flex: 1;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.mt-candidates-column {
    flex: 0 0 300px;
}

.mt-assignment-actions-column {
    flex: 0 0 250px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 20px;
}

.mt-candidates-actions {
    padding: 15px;
    border-top: 1px solid #eee;
}

.mt-candidates-actions .button {
    margin-right: 10px;
}

.mt-selection-info {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 15px;
}

.mt-selection-info p {
    margin: 5px 0;
    font-size: 14px;
}

.mt-selected-candidates-count,
.mt-selected-jury-name {
    font-weight: bold;
    color: #0073aa;
}

.mt-jury-columns {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    flex: 1;
}

.mt-jury-column {
    flex: 0 0 280px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    min-height: 400px;
}

.mt-column-header {
    padding: 15px;
    border-bottom: 1px solid #eee;
    background: #f8f9fa;
    border-radius: 8px 8px 0 0;
}

.mt-column-header h3 {
    margin: 0 0 10px 0;
    font-size: 16px;
}

.mt-search-box input,
.mt-filter-box select {
    width: 100%;
    margin-bottom: 10px;
}

.mt-candidates-list,
.mt-jury-candidates {
    padding: 15px;
    min-height: 300px;
}

.droppable-area {
    border: 2px dashed #ddd;
    transition: all 0.3s ease;
}

.droppable-area.drag-over {
    border-color: #0073aa;
    background-color: #f0f8ff;
}

.candidate-item {
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 10px;
    margin-bottom: 8px;
    cursor: move;
    transition: all 0.3s ease;
    position: relative;
}

.candidate-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    transform: translateY(-1px);
}

.candidate-item.dragging {
    opacity: 0.5;
    transform: rotate(5deg);
}

.candidate-name {
    font-weight: bold;
    margin-bottom: 4px;
}

.candidate-company {
    font-size: 12px;
    color: #666;
    margin-bottom: 2px;
}

.candidate-category {
    font-size: 11px;
    background: #e1f5fe;
    color: #01579b;
    padding: 2px 6px;
    border-radius: 3px;
    display: inline-block;
}

.jury-header {
    background: #e3f2fd;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 10px;
}

.jury-name {
    font-weight: bold;
    margin-bottom: 4px;
}

.jury-stats {
    font-size: 12px;
    color: #666;
}

.assignment-count {
    background: #4caf50;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
}

.mt-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 10000;
    display: none;
    align-items: center;
    justify-content: center;
}

.mt-modal.show {
    display: flex;
}

.mt-modal-content {
    background: white;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.mt-modal-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.mt-close-btn {
    cursor: pointer;
    font-size: 24px;
    color: #999;
}

.mt-algorithm-selection {
    margin: 20px 0;
}

.mt-algorithm-option {
    border: 2px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.mt-algorithm-option:hover {
    border-color: #0073aa;
}

.mt-algorithm-option.selected {
    border-color: #0073aa;
    background-color: #f0f8ff;
}

.mt-algorithm-option strong {
    display: block;
    margin-bottom: 5px;
    color: #0073aa;
}

.mt-algorithm-option p {
    margin: 0;
    font-size: 13px;
    color: #666;
}

.mt-modal-body {
    padding: 20px;
}

.mt-modal-body label {
    display: block;
    margin-bottom: 15px;
    font-weight: bold;
}

.mt-modal-body input,
.mt-modal-body select {
    width: 100%;
    margin-top: 5px;
}

.mt-modal-footer {
    padding: 20px;
    border-top: 1px solid #eee;
    text-align: right;
}

.mt-modal-footer .button {
    margin-left: 10px;
}

#mt-notifications {
    position: fixed;
    top: 32px;
    right: 20px;
    z-index: 10001;
}

.mt-notification {
    background: white;
    border-left: 4px solid #0073aa;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 4px;
    max-width: 300px;
    animation: slideIn 0.3s ease;
}

.mt-notification.success {
    border-left-color: #4caf50;
}

.mt-notification.error {
    border-left-color: #f44336;
}

.mt-notification.warning {
    border-left-color: #ff9800;
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

.mt-loading {
    text-align: center;
    padding: 40px;
    color: #666;
}

.mt-manual-assignment {
    margin-top: 30px;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Manual assignment (fallback) - only for the dropdown method
    $('#jury-select').on('change', function() {
        var juryId = $(this).val();
        if (juryId) {
            loadCandidatesForAssignment(juryId);
            $('#candidate-selection').show();
        } else {
            $('#candidate-selection').hide();
        }
    });
    
    $('#save-assignment-btn').on('click', function() {
        var juryId = $('#jury-select').val();
        var candidateIds = [];
        
        $('#candidate-list input[type="checkbox"]:checked').each(function() {
            candidateIds.push($(this).val());
        });
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mt_assign_candidates',
                jury_id: juryId,
                candidate_ids: candidateIds,
                nonce: '<?php echo wp_create_nonce('mt_assignment_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Assignment saved successfully');
                    location.reload();
                } else {
                    alert('Failed to save assignment: ' + response.data);
                }
            }
        });
    });

    function loadCandidatesForAssignment(juryId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mt_get_candidates_for_assignment',
                jury_id: juryId,
                nonce: '<?php echo wp_create_nonce('mt_assignment_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    displayCandidateList(response.data);
                }
            }
        });
    }

    function displayCandidateList(candidates) {
        var html = '<div class="candidate-checkboxes" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">';
        
        if (candidates && candidates.length > 0) {
            candidates.forEach(function(candidate) {
                var checked = candidate.assigned ? ' checked' : '';
                html += '<label style="display: block; margin-bottom: 5px;">';
                html += '<input type="checkbox" value="' + candidate.id + '"' + checked + '> ';
                html += candidate.title;
                html += '</label>';
            });
        } else {
            html += '<p><?php _e('No candidates available.', 'mobility-trailblazers'); ?></p>';
        }
        
        html += '</div>';
        $('#candidate-list').html(html);
    }
});
</script> 
});
</script> 