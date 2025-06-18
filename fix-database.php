<?php
/**
 * Database Fix Script for Mobility Trailblazers Plugin
 * 
 * This script can be run to fix database issues with the plugin.
 * Run this from the WordPress root directory: php wp-content/plugins/mobility-trailblazers/fix-database.php
 */

// Load WordPress
require_once dirname(__FILE__) . '/../../../wp-load.php';

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('You need to be an administrator to run this script.');
}

echo "=== Mobility Trailblazers Database Fix Script ===\n\n";

// Load plugin files
require_once dirname(__FILE__) . '/includes/class-database.php';
require_once dirname(__FILE__) . '/includes/mt-utility-functions.php';
require_once dirname(__FILE__) . '/includes/mt-debug-functions.php';

// Check current database status
echo "Checking current database status...\n";
$table_status = mt_check_database_tables();

$missing_tables = array();
foreach ($table_status as $table_name => $status) {
    if ($status['exists']) {
        echo "✓ {$table_name} - EXISTS ({$status['columns']} columns)\n";
    } else {
        echo "✗ {$table_name} - MISSING\n";
        $missing_tables[] = $table_name;
    }
}

echo "\n";

if (empty($missing_tables)) {
    echo "All database tables exist. No action needed.\n";
    exit(0);
}

echo "Missing tables detected. Creating them now...\n\n";

// Fix database issues
$results = mt_fix_database_issues();

echo "Database fix completed. Checking results...\n\n";

// Check results
$table_status_after = mt_check_database_tables();
$still_missing = array();

foreach ($table_status_after as $table_name => $status) {
    if ($status['exists']) {
        echo "✓ {$table_name} - CREATED ({$status['columns']} columns)\n";
    } else {
        echo "✗ {$table_name} - STILL MISSING\n";
        $still_missing[] = $table_name;
    }
}

echo "\n";

if (empty($still_missing)) {
    echo "SUCCESS: All database tables have been created successfully!\n";
    echo "The plugin should now work without database errors.\n";
    exit(0);
} else {
    echo "WARNING: Some tables could not be created:\n";
    foreach ($still_missing as $table) {
        echo "- {$table}\n";
    }
    echo "\nPlease check your database permissions and try again.\n";
    exit(1);
} 