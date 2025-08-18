<?php
/**
 * Script to attach existing media library photos to candidates
 * 
 * Usage: php attach-existing-photos.php [--dry-run]
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
echo "  ATTACH EXISTING PHOTOS SCRIPT\n";
echo "========================================\n";
echo "Mode: " . ($dry_run ? "DRY RUN (no changes will be made)" : "LIVE (will attach photos)") . "\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Get all candidates
$candidates = get_posts([
    'post_type' => 'mt_candidate',
    'posts_per_page' => -1,
    'post_status' => 'publish'
]);

echo "Found " . count($candidates) . " candidates\n";

// Get all image attachments
$attachments = get_posts([
    'post_type' => 'attachment',
    'posts_per_page' => -1,
    'post_status' => 'inherit',
    'post_mime_type' => 'image'
]);

echo "Found " . count($attachments) . " images in media library\n\n";

// Create lookup arrays for matching
$attachment_lookup = [];
foreach ($attachments as $attachment) {
    // Store by title
    $normalized_title = strtolower(trim($attachment->post_title));
    $attachment_lookup[$normalized_title] = $attachment->ID;
    
    // Also store without spaces
    $no_spaces = str_replace(' ', '', $normalized_title);
    $attachment_lookup[$no_spaces] = $attachment->ID;
    
    // Store without special characters
    $clean_title = preg_replace('/[^a-z0-9]/', '', $normalized_title);
    $attachment_lookup[$clean_title] = $attachment->ID;
}

// Special name mappings (if needed)
$special_mappings = [
    'Dr. Christian Dahlheim' => 'Christian Dahlheim',
    'Dr. Corsin Sulser' => 'Corsin Sulser',
    'Dr. Jan Hegner' => 'Jan Hegner',
    'Prof. Dr. Uwe Schneidewind' => 'Uwe Schneidewind',
    'Susanne Püllo' => 'Susanne Puello',
];

// Process candidates
$attached = 0;
$already_has = 0;
$not_found = 0;
$errors = 0;

echo "=== Matching Photos to Candidates ===\n";

foreach ($candidates as $candidate) {
    // Check if already has featured image
    if (has_post_thumbnail($candidate->ID)) {
        if ($dry_run) {
            echo "[DRY RUN] Already has photo: {$candidate->post_title}\n";
        } else {
            echo "⏭️  Already has photo: {$candidate->post_title}\n";
        }
        $already_has++;
        continue;
    }
    
    // Try to find matching attachment
    $found_attachment_id = null;
    $candidate_name = $candidate->post_title;
    
    // Try exact match first
    $normalized_name = strtolower(trim($candidate_name));
    if (isset($attachment_lookup[$normalized_name])) {
        $found_attachment_id = $attachment_lookup[$normalized_name];
    }
    
    // Try without spaces
    if (!$found_attachment_id) {
        $no_spaces = str_replace(' ', '', $normalized_name);
        if (isset($attachment_lookup[$no_spaces])) {
            $found_attachment_id = $attachment_lookup[$no_spaces];
        }
    }
    
    // Try special mappings
    if (!$found_attachment_id && isset($special_mappings[$candidate_name])) {
        $mapped_name = strtolower($special_mappings[$candidate_name]);
        if (isset($attachment_lookup[$mapped_name])) {
            $found_attachment_id = $attachment_lookup[$mapped_name];
        }
    }
    
    // Try without Dr./Prof. prefix
    if (!$found_attachment_id) {
        $clean_name = preg_replace('/^(Dr\.|Prof\. Dr\.|Prof\.)\s+/i', '', $candidate_name);
        $clean_name = strtolower(trim($clean_name));
        if (isset($attachment_lookup[$clean_name])) {
            $found_attachment_id = $attachment_lookup[$clean_name];
        }
    }
    
    // Try fuzzy matching - search through all attachments
    if (!$found_attachment_id) {
        foreach ($attachments as $attachment) {
            // Check if attachment title contains candidate name or vice versa
            if (stripos($attachment->post_title, $candidate_name) !== false ||
                stripos($candidate_name, $attachment->post_title) !== false) {
                $found_attachment_id = $attachment->ID;
                break;
            }
            
            // Check without special characters
            $clean_candidate = preg_replace('/[^a-z0-9]/', '', strtolower($candidate_name));
            $clean_attachment = preg_replace('/[^a-z0-9]/', '', strtolower($attachment->post_title));
            if ($clean_candidate === $clean_attachment) {
                $found_attachment_id = $attachment->ID;
                break;
            }
        }
    }
    
    if (!$found_attachment_id) {
        echo "⚠️  No photo found for: {$candidate->post_title}\n";
        $not_found++;
        continue;
    }
    
    if ($dry_run) {
        $attachment = get_post($found_attachment_id);
        echo "[DRY RUN] Would attach photo '{$attachment->post_title}' (ID: $found_attachment_id) to: {$candidate->post_title}\n";
        $attached++;
    } else {
        // Attach the photo as featured image
        $result = set_post_thumbnail($candidate->ID, $found_attachment_id);
        
        if ($result) {
            $attachment = get_post($found_attachment_id);
            echo "✅ Attached photo '{$attachment->post_title}' to: {$candidate->post_title}\n";
            
            // Update candidate meta
            update_post_meta($candidate->ID, '_mt_candidate_photo', 'yes');
            $attached++;
        } else {
            echo "❌ Failed to attach photo to: {$candidate->post_title}\n";
            $errors++;
        }
    }
}

// Summary
echo "\n========================================\n";
echo "  ATTACHMENT SUMMARY\n";
echo "========================================\n";

if ($dry_run) {
    echo "DRY RUN COMPLETE - No changes were made\n";
    echo "Would attach: $attached photos\n";
} else {
    echo "Successfully attached: $attached photos\n";
}

echo "Already have photos: $already_has candidates\n";

if ($not_found > 0) {
    echo "No photo found: $not_found candidates\n";
}
if ($errors > 0) {
    echo "Errors: $errors attachments\n";
}

// Final verification
$candidates_with_photos = 0;
foreach ($candidates as $candidate) {
    if (has_post_thumbnail($candidate->ID)) {
        $candidates_with_photos++;
    }
}

echo "\nFinal status: $candidates_with_photos / " . count($candidates) . " candidates have photos\n";

// List candidates without photos for debugging
if ($not_found > 0) {
    echo "\nCandidates without matching photos:\n";
    foreach ($candidates as $candidate) {
        if (!has_post_thumbnail($candidate->ID)) {
            echo "  - {$candidate->post_title}\n";
        }
    }
}

echo "========================================\n";