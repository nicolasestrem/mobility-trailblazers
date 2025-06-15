<?php
/**
 * Admin Menus Handler
 *
 * @package MobilityTrailblazers
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Admin_Menus
 * Handles all admin menu registrations
 */
class MT_Admin_Menus {
    
    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'register_menus'));
    }
    
    /**
     * Register all admin menus
     */
    public function register_menus() {
        // Main menu
        add_menu_page(
            __('MT Award System', 'mobility-trailblazers'),
            __('MT Awards', 'mobility-trailblazers'),
            'mt_manage_awards',
            'mt-award-system',
            array($this, 'admin_dashboard'),
            'dashicons-awards',
            30
        );
        
        // Dashboard submenu (rename the first item)
        add_submenu_page(
            'mt-award-system',
            __('Dashboard', 'mobility-trailblazers'),
            __('Dashboard', 'mobility-trailblazers'),
            'mt_manage_awards',
            'mt-award-system',
            array($this, 'admin_dashboard')
        );
        
        // Candidates submenu
        add_submenu_page(
            'mt-award-system',
            __('Candidates', 'mobility-trailblazers'),
            __('Candidates', 'mobility-trailblazers'),
            'edit_mt_candidates',
            'edit.php?post_type=mt_candidate'
        );
        
        // Jury Members submenu
        add_submenu_page(
            'mt-award-system',
            __('Jury Members', 'mobility-trailblazers'),
            __('Jury Members', 'mobility-trailblazers'),
            'edit_mt_jurys',
            'edit.php?post_type=mt_jury'
        );
        
        // Jury Evaluation page (for jury members)
        add_submenu_page(
            'mt-award-system',
            __('Jury Evaluation', 'mobility-trailblazers'),
            __('Jury Evaluation', 'mobility-trailblazers'),
            'mt_submit_evaluations',
            'mt-jury-evaluation',
            array($this, 'jury_evaluation_page')
        );
        
        // Assignment Management
        add_submenu_page(
            'mt-award-system',
            __('Assignment Management', 'mobility-trailblazers'),
            __('Assignments', 'mobility-trailblazers'),
            'mt_manage_assignments',
            'mt-assignment-management',
            array($this, 'assignment_management_page')
        );
        
        // Voting Results
        add_submenu_page(
            'mt-award-system',
            __('Voting Results', 'mobility-trailblazers'),
            __('Results', 'mobility-trailblazers'),
            'mt_view_all_evaluations',
            'mt-voting-results',
            array($this, 'voting_results_page')
        );
        
        // Vote Reset Management
        add_submenu_page(
            'mt-award-system',
            __('Vote Reset Management', 'mobility-trailblazers'),
            __('Vote Reset', 'mobility-trailblazers'),
            'mt_manage_voting',
            'mt-vote-reset',
            array($this, 'vote_reset_page')
        );
        
        // Settings
        add_submenu_page(
            'mt-award-system',
            __('Settings', 'mobility-trailblazers'),
            __('Settings', 'mobility-trailblazers'),
            'mt_manage_awards',
            'mt-settings',
            array($this, 'settings_page')
        );
        
        // Diagnostic
        add_submenu_page(
            'mt-award-system',
            __('System Diagnostic', 'mobility-trailblazers'),
            __('Diagnostic', 'mobility-trailblazers'),
            'manage_options',
            'mt-diagnostic',
            array($this, 'diagnostic_page')
        );
        
        // Add Jury Dashboard menu for jury members
        if (MT_Roles::is_jury_member() && !current_user_can('manage_options')) {
            add_menu_page(
                __('Jury Dashboard', 'mobility-trailblazers'),
                __('Jury Dashboard', 'mobility-trailblazers'),
                'mt_access_jury_dashboard',
                'mt-jury-dashboard',
                array($this, 'jury_dashboard_redirect'),
                'dashicons-clipboard',
                25
            );
        }
    }
    
    /**
     * Admin dashboard page
     */
    public function admin_dashboard() {
        ?>
        <div class="wrap">
            <h1><?php _e('Mobility Trailblazers Award System', 'mobility-trailblazers'); ?></h1>
            
            <?php 
            // Temporary diagnostic display
            if (function_exists('mt_display_diagnostic')) {
                mt_display_diagnostic();
            }
            ?>
            
            <div class="mt-dashboard-widgets">
                <div class="mt-widget">
                    <h2><?php _e('System Overview', 'mobility-trailblazers'); ?></h2>
                    <ul>
                        <li><?php 
                            $candidate_count = wp_count_posts('mt_candidate');
                            $candidate_total = (is_object($candidate_count) && isset($candidate_count->publish)) ? $candidate_count->publish : 0;
                            printf(__('Total Candidates: %d', 'mobility-trailblazers'), $candidate_total); 
                        ?></li>
                        <li><?php 
                            $jury_count = wp_count_posts('mt_jury');
                            $jury_total = (is_object($jury_count) && isset($jury_count->publish)) ? $jury_count->publish : 0;
                            printf(__('Total Jury Members: %d', 'mobility-trailblazers'), $jury_total); 
                        ?></li>
                        <li><?php 
                            $total_votes = function_exists('mt_get_total_active_votes') ? mt_get_total_active_votes() : 0;
                            printf(__('Total Votes: %d', 'mobility-trailblazers'), $total_votes); 
                        ?></li>
                        <li><?php 
                            $total_evaluations = function_exists('mt_get_total_evaluations') ? mt_get_total_evaluations() : 0;
                            printf(__('Total Evaluations: %d', 'mobility-trailblazers'), $total_evaluations); 
                        ?></li>
                    </ul>
                </div>
                
                <div class="mt-widget">
                    <h2><?php _e('Recent Activity', 'mobility-trailblazers'); ?></h2>
                    <?php $this->display_recent_activity(); ?>
                </div>
                
                <div class="mt-widget">
                    <h2><?php _e('Quick Actions', 'mobility-trailblazers'); ?></h2>
                    <p>
                        <a href="<?php echo admin_url('post-new.php?post_type=mt_candidate'); ?>" class="button button-primary">
                            <?php _e('Add New Candidate', 'mobility-trailblazers'); ?>
                        </a>
                        <a href="<?php echo admin_url('post-new.php?post_type=mt_jury'); ?>" class="button">
                            <?php _e('Add Jury Member', 'mobility-trailblazers'); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Jury evaluation page
     */
    public function jury_evaluation_page() {
        // Load the jury evaluation admin class
        if (class_exists('MT_Jury_Management_Admin')) {
            $jury_admin = MT_Jury_Management_Admin::get_instance();
            $jury_admin->render_evaluation_page();
        } else {
            echo '<div class="wrap"><h1>' . __('Error', 'mobility-trailblazers') . '</h1>';
            echo '<p>' . __('Jury management system not available.', 'mobility-trailblazers') . '</p></div>';
        }
    }
    
    /**
     * Assignment management page
     */
    public function assignment_management_page() {
        // Load the assignment management template
        $template = MT_PLUGIN_PATH . 'admin/views/assignment-management.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="wrap"><h1>' . __('Assignment Management', 'mobility-trailblazers') . '</h1>';
            echo '<p>' . __('Assignment management interface coming soon.', 'mobility-trailblazers') . '</p></div>';
        }
    }
    
    /**
     * Voting results page
     */
    public function voting_results_page() {
        // Load the voting results template
        $template = MT_PLUGIN_PATH . 'admin/views/voting-results.php';
        if (file_exists($template)) {
            include $template;
        } else {
            ?>
            <div class="wrap">
                <h1><?php _e('Voting Results', 'mobility-trailblazers'); ?></h1>
                <p><?php _e('View and analyze voting results here.', 'mobility-trailblazers'); ?></p>
                
                <div id="mt-voting-results">
                    <!-- Results will be loaded here via AJAX -->
                </div>
            </div>
            <?php
        }
    }
    
    /**
     * Vote reset page
     */
    public function vote_reset_page() {
        // Load the vote reset template
        $template = MT_PLUGIN_PATH . 'admin/views/vote-reset.php';
        if (file_exists($template)) {
            include $template;
        } else {
            // Fallback interface
            ?>
            <div class="wrap">
                <h1><?php _e('Vote Reset Management', 'mobility-trailblazers'); ?></h1>
                <div class="notice notice-error">
                    <p><?php _e('Vote reset interface template not found. Please check if admin/views/vote-reset.php exists.', 'mobility-trailblazers'); ?></p>
                </div>
                
                <!-- Basic Reset Options -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle"><?php _e('Basic Reset Options', 'mobility-trailblazers'); ?></h2>
                    </div>
                    <div class="inside">
                        <p><?php _e('Vote reset functionality is available through the REST API endpoints:', 'mobility-trailblazers'); ?></p>
                        <ul>
                            <li><code>POST /wp-json/mt/v1/reset-vote</code> - Reset individual vote</li>
                            <li><code>POST /wp-json/mt/v1/bulk-reset</code> - Bulk reset operations</li>
                        </ul>
                        
                        <?php if (class_exists('MT_Vote_Reset_Manager')): ?>
                            <p><strong><?php _e('Vote Reset Manager is loaded and ready.', 'mobility-trailblazers'); ?></strong></p>
                        <?php else: ?>
                            <div class="notice notice-warning inline">
                                <p><?php _e('Vote Reset Manager class is not available.', 'mobility-trailblazers'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php
        }
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Mobility Trailblazers Settings', 'mobility-trailblazers'); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('mt_award_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="mt_jury_dashboard_page">
                                <?php _e('Jury Dashboard Page', 'mobility-trailblazers'); ?>
                            </label>
                        </th>
                        <td>
                            <?php
                            wp_dropdown_pages(array(
                                'name' => 'mt_jury_dashboard_page',
                                'selected' => get_option('mt_jury_dashboard_page'),
                                'show_option_none' => __('— Select —', 'mobility-trailblazers'),
                                'option_none_value' => '0'
                            ));
                            ?>
                            <p class="description">
                                <?php _e('Select the page to use as the jury dashboard. Add the [mt_jury_dashboard] shortcode to this page.', 'mobility-trailblazers'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="mt_votes_per_candidate">
                                <?php _e('Votes per Candidate', 'mobility-trailblazers'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="number" id="mt_votes_per_candidate" name="mt_votes_per_candidate" 
                                   value="<?php echo get_option('mt_votes_per_candidate', 3); ?>" min="1" max="10" />
                            <p class="description">
                                <?php _e('Number of jury members who should evaluate each candidate.', 'mobility-trailblazers'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="mt_enable_public_voting">
                                <?php _e('Enable Public Voting', 'mobility-trailblazers'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox" id="mt_enable_public_voting" name="mt_enable_public_voting" 
                                   value="1" <?php checked(get_option('mt_enable_public_voting'), 1); ?> />
                            <label for="mt_enable_public_voting">
                                <?php _e('Allow public users to vote for candidates', 'mobility-trailblazers'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Diagnostic page
     */
    public function diagnostic_page() {
        if (class_exists('MT_Diagnostic')) {
            $diagnostic = new MT_Diagnostic();
            $diagnostic->render_page();
        } else {
            echo '<div class="wrap"><h1>' . __('System Diagnostic', 'mobility-trailblazers') . '</h1>';
            echo '<p>' . __('Diagnostic system not available.', 'mobility-trailblazers') . '</p></div>';
        }
    }
    
    /**
     * Jury dashboard redirect
     */
    public function jury_dashboard_redirect() {
        $dashboard_url = mt_get_jury_dashboard_url();
        wp_redirect($dashboard_url);
        exit;
    }
    
    /**
     * Display recent activity
     */
    private function display_recent_activity() {
        $activities = mt_get_recent_vote_activity(5);
        
        if (empty($activities)) {
            echo '<p>' . __('No recent activity.', 'mobility-trailblazers') . '</p>';
            return;
        }
        
        echo '<ul>';
        foreach ($activities as $activity) {
            if ($activity['type'] === 'vote') {
                printf(
                    __('<li>%s voted %d/10 for %s on %s</li>', 'mobility-trailblazers'),
                    esc_html($activity['jury_name']),
                    $activity['rating'],
                    esc_html($activity['candidate_name']),
                    date_i18n(get_option('date_format'), strtotime($activity['date']))
                );
            } else {
                printf(
                    __('<li>%s evaluated %s (Score: %d/25) on %s</li>', 'mobility-trailblazers'),
                    esc_html($activity['jury_name']),
                    esc_html($activity['candidate_name']),
                    $activity['total_score'],
                    date_i18n(get_option('date_format'), strtotime($activity['date']))
                );
            }
        }
        echo '</ul>';
    }
} 