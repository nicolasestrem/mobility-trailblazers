<?php
/**
 * Admin AJAX Handler
 *
 * @package MobilityTrailblazers
 * @since 1.0.7
 */

namespace MobilityTrailblazers\Ajax;

class MT_Admin_Ajax extends MT_Base_Ajax {
    
    /**
     * Register AJAX hooks
     */
    protected function register_hooks() {
        // Assignment management
        add_action('wp_ajax_mt_get_candidates_for_assignment', array($this, 'get_candidates_for_assignment'));
        
        // Backup management
        add_action('wp_ajax_mt_create_backup', array($this, 'create_backup'));
        add_action('wp_ajax_mt_restore_backup', array($this, 'restore_backup'));
        add_action('wp_ajax_mt_delete_backup', array($this, 'delete_backup'));
        add_action('wp_ajax_mt_export_backup', array($this, 'export_backup'));
        
        // Import/Export
        add_action('wp_ajax_mt_export_candidates', array($this, 'export_candidates'));
        add_action('wp_ajax_mt_export_jury', array($this, 'export_jury'));
        add_action('wp_ajax_mt_export_votes', array($this, 'export_votes'));
        add_action('wp_ajax_mt_import_data', array($this, 'import_data'));
        
        // Jury user management
        add_action('wp_ajax_mt_create_jury_user', array($this, 'create_jury_user'));
        add_action('wp_ajax_mt_send_jury_credentials', array($this, 'send_jury_credentials'));
        
        // Public registration
        add_action('wp_ajax_mt_submit_registration', array($this, 'submit_registration'));
        add_action('wp_ajax_nopriv_mt_submit_registration', array($this, 'submit_registration'));
    }
    
