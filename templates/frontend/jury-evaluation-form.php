<?php
/**
 * Jury Evaluation Form Template
 *
 * @package MobilityTrailblazers
 * @since 2.0.1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get candidate
$candidate = get_post($candidate_id);
if (!$candidate) {
    echo '<div class="mt-notice mt-notice-error">' . __('Candidate not found.', 'mobility-trailblazers') . '</div>';
    return;
}

// Get candidate meta
$organization = get_post_meta($candidate->ID, '_mt_organization', true);
$position = get_post_meta($candidate->ID, '_mt_position', true);
$innovation_summary = get_post_meta($candidate->ID, '_mt_innovation_summary', true);
$categories = wp_get_post_terms($candidate->ID, 'mt_award_category');
$photo_id = get_post_thumbnail_id($candidate->ID);

// Get existing evaluation if any
$evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
$existing = $evaluation_repo->find_all([
    'jury_member_id' => $jury_member->ID,
    'candidate_id' => $candidate_id,
    'limit' => 1
]);
$evaluation = !empty($existing) ? $existing[0] : null;

// Get candidate presentation settings
$presentation_settings = get_option('mt_candidate_presentation', [
    'profile_layout' => 'side-by-side',
    'photo_style' => 'rounded',
    'photo_size' => 'medium',
    'show_organization' => 1,
    'show_position' => 1,
    'show_category' => 1,
    'show_innovation_summary' => 1,
    'show_full_bio' => 1,
    'form_style' => 'cards',
    'scoring_style' => 'slider',
    'enable_animations' => 1,
    'enable_hover_effects' => 1
]);

// Apply layout classes
$showcase_class = 'mt-candidate-showcase mt-layout-' . $presentation_settings['profile_layout'];
$photo_class = 'mt-candidate-photo mt-photo-' . $presentation_settings['photo_style'] . ' mt-photo-' . $presentation_settings['photo_size'];
$form_class = 'mt-evaluation-form mt-form-' . $presentation_settings['form_style'];

// Add animation classes if enabled
if (!empty($presentation_settings['enable_animations'])) {
    $showcase_class .= ' mt-animated';
}

// Evaluation criteria with icons and descriptions
$criteria = [
    'courage' => [
        'label' => __('Mut & Pioniergeist', 'mobility-trailblazers'),
        'description' => __('Courage to challenge conventions and pioneer new paths in mobility', 'mobility-trailblazers'),
        'icon' => 'dashicons-superhero',
        'color' => '#e74c3c'
    ],
    'innovation' => [
        'label' => __('Innovationsgrad', 'mobility-trailblazers'),
        'description' => __('Level of innovation and creative solutions for mobility challenges', 'mobility-trailblazers'),
        'icon' => 'dashicons-lightbulb',
        'color' => '#f39c12'
    ],
    'implementation' => [
        'label' => __('Umsetzungskraft & Wirkung', 'mobility-trailblazers'),
        'description' => __('Implementation strength and real-world impact of initiatives', 'mobility-trailblazers'),
        'icon' => 'dashicons-hammer',
        'color' => '#27ae60'
    ],
    'relevance' => [
        'label' => __('Relevanz für Mobilitätswende', 'mobility-trailblazers'),
        'description' => __('Relevance and contribution to sustainable mobility transformation', 'mobility-trailblazers'),
        'icon' => 'dashicons-location-alt',
        'color' => '#3498db'
    ],
    'visibility' => [
        'label' => __('Vorbildfunktion & Sichtbarkeit', 'mobility-trailblazers'),
        'description' => __('Role model function and visibility in the mobility sector', 'mobility-trailblazers'),
        'icon' => 'dashicons-visibility',
        'color' => '#9b59b6'
    ]
];
?>

<div class="mt-evaluation-page">
    <!-- Header -->
    <div class="mt-evaluation-header">
        <a href="<?php echo esc_url(remove_query_arg('evaluate')); ?>" class="mt-back-link">
            <span class="dashicons dashicons-arrow-left-alt"></span>
            <?php _e('Back to Dashboard', 'mobility-trailblazers'); ?>
        </a>
        
        <div class="mt-evaluation-title">
            <h1><?php _e('Evaluate Candidate', 'mobility-trailblazers'); ?></h1>
            <?php if ($evaluation && $evaluation->status === 'completed') : ?>
                <span class="mt-status-badge mt-status-completed">
                    <?php _e('Evaluation Submitted', 'mobility-trailblazers'); ?>
                </span>
            <?php elseif ($evaluation && $evaluation->status === 'draft') : ?>
                <span class="mt-status-badge mt-status-draft">
                    <?php _e('Draft Saved', 'mobility-trailblazers'); ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Candidate Information -->
    <div class="<?php echo esc_attr($showcase_class); ?>">
        <div class="mt-candidate-profile">
            <?php if ($photo_id && $presentation_settings['profile_layout'] !== 'minimal') : ?>
                <div class="mt-candidate-photo-wrap">
                    <?php echo wp_get_attachment_image($photo_id, 'medium', false, ['class' => $photo_class]); ?>
                </div>
            <?php endif; ?>
            
            <div class="mt-candidate-details">
                <h2 class="mt-candidate-name"><?php echo esc_html($candidate->post_title); ?></h2>
                
                <div class="mt-candidate-meta">
                    <?php if ($presentation_settings['show_organization'] && $organization) : ?>
                        <div class="mt-meta-item">
                            <span class="dashicons dashicons-building"></span>
                            <span><?php echo esc_html($organization); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($presentation_settings['show_position'] && $position) : ?>
                        <div class="mt-meta-item">
                            <span class="dashicons dashicons-id"></span>
                            <span><?php echo esc_html($position); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($presentation_settings['show_category'] && !empty($categories)) : ?>
                        <div class="mt-meta-item">
                            <span class="dashicons dashicons-category"></span>
                            <span><?php echo esc_html($categories[0]->name); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($presentation_settings['show_innovation_summary'] && $innovation_summary) : ?>
                    <div class="mt-innovation-summary">
                        <h3><?php _e('Innovation Summary', 'mobility-trailblazers'); ?></h3>
                        <p><?php echo esc_html($innovation_summary); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if ($presentation_settings['show_full_bio'] && $candidate->post_content) : ?>
                    <div class="mt-candidate-bio">
                        <h3><?php _e('Biography', 'mobility-trailblazers'); ?></h3>
                        <div class="mt-bio-content">
                            <?php echo wp_kses_post(wpautop($candidate->post_content)); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Evaluation Form -->
    <form id="mt-evaluation-form" class="<?php echo esc_attr($form_class); ?>" data-candidate-id="<?php echo esc_attr($candidate_id); ?>">
        <?php wp_nonce_field('mt_evaluation_nonce', 'mt_nonce'); ?>
        <input type="hidden" name="candidate_id" value="<?php echo esc_attr($candidate_id); ?>">
        <input type="hidden" name="jury_member_id" value="<?php echo esc_attr($jury_member->ID); ?>">
        
        <!-- Scoring Section -->
        <div class="mt-scoring-section">
            <h2 class="mt-section-title">
                <?php _e('Evaluation Criteria', 'mobility-trailblazers'); ?>
                <span class="mt-total-score-display">
                    <?php _e('Total Score:', 'mobility-trailblazers'); ?>
                    <span id="mt-total-score">0</span>/10
                </span>
            </h2>
            
            <div class="mt-criteria-grid">
                <?php foreach ($criteria as $key => $criterion) : 
                    $score_value = $evaluation ? $evaluation->{$key . '_score'} : 5;
                ?>
                    <div class="mt-criterion-card" data-criterion="<?php echo esc_attr($key); ?>">
                        <div class="mt-criterion-header" style="border-left-color: <?php echo esc_attr($criterion['color']); ?>">
                            <span class="mt-criterion-icon dashicons <?php echo esc_attr($criterion['icon']); ?>" 
                                  style="color: <?php echo esc_attr($criterion['color']); ?>"></span>
                            <div class="mt-criterion-info">
                                <h3 class="mt-criterion-label"><?php echo esc_html($criterion['label']); ?></h3>
                                <p class="mt-criterion-description"><?php echo esc_html($criterion['description']); ?></p>
                            </div>
                        </div>
                        
                        <div class="mt-scoring-control">
                            <?php if ($presentation_settings['scoring_style'] === 'slider') : ?>
                                <div class="mt-score-slider-wrapper">
                                    <input type="range" 
                                           name="<?php echo esc_attr($key); ?>_score" 
                                           class="mt-score-slider" 
                                           min="0" 
                                           max="10" 
                                           value="<?php echo esc_attr($score_value); ?>"
                                           data-criterion="<?php echo esc_attr($key); ?>">
                                    <div class="mt-score-marks">
                                        <?php for ($i = 0; $i <= 10; $i++) : ?>
                                            <span class="mt-score-mark" data-value="<?php echo $i; ?>"><?php echo $i; ?></span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            <?php elseif ($presentation_settings['scoring_style'] === 'stars') : ?>
                                <div class="mt-star-rating" data-criterion="<?php echo esc_attr($key); ?>">
                                    <?php for ($i = 1; $i <= 10; $i++) : ?>
                                        <span class="dashicons dashicons-star-empty <?php echo $i <= $score_value ? 'active' : ''; ?>" 
                                              data-value="<?php echo $i; ?>"></span>
                                    <?php endfor; ?>
                                    <input type="hidden" name="<?php echo esc_attr($key); ?>_score" 
                                           value="<?php echo esc_attr($score_value); ?>" />
                                </div>
                            <?php elseif ($presentation_settings['scoring_style'] === 'numeric') : ?>
                                <div class="mt-numeric-input">
                                    <input type="number" 
                                           name="<?php echo esc_attr($key); ?>_score" 
                                           min="0" 
                                           max="10" 
                                           value="<?php echo esc_attr($score_value); ?>"
                                           class="mt-score-input" />
                                    <span class="mt-score-label">/ 10</span>
                                </div>
                            <?php elseif ($presentation_settings['scoring_style'] === 'buttons') : ?>
                                <div class="mt-button-group" data-criterion="<?php echo esc_attr($key); ?>">
                                    <?php for ($i = 0; $i <= 10; $i++) : ?>
                                        <button type="button" 
                                                class="mt-score-button <?php echo $i == $score_value ? 'active' : ''; ?>" 
                                                data-value="<?php echo $i; ?>"><?php echo $i; ?></button>
                                    <?php endfor; ?>
                                    <input type="hidden" name="<?php echo esc_attr($key); ?>_score" 
                                           value="<?php echo esc_attr($score_value); ?>" />
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-score-display" style="background-color: <?php echo esc_attr($criterion['color']); ?>">
                                <span class="mt-score-value"><?php echo esc_html($score_value); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Comments Section -->
        <div class="mt-comments-section">
            <h2 class="mt-section-title"><?php _e('Additional Comments', 'mobility-trailblazers'); ?></h2>
            <p class="mt-section-description">
                <?php _e('Please provide any additional insights or observations about this candidate (optional).', 'mobility-trailblazers'); ?>
            </p>
            <textarea name="comments" 
                      id="mt-comments" 
                      class="mt-comments-textarea" 
                      rows="6"
                      placeholder="<?php esc_attr_e('Share your thoughts about this candidate\'s contributions to mobility innovation...', 'mobility-trailblazers'); ?>"><?php 
                      echo $evaluation ? esc_textarea($evaluation->comments) : ''; 
            ?></textarea>
            <div class="mt-char-count">
                <span id="mt-char-current">0</span> / 1000 <?php _e('characters', 'mobility-trailblazers'); ?>
            </div>
        </div>
        
        <!-- Form Actions -->
        <div class="mt-form-actions">
            <button type="button" class="mt-btn mt-btn-secondary mt-save-draft">
                <span class="dashicons dashicons-edit"></span>
                <?php _e('Save as Draft', 'mobility-trailblazers'); ?>
            </button>
            
            <button type="submit" class="mt-btn mt-btn-primary">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Submit Evaluation', 'mobility-trailblazers'); ?>
            </button>
        </div>
        
        <!-- Submission Guidelines -->
        <div class="mt-submission-guidelines">
            <h3><?php _e('Evaluation Guidelines', 'mobility-trailblazers'); ?></h3>
            <ul>
                <li><?php _e('Score each criterion from 0 (lowest) to 10 (highest) based on your assessment', 'mobility-trailblazers'); ?></li>
                <li><?php _e('Consider the candidate\'s overall impact on mobility transformation', 'mobility-trailblazers'); ?></li>
                <li><?php _e('You can save your evaluation as a draft and return later to complete it', 'mobility-trailblazers'); ?></li>
                <li><?php _e('Once submitted, you can still edit your evaluation if needed', 'mobility-trailblazers'); ?></li>
            </ul>
        </div>
    </form>
</div> 