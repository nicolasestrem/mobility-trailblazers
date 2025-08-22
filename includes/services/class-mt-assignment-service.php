<?php
/**
 * Assignment Service
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

namespace MobilityTrailblazers\Services;

use MobilityTrailblazers\Interfaces\MT_Service_Interface;
use MobilityTrailblazers\Interfaces\MT_Assignment_Repository_Interface;
use MobilityTrailblazers\Repositories\MT_Assignment_Repository;
use MobilityTrailblazers\Core\MT_Audit_Logger;
use MobilityTrailblazers\Core\MT_Logger;
use MobilityTrailblazers\Core\MT_Plugin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Assignment_Service
 *
 * Handles business logic for jury assignments
 */
class MT_Assignment_Service implements MT_Service_Interface {
    
    /**
     * Repository instance
     *
     * @var MT_Assignment_Repository_Interface
     */
    private $repository;
    
    /**
     * Validation errors
     *
     * @var array
     */
    private $errors = [];
    
    /**
     * Constructor with dependency injection support
     *
     * @param MT_Assignment_Repository_Interface|null $repository Optional repository dependency
     */
    public function __construct(MT_Assignment_Repository_Interface $repository = null) {
        // Use dependency injection if repository is provided
        if ($repository !== null) {
            $this->repository = $repository;
            return;
        }
        
        // Backward compatibility: try to get repository from container
        try {
            $container = MT_Plugin::container();
            
            if ($container->has('MobilityTrailblazers\Interfaces\MT_Assignment_Repository_Interface')) {
                $this->repository = $container->make('MobilityTrailblazers\Interfaces\MT_Assignment_Repository_Interface');
            } else {
                // Fallback to direct instantiation
                $this->repository = new MT_Assignment_Repository();
            }
        } catch (\Exception $e) {
            // Final fallback: direct instantiation
            MT_Logger::warning('Container not available for Assignment Service', [
                'exception' => $e->getMessage()
            ]);
            $this->repository = new MT_Assignment_Repository();
        }
    }
    
    /**
     * Process assignment request
     *
     * @param array $data Assignment data
     * @return mixed
     */
    public function process($data) {
        $this->errors = [];
        
        // Determine assignment type
        if (isset($data['assignment_type']) && $data['assignment_type'] === 'auto') {
            return $this->process_auto_assignment($data);
        } else {
            return $this->process_manual_assignment($data);
        }
    }
    
    /**
     * Process manual assignment
     *
     * @param array $data Assignment data
     * @return bool
     */
    private function process_manual_assignment($data) {
        if (!$this->validate($data)) {
            return false;
        }
        
        // Create single assignment
        $assignment_data = [
            'jury_member_id' => intval($data['jury_member_id']),
            'candidate_id' => intval($data['candidate_id'])
        ];
        
        // Check if assignment already exists
        if ($this->repository->exists($assignment_data['jury_member_id'], $assignment_data['candidate_id'])) {
            $this->errors[] = __('This assignment already exists.', 'mobility-trailblazers');
            return false;
        }
        
        $result = $this->repository->create($assignment_data);
        
        if ($result) {
            do_action('mt_assignment_created', $result, $assignment_data);
            MT_Audit_Logger::log('assignment_created', 'assignment', $result, $assignment_data);
            return true;
        }
        
        $this->errors[] = __('Failed to create assignment.', 'mobility-trailblazers');
        return false;
    }
    
