<?php
/**
 * Evaluation Repository
 *
 * @package MobilityTrailblazers
 * @since 1.0.7
 */

namespace MobilityTrailblazers\Repositories;

use MobilityTrailblazers\Interfaces\MT_Repository_Interface;

class MT_Evaluation_Repository implements MT_Repository_Interface {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'mt_evaluations';
    }
    
    /**
     * Find evaluation by ID
     */
    public function find($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ));
    }
    
    /**
     * Find all evaluations with filters
     */
    public function find_all($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'jury_member_id' => null,
            'candidate_id' => null,
            'limit' => 50,
            'offset' => 0
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
        
        $where_clause = implode(' AND ', $where);
        
        $query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} LIMIT %d OFFSET %d";
        $values[] = $args['limit'];
        $values[] = $args['offset'];
        
        return $wpdb->get_results($wpdb->prepare($query, $values));
    }
    
    /**
     * Create new evaluation
     */
    public function create($data) {
        global $wpdb;
        
        $defaults = array(
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $result = $wpdb->insert($this->table_name, $data);
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Update evaluation
     */
    public function update($id, $data) {
        global $wpdb;
        
        $data['updated_at'] = current_time('mysql');
        
        return $wpdb->update(
            $this->table_name,
            $data,
            array('id' => $id)
        );
    }
    
    /**
     * Delete evaluation
     */
    public function delete($id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            array('id' => $id)
        );
    }
    
    /**
     * Get evaluations by jury member
     */
    public function get_by_jury_member($jury_member_id) {
        return $this->find_all(array(
            'jury_member_id' => $jury_member_id
        ));
    }
    
    /**
     * Get evaluations for candidate
     */
    public function get_by_candidate($candidate_id) {
        return $this->find_all(array(
            'candidate_id' => $candidate_id
        ));
    }
    
    /**
     * Check if evaluation exists
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
     * Get average score for a candidate
     *
     * @param int $candidate_id
     * @return float
     */
    public function get_average_score_for_candidate($candidate_id) {
        global $wpdb;
        $score = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(total_score) FROM {$this->table_name} WHERE candidate_id = %d AND status = 'completed'",
            $candidate_id
        ));
        return $score ? round($score, 2) : 0;
    }
    
    /**
     * Get statistics for evaluations (updated version)
     *
     * @param array $args
     * @return array
     */
    public function get_statistics($args = array()) {
        global $wpdb;
        $defaults = array(
            'year' => date('Y'),
            'phase' => null
        );
        $args = wp_parse_args($args, $defaults);
        $stats = array(
            'total_evaluations' => 0,
            'completed_evaluations' => 0,
            'draft_evaluations' => 0,
            'average_score' => 0,
            'evaluations_by_jury' => array()
        );
        // Total evaluations
        $stats['total_evaluations'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name}"
        );
        // Completed evaluations
        $stats['completed_evaluations'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'completed'"
        );
        // Draft evaluations
        $stats['draft_evaluations'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'draft'"
        );
        // Average score
        $avg = $wpdb->get_var(
            "SELECT AVG(total_score) FROM {$this->table_name} WHERE status = 'completed'"
        );
        $stats['average_score'] = $avg ? round($avg, 2) : 0;
        // Evaluations by jury member
        $by_jury = $wpdb->get_results(
            "SELECT jury_member_id, COUNT(*) as count FROM {$this->table_name} GROUP BY jury_member_id",
            ARRAY_A
        );
        foreach ($by_jury as $row) {
            $stats['evaluations_by_jury'][$row['jury_member_id']] = $row['count'];
        }
        return $stats;
    }
}