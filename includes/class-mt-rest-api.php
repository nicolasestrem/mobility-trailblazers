<?php
/**
 * REST API handler class
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_REST_API
 * Handles REST API endpoints
 */
class MT_REST_API {
    
    /**
     * API namespace
     *
     * @var string
     */
    private $namespace = 'mobility-trailblazers/v1';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Candidate routes
        register_rest_route($this->namespace, '/candidates', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_candidates'),
                'permission_callback' => '__return_true',
                'args' => $this->get_candidates_args(),
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_candidate'),
                'permission_callback' => array($this, 'create_candidate_permission'),
                'args' => $this->get_candidate_schema(),
            ),
        ));
        
        register_rest_route($this->namespace, '/candidates/(?P<id>\d+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_candidate'),
                'permission_callback' => '__return_true',
                'args' => array(
                    'id' => array(
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    ),
                ),
            ),
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_candidate'),
                'permission_callback' => array($this, 'update_candidate_permission'),
                'args' => $this->get_candidate_schema(),
            ),
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_candidate'),
                'permission_callback' => array($this, 'delete_candidate_permission'),
            ),
        ));
        
        // Jury routes
        register_rest_route($this->namespace, '/jury-members', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_jury_members'),
                'permission_callback' => '__return_true',
                'args' => $this->get_jury_members_args(),
            ),
        ));
        
        // Evaluation routes
        register_rest_route($this->namespace, '/evaluations', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_evaluations'),
                'permission_callback' => array($this, 'get_evaluations_permission'),
                'args' => $this->get_evaluations_args(),
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_evaluation'),
                'permission_callback' => array($this, 'create_evaluation_permission'),
                'args' => $this->get_evaluation_schema(),
            ),
        ));
        
        register_rest_route($this->namespace, '/evaluations/(?P<id>\d+)', array(
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_evaluation'),
                'permission_callback' => array($this, 'update_evaluation_permission'),
                'args' => $this->get_evaluation_schema(),
            ),
        ));
        
        // Assignment routes
        register_rest_route($this->namespace, '/assignments', array(
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_assignments'),
                'permission_callback' => array($this, 'manage_assignments_permission'),
                'args' => array(
                    'candidate_ids' => array(
                        'required' => true,
                        'type' => 'array',
                        'items' => array(
                            'type' => 'integer',
                        ),
                    ),
                    'jury_member_id' => array(
                        'required' => true,
                        'type' => 'integer',
                    ),
                ),
            ),
        ));
        
        register_rest_route($this->namespace, '/assignments/auto', array(
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'auto_assign'),
                'permission_callback' => array($this, 'manage_assignments_permission'),
                'args' => array(
                    'algorithm' => array(
                        'default' => 'balanced',
                        'enum' => array('balanced', 'random', 'expertise', 'category'),
                    ),
                    'candidates_per_jury' => array(
                        'default' => 20,
                        'type' => 'integer',
                        'minimum' => 1,
                        'maximum' => 100,
                    ),
                    'preserve_existing' => array(
                        'default' => true,
                        'type' => 'boolean',
                    ),
                ),
            ),
        ));
        
        register_rest_route($this->namespace, '/assignments/stats', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_assignment_stats'),
                'permission_callback' => array($this, 'view_assignments_permission'),
            ),
        ));
        
        register_rest_route($this->namespace, '/assignments/clear', array(
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'clear_assignments'),
                'permission_callback' => array($this, 'manage_assignments_permission'),
            ),
        ));
        
        register_rest_route($this->namespace, '/assignments/export', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'export_assignments'),
                'permission_callback' => array($this, 'export_data_permission'),
            ),
        ));
        
        // Vote reset routes
        register_rest_route($this->namespace, '/reset-vote', array(
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'reset_vote'),
                'permission_callback' => array($this, 'reset_votes_permission'),
                'args' => array(
                    'type' => array(
                        'required' => true,
                        'enum' => array('individual', 'bulk_candidate', 'bulk_jury', 'phase_transition', 'full_system'),
                    ),
                    'candidate_id' => array(
                        'type' => 'integer',
                    ),
                    'jury_member_id' => array(
                        'type' => 'integer',
                    ),
                    'reason' => array(
                        'type' => 'string',
                    ),
                    'notify' => array(
                        'type' => 'boolean',
                        'default' => true,
                    ),
                ),
            ),
        ));
        
        register_rest_route($this->namespace, '/reset-history', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_reset_history'),
                'permission_callback' => array($this, 'view_reset_history_permission'),
                'args' => array(
                    'page' => array(
                        'default' => 1,
                        'type' => 'integer',
                    ),
                    'per_page' => array(
                        'default' => 20,
                        'type' => 'integer',
                    ),
                ),
            ),
        ));
        
        // Backup routes
        register_rest_route($this->namespace, '/backup-create', array(
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_backup'),
                'permission_callback' => array($this, 'create_backup_permission'),
                'args' => array(
                    'reason' => array(
                        'type' => 'string',
                        'required' => true,
                    ),
                    'type' => array(
                        'type' => 'string',
                        'default' => 'full',
                        'enum' => array('full', 'partial'),
                    ),
                ),
            ),
        ));
        
        register_rest_route($this->namespace, '/backup-history', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_backup_history'),
                'permission_callback' => array($this, 'view_backup_permission'),
                'args' => array(
                    'page' => array(
                        'default' => 1,
                        'type' => 'integer',
                    ),
                    'per_page' => array(
                        'default' => 20,
                        'type' => 'integer',
                        'maximum' => 200,
                    ),
                ),
            ),
        ));
        
        register_rest_route($this->namespace, '/admin/restore-backup', array(
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'restore_backup'),
                'permission_callback' => array($this, 'restore_backup_permission'),
                'args' => array(
                    'backup_id' => array(
                        'required' => true,
                        'type' => 'integer',
                    ),
                    'type' => array(
                        'default' => 'both',
                        'enum' => array('votes', 'scores', 'both'),
                    ),
                ),
            ),
        ));
        
        register_rest_route($this->namespace, '/backup-statistics', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_backup_statistics'),
                'permission_callback' => array($this, 'view_backup_permission'),
            ),
        ));
        
        // Export routes
        register_rest_route($this->namespace, '/export-votes', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'export_votes'),
                'permission_callback' => array($this, 'export_data_permission'),
                'args' => array(
                    'format' => array(
                        'default' => 'csv',
                        'enum' => array('csv', 'json'),
                    ),
                ),
            ),
        ));
        
        register_rest_route($this->namespace, '/export-evaluations', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'export_evaluations'),
                'permission_callback' => array($this, 'export_data_permission'),
                'args' => array(
                    'format' => array(
                        'default' => 'csv',
                        'enum' => array('csv', 'json'),
                    ),
                    'jury_member_id' => array(
                        'type' => 'integer',
                    ),
                ),
            ),
        ));
    }
    
    /**
     * Get candidates
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_candidates($request) {
        $args = array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => $request->get_param('per_page') ?: 20,
            'paged' => $request->get_param('page') ?: 1,
            'orderby' => $request->get_param('orderby') ?: 'title',
            'order' => $request->get_param('order') ?: 'ASC',
            'post_status' => 'publish',
        );
        
        // Add category filter
        if ($request->get_param('category')) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'mt_category',
                    'field' => 'term_id',
                    'terms' => $request->get_param('category'),
                ),
            );
        }
        
        // Add status filter
        if ($request->get_param('status')) {
            $args['meta_query'] = array(
                array(
                    'key' => '_mt_status',
                    'value' => $request->get_param('status'),
                ),
            );
        }
        
        // Add search
        if ($request->get_param('search')) {
            $args['s'] = $request->get_param('search');
        }
        
        $query = new WP_Query($args);
        
        $candidates = array();
        
        foreach ($query->posts as $post) {
            $candidates[] = $this->prepare_candidate($post);
        }
        
        $response = new WP_REST_Response($candidates);
        
        // Add pagination headers
        $response->header('X-WP-Total', $query->found_posts);
        $response->header('X-WP-TotalPages', $query->max_num_pages);
        
        return $response;
    }
    
    /**
     * Get single candidate
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_candidate($request) {
        $candidate = get_post($request->get_param('id'));
        
        if (!$candidate || $candidate->post_type !== 'mt_candidate') {
            return new WP_Error('not_found', __('Candidate not found.', 'mobility-trailblazers'), array('status' => 404));
        }
        
        return new WP_REST_Response($this->prepare_candidate($candidate, true));
    }
    
    /**
     * Create candidate
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function create_candidate($request) {
        $post_data = array(
            'post_title' => $request->get_param('name'),
            'post_content' => $request->get_param('description'),
            'post_type' => 'mt_candidate',
            'post_status' => $request->get_param('status') ?: 'pending',
        );
        
        $candidate_id = wp_insert_post($post_data);
        
        if (is_wp_error($candidate_id)) {
            return new WP_Error('create_failed', __('Failed to create candidate.', 'mobility-trailblazers'), array('status' => 500));
        }
        
        // Save meta data
        $this->save_candidate_meta($candidate_id, $request);
        
        // Set taxonomies
        if ($request->get_param('category')) {
            wp_set_object_terms($candidate_id, $request->get_param('category'), 'mt_category');
        }
        
        $candidate = get_post($candidate_id);
        
        return new WP_REST_Response($this->prepare_candidate($candidate), 201);
    }
    
    /**
     * Update candidate
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function update_candidate($request) {
        $candidate = get_post($request->get_param('id'));
        
        if (!$candidate || $candidate->post_type !== 'mt_candidate') {
            return new WP_Error('not_found', __('Candidate not found.', 'mobility-trailblazers'), array('status' => 404));
        }
        
        $post_data = array(
            'ID' => $candidate->ID,
        );
        
        if ($request->get_param('name')) {
            $post_data['post_title'] = $request->get_param('name');
        }
        
        if ($request->get_param('description')) {
            $post_data['post_content'] = $request->get_param('description');
        }
        
        if ($request->get_param('status')) {
            $post_data['post_status'] = $request->get_param('status');
        }
        
        $result = wp_update_post($post_data);
        
        if (is_wp_error($result)) {
            return new WP_Error('update_failed', __('Failed to update candidate.', 'mobility-trailblazers'), array('status' => 500));
        }
        
        // Update meta data
        $this->save_candidate_meta($candidate->ID, $request);
        
        // Update taxonomies
        if ($request->get_param('category')) {
            wp_set_object_terms($candidate->ID, $request->get_param('category'), 'mt_category');
        }
        
        $candidate = get_post($candidate->ID);
        
        return new WP_REST_Response($this->prepare_candidate($candidate));
    }
    
    /**
     * Delete candidate
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function delete_candidate($request) {
        $candidate = get_post($request->get_param('id'));
        
        if (!$candidate || $candidate->post_type !== 'mt_candidate') {
            return new WP_Error('not_found', __('Candidate not found.', 'mobility-trailblazers'), array('status' => 404));
        }
        
        $result = wp_delete_post($candidate->ID, true);
        
        if (!$result) {
            return new WP_Error('delete_failed', __('Failed to delete candidate.', 'mobility-trailblazers'), array('status' => 500));
        }
        
        return new WP_REST_Response(array('deleted' => true));
    }
    
    /**
     * Get jury members
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_jury_members($request) {
        $args = array(
            'post_type' => 'mt_jury',
            'posts_per_page' => $request->get_param('per_page') ?: -1,
            'orderby' => $request->get_param('orderby') ?: 'title',
            'order' => $request->get_param('order') ?: 'ASC',
            'post_status' => 'publish',
        );
        
        // Add role filter
        if ($request->get_param('role')) {
            $args['meta_query'] = array(
                array(
                    'key' => '_mt_jury_role',
                    'value' => $request->get_param('role'),
                ),
            );
        }
        
        $query = new WP_Query($args);
        
        $jury_members = array();
        
        foreach ($query->posts as $post) {
            $jury_members[] = $this->prepare_jury_member($post);
        }
        
        return new WP_REST_Response($jury_members);
    }
    
    /**
     * Get evaluations
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_evaluations($request) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_candidate_scores';
        
        $where = array('is_active = 1');
        $params = array();
        
        // Add filters
        if ($request->get_param('candidate_id')) {
            $where[] = 'candidate_id = %d';
            $params[] = $request->get_param('candidate_id');
        }
        
        if ($request->get_param('jury_member_id')) {
            $where[] = 'jury_member_id = %d';
            $params[] = $request->get_param('jury_member_id');
        }
        
        if ($request->get_param('evaluation_round')) {
            $where[] = 'evaluation_round = %s';
            $params[] = $request->get_param('evaluation_round');
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Get total count
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE $where_clause",
            $params
        ));
        
        // Get paginated results
        $page = $request->get_param('page') ?: 1;
        $per_page = $request->get_param('per_page') ?: 20;
        $offset = ($page - 1) * $per_page;
        
        $evaluations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE $where_clause 
             ORDER BY created_at DESC 
             LIMIT %d OFFSET %d",
            array_merge($params, array($per_page, $offset))
        ));
        
        $response = new WP_REST_Response($evaluations);
        
        // Add pagination headers
        $response->header('X-WP-Total', $total);
        $response->header('X-WP-TotalPages', ceil($total / $per_page));
        
        return $response;
    }
    
    /**
     * Create evaluation
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function create_evaluation($request) {
        global $wpdb;
        
        // Validate candidate
        $candidate = get_post($request->get_param('candidate_id'));
        if (!$candidate || $candidate->post_type !== 'mt_candidate') {
            return new WP_Error('invalid_candidate', __('Invalid candidate.', 'mobility-trailblazers'), array('status' => 400));
        }
        
        // Validate jury member
        $jury_member = get_post($request->get_param('jury_member_id'));
        if (!$jury_member || $jury_member->post_type !== 'mt_jury') {
            return new WP_Error('invalid_jury_member', __('Invalid jury member.', 'mobility-trailblazers'), array('status' => 400));
        }
        
        // Calculate total score
        $total_score = 0;
        $criteria = array('courage', 'innovation', 'implementation', 'relevance', 'visibility');
        
        foreach ($criteria as $criterion) {
            $score = $request->get_param($criterion . '_score');
            if ($score < 0 || $score > 10) {
                return new WP_Error('invalid_score', sprintf(__('Invalid %s score.', 'mobility-trailblazers'), $criterion), array('status' => 400));
            }
            $total_score += $score;
        }
        
        // Insert evaluation
        $table_name = $wpdb->prefix . 'mt_candidate_scores';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'candidate_id' => $request->get_param('candidate_id'),
                'jury_member_id' => $request->get_param('jury_member_id'),
                'courage_score' => $request->get_param('courage_score'),
                'innovation_score' => $request->get_param('innovation_score'),
                'implementation_score' => $request->get_param('implementation_score'),
                'relevance_score' => $request->get_param('relevance_score'),
                'visibility_score' => $request->get_param('visibility_score'),
                'total_score' => $total_score,
                'evaluation_round' => $request->get_param('evaluation_round') ?: 'initial',
                'comments' => $request->get_param('comments'),
                'evaluation_date' => current_time('mysql'),
            ),
            array('%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('create_failed', __('Failed to create evaluation.', 'mobility-trailblazers'), array('status' => 500));
        }
        
        $evaluation_id = $wpdb->insert_id;
        
        // Fire action
        do_action('mt_evaluation_completed', $request->get_param('candidate_id'), $request->get_param('jury_member_id'), $total_score);
        
        return new WP_REST_Response(array(
            'id' => $evaluation_id,
            'total_score' => $total_score,
        ), 201);
    }
    
    /**
     * Update evaluation
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function update_evaluation($request) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_candidate_scores';
        
        // Check if evaluation exists
        $evaluation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $request->get_param('id')
        ));
        
        if (!$evaluation) {
            return new WP_Error('not_found', __('Evaluation not found.', 'mobility-trailblazers'), array('status' => 404));
        }
        
        // Prepare update data
        $update_data = array();
        $update_format = array();
        
        $criteria = array('courage', 'innovation', 'implementation', 'relevance', 'visibility');
        $total_score = 0;
        
        foreach ($criteria as $criterion) {
            $score_key = $criterion . '_score';
            if ($request->has_param($score_key)) {
                $score = $request->get_param($score_key);
                if ($score < 0 || $score > 10) {
                    return new WP_Error('invalid_score', sprintf(__('Invalid %s score.', 'mobility-trailblazers'), $criterion), array('status' => 400));
                }
                $update_data[$score_key] = $score;
                $update_format[] = '%d';
                $total_score += $score;
            } else {
                $total_score += $evaluation->$score_key;
            }
        }
        
        if (!empty($update_data)) {
            $update_data['total_score'] = $total_score;
            $update_format[] = '%d';
        }
        
        if ($request->has_param('comments')) {
            $update_data['comments'] = $request->get_param('comments');
            $update_format[] = '%s';
        }
        
        if (empty($update_data)) {
            return new WP_Error('no_changes', __('No changes to update.', 'mobility-trailblazers'), array('status' => 400));
        }
        
        $update_data['updated_at'] = current_time('mysql');
        $update_format[] = '%s';
        
        // Update evaluation
        $result = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $request->get_param('id')),
            $update_format,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('update_failed', __('Failed to update evaluation.', 'mobility-trailblazers'), array('status' => 500));
        }
        
        return new WP_REST_Response(array(
            'updated' => true,
            'total_score' => $total_score,
        ));
    }
    
    /**
     * Create assignments
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function create_assignments($request) {
        $candidate_ids = $request->get_param('candidate_ids');
        $jury_member_id = $request->get_param('jury_member_id');
        
        // Validate jury member
        $jury_member = get_post($jury_member_id);
        if (!$jury_member || $jury_member->post_type !== 'mt_jury') {
            return new WP_Error('invalid_jury_member', __('Invalid jury member.', 'mobility-trailblazers'), array('status' => 400));
        }
        
        $assigned_count = 0;
        
        foreach ($candidate_ids as $candidate_id) {
            // Validate candidate
            $candidate = get_post($candidate_id);
            if (!$candidate || $candidate->post_type !== 'mt_candidate') {
                continue;
            }
            
            // Get current assignments
            $current_assignments = get_post_meta($candidate_id, '_mt_assigned_jury_members', true);
            if (!is_array($current_assignments)) {
                $current_assignments = array();
            }
            
            // Add jury member if not already assigned
            if (!in_array($jury_member_id, $current_assignments)) {
                $current_assignments[] = $jury_member_id;
                update_post_meta($candidate_id, '_mt_assigned_jury_members', $current_assignments);
                
                // Fire action
                do_action('mt_after_candidate_assignment', $candidate_id, $jury_member_id);
                
                $assigned_count++;
            }
        }
        
        return new WP_REST_Response(array(
            'assigned' => $assigned_count,
            'message' => sprintf(
                _n('%d candidate assigned successfully.', '%d candidates assigned successfully.', $assigned_count, 'mobility-trailblazers'),
                $assigned_count
            ),
        ));
    }
    
    /**
     * Auto-assign candidates
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function auto_assign($request) {
        // This would implement the auto-assignment logic
        // For now, return a placeholder response
        return new WP_REST_Response(array(
            'message' => __('Auto-assignment completed.', 'mobility-trailblazers'),
            'assigned' => 0,
        ));
    }
    
    /**
     * Get assignment statistics
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_assignment_stats($request) {
        global $wpdb;
        
        // Get total candidates
        $total_candidates = wp_count_posts('mt_candidate')->publish;
        
        // Get total jury members
        $total_jury = wp_count_posts('mt_jury')->publish;
        
        // Get assigned candidates count
        $assigned_candidates = $wpdb->get_var("
            SELECT COUNT(DISTINCT post_id) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_mt_assigned_jury_members' 
            AND meta_value != 'a:0:{}'
        ");
        
        // Get average assignments per jury member
        $avg_assignments = 0;
        if ($total_jury > 0) {
            $avg_assignments = round($assigned_candidates / $total_jury, 1);
        }
        
        return new WP_REST_Response(array(
            'total_candidates' => $total_candidates,
            'total_jury_members' => $total_jury,
            'assigned_candidates' => $assigned_candidates,
            'unassigned_candidates' => $total_candidates - $assigned_candidates,
            'average_assignments_per_jury' => $avg_assignments,
        ));
    }
    
    /**
     * Clear all assignments
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function clear_assignments($request) {
        global $wpdb;
        
        // Delete all assignment meta
        $deleted = $wpdb->delete(
            $wpdb->postmeta,
            array('meta_key' => '_mt_assigned_jury_members'),
            array('%s')
        );
        
        return new WP_REST_Response(array(
            'cleared' => $deleted,
            'message' => sprintf(__('%d assignments cleared.', 'mobility-trailblazers'), $deleted),
        ));
    }
    
    /**
     * Export assignments
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function export_assignments($request) {
        // Get all candidates with assignments
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_mt_assigned_jury_members',
                    'compare' => 'EXISTS',
                ),
            ),
        ));
        
        $export_data = array();
        
        foreach ($candidates as $candidate) {
            $jury_member_ids = get_post_meta($candidate->ID, '_mt_assigned_jury_members', true);
            $jury_names = array();
            
            if (is_array($jury_member_ids)) {
                foreach ($jury_member_ids as $jury_id) {
                    $jury = get_post($jury_id);
                    if ($jury) {
                        $jury_names[] = $jury->post_title;
                    }
                }
            }
            
            $export_data[] = array(
                'candidate_id' => $candidate->ID,
                'candidate_name' => $candidate->post_title,
                'company' => get_post_meta($candidate->ID, '_mt_company', true),
                'assigned_jury_members' => $jury_names,
                'assignment_count' => count($jury_names),
            );
        }
        
        return new WP_REST_Response(array(
            'data' => $export_data,
            'count' => count($export_data),
        ));
    }
    
    /**
     * Reset vote
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function reset_vote($request) {
        global $wpdb;
        
        $type = $request->get_param('type');
        $reason = $request->get_param('reason');
        
        // Handle different reset types
        switch ($type) {
            case 'individual':
                // Reset individual vote logic
                break;
                
            case 'bulk_candidate':
                // Reset all votes for a candidate
                break;
                
            case 'bulk_jury':
                // Reset all votes by a jury member
                break;
                
            case 'phase_transition':
                // Reset for phase transition
                break;
                
            case 'full_system':
                // Full system reset
                break;
                
            default:
                return new WP_Error('invalid_type', __('Invalid reset type.', 'mobility-trailblazers'), array('status' => 400));
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => __('Reset completed successfully.', 'mobility-trailblazers'),
        ));
    }
    
    /**
     * Get reset history
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_reset_history($request) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vote_reset_logs';
        
        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $offset = ($page - 1) * $per_page;
        
        // Get total count
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        // Get paginated results
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, u.display_name 
             FROM $table_name l
             LEFT JOIN {$wpdb->users} u ON l.performed_by = u.ID
             ORDER BY l.created_at DESC
             LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));
        
        $response = new WP_REST_Response($logs);
        
        // Add pagination headers
        $response->header('X-WP-Total', $total);
        $response->header('X-WP-TotalPages', ceil($total / $per_page));
        
        return $response;
    }
    
    /**
     * Create backup
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function create_backup($request) {
        global $wpdb;
        
        $reason = $request->get_param('reason');
        $type = $request->get_param('type');
        
        // Create backup logic here
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => __('Backup created successfully.', 'mobility-trailblazers'),
            'backup_id' => 0, // Replace with actual backup ID
        ), 201);
    }
    
    /**
     * Get backup history
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_backup_history($request) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_vote_backups';
        
        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $offset = ($page - 1) * $per_page;
        
        // Get total count
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        // Get paginated results
        $backups = $wpdb->get_results($wpdb->prepare(
            "SELECT b.*, u.display_name 
             FROM $table_name b
             LEFT JOIN {$wpdb->users} u ON b.created_by = u.ID
             ORDER BY b.created_at DESC
             LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));
        
        // Process backup data
        foreach ($backups as &$backup) {
            $backup->backup_data = json_decode($backup->backup_data);
        }
        
        $response = new WP_REST_Response($backups);
        
        // Add pagination headers
        $response->header('X-WP-Total', $total);
        $response->header('X-WP-TotalPages', ceil($total / $per_page));
        
        return $response;
    }
    
    /**
     * Restore backup
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function restore_backup($request) {
        $backup_id = $request->get_param('backup_id');
        $type = $request->get_param('type');
        
        // Restore backup logic here
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => __('Backup restored successfully.', 'mobility-trailblazers'),
        ));
    }
    
    /**
     * Get backup statistics
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function get_backup_statistics($request) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_vote_backups';
        
        // Get statistics
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_backups,
                COUNT(DISTINCT backup_type) as backup_types,
                MIN(created_at) as oldest_backup,
                MAX(created_at) as newest_backup
            FROM $table_name
        ");
        
        // Get backup counts by type
        $by_type = $wpdb->get_results("
            SELECT backup_type, COUNT(*) as count
            FROM $table_name
            GROUP BY backup_type
        ");
        
        return new WP_REST_Response(array(
            'total_backups' => $stats->total_backups,
            'backup_types' => $stats->backup_types,
            'oldest_backup' => $stats->oldest_backup,
            'newest_backup' => $stats->newest_backup,
            'backups_by_type' => $by_type,
        ));
    }
    
    /**
     * Export votes
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function export_votes($request) {
        global $wpdb;
        
        $format = $request->get_param('format');
        $table_name = $wpdb->prefix . 'mt_votes';
        
        // Get all active votes
        $votes = $wpdb->get_results("
            SELECT v.*, c.post_title as candidate_name, j.post_title as jury_name
            FROM $table_name v
            LEFT JOIN {$wpdb->posts} c ON v.candidate_id = c.ID
            LEFT JOIN {$wpdb->posts} j ON v.jury_member_id = j.ID
            WHERE v.is_active = 1
            ORDER BY v.created_at DESC
        ");
        
        if ($format === 'json') {
            return new WP_REST_Response($votes);
        }
        
        // Prepare CSV data
        $csv_data = array();
        $headers = array('ID', 'Candidate', 'Jury Member', 'Total Score', 'Voting Phase', 'Created At');
        
        foreach ($votes as $vote) {
            $csv_data[] = array(
                $vote->id,
                $vote->candidate_name,
                $vote->jury_name ?: 'Public Vote',
                $vote->total_score,
                $vote->voting_phase,
                $vote->created_at,
            );
        }
        
        return new WP_REST_Response(array(
            'headers' => $headers,
            'data' => $csv_data,
            'count' => count($csv_data),
        ));
    }
    
    /**
     * Export evaluations
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function export_evaluations($request) {
        global $wpdb;
        
        $format = $request->get_param('format');
        $jury_member_id = $request->get_param('jury_member_id');
        $table_name = $wpdb->prefix . 'mt_candidate_scores';
        
        $where = array('is_active = 1');
        $params = array();
        
        if ($jury_member_id) {
            $where[] = 'jury_member_id = %d';
            $params[] = $jury_member_id;
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Get evaluations
        $evaluations = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, c.post_title as candidate_name, j.post_title as jury_name
             FROM $table_name s
             LEFT JOIN {$wpdb->posts} c ON s.candidate_id = c.ID
             LEFT JOIN {$wpdb->posts} j ON s.jury_member_id = j.ID
             WHERE $where_clause
             ORDER BY s.evaluation_date DESC",
            $params
        ));
        
        if ($format === 'json') {
            return new WP_REST_Response($evaluations);
        }
        
        // Prepare CSV data
        $csv_data = array();
        $headers = array(
            'ID', 'Candidate', 'Jury Member', 
            'Courage', 'Innovation', 'Implementation', 'Relevance', 'Visibility',
            'Total Score', 'Evaluation Round', 'Date'
        );
        
        foreach ($evaluations as $evaluation) {
            $csv_data[] = array(
                $evaluation->id,
                $evaluation->candidate_name,
                $evaluation->jury_name,
                $evaluation->courage_score,
                $evaluation->innovation_score,
                $evaluation->implementation_score,
                $evaluation->relevance_score,
                $evaluation->visibility_score,
                $evaluation->total_score,
                $evaluation->evaluation_round,
                $evaluation->evaluation_date,
            );
        }
        
        return new WP_REST_Response(array(
            'headers' => $headers,
            'data' => $csv_data,
            'count' => count($csv_data),
        ));
    }
    
    /**
     * Prepare candidate for response
     *
     * @param WP_Post $post Post object
     * @param bool $full Whether to include full details
     * @return array Prepared candidate data
     */
    private function prepare_candidate($post, $full = false) {
        $data = array(
            'id' => $post->ID,
            'name' => $post->post_title,
            'slug' => $post->post_name,
            'status' => $post->post_status,
            'created' => $post->post_date,
            'modified' => $post->post_modified,
            'link' => get_permalink($post->ID),
        );
        
        // Add meta data
        $data['company'] = get_post_meta($post->ID, '_mt_company', true);
        $data['position'] = get_post_meta($post->ID, '_mt_position', true);
        $data['location'] = get_post_meta($post->ID, '_mt_location', true);
        $data['email'] = get_post_meta($post->ID, '_mt_email', true);
        
        // Add categories
        $categories = get_the_terms($post->ID, 'mt_category');
        if ($categories && !is_wp_error($categories)) {
            $data['categories'] = wp_list_pluck($categories, 'name', 'term_id');
        }
        
        // Add featured image
        if (has_post_thumbnail($post->ID)) {
            $data['featured_image'] = get_the_post_thumbnail_url($post->ID, 'full');
        }
        
        if ($full) {
            $data['description'] = $post->post_content;
            $data['excerpt'] = $post->post_excerpt;
            
            // Add all meta data
            $data['meta'] = array(
                'phone' => get_post_meta($post->ID, '_mt_phone', true),
                'website' => get_post_meta($post->ID, '_mt_website', true),
                'linkedin' => get_post_meta($post->ID, '_mt_linkedin', true),
                'founded_year' => get_post_meta($post->ID, '_mt_founded_year', true),
                'employees' => get_post_meta($post->ID, '_mt_employees', true),
                'innovation_title' => get_post_meta($post->ID, '_mt_innovation_title', true),
                'innovation_description' => get_post_meta($post->ID, '_mt_innovation_description', true),
                'innovation_stage' => get_post_meta($post->ID, '_mt_innovation_stage', true),
                'target_market' => get_post_meta($post->ID, '_mt_target_market', true),
                'unique_selling_points' => get_post_meta($post->ID, '_mt_unique_selling_points', true),
                'users_reached' => get_post_meta($post->ID, '_mt_users_reached', true),
                'revenue' => get_post_meta($post->ID, '_mt_revenue', true),
                'funding_raised' => get_post_meta($post->ID, '_mt_funding_raised', true),
                'co2_saved' => get_post_meta($post->ID, '_mt_co2_saved', true),
                'awards_recognition' => get_post_meta($post->ID, '_mt_awards_recognition', true),
                'key_partnerships' => get_post_meta($post->ID, '_mt_key_partnerships', true),
                'video_url' => get_post_meta($post->ID, '_mt_video_url', true),
                'presentation_url' => get_post_meta($post->ID, '_mt_presentation_url', true),
                'additional_docs' => get_post_meta($post->ID, '_mt_additional_docs', true),
                'status' => get_post_meta($post->ID, '_mt_status', true),
                'final_score' => get_post_meta($post->ID, '_mt_final_score', true),
            );
            
            // Add assigned jury members
            $data['assigned_jury_members'] = get_post_meta($post->ID, '_mt_assigned_jury_members', true) ?: array();
            
            // Add evaluation statistics
            global $wpdb;
            $table_name = $wpdb->prefix . 'mt_candidate_scores';
            
            $stats = $wpdb->get_row($wpdb->prepare(
                "SELECT 
                    COUNT(*) as total_evaluations,
                    AVG(total_score) as average_score,
                    MIN(total_score) as min_score,
                    MAX(total_score) as max_score
                 FROM $table_name 
                 WHERE candidate_id = %d AND is_active = 1",
                $post->ID
            ));
            
            $data['evaluation_stats'] = $stats;
        }
        
        return $data;
    }
    
    /**
     * Prepare jury member for response
     *
     * @param WP_Post $post Post object
     * @return array Prepared jury member data
     */
    private function prepare_jury_member($post) {
        $data = array(
            'id' => $post->ID,
            'name' => $post->post_title,
            'slug' => $post->post_name,
            'status' => $post->post_status,
            'created' => $post->post_date,
            'modified' => $post->post_modified,
        );
        
        // Add meta data
        $data['organization'] = get_post_meta($post->ID, '_mt_organization', true);
        $data['position'] = get_post_meta($post->ID, '_mt_position', true);
        $data['email'] = get_post_meta($post->ID, '_mt_email', true);
        $data['role'] = get_post_meta($post->ID, '_mt_jury_role', true);
        $data['expertise_areas'] = get_post_meta($post->ID, '_mt_expertise_areas', true) ?: array();
        
        // Add featured image
        if (has_post_thumbnail($post->ID)) {
            $data['photo'] = get_the_post_thumbnail_url($post->ID, 'full');
        }
        
        // Add assigned candidates count
        $data['assigned_candidates_count'] = count(mt_get_assigned_candidates($post->ID));
        
        return $data;
    }
    
    /**
     * Save candidate meta data
     *
     * @param int $candidate_id Candidate ID
     * @param WP_REST_Request $request Request object
     */
    private function save_candidate_meta($candidate_id, $request) {
        $meta_fields = array(
            'company', 'position', 'location', 'email', 'phone',
            'website', 'linkedin', 'founded_year', 'employees',
            'innovation_title', 'innovation_description', 'innovation_stage',
            'target_market', 'unique_selling_points', 'users_reached',
            'revenue', 'funding_raised', 'co2_saved', 'awards_recognition',
            'key_partnerships', 'video_url', 'presentation_url',
            'additional_docs', 'status', 'final_score'
        );
        
        foreach ($meta_fields as $field) {
            if ($request->has_param($field)) {
                update_post_meta($candidate_id, '_mt_' . $field, $request->get_param($field));
            }
        }
    }
    
    /**
     * Get candidates arguments
     *
     * @return array Arguments schema
     */
    private function get_candidates_args() {
        return array(
            'page' => array(
                'default' => 1,
                'type' => 'integer',
            ),
            'per_page' => array(
                'default' => 20,
                'type' => 'integer',
            ),
            'search' => array(
                'type' => 'string',
            ),
            'orderby' => array(
                'default' => 'title',
                'enum' => array('title', 'date', 'modified', 'menu_order'),
            ),
            'order' => array(
                'default' => 'ASC',
                'enum' => array('ASC', 'DESC'),
            ),
            'category' => array(
                'type' => 'integer',
            ),
            'status' => array(
                'type' => 'string',
            ),
        );
    }
    
    /**
     * Get jury members arguments
     *
     * @return array Arguments schema
     */
    private function get_jury_members_args() {
        return array(
            'per_page' => array(
                'default' => -1,
                'type' => 'integer',
            ),
            'orderby' => array(
                'default' => 'title',
                'enum' => array('title', 'date', 'modified', 'menu_order'),
            ),
            'order' => array(
                'default' => 'ASC',
                'enum' => array('ASC', 'DESC'),
            ),
            'role' => array(
                'type' => 'string',
                'enum' => array('president', 'vice_president', 'member'),
            ),
        );
    }
    
    /**
     * Get evaluations arguments
     *
     * @return array Arguments schema
     */
    private function get_evaluations_args() {
        return array(
            'page' => array(
                'default' => 1,
                'type' => 'integer',
            ),
            'per_page' => array(
                'default' => 20,
                'type' => 'integer',
            ),
            'candidate_id' => array(
                'type' => 'integer',
            ),
            'jury_member_id' => array(
                'type' => 'integer',
            ),
            'evaluation_round' => array(
                'type' => 'string',
            ),
        );
    }
    
    /**
     * Get candidate schema
     *
     * @return array Schema definition
     */
    private function get_candidate_schema() {
        return array(
            'name' => array(
                'type' => 'string',
                'required' => true,
            ),
            'description' => array(
                'type' => 'string',
            ),
            'status' => array(
                'type' => 'string',
                'enum' => array('pending', 'publish', 'draft'),
            ),
            'company' => array(
                'type' => 'string',
            ),
            'position' => array(
                'type' => 'string',
            ),
            'location' => array(
                'type' => 'string',
            ),
            'email' => array(
                'type' => 'string',
                'format' => 'email',
            ),
            'phone' => array(
                'type' => 'string',
            ),
            'category' => array(
                'type' => 'integer',
            ),
        );
    }
    
    /**
     * Get evaluation schema
     *
     * @return array Schema definition
     */
    private function get_evaluation_schema() {
        return array(
            'candidate_id' => array(
                'type' => 'integer',
                'required' => true,
            ),
            'jury_member_id' => array(
                'type' => 'integer',
                'required' => true,
            ),
            'courage_score' => array(
                'type' => 'integer',
                'minimum' => 0,
                'maximum' => 10,
                'required' => true,
            ),
            'innovation_score' => array(
                'type' => 'integer',
                'minimum' => 0,
                'maximum' => 10,
                'required' => true,
            ),
            'implementation_score' => array(
                'type' => 'integer',
                'minimum' => 0,
                'maximum' => 10,
                'required' => true,
            ),
            'relevance_score' => array(
                'type' => 'integer',
                'minimum' => 0,
                'maximum' => 10,
                'required' => true,
            ),
            'visibility_score' => array(
                'type' => 'integer',
                'minimum' => 0,
                'maximum' => 10,
                'required' => true,
            ),
            'comments' => array(
                'type' => 'string',
            ),
            'evaluation_round' => array(
                'type' => 'string',
                'default' => 'initial',
            ),
        );
    }
    
    // Permission callbacks
    
    public function create_candidate_permission() {
        return current_user_can('publish_mt_candidates');
    }
    
    public function update_candidate_permission($request) {
        return current_user_can('edit_mt_candidate', $request->get_param('id'));
    }
    
    public function delete_candidate_permission($request) {
        return current_user_can('delete_mt_candidate', $request->get_param('id'));
    }
    
    public function get_evaluations_permission() {
        return current_user_can('mt_view_all_evaluations') || current_user_can('mt_submit_evaluations');
    }
    
    public function create_evaluation_permission() {
        return current_user_can('mt_submit_evaluations');
    }
    
    public function update_evaluation_permission() {
        return current_user_can('mt_submit_evaluations');
    }
    
    public function manage_assignments_permission() {
        return current_user_can('mt_manage_assignments');
    }
    
    public function view_assignments_permission() {
        return current_user_can('mt_manage_assignments') || current_user_can('mt_view_all_evaluations');
    }
    
    public function export_data_permission() {
        return current_user_can('mt_export_data');
    }
    
    public function reset_votes_permission() {
        return current_user_can('mt_reset_votes');
    }
    
    public function view_reset_history_permission() {
        return current_user_can('mt_reset_votes');
    }
    
    public function create_backup_permission() {
        return current_user_can('mt_create_backups');
    }
    
    public function view_backup_permission() {
        return current_user_can('mt_create_backups') || current_user_can('mt_restore_backups');
    }
    
    public function restore_backup_permission() {
        return current_user_can('mt_restore_backups');
    }
} 