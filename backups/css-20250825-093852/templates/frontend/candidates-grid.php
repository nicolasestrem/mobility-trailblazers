﻿<?php
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

// Performance optimization: Batch fetch metadata and terms to prevent N+1 queries
$candidate_ids = [];
if ($candidates->have_posts()) {
    foreach ($candidates->posts as $post) {
        $candidate_ids[] = $post->ID;
    }
}

// Batch fetch metadata for all candidates
$metadata = [];
if (!empty($candidate_ids)) {
    global $wpdb;
    $ids_placeholder = implode(',', array_fill(0, count($candidate_ids), '%d'));
    $meta_results = $wpdb->get_results($wpdb->prepare(
        "SELECT post_id, meta_key, meta_value 
         FROM {$wpdb->postmeta} 
         WHERE post_id IN ({$ids_placeholder}) 
         AND meta_key IN ('_mt_organization', '_mt_position')",
        $candidate_ids
    ));
    
    foreach ($meta_results as $meta) {
        $metadata[$meta->post_id][$meta->meta_key] = $meta->meta_value;
    }
}

// Batch fetch terms for all candidates if needed
$candidate_terms = [];
if ($show_category && !empty($candidate_ids)) {
    $terms_results = wp_get_object_terms($candidate_ids, 'mt_award_category', [
        'fields' => 'all_with_object_id'
    ]);
    
    foreach ($terms_results as $term) {
        if (!isset($candidate_terms[$term->object_id])) {
            $candidate_terms[$term->object_id] = [];
        }
        $candidate_terms[$term->object_id][] = $term;
    }
}
?>

<div class="mt-root">
<div class="mt-candidates-grid columns-<?php echo esc_attr($columns); ?>">
    <?php while ($candidates->have_posts()) : $candidates->the_post(); 
        $candidate_id = get_the_ID();
        
        // Use pre-fetched metadata instead of individual queries
        $organization = $metadata[$candidate_id]['_mt_organization'] ?? '';
        $position = $metadata[$candidate_id]['_mt_position'] ?? '';
        $categories = $candidate_terms[$candidate_id] ?? [];
        
        // Special handling for Friedrich Dräxlmaier (Issue #13)
        $image_modifier = '';
        if ($candidate_id == 4627) {
            $image_modifier = 'mt-candidate-card__image--adjusted';
        }
    ?>
        <div class="mt-candidate-card" data-i18n-class="mt-kandidaten-karte" data-candidate-id="<?php echo $candidate_id; ?>">
            <a href="<?php the_permalink(); ?>" class="mt-candidate-card__link">
                <?php if (has_post_thumbnail()) : ?>
                    <div class="mt-candidate-card__image-container">
                        <?php 
                        // Apply BEM modifier for specific candidates
                        $image_classes = 'mt-candidate-card__image';
                        if ($candidate_id == 4627) {
                            $image_classes .= ' mt-candidate-card__image--adjusted';
                        }
                        the_post_thumbnail('medium', ['class' => $image_classes]);
                        ?>
                        <span class="mt-candidate-card__overlay"><?php _e('View Profile', 'mobility-trailblazers'); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="mt-candidate-card__info">
                    <h3 class="mt-candidate-card__title"><?php the_title(); ?></h3>
                
                <?php if ($organization || $position) : ?>
                    <div class="mt-candidate-card__meta">
                        <?php if ($position) : ?>
                            <span class="mt-candidate-card__position"><?php echo esc_html($position); ?></span>
                        <?php endif; ?>
                        <?php if ($organization && $position) : ?>
                            <span class="mt-candidate-card__separator">@</span>
                        <?php endif; ?>
                        <?php if ($organization) : ?>
                            <span class="mt-candidate-card__organization"><?php echo esc_html($organization); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($show_category && !empty($categories)) : ?>
                    <div class="mt-candidate-card__categories">
                        <?php foreach ($categories as $category) : ?>
                            <span class="mt-candidate-card__category"><?php echo esc_html($category->name); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($show_bio && has_excerpt()) : ?>
                    <div class="mt-candidate-card__description">
                        <?php the_excerpt(); ?>
                    </div>
                <?php endif; ?>
                </div>
            </a>
        </div>
    <?php endwhile; ?>
</div>
</div><!-- .mt-root --> 

