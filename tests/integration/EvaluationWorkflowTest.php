<?php
/**
 * Evaluation Workflow Integration Tests
 *
 * @package MobilityTrailblazers\Tests\Integration
 */

namespace MobilityTrailblazers\Tests\Integration;

use MobilityTrailblazers\Tests\MT_Test_Case;
use MobilityTrailblazers\Tests\MT_Test_Helpers;
use MobilityTrailblazers\Tests\MT_Test_Factory;

/**
 * Test complete evaluation workflow from assignment to submission
 */
class EvaluationWorkflowTest extends MT_Test_Case {
    
    use MT_Test_Helpers;

    /**
     * Test complete evaluation workflow
     */
    public function test_complete_evaluation_workflow() {
        // Step 1: Create jury member user
        $user_id = wp_create_user('jury_test', 'password123', 'jury@test.com');
        $user = get_user_by('id', $user_id);
        $user->add_cap('mt_submit_evaluations');
        
        // Step 2: Create jury member post
        $jury_data = MT_Test_Factory::jury_member([
            'meta_input' => ['mt_user_id' => $user_id]
        ]);
        $jury_id = wp_insert_post($jury_data);
        
        // Step 3: Create candidates
        $candidate_ids = MT_Test_Factory::create_candidates(5);
        
        // Step 4: Create assignments via service
        $assignment_service = new \MobilityTrailblazers\Services\MT_Assignment_Service();
        $result = $assignment_service->create_assignments($jury_id, $candidate_ids);
        
        $this->assertTrue($result['success'], 'Assignments should be created');
        $this->assertEquals(5, $result['count'], 'Should create 5 assignments');
        
        // Step 5: Login as jury member
        wp_set_current_user($user_id);
        
        // Step 6: Submit evaluations via AJAX
        foreach ($candidate_ids as $index => $candidate_id) {
            $_POST = [
                'action' => 'mt_save_evaluation',
                'nonce' => wp_create_nonce('mt_ajax_nonce'),
                'candidate_id' => $candidate_id,
                'courage_score' => 80 + $index,
                'innovation_score' => 75 + $index,
                'implementation_score' => 85 + $index,
                'relevance_score' => 90 + $index,
                'visibility_score' => 70 + $index,
                'comments' => 'Test evaluation ' . $index,
                'status' => 'submitted'
            ];
            
            // Trigger AJAX action
            ob_start();
            do_action('wp_ajax_mt_save_evaluation');
            $response = json_decode(ob_get_clean(), true);
            
            $this->assertAjaxSuccess($response, "Evaluation {$index} should be saved");
        }
        
        // Step 7: Verify all assignments are completed
        $assignment_repo = new \MobilityTrailblazers\Repositories\MT_Assignment_Repository();
        $assignments = $assignment_repo->get_by_jury_member($jury_id);
        
        foreach ($assignments as $assignment) {
            $this->assertEquals('completed', $assignment->status, 'Assignment should be completed');
        }
        
        // Step 8: Calculate candidate scores
        foreach ($candidate_ids as $candidate_id) {
            $score_service = new \MobilityTrailblazers\Services\MT_Scoring_Service();
            $score = $score_service->calculate_candidate_score($candidate_id);
            
            $this->assertGreaterThan(0, $score, 'Candidate should have a score');
        }
    }

