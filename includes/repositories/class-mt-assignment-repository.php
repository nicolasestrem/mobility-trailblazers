<?php
/**
 * Assignment Repository
 *
 * @package MobilityTrailblazers
 * @since 1.0.7
 */

namespace MobilityTrailblazers\Repositories;

use MobilityTrailblazers\Interfaces\MT_Repository_Interface;

class MT_Assignment_Repository implements MT_Repository_Interface {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'mt_jury_assignments';
    }
    
    /**
     * Find assignment by ID
     */
    public function find($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ));
    }
    
    /**
     * Find all assignments with filters
     */
    public function find_all($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'jury_member_id' => null,
            'candidate_id' => null,
            'is_active' => null,
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'assignment_date',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        $values = array();
        
        if ($args['jury_member_id']) {
            $where[] = 'jury_member_id = %d';
            $values[] = $args['jury_member_id'];
        }
        
        if ($args['candidate_id']) {
            $where[] = 'candidate_id = %d';
            $values[] = $args['candidate_id'];
        }
        
        if ($args['is_active'] !== null) {
            $where[] = 'is_active = %d';
            $values[] = $args['is_active'];
        }
        
        $where_clause = implode(' AND ', $where);
        $order_clause = sprintf('%s %s', $args['orderby'], $args['order']);
        
        $query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY {$order_clause} LIMIT %d OFFSET %d";
        $values[] = $args['limit'];
        $values[] = $args['offset'];
        
        return $wpdb->get_results($wpdb->prepare($query, $values));
    }
    
    /**
     * Create new assignment
     */
    public function create($data) {
        global $wpdb;
        
        $defaults = array(
            'assignment_date' => current_time('mysql'),
            'is_active' => 1
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $result = $wpdb->insert($this->table_name, $data);
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Update assignment
     */
    public function update($id, $data) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            $data,
            array('id' => $id)
        );
    }
    
    /**
     * Delete assignment
     */
    public function delete($id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            array('id' => $id)
        );
    }
    
    /**
     * Bulk create assignments
     */
    public function bulk_create($assignments) {
        global $wpdb;
        
        if (empty($assignments)) {
            return false;
        }
        
        $values = array();
        $place_holders = array();
        
        foreach ($assignments as $assignment) {
            $place_holders[] = "(%d, %d, %s, %d)";
            array_push($values, 
                $assignment['jury_member_id'],
                $assignment['candidate_id'],
                current_time('mysql'),
                1 // is_active
            );
        }
        
        $query = "INSERT INTO {$this->table_name} (jury_member_id, candidate_id, assignment_date, is_active) VALUES ";
        $query .= implode(', ', $place_holders);
        
        return $wpdb->query($wpdb->prepare($query, $values));
    }
    
    /**
     * Delete assignments by jury member
     */
    public function delete_by_jury_member($jury_member_id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            array('jury_member_id' => $jury_member_id)
        );
    }
    
    /**
     * Delete assignments by candidate
     */
    public function delete_by_candidate($candidate_id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            array('candidate_id' => $candidate_id)
        );
    }
    
    /**
     * Check if assignment exists
     */
    public function exists($jury_member_id, $candidate_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE jury_member_id = %d AND candidate_id = %d",
            $jury_member_id,
            $candidate_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Get assignment statistics
     */
    public function get_statistics() {
        global $wpdb;
        
        $stats = array();
        
        // Total assignments
        $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        
        // Assignments by status
        $status_counts = $wpdb->get_results(
            "SELECT is_active, COUNT(*) as count FROM {$this->table_name} GROUP BY is_active"
        );
        
        foreach ($status_counts as $status) {
            $stats['by_status'][$status->is_active ? 'active' : 'inactive'] = $status->count;
        }
        
        // Average assignments per jury member
        $stats['avg_per_jury'] = $wpdb->get_var(
            "SELECT AVG(assignment_count) FROM (
                SELECT COUNT(*) as assignment_count 
                FROM {$this->table_name} 
                GROUP BY jury_member_id
            ) as counts"
        );
        
        return $stats;
    }
}