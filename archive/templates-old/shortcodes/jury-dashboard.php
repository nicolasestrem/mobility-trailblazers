<?php
/**
 * Jury Dashboard Template
 * Enhanced evaluation interface with modern UI/UX
 * 
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current user and jury member
$current_user = wp_get_current_user();
$jury_member = mt_get_jury_member_by_user_id($current_user->ID);

if (!$jury_member) {
    echo '<div class="mt-jury-dashboard-error">';
    echo '<p>' . __('You are not authorized to access this dashboard.', 'mobility-trailblazers') . '</p>';
    echo '</div>';
    return;
}

// Get initial stats for server-side rendering
$assigned_candidates = mt_get_assigned_candidates($jury_member->ID);
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
    
    <!-- Statistics Section -->
    <div class="mt-stats-section">
        <div class="stats-grid">
            <div class="stat-box">
                <span class="stat-label"><?php _e('Assigned Candidates', 'mobility-trailblazers'); ?></span>
                <span class="stat-value" id="assigned-count"><?php echo $total_assigned; ?></span>
            </div>
            <div class="stat-box">
                <span class="stat-label"><?php _e('Completed Evaluations', 'mobility-trailblazers'); ?></span>
                <span class="stat-value" id="completed-count"><?php echo $completed_evaluations; ?></span>
            </div>
            <div class="stat-box">
                <span class="stat-label"><?php _e('Draft Evaluations', 'mobility-trailblazers'); ?></span>
                <span class="stat-value" id="draft-count"><?php echo $draft_evaluations; ?></span>
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="progress-container">
            <div class="progress-header">
                <h3><?php _e('Overall Progress', 'mobility-trailblazers'); ?></h3>
                <span class="progress-percentage" id="completion-percentage"><?php echo $completion_rate; ?>%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $completion_rate; ?>%" data-percentage="<?php echo $completion_rate; ?>"></div>
            </div>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="mt-filter-section">
        <div class="filter-controls">
            <div class="search-box">
                <input type="text" 
                       id="candidate-search" 
                       placeholder="<?php esc_attr_e('Search candidates...', 'mobility-trailblazers'); ?>"
                       aria-label="<?php esc_attr_e('Search candidates', 'mobility-trailblazers'); ?>">
            </div>
            <div class="filter-buttons">
                <button class="filter-btn active" data-status="all">
                    <?php _e('All', 'mobility-trailblazers'); ?>
                </button>
                <button class="filter-btn" data-status="pending">
                    <?php _e('Pending', 'mobility-trailblazers'); ?>
                </button>
                <button class="filter-btn" data-status="draft">
                    <?php _e('Draft', 'mobility-trailblazers'); ?>
                </button>
                <button class="filter-btn" data-status="completed">
                    <?php _e('Evaluated', 'mobility-trailblazers'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Candidates Grid -->
    <div class="mt-candidates-section">
        <div class="candidates-grid" id="candidates-grid">
            <!-- Populated via JavaScript -->
            <div class="candidates-loading">
                <p><?php _e('Loading candidates...', 'mobility-trailblazers'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Evaluation Modal -->
    <div id="evaluation-modal" class="modal-overlay">
        <div class="modal-content">
            <button class="modal-close" aria-label="<?php esc_attr_e('Close modal', 'mobility-trailblazers'); ?>">&times;</button>
            
            <div class="modal-header">
                <h2 id="modal-candidate-name"><?php _e('Candidate Name', 'mobility-trailblazers'); ?></h2>
                <div class="modal-meta">
                    <span id="modal-candidate-company"></span>
                    <span id="modal-candidate-position"></span>
                </div>
            </div>
            
            <div class="modal-body">
                <!-- Candidate Details -->
                <div class="candidate-details">
                    <h3><?php _e('About the Candidate', 'mobility-trailblazers'); ?></h3>
                    <div id="modal-candidate-content"></div>
                    
                    <div class="candidate-links">
                        <a href="#" id="modal-candidate-website" target="_blank" style="display:none;">
                            🌐 <?php _e('Website', 'mobility-trailblazers'); ?>
                        </a>
                        <a href="#" id="modal-candidate-linkedin" target="_blank" style="display:none;">
                            💼 <?php _e('LinkedIn', 'mobility-trailblazers'); ?>
                        </a>
                    </div>
                    
                    <div id="modal-candidate-achievement" style="display:none;"></div>
                    <div id="modal-candidate-impact" style="display:none;"></div>
                    <div id="modal-candidate-vision" style="display:none;"></div>
                </div>
                
                <!-- Readonly Notice -->
                <div class="evaluation-readonly-notice">
                    <?php _e('You have already submitted your evaluation for this candidate.', 'mobility-trailblazers'); ?>
                </div>
                
                <!-- Evaluation Form -->
                <form class="evaluation-form" id="evaluation-form">
                    <h3><?php _e('Evaluation Criteria', 'mobility-trailblazers'); ?></h3>
                    
                    <div class="criteria-grid">
                        <!-- Courage & Pioneer Spirit -->
                        <div class="criteria-group">
                            <label for="courage-score">
                                <?php _e('Mut & Pioniergeist (Courage & Pioneer Spirit)', 'mobility-trailblazers'); ?>
                                <span class="score-display">5</span>
                            </label>
                            <input type="range" id="courage-score" class="score-slider" min="1" max="10" value="5">
                            <p class="criteria-description">
                                <?php _e('Did they act against resistance? Were there new paths? Personal risk?', 'mobility-trailblazers'); ?>
                            </p>
                        </div>
                        
                        <!-- Innovation Degree -->
                        <div class="criteria-group">
                            <label for="innovation-score">
                                <?php _e('Innovationsgrad (Innovation Degree)', 'mobility-trailblazers'); ?>
                                <span class="score-display">5</span>
                            </label>
                            <input type="range" id="innovation-score" class="score-slider" min="1" max="10" value="5">
                            <p class="criteria-description">
                                <?php _e('To what extent does the contribution represent a real innovation?', 'mobility-trailblazers'); ?>
                            </p>
                        </div>
                        
                        <!-- Implementation & Impact -->
                        <div class="criteria-group">
                            <label for="implementation-score">
                                <?php _e('Umsetzungskraft & Wirkung (Implementation & Impact)', 'mobility-trailblazers'); ?>
                                <span class="score-display">5</span>
                            </label>
                            <input type="range" id="implementation-score" class="score-slider" min="1" max="10" value="5">
                            <p class="criteria-description">
                                <?php _e('What results were achieved? Scaling? Measurable impact?', 'mobility-trailblazers'); ?>
                            </p>
                        </div>
                        
                        <!-- Mobility Transformation Relevance -->
                        <div class="criteria-group">
                            <label for="relevance-score">
                                <?php _e('Relevanz für Mobilitätswende (Mobility Transformation Relevance)', 'mobility-trailblazers'); ?>
                                <span class="score-display">5</span>
                            </label>
                            <input type="range" id="relevance-score" class="score-slider" min="1" max="10" value="5">
                            <p class="criteria-description">
                                <?php _e('Does the initiative contribute to mobility transformation in DACH?', 'mobility-trailblazers'); ?>
                            </p>
                        </div>
                        
                        <!-- Role Model & Visibility -->
                        <div class="criteria-group">
                            <label for="visibility-score">
                                <?php _e('Vorbildfunktion & Sichtbarkeit (Role Model & Visibility)', 'mobility-trailblazers'); ?>
                                <span class="score-display">5</span>
                            </label>
                            <input type="range" id="visibility-score" class="score-slider" min="1" max="10" value="5">
                            <p class="criteria-description">
                                <?php _e('Is the person an inspiring role model with public impact?', 'mobility-trailblazers'); ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Total Score -->
                    <div class="total-score-section">
                        <strong><?php _e('Total Score:', 'mobility-trailblazers'); ?></strong>
                        <span id="total-score">25</span>/50
                        <div id="score-indicator" class="score-indicator medium"></div>
                    </div>
                    
                    <!-- Comments -->
                    <div class="evaluation-comments">
                        <label for="evaluation-comments">
                            <?php _e('Additional Comments (Optional)', 'mobility-trailblazers'); ?>
                        </label>
                        <textarea id="evaluation-comments" 
                                  rows="4" 
                                  placeholder="<?php esc_attr_e('Share any additional observations or insights about this candidate...', 'mobility-trailblazers'); ?>"></textarea>
                    </div>
                    
                    <!-- Action Buttons -->
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
</div>