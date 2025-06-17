<?php
/**
 * Assignment Management Template - Clean Version
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get statistics
global $wpdb;

// Count total candidates
$total_candidates = wp_count_posts('mt_candidate')->publish;

// Count total jury members
$total_jury = wp_count_posts('mt_jury')->publish;

// Count assigned candidates with proper query
$assigned_candidates = $wpdb->get_var("
    SELECT COUNT(DISTINCT p.ID) 
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
    WHERE p.post_type = 'mt_candidate' 
    AND p.post_status = 'publish'
    AND pm.meta_key = '_mt_assigned_jury_members' 
    AND pm.meta_value != ''
    AND pm.meta_value != 'a:0:{}'
    AND pm.meta_value IS NOT NULL
");

// Get unassigned candidates
$unassigned_candidates = $total_candidates - $assigned_candidates;
?>

<div class="wrap">
    <h1><?php _e('Assignment Management', 'mobility-trailblazers'); ?></h1>
    
    <!-- Statistics -->
    <div class="mt-stats-row">
        <div class="mt-stat-box">
            <h3><?php _e('Total Candidates', 'mobility-trailblazers'); ?></h3>
            <p class="mt-stat-number"><?php echo $total_candidates; ?></p>
        </div>
        
        <div class="mt-stat-box">
            <h3><?php _e('Total Jury Members', 'mobility-trailblazers'); ?></h3>
            <p class="mt-stat-number"><?php echo $total_jury; ?></p>
        </div>
        
        <div class="mt-stat-box">
            <h3><?php _e('Assigned Candidates', 'mobility-trailblazers'); ?></h3>
            <p class="mt-stat-number"><?php echo $assigned_candidates; ?></p>
        </div>
        
        <div class="mt-stat-box">
            <h3><?php _e('Unassigned Candidates', 'mobility-trailblazers'); ?></h3>
            <p class="mt-stat-number"><?php echo $unassigned_candidates; ?></p>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="mt-action-bar">
        <button type="button" class="button button-primary" id="mt-auto-assign-btn">
            <span class="dashicons dashicons-randomize"></span>
            <?php _e('Auto-Assign', 'mobility-trailblazers'); ?>
        </button>
        
        <button type="button" class="button" id="mt-clear-assignments-btn">
            <span class="dashicons dashicons-dismiss"></span>
            <?php _e('Clear All Assignments', 'mobility-trailblazers'); ?>
        </button>
        
        <button type="button" class="button" id="mt-export-assignments-btn">
            <span class="dashicons dashicons-download"></span>
            <?php _e('Export Assignments', 'mobility-trailblazers'); ?>
        </button>
        
        <button type="button" class="button" id="mt-manual-assignment-btn">
            <span class="dashicons dashicons-admin-users"></span>
            <?php _e('Manual Assignment', 'mobility-trailblazers'); ?>
        </button>
    </div>
    
    <!-- Search and Filter -->
    <div class="mt-search-filter-row">
        <div class="mt-search-box">
            <input type="text" id="mt-candidate-search" placeholder="<?php _e('Search candidates...', 'mobility-trailblazers'); ?>" />
        </div>
        
        <div class="mt-filter-box">
            <select id="mt-category-filter">
                <option value=""><?php _e('All Categories', 'mobility-trailblazers'); ?></option>
                <?php
                $categories = get_terms(array(
                    'taxonomy' => 'mt_category',
                    'hide_empty' => false,
                ));
                
                foreach ($categories as $category) {
                    echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
                }
                ?>
            </select>
        </div>
        
        <div class="mt-filter-box">
            <select id="mt-status-filter">
                <option value=""><?php _e('All Statuses', 'mobility-trailblazers'); ?></option>
                <option value="assigned"><?php _e('Assigned', 'mobility-trailblazers'); ?></option>
                <option value="unassigned"><?php _e('Unassigned', 'mobility-trailblazers'); ?></option>
            </select>
        </div>
    </div>
    
    <!-- Assignment Interface -->
    <div class="mt-assignment-container">
        <!-- Candidates Column -->
        <div class="mt-assignment-column">
            <h2><?php _e('Candidates', 'mobility-trailblazers'); ?></h2>
            <div class="mt-selection-info">
                <span id="mt-selected-count">0</span> <?php _e('selected', 'mobility-trailblazers'); ?>
                <button type="button" class="button button-link" id="mt-clear-selection"><?php _e('Clear', 'mobility-trailblazers'); ?></button>
            </div>
            
            <div id="mt-candidates-list" class="mt-draggable-list">
                <?php
                // Get candidates
                $candidates = get_posts(array(
                    'post_type' => 'mt_candidate',
                    'posts_per_page' => -1,
                    'orderby' => 'title',
                    'order' => 'ASC',
                    'post_status' => 'publish',
                ));
                
                foreach ($candidates as $candidate) {
                    $company = get_post_meta($candidate->ID, '_mt_company', true);
                    $category_terms = get_the_terms($candidate->ID, 'mt_category');
                    $category = $category_terms && !is_wp_error($category_terms) ? $category_terms[0]->name : '';
                    $assigned_jury = mt_get_assigned_jury_members($candidate->ID);
                    $is_assigned = !empty($assigned_jury);
                    ?>
                    <div class="mt-draggable-item mt-candidate-item <?php echo $is_assigned ? 'assigned' : ''; ?>" 
                         data-candidate-id="<?php echo $candidate->ID; ?>"
                         data-category-id="<?php echo $category_terms ? $category_terms[0]->term_id : ''; ?>">
                        <div class="mt-item-header">
                            <input type="checkbox" class="mt-candidate-checkbox" value="<?php echo $candidate->ID; ?>" />
                            <h4><?php echo esc_html($candidate->post_title); ?></h4>
                            <?php if ($is_assigned) : ?>
                                <span class="mt-assigned-badge"><?php _e('Assigned', 'mobility-trailblazers'); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="mt-item-meta">
                            <?php if ($company) : ?>
                                <span class="mt-meta-item">
                                    <span class="dashicons dashicons-building"></span>
                                    <?php echo esc_html($company); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($category) : ?>
                                <span class="mt-meta-item">
                                    <span class="dashicons dashicons-category"></span>
                                    <?php echo esc_html($category); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($is_assigned) : ?>
                                <span class="mt-meta-item">
                                    <span class="dashicons dashicons-groups"></span>
                                    <?php echo sprintf(_n('%d jury member', '%d jury members', count($assigned_jury), 'mobility-trailblazers'), count($assigned_jury)); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
        
        <!-- Jury Members Column -->
        <div class="mt-assignment-column">
            <h2><?php _e('Jury Members', 'mobility-trailblazers'); ?></h2>
            <div class="mt-jury-search">
                <input type="text" id="mt-jury-search" placeholder="<?php _e('Search jury members...', 'mobility-trailblazers'); ?>" />
            </div>
            
            <div id="mt-jury-list" class="mt-droppable-list">
                <?php
                // Get jury members
                $jury_members = get_posts(array(
                    'post_type' => 'mt_jury_member',
                    'posts_per_page' => -1,
                    'orderby' => 'title',
                    'order' => 'ASC',
                    'post_status' => 'publish',
                ));
                
                foreach ($jury_members as $jury_member) {
                    $organization = get_post_meta($jury_member->ID, '_mt_organization', true);
                    $role = get_post_meta($jury_member->ID, '_mt_jury_role', true);
                    $assigned_candidates = mt_get_assigned_candidates($jury_member->ID);
                    $expertise_areas = get_post_meta($jury_member->ID, '_mt_expertise_areas', true);
                    ?>
                    <div class="mt-droppable-item mt-jury-item" data-jury-id="<?php echo $jury_member->ID; ?>">
                        <div class="mt-item-header">
                            <h4><?php echo esc_html($jury_member->post_title); ?></h4>
                            <?php if ($role && $role !== 'member') : ?>
                                <span class="mt-role-badge mt-role-<?php echo esc_attr($role); ?>">
                                    <?php echo $role === 'president' ? __('President', 'mobility-trailblazers') : __('Vice President', 'mobility-trailblazers'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="mt-item-meta">
                            <?php if ($organization) : ?>
                                <span class="mt-meta-item">
                                    <span class="dashicons dashicons-building"></span>
                                    <?php echo esc_html($organization); ?>
                                </span>
                            <?php endif; ?>
                            <span class="mt-meta-item">
                                <span class="dashicons dashicons-portfolio"></span>
                                <?php echo sprintf(__('%d assigned', 'mobility-trailblazers'), count($assigned_candidates)); ?>
                            </span>
                        </div>
                        <?php if (is_array($expertise_areas) && !empty($expertise_areas)) : ?>
                            <div class="mt-expertise-tags">
                                <?php foreach (array_slice($expertise_areas, 0, 3) as $expertise) : ?>
                                    <span class="mt-expertise-tag"><?php echo esc_html($expertise); ?></span>
                                <?php endforeach; ?>
                                <?php if (count($expertise_areas) > 3) : ?>
                                    <span class="mt-expertise-tag">+<?php echo (count($expertise_areas) - 3); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Drop zone for candidates -->
                        <div class="mt-drop-zone" data-jury-id="<?php echo $jury_member->ID; ?>">
                            <p><?php _e('Drop candidates here', 'mobility-trailblazers'); ?></p>
                        </div>
                        
                        <!-- Assigned candidates list -->
                        <div class="mt-assigned-candidates" data-jury-id="<?php echo $jury_member->ID; ?>">
                            <?php
                            if (!empty($assigned_candidates)) {
                                foreach ($assigned_candidates as $candidate) {
                                    // Handle both objects and IDs
                                    $candidate_id = is_object($candidate) ? $candidate->ID : $candidate;
                                    $candidate_title = is_object($candidate) ? $candidate->post_title : get_the_title($candidate_id);
                                    
                                    if ($candidate_id && $candidate_title) {
                                        ?>
                                        <div class="mt-assigned-candidate" data-candidate-id="<?php echo $candidate_id; ?>">
                                            <span><?php echo esc_html($candidate_title); ?></span>
                                            <button type="button" class="mt-remove-assignment" data-candidate-id="<?php echo $candidate_id; ?>" data-jury-id="<?php echo $jury_member->ID; ?>">
                                                <span class="dashicons dashicons-no"></span>
                                            </button>
                                        </div>
                                        <?php
                                    }
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
    
    <!-- Auto-Assignment Modal -->
    <div id="mt-auto-assign-modal" class="mt-modal" style="display: none;">
        <div class="mt-modal-content">
            <h2><?php _e('Auto-Assignment Settings', 'mobility-trailblazers'); ?></h2>
            
            <div class="mt-form-group">
                <label for="mt-assignment-algorithm"><?php _e('Assignment Algorithm', 'mobility-trailblazers'); ?></label>
                <select id="mt-assignment-algorithm">
                    <option value="balanced"><?php _e('Balanced Distribution', 'mobility-trailblazers'); ?></option>
                    <option value="random"><?php _e('Random Assignment', 'mobility-trailblazers'); ?></option>
                    <option value="expertise"><?php _e('Expertise-Based', 'mobility-trailblazers'); ?></option>
                    <option value="category"><?php _e('Category-Based', 'mobility-trailblazers'); ?></option>
                </select>
            </div>
            
            <div class="mt-form-group">
                <label for="mt-candidates-per-jury"><?php _e('Candidates per Jury Member', 'mobility-trailblazers'); ?></label>
                <input type="number" id="mt-candidates-per-jury" value="20" min="1" max="50" />
            </div>
            
            <div class="mt-form-group">
                <label>
                    <input type="checkbox" id="mt-preserve-existing" checked />
                    <?php _e('Preserve existing assignments', 'mobility-trailblazers'); ?>
                </label>
            </div>
            
            <div class="mt-modal-actions">
                <button type="button" class="button button-primary" id="mt-confirm-auto-assign">
                    <?php _e('Start Auto-Assignment', 'mobility-trailblazers'); ?>
                </button>
                <button type="button" class="button mt-modal-close">
                    <?php _e('Cancel', 'mobility-trailblazers'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Manual Assignment Modal -->
    <div id="mt-manual-assign-modal" class="mt-modal" style="display: none;">
        <div class="mt-modal-content">
            <h2><?php _e('Manual Assignment', 'mobility-trailblazers'); ?></h2>
            
            <div class="mt-form-group">
                <label for="mt-manual-candidate"><?php _e('Select Candidate', 'mobility-trailblazers'); ?></label>
                <select id="mt-manual-candidate">
                    <option value=""><?php _e('Choose a candidate...', 'mobility-trailblazers'); ?></option>
                    <?php
                    foreach ($candidates as $candidate) {
                        echo '<option value="' . $candidate->ID . '">' . esc_html($candidate->post_title) . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div class="mt-form-group">
                <label for="mt-manual-jury"><?php _e('Assign to Jury Members', 'mobility-trailblazers'); ?></label>
                <select id="mt-manual-jury" multiple size="10">
                    <?php
                    foreach ($jury_members as $jury_member) {
                        echo '<option value="' . $jury_member->ID . '">' . esc_html($jury_member->post_title) . '</option>';
                    }
                    ?>
                </select>
                <p class="description"><?php _e('Hold Ctrl/Cmd to select multiple jury members', 'mobility-trailblazers'); ?></p>
            </div>
            
            <div class="mt-modal-actions">
                <button type="button" class="button button-primary" id="mt-confirm-manual-assign">
                    <?php _e('Assign', 'mobility-trailblazers'); ?>
                </button>
                <button type="button" class="button mt-modal-close">
                    <?php _e('Cancel', 'mobility-trailblazers'); ?>
                </button>
            </div>
        </div>
    </div>
</div> 