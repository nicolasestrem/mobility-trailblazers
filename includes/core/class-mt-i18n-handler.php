<?php
/**
 * Internationalization Handler
 * 
 * Centralizes all JavaScript localization for the plugin
 * 
 * @package MobilityTrailblazers
 * @since 2.5.38
 */

namespace MobilityTrailblazers\Core;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class MT_I18n_Handler {
    
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
        // Hook into script enqueuing
        add_action('admin_enqueue_scripts', [$this, 'localize_admin_scripts'], 100);
        add_action('wp_enqueue_scripts', [$this, 'localize_frontend_scripts'], 100);
    }
    
    /**
     * Localize admin scripts
     */
    public function localize_admin_scripts($hook) {
        // Settings Admin Script
        if (wp_script_is('mt-settings-admin', 'enqueued')) {
            wp_localize_script('mt-settings-admin', 'mt_settings_i18n', $this->get_settings_admin_strings());
        }
        
        // Rich Editor Script
        if (wp_script_is('mt-rich-editor', 'enqueued')) {
            wp_localize_script('mt-rich-editor', 'mt_editor_i18n', $this->get_rich_editor_strings());
        }
        
        // Evaluations Admin Script
        if (wp_script_is('mt-evaluations-admin', 'enqueued')) {
            wp_localize_script('mt-evaluations-admin', 'mt_eval_i18n', $this->get_evaluations_admin_strings());
        }
        
        // Evaluation Details Emergency Fix
        if (wp_script_is('mt-evaluation-details-fix', 'enqueued')) {
            wp_localize_script('mt-evaluation-details-fix', 'mt_eval_details_i18n', $this->get_evaluation_details_strings());
        }
        
        // Assignments Script
        if (wp_script_is('mt-assignments', 'enqueued')) {
            wp_localize_script('mt-assignments', 'mt_assignments_i18n', $this->get_assignments_strings());
        }
        
        // General Admin Script - enhance existing localization
        if (wp_script_is('mt-admin', 'enqueued')) {
            wp_localize_script('mt-admin', 'mt_admin_i18n', $this->get_admin_strings());
        }
    }
    
    /**
     * Localize frontend scripts
     */
    public function localize_frontend_scripts() {
        // Frontend Script
        if (wp_script_is('mt-frontend', 'enqueued')) {
            wp_localize_script('mt-frontend', 'mt_frontend_i18n', $this->get_frontend_strings());
        }
    }
    
    /**
     * Get settings admin strings
     */
    private function get_settings_admin_strings() {
        return [
            'media' => [
                'title' => __('Choose Header Background Image', 'mobility-trailblazers'),
                'button' => __('Use this image', 'mobility-trailblazers'),
                'remove' => __('Remove Image', 'mobility-trailblazers'),
                'preview' => __('Preview Animation', 'mobility-trailblazers')
            ],
            'validation' => [
                'weights' => __('Please enter valid weights between 0 and 10', 'mobility-trailblazers'),
                'data_deletion_warning' => __('WARNING: You have enabled data deletion on uninstall. This will permanently delete all plugin data when the plugin is removed. Are you sure?', 'mobility-trailblazers')
            ]
        ];
    }
    
    /**
     * Get rich editor strings
     */
    private function get_rich_editor_strings() {
        return [
            'toolbar' => [
                'bold' => __('Bold', 'mobility-trailblazers'),
                'italic' => __('Italic', 'mobility-trailblazers'),
                'underline' => __('Underline', 'mobility-trailblazers'),
                'strikethrough' => __('Strikethrough', 'mobility-trailblazers'),
                'ordered_list' => __('Ordered List', 'mobility-trailblazers'),
                'unordered_list' => __('Unordered List', 'mobility-trailblazers'),
                'insert_link' => __('Insert Link', 'mobility-trailblazers'),
                'remove_format' => __('Remove Format', 'mobility-trailblazers'),
                'undo' => __('Undo', 'mobility-trailblazers'),
                'redo' => __('Redo', 'mobility-trailblazers'),
                'headings' => __('Headings', 'mobility-trailblazers')
            ],
            'dropdown' => [
                'normal_text' => __('Normal Text', 'mobility-trailblazers'),
                'heading_1' => __('Heading 1', 'mobility-trailblazers'),
                'heading_2' => __('Heading 2', 'mobility-trailblazers'),
                'heading_3' => __('Heading 3', 'mobility-trailblazers')
            ],
            'prompts' => [
                'enter_url' => __('Enter URL:', 'mobility-trailblazers')
            ],
            'help' => [
                'keyboard_shortcut' => __('Press Ctrl+Enter to submit', 'mobility-trailblazers')
            ]
        ];
    }
    
    /**
     * Get evaluations admin strings
     */
    private function get_evaluations_admin_strings() {
        return [
            'loading' => __('Loading...', 'mobility-trailblazers'),
            'close' => __('Close', 'mobility-trailblazers'),
            'delete' => __('Delete Evaluation', 'mobility-trailblazers'),
            'confirm_delete' => __('Are you sure you want to delete this evaluation?', 'mobility-trailblazers'),
            'confirm_bulk_delete' => __('Are you sure you want to delete the selected evaluations?', 'mobility-trailblazers'),
            'select_action' => __('Please select a bulk action', 'mobility-trailblazers'),
            'select_items' => __('Please select at least one evaluation', 'mobility-trailblazers'),
            'error' => __('An error occurred. Please try again.', 'mobility-trailblazers')
        ];
    }
    
    /**
     * Get evaluation details strings
     */
    private function get_evaluation_details_strings() {
        return [
            'modal' => [
                'title' => __('Evaluation Details', 'mobility-trailblazers'),
                'close' => __('Close', 'mobility-trailblazers'),
                'view_candidate' => __('View Candidate', 'mobility-trailblazers'),
                'delete' => __('Delete Evaluation', 'mobility-trailblazers'),
                'note' => __('Note:', 'mobility-trailblazers'),
                'temp_message' => __('Full evaluation details with individual criteria scores would normally appear here. This is a temporary fix while the full AJAX functionality is being restored.', 'mobility-trailblazers')
            ],
            'confirmations' => [
                'delete_single' => __('Are you sure you want to delete this evaluation? This action cannot be undone.', 'mobility-trailblazers'),
                'delete_multiple' => __('Are you sure you want to delete %s evaluation(s)? This action cannot be undone.', 'mobility-trailblazers'),
                'select_evaluation' => __('Please select at least one evaluation to delete.', 'mobility-trailblazers')
            ],
            'errors' => [
                'token_missing' => __('Security token not found. Please refresh the page and try again.', 'mobility-trailblazers')
            ]
        ];
    }
    
    /**
     * Get assignments strings
     */
    private function get_assignments_strings() {
        return [
            'debug' => [
                'test_distribution' => __('This will run a test distribution simulation. Continue?', 'mobility-trailblazers'),
                'enter_method' => __('Enter distribution method (balanced/random):', 'mobility-trailblazers'),
                'enter_candidates' => __('Enter candidates per jury member:', 'mobility-trailblazers'),
                'test_seed' => __('Distribution test seed:', 'mobility-trailblazers'),
                'check_console' => __('Check console for results after running auto-assignment.', 'mobility-trailblazers'),
                'debug_function' => __('This is a debug function. Continue?', 'mobility-trailblazers'),
                'ajax_success' => __('AJAX Success:', 'mobility-trailblazers'),
                'ajax_error' => __('AJAX Error:', 'mobility-trailblazers')
            ]
        ];
    }
    
    /**
     * Get admin strings
     */
    private function get_admin_strings() {
        return [
            'general' => [
                'yes' => __('Yes', 'mobility-trailblazers'),
                'no' => __('No', 'mobility-trailblazers'),
                'confirm' => __('Are you sure?', 'mobility-trailblazers'),
                'success' => __('Success!', 'mobility-trailblazers'),
                'error' => __('Error', 'mobility-trailblazers'),
                'loading' => __('Loading...', 'mobility-trailblazers'),
                'saving' => __('Saving...', 'mobility-trailblazers'),
                'saved' => __('Saved!', 'mobility-trailblazers')
            ]
        ];
    }
    
    /**
     * Get frontend strings
     */
    private function get_frontend_strings() {
        return [
            'evaluation' => [
                'submitted' => __('Evaluation submitted successfully!', 'mobility-trailblazers'),
                'draft_saved' => __('Draft saved successfully!', 'mobility-trailblazers'),
                'all_complete' => __('Congratulations! You have completed all evaluations!', 'mobility-trailblazers'),
                'rate_all' => __('Please rate all criteria before submitting.', 'mobility-trailblazers'),
                'confirm_submit' => __('Are you sure you want to submit this evaluation?', 'mobility-trailblazers')
            ],
            'errors' => [
                'general' => __('An error occurred. Please try again.', 'mobility-trailblazers'),
                'network' => __('Network error. Please check your connection and try again.', 'mobility-trailblazers'),
                'validation' => __('Please complete all required fields.', 'mobility-trailblazers')
            ],
            'ui' => [
                'loading' => __('Loading...', 'mobility-trailblazers'),
                'processing' => __('Processing...', 'mobility-trailblazers'),
                'please_wait' => __('Please wait...', 'mobility-trailblazers'),
                'submitting' => __('Submitting...', 'mobility-trailblazers'),
                'submit_evaluation' => __('Submit Evaluation', 'mobility-trailblazers'),
                'evaluation_submitted' => __('Thank you for submitting your evaluation!', 'mobility-trailblazers'),
                'evaluation_submitted_status' => __('Evaluation Submitted', 'mobility-trailblazers'),
                'submit_evaluation_btn' => __('Submit Evaluation', 'mobility-trailblazers'),
                'back_to_dashboard' => __('Back to Dashboard', 'mobility-trailblazers'),
                'additional_comments' => __('Additional Comments (Optional)', 'mobility-trailblazers'),
                'characters' => __('characters', 'mobility-trailblazers'),
                'criteria_evaluated' => __('criteria evaluated', 'mobility-trailblazers'),
                'evaluation_submitted_editable' => __('This evaluation has been submitted. You can still edit and resubmit.', 'mobility-trailblazers')
            ]
        ];
    }
}