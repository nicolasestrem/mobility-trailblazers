<?php
/**
 * Diagnostic Service Interface
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
 * Interface MT_Diagnostic_Service_Interface
 *
 * Contract for diagnostic service implementations
 */
interface MT_Diagnostic_Service_Interface extends MT_Service_Interface {
    
    /**
     * Run database diagnostics
     *
     * @return array Diagnostic results
     */
    public function check_database_health();
    
    /**
     * Check system configuration
     *
     * @return array Configuration status
     */
    public function check_system_config();
    
    /**
     * Get error log entries
     *
     * @param int $limit Number of entries to retrieve
     * @return array Error log entries
     */
    public function get_error_logs($limit = 100);
    
    /**
     * Run full system diagnostic
     *
     * @return array Complete diagnostic report
     */
    public function run_full_diagnostic();
    
    /**
     * Clear diagnostic caches
     *
     * @return bool Success status
     */
    public function clear_diagnostic_cache();
}