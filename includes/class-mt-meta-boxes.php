<?php
/**
 * Meta Boxes Handler
 *
 * @package MobilityTrailblazers
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Meta_Boxes
 * Handles all custom meta boxes
 */
class MT_Meta_Boxes {
    
    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_candidate_meta'));
        add_action('save_post', array($this, 'save_jury_meta'));
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        // Candidate meta boxes
        add_meta_box(
            'mt_candidate_details',
            __('Candidate Details', 'mobility-trailblazers'),
            array($this, 'candidate_details_meta_box'),
            'mt_candidate',
            'normal',
            'high'
        );
        
        add_meta_box(
            'mt_candidate_evaluation',
            __('Evaluation Status', 'mobility-trailblazers'),
            array($this, 'candidate_evaluation_meta_box'),
            'mt_candidate',
            'side',
            'default'
        );
        
        // Jury member meta boxes
        add_meta_box(
            'mt_jury_details',
            __('Jury Member Details', 'mobility-trailblazers'),
            array($this, 'jury_details_meta_box'),
            'mt_jury',
            'normal',
            'high'
        );
    }
    
    /**
     * Candidate details meta box
     */
    public function candidate_details_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('mt_save_candidate_meta', 'mt_candidate_meta_nonce');
        
        // Get existing values
        $organization = get_post_meta($post->ID, 'organization', true);
        $website = get_post_meta($post->ID, 'website', true);
        $contact_person = get_post_meta($post->ID, 'contact_person', true);
        $email = get_post_meta($post->ID, 'email', true);
        $phone = get_post_meta($post->ID, 'phone', true);
        $innovation_description = get_post_meta($post->ID, 'innovation_description', true);
        $impact_description = get_post_meta($post->ID, 'impact_description', true);
        ?>
        
        <table class="form-table">
            <tr>
                <th><label for="organization"><?php _e('Organization', 'mobility-trailblazers'); ?></label></th>
                <td><input type="text" id="organization" name="organization" value="<?php echo esc_attr($organization); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="website"><?php _e('Website', 'mobility-trailblazers'); ?></label></th>
                <td><input type="url" id="website" name="website" value="<?php echo esc_url($website); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="contact_person"><?php _e('Contact Person', 'mobility-trailblazers'); ?></label></th>
                <td><input type="text" id="contact_person" name="contact_person" value="<?php echo esc_attr($contact_person); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="email"><?php _e('Email', 'mobility-trailblazers'); ?></label></th>
                <td><input type="email" id="email" name="email" value="<?php echo esc_attr($email); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="phone"><?php _e('Phone', 'mobility-trailblazers'); ?></label></th>
                <td><input type="tel" id="phone" name="phone" value="<?php echo esc_attr($phone); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="innovation_description"><?php _e('Innovation Description', 'mobility-trailblazers'); ?></label></th>
                <td><textarea id="innovation_description" name="innovation_description" rows="5" class="large-text"><?php echo esc_textarea($innovation_description); ?></textarea></td>
            </tr>
            <tr>
                <th><label for="impact_description"><?php _e('Impact Description', 'mobility-trailblazers'); ?></label></th>
                <td><textarea id="impact_description" name="impact_description" rows="5" class="large-text"><?php echo esc_textarea($impact_description); ?></textarea></td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Candidate evaluation meta box
     */
    public function candidate_evaluation_meta_box($post) {
        global $wpdb;
        
        // Get evaluation statistics
        $total_evaluations = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores WHERE candidate_id = %d",
            $post->ID
        ));
        
        $avg_score = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(total_score) FROM {$wpdb->prefix}mt_candidate_scores WHERE candidate_id = %d",
            $post->ID
        ));
        
        $total_votes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes WHERE candidate_id = %d",
            $post->ID
        ));
        
        $avg_rating = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(rating) FROM {$wpdb->prefix}mt_votes WHERE candidate_id = %d",
            $post->ID
        ));
        ?>
        
        <div class="mt-evaluation-stats">
            <p>
                <strong><?php _e('Total Evaluations:', 'mobility-trailblazers'); ?></strong> 
                <?php echo intval($total_evaluations); ?>
            </p>
            
            <?php if ($avg_score) : ?>
            <p>
                <strong><?php _e('Average Score:', 'mobility-trailblazers'); ?></strong> 
                <?php echo number_format($avg_score, 2); ?>/25
            </p>
            <?php endif; ?>
            
            <p>
                <strong><?php _e('Total Votes:', 'mobility-trailblazers'); ?></strong> 
                <?php echo intval($total_votes); ?>
            </p>
            
            <?php if ($avg_rating) : ?>
            <p>
                <strong><?php _e('Average Rating:', 'mobility-trailblazers'); ?></strong> 
                <?php echo number_format($avg_rating, 2); ?>/10
            </p>
            <?php endif; ?>
        </div>
        
        <hr>
        
        <p>
            <a href="<?php echo admin_url('admin.php?page=mt-voting-results&candidate=' . $post->ID); ?>" class="button">
                <?php _e('View Detailed Results', 'mobility-trailblazers'); ?>
            </a>
        </p>
        <?php
    }
    
    /**
     * Jury details meta box
     */
    public function jury_details_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('mt_save_jury_meta', 'mt_jury_meta_nonce');
        
        // Get existing values
        $email = get_post_meta($post->ID, 'email', true);
        $organization = get_post_meta($post->ID, 'organization', true);
        $position = get_post_meta($post->ID, 'position', true);
        $bio = get_post_meta($post->ID, 'bio', true);
        $user_id = get_post_meta($post->ID, 'user_id', true);
        ?>
        
        <table class="form-table">
            <tr>
                <th><label for="email"><?php _e('Email', 'mobility-trailblazers'); ?></label></th>
                <td><input type="email" id="email" name="email" value="<?php echo esc_attr($email); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="organization"><?php _e('Organization', 'mobility-trailblazers'); ?></label></th>
                <td><input type="text" id="organization" name="organization" value="<?php echo esc_attr($organization); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="position"><?php _e('Position', 'mobility-trailblazers'); ?></label></th>
                <td><input type="text" id="position" name="position" value="<?php echo esc_attr($position); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="bio"><?php _e('Biography', 'mobility-trailblazers'); ?></label></th>
                <td><textarea id="bio" name="bio" rows="5" class="large-text"><?php echo esc_textarea($bio); ?></textarea></td>
            </tr>
            <tr>
                <th><label for="user_id"><?php _e('Linked WordPress User', 'mobility-trailblazers'); ?></label></th>
                <td>
                    <?php
                    wp_dropdown_users(array(
                        'name' => 'user_id',
                        'selected' => $user_id,
                        'show_option_none' => __('— Select User —', 'mobility-trailblazers'),
                        'option_none_value' => '0'
                    ));
                    ?>
                    <p class="description"><?php _e('Link this jury member to a WordPress user account for login access.', 'mobility-trailblazers'); ?></p>
                </td>
            </tr>
        </table>
        
        <?php if ($user_id) : ?>
        <hr>
        <h4><?php _e('Assignment Statistics', 'mobility-trailblazers'); ?></h4>
        <?php
        $assigned_candidates = get_post_meta($post->ID, 'assigned_candidates', true);
        $assigned_count = is_array($assigned_candidates) ? count($assigned_candidates) : 0;
        
        global $wpdb;
        $completed_evaluations = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores WHERE jury_member_id = %d",
            $user_id
        ));
        ?>
        <p>
            <strong><?php _e('Assigned Candidates:', 'mobility-trailblazers'); ?></strong> 
            <?php echo $assigned_count; ?>
        </p>
        <p>
            <strong><?php _e('Completed Evaluations:', 'mobility-trailblazers'); ?></strong> 
            <?php echo $completed_evaluations; ?>
        </p>
        <?php if ($assigned_count > 0) : ?>
        <p>
            <strong><?php _e('Completion Rate:', 'mobility-trailblazers'); ?></strong> 
            <?php echo round(($completed_evaluations / $assigned_count) * 100, 1); ?>%
        </p>
        <?php endif; ?>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Save candidate meta
     */
    public function save_candidate_meta($post_id) {
        // Check if our nonce is set
        if (!isset($_POST['mt_candidate_meta_nonce'])) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['mt_candidate_meta_nonce'], 'mt_save_candidate_meta')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check post type
        if (get_post_type($post_id) !== 'mt_candidate') {
            return;
        }
        
        // Save meta fields
        $fields = array(
            'organization',
            'website',
            'contact_person',
            'email',
            'phone',
            'innovation_description',
            'impact_description'
        );
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }
    
    /**
     * Save jury meta
     */
    public function save_jury_meta($post_id) {
        // Check if our nonce is set
        if (!isset($_POST['mt_jury_meta_nonce'])) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['mt_jury_meta_nonce'], 'mt_save_jury_meta')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check post type
        if (get_post_type($post_id) !== 'mt_jury') {
            return;
        }
        
        // Save meta fields
        $fields = array(
            'email',
            'organization',
            'position',
            'bio',
            'user_id'
        );
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // If user_id is set, ensure the user has the jury member role
        if (isset($_POST['user_id']) && $_POST['user_id'] > 0) {
            $user = get_user_by('id', $_POST['user_id']);
            if ($user && !in_array('mt_jury_member', $user->roles)) {
                $user->add_role('mt_jury_member');
            }
        }
    }
} 