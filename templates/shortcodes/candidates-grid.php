<?php
/**
 * Candidates Grid Template
 *
 * @package MobilityTrailblazers
 * 
 * Available variables:
 * $query - WP_Query object with candidates
 * $atts - Shortcode attributes
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="mt-candidates-grid-wrapper">
    <?php if ($atts['show_filters'] === 'yes'): ?>
        <div class="mt-grid-filters">
            <div class="mt-filter-buttons">
                <button class="mt-filter-button active" data-filter="all">
                    <?php _e('All', 'mobility-trailblazers'); ?>
                </button>
                <?php
                $categories = get_terms(array(
                    'taxonomy' => 'mt_category',
                    'hide_empty' => true,
                ));
                foreach ($categories as $category):
                ?>
                    <button class="mt-filter-button" data-filter="<?php echo esc_attr($category->slug); ?>">
                        <?php echo esc_html($category->name); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-search-box">
                <input type="text" id="mt-candidate-search" placeholder="<?php _e('Search candidates...', 'mobility-trailblazers'); ?>">
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($query->have_posts()): ?>
        <div class="mt-candidates-grid columns-<?php echo esc_attr($atts['columns']); ?>">
            <div class="grid-sizer"></div>
            
            <?php while ($query->have_posts()): $query->the_post(); 
                $candidate_id = get_the_ID();
                $company = get_post_meta($candidate_id, '_mt_company_name', true);
                $position = get_post_meta($candidate_id, '_mt_position', true);
                $location = get_post_meta($candidate_id, '_mt_location', true);
                $innovation = get_post_meta($candidate_id, '_mt_innovation_title', true);
                $photo = get_post_meta($candidate_id, '_mt_photo_url', true);
                $categories = wp_get_post_terms($candidate_id, 'mt_category');
                $category_slugs = wp_list_pluck($categories, 'slug');
                
                // Get public vote count if enabled
                $vote_count = mt_get_public_vote_count($candidate_id);
            ?>
                <div class="mt-candidate-card" data-candidate-id="<?php echo $candidate_id; ?>" 
                     data-categories="<?php echo esc_attr(implode(' ', $category_slugs)); ?>">
                    
                    <?php if ($photo): ?>
                        <div class="mt-candidate-photo">
                            <img src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" class="lazy-load">
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-candidate-content">
                        <h3 class="mt-candidate-name"><?php the_title(); ?></h3>
                        
                        <?php if ($company): ?>
                            <p class="mt-candidate-company"><?php echo esc_html($company); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($position): ?>
                            <p class="mt-candidate-position"><?php echo esc_html($position); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($location): ?>
                            <p class="mt-candidate-location">
                                <i class="dashicons dashicons-location"></i>
                                <?php echo esc_html($location); ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if ($innovation): ?>
                            <div class="mt-candidate-innovation">
                                <h4><?php _e('Innovation:', 'mobility-trailblazers'); ?></h4>
                                <p><?php echo esc_html($innovation); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($categories)): ?>
                            <div class="mt-candidate-categories">
                                <?php foreach ($categories as $category): ?>
                                    <span class="mt-category-tag"><?php echo esc_html($category->name); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (mt_is_public_voting_enabled()): ?>
                            <div class="mt-candidate-voting">
                                <button class="mt-vote-button" data-candidate-id="<?php echo $candidate_id; ?>">
                                    <i class="dashicons dashicons-thumbs-up"></i>
                                    <?php _e('Vote', 'mobility-trailblazers'); ?>
                                </button>
                                <span class="mt-vote-count" data-candidate-id="<?php echo $candidate_id; ?>">
                                    <?php echo number_format($vote_count); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-candidate-actions">
                            <a href="<?php the_permalink(); ?>" class="mt-view-profile">
                                <?php _e('View Profile', 'mobility-trailblazers'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <?php if ($atts['show_pagination'] === 'yes' && $query->max_num_pages > 1): ?>
            <div class="mt-pagination">
                <?php
                echo paginate_links(array(
                    'total' => $query->max_num_pages,
                    'current' => max(1, get_query_var('paged')),
                    'prev_text' => __('&laquo; Previous', 'mobility-trailblazers'),
                    'next_text' => __('Next &raquo;', 'mobility-trailblazers'),
                ));
                ?>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="mt-no-results">
            <p><?php _e('No candidates found.', 'mobility-trailblazers'); ?></p>
        </div>
    <?php endif; ?>
    
    <?php wp_reset_postdata(); ?>
</div> 