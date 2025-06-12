<?php
if (!defined('ABSPATH')) {
    exit;
}

$candidate_id = isset($_GET['candidate_id']) ? intval($_GET['candidate_id']) : 0;
if (!$candidate_id) {
    wp_die(__('Invalid candidate ID.', 'mobility-trailblazers'));
}

$candidate = get_post($candidate_id);
if (!$candidate || $candidate->post_type !== 'mt_candidate') {
    wp_die(__('Candidate not found.', 'mobility-trailblazers'));
}

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
    wp_die(__('Jury member profile not found.', 'mobility-trailblazers'));
}

$jury_member_id = $jury_post[0]->ID;

// Check if candidate is assigned to this jury member
$assigned_jury = get_post_meta($candidate_id, '_mt_assigned_jury_member', true);
if ($assigned_jury != $jury_member_id) {
    wp_die(__('You are not assigned to evaluate this candidate.', 'mobility-trailblazers'));
}

// Get existing evaluation if any
global $wpdb;
$table_scores = $wpdb->prefix . 'mt_candidate_scores';
$existing_score = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table_scores WHERE candidate_id = %d AND jury_member_id = %d",
    $candidate_id,
    $current_user_id
));

// Get candidate details
$company = get_post_meta($candidate_id, '_mt_company', true);
$position = get_post_meta($candidate_id, '_mt_position', true);
$location = get_post_meta($candidate_id, '_mt_location', true);
$description = get_post_meta($candidate_id, '_mt_description', true);
$categories = wp_get_post_terms($candidate_id, 'mt_category');
?>

<div class="mt-jury-evaluation-frontend">
    <div class="mt-evaluation-header">
        <h1><?php _e('Evaluate Candidate', 'mobility-trailblazers'); ?></h1>
        <a href="<?php echo home_url('/jury/dashboard'); ?>" class="button">
            <i class="dashicons dashicons-arrow-left-alt"></i> <?php _e('Back to Dashboard', 'mobility-trailblazers'); ?>
        </a>
    </div>
    
    <div class="mt-candidate-profile">
        <div class="mt-profile-header">
            <?php if (has_post_thumbnail($candidate_id)): ?>
                <div class="mt-profile-image">
                    <?php echo get_the_post_thumbnail($candidate_id, 'medium'); ?>
                </div>
            <?php endif; ?>
            
            <div class="mt-profile-info">
                <h2><?php echo esc_html($candidate->post_title); ?></h2>
                <?php if ($position): ?>
                    <p class="mt-position"><?php echo esc_html($position); ?></p>
                <?php endif; ?>
                <?php if ($company): ?>
                    <p class="mt-company"><?php echo esc_html($company); ?></p>
                <?php endif; ?>
                <?php if ($location): ?>
                    <p class="mt-location">
                        <i class="dashicons dashicons-location"></i>
                        <?php echo esc_html($location); ?>
                    </p>
                <?php endif; ?>
                <?php if (!empty($categories)): ?>
                    <div class="mt-categories">
                        <?php foreach ($categories as $category): ?>
                            <span class="mt-category-badge"><?php echo esc_html($category->name); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($description): ?>
            <div class="mt-description">
                <h3><?php _e('Description', 'mobility-trailblazers'); ?></h3>
                <?php echo wpautop(esc_html($description)); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <form id="mt-evaluation-form" class="mt-evaluation-form">
        <input type="hidden" name="candidate_id" value="<?php echo $candidate_id; ?>">
        <input type="hidden" name="action" value="mt_submit_vote">
        <?php wp_nonce_field('mt_nonce', 'mt_nonce'); ?>
        
        <div class="mt-evaluation-criteria">
            <h3><?php _e('Evaluation Criteria', 'mobility-trailblazers'); ?></h3>
            
            <div class="mt-criterion">
                <label for="courage_score">
                    <?php _e('Mut & Pioniergeist (Courage & Pioneer Spirit)', 'mobility-trailblazers'); ?>
                    <span class="mt-criterion-help" title="<?php _e('Did they act against resistance? Were there new paths? Personal risk?', 'mobility-trailblazers'); ?>">?</span>
                </label>
                <div class="mt-score-slider">
                    <input type="range" id="courage_score" name="courage_score" min="0" max="10" 
                           value="<?php echo $existing_score ? $existing_score->courage_score : 0; ?>" 
                           class="mt-slider">
                    <span class="mt-score-display"><?php echo $existing_score ? $existing_score->courage_score : 0; ?></span>
                </div>
            </div>
            
            <div class="mt-criterion">
                <label for="innovation_score">
                    <?php _e('Innovationsgrad (Innovation Degree)', 'mobility-trailblazers'); ?>
                    <span class="mt-criterion-help" title="<?php _e('To what extent does the contribution represent a real innovation (technology, business model)?', 'mobility-trailblazers'); ?>">?</span>
                </label>
                <div class="mt-score-slider">
                    <input type="range" id="innovation_score" name="innovation_score" min="0" max="10" 
                           value="<?php echo $existing_score ? $existing_score->innovation_score : 0; ?>" 
                           class="mt-slider">
                    <span class="mt-score-display"><?php echo $existing_score ? $existing_score->innovation_score : 0; ?></span>
                </div>
            </div>
            
            <div class="mt-criterion">
                <label for="implementation_score">
                    <?php _e('Umsetzungskraft & Wirkung (Implementation & Impact)', 'mobility-trailblazers'); ?>
                    <span class="mt-criterion-help" title="<?php _e('What results were achieved (e.g., scaling, impact)?', 'mobility-trailblazers'); ?>">?</span>
                </label>
                <div class="mt-score-slider">
                    <input type="range" id="implementation_score" name="implementation_score" min="0" max="10" 
                           value="<?php echo $existing_score ? $existing_score->implementation_score : 0; ?>" 
                           class="mt-slider">
                    <span class="mt-score-display"><?php echo $existing_score ? $existing_score->implementation_score : 0; ?></span>
                </div>
            </div>
            
            <div class="mt-criterion">
                <label for="mobility_relevance_score">
                    <?php _e('Relevanz für Mobilitätswende (Mobility Transformation Relevance)', 'mobility-trailblazers'); ?>
                    <span class="mt-criterion-help" title="<?php _e('Does the initiative contribute to the transformation of mobility in the DACH region?', 'mobility-trailblazers'); ?>">?</span>
                </label>
                <div class="mt-score-slider">
                    <input type="range" id="mobility_relevance_score" name="mobility_relevance_score" min="0" max="10" 
                           value="<?php echo $existing_score ? $existing_score->mobility_relevance_score : 0; ?>" 
                           class="mt-slider">
                    <span class="mt-score-display"><?php echo $existing_score ? $existing_score->mobility_relevance_score : 0; ?></span>
                </div>
            </div>
            
            <div class="mt-criterion">
                <label for="visibility_score">
                    <?php _e('Vorbildfunktion & Sichtbarkeit (Role Model & Visibility)', 'mobility-trailblazers'); ?>
                    <span class="mt-criterion-help" title="<?php _e('Is the person an inspiring role model with public impact?', 'mobility-trailblazers'); ?>">?</span>
                </label>
                <div class="mt-score-slider">
                    <input type="range" id="visibility_score" name="visibility_score" min="0" max="10" 
                           value="<?php echo $existing_score ? $existing_score->visibility_score : 0; ?>" 
                           class="mt-slider">
                    <span class="mt-score-display"><?php echo $existing_score ? $existing_score->visibility_score : 0; ?></span>
                </div>
            </div>
            
            <div class="mt-total-score">
                <span class="mt-total-label"><?php _e('Total Score:', 'mobility-trailblazers'); ?></span>
                <span class="mt-total-value"><?php echo $existing_score ? $existing_score->total_score : 0; ?></span>
                <span class="mt-total-max">/50</span>
            </div>
            
            <div class="mt-criterion mt-comments">
                <label for="evaluation_comments">
                    <?php _e('Comments (Optional)', 'mobility-trailblazers'); ?>
                </label>
                <textarea id="evaluation_comments" name="comments" rows="4" 
                          placeholder="<?php _e('Add any additional observations or notes...', 'mobility-trailblazers'); ?>"><?php echo $existing_score ? esc_textarea($existing_score->comments) : ''; ?></textarea>
            </div>
        </div>
        
        <div class="mt-form-actions">
            <button type="submit" class="button button-primary">
                <?php echo $existing_score ? __('Update Evaluation', 'mobility-trailblazers') : __('Submit Evaluation', 'mobility-trailblazers'); ?>
            </button>
        </div>
    </form>
