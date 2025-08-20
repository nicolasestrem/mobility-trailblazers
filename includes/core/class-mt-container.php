<?php
/**
 * Dependency Injection Container
 *
 * Simple service container for managing dependencies
 * Compatible with WordPress patterns and practices
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
 * Class MT_Container
 *
 * Lightweight dependency injection container
 * Manages service registration and resolution
 */
class MT_Container {
    
    /**
     * Container instance
     *
     * @var MT_Container|null
     */
    private static $instance = null;
    
    /**
     * Registered bindings
     *
     * @var array
     */
    private $bindings = [];
    
    /**
     * Resolved instances (singletons)
     *
     * @var array
     */
    private $instances = [];
    
    /**
     * Get container instance
     *
     * @return MT_Container
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor (singleton pattern)
     */
    private function __construct() {
        // Private constructor to enforce singleton
    }
    
    /**
     * Bind an abstract to a concrete implementation
     *
     * @param string $abstract Abstract class or interface name
     * @param mixed $concrete Concrete implementation (class name or closure)
     * @param bool $shared Whether to share the instance (singleton)
     * @return void
     */
    public function bind($abstract, $concrete = null, $shared = false) {
        // If no concrete provided, assume abstract is the concrete
        if (null === $concrete) {
            $concrete = $abstract;
        }
        
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared
        ];
    }
    
    /**
     * Register a shared binding (singleton)
     *
     * @param string $abstract Abstract class or interface name
     * @param mixed $concrete Concrete implementation
     * @return void
     */
    public function singleton($abstract, $concrete = null) {
        $this->bind($abstract, $concrete, true);
    }
    
    /**
     * Resolve a service from the container
     *
     * @param string $abstract Abstract to resolve
     * @return mixed Resolved instance
     * @throws \Exception If unable to resolve
     */
    public function make($abstract) {
        // If it's a shared instance and already resolved, return it
        if ($this->is_shared($abstract) && isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        
        // Build the instance
        $instance = $this->build($abstract);
        
        // If it's shared, store it
        if ($this->is_shared($abstract)) {
            $this->instances[$abstract] = $instance;
        }
        
        return $instance;
    }
    
    /**
     * Alias for make method
     *
     * @param string $abstract Abstract to resolve
     * @return mixed Resolved instance
     */
    public function get($abstract) {
        return $this->make($abstract);
    }
    
    /**
     * Check if a binding exists
     *
     * @param string $abstract Abstract to check
     * @return bool
     */
    public function has($abstract) {
        return isset($this->bindings[$abstract]);
    }
    
    /**
     * Build an instance
     *
     * @param string $abstract Abstract to build
     * @return mixed Built instance
     * @throws \Exception If unable to build
     */
    private function build($abstract) {
        // Check if we have a binding
        if (!isset($this->bindings[$abstract])) {
            // Try to instantiate directly if it's a class
            if (class_exists($abstract)) {
                return $this->instantiate($abstract);
            }
            
            throw new \Exception("Unable to resolve {$abstract} from container");
        }
        
        $binding = $this->bindings[$abstract];
        $concrete = $binding['concrete'];
        
        // If concrete is a closure, execute it
        if ($concrete instanceof \Closure) {
            return $concrete($this);
        }
        
        // If it's a class name, instantiate it
        if (is_string($concrete)) {
            return $this->instantiate($concrete);
        }
        
        // Return as-is (could be an object)
        return $concrete;
    }
    
    /**
     * Instantiate a class with automatic dependency injection
     *
     * @param string $class Class name to instantiate
     * @return object Instance
     * @throws \Exception If unable to instantiate
     */
    private function instantiate($class) {
        if (!class_exists($class)) {
            throw new \Exception("Class {$class} does not exist");
        }
        
        $reflection = new \ReflectionClass($class);
        
        // If no constructor, just create instance
        $constructor = $reflection->getConstructor();
        if (null === $constructor) {
            return new $class();
        }
        
        // Get constructor parameters
        $parameters = $constructor->getParameters();
        if (empty($parameters)) {
            return new $class();
        }
        
        // Resolve dependencies
        $dependencies = [];
        foreach ($parameters as $parameter) {
            $dependencies[] = $this->resolve_parameter($parameter);
        }
        
        return $reflection->newInstanceArgs($dependencies);
    }
    
    /**
     * Resolve a constructor parameter
     *
     * @param \ReflectionParameter $parameter Parameter to resolve
     * @return mixed Resolved value
     * @throws \Exception If unable to resolve
     */
    private function resolve_parameter(\ReflectionParameter $parameter) {
        $type = $parameter->getType();
        
        // If no type hint, check for default value
        if (null === $type) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }
            return null;
        }
        
        // Get the type name
        $type_name = $type->getName();
        
        // Skip built-in types
        if ($type->isBuiltin()) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }
            return null;
        }
        
        // Try to resolve from container
        try {
            return $this->make($type_name);
        } catch (\Exception $e) {
            // If we can't resolve and there's a default, use it
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }
            
            // If the type allows null, return null
            if ($type->allowsNull()) {
                return null;
            }
            
            throw new \Exception("Unable to resolve parameter {$parameter->getName()} of type {$type_name}");
        }
    }
    
    /**
     * Check if a binding is shared (singleton)
     *
     * @param string $abstract Abstract to check
     * @return bool
     */
    private function is_shared($abstract) {
        return isset($this->bindings[$abstract]) && 
               $this->bindings[$abstract]['shared'] === true;
    }
    
    /**
     * Clear all bindings and instances
     * Useful for testing
     *
     * @return void
     */
    public function flush() {
        $this->bindings = [];
        $this->instances = [];
    }
    
    /**
     * Register a service provider
     *
     * Service providers are used to bootstrap services and dependencies
     * following WordPress plugin architecture patterns.
     *
     * @since 2.6.0
     *
     * @param MT_Service_Provider|string $provider Service provider instance or fully-qualified class name
     * @return void
     * @throws \Exception If provider class is invalid
     */
    public function register_provider($provider) {
        if (is_string($provider)) {
            // Security validation: Check if class exists
            if (!class_exists($provider)) {
                throw new \Exception(
                    sprintf(
                        /* translators: %s: provider class name */
                        __('Provider class %s does not exist', 'mobility-trailblazers'),
                        esc_html($provider)
                    )
                );
            }
            
            // Security validation: Check if class extends MT_Service_Provider
            if (!is_subclass_of($provider, MT_Service_Provider::class)) {
                throw new \Exception(
                    sprintf(
                        /* translators: %s: provider class name */
                        __('Provider %s must extend MT_Service_Provider', 'mobility-trailblazers'),
                        esc_html($provider)
                    )
                );
            }
            
            try {
                $provider = new $provider($this);
            } catch (\Throwable $e) {
                throw new \Exception(
                    sprintf(
                        /* translators: 1: provider class name, 2: error message */
                        __('Failed to instantiate provider %1$s: %2$s', 'mobility-trailblazers'),
                        esc_html($provider),
                        esc_html($e->getMessage())
                    )
                );
            }
        }
        
        if (!($provider instanceof MT_Service_Provider)) {
            throw new \Exception(
                __('Provider must be an instance of MT_Service_Provider or a valid class name', 'mobility-trailblazers')
            );
        }
        
        $provider->register();
        
        // Call boot method if it exists
        if (method_exists($provider, 'boot')) {
            $provider->boot();
        }
    }
}