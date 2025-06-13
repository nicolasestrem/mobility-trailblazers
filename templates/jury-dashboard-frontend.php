<?php
/**
 * Jury Dashboard Frontend Template
 * File: /wp-content/plugins/mobility-trailblazers/templates/jury-dashboard-frontend.php
 * 
 * This template can be used with the [mt_jury_dashboard] shortcode
 * or as a standalone page template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Get current user and verify they're a jury member
$current_user_id = get_current_user_id();
$jury_post = get_posts(array(
    'post_type' => 'mt_jury',
    'meta_query' => array(
        array(
            'key' => '_mt_jury_user_id',
            'value' => $current_user_id,
            'compare' => '='
        )
    ),
    'posts_per_page' => 1
));

if (empty($jury_post)) {
    ?>
    <div class="mt-container">
        <div class="mt-access-denied">
            <h1><?php _e('Access Denied', 'mobility-trailblazers'); ?></h1>
            <p><?php _e('This page is only accessible to jury members.', 'mobility-trailblazers'); ?></p>
            <a href="<?php echo home_url(); ?>" class="button"><?php _e('Return to Homepage', 'mobility-trailblazers'); ?></a>
        </div>
    </div>
    <?php
    get_footer();
    return;
}

$jury_member = $jury_post[0];
$jury_member_id = $jury_member->ID;

// Get assigned candidates
$assigned_candidates = get_posts(array(
    'post_type' => 'mt_candidate',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'meta_query' => array(
        array(
            'key' => '_mt_assigned_jury_member',
            'value' => $jury_member_id,
            'compare' => '='
        )
    ),
    'orderby' => 'title',
    'order' => 'ASC'
));

// Get evaluation statistics
global $wpdb;
$table_scores = $wpdb->prefix . 'mt_candidate_scores';
$evaluated_count = mt_get_user_evaluation_count($current_user_id);

$total_assigned = count($assigned_candidates);
$completion_rate = $total_assigned > 0 ? ($evaluated_count / $total_assigned) * 100 : 0;

// Get current phase
$current_phase = get_option('mt_current_phase', 'preparation');
$voting_enabled = get_option('mt_voting_enabled', false);

?>

<div class="mt-jury-dashboard-page">
    <div class="mt-container">
        
        <!-- Hero Section -->
        <div class="mt-hero-section">
            <div class="mt-hero-content">
                <h1><?php _e('Jury Member Dashboard', 'mobility-trailblazers'); ?></h1>
                <p class="mt-hero-subtitle">
                    <?php printf(__('Welcome back, %s', 'mobility-trailblazers'), esc_html($jury_member->post_title)); ?>
                    <?php if (get_post_meta($jury_member_id, '_mt_jury_is_president', true)): ?>
                        <span class="mt-role-badge president"><?php _e('President', 'mobility-trailblazers'); ?></span>
                    <?php elseif (get_post_meta($jury_member_id, '_mt_jury_is_vice_president', true)): ?>
                        <span class="mt-role-badge vice-president"><?php _e('Vice President', 'mobility-trailblazers'); ?></span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="mt-hero-stats">
                <div class="mt-stat-card">
                    <div class="mt-stat-icon">📋</div>
                    <div class="mt-stat-content">
                        <span class="mt-stat-value"><?php echo $total_assigned; ?></span>
                        <span class="mt-stat-label"><?php _e('Assigned Candidates', 'mobility-trailblazers'); ?></span>
                    </div>
                </div>
                <div class="mt-stat-card">
                    <div class="mt-stat-icon">✅</div>
                    <div class="mt-stat-content">
                        <span class="mt-stat-value"><?php echo $evaluated_count; ?></span>
                        <span class="mt-stat-label"><?php _e('Evaluated', 'mobility-trailblazers'); ?></span>
                    </div>
                </div>
                <div class="mt-stat-card">
                    <div class="mt-stat-icon">📊</div>
                    <div class="mt-stat-content">
                        <span class="mt-stat-value"><?php echo number_format($completion_rate, 0); ?>%</span>
                        <span class="mt-stat-label"><?php _e('Completion', 'mobility-trailblazers'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!$voting_enabled): ?>
            <div class="mt-notice mt-notice-warning">
                <div class="mt-notice-icon">⚠️</div>
                <div class="mt-notice-content">
                    <p><?php _e('The evaluation phase has not started yet. You will be notified when voting begins.', 'mobility-trailblazers'); ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Progress Section -->
        <div class="mt-section mt-progress-section">
            <h2 class="mt-section-title"><?php _e('Your Progress', 'mobility-trailblazers'); ?></h2>
            <div class="mt-progress-wrapper">
                <div class="mt-progress-bar">
                    <div class="mt-progress-fill" style="width: <?php echo $completion_rate; ?>%;">
                        <span class="mt-progress-text"><?php echo $evaluated_count; ?> / <?php echo $total_assigned; ?></span>
                    </div>
                </div>
                <p class="mt-progress-description">
                    <?php 
                    if ($completion_rate == 100) {
                        _e('Excellent! You have evaluated all assigned candidates.', 'mobility-trailblazers');
                    } elseif ($completion_rate >= 75) {
                        _e('Great progress! Almost done with your evaluations.', 'mobility-trailblazers');
                    } elseif ($completion_rate >= 50) {
                        _e('Good job! You\'re halfway through your evaluations.', 'mobility-trailblazers');
                    } elseif ($completion_rate > 0) {
                        _e('You\'re making progress. Keep going!', 'mobility-trailblazers');
                    } else {
                        _e('Ready to start? Begin evaluating your assigned candidates.', 'mobility-trailblazers');
                    }
                    ?>
                </p>
            </div>
        </div>
        
        <!-- Filter Controls -->
        <div class="mt-section mt-filter-controls">
            <div class="mt-filter-row">
                <div class="mt-search-box">
                    <input type="text" id="mt-search-candidates" placeholder="<?php _e('Search candidates by name or company...', 'mobility-trailblazers'); ?>">
                    <i class="mt-search-icon">🔍</i>
                </div>
                <div class="mt-filter-group">
                    <button class="mt-filter-button active" data-filter="all">
                        <?php _e('All', 'mobility-trailblazers'); ?> (<?php echo $total_assigned; ?>)
                    </button>
                    <button class="mt-filter-button" data-filter="pending">
                        <?php _e('Pending', 'mobility-trailblazers'); ?> (<?php echo $total_assigned - $evaluated_count; ?>)
                    </button>
                    <button class="mt-filter-button" data-filter="evaluated">
                        <?php _e('Evaluated', 'mobility-trailblazers'); ?> (<?php echo $evaluated_count; ?>)
                    </button>
                </div>
                <select id="mt-category-filter" class="mt-filter-select">
                    <option value=""><?php _e('All Categories', 'mobility-trailblazers'); ?></option>
                    <option value="established-companies"><?php _e('Established Companies', 'mobility-trailblazers'); ?></option>
                    <option value="startups-new-makers"><?php _e('Start-ups & New Makers', 'mobility-trailblazers'); ?></option>
                    <option value="infrastructure-politics-public"><?php _e('Infrastructure/Politics/Public', 'mobility-trailblazers'); ?></option>
                </select>
            </div>
        </div>
        
        <!-- Candidates Section -->
        <div class="mt-section mt-candidates-section">
            <h2 class="mt-section-title"><?php _e('Your Assigned Candidates', 'mobility-trailblazers'); ?></h2>
            
            <?php if (empty($assigned_candidates)): ?>
                <div class="mt-empty-state">
                    <div class="mt-empty-icon">📭</div>
                    <h3><?php _e('No Candidates Assigned Yet', 'mobility-trailblazers'); ?></h3>
                    <p><?php _e('You will be notified when candidates are assigned to you for evaluation.', 'mobility-trailblazers'); ?></p>
                </div>
            <?php else: ?>
                <div class="mt-candidates-grid">
                    <?php 
                    foreach ($assigned_candidates as $candidate): 
                        $candidate_id = $candidate->ID;
                        $company = get_post_meta($candidate_id, '_mt_company', true);
                        $position = get_post_meta($candidate_id, '_mt_position', true);
                        $location = get_post_meta($candidate_id, '_mt_location', true);
                        $innovation = get_post_meta($candidate_id, '_mt_innovation_description', true);
                        $categories = wp_get_post_terms($candidate_id, 'mt_category');
                        $category_slug = !empty($categories) ? $categories[0]->slug : '';
                        
                        // Check if already evaluated
                        $is_evaluated = mt_has_jury_evaluated($current_user_id, $candidate_id);
                        $total_score = $is_evaluated ? $existing_score->total_score : 0;
                    ?>
                        <div class="mt-candidate-card <?php echo $is_evaluated ? 'mt-evaluated' : 'mt-pending'; ?>" 
                             data-candidate-id="<?php echo $candidate_id; ?>"
                             data-status="<?php echo $is_evaluated ? 'evaluated' : 'pending'; ?>"
                             data-category="<?php echo esc_attr($category_slug); ?>">
                            
                            <!-- Status Indicator -->
                            <div class="mt-card-status">
                                <?php if ($is_evaluated): ?>
                                    <span class="mt-status-badge mt-evaluated">
                                        ✓ <?php _e('Evaluated', 'mobility-trailblazers'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="mt-status-badge mt-pending">
                                        ⏳ <?php _e('Pending', 'mobility-trailblazers'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Candidate Photo -->
                            <div class="mt-candidate-header">
                                <?php if (has_post_thumbnail($candidate_id)): ?>
                                    <div class="mt-candidate-photo">
                                        <?php echo get_the_post_thumbnail($candidate_id, 'medium'); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="mt-candidate-photo mt-photo-placeholder">
                                        <span>👤</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mt-candidate-info">
                                    <h3 class="mt-candidate-name"><?php echo esc_html($candidate->post_title); ?></h3>
                                    <?php if ($position): ?>
                                        <p class="mt-candidate-position"><?php echo esc_html($position); ?></p>
                                    <?php endif; ?>
                                    <?php if ($company): ?>
                                        <p class="mt-candidate-company"><?php echo esc_html($company); ?></p>
                                    <?php endif; ?>
                                    <?php if ($location): ?>
                                        <p class="mt-candidate-location">📍 <?php echo esc_html($location); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Category Badge -->
                            <?php if (!empty($categories)): ?>
                                <div class="mt-candidate-category">
                                    <span class="mt-category-badge mt-category-<?php echo esc_attr($category_slug); ?>">
                                        <?php echo esc_html($categories[0]->name); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Innovation Preview -->
                            <?php if ($innovation): ?>
                                <div class="mt-candidate-innovation">
                                    <h4><?php _e('Innovation Focus', 'mobility-trailblazers'); ?></h4>
                                    <p><?php echo wp_trim_words($innovation, 20); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Score Display (if evaluated) -->
                            <?php if ($is_evaluated): ?>
                                <div class="mt-score-summary">
                                    <div class="mt-score-circle">
                                        <span class="mt-score-value"><?php echo number_format($total_score, 1); ?></span>
                                        <span class="mt-score-max">/50</span>
                                    </div>
                                    <div class="mt-score-details">
                                        <div class="mt-score-row">
                                            <span><?php _e('Courage', 'mobility-trailblazers'); ?>:</span>
                                            <span><?php echo $existing_score->courage_score; ?>/10</span>
                                        </div>
                                        <div class="mt-score-row">
                                            <span><?php _e('Innovation', 'mobility-trailblazers'); ?>:</span>
                                            <span><?php echo $existing_score->innovation_score; ?>/10</span>
                                        </div>
                                        <div class="mt-score-row">
                                            <span><?php _e('Implementation', 'mobility-trailblazers'); ?>:</span>
                                            <span><?php echo $existing_score->implementation_score; ?>/10</span>
                                        </div>
                                        <div class="mt-score-row">
                                            <span><?php _e('Relevance', 'mobility-trailblazers'); ?>:</span>
                                            <span><?php echo $existing_score->relevance_score; ?>/10</span>
                                        </div>
                                        <div class="mt-score-row">
                                            <span><?php _e('Visibility', 'mobility-trailblazers'); ?>:</span>
                                            <span><?php echo $existing_score->visibility_score; ?>/10</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Action Buttons -->
                            <div class="mt-card-actions">
                                <a href="<?php echo get_permalink($candidate_id); ?>" class="mt-button mt-button-secondary" target="_blank">
                                    <?php _e('View Full Profile', 'mobility-trailblazers'); ?>
                                </a>
                                <?php if ($voting_enabled): ?>
                                    <button class="mt-button mt-button-primary mt-evaluate-button" data-candidate-id="<?php echo $candidate_id; ?>">
                                        <?php echo $is_evaluated ? __('Update Evaluation', 'mobility-trailblazers') : __('Evaluate Now', 'mobility-trailblazers'); ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Evaluation Modal -->
<div id="mt-evaluation-modal" class="mt-modal" style="display: none;">
    <div class="mt-modal-overlay"></div>
    <div class="mt-modal-content">
        <button class="mt-modal-close">×</button>
        
        <div class="mt-modal-header">
            <h2><?php _e('Candidate Evaluation', 'mobility-trailblazers'); ?></h2>
            <p class="mt-modal-subtitle"><?php _e('Please evaluate the candidate based on the five criteria below', 'mobility-trailblazers'); ?></p>
        </div>
        
        <form id="mt-evaluation-form" class="mt-evaluation-form">
            <input type="hidden" id="mt-candidate-id" name="candidate_id" value="">
            
            <!-- Candidate Info Display -->
            <div class="mt-modal-candidate-info">
                <h3 id="mt-modal-candidate-name"></h3>
                <p id="mt-modal-candidate-position"></p>
                <p id="mt-modal-candidate-company"></p>
            </div>
            
            <!-- Evaluation Criteria -->
            <div class="mt-evaluation-criteria">
                
                <!-- Courage & Pioneer Spirit -->
                <div class="mt-criterion-block">
                    <div class="mt-criterion-header">
                        <label for="courage_score">
                            <?php _e('Mut & Pioniergeist', 'mobility-trailblazers'); ?>
                            <span class="mt-label-en"><?php _e('(Courage & Pioneer Spirit)', 'mobility-trailblazers'); ?></span>
                        </label>
                        <div class="mt-score-value-display">
                            <span id="courage_value">5</span>/10
                        </div>
                    </div>
                    <p class="mt-criterion-description">
                        <?php _e('Did they act against resistance? Were there new paths? Personal risk?', 'mobility-trailblazers'); ?>
                    </p>
                    <div class="mt-slider-container">
                        <input type="range" id="courage_score" name="courage_score" min="0" max="10" value="5" class="mt-score-slider">
                        <div class="mt-slider-labels">
                            <span>0</span>
                            <span>5</span>
                            <span>10</span>
                        </div>
                    </div>
                </div>
                
                <!-- Innovation Degree -->
                <div class="mt-criterion-block">
                    <div class="mt-criterion-header">
                        <label for="innovation_score">
                            <?php _e('Innovationsgrad', 'mobility-trailblazers'); ?>
                            <span class="mt-label-en"><?php _e('(Innovation Degree)', 'mobility-trailblazers'); ?></span>
                        </label>
                        <div class="mt-score-value-display">
                            <span id="innovation_value">5</span>/10
                        </div>
                    </div>
                    <p class="mt-criterion-description">
                        <?php _e('To what extent does the contribution represent a real innovation?', 'mobility-trailblazers'); ?>
                    </p>
                    <div class="mt-slider-container">
                        <input type="range" id="innovation_score" name="innovation_score" min="0" max="10" value="5" class="mt-score-slider">
                        <div class="mt-slider-labels">
                            <span>0</span>
                            <span>5</span>
                            <span>10</span>
                        </div>
                    </div>
                </div>
                
                <!-- Implementation & Impact -->
                <div class="mt-criterion-block">
                    <div class="mt-criterion-header">
                        <label for="implementation_score">
                            <?php _e('Umsetzungskraft & Wirkung', 'mobility-trailblazers'); ?>
                            <span class="mt-label-en"><?php _e('(Implementation & Impact)', 'mobility-trailblazers'); ?></span>
                        </label>
                        <div class="mt-score-value-display">
                            <span id="implementation_value">5</span>/10
                        </div>
                    </div>
                    <p class="mt-criterion-description">
                        <?php _e('What results were achieved? Scaling? Measurable impact?', 'mobility-trailblazers'); ?>
                    </p>
                    <div class="mt-slider-container">
                        <input type="range" id="implementation_score" name="implementation_score" min="0" max="10" value="5" class="mt-score-slider">
                        <div class="mt-slider-labels">
                            <span>0</span>
                            <span>5</span>
                            <span>10</span>
                        </div>
                    </div>
                </div>
                
                <!-- Mobility Transformation Relevance -->
                <div class="mt-criterion-block">
                    <div class="mt-criterion-header">
                        <label for="mobility_relevance_score">
                            <?php _e('Relevanz für Mobilitätswende', 'mobility-trailblazers'); ?>
                            <span class="mt-label-en"><?php _e('(Mobility Transformation Relevance)', 'mobility-trailblazers'); ?></span>
                        </label>
                        <div class="mt-score-value-display">
                            <span id="mobility_relevance_value">5</span>/10
                        </div>
                    </div>
                    <p class="mt-criterion-description">
                        <?php _e('Does the initiative contribute to mobility transformation in DACH?', 'mobility-trailblazers'); ?>
                    </p>
                    <div class="mt-slider-container">
                        <input type="range" id="mobility_relevance_score" name="mobility_relevance_score" min="0" max="10" value="5" class="mt-score-slider">
                        <div class="mt-slider-labels">
                            <span>0</span>
                            <span>5</span>
                            <span>10</span>
                        </div>
                    </div>
                </div>
                
                <!-- Role Model & Visibility -->
                <div class="mt-criterion-block">
                    <div class="mt-criterion-header">
                        <label for="visibility_score">
                            <?php _e('Vorbildfunktion & Sichtbarkeit', 'mobility-trailblazers'); ?>
                            <span class="mt-label-en"><?php _e('(Role Model & Visibility)', 'mobility-trailblazers'); ?></span>
                        </label>
                        <div class="mt-score-value-display">
                            <span id="visibility_value">5</span>/10
                        </div>
                    </div>
                    <p class="mt-criterion-description">
                        <?php _e('Is the person an inspiring role model with public impact?', 'mobility-trailblazers'); ?>
                    </p>
                    <div class="mt-slider-container">
                        <input type="range" id="visibility_score" name="visibility_score" min="0" max="10" value="5" class="mt-score-slider">
                        <div class="mt-slider-labels">
                            <span>0</span>
                            <span>5</span>
                            <span>10</span>
                        </div>
                    </div>
                </div>
                
                <!-- Total Score Display -->
                <div class="mt-total-score-display">
                    <span class="mt-total-label"><?php _e('Total Score', 'mobility-trailblazers'); ?>:</span>
                    <span class="mt-total-value" id="mt-total-score">25</span>
                    <span class="mt-total-max">/50</span>
                </div>
                
                <!-- Comments Section -->
                <div class="mt-comments-section">
                    <label for="evaluation_comments">
                        <?php _e('Additional Comments', 'mobility-trailblazers'); ?>
                        <span class="mt-optional"><?php _e('(Optional)', 'mobility-trailblazers'); ?></span>
                    </label>
                    <textarea id="evaluation_comments" name="comments" rows="4" 
                              placeholder="<?php _e('Share any additional observations or insights about this candidate...', 'mobility-trailblazers'); ?>"></textarea>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="mt-modal-actions">
                <button type="button" class="mt-button mt-button-cancel">
                    <?php _e('Cancel', 'mobility-trailblazers'); ?>
                </button>
                <button type="submit" class="mt-button mt-button-primary">
                    <span class="mt-button-text"><?php _e('Submit Evaluation', 'mobility-trailblazers'); ?></span>
                    <span class="mt-button-loading" style="display: none;">⏳ <?php _e('Submitting...', 'mobility-trailblazers'); ?></span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Success Message -->
<div id="mt-success-message" class="mt-toast" style="display: none;">
    <div class="mt-toast-content">
        <span class="mt-toast-icon">✅</span>
        <span class="mt-toast-message"><?php _e('Evaluation submitted successfully!', 'mobility-trailblazers'); ?></span>
    </div>
</div>

<!-- Frontend Styles -->
<style>
/* Base Styles */
.mt-jury-dashboard-page {
    background: #f5f7fa;
    min-height: 100vh;
    padding: 40px 0;
}

