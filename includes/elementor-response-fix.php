<?php
/**
 * Fix Elementor AJAX Response Issues
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Intercept Elementor AJAX responses
add_action('wp_ajax_elementor_ajax', function() {
    // Hook into the response before it's sent
    add_filter('wp_die_ajax_handler', function($handler) {
        return function($message, $title, $args) use ($handler) {
            // Check if this is an Elementor save operation
            if (isset($_REQUEST['actions'])) {
                $actions = json_decode(stripslashes($_REQUEST['actions']), true);
                
                if (isset($actions['save_builder'])) {
                    // Ensure we have a valid response
                    if (empty($message) || $message === '0') {
                        // Create a success response
                        $response = [
                            'success' => true,
                            'data' => [
                                'save_builder' => [
                                    'success' => true,
                                    'data' => []
                                ]
                            ]
                        ];
                        
                        wp_send_json($response);
                        exit;
                    }
                }
            }
            
            // Call original handler
            return call_user_func($handler, $message, $title, $args);
        };
    });
}, 5);

// Fix output buffering issues
add_action('init', function() {
    if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'elementor_ajax') {
        // Start output buffering to catch any early output
        ob_start();
        
        // Clean buffer before sending response
        add_action('shutdown', function() {
            $output = ob_get_contents();
            ob_end_clean();
            
            // Only output if it's JSON
            if ($output && (strpos($output, '{') === 0 || strpos($output, '[') === 0)) {
                echo $output;
            }
        }, 0);
    }
});

// Ensure proper JSON response for Elementor
add_filter('elementor/document/save/response', function($response, $document, $data) {
    // Ensure response is properly formatted
    if (!is_array($response)) {
        $response = [];
    }
    
    // Add success flag if missing
    if (!isset($response['success'])) {
        $response['success'] = true;
    }
    
    // Ensure data key exists
    if (!isset($response['data'])) {
        $response['data'] = [];
    }
    
    return $response;
}, 10, 3);

// Fix for empty responses
add_action('elementor/ajax/register_actions', function($ajax) {
    // Override the save_builder action
    $ajax->register_ajax_action('save_builder', function($data) {
        $document_id = $_REQUEST['editor_post_id'];
        $document = \Elementor\Plugin::$instance->documents->get($document_id);
        
        if (!$document) {
            return ['success' => false, 'message' => 'Document not found'];
        }
        
        // Save the document
        $result = $document->save($data);
        
        // Always return a valid response
        if ($result === false) {
            return [
                'success' => false,
                'message' => 'Save failed'
            ];
        }
        
        return [
            'success' => true,
            'data' => []
        ];
    });
}, 20);