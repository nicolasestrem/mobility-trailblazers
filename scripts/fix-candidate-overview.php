<?php
/**
 * Script to fix missing overview field for candidates
 * Copies post_content to _mt_description_full meta field
 * 
 * Usage: php fix-candidate-overview.php [--dry-run]
 * 
 * @version 1.0.0
 * @date 2025-01-20
 */

// Check if running in CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Parse command line arguments
$dry_run = in_array('--dry-run', $argv);

// Load WordPress
$wp_load_paths = [
    '/var/www/html/wp-load.php',  // Docker environment
    dirname(__FILE__) . '/../../../../wp-load.php',  // Standard WordPress
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die("Could not load WordPress. Please run this script from the WordPress root directory.\n");
}

echo "========================================\n";
echo "  FIX CANDIDATE OVERVIEW SCRIPT\n";
echo "========================================\n";
echo "Mode: " . ($dry_run ? "DRY RUN (no changes will be made)" : "LIVE (will update data)") . "\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Get all candidates
$candidates = get_posts([
    'post_type' => 'mt_candidate',
    'posts_per_page' => -1,
    'post_status' => 'publish'
]);

echo "Found " . count($candidates) . " candidates\n\n";

$updated = 0;
$already_has = 0;
$no_content = 0;

foreach ($candidates as $candidate) {
    $existing_overview = get_post_meta($candidate->ID, '_mt_description_full', true);
    
    if ($existing_overview) {
        if ($dry_run) {
            echo "[DRY RUN] Already has overview: {$candidate->post_title}\n";
        }
        $already_has++;
        continue;
    }
    
    if (empty($candidate->post_content)) {
        if ($dry_run) {
            echo "[DRY RUN] No content to copy: {$candidate->post_title}\n";
        }
        $no_content++;
        continue;
    }
    
    if ($dry_run) {
        echo "[DRY RUN] Would update: {$candidate->post_title}\n";
        echo "  Content preview: " . substr($candidate->post_content, 0, 100) . "...\n";
        $updated++;
    } else {
        update_post_meta($candidate->ID, '_mt_description_full', $candidate->post_content);
        echo "✅ Updated: {$candidate->post_title}\n";
        $updated++;
    }
}

echo "\n========================================\n";
echo "  SUMMARY\n";
echo "========================================\n";

if ($dry_run) {
    echo "DRY RUN COMPLETE - No changes were made\n";
    echo "Would update: $updated candidates\n";
} else {
    echo "Successfully updated: $updated candidates\n";
}

if ($already_has > 0) {
    echo "Already had overview: $already_has candidates\n";
}
if ($no_content > 0) {
    echo "No content to copy: $no_content candidates\n";
}

echo "========================================\n";