.mt-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Hero Section */
.mt-hero-section {
    background: linear-gradient(135deg, #2c5282 0%, #38b2ac 100%);
    color: white;
    padding: 60px 40px;
    border-radius: 20px;
    margin-bottom: 40px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
}

.mt-hero-content h1 {
    font-size: 3rem;
    margin: 0 0 15px 0;
    font-weight: 700;
}

.mt-hero-subtitle {
    font-size: 1.3rem;
    opacity: 0.95;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 15px;
}

.mt-role-badge {
    display: inline-block;
    padding: 6px 16px;
    border-radius: 25px;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.mt-role-badge.president {
    background: #ffd700;
    color: #8b6914;
}

.mt-role-badge.vice-president {
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
}

/* Hero Stats */
.mt-hero-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
    margin-top: 50px;
}

.mt-stat-card {
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    padding: 30px;
    border-radius: 15px;
    text-align: center;
    border: 1px solid rgba(255,255,255,0.2);
    transition: transform 0.3s ease;
}

.mt-stat-card:hover {
    transform: translateY(-5px);
}

.mt-stat-icon {
    font-size: 2.5rem;
    margin-bottom: 15px;
}

.mt-stat-value {
    display: block;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 5px;
}

.mt-stat-label {
    font-size: 0.95rem;
    opacity: 0.9;
}

/* Sections */
.mt-section {
    background: white;
    padding: 40px;
    border-radius: 15px;
    margin-bottom: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.mt-section-title {
    font-size: 2rem;
    color: #2c5282;
    margin: 0 0 30px 0;
    font-weight: 600;
}

/* Progress Bar */
.mt-progress-wrapper {
    max-width: 800px;
    margin: 0 auto;
}

.mt-progress-bar {
    height: 50px;
    background: #e2e8f0;
    border-radius: 25px;
    overflow: hidden;
    position: relative;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
}

.mt-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #38a169 0%, #38b2ac 100%);
    transition: width 1s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.mt-progress-text {
    color: white;
    font-weight: 600;
    font-size: 1.2rem;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}

.mt-progress-description {
    text-align: center;
    margin-top: 20px;
    font-size: 1.1rem;
    color: #4a5568;
}

/* Filter Controls */
.mt-filter-controls {
    background: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.mt-filter-row {
    display: flex;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
}

.mt-search-box {
    flex: 1;
    min-width: 300px;
    position: relative;
}

.mt-search-box input {
    width: 100%;
    padding: 12px 20px 12px 45px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}

.mt-search-box input:focus {
    outline: none;
    border-color: #38b2ac;
}

.mt-search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 20px;
}

.mt-filter-group {
    display: flex;
    gap: 10px;
}

.mt-filter-button {
    padding: 10px 20px;
    border: 2px solid #e2e8f0;
    background: white;
    border-radius: 10px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.mt-filter-button:hover {
    border-color: #38b2ac;
    color: #38b2ac;
}

.mt-filter-button.active {
    background: #38b2ac;
    color: white;
    border-color: #38b2ac;
}

.mt-filter-select {
    padding: 10px 20px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 16px;
    cursor: pointer;
}

/* Candidates Grid */
.mt-candidates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 30px;
}

.mt-candidate-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    position: relative;
    border: 2px solid transparent;
}

.mt-candidate-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.12);
}