    /**
     * Process auto assignment
     *
     * @param array $data Auto-assignment parameters
     * @return bool
     */
    private function process_auto_assignment($data) {
        if (!$this->validate_auto_assignment($data)) {
            return false;
        }
        
        // Get all jury members with pagination
        $paged = 1;
        $all_jury_members = [];
        
        do {
            $jury_members = get_posts([
                'post_type' => 'mt_jury_member',
                'posts_per_page' => 50,
                'paged' => $paged,
                'post_status' => 'publish',
                'fields' => 'ids'
            ]);
            
            if (!empty($jury_members)) {
                $all_jury_members = array_merge($all_jury_members, $jury_members);
            }
            
            $paged++;
        } while (!empty($jury_members));
        
        $jury_members = $all_jury_members;
        
        if (empty($jury_members)) {
            $this->errors[] = __('No jury members found.', 'mobility-trailblazers');
            return false;
        }
        
        // Get all candidates with pagination
        $paged = 1;
        $all_candidates = [];
        
        do {
            $candidates = get_posts([
                'post_type' => 'mt_candidate',
                'posts_per_page' => 50,
                'paged' => $paged,
                'post_status' => 'publish',
                'fields' => 'ids'
            ]);
            
            if (!empty($candidates)) {
                $all_candidates = array_merge($all_candidates, $candidates);
            }
            
            $paged++;
        } while (!empty($candidates));
        
        $candidates = $all_candidates;
        
        if (empty($candidates)) {
            $this->errors[] = __('No candidates found.', 'mobility-trailblazers');
            return false;
        }
        
        // Clear existing assignments if requested
        if (!empty($data['clear_existing'])) {
            $this->repository->clear_all();
        }
        
        // Distribute candidates
        $candidates_per_jury = intval($data['candidates_per_jury']);
        $distribution_type = $data['distribution_type'] ?? 'balanced';
        
        if ($distribution_type === 'random') {
            $assignments = $this->distribute_randomly($jury_members, $candidates, $candidates_per_jury);
        } else {
            $assignments = $this->distribute_balanced($jury_members, $candidates, $candidates_per_jury);
        }
        
        // Bulk create assignments
        $created = $this->repository->bulk_create($assignments);
        
        if ($created > 0) {
            do_action('mt_auto_assignment_completed', $created, $data);
            return true;
        }
        
        $this->errors[] = __('No assignments were created.', 'mobility-trailblazers');
        return false;
    }
    
    /**
     * Remove assignment by ID (efficient method)
     *
     * @param int $assignment_id Assignment ID
     * @return bool
     */
    public function remove_by_id($assignment_id) {
        $this->errors = [];
        
        // Get assignment directly by ID
        $assignment = $this->repository->find($assignment_id);
        
        if (!$assignment) {
            $this->errors[] = __('Assignment not found.', 'mobility-trailblazers');
            return false;
        }
        
        // Capture assignment details before deletion for audit log
        $assignment_details = [
            'jury_member_id' => $assignment->jury_member_id,
            'candidate_id' => $assignment->candidate_id,
            'assigned_at' => $assignment->assigned_at ?? null,
            'assigned_by' => $assignment->assigned_by ?? null,
            'removed_by' => get_current_user_id()
        ];
        
        // Get jury member and candidate names for better audit trail
        $jury_member = get_post($assignment->jury_member_id);
        $candidate = get_post($assignment->candidate_id);
        if ($jury_member) {
            $assignment_details['jury_member_name'] = $jury_member->post_title;
        }
        if ($candidate) {
            $assignment_details['candidate_name'] = $candidate->post_title;
        }
        
        $result = $this->repository->delete($assignment_id);
        
        if ($result) {
            do_action('mt_assignment_removed', $assignment->jury_member_id, $assignment->candidate_id);
            MT_Audit_Logger::log(
                'assignment_removed', 
                'assignment', 
                $assignment_id,
                $assignment_details
            );
            return true;
        }
        
        $this->errors[] = __('Failed to remove assignment.', 'mobility-trailblazers');
        return false;
    }
    
