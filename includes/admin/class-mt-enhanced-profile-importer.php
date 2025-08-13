<?php
/**
 * Enhanced Bulk Import Candidate Profiles
 *
 * @package MobilityTrailblazers
 * @since 2.2.0
 */

namespace MobilityTrailblazers\Admin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Enhanced_Profile_Importer
 *
 * Handles enhanced bulk import of candidate profiles with better mapping and validation
 */
class MT_Enhanced_Profile_Importer {
    
    /**
     * Get field mapping for CSV columns to WordPress fields
     *
     * @return array Field mapping
     */
    public static function get_field_mapping() {
        return [
            'ID' => '_mt_candidate_id',
            'Name' => 'post_title',
            'Organisation' => '_mt_organization',
            'Position' => '_mt_position',
            'LinkedIn-Link' => '_mt_linkedin_url',
            'Webseite' => '_mt_website_url',
            'Article about coming of age' => '_mt_article_url',
            'Description' => '_mt_description_full',
            'Category' => '_mt_category_type',
            'Status' => '_mt_top_50_status'
        ];
    }
    
    /**
     * Parse evaluation criteria from German description text
     *
     * @param string $description Description text containing evaluation criteria
     * @return array Parsed criteria with scores
     */
    public static function parse_evaluation_criteria($description) {
        $criteria = [
            '_mt_evaluation_courage' => '',
            '_mt_evaluation_innovation' => '',
            '_mt_evaluation_implementation' => '',
            '_mt_evaluation_relevance' => '',
            '_mt_evaluation_visibility' => '',
            '_mt_evaluation_personality' => ''
        ];
        
        if (empty($description)) {
            return $criteria;
        }
        
        // Define patterns for each criterion (German text)
        $patterns = [
            '_mt_evaluation_courage' => '/Mut\s*&\s*Pioniergeist:\s*(.+?)(?=(?:Innovationsgrad:|Umsetzungskraft|Relevanz|Vorbildfunktion|Persönlichkeit|$))/isu',
            '_mt_evaluation_innovation' => '/Innovationsgrad:\s*(.+?)(?=(?:Mut\s*&|Umsetzungskraft|Relevanz|Vorbildfunktion|Persönlichkeit|$))/isu',
            '_mt_evaluation_implementation' => '/Umsetzungskraft\s*&\s*Wirkung:\s*(.+?)(?=(?:Mut\s*&|Innovationsgrad:|Relevanz|Vorbildfunktion|Persönlichkeit|$))/isu',
            '_mt_evaluation_relevance' => '/Relevanz\s*für\s*die\s*Mobilitätswende:\s*(.+?)(?=(?:Mut\s*&|Innovationsgrad:|Umsetzungskraft|Vorbildfunktion|Persönlichkeit|$))/isu',
            '_mt_evaluation_visibility' => '/Vorbildfunktion\s*&\s*Sichtbarkeit:\s*(.+?)(?=(?:Mut\s*&|Innovationsgrad:|Umsetzungskraft|Relevanz|Persönlichkeit|$))/isu',
            '_mt_evaluation_personality' => '/Persönlichkeit\s*&\s*Motivation:\s*(.+?)(?=(?:Mut\s*&|Innovationsgrad:|Umsetzungskraft|Relevanz|Vorbildfunktion|$))/isu'
        ];
        
        // Extract text for each criterion
        foreach ($patterns as $field => $pattern) {
            if (preg_match($pattern, $description, $matches)) {
                $criteria[$field] = trim($matches[1]);
                // Clean up the extracted text
                $criteria[$field] = preg_replace('/\s+/', ' ', $criteria[$field]);
                $criteria[$field] = trim($criteria[$field], " \t\n\r\0\x0B.,;:");
            }
        }
        
        return $criteria;
    }
    
