<?php
/**
 * Mobility Trailblazers - Enhanced Jury Dashboard Consistency
 * 
 * This class ensures evaluations are consistent between admin and frontend dashboards
 * and handles jury members being added or deleted dynamically.
 * 
 * @package MobilityTrailblazers
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class MT_Jury_Consistency {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get single instance of the class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Initialize hooks
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Admin notices for sync issues
        add_action('admin_notices', array($this, 'display_sync_notice'));
        
        // AJAX handlers
        add_action('wp_ajax_mt_sync_evaluations', array($this, 'ajax_sync_evaluations'));
        add_action('wp_ajax_mt_check_sync_status', array($this, 'ajax_check_sync_status'));
        
        // Handle jury member deletion
        add_action('before_delete_post', array($this, 'handle_jury_deletion'), 10, 2);
        
        // Handle jury member addition
        add_action('save_post_mt_jury', array($this, 'handle_jury_creation'), 10, 3);
        
        // Ensure consistent saving
        add_filter('mt_before_save_evaluation', array($this, 'ensure_consistent_save'), 10, 2);
    }
    
    /**
     * Get all jury post to user ID mappings
     * 
     * @return array Array of jury_post_id => user_id mappings
     */
    public function get_all_jury_mappings() {
        global $wpdb;
        
        $mappings = array();
        
        // Get all jury posts with their user IDs
        $jury_posts = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        foreach ($jury_posts as $jury_post) {
            $user_id = get_post_meta($jury_post->ID, '_mt_jury_user_id', true);
            if ($user_id) {
                $mappings[$jury_post->ID] = intval($user_id);
            }
        }
        
        return $mappings;
    }
    
    /**
     * Get jury post ID for a specific user
     * 
     * @param int $user_id WordPress user ID
     * @return int|false Jury post ID or false if not found
     */
    public function get_jury_post_id_for_user($user_id) {
        $jury_posts = get_posts(array(
            'post_type' => 'mt_jury',
            'meta_query' => array(
                array(
                    'key' => '_mt_jury_user_id',
                    'value' => $user_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1,
            'post_status' => 'any'
        ));
        
        return !empty($jury_posts) ? $jury_posts[0]->ID : false;
    }
    
    /**
     * Get user ID for a specific jury post
     * 
     * @param int $jury_post_id Jury post ID
     * @return int|false User ID or false if not found
     */
    public function get_user_id_for_jury_post($jury_post_id) {
        $user_id = get_post_meta($jury_post_id, '_mt_jury_user_id', true);
        return $user_id ? intval($user_id) : false;
    }
    
    /**
     * Check if there are sync issues
     * 
     * @return array Array with 'count' and 'details' of sync issues
     */
    public function check_sync_issues() {
        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        
        // Get all jury mappings
        $mappings = $this->get_all_jury_mappings();
        $valid_user_ids = array_values($mappings);
        $valid_jury_ids = array_keys($mappings);
        
        // Also get all existing user IDs
        $all_user_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->users}");
        
        // Find evaluations with invalid IDs
        $issues = array(
            'high_ids' => 0,
            'orphaned' => 0,
            'total' => 0,
            'details' => array()
        );
        
        // Get all unique jury_member_ids from evaluations
        $all_eval_ids = $wpdb->get_results(
            "SELECT DISTINCT jury_member_id, COUNT(*) as count 
            FROM $table_scores 
            GROUP BY jury_member_id"
        );
        
        foreach ($all_eval_ids as $eval) {
            $id = intval($eval->jury_member_id);
            $type = 'unknown';
            $needs_action = false;
            
            if (in_array($id, $all_user_ids)) {
                // It's a valid user ID
                $type = 'user_id';
                $needs_action = false;
            } elseif (in_array($id, $valid_jury_ids)) {
                // It's a jury post ID that needs conversion
                $type = 'jury_post_id';
                $needs_action = true;
                $issues['high_ids'] += intval($eval->count);
            } else {
                // It's orphaned (no matching user or jury post)
                $type = 'orphaned';
                $needs_action = false; // We preserve orphaned records
                $issues['orphaned'] += intval($eval->count);
            }
            
            // Only add to details if it needs action or is orphaned
            if ($needs_action || $type === 'orphaned') {
                $issues['details'][] = array(
                    'id' => $id,
                    'type' => $type,
                    'count' => intval($eval->count),
                    'mapped_to' => isset($mappings[$id]) ? $mappings[$id] : null
                );
            }
        }
        
        $issues['total'] = $issues['high_ids'] + $issues['orphaned'];
        
        return $issues;
    }
    
    /**
     * Display admin notice for sync issues
     */
    public function display_sync_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'mt-') === false) {
            return;
        }
        
        $issues = $this->check_sync_issues();
        
        if ($issues['total'] > 0) {
            ?>
            <div class="notice notice-warning mt-sync-notice">
                <h3><?php _e('Mobility Trailblazers: Evaluation Data Sync Required', 'mobility-trailblazers'); ?></h3>
                <p>
                    <?php 
                    printf(
                        __('Found %d evaluations that need syncing:', 'mobility-trailblazers'),
                        $issues['total']
                    );
                    ?>
                </p>
                <ul>
                    <?php if ($issues['high_ids'] > 0): ?>
                    <li><?php printf(__('%d evaluations using jury post IDs instead of user IDs', 'mobility-trailblazers'), $issues['high_ids']); ?></li>
                    <?php endif; ?>
                    <?php if ($issues['orphaned'] > 0): ?>
                    <li><?php printf(__('%d orphaned evaluations (jury member deleted)', 'mobility-trailblazers'), $issues['orphaned']); ?></li>
                    <?php endif; ?>
                </ul>
                <p>
                    <button class="button button-primary" id="mt-sync-evaluations">
                        <?php _e('Fix Evaluation Data', 'mobility-trailblazers'); ?>
                    </button>
                    <button class="button" id="mt-check-sync-details">
                        <?php _e('Show Details', 'mobility-trailblazers'); ?>
                    </button>
                    <span class="spinner" style="float: none; margin-top: 0;"></span>
                </p>
                <div id="mt-sync-results" style="display: none;"></div>
                <div id="mt-sync-details" style="display: none; margin-top: 10px;">
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Type</th>
                                <th>Evaluations</th>
                                <th>Action Needed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($issues['details'] as $detail): ?>
                            <tr>
                                <td><?php echo $detail['id']; ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $detail['type'])); ?></td>
                                <td><?php echo $detail['count']; ?></td>
                                <td>
                                    <?php 
                                    if ($detail['type'] === 'jury_post_id' && $detail['mapped_to']) {
                                        printf(__('Convert to user ID %d', 'mobility-trailblazers'), $detail['mapped_to']);
                                    } elseif ($detail['type'] === 'orphaned') {
                                        _e('Will be preserved (orphaned)', 'mobility-trailblazers');
                                    } else {
                                        _e('No action needed', 'mobility-trailblazers');
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <script>
                jQuery(document).ready(function($) {
                    $('#mt-sync-evaluations').on('click', function() {
                        var button = $(this);
                        var spinner = $('.mt-sync-notice .spinner');
                        var results = $('#mt-sync-results');
                        
                        button.prop('disabled', true);
                        spinner.addClass('is-active');
                        results.slideUp();
                        
                        $.post(ajaxurl, {
                            action: 'mt_sync_evaluations',
                            nonce: '<?php echo wp_create_nonce('mt_sync_evaluations'); ?>'
                        }, function(response) {
                            results.html('<div class="' + (response.success ? 'notice-success' : 'notice-error') + ' notice"><p>' + response.data.message + '</p></div>');
                            results.slideDown();
                            
                            if (response.success) {
                                setTimeout(function() {
                                    location.reload();
                                }, 2000);
                            }
                        }).fail(function() {
                            results.html('<div class="notice-error notice"><p><?php _e('An error occurred. Please try again.', 'mobility-trailblazers'); ?></p></div>');
                            results.slideDown();
                        }).always(function() {
                            button.prop('disabled', false);
                            spinner.removeClass('is-active');
                        });
                    });
                    
                    $('#mt-check-sync-details').on('click', function() {
                        $('#mt-sync-details').slideToggle();
                        $(this).text($(this).text() === '<?php _e('Show Details', 'mobility-trailblazers'); ?>' ? '<?php _e('Hide Details', 'mobility-trailblazers'); ?>' : '<?php _e('Show Details', 'mobility-trailblazers'); ?>');
                    });
                });
                </script>
            </div>
            <?php
        }
    }
    
    /**
     * AJAX handler for syncing evaluations
     */
    public function ajax_sync_evaluations() {
        if (!check_ajax_referer('mt_sync_evaluations', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed', 'mobility-trailblazers')));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'mobility-trailblazers')));
        }
        
        $result = $this->sync_all_evaluations();
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Successfully synced %d evaluations. %s', 'mobility-trailblazers'),
                    $result['synced'],
                    $result['message']
                )
            ));
        } else {
            wp_send_json_error(array('message' => $result['message']));
        }
    }
    
    /**
     * Sync all evaluations to use correct user IDs
     * Handles duplicate entries by merging or keeping the most recent
     * 
     * @return array Result array with success status and message
     */
    public function sync_all_evaluations() {
        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        
        $synced = 0;
        $duplicates_handled = 0;
        $errors = array();
        
        // Get all jury mappings
        $mappings = $this->get_all_jury_mappings();
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Process each jury post ID to user ID mapping
            foreach ($mappings as $jury_post_id => $user_id) {
                // First, check for potential duplicates
                $duplicates = $wpdb->get_results($wpdb->prepare(
                    "SELECT j.*, u.id as user_eval_id, u.total_score as user_score, u.evaluated_at as user_evaluated
                    FROM $table_scores j
                    LEFT JOIN $table_scores u ON (
                        u.candidate_id = j.candidate_id 
                        AND u.jury_member_id = %d 
                        AND u.evaluation_round = j.evaluation_round
                    )
                    WHERE j.jury_member_id = %d",
                    $user_id,
                    $jury_post_id
                ));
                
                foreach ($duplicates as $dup) {
                    if ($dup->user_eval_id) {
                        // Duplicate exists - decide which to keep
                        $jury_time = strtotime($dup->evaluated_at);
                        $user_time = strtotime($dup->user_evaluated);
                        
                        if ($jury_time > $user_time) {
                            // Jury post evaluation is newer - update the user ID record
                            $wpdb->update(
                                $table_scores,
                                array(
                                    'courage_score' => $dup->courage_score,
                                    'innovation_score' => $dup->innovation_score,
                                    'implementation_score' => $dup->implementation_score,
                                    'mobility_relevance_score' => $dup->mobility_relevance_score,
                                    'visibility_score' => $dup->visibility_score,
                                    'total_score' => $dup->total_score,
                                    'comments' => $dup->comments,
                                    'evaluated_at' => $dup->evaluated_at
                                ),
                                array(
                                    'id' => $dup->user_eval_id
                                ),
                                array('%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s'),
                                array('%d')
                            );
                            
                            // Delete the jury post record
                            $wpdb->delete(
                                $table_scores,
                                array('id' => $dup->id),
                                array('%d')
                            );
                            
                            $duplicates_handled++;
                            error_log(sprintf(
                                'MT Sync: Merged newer jury evaluation (ID %d) into user evaluation (ID %d) for candidate %d',
                                $dup->id,
                                $dup->user_eval_id,
                                $dup->candidate_id
                            ));
                        } else {
                            // User evaluation is newer - just delete the jury post record
                            $wpdb->delete(
                                $table_scores,
                                array('id' => $dup->id),
                                array('%d')
                            );
                            
                            $duplicates_handled++;
                            error_log(sprintf(
                                'MT Sync: Removed older jury evaluation (ID %d) in favor of user evaluation (ID %d) for candidate %d',
                                $dup->id,
                                $dup->user_eval_id,
                                $dup->candidate_id
                            ));
                        }
                    } else {
                        // No duplicate - safe to convert
                        $result = $wpdb->update(
                            $table_scores,
                            array('jury_member_id' => $user_id),
                            array('id' => $dup->id),
                            array('%d'),
                            array('%d')
                        );
                        
                        if ($result !== false) {
                            $synced++;
                        } elseif ($wpdb->last_error) {
                            $errors[] = $wpdb->last_error;
                        }
                    }
                }
            }
            
            // Handle orphaned evaluations
            $orphaned_count = $wpdb->get_var(
                "SELECT COUNT(DISTINCT jury_member_id) FROM $table_scores 
                WHERE jury_member_id NOT IN (SELECT ID FROM {$wpdb->users})
                AND jury_member_id NOT IN (" . implode(',', array_keys($mappings)) . ")"
            );
            
            if (empty($errors)) {
                $wpdb->query('COMMIT');
                
                $message = sprintf(
                    __('Successfully processed evaluations: %d converted, %d duplicates resolved.', 'mobility-trailblazers'),
                    $synced,
                    $duplicates_handled
                );
                
                if ($orphaned_count > 0) {
                    $message .= ' ' . sprintf(
                        __('%d orphaned evaluations were preserved.', 'mobility-trailblazers'),
                        $orphaned_count
                    );
                }
                
                return array(
                    'success' => true,
                    'synced' => $synced,
                    'duplicates' => $duplicates_handled,
                    'orphaned' => $orphaned_count,
                    'message' => $message
                );
            } else {
                $wpdb->query('ROLLBACK');
                return array(
                    'success' => false,
                    'message' => __('Database error occurred: ', 'mobility-trailblazers') . implode(', ', array_unique($errors))
                );
            }
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return array(
                'success' => false,
                'message' => __('Error: ', 'mobility-trailblazers') . $e->getMessage()
            );
        }
    }
    
    /**
     * Handle jury member deletion
     * 
     * @param int $post_id Post ID being deleted
     * @param WP_Post $post Post object
     */
    public function handle_jury_deletion($post_id, $post) {
        if ($post->post_type !== 'mt_jury') {
            return;
        }
        
        $user_id = get_post_meta($post_id, '_mt_jury_user_id', true);
        
        if ($user_id) {
            // Log the deletion for reference
            error_log(sprintf(
                'MT Jury Deletion: Jury post %d (linked to user %d) is being deleted. Evaluations will be preserved.',
                $post_id,
                $user_id
            ));
            
            // Note: We're NOT deleting evaluations - they remain with the user ID
            // This preserves the evaluation history even if jury member is removed
        }
    }
    
    /**
     * Handle jury member creation/update
     * 
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     * @param bool $update Whether this is an update
     */
    public function handle_jury_creation($post_id, $post, $update) {
        // Only proceed if user ID is set
        $user_id = get_post_meta($post_id, '_mt_jury_user_id', true);
        
        if (!$user_id) {
            return;
        }
        
        // Check if there are any evaluations that might need updating
        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        
        // If this is a new jury member, check for any existing evaluations by the user
        if (!$update) {
            $existing_evals = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_scores WHERE jury_member_id = %d",
                $user_id
            ));
            
            if ($existing_evals > 0) {
                error_log(sprintf(
                    'MT Jury Creation: User %d already has %d evaluations when creating jury post %d',
                    $user_id,
                    $existing_evals,
                    $post_id
                ));
            }
        }
    }
    
    /**
     * Ensure evaluations are saved with correct user ID
     * 
     * @param array $data Evaluation data
     * @param array $context Additional context
     * @return array Modified evaluation data
     */
    public function ensure_consistent_save($data, $context) {
        // Always use WordPress user ID
        if (isset($data['jury_member_id'])) {
            $jury_member_id = intval($data['jury_member_id']);
            
            // Check if it's a jury post ID
            if ($jury_member_id > 100) { // Assuming user IDs are typically < 100
                $user_id = $this->get_user_id_for_jury_post($jury_member_id);
                if ($user_id) {
                    $data['jury_member_id'] = $user_id;
                    error_log(sprintf(
                        'MT Evaluation Save: Converted jury post ID %d to user ID %d',
                        $jury_member_id,
                        $user_id
                    ));
                }
            }
        }
        
        // If no jury_member_id, use current user
        if (empty($data['jury_member_id'])) {
            $data['jury_member_id'] = get_current_user_id();
        }
        
        return $data;
    }
    
    /**
     * Get consistent evaluation count for a user
     * This checks both user ID and any linked jury post IDs
     * 
     * @param int $user_id WordPress user ID
     * @return int Number of evaluations
     */
    public function get_evaluation_count($user_id) {
        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        
        // Get jury post ID if exists
        $jury_post_id = $this->get_jury_post_id_for_user($user_id);
        
        if ($jury_post_id) {
            // Count evaluations with either user ID or jury post ID
            return $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT candidate_id) 
                FROM $table_scores 
                WHERE jury_member_id IN (%d, %d)",
                $user_id,
                $jury_post_id
            ));
        } else {
            // Just count by user ID
            return $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT candidate_id) 
                FROM $table_scores 
                WHERE jury_member_id = %d",
                $user_id
            ));
        }
    }
    
    /**
     * Check if a user has evaluated a specific candidate
     * 
     * @param int $user_id WordPress user ID
     * @param int $candidate_id Candidate post ID
     * @return bool Whether the user has evaluated this candidate
     */
    public function has_evaluated($user_id, $candidate_id) {
        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        
        // Get jury post ID if exists
        $jury_post_id = $this->get_jury_post_id_for_user($user_id);
        
        if ($jury_post_id) {
            // Check with both IDs
            return $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) 
                FROM $table_scores 
                WHERE candidate_id = %d 
                AND jury_member_id IN (%d, %d)",
                $candidate_id,
                $user_id,
                $jury_post_id
            )) > 0;
        } else {
            // Check with user ID only
            return $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) 
                FROM $table_scores 
                WHERE candidate_id = %d 
                AND jury_member_id = %d",
                $candidate_id,
                $user_id
            )) > 0;
        }
    }

    /**
     * Alternative manual sync method for complex cases
     * This method provides more control and detailed feedback
     */
    public function manual_sync_with_details() {
        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        
        $report = array(
            'checked' => 0,
            'converted' => 0,
            'duplicates_removed' => 0,
            'errors' => array(),
            'details' => array()
        );
        
        // Get all jury mappings
        $mappings = $this->get_all_jury_mappings();
        
        foreach ($mappings as $jury_post_id => $user_id) {
            // Get all evaluations for this jury post ID
            $jury_evals = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_scores WHERE jury_member_id = %d",
                $jury_post_id
            ));
            
            $report['checked'] += count($jury_evals);
            
            foreach ($jury_evals as $eval) {
                // Check if user already has evaluation for this candidate
                $existing = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table_scores 
                    WHERE candidate_id = %d 
                    AND jury_member_id = %d 
                    AND evaluation_round = %d",
                    $eval->candidate_id,
                    $user_id,
                    $eval->evaluation_round
                ));
                
                if ($existing) {
                    // Duplicate found - compare and decide
                    $jury_time = strtotime($eval->evaluated_at);
                    $user_time = strtotime($existing->evaluated_at);
                    
                    $action = '';
                    if ($jury_time > $user_time) {
                        // Update existing with jury data
                        $wpdb->update(
                            $table_scores,
                            array(
                                'courage_score' => $eval->courage_score,
                                'innovation_score' => $eval->innovation_score,
                                'implementation_score' => $eval->implementation_score,
                                'mobility_relevance_score' => $eval->mobility_relevance_score,
                                'visibility_score' => $eval->visibility_score,
                                'total_score' => $eval->total_score,
                                'comments' => $eval->comments,
                                'evaluated_at' => $eval->evaluated_at
                            ),
                            array('id' => $existing->id)
                        );
                        $action = 'updated_existing';
                    } else {
                        $action = 'kept_existing';
                    }
                    
                    // Delete jury record
                    $wpdb->delete($table_scores, array('id' => $eval->id));
                    $report['duplicates_removed']++;
                    
                    $report['details'][] = array(
                        'candidate_id' => $eval->candidate_id,
                        'jury_post_id' => $jury_post_id,
                        'user_id' => $user_id,
                        'action' => $action,
                        'jury_date' => $eval->evaluated_at,
                        'user_date' => $existing->evaluated_at
                    );
                } else {
                    // No duplicate - convert
                    $result = $wpdb->update(
                        $table_scores,
                        array('jury_member_id' => $user_id),
                        array('id' => $eval->id)
                    );
                    
                    if ($result !== false) {
                        $report['converted']++;
                        $report['details'][] = array(
                            'candidate_id' => $eval->candidate_id,
                            'jury_post_id' => $jury_post_id,
                            'user_id' => $user_id,
                            'action' => 'converted'
                        );
                    } else {
                        $report['errors'][] = array(
                            'eval_id' => $eval->id,
                            'error' => $wpdb->last_error
                        );
                    }
                }
            }
        }
        
        return $report;
    }
}

// Initialize the class
MT_Jury_Consistency::get_instance();