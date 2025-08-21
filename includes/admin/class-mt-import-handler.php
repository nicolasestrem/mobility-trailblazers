<?php
/**
 * CSV Import Handler
 *
 * @package MobilityTrailblazers
 * @since 2.2.23
 */

namespace MobilityTrailblazers\Admin;

use MobilityTrailblazers\Core\MT_Logger;
use MobilityTrailblazers\Services\MT_Assignment_Service;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Import_Handler
 *
 * Handles CSV import processing for candidates and jury members
 */
class MT_Import_Handler {
    
    /**
     * Field mapping for candidates CSV
     */
    const CANDIDATE_FIELD_MAPPING = [
        'ID' => 'import_id',
        'Name' => 'name',
        'Organisation' => 'organisation',
        'Organization' => 'organisation',  // US spelling variant
        'Position' => 'position',
        'LinkedIn-Link' => 'linkedin',
        'LinkedIn' => 'linkedin',  // Alternate
        'Webseite' => 'website',
        'Website' => 'website',  // English variant
        'Article about coming of age' => 'article',
        'Article' => 'article',  // Shortened
        'Description' => 'description',
        'Category' => 'category',
        'Status' => 'status'
    ];
    
    /**
     * Field mapping for jury members CSV
     */
    const JURY_FIELD_MAPPING = [
        'name' => 'name',
        'title' => 'title',
        'organization' => 'organization',
        'email' => 'email',
        'role' => 'role'
    ];
    
    /**
     * Process CSV import
     *
     * @param string $file Path to CSV file
     * @param string $import_type Type of import (candidates or jury_members)
     * @param bool $update_existing Whether to update existing records
     * @param int $batch_size Number of records to process per batch
     * @return array Results array with counts and messages
     */
    public function process_csv_import($file, $import_type, $update_existing = false, $batch_size = 50) {
        $results = [
            'success' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'error_details' => [],
            'messages' => []
        ];
        
        // Validate file path to prevent directory traversal
        $file = realpath($file);
        if (!$file) {
            $results['messages'][] = __('Invalid file path', 'mobility-trailblazers');
            MT_Logger::error('Invalid import file path');
            return $results;
        }
        
        // Ensure file is within uploads directory for security
        $upload_dir = wp_upload_dir();
        $upload_basedir = realpath($upload_dir['basedir']);
        if (strpos($file, $upload_basedir) !== 0) {
            $results['messages'][] = __('File must be within the uploads directory', 'mobility-trailblazers');
            MT_Logger::error('Import file outside uploads directory', ['file' => $file]);
            return $results;
        }
        
        // Validate file exists and is readable
        if (!file_exists($file) || !is_readable($file)) {
            $results['messages'][] = __('File not found or not readable', 'mobility-trailblazers');
            MT_Logger::error('Import file not readable', ['file' => $file]);
            return $results;
        }
        
        // Validate file size (max 10MB)
        $max_size = 10 * MB_IN_BYTES;
        if (filesize($file) > $max_size) {
            $results['messages'][] = sprintf(
                __('File size exceeds maximum of %s', 'mobility-trailblazers'),
                size_format($max_size)
            );
            return $results;
        }
        
        // Open CSV file
        $handle = fopen($file, 'r');
        if (!$handle) {
            $results['messages'][] = __('Could not open CSV file', 'mobility-trailblazers');
            return $results;
        }
        
        // Detect and skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            // No BOM found, rewind to start
            rewind($handle);
        }
        // If BOM is found, we've already read past it, so don't rewind
        
        // Detect delimiter
        $delimiter = $this->detect_delimiter($file);
        
        // Read headers
        $headers = fgetcsv($handle, 0, $delimiter);
        if (!$headers) {
            fclose($handle);
            $results['messages'][] = __('CSV file appears to be empty', 'mobility-trailblazers');
            return $results;
        }
        
        // Clean headers (remove any remaining BOM, trim whitespace, normalize)
        $headers = array_map(function($header) {
            // Remove BOM if present
            $header = str_replace("\xEF\xBB\xBF", '', $header);
            // Trim whitespace
            $header = trim($header);
            // Normalize multiple spaces to single space
            $header = preg_replace('/\s+/', ' ', $header);
            return $header;
        }, $headers);
        
        // Collect all data rows
        $data = [];
        $row_number = 1;
        
