<?php
/**
 * Jury Dashboard Frontend Template
 * File: /wp-content/plugins/mobility-trailblazers/templates/jury-dashboard-frontend.php
 * 
 * This template creates a beautiful, modern interface for jury members to evaluate candidates
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
    <div class="mt-jury-container mt-access-denied-container">
        <div class="mt-access-denied-card">
            <div class="mt-access-denied-icon">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="m15 9-6 6"></path>
                    <path d="m9 9 6 6"></path>
                </svg>
            </div>
            <h1><?php _e('Access Restricted', 'mobility-trailblazers'); ?></h1>
            <p><?php _e('This page is only accessible to jury members. If you believe you should have access, please contact the administrator.', 'mobility-trailblazers'); ?></p>
            <a href="<?php echo home_url(); ?>" class="mt-button mt-button-primary">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m12 19-7-7 7-7"></path>
                    <path d="M19 12H5"></path>
                </svg>
                <?php _e('Return to Homepage', 'mobility-trailblazers'); ?>
            </a>
        </div>
    </div>
    <?php
    get_footer();
    return;
}

$jury_member = $jury_post[0];
$jury_member_id = $jury_member->ID;

// Get assigned candidates with all metadata
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
$evaluated_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(DISTINCT candidate_id) FROM $table_scores WHERE jury_member_id = %d",
    $jury_member_id
));

$total_assigned = count($assigned_candidates);
$completion_rate = $total_assigned > 0 ? round(($evaluated_count / $total_assigned) * 100) : 0;

// Get evaluated candidate IDs
$evaluated_ids = $wpdb->get_col($wpdb->prepare(
    "SELECT DISTINCT candidate_id FROM $table_scores WHERE jury_member_id = %d",
    $jury_member_id
));

// Prepare candidates data with evaluation status
$candidates_data = array();
foreach ($assigned_candidates as $candidate) {
    $is_evaluated = in_array($candidate->ID, $evaluated_ids);
    $candidate_score = null;
    
    if ($is_evaluated) {
        $candidate_score = $wpdb->get_var($wpdb->prepare(
            "SELECT total_score 
             FROM $table_scores 
             WHERE candidate_id = %d AND jury_member_id = %d
             ORDER BY evaluation_date DESC LIMIT 1",
            $candidate->ID,
            $jury_member_id
        ));
    }
    
    $candidates_data[] = array(
        'post' => $candidate,
        'evaluated' => $is_evaluated,
        'score' => $candidate_score
    );
}

?>

<div class="mt-jury-dashboard-container">
    <!-- Hero Section -->
    <section class="mt-jury-hero">
        <div class="mt-jury-hero-background"></div>
        <div class="mt-jury-hero-content">
            <div class="mt-jury-welcome">
                <h1><?php printf(__('Welcome, %s', 'mobility-trailblazers'), esc_html($current_user->display_name)); ?></h1>
                <p class="mt-jury-subtitle"><?php _e('Mobility Trailblazers Awards - Jury Dashboard', 'mobility-trailblazers'); ?></p>
            </div>
            
            <!-- Progress Overview -->
            <div class="mt-jury-progress-card">
                <div class="mt-progress-header">
                    <h2><?php _e('Your Evaluation Progress', 'mobility-trailblazers'); ?></h2>
                    <span class="mt-progress-percentage"><?php echo $completion_rate; ?>%</span>
                </div>
                
                <div class="mt-progress-bar-container">
                    <div class="mt-progress-bar">
                        <div class="mt-progress-fill" style="width: <?php echo $completion_rate; ?>%"></div>
                    </div>
                </div>
                
                <div class="mt-progress-stats">
                    <div class="mt-stat">
                        <span class="mt-stat-number"><?php echo $evaluated_count; ?></span>
                        <span class="mt-stat-label"><?php _e('Evaluated', 'mobility-trailblazers'); ?></span>
                    </div>
                    <div class="mt-stat">
                        <span class="mt-stat-number"><?php echo $total_assigned - $evaluated_count; ?></span>
                        <span class="mt-stat-label"><?php _e('Remaining', 'mobility-trailblazers'); ?></span>
                    </div>
                    <div class="mt-stat">
                        <span class="mt-stat-number"><?php echo $total_assigned; ?></span>
                        <span class="mt-stat-label"><?php _e('Total Assigned', 'mobility-trailblazers'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Actions -->
    <section class="mt-jury-actions">
        <h2><?php _e('Quick Actions', 'mobility-trailblazers'); ?></h2>
        <div class="mt-action-buttons">
            <button class="mt-action-button" id="mt-filter-pending">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <?php _e('Show Pending Only', 'mobility-trailblazers'); ?>
            </button>
            <button class="mt-action-button" id="mt-filter-evaluated">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <?php _e('Show Evaluated', 'mobility-trailblazers'); ?>
            </button>
            <button class="mt-action-button" id="mt-filter-all" class="active">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                <?php _e('Show All', 'mobility-trailblazers'); ?>
            </button>
            <button class="mt-action-button" id="mt-export-evaluations">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                <?php _e('Export My Evaluations', 'mobility-trailblazers'); ?>
            </button>
        </div>
    </section>

    <!-- Candidates Grid -->
    <section class="mt-jury-candidates">
        <div class="mt-candidates-header">
            <h2><?php _e('Assigned Candidates', 'mobility-trailblazers'); ?></h2>
            <div class="mt-search-box">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input type="text" id="mt-candidate-search" placeholder="<?php _e('Search candidates...', 'mobility-trailblazers'); ?>">
            </div>
        </div>

        <div class="mt-candidates-grid" id="mt-candidates-grid">
            <?php foreach ($candidates_data as $candidate_data): 
                $candidate = $candidate_data['post'];
                $is_evaluated = $candidate_data['evaluated'];
                $score = $candidate_data['score'];
                
                // Get candidate metadata
                $position = get_post_meta($candidate->ID, '_mt_candidate_position', true);
                $company = get_post_meta($candidate->ID, '_mt_candidate_company', true);
                $location = get_post_meta($candidate->ID, '_mt_candidate_location', true);
                $innovation = get_post_meta($candidate->ID, '_mt_candidate_innovation', true);
                $linkedin = get_post_meta($candidate->ID, '_mt_candidate_linkedin', true);
                $photo_id = get_post_meta($candidate->ID, '_mt_candidate_photo', true);
                $photo_url = $photo_id ? wp_get_attachment_image_url($photo_id, 'medium') : '';
                
                // Get categories
                $categories = get_the_terms($candidate->ID, 'candidate_category');
                $category_class = '';
                $category_slug = '';
                if (!empty($categories)) {
                    $category_slug = $categories[0]->slug;
                    $category_class = 'category-' . $category_slug;
                }
            ?>
                <div class="mt-candidate-card <?php echo $is_evaluated ? 'evaluated' : 'pending'; ?> <?php echo esc_attr($category_class); ?>" 
                     data-candidate-id="<?php echo $candidate->ID; ?>"
                     data-candidate-name="<?php echo esc_attr($candidate->post_title); ?>"
                     data-status="<?php echo $is_evaluated ? 'evaluated' : 'pending'; ?>">
                    
                    <!-- Status Badge -->
                    <div class="mt-candidate-status">
                        <?php if ($is_evaluated): ?>
                            <span class="mt-status-badge mt-status-evaluated">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                <?php _e('Evaluated', 'mobility-trailblazers'); ?>
                            </span>
                            <?php if ($score): ?>
                                <span class="mt-score-badge"><?php echo number_format($score, 1); ?>/50</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="mt-status-badge mt-status-pending">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <?php _e('Pending', 'mobility-trailblazers'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Candidate Photo -->
                    <div class="mt-candidate-photo">
                        <?php if ($photo_url): ?>
                            <img src="<?php echo esc_url($photo_url); ?>" alt="<?php echo esc_attr($candidate->post_title); ?>">
                        <?php else: ?>
                            <div class="mt-photo-placeholder">
                                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Candidate Info -->
                    <div class="mt-candidate-info">
                        <h3><?php echo esc_html($candidate->post_title); ?></h3>
                        <?php if ($position): ?>
                            <p class="mt-candidate-position"><?php echo esc_html($position); ?></p>
                        <?php endif; ?>
                        <?php if ($company): ?>
                            <p class="mt-candidate-company"><?php echo esc_html($company); ?></p>
                        <?php endif; ?>
                        <?php if ($location): ?>
                            <p class="mt-candidate-location">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <?php echo esc_html($location); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Category -->
                    <?php if (!empty($categories)): ?>
                        <div class="mt-candidate-category">
                            <span class="mt-category-badge"><?php echo esc_html($categories[0]->name); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Innovation Preview -->
                    <?php if ($innovation): ?>
                        <div class="mt-candidate-innovation">
                            <p><?php echo wp_trim_words(esc_html($innovation), 20, '...'); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Action Button -->
                    <div class="mt-candidate-actions">
                        <button class="mt-evaluate-button" data-candidate-id="<?php echo $candidate->ID; ?>">
                            <?php if ($is_evaluated): ?>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                                <?php _e('Edit Evaluation', 'mobility-trailblazers'); ?>
                            <?php else: ?>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="12" y1="18" x2="12" y2="12"></line>
                                    <line x1="9" y1="15" x2="15" y2="15"></line>
                                </svg>
                                <?php _e('Evaluate Now', 'mobility-trailblazers'); ?>
                            <?php endif; ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($assigned_candidates)): ?>
            <div class="mt-no-candidates">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="12" y1="18" x2="12" y2="12"></line>
                    <line x1="9" y1="15" x2="15" y2="15"></line>
                </svg>
                <h3><?php _e('No Candidates Assigned', 'mobility-trailblazers'); ?></h3>
                <p><?php _e('You currently have no candidates assigned for evaluation. Please contact the administrator.', 'mobility-trailblazers'); ?></p>
            </div>
        <?php endif; ?>
    </section>
</div>

<!-- Evaluation Modal -->
<div class="mt-evaluation-modal" id="mt-evaluation-modal">
    <div class="mt-modal-backdrop"></div>
    <div class="mt-modal-container">
        <div class="mt-modal-content">
            <button class="mt-modal-close" id="mt-close-modal">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            
            <div class="mt-modal-header">
                <h2><?php _e('Candidate Evaluation', 'mobility-trailblazers'); ?></h2>
                <p class="mt-modal-subtitle"><?php _e('Please rate the candidate on each of the five criteria below', 'mobility-trailblazers'); ?></p>
            </div>
            
            <form id="mt-evaluation-form" class="mt-evaluation-form">
                <input type="hidden" id="mt-candidate-id" name="candidate_id" value="">
                                        <?php wp_nonce_field('mt_jury_dashboard', 'nonce'); ?>
                
                <!-- Candidate Info Display -->
                <div class="mt-modal-candidate-info" id="mt-modal-candidate-info">
                    <!-- Populated by JavaScript -->
                </div>
                
                <!-- Evaluation Criteria -->
                <div class="mt-evaluation-criteria">
                    <!-- Courage -->
                    <div class="mt-criterion-block">
                        <div class="mt-criterion-header">
                            <label for="mt-courage">
                                <?php _e('Mut & Pioniergeist', 'mobility-trailblazers'); ?>
                                <span class="mt-label-en">(Courage & Pioneering Spirit)</span>
                            </label>
                            <span class="mt-score-value-display" id="mt-courage-display">5</span>
                        </div>
                        <p class="mt-criterion-description">
                            <?php _e('Willingness to challenge the status quo and explore new paths in mobility', 'mobility-trailblazers'); ?>
                        </p>
                        <div class="mt-slider-container">
                            <input type="range" id="mt-courage" name="courage" min="0" max="10" step="0.5" value="5" class="mt-score-slider">
                            <div class="mt-slider-labels">
                                <span>0</span>
                                <span>5</span>
                                <span>10</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Innovation -->
                    <div class="mt-criterion-block">
                        <div class="mt-criterion-header">
                            <label for="mt-innovation">
                                <?php _e('Innovationsgrad', 'mobility-trailblazers'); ?>
                                <span class="mt-label-en">(Degree of Innovation)</span>
                            </label>
                            <span class="mt-score-value-display" id="mt-innovation-display">5</span>
                        </div>
                        <p class="mt-criterion-description">
                            <?php _e('Originality and creativity of the solution or approach', 'mobility-trailblazers'); ?>
                        </p>
                        <div class="mt-slider-container">
                            <input type="range" id="mt-innovation" name="innovation" min="0" max="10" step="0.5" value="5" class="mt-score-slider">
                            <div class="mt-slider-labels">
                                <span>0</span>
                                <span>5</span>
                                <span>10</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Implementation -->
                    <div class="mt-criterion-block">
                        <div class="mt-criterion-header">
                            <label for="mt-implementation">
                                <?php _e('Umsetzung', 'mobility-trailblazers'); ?>
                                <span class="mt-label-en">(Implementation)</span>
                            </label>
                            <span class="mt-score-value-display" id="mt-implementation-display">5</span>
                        </div>
                        <p class="mt-criterion-description">
                            <?php _e('Quality of execution and practical viability of the initiative', 'mobility-trailblazers'); ?>
                        </p>
                        <div class="mt-slider-container">
                            <input type="range" id="mt-implementation" name="implementation" min="0" max="10" step="0.5" value="5" class="mt-score-slider">
                            <div class="mt-slider-labels">
                                <span>0</span>
                                <span>5</span>
                                <span>10</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Relevance -->
                    <div class="mt-criterion-block">
                        <div class="mt-criterion-header">
                            <label for="mt-relevance">
                                <?php _e('Relevanz', 'mobility-trailblazers'); ?>
                                <span class="mt-label-en">(Relevance)</span>
                            </label>
                            <span class="mt-score-value-display" id="mt-relevance-display">5</span>
                        </div>
                        <p class="mt-criterion-description">
                            <?php _e('Impact and significance for the future of mobility', 'mobility-trailblazers'); ?>
                        </p>
                        <div class="mt-slider-container">
                            <input type="range" id="mt-relevance" name="relevance" min="0" max="10" step="0.5" value="5" class="mt-score-slider">
                            <div class="mt-slider-labels">
                                <span>0</span>
                                <span>5</span>
                                <span>10</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Visibility -->
                    <div class="mt-criterion-block">
                        <div class="mt-criterion-header">
                            <label for="mt-visibility">
                                <?php _e('Sichtbarkeit', 'mobility-trailblazers'); ?>
                                <span class="mt-label-en">(Visibility)</span>
                            </label>
                            <span class="mt-score-value-display" id="mt-visibility-display">5</span>
                        </div>
                        <p class="mt-criterion-description">
                            <?php _e('Public awareness and communication of the initiative', 'mobility-trailblazers'); ?>
                        </p>
                        <div class="mt-slider-container">
                            <input type="range" id="mt-visibility" name="visibility" min="0" max="10" step="0.5" value="5" class="mt-score-slider">
                            <div class="mt-slider-labels">
                                <span>0</span>
                                <span>5</span>
                                <span>10</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Total Score -->
                <div class="mt-total-score-display">
                    <div class="mt-total-label"><?php _e('Total Score', 'mobility-trailblazers'); ?></div>
                    <div class="mt-total-value" id="mt-total-score">25</div>
                    <div class="mt-total-max">/ 50</div>
                </div>
                
                <!-- Optional Comments -->
                <div class="mt-comments-section">
                    <label for="mt-comments"><?php _e('Additional Comments (Optional)', 'mobility-trailblazers'); ?></label>
                    <textarea id="mt-comments" name="comments" rows="4" placeholder="<?php _e('Share any additional thoughts about this candidate...', 'mobility-trailblazers'); ?>"></textarea>
                </div>
                
                <!-- Form Actions -->
                <div class="mt-form-actions">
                    <button type="button" class="mt-button mt-button-secondary" id="mt-save-draft">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        <?php _e('Save as Draft', 'mobility-trailblazers'); ?>
                    </button>
                    <button type="submit" class="mt-button mt-button-primary" id="mt-submit-evaluation">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <?php _e('Submit Evaluation', 'mobility-trailblazers'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Notification Container -->
<div class="mt-notification-container" id="mt-notification-container"></div>

<!-- Inline CSS for the jury dashboard -->
<style>
/* CSS Variables */
:root {
    --mt-primary: #2c5282;
    --mt-primary-light: #4a90e2;
    --mt-primary-dark: #1a365d;
    --mt-accent: #38b2ac;
    --mt-accent-light: #4fd1c5;
    --mt-success: #48bb78;
    --mt-warning: #ed8936;
    --mt-danger: #f56565;
    --mt-gray-100: #f7fafc;
    --mt-gray-200: #edf2f7;
    --mt-gray-300: #e2e8f0;
    --mt-gray-400: #cbd5e0;
    --mt-gray-500: #a0aec0;
    --mt-gray-600: #718096;
    --mt-gray-700: #4a5568;
    --mt-gray-800: #2d3748;
    --mt-gray-900: #1a202c;
    --mt-shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --mt-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --mt-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    --mt-shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

/* Reset and Base Styles */
.mt-jury-dashboard-container * {
    box-sizing: border-box;
}

.mt-jury-dashboard-container {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    color: var(--mt-gray-800);
    line-height: 1.6;
    background: var(--mt-gray-100);
    min-height: 100vh;
}

/* Access Denied Styles */
.mt-access-denied-container {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
    padding: 40px 20px;
}

.mt-access-denied-card {
    background: white;
    border-radius: 20px;
    padding: 60px;
    text-align: center;
    max-width: 500px;
    box-shadow: var(--mt-shadow-xl);
}

.mt-access-denied-icon {
    color: var(--mt-danger);
    margin-bottom: 30px;
}

.mt-access-denied-card h1 {
    color: var(--mt-gray-900);
    margin: 0 0 15px 0;
    font-size: 2rem;
}

.mt-access-denied-card p {
    color: var(--mt-gray-600);
    margin: 0 0 30px 0;
}

/* Button Styles */
.mt-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

.mt-button-primary {
    background: var(--mt-primary);
    color: white;
}

.mt-button-primary:hover {
    background: var(--mt-primary-dark);
    transform: translateY(-2px);
    box-shadow: var(--mt-shadow-lg);
}

.mt-button-secondary {
    background: var(--mt-gray-200);
    color: var(--mt-gray-700);
}

.mt-button-secondary:hover {
    background: var(--mt-gray-300);
}

/* Hero Section */
.mt-jury-hero {
    position: relative;
    padding: 80px 0 60px;
    overflow: hidden;
}

.mt-jury-hero-background {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, var(--mt-primary) 0%, var(--mt-accent) 100%);
    opacity: 0.9;
    z-index: -1;
}

