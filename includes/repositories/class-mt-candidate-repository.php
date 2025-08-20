<?php
/**
 * Candidate Repository
 *
 * @package MobilityTrailblazers
 * @since 2.5.26
 */

namespace MobilityTrailblazers\Repositories;

use MobilityTrailblazers\Interfaces\MT_Repository_Interface;
use MobilityTrailblazers\Core\MT_Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Candidate_Repository
 *
 * Handles database operations for candidates
 */
class MT_Candidate_Repository implements MT_Repository_Interface {
    
    /**
     * Table name
     *
     * @var string
     */
    private $table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'mt_candidates';
    }
    
    /**
     * Find a candidate by ID
     *
     * @param int $id Candidate ID
     * @return object|null
     */
    public function find($id) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE id = %d",
            $id
        );
        
        $result = $wpdb->get_row($sql);
        
        if ($result && !empty($result->description_sections)) {
            $result->description_sections = json_decode($result->description_sections, true);
        }
        
        return $result;
    }
    
    /**
     * Find all candidates matching criteria
     *
     * @param array $args Query arguments
     * @return array
     */
    public function find_all($args = []) {
        global $wpdb;
        
        $defaults = [
            'orderby' => 'name',
            'order' => 'ASC',
            'limit' => -1,
            'offset' => 0
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $sql = "SELECT * FROM {$this->table}";
        
        // Add WHERE conditions if needed
        $where = [];
        if (!empty($args['organization'])) {
            $where[] = $wpdb->prepare("organization = %s", $args['organization']);
        }
        if (!empty($args['country'])) {
            $where[] = $wpdb->prepare("country = %s", $args['country']);
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        // Add ORDER BY
        $allowed_orderby = ['id', 'name', 'organization', 'created_at', 'updated_at'];
        if (in_array($args['orderby'], $allowed_orderby)) {
            $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
            $sql .= " ORDER BY {$args['orderby']} {$order}";
        }
        
        // Add LIMIT
        if ($args['limit'] > 0) {
            $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $args['limit'], $args['offset']);
        }
        
        $results = $wpdb->get_results($sql);
        
        // Decode JSON fields
        foreach ($results as &$result) {
            if (!empty($result->description_sections)) {
                $result->description_sections = json_decode($result->description_sections, true);
            }
        }
        
        return $results;
    }
    
    /**
     * Create a new candidate
     *
     * @param array $data Candidate data
     * @return int|false Insert ID on success, false on failure
     */
    public function create($data) {
        global $wpdb;
        
        // Ensure required fields
        if (empty($data['name']) || empty($data['slug'])) {
            MT_Logger::warning('Candidate creation failed: missing required fields', ['data' => $data]);
            return false;
        }
        
        // Encode JSON fields
        if (isset($data['description_sections']) && is_array($data['description_sections'])) {
            $data['description_sections'] = wp_json_encode($data['description_sections'], JSON_UNESCAPED_UNICODE);
        }
        
        // Sanitize data
        $insert_data = [
            'slug' => sanitize_title($data['slug']),
            'name' => sanitize_text_field($data['name']),
            'organization' => isset($data['organization']) ? sanitize_text_field($data['organization']) : null,
            'position' => isset($data['position']) ? sanitize_text_field($data['position']) : null,
            'country' => isset($data['country']) ? sanitize_text_field($data['country']) : null,
            'linkedin_url' => isset($data['linkedin_url']) ? esc_url_raw($data['linkedin_url']) : null,
            'website_url' => isset($data['website_url']) ? esc_url_raw($data['website_url']) : null,
            'article_url' => isset($data['article_url']) ? esc_url_raw($data['article_url']) : null,
            'description_sections' => isset($data['description_sections']) ? $data['description_sections'] : null,
            'photo_attachment_id' => isset($data['photo_attachment_id']) ? absint($data['photo_attachment_id']) : null,
            'post_id' => isset($data['post_id']) ? absint($data['post_id']) : null,
            'import_id' => isset($data['import_id']) ? sanitize_text_field($data['import_id']) : null
        ];
        
        $result = $wpdb->insert($this->table, $insert_data);
        
        if ($result === false) {
            MT_Logger::database_error('INSERT', $this->table, $wpdb->last_error, ['data' => $insert_data]);
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update an existing candidate
     *
     * @param int $id Candidate ID
     * @param array $data Updated data
     * @return bool True on success, false on failure
     */
    public function update($id, $data) {
        global $wpdb;
        
        // Encode JSON fields
        if (isset($data['description_sections']) && is_array($data['description_sections'])) {
            $data['description_sections'] = wp_json_encode($data['description_sections'], JSON_UNESCAPED_UNICODE);
        }
        
        // Sanitize update data
        $update_data = [];
        
        if (isset($data['slug'])) {
            $update_data['slug'] = sanitize_title($data['slug']);
        }
        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
        }
        if (isset($data['organization'])) {
            $update_data['organization'] = sanitize_text_field($data['organization']);
        }
        if (isset($data['position'])) {
            $update_data['position'] = sanitize_text_field($data['position']);
        }
        if (isset($data['country'])) {
            $update_data['country'] = sanitize_text_field($data['country']);
        }
        if (isset($data['linkedin_url'])) {
            $update_data['linkedin_url'] = esc_url_raw($data['linkedin_url']);
        }
        if (isset($data['website_url'])) {
            $update_data['website_url'] = esc_url_raw($data['website_url']);
        }
        if (isset($data['article_url'])) {
            $update_data['article_url'] = esc_url_raw($data['article_url']);
        }
        if (isset($data['description_sections'])) {
            $update_data['description_sections'] = $data['description_sections'];
        }
        if (isset($data['photo_attachment_id'])) {
            $update_data['photo_attachment_id'] = absint($data['photo_attachment_id']);
        }
        if (isset($data['post_id'])) {
            $update_data['post_id'] = absint($data['post_id']);
        }
        
        $result = $wpdb->update(
            $this->table,
            $update_data,
            ['id' => $id],
            null,
            ['%d']
        );
        
        if ($result === false) {
            MT_Logger::database_error('UPDATE', $this->table, $wpdb->last_error, ['id' => $id, 'data' => $update_data]);
            return false;
        }
        
        return true;
    }
    
    /**
     * Delete a candidate
     *
     * @param int $id Candidate ID
     * @return bool True on success, false on failure
     */
    public function delete($id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table,
            ['id' => $id],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Find candidate by slug
     *
     * @param string $slug Candidate slug
     * @return object|null
     */
    public function find_by_slug($slug) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE slug = %s",
            $slug
        );
        
        $result = $wpdb->get_row($sql);
        
        if ($result && !empty($result->description_sections)) {
            $result->description_sections = json_decode($result->description_sections, true);
        }
        
        return $result;
    }
    
    /**
     * Find candidate by name
     *
     * @param string $name Candidate name
     * @return object|null
     */
    public function find_by_name($name) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE name = %s",
            $name
        );
        
        $result = $wpdb->get_row($sql);
        
        if ($result && !empty($result->description_sections)) {
            $result->description_sections = json_decode($result->description_sections, true);
        }
        
        return $result;
    }
    
    /**
     * Find candidate by post ID
     *
     * @param int $post_id WordPress post ID
     * @return object|null
     */
    public function find_by_post_id($post_id) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE post_id = %d",
            $post_id
        );
        
        $result = $wpdb->get_row($sql);
        
        if ($result && !empty($result->description_sections)) {
            $result->description_sections = json_decode($result->description_sections, true);
        }
        
        return $result;
    }
    
    /**
     * Update description sections for a candidate
     *
     * @param int $id Candidate ID
     * @param array $sections Description sections
     * @return bool
     */
    public function update_description_sections($id, $sections) {
        global $wpdb;
        
        $json = wp_json_encode($sections, JSON_UNESCAPED_UNICODE);
        
        $result = $wpdb->update(
            $this->table,
            ['description_sections' => $json],
            ['id' => $id],
            ['%s'],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Truncate the candidates table
     *
     * @return bool
     */
    public function truncate() {
        global $wpdb;
        
        $result = $wpdb->query("TRUNCATE TABLE {$this->table}");
        
        return $result !== false;
    }
    
    /**
     * Get total count of candidates
     *
     * @return int
     */
    public function count() {
        global $wpdb;
        
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table}");
    }
}
