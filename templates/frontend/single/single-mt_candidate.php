<?php
/**
 * Enhanced Single Candidate Template
 *
 * @package MobilityTrailblazers
 * @version 2.2.0
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
    $personality = get_post_meta($candidate_id, '_mt_personality_motivation', true);
    $linkedin = get_post_meta($candidate_id, '_mt_linkedin', true);
    $website = get_post_meta($candidate_id, '_mt_website', true);
    $categories = wp_get_post_terms($candidate_id, 'mt_award_category');
    $category_slug = !empty($categories) ? $categories[0]->slug : 'default';
    
    // Parse evaluation criteria into sections
    $criteria_sections = [];
    if ($eval_criteria) {
        // Split by common patterns
        $patterns = [
            'mut' => '/Mut & Pioniergeist:(.+?)(?=Innovationsgrad:|Umsetzungskraft:|Relevanz|Vorbildfunktion:|$)/si',
            'innovation' => '/Innovationsgrad:(.+?)(?=Mut & Pioniergeist:|Umsetzungskraft:|Relevanz|Vorbildfunktion:|$)/si',
            'umsetzung' => '/Umsetzungskraft & Wirkung:(.+?)(?=Mut & Pioniergeist:|Innovationsgrad:|Relevanz|Vorbildfunktion:|$)/si',
            'relevanz' => '/Relevanz für die Mobilitätswende:(.+?)(?=Mut & Pioniergeist:|Innovationsgrad:|Umsetzungskraft:|Vorbildfunktion:|$)/si',
            'vorbild' => '/Vorbildfunktion & Sichtbarkeit:(.+?)(?=Mut & Pioniergeist:|Innovationsgrad:|Umsetzungskraft:|Relevanz|$)/si'
        ];
        
        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $eval_criteria, $matches)) {
                $criteria_sections[$key] = trim($matches[1]);
            }
        }
    }
?>

<style>
/* Enhanced Candidate Profile Styles */
.mt-candidate-enhanced {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.mt-hero-section {
    position: relative;
    padding: 80px 0 60px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    overflow: hidden;
}

.mt-hero-section.category-tech {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.mt-hero-section.category-startup {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.mt-hero-section.category-gov {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.mt-hero-pattern {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    opacity: 0.1;
    background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.4'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    animation: float 20s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    33% { transform: translateY(-10px) rotate(1deg); }
    66% { transform: translateY(10px) rotate(-1deg); }
}

.mt-hero-content {
    position: relative;
    z-index: 1;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.mt-profile-header-enhanced {
    display: flex;
    align-items: center;
    gap: 60px;
    flex-wrap: wrap;
}

.mt-photo-frame {
    position: relative;
    width: 280px;
    height: 280px;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    background: white;
    padding: 8px;
    transform: rotate(-2deg);
    transition: transform 0.3s ease;
}

.mt-photo-frame:hover {
    transform: rotate(0deg) scale(1.05);
}

.mt-photo-frame img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 12px;
}

.mt-photo-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    border-radius: 12px;
}

.mt-photo-placeholder .dashicons {
    font-size: 80px;
    color: #8b92a8;
}

.mt-profile-info-enhanced {
    flex: 1;
    color: white;
}

.mt-profile-name-enhanced {
    font-size: 3.5rem;
    font-weight: 700;
    margin: 0 0 10px;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
    line-height: 1.2;
}

.mt-profile-title-enhanced {
    font-size: 1.4rem;
    opacity: 0.95;
    margin-bottom: 20px;
    font-weight: 300;
}

.mt-category-badges {
    display: flex;
    gap: 10px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.mt-category-badge-enhanced {
    display: inline-block;
    padding: 8px 20px;
    background: rgba(255,255,255,0.2);
    border: 2px solid rgba(255,255,255,0.3);
    border-radius: 25px;
    font-size: 0.9rem;
    font-weight: 600;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.mt-category-badge-enhanced:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px);
}

.mt-social-links {
    display: flex;
    gap: 15px;
}

.mt-social-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: rgba(255,255,255,0.2);
    color: white;
    text-decoration: none;
    border-radius: 30px;
    font-weight: 500;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.mt-social-link:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.mt-social-link .dashicons {
    font-size: 20px;
}

/* Content Section */
.mt-content-section {
    background: #f8f9fa;
    padding: 60px 0;
    margin-top: -30px;
    border-radius: 30px 30px 0 0;
    position: relative;
    z-index: 2;
}

.mt-content-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 40px;
}

/* Criteria Cards */
.mt-criteria-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

.mt-criterion-card {
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.07);
    transition: all 0.3s ease;
    border-top: 4px solid;
}

.mt-criterion-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.12);
}