.mt-jury-hero-background::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: 
        radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255,255,255,0.1) 0%, transparent 50%);
}

.mt-jury-hero-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    position: relative;
    z-index: 1;
}

.mt-jury-welcome h1 {
    color: white;
    font-size: 2.5rem;
    margin: 0 0 10px 0;
}

.mt-jury-subtitle {
    color: rgba(255,255,255,0.9);
    font-size: 1.2rem;
    margin: 0 0 40px 0;
}

/* Progress Card */
.mt-jury-progress-card {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: var(--mt-shadow-xl);
}

.mt-progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.mt-progress-header h2 {
    color: var(--mt-gray-900);
    margin: 0;
    font-size: 1.5rem;
}

.mt-progress-percentage {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--mt-accent);
}

.mt-progress-bar-container {
    margin-bottom: 30px;
}

.mt-progress-bar {
    background: var(--mt-gray-200);
    height: 20px;
    border-radius: 10px;
    overflow: hidden;
}

.mt-progress-fill {
    background: linear-gradient(to right, var(--mt-accent), var(--mt-accent-light));
    height: 100%;
    border-radius: 10px;
    transition: width 1s ease;
    position: relative;
}

.mt-progress-fill::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg,
        rgba(255,255,255,0.1) 25%,
        rgba(255,255,255,0.2) 25%,
        rgba(255,255,255,0.2) 50%,
        rgba(255,255,255,0.1) 50%,
        rgba(255,255,255,0.1) 75%,
        rgba(255,255,255,0.2) 75%,
        rgba(255,255,255,0.2)
    );
    background-size: 30px 30px;
    animation: progress-animation 1s linear infinite;
}

