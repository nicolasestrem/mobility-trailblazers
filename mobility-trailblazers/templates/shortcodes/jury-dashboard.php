<?php
/**
 * Jury Dashboard Template
 *
 * @package MobilityTrailblazers
 * 
 * Available variables:
 * $jury_member - WP_Post object for jury member
 * $assigned_candidates - Array of assigned candidate posts
 * $atts - Shortcode attributes
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get evaluation statistics
global $wpdb;
$total_assigned = count($assigned_candidates);
$evaluated_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(DISTINCT candidate_id) FROM {$wpdb->prefix}mt_votes 
     WHERE jury_member_id = %d AND is_active = 1",
    $jury_member->ID
));
$draft_count = count(mt_get_draft_evaluations($jury_member->ID));
$remaining_count = $total_assigned - $evaluated_count;
$completion_rate = $total_assigned > 0 ? round(($evaluated_count / $total_assigned) * 100) : 0;
?>

<div class="mt-jury-dashboard" id="mt-jury-dashboard">
    <?php if ($atts['show_stats'] === 'yes'): ?>
        <div class="mt-dashboard-header">
            <h2><?php printf(__('Welcome, %s', 'mobility-trailblazers'), esc_html($jury_member->post_title)); ?></h2>
            <p><?php _e('Review and evaluate your assigned candidates below.', 'mobility-trailblazers'); ?></p>
        </div>
        
        <div class="mt-dashboard-stats">
            <div class="mt-stat-box">
                <span class="mt-stat-number"><?php echo $total_assigned; ?></span>
                <span class="mt-stat-label"><?php _e('Total Assigned', 'mobility-trailblazers'); ?></span>
            </div>
            <div class="mt-stat-box">
                <span class="mt-stat-number"><?php echo $evaluated_count; ?></span>
                <span class="mt-stat-label"><?php _e('Evaluated', 'mobility-trailblazers'); ?></span>
            </div>
            <div class="mt-stat-box">
                <span class="mt-stat-number"><?php echo $draft_count; ?></span>
                <span class="mt-stat-label"><?php _e('Drafts', 'mobility-trailblazers'); ?></span>
            </div>
            <div class="mt-stat-box">
                <span class="mt-stat-number"><?php echo $remaining_count; ?></span>
                <span class="mt-stat-label"><?php _e('Remaining', 'mobility-trailblazers'); ?></span>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($atts['show_progress'] === 'yes'): ?>
        <div class="mt-progress-section">
            <h3><?php _e('Progress', 'mobility-trailblazers'); ?></h3>
            <div class="mt-progress-bar">
                <div class="mt-progress-fill" style="width: <?php echo $completion_rate; ?>%"></div>
                <span class="mt-progress-text"><?php echo $completion_rate; ?>%</span>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($atts['show_filters'] === 'yes'): ?>
        <div class="mt-dashboard-filters">
            <input type="text" id="candidate-search" placeholder="<?php _e('Search candidates...', 'mobility-trailblazers'); ?>">
            
            <select id="category-filter">
                <option value=""><?php _e('All Categories', 'mobility-trailblazers'); ?></option>
                <?php
                $categories = get_terms(array(
                    'taxonomy' => 'mt_category',
                    'hide_empty' => false,
                ));
                foreach ($categories as $category):
                ?>
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
            
            <button class="button" id="export-evaluations">
                <?php _e('Export My Evaluations', 'mobility-trailblazers'); ?>
            </button>
        </div>
    <?php endif; ?>
    
    <div class="mt-candidates-list">
        <?php if (empty($assigned_candidates)): ?>
            <p class="no-candidates"><?php _e('No candidates have been assigned to you yet.', 'mobility-trailblazers'); ?></p>
        <?php else: ?>
            <?php foreach ($assigned_candidates as $candidate): 
                $evaluation = mt_get_evaluation($candidate->ID, $jury_member->ID);
                $is_draft = mt_has_draft_evaluation($candidate->ID, $jury_member->ID);
                $categories = wp_get_post_terms($candidate->ID, 'mt_category');
                $company = get_post_meta($candidate->ID, '_mt_company_name', true);
                $position = get_post_meta($candidate->ID, '_mt_position', true);
                $photo = get_post_meta($candidate->ID, '_mt_photo_url', true);
            ?>
                <div class="candidate-card" data-candidate-id="<?php echo $candidate->ID; ?>" 
                     data-categories="<?php echo esc_attr(implode(' ', wp_list_pluck($categories, 'slug'))); ?>">
                    
                    <div class="candidate-header">
                        <?php if ($photo): ?>
                            <img src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr($candidate->post_title); ?>" class="candidate-photo">
                        <?php endif; ?>
                        
                        <div class="candidate-info">
                            <h4 class="candidate-name"><?php echo esc_html($candidate->post_title); ?></h4>
                            <?php if ($company): ?>
                                <p class="candidate-company"><?php echo esc_html($company); ?></p>
                            <?php endif; ?>
                            <?php if ($position): ?>
                                <p class="candidate-position"><?php echo esc_html($position); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="candidate-status">
                            <?php if ($evaluation): ?>
                                <span class="evaluation-status completed">
                                    <i class="dashicons dashicons-yes-alt"></i>
                                    <?php _e('Evaluated', 'mobility-trailblazers'); ?>
                                </span>
                                <span class="evaluation-score"><?php echo $evaluation->total_score; ?>/50</span>
                            <?php elseif ($is_draft): ?>
                                <span class="evaluation-status draft">
                                    <i class="dashicons dashicons-edit"></i>
                                    <?php _e('Draft', 'mobility-trailblazers'); ?>
                                </span>
                            <?php else: ?>
                                <span class="evaluation-status pending">
                                    <i class="dashicons dashicons-clock"></i>
                                    <?php _e('Pending', 'mobility-trailblazers'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($categories)): ?>
                        <div class="candidate-categories">
                            <?php foreach ($categories as $category): ?>
                                <span class="category-tag"><?php echo esc_html($category->name); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="candidate-actions">
                        <button class="button button-primary evaluate-button" data-candidate-id="<?php echo $candidate->ID; ?>">
                            <?php echo $evaluation ? __('Edit Evaluation', 'mobility-trailblazers') : __('Evaluate', 'mobility-trailblazers'); ?>
                        </button>
                        <a href="<?php echo get_permalink($candidate->ID); ?>" target="_blank" class="button">
                            <?php _e('View Profile', 'mobility-trailblazers'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Evaluation Modal -->
    <div id="evaluation-modal" class="mt-modal" style="display: none;">
        <div class="mt-modal-content">
            <span class="mt-modal-close">&times;</span>
            <h2><?php _e('Evaluate Candidate', 'mobility-trailblazers'); ?></h2>
            
            <div id="evaluation-candidate-info">
                <!-- Populated via JavaScript -->
            </div>
            
            <form id="evaluation-form">
                <div class="evaluation-criteria">
                    <div class="criteria-item">
                        <label for="courage-score">
                            <?php _e('Mut & Pioniergeist (Courage & Pioneer Spirit)', 'mobility-trailblazers'); ?>
                            <span class="score-display">5</span>
                        </label>
                        <input type="range" id="courage-score" class="score-slider" min="1" max="10" value="5">
                        <p class="criteria-description"><?php _e('Willingness to take risks and break new ground', 'mobility-trailblazers'); ?></p>
                    </div>
                    
                    <div class="criteria-item">
                        <label for="innovation-score">
                            <?php _e('Innovationsgrad (Degree of Innovation)', 'mobility-trailblazers'); ?>
                            <span class="score-display">5</span>
                        </label>
                        <input type="range" id="innovation-score" class="score-slider" min="1" max="10" value="5">
                        <p class="criteria-description"><?php _e('Novelty and originality of the solution', 'mobility-trailblazers'); ?></p>
                    </div>
                    
                    <div class="criteria-item">
                        <label for="implementation-score">
                            <?php _e('Umsetzungskraft & Wirkung (Implementation & Impact)', 'mobility-trailblazers'); ?>
                            <span class="score-display">5</span>
                        </label>
                        <input type="range" id="implementation-score" class="score-slider" min="1" max="10" value="5">
                        <p class="criteria-description"><?php _e('Ability to execute and measurable impact', 'mobility-trailblazers'); ?></p>
                    </div>
                    
                    <div class="criteria-item">
                        <label for="relevance-score">
                            <?php _e('Relevanz für Mobilitätswende (Mobility Transformation Relevance)', 'mobility-trailblazers'); ?>
                            <span class="score-display">5</span>
                        </label>
                        <input type="range" id="relevance-score" class="score-slider" min="1" max="10" value="5">
                        <p class="criteria-description"><?php _e('Contribution to sustainable mobility', 'mobility-trailblazers'); ?></p>
                    </div>
                    
                    <div class="criteria-item">
                        <label for="visibility-score">
                            <?php _e('Vorbildfunktion & Sichtbarkeit (Role Model & Visibility)', 'mobility-trailblazers'); ?>
                            <span class="score-display">5</span>
                        </label>
                        <input type="range" id="visibility-score" class="score-slider" min="1" max="10" value="5">
                        <p class="criteria-description"><?php _e('Inspirational impact and public visibility', 'mobility-trailblazers'); ?></p>
                    </div>
                </div>
                
                <div class="total-score-section">
                    <strong><?php _e('Total Score:', 'mobility-trailblazers'); ?></strong>
                    <span id="total-score">25</span>/50
                    <div id="score-indicator" class="score-indicator medium"></div>
                </div>
                
                <div class="evaluation-comments">
                    <label for="evaluation-comments"><?php _e('Comments (Optional)', 'mobility-trailblazers'); ?></label>
                    <textarea id="evaluation-comments" rows="4"></textarea>
                </div>
                
                <div class="evaluation-actions">
                    <button type="button" class="button" id="save-draft"><?php _e('Save as Draft', 'mobility-trailblazers'); ?></button>
                    <button type="button" class="button button-primary" id="submit-evaluation"><?php _e('Submit Evaluation', 'mobility-trailblazers'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Initialize jury dashboard if script is loaded
jQuery(document).ready(function($) {
    if (typeof MTJuryDashboard !== 'undefined') {
        MTJuryDashboard.init();
    }
});
</script> 