<?php
/**
 * Run database upgrade
 */

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

// Load the class
require_once dirname(__FILE__) . '/../includes/core/class-mt-database-upgrade.php';

// Run upgrade
echo "Running database upgrade...\n";
MobilityTrailblazers\Core\MT_Database_Upgrade::run();

// Check if table exists
global $wpdb;
$table = $wpdb->prefix . 'mt_candidates';
$exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));

if ($exists) {
    echo "✅ Candidates table created successfully: $table\n";
    
    // Show structure - safe since we control the table name
    $columns = $wpdb->get_results("SHOW COLUMNS FROM `{$table}`");
    echo "\nTable structure:\n";
    foreach ($columns as $column) {
        echo "  - {$column->Field} ({$column->Type})\n";
    }
} else {
    echo "❌ Failed to create candidates table\n";
}