@keyframes progress-animation {
    0% { background-position: 0 0; }
    100% { background-position: 30px 30px; }
}

.mt-progress-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.mt-stat {
    text-align: center;
}

.mt-stat-number {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    color: var(--mt-primary);
}

.mt-stat-label {
    display: block;
    color: var(--mt-gray-600);
    font-size: 0.9rem;
}

/* Quick Actions Section */
.mt-jury-actions {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.mt-jury-actions h2 {
    color: var(--mt-gray-900);
    margin: 0 0 20px 0;
}

.mt-action-buttons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.mt-action-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: white;
    border: 2px solid var(--mt-gray-300);
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 500;
    color: var(--mt-gray-700);
    cursor: pointer;
    transition: all 0.3s ease;
}

.mt-action-button:hover {
    border-color: var(--mt-accent);
    color: var(--mt-accent);
    transform: translateY(-2px);
    box-shadow: var(--mt-shadow);
}

.mt-action-button.active {
    background: var(--mt-accent);
    border-color: var(--mt-accent);
    color: white;
}

/* Candidates Section */
.mt-jury-candidates {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.mt-candidates-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 20px;
}

.mt-candidates-header h2 {
    color: var(--mt-gray-900);
    margin: 0;
}

.mt-search-box {
    position: relative;
    width: 300px;
}

