<?php
/**
 * Enhanced Candidates Grid Template with Card Layout
 *
 * @package MobilityTrailblazers
 * @version 2.4.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Template variables from shortcode
$columns = isset($atts['columns']) ? intval($atts['columns']) : 3;
$show_bio = isset($atts['show_bio']) && $atts['show_bio'] === 'yes';
$show_category = isset($atts['show_category']) && $atts['show_category'] === 'yes';
$enable_filter = isset($atts['enable_filter']) && $atts['enable_filter'] === 'yes';
$enable_search = isset($atts['enable_search']) && $atts['enable_search'] === 'yes';

// Get all categories for filter
$all_categories = get_terms([
    'taxonomy' => 'mt_award_category',
    'hide_empty' => true
]);
?>

<style>
/* Enhanced Grid Styles */
.mt-candidates-enhanced-container {
    padding: 40px 0;
}

.mt-grid-controls {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 40px;
    padding: 25px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.mt-search-box {
    flex: 1;
    min-width: 280px;
}

.mt-search-input {
    width: 100%;
    padding: 12px 20px 12px 45px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'%3E%3C/path%3E%3C/svg%3E") no-repeat 15px center;
    background-size: 20px;
}

.mt-search-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.mt-filter-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
}

.mt-filter-label {
    font-weight: 600;
    color: #4b5563;
}

.mt-filter-btn {
    padding: 8px 20px;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 25px;
    color: #4b5563;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.mt-filter-btn:hover {
    border-color: #667eea;
    color: #667eea;
    transform: translateY(-2px);
}

.mt-filter-btn.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: transparent;
}

.mt-candidates-enhanced-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 30px;
    margin-bottom: 40px;
}

.mt-candidate-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0,0,0,0.07);
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    display: flex;
    flex-direction: column;
}

.mt-candidate-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.mt-candidate-card.hidden {
    display: none;
}

