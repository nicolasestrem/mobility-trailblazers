<?php
/**
 * REST API functionality for Mobility Trailblazers
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_REST_API
 * 
 * Handles REST API endpoints for the plugin
 */
class MT_REST_API {
    
    /**
     * API namespace
     */
    const NAMESPACE = 'mobility-trailblazers/v1';
    
    /**
     * Initialize the REST API
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Submissions endpoints
        register_rest_route(self::NAMESPACE, '/submissions', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_submissions'),
            'permission_callback' => array($this, 'check_permissions'),
        ));
        
        register_rest_route(self::NAMESPACE, '/submissions', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_submission'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => $this->get_submission_args(),
        ));
        
        register_rest_route(self::NAMESPACE, '/submissions/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_submission'),
            'permission_callback' => array($this, 'check_permissions'),
        ));
        
        register_rest_route(self::NAMESPACE, '/submissions/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_submission'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => $this->get_submission_args(),
        ));
        
        register_rest_route(self::NAMESPACE, '/submissions/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_submission'),
            'permission_callback' => array($this, 'check_permissions'),
        ));
        
        // Voting endpoints
        register_rest_route(self::NAMESPACE, '/votes', array(
            'methods' => 'POST',
            'callback' => array($this, 'cast_vote'),
            'permission_callback' => array($this, 'check_voting_permissions'),
            'args' => $this->get_vote_args(),
        ));
        
        register_rest_route(self::NAMESPACE, '/votes/(?P<submission_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_votes'),
            'permission_callback' => array($this, 'check_permissions'),
        ));
        
        // Statistics endpoints
        register_rest_route(self::NAMESPACE, '/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_statistics'),
            'permission_callback' => array($this, 'check_permissions'),
        ));
    }
    
    /**
     * Get submissions
     */
    public function get_submissions($request) {
        $args = array(
            'post_type' => 'mt_submission',
            'post_status' => 'publish',
            'posts_per_page' => $request->get_param('per_page') ?: 10,
            'paged' => $request->get_param('page') ?: 1,
        );
        
        $submissions = get_posts($args);
        $data = array();
        
        foreach ($submissions as $submission) {
            $data[] = $this->prepare_submission_data($submission);
        }
        
        return rest_ensure_response($data);
    }
    
    /**
     * Get single submission
     */
    public function get_submission($request) {
        $id = $request->get_param('id');
        $submission = get_post($id);
        
        if (!$submission || $submission->post_type !== 'mt_submission') {
            return new WP_Error('not_found', __('Submission not found', 'mobility-trailblazers'), array('status' => 404));
        }
        
        return rest_ensure_response($this->prepare_submission_data($submission));
    }
    
    /**
     * Create submission
     */
    public function create_submission($request) {
        $args = array(
            'post_type' => 'mt_submission',
            'post_title' => sanitize_text_field($request->get_param('title')),
            'post_content' => wp_kses_post($request->get_param('content')),
            'post_status' => 'pending',
            'post_author' => get_current_user_id(),
        );
        
        $submission_id = wp_insert_post($args);
        
        if (is_wp_error($submission_id)) {
            return $submission_id;
        }
        
        // Save additional meta data
        if ($request->get_param('category')) {
            update_post_meta($submission_id, '_mt_category', sanitize_text_field($request->get_param('category')));
        }
        
        $submission = get_post($submission_id);
        return rest_ensure_response($this->prepare_submission_data($submission));
    }
    
    /**
     * Update submission
     */
    public function update_submission($request) {
        $id = $request->get_param('id');
        $submission = get_post($id);
        
        if (!$submission || $submission->post_type !== 'mt_submission') {
            return new WP_Error('not_found', __('Submission not found', 'mobility-trailblazers'), array('status' => 404));
        }
        
        $args = array(
            'ID' => $id,
            'post_title' => sanitize_text_field($request->get_param('title')),
            'post_content' => wp_kses_post($request->get_param('content')),
        );
        
        $result = wp_update_post($args);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $submission = get_post($id);
        return rest_ensure_response($this->prepare_submission_data($submission));
    }
    