    /**
     * Remove assignment by jury member and candidate IDs (legacy method)
     *
     * @deprecated Use remove_by_id() for better performance
     * @param int $jury_member_id Jury member ID
     * @param int $candidate_id Candidate ID
     * @return bool
     */
    public function remove_assignment($jury_member_id, $candidate_id) {
        $this->errors = [];
        
        // Find the assignment (inefficient - requires additional query)
        $assignments = $this->repository->find_all([
            'jury_member_id' => $jury_member_id,
            'candidate_id' => $candidate_id,
            'limit' => 1
        ]);
        
        if (empty($assignments)) {
            $this->errors[] = __('Assignment not found.', 'mobility-trailblazers');
            return false;
        }
        
        // Use the efficient method
        return $this->remove_by_id($assignments[0]->id);
    }
    
    /**
     * Validate manual assignment data
     *
     * @param array $data Input data
     * @return bool
     */
    public function validate($data) {
        $valid = true;
        
        if (empty($data['jury_member_id'])) {
            $this->errors[] = __('Jury member is required.', 'mobility-trailblazers');
            $valid = false;
        }
        
        if (empty($data['candidate_id'])) {
            $this->errors[] = __('Candidate is required.', 'mobility-trailblazers');
            $valid = false;
        }
        
        return $valid;
    }
    
    /**
     * Validate auto assignment data
     *
     * @param array $data Input data
     * @return bool
     */
    private function validate_auto_assignment($data) {
        $valid = true;
        
        if (!isset($data['candidates_per_jury']) || intval($data['candidates_per_jury']) < 1) {
            $this->errors[] = __('Candidates per jury must be at least 1.', 'mobility-trailblazers');
            $valid = false;
        }
        
        return $valid;
    }
    
    /**
     * Get validation errors
     *
     * @return array
     */
    public function get_errors() {
        return $this->errors;
    }
    
    /**
     * Validate assignment distribution
     * Checks if assignments are evenly distributed among jury members
     *
     * @param string $method Distribution method used
     * @return bool True if distribution is balanced
     * @since 2.2.29
     */
    public function validate_assignment_distribution($method = 'balanced') {
        $assignments = $this->repository->find_all();
        
        if (empty($assignments)) {
            return true; // No assignments to validate
        }
        
        // Group by jury member
        $jury_assignments = [];
        foreach ($assignments as $assignment) {
            if (!isset($jury_assignments[$assignment->jury_member_id])) {
                $jury_assignments[$assignment->jury_member_id] = 0;
            }
            $jury_assignments[$assignment->jury_member_id]++;
        }
        
        // Calculate distribution statistics
        $counts = array_values($jury_assignments);
        $avg = array_sum($counts) / count($counts);
        $variance = 0;
        
        foreach ($counts as $count) {
            $variance += pow($count - $avg, 2);
        }
        $variance = $variance / count($counts);
        $std_dev = sqrt($variance);
        
        // Log distribution statistics
        MT_Audit_Logger::log('assignment_distribution_validated', 'assignment', null, [
            'method' => $method,
            'average_per_jury' => round($avg, 2),
            'std_deviation' => round($std_dev, 2),
            'min_assignments' => min($counts),
            'max_assignments' => max($counts),
            'jury_count' => count($jury_assignments),
            'distribution_quality' => $std_dev <= 1.5 ? 'Excellent' : ($std_dev <= 3 ? 'Good' : 'Poor')
        ]);
        
        // Return true if distribution is balanced (low standard deviation)
        return $std_dev <= 1.5; // Acceptable variance threshold
    }
    
