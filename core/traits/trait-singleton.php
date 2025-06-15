<?php
/**
 * Singleton Trait
 *
 * @package MobilityTrailblazers
 * @subpackage Core\Traits
 */

namespace MobilityTrailblazers\Core\Traits;

/**
 * Singleton trait for ensuring single instance
 */
trait Singleton {
    
    /**
     * Instance of the class
     *
     * @var static
     */
    private static $instance = null;
    
    /**
     * Get instance of the class
     *
     * @return static
     */
    public static function get_instance() {
        if (null === static::$instance) {
            static::$instance = new static();
        }
        
        return static::$instance;
    }
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        // Override in child classes if needed
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {
        // Prevent cloning
    }
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new \Exception('Cannot unserialize singleton');
    }
} 