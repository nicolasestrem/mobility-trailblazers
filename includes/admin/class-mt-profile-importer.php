<?php
/**
 * Bulk Import Candidate Profiles
 *
 * @package MobilityTrailblazers
 * @since 2.1.0
 */

namespace MobilityTrailblazers\Admin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Profile_Importer
 *
 * Handles bulk import of candidate profiles
 */
class MT_Profile_Importer {
    
    /**
     * Process CSV import
     *
     * @param string $file_path Path to uploaded CSV file
     * @return array Import results
     */
    public static function import_csv($file_path) {
        $results = [
            'success' => 0,
            'errors' => 0,
            'messages' => []
        ];
        
        // Check if file exists
        if (!file_exists($file_path)) {
            $results['messages'][] = __('File not found.', 'mobility-trailblazers');
            return $results;
        }
        
        // Open file
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            $results['messages'][] = __('Could not open file.', 'mobility-trailblazers');
            return $results;
        }
        
        // Get headers
        $headers = fgetcsv($handle);
        if (!$headers) {
            $results['messages'][] = __('No headers found in CSV.', 'mobility-trailblazers');
            fclose($handle);
            return $results;
        }
        
        // Map headers to fields
        $field_map = self::map_csv_headers($headers);
        
        // Process rows
        $row_number = 1;
        while (($data = fgetcsv($handle)) !== FALSE) {
            $row_number++;
            
            // Skip empty rows
            if (empty(array_filter($data))) {
                continue;
            }
            
            // Map data to fields
            $candidate_data = self::map_row_data($data, $field_map);
            
            // Import candidate
            $result = self::import_candidate($candidate_data, $row_number);
            
            if ($result['success']) {
                $results['success']++;
            } else {
                $results['errors']++;
                $results['messages'][] = sprintf(
                    __('Row %d: %s', 'mobility-trailblazers'),
                    $row_number,
                    $result['message']
                );
            }
        }
        
        fclose($handle);
        
        return $results;
    }
    
    /**
     * Map CSV headers to profile fields
     *
     * @param array $headers CSV headers
     * @return array Field mapping
     */
    private static function map_csv_headers($headers) {
        $mapping = [];
        
        // Define possible header variations
        $field_variations = [
            'name' => ['name', 'candidate name', 'full name', 'display name', 'kandidat', 'name des kandidaten'],
            'organization' => ['organization', 'company', 'organisation', 'firma', 'unternehmen'],
            'position' => ['position', 'title', 'job title', 'rolle', 'position'],
            'linkedin' => ['linkedin', 'linkedin url', 'linkedin profile'],
            'website' => ['website', 'website url', 'web', 'webseite'],
            'email' => ['email', 'e-mail', 'email address'],
            'overview' => ['overview', 'überblick', 'description', 'beschreibung', 'bio', 'biography'],
            'evaluation_criteria' => ['evaluation criteria', 'bewertung nach kriterien', 'criteria', 'kriterien'],
            'personality' => ['personality', 'motivation', 'persönlichkeit', 'persönlichkeit & motivation'],
            'category' => ['category', 'kategorie', 'award category']
        ];
        
        // Map headers
        foreach ($headers as $index => $header) {
            $header_lower = strtolower(trim($header));
            
            foreach ($field_variations as $field => $variations) {
                if (in_array($header_lower, $variations)) {
                    $mapping[$field] = $index;
                    break;
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
                $mapped[$field] = trim($data[$index]);
            }
        }
        
        return $mapped;
    }
    
    /**
     * Import single candidate
     *
     * @param array $data Candidate data
     * @param int $row_number Row number for error reporting
     * @return array Result with success status and message
     */
    private static function import_candidate($data, $row_number) {
        // Check required fields
        if (empty($data['name'])) {
            return [
                'success' => false,
                'message' => __('Name is required', 'mobility-trailblazers')
            ];
        }
        
        // Check if candidate exists
        $existing = get_page_by_title($data['name'], OBJECT, 'mt_candidate');
        
        if ($existing) {
            // Update existing candidate
            $post_id = $existing->ID;
            $action = 'updated';
        } else {
            // Create new candidate
            $post_data = [
                'post_title' => $data['name'],
                'post_type' => 'mt_candidate',
                'post_status' => 'publish',
                'post_content' => isset($data['overview']) ? $data['overview'] : ''
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
        if (isset($data['name'])) {
            update_post_meta($post_id, '_mt_display_name', $data['name']);
        }
        
        if (isset($data['organization'])) {
            update_post_meta($post_id, '_mt_organization', $data['organization']);
        }
        
        if (isset($data['position'])) {
            update_post_meta($post_id, '_mt_position', $data['position']);
        }
        
        if (isset($data['linkedin'])) {
            update_post_meta($post_id, '_mt_linkedin', esc_url_raw($data['linkedin']));
        }
        
        if (isset($data['website'])) {
            update_post_meta($post_id, '_mt_website', esc_url_raw($data['website']));
        }
        
        if (isset($data['overview'])) {
            update_post_meta($post_id, '_mt_overview', wp_kses_post($data['overview']));
        }
        
        if (isset($data['evaluation_criteria'])) {
            update_post_meta($post_id, '_mt_evaluation_criteria', wp_kses_post($data['evaluation_criteria']));
        }
        
        if (isset($data['personality'])) {
            update_post_meta($post_id, '_mt_personality_motivation', wp_kses_post($data['personality']));
        }
        
        // Handle category
        if (!empty($data['category'])) {
            $term = term_exists($data['category'], 'mt_award_category');
            if (!$term) {
                $term = wp_insert_term($data['category'], 'mt_award_category');
            }
            if ($term && !is_wp_error($term)) {
                wp_set_post_terms($post_id, [$term['term_id']], 'mt_award_category');
            }
        }
        
        return [
            'success' => true,
            'message' => sprintf(
                __('Candidate "%s" %s successfully', 'mobility-trailblazers'),
                $data['name'],
                $action
            ),
            'post_id' => $post_id
        ];
    }
    
    /**
     * Generate sample CSV template
     *
     * @return string CSV content
     */
    public static function generate_template() {
        $headers = [
            'Name',
            'Organization',
            'Position',
            'LinkedIn URL',
            'Website URL',
            'Category',
            'Overview',
            'Evaluation Criteria',
            'Personality & Motivation'
        ];
        
        $sample_data = [
            [
                'Dr. Example Name',
                'Example Company GmbH',
                'CEO & Founder',
                'https://linkedin.com/in/example',
                'https://example.com',
                'Innovation Leaders',
                'This is the overview/biography section. You can include multiple paragraphs here.',
                'This section describes how the candidate meets the evaluation criteria.',
                'This section describes the personality and motivation of the candidate.'
            ]
        ];
        
        // Generate CSV
        $output = fopen('php://temp', 'r+');
        
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
}
