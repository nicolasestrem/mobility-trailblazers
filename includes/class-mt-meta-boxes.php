<?php
/**
 * Meta Boxes functionality for Mobility Trailblazers
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Meta_Boxes
 * 
 * Handles custom meta boxes for the plugin
 */
class MT_Meta_Boxes {
    
    /**
     * Initialize the meta boxes
     */
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
    }
    
    /**
     * Add meta boxes to post types
     */
    public function add_meta_boxes() {
        // Add meta boxes for custom post types if needed
        add_meta_box(
            'mt_submission_details',
            __('Submission Details', 'mobility-trailblazers'),
            array($this, 'render_submission_details_meta_box'),
            'mt_submission',
            'normal',
            'high'
        );
        
        add_meta_box(
            'mt_voting_details',
            __('Voting Details', 'mobility-trailblazers'),
            array($this, 'render_voting_details_meta_box'),
            'mt_submission',
            'side',
            'default'
        );
    }
    
    /**
     * Render submission details meta box
     */
    public function render_submission_details_meta_box($post) {
        wp_nonce_field('mt_submission_details_nonce', 'mt_submission_details_nonce');
        
        $submission_data = get_post_meta($post->ID, '_mt_submission_data', true);
        $submission_status = get_post_meta($post->ID, '_mt_submission_status', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="mt_submission_status"><?php _e('Status', 'mobility-trailblazers'); ?></label></th>
                <td>
                    <select name="mt_submission_status" id="mt_submission_status">
                        <option value="pending" <?php selected($submission_status, 'pending'); ?>><?php _e('Pending', 'mobility-trailblazers'); ?></option>
                        <option value="approved" <?php selected($submission_status, 'approved'); ?>><?php _e('Approved', 'mobility-trailblazers'); ?></option>
                        <option value="rejected" <?php selected($submission_status, 'rejected'); ?>><?php _e('Rejected', 'mobility-trailblazers'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render voting details meta box
     */
    public function render_voting_details_meta_box($post) {
        $vote_count = get_post_meta($post->ID, '_mt_vote_count', true);
        $average_score = get_post_meta($post->ID, '_mt_average_score', true);
        
        ?>
        <p><strong><?php _e('Total Votes:', 'mobility-trailblazers'); ?></strong> <?php echo esc_html($vote_count ?: 0); ?></p>
        <p><strong><?php _e('Average Score:', 'mobility-trailblazers'); ?></strong> <?php echo esc_html($average_score ?: 'N/A'); ?></p>
        <?php
    }
    
    /**
     * Save meta box data
     */
    public function save_meta_boxes($post_id) {
        // Check if nonce is valid
        if (!isset($_POST['mt_submission_details_nonce']) || !wp_verify_nonce($_POST['mt_submission_details_nonce'], 'mt_submission_details_nonce')) {
            return;
        }
        
        // Check if user has permission to edit the post
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save submission status
        if (isset($_POST['mt_submission_status'])) {
            update_post_meta($post_id, '_mt_submission_status', sanitize_text_field($_POST['mt_submission_status']));
        }
    }
}

// Alias for backward compatibility
class MetaBoxes extends MT_Meta_Boxes {} 