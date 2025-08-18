<?php
/**
 * Script to import candidates from CSV file with evaluation criteria parsing
 * 
 * Usage: php import-new-candidates.php [--dry-run] [--csv=/path/to/file.csv]
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
$csv_file = null;

foreach ($argv as $arg) {
    if (strpos($arg, '--csv=') === 0) {
        $csv_file = substr($arg, 6);
    }
}

// Default CSV file location
if (!$csv_file) {
    $csv_file = dirname(__FILE__) . '/../Photos_candidates/mobility_trailblazers_candidates.csv';
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

echo "========================================\n";
echo "  CANDIDATE IMPORT SCRIPT\n";
echo "========================================\n";
echo "Mode: " . ($dry_run ? "DRY RUN (no changes will be made)" : "LIVE (will import data)") . "\n";
echo "CSV File: $csv_file\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Check if CSV file exists
if (!file_exists($csv_file)) {
    die("CSV file not found: $csv_file\n");
}

// Function to parse evaluation criteria from description
function parse_evaluation_criteria($description) {
    $criteria = [
        'courage' => '',
        'innovation' => '',
        'implementation' => '',
        'relevance' => '',
        'visibility' => ''
    ];
    
    if (empty($description)) {
        return $criteria;
    }
    
    // Parse Mut & Pioniergeist
    if (preg_match('/Mut\s*&\s*Pioniergeist:\s*(.+?)(?=(?:Innovationsgrad:|Umsetzungs|Relevanz|Sichtbarkeit|Vorbildfunktion|$))/isu', $description, $matches)) {
        $criteria['courage'] = trim($matches[1]);
    }
    
    // Parse Innovationsgrad
    if (preg_match('/Innovationsgrad:\s*(.+?)(?=(?:Mut\s*&|Umsetzungs|Relevanz|Sichtbarkeit|Vorbildfunktion|$))/isu', $description, $matches)) {
        $criteria['innovation'] = trim($matches[1]);
    }
    
    // Parse Umsetzungskraft & Wirkung / Umsetzungsstärke
    if (preg_match('/Umsetzungs(?:kraft|stärke)\s*(?:&\s*Wirkung)?:\s*(.+?)(?=(?:Mut\s*&|Innovationsgrad:|Relevanz|Sichtbarkeit|Vorbildfunktion|$))/isu', $description, $matches)) {
        $criteria['implementation'] = trim($matches[1]);
    }
    
    // Parse Relevanz für die Mobilitätswende / Relevanz & Impact
    if (preg_match('/Relevanz\s*(?:für die Mobilitätswende|&\s*Impact)?:\s*(.+?)(?=(?:Mut\s*&|Innovationsgrad:|Umsetzungs|Sichtbarkeit|Vorbildfunktion|$))/isu', $description, $matches)) {
        $criteria['relevance'] = trim($matches[1]);
    }
    
    // Parse Sichtbarkeit & Reichweite / Vorbildfunktion & Sichtbarkeit
    if (preg_match('/(?:Sichtbarkeit\s*&\s*Reichweite|Vorbildfunktion\s*&\s*Sichtbarkeit):\s*(.+?)$/isu', $description, $matches)) {
        $criteria['visibility'] = trim($matches[1]);
    }
    
    return $criteria;
}

// Function to map categories
function map_category($category) {
    $category = trim(strtolower($category));
    
    if (strpos($category, 'startup') !== false || strpos($category, 'start-up') !== false) {
        return 'Startup';
    } elseif (strpos($category, 'gov') !== false || strpos($category, 'verwaltung') !== false || strpos($category, 'governance') !== false) {
        return 'Gov';
    } else {
        return 'Tech'; // Default
    }
}

// Function to normalize URL
function normalize_url($url) {
    $url = trim($url);
    if (empty($url)) {
        return '';
    }
    
    // Add https:// if no protocol
    if (!preg_match('/^https?:\/\//i', $url)) {
        $url = 'https://' . $url;
    }
    
    return esc_url_raw($url);
}

// Read and parse CSV
$handle = fopen($csv_file, 'r');
if (!$handle) {
    die("Failed to open CSV file: $csv_file\n");
}

// Check for BOM and remove if present
$bom = fread($handle, 3);
if ($bom !== "\xEF\xBB\xBF") {
    rewind($handle);
}

// Read headers
$headers = fgetcsv($handle);

// Clean headers (remove BOM, trim whitespace)
$headers = array_map(function($header) {
    $header = str_replace("\xEF\xBB\xBF", '', $header);
    return trim($header);
}, $headers);

echo "CSV Headers: " . implode(', ', $headers) . "\n\n";

// Validate headers
$required_headers = ['Name', 'Organisation', 'Position', 'Description'];
foreach ($required_headers as $required) {
    if (!in_array($required, $headers)) {
        die("Missing required header: $required\n");
    }
}

// Process candidates
$imported = 0;
$skipped = 0;
$errors = 0;
$candidates_data = [];

while (($data = fgetcsv($handle)) !== FALSE) {
    if (count($data) !== count($headers)) {
        echo "⚠️  Skipping row - column count mismatch\n";
        continue;
    }
    
    $candidate = array_combine($headers, $data);
    
    // Skip empty names
    if (empty($candidate['Name'])) {
        continue;
    }
    
    // Parse evaluation criteria from description
    $criteria = parse_evaluation_criteria($candidate['Description'] ?? '');
    
    // Extract overview from description (text before criteria)
    $overview = $candidate['Description'] ?? '';
    if (preg_match('/^(.*?)(?=Mut\s*&\s*Pioniergeist:|Innovationsgrad:|$)/isu', $overview, $matches)) {
        $overview = trim($matches[1]);
        // Remove "Überblick:" prefix if present
        $overview = preg_replace('/^Überblick:\s*/i', '', $overview);
    }
    
    // Prepare candidate data
    $candidate_data = [
        'post_title' => trim($candidate['Name']),
        'post_type' => 'mt_candidate',
        'post_status' => 'publish',
        'post_content' => $overview,
        'meta_input' => [
            '_mt_candidate_id' => $candidate['ID'] ?? '',
            '_mt_organization' => $candidate['Organisation'] ?? '',
            '_mt_position' => $candidate['Position'] ?? '',
            '_mt_linkedin_url' => normalize_url($candidate['LinkedIn-Link'] ?? ''),
            '_mt_website_url' => normalize_url($candidate['Webseite'] ?? ''),
            '_mt_article_url' => normalize_url($candidate['Article about coming of age'] ?? ''),
            '_mt_category_type' => map_category($candidate['Category'] ?? 'Tech'),
            '_mt_top_50_status' => (strtolower($candidate['Status'] ?? '') === 'ja') ? 'yes' : 'no',
            
            // Overview/Biography
            '_mt_description_full' => $overview,
            
            // Evaluation criteria
            '_mt_evaluation_courage' => $criteria['courage'],
            '_mt_evaluation_innovation' => $criteria['innovation'],
            '_mt_evaluation_implementation' => $criteria['implementation'],
            '_mt_evaluation_relevance' => $criteria['relevance'],
            '_mt_evaluation_visibility' => $criteria['visibility'],
        ]
    ];
    
    $candidates_data[] = $candidate_data;
    
    if ($dry_run) {
        echo "[DRY RUN] Would import: {$candidate['Name']}\n";
        echo "  - Organization: {$candidate['Organisation']}\n";
        echo "  - Position: {$candidate['Position']}\n";
        echo "  - Category: " . map_category($candidate['Category'] ?? '') . "\n";
        echo "  - Has evaluation criteria: " . (!empty($criteria['courage']) || !empty($criteria['innovation']) ? 'Yes' : 'No') . "\n";
        $imported++;
    }
}

