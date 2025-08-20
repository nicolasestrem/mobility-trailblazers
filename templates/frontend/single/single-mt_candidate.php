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
    $organization = get_post_meta($candidate_id, '_mt_organization', true) ?: '';
    $position = get_post_meta($candidate_id, '_mt_position', true) ?: '';
    $display_name = get_post_meta($candidate_id, '_mt_candidate_name', true) ?: get_the_title();
    
    // Get content from all possible sources in priority order
    $full_description = get_the_content();
    if (empty($full_description)) {
        // Check the custom editor's overview field
        $full_description = get_post_meta($candidate_id, '_mt_overview', true);
    }
    if (empty($full_description)) {
        // Fallback to legacy description field
        $full_description = get_post_meta($candidate_id, '_mt_description_full', true);
    }
    $linkedin = get_post_meta($candidate_id, '_mt_linkedin_url', true) ?: '';
    $website = get_post_meta($candidate_id, '_mt_website_url', true) ?: '';
    
    // Get individual evaluation criteria
    $eval_courage = get_post_meta($candidate_id, '_mt_evaluation_courage', true) ?: '';
    $eval_innovation = get_post_meta($candidate_id, '_mt_evaluation_innovation', true) ?: '';
    $eval_implementation = get_post_meta($candidate_id, '_mt_evaluation_implementation', true) ?: '';
    $eval_relevance = get_post_meta($candidate_id, '_mt_evaluation_relevance', true) ?: '';
    $eval_visibility = get_post_meta($candidate_id, '_mt_evaluation_visibility', true) ?: '';
    $categories = wp_get_post_terms($candidate_id, 'mt_award_category');
    $category_slug = !empty($categories) ? $categories[0]->slug : 'default';
    
    // Build criteria sections from individual fields
    $criteria_sections = [];
    if ($eval_courage) {
        $criteria_sections['mut'] = $eval_courage;
    }
    if ($eval_innovation) {
        $criteria_sections['innovation'] = $eval_innovation;
    }
    if ($eval_implementation) {
        $criteria_sections['umsetzung'] = $eval_implementation;
    }
    if ($eval_relevance) {
        $criteria_sections['relevanz'] = $eval_relevance;
    }
    if ($eval_visibility) {
        $criteria_sections['vorbild'] = $eval_visibility;
    }
    
    // If no individual criteria, check for combined criteria field from editor
    if (empty($criteria_sections)) {
        $combined_criteria = get_post_meta($candidate_id, '_mt_evaluation_criteria', true);
        if ($combined_criteria) {
            // Parse the combined criteria text to extract sections
            // This maintains compatibility with the custom editor
            $criteria_sections['combined'] = $combined_criteria;
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
    padding: 50px 0 40px; /* Reduced from 80px 0 60px */
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    overflow: hidden;
    max-height: 400px; /* Add maximum height constraint */
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

.mt-content-subheading {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1f2937;
    margin-top: 30px;
    margin-bottom: 15px;
    padding-bottom: 8px;
    border-bottom: 2px solid #e5e7eb;
}

.mt-content-subheading:first-child {
    margin-top: 0;
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
                    

                    
                    <div class="mt-social-links">
                        <?php if ($linkedin) : ?>
                            <a href="<?php echo esc_url($linkedin); ?>" target="_blank" class="mt-social-link linkedin">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                                </svg>
                                LinkedIn
                            </a>
                        <?php endif; ?>
                        <?php if ($website) : ?>
                            <a href="<?php echo esc_url($website); ?>" target="_blank" class="mt-social-link website">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 0c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm1 16.057v-3.057h2.994c-.059 1.143-.212 2.24-.456 3.279-.823-.12-1.674-.188-2.538-.222zm1.957 2.162c-.499 1.33-1.159 2.497-1.957 3.456v-3.62c.666.028 1.319.081 1.957.164zm-1.957-7.219v-3.015c.868-.034 1.721-.103 2.548-.224.238 1.027.389 2.111.446 3.239h-2.994zm0-5.014v-3.661c.806.969 1.471 2.15 1.971 3.496-.642.084-1.3.137-1.971.165zm2.703-3.267c1.237.496 2.354 1.228 3.29 2.146-.642.234-1.311.442-2.019.607-.344-.992-.775-1.91-1.271-2.753zm-7.241 13.56c-.244-1.039-.398-2.136-.456-3.279h2.994v3.057c-.865.034-1.714.102-2.538.222zm2.538 1.776v3.62c-.798-.959-1.458-2.126-1.957-3.456.638-.083 1.291-.136 1.957-.164zm-2.994-7.055c.057-1.128.207-2.212.446-3.239.827.121 1.68.19 2.548.224v3.015h-2.994zm1.024-5.179c.5-1.346 1.165-2.527 1.97-3.496v3.661c-.671-.028-1.329-.081-1.97-.165zm-2.005-.35c-.708-.165-1.377-.373-2.018-.607.937-.918 2.053-1.65 3.29-2.146-.496.844-.927 1.762-1.272 2.753zm-.549 1.918c-.264 1.151-.434 2.36-.492 3.611h-3.933c.165-1.658.739-3.197 1.617-4.518.88.361 1.816.67 2.808.907zm.009 9.262c-.988.236-1.92.542-2.797.9-.89-1.328-1.471-2.879-1.637-4.551h3.934c.058 1.265.231 2.488.5 3.651zm.553 1.917c.342.976.768 1.881 1.257 2.712-1.223-.49-2.326-1.211-3.256-2.115.636-.229 1.299-.435 1.999-.597zm9.924 0c.7.163 1.362.367 1.999.597-.931.903-2.034 1.625-3.257 2.116.489-.832.915-1.737 1.258-2.713zm.553-1.917c.27-1.163.442-2.386.501-3.651h3.934c-.167 1.672-.748 3.223-1.638 4.551-.877-.358-1.81-.664-2.797-.9zm.501-5.651c-.058-1.251-.229-2.46-.492-3.611.992-.237 1.929-.546 2.809-.907.877 1.321 1.451 2.86 1.616 4.518h-3.933z"/>
                                </svg>
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
                <?php 
                // Display the full content if available
                if ($full_description) : 
                    // Check if content has sections (contains headers like "Mut & Pioniergeist")
                    if (strpos($full_description, 'Mut &') !== false || 
                        strpos($full_description, 'Innovation') !== false ||
                        strpos($full_description, 'Umsetzung') !== false) :
                        // Content has multiple sections, display as formatted content
                        ?>
                        <div class="mt-overview-section">
                            <h2 class="mt-section-heading">Beschreibung</h2>
                            <div class="mt-section-content">
                                <?php 
                                // Parse and format the content with proper sections
                                $formatted_content = $full_description;
                                
                                // Convert section headers to proper HTML headers
                                $formatted_content = preg_replace('/^(Überblick|Mut & Pioniergeist|Innovationsgrad|Umsetzungskraft & Wirkung|Relevanz für die Mobilitätswende|Vorbildfunktion & Sichtbarkeit)$/m', '<h3 class="mt-content-subheading">$1</h3>', $formatted_content);
                                
                                echo wp_kses_post(wpautop($formatted_content)); 
                                ?>
                            </div>
                        </div>
                    <?php else : ?>
                        <!-- Simple content without sections -->
                        <div class="mt-overview-section">
                            <h2 class="mt-section-heading">Überblick</h2>
                            <div class="mt-section-content">
                                <?php echo wp_kses_post(wpautop($full_description)); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if (!empty($criteria_sections)) : ?>
                    <h2 class="mt-section-heading"><?php _e('Evaluation Criteria', 'mobility-trailblazers'); ?></h2>
                    <?php if (isset($criteria_sections['combined'])) : ?>
                        <!-- Display combined criteria from editor -->
                        <div class="mt-overview-section">
                            <div class="mt-section-content">
                                <?php echo wp_kses_post(wpautop($criteria_sections['combined'])); ?>
                            </div>
                        </div>
                    <?php else : ?>
                        <!-- Display individual criteria cards -->
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
                    <?php endif; ?>
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
                
                <?php 
                // Check if current user is a jury member assigned to this candidate
                if (current_user_can('mt_submit_evaluations')) : 
                    global $wpdb;
                    $user_id = get_current_user_id();
                    $is_assigned = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}mt_jury_assignments 
                        WHERE jury_member_id = %d AND candidate_id = %d",
                        $user_id, $candidate_id
                    ));
                    
                    if ($is_assigned) :
                ?>
                    <div class="mt-sidebar-card">
                        <h3 class="mt-sidebar-title"><?php _e('Jury Action', 'mobility-trailblazers'); ?></h3>
                        <a href="<?php echo esc_url(add_query_arg('evaluate', $candidate_id, home_url('/jury-dashboard/'))); ?>" 
                           class="mt-cta-button">
                            <?php _e('Evaluate Candidate', 'mobility-trailblazers'); ?>
                        </a>
                    </div>
                <?php 
                    endif;
                endif; 
                ?>
                
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
