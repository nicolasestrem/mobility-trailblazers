<?php
/**
 * Evaluation Service Tests
 *
 * @package MobilityTrailblazers\Tests\Unit
 */

namespace MobilityTrailblazers\Tests\Unit\Services;

use MobilityTrailblazers\Tests\MT_Test_Case;
use MobilityTrailblazers\Tests\MT_Test_Helpers;
use MobilityTrailblazers\Services\MT_Evaluation_Service;

/**
 * Test evaluation service functionality
 */
class EvaluationServiceTest extends MT_Test_Case {
    
    use MT_Test_Helpers;
    
    /**
     * @var MT_Evaluation_Service
     */
    private $service;
    
    /**
     * @var int
     */
    private $jury_id;
    
    /**
     * @var int
     */
    private $candidate_id;

    /**
     * Setup test
     */
    public function setUp(): void {
        parent::setUp();
        
        // Initialize service
        $this->service = new MT_Evaluation_Service();
        
        // Create test data
        $this->candidate_id = $this->create_test_candidate();
        $this->jury_id = $this->create_test_jury_member();
        
        // Create assignment
        $this->create_test_assignment($this->jury_id, $this->candidate_id);
    }

    /**
     * Test saving evaluation as draft
     */
    public function test_save_evaluation_as_draft() {
        $data = [
            'jury_member_id' => $this->jury_id,
            'candidate_id' => $this->candidate_id,
            'courage_score' => 80,
            'innovation_score' => 75,
            'implementation_score' => 85,
            'relevance_score' => 90,
            'visibility_score' => 70,
            'comments' => 'Test evaluation',
            'status' => 'draft'
        ];
        
        $result = $this->service->save_evaluation($data);
        
        $this->assertTrue($result['success'], 'Evaluation should be saved successfully');
        $this->assertArrayHasKey('evaluation_id', $result, 'Result should contain evaluation ID');
        $this->assertGreaterThan(0, $result['evaluation_id'], 'Evaluation ID should be positive');
    }

    /**
     * Test submitting evaluation
     */
    public function test_submit_evaluation() {
        // First save as draft
        $data = [
            'jury_member_id' => $this->jury_id,
            'candidate_id' => $this->candidate_id,
            'courage_score' => 80,
            'innovation_score' => 75,
            'implementation_score' => 85,
            'relevance_score' => 90,
            'visibility_score' => 70,
            'comments' => 'Test evaluation',
            'status' => 'draft'
        ];
        
        $save_result = $this->service->save_evaluation($data);
        $evaluation_id = $save_result['evaluation_id'];
        
        // Now submit it
        $data['status'] = 'submitted';
        $data['evaluation_id'] = $evaluation_id;
        
        $submit_result = $this->service->save_evaluation($data);
        
        $this->assertTrue($submit_result['success'], 'Evaluation should be submitted successfully');
        
        // Check assignment status was updated
        global $wpdb;
        $assignment_status = $wpdb->get_var($wpdb->prepare(
            "SELECT status FROM {$wpdb->prefix}mt_assignments 
             WHERE jury_member_id = %d AND candidate_id = %d",
            $this->jury_id,
            $this->candidate_id
        ));
        
        $this->assertEquals('completed', $assignment_status, 'Assignment should be marked as completed');
    }

    /**
     * Test validation of evaluation scores
     */
    public function test_evaluation_score_validation() {
        // Test invalid score (over 100)
        $data = [
            'jury_member_id' => $this->jury_id,
            'candidate_id' => $this->candidate_id,
            'courage_score' => 150, // Invalid
            'innovation_score' => 75,
            'implementation_score' => 85,
            'relevance_score' => 90,
            'visibility_score' => 70,
            'status' => 'draft'
        ];
        
        $result = $this->service->save_evaluation($data);
        
        $this->assertFalse($result['success'], 'Evaluation with invalid score should fail');
        $this->assertStringContainsString('between 0 and 100', $result['message'], 'Error message should mention score range');
    }

    /**
     * Test evaluation requires assignment
     */
    public function test_evaluation_requires_assignment() {
        // Create new candidate without assignment
        $unassigned_candidate = $this->create_test_candidate();
        
        $data = [
            'jury_member_id' => $this->jury_id,
            'candidate_id' => $unassigned_candidate,
            'courage_score' => 80,
            'innovation_score' => 75,
            'implementation_score' => 85,
            'relevance_score' => 90,
            'visibility_score' => 70,
            'status' => 'draft'
        ];
        
        $result = $this->service->save_evaluation($data);
        
        $this->assertFalse($result['success'], 'Evaluation without assignment should fail');
        $this->assertStringContainsString('not assigned', $result['message'], 'Error message should mention assignment');
    }

    /**
     * Test getting evaluation
     */
    public function test_get_evaluation() {
        // Create evaluation
        $evaluation_id = $this->create_test_evaluation($this->jury_id, $this->candidate_id);
        
        // Get evaluation
        $evaluation = $this->service->get_evaluation($evaluation_id);
        
        $this->assertNotNull($evaluation, 'Evaluation should be retrieved');
        $this->assertEquals($this->jury_id, $evaluation->jury_member_id, 'Jury member ID should match');
        $this->assertEquals($this->candidate_id, $evaluation->candidate_id, 'Candidate ID should match');
    }

