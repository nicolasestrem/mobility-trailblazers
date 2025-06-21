<?php
/**
 * Assignment Service
 *
 * @package MobilityTrailblazers
 * @since 1.0.7
 */

namespace MobilityTrailblazers\Services;

use MobilityTrailblazers\Interfaces\MT_Service_Interface;
use MobilityTrailblazers\Repositories\MT_Assignment_Repository;

class MT_Assignment_Service implements MT_Service_Interface {
    
    private $repository;
    private $errors = array();
    
    public function __construct() {
        $this->repository = new MT_Assignment_Repository();
    }
    
    /**
     * Process assignment creation
     */
    public function process($data) {
        $this->errors = array();
        
        if (!$this->validate($data)) {
            return false;
        }
        
        // Handle different assignment types
        if (isset($data['assignment_type'])) {
            switch ($data['assignment_type']) {
                case 'manual':
                    return $this->process_manual_assignment($data);
                case 'auto':
                    return $this->process_auto_assignment($data);
                case 'bulk':
                    return $this->process_bulk_assignment($data);
                default:
                    $this->errors[] = __('Invalid assignment type', 'mobility-trailblazers');
                    return false;
            }
        }
        
        // Single assignment
        return $this->create_assignment($data['jury_member_id'], $data['candidate_id']);
    }
    
    /**
     * Validate assignment data
     */
    public function validate($data) {
        $valid = true;
        
        if (isset($data['assignment_type']) && $data['assignment_type'] === 'auto') {
            // Auto assignment validation
            if (empty($data['candidates_per_jury'])) {
                $this->errors[] = __('Number of candidates per jury member is required', 'mobility-trailblazers');
                $valid = false;
            }
        } else {
            // Manual assignment validation
            if (empty($data['jury_member_id'])) {
                $this->errors[] = __('Jury member is required', 'mobility-trailblazers');
                $valid = false;
            }
            
            if (empty($data['candidate_id']) && empty($data['candidate_ids'])) {
                $this->errors[] = __('At least one candidate is required', 'mobility-trailblazers');
                $valid = false;
            }
        }
        
        return $valid;
    }
    
    /**
     * Get validation errors
     */
    public function get_errors() {
        return $this->errors;
    }
    
    /**
     * Process manual assignment
     */
    private function process_manual_assignment($data) {
        $jury_member_id = intval($data['jury_member_id']);
        
        // Handle multiple candidates
        if (!empty($data['candidate_ids']) && is_array($data['candidate_ids'])) {
            $success = true;
            foreach ($data['candidate_ids'] as $candidate_id) {
                if (!$this->create_assignment($jury_member_id, intval($candidate_id))) {
                    $success = false;
                }
            }
            return $success;
        }
        
        // Single candidate
        return $this->create_assignment($jury_member_id, intval($data['candidate_id']));
    }
    
    /**
     * Process auto assignment
     */
    private function process_auto_assignment($data) {
        $candidates_per_jury = intval($data['candidates_per_jury']);
        
        // Get all active jury members
        $jury_members = $this->get_active_jury_members();
        
        if (empty($jury_members)) {
            $this->errors[] = __('No active jury members found', 'mobility-trailblazers');
            return false;
        }
        
        // Get all candidates
        $candidates = $this->get_available_candidates();
        
        if (empty($candidates)) {
            $this->errors[] = __('No candidates available for assignment', 'mobility-trailblazers');
            return false;
        }
        
        // Clear existing assignments if requested
        if (!empty($data['clear_existing'])) {
            $this->clear_all_assignments();
        }
        
        // Distribute candidates evenly
        return $this->distribute_candidates($jury_members, $candidates, $candidates_per_jury);
    }
    
    /**
     * Process bulk assignment
     */
    private function process_bulk_assignment($data) {
        if (empty($data['assignments']) || !is_array($data['assignments'])) {
            $this->errors[] = __('No assignments provided', 'mobility-trailblazers');
            return false;
        }
        
        return $this->repository->bulk_create($data['assignments']);
    }
    
    /**
     * Create single assignment
     */
    public function create_assignment($jury_member_id, $candidate_id) {
        // Check if assignment already exists
        if ($this->repository->exists($jury_member_id, $candidate_id)) {
            $this->errors[] = sprintf(
                __('Assignment already exists for jury member %d and candidate %d', 'mobility-trailblazers'),
                $jury_member_id,
                $candidate_id
            );
            return false;
        }
        
        $result = $this->repository->create(array(
            'jury_member_id' => $jury_member_id,
            'candidate_id' => $candidate_id
        ));
        
        if ($result) {
            // Trigger action
            do_action('mt_assignment_created', $candidate_id, $jury_member_id);
        }
        
        return $result;
    }
    
