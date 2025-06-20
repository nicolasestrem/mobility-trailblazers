<?php
/**
 * Voting Repository
 *
 * @package MobilityTrailblazers
 * @since 1.0.7
 */

namespace MobilityTrailblazers\Repositories;

use MobilityTrailblazers\Interfaces\MT_Repository_Interface;

class MT_Voting_Repository implements MT_Repository_Interface {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'mt_votes';
    }
    
    /**
     * Find vote by ID
     */
    public function find($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ));
    }
    
    /**
     * Find all votes with filters
     */
    public function find_all($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'candidate_id' => null,
            'voter_email' => null,
            'limit' => 50,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        $values = array();
        
        if ($args['candidate_id']) {
            $where[] = 'candidate_id = %d';
            $values[] = $args['candidate_id'];
        }
        
        if ($args['voter_email']) {
            $where[] = 'voter_email = %s';
            $values[] = $args['voter_email'];
        }
        
        $where_clause = implode(' AND ', $where);
        
        $query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} LIMIT %d OFFSET %d";
        $values[] = $args['limit'];
        $values[] = $args['offset'];
        
        return $wpdb->get_results($wpdb->prepare($query, $values));
    }
    
    /**
     * Create new vote
     */
    public function create($data) {
        global $wpdb;
        
        $result = $wpdb->insert($this->table_name, $data);
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Update vote (not typically used)
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
     * Delete vote
     */
    public function delete($id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            array('id' => $id)
        );
    }
    
    /**
     * Check if voter has already voted
     */
    public function has_voted($voter_email, $candidate_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE voter_email = %s AND candidate_id = %d",
            $voter_email,
            $candidate_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Get vote counts by candidate
     */
    public function get_vote_counts($category_id = null) {
        global $wpdb;
        
        if ($category_id) {
            // Get votes for candidates in specific category
            $query = "
                SELECT c.ID as candidate_id, c.post_title as candidate_name, COUNT(v.id) as vote_count
                FROM {$wpdb->posts} c
                LEFT JOIN {$this->table_name} v ON c.ID = v.candidate_id
                INNER JOIN {$wpdb->term_relationships} tr ON c.ID = tr.object_id
                WHERE c.post_type = 'mt_candidate' 
                AND c.post_status = 'publish'
                AND tr.term_taxonomy_id = %d
                GROUP BY c.ID
                ORDER BY vote_count DESC
            ";
            return $wpdb->get_results($wpdb->prepare($query, $category_id));
        } else {
            // Get all votes
            $query = "
                SELECT c.ID as candidate_id, c.post_title as candidate_name, COUNT(v.id) as vote_count
                FROM {$wpdb->posts} c
                LEFT JOIN {$this->table_name} v ON c.ID = v.candidate_id
                WHERE c.post_type = 'mt_candidate' 
                AND c.post_status = 'publish'
                GROUP BY c.ID
                ORDER BY vote_count DESC
            ";
            return $wpdb->get_results($query);
        }
    }
    
    /**
     * Clear all votes
     */
    public function clear_all() {
        global $wpdb;
        
        return $wpdb->query("TRUNCATE TABLE {$this->table_name}");
    }
    
    /**
     * Create backup of votes
     */
    public function create_backup() {
        global $wpdb;
        $backup_table = $wpdb->prefix . 'mt_vote_backups';
        
        // Insert backup record
        $wpdb->insert($backup_table, array(
            'backup_data' => json_encode($this->find_all(array('limit' => -1))),
            'created_at' => current_time('mysql'),
            'created_by' => get_current_user_id()
        ));
        
        return $wpdb->insert_id;
    }
}