    /**
     * Delete submission
     */
    public function delete_submission($request) {
        $id = $request->get_param('id');
        $submission = get_post($id);
        
        if (!$submission || $submission->post_type !== 'mt_submission') {
            return new WP_Error('not_found', __('Submission not found', 'mobility-trailblazers'), array('status' => 404));
        }
        
        $result = wp_delete_post($id, true);
        
        if (!$result) {
            return new WP_Error('delete_failed', __('Failed to delete submission', 'mobility-trailblazers'), array('status' => 500));
        }
        
        return rest_ensure_response(array('deleted' => true));
    }
    
    /**
     * Cast vote
     */
    public function cast_vote($request) {
        global $wpdb;
        
        $submission_id = intval($request->get_param('submission_id'));
        $score = intval($request->get_param('score'));
        $user_id = get_current_user_id();
        
        // Check if user already voted for this submission
        $existing_vote = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}mt_votes WHERE submission_id = %d AND user_id = %d",
            $submission_id,
            $user_id
        ));
        
        if ($existing_vote) {
            return new WP_Error('already_voted', __('You have already voted for this submission', 'mobility-trailblazers'), array('status' => 400));
        }
        
        // Insert vote
        $result = $wpdb->insert(
            $wpdb->prefix . 'mt_votes',
            array(
                'submission_id' => $submission_id,
                'user_id' => $user_id,
                'score' => $score,
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%d', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('vote_failed', __('Failed to cast vote', 'mobility-trailblazers'), array('status' => 500));
        }
        
        return rest_ensure_response(array('success' => true, 'vote_id' => $wpdb->insert_id));
    }
    
    /**
     * Get votes for submission
     */
    public function get_votes($request) {
        global $wpdb;
        
        $submission_id = $request->get_param('submission_id');
        
        $votes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}mt_votes WHERE submission_id = %d",
            $submission_id
        ));
        
        return rest_ensure_response($votes);
    }
    
    /**
     * Get statistics
     */
    public function get_statistics($request) {
        global $wpdb;
        
        $stats = array(
            'total_submissions' => wp_count_posts('mt_submission')->publish,
            'total_votes' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes"),
            'average_score' => $wpdb->get_var("SELECT AVG(score) FROM {$wpdb->prefix}mt_votes"),
        );
        
        return rest_ensure_response($stats);
    }
    
    /**
     * Check permissions
     */
    public function check_permissions($request) {
        return current_user_can('edit_posts');
    }
    
    /**
     * Check voting permissions
     */
    public function check_voting_permissions($request) {
        return is_user_logged_in();
    }
    
    /**
     * Get submission arguments
     */
    private function get_submission_args() {
        return array(
            'title' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'content' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'wp_kses_post',
            ),
            'category' => array(
                'required' => false,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        );
    }
    
    /**
     * Get vote arguments
     */
    private function get_vote_args() {
        return array(
            'submission_id' => array(
                'required' => true,
                'type' => 'integer',
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0;
                },
            ),
            'score' => array(
                'required' => true,
                'type' => 'integer',
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param >= 1 && $param <= 10;
                },
            ),
        );
    }
    
    /**
     * Prepare submission data for API response
     */
    private function prepare_submission_data($submission) {
        return array(
            'id' => $submission->ID,
            'title' => $submission->post_title,
            'content' => $submission->post_content,
            'status' => $submission->post_status,
            'author' => $submission->post_author,
            'date' => $submission->post_date,
            'category' => get_post_meta($submission->ID, '_mt_category', true),
            'vote_count' => get_post_meta($submission->ID, '_mt_vote_count', true),
            'average_score' => get_post_meta($submission->ID, '_mt_average_score', true),
        );
    }
} 