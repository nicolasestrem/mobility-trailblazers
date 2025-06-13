<?php
/**
 * Elementor AJAX Error Fixes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Fix for Elementor AJAX saving issues
add_action('init', function() {
    // Only run if Elementor is active
    if (!did_action('elementor/loaded')) {
        return;
    }
    
    // Increase PHP limits for Elementor operations
    if (defined('DOING_AJAX') && DOING_AJAX && isset($_REQUEST['action']) && strpos($_REQUEST['action'], 'elementor') === 0) {
        @ini_set('memory_limit', '256M');
        @ini_set('max_execution_time', '300');
        @ini_set('max_input_vars', '5000');
        @ini_set('max_input_time', '300');
    }
});

// Fix REST API issues with Elementor
add_filter('rest_pre_dispatch', function($result, $server, $request) {
    $route = $request->get_route();
    
    // Check if this is an Elementor REST request
    if (strpos($route, '/elementor/') !== false || strpos($route, '/wp/v2/posts') !== false) {
        // Ensure proper permissions
        if (!current_user_can('edit_posts')) {
            return new WP_Error('rest_forbidden', __('Sorry, you are not allowed to do that.'), array('status' => 403));
        }
    }
    
    return $result;
}, 10, 3);

// Add debugging for AJAX errors
add_action('wp_ajax_elementor_ajax', function() {
    // Log the request for debugging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Elementor AJAX Request: ' . print_r($_REQUEST, true));
    }
}, 1);

// Fix for mod_security issues
add_filter('elementor/document/save/data', function($data) {
    // Clean data that might trigger mod_security
    if (is_array($data)) {
        array_walk_recursive($data, function(&$value) {
            if (is_string($value)) {
                // Remove potentially problematic strings
                $value = str_replace(['<script', '</script', 'javascript:', 'onclick', 'onerror'], '', $value);
            }
        });
    }
    
    return $data;
}, 10);

// Add custom headers to prevent caching issues
add_action('send_headers', function() {
    if (isset($_REQUEST['action']) && strpos($_REQUEST['action'], 'elementor') === 0) {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('X-Elementor-Ajax: true');
    }
});

// Fix JSON encoding issues
add_filter('wp_json_encode_options', function($options, $data, $depth) {
    if (defined('DOING_AJAX') && DOING_AJAX && isset($_REQUEST['action']) && strpos($_REQUEST['action'], 'elementor') === 0) {
        return JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    }
    return $options;
}, 10, 3);

// Add error handling for Elementor saves
add_action('elementor/document/after_save', function($document, $data) {
    // Clear any output buffers that might interfere
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Ensure clean JSON response
    if (defined('DOING_AJAX') && DOING_AJAX) {
        @header('Content-Type: application/json; charset=utf-8');
    }
}, 10, 2);

// Fix for custom widget saving
add_filter('elementor/editor/localize_settings', function($settings) {
    // Ensure our widgets are recognized
    if (isset($settings['initial_document']['elements'])) {
        $mt_widgets = ['mt_jury_dashboard', 'mt_candidate_grid', 'mt_evaluation_stats'];
        
        foreach ($settings['initial_document']['elements'] as &$element) {
            if (isset($element['widgetType']) && in_array($element['widgetType'], $mt_widgets)) {
                // Ensure widget has all required properties
                if (!isset($element['settings'])) {
                    $element['settings'] = new stdClass();
                }
            }
        }
    }
    
    return $settings;
});

// Handle our custom widgets in save process
add_action('elementor/frontend/widget/before_render', function($widget) {
    $widget_name = $widget->get_name();
    $mt_widgets = ['mt_jury_dashboard', 'mt_candidate_grid', 'mt_evaluation_stats'];
    
    if (in_array($widget_name, $mt_widgets)) {
        // Ensure widget data is properly formatted
        $widget->set_settings('_element_id', $widget->get_id());
    }
});

// Debug helper - add to wp-config.php temporarily if needed:
// define('ELEMENTOR_DEBUG', true);