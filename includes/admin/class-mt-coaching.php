<?php
/**
 * Coaching Dashboard
 *
 * @package MobilityTrailblazers
 * @since 2.2.29
 */

namespace MobilityTrailblazers\Admin;

use MobilityTrailblazers\Core\MT_Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Coaching
 * 
 * Provides coaching dashboard for jury evaluation management
 */
class MT_Coaching {
    
    /**
     * Initialize coaching features
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_coaching_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_coaching_scripts']);
        
        // AJAX handlers
        add_action('wp_ajax_mt_send_coaching_reminder', [$this, 'send_coaching_reminder']);
        add_action('wp_ajax_mt_export_coaching_report', [$this, 'export_coaching_report']);
    }
    
    /**
     * Add coaching menu item
     */
    public function add_coaching_menu() {
        add_submenu_page(
            'mobility-trailblazers',
            __('Coaching Dashboard', 'mobility-trailblazers'),
            __('Coaching', 'mobility-trailblazers'),
            'manage_options',
            'mt-coaching',
            [$this, 'render_coaching_page']
        );
    }
    
    /**
     * Enqueue coaching scripts and styles
     */
    public function enqueue_coaching_scripts($hook) {
        if ($hook !== 'mobility-trailblazers_page_mt-coaching') {
            return;
        }
        
        wp_enqueue_style('mt-admin-style', MT_PLUGIN_URL . 'assets/css/admin.css', [], MT_VERSION);
        wp_enqueue_script('mt-coaching', MT_PLUGIN_URL . 'assets/js/coaching.js', ['jquery'], MT_VERSION, true);
        
        wp_localize_script('mt-coaching', 'mt_coaching', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_coaching_nonce'),
            'i18n' => [
                'confirm_reminder' => __('Send reminder emails to all jury members with incomplete evaluations?', 'mobility-trailblazers'),
                'confirm_single_reminder' => __('Send reminder to %s?', 'mobility-trailblazers'),
                'reminder_sent' => __('Reminder Sent', 'mobility-trailblazers'),
                'sending' => __('Sending reminders...', 'mobility-trailblazers'),
                'sent' => __('Reminders sent successfully', 'mobility-trailblazers'),
                'export_success' => __('Report exported successfully', 'mobility-trailblazers'),
                'error' => __('An error occurred. Please try again.', 'mobility-trailblazers')
            ]
        ]);
    }
    
    /**
     * Render coaching page
     */
    public function render_coaching_page() {
        // Get coaching statistics
        $stats = $this->get_coaching_statistics();
        
        include MT_PLUGIN_DIR . 'templates/admin/coaching.php';
    }
    
    /**
     * Get coaching statistics
     */
    public function get_coaching_statistics() {
        global $wpdb;
        
        $stats = $wpdb->get_results("
            SELECT 
                u.ID,
                u.display_name,
                u.user_email,
                COUNT(DISTINCT a.candidate_id) as assigned,
                COUNT(DISTINCT e.candidate_id) as completed,
                COUNT(DISTINCT CASE WHEN e.status = 'draft' THEN e.candidate_id END) as drafts,
                AVG(CASE 
                    WHEN e.criterion_1 IS NOT NULL 
                    THEN e.criterion_1 + e.criterion_2 + e.criterion_3 + e.criterion_4 + e.criterion_5 
                    ELSE NULL 
                END) as avg_score,
                MAX(e.updated_at) as last_activity
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->prefix}mt_jury_assignments a ON u.ID = a.jury_member_id
            LEFT JOIN {$wpdb->prefix}mt_evaluations e ON u.ID = e.jury_member_id AND a.candidate_id = e.candidate_id
            WHERE u.ID IN (
                SELECT user_id FROM {$wpdb->usermeta} 
                WHERE meta_key = 'mt_jury_member' AND meta_value = 'yes'
            )
            GROUP BY u.ID
            ORDER BY u.display_name ASC
        ");
        
        // Calculate overall statistics
        $total_assigned = 0;
        $total_completed = 0;
        $total_drafts = 0;
        
        foreach ($stats as $stat) {
            $total_assigned += $stat->assigned;
            $total_completed += $stat->completed;
            $total_drafts += $stat->drafts;
        }
        
        return [
            'jury_stats' => $stats,
            'total_assigned' => $total_assigned,
            'total_completed' => $total_completed,
            'total_drafts' => $total_drafts,
            'completion_rate' => $total_assigned > 0 ? round(($total_completed / $total_assigned) * 100, 1) : 0
        ];
    }
    
    /**
     * AJAX handler for sending coaching reminders
     */
    public function send_coaching_reminder() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mt_coaching_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'mobility-trailblazers')]);
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'mobility-trailblazers')]);
            return;
        }
        
        $type = sanitize_text_field($_POST['type'] ?? 'incomplete');
        
        // Get jury members needing reminders
        $stats = $this->get_coaching_statistics();
        $sent_count = 0;
        
        foreach ($stats['jury_stats'] as $jury) {
            // Skip if completed all assignments
            if ($jury->assigned <= $jury->completed && $type === 'incomplete') {
                continue;
            }
            
            // Send reminder email
            $subject = __('Reminder: Mobility Trailblazers Evaluations Pending', 'mobility-trailblazers');
            $pending = $jury->assigned - $jury->completed;
            
            $message = sprintf(
                __('Dear %s,\n\nYou have %d pending evaluations for the Mobility Trailblazers Awards.\n\nPlease log in to complete your evaluations: %s\n\nThank you for your participation.\n\nBest regards,\nMobility Trailblazers Team', 'mobility-trailblazers'),
                $jury->display_name,
                $pending,
                home_url('/jury-dashboard/')
            );
            
            if (wp_mail($jury->user_email, $subject, $message)) {
                $sent_count++;
            }
        }
        
        // Log the action
        MT_Logger::info('Coaching reminders sent', [
            'type' => $type,
            'sent_count' => $sent_count,
            'user_id' => get_current_user_id()
        ]);
        
        wp_send_json_success([
            'message' => sprintf(__('%d reminder emails sent', 'mobility-trailblazers'), $sent_count),
            'sent_count' => $sent_count
        ]);
    }
    
    /**
     * AJAX handler for exporting coaching report
     */
    public function export_coaching_report() {
        // Verify nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'mt_coaching_nonce')) {
            wp_die(__('Security check failed', 'mobility-trailblazers'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'mobility-trailblazers'));
        }
        
        $stats = $this->get_coaching_statistics();
        
        // Set headers for CSV download
        $filename = 'coaching-report-' . date('Y-m-d-His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        fputcsv($output, [
            'Jury Member',
            'Email',
            'Assigned',
            'Completed',
            'Drafts',
            'Pending',
            'Progress %',
            'Avg Score',
            'Last Activity'
        ]);
        
        // Write data
        foreach ($stats['jury_stats'] as $jury) {
            $progress = $jury->assigned > 0 ? round(($jury->completed / $jury->assigned) * 100, 1) : 0;
            $pending = $jury->assigned - $jury->completed;
            
            fputcsv($output, [
                $jury->display_name,
                $jury->user_email,
                $jury->assigned,
                $jury->completed,
                $jury->drafts,
                $pending,
                $progress . '%',
                $jury->avg_score ? round($jury->avg_score, 1) : 'N/A',
                $jury->last_activity ?: 'Never'
            ]);
        }
        
        // Add summary row
        fputcsv($output, []); // Empty row
        fputcsv($output, [
            'TOTAL',
            '',
            $stats['total_assigned'],
            $stats['total_completed'],
            $stats['total_drafts'],
            $stats['total_assigned'] - $stats['total_completed'],
            $stats['completion_rate'] . '%',
            '',
            ''
        ]);
        
        fclose($output);
        
        // Log export
        MT_Logger::info('Coaching report exported', [
            'user_id' => get_current_user_id()
        ]);
        
        exit;
    }
}