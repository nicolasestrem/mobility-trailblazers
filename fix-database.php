<?php
/**
 * Database Fix Script for Mobility Trailblazers Plugin
 * 
 * This script can be run to fix database issues with the plugin.
 * Run this from the WordPress root directory: php wp-content/plugins/mobility-trailblazers/fix-database.php
 */

// Load WordPress
require_once dirname(__FILE__) . '/../../../wp-load.php';

// Check if user is admin (if running from CLI, assume admin privileges)
if (php_sapi_name() !== 'cli' && !current_user_can('manage_options')) {
    die('You need to be an administrator to run this script.');
}

echo "=== Mobility Trailblazers Database Fix Script ===\n\n";

// Load plugin files
require_once dirname(__FILE__) . '/includes/class-database.php';
require_once dirname(__FILE__) . '/includes/mt-utility-functions.php';

// Check current database status
echo "Checking current database status...\n";

global $wpdb;
$tables = array(
    'mt_votes' => $wpdb->prefix . 'mt_votes',
    'mt_candidate_scores' => $wpdb->prefix . 'mt_candidate_scores',
    'mt_evaluations' => $wpdb->prefix . 'mt_evaluations',
    'mt_jury_assignments' => $wpdb->prefix . 'mt_jury_assignments',
    'vote_reset_logs' => $wpdb->prefix . 'vote_reset_logs',
    'mt_vote_backups' => $wpdb->prefix . 'mt_vote_backups',
);

$missing_tables = array();
foreach ($tables as $name => $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    if ($exists) {
        $columns = count($wpdb->get_results("SHOW COLUMNS FROM $table"));
        echo "✓ {$name} - EXISTS ({$columns} columns)\n";
    } else {
        echo "✗ {$name} - MISSING\n";
        $missing_tables[] = $name;
    }
}

echo "\n";

if (empty($missing_tables)) {
    echo "All database tables exist. No action needed.\n";
    exit(0);
}

echo "Missing tables detected. Creating them now...\n\n";

// Fix database issues
$database = new MT_Database();
$database->force_create_tables();

echo "Database fix completed. Checking results...\n\n";

// Check results
$all_good = true;
foreach ($tables as $name => $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    if ($exists) {
        $columns = count($wpdb->get_results("SHOW COLUMNS FROM $table"));
        echo "✓ {$name} - CREATED ({$columns} columns)\n";
    } else {
        echo "✗ {$name} - STILL MISSING\n";
        $all_good = false;
    }
}

echo "\n";

if ($all_good) {
    echo "SUCCESS: All database tables have been created successfully!\n";
    echo "The plugin should now work without database errors.\n";
    exit(0);
} else {
    echo "WARNING: Some tables could not be created.\n";
    echo "Please check your database permissions and try again.\n";
    exit(1);
}