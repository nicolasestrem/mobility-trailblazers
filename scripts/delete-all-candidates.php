<?php
/**
 * Script to safely delete all candidates while preserving jury members
 * 
 * Usage: php delete-all-candidates.php [--dry-run]
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
echo "  CANDIDATE DELETION SCRIPT\n";
echo "========================================\n";
echo "Mode: " . ($dry_run ? "DRY RUN (no changes will be made)" : "LIVE (will delete data)") . "\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Step 1: Get all jury members (to ensure we don't delete them)
$jury_members = get_posts([
    'post_type' => 'mt_jury_member',
    'posts_per_page' => -1,
    'post_status' => 'any',
    'fields' => 'ids'
]);

echo "Protected jury members: " . count($jury_members) . "\n";
echo "IDs: " . implode(', ', $jury_members) . "\n\n";

// Step 2: Get all candidates
$candidates = get_posts([
    'post_type' => 'mt_candidate',
    'posts_per_page' => -1,
    'post_status' => ['publish', 'draft', 'trash', 'auto-draft', 'private', 'pending'],
    'fields' => 'ids'
]);

echo "Found " . count($candidates) . " candidates to delete\n";

// Step 3: Create backup array for logging
$backup_data = [];
foreach ($candidates as $candidate_id) {
    // Safety check - ensure we're not deleting jury members
    if (in_array($candidate_id, $jury_members)) {
        echo "⚠️  WARNING: Skipping ID $candidate_id - it's a jury member!\n";
        continue;
    }
    
    $post = get_post($candidate_id);
    $backup_data[] = [
        'id' => $candidate_id,
        'title' => $post->post_title,
        'status' => $post->post_status,
        'organization' => get_post_meta($candidate_id, '_mt_candidate_organization', true),
        'position' => get_post_meta($candidate_id, '_mt_candidate_position', true),
        'category' => get_post_meta($candidate_id, '_mt_candidate_category', true),
        'top50' => get_post_meta($candidate_id, '_mt_candidate_top50', true),
    ];
}

// Step 4: Log backup data
if (!$dry_run) {
    $backup_file = dirname(__FILE__) . '/backups/candidates_backup_' . date('Y-m-d_H-i-s') . '.json';
    if (!is_dir(dirname($backup_file))) {
        mkdir(dirname($backup_file), 0755, true);
    }
    file_put_contents($backup_file, json_encode($backup_data, JSON_PRETTY_PRINT));
    echo "\nBackup saved to: $backup_file\n";
}

// Step 5: Check for related data
echo "\n=== Checking Related Data ===\n";

// Check evaluations
global $wpdb;
$evaluation_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_evaluations WHERE candidate_id IN (" . implode(',', array_map('intval', $candidates)) . ")");
echo "Related evaluations: $evaluation_count\n";

// Check assignments
$assignment_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_jury_assignments WHERE candidate_id IN (" . implode(',', array_map('intval', $candidates)) . ")");
echo "Related assignments: $assignment_count\n";

// Check scores
$score_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores WHERE candidate_id IN (" . implode(',', array_map('intval', $candidates)) . ")");
echo "Related scores: $score_count\n";

// Check votes
$vote_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes WHERE candidate_id IN (" . implode(',', array_map('intval', $candidates)) . ")");
echo "Related votes: $vote_count\n";

// Step 6: Delete candidates
echo "\n=== Deleting Candidates ===\n";

$deleted_count = 0;
$error_count = 0;

foreach ($candidates as $candidate_id) {
    // Safety check again
    if (in_array($candidate_id, $jury_members)) {
        continue;
    }
    
    $post = get_post($candidate_id);
    $title = $post ? $post->post_title : "Unknown";
    
    if ($dry_run) {
        echo "[DRY RUN] Would delete: $title (ID: $candidate_id)\n";
        $deleted_count++;
    } else {
        // Delete related data first
        // Delete evaluations
        $wpdb->delete("{$wpdb->prefix}mt_evaluations", ['candidate_id' => $candidate_id]);
        
        // Delete assignments
        $wpdb->delete("{$wpdb->prefix}mt_jury_assignments", ['candidate_id' => $candidate_id]);
        
        // Delete scores
        $wpdb->delete("{$wpdb->prefix}mt_candidate_scores", ['candidate_id' => $candidate_id]);
        
        // Delete votes
        $wpdb->delete("{$wpdb->prefix}mt_votes", ['candidate_id' => $candidate_id]);
        
        // Delete the post and all its metadata
        $result = wp_delete_post($candidate_id, true); // true = force delete (skip trash)
        
        if ($result) {
            echo "✅ Deleted: $title (ID: $candidate_id)\n";
            $deleted_count++;
        } else {
            echo "❌ Failed to delete: $title (ID: $candidate_id)\n";
            $error_count++;
        }
    }
}

// Step 7: Clean up orphaned metadata
if (!$dry_run && $deleted_count > 0) {
    echo "\n=== Cleaning Orphaned Metadata ===\n";
    
    // Clean orphaned postmeta
    $orphaned = $wpdb->query("
        DELETE pm FROM {$wpdb->postmeta} pm
        LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE p.ID IS NULL
    ");
    
    echo "Removed $orphaned orphaned postmeta entries\n";
}

// Step 8: Final verification
echo "\n=== Final Verification ===\n";

$remaining_candidates = get_posts([
    'post_type' => 'mt_candidate',
    'posts_per_page' => -1,
    'post_status' => 'any',
    'fields' => 'ids'
]);

$remaining_jury = get_posts([
    'post_type' => 'mt_jury_member',
    'posts_per_page' => -1,
    'post_status' => 'any',
    'fields' => 'ids'
]);

echo "Remaining candidates: " . count($remaining_candidates) . "\n";
echo "Remaining jury members: " . count($remaining_jury) . "\n";

// Summary
echo "\n========================================\n";
echo "  SUMMARY\n";
echo "========================================\n";
if ($dry_run) {
    echo "DRY RUN COMPLETE - No changes were made\n";
    echo "Would have deleted: $deleted_count candidates\n";
} else {
    echo "Successfully deleted: $deleted_count candidates\n";
    if ($error_count > 0) {
        echo "Failed to delete: $error_count candidates\n";
    }
    echo "Preserved: " . count($remaining_jury) . " jury members\n";
}
echo "========================================\n";