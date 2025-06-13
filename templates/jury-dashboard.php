<?php
/**
 * Jury Member Dashboard Template
 * File: /wp-content/plugins/mobility-trailblazers/templates/jury-dashboard.php
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

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
    wp_die(__('Access denied. This page is only for jury members.', 'mobility-trailblazers'));
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
$evaluated_count = function_exists('mt_get_user_evaluation_count') 
    ? mt_get_user_evaluation_count($current_user_id) 
    : 0;

$total_assigned = count($assigned_candidates);
$completion_rate = $total_assigned > 0 ? ($evaluated_count / $total_assigned) * 100 : 0;

// Get current phase
$current_phase = get_option('mt_current_phase', 'preparation');
$voting_enabled = get_option('mt_voting_enabled', false);

?>

<div class="wrap mt-jury-dashboard">
    <!-- Header -->
    <div class="mt-dashboard-header">
        <div class="mt-header-content">
            <h1><?php _e('Jury Member Dashboard', 'mobility-trailblazers'); ?></h1>
            <p class="mt-welcome-message">
                <?php printf(__('Welcome, %s', 'mobility-trailblazers'), esc_html($jury_member->post_title)); ?>
                <?php if (get_post_meta($jury_member_id, '_mt_jury_is_president', true)): ?>
                    <span class="mt-role-badge president"><?php _e('President', 'mobility-trailblazers'); ?></span>
                <?php elseif (get_post_meta($jury_member_id, '_mt_jury_is_vice_president', true)): ?>
                    <span class="mt-role-badge vice-president"><?php _e('Vice President', 'mobility-trailblazers'); ?></span>
                <?php endif; ?>
            </p>
        </div>
        <div class="mt-header-stats">
            <div class="mt-stat-box">
                <span class="mt-stat-value"><?php echo $total_assigned; ?></span>
                <span class="mt-stat-label"><?php _e('Assigned Candidates', 'mobility-trailblazers'); ?></span>
            </div>
            <div class="mt-stat-box">
                <span class="mt-stat-value"><?php echo $evaluated_count; ?></span>
                <span class="mt-stat-label"><?php _e('Evaluated', 'mobility-trailblazers'); ?></span>
            </div>
            <div class="mt-stat-box">
                <span class="mt-stat-value"><?php echo number_format($completion_rate, 0); ?>%</span>
                <span class="mt-stat-label"><?php _e('Completion', 'mobility-trailblazers'); ?></span>
            </div>
        </div>
    </div>

    <?php if (!$voting_enabled): ?>
        <div class="notice notice-warning">
            <p><?php _e('Voting is currently disabled. The evaluation phase will begin soon.', 'mobility-trailblazers'); ?></p>
        </div>
    <?php endif; ?>

    <!-- Progress Overview -->
    <div class="mt-progress-section">
        <h2><?php _e('Your Evaluation Progress', 'mobility-trailblazers'); ?></h2>
        <div class="mt-progress-bar-large">
            <div class="mt-progress-fill" style="width: <?php echo $completion_rate; ?>%;">
                <span class="mt-progress-text"><?php echo $evaluated_count; ?> / <?php echo $total_assigned; ?></span>
            </div>
        </div>
    </div>

    <!-- Filter and Search -->
    <div class="mt-filter-section">
        <div class="mt-search-wrapper">
            <input type="text" id="mt-candidate-search" placeholder="<?php _e('Search candidates...', 'mobility-trailblazers'); ?>" />
        </div>
        <div class="mt-filter-buttons">
            <button class="mt-filter-btn active" data-filter="all"><?php _e('All', 'mobility-trailblazers'); ?> (<?php echo $total_assigned; ?>)</button>
            <button class="mt-filter-btn" data-filter="evaluated"><?php _e('Evaluated', 'mobility-trailblazers'); ?> (<?php echo $evaluated_count; ?>)</button>
            <button class="mt-filter-btn" data-filter="pending"><?php _e('Pending', 'mobility-trailblazers'); ?> (<?php echo $total_assigned - $evaluated_count; ?>)</button>
        </div>
        <div class="mt-category-filters">
            <label><?php _e('Category:', 'mobility-trailblazers'); ?></label>
            <select id="mt-category-filter">
                <option value=""><?php _e('All Categories', 'mobility-trailblazers'); ?></option>
                <option value="established-companies"><?php _e('Established Companies', 'mobility-trailblazers'); ?></option>
                <option value="startups-new-makers"><?php _e('Start-ups & New Makers', 'mobility-trailblazers'); ?></option>
                <option value="infrastructure-politics-public"><?php _e('Infrastructure/Politics/Public', 'mobility-trailblazers'); ?></option>
            </select>
        </div>
    </div>

    <!-- Candidates Grid -->
    <div class="mt-candidates-section">
        <h2><?php _e('Your Assigned Candidates', 'mobility-trailblazers'); ?></h2>
        
        <?php if (empty($assigned_candidates)): ?>
            <div class="mt-no-candidates">
                <p><?php _e('No candidates have been assigned to you yet.', 'mobility-trailblazers'); ?></p>
            </div>
        <?php else: ?>
            <div class="mt-candidates-grid">
                <?php foreach ($assigned_candidates as $candidate): 
                    $candidate_id = $candidate->ID;
                    $company = get_post_meta($candidate_id, '_mt_company', true);
                    $position = get_post_meta($candidate_id, '_mt_position', true);
                    $location = get_post_meta($candidate_id, '_mt_location', true);
                    $categories = wp_get_post_terms($candidate_id, 'mt_category');
                    $category_slug = !empty($categories) ? $categories[0]->slug : '';
                    
                    // Check if already evaluated
                    $is_evaluated = function_exists('mt_has_jury_evaluated')
                        ? mt_has_jury_evaluated($current_user_id, $candidate_id)
                        : false;
                    
                    // Get the evaluation data if it exists
                    $existing_score = null;
                    if ($is_evaluated && function_exists('mt_get_user_evaluation')) {
                        $existing_score = mt_get_user_evaluation($current_user_id, $candidate_id);
                    } elseif ($is_evaluated) {
                        // Fallback if function doesn't exist
                        $existing_score = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM $table_scores WHERE candidate_id = %d AND jury_member_id = %d",
                            $candidate_id,
                            $current_user_id
                        ));
                    }
                    
                    $total_score = $is_evaluated ? $existing_score->total_score : 0;
                ?>
                    <div class="mt-candidate-card <?php echo $is_evaluated ? 'evaluated' : 'pending'; ?>" 
                         data-candidate-id="<?php echo $candidate_id; ?>"
                         data-status="<?php echo $is_evaluated ? 'evaluated' : 'pending'; ?>"
                         data-category="<?php echo esc_attr($category_slug); ?>">
                        
                        <!-- Card Header -->
                        <div class="mt-card-header">
                            <?php if ($is_evaluated): ?>
                                <span class="mt-status-badge evaluated">
                                    <i class="dashicons dashicons-yes-alt"></i> <?php _e('Evaluated', 'mobility-trailblazers'); ?>
                                </span>
                            <?php else: ?>
                                <span class="mt-status-badge pending">
                                    <i class="dashicons dashicons-clock"></i> <?php _e('Pending', 'mobility-trailblazers'); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($categories)): ?>
                                <span class="mt-category-badge"><?php echo esc_html($categories[0]->name); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Candidate Info -->
                        <div class="mt-candidate-info">
                            <?php if (has_post_thumbnail($candidate_id)): ?>
                                <div class="mt-candidate-photo">
                                    <?php echo get_the_post_thumbnail($candidate_id, 'thumbnail'); ?>
                                </div>
                            <?php else: ?>
                                <div class="mt-candidate-photo mt-placeholder-photo">
                                    <i class="dashicons dashicons-businessperson"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-candidate-details">
                                <h3><?php echo esc_html($candidate->post_title); ?></h3>
                                <?php if ($position): ?>
                                    <p class="mt-position"><?php echo esc_html($position); ?></p>
                                <?php endif; ?>
                                <?php if ($company): ?>
                                    <p class="mt-company"><?php echo esc_html($company); ?></p>
                                <?php endif; ?>
                                <?php if ($location): ?>
                                    <p class="mt-location"><i class="dashicons dashicons-location"></i> <?php echo esc_html($location); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Score Display (if evaluated) -->
                        <?php if ($is_evaluated): ?>
                            <div class="mt-score-display">
                                <div class="mt-score-circle">
                                    <span class="mt-score-value"><?php echo number_format($total_score, 1); ?></span>
                                    <span class="mt-score-max">/50</span>
                                </div>
                                <div class="mt-score-breakdown">
                                    <div class="mt-score-item">
                                        <span class="mt-score-label"><?php _e('Courage', 'mobility-trailblazers'); ?>:</span>
                                        <span class="mt-score-points"><?php echo $existing_score->courage_score; ?>/10</span>
                                    </div>
                                    <div class="mt-score-item">
                                        <span class="mt-score-label"><?php _e('Innovation', 'mobility-trailblazers'); ?>:</span>
                                        <span class="mt-score-points"><?php echo $existing_score->innovation_score; ?>/10</span>
                                    </div>
                                    <div class="mt-score-item">
                                        <span class="mt-score-label"><?php _e('Implementation', 'mobility-trailblazers'); ?>:</span>
                                        <span class="mt-score-points"><?php echo $existing_score->implementation_score; ?>/10</span>
                                    </div>
                                    <div class="mt-score-item">
                                        <span class="mt-score-label"><?php _e('Relevance', 'mobility-trailblazers'); ?>:</span>
                                        <span class="mt-score-points"><?php echo $existing_score->mobility_relevance_score; ?>/10</span>
                                    </div>
                                    <div class="mt-score-item">
                                        <span class="mt-score-label"><?php _e('Visibility', 'mobility-trailblazers'); ?>:</span>
                                        <span class="mt-score-points"><?php echo $existing_score->visibility_score; ?>/10</span>
                                    </div>
                                </div>
                                <?php if ($existing_score->evaluation_date): ?>
                                    <p class="mt-evaluation-date">
                                        <i class="dashicons dashicons-calendar-alt"></i> 
                                        <?php echo date_i18n(get_option('date_format'), strtotime($existing_score->evaluation_date)); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Action Buttons -->
                        <div class="mt-card-actions">
                            <a href="<?php echo get_permalink($candidate_id); ?>" class="button button-secondary" target="_blank">
                                <i class="dashicons dashicons-visibility"></i> <?php _e('View Profile', 'mobility-trailblazers'); ?>
                            </a>
                            <?php if ($voting_enabled): ?>
                                <button class="button button-primary mt-evaluate-btn" data-candidate-id="<?php echo $candidate_id; ?>">
                                    <i class="dashicons dashicons-edit"></i> 
                                    <?php echo $is_evaluated ? __('Update Evaluation', 'mobility-trailblazers') : __('Evaluate', 'mobility-trailblazers'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Evaluation Modal -->
    <div id="mt-evaluation-modal" class="mt-modal" style="display: none;">
        <div class="mt-modal-content">
            <div class="mt-modal-header">
                <h2><?php _e('Evaluate Candidate', 'mobility-trailblazers'); ?></h2>
                <button class="mt-modal-close">&times;</button>
            </div>
            <div class="mt-modal-body">
                <form id="mt-evaluation-form">
                    <input type="hidden" id="mt-candidate-id" name="candidate_id" value="">
                    
                    <!-- Candidate Preview -->
                    <div class="mt-candidate-preview">
                        <h3 id="mt-candidate-name"></h3>
                        <p id="mt-candidate-position"></p>
                        <p id="mt-candidate-company"></p>
                    </div>
                    
                    <!-- Evaluation Criteria -->
                    <div class="mt-evaluation-criteria">
                        <h4><?php _e('Evaluation Criteria', 'mobility-trailblazers'); ?></h4>
                        
                        <div class="mt-criterion">
                            <label for="courage_score">
                                <?php _e('Mut & Pioniergeist (Courage & Pioneer Spirit)', 'mobility-trailblazers'); ?>
                                <span class="mt-criterion-help" title="<?php _e('Did they act against resistance? Were there new paths? Personal risk?', 'mobility-trailblazers'); ?>">?</span>
                            </label>
                            <div class="mt-score-slider">
                                <input type="range" id="courage_score" name="courage_score" min="0" max="10" value="0" class="mt-slider">
                                <span class="mt-score-display">0</span>
                            </div>
                        </div>
                        
                        <div class="mt-criterion">
                            <label for="innovation_score">
                                <?php _e('Innovationsgrad (Innovation Degree)', 'mobility-trailblazers'); ?>
                                <span class="mt-criterion-help" title="<?php _e('To what extent does the contribution represent a real innovation (technology, business model)?', 'mobility-trailblazers'); ?>">?</span>
                            </label>
                            <div class="mt-score-slider">
                                <input type="range" id="innovation_score" name="innovation_score" min="0" max="10" value="0" class="mt-slider">
                                <span class="mt-score-display">0</span>
                            </div>
                        </div>
                        
                        <div class="mt-criterion">
                            <label for="implementation_score">
                                <?php _e('Umsetzungskraft & Wirkung (Implementation & Impact)', 'mobility-trailblazers'); ?>
                                <span class="mt-criterion-help" title="<?php _e('What results were achieved (e.g., scaling, impact)?', 'mobility-trailblazers'); ?>">?</span>
                            </label>
                            <div class="mt-score-slider">
                                <input type="range" id="implementation_score" name="implementation_score" min="0" max="10" value="0" class="mt-slider">
                                <span class="mt-score-display">0</span>
                            </div>
                        </div>
                        
                        <div class="mt-criterion">
                            <label for="mobility_relevance_score">
                                <?php _e('Relevanz für Mobilitätswende (Mobility Transformation Relevance)', 'mobility-trailblazers'); ?>
                                <span class="mt-criterion-help" title="<?php _e('Does the initiative contribute to the transformation of mobility in the DACH region?', 'mobility-trailblazers'); ?>">?</span>
                            </label>
                            <div class="mt-score-slider">
                                <input type="range" id="mobility_relevance_score" name="mobility_relevance_score" min="0" max="10" value="0" class="mt-slider">
                                <span class="mt-score-display">0</span>
                            </div>
                        </div>
                        
                        <div class="mt-criterion">
                            <label for="visibility_score">
                                <?php _e('Vorbildfunktion & Sichtbarkeit (Role Model & Visibility)', 'mobility-trailblazers'); ?>
                                <span class="mt-criterion-help" title="<?php _e('Is the person an inspiring role model with public impact?', 'mobility-trailblazers'); ?>">?</span>
                            </label>
                            <div class="mt-score-slider">
                                <input type="range" id="visibility_score" name="visibility_score" min="0" max="10" value="0" class="mt-slider">
                                <span class="mt-score-display">0</span>
                            </div>
                        </div>
                        
                        <!-- Total Score Display -->
                        <div class="mt-total-score">
                            <span class="mt-total-label"><?php _e('Total Score:', 'mobility-trailblazers'); ?></span>
                            <span class="mt-total-value">0</span>
                            <span class="mt-total-max">/50</span>
                        </div>
                        
                        <!-- Comments -->
                        <div class="mt-criterion mt-comments">
                            <label for="evaluation_comments">
                                <?php _e('Comments (Optional)', 'mobility-trailblazers'); ?>
                            </label>
                            <textarea id="evaluation_comments" name="comments" rows="4" placeholder="<?php _e('Add any additional observations or notes...', 'mobility-trailblazers'); ?>"></textarea>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="mt-modal-actions">
                        <button type="button" class="button mt-modal-cancel"><?php _e('Cancel', 'mobility-trailblazers'); ?></button>
                        <button type="submit" class="button button-primary"><?php _e('Submit Evaluation', 'mobility-trailblazers'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Jury Dashboard Styles */
