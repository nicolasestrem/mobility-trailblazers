<?php
/**
 * Enhanced Single Candidate Template v2.4.0
 * 
 * Modern design with hero section, criteria cards, and enhanced UI
 *
 * @package MobilityTrailblazers
 * @since 2.4.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) : the_post();
    $candidate_id = get_the_ID();
    $organization = get_post_meta($candidate_id, '_mt_organization', true);
    $position = get_post_meta($candidate_id, '_mt_position', true);
    $display_name = get_post_meta($candidate_id, '_mt_display_name', true) ?: get_the_title();
    $overview = get_post_meta($candidate_id, '_mt_overview', true);
    $eval_criteria = get_post_meta($candidate_id, '_mt_evaluation_criteria', true);
    $linkedin = get_post_meta($candidate_id, '_mt_linkedin_url', true);
    $website = get_post_meta($candidate_id, '_mt_website_url', true);
    
    // Get category from meta field instead of taxonomy
    $category_meta = get_post_meta($candidate_id, '_mt_category_type', true);
    $categories = $category_meta ? array((object)array('name' => $category_meta)) : array();
    
    // Parse evaluation criteria into structured format
    $parsed_criteria = [];
    if ($eval_criteria) {
        $criteria_fields = [
            'courage' => __('Mut & Pioniergeist', 'mobility-trailblazers'),
            'innovation' => __('Innovationsgrad', 'mobility-trailblazers'),
            'implementation' => __('Umsetzungskraft & Wirkung', 'mobility-trailblazers'),
            'relevance' => __('Relevanz für Mobilitätswende', 'mobility-trailblazers'),
            'visibility' => __('Vorbildfunktion & Sichtbarkeit', 'mobility-trailblazers')
        ];
        
        foreach ($criteria_fields as $key => $label) {
            $value = get_post_meta($candidate_id, '_mt_criterion_' . $key, true);
            if ($value) {
                $parsed_criteria[$key] = [
                    'label' => $label,
                    'content' => $value
                ];
            }
        }
    }
?>

<div class="mt-enhanced-candidate-profile">
    <!-- Hero Section with Gradient Background -->
    <section class="mt-candidate-hero">
        <div class="mt-hero-pattern"></div>
        <div class="mt-hero-container">
            <div class="mt-hero-content">
                <!-- Floating Photo Frame -->
                <div class="mt-photo-frame">
                    <div class="mt-photo-border">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('large', [
                                'class' => 'mt-candidate-hero-photo',
                                'alt' => esc_attr($display_name)
                            ]); ?>
                        <?php else : ?>
                            <div class="mt-photo-placeholder">
                                <i class="dashicons dashicons-businessman"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-photo-glow"></div>
                </div>
                
                <!-- Candidate Information -->
                <div class="mt-hero-info">
                    <h1 class="mt-hero-name"><?php echo esc_html($display_name); ?></h1>
                    
                    <?php if ($position || $organization) : ?>
                        <div class="mt-hero-title">
                            <?php if ($position) : ?>
                                <span class="mt-position"><?php echo esc_html($position); ?></span>
                            <?php endif; ?>
                            <?php if ($position && $organization) : ?>
                                <span class="mt-separator">bei</span>
                            <?php endif; ?>
                            <?php if ($organization) : ?>
                                <span class="mt-organization"><?php echo esc_html($organization); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Category Badges -->
                    <?php if (!empty($categories)) : ?>
                        <div class="mt-hero-categories">
                            <?php foreach ($categories as $category) : ?>
                                <span class="mt-category-badge">
                                    <i class="dashicons dashicons-awards"></i>
                                    <?php echo esc_html($category->name); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Social Links -->
                    <?php if ($linkedin || $website) : ?>
                        <div class="mt-hero-social">
                            <?php if ($linkedin) : ?>
                                <a href="<?php echo esc_url($linkedin); ?>" target="_blank" rel="noopener" class="mt-social-link linkedin">
                                    <i class="dashicons dashicons-linkedin"></i>
                                    <span><?php _e('LinkedIn', 'mobility-trailblazers'); ?></span>
                                </a>
                            <?php endif; ?>
                            <?php if ($website) : ?>
                                <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener" class="mt-social-link website">
                                    <i class="dashicons dashicons-admin-links"></i>
                                    <span><?php _e('Website', 'mobility-trailblazers'); ?></span>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content Section -->
    <section class="mt-candidate-content">
        <div class="mt-content-container">
            <div class="mt-content-main">
                <!-- Innovation Summary / Description -->
                <?php 
                // First try to get the full content from post_content
                $full_description = get_the_content();
                // Fall back to overview if post_content is empty
                $description_content = !empty($full_description) ? $full_description : $overview;
                
                if ($description_content) : ?>
                    <section class="mt-content-section mt-innovation-summary">
                        <div class="mt-section-header">
                            <i class="dashicons dashicons-lightbulb"></i>
                            <h2><?php _e('Description', 'mobility-trailblazers'); ?></h2>
                        </div>
                        <div class="mt-section-content">
                            <?php 
                            // Apply content filters if using post_content
                            if (!empty($full_description)) {
                                echo apply_filters('the_content', $full_description);
                            } else {
                                echo wp_kses_post($overview);
                            }
                            ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Evaluation Criteria Cards -->
                <?php if (!empty($parsed_criteria)) : ?>
                    <section class="mt-content-section mt-evaluation-criteria">
                        <div class="mt-section-header">
                            <i class="dashicons dashicons-analytics"></i>
                            <h2><?php _e('Evaluation Criteria', 'mobility-trailblazers'); ?></h2>
                        </div>
                        <div class="mt-criteria-grid">
                            <?php 
                            $criterion_icons = [
                                'courage' => 'dashicons-superhero',
                                'innovation' => 'dashicons-lightbulb',
                                'implementation' => 'dashicons-performance',
                                'relevance' => 'dashicons-chart-line',
                                'visibility' => 'dashicons-visibility'
                            ];
                            $criterion_colors = [
                                'courage' => '#ff6b35',
                                'innovation' => '#1e88e5',
                                'implementation' => '#43a047',
                                'relevance' => '#8e24aa',
                                'visibility' => '#fb8c00'
                            ];
                            
                            foreach ($parsed_criteria as $key => $criterion) : 
                                $icon = $criterion_icons[$key] ?? 'dashicons-star-filled';
                                $color = $criterion_colors[$key] ?? '#667eea';
                            ?>
                                <div class="mt-criterion-card" style="border-left-color: <?php echo esc_attr($color); ?>">
                                    <div class="mt-criterion-header">
                                        <div class="mt-criterion-icon" style="color: <?php echo esc_attr($color); ?>">
                                            <i class="dashicons <?php echo esc_attr($icon); ?>"></i>
                                        </div>
                                        <h3 class="mt-criterion-title"><?php echo esc_html($criterion['label']); ?></h3>
                                    </div>
                                    <div class="mt-criterion-content">
                                        <?php echo wp_kses_post($criterion['content']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Fallback for unstructured criteria -->
                <?php if (empty($parsed_criteria) && $eval_criteria) : ?>
                    <section class="mt-content-section mt-evaluation-criteria">
                        <div class="mt-section-header">
                            <i class="dashicons dashicons-analytics"></i>
                            <h2><?php _e('Evaluation Criteria', 'mobility-trailblazers'); ?></h2>
                        </div>
                        <div class="mt-section-content">
                            <?php echo wp_kses_post($eval_criteria); ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Additional Comments -->
                <?php if (!$overview && empty($parsed_criteria) && !$eval_criteria) : ?>
                    <div class="mt-placeholder-notice">
                        <div class="mt-notice-icon">
                            <i class="dashicons dashicons-info"></i>
                        </div>
                        <div class="mt-notice-content">
                            <h3><?php _e('Profile Coming Soon', 'mobility-trailblazers'); ?></h3>
                            <p><?php _e('The detailed profile information for this candidate is being prepared and will be available soon.', 'mobility-trailblazers'); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar with Quick Facts -->
            <aside class="mt-content-sidebar">
                <!-- Quick Facts -->
                <div class="mt-sidebar-widget mt-quick-facts">
                    <h3 class="mt-widget-title">
                        <i class="dashicons dashicons-admin-tools"></i>
                        <?php _e('Quick Facts', 'mobility-trailblazers'); ?>
                    </h3>
                    <div class="mt-fact-list">
                        <?php if (!empty($categories)) : ?>
                            <div class="mt-fact-item">
                                <span class="mt-fact-label"><?php _e('Category:', 'mobility-trailblazers'); ?></span>
                                <span class="mt-fact-value">
                                    <?php echo esc_html(implode(', ', wp_list_pluck($categories, 'name'))); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($organization) : ?>
                            <div class="mt-fact-item">
                                <span class="mt-fact-label"><?php _e('Organization:', 'mobility-trailblazers'); ?></span>
                                <span class="mt-fact-value"><?php echo esc_html($organization); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($position) : ?>
                            <div class="mt-fact-item">
                                <span class="mt-fact-label"><?php _e('Position:', 'mobility-trailblazers'); ?></span>
                                <span class="mt-fact-value"><?php echo esc_html($position); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-fact-item">
                            <span class="mt-fact-label"><?php _e('Award Year:', 'mobility-trailblazers'); ?></span>
                            <span class="mt-fact-value">2025</span>
                        </div>
                    </div>
                </div>

                <!-- Evaluation CTA removed 2025-08-20 -->
                <!-- Button was showing for all jury members regardless of assignment status -->
                <!-- Evaluations should only be started from the jury dashboard where assignments are properly checked -->

                <!-- Navigation Widget -->
                <div class="mt-sidebar-widget mt-navigation">
                    <h3 class="mt-widget-title">
                        <i class="dashicons dashicons-networking"></i>
                        <?php _e('More Candidates', 'mobility-trailblazers'); ?>
                    </h3>
                    <div class="mt-nav-links">
                        <?php
                        $prev_post = get_previous_post(true, '', 'mt_award_category');
                        $next_post = get_next_post(true, '', 'mt_award_category');
                        ?>
                        
                        <?php if ($prev_post) : ?>
                            <a href="<?php echo get_permalink($prev_post); ?>" class="mt-nav-link mt-nav-prev">
                                <i class="dashicons dashicons-arrow-left-alt2"></i>
                                <div class="mt-nav-text">
                                    <span class="mt-nav-label"><?php _e('Previous', 'mobility-trailblazers'); ?></span>
                                    <span class="mt-nav-title"><?php echo esc_html(get_the_title($prev_post)); ?></span>
                                </div>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($next_post) : ?>
                            <a href="<?php echo get_permalink($next_post); ?>" class="mt-nav-link mt-nav-next">
                                <div class="mt-nav-text">
                                    <span class="mt-nav-label"><?php _e('Next', 'mobility-trailblazers'); ?></span>
                                    <span class="mt-nav-title"><?php echo esc_html(get_the_title($next_post)); ?></span>
                                </div>
                                <i class="dashicons dashicons-arrow-right-alt2"></i>
                            </a>
                        <?php endif; ?>
                        
                        <a href="<?php echo esc_url(home_url('/candidates/')); ?>" class="mt-nav-link mt-nav-all">
                            <i class="dashicons dashicons-list-view"></i>
                            <span><?php _e('View All Candidates', 'mobility-trailblazers'); ?></span>
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </section>
</div>

<?php endwhile;

get_footer(); ?>