    /**
     * Test evaluation form submission with validation
     */
    public function test_evaluation_form_validation() {
        // Setup
        $this->login_as_jury();
        $jury_id = $this->create_test_jury_member($this->jury_user);
        $candidate_id = $this->create_test_candidate();
        $this->create_test_assignment($jury_id, $candidate_id);
        
        // Test 1: Missing required fields
        $_POST = [
            'action' => 'mt_save_evaluation',
            'nonce' => wp_create_nonce('mt_ajax_nonce'),
            'candidate_id' => $candidate_id,
            'courage_score' => 80,
            // Missing other criteria
            'status' => 'submitted'
        ];
        
        ob_start();
        do_action('wp_ajax_mt_save_evaluation');
        $response = json_decode(ob_get_clean(), true);
        
        $this->assertAjaxError($response, 'Should fail with missing criteria');
        
        // Test 2: Invalid score range
        $_POST = [
            'action' => 'mt_save_evaluation',
            'nonce' => wp_create_nonce('mt_ajax_nonce'),
            'candidate_id' => $candidate_id,
            'courage_score' => 150, // Invalid
            'innovation_score' => 75,
            'implementation_score' => 85,
            'relevance_score' => 90,
            'visibility_score' => 70,
            'status' => 'submitted'
        ];
        
        ob_start();
        do_action('wp_ajax_mt_save_evaluation');
        $response = json_decode(ob_get_clean(), true);
        
        $this->assertAjaxError($response, 'Should fail with invalid score');
        
        // Test 3: Valid submission
        $_POST = [
            'action' => 'mt_save_evaluation',
            'nonce' => wp_create_nonce('mt_ajax_nonce'),
            'candidate_id' => $candidate_id,
            'courage_score' => 80,
            'innovation_score' => 75,
            'implementation_score' => 85,
            'relevance_score' => 90,
            'visibility_score' => 70,
            'comments' => 'Valid evaluation',
            'status' => 'submitted'
        ];
        
        ob_start();
        do_action('wp_ajax_mt_save_evaluation');
        $response = json_decode(ob_get_clean(), true);
        
        $this->assertAjaxSuccess($response, 'Valid submission should succeed');
    }

    /**
     * Test jury dashboard display
     */
    public function test_jury_dashboard_display() {
        // Setup
        $this->login_as_jury();
        $jury_id = $this->create_test_jury_member($this->jury_user);
        
        // Create assignments with different statuses
        $candidate1 = $this->create_test_candidate(['post_title' => 'Candidate 1']);
        $candidate2 = $this->create_test_candidate(['post_title' => 'Candidate 2']);
        $candidate3 = $this->create_test_candidate(['post_title' => 'Candidate 3']);
        
        $this->create_test_assignment($jury_id, $candidate1, 'pending');
        $this->create_test_assignment($jury_id, $candidate2, 'in_progress');
        $this->create_test_assignment($jury_id, $candidate3, 'completed');
        
        // Create evaluations
        $this->create_test_evaluation($jury_id, $candidate2, ['status' => 'draft']);
        $this->create_test_evaluation($jury_id, $candidate3, ['status' => 'submitted']);
        
        // Render dashboard
        $output = do_shortcode('[mt_jury_dashboard]');
        
        // Check output contains expected elements
        $this->assertStringContainsString('mt-jury-dashboard', $output, 'Dashboard should have main container');
        $this->assertStringContainsString('Candidate 1', $output, 'Should show pending candidate');
        $this->assertStringContainsString('Candidate 2', $output, 'Should show in-progress candidate');
        $this->assertStringContainsString('Candidate 3', $output, 'Should show completed candidate');
        
        // Check progress indicators
        $this->assertStringContainsString('33%', $output, 'Should show completion percentage');
    }

    /**
     * Test assignment auto-distribution
     */
    public function test_assignment_auto_distribution() {
        // Create jury members
        $jury_ids = MT_Test_Factory::create_jury_members(3);
        
        // Create candidates
        $candidate_ids = MT_Test_Factory::create_candidates(15);
        
        // Run auto-assignment
        $assignment_service = new \MobilityTrailblazers\Services\MT_Assignment_Service();
        $result = $assignment_service->auto_assign_candidates($candidate_ids, $jury_ids, [
            'method' => 'balanced',
            'candidates_per_jury' => 5
        ]);
        
        $this->assertTrue($result['success'], 'Auto-assignment should succeed');
        $this->assertEquals(15, $result['total_assignments'], 'Should create 15 assignments');
        
        // Check distribution
        foreach ($jury_ids as $jury_id) {
            $assignments = $assignment_service->get_jury_assignments($jury_id);
            $this->assertCount(5, $assignments, 'Each jury should have 5 assignments');
        }
    }

