<?php
// GPL 2.0 or later. See LICENSE. Copyright (c) 2025 Nicolas Estrem

/**
 * Repository Interface
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
 * Interface MT_Repository_Interface
 *
 * Defines the contract for repository classes
 */
interface MT_Repository_Interface {
    
    /**
     * Find a single record by ID
     *
     * @param int $id Record ID
     * @return object|null
     */
    public function find($id);
    
    /**
     * Find all records matching criteria
     *
     * @param array $args Query arguments
     * @return array
     */
    public function find_all($args = []);
    
    /**
     * Create a new record
     *
     * @param array $data Record data
     * @return int|false Insert ID on success, false on failure
     */
    public function create($data);
    
    /**
     * Update an existing record
     *
     * @param int $id Record ID
     * @param array $data Updated data
     * @return bool True on success, false on failure
     */
    public function update($id, $data);
    
    /**
     * Delete a record
     *
     * @param int $id Record ID
     * @return bool True on success, false on failure
     */
    public function delete($id);
} 
