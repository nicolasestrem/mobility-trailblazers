<?php
/**
 * Debug database table creation
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

global $wpdb;

echo "Testing database table creation...\n";
echo "Database charset: " . $wpdb->charset . "\n";
echo "Database collate: " . $wpdb->collate . "\n\n";

// Get charset collate
$charset_collate = $wpdb->get_charset_collate();
echo "Charset collate: " . $charset_collate . "\n\n";

// Try to create the table directly
$table_name = $wpdb->prefix . 'mt_candidates';

// First check if table exists
$existing = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
if ($existing) {
    echo "Table already exists: $table_name\n";
    
    // Show structure
    $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
    echo "\nExisting structure:\n";
    foreach ($columns as $column) {
        echo "  - {$column->Field} ({$column->Type})\n";
    }
} else {
    echo "Table does not exist. Creating...\n";
    
    // Create table with simpler structure first
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) DEFAULT NULL,
        slug varchar(255) NOT NULL,
        name varchar(255) NOT NULL,
        organization varchar(255) DEFAULT NULL,
        position varchar(255) DEFAULT NULL,
        country varchar(100) DEFAULT NULL,
        linkedin_url text DEFAULT NULL,
        website_url text DEFAULT NULL,
        article_url text DEFAULT NULL,
        description_sections longtext DEFAULT NULL,
        photo_attachment_id bigint(20) DEFAULT NULL,
        import_id varchar(100) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_slug (slug),
        KEY idx_name (name),
        KEY idx_organization (organization)
    ) $charset_collate";
    
    echo "SQL Query:\n" . $sql . "\n\n";
    
    // Try direct query
    $result = $wpdb->query($sql);
    
    if ($result === false) {
        echo "❌ Failed to create table\n";
        echo "Last error: " . $wpdb->last_error . "\n";
    } else {
        echo "✅ Table created successfully\n";
        
        // Verify
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if ($exists) {
            echo "✅ Table verified: $table_name\n";
            
            // Show structure
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
            echo "\nTable structure:\n";
            foreach ($columns as $column) {
                echo "  - {$column->Field} ({$column->Type})\n";
            }
        }
    }
}

// Also check other MT tables
echo "\n\nOther MT tables:\n";
$tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}mt_%'", ARRAY_N);
foreach ($tables as $table) {
    echo "  - " . $table[0] . "\n";
}