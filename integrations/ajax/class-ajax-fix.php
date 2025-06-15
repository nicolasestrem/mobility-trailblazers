<?php
/**
 * Mobility Trailblazers - AJAX Evaluation Fix
 * 
 * This file fixes the evaluation form submission issues
 * Add this as a new file: /wp-content/plugins/mobility-trailblazers/includes/class-mt-ajax-fix.php
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Ajax_Fix
 * Handles fixing AJAX evaluation submissions
 */
class MT_Ajax_Fix {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Get instance
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
        // Hook into WordPress
        add_action('init', array($this, 'init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_fix_scripts'), 20);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_fix_scripts'), 20);
        
        // Override the AJAX handler with higher priority
        add_action('wp_ajax_mt_submit_vote', array($this, 'handle_evaluation_submission'), 5);
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Any initialization code here
    }
    
    /**
     * Enqueue fix scripts
     */
    public function enqueue_fix_scripts($hook = '') {
        // Check if we're on a relevant page
        $is_relevant = false;
        
        if (is_admin()) {
            $is_relevant = !empty($_GET['page']) && strpos($_GET['page'], 'mt-') === 0;
        } else {
            $is_relevant = is_page('jury-dashboard') || has_shortcode(get_post()->post_content ?? '', 'mt_jury_dashboard');
        }
        
        if (!$is_relevant) {
            return;
        }
        
        // Add inline script to fix form submission
        wp_add_inline_script('mt-admin-js', $this->get_fix_javascript(), 'after');
        wp_add_inline_script('mt-frontend-js', $this->get_fix_javascript(), 'after');
    }
    
    /**
     * Get fix JavaScript
     */
    private function get_fix_javascript() {
        return "
        // Mobility Trailblazers Evaluation Fix
        (function($) {
            'use strict';
            
            console.log('MT: Loading evaluation form fix');
            
            // Wait for document ready
            $(document).ready(function() {
                
                // Fix evaluation form submission
                $(document).off('submit', '#mt-evaluation-form');
                $(document).on('submit', '#mt-evaluation-form', function(e) {
                    e.preventDefault();
                    
                    var \$form = $(this);
                    var \$submitBtn = \$form.find('button[type=\"submit\"]');
                    
                    // Disable button and show loading
                    \$submitBtn.prop('disabled', true);
                    var originalText = \$submitBtn.text();
                    \$submitBtn.text('Saving...');
                    
                    // Collect all form data
                    var formData = {
                        action: 'mt_submit_vote',
                        nonce: \$('#mt_nonce').val() || (typeof mt_ajax !== 'undefined' ? mt_ajax.nonce : ''),
                        candidate_id: \$form.find('input[name=\"candidate_id\"]').val(),
                        courage_score: \$('#courage_score').val(),
                        innovation_score: \$('#innovation_score').val(),
                        implementation_score: \$('#implementation_score').val(),
                        relevance_score: \$('#relevance_score').val() || \$('#mobility_relevance_score').val(),
                        visibility_score: \$('#visibility_score').val(),
                        comments: \$('#comments').val() || ''
                    };
                    
                    console.log('MT: Submitting evaluation', formData);
                    
                    // Validate scores
                    var isValid = true;
                    var scoreFields = ['courage', 'innovation', 'implementation', 'relevance', 'visibility'];
                    
                    for (var i = 0; i < scoreFields.length; i++) {
                        var score = parseInt(formData[scoreFields[i] + '_score']);
                        if (isNaN(score) || score < 1 || score > 10) {
                            alert('Please set all scores between 1 and 10. Missing: ' + scoreFields[i]);
                            isValid = false;
                            break;
                        }
                    }
                    
                    if (!isValid) {
                        \$submitBtn.prop('disabled', false).text(originalText);
                        return false;
                    }
                    
                    // Submit via AJAX
                    $.ajax({
                        url: ajaxurl || mt_ajax.ajax_url,
                        type: 'POST',
                        data: formData,
                        success: function(response) {
                            console.log('MT: Response received', response);
                            
                            if (response.success) {
                                // Show success message
                                var message = response.data.message || 'Evaluation saved successfully!';
                                
                                // Try different ways to show success
                                if ($('#mt-success-message').length) {
                                    $('#mt-success-message').text(message).fadeIn();
                                } else if ($('.mt-success-message').length) {
                                    $('.mt-success-message').text(message).fadeIn();
                                } else {
                                    // Create success message if it doesn't exist
                                    \$form.before('<div class=\"notice notice-success mt-success-message\" style=\"padding: 10px; margin: 10px 0;\">' + message + '</div>');
                                }
                                
                                // Close modal if exists
                                if ($('#mt-evaluation-modal').length) {
                                    setTimeout(function() {
                                        $('#mt-evaluation-modal').fadeOut();
                                    }, 1000);
                                }
                                
                                // Reload page after short delay
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                                
                            } else {
                                alert('Error: ' + (response.data.message || 'Unknown error occurred'));
                                console.error('MT: Error response', response);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('MT: AJAX Error', {status: status, error: error, xhr: xhr});
                            alert('Network error: ' + error + '. Please check your connection and try again.');
                        },
                        complete: function() {
                            \$submitBtn.prop('disabled', false).text(originalText);
                        }
                    });
                    
                    return false;
                });
                
                // Also fix the score sliders to ensure they update properly
                $('.mt-score-slider').on('input change', function() {
                    var score = $(this).val();
                    var scoreId = $(this).attr('id').replace('_score', '_value');
                    $('#' + scoreId).text(score);
                    
                    // Update total score
                    var total = 0;
                    $('.mt-score-slider').each(function() {
                        total += parseInt($(this).val()) || 0;
                    });
                    $('#mt-total-score').text(total);
                });
                
            });
            
        })(jQuery);
        ";
    }
    
    /**
     * Handle evaluation submission (improved version)
     */
    public function handle_evaluation_submission() {
        // Remove other handlers to prevent conflicts
        remove_all_actions('wp_ajax_mt_submit_vote');
        add_action('wp_ajax_mt_submit_vote', array($this, 'handle_evaluation_submission'), 5);
        
        // Verify nonce
        if (!check_ajax_referer('mt_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed. Please refresh the page and try again.', 'mobility-trailblazers')));
            return;
        }
        
        $candidate_id = intval($_POST['candidate_id'] ?? 0);
        $current_user_id = get_current_user_id();
        
        // Debug logging
        error_log('MT Ajax Fix: Processing evaluation for candidate ' . $candidate_id . ' by user ' . $current_user_id);
        
        // Verify user is logged in
        if (!$current_user_id) {
            wp_send_json_error(array('message' => __('Please log in to submit an evaluation.', 'mobility-trailblazers')));
            return;
        }
        
        // Check if user is jury member (flexible check)
        $is_jury = false;
        $user = wp_get_current_user();
        
        // Check by role
        if (in_array('mt_jury_member', (array) $user->roles) || in_array('administrator', (array) $user->roles)) {
            $is_jury = true;
        }
        
        // Check by jury post
        if (!$is_jury) {
            $jury_posts = get_posts(array(
                'post_type' => 'mt_jury',
                'meta_query' => array(
                    array(
                        'key' => '_mt_jury_user_id',
                        'value' => $current_user_id,
                        'compare' => '='
                    )
                ),
                'posts_per_page' => 1
            ));
            $is_jury = !empty($jury_posts);
        }
        
        if (!$is_jury) {
            wp_send_json_error(array('message' => __('Unauthorized access. You must be a jury member.', 'mobility-trailblazers')));
            return;
        }

        // Validate candidate
        if (!$candidate_id || get_post_type($candidate_id) !== 'mt_candidate') {
            wp_send_json_error(array('message' => __('Invalid candidate selected.', 'mobility-trailblazers')));
            return;
        }

        // Collect and validate scores - handle both field names
        $scores = array(
            'courage_score' => intval($_POST['courage_score'] ?? 0),
            'innovation_score' => intval($_POST['innovation_score'] ?? 0),
            'implementation_score' => intval($_POST['implementation_score'] ?? 0),
            'relevance_score' => intval($_POST['relevance_score'] ?? $_POST['mobility_relevance_score'] ?? 0),
            'visibility_score' => intval($_POST['visibility_score'] ?? 0)
        );

        // Validate all scores are between 1-10
        foreach ($scores as $key => $score) {
            if ($score < 1 || $score > 10) {
                $field_name = str_replace('_', ' ', str_replace('_score', '', $key));
                wp_send_json_error(array('message' => sprintf(__('Invalid %s. Score must be between 1 and 10.', 'mobility-trailblazers'), $field_name)));
                return;
            }
        }

        $total_score = array_sum($scores);
        $comments = sanitize_textarea_field($_POST['comments'] ?? '');

        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';

        // Check if evaluation already exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_scores WHERE candidate_id = %d AND jury_member_id = %d",
            $candidate_id,
            $current_user_id
        ));

        // Prepare data
        $data = array(
            'candidate_id' => $candidate_id,
            'jury_member_id' => $current_user_id,
            'courage_score' => $scores['courage_score'],
            'innovation_score' => $scores['innovation_score'],
            'implementation_score' => $scores['implementation_score'],
            'relevance_score' => $scores['relevance_score'],
            'visibility_score' => $scores['visibility_score'],
            'total_score' => $total_score,
            'comments' => $comments,
            'evaluated_at' => current_time('mysql')
        );

        // Save to database
        if ($existing) {
            $result = $wpdb->update(
                $table_scores,
                $data,
                array('id' => $existing->id),
                array('%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s'),
                array('%d')
            );
            $action = 'updated';
        } else {
            $result = $wpdb->insert(
                $table_scores,
                $data,
                array('%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s')
            );
            $action = 'created';
        }

        if ($result !== false) {
            // Get candidate name for success message
            $candidate_name = get_the_title($candidate_id);
            
            error_log('MT Ajax Fix: Successfully ' . $action . ' evaluation for candidate ' . $candidate_id);
            
            wp_send_json_success(array(
                'message' => sprintf(__('Evaluation for %s saved successfully! Total score: %d/50', 'mobility-trailblazers'), $candidate_name, $total_score),
                'total_score' => $total_score,
                'evaluated' => true,
                'action' => $action
            ));
        } else {
            error_log('MT Ajax Fix: Database error - ' . $wpdb->last_error);
            wp_send_json_error(array(
                'message' => __('Database error. Please try again.', 'mobility-trailblazers'),
                'error' => $wpdb->last_error
            ));
        }
    }
}

// Initialize the fix
MT_Ajax_Fix::get_instance();