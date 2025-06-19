<?php
/**
 * Elementor REST API Fix
 * Ensures Elementor can access REST API endpoints
 */

// Priority fix for Elementor REST API access
add_action("init", function() {
    // Only apply fixes when Elementor is active
    if (!did_action("elementor/loaded")) {
        return;
    }
    
    // Check if we are in Elementor context
    $is_elementor = false;
    
    // Check various Elementor contexts
    if (isset($_GET["action"]) && $_GET["action"] === "elementor") {
        $is_elementor = true;
    }
    
    if (isset($_GET["elementor-preview"])) {
        $is_elementor = true;
    }
    
    if (defined("REST_REQUEST") && REST_REQUEST) {
        $request_uri = $_SERVER["REQUEST_URI"] ?? "";
        if (strpos($request_uri, "/elementor/") !== false) {
            $is_elementor = true;
        }
    }
    
    // If in Elementor context, ensure REST API access
    if ($is_elementor) {
        // Remove all REST API filters
        remove_all_filters("rest_authentication_errors");
        remove_all_filters("rest_pre_dispatch", 10);
        
        // Add a permissive filter for logged-in users
        add_filter("rest_authentication_errors", function($result) {
            if (is_user_logged_in()) {
                return true;
            }
            return $result;
        }, 999);
    }
}, 0);

// Ensure Elementor routes are never blocked
add_filter("rest_pre_dispatch", function($result, $server, $request) {
    if (!is_wp_error($result)) {
        return $result;
    }
    
    $route = $request->get_route();
    if (empty($route)) {
        return $result;
    }
    
    // Check if this is an Elementor route
    $elementor_routes = array(
        "/elementor/",
        "/wp/v2/blocks",
        "/wp/v2/global-styles",
        "/wp/v2/types",
        "/wp/v2/taxonomies",
    );
    
    foreach ($elementor_routes as $pattern) {
        if (strpos($route, $pattern) !== false) {
            // If user is logged in and can edit, allow access
            if (is_user_logged_in() && current_user_can("edit_posts")) {
                return null; // Allow access
            }
        }
    }
    
    return $result;
}, 5, 3);