.mt-search-box svg {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--mt-gray-500);
}

.mt-search-box input {
    width: 100%;
    padding: 12px 20px 12px 45px;
    border: 2px solid var(--mt-gray-300);
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.mt-search-box input:focus {
    outline: none;
    border-color: var(--mt-accent);
    box-shadow: 0 0 0 3px rgba(56, 178, 172, 0.1);
}

/* Candidates Grid */
.mt-candidates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 25px;
}

.mt-candidate-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: var(--mt-shadow);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.mt-candidate-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--mt-gray-300);
}

.mt-candidate-card.pending::before {
    background: var(--mt-warning);
}

.mt-candidate-card.evaluated::before {
    background: var(--mt-success);
}

.mt-candidate-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--mt-shadow-xl);
}

/* Status Badge */
.mt-candidate-status {
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
    font-size: 0.85rem;
    font-weight: 500;
}

.mt-status-pending {
    background: rgba(237, 137, 54, 0.1);
    color: var(--mt-warning);
}

.mt-status-evaluated {
    background: rgba(72, 187, 120, 0.1);
    color: var(--mt-success);
}

.mt-score-badge {
    font-weight: 700;
    color: var(--mt-accent);
}

/* Candidate Photo */
.mt-candidate-photo {
    width: 100px;
    height: 100px;
    margin: 0 auto 20px;
    border-radius: 50%;
    overflow: hidden;
    background: var(--mt-gray-100);
}

