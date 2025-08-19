<?php
/**
 * WP-CLI Commands
 *
 * @package MobilityTrailblazers
 * @since 2.5.26
 */

namespace MobilityTrailblazers\CLI;

use MobilityTrailblazers\Services\MT_Candidate_Import_Service;
use WP_CLI;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_CLI_Commands
 *
 * WP-CLI commands for Mobility Trailblazers
 */
class MT_CLI_Commands {
    
    /**
     * Import candidates from Excel file
     *
     * ## OPTIONS
     *
     * [--excel=<path>]
     * : Path to Excel file. Default: .internal/Kandidatenliste Trailblazers 2025_08_18_List for Nicolas.xlsx
     *
     * [--photos=<path>]
     * : Path to photos directory. Default: .internal/Photos_candidates
     *
     * [--dry-run]
     * : Perform a dry run without making changes
     *
     * [--backup]
     * : Create backup before import. Default: true
     *
     * [--delete-existing]
     * : Delete all existing candidates before import
     *
     * ## EXAMPLES
     *
     *     # Dry run with default paths
     *     wp mt import-candidates --dry-run
     *
     *     # Import with custom Excel file
     *     wp mt import-candidates --excel=/path/to/file.xlsx
     *
     *     # Full import with deletion
     *     wp mt import-candidates --delete-existing --backup
     *
     * @when after_wp_load
     */
    public function import_candidates($args, $assoc_args) {
        $dry_run = isset($assoc_args['dry-run']);
        $backup = isset($assoc_args['backup']) ? $assoc_args['backup'] !== 'false' : true;
        $delete_existing = isset($assoc_args['delete-existing']);
        
        // Get paths with defaults
        $plugin_dir = WP_CONTENT_DIR . '/plugins/mobility-trailblazers';
        $excel_path = $assoc_args['excel'] ?? $plugin_dir . '/.internal/Kandidatenliste Trailblazers 2025_08_18_List for Nicolas.xlsx';
        $photos_dir = $assoc_args['photos'] ?? $plugin_dir . '/.internal/Photos_candidates';
        
        // Normalize paths for Windows
        if (DIRECTORY_SEPARATOR === '\\') {
            $excel_path = str_replace('/', '\\', $excel_path);
            $photos_dir = str_replace('/', '\\', $photos_dir);
            
            // Handle absolute Windows paths
            if (!file_exists($excel_path) && strpos($excel_path, 'internal') !== false) {
                $excel_path = 'E:\OneDrive\CoWorkSpace\Tech Stack\Platform\plugin\mobility-trailblazers\.internal\Kandidatenliste Trailblazers 2025_08_18_List for Nicolas.xlsx';
            }
            if (!is_dir($photos_dir) && strpos($photos_dir, 'internal') !== false) {
                $photos_dir = 'E:\OneDrive\CoWorkSpace\Tech Stack\Platform\plugin\mobility-trailblazers\.internal\Photos_candidates';
            }
        }
        
        WP_CLI::log('=== Mobility Trailblazers Candidate Import ===');
        WP_CLI::log('Mode: ' . ($dry_run ? 'DRY RUN' : 'LIVE'));
        WP_CLI::log('Excel file: ' . $excel_path);
        WP_CLI::log('Photos directory: ' . $photos_dir);
        WP_CLI::log('');
        
        $service = new MT_Candidate_Import_Service();
        
        // Create backup if requested
        if ($backup && !$dry_run) {
            WP_CLI::log('Creating backup...');
            $backup_file = $service->backup_existing_candidates();
            
            if ($backup_file) {
                WP_CLI::success('Backup created: ' . basename($backup_file));
            } else {
                WP_CLI::log('No existing candidates to backup');
            }
        }
        
        // Delete existing if requested
        if ($delete_existing && !$dry_run) {
            WP_CLI::log('Deleting existing candidates...');
            
            if ($service->truncate_candidate_data()) {
                WP_CLI::success('All existing candidates deleted');
            } else {
                WP_CLI::error('Failed to delete existing candidates');
            }
        } elseif ($delete_existing && $dry_run) {
            WP_CLI::log('[DRY RUN] Would delete all existing candidates');
        }
        
        // Import from Excel
        WP_CLI::log('Importing candidates from Excel...');
        $results = $service->import_from_excel($excel_path, $dry_run);
        
        // Display Excel import results
        $this->display_results($results, 'Excel Import');
        
        // Import photos
        WP_CLI::log('');
        WP_CLI::log('Importing candidate photos...');
        $photo_results = $service->import_candidate_photos($photos_dir, $dry_run);
        
        // Display photo import results
        $this->display_results($photo_results, 'Photo Import');
        
        // Final summary
        WP_CLI::log('');
        WP_CLI::log('=== Import Summary ===');
        WP_CLI::log('Candidates created: ' . $results['created']);
        WP_CLI::log('Candidates updated: ' . $results['updated']);
        WP_CLI::log('Candidates skipped: ' . $results['skipped']);
        WP_CLI::log('Photos attached: ' . $photo_results['photos_attached']);
        WP_CLI::log('Errors: ' . ($results['errors'] + ($photo_results['errors'] ?? 0)));
        
        if ($dry_run) {
            WP_CLI::warning('This was a dry run. No changes were made to the database.');
        } else {
            WP_CLI::success('Import completed successfully!');
        }
    }
    
