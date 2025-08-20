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
    
    // Verify candidate exists and is valid
    $candidate = get_post($candidate_id);
    if (!$candidate || $candidate->post_type !== 'mt_candidate') {
        echo '<div class="mt-notice mt-notice-error">' . 
             __('Invalid candidate.', 'mobility-trailblazers') . 
             '</div>';
        echo '<a href="' . esc_url(remove_query_arg('evaluate')) . '" class="mt-btn mt-btn-secondary">' . 
             __('Back to Dashboard', 'mobility-trailblazers') . 
             '</a>';
        return;
    }
    
    // Verify assignment - Skip check for admin users during testing
    $assignment_repo = new \MobilityTrailblazers\Repositories\MT_Assignment_Repository();
    $has_assignment = $assignment_repo->exists($jury_member->ID, $candidate_id);
    
    // Allow admin users to bypass assignment check for testing
    if (current_user_can('manage_options')) {
        $has_assignment = true;
    }
    
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
$header_class = 'mt-dashboard-header mt-header-' . (isset($dashboard_settings['header_style']) ? $dashboard_settings['header_style'] : 'gradient');
$progress_class = 'mt-progress-bar mt-progress-' . (isset($dashboard_settings['progress_bar_style']) ? $dashboard_settings['progress_bar_style'] : 'rounded');
$layout_class = 'mt-candidates-' . (isset($dashboard_settings['card_layout']) ? $dashboard_settings['card_layout'] : 'grid');
?>

