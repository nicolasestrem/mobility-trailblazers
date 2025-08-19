<?php
/**
 * Dry-run import test
 */

// Check if running in CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
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
    die("Could not load WordPress.\n");
}

// Load required files
require_once dirname(__FILE__) . '/../vendor/autoload.php';
require_once dirname(__FILE__) . '/../includes/repositories/class-mt-candidate-repository.php';
require_once dirname(__FILE__) . '/../includes/services/class-mt-candidate-import-service.php';

// Set up paths
$excel_path = 'E:\OneDrive\CoWorkSpace\Tech Stack\Platform\plugin\mobility-trailblazers\.internal\Kandidatenliste Trailblazers 2025_08_18_List for Nicolas.xlsx';
$photos_dir = 'E:\OneDrive\CoWorkSpace\Tech Stack\Platform\plugin\mobility-trailblazers\.internal\Photos_candidates';

// For Docker environment, try alternative paths if files don't exist
if (!file_exists($excel_path)) {
    // Try mounted path
    $alt_path = '/var/www/html/wp-content/plugins/mobility-trailblazers/.internal/Kandidatenliste Trailblazers 2025_08_18_List for Nicolas.xlsx';
    if (file_exists($alt_path)) {
        $excel_path = $alt_path;
    }
}

if (!is_dir($photos_dir)) {
    // Try mounted path
    $alt_path = '/var/www/html/wp-content/plugins/mobility-trailblazers/.internal/Photos_candidates';
    if (is_dir($alt_path)) {
        $photos_dir = $alt_path;
    }
}

echo "========================================\n";
echo "  MOBILITY TRAILBLAZERS DRY-RUN IMPORT\n";
echo "========================================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Mode: DRY RUN (no changes will be made)\n\n";

echo "Input Files:\n";
echo "Excel: " . (file_exists($excel_path) ? "✅ Found" : "❌ Not found") . "\n";
echo "  Path: $excel_path\n";
echo "Photos: " . (is_dir($photos_dir) ? "✅ Found" : "❌ Not found") . "\n";
echo "  Path: $photos_dir\n\n";

// Check current state
$repository = new \MobilityTrailblazers\Repositories\MT_Candidate_Repository();
$current_count = $repository->count();
echo "Current candidates in database: $current_count\n\n";

// Initialize service
$service = new \MobilityTrailblazers\Services\MT_Candidate_Import_Service();

// Run Excel import (dry-run)
echo "=== PHASE 1: Excel Import (Dry Run) ===\n";
$results = $service->import_from_excel($excel_path, true);

echo "\nExcel Import Results:\n";
echo "  - Would create: {$results['created']} candidates\n";
echo "  - Would update: {$results['updated']} candidates\n";
echo "  - Skipped: {$results['skipped']} rows\n";
echo "  - Errors: {$results['errors']}\n";

// Show sample candidates
if (!empty($results['candidates'])) {
    echo "\nSample Candidates (first 5):\n";
    $shown = 0;
    foreach ($results['candidates'] as $candidate) {
        if ($shown >= 5) break;
        
        echo "\n  " . ($shown + 1) . ". {$candidate['name']}\n";
        echo "     Organization: " . ($candidate['organization'] ?? 'N/A') . "\n";
        
        if (!empty($candidate['sections'])) {
            $populated = 0;
            foreach ($candidate['sections'] as $section) {
                if (!empty($section)) $populated++;
            }
            echo "     German sections: $populated/6 populated\n";
            
            // Show which sections have content
            $section_names = [
                'ueberblick' => 'Überblick',
                'mut_pioniergeist' => 'Mut & Pioniergeist',
                'innovationsgrad' => 'Innovationsgrad',
                'umsetzungskraft_wirkung' => 'Umsetzungskraft & Wirkung',
                'relevanz_mobilitaetswende' => 'Relevanz für die Mobilitätswende',
                'vorbild_sichtbarkeit' => 'Vorbildfunktion & Sichtbarkeit'
            ];
            
            foreach ($candidate['sections'] as $key => $content) {
                if (!empty($content) && isset($section_names[$key])) {
                    $preview = substr($content, 0, 50);
                    if (strlen($content) > 50) $preview .= '...';
                    echo "     - {$section_names[$key]}: \"$preview\"\n";
                }
            }
        }
        
        $shown++;
    }
}

// Show messages
if (!empty($results['messages'])) {
    echo "\nMessages:\n";
    foreach ($results['messages'] as $message) {
        echo "  - $message\n";
    }
}

// Run photo import (dry-run)
echo "\n=== PHASE 2: Photo Import (Dry Run) ===\n";
$photo_results = $service->import_candidate_photos($photos_dir, true);

echo "\nPhoto Import Results:\n";
echo "  - Would attach: {$photo_results['photos_attached']} photos\n";

if (!empty($photo_results['messages'])) {
    echo "\nPhoto Messages (first 10):\n";
    $shown = 0;
    foreach ($photo_results['messages'] as $message) {
        if ($shown >= 10) break;
        echo "  - $message\n";
        $shown++;
    }
    
    if (count($photo_results['messages']) > 10) {
        echo "  ... and " . (count($photo_results['messages']) - 10) . " more messages\n";
    }
}

// Summary
echo "\n========================================\n";
echo "  DRY RUN SUMMARY\n";
echo "========================================\n";
echo "Total candidates to import: {$results['created']}\n";
echo "Total photos to attach: {$photo_results['photos_attached']}\n";
echo "Total errors encountered: " . ($results['errors'] + ($photo_results['errors'] ?? 0)) . "\n";

// Data validation
echo "\n=== DATA VALIDATION ===\n";

// Check if German sections were parsed
$sections_found = false;
foreach ($results['candidates'] as $candidate) {
    if (!empty($candidate['sections']) && count(array_filter($candidate['sections'])) > 0) {
        $sections_found = true;
        break;
    }
}

echo "German sections parsing: " . ($sections_found ? "✅ Working" : "⚠️ No sections found") . "\n";
echo "Excel file readable: " . (!empty($results['candidates']) ? "✅ Yes" : "❌ No") . "\n";
echo "Photo matching: " . ($photo_results['photos_attached'] > 0 ? "✅ Working" : "⚠️ No matches") . "\n";

echo "\n========================================\n";
echo "This was a DRY RUN - no changes were made to the database.\n";
echo "To perform the actual import, run without the dry-run flag.\n";
echo "========================================\n";