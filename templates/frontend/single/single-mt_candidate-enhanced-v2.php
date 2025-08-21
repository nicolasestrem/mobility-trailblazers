<?php
/**
 * Enhanced Single Candidate Template v2
 * With automatic German section formatting
 *
 * @package MobilityTrailblazers
 * @since 2.5.26
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue the enhanced styles
wp_enqueue_style('mt-candidate-enhanced-v2', MT_PLUGIN_URL . 'assets/css/candidate-enhanced-v2.css', [], MT_VERSION);
// Add the override CSS with maximum priority to force clean layout
wp_enqueue_style('mt-candidate-profile-override', MT_PLUGIN_URL . 'assets/css/candidate-profile-override.css', ['mt-candidate-enhanced-v2'], MT_VERSION . '.2');

get_header();

// Get candidate data
if (have_posts()) : 
    while (have_posts()) : the_post();
    
    $candidate_id = get_the_ID();
    
    // Try to get data from new candidates table first
    global $wpdb;
    $candidates_table = $wpdb->prefix . 'mt_candidates';
    $candidate_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$candidates_table} WHERE post_id = %d",
        $candidate_id
    ));
    
    // Decode description sections if from new table
    $description_sections = null;
    if ($candidate_data && !empty($candidate_data->description_sections)) {
        $description_sections = json_decode($candidate_data->description_sections, true);
    }
    
    // Fallback to post meta
    $organization = $candidate_data ? $candidate_data->organization : get_post_meta($candidate_id, '_mt_organization', true);
    $position = $candidate_data ? $candidate_data->position : get_post_meta($candidate_id, '_mt_position', true);
    $display_name = get_the_title();
    $linkedin = $candidate_data ? $candidate_data->linkedin_url : get_post_meta($candidate_id, '_mt_linkedin_url', true);
    $website = $candidate_data ? $candidate_data->website_url : get_post_meta($candidate_id, '_mt_website_url', true);
    
    // Get category from meta field instead of taxonomy
    $category_meta = get_post_meta($candidate_id, '_mt_category_type', true);
    $categories = $category_meta ? array((object)array('name' => $category_meta)) : array();
    
    // Get overview (Überblick) - prioritize meta field (from editor) over database table
    $overview = get_post_meta($candidate_id, '_mt_overview', true);
    if (empty($overview) && $description_sections && !empty($description_sections['ueberblick'])) {
        $overview = $description_sections['ueberblick'];
    }
    
    
    // Parse evaluation criteria - prioritize meta fields over database
    $parsed_criteria = [];
    
    // First check if we have the combined criteria field from editor
    $eval_criteria_meta = get_post_meta($candidate_id, '_mt_evaluation_criteria', true);
    
    if ($eval_criteria_meta && !empty(trim($eval_criteria_meta))) {
        // Parse the meta field for sections - handle both ** and <strong> formats
        $sections = [
            'courage' => ['pattern' => '/(?:<strong>|\\*\\*)Mut\s*(?:&amp;|&)\s*Pioniergeist:(?:<\/strong>|\\*\\*)(?:<br>|<br\/>|\s)*(.+?)(?=<strong>|\\*\\*|$)/is', 'label' => 'Mut & Pioniergeist'],
            'innovation' => ['pattern' => '/(?:<strong>|\\*\\*)Innovationsgrad:(?:[^<]*<\/strong>|\\*\\*)(?:<br>|<br\/>|\s)*(.+?)(?=<strong>|\\*\\*|$)/is', 'label' => 'Innovationsgrad'],
            'implementation' => ['pattern' => '/(?:<strong>|\\*\\*)Umsetzungskraft\s*(?:&amp;|&)\s*Wirkung:(?:<\/strong>|\\*\\*)(?:<br>|<br\/>|\s)*(.+?)(?=<strong>|\\*\\*|$)/is', 'label' => 'Umsetzungskraft & Wirkung'],
            'relevance' => ['pattern' => '/(?:<strong>|\\*\\*)Relevanz\s*für\s*die\s*Mobilitätswende:(?:<\/strong>|\\*\\*)(?:<br>|<br\/>|\s)*(.+?)(?=<strong>|\\*\\*|$)/is', 'label' => 'Relevanz für die Mobilitätswende'],
            'visibility' => ['pattern' => '/(?:<strong>|\\*\\*)Vorbildfunktion\s*(?:&amp;|&)\s*Sichtbarkeit:(?:<\/strong>|\\*\\*)(?:<br>|<br\/>|\s)*(.+?)(?=<strong>|\\*\\*|$)/is', 'label' => 'Vorbildfunktion & Sichtbarkeit']
        ];
        
        foreach ($sections as $key => $section) {
            if (preg_match($section['pattern'], $eval_criteria_meta, $matches)) {
                // Clean up the content - remove <br> tags and trim
                $content = str_replace(['<br>', '<br/>', '<br />'], "\n", $matches[1]);
                $content = trim(strip_tags($content));
                
                $parsed_criteria[$key] = [
                    'label' => $section['label'],
                    'content' => $content
                ];
            }
        }
    } elseif ($description_sections) {
        // Fallback to database table sections
        $criteria_mapping = [
            'mut_pioniergeist' => [
                'key' => 'courage',
                'label' => 'Mut & Pioniergeist',
                'content' => $description_sections['mut_pioniergeist'] ?? ''
            ],
            'innovationsgrad' => [
                'key' => 'innovation',
                'label' => 'Innovationsgrad',
                'content' => $description_sections['innovationsgrad'] ?? ''
            ],
            'umsetzungskraft_wirkung' => [
                'key' => 'implementation',
                'label' => 'Umsetzungskraft & Wirkung',
                'content' => $description_sections['umsetzungskraft_wirkung'] ?? ''
            ],
            'relevanz_mobilitaetswende' => [
                'key' => 'relevance',
                'label' => 'Relevanz für die Mobilitätswende',
                'content' => $description_sections['relevanz_mobilitaetswende'] ?? ''
            ],
            'vorbild_sichtbarkeit' => [
                'key' => 'visibility',
                'label' => 'Vorbildfunktion & Sichtbarkeit',
                'content' => $description_sections['vorbild_sichtbarkeit'] ?? ''
            ]
        ];
        
        foreach ($criteria_mapping as $section_key => $criteria) {
            if (!empty($criteria['content'])) {
                $parsed_criteria[$criteria['key']] = [
                    'label' => $criteria['label'],
                    'content' => nl2br($criteria['content']) // Preserve line breaks
                ];
            }
        }
    }
    
    // Get featured image
    $featured_image = '';
    if (has_post_thumbnail()) {
        $featured_image = get_the_post_thumbnail_url($candidate_id, 'large');
    } elseif ($candidate_data && $candidate_data->photo_attachment_id) {
        $featured_image = wp_get_attachment_url($candidate_data->photo_attachment_id);
    }
?>

<div class="mt-enhanced-candidate-profile">
    <!-- Hero Section -->
    <section class="mt-candidate-hero">
        <div class="mt-hero-pattern"></div>
        <div class="mt-hero-container">
            <div class="mt-hero-content">
                <!-- Photo Frame -->
                <?php if ($featured_image) : ?>
                    <div class="mt-hero-photo-frame">
                        <div class="mt-hero-photo">
                            <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr($display_name); ?>">
                        </div>
                    </div>
                <?php else : ?>
                    <div class="mt-hero-photo-frame">
                        <div class="mt-hero-photo mt-photo-placeholder">
                            <i class="dashicons dashicons-businessperson"></i>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Candidate Info -->
                <div class="mt-hero-info">
                    <h1 class="mt-hero-name"><?php echo esc_html($display_name); ?></h1>
                    
                    <?php if ($position || $organization) : ?>
                        <div class="mt-hero-title">
                            <?php if ($position) : ?>
                                <span class="mt-position"><?php echo esc_html($position); ?></span>
                            <?php endif; ?>
                            <?php if ($position && $organization) : ?>
                                <span class="mt-separator">•</span>
                            <?php endif; ?>
                            <?php if ($organization) : ?>
                                <span class="mt-organization"><?php echo esc_html($organization); ?></span>
                            <?php endif; ?>
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
            <div class="mt-content-wrapper">
                <div class="mt-content-main">
                
                <!-- Überblick / Overview Section -->
                <?php if ($overview) : ?>
                    <section class="mt-content-section mt-overview">
                        <div class="mt-section-header">
                            <i class="dashicons dashicons-lightbulb"></i>
                            <h2><?php _e('Überblick', 'mobility-trailblazers'); ?></h2>
                        </div>
                        <div class="mt-section-content">
                            <?php echo wp_kses_post(wpautop($overview)); ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Evaluation Criteria with Auto-formatted German Sections -->
                <?php if (!empty($parsed_criteria)) : ?>
                    <section class="mt-content-section mt-evaluation-criteria">
                        <div class="mt-section-header">
                            <i class="dashicons dashicons-analytics"></i>
                            <h2><?php _e('Evaluation Criteria', 'mobility-trailblazers'); ?></h2>
                        </div>
                        
                        <!-- Display as formatted grid cards -->
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
                                        <?php echo wp_kses_post(wpautop($criterion['content'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
                
                </div>
                
                <!-- Sidebar with Quick Facts and Navigation -->
                <aside class="mt-content-sidebar">
                    <!-- Quick Facts Widget -->
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

                    <!-- Navigation Widget -->
                    <div class="mt-sidebar-widget mt-navigation">
                        <h3 class="mt-widget-title">
                            <i class="dashicons dashicons-networking"></i>
                            <?php _e('More Candidates', 'mobility-trailblazers'); ?>
                        </h3>
                        <div class="mt-nav-links">
                            <?php
                            $prev_post = get_previous_post(true, '', 'mt_candidate_category');
                            $next_post = get_next_post(true, '', 'mt_candidate_category');
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
        </div>
    </section>
</div>

<?php
    endwhile;
endif;

get_footer();
?>
