<?php
/**
 * Jury Dashboard Admin View
 *
 * @package MobilityTrailblazers
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get current jury member
$jury_member = isset($jury_member) ? $jury_member : mt_get_jury_member_by_user_id(get_current_user_id());

if (!$jury_member) {
    wp_die(__('Jury member profile not found.', 'mobility-trailblazers'));
}

// Get assigned candidates
$assigned_candidate_ids = mt_get_assigned_candidates($jury_member->ID);
$total_assigned = count($assigned_candidate_ids);

// Convert IDs to candidate objects
$assigned_candidates = array();
if (!empty($assigned_candidate_ids)) {
    $assigned_candidates = get_posts(array(
        'post_type' => 'mt_candidate',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'post__in' => $assigned_candidate_ids,
        'orderby' => 'title',
        'order' => 'ASC'
    ));
}

// Get evaluation statistics
global $wpdb;
$evaluated_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(DISTINCT candidate_id) FROM {$wpdb->prefix}mt_votes 
     WHERE jury_member_id = %d AND is_active = 1",
    $jury_member->ID
));

$draft_count = 0;
foreach ($assigned_candidates as $candidate) {
    if (mt_has_draft_evaluation($candidate->ID, $jury_member->ID)) {
        $draft_count++;
    }
}
$remaining_count = $total_assigned - $evaluated_count;
$completion_rate = $total_assigned > 0 ? round(($evaluated_count / $total_assigned) * 100) : 0;

// Get categories for filtering
$categories = get_terms(array(
    'taxonomy' => 'mt_category',
    'hide_empty' => false
));
?>

<div class="wrap mt-jury-dashboard">
    <h1><?php _e('Jury Dashboard', 'mobility-trailblazers'); ?></h1>
    
    <!-- Welcome Section -->
    <div class="mt-welcome-section">
        <h2><?php printf(__('Welcome, %s', 'mobility-trailblazers'), esc_html($jury_member->post_title)); ?></h2>
        <p><?php _e('Review and evaluate your assigned candidates below. Your evaluations help identify the most innovative mobility leaders.', 'mobility-trailblazers'); ?></p>
    </div>
    
    <!-- Progress Overview -->
    <div class="mt-progress-overview">
        <div class="mt-progress-stats">
            <div class="mt-stat">
                <span class="mt-stat-number" id="assigned-count"><?php echo $total_assigned; ?></span>
                <span class="mt-stat-label"><?php _e('Total Assigned', 'mobility-trailblazers'); ?></span>
            </div>
            <div class="mt-stat">
                <span class="mt-stat-number" id="completed-count"><?php echo $evaluated_count; ?></span>
                <span class="mt-stat-label"><?php _e('Evaluated', 'mobility-trailblazers'); ?></span>
            </div>
            <div class="mt-stat">
                <span class="mt-stat-number" id="draft-count"><?php echo $draft_count; ?></span>
                <span class="mt-stat-label"><?php _e('Drafts', 'mobility-trailblazers'); ?></span>
            </div>
            <div class="mt-stat">
                <span class="mt-stat-number"><?php echo $remaining_count; ?></span>
                <span class="mt-stat-label"><?php _e('Remaining', 'mobility-trailblazers'); ?></span>
            </div>
        </div>
        
        <div class="mt-progress-bar-container">
            <div class="mt-progress-bar">
                <div class="mt-progress-fill progress-fill" style="width: <?php echo $completion_rate; ?>%"></div>
            </div>
            <span class="mt-progress-percentage" id="completion-percentage"><?php echo $completion_rate; ?>% <?php _e('Complete', 'mobility-trailblazers'); ?></span>
        </div>
    </div>
    
    <!-- Filters and Search -->
    <div class="mt-filters-section">
        <div class="mt-search-box">
            <input type="text" id="candidate-search" placeholder="<?php _e('Search candidates...', 'mobility-trailblazers'); ?>" />
        </div>
        
        <div class="mt-filter-controls">
            <select id="category-filter">
                <option value=""><?php _e('All Categories', 'mobility-trailblazers'); ?></option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo esc_attr($category->slug); ?>">
                        <?php echo esc_html($category->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select id="status-filter">
                <option value=""><?php _e('All Status', 'mobility-trailblazers'); ?></option>
                <option value="pending"><?php _e('Pending', 'mobility-trailblazers'); ?></option>
                <option value="draft"><?php _e('Draft', 'mobility-trailblazers'); ?></option>
                <option value="completed"><?php _e('Completed', 'mobility-trailblazers'); ?></option>
            </select>
        </div>
        
        <div class="mt-action-buttons">
            <button class="button" id="export-evaluations">
                <?php _e('Export My Evaluations', 'mobility-trailblazers'); ?>
            </button>
        </div>
    </div>
    
    <!-- Candidates Grid -->
    <div class="mt-candidates-section">
        <h3><?php _e('Assigned Candidates', 'mobility-trailblazers'); ?></h3>
        
        <?php if (empty($assigned_candidates)): ?>
            <p class="no-candidates"><?php _e('No candidates have been assigned to you yet.', 'mobility-trailblazers'); ?></p>
        <?php else: ?>
            <div class="mt-candidates-grid" id="candidates-grid">
                <?php foreach ($assigned_candidates as $candidate): 
                    $evaluation = mt_get_evaluation($candidate->ID, $jury_member->ID);
                    $is_draft = mt_has_draft_evaluation($candidate->ID, $jury_member->ID);
                    $categories = wp_get_post_terms($candidate->ID, 'mt_category', array('fields' => 'names'));
                    $company = get_post_meta($candidate->ID, '_mt_company_name', true);
                    $position = get_post_meta($candidate->ID, '_mt_position', true);
                    $status = $evaluation ? 'completed' : ($is_draft ? 'draft' : 'pending');
                ?>
                    <div class="candidate-card mt-candidate-card status-<?php echo $status; ?>" 
                         data-candidate-id="<?php echo $candidate->ID; ?>" 
                         data-status="<?php echo $status; ?>"
                         data-categories="<?php echo esc_attr(implode(' ', wp_list_pluck($categories, 'slug'))); ?>">
                        <div class="candidate-header">
                            <h4 class="candidate-name"><?php echo esc_html($candidate->post_title); ?></h4>
                            <span class="candidate-status">
                                <?php if ($evaluation): ?>
                                    <?php _e('Evaluated', 'mobility-trailblazers'); ?>
                                <?php elseif ($is_draft): ?>
                                    <?php _e('Draft', 'mobility-trailblazers'); ?>
                                <?php else: ?>
                                    <?php _e('Pending', 'mobility-trailblazers'); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <div class="candidate-body">
                            <?php if ($company): ?>
                                <p class="candidate-company"><?php echo esc_html($company); ?></p>
                            <?php endif; ?>
                            <?php if ($position): ?>
                                <p class="candidate-position"><?php echo esc_html($position); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($categories)): ?>
                                <span class="candidate-category">
                                    <?php echo esc_html(implode(', ', $categories)); ?>
                                </span>
                            <?php endif; ?>
                            <p class="candidate-excerpt"><?php echo wp_trim_words($candidate->post_content, 20); ?></p>
                        </div>
                        
                        <div class="candidate-footer">
                            <button class="evaluate-btn">
                                <?php if ($evaluation): ?>
                                    <?php _e('View Evaluation', 'mobility-trailblazers'); ?>
                                <?php else: ?>
                                    <?php _e('Evaluate', 'mobility-trailblazers'); ?>
                                <?php endif; ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Evaluation Form (Hidden by default) -->
    <div id="evaluation-form" style="display: none;">
        <div class="mt-evaluation-header">
            <h3><?php _e('Evaluate Candidate', 'mobility-trailblazers'); ?></h3>
            <button class="button" id="close-evaluation"><?php _e('Close', 'mobility-trailblazers'); ?></button>
        </div>
        
        <div id="candidate-details">
            <!-- Populated via JavaScript -->
        </div>
        
        <form id="evaluation-form-fields">
            <?php wp_nonce_field('mt_jury_evaluation', 'mt_jury_evaluation_nonce'); ?>
            <h4><?php _e('Evaluation Criteria', 'mobility-trailblazers'); ?></h4>
            
            <div class="criteria-group">
                <label for="courage-score">
                    <?php _e('Mut & Pioniergeist (Courage & Pioneer Spirit)', 'mobility-trailblazers'); ?>
                    <span class="score-value">5</span>
                </label>
                <input type="range" id="courage-score" class="score-slider" min="1" max="10" value="5" />
                <p class="criteria-description"><?php _e('Willingness to take risks and break new ground', 'mobility-trailblazers'); ?></p>
            </div>
            
            <div class="criteria-group">
                <label for="innovation-score">
                    <?php _e('Innovationsgrad (Degree of Innovation)', 'mobility-trailblazers'); ?>
                    <span class="score-value">5</span>
                </label>
                <input type="range" id="innovation-score" class="score-slider" min="1" max="10" value="5" />
                <p class="criteria-description"><?php _e('Novelty and originality of the solution', 'mobility-trailblazers'); ?></p>
            </div>
            
            <div class="criteria-group">
                <label for="implementation-score">
                    <?php _e('Umsetzungskraft & Wirkung (Implementation & Impact)', 'mobility-trailblazers'); ?>
                    <span class="score-value">5</span>
                </label>
                <input type="range" id="implementation-score" class="score-slider" min="1" max="10" value="5" />
                <p class="criteria-description"><?php _e('Ability to execute and measurable impact achieved', 'mobility-trailblazers'); ?></p>
            </div>
            
            <div class="criteria-group">
                <label for="relevance-score">
                    <?php _e('Relevanz für Mobilitätswende (Mobility Transformation Relevance)', 'mobility-trailblazers'); ?>
                    <span class="score-value">5</span>
                </label>
                <input type="range" id="relevance-score" class="score-slider" min="1" max="10" value="5" />
                <p class="criteria-description"><?php _e('Contribution to sustainable mobility transformation', 'mobility-trailblazers'); ?></p>
            </div>
            
            <div class="criteria-group">
                <label for="visibility-score">
                    <?php _e('Vorbildfunktion & Sichtbarkeit (Role Model & Visibility)', 'mobility-trailblazers'); ?>
                    <span class="score-value">5</span>
                </label>
                <input type="range" id="visibility-score" class="score-slider" min="1" max="10" value="5" />
                <p class="criteria-description"><?php _e('Inspirational impact and public visibility', 'mobility-trailblazers'); ?></p>
            </div>
            
            <div class="total-score">
                <strong><?php _e('Total Score:', 'mobility-trailblazers'); ?></strong>
                <span id="total-score">25</span>/50
            </div>
            
            <div class="evaluation-comments">
                <label for="evaluation-comments"><?php _e('Additional Comments (Optional)', 'mobility-trailblazers'); ?></label>
                <textarea id="evaluation-comments" rows="4"></textarea>
            </div>
            
            <div class="evaluation-actions">
                <button type="button" class="button" id="save-draft"><?php _e('Save as Draft', 'mobility-trailblazers'); ?></button>
                <button type="button" class="button button-primary" id="submit-evaluation"><?php _e('Submit Evaluation', 'mobility-trailblazers'); ?></button>
            </div>
        </form>
    </div>
</div>

<style>
/* Jury Dashboard Styles */
.mt-jury-dashboard {
    max-width: 1200px;
}

