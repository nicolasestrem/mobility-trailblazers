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
        ), ARRAY_A);
    }
    
    public function find_all($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             ORDER BY {$args['orderby']} {$args['order']} 
             LIMIT %d OFFSET %d",
            $args['limit'],
            $args['offset']
        ), ARRAY_A);
    }
    
    public function create($data) {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_name,
            array(
                'candidate_id' => $data['candidate_id'],
                'voter_email' => $data['voter_email'],
                'voter_name' => $data['voter_name'],
                'ip_address' => $data['ip_address'],
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
        
        return $wpdb->insert_id;
    }
    
    public function update($id, $data) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            $data,
            array('id' => $id)
        );
    }
    
    public function delete($id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
    }
    
    // Additional methods specific to voting
    public function has_voted($email, $candidate_id = null) {
        global $wpdb;
        
        $where = "voter_email = %s";
        $values = array($email);
        
        if ($candidate_id) {
            $where .= " AND candidate_id = %d";
            $values[] = $candidate_id;
        }
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where}",
            $values
        ));
        
        return $count > 0;
    }
    
    public function get_vote_counts() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT candidate_id, COUNT(*) as vote_count 
             FROM {$this->table_name} 
             GROUP BY candidate_id 
             ORDER BY vote_count DESC",
            ARRAY_A
        );
    }
    
    public function create_backup() {
        global $wpdb;
        
        $backup_table = $this->table_name . '_backup_' . date('YmdHis');
        
        $wpdb->query("CREATE TABLE {$backup_table} LIKE {$this->table_name}");
        $wpdb->query("INSERT INTO {$backup_table} SELECT * FROM {$this->table_name}");
        
        return $backup_table;
    }
    
    public function clear_all() {
        global $wpdb;
        return $wpdb->query("TRUNCATE TABLE {$this->table_name}");
    }
    
    public function get_results() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT c.ID, c.post_title, COUNT(v.id) as vote_count,
                    (SELECT meta_value FROM {$wpdb->postmeta} 
                     WHERE post_id = c.ID AND meta_key = '_mt_profile_image' LIMIT 1) as profile_image
             FROM {$wpdb->posts} c
             LEFT JOIN {$this->table_name} v ON c.ID = v.candidate_id
             WHERE c.post_type = 'mt_candidate' AND c.post_status = 'publish'
             GROUP BY c.ID
             ORDER BY vote_count DESC",
            ARRAY_A
        );
    }
    
    public function get_vote_details($candidate_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT voter_name, voter_email, created_at, ip_address
             FROM {$this->table_name}
             WHERE candidate_id = %d
             ORDER BY created_at DESC",
            $candidate_id
        ), ARRAY_A);
    }
}