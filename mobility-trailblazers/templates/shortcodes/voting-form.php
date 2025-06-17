<?php
/**
 * Voting Form Template
 *
 * @package MobilityTrailblazers
 * 
 * Available variables:
 * $candidate_id - Candidate ID (if specified)
 * $candidate - WP_Post object (if candidate_id provided)
 * $atts - Shortcode attributes
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="mt-voting-form-wrapper">
    <?php if ($candidate_id && isset($candidate)): ?>
        <!-- Single candidate voting -->
        <div class="mt-voting-single">
            <div class="mt-candidate-info">
                <?php
                $photo = get_post_meta($candidate_id, '_mt_photo_url', true);
                $company = get_post_meta($candidate_id, '_mt_company_name', true);
                $innovation = get_post_meta($candidate_id, '_mt_innovation_title', true);
                $vote_count = mt_get_public_vote_count($candidate_id);
                ?>
                
                <?php if ($photo): ?>
                    <img src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr($candidate->post_title); ?>" class="mt-candidate-photo">
                <?php endif; ?>
                
                <div class="mt-candidate-details">
                    <h3><?php echo esc_html($candidate->post_title); ?></h3>
                    <?php if ($company): ?>
                        <p class="mt-company"><?php echo esc_html($company); ?></p>
                    <?php endif; ?>
                    <?php if ($innovation): ?>
                        <p class="mt-innovation"><?php echo esc_html($innovation); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mt-voting-section">
                <button class="mt-vote-button button button-primary" data-candidate-id="<?php echo $candidate_id; ?>">
                    <i class="dashicons dashicons-thumbs-up"></i>
                    <?php _e('Vote for this Candidate', 'mobility-trailblazers'); ?>
                </button>
                
                <?php if ($atts['show_results'] === 'yes'): ?>
                    <div class="mt-vote-results">
                        <span class="mt-vote-count"><?php echo number_format($vote_count); ?></span>
                        <span class="mt-vote-label"><?php _e('votes', 'mobility-trailblazers'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    <?php else: ?>
        <!-- General voting form -->
        <div class="mt-voting-general">
            <h3><?php _e('Vote for Your Favorite Candidates', 'mobility-trailblazers'); ?></h3>
            <p><?php _e('Browse through our candidates and vote for the ones you believe deserve recognition.', 'mobility-trailblazers'); ?></p>
            
            <div class="mt-voting-search">
                <input type="text" id="mt-voting-search" placeholder="<?php _e('Search candidates...', 'mobility-trailblazers'); ?>">
            </div>
            
            <div class="mt-voting-categories">
                <?php
                $categories = get_terms(array(
                    'taxonomy' => 'mt_category',
                    'hide_empty' => true,
                ));
                ?>
                <button class="mt-category-filter active" data-category="all">
                    <?php _e('All Categories', 'mobility-trailblazers'); ?>
                </button>
                <?php foreach ($categories as $category): ?>
                    <button class="mt-category-filter" data-category="<?php echo esc_attr($category->slug); ?>">
                        <?php echo esc_html($category->name); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-voting-candidates" id="mt-voting-candidates">
                <?php
                // Query candidates
                $args = array(
                    'post_type' => 'mt_candidate',
                    'posts_per_page' => 20,
                    'post_status' => 'publish',
                    'meta_query' => array(
                        array(
                            'key' => '_mt_status',
                            'value' => 'approved',
                            'compare' => '=',
                        ),
                    ),
                );
                
                $candidates = new WP_Query($args);
                
                if ($candidates->have_posts()):
                    while ($candidates->have_posts()): $candidates->the_post();
                        $cand_id = get_the_ID();
                        $photo = get_post_meta($cand_id, '_mt_photo_url', true);
                        $company = get_post_meta($cand_id, '_mt_company_name', true);
                        $categories = wp_get_post_terms($cand_id, 'mt_category');
                        $vote_count = mt_get_public_vote_count($cand_id);
                ?>
                    <div class="mt-voting-candidate" data-categories="<?php echo esc_attr(implode(' ', wp_list_pluck($categories, 'slug'))); ?>">
                        <?php if ($photo): ?>
                            <img src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                        <?php endif; ?>
                        
                        <h4><?php the_title(); ?></h4>
                        <?php if ($company): ?>
                            <p class="mt-company"><?php echo esc_html($company); ?></p>
                        <?php endif; ?>
                        
                        <div class="mt-voting-actions">
                            <button class="mt-vote-button" data-candidate-id="<?php echo $cand_id; ?>">
                                <i class="dashicons dashicons-thumbs-up"></i>
                                <?php _e('Vote', 'mobility-trailblazers'); ?>
                            </button>
                            
                            <?php if ($atts['show_results'] === 'yes'): ?>
                                <span class="mt-vote-count"><?php echo number_format($vote_count); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php
                    endwhile;
                    wp_reset_postdata();
                else:
                ?>
                    <p><?php _e('No candidates available for voting at this time.', 'mobility-trailblazers'); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="mt-load-more-wrapper">
                <button id="mt-load-more-voting" class="button" data-page="1">
                    <?php _e('Load More Candidates', 'mobility-trailblazers'); ?>
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="mt-voting-notification" id="mt-voting-notification" style="display: none;">
    <div class="mt-notification-content">
        <span class="mt-notification-message"></span>
        <button class="mt-notification-close">&times;</button>
    </div>
</div> 