.mt-jury-dashboard {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.mt-dashboard-header {
    background: linear-gradient(135deg, #2c5282 0%, #38b2ac 100%);
    color: white;
    padding: 40px;
    border-radius: 12px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
}

.mt-header-content h1 {
    margin: 0 0 10px 0;
    font-size: 2.5em;
    font-weight: 700;
}

.mt-welcome-message {
    font-size: 1.2em;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 15px;
}

.mt-role-badge {
    display: inline-block;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.8em;
    font-weight: 600;
    text-transform: uppercase;
}

.mt-role-badge.president {
    background: #ffd700;
    color: #8b6914;
}

.mt-role-badge.vice-president {
    background: rgba(255,255,255,0.3);
}

.mt-header-stats {
    display: flex;
    gap: 30px;
}

.mt-stat-box {
    text-align: center;
    background: rgba(255,255,255,0.1);
    padding: 20px 30px;
    border-radius: 10px;
    backdrop-filter: blur(10px);
}

.mt-stat-value {
    display: block;
    font-size: 2.5em;
    font-weight: 700;
    margin-bottom: 5px;
}

.mt-stat-label {
    font-size: 0.9em;
    opacity: 0.9;
}

/* Progress Section */
.mt-progress-section {
    background: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.mt-progress-section h2 {
    margin: 0 0 20px 0;
    color: #2c5282;
}

.mt-progress-bar-large {
    height: 40px;
    background: #e2e8f0;
    border-radius: 20px;
    overflow: hidden;
    position: relative;
}

.mt-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #38a169 0%, #38b2ac 100%);
    transition: width 0.5s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mt-progress-text {
    color: white;
    font-weight: 600;
    font-size: 1.1em;
}

/* Filter Section */
.mt-filter-section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    display: flex;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
}

.mt-search-wrapper {
    flex: 1;
    min-width: 250px;
}

#mt-candidate-search {
    width: 100%;
    padding: 12px 20px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 16px;
}

.mt-filter-buttons {
    display: flex;
    gap: 10px;
}

.mt-filter-btn {
    padding: 10px 20px;
    border: 2px solid #e2e8f0;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.mt-filter-btn:hover {
    border-color: #38b2ac;
    color: #38b2ac;
}

.mt-filter-btn.active {
    background: #38b2ac;
    color: white;
    border-color: #38b2ac;
}

.mt-category-filters select {
    padding: 10px 15px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
}

/* Candidates Grid */
.mt-candidates-section {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.mt-candidates-section h2 {
    margin: 0 0 30px 0;
    color: #2c5282;
}

.mt-candidates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 25px;
}

.mt-candidate-card {
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 25px;
    transition: all 0.3s ease;
    background: #fafafa;
}

.mt-candidate-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.mt-candidate-card.evaluated {
    background: #f0fdf4;
    border-color: #38a169;
}

.mt-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.mt-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: 600;
}