    /**
     * Rebalance assignments for more even distribution
     * Moves assignments from over-assigned to under-assigned jury members
     *
     * @return array Result with success status and moved count
     * @since 2.2.29
     */
    public function rebalance_assignments() {
        global $wpdb;
        $table = $wpdb->prefix . 'mt_jury_assignments';
        
        // Get current distribution
        $distribution = $wpdb->get_results("
            SELECT jury_member_id, COUNT(*) as count
            FROM {$table}
            GROUP BY jury_member_id
            ORDER BY count DESC
        ");
        
        if (empty($distribution)) {
            return [
                'success' => false,
                'message' => __('No assignments to balance', 'mobility-trailblazers')
            ];
        }
        
        // Calculate target distribution
        $total = array_sum(array_column($distribution, 'count'));
        $jury_count = count($distribution);
        $target_per_jury = floor($total / $jury_count);
        
        // Identify over-assigned and under-assigned jury members
        $over_assigned = [];
        $under_assigned = [];
        
        foreach ($distribution as $jury) {
            $diff = $jury->count - $target_per_jury;
            if ($diff > 1) {
                $over_assigned[] = [
                    'id' => $jury->jury_member_id,
                    'excess' => $diff
                ];
            } elseif ($diff < 0) {
                $under_assigned[] = [
                    'id' => $jury->jury_member_id,
                    'needed' => abs($diff)
                ];
            }
        }
        
        // Rebalance by moving assignments
        $moved = 0;
        $moves = [];
        
        foreach ($over_assigned as &$over) {
            foreach ($under_assigned as &$under) {
                if ($under['needed'] <= 0 || $over['excess'] <= 0) {
                    continue;
                }
                
                // Get assignments that can be moved
                $moveable = $wpdb->get_results($wpdb->prepare("
                    SELECT id, candidate_id 
                    FROM {$table}
                    WHERE jury_member_id = %d
                    AND candidate_id NOT IN (
                        SELECT candidate_id 
                        FROM {$table} 
                        WHERE jury_member_id = %d
                    )
                    LIMIT %d
                ", $over['id'], $under['id'], min($over['excess'], $under['needed'])));
                
                foreach ($moveable as $assignment) {
                    // Move assignment
                    $wpdb->update(
                        $table,
                        [
                            'jury_member_id' => $under['id'],
                            'updated_at' => current_time('mysql')
                        ],
                        ['id' => $assignment->id]
                    );
                    
                    $moves[] = [
                        'assignment_id' => $assignment->id,
                        'from_jury' => $over['id'],
                        'to_jury' => $under['id'],
                        'candidate_id' => $assignment->candidate_id
                    ];
                    
                    $moved++;
                    $under['needed']--;
                    $over['excess']--;
                    
                    if ($over['excess'] <= 0) {
                        break;
                    }
                }
            }
        }
        
        // Clear caches
        $this->repository->clear_all_caches();
        
        // Log the rebalancing action
        MT_Audit_Logger::log('assignments_rebalanced', 'assignment', null, [
            'moved_count' => $moved,
            'moves' => $moves,
            'target_per_jury' => $target_per_jury,
            'jury_count' => $jury_count
        ]);
        
        return [
            'success' => true,
            'moved' => $moved,
            'message' => sprintf(
                __('%d assignments rebalanced across %d jury members', 'mobility-trailblazers'),
                $moved,
                $jury_count
            ),
            'details' => [
                'target_per_jury' => $target_per_jury,
                'moves' => $moves
            ]
        ];
    }
    
    /**
     * Get distribution statistics
     * Returns detailed statistics about current assignment distribution
     *
     * @return array Distribution statistics
     * @since 2.2.29
     */
    public function get_distribution_statistics() {
        global $wpdb;
        $table = $wpdb->prefix . 'mt_jury_assignments';
        
        // Get distribution data
        $distribution = $wpdb->get_results("
            SELECT 
                jury_member_id,
                COUNT(*) as assignment_count,
                COUNT(DISTINCT candidate_id) as unique_candidates
            FROM {$table}
            GROUP BY jury_member_id
        ");
        
        if (empty($distribution)) {
            return [
                'total_assignments' => 0,
                'jury_count' => 0,
                'average' => 0,
                'min' => 0,
                'max' => 0,
                'std_deviation' => 0,
                'quality' => 'N/A'
            ];
        }
        
        // Calculate statistics
        $counts = array_column($distribution, 'assignment_count');
        $total = array_sum($counts);
        $avg = $total / count($counts);
        
        // Calculate standard deviation
        $variance = 0;
        foreach ($counts as $count) {
            $variance += pow($count - $avg, 2);
        }
        $std_dev = sqrt($variance / count($counts));
        
        // Determine quality
        if ($std_dev <= 1.5) {
            $quality = 'Excellent';
        } elseif ($std_dev <= 3) {
            $quality = 'Good';
        } elseif ($std_dev <= 5) {
            $quality = 'Fair';
        } else {
            $quality = 'Poor';
        }
        
        return [
            'total_assignments' => $total,
            'jury_count' => count($distribution),
            'average' => round($avg, 2),
            'min' => min($counts),
            'max' => max($counts),
            'std_deviation' => round($std_dev, 2),
            'quality' => $quality,
            'distribution' => $distribution
        ];
    }
    
    /**
     * Distribute candidates randomly
     *
     * @param array $jury_members Array of jury member IDs
     * @param array $candidates Array of candidate IDs
     * @param int $per_jury Number of candidates per jury member
     * @return array
     */
    private function distribute_randomly($jury_members, $candidates, $per_jury) {
        $assignments = [];
        
        foreach ($jury_members as $jury_id) {
            // Shuffle candidates for random selection
            $shuffled = $candidates;
            shuffle($shuffled);
            
            // Assign the specified number of candidates
            $assigned = array_slice($shuffled, 0, $per_jury);
            
            foreach ($assigned as $candidate_id) {
                $assignments[] = [
                    'jury_member_id' => $jury_id,
                    'candidate_id' => $candidate_id
                ];
            }
        }
        
        return $assignments;
    }
    
    /**
     * Distribute candidates in a balanced way
     *
     * @param array $jury_members Array of jury member IDs
     * @param array $candidates Array of candidate IDs
     * @param int $per_jury Number of candidates per jury member
     * @return array
     */
    private function distribute_balanced($jury_members, $candidates, $per_jury) {
        $assignments = [];
        $candidate_assignment_count = [];
        
        // Initialize assignment count for each candidate
        foreach ($candidates as $candidate_id) {
            $candidate_assignment_count[$candidate_id] = 0;
        }
        
        // Assign candidates to each jury member
        foreach ($jury_members as $jury_id) {
            // Sort candidates by assignment count (ascending)
            asort($candidate_assignment_count);
            
            // Get the least assigned candidates
            $available_candidates = array_keys($candidate_assignment_count);
            $to_assign = array_slice($available_candidates, 0, $per_jury);
            
            foreach ($to_assign as $candidate_id) {
                $assignments[] = [
                    'jury_member_id' => $jury_id,
                    'candidate_id' => $candidate_id
                ];
                
                // Increment assignment count
                $candidate_assignment_count[$candidate_id]++;
            }
        }
        
        return $assignments;
    }
    
    /**
     * Get assignment summary
     *
     * @return array
     */
    public function get_summary() {
        $stats = $this->repository->get_statistics();
        
        // Add additional summary data
        $total_jury = wp_count_posts('mt_jury_member')->publish;
        $total_candidates = wp_count_posts('mt_candidate')->publish;
        
        $stats['total_jury_members'] = $total_jury;
        $stats['total_candidates'] = $total_candidates;
        $stats['unassigned_jury'] = $total_jury - $stats['assigned_jury_members'];
        $stats['unassigned_candidates'] = $total_candidates - $stats['assigned_candidates'];
        
        return $stats;
    }
    
    /**
     * Auto assign candidates to jury members
     *
     * @param string $method Assignment method (balanced/random)
     * @param int $candidates_per_jury Number of candidates per jury member
     * @return bool
     */
    public function auto_assign($method, $candidates_per_jury) {
        $data = [
            'assignment_type' => 'auto',
            'distribution_type' => $method,
            'candidates_per_jury' => $candidates_per_jury,
            'clear_existing' => true
        ];
        
        return $this->process_auto_assignment($data);
    }
} 