.mt-criterion-card.mut { border-top-color: #f59e0b; }
.mt-criterion-card.innovation { border-top-color: #3b82f6; }
.mt-criterion-card.umsetzung { border-top-color: #10b981; }
.mt-criterion-card.relevanz { border-top-color: #8b5cf6; }
.mt-criterion-card.vorbild { border-top-color: #ec4899; }

.mt-criterion-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 15px;
    font-size: 24px;
}

.mt-criterion-card.mut .mt-criterion-icon { background: #fef3c7; color: #f59e0b; }
.mt-criterion-card.innovation .mt-criterion-icon { background: #dbeafe; color: #3b82f6; }
.mt-criterion-card.umsetzung .mt-criterion-icon { background: #d1fae5; color: #10b981; }
.mt-criterion-card.relevanz .mt-criterion-icon { background: #ede9fe; color: #8b5cf6; }
.mt-criterion-card.vorbild .mt-criterion-icon { background: #fce7f3; color: #ec4899; }

.mt-criterion-title {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 12px;
    color: #1f2937;
}

.mt-criterion-content {
    color: #4b5563;
    line-height: 1.6;
    font-size: 0.95rem;
}

/* Sidebar */
.mt-sidebar {
    position: sticky;
    top: 20px;
}

.mt-sidebar-card {
    background: white;
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.07);
}

.mt-sidebar-title {
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 15px;
    color: #1f2937;
}

.mt-quick-facts {
    list-style: none;
    padding: 0;
    margin: 0;
}

.mt-quick-facts li {
    padding: 10px 0;
    border-bottom: 1px solid #e5e7eb;
    color: #4b5563;
}

.mt-quick-facts li:last-child {
    border-bottom: none;
}

.mt-cta-button {
    display: block;
    width: 100%;
    padding: 15px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-align: center;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.mt-cta-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
}

/* Overview Section */
.mt-overview-section {
    background: white;
    border-radius: 16px;
    padding: 35px;
    margin-bottom: 30px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.07);
}

.mt-section-heading {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 20px;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 10px;
}

.mt-section-heading:before {
    content: '';
    width: 4px;
    height: 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 2px;
}

.mt-section-content {
    color: #4b5563;
    line-height: 1.8;
    font-size: 1.05rem;
}

/* Navigation */
.mt-profile-navigation {
    display: flex;
    justify-content: space-between;
    margin-top: 40px;
    padding-top: 40px;
    border-top: 1px solid #e5e7eb;
}

.mt-nav-link {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 24px;
    background: white;
    color: #4b5563;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.06);
}

.mt-nav-link:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    color: #667eea;
}

.mt-nav-link.prev:hover {
    transform: translateX(-5px);
}

/* Responsive Design */
@media (max-width: 968px) {
    .mt-content-container {
        grid-template-columns: 1fr;
    }
    
    .mt-profile-header-enhanced {
        flex-direction: column;
        text-align: center;
    }
    
    .mt-profile-name-enhanced {
        font-size: 2.5rem;
    }
    
    .mt-criteria-grid {
        grid-template-columns: 1fr;
    }
    
    .mt-sidebar {
        position: static;
    }
}

@media (max-width: 640px) {
    .mt-photo-frame {
        width: 200px;
        height: 200px;
    }
    
    .mt-profile-name-enhanced {
        font-size: 2rem;
    }
    
    .mt-social-links {
        flex-direction: column;
    }
}
</style>

<div class="mt-candidate-enhanced">
    <!-- Hero Section -->
    <div class="mt-hero-section category-<?php echo esc_attr($category_slug); ?>">
        <div class="mt-hero-pattern"></div>
        <div class="mt-hero-content">
            <div class="mt-profile-header-enhanced">
                <div class="mt-photo-frame">
                    <?php if (has_post_thumbnail()) : ?>
                        <?php the_post_thumbnail('large', ['class' => 'mt-candidate-photo']); ?>
                    <?php else : ?>
                        <div class="mt-photo-placeholder">
                            <span class="dashicons dashicons-businessman"></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="mt-profile-info-enhanced">
                    <h1 class="mt-profile-name-enhanced"><?php echo esc_html($display_name); ?></h1>
                    
                    <?php if ($position || $organization) : ?>
                        <div class="mt-profile-title-enhanced">
                            <?php if ($position) echo esc_html($position); ?>
                            <?php if ($position && $organization) echo ' • '; ?>
                            <?php if ($organization) echo esc_html($organization); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($categories)) : ?>
                        <div class="mt-category-badges">
                            <?php foreach ($categories as $category) : ?>
                                <span class="mt-category-badge-enhanced">
                                    <?php echo esc_html($category->name); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-social-links">
                        <?php if ($linkedin) : ?>
                            <a href="<?php echo esc_url($linkedin); ?>" target="_blank" class="mt-social-link">
                                <span class="dashicons dashicons-linkedin"></span>
                                LinkedIn
                            </a>
                        <?php endif; ?>
                        <?php if ($website) : ?>
                            <a href="<?php echo esc_url($website); ?>" target="_blank" class="mt-social-link">
                                <span class="dashicons dashicons-admin-site"></span>
                                Website
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Content Section -->
    <div class="mt-content-section">
        <div class="mt-content-container">
            <div class="mt-main-content">
                <?php if ($overview) : ?>
                    <div class="mt-overview-section">
                        <h2 class="mt-section-heading">Überblick</h2>
                        <div class="mt-section-content">
                            <?php echo wp_kses_post(wpautop($overview)); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($criteria_sections)) : ?>
                    <h2 class="mt-section-heading"><?php _e('Evaluation Criteria', 'mobility-trailblazers'); ?></h2>
                    <div class="mt-criteria-grid">
                        <?php if (isset($criteria_sections['mut'])) : ?>
                            <div class="mt-criterion-card mut">
                                <div class="mt-criterion-icon">🚀</div>
                                <h3 class="mt-criterion-title">Mut & Pioniergeist</h3>
                                <div class="mt-criterion-content">
                                    <?php echo wp_kses_post($criteria_sections['mut']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($criteria_sections['innovation'])) : ?>
                            <div class="mt-criterion-card innovation">
                                <div class="mt-criterion-icon">💡</div>
                                <h3 class="mt-criterion-title">Innovationsgrad</h3>
                                <div class="mt-criterion-content">
                                    <?php echo wp_kses_post($criteria_sections['innovation']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($criteria_sections['umsetzung'])) : ?>
                            <div class="mt-criterion-card umsetzung">
                                <div class="mt-criterion-icon">⚡</div>
                                <h3 class="mt-criterion-title">Umsetzungskraft & Wirkung</h3>
                                <div class="mt-criterion-content">
                                    <?php echo wp_kses_post($criteria_sections['umsetzung']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($criteria_sections['relevanz'])) : ?>
                            <div class="mt-criterion-card relevanz">
                                <div class="mt-criterion-icon">🌍</div>
                                <h3 class="mt-criterion-title">Relevanz für die Mobilitätswende</h3>
                                <div class="mt-criterion-content">
                                    <?php echo wp_kses_post($criteria_sections['relevanz']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($criteria_sections['vorbild'])) : ?>
                            <div class="mt-criterion-card vorbild">
                                <div class="mt-criterion-icon">⭐</div>
                                <h3 class="mt-criterion-title">Vorbildfunktion & Sichtbarkeit</h3>
                                <div class="mt-criterion-content">
                                    <?php echo wp_kses_post($criteria_sections['vorbild']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif ($eval_criteria) : ?>
                    <div class="mt-overview-section">
                        <h2 class="mt-section-heading"><?php _e('Evaluation Criteria', 'mobility-trailblazers'); ?></h2>
                        <div class="mt-section-content">
                            <?php echo wp_kses_post(wpautop($eval_criteria)); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Navigation -->
                <div class="mt-profile-navigation">
                    <?php
                    $prev_post = get_previous_post(true, '', 'mt_award_category');
                    $next_post = get_next_post(true, '', 'mt_award_category');
                    ?>
                    
                    <?php if ($prev_post) : ?>
                        <a href="<?php echo get_permalink($prev_post); ?>" class="mt-nav-link prev">
                            <span class="dashicons dashicons-arrow-left-alt2"></span>
                            <?php _e('Previous Candidate', 'mobility-trailblazers'); ?>
                        </a>
                    <?php else : ?>
                        <span></span>
                    <?php endif; ?>
                    
                    <?php if ($next_post) : ?>
                        <a href="<?php echo get_permalink($next_post); ?>" class="mt-nav-link next">
                            <?php _e('Next Candidate', 'mobility-trailblazers'); ?>
                            <span class="dashicons dashicons-arrow-right-alt2"></span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Sidebar -->
            <aside class="mt-sidebar">
                <div class="mt-sidebar-card">
                    <h3 class="mt-sidebar-title"><?php _e('Quick Facts', 'mobility-trailblazers'); ?></h3>
                    <ul class="mt-quick-facts">
                        <?php if ($organization) : ?>
                            <li><strong><?php _e('Organization:', 'mobility-trailblazers'); ?></strong> <?php echo esc_html($organization); ?></li>
                        <?php endif; ?>
                        <?php if ($position) : ?>
                            <li><strong><?php _e('Position:', 'mobility-trailblazers'); ?></strong> <?php echo esc_html($position); ?></li>
                        <?php endif; ?>
                        <?php if (!empty($categories)) : ?>
                            <li><strong><?php _e('Category:', 'mobility-trailblazers'); ?></strong> <?php echo esc_html($categories[0]->name); ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <?php if (current_user_can('mt_submit_evaluations')) : ?>
                    <div class="mt-sidebar-card">
                        <h3 class="mt-sidebar-title"><?php _e('Jury Action', 'mobility-trailblazers'); ?></h3>
                        <a href="<?php echo esc_url(add_query_arg('evaluate', $candidate_id, home_url('/jury-dashboard/'))); ?>" 
                           class="mt-cta-button">
                            <?php _e('Evaluate Candidate', 'mobility-trailblazers'); ?>
                        </a>
                    </div>
                <?php endif; ?>
                
                <div class="mt-sidebar-card">
                    <h3 class="mt-sidebar-title"><?php _e('Share', 'mobility-trailblazers'); ?></h3>
                    <div style="display: flex; gap: 10px;">
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode($display_name . ' - Mobility Trailblazers'); ?>" 
                           target="_blank" 
                           class="mt-social-link" 
                           style="flex: 1; justify-content: center; padding: 10px;">
                            <span class="dashicons dashicons-twitter"></span>
                        </a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(get_permalink()); ?>" 
                           target="_blank" 
                           class="mt-social-link" 
                           style="flex: 1; justify-content: center; padding: 10px;">
                            <span class="dashicons dashicons-linkedin"></span>
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

<?php endwhile;

get_footer(); ?>