        while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
            $row_number++;
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            // Create associative array from headers and row data
            if (count($headers) !== count($row)) {
                $results['errors']++;
                $results['error_details'][] = [
                    'row' => $row_number,
                    'error' => __('Column count mismatch', 'mobility-trailblazers')
                ];
                continue;
            }
            
            $row_data = array_combine($headers, $row);
            if ($row_data === false) {
                $results['errors']++;
                $results['error_details'][] = [
                    'row' => $row_number,
                    'error' => __('Failed to parse row', 'mobility-trailblazers')
                ];
                continue;
            }
            
            $row_data['_row_number'] = $row_number;
            $data[] = $row_data;
        }
        
        fclose($handle);
        
        // Process in batches for large datasets
        if (count($data) > $batch_size) {
            $results = $this->process_in_batches($data, $import_type, $update_existing, $batch_size, $results);
        } else {
            // Process normally for small datasets
            if ($import_type === 'jury_members') {
                $results = $this->import_jury_members($data, $update_existing, $results);
            } elseif ($import_type === 'candidates') {
                $results = $this->import_candidates($data, $update_existing, $results);
            } else {
                $results['messages'][] = sprintf(
                    __('Invalid import type: %s', 'mobility-trailblazers'),
                    $import_type
                );
            }
        }
        
        // Log import completion
        MT_Logger::info('CSV import completed', [
            'type' => $import_type,
            'file' => basename($file),
            'results' => [
                'success' => $results['success'],
                'updated' => $results['updated'],
                'skipped' => $results['skipped'],
                'errors' => $results['errors']
            ]
        ]);
        
        return $results;
    }
    
    /**
     * Process data in batches for better performance
     *
     * @param array $data All data rows
     * @param string $import_type Type of import
     * @param bool $update_existing Whether to update existing records
     * @param int $batch_size Size of each batch
     * @param array $results Initial results array
     * @return array Updated results
     * @since 2.2.28
     */
    private function process_in_batches($data, $import_type, $update_existing, $batch_size, $results) {
        $batches = array_chunk($data, $batch_size);
        $total_batches = count($batches);
        $current_batch = 0;
        
        // Store progress in transient for AJAX polling
        $progress_key = 'mt_import_progress_' . get_current_user_id();
        
        foreach ($batches as $batch) {
            $current_batch++;
            
            // Update progress
            $progress = [
                'current_batch' => $current_batch,
                'total_batches' => $total_batches,
                'percentage' => round(($current_batch / $total_batches) * 100),
                'message' => sprintf(
                    __('Processing batch %d of %d...', 'mobility-trailblazers'),
                    $current_batch,
                    $total_batches
                )
            ];
            set_transient($progress_key, $progress, 300);
            
            // Process batch based on type
            if ($import_type === 'jury_members') {
                $results = $this->import_jury_members($batch, $update_existing, $results);
            } elseif ($import_type === 'candidates') {
                $results = $this->import_candidates($batch, $update_existing, $results);
            }
            
            // Check for timeout or user abort
            if (connection_aborted()) {
                $results['messages'][] = __('Import aborted by user', 'mobility-trailblazers');
                break;
            }
            
            // Prevent PHP timeout on large imports
            if (function_exists('set_time_limit')) {
                set_time_limit(30);
            }
        }
        
        // Clear progress transient
        delete_transient($progress_key);
        
        return $results;
    }
    
    /**
     * Import jury members from CSV data
     *
     * @param array $data CSV data rows
     * @param bool $update_existing Whether to update existing records
     * @param array $results Results array to update
     * @return array Updated results
     */
    private function import_jury_members($data, $update_existing, $results) {
        foreach ($data as $row) {
            $row_number = $row['_row_number'];
            
            try {
                // Map fields
                $mapped_data = $this->map_fields($row, self::JURY_FIELD_MAPPING);
                
                // Validate required fields
                $required = ['name', 'email', 'organization'];
                foreach ($required as $field) {
                    if (empty($mapped_data[$field])) {
                        throw new \Exception(sprintf(
                            __('Missing required field: %s', 'mobility-trailblazers'),
                            $field
                        ));
                    }
                }
                
                // Validate email
                if (!is_email($mapped_data['email'])) {
                    throw new \Exception(__('Invalid email address', 'mobility-trailblazers'));
                }
                
                // Check if user exists
                $existing_user = get_user_by('email', $mapped_data['email']);
                
                if ($existing_user && !$update_existing) {
                    $results['skipped']++;
                    continue;
                }
                
                // Create or update WordPress user
                if ($existing_user) {
                    $user_id = $existing_user->ID;
                    
                    // Update user details
                    wp_update_user([
                        'ID' => $user_id,
                        'display_name' => $mapped_data['name'],
                        'first_name' => $this->get_first_name($mapped_data['name']),
                        'last_name' => $this->get_last_name($mapped_data['name'])
                    ]);
                    
                    $results['updated']++;
                } else {
                    // Generate username from name
                    $username = $this->generate_username($mapped_data['name']);
                    $password = wp_generate_password(12, true, true);
                    
                    $user_id = wp_create_user($username, $password, $mapped_data['email']);
                    
                    if (is_wp_error($user_id)) {
                        throw new \Exception($user_id->get_error_message());
                    }
                    
                    // Update user details
                    wp_update_user([
                        'ID' => $user_id,
                        'display_name' => $mapped_data['name'],
                        'first_name' => $this->get_first_name($mapped_data['name']),
                        'last_name' => $this->get_last_name($mapped_data['name'])
                    ]);
                    
                    // Send welcome email
                    wp_new_user_notification($user_id, null, 'both');
                    
                    $results['success']++;
                }
                
                // Set user role
                $user = new \WP_User($user_id);
                $user->set_role('mt_jury_member');
                
                // Update user meta
                update_user_meta($user_id, 'mt_organization', $mapped_data['organization']);
                update_user_meta($user_id, 'mt_title', $mapped_data['title']);
                update_user_meta($user_id, 'mt_jury_member', 'yes');
                
                // Set role flags (president/vice_president)
                if (!empty($mapped_data['role'])) {
                    $role_lower = strtolower($mapped_data['role']);
                    if (strpos($role_lower, 'president') !== false) {
                        update_user_meta($user_id, 'mt_is_president', 'yes');
                    }
                    if (strpos($role_lower, 'vice') !== false) {
                        update_user_meta($user_id, 'mt_is_vice_president', 'yes');
                    }
                }
                
                // Create or update jury member post
                $this->create_jury_member_post($user_id, $mapped_data);
                
                // Create initial assignments (optional - can be done separately)
                if (apply_filters('mt_create_initial_assignments', false)) {
                    $this->create_initial_assignments($user_id);
                }
                
            } catch (\Exception $e) {
                $results['errors']++;
                $results['error_details'][] = [
                    'row' => $row_number,
                    'error' => $e->getMessage()
                ];
                
                MT_Logger::error('Jury member import failed', [
                    'row' => $row_number,
                    'error' => $e->getMessage(),
                    'data' => $row
                ]);
            }
        }
        
        return $results;
    }
    
    /**
     * Import candidates from CSV data
     *
     * @param array $data CSV data rows
     * @param bool $update_existing Whether to update existing records
     * @param array $results Results array to update
     * @return array Updated results
     */
    private function import_candidates($data, $update_existing, $results) {
        foreach ($data as $row) {
            $row_number = $row['_row_number'];
            
            try {
                // Map fields
                $mapped_data = $this->map_fields($row, self::CANDIDATE_FIELD_MAPPING);
                
                // Validate required fields
                $required = ['name', 'organisation', 'category'];
                foreach ($required as $field) {
                    if (empty($mapped_data[$field])) {
                        throw new \Exception(sprintf(
                            __('Missing required field: %s', 'mobility-trailblazers'),
                            $field
                        ));
                    }
                }
                
                // Check if candidate exists
                $existing_post = null;
                
                // First check by import ID if provided
                if (!empty($mapped_data['import_id'])) {
                    $query = new \WP_Query([
                        'post_type' => 'mt_candidate',
                        'meta_key' => '_mt_candidate_id',
                        'meta_value' => $mapped_data['import_id'],
                        'posts_per_page' => 1
                    ]);
                    
                    if ($query->have_posts()) {
                        $existing_post = $query->posts[0];
                    }
                }
                
                // If not found by ID, check by name
                if (!$existing_post) {
                    $query = new \WP_Query([
                        'post_type' => 'mt_candidate',
                        'meta_key' => '_mt_candidate_name',
                        'meta_value' => $mapped_data['name'],
                        'posts_per_page' => 1
                    ]);
                    
                    if ($query->have_posts()) {
                        $existing_post = $query->posts[0];
                    }
                }
                
                if ($existing_post && !$update_existing) {
                    $results['skipped']++;
                    continue;
                }
                
                // Prepare post data
                $post_data = [
                    'post_title' => $mapped_data['name'],
                    'post_type' => 'mt_candidate',
                    'post_status' => 'publish',
                    'post_content' => $mapped_data['description']
                ];
                
                // Create or update post
                if ($existing_post) {
                    $post_data['ID'] = $existing_post->ID;
                    $post_id = wp_update_post($post_data);
                    $results['updated']++;
                } else {
                    $post_id = wp_insert_post($post_data);
                    $results['success']++;
                }
                
                if (is_wp_error($post_id)) {
                    throw new \Exception($post_id->get_error_message());
                }
                
                // Set all meta fields
                $this->update_candidate_meta($post_id, $mapped_data);
                
                // Assign to categories
                $this->assign_candidate_category($post_id, $mapped_data['category']);
                
                // Parse and save evaluation criteria if present
                if (!empty($mapped_data['description'])) {
                    $this->save_evaluation_criteria($post_id, $mapped_data['description']);
                }
                
            } catch (\Exception $e) {
                $results['errors']++;
                $results['error_details'][] = [
                    'row' => $row_number,
                    'error' => $e->getMessage()
                ];
                
                MT_Logger::error('Candidate import failed', [
                    'row' => $row_number,
                    'error' => $e->getMessage(),
                    'data' => $row
                ]);
            }
        }
        
        return $results;
    }
    
    /**
     * Map CSV fields to internal field names
     *
     * @param array $row CSV row data
     * @param array $mapping Field mapping array
     * @return array Mapped data
     */
    private function map_fields($row, $mapping) {
        $mapped = [];
        
        foreach ($mapping as $csv_field => $internal_field) {
            // Try exact match first
            if (isset($row[$csv_field])) {
                $mapped[$internal_field] = $this->sanitize_csv_value(trim($row[$csv_field]));
                continue;
            }
            
            // Try case-insensitive match
            foreach ($row as $key => $value) {
                if (strcasecmp($key, $csv_field) === 0) {
                    $mapped[$internal_field] = $this->sanitize_csv_value(trim($value));
                    break;
                }
            }
            
            // If still not found, set empty string
            if (!isset($mapped[$internal_field])) {
                $mapped[$internal_field] = '';
            }
        }
        
        return $mapped;
    }
    
    /**
     * Sanitize CSV value to prevent formula injection
     *
     * @param string $value The value to sanitize
     * @return string Sanitized value
     * @since 2.5.38
     */
    private function sanitize_csv_value($value) {
        if (empty($value)) {
            return $value;
        }
        
        // Prevent formula injection by escaping potentially dangerous characters
        // Check if the value starts with =, +, -, @, tab, or carriage return
        if (preg_match('/^[\=\+\-\@\t\r]/', $value)) {
            // Prefix with single quote to neutralize formula execution
            $value = "'" . $value;
            
            // Log potential formula injection attempt
            MT_Logger::warning('Potential CSV formula injection detected and neutralized', [
                'original_value' => substr($value, 0, 50) // Log only first 50 chars for security
            ]);
        }
        
        // Also escape if it starts with a pipe character (used in DDE attacks)
        if (strpos($value, '|') === 0) {
            $value = "'" . $value;
        }
        
        return $value;
    }
    
    /**
     * Create or update jury member post
     *
     * @param int $user_id WordPress user ID
     * @param array $data Jury member data
     * @return int|WP_Error Post ID or error
     */
    private function create_jury_member_post($user_id, $data) {
        // Check if post already exists for this user
        $query = new \WP_Query([
            'post_type' => 'mt_jury_member',
            'meta_key' => '_mt_user_id',
            'meta_value' => $user_id,
            'posts_per_page' => 1
        ]);
        
        $post_data = [
            'post_title' => $data['name'],
            'post_type' => 'mt_jury_member',
            'post_status' => 'publish',
            'post_content' => ''
        ];
        
        if ($query->have_posts()) {
            $post_data['ID'] = $query->posts[0]->ID;
            $post_id = wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);
        }
        
        if (!is_wp_error($post_id)) {
            update_post_meta($post_id, '_mt_user_id', $user_id);
            update_post_meta($post_id, '_mt_jury_name', $data['name']);
            update_post_meta($post_id, '_mt_jury_email', $data['email']);
            update_post_meta($post_id, '_mt_jury_organization', $data['organization']);
            update_post_meta($post_id, '_mt_jury_title', $data['title']);
        }
        
        return $post_id;
    }
    
    /**
     * Create initial assignments for jury member
     *
     * @param int $user_id Jury member user ID
     * @return void
     */
    private function create_initial_assignments($user_id) {
        // Get assignment service
        $assignment_service = new MT_Assignment_Service();
        
        // Get random candidates for initial assignment
        $candidates = get_posts([
            'post_type' => 'mt_candidate',
            'posts_per_page' => 5, // Assign 5 candidates initially
            'orderby' => 'rand'
        ]);
        
        foreach ($candidates as $candidate) {
            $assignment_service->create_assignment($user_id, $candidate->ID);
        }
    }
    
    /**
     * Update candidate meta fields
     *
     * @param int $post_id Candidate post ID
     * @param array $data Candidate data
     * @return void
     */
    private function update_candidate_meta($post_id, $data) {
        // Standard meta fields
        $meta_fields = [
            'import_id' => '_mt_candidate_id',
            'name' => '_mt_candidate_name',
            'organisation' => '_mt_organization',
            'position' => '_mt_position',
            'category' => '_mt_category_type',
            'status' => '_mt_top_50_status',
            'description' => '_mt_description_full',
            'website' => '_mt_website_url',
            'linkedin' => '_mt_linkedin_url',
            'article' => '_mt_article_url'
        ];
        
        foreach ($meta_fields as $data_key => $meta_key) {
            if (isset($data[$data_key])) {
                $value = $data[$data_key];
                
                // Sanitize URLs
                if (strpos($meta_key, '_url') !== false) {
                    $value = esc_url_raw($value);
                }
                // Sanitize other text fields
                else {
                    $value = sanitize_text_field($value);
                }
                
                update_post_meta($post_id, $meta_key, $value);
            }
        }
    }
    
    /**
     * Assign candidate to category
     *
     * @param int $post_id Candidate post ID
     * @param string $category Category name
     * @return void
     */
    private function assign_candidate_category($post_id, $category) {
        if (empty($category)) {
            return;
        }
        
        // Normalize category name
        $category_map = [
            'startup' => 'Startup',
            'gov' => 'Government',
            'government' => 'Government',
            'tech' => 'Technology',
            'technology' => 'Technology'
        ];
        
        $category_lower = strtolower($category);
        $normalized_category = isset($category_map[$category_lower]) 
            ? $category_map[$category_lower] 
            : $category;
        
        // Check if category taxonomy exists
        if (taxonomy_exists('mt_candidate_category')) {
            // Get or create term
            $term = term_exists($normalized_category, 'mt_candidate_category');
            
            if (!$term) {
                $term = wp_insert_term($normalized_category, 'mt_candidate_category');
            }
            
            if (!is_wp_error($term)) {
                $term_id = is_array($term) ? $term['term_id'] : $term;
                wp_set_post_terms($post_id, [$term_id], 'mt_candidate_category');
            }
        }
    }
    
    /**
     * Save evaluation criteria to post meta
     *
     * @param int $post_id Candidate post ID
     * @param string $description Description containing criteria
     * @return void
     */
    private function save_evaluation_criteria($post_id, $description) {
        // Parse evaluation criteria from description
        $criteria = self::parse_evaluation_criteria($description);
        
        foreach ($criteria as $key => $value) {
            if (!empty($value)) {
                update_post_meta($post_id, $key, $value);
            }
        }
    }
    
    /**
     * Generate unique username from name
     *
     * @param string $name Full name
     * @return string Unique username
     */
    private function generate_username($name) {
        // Convert to lowercase and replace spaces with underscores
        $base = sanitize_user(strtolower(str_replace(' ', '_', $name)));
        
        // Remove special characters
        $base = preg_replace('/[^a-z0-9_]/', '', $base);
        
        // Add random suffix for security (prevents username enumeration)
        $random = wp_generate_password(4, false, false);
        $username = $base . '_' . strtolower($random);
        
        // Ensure uniqueness
        $original = $username;
        $counter = 1;
        
        while (username_exists($username)) {
            // Generate new random suffix if collision occurs
            $random = wp_generate_password(4, false, false);
            $username = $original . '_' . strtolower($random) . '_' . $counter;
            $counter++;
        }
        
        return $username;
    }
    
    /**
     * Get first name from full name
     *
     * @param string $name Full name
     * @return string First name
     */
    private function get_first_name($name) {
        $parts = explode(' ', $name);
        return isset($parts[0]) ? $parts[0] : '';
    }
    
    /**
     * Get last name from full name
     *
     * @param string $name Full name
     * @return string Last name
     */
    private function get_last_name($name) {
        $parts = explode(' ', $name);
        if (count($parts) > 1) {
            array_shift($parts);
            return implode(' ', $parts);
        }
        return '';
    }
    
    /**
     * Parse evaluation criteria from German description text
     * Moved from MT_Enhanced_Profile_Importer for consolidation
     *
     * @param string $description Description text containing evaluation criteria
     * @return array Parsed criteria with scores
     * @since 2.2.24
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
        
        // Use a more robust approach - split by known labels
        // This regex splits the text by any of the known section headers
        $split_pattern = '/(Mut\s*&\s*Pioniergeist\s*:|Innovationsgrad\s*:|Umsetzungskraft\s*&\s*Wirkung\s*:|Relevanz\s*für\s*die\s*Mobilitätswende\s*:|Vorbildfunktion\s*&\s*Sichtbarkeit\s*:|Persönlichkeit\s*&\s*Motivation\s*:)/u';
        
        $sections = preg_split($split_pattern, $description, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        
        // Map labels to field names
        $label_map = [
            'Mut & Pioniergeist' => '_mt_evaluation_courage',
            'Innovationsgrad' => '_mt_evaluation_innovation',
            'Umsetzungskraft & Wirkung' => '_mt_evaluation_implementation',
            'Relevanz für die Mobilitätswende' => '_mt_evaluation_relevance',
            'Vorbildfunktion & Sichtbarkeit' => '_mt_evaluation_visibility',
            'Persönlichkeit & Motivation' => '_mt_evaluation_personality'
        ];
        
        // Process sections - labels and their content alternate
        for ($i = 0; $i < count($sections); $i++) {
            $section = trim($sections[$i]);
            
            // Check if this section is a label
            foreach ($label_map as $label_text => $field_name) {
                // Check if section contains the label (ignoring colons and extra spaces)
                $clean_section = preg_replace('/\s+/', ' ', rtrim($section, ':'));
                $clean_label = preg_replace('/\s+/', ' ', $label_text);
                
                if (stripos($clean_section, $clean_label) !== false) {
                    // The next section should be the content for this label
                    if (isset($sections[$i + 1])) {
                        $content = trim($sections[$i + 1]);
                        // Clean up the content
                        $content = preg_replace('/\s+/', ' ', $content);
                        $content = trim($content, " \t\n\r\0\x0B.,;:");
                        $criteria[$field_name] = $content;
                    }
                    break;
                }
            }
        }
        
        return $criteria;
    }
    
    /**
     * Detect CSV delimiter from file
     *
     * @param string $file Path to CSV file
     * @return string Detected delimiter (default: ',')
     * @since 2.2.28
     */
    private function detect_delimiter($file) {
        $handle = fopen($file, 'r');
        if (!$handle) {
            return ',';
        }
        
        // Read first line
        $first_line = fgets($handle);
        fclose($handle);
        
        if (!$first_line) {
            return ',';
        }
        
        // Remove BOM if present
        $first_line = str_replace("\xEF\xBB\xBF", '', $first_line);
        
        // Count occurrences of common delimiters
        $delimiters = [',', ';', "\t", '|'];
        $counts = [];
        
        foreach ($delimiters as $delimiter) {
            $counts[$delimiter] = substr_count($first_line, $delimiter);
        }
        
        // Return delimiter with highest count
        arsort($counts);
        $detected = key($counts);
        
        return $counts[$detected] > 0 ? $detected : ',';
    }
}
