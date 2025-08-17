<?php
/**
 * Excel to CSV Converter for Mobility Trailblazers Platform
 * Converts the Excel candidate list to the proper CSV format for import
 * 
 * @version 1.0.0
 * @date 2025-08-16
 */

// This is a standalone PHP script that can be run via WP-CLI or included in the admin

/**
 * Convert Excel data array to CSV format matching platform requirements
 * 
 * @param array $excel_data Array of candidate data from Excel
 * @return string CSV formatted string
 */
function convert_excel_to_platform_csv($excel_data) {
    // Define the CSV headers matching the platform requirements
    $csv_headers = [
        'ID',
        'Name', 
        'Organisation',
        'Position',
        'LinkedIn-Link',
        'Webseite',
        'Article about coming of age',
        'Description',
        'Category',
        'Status'
    ];
    
    // Start building CSV
    $csv_output = [];
    $csv_output[] = implode(',', $csv_headers);
    
    foreach ($excel_data as $candidate) {
        $row = [];
        
        // Map Excel columns to CSV columns
        $row[] = isset($candidate['ID']) ? $candidate['ID'] : '';
        $row[] = isset($candidate['Name']) ? clean_csv_field($candidate['Name']) : '';
        $row[] = isset($candidate['Organisation']) ? clean_csv_field($candidate['Organisation']) : '';
        $row[] = isset($candidate['Position']) ? clean_csv_field($candidate['Position']) : '';
        $row[] = isset($candidate['Linkedin-Link']) ? clean_url($candidate['Linkedin-Link']) : '';
        $row[] = isset($candidate['Webseite']) ? clean_url($candidate['Webseite']) : '';
        $row[] = ''; // Article field - not in Excel data
        $row[] = isset($candidate['Description']) ? clean_csv_field($candidate['Description']) : '';
        $row[] = isset($candidate['Category']) ? map_category($candidate['Category']) : '';
        $row[] = isset($candidate['Top 50']) ? map_status($candidate['Top 50']) : '';
        
        $csv_output[] = format_csv_row($row);
    }
    
    return implode("\n", $csv_output);
}

/**
 * Clean and escape CSV field
 */
function clean_csv_field($value) {
    // Remove any potential issues
    $value = trim($value);
    
    // Handle line breaks - replace with spaces or keep based on field
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    
    // If contains comma, newline, or quote, wrap in quotes
    if (strpos($value, ',') !== false || strpos($value, "\n") !== false || strpos($value, '"') !== false) {
        // Escape quotes by doubling them
        $value = str_replace('"', '""', $value);
        return '"' . $value . '"';
    }
    
    return $value;
}

/**
 * Clean and validate URLs
 */
function clean_url($url) {
    $url = trim($url);
    if (empty($url)) {
        return '';
    }
    
    // Add https:// if no protocol
    if (!preg_match('/^https?:\/\//i', $url)) {
        $url = 'https://' . $url;
    }
    
    return $url;
}

/**
 * Map category values to standard platform categories
 */
function map_category($category) {
    $category = trim($category);
    
    // Category mapping based on Excel data
    $mappings = [
        'Governance & Verwaltungen, Politik, öffentliche Unternehmen' => 'Gov',
        'Etablierte Unternehmen' => 'Tech',
        'Start-ups, Scale-ups & Katalysatoren' => 'Startup',
        'Start-ups & Scale-ups' => 'Startup',
    ];
    
    return isset($mappings[$category]) ? $mappings[$category] : 'Tech';
}

/**
 * Map status values
 */
function map_status($status) {
    $status = trim(strtolower($status));
    
    if ($status === 'ja' || $status === 'yes' || $status === '1' || $status === 'true') {
        return 'Ja';
    }
    
    return 'Nein';
}

/**
 * Format CSV row properly
 */
function format_csv_row($fields) {
    $formatted = [];
    foreach ($fields as $field) {
        if (is_string($field) && !preg_match('/^".*"$/', $field)) {
            // Field not already quoted, check if it needs quotes
            if (strpos($field, ',') !== false || strpos($field, "\n") !== false) {
                $field = clean_csv_field($field);
            }
        }
        $formatted[] = $field;
    }
    return implode(',', $formatted);
}

/**
 * Sample data structure for testing
 * In production, this would come from parsing the Excel file
 */
function get_sample_candidate_data() {
    return [
        [
            'ID' => 'CAND-001',
            'Name' => 'Alexander Möller',
            'Position' => 'Geschäftsführer',
            'Organisation' => 'VDV',
            'Linkedin-Link' => 'https://www.linkedin.com/in/alexander-möller-486538187/',
            'Webseite' => 'https://www.vdv.de/',
            'Category' => 'Governance & Verwaltungen, Politik, öffentliche Unternehmen',
            'Description' => 'Alexander Möller hat als Geschäftsführer des Verbands Deutscher Verkehrsunternehmen ein umfassendes Zukunftsgutachten für den ÖPNV vorgelegt.',
            'Top 50' => 'Ja'
        ]
    ];
}

// If run directly (for testing)
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($argv[0])) {
    echo "Excel to CSV Converter for Mobility Trailblazers\n";
    echo "==============================================\n\n";
    
    $sample_data = get_sample_candidate_data();
    $csv_output = convert_excel_to_platform_csv($sample_data);
    
    echo "Sample CSV Output:\n";
    echo $csv_output . "\n";
    
    // Save to file
    $output_file = __DIR__ . '/candidates_import.csv';
    file_put_contents($output_file, $csv_output);
    echo "\nCSV saved to: " . $output_file . "\n";
}