    /**
     * Get active jury members
     */
    private function get_active_jury_members() {
        $args = array(
            'role' => 'mt_jury_member',
            'meta_query' => array(
                array(
                    'key' => 'mt_jury_status',
                    'value' => 'active',
                    'compare' => '='
                )
            )
        );
        
        $users = get_users($args);
        return wp_list_pluck($users, 'ID');
    }
    
    /**
     * Get available candidates
     */
    private function get_available_candidates() {
        $args = array(
            'post_type' => 'mt_candidate',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        );
        
        return get_posts($args);
    }
    
    /**
     * Distribute candidates evenly among jury members
     */
    private function distribute_candidates($jury_members, $candidates, $candidates_per_jury) {
        $assignments = array();
        $candidate_index = 0;
        $total_candidates = count($candidates);
        
        // Shuffle for random distribution
        shuffle($candidates);
        
        foreach ($jury_members as $jury_member_id) {
            for ($i = 0; $i < $candidates_per_jury; $i++) {
                if ($candidate_index >= $total_candidates) {
                    // Start over if we run out of candidates
                    $candidate_index = 0;
                }
                
                $assignments[] = array(
                    'jury_member_id' => $jury_member_id,
                    'candidate_id' => $candidates[$candidate_index]
                );
                
                $candidate_index++;
            }
        }
        
        return $this->repository->bulk_create($assignments);
    }
    
    /**
     * Clear all assignments
     */
    public function clear_all_assignments() {
        global $wpdb;
        $table = $wpdb->prefix . 'mt_jury_assignments';
        return $wpdb->query("TRUNCATE TABLE {$table}");
    }
    
    /**
     * Remove assignment
     */
    public function remove_assignment($jury_member_id, $candidate_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'mt_jury_assignments';
        $result = $wpdb->delete(
            $table,
            array(
                'jury_member_id' => $jury_member_id,
                'candidate_id' => $candidate_id
            )
        );
        if ($result) {
            do_action('mt_assignment_removed', $candidate_id, $jury_member_id);
        }
        return $result;
    }
    
    /**
     * Get assignment statistics
     */
    public function get_statistics() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'mt_jury_assignments';
        
        // Get total candidates
        $total_candidates = wp_count_posts('mt_candidate')->publish;
        
        // Get total jury members
        $total_jury = wp_count_posts('mt_jury_member')->publish;
        
        // Get assigned candidates count
        $assigned_candidates = $wpdb->get_var("SELECT COUNT(DISTINCT candidate_id) FROM {$table} WHERE is_active = 1");
        
        // Get unassigned candidates count
        $unassigned_candidates = $total_candidates - $assigned_candidates;
        
        // Get total assignments
        $total_assignments = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE is_active = 1");
        
        // Get assignments per jury member
        $assignments_per_jury = $wpdb->get_results("
            SELECT jury_member_id, COUNT(*) as count 
            FROM {$table} 
            WHERE is_active = 1 
            GROUP BY jury_member_id
        ");
        
        $stats = array(
            'total_candidates' => intval($total_candidates),
            'total_jury_members' => intval($total_jury),
            'assigned_candidates' => intval($assigned_candidates),
            'unassigned_candidates' => intval($unassigned_candidates),
            'total_assignments' => intval($total_assignments),
            'assignments_per_jury' => $assignments_per_jury
        );
        
        return $stats;
    }
    
    /**
     * Get all assignments for export
     */
    public function get_all_assignments_for_export() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'mt_jury_assignments';
        
        $query = "
            SELECT 
                jm.post_title as jury_name,
                jm_meta.meta_value as jury_email,
                c.post_title as candidate_name,
                c_cat.name as category,
                a.assignment_date
            FROM {$table} a
            LEFT JOIN {$wpdb->posts} jm ON a.jury_member_id = jm.ID
            LEFT JOIN {$wpdb->postmeta} jm_meta ON jm.ID = jm_meta.post_id AND jm_meta.meta_key = '_mt_email'
            LEFT JOIN {$wpdb->posts} c ON a.candidate_id = c.ID
            LEFT JOIN {$wpdb->term_relationships} c_tr ON c.ID = c_tr.object_id
            LEFT JOIN {$wpdb->term_taxonomy} c_tt ON c_tr.term_taxonomy_id = c_tt.term_taxonomy_id
            LEFT JOIN {$wpdb->terms} c_cat ON c_tt.term_id = c_cat.term_id
            WHERE a.is_active = 1 
            AND c_tt.taxonomy = 'mt_category'
            ORDER BY a.assignment_date DESC
        ";
        
        return $wpdb->get_results($query, ARRAY_A);
    }
}