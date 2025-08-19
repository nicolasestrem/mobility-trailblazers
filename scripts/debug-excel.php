<?php
/**
 * Debug Excel file reading
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

// Load PhpSpreadsheet
require_once dirname(__FILE__) . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$excel_path = '/var/www/html/wp-content/plugins/mobility-trailblazers/.internal/Kandidatenliste Trailblazers 2025_08_18_List for Nicolas.xlsx';

echo "Excel Debug\n";
echo "===========\n\n";

if (!file_exists($excel_path)) {
    die("Excel file not found: $excel_path\n");
}

echo "File found: $excel_path\n";
echo "File size: " . filesize($excel_path) . " bytes\n\n";

try {
    // Load the spreadsheet
    echo "Loading spreadsheet...\n";
    $reader = IOFactory::createReaderForFile($excel_path);
    $reader->setReadDataOnly(true);
    $reader->setReadEmptyCells(false);
    $spreadsheet = $reader->load($excel_path);
    
    echo "Spreadsheet loaded successfully\n\n";
    
    // Get worksheet
    $worksheet = $spreadsheet->getActiveSheet();
    echo "Active sheet: " . $worksheet->getTitle() . "\n";
    
    // Get dimensions
    $highestRow = $worksheet->getHighestRow();
    $highestColumn = $worksheet->getHighestColumn();
    echo "Dimensions: $highestColumn x $highestRow\n\n";
    
    // Read first 5 rows to understand structure
    echo "First 5 rows:\n";
    echo "=============\n";
    
    for ($row = 1; $row <= min(5, $highestRow); $row++) {
        echo "\nRow $row:\n";
        $rowData = $worksheet->rangeToArray("A$row:$highestColumn$row", null, true, false)[0];
        
        foreach ($rowData as $col => $value) {
            if (!empty($value)) {
                $preview = substr($value, 0, 50);
                if (strlen($value) > 50) $preview .= '...';
                echo "  Column " . ($col + 1) . ": $preview\n";
            }
        }
    }
    
    // Check headers specifically
    echo "\n\nHeaders (Row 1):\n";
    echo "================\n";
    $headers = $worksheet->rangeToArray("A1:${highestColumn}1", null, true, false)[0];
    foreach ($headers as $index => $header) {
        if (!empty($header)) {
            echo "  Column " . chr(65 + $index) . " (Index $index): $header\n";
        }
    }
    
    // Check a specific candidate row
    echo "\n\nSample Candidate (Row 2):\n";
    echo "=========================\n";
    $sampleRow = $worksheet->rangeToArray("A2:${highestColumn}2", null, true, false)[0];
    foreach ($sampleRow as $index => $value) {
        if (!empty($value) && !empty($headers[$index])) {
            $preview = substr($value, 0, 100);
            if (strlen($value) > 100) $preview .= '...';
            echo "  {$headers[$index]}: $preview\n";
        }
    }
    
} catch (\Exception $e) {
    echo "Error reading Excel file: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}