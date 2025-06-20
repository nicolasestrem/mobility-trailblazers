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
    
    public function find($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ));
    }
    
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
    
    public function create($data) {
        global $wpdb;
        
        $defaults = array(
            'vote_time' => current_time('mysql'),
            'ip_address' => $_SERVER['REMOTE_ADDR']
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $result = $wpdb->insert($this->table_name, $data);
        
        return $result ? $wpdb->insert_id : false;
    }
    
    public function update($id, $data) {
        global $wpdb;
        return $wpdb->update($this->table_name, $data, array('id' => $id));
    }
    
    public function delete($id) {
        global $wpdb;
        return $wpdb->delete($this->table_name, array('id' => $id));
    }
    
    /**
     * Check if voter has already voted
     */
    public function has_voted($voter_email, $candidate_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
             WHERE voter_email = %s AND candidate_id = %d",
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
            // Join with posts and term relationships for category filtering
            return $wpdb->get_results($wpdb->prepare(
                "SELECT v.candidate_id, COUNT(*) as vote_count, p.post_title as candidate_name
                 FROM {$this->table_name} v
                 JOIN {$wpdb->posts} p ON v.candidate_id = p.ID
                 JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
                 WHERE tr.term_taxonomy_id = %d
                 GROUP BY v.candidate_id
                 ORDER BY vote_count DESC",
                $category_id
            ));
        }
        
        return $wpdb->get_results(
            "SELECT v.candidate_id, COUNT(*) as vote_count, p.post_title as candidate_name
             FROM {$this->table_name} v
             JOIN {$wpdb->posts} p ON v.candidate_id = p.ID
             GROUP BY v.candidate_id
             ORDER BY vote_count DESC"
        );
    }
    
    /**
     * Create backup of votes
     */
    public function create_backup() {
        global $wpdb;
        $backup_table = $wpdb->prefix . 'mt_vote_backups';
        
        // Create backup record
        $wpdb->insert($backup_table, array(
            'backup_date' => current_time('mysql'),
            'created_by' => get_current_user_id()
        ));
        
        $backup_id = $wpdb->insert_id;
        
        // Copy votes to backup
        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$backup_table}_data 
             SELECT %d, v.* FROM {$this->table_name} v",
            $backup_id
        ));
        
        return $backup_id;
    }
    
    /**
     * Clear all votes
     */
    public function clear_all() {
        global $wpdb;
        return $wpdb->query("TRUNCATE TABLE {$this->table_name}");
    }
}