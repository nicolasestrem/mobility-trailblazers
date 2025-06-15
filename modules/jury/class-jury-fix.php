<?php
/**
 * Mobility Trailblazers - Permanent Fix for Jury Dashboard Consistency
 * Version 2.0 - Corrected to avoid fatal errors
 * 
 * This file contains all fixes for the jury evaluation system.
 * Place this file in: /wp-content/plugins/mobility-trailblazers/includes/class-mt-jury-fix.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class MT_Jury_Fix {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Initialize fixes
        add_action('init', array($this, 'init_fixes'));
        
        // Fix rewrite rules on activation - using plugin_basename instead of MT_PLUGIN_FILE
        $plugin_file = dirname(dirname(__FILE__)) . '/mobility-trailblazers.php';
        register_activation_hook($plugin_file, array($this, 'activate_fixes'));
        
        // Add admin notice for migration
        add_action('admin_notices', array($this, 'migration_notice'));
        
        // AJAX handler for migration
        add_action('wp_ajax_mt_migrate_evaluations', array($this, 'ajax_migrate_evaluations'));
    }
    
    /**
     * Initialize all fixes
     */
    public function init_fixes() {
        // Fix 1: Ensure rewrite rules are properly set
        $this->fix_rewrite_rules();
        
        // Fix 2: Standardize evaluation queries
        $this->fix_evaluation_queries();
        
        // Fix 3: Fix AJAX submission handler
        $this->fix_ajax_handlers();
        
        // Fix 4: Add consistency check
        $this->add_consistency_check();
    }
    
    /**
     * Fix 1: Ensure rewrite rules work properly
     */
    private function fix_rewrite_rules() {
        // Check if rewrite rules need to be flushed
        $rules_version = get_option('mt_rewrite_rules_version', 0);
        if ($rules_version < 2) {
            add_action('init', function() {
                flush_rewrite_rules();
                update_option('mt_rewrite_rules_version', 2);
            }, 999);
        }
        
        // Add fallback shortcode support
        add_shortcode('mt_jury_dashboard', array($this, 'jury_dashboard_shortcode'));
    }
    
    /**
     * Jury dashboard shortcode fallback
     */
    public function jury_dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to access the jury dashboard.', 'mobility-trailblazers') . '</p>';
        }
        
        ob_start();
        
        // Use dynamic path resolution
        $template_path = dirname(dirname(__FILE__)) . '/templates/jury-dashboard-frontend.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<p>Dashboard template not found.</p>';
        }
        
        return ob_get_clean();
    }
    
    /**
     * Fix 2: Standardize evaluation queries to use user IDs consistently
     */
    private function fix_evaluation_queries() {
        // Override the is_jury_member function to be more reliable
        add_filter('mt_is_jury_member', array($this, 'enhanced_is_jury_member'), 10, 2);
        
        // Override get_jury_member_for_user to ensure consistency
        add_filter('mt_get_jury_member_id', array($this, 'get_consistent_jury_member_id'), 10, 2);
    }
    
    /**
     * Enhanced jury member check
     */
    public function enhanced_is_jury_member($is_jury, $user_id) {
        // Check by role
        $user = get_user_by('id', $user_id);
        if ($user && in_array('mt_jury_member', (array) $user->roles)) {
            return true;
        }
        
        // Check by jury post association
        $jury_post = $this->get_jury_post_for_user($user_id);
        return !empty($jury_post);
    }
    
    /**
     * Get jury post for user
     */
    private function get_jury_post_for_user($user_id) {
        return get_posts(array(
            'post_type' => 'mt_jury',
            'meta_query' => array(
                array(
                    'key' => '_mt_jury_user_id',
                    'value' => $user_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1,
            'post_status' => 'any'
        ));
    }
    
    /**
     * Get consistent jury member ID (always returns user ID)
     */
    public function get_consistent_jury_member_id($jury_id, $user_id) {
        return $user_id; // Always use user ID for consistency
    }
    
    /**
     * Fix 3: Fix AJAX handlers to save evaluations consistently
     */
    private function fix_ajax_handlers() {
        // Don't try to remove the original handler - just add ours with higher priority
        // This prevents the undefined global variable error
        add_action('wp_ajax_mt_submit_vote', array($this, 'fixed_handle_jury_vote'), 1);
    }
    
    /**
     * Fixed jury vote handler
     */
    public function fixed_handle_jury_vote() {
        // Don't try to remove other handlers to avoid undefined variable errors
        
        // Check nonce
        if (!check_ajax_referer('mt_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
        }
        
        $candidate_id = intval($_POST['candidate_id']);
        $current_user_id = get_current_user_id();
        
        // Verify user is a jury member
        if (!$this->enhanced_is_jury_member(false, $current_user_id)) {
            wp_send_json_error(array('message' => __('Unauthorized access.', 'mobility-trailblazers')));
        }
        
        // Collect scores
        $scores = array(
            'courage_score' => intval($_POST['courage_score'] ?? 0),
            'innovation_score' => intval($_POST['innovation_score'] ?? 0),
            'implementation_score' => intval($_POST['implementation_score'] ?? 0),
            'relevance_score' => intval($_POST['relevance_score'] ?? $_POST['mobility_relevance_score'] ?? 0),
            'visibility_score' => intval($_POST['visibility_score'] ?? 0)
        );
        
        // Validate scores
        foreach ($scores as $key => $score) {
            if ($score < 1 || $score > 10) {
                wp_send_json_error(array('message' => sprintf(__('Invalid score for %s. Must be between 1 and 10.', 'mobility-trailblazers'), $key)));
            }
        }
        
        $total_score = array_sum($scores);
        
        // Save to database using USER ID consistently
        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        
        // Check if evaluation exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_scores WHERE candidate_id = %d AND jury_member_id = %d",
            $candidate_id,
            $current_user_id
        ));
        
        $data = array(
            'candidate_id' => $candidate_id,
            'jury_member_id' => $current_user_id, // ALWAYS use user ID
            'courage_score' => $scores['courage_score'],
            'innovation_score' => $scores['innovation_score'],
            'implementation_score' => $scores['implementation_score'],
            'relevance_score' => $scores['relevance_score'],
            'visibility_score' => $scores['visibility_score'],
            'total_score' => $total_score,
            'comments' => sanitize_textarea_field($_POST['comments'] ?? ''),
            'evaluated_at' => current_time('mysql')
        );
        
        if ($existing) {
            $result = $wpdb->update(
                $table_scores,
                $data,
                array('id' => $existing->id)
            );
        } else {
            $result = $wpdb->insert($table_scores, $data);
        }
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => __('Evaluation saved successfully!', 'mobility-trailblazers'),
                'total_score' => $total_score
            ));
        } else {
            wp_send_json_error(array('message' => __('Database error. Please try again.', 'mobility-trailblazers')));
        }
        
        wp_die(); // Important for AJAX
    }
    
    /**
     * Fix 4: Add consistency check and migration
     */
    private function add_consistency_check() {
        // Check if migration is needed
        if (!get_option('mt_evaluation_migration_completed')) {
            $this->check_migration_needed();
        }
    }
    
    /**
     * Check if migration is needed
     */
    private function check_migration_needed() {
        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        
        // Check if table exists first
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_scores'") != $table_scores) {
            return;
        }
        
        // Check if there are evaluations with jury post IDs (larger numbers)
        $max_jury_id = $wpdb->get_var("SELECT MAX(jury_member_id) FROM $table_scores");
        
        if ($max_jury_id > 100) { // Likely using post IDs
            update_option('mt_evaluation_migration_needed', true);
        }
    }
    
    /**
     * Show migration notice
     */
    public function migration_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (!get_option('mt_evaluation_migration_needed')) {
            return;
        }
        
        if (get_option('mt_evaluation_migration_completed')) {
            return;
        }
        
        ?>
        <div class="notice notice-warning is-dismissible" id="mt-migration-notice">
            <p><strong>Mobility Trailblazers:</strong> Database migration needed to fix evaluation consistency.</p>
            <p>
                <button class="button button-primary" id="mt-run-migration">Run Migration</button>
                <span class="spinner" style="float: none;"></span>
            </p>
            <div id="mt-migration-results"></div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#mt-run-migration').on('click', function() {
                var button = $(this);
                var spinner = button.next('.spinner');
                var results = $('#mt-migration-results');
                
                button.prop('disabled', true);
                spinner.addClass('is-active');
                
                $.post(ajaxurl, {
                    action: 'mt_migrate_evaluations',
                    nonce: '<?php echo wp_create_nonce('mt_migrate'); ?>'
                }, function(response) {
                    if (response.success) {
                        results.html('<p style="color: green;">' + response.data.message + '</p>');
                        setTimeout(function() {
                            $('#mt-migration-notice').fadeOut();
                        }, 3000);
                    } else {
                        results.html('<p style="color: red;">Error: ' + response.data.message + '</p>');
                    }
                }).always(function() {
                    button.prop('disabled', false);
                    spinner.removeClass('is-active');
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for migration
     */
    public function ajax_migrate_evaluations() {
        if (!check_ajax_referer('mt_migrate', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $result = $this->migrate_evaluations_to_user_ids();
        
        if ($result['success']) {
            update_option('mt_evaluation_migration_completed', true);
            delete_option('mt_evaluation_migration_needed');
            wp_send_json_success(array('message' => $result['message']));
        } else {
            wp_send_json_error(array('message' => $result['message']));
        }
    }
    
    /**
     * Migrate evaluations to use user IDs consistently
     */
    private function migrate_evaluations_to_user_ids() {
        global $wpdb;
        
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        $migrated = 0;
        $errors = 0;
        
        // Get all jury members
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        foreach ($jury_members as $jury_member) {
            $jury_post_id = $jury_member->ID;
            $user_id = get_post_meta($jury_post_id, '_mt_jury_user_id', true);
            
            if ($user_id && $user_id != $jury_post_id) {
                // Update evaluations from jury post ID to user ID
                $updated = $wpdb->update(
                    $table_scores,
                    array('jury_member_id' => $user_id),
                    array('jury_member_id' => $jury_post_id),
                    array('%d'),
                    array('%d')
                );
                
                if ($updated !== false) {
                    $migrated += $updated;
                } else {
                    $errors++;
                }
            }
        }
        
        if ($errors > 0) {
            return array(
                'success' => false,
                'message' => sprintf('Migration completed with errors. Migrated %d evaluations, %d errors.', $migrated, $errors)
            );
        }
        
        return array(
            'success' => true,
            'message' => sprintf('Successfully migrated %d evaluations to use consistent user IDs.', $migrated)
        );
    }
    
    /**
     * Activation fixes
     */
    public function activate_fixes() {
        // Flush rewrite rules
        flush_rewrite_rules();
        update_option('mt_rewrite_rules_version', 2);
        
        // Check if migration needed
        $this->check_migration_needed();
    }
}

// Initialize the fix class only if constants are defined
if (defined('MT_PLUGIN_PATH')) {
    MT_Jury_Fix::get_instance();
}

