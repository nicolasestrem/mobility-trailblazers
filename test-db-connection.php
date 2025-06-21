<?php
/**
 * Test Database Connection Script
 * 
 * This script tests the database connection and checks if required tables exist
 * 
 * Usage: Place in wp-content/ and run via WP-CLI:
 * docker exec mobility_wpcli_STAGING wp eval-file wp-content/test-db-connection.php --path="/var/www/html"
 */

// Ensure we're in WordPress environment
if (!defined('ABSPATH')) {
    die('This script must be run within WordPress environment.');
}

// Get global $wpdb
global $wpdb;

echo "=== DATABASE CONNECTION TEST ===\n";

// Test basic connection
try {
    $result = $wpdb->get_var("SELECT 1");
    if ($result == 1) {
        echo "✓ Database connection successful\n";
    } else {
        echo "✗ Database connection failed\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ Database connection error: " . $e->getMessage() . "\n";
    exit(1);
}

// Check WordPress tables
$wp_tables = array(
    $wpdb->prefix . 'posts',
    $wpdb->prefix . 'users',
    $wpdb->prefix . 'usermeta',
    $wpdb->prefix . 'postmeta',
    $wpdb->prefix . 'terms',
    $wpdb->prefix . 'term_taxonomy',
    $wpdb->prefix . 'term_relationships'
);

echo "\n=== WORDPRESS TABLES ===\n";
foreach ($wp_tables as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
    echo ($exists ? "✓" : "✗") . " $table\n";
}

// Check Mobility Trailblazers tables
$mt_tables = array(
    $wpdb->prefix . 'mt_assignments',
    $wpdb->prefix . 'mt_evaluations'
);

echo "\n=== MOBILITY TRAILBLAZERS TABLES ===\n";
foreach ($mt_tables as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
    echo ($exists ? "✓" : "✗") . " $table\n";
}

// Check post types
echo "\n=== POST TYPES ===\n";
$post_types = array('mt_candidate', 'mt_jury');
foreach ($post_types as $post_type) {
    $exists = post_type_exists($post_type);
    echo ($exists ? "✓" : "✗") . " $post_type\n";
}

// Check taxonomies
echo "\n=== TAXONOMIES ===\n";
$taxonomies = array('mt_category', 'mt_status', 'mt_award_year');
foreach ($taxonomies as $taxonomy) {
    $exists = taxonomy_exists($taxonomy);
    echo ($exists ? "✓" : "✗") . " $taxonomy\n";
}

// Check roles
echo "\n=== ROLES ===\n";
$roles = array('mt_jury_member', 'administrator');
foreach ($roles as $role) {
    $exists = get_role($role) !== null;
    echo ($exists ? "✓" : "✗") . " $role\n";
}

echo "\n=== TEST COMPLETED ===\n";
?> 