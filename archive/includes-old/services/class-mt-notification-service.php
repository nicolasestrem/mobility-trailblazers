<?php
/**
 * Notification Service
 *
 * @package MobilityTrailblazers
 * @since 1.0.7
 */

namespace MobilityTrailblazers\Services;

class MT_Notification_Service {
    
    /**
     * Send assignment notification
     */
    public function send_assignment_notification($jury_member_id, $candidate_ids) {
        $user = get_user_by('id', $jury_member_id);
        if (!$user) {
            return false;
        }
        
        $candidates = array();
        foreach ($candidate_ids as $candidate_id) {
            $candidates[] = get_the_title($candidate_id);
        }
        
        $subject = __('New Candidate Assignments', 'mobility-trailblazers');
        $message = sprintf(
            __('Hello %s,\n\nYou have been assigned the following candidates for evaluation:\n\n%s\n\nPlease log in to complete your evaluations.\n\nBest regards,\nMobility Trailblazers Team', 'mobility-trailblazers'),
            $user->display_name,
            implode("\n", array_map(function($c) { return "- " . $c; }, $candidates))
        );
        
        return wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Send evaluation reminder
     */
    public function send_evaluation_reminder($jury_member_id, $pending_count) {
        $user = get_user_by('id', $jury_member_id);
        if (!$user) {
            return false;
        }
        
        $subject = __('Evaluation Reminder', 'mobility-trailblazers');
        $message = sprintf(
            __('Hello %s,\n\nYou have %d pending evaluations. Please log in to complete them before the deadline.\n\nBest regards,\nMobility Trailblazers Team', 'mobility-trailblazers'),
            $user->display_name,
            $pending_count
        );
        
        return wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Send voting confirmation
     */
    public function send_voting_confirmation($voter_email, $candidate_name) {
        $subject = __('Vote Confirmation', 'mobility-trailblazers');
        $message = sprintf(
            __('Thank you for voting!\n\nYour vote for %s has been recorded successfully.\n\nBest regards,\nMobility Trailblazers Team', 'mobility-trailblazers'),
            $candidate_name
        );
        
        return wp_mail($voter_email, $subject, $message);
    }
    
    /**
     * Send bulk notifications
     */
    public function send_bulk_notifications($type, $recipients, $data = array()) {
        $sent = 0;
        
        foreach ($recipients as $recipient_id) {
            switch ($type) {
                case 'assignment':
                    if ($this->send_assignment_notification($recipient_id, $data['candidates'])) {
                        $sent++;
                    }
                    break;
                case 'reminder':
                    if ($this->send_evaluation_reminder($recipient_id, $data['pending_count'])) {
                        $sent++;
                    }
                    break;
            }
        }
        
        return $sent;
    }
}