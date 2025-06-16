<?php
/**
 * Jury System functionality for Mobility Trailblazers
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Jury_System
 * 
 * Handles jury management and voting system
 */
class MT_Jury_System {
    
    /**
     * Initialize the jury system
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_assign_jury', array($this, 'assign_jury'));
        add_action('wp_ajax_remove_jury', array($this, 'remove_jury'));
        add_action('wp_ajax_jury_vote', array($this, 'process_jury_vote'));
    }
    
    /**
     * Initialize jury system
     */
    public function init() {
        $this->create_jury_role();
        add_action('admin_menu', array($this, 'add_jury_menu'));
    }
    
    /**
     * Create jury role
     */
    public function create_jury_role() {
        if (!get_role('mt_jury')) {
            add_role('mt_jury', __('Jury Member', 'mobility-trailblazers'), array(
                'read' => true,
                'mt_vote' => true,
                'mt_view_submissions' => true,
            ));
        }
    }
    
    /**
     * Add jury management menu
     */
    public function add_jury_menu() {
        add_submenu_page(
            'mobility-trailblazers',
            __('Jury Management', 'mobility-trailblazers'),
            __('Jury', 'mobility-trailblazers'),
            'manage_options',
            'mt-jury',
            array($this, 'render_jury_page')
        );
    }
    
    /**
     * Render jury management page
     */
    public function render_jury_page() {
        $jury_members = $this->get_jury_members();
        $all_users = get_users(array('role__not_in' => array('mt_jury')));
        
        ?>
        <div class="wrap">
            <h1><?php _e('Jury Management', 'mobility-trailblazers'); ?></h1>
            
            <div class="mt-jury-management">
                <div class="mt-jury-section">
                    <h2><?php _e('Current Jury Members', 'mobility-trailblazers'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Name', 'mobility-trailblazers'); ?></th>
                                <th><?php _e('Email', 'mobility-trailblazers'); ?></th>
                                <th><?php _e('Votes Cast', 'mobility-trailblazers'); ?></th>
                                <th><?php _e('Actions', 'mobility-trailblazers'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($jury_members)): ?>
                                <tr>
                                    <td colspan="4"><?php _e('No jury members assigned yet.', 'mobility-trailblazers'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($jury_members as $member): ?>
                                    <tr>
                                        <td><?php echo esc_html($member->display_name); ?></td>
                                        <td><?php echo esc_html($member->user_email); ?></td>
                                        <td><?php echo $this->get_jury_vote_count($member->ID); ?></td>
                                        <td>
                                            <button class="button remove-jury" data-user-id="<?php echo $member->ID; ?>">
                                                <?php _e('Remove', 'mobility-trailblazers'); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-jury-section">
                    <h2><?php _e('Add Jury Members', 'mobility-trailblazers'); ?></h2>
                    <form id="add-jury-form">
                        <table class="form-table">
                            <tr>
                                <th><label for="jury-user"><?php _e('Select User', 'mobility-trailblazers'); ?></label></th>
                                <td>
                                    <select name="user_id" id="jury-user" required>
                                        <option value=""><?php _e('Select a user...', 'mobility-trailblazers'); ?></option>
                                        <?php foreach ($all_users as $user): ?>
                                            <option value="<?php echo $user->ID; ?>">
                                                <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php _e('Add to Jury', 'mobility-trailblazers'); ?></button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Add jury member
            $('#add-jury-form').on('submit', function(e) {
                e.preventDefault();
                var userId = $('#jury-user').val();
                if (!userId) return;
                
                $.post(ajaxurl, {
                    action: 'assign_jury',
                    user_id: userId,
                    nonce: '<?php echo wp_create_nonce('jury_management'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data);
                    }
                });
            });
            
            // Remove jury member
            $('.remove-jury').on('click', function() {
                var userId = $(this).data('user-id');
                if (confirm('<?php _e('Are you sure you want to remove this jury member?', 'mobility-trailblazers'); ?>')) {
                    $.post(ajaxurl, {
                        action: 'remove_jury',
                        user_id: userId,
                        nonce: '<?php echo wp_create_nonce('jury_management'); ?>'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data);
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Get jury members
     */
    public function get_jury_members() {
        return get_users(array('role' => 'mt_jury'));
    }
    
    /**
     * Get jury vote count for a user
     */
    public function get_jury_vote_count($user_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes WHERE user_id = %d",
            $user_id
        ));
    }
    
    /**
     * Assign user to jury
     */
    public function assign_jury() {
        if (!wp_verify_nonce($_POST['nonce'], 'jury_management') || !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'mobility-trailblazers'));
        }
        
        $user_id = intval($_POST['user_id']);
        $user = get_user_by('id', $user_id);
        
        if (!$user) {
            wp_send_json_error(__('User not found', 'mobility-trailblazers'));
        }
        
        $user->set_role('mt_jury');
        
        // Send notification email
        $this->send_jury_notification($user);
        
        wp_send_json_success(__('User added to jury successfully', 'mobility-trailblazers'));
    }
    
    /**
     * Remove user from jury
     */
    public function remove_jury() {
        if (!wp_verify_nonce($_POST['nonce'], 'jury_management') || !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'mobility-trailblazers'));
        }
        
        $user_id = intval($_POST['user_id']);
        $user = get_user_by('id', $user_id);
        
        if (!$user) {
            wp_send_json_error(__('User not found', 'mobility-trailblazers'));
        }
        
        $user->set_role('subscriber'); // Default role
        
        wp_send_json_success(__('User removed from jury successfully', 'mobility-trailblazers'));
    }
    
