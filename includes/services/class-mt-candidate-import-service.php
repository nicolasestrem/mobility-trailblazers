<?php
/**
 * Candidate Import Service
 *
 * @package MobilityTrailblazers
 * @since 2.5.26
 */

namespace MobilityTrailblazers\Services;

use MobilityTrailblazers\Repositories\MT_Candidate_Repository;
use MobilityTrailblazers\Core\MT_Logger;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Candidate_Import_Service
 *
 * Handles importing candidates from Excel files
 */
class MT_Candidate_Import_Service {
    
    /**
     * Repository instance
     *
     * @var MT_Candidate_Repository
     */
    private $repository;
    
    /**
     * Import results
     *
     * @var array
     */
    private $results = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
        'photos_attached' => 0,
        'messages' => [],
        'candidates' => []
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->repository = new MT_Candidate_Repository();
    }
    
    /**
     * Import candidates from Excel file
     *
     * @param string $file_path Path to Excel file
     * @param bool $dry_run Whether to perform a dry run
     * @return array Import results
     */
    public function import_from_excel($file_path, $dry_run = false) {
        if (!file_exists($file_path)) {
            $this->results['messages'][] = __('Excel file not found', 'mobility-trailblazers') . ': ' . $file_path;
            return $this->results;
        }
        
        try {
            // Load the spreadsheet
            $reader = IOFactory::createReaderForFile($file_path);
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);
            $spreadsheet = $reader->load($file_path);
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Find the header row (looking for "Name" column)
            $headerRow = 0;
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            
            for ($row = 1; $row <= min(20, $highestRow); $row++) {
                $rowData = $worksheet->rangeToArray("A$row:$highestColumn$row", null, true, false)[0];
                foreach ($rowData as $value) {
                    if (!empty($value) && (stripos($value, 'name') !== false || stripos($value, 'Name') !== false)) {
                        $headerRow = $row;
                        break 2;
                    }
                }
            }
            
            if ($headerRow === 0) {
                $this->results['messages'][] = __('Could not find header row in Excel file', 'mobility-trailblazers');
                return $this->results;
            }
            
            // Get headers
            $headers = $worksheet->rangeToArray("A$headerRow:$highestColumn$headerRow", null, true, false)[0];
            $headers = array_map('trim', $headers);
            
            // Map headers to our field names
            $header_mapping = $this->get_header_mapping($headers);
            
            // Process data rows starting after header
            for ($rowNum = $headerRow + 1; $rowNum <= $highestRow; $rowNum++) {
                $row = $worksheet->rangeToArray("A$rowNum:$highestColumn$rowNum", null, true, false)[0];
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // Map row data to fields
                $candidate_data = $this->map_row_to_candidate($row, $header_mapping);
                
                if (empty($candidate_data['name'])) {
                    $this->results['messages'][] = sprintf(
                        __('Row %d: Missing candidate name, skipping', 'mobility-trailblazers'),
                        $rowNum
                    );
                    $this->results['skipped']++;
                    continue;
                }
                
                // Parse German description sections
                if (!empty($candidate_data['description'])) {
                    $candidate_data['description_sections'] = $this->parse_german_sections($candidate_data['description']);
                    unset($candidate_data['description']); // Remove raw description
                }
                
                // Generate slug
                $candidate_data['slug'] = sanitize_title($candidate_data['name']);
                
                if ($dry_run) {
                    // Dry run - just collect what would be done
                    $this->results['candidates'][] = [
                        'action' => 'would_create',
                        'name' => $candidate_data['name'],
                        'organization' => $candidate_data['organization'] ?? '',
                        'sections' => array_keys($candidate_data['description_sections'] ?? [])
                    ];
                    $this->results['created']++;
                } else {
                    // Real import - create or update candidate
                    $this->import_candidate($candidate_data);
                }
            }
            
        } catch (\Exception $e) {
            $this->results['messages'][] = __('Error reading Excel file', 'mobility-trailblazers') . ': ' . $e->getMessage();
            MT_Logger::error('Excel import failed', [
                'file_path' => $file_path,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        return $this->results;
    }
    
    /**
     * Get header mapping for Excel columns
     *
     * @param array $headers Excel headers
     * @return array Mapping of column index to field name
     */
    private function get_header_mapping($headers) {
        $mapping = [];
        
        $field_map = [
            'ID' => 'import_id',
            'Name' => 'name',
            'Organisation' => 'organization',
            'Organization' => 'organization',
            'Position' => 'position',
            'Country' => 'country',
            'Land' => 'country',
            'LinkedIn-Link' => 'linkedin_url',
            'LinkedIn' => 'linkedin_url',
            'Webseite' => 'website_url',
            'Website' => 'website_url',
            'Article' => 'article_url',
            'Artikel' => 'article_url',
            'Description' => 'description',
            'Beschreibung' => 'description',
            'Category' => 'category',
            'Kategorie' => 'category',
            'Status' => 'status'
        ];
        
        foreach ($headers as $index => $header) {
            if (empty($header)) continue;
            
            // Try exact match first
            if (isset($field_map[$header])) {
                $mapping[$index] = $field_map[$header];
                continue;
            }
            
            // Try case-insensitive match
            foreach ($field_map as $key => $field) {
                if (strcasecmp($header, $key) === 0) {
                    $mapping[$index] = $field;
                    break;
                }
            }
        }
        
        return $mapping;
    }
    
    /**
     * Map row data to candidate fields
     *
     * @param array $row Excel row data
     * @param array $mapping Column mapping
     * @return array Candidate data
     */
    private function map_row_to_candidate($row, $mapping) {
        $candidate = [];
        
        foreach ($mapping as $index => $field) {
            if (isset($row[$index]) && !empty($row[$index])) {
                $value = trim($row[$index]);
                
                // Clean up URLs
                if (strpos($field, '_url') !== false) {
                    $value = esc_url_raw($value);
                }
                
                $candidate[$field] = $value;
            }
        }
        
        return $candidate;
    }
    
    /**
     * Parse German description sections
     *
     * @param string $description Full description text
     * @return array Parsed sections
     */
    public function parse_german_sections($description) {
        $sections = [
            'ueberblick' => '',
            'mut_pioniergeist' => '',
            'innovationsgrad' => '',
            'umsetzungskraft_wirkung' => '',
            'relevanz_mobilitaetswende' => '',
            'vorbild_sichtbarkeit' => ''
        ];
        
        if (empty($description)) {
            return $sections;
        }
        
        // Preserve line breaks
        $description = str_replace(["\r\n", "\r"], "\n", $description);
        
        // Define patterns for each section
        $patterns = [
            'ueberblick' => '/(?:^|\n)(?:Überblick|Ueberblick)\s*:?\s*\n?(.*?)(?=\n(?:Mut\s*&|Innovationsgrad|Umsetzungs|Relevanz|Vorbild|Sichtbarkeit)|$)/isu',
            'mut_pioniergeist' => '/(?:^|\n)Mut\s*&\s*Pioniergeist\s*:?\s*\n?(.*?)(?=\n(?:Innovationsgrad|Umsetzungs|Relevanz|Vorbild|Sichtbarkeit)|$)/isu',
            'innovationsgrad' => '/(?:^|\n)Innovationsgrad\s*:?\s*\n?(.*?)(?=\n(?:Mut\s*&|Umsetzungs|Relevanz|Vorbild|Sichtbarkeit)|$)/isu',
            'umsetzungskraft_wirkung' => '/(?:^|\n)Umsetzungs(?:kraft|stärke)\s*(?:&\s*Wirkung)?\s*:?\s*\n?(.*?)(?=\n(?:Mut\s*&|Innovationsgrad|Relevanz|Vorbild|Sichtbarkeit)|$)/isu',
            'relevanz_mobilitaetswende' => '/(?:^|\n)Relevanz\s*(?:für\s*die\s*)?Mobilitätswende\s*:?\s*\n?(.*?)(?=\n(?:Mut\s*&|Innovationsgrad|Umsetzungs|Vorbild|Sichtbarkeit)|$)/isu',
            'vorbild_sichtbarkeit' => '/(?:^|\n)(?:Vorbildfunktion\s*&\s*Sichtbarkeit|Sichtbarkeit\s*&\s*Reichweite)\s*:?\s*\n?(.*?)$/isu'
        ];
        
        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $description, $matches)) {
                $content = trim($matches[1]);
                // Preserve paragraphs but clean up excessive whitespace
                $content = preg_replace('/\n{3,}/', "\n\n", $content);
                $content = trim($content);
                
                if (!empty($content)) {
                    $sections[$key] = $content;
                }
            }
        }
        
        // If no sections were parsed, check if there's an overview section
        $all_empty = true;
        foreach ($sections as $content) {
            if (!empty($content)) {
                $all_empty = false;
                break;
            }
        }
        
        // If all sections are empty and we have content, put it in overview
        if ($all_empty && !empty($description)) {
            // Check if description doesn't contain any section headers
            $has_headers = preg_match('/(?:Überblick|Mut\s*&\s*Pioniergeist|Innovationsgrad|Umsetzungs|Relevanz|Vorbild|Sichtbarkeit)/iu', $description);
            if (!$has_headers) {
                $sections['ueberblick'] = trim($description);
            }
        }
        
        return $sections;
    }
    
    /**
     * Import a single candidate
     *
     * @param array $data Candidate data
     * @return bool Success
     */
    private function import_candidate($data) {
        // Check if candidate exists
        $existing = $this->repository->find_by_name($data['name']);
        
        if ($existing) {
            // Update existing
            $result = $this->repository->update($existing->id, $data);
            
            if ($result) {
                $this->results['updated']++;
                $this->results['candidates'][] = [
                    'action' => 'updated',
                    'name' => $data['name'],
                    'id' => $existing->id
                ];
            } else {
                $this->results['errors']++;
                $this->results['messages'][] = sprintf(
                    __('Failed to update candidate: %s', 'mobility-trailblazers'),
                    $data['name']
                );
            }
        } else {
            // Create new
            // Also create WordPress post
            $post_id = $this->create_candidate_post($data);
            if ($post_id) {
                $data['post_id'] = $post_id;
            }
            
            $id = $this->repository->create($data);
            
            if ($id) {
                $this->results['created']++;
                $this->results['candidates'][] = [
                    'action' => 'created',
                    'name' => $data['name'],
                    'id' => $id,
                    'post_id' => $post_id
                ];
            } else {
                $this->results['errors']++;
                $this->results['messages'][] = sprintf(
                    __('Failed to create candidate: %s', 'mobility-trailblazers'),
                    $data['name']
                );
            }
        }
        
        return true;
    }
    
    /**
     * Create WordPress post for candidate
     *
     * @param array $data Candidate data
     * @return int|false Post ID or false on failure
     */
    private function create_candidate_post($data) {
        $post_data = [
            'post_title' => $data['name'],
            'post_type' => 'mt_candidate',
            'post_status' => 'publish',
            'post_content' => ''
        ];
        
        $post_id = wp_insert_post($post_data);
        
        if (!is_wp_error($post_id)) {
            // Add meta fields for compatibility
            update_post_meta($post_id, '_mt_candidate_name', $data['name']);
            update_post_meta($post_id, '_mt_organization', $data['organization'] ?? '');
            update_post_meta($post_id, '_mt_position', $data['position'] ?? '');
            update_post_meta($post_id, '_mt_linkedin_url', $data['linkedin_url'] ?? '');
            update_post_meta($post_id, '_mt_website_url', $data['website_url'] ?? '');
            
            // Store sections in meta for backward compatibility
            if (!empty($data['description_sections'])) {
                $sections = $data['description_sections'];
                
                // Store combined overview
                if (!empty($sections['ueberblick'])) {
                    update_post_meta($post_id, '_mt_overview', $sections['ueberblick']);
                }
                
                // Store evaluation criteria as formatted text with proper headers
                $criteria_text = '';
                if (!empty($sections['mut_pioniergeist'])) {
                    $criteria_text .= "**Mut & Pioniergeist:**\n" . $sections['mut_pioniergeist'] . "\n\n";
                    update_post_meta($post_id, '_mt_criterion_courage', $sections['mut_pioniergeist']);
                }
                if (!empty($sections['innovationsgrad'])) {
                    $criteria_text .= "**Innovationsgrad:**\n" . $sections['innovationsgrad'] . "\n\n";
                    update_post_meta($post_id, '_mt_criterion_innovation', $sections['innovationsgrad']);
                }
                if (!empty($sections['umsetzungskraft_wirkung'])) {
                    $criteria_text .= "**Umsetzungskraft & Wirkung:**\n" . $sections['umsetzungskraft_wirkung'] . "\n\n";
                    update_post_meta($post_id, '_mt_criterion_implementation', $sections['umsetzungskraft_wirkung']);
                }
                if (!empty($sections['relevanz_mobilitaetswende'])) {
                    $criteria_text .= "**Relevanz für die Mobilitätswende:**\n" . $sections['relevanz_mobilitaetswende'] . "\n\n";
                    update_post_meta($post_id, '_mt_criterion_relevance', $sections['relevanz_mobilitaetswende']);
                }
                if (!empty($sections['vorbild_sichtbarkeit'])) {
                    $criteria_text .= "**Vorbildfunktion & Sichtbarkeit:**\n" . $sections['vorbild_sichtbarkeit'];
                    update_post_meta($post_id, '_mt_criterion_visibility', $sections['vorbild_sichtbarkeit']);
                }
                
                if (!empty($criteria_text)) {
                    update_post_meta($post_id, '_mt_evaluation_criteria', trim($criteria_text));
                }
            }
            
            return $post_id;
        }
        
        return false;
    }
    
    /**
     * Backup existing candidates to CSV
     *
     * @return string|false Path to backup file or false on failure
     */
    public function backup_existing_candidates() {
        $candidates = $this->repository->find_all();
        
        if (empty($candidates)) {
            return false;
        }
        
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/mt-backups';
        
        if (!is_dir($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }
        
        $filename = 'candidates_backup_' . date('Y-m-d_H-i-s') . '.csv';
        $file_path = $backup_dir . '/' . $filename;
        
        $handle = fopen($file_path, 'w');
        if (!$handle) {
            return false;
        }
        
        // Write UTF-8 BOM
        fwrite($handle, "\xEF\xBB\xBF");
        
        // Write headers
        $headers = [
            __('ID', 'mobility-trailblazers'), 
            __('Name', 'mobility-trailblazers'), 
            __('Organization', 'mobility-trailblazers'), 
            __('Position', 'mobility-trailblazers'), 
            __('Country', 'mobility-trailblazers'),
            'LinkedIn', 
            __('Website', 'mobility-trailblazers'), 
            __('Article', 'mobility-trailblazers'),
            'Überblick', 'Mut & Pioniergeist', 'Innovationsgrad',
            'Umsetzungskraft & Wirkung', 'Relevanz für die Mobilitätswende',
            'Vorbildfunktion & Sichtbarkeit'
        ];
        fputcsv($handle, $headers);
        
        // Write data
        foreach ($candidates as $candidate) {
            $sections = $candidate->description_sections ?? [];
            
            $row = [
                $candidate->id,
                $candidate->name,
                $candidate->organization,
                $candidate->position,
                $candidate->country,
                $candidate->linkedin_url,
                $candidate->website_url,
                $candidate->article_url,
                $sections['ueberblick'] ?? '',
                $sections['mut_pioniergeist'] ?? '',
                $sections['innovationsgrad'] ?? '',
                $sections['umsetzungskraft_wirkung'] ?? '',
                $sections['relevanz_mobilitaetswende'] ?? '',
                $sections['vorbild_sichtbarkeit'] ?? ''
            ];
            
            fputcsv($handle, $row);
        }
        
        fclose($handle);
        
        MT_Logger::info('Candidates backed up successfully', [
            'file_path' => $file_path,
            'candidate_count' => count($candidates)
        ]);
        
        return $file_path;
    }
    
    /**
     * Truncate candidate data
     *
     * @return bool Success
     */
    public function truncate_candidate_data() {
        global $wpdb;
        
        // Delete all candidate posts
        $candidates = get_posts([
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);
        
        foreach ($candidates as $candidate) {
            wp_delete_post($candidate->ID, true);
        }
        
        // Delete related data
        $wpdb->query("DELETE FROM {$wpdb->prefix}mt_evaluations WHERE candidate_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'mt_candidate')");
        $wpdb->query("DELETE FROM {$wpdb->prefix}mt_jury_assignments WHERE candidate_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'mt_candidate')");
        
        // Truncate candidates table
        return $this->repository->truncate();
    }
    
    /**
     * Import candidate photos from directory
     *
     * @param string $photos_dir Path to photos directory
     * @param bool $dry_run Whether to perform a dry run
     * @return array Results
     */
    public function import_candidate_photos($photos_dir, $dry_run = false) {
        // Handle different path formats using WordPress functions
        if (!is_dir($photos_dir)) {
            // Try to resolve the path relative to the plugin directory
            $plugin_dir = plugin_dir_path(dirname(dirname(__FILE__)));
            $relative_path = str_replace($plugin_dir, '', $photos_dir);
            $resolved_path = $plugin_dir . trim($relative_path, '/\\');
            
            if (is_dir($resolved_path)) {
                $photos_dir = $resolved_path;
            } else {
                // Try upload directory
                $upload_dir = wp_upload_dir();
                $upload_path = $upload_dir['basedir'] . '/mobility-trailblazers/' . basename($photos_dir);
                
                if (is_dir($upload_path)) {
                    $photos_dir = $upload_path;
                } else {
                    $this->results['messages'][] = __('Photos directory not found', 'mobility-trailblazers') . ': ' . $photos_dir;
                    return $this->results;
                }
            }
        }
        
        // Get all WebP files
        $photos = glob($photos_dir . '/*.webp');
        
        if (empty($photos)) {
            $this->results['messages'][] = __('No WebP photos found in directory', 'mobility-trailblazers');
            return $this->results;
        }
        
        // Load WordPress media functions
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        foreach ($photos as $photo_path) {
            $filename = basename($photo_path);
            $candidate_name = $this->match_photo_to_candidate($filename);
            
            if (!$candidate_name) {
                $this->results['messages'][] = sprintf(
                    __('Could not match photo to candidate: %s', 'mobility-trailblazers'),
                    $filename
                );
                continue;
            }
            
            // Find candidate
            $candidate = $this->repository->find_by_name($candidate_name);
            if (!$candidate) {
                $this->results['messages'][] = sprintf(
                    __('Candidate not found for photo: %s', 'mobility-trailblazers'),
                    $candidate_name
                );
                continue;
            }
            
            if ($dry_run) {
                $this->results['messages'][] = sprintf(
                    __('Would attach photo %s to candidate %s', 'mobility-trailblazers'),
                    $filename,
                    $candidate_name
                );
                $this->results['photos_attached']++;
            } else {
                // Upload photo
                $attachment_id = $this->upload_photo($photo_path, $candidate);
                
                if ($attachment_id) {
                    // Update candidate with photo
                    $this->repository->update($candidate->id, [
                        'photo_attachment_id' => $attachment_id
                    ]);
                    
                    // Set as featured image if post exists
                    if ($candidate->post_id) {
                        set_post_thumbnail($candidate->post_id, $attachment_id);
                    }
                    
                    $this->results['photos_attached']++;
                    $this->results['messages'][] = sprintf(
                        __('Attached photo to candidate: %s', 'mobility-trailblazers'),
                        $candidate_name
                    );
                }
            }
        }
        
        return $this->results;
    }
    
    /**
     * Match photo filename to candidate name
     *
     * @param string $filename Photo filename
     * @return string|null Candidate name or null if not matched
     */
    private function match_photo_to_candidate($filename) {
        // Remove extension
        $name = pathinfo($filename, PATHINFO_FILENAME);
        
        // Name mappings for special cases
        $mappings = [
            'AlexanderMöller' => 'Alexander Möller',
            'AndréSchwämmlein' => 'André Schwämmlein',
            'AnjesTjarks' => 'Anjes Tjarks',
            'Anna-TheresaKorbutt' => 'Anna-Theresa Korbutt',
            'BenediktMiddendorf' => 'Benedikt Middendorf',
            'BjörnBender' => 'Björn Bender',
            'ChristianDahlheim' => 'Dr. Christian Dahlheim',
            'CorsinSulser' => 'Dr. Corsin Sulser',
            'JanHegner' => 'Dr. Jan Hegner',
            'UweSchneidewind' => 'Prof. Dr. Uwe Schneidewind',
            // Add more mappings as needed
        ];
        
        if (isset($mappings[$name])) {
            return $mappings[$name];
        }
        
        // Add spaces before capital letters for CamelCase names
        $name = preg_replace('/([a-z])([A-Z])/', '$1 $2', $name);
        $name = preg_replace('/([A-Z])([A-Z][a-z])/', '$1 $2', $name);
        
        return $name;
    }
    
    /**
     * Upload photo to WordPress media library
     *
     * @param string $photo_path Path to photo file
     * @param object $candidate Candidate object
     * @return int|false Attachment ID or false on failure
     */
    private function upload_photo($photo_path, $candidate) {
        $upload_dir = wp_upload_dir();
        $filename = sanitize_file_name($candidate->name . '.webp');
        $target_path = $upload_dir['path'] . '/' . $filename;
        
        // Copy file to uploads directory
        if (!copy($photo_path, $target_path)) {
            return false;
        }
        
        // Create attachment
        $filetype = wp_check_filetype($filename, null);
        $attachment = [
            'post_mime_type' => $filetype['type'],
            'post_title'     => $candidate->name,
            'post_content'   => '',
            'post_status'    => 'inherit'
        ];
        
        // Insert attachment
        $attach_id = wp_insert_attachment($attachment, $target_path, $candidate->post_id);
        
        if (is_wp_error($attach_id)) {
            @unlink($target_path);
            return false;
        }
        
        // Generate attachment metadata
        $attach_data = wp_generate_attachment_metadata($attach_id, $target_path);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        return $attach_id;
    }
    
    /**
     * Get import results
     *
     * @return array
     */
    public function get_results() {
        return $this->results;
    }
}
