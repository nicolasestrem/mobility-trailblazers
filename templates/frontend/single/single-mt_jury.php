<?php
/**
 * Single Jury Member Profile Template
 *
 * @package MobilityTrailblazers
 * @version 2.4.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) : the_post();
    $jury_id = get_the_ID();
    $user_id = get_post_meta($jury_id, '_mt_user_id', true);
    $user = get_user_by('id', $user_id);
    
    // Get jury member meta data
    $organization = get_post_meta($jury_id, '_mt_organization', true) ?: '';
    $position = get_post_meta($jury_id, '_mt_position', true) ?: '';
    $bio = get_post_meta($jury_id, '_mt_bio', true) ?: '';
    $expertise = get_post_meta($jury_id, '_mt_expertise', true) ?: '';
    $linkedin = get_post_meta($jury_id, '_mt_linkedin', true) ?: '';
    $website = get_post_meta($jury_id, '_mt_website', true) ?: '';
    $display_name = get_post_meta($jury_id, '_mt_display_name', true) ?: get_the_title();
    
    // Get evaluation statistics
    global $wpdb;
    $table_evaluations = $wpdb->prefix . 'mt_evaluations';
    $stats = $wpdb->get_row($wpdb->prepare("
        SELECT 
            COUNT(DISTINCT candidate_id) as evaluated_count,
            COUNT(CASE WHEN status = 'submitted' THEN 1 END) as submitted_count,
            COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_count,
            AVG((courage_score + innovation_score + implementation_score + relevance_score + visibility_score) / 5) as avg_score
        FROM $table_evaluations 
        WHERE jury_member_id = %d
    ", $jury_id));
    
    // Get assigned candidates count
    $table_assignments = $wpdb->prefix . 'mt_jury_assignments';
    $assigned_count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM $table_assignments 
        WHERE jury_member_id = %d
    ", $jury_id));
?>

<style>
/* Jury Member Profile Styles */
.mt-jury-profile {
    min-height: 100vh;
    background: #f8f9fa;
}

.mt-jury-hero {
    position: relative;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 80px 0 60px;
    overflow: hidden;
}

.mt-jury-hero-pattern {
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

.mt-jury-hero-content {
    position: relative;
    z-index: 1;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.mt-jury-header {
    display: flex;
    align-items: center;
    gap: 50px;
    flex-wrap: wrap;
}

.mt-jury-photo-wrapper {
    position: relative;
}

.mt-jury-photo {
    width: 200px;
    height: 200px;
    border-radius: 50%;
    overflow: hidden;
    border: 6px solid rgba(255,255,255,0.3);
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    background: white;
}

.mt-jury-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.mt-jury-photo-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

.mt-jury-photo-placeholder .dashicons {
    font-size: 60px;
    color: #8b92a8;
}

.mt-jury-badge {
    position: absolute;
    bottom: 10px;
    right: 10px;
    width: 50px;
    height: 50px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.mt-jury-badge .dashicons {
    font-size: 28px;
    color: #f59e0b;
}

.mt-jury-info {
    flex: 1;
    color: white;
}

.mt-jury-name {
    font-size: 3rem;
    font-weight: 700;
    margin: 0 0 10px;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
}

.mt-jury-title {
    font-size: 1.3rem;
    opacity: 0.95;
    margin-bottom: 20px;
    font-weight: 300;
}

.mt-jury-role-badge {
    display: inline-block;
    padding: 8px 20px;
    background: rgba(255,255,255,0.2);
    border: 2px solid rgba(255,255,255,0.3);
    border-radius: 25px;
    font-weight: 600;
    backdrop-filter: blur(10px);
    margin-bottom: 20px;
}

.mt-jury-social-links {
    display: flex;
    gap: 15px;
}

.mt-jury-social-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: rgba(255,255,255,0.2);
    color: white;
    text-decoration: none;
    border-radius: 25px;
    font-weight: 500;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.mt-jury-social-link:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-3px);
}

/* Content Section */
.mt-jury-content {
    background: white;
    margin-top: -30px;
    border-radius: 30px 30px 0 0;
    position: relative;
    z-index: 2;
    padding: 60px 0;
}

.mt-jury-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Stats Cards */
.mt-jury-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 25px;
    margin-bottom: 50px;
}

