<?php
/**
 * Master script to orchestrate the complete candidate migration process
 * 
 * This script will:
 * 1. Backup current data
 * 2. Delete all existing candidates
 * 3. Import new candidates from CSV
 * 4. Upload and attach photos
 * 
 * Usage: php migrate-candidates.php [--dry-run] [--skip-backup] [--skip-delete] [--skip-import] [--skip-photos]
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
$skip_backup = in_array('--skip-backup', $argv);
$skip_delete = in_array('--skip-delete', $argv);
$skip_import = in_array('--skip-import', $argv);
$skip_photos = in_array('--skip-photos', $argv);

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

echo "╔════════════════════════════════════════╗\n";
echo "║   CANDIDATE MIGRATION MASTER SCRIPT    ║\n";
echo "╚════════════════════════════════════════╝\n";
echo "\n";
echo "Mode: " . ($dry_run ? "🔍 DRY RUN (no changes)" : "⚡ LIVE (will modify data)") . "\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Environment: " . (defined('WP_ENV') ? WP_ENV : 'unknown') . "\n";
echo "Site URL: " . get_site_url() . "\n";
echo "\n";
echo "Steps to execute:\n";
echo ($skip_backup ? "⏭️" : "✅") . " Step 1: Backup current data\n";
echo ($skip_delete ? "⏭️" : "✅") . " Step 2: Delete existing candidates\n";
echo ($skip_import ? "⏭️" : "✅") . " Step 3: Import new candidates\n";
echo ($skip_photos ? "⏭️" : "✅") . " Step 4: Upload photos\n";
echo "\n";

// Confirm execution in live mode
if (!$dry_run) {
    echo "⚠️  WARNING: This will modify your database!\n";
    echo "Press ENTER to continue or Ctrl+C to cancel...";
    fgets(STDIN);
    echo "\n";
}

// Step 1: Backup
if (!$skip_backup) {
    echo "════════════════════════════════════════\n";
    echo "STEP 1: BACKING UP CURRENT DATA\n";
    echo "════════════════════════════════════════\n\n";
    
    // Get current candidates
    $candidates = get_posts([
        'post_type' => 'mt_candidate',
        'posts_per_page' => -1,
        'post_status' => 'any'
    ]);
    
    echo "Found " . count($candidates) . " candidates to backup\n";
    
    if (!$dry_run) {
        $backup_dir = dirname(__FILE__) . '/backups';
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        // Create detailed backup
        $backup_data = [];
        foreach ($candidates as $candidate) {
            $backup_data[] = [
                'id' => $candidate->ID,
                'title' => $candidate->post_title,
                'content' => $candidate->post_content,
                'status' => $candidate->post_status,
                'date' => $candidate->post_date,
                'meta' => get_post_meta($candidate->ID),
                'thumbnail_id' => get_post_thumbnail_id($candidate->ID)
            ];
        }
        
        $backup_file = $backup_dir . '/full_backup_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($backup_file, json_encode($backup_data, JSON_PRETTY_PRINT));
        echo "✅ Full backup saved to: $backup_file\n";
        
        // Also export as CSV for easy viewing
        $csv_file = $backup_dir . '/candidates_list_' . date('Y-m-d_H-i-s') . '.csv';
        $fp = fopen($csv_file, 'w');
        fputcsv($fp, ['ID', 'Name', 'Organization', 'Position', 'Status', 'Has Photo']);
        
        foreach ($candidates as $candidate) {
            fputcsv($fp, [
                $candidate->ID,
                $candidate->post_title,
                get_post_meta($candidate->ID, '_mt_organization', true),
                get_post_meta($candidate->ID, '_mt_position', true),
                $candidate->post_status,
                has_post_thumbnail($candidate->ID) ? 'Yes' : 'No'
            ]);
        }
        fclose($fp);
        echo "✅ CSV backup saved to: $csv_file\n";
    }
    
    echo "\n";
}

// Step 2: Delete candidates
if (!$skip_delete) {
    echo "════════════════════════════════════════\n";
    echo "STEP 2: DELETING EXISTING CANDIDATES\n";
    echo "════════════════════════════════════════\n\n";
    
    $delete_script = dirname(__FILE__) . '/delete-all-candidates.php';
    $cmd = "php \"$delete_script\"" . ($dry_run ? " --dry-run" : "");
    
    echo "Executing: $cmd\n\n";
    passthru($cmd, $return_code);
    
    if ($return_code !== 0) {
        die("\n❌ Error: Delete script failed with code $return_code\n");
    }
    
    echo "\n";
}

// Step 3: Import candidates
if (!$skip_import) {
    echo "════════════════════════════════════════\n";
    echo "STEP 3: IMPORTING NEW CANDIDATES\n";
    echo "════════════════════════════════════════\n\n";
    
    $import_script = dirname(__FILE__) . '/import-new-candidates.php';
    $csv_file = dirname(__FILE__) . '/../Photos_candidates/mobility_trailblazers_candidates.csv';
    $cmd = "php \"$import_script\"" . ($dry_run ? " --dry-run" : "") . " --csv=\"$csv_file\"";
    
    echo "Executing: $cmd\n\n";
    passthru($cmd, $return_code);
    
    if ($return_code !== 0) {
        die("\n❌ Error: Import script failed with code $return_code\n");
    }
    
    echo "\n";
}

// Step 4: Attach existing photos from media library
if (!$skip_photos) {
    echo "════════════════════════════════════════\n";
    echo "STEP 4: ATTACHING EXISTING PHOTOS\n";
    echo "════════════════════════════════════════\n\n";
    
    $photos_script = dirname(__FILE__) . '/attach-existing-photos.php';
    $cmd = "php \"$photos_script\"" . ($dry_run ? " --dry-run" : "");
    
    echo "Executing: $cmd\n\n";
    passthru($cmd, $return_code);
    
    if ($return_code !== 0) {
        die("\n❌ Error: Photo attachment script failed with code $return_code\n");
    }
    
    echo "\n";
}

// Final verification
echo "════════════════════════════════════════\n";
echo "FINAL VERIFICATION\n";
echo "════════════════════════════════════════\n\n";

// Get final counts
$final_candidates = get_posts([
    'post_type' => 'mt_candidate',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'fields' => 'ids'
]);

$final_jury = get_posts([
    'post_type' => 'mt_jury_member',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'fields' => 'ids'
]);

$candidates_with_photos = 0;
foreach ($final_candidates as $id) {
    if (has_post_thumbnail($id)) {
        $candidates_with_photos++;
    }
}

echo "📊 Final Statistics:\n";
echo "├─ Total Candidates: " . count($final_candidates) . "\n";
echo "├─ Candidates with Photos: $candidates_with_photos\n";
echo "├─ Jury Members (preserved): " . count($final_jury) . "\n";
echo "└─ Site URL: " . get_site_url() . "\n";

// Check critical functionality
echo "\n🔍 System Check:\n";

// Check if candidate post type is registered
echo "├─ Candidate post type: " . (post_type_exists('mt_candidate') ? '✅ Registered' : '❌ Not found') . "\n";

// Check if jury post type is registered
echo "├─ Jury post type: " . (post_type_exists('mt_jury_member') ? '✅ Registered' : '❌ Not found') . "\n";

// Check database tables
global $wpdb;
$tables = [
    'mt_evaluations',
    'mt_jury_assignments',
    'mt_candidate_scores',
    'mt_votes'
];

foreach ($tables as $table) {
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}$table'") === "{$wpdb->prefix}$table";
    echo "├─ Table {$wpdb->prefix}$table: " . ($table_exists ? '✅ Exists' : '❌ Missing') . "\n";
}

echo "\n";

if ($dry_run) {
    echo "╔════════════════════════════════════════╗\n";
    echo "║   DRY RUN COMPLETE - NO CHANGES MADE   ║\n";
    echo "╚════════════════════════════════════════╝\n";
} else {
    echo "╔════════════════════════════════════════╗\n";
    echo "║     MIGRATION COMPLETE - SUCCESS!      ║\n";
    echo "╚════════════════════════════════════════╝\n";
    echo "\n";
    echo "Next steps:\n";
    echo "1. ✅ Test the staging site thoroughly\n";
    echo "2. 🌐 Check candidate pages: " . get_site_url() . "/candidate/\n";
    echo "3. 👥 Verify jury dashboard functionality\n";
    echo "4. 📸 Confirm photos are displaying correctly\n";
    echo "5. 🚀 If all good, run this script on production\n";
}

echo "\n";