<?php
/**
 * Hookable Interface
 *
 * @package MobilityTrailblazers
 * @subpackage Core\Interfaces
 */

namespace MobilityTrailblazers\Core\Interfaces;

/**
 * Interface for classes that can hook into WordPress
 */
interface Hookable {
    
    /**
     * Initialize hooks
     *
     * @return void
     */
    public function init_hooks();
} 