</div>

<style>
.mt-jury-evaluation-frontend {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
}

.mt-evaluation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.mt-evaluation-header h1 {
    color: #2c5282;
    margin: 0;
}

.mt-candidate-profile {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.mt-profile-header {
    display: flex;
    gap: 30px;
    margin-bottom: 30px;
}

.mt-profile-image {
    width: 200px;
    height: 200px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}

.mt-profile-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.mt-profile-info h2 {
    margin: 0 0 15px 0;
    color: #2c5282;
}

.mt-position {
    font-size: 1.2em;
    color: #2d3748;
    margin: 0 0 10px 0;
}

.mt-company {
    font-size: 1.1em;
    color: #4a5568;
    margin: 0 0 10px 0;
}

.mt-location {
    color: #718096;
    margin: 0 0 15px 0;
    display: flex;
    align-items: center;
    gap: 5px;
}

.mt-categories {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.mt-category-badge {
    background: #2c5282;
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.85em;
}

.mt-description {
    border-top: 1px solid #e2e8f0;
    padding-top: 20px;
}

.mt-description h3 {
    color: #2c5282;
    margin: 0 0 15px 0;
}

.mt-evaluation-form {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.mt-evaluation-criteria h3 {
    color: #2c5282;
    margin: 0 0 25px 0;
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

.mt-total-score {
    background: #f7fafc;
    padding: 20px;
    border-radius: 8px;
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

.mt-comments textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    resize: vertical;
}

.mt-form-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

@media (max-width: 768px) {
    .mt-profile-header {
        flex-direction: column;
    }
    
    .mt-profile-image {
        width: 100%;
        height: 300px;
    }
    
    .mt-evaluation-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Update score displays when sliders change
    $('.mt-slider').on('input', function() {
        var value = $(this).val();
        $(this).siblings('.mt-score-display').text(value);
        updateTotalScore();
    });
    
    // Calculate and update total score
    function updateTotalScore() {
        var total = 0;
        $('.mt-slider').each(function() {
            total += parseInt($(this).val());
        });
        $('.mt-total-value').text(total);
    }
    
    // Form submission
    $('#mt-evaluation-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        // Show loading state
        $(this).addClass('mt-loading');
        
        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                alert(response.data.message);
                window.location.href = '<?php echo home_url('/jury/dashboard'); ?>';
            } else {
                alert('Error: ' + response.data.message);
            }
        }).always(function() {
            $('#mt-evaluation-form').removeClass('mt-loading');
        });
    });
});
</script> 