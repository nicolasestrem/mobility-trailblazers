<?php
/**
 * Check Jury Status Script
 * 
 * This script checks the current status of jury posts and helps with migration
 * 
 * Usage: Place in wp-content/ and run via WP-CLI:
 * docker exec mobility_wpcli_STAGING wp eval-file wp-content/check-jury-status.php --path="/var/www/html"
 */

// Ensure we're in WordPress environment
if (!defined('ABSPATH')) {
    die('This script must be run within WordPress environment.');
}

echo "=== JURY STATUS CHECK ===\n\n";

// Check mt_jury posts
$mt_jury_posts = get_posts(array(
    'post_type' => 'mt_jury',
    'posts_per_page' => -1,
    'post_status' => 'any'
));

echo "mt_jury posts found: " . count($mt_jury_posts) . "\n";
if (!empty($mt_jury_posts)) {
    echo "Sample mt_jury posts:\n";
    foreach (array_slice($mt_jury_posts, 0, 3) as $post) {
        echo "  - " . $post->post_title . " (ID: " . $post->ID . ")\n";
    }
    if (count($mt_jury_posts) > 3) {
        echo "  ... and " . (count($mt_jury_posts) - 3) . " more\n";
    }
}

// Check mt_jury_member posts
$mt_jury_member_posts = get_posts(array(
    'post_type' => 'mt_jury_member',
    'posts_per_page' => -1,
    'post_status' => 'any'
));

echo "\nmt_jury_member posts found: " . count($mt_jury_member_posts) . "\n";
if (!empty($mt_jury_member_posts)) {
    echo "Sample mt_jury_member posts:\n";
    foreach (array_slice($mt_jury_member_posts, 0, 3) as $post) {
        echo "  - " . $post->post_title . " (ID: " . $post->ID . ")\n";
    }
    if (count($mt_jury_member_posts) > 3) {
        echo "  ... and " . (count($mt_jury_member_posts) - 3) . " more\n";
    }
}

// Check post types
echo "\n=== POST TYPE STATUS ===\n";
$post_types = array('mt_jury', 'mt_jury_member');
foreach ($post_types as $post_type) {
    $exists = post_type_exists($post_type);
    echo ($exists ? "✓" : "✗") . " $post_type post type: " . ($exists ? "registered" : "not registered") . "\n";
}

// Check admin URLs
echo "\n=== ADMIN URLS ===\n";
echo "mt_jury admin: " . admin_url('edit.php?post_type=mt_jury') . "\n";
echo "mt_jury_member admin: " . admin_url('edit.php?post_type=mt_jury_member') . "\n";

// Recommendations
echo "\n=== RECOMMENDATIONS ===\n";
if (count($mt_jury_posts) > 0 && count($mt_jury_member_posts) == 0) {
    echo "⚠ You have mt_jury posts but no mt_jury_member posts.\n";
    echo "  → Run the migration script: migrate-jury-posts.php\n";
} elseif (count($mt_jury_posts) == 0 && count($mt_jury_member_posts) > 0) {
    echo "✓ Migration appears to be complete!\n";
    echo "  → Check admin at: " . admin_url('edit.php?post_type=mt_jury_member') . "\n";
} elseif (count($mt_jury_posts) > 0 && count($mt_jury_member_posts) > 0) {
    echo "⚠ You have both mt_jury and mt_jury_member posts.\n";
    echo "  → Run the migration script to consolidate them.\n";
} else {
    echo "ℹ No jury posts found.\n";
    echo "  → Run the jury import script: jury-import.php\n";
}

echo "\nStatus check completed!\n";
?> 