    /**
     * Process CSV import with enhanced features
     *
     * @param string $file_path Path to uploaded CSV file
     * @param array $options Import options
     * @return array Import results
     */
    public static function import_csv($file_path, $options = []) {
        $results = [
            'success' => 0,
            'errors' => 0,
            'updated' => 0,
            'skipped' => 0,
            'messages' => [],
            'imported_ids' => [],
            'error_details' => []
        ];
        
        // Default options
        $options = wp_parse_args($options, [
            'update_existing' => true,
            'skip_empty_fields' => false,
            'validate_urls' => true,
            'import_photos' => true,
            'dry_run' => false
        ]);
        
        // Check if file exists
        if (!file_exists($file_path)) {
            $results['messages'][] = __('File not found.', 'mobility-trailblazers');
            return $results;
        }
        
        // Validate file type (more lenient)
        $file_info = wp_check_filetype($file_path);
        $allowed_types = ['csv', 'txt', 'text'];
        // Allow if extension is in allowed list or if no extension (could be temp file)
        if (!empty($file_info['ext']) && !in_array(strtolower($file_info['ext']), $allowed_types)) {
            $results['messages'][] = sprintf(
                __('Warning: Unexpected file extension "%s". Processing as CSV.', 'mobility-trailblazers'),
                $file_info['ext']
            );
            // Don't return - continue processing
        }
        
        // Validate MIME type for additional security (more lenient check)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        
        $allowed_mimes = ['text/csv', 'text/plain', 'application/csv', 'application/x-csv', 'application/vnd.ms-excel', 'text/x-csv', 'text/comma-separated-values', 'application/octet-stream'];
        // Allow if MIME type contains 'text' or 'csv'
        $is_valid_mime = in_array($mime_type, $allowed_mimes) || 
                        strpos($mime_type, 'text') !== false || 
                        strpos($mime_type, 'csv') !== false;
        
        if (!$is_valid_mime) {
            $results['messages'][] = sprintf(
                __('Warning: Unexpected file MIME type "%s". Attempting to process as CSV anyway.', 'mobility-trailblazers'),
                $mime_type
            );
            // Don't return here - continue processing
        }
        
        // Check file size (max 10MB)
        $max_size = 10 * MB_IN_BYTES;
        $file_size = filesize($file_path);
        if ($file_size > $max_size) {
            $results['messages'][] = sprintf(
                __('File too large. Maximum size is %s. Your file is %s.', 'mobility-trailblazers'),
                size_format($max_size),
                size_format($file_size)
            );
            return $results;
        }
        
        // Open file with UTF-8 encoding support
        // Try to detect if file has BOM and handle accordingly
        $bom = file_get_contents($file_path, false, null, 0, 3);
        $has_bom = ($bom === "\xEF\xBB\xBF");
        
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            $results['messages'][] = __('Could not open file.', 'mobility-trailblazers');
            return $results;
        }
        
        // Skip BOM if present
        if ($has_bom) {
            fread($handle, 3);
        }
        
        // Detect delimiter
        $delimiter = self::detect_delimiter($file_path);
        
        // Add debug info
        $results['messages'][] = sprintf(
            __('File info: MIME type: %s, Delimiter detected: %s', 'mobility-trailblazers'),
            isset($mime_type) ? $mime_type : 'unknown',
            $delimiter === '\t' ? 'TAB' : $delimiter
        );
        
        // Find the actual header row (skip metadata rows)
        $headers = null;
        $row_number = 0;
        
        // Reset file pointer to beginning (but skip BOM if present)
        rewind($handle);
        if ($has_bom) {
            fread($handle, 3);
        }
        
        while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
            $row_number++;
            
            // Skip empty rows
            if (empty(array_filter($data))) {
                continue;
            }
            
            // Look for the row that contains key headers (case-insensitive)
            $has_id = false;
            $has_name = false;
            
            foreach ($data as $index => $cell) {
                // Remove BOM from first cell if present
                if ($index === 0) {
                    $cell = str_replace("\xEF\xBB\xBF", '', $cell);
                }
                $cell_lower = strtolower(trim($cell));
                if ($cell_lower === 'id' || $cell_lower === 'nummer') {
                    $has_id = true;
                }
                if ($cell_lower === 'name' || strpos($cell_lower, 'name') !== false) {
                    $has_name = true;
                }
            }
            