.mt-candidate-card.mt-evaluated {
    border-color: #38a169;
    background: #f0fdf4;
}

.mt-card-status {
    position: absolute;
    top: 20px;
    right: 20px;
}

.mt-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.mt-status-badge.mt-evaluated {
    background: #38a169;
    color: white;
}

.mt-status-badge.mt-pending {
    background: #ed8936;
    color: white;
}

/* Candidate Header */
.mt-candidate-header {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.mt-candidate-photo {
    width: 100px;
    height: 100px;
    border-radius: 15px;
    overflow: hidden;
    flex-shrink: 0;
}

.mt-candidate-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.mt-photo-placeholder {
    background: #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 50px;
}

.mt-candidate-info {
    flex: 1;
}

.mt-candidate-name {
    font-size: 1.5rem;
    color: #2c5282;
    margin: 0 0 8px 0;
    font-weight: 600;
}

.mt-candidate-position {
    font-weight: 500;
    color: #4a5568;
    margin: 0 0 5px 0;
}

.mt-candidate-company {
    color: #718096;
    margin: 0 0 5px 0;
}

.mt-candidate-location {
    font-size: 0.9rem;
    color: #718096;
    margin: 0;
}

/* Category Badge */
.mt-candidate-category {
    margin-bottom: 20px;
}

.mt-category-badge {
    display: inline-block;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    color: white;
}

.mt-category-established-companies {
    background: #2c5282;
}

.mt-category-startups-new-makers {
    background: #ed8936;
}

.mt-category-infrastructure-politics-public {
    background: #38b2ac;
}

/* Innovation Section */
.mt-candidate-innovation {
    background: #f7fafc;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.mt-candidate-innovation h4 {
    margin: 0 0 10px 0;
    color: #2c5282;
    font-size: 1rem;
}

.mt-candidate-innovation p {
    margin: 0;
    color: #4a5568;
    line-height: 1.6;
}

/* Score Summary */
.mt-score-summary {
    background: #f7fafc;
    padding: 25px;
    border-radius: 10px;
    margin-bottom: 20px;
    border: 1px solid #e2e8f0;
}

.mt-score-circle {
    text-align: center;
    margin-bottom: 20px;
}

.mt-score-value {
    font-size: 3rem;
    font-weight: 700;
    color: #38a169;
}

.mt-score-max {
    font-size: 1.5rem;
    color: #718096;
}

.mt-score-details {
    display: grid;
    gap: 10px;
}

.mt-score-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 15px;
    background: white;
    border-radius: 5px;
    font-size: 0.95rem;
}

