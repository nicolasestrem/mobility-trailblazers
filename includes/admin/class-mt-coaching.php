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
        add_action('wp_ajax_mt_export_coaching_report', [$this, 'export_coaching_report']);
        
        // Clear cache when evaluations or assignments change
        add_action('mt_evaluation_updated', [$this, 'clear_statistics_cache']);
        add_action('mt_assignment_updated', [$this, 'clear_statistics_cache']);
        add_action('save_post', [$this, 'clear_cache_on_jury_update']);
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
        $coaching_data = $this->get_coaching_statistics();
        
        include MT_PLUGIN_DIR . 'templates/admin/coaching.php';
    }
    
    /**
     * Get coaching statistics with caching
     */
    public function get_coaching_statistics() {
        // Check for cached data first (5 minute cache)
        $cache_key = 'mt_coaching_statistics';
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        global $wpdb;
        
        // First get all jury members from the custom post type
        $jury_members = get_posts([
            'post_type' => 'mt_jury_member',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        // Get jury member IDs and their associated user IDs
        $jury_member_ids = [];
        $jury_member_map = [];
        
        foreach ($jury_members as $jury_member) {
            $jury_member_ids[] = $jury_member->ID;
            // Map jury member post ID to user data
            $user_id = get_post_meta($jury_member->ID, '_mt_user_id', true);
            if ($user_id) {
                $user = get_user_by('id', $user_id);
                if ($user) {
                    $jury_member_map[$jury_member->ID] = [
                        'user_id' => $user_id,
                        'display_name' => $jury_member->post_title,
                        'user_email' => $user->user_email
                    ];
                }
            } else {
                // Fallback to jury member title if no user linked
                $jury_member_map[$jury_member->ID] = [
                    'user_id' => 0,
                    'display_name' => $jury_member->post_title,
                    'user_email' => get_post_meta($jury_member->ID, '_mt_email', true) ?: ''
                ];
            }
        }
        
        if (empty($jury_member_ids)) {
            return [
                'jury_stats' => [],
                'total_assigned' => 0,
                'total_completed' => 0,
                'total_drafts' => 0,
                'completion_rate' => 0
            ];
        }
        
        // Get statistics for each jury member
        $jury_member_ids_str = implode(',', array_map('intval', $jury_member_ids));
        
        $stats = $wpdb->get_results("
            SELECT 
                a.jury_member_id,
                COUNT(DISTINCT a.candidate_id) as assigned,
                COUNT(DISTINCT e.candidate_id) as completed,
                COUNT(DISTINCT CASE WHEN e.status = 'draft' THEN e.candidate_id END) as drafts,
                AVG(CASE 
                    WHEN e.courage_score IS NOT NULL 
                    THEN e.courage_score + e.innovation_score + e.implementation_score + e.relevance_score + e.visibility_score 
                    ELSE NULL 
                END) as avg_score,
                MAX(e.updated_at) as last_activity
            FROM {$wpdb->prefix}mt_jury_assignments a
            LEFT JOIN {$wpdb->prefix}mt_evaluations e ON a.jury_member_id = e.jury_member_id AND a.candidate_id = e.candidate_id
            WHERE a.jury_member_id IN ({$jury_member_ids_str})
            GROUP BY a.jury_member_id
        ");
        
        // Merge jury member data with statistics
        $final_stats = [];
        foreach ($stats as $stat) {
            if (isset($jury_member_map[$stat->jury_member_id])) {
                $jury_data = $jury_member_map[$stat->jury_member_id];
                $stat->ID = $jury_data['user_id'];
                $stat->display_name = $jury_data['display_name'];
                $stat->user_email = $jury_data['user_email'];
                $final_stats[] = $stat;
            }
        }
        
        // Handle jury members with no statistics
        foreach ($jury_member_map as $jury_id => $jury_data) {
            $found = false;
            foreach ($stats as $stat) {
                if ($stat->jury_member_id == $jury_id) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $final_stats[] = (object)[
                    'ID' => $jury_data['user_id'],
                    'display_name' => $jury_data['display_name'],
                    'user_email' => $jury_data['user_email'],
                    'assigned' => 0,
                    'completed' => 0,
                    'drafts' => 0,
                    'avg_score' => null,
                    'last_activity' => null
                ];
            }
        }
        
        // Sort by display name
        usort($final_stats, function($a, $b) {
            return strcasecmp($a->display_name, $b->display_name);
        });
        
        // Calculate overall statistics
        $total_assigned = 0;
        $total_completed = 0;
        $total_drafts = 0;
        
        foreach ($final_stats as $stat) {
            $total_assigned += $stat->assigned;
            $total_completed += $stat->completed;
            $total_drafts += $stat->drafts;
        }
        
        $statistics = [
            'jury_stats' => $final_stats,
            'total_assigned' => $total_assigned,
            'total_completed' => $total_completed,
            'total_drafts' => $total_drafts,
            'completion_rate' => $total_assigned > 0 ? round(($total_completed / $total_assigned) * 100, 1) : 0
        ];
        
        // Cache the results for 5 minutes (300 seconds)
        set_transient($cache_key, $statistics, 300);
        
        return $statistics;
    }
    
    /**
     * AJAX handler for exporting coaching report
     */
    public function export_coaching_report() {
        // Security: Use POST method for state-changing operations
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_die(__('Invalid request method', 'mobility-trailblazers'), 405);
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_coaching_nonce')) {
            wp_die(__('Security check failed', 'mobility-trailblazers'), 403);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'mobility-trailblazers'), 403);
        }
        
        $coaching_data = $this->get_coaching_statistics();
        
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
        foreach ($coaching_data['jury_stats'] as $jury) {
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
            $coaching_data['total_assigned'],
            $coaching_data['total_completed'],
            $coaching_data['total_drafts'],
            $coaching_data['total_assigned'] - $coaching_data['total_completed'],
            $coaching_data['completion_rate'] . '%',
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
    
    /**
     * Clear coaching statistics cache
     */
    public function clear_statistics_cache() {
        delete_transient('mt_coaching_statistics');
    }
    
    /**
     * Clear cache when jury member posts are updated
     */
    public function clear_cache_on_jury_update($post_id) {
        $post_type = get_post_type($post_id);
        if ($post_type === 'mt_jury_member') {
            $this->clear_statistics_cache();
        }
    }
}
