<?php
/**
 * Base Service Provider
 *
 * Abstract base class for all service providers
 * Handles dependency registration and bootstrapping
 *
 * @package MobilityTrailblazers
 * @since 2.6.0
 */

namespace MobilityTrailblazers\Core;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract Class MT_Service_Provider
 *
 * Base class for registering services with the container
 */
abstract class MT_Service_Provider {
    
    /**
     * Container instance
     *
     * @var MT_Container
     */
    protected $container;
    
    /**
     * Constructor
     *
     * @param MT_Container $container Container instance
     */
    public function __construct(MT_Container $container) {
        $this->container = $container;
    }
    
    /**
     * Register services with the container
     * Must be implemented by child classes
     *
     * @return void
     */
    abstract public function register();
    
    /**
     * Bootstrap services after registration
     * Can be overridden by child classes
     *
     * @return void
     */
    public function boot() {
        // Override in child classes if needed
    }
    
    /**
     * Helper method to bind a service
     *
     * @param string $abstract Abstract name
     * @param mixed $concrete Concrete implementation
     * @param bool $singleton Whether to register as singleton
     * @return void
     */
    protected function bind($abstract, $concrete = null, $singleton = false) {
        if ($singleton) {
            $this->container->singleton($abstract, $concrete);
        } else {
            $this->container->bind($abstract, $concrete);
        }
    }
    
    /**
     * Helper method to register a singleton
     *
     * @param string $abstract Abstract name
     * @param mixed $concrete Concrete implementation
     * @return void
     */
    protected function singleton($abstract, $concrete = null) {
        $this->container->singleton($abstract, $concrete);
    }
}