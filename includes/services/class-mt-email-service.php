<?php
/**
 * Email Service for Mobility Trailblazers
 *
 * @package MobilityTrailblazers
 */

namespace MobilityTrailblazers\Services;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Email Service Class
 */
class MT_Email_Service {
    
    /**
     * Send evaluation reminder email
     *
     * @param int $jury_id Jury member ID
     * @param int $pending_count Number of pending evaluations
     * @param string $deadline Optional deadline
     * @return bool
     */
    public function send_evaluation_reminder($jury_id, $pending_count, $deadline = '') {
        $user = get_user_by('ID', $jury_id);
        if (!$user) {
            return false;
        }
        
        // Get user's preferred language
        $locale = get_user_meta($jury_id, 'mt_preferred_language', true);
        if (empty($locale)) {
            $locale = get_locale();
        }
        
        // Switch to user's language
        $original_locale = get_locale();
        if ($locale !== $original_locale) {
            switch_to_locale($locale);
        }
        
        // Prepare template variables
        $jury_name = get_user_meta($jury_id, 'mt_display_name', true) ?: $user->display_name;
        $dashboard_url = home_url('/jury-dashboard/');
        
        // Load email template
        ob_start();
        include MT_PLUGIN_DIR . 'templates/emails/evaluation-reminder.php';
        $email_content = ob_get_clean();
        
        // Prepare email
        $to = $user->user_email;
        $subject = sprintf(
            __('[Mobility Trailblazers] Reminder: %d Evaluations Pending', 'mobility-trailblazers'),
            $pending_count
        );
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('blogname') . ' <' . get_option('admin_email') . '>'
        ];
        
        // Send email
        $result = wp_mail($to, $subject, $email_content, $headers);
        
        // Switch back to original locale
        if ($locale !== $original_locale) {
            switch_to_locale($original_locale);
        }
        
        // Log the email
        if ($result) {
            do_action('mt_email_sent', 'evaluation_reminder', $jury_id, [
                'pending_count' => $pending_count,
                'deadline' => $deadline
            ]);
        }
        
        return $result;
    }
    
    /**
     * Send assignment notification email
     *
     * @param int $jury_id Jury member ID
     * @param array $candidate_ids Array of candidate IDs
     * @return bool
     */
    public function send_assignment_notification($jury_id, $candidate_ids) {
        $user = get_user_by('ID', $jury_id);
        if (!$user || empty($candidate_ids)) {
            return false;
        }
        
        // Get user's preferred language
        $locale = get_user_meta($jury_id, 'mt_preferred_language', true);
        if (empty($locale)) {
            $locale = get_locale();
        }
        
        // Switch to user's language
        $original_locale = get_locale();
        if ($locale !== $original_locale) {
            switch_to_locale($locale);
        }
        
        // Prepare candidates data
        $candidates = [];
        foreach ($candidate_ids as $candidate_id) {
            $candidate = get_post($candidate_id);
            if ($candidate) {
                $categories = wp_get_post_terms($candidate_id, 'mt_award_category');
                $candidates[] = [
                    'name' => get_post_meta($candidate_id, '_mt_display_name', true) ?: $candidate->post_title,
                    'organization' => get_post_meta($candidate_id, '_mt_organization', true),
                    'category' => !empty($categories) ? $categories[0]->name : ''
                ];
            }
        }
        
        // Prepare template variables
        $jury_name = get_user_meta($jury_id, 'mt_display_name', true) ?: $user->display_name;
        $dashboard_url = home_url('/jury-dashboard/');
        $total_assignments = count($candidates);
        
        // Load email template
        ob_start();
        include MT_PLUGIN_DIR . 'templates/emails/assignment-notification.php';
        $email_content = ob_get_clean();
        
        // Prepare email
        $to = $user->user_email;
        $subject = sprintf(
            __('[Mobility Trailblazers] %d New Candidate Assignment(s)', 'mobility-trailblazers'),
            $total_assignments
        );
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_option('blogname') . ' <' . get_option('admin_email') . '>'
        ];
        
        // Send email
        $result = wp_mail($to, $subject, $email_content, $headers);
        
        // Switch back to original locale
        if ($locale !== $original_locale) {
            switch_to_locale($original_locale);
        }
        
        // Log the email
        if ($result) {
            do_action('mt_email_sent', 'assignment_notification', $jury_id, [
                'candidate_ids' => $candidate_ids,
                'total' => $total_assignments
            ]);
        }
        
        return $result;
    }
    
    /**
     * Send bulk reminders to all jury members with pending evaluations
     *
     * @return array Results array
     */
    public function send_bulk_reminders() {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => []
        ];
        
        // Get all jury members
        $jury_members = get_users([
            'role' => 'mt_jury',
            'fields' => 'ID'
        ]);
        
        $assignment_repo = new \MobilityTrailblazers\Repositories\MT_Assignment_Repository();
        
        foreach ($jury_members as $jury_id) {
            // Get pending assignments
            $pending = $assignment_repo->find_all([
                'jury_member_id' => $jury_id,
                'status' => 'pending'
            ]);
            
            if (empty($pending)) {
                $results['skipped']++;
                continue;
            }
            
            // Send reminder
            if ($this->send_evaluation_reminder($jury_id, count($pending))) {
                $results['sent']++;
            } else {
                $results['failed']++;
                $results['errors'][] = sprintf(
                    __('Failed to send reminder to user ID %d', 'mobility-trailblazers'),
                    $jury_id
                );
            }
        }
        
        return $results;
    }
    
    /**
     * Get available email templates
     *
     * @return array
     */
    public function get_email_templates() {
        $templates_dir = MT_PLUGIN_DIR . 'templates/emails/';
        $templates = [];
        
        if (is_dir($templates_dir)) {
            $files = glob($templates_dir . '*.php');
            foreach ($files as $file) {
                $template_name = basename($file, '.php');
                $templates[$template_name] = [
                    'file' => $file,
                    'name' => ucwords(str_replace('-', ' ', $template_name))
                ];
            }
        }
        
        return $templates;
    }
}
