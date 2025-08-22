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
$organization = get_post_meta($candidate->ID, '_mt_organization', true) ?: '';
$position = get_post_meta($candidate->ID, '_mt_position', true) ?: '';
$biography = get_post_meta($candidate->ID, '_mt_description_full', true) ?: '';
$linkedin_url = get_post_meta($candidate->ID, '_mt_linkedin_url', true) ?: '';
$website_url = get_post_meta($candidate->ID, '_mt_website_url', true) ?: '';
// Get category from new meta field system (3 categories)
$category_type = get_post_meta($candidate->ID, '_mt_category_type', true) ?: '';
$photo_id = get_post_thumbnail_id($candidate->ID);

// Get individual evaluation criteria content
$criterion_courage = get_post_meta($candidate->ID, '_mt_criterion_courage', true) ?: '';
$criterion_innovation = get_post_meta($candidate->ID, '_mt_criterion_innovation', true) ?: '';
$criterion_implementation = get_post_meta($candidate->ID, '_mt_criterion_implementation', true) ?: '';
$criterion_relevance = get_post_meta($candidate->ID, '_mt_criterion_relevance', true) ?: '';
$criterion_visibility = get_post_meta($candidate->ID, '_mt_criterion_visibility', true) ?: '';

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
        'description' => __('Mut, Konventionen herauszufordern und neue Wege in der Mobilität zu beschreiten', 'mobility-trailblazers'),
        'icon' => 'dashicons-superhero',
        'color' => '#e74c3c'
    ],
    'innovation' => [
        'label' => __('Innovationsgrad', 'mobility-trailblazers'),
        'description' => __('Grad an Innovation und Kreativität bei der Lösung von Mobilitätsherausforderungen', 'mobility-trailblazers'),
        'icon' => 'dashicons-lightbulb',
        'color' => '#f39c12'
    ],
    'implementation' => [
        'label' => __('Umsetzungskraft & Wirkung', 'mobility-trailblazers'),
        'description' => __('Fähigkeit zur Umsetzung und realer Einfluss der Initiativen', 'mobility-trailblazers'),
        'icon' => 'dashicons-hammer',
        'color' => '#27ae60'
    ],
    'relevance' => [
        'label' => __('Relevanz für die Mobilitätswende', 'mobility-trailblazers'),
        'description' => __('Bedeutung und Beitrag zur Transformation der Mobilität', 'mobility-trailblazers'),
        'icon' => 'dashicons-location-alt',
        'color' => '#3498db'
    ],
    'visibility' => [
        'label' => __('Vorbildfunktion & Sichtbarkeit', 'mobility-trailblazers'),
        'description' => __('Rolle als Vorbild und öffentliche Wahrnehmbarkeit im Mobilitätssektor', 'mobility-trailblazers'),
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
                    
                    <?php if ($presentation_settings['show_category'] && !empty($category_type)) : ?>
                        <div class="mt-meta-item">
                            <span class="dashicons dashicons-category"></span>
                            <span><?php echo esc_html($category_type); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($linkedin_url || $website_url) : ?>
                    <div class="mt-candidate-links">
                        <?php if ($linkedin_url) : ?>
                            <a href="<?php echo esc_url($linkedin_url); ?>" target="_blank" class="mt-link-button">
                                <span class="dashicons dashicons-linkedin"></span>
                                <?php _e('LinkedIn Profile', 'mobility-trailblazers'); ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($website_url) : ?>
                            <a href="<?php echo esc_url($website_url); ?>" target="_blank" class="mt-link-button">
                                <span class="dashicons dashicons-admin-site"></span>
                                <?php _e('Website', 'mobility-trailblazers'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php 
                // Show Innovation Summary / Description
                $overview = get_post_meta($candidate->ID, '_mt_overview', true);
                $full_description = !empty($candidate->post_content) ? $candidate->post_content : $overview;
                
                if (!empty($full_description)) : ?>
                    <div class="mt-candidate-description" style="margin-top: 20px;">
                        <h3 style="color: #212529 !important;"><?php _e('Description', 'mobility-trailblazers'); ?></h3>
                        <div class="mt-description-content" style="color: #495057 !important;">
                            <?php 
                            if (!empty($candidate->post_content)) {
                                echo apply_filters('the_content', $candidate->post_content);
                            } else {
                                echo wp_kses_post($overview);
                            }
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php 
                // Always show biography if available - check both sources
                $bio_content = !empty($biography) ? $biography : '';
                if (empty($bio_content) && !empty($candidate->post_content)) {
                    $bio_content = $candidate->post_content;
                }
                
                if (!empty(trim($bio_content))) : ?>
                    <div class="mt-candidate-bio" style="display: block !important;">
                        <h3 style="color: #212529 !important;"><?php _e('Description', 'mobility-trailblazers'); ?></h3>
                        <div class="mt-bio-content" style="display: block !important;">
                            <?php 
                            // Clean and format the biography content
                            $bio_display = html_entity_decode($bio_content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                            $bio_display = wp_kses_post($bio_display);
                            
                            // If content is too long, show excerpt
                            if (strlen($bio_display) > 500) {
                                echo '<p style="color: #495057 !important;">' . wp_trim_words($bio_display, 80, '...') . '</p>';
                            } else {
                                echo '<p style="color: #495057 !important;">' . $bio_display . '</p>'; 
                            }
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Evaluation Criteria Information -->
    <?php if ($criterion_courage || $criterion_innovation || $criterion_implementation || $criterion_relevance || $criterion_visibility) : ?>
    <div class="mt-criteria-info-section">
        <h2 class="mt-section-title"><?php _e('Bewertungskriterien Details', 'mobility-trailblazers'); ?></h2>
        <div class="mt-criteria-info-grid">
            <?php if ($criterion_courage) : ?>
            <div class="mt-criterion-info-card" style="border-left: 4px solid #e74c3c;">
                <div class="mt-criterion-info-header">
                    <span class="dashicons dashicons-superhero" style="color: #e74c3c; font-size: 24px;"></span>
                    <h3 class="mt-criterion-info-title"><?php _e('Mut & Pioniergeist', 'mobility-trailblazers'); ?></h3>
                </div>
                <div class="mt-criterion-info-content">
                    <?php echo wp_kses_post(wpautop($criterion_courage)); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($criterion_innovation) : ?>
            <div class="mt-criterion-info-card" style="border-left: 4px solid #f39c12;">
                <div class="mt-criterion-info-header">
                    <span class="dashicons dashicons-lightbulb" style="color: #f39c12; font-size: 24px;"></span>
                    <h3 class="mt-criterion-info-title"><?php _e('Innovationsgrad', 'mobility-trailblazers'); ?></h3>
                </div>
                <div class="mt-criterion-info-content">
                    <?php echo wp_kses_post(wpautop($criterion_innovation)); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($criterion_implementation) : ?>
            <div class="mt-criterion-info-card" style="border-left: 4px solid #27ae60;">
                <div class="mt-criterion-info-header">
                    <span class="dashicons dashicons-hammer" style="color: #27ae60; font-size: 24px;"></span>
                    <h3 class="mt-criterion-info-title"><?php _e('Umsetzungskraft & Wirkung', 'mobility-trailblazers'); ?></h3>
                </div>
                <div class="mt-criterion-info-content">
                    <?php echo wp_kses_post(wpautop($criterion_implementation)); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($criterion_relevance) : ?>
            <div class="mt-criterion-info-card" style="border-left: 4px solid #3498db;">
                <div class="mt-criterion-info-header">
                    <span class="dashicons dashicons-location-alt" style="color: #3498db; font-size: 24px;"></span>
                    <h3 class="mt-criterion-info-title"><?php _e('Relevanz für die Mobilitätswende', 'mobility-trailblazers'); ?></h3>
                </div>
                <div class="mt-criterion-info-content">
                    <?php echo wp_kses_post(wpautop($criterion_relevance)); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($criterion_visibility) : ?>
            <div class="mt-criterion-info-card" style="border-left: 4px solid #9b59b6;">
                <div class="mt-criterion-info-header">
                    <span class="dashicons dashicons-visibility" style="color: #9b59b6; font-size: 24px;"></span>
                    <h3 class="mt-criterion-info-title"><?php _e('Vorbildfunktion & Sichtbarkeit', 'mobility-trailblazers'); ?></h3>
                </div>
                <div class="mt-criterion-info-content">
                    <?php echo wp_kses_post(wpautop($criterion_visibility)); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Evaluation Form -->
    <form id="mt-evaluation-form" class="<?php echo esc_attr($form_class); ?>" data-candidate-id="<?php echo esc_attr($candidate_id); ?>">
        <?php wp_nonce_field('mt_evaluation_nonce', 'mt_nonce'); ?>
        <input type="hidden" name="candidate_id" value="<?php echo esc_attr($candidate_id); ?>">
        <input type="hidden" name="jury_member_id" value="<?php echo esc_attr($jury_member->ID); ?>">
        
        <!-- Scoring Section -->
        <div class="mt-scoring-section">
            <div class="mt-evaluation-header">
                <h2 class="mt-section-title">
                    <?php _e('Evaluation Criteria', 'mobility-trailblazers'); ?>
                </h2>
                <div class="mt-total-score-display">
                    <?php 
                    $total = 0;
                    $count = 0;
                    if ($evaluation) {
                        foreach (['courage', 'innovation', 'implementation', 'relevance', 'visibility'] as $criterion) {
                            $score = $evaluation->{$criterion . '_score'};
                            if ($score !== null) {
                                $total += $score;
                                $count++;
                            }
                        }
                    }
                    $avg = $count > 0 ? round($total / $count, 1) : 0;
                    ?>
                    <?php _e('Average Score:', 'mobility-trailblazers'); ?>
                    <span id="mt-total-score"><?php echo esc_html($avg); ?></span>/10
                    <span class="mt-evaluated-count">(<?php echo esc_html($count); ?>/5 <?php _e('criteria evaluated', 'mobility-trailblazers'); ?>)</span>
                </div>
            </div>
            
            <div class="mt-criteria-grid">
                <?php foreach ($criteria as $key => $criterion) : 
                    $score_value = ($evaluation && isset($evaluation->{$key . '_score'})) ? $evaluation->{$key . '_score'} : 5;
                    $score_value = $score_value ?? 5; // Ensure it's never null
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
                                           step="0.5"
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
                            
                            <?php 
                            // Determine score state class for v4 CSS
                            $score_state_class = '';
                            $numeric_score = floatval($score_value);
                            if ($numeric_score >= 8) {
                                $score_state_class = 'mt-score-display--high';
                            } elseif ($numeric_score >= 5) {
                                $score_state_class = 'mt-score-display--medium';
                            } elseif ($numeric_score > 0) {
                                $score_state_class = 'mt-score-display--low';
                            }
                            ?>
                            <div class="mt-score-display <?php echo esc_attr($score_state_class); ?>">
                                <span class="mt-score-value"><?php echo esc_html($score_value); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Comments Section - Removed per Issue #25 -->
        <!-- The additional comments section has been removed from individual evaluation pages -->
        
        <!-- Form Actions -->
        <div class="mt-form-actions">
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
                <li><?php _e('Once submitted, you can still edit your evaluation if needed', 'mobility-trailblazers'); ?></li>
            </ul>
        </div>
    </form>
</div> 