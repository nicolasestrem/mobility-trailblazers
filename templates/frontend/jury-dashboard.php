<?php
/**
 * Jury Dashboard Template
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if viewing evaluation form
if (isset($_GET['evaluate']) && is_numeric($_GET['evaluate'])) {
    $candidate_id = intval($_GET['evaluate']);
    
    // Verify assignment
    $assignment_repo = new \MobilityTrailblazers\Repositories\MT_Assignment_Repository();
    $has_assignment = $assignment_repo->exists($jury_member->ID, $candidate_id);
    
    if ($has_assignment) {
        include MT_PLUGIN_DIR . 'templates/frontend/jury-evaluation-form.php';
        return;
    } else {
        echo '<div class="mt-notice mt-notice-error">' . 
             __('You are not assigned to evaluate this candidate.', 'mobility-trailblazers') . 
             '</div>';
        echo '<a href="' . esc_url(remove_query_arg('evaluate')) . '" class="mt-btn mt-btn-secondary">' . 
             __('Back to Dashboard', 'mobility-trailblazers') . 
             '</a>';
        return;
    }
}

// Get evaluation service and data
$evaluation_service = new \MobilityTrailblazers\Services\MT_Evaluation_Service();
$assignment_repo = new \MobilityTrailblazers\Repositories\MT_Assignment_Repository();

// Get progress data
$progress = $evaluation_service->get_jury_progress($jury_member->ID);
$assignments = $assignment_repo->get_by_jury_member($jury_member->ID);

// Get user info
$current_user = wp_get_current_user();

// Get dashboard customization settings
$dashboard_settings = get_option('mt_dashboard_settings', [
    'header_style' => 'gradient',
    'primary_color' => '#667eea',
    'secondary_color' => '#764ba2',
    'progress_bar_style' => 'rounded',
    'show_welcome_message' => 1,
    'show_progress_bar' => 1,
    'show_stats_cards' => 1,
    'show_search_filter' => 1,
    'show_rankings' => 1,
    'card_layout' => 'grid',
    'intro_text' => ''
]);

// Apply custom styles
$header_class = 'mt-dashboard-header mt-header-' . $dashboard_settings['header_style'];
$progress_class = 'mt-progress-bar mt-progress-' . $dashboard_settings['progress_bar_style'];
$layout_class = 'mt-candidates-' . $dashboard_settings['card_layout'];
?>

<div class="mt-jury-dashboard">
    <div class="<?php echo esc_attr($header_class); ?>" 
         style="<?php echo $dashboard_settings['header_style'] === 'solid' ? 'background-color: ' . esc_attr($dashboard_settings['primary_color']) : ''; ?>">
        
        <?php if ($dashboard_settings['show_welcome_message']) : ?>
            <h1><?php printf(__('Welcome, %s', 'mobility-trailblazers'), esc_html($current_user->display_name)); ?></h1>
        <?php endif; ?>
        
        <?php if (!empty($dashboard_settings['intro_text'])) : ?>
            <p><?php echo wp_kses_post($dashboard_settings['intro_text']); ?></p>
        <?php else : ?>
            <p><?php _e('Review and evaluate your assigned candidates for the Mobility Trailblazers Awards', 'mobility-trailblazers'); ?></p>
        <?php endif; ?>
        
        <?php if ($dashboard_settings['show_progress_bar'] && $progress['total'] > 0) : ?>
        <div class="<?php echo esc_attr($progress_class); ?>">
            <div class="mt-progress-fill" style="width: <?php echo esc_attr($progress['completion_rate']); ?>%">
                <span class="mt-progress-text"><?php echo esc_html($progress['completion_rate']); ?>%</span>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if ($dashboard_settings['show_stats_cards']) : ?>
    <div class="mt-stats-grid">
        <div class="mt-stat-card">
            <p class="mt-stat-number"><?php echo esc_html($progress['total']); ?></p>
            <p class="mt-stat-label"><?php _e('Total Assigned', 'mobility-trailblazers'); ?></p>
        </div>
        <div class="mt-stat-card">
            <p class="mt-stat-number"><?php echo esc_html($progress['completed']); ?></p>
            <p class="mt-stat-label"><?php _e('Completed', 'mobility-trailblazers'); ?></p>
        </div>
        <div class="mt-stat-card">
            <p class="mt-stat-number"><?php echo esc_html($progress['drafts']); ?></p>
            <p class="mt-stat-label"><?php _e('In Draft', 'mobility-trailblazers'); ?></p>
        </div>
        <div class="mt-stat-card">
            <p class="mt-stat-number"><?php echo esc_html($progress['pending']); ?></p>
            <p class="mt-stat-label"><?php _e('Pending', 'mobility-trailblazers'); ?></p>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($dashboard_settings['show_search_filter']) : ?>
    <div class="mt-search-filters">
        <div class="mt-search-box">
            <input type="text" 
                   class="mt-search-input" 
                   id="mt-candidate-search" 
                   placeholder="<?php esc_attr_e('Search candidates...', 'mobility-trailblazers'); ?>">
            
            <select class="mt-filter-select" id="mt-status-filter">
                <option value=""><?php _e('All Statuses', 'mobility-trailblazers'); ?></option>
                <option value="pending"><?php _e('Pending', 'mobility-trailblazers'); ?></option>
                <option value="draft"><?php _e('Draft', 'mobility-trailblazers'); ?></option>
                <option value="completed"><?php _e('Completed', 'mobility-trailblazers'); ?></option>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <!-- Add Rankings Section -->
    <?php if ($dashboard_settings['show_rankings'] ?? true) : ?>
        <div id="mt-rankings-container" class="mt-rankings-container">
            <?php 
            // Get initial rankings
            $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
            $rankings = $evaluation_repo->get_ranked_candidates_for_jury($jury_member->ID, 10);
            include MT_PLUGIN_DIR . 'templates/frontend/partials/jury-rankings.php';
            ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($assignments)) : ?>
        <div class="mt-candidates-list <?php echo esc_attr($layout_class); ?>" id="mt-candidates-list">
            <?php foreach ($assignments as $assignment) : 
                $candidate = get_post($assignment->candidate_id);
                if (!$candidate) continue;
                
                // Get evaluation status
                $evaluation = null;
                foreach ($progress['candidates'] as $cand) {
                    if ($cand['id'] == $assignment->candidate_id) {
                        $evaluation = $cand;
                        break;
                    }
                }
                
                $status = $evaluation ? $evaluation['status'] : 'pending';
                $organization = get_post_meta($candidate->ID, '_mt_organization', true);
                $categories = wp_get_post_terms($candidate->ID, 'mt_award_category');
            ?>
                <div class="mt-candidate-card" data-status="<?php echo esc_attr($status); ?>" data-name="<?php echo esc_attr(strtolower($candidate->post_title)); ?>">
                    <div class="mt-candidate-header">
                        <h3 class="mt-candidate-name"><?php echo esc_html($candidate->post_title); ?></h3>
                        <?php if ($organization) : ?>
                            <p class="mt-candidate-org"><?php echo esc_html($organization); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-candidate-body">
                        <?php if (!empty($categories)) : ?>
                            <div class="mt-candidate-category">
                                <?php echo esc_html($categories[0]->name); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-evaluation-status">
                            <?php
                            $status_class = 'mt-status-' . $status;
                            $status_text = __('Not Started', 'mobility-trailblazers');
                            
                            if ($status === 'draft') {
                                $status_text = __('Draft Saved', 'mobility-trailblazers');
                            } elseif ($status === 'completed') {
                                $status_text = __('Completed', 'mobility-trailblazers');
                            }
                            ?>
                            <span class="mt-status-badge <?php echo esc_attr($status_class); ?>">
                                <?php echo esc_html($status_text); ?>
                            </span>
                        </div>
                        
                        <a href="#" 
                           class="mt-evaluate-btn" 
                           data-candidate-id="<?php echo esc_attr($candidate->ID); ?>">
                            <?php
                            if ($status === 'completed') {
                                _e('View/Edit Evaluation', 'mobility-trailblazers');
                            } elseif ($status === 'draft') {
                                _e('Continue Evaluation', 'mobility-trailblazers');
                            } else {
                                _e('Start Evaluation', 'mobility-trailblazers');
                            }
                            ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <div class="mt-notice">
            <p><?php _e('No candidates have been assigned to you yet.', 'mobility-trailblazers'); ?></p>
        </div>
    <?php endif; ?>
</div>

<!-- Evaluation Modal -->
<div id="mt-evaluation-modal" class="mt-modal" style="display: none;">
    <div class="mt-modal-content">
        <div class="mt-modal-header">
            <h2 id="mt-modal-title"><?php _e('Evaluate Candidate', 'mobility-trailblazers'); ?></h2>
            <button type="button" class="mt-modal-close">&times;</button>
        </div>
        <div class="mt-modal-body">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Search functionality
    $('#mt-candidate-search').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();
        filterCandidates();
    });
    
    // Status filter
    $('#mt-status-filter').on('change', function() {
        filterCandidates();
    });
    
    function filterCandidates() {
        var searchTerm = $('#mt-candidate-search').val().toLowerCase();
        var statusFilter = $('#mt-status-filter').val();
        
        $('.mt-candidate-card').each(function() {
            var $card = $(this);
            var name = $card.data('name');
            var status = $card.data('status');
            
            var matchesSearch = searchTerm === '' || name.indexOf(searchTerm) !== -1;
            var matchesStatus = statusFilter === '' || status === statusFilter;
            
            if (matchesSearch && matchesStatus) {
                $card.show();
            } else {
                $card.hide();
            }
        });
    }
    
    // Evaluation button click
    $('.mt-evaluate-btn').on('click', function(e) {
        e.preventDefault();
        var candidateId = $(this).data('candidate-id');
        window.location.href = '?evaluate=' + candidateId;
    });
});
</script> 