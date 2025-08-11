<?php
/**
 * Candidates Grid Template
 *
 * @package MobilityTrailblazers
 * @since 2.0.1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Template variables from shortcode
$columns = intval($atts['columns']);
$show_bio = $atts['show_bio'] === 'yes';
$show_category = $atts['show_category'] === 'yes';
?>

<div class="mt-candidates-grid columns-<?php echo esc_attr($columns); ?>">
    <?php while ($candidates->have_posts()) : $candidates->the_post(); 
        $organization = get_post_meta(get_the_ID(), '_mt_organization', true);
        $position = get_post_meta(get_the_ID(), '_mt_position', true);
        $categories = wp_get_post_terms(get_the_ID(), 'mt_award_category');
    ?>
        <div class="mt-candidate-grid-item">
            <?php if (has_post_thumbnail()) : ?>
                <div class="mt-candidate-image">
                    <?php the_post_thumbnail('medium', ['class' => 'mt-candidate-photo']); ?>
                </div>
            <?php endif; ?>
            
            <div class="mt-candidate-info">
                <h3><?php the_title(); ?></h3>
                
                <?php if ($organization || $position) : ?>
                    <div class="mt-candidate-meta">
                        <?php if ($position) : ?>
                            <span class="mt-position"><?php echo esc_html($position); ?></span>
                        <?php endif; ?>
                        <?php if ($organization && $position) : ?>
                            <span class="mt-separator">@</span>
                        <?php endif; ?>
                        <?php if ($organization) : ?>
                            <span class="mt-organization"><?php echo esc_html($organization); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($show_category && !empty($categories)) : ?>
                    <div class="mt-candidate-categories">
                        <?php foreach ($categories as $category) : ?>
                            <span class="mt-category-tag"><?php echo esc_html($category->name); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($show_bio && has_excerpt()) : ?>
                    <div class="mt-candidate-bio">
                        <?php the_excerpt(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
</div> 