    /**
     * Test evaluation email notifications
     */
    public function test_evaluation_notifications() {
        // Enable email notifications
        update_option('mt_enable_notifications', true);
        update_option('mt_notification_email', 'admin@test.com');
        
        // Setup
        $this->login_as_jury();
        $jury_id = $this->create_test_jury_member($this->jury_user);
        $candidate_id = $this->create_test_candidate();
        $this->create_test_assignment($jury_id, $candidate_id);
        
        // Hook into wp_mail to capture email
        $email_sent = false;
        $email_data = null;
        
        add_filter('wp_mail', function($args) use (&$email_sent, &$email_data) {
            $email_sent = true;
            $email_data = $args;
            return $args;
        });
        
        // Submit evaluation
        $evaluation_service = new \MobilityTrailblazers\Services\MT_Evaluation_Service();
        $evaluation_service->save_evaluation([
            'jury_member_id' => $jury_id,
            'candidate_id' => $candidate_id,
            'courage_score' => 80,
            'innovation_score' => 75,
            'implementation_score' => 85,
            'relevance_score' => 90,
            'visibility_score' => 70,
            'status' => 'submitted'
        ]);
        
        // Check email was sent
        $this->assertTrue($email_sent, 'Email should be sent');
        $this->assertEquals('admin@test.com', $email_data['to'], 'Email should go to admin');
        $this->assertStringContainsString('Evaluation Submitted', $email_data['subject'], 'Subject should mention evaluation');
        
        // Clean up
        delete_option('mt_enable_notifications');
        delete_option('mt_notification_email');
    }

    /**
     * Test concurrent evaluation submissions
     */
    public function test_concurrent_evaluation_submissions() {
        // Create multiple jury members
        $jury_users = [];
        $jury_ids = [];
        
        for ($i = 1; $i <= 3; $i++) {
            $user_id = wp_create_user("jury_{$i}", 'password', "jury{$i}@test.com");
            $user = get_user_by('id', $user_id);
            $user->add_cap('mt_submit_evaluations');
            
            $jury_id = wp_insert_post([
                'post_title' => "Jury Member {$i}",
                'post_type' => 'mt_jury_member',
                'post_status' => 'publish',
                'meta_input' => ['mt_user_id' => $user_id]
            ]);
            
            $jury_users[] = $user_id;
            $jury_ids[] = $jury_id;
        }
        
        // Create shared candidate
        $candidate_id = $this->create_test_candidate();
        
        // Create assignments for all jury members
        foreach ($jury_ids as $jury_id) {
            $this->create_test_assignment($jury_id, $candidate_id);
        }
        
        // Submit evaluations "concurrently"
        $evaluation_service = new \MobilityTrailblazers\Services\MT_Evaluation_Service();
        $results = [];
        
        foreach ($jury_ids as $index => $jury_id) {
            $result = $evaluation_service->save_evaluation([
                'jury_member_id' => $jury_id,
                'candidate_id' => $candidate_id,
                'courage_score' => 70 + ($index * 10),
                'innovation_score' => 75 + ($index * 10),
                'implementation_score' => 80 + ($index * 10),
                'relevance_score' => 85 + ($index * 10),
                'visibility_score' => 90 + ($index * 10),
                'status' => 'submitted'
            ]);
            
            $results[] = $result;
        }
        
        // All should succeed
        foreach ($results as $result) {
            $this->assertTrue($result['success'], 'Each evaluation should save successfully');
        }
        
        // Check all evaluations exist
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mt_evaluations WHERE candidate_id = %d",
            $candidate_id
        ));
        
        $this->assertEquals(3, $count, 'Should have 3 evaluations for the candidate');
    }
}