.mt-candidate-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.mt-photo-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--mt-gray-400);
}

/* Candidate Info */
.mt-candidate-info {
    text-align: center;
    margin-bottom: 20px;
}

.mt-candidate-info h3 {
    color: var(--mt-gray-900);
    margin: 0 0 8px 0;
    font-size: 1.3rem;
}

.mt-candidate-position {
    color: var(--mt-gray-700);
    margin: 0 0 5px 0;
    font-weight: 500;
}

.mt-candidate-company {
    color: var(--mt-gray-600);
    margin: 0 0 5px 0;
}

.mt-candidate-location {
    color: var(--mt-gray-500);
    margin: 0;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

/* Category Badge */
.mt-candidate-category {
    text-align: center;
    margin-bottom: 15px;
}

.mt-category-badge {
    display: inline-block;
    padding: 5px 15px;
    background: var(--mt-primary-light);
    color: white;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

/* Innovation Preview */
.mt-candidate-innovation {
    background: var(--mt-gray-100);
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.mt-candidate-innovation p {
    margin: 0;
    color: var(--mt-gray-700);
    font-size: 0.95rem;
    line-height: 1.5;
}

/* Evaluate Button */
.mt-evaluate-button {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 20px;
    background: var(--mt-accent);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.mt-evaluate-button:hover {
    background: var(--mt-accent-light);
    transform: translateY(-2px);
    box-shadow: var(--mt-shadow);
}

/* No Candidates */
.mt-no-candidates {
    text-align: center;
    padding: 80px 20px;
    color: var(--mt-gray-500);
}

.mt-no-candidates h3 {
    color: var(--mt-gray-700);
    margin: 20px 0 10px 0;
}

/* Evaluation Modal */
.mt-evaluation-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
}

.mt-evaluation-modal.show {
    display: block;
}

.mt-modal-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(5px);
}

.mt-modal-container {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    overflow-y: auto;
    padding: 40px 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mt-modal-content {
    background: white;
    border-radius: 20px;
    width: 100%;
    max-width: 900px;
    box-shadow: var(--mt-shadow-xl);
    position: relative;
    animation: modal-enter 0.3s ease;
}

@keyframes modal-enter {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.mt-modal-close {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 40px;
    height: 40px;
    border: none;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: var(--mt-shadow);
    transition: all 0.3s ease;
    z-index: 10;
}

.mt-modal-close:hover {
    background: var(--mt-gray-100);
    transform: rotate(90deg);
}

.mt-modal-header {
    padding: 40px 40px 20px;
    border-bottom: 1px solid var(--mt-gray-200);
}

.mt-modal-header h2 {
    margin: 0 0 10px 0;
    color: var(--mt-primary);
    font-size: 2rem;
}

.mt-modal-subtitle {
    color: var(--mt-gray-600);
    margin: 0;
}

/* Evaluation Form */
.mt-evaluation-form {
    padding: 40px;
}

.mt-modal-candidate-info {
    background: var(--mt-gray-100);
    padding: 25px;
    border-radius: 15px;
    margin-bottom: 40px;
    text-align: center;
}

.mt-modal-candidate-info h3 {
    margin: 0 0 10px 0;
    color: var(--mt-primary);
    font-size: 1.8rem;
}

.mt-modal-candidate-info p {
    margin: 5px 0;
    color: var(--mt-gray-600);
}

/* Criteria Blocks */
.mt-criterion-block {
    margin-bottom: 35px;
    padding: 25px;
    background: #fafafa;
    border-radius: 15px;
    border: 1px solid var(--mt-gray-200);
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
    color: var(--mt-primary);
}

.mt-label-en {
    font-size: 0.9rem;
    color: var(--mt-gray-500);
    font-weight: 400;
}

.mt-score-value-display {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--mt-accent);
}

.mt-criterion-description {
    color: var(--mt-gray-600);
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
    background: var(--mt-gray-300);
    border-radius: 4px;
    outline: none;
    margin: 20px 0;
}

.mt-score-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 28px;
    height: 28px;
    background: var(--mt-accent);
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
}

.mt-score-slider::-moz-range-thumb {
    width: 28px;
    height: 28px;
    background: var(--mt-accent);
    border-radius: 50%;
    cursor: pointer;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
}

.mt-score-slider:hover::-webkit-slider-thumb {
    transform: scale(1.2);
}

.mt-score-slider:hover::-moz-range-thumb {
    transform: scale(1.2);
}

.mt-slider-labels {
    display: flex;
    justify-content: space-between;
    margin-top: -10px;
    font-size: 0.85rem;
    color: var(--mt-gray-500);
}

/* Total Score Display */
.mt-total-score-display {
    background: linear-gradient(135deg, var(--mt-primary) 0%, var(--mt-accent) 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
    text-align: center;
    margin: 40px 0;
    position: relative;
    overflow: hidden;
}

.mt-total-score-display::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: pulse 3s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

.mt-total-label {
    font-size: 1.2rem;
    margin-bottom: 10px;
}

.mt-total-value {
    font-size: 3.5rem;
    font-weight: 700;
}

.mt-total-max {
    font-size: 1.5rem;
    opacity: 0.8;
}

/* Comments Section */
.mt-comments-section {
    margin-bottom: 30px;
}

.mt-comments-section label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
    color: var(--mt-gray-700);
}

.mt-comments-section textarea {
    width: 100%;
    padding: 15px;
    border: 2px solid var(--mt-gray-300);
    border-radius: 10px;
    font-size: 1rem;
    resize: vertical;
    transition: all 0.3s ease;
}

.mt-comments-section textarea:focus {
    outline: none;
    border-color: var(--mt-accent);
    box-shadow: 0 0 0 3px rgba(56, 178, 172, 0.1);
}

/* Form Actions */
.mt-form-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
}

/* Notification Container */
.mt-notification-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 10000;
}