.mt-status-badge.evaluated {
    background: #38a169;
    color: white;
}

.mt-status-badge.pending {
    background: #ed8936;
    color: white;
}

.mt-category-badge {
    background: #2c5282;
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.85em;
}

.mt-candidate-info {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.mt-candidate-photo {
    width: 80px;
    height: 80px;
    border-radius: 10px;
    overflow: hidden;
    flex-shrink: 0;
}

.mt-candidate-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.mt-placeholder-photo {
    background: #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
    color: #a0aec0;
}

.mt-candidate-details h3 {
    margin: 0 0 8px 0;
    font-size: 1.3em;
    color: #2c5282;
}

.mt-candidate-details p {
    margin: 5px 0;
    color: #4a5568;
}

.mt-position {
    font-weight: 600;
    color: #2d3748;
}

.mt-company {
    color: #718096;
}

.mt-location {
    font-size: 0.9em;
    color: #718096;
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Score Display */
.mt-score-display {
    background: #f7fafc;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.mt-score-circle {
    text-align: center;
    margin-bottom: 15px;
}

.mt-score-value {
    font-size: 2.5em;
    font-weight: 700;
    color: #38a169;
}

.mt-score-max {
    font-size: 1.2em;
    color: #718096;
}

.mt-score-breakdown {
    display: grid;
    gap: 8px;
}

.mt-score-item {
    display: flex;
    justify-content: space-between;
    padding: 5px 10px;
    background: white;
    border-radius: 5px;
}

.mt-score-label {
    color: #4a5568;
    font-size: 0.9em;
}

.mt-score-points {
    font-weight: 600;
    color: #2c5282;
}

.mt-evaluation-date {
    text-align: center;
    margin-top: 10px;
    font-size: 0.85em;
    color: #718096;
}

/* Card Actions */
.mt-card-actions {
    display: flex;
    gap: 10px;
    margin-top: auto;
}

.mt-card-actions .button {
    flex: 1;
    justify-content: center;
}

/* Modal Styles */
.mt-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.8);
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.mt-modal-content {
    background: white;
    border-radius: 12px;
    max-width: 700px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.mt-modal-header {
    padding: 30px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.mt-modal-header h2 {
    margin: 0;
    color: #2c5282;
}

.mt-modal-close {
    background: none;
    border: none;
    font-size: 30px;
    color: #718096;
    cursor: pointer;
    padding: 0;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.mt-modal-close:hover {
    background: #f7fafc;
    color: #2d3748;
}

.mt-modal-body {
    padding: 30px;
}

/* Candidate Preview in Modal */
.mt-candidate-preview {
    background: #f7fafc;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 30px;
    text-align: center;
}

.mt-candidate-preview h3 {
    margin: 0 0 10px 0;
    color: #2c5282;
    font-size: 1.5em;
}

.mt-candidate-preview p {
    margin: 5px 0;
    color: #4a5568;
}

/* Evaluation Criteria */
.mt-evaluation-criteria h4 {
    margin: 0 0 20px 0;
    color: #2c5282;
}

.mt-criterion {
    margin-bottom: 25px;
}

.mt-criterion label {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 10px;
}

.mt-criterion-help {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    background: #e2e8f0;
    color: #718096;
    border-radius: 50%;
    font-size: 12px;
    cursor: help;
}

.mt-score-slider {
    display: flex;
    align-items: center;
    gap: 15px;
}

.mt-slider {
    flex: 1;
    height: 8px;
    -webkit-appearance: none;
    appearance: none;
    background: #e2e8f0;
    border-radius: 4px;
    outline: none;
}

.mt-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 24px;
    height: 24px;
    background: #38b2ac;
    border-radius: 50%;
    cursor: pointer;
}

.mt-slider::-moz-range-thumb {
    width: 24px;
    height: 24px;
    background: #38b2ac;
    border-radius: 50%;
    cursor: pointer;
    border: none;
}

.mt-score-display {
    min-width: 30px;
    text-align: center;
    font-weight: 700;
    font-size: 1.2em;
    color: #2c5282;
}

/* Total Score */
.mt-total-score {
    background: #f7fafc;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    margin: 30px 0;
    border: 2px solid #38b2ac;
}

.mt-total-label {
    font-size: 1.2em;
    color: #4a5568;
    margin-right: 10px;
}

.mt-total-value {
    font-size: 2em;
    font-weight: 700;
    color: #38b2ac;
}

.mt-total-max {
    font-size: 1.2em;
    color: #718096;
}

/* Comments */
.mt-comments textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    resize: vertical;
}

/* Modal Actions */
.mt-modal-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

/* Responsive */
@media (max-width: 768px) {
    .mt-dashboard-header {
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }
    
    .mt-header-stats {
        width: 100%;
        justify-content: space-around;
    }
    
    .mt-filter-section {
        flex-direction: column;
        align-items: stretch;
    }
    
    .mt-candidates-grid {
        grid-template-columns: 1fr;
    }
    
    .mt-candidate-info {
        flex-direction: column;
        text-align: center;
    }
}

/* Loading State */
.mt-loading {
    opacity: 0.6;
    pointer-events: none;
}

.mt-loading:after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 30px;
    height: 30px;
    margin: -15px 0 0 -15px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #38b2ac;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Search functionality
    $('#mt-candidate-search').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();
        filterCandidates();
    });
    
    // Filter buttons
    $('.mt-filter-btn').on('click', function() {
        $('.mt-filter-btn').removeClass('active');
        $(this).addClass('active');
        filterCandidates();
    });
    
    // Category filter
    $('#mt-category-filter').on('change', function() {
        filterCandidates();
    });
    
    // Filter candidates function
    function filterCandidates() {
        var searchTerm = $('#mt-candidate-search').val().toLowerCase();
        var statusFilter = $('.mt-filter-btn.active').data('filter');
        var categoryFilter = $('#mt-category-filter').val();
        
        $('.mt-candidate-card').each(function() {
            var $card = $(this);
            var name = $card.find('h3').text().toLowerCase();
            var company = $card.find('.mt-company').text().toLowerCase();
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
        
        // Update counts
        updateFilterCounts();
    }
    
    // Update filter counts
    function updateFilterCounts() {
        var total = $('.mt-candidate-card').length;
        var evaluated = $('.mt-candidate-card.evaluated').length;
        var pending = $('.mt-candidate-card.pending').length;
        
        $('.mt-filter-btn[data-filter="all"]').text('All (' + total + ')');
        $('.mt-filter-btn[data-filter="evaluated"]').text('Evaluated (' + evaluated + ')');
        $('.mt-filter-btn[data-filter="pending"]').text('Pending (' + pending + ')');
    }
    
    // Evaluate button click
    $('.mt-evaluate-btn').on('click', function() {
        var candidateId = $(this).data('candidate-id');
        var $card = $(this).closest('.mt-candidate-card');
        
        // Get candidate info
        var name = $card.find('h3').text();
        var position = $card.find('.mt-position').text();
        var company = $card.find('.mt-company').text();
        
        // Set modal data
        $('#mt-candidate-id').val(candidateId);
        $('#mt-candidate-name').text(name);
        $('#mt-candidate-position').text(position);
        $('#mt-candidate-company').text(company);
        
        // Load existing scores if evaluated
        if ($card.hasClass('evaluated')) {
            loadExistingScores(candidateId);
        } else {
            resetForm();
        }
        
        // Show modal
        $('#mt-evaluation-modal').fadeIn();
    });
    
    // Load existing scores
    function loadExistingScores(candidateId) {
        // In real implementation, this would load via AJAX
        // For now, we'll just reset
        resetForm();
    }
    
    // Reset form
    function resetForm() {
        $('#mt-evaluation-form')[0].reset();
        $('.mt-slider').each(function() {
            $(this).val(0);
            $(this).siblings('.mt-score-display').text('0');
        });
        updateTotalScore();
    }
    
    // Slider change
    $('.mt-slider').on('input', function() {
        var value = $(this).val();
        $(this).siblings('.mt-score-display').text(value);
        updateTotalScore();
    });
    
    // Update total score
    function updateTotalScore() {
        var total = 0;
        $('.mt-slider').each(function() {
            total += parseInt($(this).val());
        });
        $('.mt-total-value').text(total);
    }
    
    // Close modal
    $('.mt-modal-close, .mt-modal-cancel').on('click', function() {
        $('#mt-evaluation-modal').fadeOut();
    });
    
    // Submit evaluation
    $('#mt-evaluation-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=mt_submit_vote';
        formData += '&nonce=<?php echo wp_create_nonce('mt_nonce'); ?>';
        
        // Show loading
        $(this).addClass('mt-loading');
        
        $.post('<?php echo admin_url('admin-ajax.php'); ?>', formData, function(response) {
            if (response.success) {
                // Success notification
                alert(response.data.message);
                
                // Update card status
                var candidateId = $('#mt-candidate-id').val();
                var $card = $('.mt-candidate-card[data-candidate-id="' + candidateId + '"]');
                $card.removeClass('pending').addClass('evaluated');
                $card.attr('data-status', 'evaluated');
                
                // Update status badge
                $card.find('.mt-status-badge')
                    .removeClass('pending')
                    .addClass('evaluated')
                    .html('<i class="dashicons dashicons-yes-alt"></i> Evaluated');
                
                // Update button text
                $card.find('.mt-evaluate-btn').text('Update Evaluation');
                
                // Close modal
                $('#mt-evaluation-modal').fadeOut();
                
                // Update statistics
                location.reload(); // Simple reload for now
            } else {
                alert('Error: ' + response.data.message);
            }
        }).always(function() {
            $('#mt-evaluation-form').removeClass('mt-loading');
        });
    });
    
    // Click outside modal to close
    $('#mt-evaluation-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).fadeOut();
        }
    });
});
</script>