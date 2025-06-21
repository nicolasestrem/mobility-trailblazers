<?php
/**
 * Candidate Profile Template
 *
 * @package MobilityTrailblazers
 * 
 * Available variables:
 * $candidate - WP_Post object
 * $candidate_id - Candidate ID
 * $atts - Shortcode attributes
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get candidate meta data
$photo = get_post_meta($candidate_id, '_mt_photo_url', true);
$company = get_post_meta($candidate_id, '_mt_company_name', true);
$position = get_post_meta($candidate_id, '_mt_position', true);
$location = get_post_meta($candidate_id, '_mt_location', true);
$email = get_post_meta($candidate_id, '_mt_email', true);
$phone = get_post_meta($candidate_id, '_mt_phone', true);
$website = get_post_meta($candidate_id, '_mt_website', true);
$linkedin = get_post_meta($candidate_id, '_mt_linkedin_url', true);

// Innovation details
$innovation_title = get_post_meta($candidate_id, '_mt_innovation_title', true);
$innovation_description = get_post_meta($candidate_id, '_mt_innovation_description', true);
$impact_metrics = get_post_meta($candidate_id, '_mt_impact_metrics', true);
$innovation_file = get_post_meta($candidate_id, '_mt_innovation_file_url', true);
$video_url = get_post_meta($candidate_id, '_mt_video_url', true);

// Status and scores
$status = get_post_meta($candidate_id, '_mt_status', true);
$final_score = get_post_meta($candidate_id, '_mt_final_score', true);

// Get categories
$categories = wp_get_post_terms($candidate_id, 'mt_category');

// Get jury evaluations if allowed
$evaluations = array();
if ($atts['show_jury_comments'] === 'yes' && current_user_can('mt_manage_awards')) {
    global $wpdb;
    $evaluations = $wpdb->get_results($wpdb->prepare(
        "SELECT v.*, j.post_title as jury_name 
         FROM {$wpdb->prefix}mt_votes v
         LEFT JOIN {$wpdb->posts} j ON v.jury_member_id = j.ID
         WHERE v.candidate_id = %d AND v.is_active = 1
         ORDER BY v.created_at DESC",
        $candidate_id
    ));
}
?>

<div class="mt-candidate-profile">
    <div class="mt-profile-header">
        <div class="mt-profile-photo">
            <?php if ($photo): ?>
                <img src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr($candidate->post_title); ?>">
            <?php else: ?>
                <div class="mt-no-photo-large">
                    <i class="dashicons dashicons-businessperson"></i>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="mt-profile-info">
            <h1 class="mt-candidate-name"><?php echo esc_html($candidate->post_title); ?></h1>
            
            <?php if ($position || $company): ?>
                <div class="mt-professional-info">
                    <?php if ($position): ?>
                        <p class="mt-position"><?php echo esc_html($position); ?></p>
                    <?php endif; ?>
                    <?php if ($company): ?>
                        <p class="mt-company"><?php echo esc_html($company); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($location): ?>
                <p class="mt-location">
                    <i class="dashicons dashicons-location"></i>
                    <?php echo esc_html($location); ?>
                </p>
            <?php endif; ?>
            
            <?php if (!empty($categories)): ?>
                <div class="mt-profile-categories">
                    <?php foreach ($categories as $category): ?>
                        <span class="mt-category-badge"><?php echo esc_html($category->name); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($status === 'winner'): ?>
                <div class="mt-winner-badge">
                    <i class="dashicons dashicons-awards"></i>
                    <?php _e('Award Winner', 'mobility-trailblazers'); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($atts['show_score'] === 'yes' && $final_score && current_user_can('mt_manage_awards')): ?>
                <div class="mt-profile-score">
                    <span class="mt-score-label"><?php _e('Final Score:', 'mobility-trailblazers'); ?></span>
                    <span class="mt-score-value"><?php echo esc_html($final_score); ?>/50</span>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="mt-profile-content">
        <div class="mt-profile-main">
            <?php if ($innovation_title || $innovation_description): ?>
                <section class="mt-profile-section">
                    <h2><?php _e('Innovation', 'mobility-trailblazers'); ?></h2>
                    
                    <?php if ($innovation_title): ?>
                        <h3 class="mt-innovation-title"><?php echo esc_html($innovation_title); ?></h3>
                    <?php endif; ?>
                    
                    <?php if ($innovation_description): ?>
                        <div class="mt-innovation-description">
                            <?php echo wp_kses_post(wpautop($innovation_description)); ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
            
            <?php if ($impact_metrics): ?>
                <section class="mt-profile-section">
                    <h2><?php _e('Impact Metrics', 'mobility-trailblazers'); ?></h2>
                    <div class="mt-impact-metrics">
                        <?php echo wp_kses_post(wpautop($impact_metrics)); ?>
                    </div>
                </section>
            <?php endif; ?>
            
            <?php if ($video_url): ?>
                <section class="mt-profile-section">
                    <h2><?php _e('Video Presentation', 'mobility-trailblazers'); ?></h2>
                    <div class="mt-video-embed">
                        <?php echo wp_oembed_get($video_url); ?>
                    </div>
                </section>
            <?php endif; ?>
            
            <?php if ($atts['show_jury_comments'] === 'yes' && !empty($evaluations)): ?>
                <section class="mt-profile-section">
                    <h2><?php _e('Jury Evaluations', 'mobility-trailblazers'); ?></h2>
                    <div class="mt-evaluations">
                        <?php foreach ($evaluations as $evaluation): ?>
                            <div class="mt-evaluation-card">
                                <div class="mt-evaluation-header">
                                    <span class="mt-jury-name"><?php echo esc_html($evaluation->jury_name); ?></span>
                                    <span class="mt-evaluation-date"><?php echo date_i18n(get_option('date_format'), strtotime($evaluation->created_at)); ?></span>
                                </div>
                                <div class="mt-evaluation-scores">
                                    <span class="mt-criteria-score">
                                        <?php _e('Courage:', 'mobility-trailblazers'); ?> <?php echo $evaluation->courage_score; ?>/10
                                    </span>
                                    <span class="mt-criteria-score">
                                        <?php _e('Innovation:', 'mobility-trailblazers'); ?> <?php echo $evaluation->innovation_score; ?>/10
                                    </span>
                                    <span class="mt-criteria-score">
                                        <?php _e('Implementation:', 'mobility-trailblazers'); ?> <?php echo $evaluation->implementation_score; ?>/10
                                    </span>
                                    <span class="mt-criteria-score">
                                        <?php _e('Relevance:', 'mobility-trailblazers'); ?> <?php echo $evaluation->relevance_score; ?>/10
                                    </span>
                                    <span class="mt-criteria-score">
                                        <?php _e('Visibility:', 'mobility-trailblazers'); ?> <?php echo $evaluation->visibility_score; ?>/10
                                    </span>
                                </div>
                                <?php if ($evaluation->comments): ?>
                                    <div class="mt-evaluation-comments">
                                        <p><?php echo esc_html($evaluation->comments); ?></p>
                                    </div>
                                <?php endif; ?>
                                <div class="mt-evaluation-total">
                                    <strong><?php _e('Total Score:', 'mobility-trailblazers'); ?></strong>
                                    <?php echo $evaluation->total_score; ?>/50
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
        
        <div class="mt-profile-sidebar">
            <section class="mt-contact-section">
                <h3><?php _e('Contact Information', 'mobility-trailblazers'); ?></h3>
                
                <?php if ($website): ?>
                    <div class="mt-contact-item">
                        <i class="dashicons dashicons-admin-site-alt3"></i>
                        <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener noreferrer">
                            <?php _e('Website', 'mobility-trailblazers'); ?>
                        </a>
                    </div>
                <?php endif; ?>
                
                <?php if ($linkedin): ?>
                    <div class="mt-contact-item">
                        <i class="dashicons dashicons-linkedin"></i>
                        <a href="<?php echo esc_url($linkedin); ?>" target="_blank" rel="noopener noreferrer">
                            <?php _e('LinkedIn Profile', 'mobility-trailblazers'); ?>
                        </a>
                    </div>
                <?php endif; ?>
                
                <?php if ($email && current_user_can('mt_manage_awards')): ?>
                    <div class="mt-contact-item">
                        <i class="dashicons dashicons-email"></i>
                        <a href="mailto:<?php echo esc_attr($email); ?>">
                            <?php echo esc_html($email); ?>
                        </a>
                    </div>
                <?php endif; ?>
                
                <?php if ($phone && current_user_can('mt_manage_awards')): ?>
                    <div class="mt-contact-item">
                        <i class="dashicons dashicons-phone"></i>
                        <?php echo esc_html($phone); ?>
                    </div>
                <?php endif; ?>
            </section>
            
            <?php if ($innovation_file): ?>
                <section class="mt-documents-section">
                    <h3><?php _e('Documents', 'mobility-trailblazers'); ?></h3>
                    <div class="mt-document-item">
                        <i class="dashicons dashicons-media-document"></i>
                        <a href="<?php echo esc_url($innovation_file); ?>" target="_blank">
                            <?php _e('Innovation Documentation', 'mobility-trailblazers'); ?>
                        </a>
                    </div>
                </section>
            <?php endif; ?>
            
            <?php if (mt_is_public_voting_enabled()): ?>
                <section class="mt-voting-section">
                    <h3><?php _e('Public Voting', 'mobility-trailblazers'); ?></h3>
                    <button class="mt-vote-button button button-primary" data-candidate-id="<?php echo $candidate_id; ?>">
                        <i class="dashicons dashicons-thumbs-up"></i>
                        <?php _e('Vote for this Candidate', 'mobility-trailblazers'); ?>
                    </button>
                    <p class="mt-vote-count">
                        <?php printf(__('%s votes', 'mobility-trailblazers'), number_format(mt_get_public_vote_count($candidate_id))); ?>
                    </p>
                </section>
            <?php endif; ?>
        </div>
    </div>
</div> 