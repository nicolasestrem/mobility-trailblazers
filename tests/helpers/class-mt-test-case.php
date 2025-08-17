<?php
/**
 * Base Test Case Class
 *
 * @package MobilityTrailblazers\Tests
 */

namespace MobilityTrailblazers\Tests;

use WP_UnitTestCase;

/**
 * Base test case class with common utilities
 */
abstract class MT_Test_Case extends WP_UnitTestCase {

    /**
     * Setup before each test
     */
    public function setUp(): void {
        parent::setUp();
        
        // Clear any existing data
        $this->clear_test_data();
        
        // Set up test user
        $this->setup_test_users();
        
        // Initialize services
        $this->init_services();
    }

    /**
     * Teardown after each test
     */
    public function tearDown(): void {
        // Clean up test data
        $this->clear_test_data();
        
        // Reset user
        wp_set_current_user(0);
        
        parent::tearDown();
    }

    /**
     * Clear test data from database
     */
    protected function clear_test_data() {
        global $wpdb;
        
        // Clear custom tables
        $tables = [
            'mt_evaluations',
            'mt_assignments',
            'mt_votes',
            'mt_candidate_scores'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}{$table}");
        }
        
        // Clear test posts
        $test_posts = get_posts([
            'post_type' => ['mt_candidate', 'mt_jury_member'],
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);
        
        foreach ($test_posts as $post) {
            wp_delete_post($post->ID, true);
        }
        
        // Clear transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mt_%'");
    }

    /**
     * Set up test users
     */
    protected function setup_test_users() {
        // Create admin user
        $this->admin_user = $this->factory->user->create([
            'user_login' => 'test_admin',
            'user_email' => 'admin@test.com',
            'role' => 'administrator'
        ]);
        
        // Create jury member user
        $this->jury_user = $this->factory->user->create([
            'user_login' => 'test_jury',
            'user_email' => 'jury@test.com',
            'role' => 'contributor'
        ]);
        
        // Add jury capability
        $user = get_user_by('id', $this->jury_user);
        $user->add_cap('mt_submit_evaluations');
    }

    /**
     * Initialize services
     */
    protected function init_services() {
        // Services will be initialized as needed in tests
    }

    /**
     * Create test candidate
     *
     * @param array $args Optional arguments
     * @return int Post ID
     */
    protected function create_test_candidate($args = []) {
        $defaults = [
            'post_title' => 'Test Candidate',
            'post_type' => 'mt_candidate',
            'post_status' => 'publish',
            'meta_input' => [
                'mt_organization' => 'Test Organization',
                'mt_position' => 'Test Position',
                'mt_linkedin_url' => 'https://linkedin.com/in/test',
                'mt_website_url' => 'https://example.com',
                'mt_biography' => 'Test biography',
                'mt_innovation_summary' => 'Test innovation summary'
            ]
        ];
        
        $args = wp_parse_args($args, $defaults);
        return wp_insert_post($args);
    }

    /**
     * Create test jury member
     *
     * @param int $user_id User ID to link
     * @param array $args Optional arguments
     * @return int Post ID
     */
    protected function create_test_jury_member($user_id = null, $args = []) {
        if (!$user_id) {
            $user_id = $this->jury_user;
        }
        
        $defaults = [
            'post_title' => 'Test Jury Member',
            'post_type' => 'mt_jury_member',
            'post_status' => 'publish',
            'meta_input' => [
                'mt_user_id' => $user_id,
                'mt_organization' => 'Test Jury Organization',
                'mt_expertise' => 'Testing'
            ]
        ];
        
        $args = wp_parse_args($args, $defaults);
        return wp_insert_post($args);
    }

    /**
     * Create test evaluation
     *
     * @param int $jury_id Jury member ID
     * @param int $candidate_id Candidate ID
     * @param array $data Evaluation data
     * @return int|false Insert ID or false
     */
    protected function create_test_evaluation($jury_id, $candidate_id, $data = []) {
        global $wpdb;
        
        $defaults = [
            'jury_member_id' => $jury_id,
            'candidate_id' => $candidate_id,
            'criterion_1' => 80,
            'criterion_2' => 75,
            'criterion_3' => 85,
            'criterion_4' => 90,
            'criterion_5' => 70,
            'comments' => 'Test evaluation comments',
            'status' => 'submitted',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'mt_evaluations',
            $data
        );
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Create test assignment
     *
     * @param int $jury_id Jury member ID
     * @param int $candidate_id Candidate ID
     * @param string $status Assignment status
     * @return int|false Insert ID or false
     */
    protected function create_test_assignment($jury_id, $candidate_id, $status = 'pending') {
        global $wpdb;
        
        $data = [
            'jury_member_id' => $jury_id,
            'candidate_id' => $candidate_id,
            'status' => $status,
            'assigned_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'mt_assignments',
            $data
        );
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Assert AJAX response success
     *
     * @param array $response AJAX response
     * @param string $message Optional message
     */
    protected function assertAjaxSuccess($response, $message = '') {
        $this->assertTrue($response['success'], $message ?: 'AJAX response should be successful');
        $this->assertArrayHasKey('data', $response, 'AJAX response should have data');
    }

    /**
     * Assert AJAX response error
     *
     * @param array $response AJAX response
     * @param string $message Optional message
     */
    protected function assertAjaxError($response, $message = '') {
        $this->assertFalse($response['success'], $message ?: 'AJAX response should be an error');
        $this->assertArrayHasKey('data', $response, 'AJAX response should have data');
    }

    /**
     * Mock AJAX request
     *
     * @param string $action AJAX action
     * @param array $data Request data
     * @param string $nonce Nonce value
     * @return array Response
     */
    protected function mock_ajax_request($action, $data = [], $nonce = '') {
        // Set up $_POST
        $_POST = array_merge($data, [
            'action' => $action,
            'nonce' => $nonce ?: wp_create_nonce('mt_ajax_nonce')
        ]);
        
        // Capture output
        ob_start();
        
        // Trigger action
        do_action('wp_ajax_' . $action);
        
        $output = ob_get_clean();
        
        // Parse JSON response
        return json_decode($output, true);
    }

    /**
     * Login as user
     *
     * @param int $user_id User ID
     */
    protected function login_as($user_id) {
        wp_set_current_user($user_id);
    }

    /**
     * Login as admin
     */
    protected function login_as_admin() {
        $this->login_as($this->admin_user);
    }

    /**
     * Login as jury member
     */
    protected function login_as_jury() {
        $this->login_as($this->jury_user);
    }
}