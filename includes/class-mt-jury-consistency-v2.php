<?php
/**
 * File: /wp-content/plugins/mobility-trailblazers/includes/class-mt-jury-consistency-v2.php
 * 
 * Mobility Trailblazers - Robust Jury Dashboard Consistency Fix
 * Handles jury additions/deletions and ensures bulletproof consistency
 */

if (!defined('ABSPATH')) {
    exit;
}

class MT_Jury_Consistency_V2 {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Add admin hooks
        add_action('admin_notices', array($this, 'show_consistency_notice'));
        add_action('wp_ajax_mt_fix_consistency', array($this, 'ajax_fix_consistency'));
        
        // Hook into jury post operations
        add_action('save_post_mt_jury', array($this, 'on_jury_save'), 10, 3);
        add_action('before_delete_post', array($this, 'on_jury_delete'), 10, 2);
        
        // Hook into evaluation saves to ensure they use user IDs
        add_action('wp_ajax_mt_submit_vote', array($this, 'ensure_user_id_in_votes'), 1);
        
        // Add cleanup for orphaned evaluations
        add_action('wp_ajax_mt_cleanup_orphaned', array($this, 'ajax_cleanup_orphaned'));
    }
    
    /**
     * Show consistency notice in admin
     */
    public function show_consistency_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Only show on MT pages
        $screen = get_current_screen();
        if (!$screen || (strpos($screen->id, 'mt-') === false && strpos($screen->id, 'mobility') === false)) {
            return;
        }
        
        $issues = $this->check_consistency_issues();
        
        if (!empty($issues)) {
            ?>
            <div class="notice notice-error is-dismissible" id="mt-consistency-notice">
                <h3>🚨 Mobility Trailblazers: Dashboard Consistency Issues Found</h3>
                
                <?php if ($issues['wrong_ids'] > 0): ?>
                <p><strong><?php echo $issues['wrong_ids']; ?> evaluations</strong> are using jury post IDs instead of user IDs.</p>
                <?php endif; ?>
                
                <?php if ($issues['orphaned'] > 0): ?>
                <p><strong><?php echo $issues['orphaned']; ?> evaluations</strong> are orphaned (jury member deleted).</p>
                <?php endif; ?>
                
                <?php if ($issues['missing_users'] > 0): ?>
                <p><strong><?php echo $issues['missing_users']; ?> jury members</strong> have no linked WordPress user.</p>
                <?php endif; ?>
                
                <p><strong>This causes different evaluation counts between admin and frontend dashboards!</strong></p>
                
                <p>
                    <button class="button button-primary" id="mt-fix-consistency">🔧 Fix All Issues</button>
                    <button class="button button-secondary" id="mt-cleanup-orphaned">🗑️ Remove Orphaned Evaluations</button>
                    <span class="spinner" style="float: none;"></span>
                </p>
                <div id="mt-consistency-results"></div>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                // Fix consistency button
                $('#mt-fix-consistency').on('click', function() {
                    var button = $(this);
                    var spinner = button.next('.spinner');
                    var results = $('#mt-consistency-results');
                    
                    button.prop('disabled', true);
                    spinner.addClass('is-active');
                    
                    $.post(ajaxurl, {
                        action: 'mt_fix_consistency',
                        nonce: '<?php echo wp_create_nonce('mt_consistency'); ?>'
                    }, function(response) {
                        if (response.success) {
                            results.html('<div style="padding: 10px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px;">' + response.data.message + '</div>');
                            setTimeout(function() {
                                location.reload();
                            }, 3000);
                        } else {
                            results.html('<div style="padding: 10px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px;">Error: ' + response.data.message + '</div>');
                        }
                    }).always(function() {
                        button.prop('disabled', false);
                        spinner.removeClass('is-active');
                    });
                });
                
                // Cleanup orphaned button
                $('#mt-cleanup-orphaned').on('click', function() {
                    if (!confirm('This will permanently delete evaluations from deleted jury members. Continue?')) {
                        return;
                    }
                    
                    var button = $(this);
                    var spinner = button.next('.spinner');
                    var results = $('#mt-consistency-results');
                    
                    button.prop('disabled', true);
                    spinner.addClass('is-active');
                    
                    $.post(ajaxurl, {
                        action: 'mt_cleanup_orphaned',
                        nonce: '<?php echo wp_create_nonce('mt_consistency'); ?>'
                    }, function(response) {
                        if (response.success) {
                            results.html('<div style="padding: 10px; background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; border-radius: 4px;">' + response.data.message + '</div>');
                            setTimeout(function() {
                                location.reload();
                            }, 3000);
                        } else {
                            results.html('<div style="padding: 10px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px;">Error: ' + response.data.message + '</div>');
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
     * Check for consistency issues
     */
    private function check_consistency_issues() {
        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        
        $issues = array(
            'wrong_ids' => 0,
            'orphaned' => 0,
            'missing_users' => 0
        );
        
        // Check for evaluations using jury post IDs instead of user IDs
        $issues['wrong_ids'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_scores s 
            WHERE s.jury_member_id IN (
                SELECT p.ID FROM {$wpdb->posts} p 
                WHERE p.post_type = 'mt_jury' AND p.post_status != 'trash'
            )"
        );
        
        // Check for orphaned evaluations (jury member deleted)
        $issues['orphaned'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_scores s 
            WHERE s.jury_member_id NOT IN (
                SELECT DISTINCT u.ID FROM {$wpdb->users} u
                UNION 
                SELECT DISTINCT p.ID FROM {$wpdb->posts} p 
                WHERE p.post_type = 'mt_jury' AND p.post_status != 'trash'
            )"
        );
        
        // Check for jury members without WordPress users
        $issues['missing_users'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} p 
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_mt_jury_user_id'
            WHERE p.post_type = 'mt_jury' 
            AND p.post_status != 'trash'
            AND (pm.meta_value IS NULL OR pm.meta_value = '' OR pm.meta_value NOT IN (SELECT ID FROM {$wpdb->users}))"
        );
        
        return $issues;
    }
    
    /**
     * AJAX handler to fix consistency issues
     */
    public function ajax_fix_consistency() {
        if (!check_ajax_referer('mt_consistency', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $result = $this->fix_all_consistency_issues();
        
        if ($result['success']) {
            wp_send_json_success(array('message' => $result['message']));
        } else {
            wp_send_json_error(array('message' => $result['message']));
        }
    }
    
    /**
     * Fix all consistency issues
     */
    private function fix_all_consistency_issues() {
        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        
        $fixed = 0;
        $errors = array();
        
        // Step 1: Fix evaluations using jury post IDs
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => array('publish', 'private', 'draft')
        ));
        
        foreach ($jury_members as $jury_member) {
            $jury_post_id = $jury_member->ID;
            $user_id = get_post_meta($jury_post_id, '_mt_jury_user_id', true);
            
            if ($user_id && is_numeric($user_id) && $user_id != $jury_post_id) {
                // Check if user still exists
                $user_exists = get_user_by('id', $user_id);
                if ($user_exists) {
                    // Update evaluations from jury post ID to user ID
                    $updated = $wpdb->update(
                        $table_scores,
                        array('jury_member_id' => intval($user_id)),
                        array('jury_member_id' => intval($jury_post_id)),
                        array('%d'),
                        array('%d')
                    );
                    
                    if ($updated !== false && $updated > 0) {
                        $fixed += $updated;
                    }
                } else {
                    $errors[] = "User ID $user_id for jury member '$jury_member->post_title' no longer exists";
                }
            }
        }
        
        // Step 2: Create missing user associations for jury members without them
        $jury_without_users = $wpdb->get_results(
            "SELECT p.ID, p.post_title 
            FROM {$wpdb->posts} p 
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_mt_jury_user_id'
            WHERE p.post_type = 'mt_jury' 
            AND p.post_status != 'trash'
            AND (pm.meta_value IS NULL OR pm.meta_value = '')"
        );
        
        $created_users = 0;
        foreach ($jury_without_users as $jury) {
            // Create a user for this jury member
            $username = sanitize_user(strtolower(str_replace(' ', '_', $jury->post_title)));
            $email = $username . '@mobility-trailblazers.local';
            
            // Make sure username is unique
            $counter = 1;
            $original_username = $username;
            while (username_exists($username)) {
                $username = $original_username . '_' . $counter;
                $counter++;
            }
            
            $user_id = wp_create_user($username, wp_generate_password(), $email);
            if (!is_wp_error($user_id)) {
                // Assign jury role
                $user = new WP_User($user_id);
                $user->set_role('mt_jury_member');
                
                // Link to jury post
                update_post_meta($jury->ID, '_mt_jury_user_id', $user_id);
                update_user_meta($user_id, '_mt_jury_post_id', $jury->ID);
                
                $created_users++;
            } else {
                $errors[] = "Failed to create user for jury member '$jury->post_title': " . $user_id->get_error_message();
            }
        }
        
        // Step 3: Ensure all future evaluations use user IDs
        $this->ensure_evaluation_hooks();
        
        $message = "✅ Fixed $fixed evaluations to use user IDs.";
        if ($created_users > 0) {
            $message .= " Created $created_users missing user accounts.";
        }
        if (!empty($errors)) {
            $message .= " Warnings: " . implode(', ', $errors);
        }
        
        return array(
            'success' => true,
            'message' => $message
        );
    }
    
    /**
     * AJAX handler to cleanup orphaned evaluations
     */
    public function ajax_cleanup_orphaned() {
        if (!check_ajax_referer('mt_consistency', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        
        // Delete evaluations where jury_member_id doesn't correspond to any user
        $deleted = $wpdb->query(
            "DELETE FROM $table_scores 
            WHERE jury_member_id NOT IN (
                SELECT DISTINCT u.ID FROM {$wpdb->users} u
            )"
        );
        
        if ($deleted !== false) {
            wp_send_json_success(array(
                'message' => "🗑️ Cleaned up $deleted orphaned evaluations."
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to cleanup orphaned evaluations'));
        }
    }
    
    /**
     * Ensure evaluation hooks are in place
     */
    private function ensure_evaluation_hooks() {
        // This will be called by the fix process to ensure future evaluations are saved correctly
        add_filter('pre_insert_evaluation', array($this, 'force_user_id_in_evaluation'), 10, 1);
    }
    
    /**
     * Force user ID in evaluations
     */
    public function force_user_id_in_evaluation($data) {
        if (isset($data['jury_member_id'])) {
            $current_user_id = get_current_user_id();
            if ($current_user_id) {
                $data['jury_member_id'] = $current_user_id;
            }
        }
        return $data;
    }
    
    /**
     * Ensure user ID is used in vote submissions
     */
    public function ensure_user_id_in_votes() {
        // Override the jury_member_id in $_POST to always use current user ID
        if (isset($_POST['jury_member_id'])) {
            $current_user_id = get_current_user_id();
            if ($current_user_id) {
                $_POST['jury_member_id'] = $current_user_id;
            }
        }
    }
    
    /**
     * Handle jury post save
     */
    public function on_jury_save($post_id, $post, $update) {
        // Ensure this jury member has a WordPress user
        $user_id = get_post_meta($post_id, '_mt_jury_user_id', true);
        
        if (!$user_id || !get_user_by('id', $user_id)) {
            // Create or reassign user
            $this->create_user_for_jury($post_id, $post);
        }
    }
    
    /**
     * Handle jury post deletion
     */
    public function on_jury_delete($post_id, $post) {
        if ($post && $post->post_type === 'mt_jury') {
            // Option 1: Delete associated evaluations
            // Option 2: Transfer evaluations to admin
            
            // For now, let's mark them for cleanup
            global $wpdb;
            $table_scores = $wpdb->prefix . 'mt_candidate_scores';
            
            $user_id = get_post_meta($post_id, '_mt_jury_user_id', true);
            if ($user_id) {
                // Add a meta field to track deleted jury members
                $wpdb->update(
                    $table_scores,
                    array('jury_member_id' => -1), // Mark as deleted
                    array('jury_member_id' => $user_id),
                    array('%d'),
                    array('%d')
                );
            }
        }
    }
    
    /**
     * Create user for jury member
     */
    private function create_user_for_jury($post_id, $post) {
        $username = sanitize_user(strtolower(str_replace(' ', '_', $post->post_title)));
        $email = $username . '@mobility-trailblazers.local';
        
        // Make username unique
        $counter = 1;
        $original_username = $username;
        while (username_exists($username)) {
            $username = $original_username . '_' . $counter;
            $counter++;
        }
        
        $user_id = wp_create_user($username, wp_generate_password(), $email);
        if (!is_wp_error($user_id)) {
            $user = new WP_User($user_id);
            $user->set_role('mt_jury_member');
            
            update_post_meta($post_id, '_mt_jury_user_id', $user_id);
            update_user_meta($user_id, '_mt_jury_post_id', $post_id);
            
            return $user_id;
        }
        
        return false;
    }
}

// Initialize
if (defined('ABSPATH')) {
    MT_Jury_Consistency_V2::get_instance();
}

/**
 * UNIFIED FUNCTIONS - These MUST be used by both dashboards
 */

if (!function_exists('mt_get_user_evaluation_count_unified')) {
    function mt_get_user_evaluation_count_unified($user_id) {
        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        
        // ALWAYS use user ID only - no jury post ID fallback
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT candidate_id) FROM $table_scores 
            WHERE jury_member_id = %d AND jury_member_id > 0",
            $user_id
        ));
    }
}

if (!function_exists('mt_has_jury_evaluated_unified')) {
    function mt_has_jury_evaluated_unified($user_id, $candidate_id) {
        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        
        // ALWAYS use user ID only
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_scores 
            WHERE candidate_id = %d AND jury_member_id = %d AND jury_member_id > 0",
            $candidate_id,
            $user_id
        )) > 0;
    }
}

if (!function_exists('mt_get_jury_evaluation_unified')) {
    function mt_get_jury_evaluation_unified($user_id, $candidate_id) {
        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        
        // ALWAYS use user ID only
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_scores 
            WHERE candidate_id = %d AND jury_member_id = %d AND jury_member_id > 0
            ORDER BY evaluated_at DESC LIMIT 1",
            $candidate_id,
            $user_id
        ));
    }
}