.mt-card-image-wrapper {
    position: relative;
    height: 280px;
    overflow: hidden;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.mt-card-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.mt-candidate-card:hover .mt-card-image {
    transform: scale(1.1);
}

.mt-card-image-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

.mt-card-image-placeholder .dashicons {
    font-size: 80px;
    color: #8b92a8;
}

.mt-card-category-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    padding: 6px 15px;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #4b5563;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.mt-card-content {
    padding: 25px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.mt-card-name {
    font-size: 1.4rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 8px;
    line-height: 1.3;
}

.mt-card-title {
    font-size: 0.95rem;
    color: #6b7280;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.mt-card-organization {
    color: #667eea;
    font-weight: 600;
}

.mt-card-bio {
    color: #4b5563;
    line-height: 1.6;
    margin-bottom: 20px;
    flex: 1;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.mt-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

.mt-card-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.mt-card-link:hover {
    gap: 10px;
    color: #764ba2;
}

.mt-card-social {
    display: flex;
    gap: 10px;
}

.mt-card-social a {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
    border-radius: 50%;
    color: #6b7280;
    transition: all 0.3s ease;
}

.mt-card-social a:hover {
    background: #667eea;
    color: white;
    transform: translateY(-3px);
}

/* Loading Animation */
.mt-grid-loading {
    text-align: center;
    padding: 60px;
    color: #6b7280;
}

.mt-loading-spinner {
    width: 50px;
    height: 50px;
    margin: 0 auto 20px;
    border: 3px solid #e5e7eb;
    border-top-color: #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* No Results Message */
.mt-no-results {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.mt-no-results-icon {
    font-size: 60px;
    color: #cbd5e1;
    margin-bottom: 20px;
}

.mt-no-results-text {
    font-size: 1.2rem;
    color: #4b5563;
    margin-bottom: 10px;
}

.mt-no-results-hint {
    color: #9ca3af;
}

/* Responsive Design */
@media (max-width: 768px) {
    .mt-candidates-enhanced-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }
    
    .mt-card-image-wrapper {
        height: 220px;
    }
    
    .mt-grid-controls {
        flex-direction: column;
    }
    
    .mt-search-box {
        min-width: 100%;
    }
}

@media (max-width: 480px) {
    .mt-candidates-enhanced-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="mt-candidates-enhanced-container">
    <?php if ($enable_filter || $enable_search) : ?>
        <div class="mt-grid-controls">
            <?php if ($enable_search) : ?>
                <div class="mt-search-box">
                    <input type="text" 
                           class="mt-search-input" 
                           id="mt-candidate-search" 
                           placeholder="<?php esc_attr_e('Search by name, organization or position...', 'mobility-trailblazers'); ?>">
                </div>
            <?php endif; ?>
            
            <?php if ($enable_filter && !empty($all_categories)) : ?>
                <div class="mt-filter-buttons">
                    <span class="mt-filter-label"><?php _e('Filter:', 'mobility-trailblazers'); ?></span>
                    <button class="mt-filter-btn active" data-filter="all">
                        <?php _e('All', 'mobility-trailblazers'); ?>
                    </button>
                    <?php foreach ($all_categories as $category) : ?>
                        <button class="mt-filter-btn" data-filter="<?php echo esc_attr($category->slug); ?>">
                            <?php echo esc_html($category->name); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="mt-candidates-enhanced-grid" id="mt-candidates-grid">
        <?php if ($candidates->have_posts()) : ?>
            <?php while ($candidates->have_posts()) : $candidates->the_post(); 
                $candidate_id = get_the_ID();
                $organization = get_post_meta($candidate_id, '_mt_organization', true);
                $position = get_post_meta($candidate_id, '_mt_position', true);
                $display_name = get_post_meta($candidate_id, '_mt_display_name', true) ?: get_the_title();
                $linkedin = get_post_meta($candidate_id, '_mt_linkedin_url', true);
                $website = get_post_meta($candidate_id, '_mt_website_url', true);
                $overview = get_post_meta($candidate_id, '_mt_overview', true);
                $categories = wp_get_post_terms($candidate_id, 'mt_award_category');
                $category_classes = array_map(function($cat) { return 'category-' . $cat->slug; }, $categories);
            ?>
                <div class="mt-candidate-card <?php echo esc_attr(implode(' ', $category_classes)); ?>" 
                     data-name="<?php echo esc_attr(strtolower($display_name)); ?>"
                     data-org="<?php echo esc_attr(strtolower($organization)); ?>"
                     data-position="<?php echo esc_attr(strtolower($position)); ?>"
                     onclick="window.location.href='<?php echo esc_url(get_permalink()); ?>'">
                    
                    <div class="mt-card-image-wrapper">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('medium_large', ['class' => 'mt-card-image']); ?>
                        <?php else : ?>
                            <div class="mt-card-image-placeholder">
                                <span class="dashicons dashicons-businessman"></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($show_category && !empty($categories)) : ?>
                            <span class="mt-card-category-badge">
                                <?php echo esc_html($categories[0]->name); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-card-content">
                        <h3 class="mt-card-name"><?php echo esc_html($display_name); ?></h3>
                        
                        <?php if ($position || $organization) : ?>
                            <div class="mt-card-title">
                                <?php if ($position) : ?>
                                    <span><?php echo esc_html($position); ?></span>
                                <?php endif; ?>
                                <?php if ($position && $organization) : ?>
                                    <span>•</span>
                                <?php endif; ?>
                                <?php if ($organization) : ?>
                                    <span class="mt-card-organization"><?php echo esc_html($organization); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($show_bio && $overview) : ?>
                            <div class="mt-card-bio">
                                <?php echo wp_kses_post(wp_trim_words($overview, 30)); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-card-footer">
                            <a href="<?php echo esc_url(get_permalink()); ?>" class="mt-card-link" onclick="event.stopPropagation();">
                                Profil ansehen
                                <span>→</span>
                            </a>
                            
                            <div class="mt-card-social">
                                <?php if ($linkedin) : ?>
                                    <a href="<?php echo esc_url($linkedin); ?>" 
                                       target="_blank" 
                                       rel="noopener"
                                       onclick="event.stopPropagation();"
                                       title="<?php esc_attr_e('LinkedIn', 'mobility-trailblazers'); ?>">
                                        <span class="dashicons dashicons-linkedin"></span>
                                    </a>
                                <?php endif; ?>
                                <?php if ($website) : ?>
                                    <a href="<?php echo esc_url($website); ?>" 
                                       target="_blank" 
                                       rel="noopener"
                                       onclick="event.stopPropagation();"
                                       title="<?php esc_attr_e('Website', 'mobility-trailblazers'); ?>">
                                        <span class="dashicons dashicons-admin-site"></span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else : ?>
            <div class="mt-no-results">
                <div class="mt-no-results-icon">🔍</div>
                <div class="mt-no-results-text"><?php _e('No candidates found', 'mobility-trailblazers'); ?></div>
                <div class="mt-no-results-hint">Versuchen Sie, Ihre Filterkriterien anzupassen</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter functionality
    const filterButtons = document.querySelectorAll('.mt-filter-btn');
    const candidateCards = document.querySelectorAll('.mt-candidate-card');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Update active state
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            const filter = this.getAttribute('data-filter');
            
            // Filter cards
            candidateCards.forEach(card => {
                if (filter === 'all') {
                    card.classList.remove('hidden');
                    setTimeout(() => {
                        card.style.display = 'flex';
                    }, 10);
                } else {
                    if (card.classList.contains('category-' + filter)) {
                        card.classList.remove('hidden');
                        setTimeout(() => {
                            card.style.display = 'flex';
                        }, 10);
                    } else {
                        card.style.display = 'none';
                        setTimeout(() => {
                            card.classList.add('hidden');
                        }, 300);
                    }
                }
            });
        });
    });
    
    // Search functionality
    const searchInput = document.getElementById('mt-candidate-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            candidateCards.forEach(card => {
                const name = card.getAttribute('data-name') || '';
                const org = card.getAttribute('data-org') || '';
                const position = card.getAttribute('data-position') || '';
                
                if (name.includes(searchTerm) || 
                    org.includes(searchTerm) || 
                    position.includes(searchTerm)) {
                    card.classList.remove('hidden');
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                    card.classList.add('hidden');
                }
            });
        });
    }
});
</script>