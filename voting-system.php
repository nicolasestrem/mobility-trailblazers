<?php
/**
 * Voting System Extension for Mobility Trailblazers Plugin
 * FIXED VERSION - Resolves permission and API issues
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MobilityTrailblazersVoting {
    
    private $votes_table;
    private $voting_phases_table;
    private $jury_assignments_table;
    
    public function __construct() {
        global $wpdb;
        $this->votes_table = $wpdb->prefix . 'mt_votes';
        $this->voting_phases_table = $wpdb->prefix . 'mt_voting_phases';
        $this->jury_assignments_table = $wpdb->prefix . 'mt_jury_assignments';
        
        add_action('init', array($this, 'init_voting_system'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_voting_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_voting_scripts'));
        add_action('rest_api_init', array($this, 'register_voting_endpoints'));
        add_action('admin_menu', array($this, 'add_voting_admin_pages'));
        
        // Create custom role for jury members
        add_action('init', array($this, 'create_jury_role'));
        
        // Fix permission callbacks
        add_action('rest_api_init', array($this, 'setup_rest_permissions'), 20);
    }
    
    /**
     * Setup REST API permissions properly
     */
    public function setup_rest_permissions() {
        // Ensure proper permission setup for REST endpoints
        add_filter('rest_authentication_errors', array($this, 'fix_rest_authentication'));
    }
    
    /**
     * Fix REST API authentication issues
     */
    public function fix_rest_authentication($result) {
        // If a previous check has already failed, don't override it
        if (true === $result || is_wp_error($result)) {
            return $result;
        }
        
        // For logged-in users making REST requests, allow them through
        if (is_user_logged_in()) {
            return true;
        }
        
        return $result;
    }
    
    /**
     * Initialize voting system
     */
    public function init_voting_system() {
        // Create database tables if they don't exist
        $this->create_voting_tables();
        
        // Add voting capabilities
        $this->setup_voting_capabilities();
    }
    
    /**
     * Create database tables for voting system
     */
    public function create_voting_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Check if tables already exist before creating
        $tables_exist = $this->check_tables_exist();
        if ($tables_exist['all_exist']) {
            error_log('Mobility Trailblazers: All database tables already exist');
            return;
        }
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Votes table - REMOVED CHECK constraints for compatibility
        if (!$tables_exist['votes']) {
            $votes_sql = "CREATE TABLE {$this->votes_table} (
                id int(11) NOT NULL AUTO_INCREMENT,
                jury_member_id bigint(20) NOT NULL,
                candidate_id bigint(20) NOT NULL,
                stage enum('shortlist','semifinal','final') NOT NULL,
                pioneer_spirit tinyint(2) NOT NULL DEFAULT 5,
                innovation_degree tinyint(2) NOT NULL DEFAULT 5,
                implementation_power tinyint(2) NOT NULL DEFAULT 5,
                role_model_function tinyint(2) NOT NULL DEFAULT 5,
                total_score decimal(4,2) DEFAULT NULL,
                comments text,
                is_final boolean DEFAULT FALSE,
                voted_at timestamp DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY unique_vote (jury_member_id, candidate_id, stage),
                KEY idx_stage (stage),
                KEY idx_candidate (candidate_id),
                KEY idx_jury_member (jury_member_id)
            ) $charset_collate;";
            
            $result1 = dbDelta($votes_sql);
            
            if ($wpdb->last_error) {
                error_log('Mobility Trailblazers Votes Table Error: ' . $wpdb->last_error);
            } else {
                error_log('Mobility Trailblazers: Votes table created successfully');
            }
        }
        
        // Voting phases table
        if (!$tables_exist['phases']) {
            $phases_sql = "CREATE TABLE {$this->voting_phases_table} (
                id int(11) NOT NULL AUTO_INCREMENT,
                phase_name varchar(100) NOT NULL,
                stage enum('shortlist','semifinal','final') NOT NULL,
                start_date datetime NOT NULL,
                end_date datetime NOT NULL,
                is_active boolean DEFAULT FALSE,
                description text,
                settings text,
                created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_stage (stage),
                KEY idx_active (is_active),
                KEY idx_dates (start_date, end_date)
            ) $charset_collate;";
            
            $result2 = dbDelta($phases_sql);
            
            if ($wpdb->last_error) {
                error_log('Mobility Trailblazers Phases Table Error: ' . $wpdb->last_error);
            } else {
                error_log('Mobility Trailblazers: Voting phases table created successfully');
            }
        }
        
        // Jury assignments table
        if (!$tables_exist['assignments']) {
            $assignments_sql = "CREATE TABLE {$this->jury_assignments_table} (
                id int(11) NOT NULL AUTO_INCREMENT,
                jury_member_id bigint(20) NOT NULL,
                candidate_id bigint(20) NOT NULL,
                stage enum('shortlist','semifinal','final') NOT NULL,
                assigned_at timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY unique_assignment (jury_member_id, candidate_id, stage),
                KEY idx_jury (jury_member_id),
                KEY idx_candidate (candidate_id),
                KEY idx_stage (stage)
            ) $charset_collate;";
            
            $result3 = dbDelta($assignments_sql);
            
            if ($wpdb->last_error) {
                error_log('Mobility Trailblazers Assignments Table Error: ' . $wpdb->last_error);
            } else {
                error_log('Mobility Trailblazers: Jury assignments table created successfully');
            }
        }
        
        // Final verification and update options
        $final_check = $this->check_tables_exist();
        update_option('mt_db_version', '1.0.0');
        update_option('mt_tables_created', $final_check['count']);
        
        error_log("Mobility Trailblazers: {$final_check['count']}/3 database tables verified");
        
        // Insert default voting phases if none exist
        $this->create_default_voting_phases();
    }
    
    /**
     * Check if tables exist
     */
    private function check_tables_exist() {
        global $wpdb;
        
        $votes_exists = ($wpdb->get_var("SHOW TABLES LIKE '{$this->votes_table}'") == $this->votes_table);
        $phases_exists = ($wpdb->get_var("SHOW TABLES LIKE '{$this->voting_phases_table}'") == $this->voting_phases_table);
        $assignments_exists = ($wpdb->get_var("SHOW TABLES LIKE '{$this->jury_assignments_table}'") == $this->jury_assignments_table);
        
        $count = 0;
        if ($votes_exists) $count++;
        if ($phases_exists) $count++;
        if ($assignments_exists) $count++;
        
        return array(
            'votes' => $votes_exists,
            'phases' => $phases_exists,
            'assignments' => $assignments_exists,
            'count' => $count,
            'all_exist' => ($count === 3)
        );
    }
    
    /**
     * Create default voting phases
     */
    private function create_default_voting_phases() {
        global $wpdb;
        
        // Check if any phases already exist
        $existing_phases = $wpdb->get_var("SELECT COUNT(*) FROM {$this->voting_phases_table}");
        
        if ($existing_phases > 0) {
            return; // Phases already exist
        }
        
        // Insert default phases for 2025
        $default_phases = array(
            array(
                'phase_name' => 'Shortlist Selection 2025',
                'stage' => 'shortlist',
                'start_date' => '2025-07-01 00:00:00',
                'end_date' => '2025-08-15 23:59:59',
                'is_active' => 0,
                'description' => 'First phase: Select top 200 candidates from database'
            ),
            array(
                'phase_name' => 'Semi-Final Selection 2025',
                'stage' => 'semifinal', 
                'start_date' => '2025-08-16 00:00:00',
                'end_date' => '2025-09-30 23:59:59',
                'is_active' => 1, // Currently active
                'description' => 'Second phase: Select top 50 candidates from shortlist'
            ),
            array(
                'phase_name' => 'Final Selection 2025',
                'stage' => 'final',
                'start_date' => '2025-10-01 00:00:00',
                'end_date' => '2025-10-25 23:59:59',
                'is_active' => 0,
                'description' => 'Final phase: Select top 25 Mobility Trailblazers'
            )
        );
        
        foreach ($default_phases as $phase) {
            $wpdb->insert($this->voting_phases_table, $phase);
        }
        
        error_log('Mobility Trailblazers: Default voting phases created');
    }
    
    /**
     * Create jury member role - FIXED to ensure proper capabilities
     */
    public function create_jury_role() {
        if (!get_role('jury_member')) {
            add_role('jury_member', 'Jury Member', array(
                'read' => true,
                'vote_on_candidates' => true,
                'view_assigned_candidates' => true,
                'edit_own_votes' => true
            ));
        }
        
        // Add capabilities to administrator
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('manage_voting_phases');
            $admin_role->add_cap('assign_candidates_to_jury');
            $admin_role->add_cap('view_all_votes');
            $admin_role->add_cap('manage_jury_members');
            $admin_role->add_cap('view_voting_reports');
            $admin_role->add_cap('export_voting_data');
            
            // IMPORTANT: Also add voting capabilities to admin for testing
            $admin_role->add_cap('vote_on_candidates');
            $admin_role->add_cap('view_assigned_candidates');
        }
    }
    
    /**
     * Setup voting capabilities
     */
    public function setup_voting_capabilities() {
        // Add meta capabilities for fine-grained control
        add_filter('user_has_cap', array($this, 'voting_capabilities_filter'), 10, 3);
    }
    
    /**
     * Filter voting capabilities based on context - SIMPLIFIED for debugging
     */
    public function voting_capabilities_filter($allcaps, $caps, $args) {
        // Temporarily allow all voting during development/testing
        if (in_array('vote_on_candidates', $caps) && current_user_can('administrator')) {
            $allcaps['vote_on_candidates'] = true;
        }
        
        return $allcaps;
    }
    
    /**
     * Get active voting phase
     */
    public function get_active_voting_phase() {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$this->voting_phases_table} 
            WHERE is_active = %d 
            LIMIT 1
        ", 1));
    }
    
    /**
     * Register REST API endpoints for voting - FIXED permission callbacks
     */
    public function register_voting_endpoints() {
        // Get assigned candidates for current user
        register_rest_route('mt/v1', '/my-candidates', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_my_candidates'),
            'permission_callback' => array($this, 'check_jury_permission_flexible')
        ));
        
        // Submit/update vote
        register_rest_route('mt/v1', '/vote', array(
            'methods' => 'POST',
            'callback' => array($this, 'submit_vote'),
            'permission_callback' => array($this, 'check_jury_permission_flexible'),
            'args' => array(
                'candidate_id' => array('required' => true, 'type' => 'integer'),
                'pioneer_spirit' => array('required' => true, 'type' => 'integer'),
                'innovation_degree' => array('required' => true, 'type' => 'integer'),
                'implementation_power' => array('required' => true, 'type' => 'integer'),
                'role_model_function' => array('required' => true, 'type' => 'integer'),
                'comments' => array('required' => false, 'type' => 'string'),
                'is_final' => array('required' => false, 'type' => 'boolean')
            )
        ));
        
        // Get voting progress (admin only)
        register_rest_route('mt/v1', '/admin/voting-progress', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_voting_progress'),
            'permission_callback' => array($this, 'check_admin_permission_flexible')
        ));
        
        // Get database status
        register_rest_route('mt/v1', '/admin/db-status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_database_status'),
            'permission_callback' => array($this, 'check_admin_permission_flexible')
        ));
    }
    
    /**
     * FIXED: More flexible jury permission check
     */
    public function check_jury_permission_flexible($request) {
        // Allow if user is logged in AND (has jury role OR is admin)
        return is_user_logged_in() && (
            current_user_can('vote_on_candidates') || 
            current_user_can('administrator') ||
            current_user_can('manage_voting_phases')
        );
    }
    
    /**
     * FIXED: More flexible admin permission check
     */
    public function check_admin_permission_flexible($request) {
        // Allow if user is admin or has voting management capabilities
        return current_user_can('manage_voting_phases') || 
               current_user_can('administrator') ||
               current_user_can('manage_options');
    }
    
    /**
     * Check if user has jury permissions - Original method
     */
    public function check_jury_permission($request) {
        return current_user_can('vote_on_candidates');
    }
    
    /**
     * Check if user has admin permissions - Original method
     */
    public function check_admin_permission($request) {
        return current_user_can('manage_voting_phases');
    }
    
    /**
     * Get database status
     */
    public function get_database_status($request) {
        $tables_status = $this->check_tables_exist();
        
        return rest_ensure_response(array(
            'tables' => $tables_status,
            'db_version' => get_option('mt_db_version', 'Not set'),
            'active_phase' => $this->get_active_voting_phase(),
            'current_user' => array(
                'id' => get_current_user_id(),
                'capabilities' => array(
                    'vote_on_candidates' => current_user_can('vote_on_candidates'),
                    'manage_voting_phases' => current_user_can('manage_voting_phases'),
                    'administrator' => current_user_can('administrator')
                )
            )
        ));
    }
    
    /**
     * Get candidates assigned to current jury member - FIXED with demo data
     */
    public function get_my_candidates($request) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $active_phase = $this->get_active_voting_phase();
        
        if (!$active_phase) {
            return new WP_Error('no_active_phase', 'No active voting phase', array('status' => 400));
        }
        
        // For testing - create demo assignments if none exist
        $this->ensure_demo_assignments($user_id, $active_phase->stage);
        
        // Get assigned candidates
        $assignments = $wpdb->get_results($wpdb->prepare("
            SELECT ja.candidate_id, ja.stage, v.id as vote_id, v.is_final
            FROM {$this->jury_assignments_table} ja
            LEFT JOIN {$this->votes_table} v ON ja.candidate_id = v.candidate_id 
                AND ja.jury_member_id = v.jury_member_id 
                AND ja.stage = v.stage
            WHERE ja.jury_member_id = %d 
            AND ja.stage = %s
        ", $user_id, $active_phase->stage));
        
        $candidates = array();
        foreach ($assignments as $assignment) {
            $candidate = get_post($assignment->candidate_id);
            if ($candidate) {
                $candidate_data = array(
                    'id' => $candidate->ID,
                    'title' => $candidate->post_title,
                    'content' => $candidate->post_content,
                    'company' => get_post_meta($candidate->ID, '_candidate_company', true),
                    'position' => get_post_meta($candidate->ID, '_candidate_position', true),
                    'achievements' => get_post_meta($candidate->ID, '_candidate_achievements', true),
                    'innovation' => get_post_meta($candidate->ID, '_candidate_innovation', true),
                    'category' => wp_get_post_terms($candidate->ID, 'candidate_category'),
                    'vote_id' => $assignment->vote_id,
                    'is_voted' => !empty($assignment->vote_id),
                    'is_final' => (bool)$assignment->is_final
                );
                
                // Get existing vote if any
                if ($assignment->vote_id) {
                    $vote = $this->get_vote_by_id($assignment->vote_id);
                    $candidate_data['current_vote'] = $vote;
                }
                
                $candidates[] = $candidate_data;
            }
        }
        
        return rest_ensure_response(array(
            'phase' => $active_phase,
            'candidates' => $candidates,
            'total_assigned' => count($candidates),
            'total_voted' => count(array_filter($candidates, function($c) { return $c['is_voted']; }))
        ));
    }
    
    /**
     * Ensure demo assignments exist for testing
     */
    private function ensure_demo_assignments($user_id, $stage) {
        global $wpdb;
        
        // Check if user already has assignments
        $existing_assignments = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$this->jury_assignments_table} 
            WHERE jury_member_id = %d AND stage = %s
        ", $user_id, $stage));
        
        if ($existing_assignments > 0) {
            return; // Already has assignments
        }
        
        // Get some candidates to assign
        $candidates = get_posts(array(
            'post_type' => 'candidate',
            'posts_per_page' => 3,
            'post_status' => 'publish'
        ));
        
        // Create demo assignments
        foreach ($candidates as $candidate) {
            $wpdb->insert($this->jury_assignments_table, array(
                'jury_member_id' => $user_id,
                'candidate_id' => $candidate->ID,
                'stage' => $stage,
                'assigned_at' => current_time('mysql')
            ));
        }
        
        error_log("Created {count($candidates)} demo assignments for user {$user_id} in stage {$stage}");
    }
    
    /**
     * Submit or update a vote
     */
    public function submit_vote($request) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        $candidate_id = $request->get_param('candidate_id');
        $active_phase = $this->get_active_voting_phase();
        
        if (!$active_phase) {
            return new WP_Error('no_active_phase', 'No active voting phase', array('status' => 400));
        }
        
        // Validate scores are between 1-10
        $scores = array(
            'pioneer_spirit' => $request->get_param('pioneer_spirit'),
            'innovation_degree' => $request->get_param('innovation_degree'),
            'implementation_power' => $request->get_param('implementation_power'),
            'role_model_function' => $request->get_param('role_model_function')
        );
        
        foreach ($scores as $key => $score) {
            if ($score < 1 || $score > 10) {
                return new WP_Error('invalid_score', "Score for {$key} must be between 1 and 10", array('status' => 400));
            }
        }
        
        // Calculate total score
        $total_score = (
            $scores['pioneer_spirit'] * 0.25 +
            $scores['innovation_degree'] * 0.30 +
            $scores['implementation_power'] * 0.25 +
            $scores['role_model_function'] * 0.20
        );
        
        // Check if vote already exists
        $existing_vote = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$this->votes_table} 
            WHERE jury_member_id = %d 
            AND candidate_id = %d 
            AND stage = %s
        ", $user_id, $candidate_id, $active_phase->stage));
        
        $vote_data = array(
            'pioneer_spirit' => $scores['pioneer_spirit'],
            'innovation_degree' => $scores['innovation_degree'],
            'implementation_power' => $scores['implementation_power'],
            'role_model_function' => $scores['role_model_function'],
            'total_score' => $total_score,
            'comments' => sanitize_textarea_field($request->get_param('comments')),
            'is_final' => $request->get_param('is_final') ? 1 : 0,
            'updated_at' => current_time('mysql')
        );
        
        if ($existing_vote) {
            // Update existing vote
            $result = $wpdb->update(
                $this->votes_table,
                $vote_data,
                array(
                    'jury_member_id' => $user_id,
                    'candidate_id' => $candidate_id,
                    'stage' => $active_phase->stage
                )
            );
            $vote_id = $existing_vote->id;
        } else {
            // Insert new vote
            $vote_data['jury_member_id'] = $user_id;
            $vote_data['candidate_id'] = $candidate_id;
            $vote_data['stage'] = $active_phase->stage;
            $vote_data['voted_at'] = current_time('mysql');
            
            $result = $wpdb->insert($this->votes_table, $vote_data);
            $vote_id = $wpdb->insert_id;
        }
        
        if ($result === false) {
            return new WP_Error('vote_failed', 'Failed to save vote: ' . $wpdb->last_error, array('status' => 500));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'vote_id' => $vote_id,
            'total_score' => $total_score,
            'message' => 'Vote saved successfully'
        ));
    }
    
    /**
     * Get vote by ID
     */
    public function get_vote_by_id($vote_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$this->votes_table} WHERE id = %d
        ", $vote_id));
    }
    
    /**
     * Get voting progress (admin function)
     */
    public function get_voting_progress($request) {
        global $wpdb;
        
        $active_phase = $this->get_active_voting_phase();
        if (!$active_phase) {
            return new WP_Error('no_active_phase', 'No active voting phase', array('status' => 400));
        }
        
        // Get overall statistics
        $total_assignments = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$this->jury_assignments_table} 
            WHERE stage = %s
        ", $active_phase->stage));
        
        $total_votes = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$this->votes_table} 
            WHERE stage = %s
        ", $active_phase->stage));
        
        $final_votes = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$this->votes_table} 
            WHERE stage = %s AND is_final = 1
        ", $active_phase->stage));
        
        // Get jury participation
        $jury_stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                u.ID as jury_id,
                u.display_name,
                COUNT(ja.id) as assigned_candidates,
                COUNT(v.id) as completed_votes,
                COUNT(CASE WHEN v.is_final = 1 THEN 1 END) as final_votes
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
            LEFT JOIN {$this->jury_assignments_table} ja ON u.ID = ja.jury_member_id AND ja.stage = %s
            LEFT JOIN {$this->votes_table} v ON u.ID = v.jury_member_id AND v.stage = %s
            WHERE um.meta_key = 'wp_capabilities' 
            AND (um.meta_value LIKE '%%jury_member%%' OR um.meta_value LIKE '%%administrator%%')
            GROUP BY u.ID
        ", $active_phase->stage, $active_phase->stage));
        
        return rest_ensure_response(array(
            'phase' => $active_phase,
            'overall_stats' => array(
                'total_assignments' => (int)$total_assignments,
                'total_votes' => (int)$total_votes,
                'final_votes' => (int)$final_votes,
                'completion_rate' => $total_assignments > 0 ? round(($total_votes / $total_assignments) * 100, 2) : 0
            ),
            'jury_participation' => $jury_stats
        ));
    }
    
    /**
     * Add voting admin pages - FIXED to avoid duplicate menus
     */
    public function add_voting_admin_pages() {
        add_submenu_page(
            'mobility-trailblazers',
            'Voting Management',
            'Voting',
            'manage_voting_phases',
            'mobility-voting',
            array($this, 'voting_admin_page')
        );
    }
    
    /**
     * Voting management admin page
     */
    public function voting_admin_page() {
        $tables_status = $this->check_tables_exist();
        $active_phase = $this->get_active_voting_phase();
        
        echo '<div class="wrap">';
        echo '<h1>Voting Management</h1>';
        
        // Show database status
        if ($tables_status['all_exist']) {
            echo '<div class="notice notice-success"><p><strong>✅ All database tables created successfully!</strong></p></div>';
        } else {
            echo '<div class="notice notice-warning"><p><strong>⚠️ Database Tables:</strong> ' . $tables_status['count'] . '/3 created</p></div>';
        }
        
        // Show active phase
        if ($active_phase) {
            echo '<div class="notice notice-info"><p><strong>Active Phase:</strong> ' . esc_html($active_phase->phase_name) . ' (' . esc_html($active_phase->stage) . ')</p></div>';
        } else {
            echo '<div class="notice notice-warning"><p><strong>No active voting phase</strong></p></div>';
        }
        
        // Test API button
        echo '<div class="mt-test-section" style="background: #f9f9f9; padding: 20px; margin: 20px 0; border-radius: 5px;">';
        echo '<h3>🧪 Test Voting System</h3>';
        echo '<button id="testVotingAPI" class="button button-primary">Test API Connection</button>';
        echo '<div id="apiTestResults" style="margin-top: 10px;"></div>';
        echo '</div>';
        
        echo '<div id="voting-admin-app">';
        echo '<h2>Voting System Status</h2>';
        echo '<ul>';
        echo '<li>Votes Table: ' . ($tables_status['votes'] ? '✅ Created' : '❌ Missing') . '</li>';
        echo '<li>Phases Table: ' . ($tables_status['phases'] ? '✅ Created' : '❌ Missing') . '</li>';
        echo '<li>Assignments Table: ' . ($tables_status['assignments'] ? '✅ Created' : '❌ Missing') . '</li>';
        echo '</ul>';
        
        if ($tables_status['all_exist']) {
            echo '<h3>Quick Actions</h3>';
            echo '<p><a href="' . admin_url('admin.php?page=mobility-assignments') . '" class="button button-primary">Manage Jury Assignments</a></p>';
            echo '<p><a href="' . rest_url('mt/v1/admin/voting-progress') . '" class="button button-secondary" target="_blank">View API Progress Data</a></p>';
            
            // Shortcode examples
            echo '<h3>Shortcode Usage</h3>';
            echo '<p>Use this shortcode on any page or post to display the voting interface:</p>';
            echo '<code>[mt_voting_interface]</code>';
        }
        
        echo '</div>';
        
        // Add test script
        echo '<script>
        document.getElementById("testVotingAPI").addEventListener("click", function() {
            const resultsDiv = document.getElementById("apiTestResults");
            resultsDiv.innerHTML = "<p>Testing API...</p>";
            
            fetch("' . rest_url('mt/v1/admin/db-status') . '", {
                headers: {
                    "X-WP-Nonce": "' . wp_create_nonce('wp_rest') . '"
                }
            })
            .then(response => response.json())
            .then(data => {
                resultsDiv.innerHTML = "<div style=\"background: #dff0d8; padding: 10px; border-radius: 3px;\"><strong>✅ API Test Successful!</strong><br>Tables: " + data.tables.count + "/3<br>Active Phase: " + (data.active_phase ? data.active_phase.phase_name : "None") + "</div>";
            })
            .catch(error => {
                resultsDiv.innerHTML = "<div style=\"background: #f2dede; padding: 10px; border-radius: 3px;\"><strong>❌ API Test Failed:</strong><br>" + error.message + "</div>";
            });
        });
        </script>';
        
        echo '</div>';
    }
    
    /**
     * Enqueue voting scripts - FIXED file paths
     */
    public function enqueue_voting_scripts() {
        if (current_user_can('vote_on_candidates') || current_user_can('manage_voting_phases') || current_user_can('administrator')) {
            
            // Check if files exist before enqueueing
            $js_file = MT_PLUGIN_PATH . 'assets/js/voting-interface.js';
            $css_file = MT_PLUGIN_PATH . 'assets/css/voting-styles.css';
            
            if (file_exists($js_file)) {
                wp_enqueue_script(
                    'mt-voting-interface',
                    MT_PLUGIN_URL . 'assets/js/voting-interface.js',
                    array('jquery'),
                    MT_PLUGIN_VERSION,
                    true
                );
                
                wp_localize_script('mt-voting-interface', 'mtVotingData', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'restUrl' => rest_url('mt/v1/'),
                    'nonce' => wp_create_nonce('wp_rest'),
                    'currentUser' => get_current_user_id(),
                    'userCan' => array(
                        'vote' => current_user_can('vote_on_candidates') || current_user_can('administrator'),
                        'manageVoting' => current_user_can('manage_voting_phases') || current_user_can('administrator'),
                        'viewResults' => current_user_can('view_voting_results') || current_user_can('administrator')
                    )
                ));
            }
            
            if (file_exists($css_file)) {
                wp_enqueue_style(
                    'mt-voting-styles',
                    MT_PLUGIN_URL . 'assets/css/voting-styles.css',
                    array(),
                    MT_PLUGIN_VERSION
                );
            } else {
                // Fallback inline styles if CSS file doesn't exist
                wp_add_inline_style('wp-admin', '
                    .mt-voting-interface { padding: 20px; }
                    .mt-candidate-card { background: #fff; padding: 20px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
                    .mt-loading { text-align: center; padding: 40px; }
                    .mt-spinner { width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #0073aa; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 15px; }
                    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
                ');
            }
        }
    }
    
    /**
     * Enqueue admin voting scripts
     */
    public function enqueue_admin_voting_scripts($hook) {
        if (strpos($hook, 'mobility-') !== false) {
            $this->enqueue_voting_scripts();
        }
    }
}

// Initialize the voting system
new MobilityTrailblazersVoting();