.mt-stat-card {
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.07);
    text-align: center;
    border-top: 4px solid;
    transition: all 0.3s ease;
}

.mt-stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.12);
}

.mt-stat-card:nth-child(1) { border-top-color: #3b82f6; }
.mt-stat-card:nth-child(2) { border-top-color: #10b981; }
.mt-stat-card:nth-child(3) { border-top-color: #f59e0b; }
.mt-stat-card:nth-child(4) { border-top-color: #8b5cf6; }

.mt-stat-icon {
    width: 60px;
    height: 60px;
    margin: 0 auto 15px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}

.mt-stat-card:nth-child(1) .mt-stat-icon { background: #dbeafe; color: #3b82f6; }
.mt-stat-card:nth-child(2) .mt-stat-icon { background: #d1fae5; color: #10b981; }
.mt-stat-card:nth-child(3) .mt-stat-icon { background: #fef3c7; color: #f59e0b; }
.mt-stat-card:nth-child(4) .mt-stat-icon { background: #ede9fe; color: #8b5cf6; }

.mt-stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 5px;
}

.mt-stat-label {
    font-size: 0.95rem;
    color: #6b7280;
    font-weight: 500;
}

/* Bio Section */
.mt-jury-bio-section {
    background: #f8f9fa;
    border-radius: 16px;
    padding: 40px;
    margin-bottom: 40px;
}

.mt-section-title {
    font-size: 1.8rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.mt-section-title:before {
    content: '';
    width: 4px;
    height: 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 2px;
}

.mt-bio-content {
    color: #4b5563;
    line-height: 1.8;
    font-size: 1.05rem;
}

/* Expertise Section */
.mt-expertise-section {
    margin-bottom: 40px;
}

.mt-expertise-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 20px;
}

.mt-expertise-tag {
    padding: 10px 20px;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 25px;
    color: #4b5563;
    font-weight: 500;
    transition: all 0.3s ease;
}

.mt-expertise-tag:hover {
    border-color: #667eea;
    color: #667eea;
    transform: translateY(-2px);
}

/* Action Buttons */
.mt-jury-actions {
    display: flex;
    gap: 20px;
    margin-top: 40px;
}

.mt-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 15px 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-decoration: none;
    border-radius: 30px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.mt-action-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
}

.mt-action-btn.secondary {
    background: white;
    color: #4b5563;
    border: 2px solid #e5e7eb;
}

.mt-action-btn.secondary:hover {
    border-color: #667eea;
    color: #667eea;
}

/* Responsive Design */
@media (max-width: 968px) {
    .mt-jury-header {
        flex-direction: column;
        text-align: center;
    }
    
    .mt-jury-name {
        font-size: 2.5rem;
    }
    
    .mt-jury-stats {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
}

@media (max-width: 640px) {
    .mt-jury-photo {
        width: 150px;
        height: 150px;
    }
    
    .mt-jury-name {
        font-size: 2rem;
    }
    
    .mt-jury-actions {
        flex-direction: column;
    }
    
    .mt-action-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="mt-jury-profile">
    <!-- Hero Section -->
    <div class="mt-jury-hero">
        <div class="mt-jury-hero-pattern"></div>
        <div class="mt-jury-hero-content">
            <div class="mt-jury-header">
                <div class="mt-jury-photo-wrapper">
                    <div class="mt-jury-photo">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('medium', ['class' => 'mt-jury-avatar']); ?>
                        <?php elseif ($user && get_avatar($user->ID)) : ?>
                            <?php echo get_avatar($user->ID, 200); ?>
                        <?php else : ?>
                            <div class="mt-jury-photo-placeholder">
                                <span class="dashicons dashicons-admin-users"></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-jury-badge">
                        <span class="dashicons dashicons-star-filled"></span>
                    </div>
                </div>
                
                <div class="mt-jury-info">
                    <h1 class="mt-jury-name"><?php echo esc_html($display_name); ?></h1>
                    
                    <?php if ($position || $organization) : ?>
                        <div class="mt-jury-title">
                            <?php if ($position) echo esc_html($position); ?>
                            <?php if ($position && $organization) echo ' • '; ?>
                            <?php if ($organization) echo esc_html($organization); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-jury-role-badge">
                        🏆 Jury-Mitglied 2025
                    </div>
                    
                    <div class="mt-jury-social-links">
                        <?php if ($linkedin) : ?>
                            <a href="<?php echo esc_url($linkedin); ?>" target="_blank" class="mt-jury-social-link">
                                <span class="dashicons dashicons-linkedin"></span>
                                LinkedIn
                            </a>
                        <?php endif; ?>
                        <?php if ($website) : ?>
                            <a href="<?php echo esc_url($website); ?>" target="_blank" class="mt-jury-social-link">
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
    <div class="mt-jury-content">
        <div class="mt-jury-container">
            <!-- Statistics -->
            <div class="mt-jury-stats">
                <div class="mt-stat-card">
                    <div class="mt-stat-icon">📋</div>
                    <div class="mt-stat-value"><?php echo intval($assigned_count); ?></div>
                    <div class="mt-stat-label"><?php _e('Assigned Candidates', 'mobility-trailblazers'); ?></div>
                </div>
                
                <div class="mt-stat-card">
                    <div class="mt-stat-icon">✅</div>
                    <div class="mt-stat-value"><?php echo intval($stats->submitted_count); ?></div>
                    <div class="mt-stat-label"><?php _e('Submitted Evaluations', 'mobility-trailblazers'); ?></div>
                </div>
                
                <div class="mt-stat-card">
                    <div class="mt-stat-icon">📝</div>
                    <div class="mt-stat-value"><?php echo intval($stats->draft_count); ?></div>
                    <div class="mt-stat-label"><?php _e('Drafts', 'mobility-trailblazers'); ?></div>
                </div>
                
                <div class="mt-stat-card">
                    <div class="mt-stat-icon">⭐</div>
                    <div class="mt-stat-value"><?php echo $stats->avg_score ? number_format($stats->avg_score, 1) : '—'; ?></div>
                    <div class="mt-stat-label"><?php _e('Average Score', 'mobility-trailblazers'); ?></div>
                </div>
            </div>
            
            <?php if ($bio) : ?>
                <div class="mt-jury-bio-section">
                    <h2 class="mt-section-title"><?php _e('About Me', 'mobility-trailblazers'); ?></h2>
                    <div class="mt-bio-content">
                        <?php echo wp_kses_post(wpautop($bio)); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($expertise) : ?>
                <div class="mt-expertise-section">
                    <h2 class="mt-section-title">Expertise & Schwerpunkte</h2>
                    <div class="mt-expertise-tags">
                        <?php 
                        $expertise_areas = array_map('trim', explode(',', $expertise));
                        foreach ($expertise_areas as $area) : ?>
                            <span class="mt-expertise-tag"><?php echo esc_html($area); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (current_user_can('manage_options')) : ?>
                <div class="mt-jury-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=mt-jury-management&action=edit&jury_id=' . $jury_id)); ?>" 
                       class="mt-action-btn">
                        <span class="dashicons dashicons-edit"></span>
                        Profil bearbeiten
                    </a>
                    <a href="<?php echo esc_url(home_url('/jury-dashboard/')); ?>" 
                       class="mt-action-btn secondary">
                        <span class="dashicons dashicons-dashboard"></span>
                        Zum Dashboard
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php endwhile;

get_footer(); ?>