fclose($handle);

// Import candidates if not dry run
if (!$dry_run) {
    echo "=== Importing Candidates ===\n";
    
    foreach ($candidates_data as $candidate_data) {
        $name = $candidate_data['post_title'];
        
        // Check if candidate already exists
        $existing = get_posts([
            'post_type' => 'mt_candidate',
            'title' => $name,
            'posts_per_page' => 1,
            'post_status' => 'any'
        ]);
        
        if (!empty($existing)) {
            echo "⏭️  Skipped (already exists): $name\n";
            $skipped++;
            continue;
        }
        
        // Insert the candidate
        $post_id = wp_insert_post($candidate_data);
        
        if (is_wp_error($post_id)) {
            echo "❌ Error importing $name: " . $post_id->get_error_message() . "\n";
            $errors++;
        } else {
            echo "✅ Imported: $name (ID: $post_id)\n";
            $imported++;
        }
    }
}

// Summary
echo "\n========================================\n";
echo "  IMPORT SUMMARY\n";
echo "========================================\n";

if ($dry_run) {
    echo "DRY RUN COMPLETE - No changes were made\n";
    echo "Would import: $imported candidates\n";
} else {
    echo "Successfully imported: $imported candidates\n";
    if ($skipped > 0) {
        echo "Skipped (already exists): $skipped candidates\n";
    }
    if ($errors > 0) {
        echo "Errors: $errors candidates\n";
    }
}

// Final count
$total_candidates = get_posts([
    'post_type' => 'mt_candidate',
    'posts_per_page' => -1,
    'post_status' => 'any',
    'fields' => 'ids'
]);

echo "Total candidates in database: " . count($total_candidates) . "\n";
echo "========================================\n";