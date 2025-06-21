<?php
/**
 * Migrate Jury Posts Script
 * 
 * This script migrates existing mt_jury posts to mt_jury_member post type
 * 
 * Usage: Place in wp-content/ and run via WP-CLI:
 * docker exec mobility_wpcli_STAGING wp eval-file wp-content/migrate-jury-posts.php --path="/var/www/html"
 */

// Ensure we're in WordPress environment
if (!defined('ABSPATH')) {
    die('This script must be run within WordPress environment.');
}

echo "Starting jury posts migration...\n";

// Get global $wpdb
global $wpdb;

// Find all mt_jury posts
$old_posts = get_posts(array(
    'post_type' => 'mt_jury',
    'posts_per_page' => -1,
    'post_status' => 'any'
));

echo "Found " . count($old_posts) . " mt_jury posts to migrate\n\n";

$migrated_count = 0;
$errors = array();

foreach ($old_posts as $post) {
    try {
        echo "Migrating: " . $post->post_title . " (ID: " . $post->ID . ")\n";
        
        // Create new post with mt_jury_member post type
        $new_post_id = wp_insert_post(array(
            'post_type' => 'mt_jury_member',
            'post_title' => $post->post_title,
            'post_content' => $post->post_content,
            'post_excerpt' => $post->post_excerpt,
            'post_status' => $post->post_status,
            'post_author' => $post->post_author,
            'post_date' => $post->post_date,
            'post_date_gmt' => $post->post_date_gmt,
            'post_modified' => $post->post_modified,
            'post_modified_gmt' => $post->post_modified_gmt
        ));
        
        if (is_wp_error($new_post_id)) {
            throw new Exception('Failed to create new post: ' . $new_post_id->get_error_message());
        }
        
        // Copy all post meta
        $meta_keys = get_post_custom_keys($post->ID);
        if ($meta_keys) {
            foreach ($meta_keys as $meta_key) {
                $meta_values = get_post_meta($post->ID, $meta_key, false);
                foreach ($meta_values as $meta_value) {
                    add_post_meta($new_post_id, $meta_key, $meta_value);
                }
            }
        }
        
        // Copy taxonomies
        $taxonomies = get_object_taxonomies('mt_jury');
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_object_terms($post->ID, $taxonomy, array('fields' => 'slugs'));
            if (!is_wp_error($terms) && !empty($terms)) {
                wp_set_object_terms($new_post_id, $terms, $taxonomy);
            }
        }
        
        echo "  → Created new post (ID: $new_post_id)\n";
        
        // Delete old post
        wp_delete_post($post->ID, true);
        echo "  → Deleted old post (ID: " . $post->ID . ")\n";
        
        $migrated_count++;
        echo "  ✓ Migration completed successfully\n\n";
        
    } catch (Exception $e) {
        $error_msg = "Error migrating " . $post->post_title . ": " . $e->getMessage();
        $errors[] = $error_msg;
        echo "  ✗ " . $error_msg . "\n\n";
    }
}

// Migration summary
echo "=== MIGRATION SUMMARY ===\n";
echo "Total posts processed: " . count($old_posts) . "\n";
echo "Successfully migrated: $migrated_count\n";
echo "Errors: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "\nErrors encountered:\n";
    foreach ($errors as $error) {
        echo "- $error\n";
    }
}

// Verification
echo "\n=== VERIFICATION ===\n";
$old_count_obj = wp_count_posts('mt_jury');
$new_count_obj = wp_count_posts('mt_jury_member');

$old_count = isset($old_count_obj->publish) ? $old_count_obj->publish : 0;
$new_count = isset($new_count_obj->publish) ? $new_count_obj->publish : 0;

echo "Remaining mt_jury posts: $old_count\n";
echo "Total mt_jury_member posts: $new_count\n";

if ($old_count == 0 && $new_count > 0) {
    echo "✓ Migration completed successfully!\n";
} else {
    echo "⚠ Migration may not be complete\n";
}

echo "\nMigration script completed!\n";
?> 