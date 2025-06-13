<?php
// File: /wp-content/plugins/mobility-trailblazers/includes/class-mt-jury-consistency.php

/**
 * Mobility Trailblazers - Jury Dashboard Consistency Fix
 * Ensures evaluations are consistent between admin and frontend dashboards
 */

if (!defined('ABSPATH')) {
    exit;
}

class MT_Jury_Consistency {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hook into evaluation queries
        add_filter('mt_get_user_evaluation_count', array($this, 'get_consistent_evaluation_count'), 10, 2);
        add_filter('mt_get_user_evaluations', array($this, 'get_consistent_evaluations'), 10, 3);
        
        // Ensure consistent saving
        add_action('mt_before_save_evaluation', array($this, 'ensure_consistent_ids'), 10, 2);
        
        // Add dashboard sync check
        add_action('admin_notices', array($this, 'check_dashboard_sync'));
        add_action('wp_ajax_mt_sync_evaluations', array($this, 'ajax_sync_evaluations'));
    }
    
    /**
     * Get consistent evaluation count for a user
     * This function checks BOTH user ID and jury post ID
     */
    public function get_consistent_evaluation_count($count, $user_id) {
        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        
        // Get jury post ID for this user
        $jury_post_id = $this->get_jury_post_id_for_user($user_id);
        
        if ($jury_post_id) {
            // Count evaluations by BOTH user ID and jury post ID
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT candidate_id) FROM $table_scores 
                WHERE jury_member_id IN (%d, %d)",
                $user_id,
                $jury_post_id
            ));
        } else {
            // Just count by user ID
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT candidate_id) FROM $table_scores 
                WHERE jury_member_id = %d",
                $user_id
            ));
        }
        
        return $count;
    }
    
    /**
     * Get jury post ID for a user
     */
    private function get_jury_post_id_for_user($user_id) {
        global $wpdb;
        
        // First check by user ID meta
        $post_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_mt_jury_user_id' AND meta_value = %s",
            $user_id
        ));
        
        if ($post_id) {
            return $post_id;
        }
        
        // Then check by email
        $user = get_user_by('id', $user_id);
        if ($user) {
            $post_id = $wpdb->get_var($wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key = '_mt_jury_email' AND meta_value = %s",
                $user->user_email
            ));
        }
        
        return $post_id;
    }
    
    /**
     * Get consistent evaluations for display
     */
    public function get_consistent_evaluations($evaluations, $user_id, $limit) {
        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        
        $jury_post_id = $this->get_jury_post_id_for_user($user_id);
        
        if ($jury_post_id) {
            $query = $wpdb->prepare(
                "SELECT * FROM $table_scores 
                WHERE jury_member_id IN (%d, %d) 
                ORDER BY evaluated_at DESC",
                $user_id,
                $jury_post_id
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT * FROM $table_scores 
                WHERE jury_member_id = %d 
                ORDER BY evaluated_at DESC",
                $user_id
            );
        }
        
        if ($limit > 0) {
            $query .= " LIMIT $limit";
        }
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Ensure evaluations are saved with user ID consistently
     */
    public function ensure_consistent_ids($data, $context) {
        // Always use WordPress user ID for new evaluations
        if (isset($data['jury_member_id'])) {
            $user_id = get_current_user_id();
            if ($user_id) {
                $data['jury_member_id'] = $user_id;
            }
        }
        return $data;
    }
    
    /**
     * Check if dashboards are in sync
     */
    public function check_dashboard_sync() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Only show on MT pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'mt-') === false) {
            return;
        }
        
        // Check for sync issues
        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        
        // Find evaluations with jury post IDs (likely > 100)
        $sync_issues = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_scores WHERE jury_member_id > 100"
        );
        
        if ($sync_issues > 0) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p><strong>Mobility Trailblazers:</strong> Found <?php echo $sync_issues; ?> evaluations that may need syncing.</p>
                <p>
                    <button class="button button-primary" id="mt-sync-evaluations">Sync Evaluations</button>
                    <span class="spinner" style="float: none;"></span>
                </p>
                <div id="mt-sync-results"></div>
            </div>
            <script>
            jQuery(document).ready(function($) {
                $('#mt-sync-evaluations').on('click', function() {
                    var button = $(this);
                    var spinner = button.next('.spinner');
                    var results = $('#mt-sync-results');
                    
                    button.prop('disabled', true);
                    spinner.addClass('is-active');
                    
                    $.post(ajaxurl, {
                        action: 'mt_sync_evaluations',
                        nonce: '<?php echo wp_create_nonce('mt_sync'); ?>'
                    }, function(response) {
                        if (response.success) {
                            results.html('<p style="color: green;">' + response.data.message + '</p>');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
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
    }
    
    /**
     * AJAX handler for syncing evaluations
     */
    public function ajax_sync_evaluations() {
        if (!check_ajax_referer('mt_sync', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $result = $this->sync_evaluation_ids();
        
        if ($result['success']) {
            wp_send_json_success(array('message' => $result['message']));
        } else {
            wp_send_json_error(array('message' => $result['message']));
        }
    }
    
    /**
     * Sync evaluation IDs to use WordPress user IDs
     */
    private function sync_evaluation_ids() {
        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        $synced = 0;
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
                    $synced += $updated;
                } else {
                    $errors++;
                }
            }
        }
        
        if ($errors > 0) {
            return array(
                'success' => false,
                'message' => sprintf('Synced %d evaluations with %d errors.', $synced, $errors)
            );
        }
        
        return array(
            'success' => true,
            'message' => sprintf('Successfully synced %d evaluations.', $synced)
        );
    }
}

// Initialize
MT_Jury_Consistency::get_instance();