    /**
     * Process jury vote
     */
    public function process_jury_vote() {
        if (!is_user_logged_in() || !current_user_can('mt_vote')) {
            wp_send_json_error(__('You do not have permission to vote', 'mobility-trailblazers'));
        }
        
        $submission_id = intval($_POST['submission_id']);
        $score = intval($_POST['score']);
        $user_id = get_current_user_id();
        
        if ($score < 1 || $score > 10) {
            wp_send_json_error(__('Score must be between 1 and 10', 'mobility-trailblazers'));
        }
        
        global $wpdb;
        
        // Check if already voted
        $existing_vote = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}mt_votes WHERE submission_id = %d AND user_id = %d",
            $submission_id,
            $user_id
        ));
        
        if ($existing_vote) {
            // Update existing vote
            $result = $wpdb->update(
                $wpdb->prefix . 'mt_votes',
                array('score' => $score, 'updated_at' => current_time('mysql')),
                array('id' => $existing_vote),
                array('%d', '%s'),
                array('%d')
            );
        } else {
            // Insert new vote
            $result = $wpdb->insert(
                $wpdb->prefix . 'mt_votes',
                array(
                    'submission_id' => $submission_id,
                    'user_id' => $user_id,
                    'score' => $score,
                    'created_at' => current_time('mysql'),
                ),
                array('%d', '%d', '%d', '%s')
            );
        }
        
        if ($result !== false) {
            // Update submission vote statistics
            $this->update_submission_stats($submission_id);
            wp_send_json_success(__('Vote recorded successfully', 'mobility-trailblazers'));
        } else {
            wp_send_json_error(__('Failed to record vote', 'mobility-trailblazers'));
        }
    }
    
    /**
     * Update submission voting statistics
     */
    public function update_submission_stats($submission_id) {
        global $wpdb;
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as vote_count, AVG(score) as average_score 
             FROM {$wpdb->prefix}mt_votes 
             WHERE submission_id = %d",
            $submission_id
        ));
        
        update_post_meta($submission_id, '_mt_vote_count', $stats->vote_count);
        update_post_meta($submission_id, '_mt_average_score', round($stats->average_score, 2));
    }
    
    /**
     * Send jury notification email
     */
    private function send_jury_notification($user) {
        $subject = __('You have been added to the Mobility Trailblazers Jury', 'mobility-trailblazers');
        $message = sprintf(
            __('Hello %s,

You have been selected to be a jury member for the Mobility Trailblazers competition.

You can now log in to the website and access the jury voting interface to evaluate submissions.

Thank you for your participation!

Best regards,
The Mobility Trailblazers Team', 'mobility-trailblazers'),
            $user->display_name
        );
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Check if user is jury member
     */
    public function is_jury_member($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_user_by('id', $user_id);
        return $user && in_array('mt_jury', $user->roles);
    }
    
    /**
     * Get submissions for jury voting
     */
    public function get_submissions_for_voting($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $args = array(
            'post_type' => 'mt_submission',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_mt_submission_status',
                    'value' => 'approved',
                    'compare' => '='
                )
            )
        );
        
        return get_posts($args);
    }
}

// Alias for backward compatibility
class JurySystem extends MT_Jury_System {} 