            // If we found ID or Name columns, this is likely our header row
            if ($has_id || $has_name) {
                // Clean headers - remove BOM from first header if present
                $headers = array_map('trim', $data);
                if (!empty($headers[0])) {
                    $headers[0] = str_replace("\xEF\xBB\xBF", '', $headers[0]);
                }
                break;
            }
            
            // Stop if we've checked too many rows
            if ($row_number > 20) {
                break;
            }
        }
        
        if (!$headers) {
            $results['messages'][] = __('No valid headers found in CSV. Please ensure your CSV has a header row with columns like "ID", "Name", etc.', 'mobility-trailblazers');
            fclose($handle);
            return $results;
        }
        
        // Debug: Show detected headers
        $results['messages'][] = sprintf(
            __('Found %d columns in header row: %s', 'mobility-trailblazers'),
            count($headers),
            implode(', ', array_slice($headers, 0, 5)) . (count($headers) > 5 ? '...' : '')
        );
        
        // Map headers to fields
        $field_map = self::map_csv_headers($headers);
        
        // Debug: Show mapped fields
        $mapped_fields = array_keys($field_map);
        if (!empty($mapped_fields)) {
            $results['messages'][] = sprintf(
                __('Mapped fields: %s', 'mobility-trailblazers'),
                implode(', ', $mapped_fields)
            );
        }
        
        // Validate required fields - check if 'Name' header was mapped
        if (!isset($field_map['Name'])) {
            $results['messages'][] = __('Required field "Name" not found in CSV headers.', 'mobility-trailblazers');
            $results['messages'][] = sprintf(
                __('Available headers: %s', 'mobility-trailblazers'),
                implode(', ', $headers)
            );
            fclose($handle);
            return $results;
        }
        
        // Process rows
        while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
            $row_number++;
            
            // Skip empty rows
            if (empty(array_filter($data))) {
                continue;
            }
            
            // Map data to fields
            $candidate_data = self::map_row_data($data, $field_map);
            
            // Skip if no name (check for post_title)
            if (empty($candidate_data['post_title'])) {
                continue;
            }
            
            // Import candidate
            if ($options['dry_run']) {
                $result = self::validate_candidate($candidate_data, $row_number, $options);
            } else {
                $result = self::import_candidate($candidate_data, $row_number, $options);
            }
            
            if ($result['success']) {
                if ($result['action'] === 'created') {
                    $results['success']++;
                } elseif ($result['action'] === 'updated') {
                    $results['updated']++;
                } elseif ($result['action'] === 'skipped') {
                    $results['skipped']++;
                }
                
                if (!empty($result['post_id'])) {
                    $results['imported_ids'][] = $result['post_id'];
                }
            } else {
                $results['errors']++;
                $results['error_details'][] = [
                    'row' => $row_number,
                    'name' => $candidate_data['name'] ?? 'Unknown',
                    'error' => $result['message']
                ];
            }
            
            // Add message if verbose or error
            if (!$result['success'] || $options['dry_run']) {
                $results['messages'][] = sprintf(
                    __('Row %d (%s): %s', 'mobility-trailblazers'),
                    $row_number,
                    $candidate_data['name'] ?? 'Unknown',
                    $result['message']
                );
            }
        }
        
        fclose($handle);
        
        // Add summary message
        $results['messages'][] = sprintf(
            __('Import complete: %d created, %d updated, %d skipped, %d errors', 'mobility-trailblazers'),
            $results['success'],
            $results['updated'],
            $results['skipped'],
            $results['errors']
        );
        
        return $results;
    }
    
    /**
     * Detect CSV delimiter
     *
     * @param string $file_path File path
     * @return string Delimiter
     */
    private static function detect_delimiter($file_path) {
        $delimiters = [',', ';', '\t', '|'];
        $handle = fopen($file_path, 'r');
        
        // Check for BOM and skip if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            // Not a BOM, rewind to start
            rewind($handle);
        }
        
        // Read multiple lines to better detect delimiter
        $sample_lines = [];
        for ($i = 0; $i < 5; $i++) {
            $line = fgets($handle);
            if ($line === false) break;
            // Skip empty lines
            if (trim($line) !== '') {
                $sample_lines[] = $line;
            }
        }
        fclose($handle);
        
        if (empty($sample_lines)) {
            return ',';
        }
        
        $delimiter = ',';
        $maxCount = 0;
        $mostConsistent = ',';
        $bestConsistency = 0;
        
        foreach ($delimiters as $d) {
            $counts = [];
            foreach ($sample_lines as $line) {
                $counts[] = count(str_getcsv($line, $d));
            }
            
            // Check for consistency across lines
            $avg_count = array_sum($counts) / count($counts);
            $variance = 0;
            foreach ($counts as $count) {
                $variance += pow($count - $avg_count, 2);
            }
            $variance = $variance / count($counts);
            
            // Prefer delimiter with highest count and lowest variance
            if ($avg_count > 1 && ($avg_count > $maxCount || ($avg_count == $maxCount && $variance < $bestConsistency))) {
                $maxCount = $avg_count;
                $delimiter = $d;
                $bestConsistency = $variance;
            }
        }
        
        return $delimiter;
    }
    
    /**
     * Enhanced CSV header mapping using exact field mapping
     *
     * @param array $headers CSV headers
     * @return array Field mapping
     */
    private static function map_csv_headers($headers) {
        $mapping = [];
        $field_mapping = self::get_field_mapping();
        
        // Map headers directly to their indices
        foreach ($headers as $index => $header) {
            $header_trimmed = trim($header);
            
            // Check for exact match with our field mapping
            if (isset($field_mapping[$header_trimmed])) {
                // Store the index for this header
                $mapping[$header_trimmed] = $index;
            } else {
                // Also check lowercase version for flexibility
                foreach ($field_mapping as $expected_header => $meta_key) {
                    if (strcasecmp($header_trimmed, $expected_header) === 0) {
                        $mapping[$expected_header] = $index;
                        break;
                    }
                }
            }
        }
        
        return $mapping;
    }
    
    /**
     * Map row data using field mapping with proper encoding
     *
     * @param array $data Row data
     * @param array $header_map Header to index mapping
     * @return array Mapped data
     */
    private static function map_row_data($data, $header_map) {
        $mapped = [];
        $field_mapping = self::get_field_mapping();
        
        // Process each field from our mapping
        foreach ($field_mapping as $csv_header => $meta_key) {
            if (isset($header_map[$csv_header]) && isset($data[$header_map[$csv_header]])) {
                $value = trim($data[$header_map[$csv_header]]);
                
                // Ensure UTF-8 encoding for German characters (ä, ö, ü, ß)
                if (!mb_check_encoding($value, 'UTF-8')) {
                    $value = mb_convert_encoding($value, 'UTF-8', 'auto');
                }
                
                // Clean specific fields based on meta key
                switch ($meta_key) {
                    case '_mt_linkedin_url':
                    case '_mt_website_url':
                    case '_mt_article_url':
                        // Validate and clean URLs
                        $value = self::validate_and_clean_url($value);
                        break;
                    case '_mt_top_50_status':
                        // Handle Status field for Top 50
                        $value = in_array(strtolower($value), ['ja', 'yes', '1', 'true', 'top 50', 'top50']) ? 'yes' : 'no';
                        break;
                    case '_mt_category_type':
                        // Map category types
                        $value = self::map_category_type($value);
                        break;
                    case '_mt_description_full':
                        // Preserve line breaks and German characters in description
                        $value = wp_kses_post($value);
                        break;
                    case 'post_title':
                        // Just sanitize the title
                        $value = sanitize_text_field($value);
                        break;
                    default:
                        // Preserve German characters while sanitizing
                        $value = sanitize_text_field($value);
                }
                
                $mapped[$meta_key] = $value;
            }
        }
        
        return $mapped;
    }
    
    /**
     * Validate and clean URL
     *
     * @param string $url URL to validate
     * @return string Cleaned URL or empty string if invalid
     */
    private static function validate_and_clean_url($url) {
        if (empty($url)) {
            return '';
        }
        
        // Add protocol if missing
        if (!preg_match('/^https?:\/\//i', $url)) {
            $url = 'https://' . $url;
        }
        
        // Validate URL
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return esc_url_raw($url);
        }
        
        return '';
    }
    
    /**
     * Map category type to standardized format
     *
     * @param string $category Category from CSV
     * @return string Standardized category
     */
    private static function map_category_type($category) {
        $category_lower = strtolower(trim($category));
        
        // Map to standardized categories
        if (strpos($category_lower, 'startup') !== false || strpos($category_lower, 'start-up') !== false) {
            return 'Startup';
        } elseif (strpos($category_lower, 'gov') !== false || strpos($category_lower, 'verwaltung') !== false || strpos($category_lower, 'government') !== false) {
            return 'Gov';
        } elseif (strpos($category_lower, 'tech') !== false || strpos($category_lower, 'technology') !== false || strpos($category_lower, 'technologie') !== false) {
            return 'Tech';
        }
        
        // Return original if no match
        return sanitize_text_field($category);
    }
    
    /**
     * Validate candidate data without importing
     *
     * @param array $data Candidate data with meta keys
     * @param int $row_number Row number for error reporting
     * @param array $options Import options
     * @return array Result with success status and message
     */
    private static function validate_candidate($data, $row_number, $options) {
        // Check required fields (post_title is the name)
        if (empty($data['post_title'])) {
            return [
                'success' => false,
                'message' => __('Name is required', 'mobility-trailblazers')
            ];
        }
        
        // Validate URLs if option is set
        if ($options['validate_urls']) {
            // Check LinkedIn URL
            if (!empty($data['_mt_linkedin_url']) && !filter_var($data['_mt_linkedin_url'], FILTER_VALIDATE_URL)) {
                return [
                    'success' => false,
                    'message' => __('Invalid LinkedIn URL', 'mobility-trailblazers')
                ];
            }
            
            // Check Website URL
            if (!empty($data['_mt_website_url']) && !filter_var($data['_mt_website_url'], FILTER_VALIDATE_URL)) {
                return [
                    'success' => false,
                    'message' => __('Invalid website URL', 'mobility-trailblazers')
                ];
            }
            
            // Check Article URL
            if (!empty($data['_mt_article_url']) && !filter_var($data['_mt_article_url'], FILTER_VALIDATE_URL)) {
                return [
                    'success' => false,
                    'message' => __('Invalid article URL', 'mobility-trailblazers')
                ];
            }
        }
        
        // Check if exists (using WP_Query for better compatibility)
        $existing_query = new \WP_Query([
            'post_type' => 'mt_candidate',
            'title' => $data['post_title'],
            'posts_per_page' => 1,
            'post_status' => 'any'
        ]);
        $existing = $existing_query->have_posts() ? $existing_query->posts[0] : null;
        
        if ($existing && !$options['update_existing']) {
            return [
                'success' => true,
                'action' => 'skipped',
                'message' => __('Already exists (skipped)', 'mobility-trailblazers')
            ];
        }
        
        return [
            'success' => true,
            'action' => $existing ? 'would_update' : 'would_create',
            'message' => $existing ? __('Would update existing', 'mobility-trailblazers') : __('Would create new', 'mobility-trailblazers')
        ];
    }
    
    /**
     * Import single candidate with enhanced features
     *
     * @param array $data Candidate data with meta keys
     * @param int $row_number Row number for error reporting
     * @param array $options Import options
     * @return array Result with success status and message
     */
    private static function import_candidate($data, $row_number, $options) {
        // Get the name from post_title field
        $name = isset($data['post_title']) ? $data['post_title'] : '';
        
        if (empty($name)) {
            return [
                'success' => false,
                'message' => __('Name is required', 'mobility-trailblazers')
            ];
        }
        
        // Check if candidate exists (using WP_Query for better compatibility)
        $existing_query = new \WP_Query([
            'post_type' => 'mt_candidate',
            'title' => $name,
            'posts_per_page' => 1,
            'post_status' => 'any'
        ]);
        $existing = $existing_query->have_posts() ? $existing_query->posts[0] : null;
        
        if ($existing) {
            if (!$options['update_existing']) {
                return [
                    'success' => true,
                    'action' => 'skipped',
                    'message' => __('Already exists (skipped)', 'mobility-trailblazers'),
                    'post_id' => $existing->ID
                ];
            }
            
            // Update existing candidate
            $post_id = $existing->ID;
            $action = 'updated';
            
            // Update post title if changed
            if ($existing->post_title !== $name) {
                wp_update_post([
                    'ID' => $post_id,
                    'post_title' => $name
                ]);
            }
        } else {
            // Create new candidate
            $post_data = [
                'post_title' => $name,
                'post_type' => 'mt_candidate',
                'post_status' => 'publish',
                'post_content' => '' // We'll store description as meta
            ];
            
            $post_id = wp_insert_post($post_data);
            
            if (is_wp_error($post_id)) {
                return [
                    'success' => false,
                    'message' => $post_id->get_error_message()
                ];
            }
            
            $action = 'created';
        }
        
        // Parse evaluation criteria from description if present
        $evaluation_criteria = [];
        if (!empty($data['_mt_description_full'])) {
            $evaluation_criteria = self::parse_evaluation_criteria($data['_mt_description_full']);
        }
        
        // Update all meta fields directly from the mapped data
        foreach ($data as $meta_key => $value) {
            // Skip post_title as it's not a meta field
            if ($meta_key === 'post_title') {
                continue;
            }
            
            // Only update if value is not empty or if we're not skipping empty fields
            if (!$options['skip_empty_fields'] || !empty($value)) {
                update_post_meta($post_id, $meta_key, $value);
            }
        }
        
        // Save evaluation criteria if parsed
        foreach ($evaluation_criteria as $criterion_key => $criterion_value) {
            if (!empty($criterion_value)) {
                update_post_meta($post_id, $criterion_key, $criterion_value);
            }
        }
        
        return [
            'success' => true,
            'action' => $action,
            'message' => sprintf(
                __('Candidate "%s" %s successfully', 'mobility-trailblazers'),
                $name,
                $action
            ),
            'post_id' => $post_id
        ];
    }
    
    /**
     * Attach candidate photo from uploads directory
     *
     * @param int $post_id Post ID
     * @param string $candidate_name Candidate name
     * @return bool Success
     */
    private static function attach_candidate_photo($post_id, $candidate_name) {
        // Sanitize name for filename
        $filename_base = sanitize_file_name(str_replace(' ', '_', $candidate_name));
        
        // Check for existing attachment
        $args = [
            'post_type' => 'attachment',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_wp_attached_file',
                    'value' => $filename_base,
                    'compare' => 'LIKE'
                ]
            ]
        ];
        
        $attachments = get_posts($args);
        
        if (!empty($attachments)) {
            set_post_thumbnail($post_id, $attachments[0]->ID);
            return true;
        }
        
        return false;
    }
    
    /**
     * Import photo from URL
     *
     * @param int $post_id Post ID
     * @param string $photo_url Photo URL
     * @return bool Success
     */
    private static function import_photo_from_url($post_id, $photo_url) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $attachment_id = media_sideload_image($photo_url, $post_id, null, 'id');
        
        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($post_id, $attachment_id);
            return true;
        }
        
        return false;
    }
    
    /**
     * Generate enhanced CSV template
     *
     * @return string CSV content
     */
    public static function generate_template() {
        $headers = [
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

        $sample_data = [
            [
                'CAND-001',
                'Dr. Maria Müller',
                'Beispiel Mobility GmbH',
                'CEO & Gründerin',
                'https://linkedin.com/in/mariamueller',
                'https://beispiel-mobility.de',
                'https://example.com/article-maria',
                'Dr. Maria Müller ist eine Pionierin der nachhaltigen Mobilität in Deutschland. Ihre innovative Lösung für urbane Verkehrsprobleme hat bereits mehrere Städte transformiert.',
                'Startup',
                'Ja'
            ],
            [
                'CAND-002',
                'Prof. Dr. Hans Schäfer',
                'Stadt München',
                'Oberbürgermeister',
                'https://linkedin.com/in/hansschaefer',
                'https://muenchen.de',
                'https://example.com/article-hans',
                'Prof. Dr. Hans Schäfer hat als Oberbürgermeister wegweisende Mobilitätskonzepte für München entwickelt und erfolgreich umgesetzt.',
                'Gov',
                'Nein'
            ],
            [
                'CAND-003',
                'Anna Böhm',
                'Tech Innovations AG',
                'CTO',
                'https://linkedin.com/in/annaboehm',
                'https://tech-innovations.de',
                'https://example.com/article-anna',
                'Anna Böhm entwickelt KI-basierte Lösungen für autonomes Fahren und hat mehrere Patente im Bereich der Mobilitätstechnologie.',
                'Tech',
                'Ja'
            ]
        ];
        
        // Generate CSV
        $output = fopen('php://temp', 'r+');
        
        // Add BOM for UTF-8 Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write sample data
        foreach ($sample_data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * Get import statistics
     *
     * @return array Statistics
     */
    public static function get_import_statistics() {
        global $wpdb;
        
        $stats = [
            'total_candidates' => wp_count_posts('mt_candidate')->publish,
            'with_photos' => 0,
            'with_linkedin' => 0,
            'with_website' => 0,
            'top50' => 0,
            'by_category' => []
        ];
        
        // Count candidates with photos
        $stats['with_photos'] = $wpdb->get_var("
            SELECT COUNT(DISTINCT p.ID) 
            FROM {$wpdb->posts} p 
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
            WHERE p.post_type = 'mt_candidate' 
            AND p.post_status = 'publish' 
            AND pm.meta_key = '_thumbnail_id'
        ");
        
        // Count candidates with LinkedIn
        $stats['with_linkedin'] = $wpdb->get_var("
            SELECT COUNT(DISTINCT p.ID) 
            FROM {$wpdb->posts} p 
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
            WHERE p.post_type = 'mt_candidate' 
            AND p.post_status = 'publish' 
            AND pm.meta_key = '_mt_linkedin' 
            AND pm.meta_value != ''
        ");
        
        // Count candidates with website
        $stats['with_website'] = $wpdb->get_var("
            SELECT COUNT(DISTINCT p.ID) 
            FROM {$wpdb->posts} p 
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
            WHERE p.post_type = 'mt_candidate' 
            AND p.post_status = 'publish' 
            AND pm.meta_key = '_mt_website' 
            AND pm.meta_value != ''
        ");
        
        // Count Top 50
        $stats['top50'] = $wpdb->get_var("
            SELECT COUNT(DISTINCT p.ID) 
            FROM {$wpdb->posts} p 
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
            WHERE p.post_type = 'mt_candidate' 
            AND p.post_status = 'publish' 
            AND pm.meta_key = '_mt_top50' 
            AND pm.meta_value = 'yes'
        ");
        
        // Count by category
        $categories = get_terms([
            'taxonomy' => 'mt_award_category',
            'hide_empty' => true
        ]);
        
        foreach ($categories as $category) {
            $stats['by_category'][$category->name] = $category->count;
        }
        
        return $stats;
    }
}
