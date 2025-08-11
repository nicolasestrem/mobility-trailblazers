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
        
        // Validate file type
        $file_info = wp_check_filetype($file_path);
        $allowed_types = ['csv', 'txt'];
        if (!in_array(strtolower($file_info['ext']), $allowed_types)) {
            $results['messages'][] = sprintf(
                __('Invalid file type "%s". Only CSV and TXT files are allowed.', 'mobility-trailblazers'),
                $file_info['ext'] ?: 'unknown'
            );
            return $results;
        }
        
        // Validate MIME type for additional security
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        
        $allowed_mimes = ['text/csv', 'text/plain', 'application/csv', 'application/x-csv'];
        if (!in_array($mime_type, $allowed_mimes)) {
            $results['messages'][] = sprintf(
                __('Invalid file MIME type "%s". File must be a valid CSV file.', 'mobility-trailblazers'),
                $mime_type
            );
            return $results;
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
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            $results['messages'][] = __('Could not open file.', 'mobility-trailblazers');
            return $results;
        }
        
        // Detect delimiter
        $delimiter = self::detect_delimiter($file_path);
        
        // Find the actual header row (skip metadata rows)
        $headers = null;
        $row_number = 0;
        while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
            $row_number++;
            
            // Look for the row that contains 'ID' and 'Name'
            if (in_array('ID', $data) || in_array('Name', $data)) {
                $headers = $data;
                break;
            }
            
            // Stop if we've checked too many rows
            if ($row_number > 10) {
                break;
            }
        }
        
        if (!$headers) {
            $results['messages'][] = __('No valid headers found in CSV.', 'mobility-trailblazers');
            fclose($handle);
            return $results;
        }
        
        // Map headers to fields
        $field_map = self::map_csv_headers($headers);
        
        // Validate required fields
        if (!isset($field_map['name'])) {
            $results['messages'][] = __('Required field "Name" not found in CSV headers.', 'mobility-trailblazers');
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
            
            // Skip if no name
            if (empty($candidate_data['name'])) {
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
        $firstLine = fgets($handle);
        fclose($handle);
        
        $delimiter = ',';
        $maxCount = 0;
        
        foreach ($delimiters as $d) {
            $count = count(str_getcsv($firstLine, $d));
            if ($count > $maxCount) {
                $maxCount = $count;
                $delimiter = $d;
            }
        }
        
        return $delimiter;
    }
    
    /**
     * Enhanced CSV header mapping
     *
     * @param array $headers CSV headers
     * @return array Field mapping
     */
    private static function map_csv_headers($headers) {
        $mapping = [];
        
        // Define comprehensive header variations
        $field_variations = [
            'id' => ['id', 'candidate_id', 'nummer'],
            'name' => ['name', 'candidate name', 'full name', 'display name', 'kandidat', 'name des kandidaten'],
            'organization' => ['organization', 'organisation', 'company', 'firma', 'unternehmen', 'organisation'],
            'position' => ['position', 'title', 'job title', 'rolle', 'position', 'funktion'],
            'linkedin' => ['linkedin', 'linkedin url', 'linkedin profile', 'linkedin-link'],
            'website' => ['website', 'website url', 'web', 'webseite', 'homepage'],
            'email' => ['email', 'e-mail', 'email address', 'mail'],
            'category' => ['category', 'kategorie', 'award category', 'kategorie'],
            'top50' => ['top 50', 'top50', 'finalist', 'shortlist'],
            'nominator' => ['nominator', 'nominiert von', 'nominated by', 'nominator'],
            'notes' => ['notes', 'erste notizen/nachricht', 'erste notizen', 'nachricht', 'bemerkungen'],
            'photo' => ['photo', 'foto', 'bild', 'image'],
            'description' => ['description', 'beschreibung', 'bio', 'biography', 'profil'],
            
            // Evaluation criteria fields
            'courage' => ['mut & pioniergeist', 'mut', 'courage', 'pioneer spirit'],
            'innovation' => ['innovationsgrad', 'innovation', 'innovation level'],
            'implementation' => ['umsetzungskraft & wirkung', 'umsetzungskraft', 'implementation', 'impact'],
            'relevance' => ['relevanz für die mobilitätswende', 'relevanz', 'relevance', 'mobility relevance'],
            'visibility' => ['vorbildfunktion & sichtbarkeit', 'vorbildfunktion', 'visibility', 'role model'],
            'personality' => ['persönlichkeit & motivation', 'persönlichkeit', 'personality', 'motivation']
        ];
        
        // Map headers
        foreach ($headers as $index => $header) {
            $header_lower = strtolower(trim($header));
            
            foreach ($field_variations as $field => $variations) {
                foreach ($variations as $variation) {
                    if (strpos($header_lower, $variation) !== false || $header_lower === $variation) {
                        $mapping[$field] = $index;
                        break 2;
                    }
                }
            }
        }
        
        return $mapping;
    }
    
    /**
     * Map row data using field mapping
     *
     * @param array $data Row data
     * @param array $field_map Field mapping
     * @return array Mapped data
     */
    private static function map_row_data($data, $field_map) {
        $mapped = [];
        
        foreach ($field_map as $field => $index) {
            if (isset($data[$index])) {
                $value = trim($data[$index]);
                
                // Clean specific fields
                switch ($field) {
                    case 'linkedin':
                    case 'website':
                        $value = esc_url_raw($value);
                        break;
                    case 'email':
                        $value = sanitize_email($value);
                        break;
                    case 'top50':
                        $value = in_array(strtolower($value), ['ja', 'yes', '1', 'true']) ? 'yes' : 'no';
                        break;
                    case 'description':
                        // Preserve line breaks in description
                        $value = wp_kses_post($value);
                        break;
                    default:
                        $value = sanitize_text_field($value);
                }
                
                $mapped[$field] = $value;
            }
        }
        
        return $mapped;
    }
    
    /**
     * Validate candidate data without importing
     *
     * @param array $data Candidate data
     * @param int $row_number Row number for error reporting
     * @param array $options Import options
     * @return array Result with success status and message
     */
    private static function validate_candidate($data, $row_number, $options) {
        // Check required fields
        if (empty($data['name'])) {
            return [
                'success' => false,
                'message' => __('Name is required', 'mobility-trailblazers')
            ];
        }
        
        // Validate URLs if option is set
        if ($options['validate_urls']) {
            if (!empty($data['linkedin']) && !filter_var($data['linkedin'], FILTER_VALIDATE_URL)) {
                return [
                    'success' => false,
                    'message' => __('Invalid LinkedIn URL', 'mobility-trailblazers')
                ];
            }
            
            if (!empty($data['website']) && !filter_var($data['website'], FILTER_VALIDATE_URL)) {
                return [
                    'success' => false,
                    'message' => __('Invalid website URL', 'mobility-trailblazers')
                ];
            }
        }
        
        // Check if exists
        $existing = get_page_by_title($data['name'], OBJECT, 'mt_candidate');
        
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
     * @param array $data Candidate data
     * @param int $row_number Row number for error reporting
     * @param array $options Import options
     * @return array Result with success status and message
     */
    private static function import_candidate($data, $row_number, $options) {
        // Validate first
        $validation = self::validate_candidate($data, $row_number, $options);
        if (!$validation['success']) {
            return $validation;
        }
        
        // Check if candidate exists
        $existing = get_page_by_title($data['name'], OBJECT, 'mt_candidate');
        
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
            
            // Update post content if provided
            if (!empty($data['description'])) {
                wp_update_post([
                    'ID' => $post_id,
                    'post_content' => $data['description']
                ]);
            }
        } else {
            // Create new candidate
            $post_data = [
                'post_title' => $data['name'],
                'post_type' => 'mt_candidate',
                'post_status' => 'publish',
                'post_content' => $data['description'] ?? ''
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
        
        // Update meta fields
        $meta_fields = [
            'name' => '_mt_display_name',
            'organization' => '_mt_organization',
            'position' => '_mt_position',
            'linkedin' => '_mt_linkedin',
            'website' => '_mt_website',
            'email' => '_mt_email',
            'top50' => '_mt_top50',
            'nominator' => '_mt_nominator',
            'notes' => '_mt_notes',
            'courage' => '_mt_courage',
            'innovation' => '_mt_innovation',
            'implementation' => '_mt_implementation',
            'relevance' => '_mt_relevance',
            'visibility' => '_mt_visibility',
            'personality' => '_mt_personality'
        ];
        
        foreach ($meta_fields as $field => $meta_key) {
            if (isset($data[$field]) && (!$options['skip_empty_fields'] || !empty($data[$field]))) {
                update_post_meta($post_id, $meta_key, $data[$field]);
            }
        }
        
        // Handle category taxonomy
        if (!empty($data['category'])) {
            $categories = array_map('trim', explode(',', $data['category']));
            $term_ids = [];
            
            foreach ($categories as $category_name) {
                $term = term_exists($category_name, 'mt_award_category');
                if (!$term) {
                    $term = wp_insert_term($category_name, 'mt_award_category');
                }
                if ($term && !is_wp_error($term)) {
                    $term_ids[] = is_array($term) ? $term['term_id'] : $term;
                }
            }
            
            if (!empty($term_ids)) {
                wp_set_post_terms($post_id, $term_ids, 'mt_award_category');
            }
        }
        
        // Handle photo import if enabled
        if ($options['import_photos'] && !empty($data['photo'])) {
            // If photo field contains 'Ja' or 'Yes', look for image file
            if (in_array(strtolower($data['photo']), ['ja', 'yes'])) {
                // Try to find and attach photo based on candidate name
                self::attach_candidate_photo($post_id, $data['name']);
            } elseif (filter_var($data['photo'], FILTER_VALIDATE_URL)) {
                // If it's a URL, download and attach
                self::import_photo_from_url($post_id, $data['photo']);
            }
        }
        
        return [
            'success' => true,
            'action' => $action,
            'message' => sprintf(
                __('Candidate "%s" %s successfully', 'mobility-trailblazers'),
                $data['name'],
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
            'Position',
            'Organisation',
            'Category',
            'Top 50',
            'Nominator',
            'Erste Notizen/Nachricht',
            'LinkedIn-Link',
            'Webseite',
            'Foto',
            'Description'
        ];
        
        $sample_data = [
            [
                '1',
                'Dr. Maria Beispiel',
                'CEO & Founder',
                'Beispiel Mobility GmbH',
                'Start-ups, Scale-ups & Katalysatoren',
                'Ja',
                'Max Mustermann',
                'Innovative Lösung für urbane Mobilität',
                'https://linkedin.com/in/mariabeispiel',
                'https://beispiel-mobility.de',
                'Ja',
                'Mut & Pioniergeist: Maria Beispiel hat mit der Gründung ihrer Firma großen Mut bewiesen...'
            ],
            [
                '2',
                'Prof. Dr. Hans Schmidt',
                'Oberbürgermeister',
                'Stadt Musterstadt',
                'Governance & Verwaltungen',
                'Nein',
                'Anna Weber',
                'Vorreiter in der kommunalen Verkehrswende',
                'https://linkedin.com/in/hansschmidt',
                'https://musterstadt.de',
                'Nein',
                'Innovationsgrad: Schmidt hat innovative Konzepte für die Verkehrswende entwickelt...'
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