    /**
     * Test getting evaluations by jury member
     */
    public function test_get_evaluations_by_jury_member() {
        // Create multiple evaluations
        $candidate2 = $this->create_test_candidate();
        $candidate3 = $this->create_test_candidate();
        
        $this->create_test_assignment($this->jury_id, $candidate2);
        $this->create_test_assignment($this->jury_id, $candidate3);
        
        $this->create_test_evaluation($this->jury_id, $this->candidate_id);
        $this->create_test_evaluation($this->jury_id, $candidate2);
        $this->create_test_evaluation($this->jury_id, $candidate3);
        
        // Get evaluations
        $evaluations = $this->service->get_jury_evaluations($this->jury_id);
        
        $this->assertCount(3, $evaluations, 'Should have 3 evaluations');
        
        foreach ($evaluations as $eval) {
            $this->assertEquals($this->jury_id, $eval->jury_member_id, 'All evaluations should be for the same jury member');
        }
    }

    /**
     * Test calculating average scores
     */
    public function test_calculate_average_scores() {
        // Create multiple evaluations for same candidate
        $jury2 = $this->create_test_jury_member();
        $jury3 = $this->create_test_jury_member();
        
        $this->create_test_assignment($jury2, $this->candidate_id);
        $this->create_test_assignment($jury3, $this->candidate_id);
        
        $this->create_test_evaluation($this->jury_id, $this->candidate_id, [
            'courage_score' => 80,
            'innovation_score' => 80,
            'implementation_score' => 80,
            'relevance_score' => 80,
            'visibility_score' => 80,
            'status' => 'submitted'
        ]);
        
        $this->create_test_evaluation($jury2, $this->candidate_id, [
            'courage_score' => 90,
            'innovation_score' => 90,
            'implementation_score' => 90,
            'relevance_score' => 90,
            'visibility_score' => 90,
            'status' => 'submitted'
        ]);
        
        $this->create_test_evaluation($jury3, $this->candidate_id, [
            'courage_score' => 70,
            'innovation_score' => 70,
            'implementation_score' => 70,
            'relevance_score' => 70,
            'visibility_score' => 70,
            'status' => 'submitted'
        ]);
        
        // Calculate average
        $average = $this->service->calculate_candidate_average($this->candidate_id);
        
        $this->assertEquals(80, $average, 'Average should be 80');
    }

    /**
     * Test jury progress tracking
     */
    public function test_jury_progress_tracking() {
        // Create multiple assignments
        $candidate2 = $this->create_test_candidate();
        $candidate3 = $this->create_test_candidate();
        
        $this->create_test_assignment($this->jury_id, $candidate2);
        $this->create_test_assignment($this->jury_id, $candidate3);
        
        // Complete one evaluation
        $this->create_test_evaluation($this->jury_id, $this->candidate_id, ['status' => 'submitted']);
        
        // Draft another
        $this->create_test_evaluation($this->jury_id, $candidate2, ['status' => 'draft']);
        
        // Get progress
        $progress = $this->service->get_jury_progress($this->jury_id);
        
        $this->assertEquals(3, $progress['total'], 'Total assignments should be 3');
        $this->assertEquals(1, $progress['completed'], 'Completed evaluations should be 1');
        $this->assertEquals(1, $progress['draft'], 'Draft evaluations should be 1');
        $this->assertEquals(1, $progress['pending'], 'Pending evaluations should be 1');
        $this->assertEquals(33, $progress['completion_rate'], 'Completion rate should be 33%');
    }

    /**
     * Test evaluation update
     */
    public function test_update_evaluation() {
        // Create evaluation
        $evaluation_id = $this->create_test_evaluation($this->jury_id, $this->candidate_id, [
            'courage_score' => 70,
            'status' => 'draft'
        ]);
        
        // Update it
        $data = [
            'evaluation_id' => $evaluation_id,
            'jury_member_id' => $this->jury_id,
            'candidate_id' => $this->candidate_id,
            'courage_score' => 85,
            'innovation_score' => 75,
            'implementation_score' => 85,
            'relevance_score' => 90,
            'visibility_score' => 70,
            'status' => 'submitted'
        ];
        
        $result = $this->service->save_evaluation($data);
        
        $this->assertTrue($result['success'], 'Update should succeed');
        
        // Verify update
        $evaluation = $this->service->get_evaluation($evaluation_id);
        $this->assertEquals(85, $evaluation->courage_score, 'Score should be updated');
        $this->assertEquals('submitted', $evaluation->status, 'Status should be updated');
    }

    /**
     * Test evaluation cannot be edited after submission (if configured)
     */
    public function test_submitted_evaluation_edit_restriction() {
        // Set option to prevent editing
        update_option('mt_allow_edit_submitted', false);
        
        // Create submitted evaluation
        $evaluation_id = $this->create_test_evaluation($this->jury_id, $this->candidate_id, [
            'status' => 'submitted'
        ]);
        
        // Try to update it
        $data = [
            'evaluation_id' => $evaluation_id,
            'jury_member_id' => $this->jury_id,
            'candidate_id' => $this->candidate_id,
            'courage_score' => 95,
            'status' => 'submitted'
        ];
        
        $result = $this->service->save_evaluation($data);
        
        // Reset option
        delete_option('mt_allow_edit_submitted');
        
        $this->assertFalse($result['success'], 'Editing submitted evaluation should fail when not allowed');
    }
}