<?php
/**
 * Winners Display Template
 *
 * @package MobilityTrailblazers
 * 
 * Available variables:
 * $query - WP_Query object with winners
 * $atts - Shortcode attributes
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="mt-winners-display">
    <div class="mt-winners-header">
        <h2><?php printf(__('Mobility Trailblazers %s', 'mobility-trailblazers'), esc_html($atts['year'])); ?></h2>
        <p class="mt-winners-subtitle"><?php _e('Celebrating the innovators shaping the future of mobility', 'mobility-trailblazers'); ?></p>
    </div>
    
    <?php if ($query->have_posts()): ?>
        <div class="mt-winners-grid">
            <?php 
            $position = 1;
            while ($query->have_posts()): $query->the_post(); 
                $winner_id = get_the_ID();
                $photo = get_post_meta($winner_id, '_mt_photo_url', true);
                $company = get_post_meta($winner_id, '_mt_company_name', true);
                $position_title = get_post_meta($winner_id, '_mt_position', true);
                $innovation = get_post_meta($winner_id, '_mt_innovation_title', true);
                $location = get_post_meta($winner_id, '_mt_location', true);
                $final_score = get_post_meta($winner_id, '_mt_final_score', true);
                $categories = wp_get_post_terms($winner_id, 'mt_category');
                
                // Determine medal class
                $medal_class = '';
                if ($position === 1) {
                    $medal_class = 'gold';
                } elseif ($position === 2) {
                    $medal_class = 'silver';
                } elseif ($position === 3) {
                    $medal_class = 'bronze';
                }
            ?>
                <div class="mt-winner-card <?php echo esc_attr($medal_class); ?>" data-position="<?php echo $position; ?>">
                    <?php if ($position <= 3): ?>
                        <div class="mt-winner-medal">
                            <span class="mt-medal-icon"></span>
                            <span class="mt-medal-position"><?php echo $position; ?></span>
                        </div>
                    <?php else: ?>
                        <div class="mt-winner-position"><?php echo $position; ?></div>
                    <?php endif; ?>
                    
                    <div class="mt-winner-photo">
                        <?php if ($photo): ?>
                            <img src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                        <?php else: ?>
                            <div class="mt-no-photo">
                                <i class="dashicons dashicons-businessperson"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-winner-info">
                        <h3 class="mt-winner-name"><?php the_title(); ?></h3>
                        
                        <?php if ($company): ?>
                            <p class="mt-winner-company"><?php echo esc_html($company); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($position_title): ?>
                            <p class="mt-winner-position-title"><?php echo esc_html($position_title); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($location): ?>
                            <p class="mt-winner-location">
                                <i class="dashicons dashicons-location"></i>
                                <?php echo esc_html($location); ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if ($innovation): ?>
                            <div class="mt-winner-innovation">
                                <h4><?php _e('Innovation:', 'mobility-trailblazers'); ?></h4>
                                <p><?php echo esc_html($innovation); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($atts['show_category'] === 'yes' && !empty($categories)): ?>
                            <div class="mt-winner-categories">
                                <?php foreach ($categories as $category): ?>
                                    <span class="mt-category-badge"><?php echo esc_html($category->name); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($atts['show_score'] === 'yes' && $final_score): ?>
                            <div class="mt-winner-score">
                                <span class="mt-score-label"><?php _e('Final Score:', 'mobility-trailblazers'); ?></span>
                                <span class="mt-score-value"><?php echo esc_html($final_score); ?>/50</span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-winner-actions">
                            <a href="<?php the_permalink(); ?>" class="mt-view-profile-btn">
                                <?php _e('View Full Profile', 'mobility-trailblazers'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php 
                $position++;
            endwhile; 
            ?>
        </div>
    <?php else: ?>
        <div class="mt-no-winners">
            <p><?php _e('Winners for this year have not been announced yet.', 'mobility-trailblazers'); ?></p>
        </div>
    <?php endif; ?>
    
    <?php wp_reset_postdata(); ?>
</div> 