<div class="mt-jury-dashboard">
    <?php if ($progress['completion_rate'] == 100) : ?>
        <div class="mt-completion-status-banner">
            <div class="mt-completion-status-content">
                <span class="dashicons dashicons-yes-alt"></span>
                <strong><?php _e('EVALUATION COMPLETE', 'mobility-trailblazers'); ?></strong>
                <span class="mt-completion-subtitle"><?php _e('All assignments finished!', 'mobility-trailblazers'); ?></span>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="<?php echo esc_attr($header_class); ?> <?php echo $progress['completion_rate'] == 100 ? 'mt-header-completed' : ''; ?>" 
         style="<?php echo (isset($dashboard_settings['header_style']) ? $dashboard_settings['header_style'] : 'gradient') === 'solid' ? 'background-color: ' . esc_attr(isset($dashboard_settings['primary_color']) ? $dashboard_settings['primary_color'] : '#0073aa') : ''; ?>">
        
        <?php if (isset($dashboard_settings['show_welcome_message']) ? $dashboard_settings['show_welcome_message'] : true) : ?>
            <h1>
                <?php printf(__('Welcome, %s', 'mobility-trailblazers'), esc_html($current_user->display_name)); ?>
                <?php if ($progress['completion_rate'] == 100) : ?>
                    <span class="mt-completion-icon-header">
                        <span class="dashicons dashicons-awards"></span>
                    </span>
                <?php endif; ?>
            </h1>
        <?php endif; ?>
        
        <?php if (!empty(isset($dashboard_settings['intro_text']) ? $dashboard_settings['intro_text'] : '')) : ?>
            <p><?php echo wp_kses_post($dashboard_settings['intro_text']); ?></p>
        <?php else : ?>
            <p><?php _e('Review and evaluate your assigned candidates for the Mobility Trailblazers Awards', 'mobility-trailblazers'); ?></p>
        <?php endif; ?>
        
        <?php if ((isset($dashboard_settings['show_progress_bar']) ? $dashboard_settings['show_progress_bar'] : true) && $progress['total'] > 0) : ?>
        <div class="<?php echo esc_attr($progress_class); ?> <?php echo $progress['completion_rate'] == 100 ? 'mt-progress-complete' : ''; ?>">
            <div class="mt-progress-fill" style="width: <?php echo esc_attr($progress['completion_rate']); ?>%">
                <span class="mt-progress-text">
                    <?php if ($progress['completion_rate'] == 100) : ?>
                        <span class="dashicons dashicons-yes-alt" style="color: #00a32a; margin-right: 5px;"></span>
                    <?php endif; ?>
                    <?php echo esc_html($progress['completion_rate']); ?>%
                </span>
            </div>
        </div>
        <?php if ($progress['completion_rate'] == 100) : ?>
            <div class="mt-completion-badge mt-completion-enhanced">
                <div class="mt-completion-icon-large">
                    <span class="dashicons dashicons-awards"></span>
                </div>
                <div class="mt-completion-text">
                    <strong><?php _e('Congratulations!', 'mobility-trailblazers'); ?></strong>
                    <p><?php _e('You have completed all evaluations!', 'mobility-trailblazers'); ?></p>
                    <div class="mt-completion-timestamp">
                        <?php _e('Mission accomplished', 'mobility-trailblazers'); ?> ✓
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <?php if (isset($dashboard_settings['show_stats_cards']) ? $dashboard_settings['show_stats_cards'] : true) : ?>
    <div class="mt-stats-grid" style="grid-template-columns: repeat(3, 1fr);">
        <div class="mt-stat-card">
            <p class="mt-stat-number"><?php echo esc_html($progress['total']); ?></p>
            <p class="mt-stat-label"><?php _e('Total Assigned', 'mobility-trailblazers'); ?></p>
        </div>
        <div class="mt-stat-card">
            <p class="mt-stat-number"><?php echo esc_html($progress['completed']); ?></p>
            <p class="mt-stat-label"><?php _e('Completed', 'mobility-trailblazers'); ?></p>
        </div>
        <div class="mt-stat-card">
            <p class="mt-stat-number"><?php echo esc_html($progress['pending']); ?></p>
            <p class="mt-stat-label"><?php _e('Pending', 'mobility-trailblazers'); ?></p>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (isset($dashboard_settings['show_search_filter']) ? $dashboard_settings['show_search_filter'] : true) : ?>
    <div class="mt-search-filters">
        <div class="mt-search-box">
            <input type="text" 
                   class="mt-search-input" 
                   id="mt-candidate-search" 
                   placeholder="<?php esc_attr_e('Search candidates...', 'mobility-trailblazers'); ?>">
            
            <select class="mt-filter-select" id="mt-status-filter">
                <option value=""><?php _e('All Statuses', 'mobility-trailblazers'); ?></option>
                <option value="draft"><?php _e('Draft', 'mobility-trailblazers'); ?></option>
                <option value="completed"><?php _e('Completed', 'mobility-trailblazers'); ?></option>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <!-- Add Rankings Section -->
    <?php if (isset($dashboard_settings['show_rankings']) ? $dashboard_settings['show_rankings'] : true) : ?>
        <div id="mt-rankings-container" class="mt-rankings-container">
            <?php 
            // Get initial rankings
            $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
            $rankings = $evaluation_repo->get_ranked_candidates_for_jury($jury_member->ID, isset($dashboard_settings['rankings_limit']) ? $dashboard_settings['rankings_limit'] : 10);
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
                
                $status = $evaluation ? $evaluation['status'] : 'draft';
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
                            
                            if ($status === 'completed') {
                                $status_text = __('Completed', 'mobility-trailblazers');
                            } elseif ($status === 'draft') {
                                $status_text = __('Draft', 'mobility-trailblazers');
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
    'use strict';
    
    // Initialize dashboard filtering
    initDashboardFiltering();
    
    /**
     * Initialize dashboard filtering functionality
     */
    function initDashboardFiltering() {
        // Detect if on mobile device
        var isMobile = window.innerWidth <= 768 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        
        // Search functionality with debounce
        let searchTimer;
        $('#mt-candidate-search').on('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                filterDashboardCandidates();
            }, 300);
        });
        
        // Status filter
        $('#mt-status-filter').on('change', function(e) {
            e.preventDefault();
            console.log('Status filter changed to:', $(this).val());
            filterDashboardCandidates();
        });
        
        // Only run initial filter if search or status filter has a value
        // This prevents hiding all candidates on initial load on mobile
        var hasSearchValue = $('#mt-candidate-search').val() !== '';
        var hasStatusFilter = $('#mt-status-filter').val() !== '';
        
        if (hasSearchValue || hasStatusFilter) {
            filterDashboardCandidates();
        } else {
            // Ensure all cards are visible on initial load
            $('.mt-candidate-card').show().removeClass('hidden');
            hideNoResults();
        }
        
        // Debug: Log all card data attributes on load
        if (isMobile || window.location.hash === '#debug') {
            console.log('=== Mobile Debug Info ===');
            console.log('Device is mobile:', isMobile);
            console.log('Window width:', window.innerWidth);
            console.log('User Agent:', navigator.userAgent);
            $('.mt-candidate-card').each(function(index) {
                var $card = $(this);
                console.log('Card ' + index + ':', {
                    name: $card.data('name'),
                    status: $card.data('status'),
                    title: $card.find('.mt-candidate-name').text(),
                    statusBadge: $card.find('.mt-status-badge').text(),
                    isVisible: $card.is(':visible')
                });
            });
            console.log('Total cards found:', $('.mt-candidate-card').length);
            console.log('=== End Debug ===');
        }
    }
    
    /**
     * Filter candidates based on search and status
     */
    function filterDashboardCandidates() {
        var searchTerm = $('#mt-candidate-search').val().toLowerCase().trim();
        var statusFilter = $('#mt-status-filter').val();
        var visibleCount = 0;
        var totalCandidates = $('.mt-candidate-card').length;
        var isMobile = window.innerWidth <= 768;
        
        // If no candidates found, don't filter
        if (totalCandidates === 0) {
            console.log('No candidate cards found to filter');
            return;
        }
        
        console.log('Starting filter - Search:', searchTerm, 'Status:', statusFilter, 'Total cards:', totalCandidates, 'Mobile:', isMobile);
        
        $('.mt-candidate-card').each(function() {
            var $card = $(this);
            var name = ($card.data('name') || '').toString().toLowerCase();
            var status = ($card.data('status') || 'draft').toString(); // Default to 'draft' if no status
            
            // Debug individual card data on mobile
            if (isMobile && window.console && window.console.log) {
                console.log('Card data - Name:', name, 'Status:', status);
            }
            
            // Check search match - search in both data-name and the actual text content
            var cardTitle = $card.find('.mt-candidate-name').text().toLowerCase();
            var cardOrg = $card.find('.mt-candidate-org').text().toLowerCase();
            var matchesSearch = searchTerm === '' || 
                              name.indexOf(searchTerm) !== -1 || 
                              cardTitle.indexOf(searchTerm) !== -1 ||
                              cardOrg.indexOf(searchTerm) !== -1;
            
            // Check status match - normalize status values
            var normalizedStatus = status.toLowerCase().trim();
            var normalizedFilter = statusFilter.toLowerCase().trim();
            var matchesStatus = statusFilter === '' || normalizedStatus === normalizedFilter;
            
            // Special handling for draft status (default when empty)
            if (normalizedStatus === '' && normalizedFilter === 'draft') {
                matchesStatus = true;
            }
            
            // On mobile, be more lenient with filtering if no filters are active
            if (isMobile && searchTerm === '' && statusFilter === '') {
                matchesSearch = true;
                matchesStatus = true;
            }
            
            if (matchesSearch && matchesStatus) {
                $card.show().removeClass('hidden').css('display', ''); // Ensure display is not forced to none
                visibleCount++;
            } else {
                $card.hide().addClass('hidden');
            }
        });
        
        // Show/hide no results message
        if (visibleCount === 0 && (searchTerm !== '' || statusFilter !== '')) {
            showNoResults();
        } else {
            hideNoResults();
        }
        
        console.log('Filter complete - Visible:', visibleCount, 'of', totalCandidates);
    }
    
    /**
     * Show no results message
     */
    function showNoResults() {
        if (!$('.mt-no-results-message').length) {
            $('.mt-candidates-list').append(
                '<div class="mt-no-results-message mt-notice">' +
                '<p><?php _e("No candidates match your search criteria.", "mobility-trailblazers"); ?></p>' +
                '</div>'
            );
        }
        $('.mt-no-results-message').show();
    }
    
    /**
     * Hide no results message
     */
    function hideNoResults() {
        $('.mt-no-results-message').hide();
    }
    
    // Evaluation button click
    $('.mt-evaluate-btn').on('click', function(e) {
        e.preventDefault();
        var candidateId = $(this).data('candidate-id');
        window.location.href = '?evaluate=' + candidateId;
    });
});
</script> 