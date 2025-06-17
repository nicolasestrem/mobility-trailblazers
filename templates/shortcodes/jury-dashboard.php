<?php
/**
 * Jury Dashboard Template
 * 
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get stats for the dashboard
$total_assigned = count($assigned_candidates);
$completed_evaluations = 0;
$draft_evaluations = 0;

foreach ($assigned_candidates as $candidate_id) {
    if (mt_has_evaluated($candidate_id, $jury_member->ID)) {
        $completed_evaluations++;
    } elseif (mt_has_draft_evaluation($candidate_id, $jury_member->ID)) {
        $draft_evaluations++;
    }
}

$completion_rate = $total_assigned > 0 ? round(($completed_evaluations / $total_assigned) * 100) : 0;
?>

<div class="mt-jury-dashboard">
    <!-- Welcome Section -->
    <div class="mt-welcome-section">
        <h1><?php printf(__('Welcome back, %s', 'mobility-trailblazers'), esc_html($jury_member->post_title)); ?></h1>
        <p><?php _e('Your evaluations help shape the future of mobility in the DACH region.', 'mobility-trailblazers'); ?></p>
    </div>
    
    <?php if ($atts['show_stats'] === 'yes') : ?>
    <!-- Statistics Grid -->
    <div class="mt-dashboard-stats">
        <div class="mt-stat-box">
            <span class="mt-stat-icon">📋</span>
            <span class="mt-stat-number"><?php echo esc_html($total_assigned); ?></span>
            <span class="mt-stat-label"><?php _e('Assigned Candidates', 'mobility-trailblazers'); ?></span>
        </div>
        
        <div class="mt-stat-box">
            <span class="mt-stat-icon">✅</span>
            <span class="mt-stat-number"><?php echo esc_html($completed_evaluations); ?></span>
            <span class="mt-stat-label"><?php _e('Evaluated', 'mobility-trailblazers'); ?></span>
        </div>
        
        <div class="mt-stat-box">
            <span class="mt-stat-icon">📊</span>
            <span class="mt-stat-number"><?php echo esc_html($completion_rate); ?>%</span>
            <span class="mt-stat-label"><?php _e('Completion', 'mobility-trailblazers'); ?></span>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($atts['show_progress'] === 'yes' && $total_assigned > 0) : ?>
    <!-- Progress Section -->
    <div class="mt-progress-section">
        <div class="mt-progress-header">
            <h2><?php _e('Your Progress', 'mobility-trailblazers'); ?></h2>
            <span class="mt-progress-text" id="progress-text">
                <?php echo esc_html($completed_evaluations); ?> / <?php echo esc_html($total_assigned); ?>
            </span>
        </div>
        <div class="mt-progress-bar">
            <div class="mt-progress-fill" style="width: <?php echo esc_attr($completion_rate); ?>%"></div>
            <span class="mt-progress-percentage"><?php echo esc_html($completion_rate); ?>%</span>
        </div>
        <?php if ($completed_evaluations === $total_assigned && $total_assigned > 0) : ?>
            <p class="mt-progress-message"><?php _e('Excellent! You have evaluated all assigned candidates.', 'mobility-trailblazers'); ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <?php if ($atts['show_filters'] === 'yes') : ?>
    <!-- Filters Section -->
    <div class="mt-filters-section">
        <div class="mt-search-box">
            <input type="text" 
                   id="mt-candidate-search" 
                   placeholder="<?php esc_attr_e('Search candidates by name or company...', 'mobility-trailblazers'); ?>">
        </div>
        
        <div class="mt-filter-controls">
            <button class="filter-btn active" data-filter="all">
                <?php printf(__('All (%d)', 'mobility-trailblazers'), $total_assigned); ?>
            </button>
            <button class="filter-btn" data-filter="pending">
                <?php printf(__('Pending (%d)', 'mobility-trailblazers'), $total_assigned - $completed_evaluations - $draft_evaluations); ?>
            </button>
            <button class="filter-btn" data-filter="draft">
                <?php printf(__('Draft (%d)', 'mobility-trailblazers'), $draft_evaluations); ?>
            </button>
            <button class="filter-btn" data-filter="completed">
                <?php printf(__('Evaluated (%d)', 'mobility-trailblazers'), $completed_evaluations); ?>
            </button>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Candidates Section -->
    <div class="mt-candidates-section">
        <h2><?php _e('Your Assigned Candidates', 'mobility-trailblazers'); ?></h2>
        
        <?php if ($total_assigned > 0) : ?>
            <div class="mt-candidates-grid">
                <?php foreach ($assigned_candidates as $candidate_id) : 
                    $candidate = get_post($candidate_id);
                    if (!$candidate) continue;
                    
                    // Get candidate meta
                    $company = get_post_meta($candidate_id, '_mt_company', true);
                    $position = get_post_meta($candidate_id, '_mt_position', true);
                    $categories = wp_get_post_terms($candidate_id, 'mt_candidate_category', array('fields' => 'names'));
                    
                    // Get evaluation status
                    $evaluation_status = '';
                    $total_score = null;
                    
                    if (mt_has_evaluated($candidate_id, $jury_member->ID)) {
                        $evaluation_status = 'completed';
                        $evaluation = mt_get_evaluation($candidate_id, $jury_member->ID);
                        if ($evaluation) {
                            $total_score = intval($evaluation->courage) + intval($evaluation->innovation) + 
                                         intval($evaluation->implementation) + intval($evaluation->relevance) + 
                                         intval($evaluation->visibility);
                        }
                    } elseif (mt_has_draft_evaluation($candidate_id, $jury_member->ID)) {
                        $evaluation_status = 'draft';
                    }
                ?>
                <div class="candidate-card" 
                     data-candidate-id="<?php echo esc_attr($candidate_id); ?>"
                     data-status="<?php echo esc_attr($evaluation_status); ?>">
                    <div class="candidate-header">
                        <div>
                            <h3 class="candidate-name"><?php echo esc_html($candidate->post_title); ?></h3>
                            <?php if ($position) : ?>
                                <p class="candidate-position"><?php echo esc_html($position); ?></p>
                            <?php endif; ?>
                            <?php if ($company) : ?>
                                <p class="candidate-company"><?php echo esc_html($company); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php if ($evaluation_status) : ?>
                            <span class="evaluation-status <?php echo esc_attr($evaluation_status); ?>">
                                <?php echo $evaluation_status === 'completed' ? __('Evaluated', 'mobility-trailblazers') : __('Draft', 'mobility-trailblazers'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($categories)) : ?>
                    <div class="candidate-categories">
                        <?php foreach ($categories as $category) : ?>
                            <span class="category-tag"><?php echo esc_html($category); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($total_score !== null) : ?>
                        <div class="evaluation-score"><?php echo esc_html($total_score); ?>/50</div>
                    <?php endif; ?>
                    
                    <div class="card-hover-effect"></div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="no-candidates">
                <p><?php _e('No candidates have been assigned to you yet.', 'mobility-trailblazers'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Evaluation Modal -->
<div id="evaluation-modal" class="mt-modal">
    <div class="mt-modal-overlay"></div>
    <div class="mt-modal-content">
        <div class="mt-modal-header">
            <h2><?php _e('Candidate Evaluation', 'mobility-trailblazers'); ?></h2>
            <button class="mt-modal-close">&times;</button>
        </div>
        <div class="mt-modal-body">
            <form id="evaluation-form">
                <div class="mt-evaluation-header">
                    <div>
                        <h3 id="eval-candidate-name"></h3>
                        <div class="evaluation-info">
                            <span id="eval-candidate-position"></span>
                            <span id="eval-candidate-company"></span>
                        </div>
                    </div>
                </div>
                
                <div class="criteria-sections">
                    <div class="criteria-group">
                        <label for="courage-score">
                            <?php _e('Mut & Pioniergeist (Courage & Pioneer Spirit)', 'mobility-trailblazers'); ?>
                            <span class="score-display">5</span>
                        </label>
                        <input type="range" id="courage-score" class="score-slider" min="1" max="10" value="5">
                        <p class="criteria-description"><?php _e('Did they act against resistance? Were there new paths? Personal risk?', 'mobility-trailblazers'); ?></p>
                    </div>
                    
                    <div class="criteria-group">
                        <label for="innovation-score">
                            <?php _e('Innovationsgrad (Innovation Degree)', 'mobility-trailblazers'); ?>
                            <span class="score-display">5</span>
                        </label>
                        <input type="range" id="innovation-score" class="score-slider" min="1" max="10" value="5">
                        <p class="criteria-description"><?php _e('To what extent does the contribution represent a real innovation?', 'mobility-trailblazers'); ?></p>
                    </div>
                    
                    <div class="criteria-group">
                        <label for="implementation-score">
                            <?php _e('Umsetzungskraft & Wirkung (Implementation & Impact)', 'mobility-trailblazers'); ?>
                            <span class="score-display">5</span>
                        </label>
                        <input type="range" id="implementation-score" class="score-slider" min="1" max="10" value="5">
                        <p class="criteria-description"><?php _e('What results were achieved? Scaling? Measurable impact?', 'mobility-trailblazers'); ?></p>
                    </div>
                    
                    <div class="criteria-group">
                        <label for="relevance-score">
                            <?php _e('Relevanz für Mobilitätswende (Mobility Transformation Relevance)', 'mobility-trailblazers'); ?>
                            <span class="score-display">5</span>
                        </label>
                        <input type="range" id="relevance-score" class="score-slider" min="1" max="10" value="5">
                        <p class="criteria-description"><?php _e('Does the initiative contribute to mobility transformation in DACH?', 'mobility-trailblazers'); ?></p>
                    </div>
                    
                    <div class="criteria-group">
                        <label for="visibility-score">
                            <?php _e('Vorbildfunktion & Sichtbarkeit (Role Model & Visibility)', 'mobility-trailblazers'); ?>
                            <span class="score-display">5</span>
                        </label>
                        <input type="range" id="visibility-score" class="score-slider" min="1" max="10" value="5">
                        <p class="criteria-description"><?php _e('Is the person an inspiring role model with public impact?', 'mobility-trailblazers'); ?></p>
                    </div>
                </div>
                
                <div class="total-score-section">
                    <strong><?php _e('Total Score:', 'mobility-trailblazers'); ?></strong>
                    <span id="total-score">25</span>/50
                    <div id="score-indicator" class="score-indicator medium"></div>
                </div>
                
                <div class="evaluation-comments">
                    <label for="evaluation-comments"><?php _e('Additional Comments (Optional)', 'mobility-trailblazers'); ?></label>
                    <textarea id="evaluation-comments" 
                              rows="4" 
                              placeholder="<?php esc_attr_e('Share any additional observations or insights about this candidate...', 'mobility-trailblazers'); ?>"></textarea>
                </div>
                
                <div class="evaluation-actions">
                    <button type="button" class="button" id="save-draft">
                        <?php _e('Save as Draft', 'mobility-trailblazers'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="submit-evaluation">
                        <?php _e('Submit Evaluation', 'mobility-trailblazers'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Localize script data
wp_localize_script('mt-jury-dashboard', 'mt_jury_ajax', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('mt_jury_nonce'),
    'i18n' => array(
        'completed' => __('Evaluated', 'mobility-trailblazers'),
        'draft' => __('Draft', 'mobility-trailblazers'),
        'saving' => __('Saving...', 'mobility-trailblazers'),
        'save_draft' => __('Save as Draft', 'mobility-trailblazers'),
        'submit' => __('Submit Evaluation', 'mobility-trailblazers'),
        'confirm_submit' => __('Are you sure you want to submit this evaluation? This action cannot be undone.', 'mobility-trailblazers'),
        'please_complete_scores' => __('Please complete all evaluation criteria before submitting.', 'mobility-trailblazers'),
        'no_candidates_found' => __('No candidates found matching your search.', 'mobility-trailblazers'),
        'error_loading' => __('Error loading evaluation data. Please try again.', 'mobility-trailblazers'),
    )
));
?>