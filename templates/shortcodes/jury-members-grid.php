<?php
/**
 * Jury Members Grid Template
 *
 * @package MobilityTrailblazers
 * 
 * Available variables:
 * $query - WP_Query object with jury members
 * $atts - Shortcode attributes
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="mt-jury-members-grid-wrapper">
    <?php if ($query->have_posts()): ?>
        <div class="mt-jury-grid columns-<?php echo esc_attr($atts['columns']); ?>">
            <?php while ($query->have_posts()): $query->the_post(); 
                $member_id = get_the_ID();
                $photo = get_post_meta($member_id, '_mt_photo_url', true);
                $position = get_post_meta($member_id, '_mt_position', true);
                $company = get_post_meta($member_id, '_mt_company', true);
                $expertise = get_post_meta($member_id, '_mt_expertise_areas', true);
                $bio = get_post_meta($member_id, '_mt_bio', true);
                $linkedin = get_post_meta($member_id, '_mt_linkedin_url', true);
                $role = get_post_meta($member_id, '_mt_jury_role', true);
                
                // Get role label
                $role_label = '';
                switch ($role) {
                    case 'president':
                        $role_label = __('President', 'mobility-trailblazers');
                        break;
                    case 'vice_president':
                        $role_label = __('Vice President', 'mobility-trailblazers');
                        break;
                    default:
                        $role_label = __('Jury Member', 'mobility-trailblazers');
                }
            ?>
                <div class="mt-jury-member-card" data-role="<?php echo esc_attr($role); ?>">
                    <?php if ($role === 'president' || $role === 'vice_president'): ?>
                        <div class="mt-jury-role-badge"><?php echo esc_html($role_label); ?></div>
                    <?php endif; ?>
                    
                    <div class="mt-jury-member-photo">
                        <?php if ($photo): ?>
                            <img src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                        <?php else: ?>
                            <div class="mt-no-photo">
                                <i class="dashicons dashicons-businessperson"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-jury-member-info">
                        <h3 class="mt-jury-member-name"><?php the_title(); ?></h3>
                        
                        <?php if ($position): ?>
                            <p class="mt-jury-member-position"><?php echo esc_html($position); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($company): ?>
                            <p class="mt-jury-member-company"><?php echo esc_html($company); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($expertise && is_array($expertise)): ?>
                            <div class="mt-jury-expertise">
                                <?php foreach ($expertise as $area): ?>
                                    <span class="mt-expertise-tag"><?php echo esc_html($area); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($atts['show_bio'] === 'yes' && $bio): ?>
                            <div class="mt-jury-member-bio">
                                <p><?php echo wp_trim_words(esc_html($bio), 30); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($linkedin): ?>
                            <div class="mt-jury-member-social">
                                <a href="<?php echo esc_url($linkedin); ?>" target="_blank" rel="noopener noreferrer" class="mt-linkedin-link">
                                    <i class="dashicons dashicons-linkedin"></i>
                                    <?php _e('LinkedIn', 'mobility-trailblazers'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="mt-no-jury-members">
            <p><?php _e('No jury members found.', 'mobility-trailblazers'); ?></p>
        </div>
    <?php endif; ?>
    
    <?php wp_reset_postdata(); ?>
</div> 