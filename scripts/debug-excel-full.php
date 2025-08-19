<?php
/**
 * Debug Excel file - find actual data
 */

// Load WordPress
require_once '/var/www/html/wp-load.php';

// Load PhpSpreadsheet
require_once dirname(__FILE__) . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$excel_path = '/var/www/html/wp-content/plugins/mobility-trailblazers/.internal/Kandidatenliste Trailblazers 2025_08_18_List for Nicolas.xlsx';

echo "Finding actual data in Excel file...\n\n";

try {
    $reader = IOFactory::createReaderForFile($excel_path);
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($excel_path);
    $worksheet = $spreadsheet->getActiveSheet();
    
    $highestRow = $worksheet->getHighestRow();
    $highestColumn = $worksheet->getHighestColumn();
    
    // Check rows 1-10 to find where data starts
    echo "Checking first 10 rows to find headers:\n";
    echo "========================================\n\n";
    
    for ($row = 1; $row <= min(10, $highestRow); $row++) {
        $rowData = $worksheet->rangeToArray("A$row:$highestColumn$row", null, true, false)[0];
        $nonEmpty = array_filter($rowData);
        
        if (count($nonEmpty) > 3) { // If row has more than 3 non-empty cells
            echo "Row $row (has " . count($nonEmpty) . " values):\n";
            foreach ($rowData as $col => $value) {
                if (!empty($value)) {
                    $colLetter = chr(65 + $col);
                    $preview = substr($value, 0, 30);
                    if (strlen($value) > 30) $preview .= '...';
                    echo "  $colLetter: $preview\n";
                }
            }
            echo "\n";
        }
    }
    
    // Look for row with "Name" header
    echo "\nSearching for 'Name' header...\n";
    $headerRow = 0;
    for ($row = 1; $row <= min(20, $highestRow); $row++) {
        $rowData = $worksheet->rangeToArray("A$row:$highestColumn$row", null, true, false)[0];
        foreach ($rowData as $value) {
            if (stripos($value, 'name') !== false || stripos($value, 'Name') !== false) {
                $headerRow = $row;
                echo "Found potential header row at row $row\n";
                break 2;
            }
        }
    }
    
    if ($headerRow > 0) {
        echo "\nHeaders at row $headerRow:\n";
        $headers = $worksheet->rangeToArray("A$headerRow:$highestColumn$headerRow", null, true, false)[0];
        foreach ($headers as $index => $header) {
            if (!empty($header)) {
                $colLetter = chr(65 + $index);
                echo "  $colLetter: $header\n";
            }
        }
        
        // Show next data row
        $dataRow = $headerRow + 1;
        echo "\nFirst data row (row $dataRow):\n";
        $data = $worksheet->rangeToArray("A$dataRow:$highestColumn$dataRow", null, true, false)[0];
        foreach ($data as $index => $value) {
            if (!empty($value) && !empty($headers[$index])) {
                $preview = substr($value, 0, 50);
                if (strlen($value) > 50) $preview .= '...';
                echo "  {$headers[$index]}: $preview\n";
            }
        }
        
        // Count remaining data rows
        $dataCount = 0;
        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $rowData = $worksheet->rangeToArray("A$row:H$row", null, true, false)[0];
            if (!empty(array_filter($rowData))) {
                $dataCount++;
            }
        }
        echo "\nTotal data rows after headers: $dataCount\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}