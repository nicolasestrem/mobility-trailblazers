<?php
/**
 * Winners Display Template
 *
 * @package MobilityTrailblazers
 * @since 2.0.1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Template variables from shortcode
$show_scores = $atts['show_scores'] === 'yes';
$year = $atts['year'];
?>

<div class="mt-root">
<div class="mt-winners-display">
    <div class="mt-winners-header">
        <h2><?php printf(__('Mobility Trailblazers %s Winners', 'mobility-trailblazers'), esc_html($year)); ?></h2>
        <p><?php _e('Celebrating the pioneers shaping the future of mobility', 'mobility-trailblazers'); ?></p>
    </div>
    
    <div class="mt-winners-grid">
        <?php 
        $rank = 1;
        foreach ($winners as $winner) : 
            $candidate = get_post($winner->candidate_id);
            if (!$candidate) continue;
            
            $organization = get_post_meta($candidate->ID, '_mt_organization', true);
            $position = get_post_meta($candidate->ID, '_mt_position', true);
            $categories = wp_get_post_terms($candidate->ID, 'mt_award_category');
            
            // Rank class
            $rank_class = '';
            if ($rank === 1) $rank_class = 'gold';
            elseif ($rank === 2) $rank_class = 'silver';
            elseif ($rank === 3) $rank_class = 'bronze';
        ?>
            <div class="mt-winner-card <?php echo esc_attr($rank_class); ?>">
                <div class="mt-winner-rank"><?php echo esc_html($rank); ?></div>
                
                <?php if (has_post_thumbnail($candidate->ID)) : ?>
                    <?php echo get_the_post_thumbnail($candidate->ID, 'medium', ['class' => 'mt-winner-photo']); ?>
                <?php else : ?>
                    <div class="mt-winner-photo mt-no-photo">
                        <span class="dashicons dashicons-awards"></span>
                    </div>
                <?php endif; ?>
                
                <h3 class="mt-winner-name"><?php echo esc_html($candidate->post_title); ?></h3>
                
                <?php if ($organization || $position) : ?>
                    <div class="mt-winner-meta">
                        <?php if ($position) : ?>
                            <span><?php echo esc_html($position); ?></span>
                        <?php endif; ?>
                        <?php if ($organization && $position) : ?>
                            <br>
                        <?php endif; ?>
                        <?php if ($organization) : ?>
                            <span><?php echo esc_html($organization); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($categories)) : ?>
                    <div class="mt-winner-category">
                        <?php echo esc_html($categories[0]->name); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($show_scores) : ?>
                    <div class="mt-winner-score">
                        <span class="mt-score-label"><?php _e('Average Score', 'mobility-trailblazers'); ?></span>
                        <span class="mt-score-value"><?php echo number_format($winner->avg_score, 1); ?>/10</span>
                    </div>
                <?php endif; ?>
                
                <?php if ($candidate->post_excerpt) : ?>
                    <div class="mt-winner-excerpt">
                        <?php echo wp_kses_post($candidate->post_excerpt); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php 
            $rank++;
        endforeach; 
        ?>
    </div>
</div>
</div><!-- .mt-root --> 