    /**
     * Get candidates for assignment
     */
    public function get_candidates_for_assignment() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_manage_assignments');
        
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_mt_status',
                    'value' => array('approved', 'shortlisted'),
                    'compare' => 'IN',
                ),
            ),
        ));
        
        $candidate_list = array();
        foreach ($candidates as $candidate) {
            $candidate_list[] = array(
                'id' => $candidate->ID,
                'title' => $candidate->post_title,
                'company' => get_post_meta($candidate->ID, '_mt_company', true),
                'status' => get_post_meta($candidate->ID, '_mt_status', true),
            );
        }
        
        $this->success($candidate_list);
    }
    
    /**
     * Create backup
     */
    public function create_backup() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_manage_backups');
        
        // Implementation would go here
        $this->error(__('Backup functionality not yet implemented', 'mobility-trailblazers'));
    }
    
    /**
     * Restore backup
     */
    public function restore_backup() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_manage_backups');
        
        // Implementation would go here
        $this->error(__('Restore functionality not yet implemented', 'mobility-trailblazers'));
    }
    
    /**
     * Delete backup
     */
    public function delete_backup() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_manage_backups');
        
        // Implementation would go here
        $this->error(__('Delete backup functionality not yet implemented', 'mobility-trailblazers'));
    }
    
    /**
     * Export backup
     */
    public function export_backup() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_manage_backups');
        
        // Implementation would go here
        $this->error(__('Export backup functionality not yet implemented', 'mobility-trailblazers'));
    }
    
    /**
     * Export candidates
     */
    public function export_candidates() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_export_data');
        
        // Implementation would go here
        $this->error(__('Export candidates functionality not yet implemented', 'mobility-trailblazers'));
    }
    
    /**
     * Export jury
     */
    public function export_jury() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_export_data');
        
        // Implementation would go here
        $this->error(__('Export jury functionality not yet implemented', 'mobility-trailblazers'));
    }
    
    /**
     * Export votes
     */
    public function export_votes() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_export_data');
        
        // Implementation would go here
        $this->error(__('Export votes functionality not yet implemented', 'mobility-trailblazers'));
    }
    
    /**
     * Import data
     */
    public function import_data() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_import_data');
        
        // Implementation would go here
        $this->error(__('Import data functionality not yet implemented', 'mobility-trailblazers'));
    }
    
    /**
     * Create jury user
     */
    public function create_jury_user() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_manage_jury_members');
        
        $jury_member_id = $this->get_param('jury_member_id', 0);
        
        if (!$jury_member_id) {
            $this->error(__('Invalid jury member.', 'mobility-trailblazers'));
        }
        
        // Create user
        $user_id = MT_Roles::create_jury_user($jury_member_id, array(
            'send_notification' => $this->get_param('send_notification') === 'true',
        ));
        
        if (is_wp_error($user_id)) {
            $this->error($user_id->get_error_message());
        }
        
        $this->success(
            array('user_id' => $user_id),
            __('User account created successfully.', 'mobility-trailblazers')
        );
    }
    
    /**
     * Send jury credentials
     */
    public function send_jury_credentials() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_manage_jury_members');
        
        // Implementation would go here
        $this->error(__('Send credentials functionality not yet implemented', 'mobility-trailblazers'));
    }
    
    /**
     * Submit registration
     */
    public function submit_registration() {
        // Check if registration is open
        if (!mt_is_registration_open()) {
            $this->error(__('Registration is currently closed.', 'mobility-trailblazers'));
        }
        
        $this->verify_nonce('mt_registration');
        
        // Validate required fields
        $required_fields = array('name', 'email', 'company', 'position', 'innovation_title');
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $this->error(sprintf(__('Please fill in all required fields. Missing: %s', 'mobility-trailblazers'), $field));
            }
        }
        
        // Create candidate post
        $post_data = array(
            'post_title' => sanitize_text_field($_POST['name']),
            'post_content' => sanitize_textarea_field($_POST['innovation_description'] ?? ''),
            'post_type' => 'mt_candidate',
            'post_status' => 'pending', // Requires approval
        );
        
        $candidate_id = wp_insert_post($post_data);
        
        if (is_wp_error($candidate_id)) {
            $this->error(__('Failed to create candidate profile. Please try again.', 'mobility-trailblazers'));
        }
        
        // Save candidate meta
        update_post_meta($candidate_id, '_mt_email', sanitize_email($_POST['email']));
        update_post_meta($candidate_id, '_mt_company', sanitize_text_field($_POST['company']));
        update_post_meta($candidate_id, '_mt_position', sanitize_text_field($_POST['position']));
        update_post_meta($candidate_id, '_mt_innovation_title', sanitize_text_field($_POST['innovation_title']));
        update_post_meta($candidate_id, '_mt_phone', sanitize_text_field($_POST['phone'] ?? ''));
        update_post_meta($candidate_id, '_mt_website', esc_url_raw($_POST['website'] ?? ''));
        update_post_meta($candidate_id, '_mt_status', 'pending');
        
        // Send confirmation email
        $notification_service = new \MobilityTrailblazers\Services\MT_Notification_Service();
        $notification_service->send_registration_confirmation($_POST['email'], $_POST['name']);
        
        // Send notification to admins
        $admin_emails = mt_get_admin_emails();
        if (!empty($admin_emails)) {
            $admin_subject = sprintf(__('New candidate registration: %s', 'mobility-trailblazers'), $_POST['name']);
            $admin_message = sprintf(
                __('A new candidate has registered:

Name: %s
Email: %s
Company: %s
Position: %s
Innovation: %s

Please review the application: %s

The application is currently in pending status and requires approval.', 'mobility-trailblazers'),
                sanitize_text_field($_POST['name']),
                sanitize_email($_POST['email']),
                sanitize_text_field($_POST['company']),
                sanitize_text_field($_POST['position']),
                sanitize_text_field($_POST['innovation_title']),
                admin_url('post.php?post=' . $candidate_id . '&action=edit')
            );
            
            foreach ($admin_emails as $admin_email) {
                mt_send_email($admin_email, $admin_subject, $admin_message);
            }
        }
        
        // Log registration
        mt_log('New candidate registration', 'info', array(
            'candidate_id' => $candidate_id,
            'name' => sanitize_text_field($_POST['name']),
            'company' => sanitize_text_field($_POST['company']),
        ));
        
        // Redirect URL if provided
        $redirect_url = '';
        if (!empty($_POST['redirect_url'])) {
            $redirect_url = esc_url_raw($_POST['redirect_url']);
        }
        
        $this->success(
            array(
                'redirect_url' => $redirect_url,
                'candidate_id' => $candidate_id,
            ),
            __('Thank you for your registration! We will review your application and get back to you soon.', 'mobility-trailblazers')
        );
    }
} 