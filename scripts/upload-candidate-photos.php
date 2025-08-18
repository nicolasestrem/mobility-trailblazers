<?php
/**
 * Script to upload and attach candidate photos from WebP images
 * 
 * Usage: php upload-candidate-photos.php [--dry-run] [--photos-dir=/path/to/photos]
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
$photos_dir = null;

foreach ($argv as $arg) {
    if (strpos($arg, '--photos-dir=') === 0) {
        $photos_dir = substr($arg, 13);
    }
}

// Default photos directory
if (!$photos_dir) {
    $photos_dir = dirname(__FILE__) . '/../Photos_candidates/webp';
}

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

// Load WordPress media functions
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');

echo "========================================\n";
echo "  PHOTO UPLOAD SCRIPT\n";
echo "========================================\n";
echo "Mode: " . ($dry_run ? "DRY RUN (no changes will be made)" : "LIVE (will upload photos)") . "\n";
echo "Photos Directory: $photos_dir\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Check if photos directory exists
if (!is_dir($photos_dir)) {
    die("Photos directory not found: $photos_dir\n");
}

// Name mappings for special cases
$name_mappings = [
    'AlexanderMöller' => 'Alexander Möller',
    'AndréSchwämmlein' => 'André Schwämmlein',
    'AnjesTjarks' => 'Anjes Tjarks',
    'Anna-TheresaKorbutt' => 'Anna-Theresa Korbutt',
    'BenediktMiddendorf' => 'Benedikt Middendorf',
    'BjörnBender' => 'Björn Bender',
    'BorisPalmer' => 'Boris Palmer',
    'CatrinVonCisewski' => 'Catrin von Cisewski',
    'ChristianDahlheim' => 'Dr. Christian Dahlheim',
    'ChristineVonBreitenbuch' => 'Christine von Breitenbuch',
    'ChristophSeyerlein' => 'Christoph Seyerlein',
    'ChristophWeigler' => 'Christoph Weigler',
    'CorsinSulser' => 'Dr. Corsin Sulser',
    'FabianBeste' => 'Fabian Beste',
    'FelixPörnbacher' => 'Felix Pörnbacher',
    'FranzReiner' => 'Franz Reiner',
    'FriedrichDräxlmaier' => 'Friedrich Dräxlmaier',
    'GüntherSchuh' => 'Günther Schuh',
    'HelmutRuhl' => 'Helmut Ruhl',
    'HildegardMüller' => 'Hildegard Müller',
    'HorstGraef' => 'Horst Graef',
    'JanHegner' => 'Dr. Jan Hegner',
    'JoachimFiedler' => 'Joachim Fiedler',
    'JohannesPallasch' => 'Johannes Pallasch',
    'JudithHäberli' => 'Judith Häberli',
    'KarelDijkman' => 'Karel Dijkman',
    'KatharinaKreutzer' => 'Katharina Kreutzer',
    'KevinLöffelbein' => 'Kevin Löffelbein',
    'KlausZellmer' => 'Klaus Zellmer',
    'LukasStranger' => 'Lukas Stranger',
    'LéaMiggiano' => 'Léa Miggiano',
    'ManuelHerzog' => 'Manuel Herzog',
    'MarcSchindler' => 'Marc Schindler',
    'MatthiasBallweg' => 'Matthias Ballweg',
    'MichaelKlasa' => 'Michael Klasa',
    'NicKnapp' => 'Nic Knapp',
    'OlgaNevska' => 'Olga Nevska',
    'OliverBlume' => 'Oliver Blume',
    'OliverMay-Beckmann' => 'Oliver May-Beckmann',
    'OliverZipse' => 'Oliver Zipse',
    'PhilippRaasch' => 'Philipp Raasch',
    'RoyUhlmann' => 'Roy Uhlmann',
    'RönkeVonDerHeide' => 'Rönke von der Heide',
    'SarahFleischer' => 'Sarah Fleischer',
    'SaschaMeyer' => 'Sascha Meyer',
    'SebastianTanzer' => 'Sebastian Tanzer',
    'StephanObwegeser' => 'Stephan Obwegeser',
    'SusannePuello' => 'Susanne Puello',
    'TobiasLiebelt' => 'Tobias Liebelt',
    'UweSchneidewind' => 'Prof. Dr. Uwe Schneidewind',
    'WenHan' => 'Wen Han',
    'WimOuboter' => 'Wim Ouboter',
    'WolframUerlich' => 'Wolfram Uerlich',
    'XanthiDoubara' => 'Xanthi Doubara'
];

// Get all candidates
$candidates = get_posts([
    'post_type' => 'mt_candidate',
    'posts_per_page' => -1,
    'post_status' => 'publish'
]);

echo "Found " . count($candidates) . " candidates\n";

// Create lookup array for faster matching
$candidate_lookup = [];
foreach ($candidates as $candidate) {
    $candidate_lookup[strtolower(str_replace(' ', '', $candidate->post_title))] = $candidate;
    // Also store with original title
    $candidate_lookup[strtolower($candidate->post_title)] = $candidate;
}

// Get all photo files
$photos = glob($photos_dir . '/*.webp');
echo "Found " . count($photos) . " photo files\n\n";

// Process photos
$uploaded = 0;
$skipped = 0;
$not_found = 0;
$errors = 0;

echo "=== Processing Photos ===\n";

foreach ($photos as $photo_path) {
    $filename = basename($photo_path);
    $name_without_ext = pathinfo($filename, PATHINFO_FILENAME);
    
    // Try to find matching candidate
    $candidate_name = isset($name_mappings[$name_without_ext]) 
        ? $name_mappings[$name_without_ext] 
        : $name_without_ext;
    
    // Add spaces before capital letters for CamelCase names
    if (!isset($name_mappings[$name_without_ext])) {
        $candidate_name = preg_replace('/([a-z])([A-Z])/', '$1 $2', $candidate_name);
        $candidate_name = preg_replace('/([A-Z])([A-Z][a-z])/', '$1 $2', $candidate_name);
    }
    
    // Try different matching strategies
    $found_candidate = null;
    
    // Try exact match first
    $lookup_key = strtolower($candidate_name);
    if (isset($candidate_lookup[$lookup_key])) {
        $found_candidate = $candidate_lookup[$lookup_key];
    }
    
    // Try without spaces
    if (!$found_candidate) {
        $lookup_key = strtolower(str_replace(' ', '', $candidate_name));
        if (isset($candidate_lookup[$lookup_key])) {
            $found_candidate = $candidate_lookup[$lookup_key];
        }
    }
    
    // Try partial match
    if (!$found_candidate) {
        foreach ($candidates as $candidate) {
            if (stripos($candidate->post_title, $candidate_name) !== false ||
                stripos($candidate_name, $candidate->post_title) !== false) {
                $found_candidate = $candidate;
                break;
            }
        }
    }
    
    if (!$found_candidate) {
        echo "⚠️  No candidate found for: $filename (tried: $candidate_name)\n";
        $not_found++;
        continue;
    }
    
    // Check if candidate already has a featured image
    if (has_post_thumbnail($found_candidate->ID)) {
        if ($dry_run) {
            echo "[DRY RUN] Would skip (already has photo): {$found_candidate->post_title}\n";
        } else {
            echo "⏭️  Skipped (already has photo): {$found_candidate->post_title}\n";
        }
        $skipped++;
        continue;
    }
    
    if ($dry_run) {
        echo "[DRY RUN] Would upload photo for: {$found_candidate->post_title} (from $filename)\n";
        $uploaded++;
    } else {
        // Upload the image
        $upload_dir = wp_upload_dir();
        $target_filename = sanitize_file_name($found_candidate->post_title . '.webp');
        $target_path = $upload_dir['path'] . '/' . $target_filename;
        
        // Copy file to uploads directory
        if (!copy($photo_path, $target_path)) {
            echo "❌ Failed to copy photo for: {$found_candidate->post_title}\n";
            $errors++;
            continue;
        }
        
        // Create attachment
        $filetype = wp_check_filetype($target_filename, null);
        $attachment = [
            'post_mime_type' => $filetype['type'],
            'post_title'     => $found_candidate->post_title,
            'post_content'   => '',
            'post_status'    => 'inherit'
        ];
        
        // Insert attachment
        $attach_id = wp_insert_attachment($attachment, $target_path, $found_candidate->ID);
        
        if (is_wp_error($attach_id)) {
            echo "❌ Failed to create attachment for: {$found_candidate->post_title}\n";
            $errors++;
            // Clean up copied file
            @unlink($target_path);
            continue;
        }
        
        // Generate attachment metadata
        $attach_data = wp_generate_attachment_metadata($attach_id, $target_path);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        // Set as featured image
        set_post_thumbnail($found_candidate->ID, $attach_id);
        
        // Update candidate meta
        update_post_meta($found_candidate->ID, '_mt_candidate_photo', 'yes');
        
        echo "✅ Uploaded photo for: {$found_candidate->post_title}\n";
        $uploaded++;
    }
}

// Summary
echo "\n========================================\n";
echo "  UPLOAD SUMMARY\n";
echo "========================================\n";

if ($dry_run) {
    echo "DRY RUN COMPLETE - No changes were made\n";
    echo "Would upload: $uploaded photos\n";
    echo "Would skip: $skipped (already have photos)\n";
} else {
    echo "Successfully uploaded: $uploaded photos\n";
    echo "Skipped: $skipped (already have photos)\n";
}

if ($not_found > 0) {
    echo "Not found: $not_found photos without matching candidates\n";
}
if ($errors > 0) {
    echo "Errors: $errors photos\n";
}

// Final verification
$candidates_with_photos = 0;
foreach ($candidates as $candidate) {
    if (has_post_thumbnail($candidate->ID)) {
        $candidates_with_photos++;
    }
}

echo "\nCandidates with photos: $candidates_with_photos / " . count($candidates) . "\n";
echo "========================================\n";