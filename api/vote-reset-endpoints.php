// api/vote-reset-endpoints.php
class MT_Vote_Reset_API {
    
    public function register_routes() {
        register_rest_route('mobility-trailblazers/v1', '/reset-vote', array(
            'methods' => 'POST',
            'callback' => array($this, 'reset_individual_vote'),
            'permission_callback' => array($this, 'check_jury_permission'),
            'args' => array(
                'candidate_id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
                'reason' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
        
        register_rest_route('mobility-trailblazers/v1', '/admin/bulk-reset', array(
            'methods' => 'POST',
            'callback' => array($this, 'bulk_reset_votes'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'reset_scope' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return in_array($param, array(
                            'phase_transition',
                            'all_user_votes',
                            'all_candidate_votes',
                            'full_reset'
                        ));
                    }
                ),
                'options' => array(
                    'required' => false,
                    'type' => 'object'
                )
            )
        ));
        
        register_rest_route('mobility-trailblazers/v1', '/reset-history', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_reset_history'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'page' => array(
                    'default' => 1,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    }
                ),
                'per_page' => array(
                    'default' => 20,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0 && $param <= 100;
                    }
                )
            )
        ));
    }
    
    public function reset_individual_vote($request) {
        $reset_manager = new MT_Vote_Reset_Manager();
        
        $result = $reset_manager->reset_individual_vote(
            $request['candidate_id'],
            get_current_user_id(),
            $request['reason'] ?? ''
        );
        
        if (is_wp_error($result)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $result->get_error_message()
            ), 400);
        }
        
        return new WP_REST_Response($result, 200);
    }
    
    public function bulk_reset_votes($request) {
        $reset_manager = new MT_Vote_Reset_Manager();
        
        $result = $reset_manager->bulk_reset_votes(
            $request['reset_scope'],
            $request['options'] ?? array()
        );
        
        if (is_wp_error($result)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $result->get_error_message()
            ), 400);
        }
        
        return new WP_REST_Response($result, 200);
    }
    
    public function get_reset_history($request) {
        global $wpdb;
        
        $page = $request['page'];
        $per_page = $request['per_page'];
        $offset = ($page - 1) * $per_page;
        
        $query = "SELECT 
                    r.*,
                    u1.display_name as initiated_by_name,
                    u2.display_name as affected_user_name,
                    c.name as candidate_name
                  FROM vote_reset_logs r
                  LEFT JOIN {$wpdb->users} u1 ON r.initiated_by = u1.ID
                  LEFT JOIN {$wpdb->users} u2 ON r.affected_user_id = u2.ID
                  LEFT JOIN candidates c ON r.affected_candidate_id = c.id
                  ORDER BY r.reset_timestamp DESC
                  LIMIT %d OFFSET %d";
                  
        $results = $wpdb->get_results($wpdb->prepare($query, $per_page, $offset));
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM vote_reset_logs");
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $results,
            'pagination' => array(
                'total' => intval($total),
                'pages' => ceil($total / $per_page),
                'current_page' => $page,
                'per_page' => $per_page
            )
        ), 200);
    }
    
    public function check_jury_permission() {
        return is_user_logged_in() && current_user_can('mt_jury_member');
    }
    
    public function check_admin_permission() {
        return current_user_can('manage_options');
    }
}