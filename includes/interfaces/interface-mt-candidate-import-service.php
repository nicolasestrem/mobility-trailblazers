<?php
/**
 * Candidate Import Service Interface
 *
 * @package MobilityTrailblazers
 * @since 2.6.0
 */

namespace MobilityTrailblazers\Interfaces;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface MT_Candidate_Import_Service_Interface
 *
 * Contract for candidate import service implementations
 */
interface MT_Candidate_Import_Service_Interface extends MT_Service_Interface {
    
    /**
     * Import candidates from CSV file
     *
     * @param string $file_path Path to CSV file
     * @param array $options Import options
     * @return array Import results
     */
    public function import_from_csv($file_path, $options = []);
    
    /**
     * Import candidates from Excel file
     *
     * @param string $file_path Path to Excel file
     * @param array $options Import options
     * @return array Import results
     */
    public function import_from_excel($file_path, $options = []);
    
    /**
     * Validate import file
     *
     * @param string $file_path Path to file
     * @param string $type File type (csv|excel)
     * @return array Validation results
     */
    public function validate_import_file($file_path, $type);
    
    /**
     * Map CSV columns to candidate fields
     *
     * @param array $headers CSV headers
     * @return array Column mapping
     */
    public function map_columns($headers);
    
    /**
     * Get import progress
     *
     * @param string $import_id Import session ID
     * @return array Progress information
     */
    public function get_import_progress($import_id);
    
    /**
     * Cancel import
     *
     * @param string $import_id Import session ID
     * @return bool Success status
     */
    public function cancel_import($import_id);
}