.mt-notification {
    background: white;
    padding: 15px 20px;
    border-radius: 10px;
    box-shadow: var(--mt-shadow-xl);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: slide-in 0.3s ease;
}

@keyframes slide-in {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.mt-notification.success {
    border-left: 4px solid var(--mt-success);
}

.mt-notification.error {
    border-left: 4px solid var(--mt-danger);
}

.mt-notification.info {
    border-left: 4px solid var(--mt-primary);
}

/* Responsive Design */
@media (max-width: 768px) {
    .mt-jury-hero-content {
        padding: 0 15px;
    }
    
    .mt-jury-welcome h1 {
        font-size: 2rem;
    }
    
    .mt-jury-progress-card {
        padding: 30px 20px;
    }
    
    .mt-progress-stats {
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
    }
    
    .mt-candidates-grid {
        grid-template-columns: 1fr;
    }
    
    .mt-search-box {
        width: 100%;
    }
    
    .mt-modal-content {
        margin: 20px;
    }
    
    .mt-evaluation-form {
        padding: 20px;
    }
    
    .mt-form-actions {
        flex-direction: column;
    }
    
    .mt-form-actions .mt-button {
        width: 100%;
    }
}
</style>

<!-- Inline JavaScript for the jury dashboard -->
<script>
jQuery(document).ready(function($) {
    'use strict';
    
    // State management
    const state = {
        currentFilter: 'all',
        currentCandidateId: null,
        evaluationData: {}
    };
    
    // Filter functionality
    $('.mt-action-button').on('click', function() {
        const $button = $(this);
        const filterId = $button.attr('id');
        
        // Update active state
        $('.mt-action-button').removeClass('active');
        $button.addClass('active');
        
        // Filter candidates
        if (filterId === 'mt-filter-pending') {
            $('.mt-candidate-card').hide();
            $('.mt-candidate-card.pending').show();
        } else if (filterId === 'mt-filter-evaluated') {
            $('.mt-candidate-card').hide();
            $('.mt-candidate-card.evaluated').show();
        } else if (filterId === 'mt-filter-all') {
            $('.mt-candidate-card').show();
        }
        
        // Animate visible cards
        $('.mt-candidate-card:visible').each(function(index) {
            $(this).css({
                'animation': 'none',
                'opacity': '0',
                'transform': 'translateY(20px)'
            });
            
            setTimeout(() => {
                $(this).css({
                    'animation': 'fadeInUp 0.5s ease forwards',
                    'opacity': '1',
                    'transform': 'translateY(0)'
                });
            }, index * 50);
        });
    });
    
    // Search functionality
    $('#mt-candidate-search').on('keyup', debounce(function() {
        const searchTerm = $(this).val().toLowerCase();
        
        $('.mt-candidate-card').each(function() {
            const $card = $(this);
            const candidateName = $card.data('candidate-name').toLowerCase();
            
            if (candidateName.includes(searchTerm)) {
                $card.show();
            } else {
                $card.hide();
            }
        });
    }, 300));
    
    // Evaluation button click
    $('.mt-evaluate-button').on('click', function() {
        const candidateId = $(this).data('candidate-id');
        openEvaluationModal(candidateId);
    });
    
    // Modal close
    $('#mt-close-modal, .mt-modal-backdrop').on('click', function() {
        closeEvaluationModal();
    });
    
    // Prevent modal close when clicking inside content
    $('.mt-modal-content').on('click', function(e) {
        e.stopPropagation();
    });
    
    // Slider updates
    $('.mt-score-slider').on('input', function() {
        const $slider = $(this);
        const value = parseFloat($slider.val());
        const displayId = $slider.attr('id') + '-display';
        
        // Update display value
        $('#' + displayId).text(value);
        
        // Update slider fill
        const percentage = (value / 10) * 100;
        $slider.css('background', `linear-gradient(to right, var(--mt-accent) 0%, var(--mt-accent) ${percentage}%, var(--mt-gray-300) ${percentage}%, var(--mt-gray-300) 100%)`);
        
        // Update total score
        updateTotalScore();
    });
    
    // Form submission
    $('#mt-evaluation-form').on('submit', function(e) {
        e.preventDefault();
        submitEvaluation();
    });
    
    // Save draft
    $('#mt-save-draft').on('click', function() {
        saveDraft();
    });
    
    // Export evaluations
    $('#mt-export-evaluations').on('click', function() {
        exportEvaluations();
    });
    
    // Functions
    function openEvaluationModal(candidateId) {
        state.currentCandidateId = candidateId;
        
        // Find candidate card
        const $card = $(`.mt-candidate-card[data-candidate-id="${candidateId}"]`);
        const candidateName = $card.find('h3').text();
        const position = $card.find('.mt-candidate-position').text();
        const company = $card.find('.mt-candidate-company').text();
        
        // Update modal with candidate info
        $('#mt-candidate-id').val(candidateId);
        $('#mt-modal-candidate-info').html(`
            <h3>${candidateName}</h3>
            ${position ? `<p>${position}</p>` : ''}
            ${company ? `<p>${company}</p>` : ''}
        `);
        
        // Load existing evaluation if any
        loadExistingEvaluation(candidateId);
        
        // Show modal
        $('#mt-evaluation-modal').addClass('show');
        $('body').css('overflow', 'hidden');
    }
    
    function closeEvaluationModal() {
        $('#mt-evaluation-modal').removeClass('show');
        $('body').css('overflow', '');
        
        // Reset form
        setTimeout(() => {
            $('#mt-evaluation-form')[0].reset();
            $('.mt-score-slider').val(5).trigger('input');
        }, 300);
    }
    
    function updateTotalScore() {
        let total = 0;
        $('.mt-score-slider').each(function() {
            total += parseFloat($(this).val());
        });
        
        $('#mt-total-score').text(total.toFixed(1));
        
        // Add animation
        $('#mt-total-score').addClass('score-update');
        setTimeout(() => $('#mt-total-score').removeClass('score-update'), 300);
    }
    
    function loadExistingEvaluation(candidateId) {
        // In a real implementation, this would fetch from the server
        // For now, we'll check if the candidate is marked as evaluated
        const $card = $(`.mt-candidate-card[data-candidate-id="${candidateId}"]`);
        
        if ($card.hasClass('evaluated')) {
            // Simulate loading existing scores
            showNotification('Loading existing evaluation...', 'info');
            
            // You would normally load actual scores here
            // For demo, we'll use random values
            setTimeout(() => {
                $('.mt-score-slider').each(function() {
                    const randomScore = (Math.random() * 5 + 5).toFixed(1);
                    $(this).val(randomScore).trigger('input');
                });
                showNotification('Existing evaluation loaded', 'success');
            }, 500);
        }
    }
    
    function submitEvaluation() {
        const $form = $('#mt-evaluation-form');
        const $submitBtn = $('#mt-submit-evaluation');
        
        // Disable button and show loading
        $submitBtn.prop('disabled', true).html(`
            <svg class="mt-spinner" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" opacity="0.3"></circle>
                <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
            </svg>
            <?php _e('Submitting...', 'mobility-trailblazers'); ?>
        `);
        
        // Prepare data
        const formData = new FormData($form[0]);
        formData.append('action', 'mt_submit_evaluation');
        
        // AJAX submission
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showNotification('Evaluation submitted successfully!', 'success');
                    
                    // Update candidate card
                    const $card = $(`.mt-candidate-card[data-candidate-id="${state.currentCandidateId}"]`);
                    $card.removeClass('pending').addClass('evaluated');
                    $card.find('.mt-status-badge').removeClass('mt-status-pending').addClass('mt-status-evaluated')
                        .html(`
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <?php _e('Evaluated', 'mobility-trailblazers'); ?>
                        `);
                    
                    // Update progress
                    updateProgress();
                    
                    // Close modal
                    setTimeout(() => closeEvaluationModal(), 1000);
                } else {
                    showNotification(response.data || 'Error submitting evaluation', 'error');
                }
            },
            error: function() {
                showNotification('Network error. Please try again.', 'error');
            },
            complete: function() {
                // Re-enable button
                $submitBtn.prop('disabled', false).html(`
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <?php _e('Submit Evaluation', 'mobility-trailblazers'); ?>
                `);
            }
        });
    }
    
    function saveDraft() {
        const $saveBtn = $('#mt-save-draft');
        
        // Show saving state
        $saveBtn.prop('disabled', true).html(`
            <svg class="mt-spinner" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" opacity="0.3"></circle>
                <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
            </svg>
            <?php _e('Saving...', 'mobility-trailblazers'); ?>
        `);
        
        // Simulate save
        setTimeout(() => {
            showNotification('Draft saved successfully!', 'success');
            
            $saveBtn.prop('disabled', false).html(`
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <?php _e('Save as Draft', 'mobility-trailblazers'); ?>
            `);
        }, 1000);
    }
    
    function updateProgress() {
        // Recalculate progress
        const totalCards = $('.mt-candidate-card').length;
        const evaluatedCards = $('.mt-candidate-card.evaluated').length;
        const percentage = totalCards > 0 ? Math.round((evaluatedCards / totalCards) * 100) : 0;
        
        // Update UI
        $('.mt-progress-percentage').text(percentage + '%');
        $('.mt-progress-fill').css('width', percentage + '%');
        $('.mt-stat-number').eq(0).text(evaluatedCards);
        $('.mt-stat-number').eq(1).text(totalCards - evaluatedCards);
        
        // Add celebration animation if complete
        if (percentage === 100) {
            $('.mt-jury-progress-card').addClass('complete');
            showNotification('Congratulations! You have completed all evaluations!', 'success');
        }
    }
    
    function exportEvaluations() {
        showNotification('Preparing export...', 'info');
        
        // Simulate export
        setTimeout(() => {
            showNotification('Export ready! Download will start shortly.', 'success');
            
            // In real implementation, trigger download
            // window.location.href = 'export-url';
        }, 1500);
    }
    
    function showNotification(message, type = 'info') {
        const icons = {
            success: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
            error: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
            info: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
        };
        
        const $notification = $(`
            <div class="mt-notification ${type}">
                ${icons[type]}
                <span>${message}</span>
            </div>
        `);
        
        $('#mt-notification-container').append($notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Debounce helper
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Add CSS animation for score update
    const style = document.createElement('style');
    style.textContent = `
        @keyframes scoreUpdate {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        .score-update {
            animation: scoreUpdate 0.3s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .mt-spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            100% { transform: rotate(360deg); }
        }
        
        .mt-jury-progress-card.complete {
            animation: celebrate 0.5s ease;
        }
        
        @keyframes celebrate {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
    `;
    document.head.appendChild(style);
    
    // Initialize sliders with gradient
    $('.mt-score-slider').each(function() {
        $(this).trigger('input');
    });
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#mt-evaluation-modal').hasClass('show')) {
            closeEvaluationModal();
        }
    });
});
</script>

<?php get_footer(); ?>