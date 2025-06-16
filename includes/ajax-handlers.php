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
}

// Initialize the AJAX handlers
AjaxHandlers::get_instance(); 