.mt-welcome-section {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.mt-progress-overview {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.mt-progress-stats {
    display: flex;
    justify-content: space-around;
    margin-bottom: 20px;
}

.mt-stat {
    text-align: center;
}

.mt-stat-number {
    display: block;
    font-size: 32px;
    font-weight: 600;
    color: #2271b1;
}

.mt-stat-label {
    display: block;
    font-size: 14px;
    color: #646970;
    margin-top: 5px;
}

.mt-progress-bar-container {
    position: relative;
}

.mt-progress-bar {
    width: 100%;
    height: 30px;
    background: #f0f0f1;
    border-radius: 15px;
    overflow: hidden;
}

.mt-progress-fill {
    height: 100%;
    background: #2271b1;
    transition: width 0.3s ease;
}

.mt-progress-percentage {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-weight: 600;
}

.mt-filters-section {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.mt-search-box {
    flex: 1;
    min-width: 200px;
}

.mt-search-box input {
    width: 100%;
    padding: 8px 12px;
}

.mt-filter-controls {
    display: flex;
    gap: 10px;
}

.mt-candidates-section {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.mt-candidates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.candidate-card {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.candidate-card:hover {
    border-color: #2271b1;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.candidate-card.active {
    border-color: #2271b1;
    background: #f0f8ff;
}

.candidate-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 10px;
}

.candidate-name {
    margin: 0;
    font-size: 16px;
}

.evaluation-status {
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}

.evaluation-status.completed {
    background: #d4f4dd;
    color: #00a32a;
}

.evaluation-status.draft {
    background: #fcf9e8;
    color: #dba617;
}

.candidate-company,
.candidate-position {
    margin: 5px 0;
    font-size: 14px;
    color: #646970;
}

.category-tag {
    display: inline-block;
    background: #f0f0f1;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    margin-right: 5px;
}

.evaluation-score {
    position: absolute;
    bottom: 15px;
    right: 15px;
    font-size: 18px;
    color: #2271b1;
}

/* Evaluation Form */
#evaluation-form {
    background: #fff;
    padding: 30px;
    margin-top: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.mt-evaluation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.criteria-group {
    margin-bottom: 25px;
}

.criteria-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 10px;
}

.score-value {
    float: right;
    background: #2271b1;
    color: white;
    padding: 2px 8px;
    border-radius: 3px;
}

.score-slider {
    width: 100%;
    margin: 10px 0;
}

.criteria-description {
    font-size: 13px;
    color: #646970;
    margin-top: 5px;
}

.total-score {
    font-size: 20px;
    text-align: center;
    padding: 20px;
    background: #f0f0f1;
    border-radius: 4px;
    margin: 20px 0;
}

.evaluation-comments {
    margin: 20px 0;
}

.evaluation-comments textarea {
    width: 100%;
    padding: 10px;
}

.evaluation-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.no-candidates {
    text-align: center;
    padding: 40px;
    color: #646970;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    display: none;
}

.modal-overlay.active {
    display: block;
}

.modal-content {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 30px;
    border-radius: 8px;
    max-width: 800px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    z-index: 10000;
}

.modal-close {
    position: absolute;
    top: 15px;
    right: 15px;
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.modal-close:hover {
    color: #000;
}

.candidate-status {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}

.status-completed .candidate-status {
    background: #d4f4dd;
    color: #00a32a;
}

.status-draft .candidate-status {
    background: #fcf9e8;
    color: #dba617;
}

.status-pending .candidate-status {
    background: #f0f0f1;
    color: #646970;
}

.candidate-excerpt {
    font-size: 14px;
    color: #646970;
    margin: 10px 0;
    line-height: 1.4;
}

.candidate-category {
    display: inline-block;
    background: #f0f0f1;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    margin-bottom: 10px;
}

.evaluate-btn {
    background: #2271b1;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.evaluate-btn:hover {
    background: #135e96;
}

.candidate-body {
    margin: 10px 0;
}

.candidate-footer {
    margin-top: 15px;
    text-align: right;
}
</style>

<!-- Evaluation Modal -->
<div id="evaluation-modal" class="modal-overlay">
    <div class="modal-content">
        <button class="modal-close">&times;</button>
        
        <div class="modal-header">
            <h2 id="modal-candidate-name"></h2>
            <p id="modal-candidate-company"></p>
            <p id="modal-candidate-position"></p>
        </div>
        
        <div class="modal-body">
            <div id="modal-candidate-content"></div>
            <div id="modal-candidate-achievement" style="display: none;"></div>
            <div id="modal-candidate-impact" style="display: none;"></div>
            <div id="modal-candidate-vision" style="display: none;"></div>
            
            <div class="modal-links" style="margin: 20px 0;">
                <a id="modal-candidate-website" href="#" target="_blank" style="display: none;">Website</a>
                <a id="modal-candidate-linkedin" href="#" target="_blank" style="display: none;">LinkedIn</a>
            </div>
            
            <div class="evaluation-readonly-notice" style="display: none; background: #f0f8ff; padding: 15px; border-radius: 4px; margin: 20px 0;">
                <strong><?php _e('Evaluation Complete', 'mobility-trailblazers'); ?></strong>
                <p><?php _e('This evaluation has been submitted and cannot be modified.', 'mobility-trailblazers'); ?></p>
            </div>
            
            <form id="evaluation-form-modal">
                <?php wp_nonce_field('mt_jury_evaluation', 'mt_jury_evaluation_nonce'); ?>
                <h3><?php _e('Evaluation Criteria', 'mobility-trailblazers'); ?></h3>
                
                <div class="criteria-group">
                    <label for="courage-score">
                        <?php _e('Mut & Pioniergeist (Courage & Pioneer Spirit)', 'mobility-trailblazers'); ?>
                        <span class="score-display">5</span>
                    </label>
                    <input type="range" id="courage-score" class="score-slider" min="1" max="10" value="5" />
                    <p class="criteria-description"><?php _e('Willingness to take risks and break new ground', 'mobility-trailblazers'); ?></p>
                </div>
                
                <div class="criteria-group">
                    <label for="innovation-score">
                        <?php _e('Innovationsgrad (Degree of Innovation)', 'mobility-trailblazers'); ?>
                        <span class="score-display">5</span>
                    </label>
                    <input type="range" id="innovation-score" class="score-slider" min="1" max="10" value="5" />
                    <p class="criteria-description"><?php _e('Novelty and originality of the solution', 'mobility-trailblazers'); ?></p>
                </div>
                
                <div class="criteria-group">
                    <label for="implementation-score">
                        <?php _e('Umsetzungskraft & Wirkung (Implementation & Impact)', 'mobility-trailblazers'); ?>
                        <span class="score-display">5</span>
                    </label>
                    <input type="range" id="implementation-score" class="score-slider" min="1" max="10" value="5" />
                    <p class="criteria-description"><?php _e('Ability to execute and measurable impact achieved', 'mobility-trailblazers'); ?></p>
                </div>
                
                <div class="criteria-group">
                    <label for="relevance-score">
                        <?php _e('Relevanz für Mobilitätswende (Mobility Transformation Relevance)', 'mobility-trailblazers'); ?>
                        <span class="score-display">5</span>
                    </label>
                    <input type="range" id="relevance-score" class="score-slider" min="1" max="10" value="5" />
                    <p class="criteria-description"><?php _e('Contribution to sustainable mobility transformation', 'mobility-trailblazers'); ?></p>
                </div>
                
                <div class="criteria-group">
                    <label for="visibility-score">
                        <?php _e('Vorbildfunktion & Sichtbarkeit (Role Model & Visibility)', 'mobility-trailblazers'); ?>
                        <span class="score-display">5</span>
                    </label>
                    <input type="range" id="visibility-score" class="score-slider" min="1" max="10" value="5" />
                    <p class="criteria-description"><?php _e('Inspirational impact and public visibility', 'mobility-trailblazers'); ?></p>
                </div>
                
                <div class="total-score">
                    <strong><?php _e('Total Score:', 'mobility-trailblazers'); ?></strong>
                    <span id="total-score">25</span>/50
                </div>
                
                <div class="evaluation-comments">
                    <label for="evaluation-comments"><?php _e('Additional Comments (Optional)', 'mobility-trailblazers'); ?></label>
                    <textarea id="evaluation-comments" rows="4"></textarea>
                </div>
                
                <div class="evaluation-actions">
                    <button type="button" id="save-draft" class="button"><?php _e('Save Draft', 'mobility-trailblazers'); ?></button>
                    <button type="button" id="submit-evaluation" class="button button-primary"><?php _e('Submit Evaluation', 'mobility-trailblazers'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Localize script data
var mt_jury_ajax = {
    ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('mt_jury_nonce'); ?>',
    default_avatar: '<?php echo get_avatar_url(0, array('size' => 96)); ?>',
    i18n: {
        unsaved_changes: '<?php _e('You have unsaved changes. Are you sure you want to leave?', 'mobility-trailblazers'); ?>',
        error_loading: '<?php _e('Error loading candidate data.', 'mobility-trailblazers'); ?>',
        network_error: '<?php _e('Network error. Please try again.', 'mobility-trailblazers'); ?>',
        submit_evaluation: '<?php _e('Submit Evaluation', 'mobility-trailblazers'); ?>',
        update_evaluation: '<?php _e('Update Evaluation', 'mobility-trailblazers'); ?>',
        save_success: '<?php _e('Evaluation saved successfully.', 'mobility-trailblazers'); ?>',
        save_error: '<?php _e('Error saving evaluation.', 'mobility-trailblazers'); ?>',
        submit_success: '<?php _e('Evaluation submitted successfully.', 'mobility-trailblazers'); ?>',
        submit_error: '<?php _e('Error submitting evaluation.', 'mobility-trailblazers'); ?>',
        export_success: '<?php _e('Evaluations exported successfully.', 'mobility-trailblazers'); ?>',
        export_error: '<?php _e('Error exporting evaluations.', 'mobility-trailblazers'); ?>',
        no_candidates_found: '<?php _e('No candidates found.', 'mobility-trailblazers'); ?>',
        evaluate: '<?php _e('Evaluate', 'mobility-trailblazers'); ?>',
        view_evaluation: '<?php _e('View Evaluation', 'mobility-trailblazers'); ?>',
        achievement: '<?php _e('Achievement', 'mobility-trailblazers'); ?>',
        impact: '<?php _e('Impact', 'mobility-trailblazers'); ?>',
        vision: '<?php _e('Vision', 'mobility-trailblazers'); ?>',
        please_complete_scores: '<?php _e('Please complete all scores before submitting.', 'mobility-trailblazers'); ?>',
        saving: '<?php _e('Saving...', 'mobility-trailblazers'); ?>',
        error_saving: '<?php _e('Error saving evaluation.', 'mobility-trailblazers'); ?>',
        confirm_submit: '<?php _e('Are you sure you want to submit this evaluation? This action cannot be undone.', 'mobility-trailblazers'); ?>',
        completed: '<?php _e('Completed', 'mobility-trailblazers'); ?>',
        draft: '<?php _e('Draft', 'mobility-trailblazers'); ?>',
        pending: '<?php _e('Pending', 'mobility-trailblazers'); ?>'
    }
};

jQuery(document).ready(function($) {
    // Initialize dashboard functionality
    if (typeof MTJuryDashboard !== 'undefined') {
        MTJuryDashboard.init();
    }
});
</script> 