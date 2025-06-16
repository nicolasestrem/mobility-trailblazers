<?php
namespace MobilityTrailblazers;

/**
 * AJAX Handlers for Mobility Trailblazers Plugin
 * 
 * This file contains all AJAX handlers for the plugin,
 * particularly for assignment management functionality.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AjaxHandlers {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->register_ajax_handlers();
    }
    
    /**
     * Register all AJAX handlers
     */
    private function register_ajax_handlers() {
        // Assignment handlers
        add_action('wp_ajax_mt_assign_candidates', array($this, 'handle_candidate_assignment'));
        add_action('wp_ajax_mt_auto_assign', array($this, 'handle_auto_assignment'));
        add_action('wp_ajax_mt_export_assignments', array($this, 'handle_export_assignments'));
        add_action('wp_ajax_mt_get_assignment_data', array($this, 'handle_get_assignment_data'));
    }
    
    /**
     * Handle candidate assignment
     */
    public function handle_candidate_assignment() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mt_nonce')) {
            wp_die('Security check failed');
        }
        
        $candidate_ids = array_map('intval', $_POST['candidate_ids']);
        $jury_member_id = intval($_POST['jury_member_id']);
        
        // Validate jury member exists
        if (!get_post($jury_member_id)) {
            wp_send_json_error(array('message' => 'Invalid jury member'));
        }
        
        // Update assignments
        $success_count = 0;
        foreach ($candidate_ids as $candidate_id) {
            if (get_post($candidate_id)) {
                update_post_meta($candidate_id, '_mt_assigned_jury_member', $jury_member_id);
                $success_count++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf('%d candidates assigned successfully', $success_count)
        ));
    }
    
    /**
     * Handle auto-assignment
     */
    public function handle_auto_assignment() {
        if (!wp_verify_nonce($_POST['nonce'], 'mt_nonce')) {
            wp_die('Security check failed');
        }
        
        $candidates_per_jury = intval($_POST['candidates_per_jury']);
        $algorithm = sanitize_text_field($_POST['algorithm']);
        $clear_existing = $_POST['clear_existing'] === 'true';
        
        // Validate input
        if ($candidates_per_jury < 1) {
            wp_send_json_error(array('message' => 'Invalid candidates per jury value'));
        }
        
        // Clear existing if requested
        if ($clear_existing) {
            $candidates = get_posts(array(
                'post_type' => 'mt_candidate',
                'posts_per_page' => -1
            ));
            foreach ($candidates as $candidate) {
                delete_post_meta($candidate->ID, '_mt_assigned_jury_member');
            }
        }
        
        // Implement auto-assignment logic
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_mt_assigned_jury_member',
                    'compare' => 'NOT EXISTS'
                )
            )
        ));
        
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1
        ));
        
        if (empty($jury_members)) {
            wp_send_json_error(array('message' => 'No jury members available'));
        }
        
        // Simple balanced distribution
        $jury_index = 0;
        $assignments_count = array();
        $success_count = 0;
        
        foreach ($candidates as $candidate) {
            $jury_member = $jury_members[$jury_index];
            $jury_id = $jury_member->ID;
            
            if (!isset($assignments_count[$jury_id])) {
                $assignments_count[$jury_id] = 0;
            }
            
            if ($assignments_count[$jury_id] < $candidates_per_jury) {
                update_post_meta($candidate->ID, '_mt_assigned_jury_member', $jury_id);
                $assignments_count[$jury_id]++;
                $success_count++;
            }
            
            // Move to next jury member
            $jury_index = ($jury_index + 1) % count($jury_members);
        }
        
        wp_send_json_success(array(
            'message' => 'Auto-assignment completed successfully',
            'assignments' => $assignments_count,
            'total_assigned' => $success_count
        ));
    }
    
    /**
     * Handle export assignments
     */
    public function handle_export_assignments() {
        if (!wp_verify_nonce($_POST['nonce'], 'mt_nonce')) {
            wp_die('Security check failed');
        }
        
        // Set CSV headers
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="assignments-' . date('Y-m-d') . '.csv"');
        
        // Create CSV
        $output = fopen('php://output', 'w');
        
        // Header row
        fputcsv($output, array('Candidate Name', 'Company', 'Category', 'Assigned To', 'Assignment Date'));
        
        // Get data
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1
        ));
        
        foreach ($candidates as $candidate) {
            $jury_id = get_post_meta($candidate->ID, '_mt_assigned_jury_member', true);
            $jury_name = '';
            
            if ($jury_id) {
                $jury = get_post($jury_id);
                $jury_name = $jury ? $jury->post_title : 'Unknown';
            }
            
            $categories = wp_get_post_terms($candidate->ID, 'mt_category');
            $category = !empty($categories) ? $categories[0]->name : '';
            
            fputcsv($output, array(
                $candidate->post_title,
                get_post_meta($candidate->ID, '_mt_company', true),
                $category,
                $jury_name,
                get_the_date('Y-m-d', $candidate->ID)
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Handle get assignment data AJAX request
     */
    public function handle_get_assignment_data() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mt_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'mobility-trailblazers')));
        }
        
        // Get candidates and jury data
        $candidates_data = $this->get_candidates_for_js();
        $jury_data = $this->get_jury_members_for_js();
        
        // Calculate statistics
        $total_candidates = count($candidates_data);
        $total_jury = count($jury_data);
        $assigned_count = count(array_filter($candidates_data, function($c) { return $c['assigned']; }));
        
        $completion_rate = $total_candidates > 0 ? (($assigned_count / $total_candidates) * 100) : 0;
        $avg_per_jury = $total_jury > 0 ? ($assigned_count / $total_jury) : 0;
        
        $statistics = array(
            'totalCandidates' => $total_candidates,
            'totalJury' => $total_jury,
            'assignedCount' => $assigned_count,
            'completionRate' => number_format($completion_rate, 1) . '%',
            'avgPerJury' => number_format($avg_per_jury, 1)
        );
        
        wp_send_json_success(array(
            'candidates' => $candidates_data,
            'jury_members' => $jury_data,
            'statistics' => $statistics
        ));
    }
    
    /**
     * Get candidates data for JavaScript
     */
    private function get_candidates_for_js() {
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        $candidates_data = array();
        foreach ($candidates as $candidate) {
            $jury_id = get_post_meta($candidate->ID, '_mt_assigned_jury_member', true);
            $categories = wp_get_post_terms($candidate->ID, 'mt_category');
            
            $candidates_data[] = array(
                'id' => $candidate->ID,
                'name' => $candidate->post_title,
                'company' => get_post_meta($candidate->ID, '_mt_company', true) ?: '',
                'position' => get_post_meta($candidate->ID, '_mt_position', true) ?: '',
                'category' => !empty($categories) ? $categories[0]->slug : '',
                'assigned' => !empty($jury_id),
                'jury_member_id' => $jury_id ?: null,
                'avatar' => get_the_post_thumbnail_url($candidate->ID, 'thumbnail') ?: null,
                'stage' => get_post_meta($candidate->ID, '_mt_stage', true) ?: 'pending',
                'description' => wp_trim_words($candidate->post_content, 20, '...'),
                'date_created' => get_the_date('Y-m-d H:i:s', $candidate->ID)
            );
        }
        
        return $candidates_data;
    }
    
    /**
     * Get jury members data for JavaScript
     */
    private function get_jury_members_for_js() {
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        $jury_data = array();
        foreach ($jury_members as $jury) {
            // Count assignments
            $assignments = get_posts(array(
                'post_type' => 'mt_candidate',
                'meta_query' => array(
                    array(
                        'key' => '_mt_assigned_jury_member',
                        'value' => $jury->ID
                    )
                ),
                'posts_per_page' => -1
            ));
            
            $jury_data[] = array(
                'id' => $jury->ID,
                'name' => $jury->post_title,
                'position' => get_post_meta($jury->ID, '_mt_position', true) ?: '',
                'expertise' => get_post_meta($jury->ID, '_mt_expertise', true) ?: '',
                'assignments' => count($assignments),
                'maxAssignments' => intval(get_post_meta($jury->ID, '_mt_max_assignments', true)) ?: 15,
                'role' => get_post_meta($jury->ID, '_mt_jury_role', true) ?: 'member',
                'avatar' => get_the_post_thumbnail_url($jury->ID, 'thumbnail') ?: null,
                'organization' => get_post_meta($jury->ID, '_mt_organization', true) ?: '',
                'status' => get_post_meta($jury->ID, '_mt_jury_status', true) ?: 'active',
                'voting_weight' => floatval(get_post_meta($jury->ID, '_mt_voting_weight', true)) ?: 1.0
            );
        }
        
        return $jury_data;
    }
}

// Initialize the AJAX handlers
AjaxHandlers::get_instance(); 