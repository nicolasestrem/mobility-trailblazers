<?php
/**
 * Registrable Interface
 *
 * @package MobilityTrailblazers
 * @subpackage Core\Interfaces
 */

namespace MobilityTrailblazers\Core\Interfaces;

/**
 * Interface for classes that can be registered
 */
interface Registrable {
    
    /**
     * Register the component with WordPress
     *
     * @return void
     */
    public function register();
} 