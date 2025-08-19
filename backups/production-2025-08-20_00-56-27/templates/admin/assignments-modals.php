<?php
/**
 * Assignment Modals - New Implementation
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<style>
/* New modal styles with unique class names to avoid conflicts */
.mt-new-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    z-index: 99999;
}

.mt-new-modal-overlay.active {
    display: block;
}

.mt-new-modal-container {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    z-index: 100000;
}

.mt-new-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
}

.mt-new-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mt-new-modal-close:hover {
    color: #000;
}

.mt-new-form-group {
    margin-bottom: 20px;
}

.mt-new-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.mt-new-form-group .widefat {
    width: 100%;
}

.mt-new-modal-actions {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
    text-align: right;
}

.mt-new-modal-actions button {
    margin-left: 10px;
}

.mt-new-candidates-list {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #ddd;
    padding: 10px;
    background: #f9f9f9;
}

.mt-new-candidate-item {
    display: block;
    margin-bottom: 5px;
    padding: 3px 0;
}

.mt-new-candidate-item input {
    margin-right: 8px;
}
</style>

<!-- Auto-Assignment Modal -->
<div id="mt-new-auto-modal" class="mt-new-modal-overlay">
    <div class="mt-new-modal-container">
        <div class="mt-new-modal-header">
            <h2><?php _e('Auto-Assignment Configuration', 'mobility-trailblazers'); ?></h2>
            <button type="button" class="mt-new-modal-close" onclick="closeNewModal('mt-new-auto-modal')">&times;</button>
        </div>
        
        <form id="mt-new-auto-form">
            <div class="mt-new-form-group">
                <label for="new_assignment_method"><?php _e('Assignment Method', 'mobility-trailblazers'); ?></label>
                <select name="method" id="new_assignment_method" class="widefat">
                    <option value="balanced"><?php _e('Balanced - Distributes candidates evenly', 'mobility-trailblazers'); ?></option>
                    <option value="random"><?php _e('Random - Randomly assigns candidates', 'mobility-trailblazers'); ?></option>
                </select>
            </div>
            
            <div class="mt-new-form-group">
                <label for="new_candidates_per_jury"><?php _e('Candidates per Jury Member', 'mobility-trailblazers'); ?></label>
                <input type="number" name="candidates_per_jury" id="new_candidates_per_jury" value="10" min="1" max="50" class="widefat">
            </div>
            
            <div class="mt-new-form-group">
                <label>
                    <input type="checkbox" name="clear_existing" id="new_clear_existing" value="true">
                    <?php _e('Clear all existing assignments before auto-assigning', 'mobility-trailblazers'); ?>
                </label>
                <p style="color: #d63638; margin-left: 24px;">
                    <strong><?php _e('Warning:', 'mobility-trailblazers'); ?></strong> 
                    <?php _e('This will permanently remove ALL current assignments!', 'mobility-trailblazers'); ?>
                </p>
            </div>
            
            <div class="mt-new-modal-actions">
                <button type="submit" class="button button-primary"><?php _e('Run Auto-Assignment', 'mobility-trailblazers'); ?></button>
                <button type="button" class="button" onclick="closeNewModal('mt-new-auto-modal')"><?php _e('Cancel', 'mobility-trailblazers'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Manual Assignment Modal -->
<div id="mt-new-manual-modal" class="mt-new-modal-overlay">
    <div class="mt-new-modal-container">
        <div class="mt-new-modal-header">
            <h2><?php _e('Manual Assignment', 'mobility-trailblazers'); ?></h2>
            <button type="button" class="mt-new-modal-close" onclick="closeNewModal('mt-new-manual-modal')">&times;</button>
        </div>
        
        <form id="mt-new-manual-form">
            <div class="mt-new-form-group">
                <label for="new_manual_jury"><?php _e('Jury Member', 'mobility-trailblazers'); ?></label>
                <select name="jury_member_id" id="new_manual_jury" class="widefat" required>
                    <option value=""><?php _e('Select Jury Member', 'mobility-trailblazers'); ?></option>
                    <?php 
                    $jury_members = get_posts([
                        'post_type' => 'mt_jury_member',
                        'posts_per_page' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC',
                        'post_status' => 'publish'
                    ]);
                    foreach ($jury_members as $jury) : ?>
                        <option value="<?php echo esc_attr($jury->ID); ?>">
                            <?php echo esc_html($jury->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mt-new-form-group">
                <label><?php _e('Select Candidates', 'mobility-trailblazers'); ?></label>
                <div class="mt-new-candidates-list">
                    <?php 
                    $candidates = get_posts([
                        'post_type' => 'mt_candidate',
                        'posts_per_page' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC',
                        'post_status' => 'publish'
                    ]);
                    foreach ($candidates as $candidate) : ?>
                        <label class="mt-new-candidate-item">
                            <input type="checkbox" name="candidate_ids[]" value="<?php echo esc_attr($candidate->ID); ?>">
                            <?php echo esc_html($candidate->post_title); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="mt-new-modal-actions">
                <button type="submit" class="button button-primary"><?php _e('Assign Selected', 'mobility-trailblazers'); ?></button>
                <button type="button" class="button" onclick="closeNewModal('mt-new-manual-modal')"><?php _e('Cancel', 'mobility-trailblazers'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
// Simple modal functions
function openNewModal(modalId) {
    var modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
    }
}

function closeNewModal(modalId) {
    var modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Auto-assign button
    var autoBtn = document.getElementById('mt-auto-assign-btn');
    if (autoBtn) {
        autoBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openNewModal('mt-new-auto-modal');
        });
    }
    
    // Manual assign button
    var manualBtn = document.getElementById('mt-manual-assign-btn');
    if (manualBtn) {
        manualBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openNewModal('mt-new-manual-modal');
        });
    }
    
    // Click overlay to close
    document.querySelectorAll('.mt-new-modal-overlay').forEach(function(overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                overlay.classList.remove('active');
            }
        });
    });
    
    // Handle auto-assign form submission
    document.getElementById('mt-new-auto-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        var method = document.getElementById('new_assignment_method').value;
        var candidatesPerJury = document.getElementById('new_candidates_per_jury').value;
        var clearExisting = document.getElementById('new_clear_existing').checked;
        
        // Submit via AJAX
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mt_auto_assign',
                nonce: '<?php echo wp_create_nonce('mt_admin_nonce'); ?>',
                method: method,
                candidates_per_jury: candidatesPerJury,
                clear_existing: clearExisting ? 'true' : 'false'
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message || 'Auto-assignment completed successfully!');
                    location.reload();
                } else {
                    alert(response.data || 'An error occurred');
                }
            },
            error: function() {
                alert('An error occurred');
            }
        });
    });
    
    // Handle manual assignment form submission
    document.getElementById('mt-new-manual-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        var juryMemberId = document.getElementById('new_manual_jury').value;
        var candidateIds = [];
        document.querySelectorAll('input[name="candidate_ids[]"]:checked').forEach(function(cb) {
            candidateIds.push(cb.value);
        });
        
        if (!juryMemberId || candidateIds.length === 0) {
            alert('Please select a jury member and at least one candidate.');
            return;
        }
        
        // Submit via AJAX
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mt_manual_assign',
                nonce: '<?php echo wp_create_nonce('mt_admin_nonce'); ?>',
                jury_member_id: juryMemberId,
                candidate_ids: candidateIds
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message || 'Assignments created successfully!');
                    location.reload();
                } else {
                    alert(response.data || 'An error occurred');
                }
            },
            error: function() {
                alert('An error occurred');
            }
        });
    });
});
</script>