.mt-score-row span:first-child {
    color: #4a5568;
}

.mt-score-row span:last-child {
    font-weight: 600;
    color: #2c5282;
}

/* Card Actions */
.mt-card-actions {
    display: flex;
    gap: 15px;
    margin-top: auto;
}

.mt-button {
    flex: 1;
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    text-decoration: none;
    display: inline-block;
}

.mt-button-primary {
    background: #38b2ac;
    color: white;
}

.mt-button-primary:hover {
    background: #319795;
    transform: translateY(-2px);
}

.mt-button-secondary {
    background: white;
    color: #2c5282;
    border: 2px solid #2c5282;
}

.mt-button-secondary:hover {
    background: #2c5282;
    color: white;
}

/* Empty State */
.mt-empty-state {
    text-align: center;
    padding: 80px 40px;
}

.mt-empty-icon {
    font-size: 5rem;
    margin-bottom: 20px;
}

.mt-empty-state h3 {
    font-size: 1.8rem;
    color: #2c5282;
    margin: 0 0 10px 0;
}

.mt-empty-state p {
    color: #718096;
    font-size: 1.1rem;
}

/* Notice */
.mt-notice {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px 30px;
    border-radius: 10px;
    margin-bottom: 30px;
}

.mt-notice-warning {
    background: #fef3c7;
    border: 1px solid #f59e0b;
}