    /**
     * Display import results
     *
     * @param array $results Import results
     * @param string $title Section title
     */
    private function display_results($results, $title) {
        if (!empty($results['messages'])) {
            WP_CLI::log('');
            WP_CLI::log("$title Messages:");
            foreach ($results['messages'] as $message) {
                WP_CLI::log('  - ' . $message);
            }
        }
        
        if (!empty($results['candidates']) && count($results['candidates']) <= 10) {
            WP_CLI::log('');
            WP_CLI::log("$title Details:");
            
            $table_data = [];
            foreach ($results['candidates'] as $candidate) {
                $table_data[] = [
                    'Action' => $candidate['action'],
                    'Name' => $candidate['name'],
                    'Organization' => $candidate['organization'] ?? '',
                    'Sections' => implode(', ', array_filter($candidate['sections'] ?? []))
                ];
            }
            
            \WP_CLI\Utils\format_items('table', $table_data, ['Action', 'Name', 'Organization', 'Sections']);
        }
    }
    
    /**
     * Show database upgrade status
     *
     * ## EXAMPLES
     *
     *     wp mt db-upgrade
     *
     * @when after_wp_load
     */
    public function db_upgrade($args, $assoc_args) {
        WP_CLI::log('Running database upgrade...');
        
        require_once plugin_dir_path(__FILE__) . '../core/class-mt-database-upgrade.php';
        \MobilityTrailblazers\Core\MT_Database_Upgrade::run();
        
        global $wpdb;
        $table = $wpdb->prefix . 'mt_candidates';
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        
        if ($exists) {
            WP_CLI::success("Candidates table created successfully: $table");
            
            // Show table structure
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $table");
            $column_data = [];
            foreach ($columns as $column) {
                $column_data[] = [
                    'Field' => $column->Field,
                    'Type' => $column->Type,
                    'Null' => $column->Null,
                    'Key' => $column->Key
                ];
            }
            
            WP_CLI::log('');
            WP_CLI::log('Table Structure:');
            \WP_CLI\Utils\format_items('table', $column_data, ['Field', 'Type', 'Null', 'Key']);
        } else {
            WP_CLI::error("Failed to create candidates table");
        }
    }
    
    /**
     * List all candidates
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Output format. Default: table
     *
     * ## EXAMPLES
     *
     *     wp mt list-candidates
     *     wp mt list-candidates --format=json
     *
     * @when after_wp_load
     */
    public function list_candidates($args, $assoc_args) {
        $format = $assoc_args['format'] ?? 'table';
        
        $repository = new \MobilityTrailblazers\Repositories\MT_Candidate_Repository();
        $candidates = $repository->find_all();
        
        if (empty($candidates)) {
            WP_CLI::log('No candidates found.');
            return;
        }
        
        $data = [];
        foreach ($candidates as $candidate) {
            $sections = $candidate->description_sections ?? [];
            $section_count = 0;
            foreach ($sections as $content) {
                if (!empty($content)) {
                    $section_count++;
                }
            }
            
            $data[] = [
                'ID' => $candidate->id,
                'Name' => $candidate->name,
                'Organization' => $candidate->organization,
                'Position' => $candidate->position,
                'Country' => $candidate->country,
                'Sections' => $section_count . '/6',
                'Has Photo' => $candidate->photo_attachment_id ? 'Yes' : 'No'
            ];
        }
        
        \WP_CLI\Utils\format_items($format, $data, ['ID', 'Name', 'Organization', 'Position', 'Country', 'Sections', 'Has Photo']);
        
        WP_CLI::log('');
        WP_CLI::success('Total candidates: ' . count($candidates));
    }
}
