<?php
/**
 * Script to fix meta field name mismatches between templates
 * Maps evaluation fields to the correct names expected by templates
 * 
 * Usage: php fix-meta-field-names.php [--dry-run]
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
echo "  FIX META FIELD NAMES SCRIPT\n";
echo "========================================\n";
echo "Mode: " . ($dry_run ? "DRY RUN (no changes will be made)" : "LIVE (will update data)") . "\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Define field mappings
$field_mappings = [
    // Overview fields
    '_mt_description_full' => '_mt_overview',
    
    // Evaluation criteria - enhanced template uses _mt_criterion_*
    '_mt_evaluation_courage' => '_mt_criterion_courage',
    '_mt_evaluation_innovation' => '_mt_criterion_innovation',
    '_mt_evaluation_implementation' => '_mt_criterion_implementation',
    '_mt_evaluation_relevance' => '_mt_criterion_relevance',
    '_mt_evaluation_visibility' => '_mt_criterion_visibility',
    
    // URL fields - enhanced template uses shorter names
    '_mt_linkedin_url' => '_mt_linkedin',
    '_mt_website_url' => '_mt_website',
];

// Get all candidates
$candidates = get_posts([
    'post_type' => 'mt_candidate',
    'posts_per_page' => -1,
    'post_status' => 'publish'
]);

echo "Found " . count($candidates) . " candidates\n\n";

$updated_count = 0;
$field_updates = [];

foreach ($candidates as $candidate) {
    $updates_for_candidate = [];
    
    foreach ($field_mappings as $old_key => $new_key) {
        // Get value from old field
        $value = get_post_meta($candidate->ID, $old_key, true);
        
        if ($value) {
            // Check if new field already exists
            $existing_new = get_post_meta($candidate->ID, $new_key, true);
            
            if (!$existing_new) {
                if ($dry_run) {
                    $updates_for_candidate[] = "  $old_key → $new_key";
                } else {
                    // Copy to new field name
                    update_post_meta($candidate->ID, $new_key, $value);
                    $updates_for_candidate[] = "  $old_key → $new_key";
                }
                
                if (!isset($field_updates[$old_key])) {
                    $field_updates[$old_key] = 0;
                }
                $field_updates[$old_key]++;
            }
        }
    }
    
    if (!empty($updates_for_candidate)) {
        if ($dry_run) {
            echo "[DRY RUN] Would update: {$candidate->post_title}\n";
        } else {
            echo "✅ Updated: {$candidate->post_title}\n";
        }
        foreach ($updates_for_candidate as $update) {
            echo $update . "\n";
        }
        echo "\n";
        $updated_count++;
    }
}

// Also ensure the evaluation criteria are properly formatted in post_content
echo "=== Checking post_content for evaluation criteria ===\n";

$content_updated = 0;
foreach ($candidates as $candidate) {
    // Skip if no content
    if (empty($candidate->post_content)) {
        continue;
    }
    
    // Check if content contains evaluation criteria text
    if (strpos($candidate->post_content, 'Mut & Pioniergeist:') !== false ||
        strpos($candidate->post_content, 'Innovationsgrad:') !== false) {
        
        // This content includes evaluation criteria - let's extract and save them separately
        $content = $candidate->post_content;
        
        // Extract overview (text before criteria)
        if (preg_match('/^(.*?)(?=Mut\s*&\s*Pioniergeist:|Innovationsgrad:|$)/isu', $content, $matches)) {
            $overview_text = trim($matches[1]);
            
            if ($overview_text && !get_post_meta($candidate->ID, '_mt_overview', true)) {
                if ($dry_run) {
                    echo "[DRY RUN] Would set overview for: {$candidate->post_title}\n";
                } else {
                    update_post_meta($candidate->ID, '_mt_overview', $overview_text);
                    echo "✅ Set overview for: {$candidate->post_title}\n";
                }
                $content_updated++;
            }
        }
    }
}

echo "\n========================================\n";
echo "  SUMMARY\n";
echo "========================================\n";

if ($dry_run) {
    echo "DRY RUN COMPLETE - No changes were made\n";
    echo "Would update: $updated_count candidates\n";
} else {
    echo "Successfully updated: $updated_count candidates\n";
}

if ($content_updated > 0) {
    echo "Overview extracted from content: $content_updated candidates\n";
}

echo "\nField update statistics:\n";
foreach ($field_updates as $field => $count) {
    echo "  $field: $count updates\n";
}

echo "========================================\n";