<?php
// GPL 2.0 or later. See LICENSE. Copyright (c) 2025 Nicolas Estrem

/**
 * Service Interface
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

namespace MobilityTrailblazers\Interfaces;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface MT_Service_Interface
 *
 * Defines the contract for service classes
 */
interface MT_Service_Interface {
    
    /**
     * Process the main action
     *
     * @param array $data Input data
     * @return mixed Result of the operation
     */
    public function process($data);
    
    /**
     * Validate input data
     *
     * @param array $data Input data to validate
     * @return bool True if valid, false otherwise
     */
    public function validate($data);
    
    /**
     * Get validation errors
     *
     * @return array Array of error messages
     */
    public function get_errors();
} 