.mt-notice-icon {
    font-size: 2rem;
}

.mt-notice-content p {
    margin: 0;
    color: #92400e;
    font-weight: 500;
}

/* Modal Styles */
.mt-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.mt-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.8);
}

.mt-modal-content {
    position: relative;
    background: white;
    border-radius: 20px;
    max-width: 800px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.mt-modal-close {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 40px;
    height: 40px;
    border: none;
    background: #f7fafc;
    border-radius: 50%;
    font-size: 24px;
    cursor: pointer;
    transition: all 0.3s ease;
    z-index: 10;
}

.mt-modal-close:hover {
    background: #e2e8f0;
}

.mt-modal-header {
    padding: 40px 40px 20px;
    border-bottom: 1px solid #e2e8f0;
}

.mt-modal-header h2 {
    margin: 0 0 10px 0;
    color: #2c5282;
    font-size: 2rem;
}

.mt-modal-subtitle {
    color: #718096;
    margin: 0;
}

/* Evaluation Form */
.mt-evaluation-form {
    padding: 40px;
}

.mt-modal-candidate-info {
    background: #f7fafc;
    padding: 25px;
    border-radius: 15px;
    margin-bottom: 40px;
    text-align: center;
}

.mt-modal-candidate-info h3 {
    margin: 0 0 10px 0;
    color: #2c5282;
    font-size: 1.8rem;
}

.mt-modal-candidate-info p {
    margin: 5px 0;
    color: #4a5568;
}

/* Criteria Blocks */
.mt-criterion-block {
    margin-bottom: 35px;
    padding: 25px;
    background: #fafafa;
    border-radius: 15px;
    border: 1px solid #e2e8f0;
}

.mt-criterion-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.mt-criterion-header label {
    font-size: 1.2rem;
    font-weight: 600;
    color: #2c5282;
}

.mt-label-en {
    font-size: 0.9rem;
    color: #718096;
    font-weight: 400;
}

.mt-score-value-display {
    font-size: 1.5rem;
    font-weight: 700;
    color: #38b2ac;
}

.mt-criterion-description {
    color: #4a5568;
    margin: 0 0 20px 0;
    line-height: 1.6;
}

/* Sliders */
.mt-slider-container {
    position: relative;
}

.mt-score-slider {
    width: 100%;
    height: 8px;
    -webkit-appearance: none;
    appearance: none;
    background: #e2e8f0;
    border-radius: 4px;
    outline: none;
    margin: 20px 0;
}

.mt-score-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 28px;
    height: 28px;
    background: #38b2ac;
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.mt-score-slider::-moz-range-thumb {
    width: 28px;
    height: 28px;
    background: #38b2ac;
    border-radius: 50%;
    cursor: pointer;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.mt-slider-labels {
    display: flex;
    justify-content: space-between;
    margin-top: -10px;
    font-size: 0.85rem;
    color: #718096;
}

/* Total Score Display */
.mt-total-score-display {
    background: linear-gradient(135deg, #2c5282 0%, #38b2ac 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
    text-align: center;
    margin: 40px 0;
}

.mt-total-label {
    font-size: 1.2rem;
    opacity: 0.9;
}

.mt-total-value {
    font-size: 3rem;
    font-weight: 700;
    margin: 0 10px;
}

.mt-total-max {
    font-size: 1.5rem;
    opacity: 0.9;
}

/* Comments Section */
.mt-comments-section {
    margin-bottom: 30px;
}

.mt-comments-section label {
    display: block;
    font-weight: 600;
    color: #2c5282;
    margin-bottom: 10px;
    font-size: 1.1rem;
}

.mt-optional {
    font-weight: 400;
    color: #718096;
    font-size: 0.9rem;
}

.mt-comments-section textarea {
    width: 100%;
    padding: 15px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 16px;
    resize: vertical;
    transition: border-color 0.3s ease;
}

.mt-comments-section textarea:focus {
    outline: none;
    border-color: #38b2ac;
}

/* Modal Actions */
.mt-modal-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

.mt-button-cancel {
    background: #e2e8f0;
    color: #4a5568;
}

.mt-button-cancel:hover {
    background: #cbd5e0;
}

/* Toast Message */
.mt-toast {
    position: fixed;
    top: 30px;
    right: 30px;
    background: white;
    padding: 20px 30px;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    z-index: 10000;
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

.mt-toast-content {
    display: flex;
    align-items: center;
    gap: 15px;
}

.mt-toast-icon {
    font-size: 2rem;
}

.mt-toast-message {
    font-weight: 600;
    color: #2c5282;
}

/* Responsive Design */
@media (max-width: 768px) {
    .mt-hero-content h1 {
        font-size: 2rem;
    }
    
    .mt-hero-stats {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .mt-filter-row {
        flex-direction: column;
    }
    
    .mt-candidates-grid {
        grid-template-columns: 1fr;
    }
    
    .mt-candidate-header {
        flex-direction: column;
        text-align: center;
    }
    
    .mt-card-actions {
        flex-direction: column;
    }
    
    .mt-modal-content {
        margin: 20px;
    }
}

/* Loading State */
.mt-loading {
    opacity: 0.6;
    pointer-events: none;
}
</style>

<!-- Frontend JavaScript -->
<script>
jQuery(document).ready(function($) {
    // Filter functionality
    $('.mt-filter-button').on('click', function() {
        $('.mt-filter-button').removeClass('active');
        $(this).addClass('active');
        filterCandidates();
    });
    
    $('#mt-search-candidates').on('input', function() {
        filterCandidates();
    });
    
    $('#mt-category-filter').on('change', function() {
        filterCandidates();
    });
    
    function filterCandidates() {
        var searchTerm = $('#mt-search-candidates').val().toLowerCase();
        var statusFilter = $('.mt-filter-button.active').data('filter');
        var categoryFilter = $('#mt-category-filter').val();
        
        $('.mt-candidate-card').each(function() {
            var $card = $(this);
            var name = $card.find('.mt-candidate-name').text().toLowerCase();
            var company = $card.find('.mt-candidate-company').text().toLowerCase();
            var status = $card.data('status');
            var category = $card.data('category');
            
            var matchesSearch = searchTerm === '' || name.includes(searchTerm) || company.includes(searchTerm);
            var matchesStatus = statusFilter === 'all' || status === statusFilter;
            var matchesCategory = categoryFilter === '' || category === categoryFilter;
            
            if (matchesSearch && matchesStatus && matchesCategory) {
                $card.show();
            } else {
                $card.hide();
            }
        });
    }
    
    // Evaluation button click
    $('.mt-evaluate-button').on('click', function() {
        var candidateId = $(this).data('candidate-id');
        var $card = $(this).closest('.mt-candidate-card');
        
        // Set candidate info in modal
        $('#mt-candidate-id').val(candidateId);
        $('#mt-modal-candidate-name').text($card.find('.mt-candidate-name').text());
        $('#mt-modal-candidate-position').text($card.find('.mt-candidate-position').text());
        $('#mt-modal-candidate-company').text($card.find('.mt-candidate-company').text());
        
        // Load existing scores if evaluated
        if ($card.hasClass('mt-evaluated')) {
            // In real implementation, load scores via AJAX
            // For now, reset to defaults
        }
        
        // Show modal
        $('#mt-evaluation-modal').fadeIn();
    });
    
    // Close modal
    $('.mt-modal-close, .mt-button-cancel').on('click', function() {
        $('#mt-evaluation-modal').fadeOut();
    });
    
    // Click outside modal to close
    $('.mt-modal-overlay').on('click', function() {
        $('#mt-evaluation-modal').fadeOut();
    });
    
    // Update score display on slider change
    $('.mt-score-slider').on('input', function() {
        var score = $(this).val();
        var scoreId = $(this).attr('id').replace('_score', '_value');
        $('#' + scoreId).text(score);
        
        // Update total score
        updateTotalScore();
    });
    
    function updateTotalScore() {
        var total = 0;
        $('.mt-score-slider').each(function() {
            total += parseInt($(this).val());
        });
        $('#mt-total-score').text(total);
    }
    
    // Submit evaluation
    $('#mt-evaluation-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        
        // Show loading state
        $submitBtn.find('.mt-button-text').hide();
        $submitBtn.find('.mt-button-loading').show();
        $submitBtn.prop('disabled', true);
        
        // Prepare data
        var formData = $form.serialize();
        formData += '&action=mt_submit_vote';
        formData += '&nonce=<?php echo wp_create_nonce('mt_nonce'); ?>';
        
        // Submit via AJAX
        $.post('<?php echo admin_url('admin-ajax.php'); ?>', formData, function(response) {
            if (response.success) {
                // Close modal
                $('#mt-evaluation-modal').fadeOut();
                
                // Show success message
                $('#mt-success-message').fadeIn();
                setTimeout(function() {
                    $('#mt-success-message').fadeOut();
                }, 3000);
                
                // Reload page to update status
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                alert('Error: ' + response.data.message);
            }
        }).always(function() {
            $submitBtn.find('.mt-button-text').show();
            $submitBtn.find('.mt-button-loading').hide();
            $submitBtn.prop('disabled', false);
        });
    });
    
    // Initialize sliders
    updateTotalScore();
});
</script>

<?php get_footer(); ?>