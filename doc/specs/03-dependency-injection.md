# Mobility Trailblazers - Dependency Injection Architecture Specification

**Version:** 1.0.0  
**Last Updated:** 2025-01-22  
**Status:** Complete

## Table of Contents
1. [Overview](#overview)
2. [Container Implementation](#container-implementation)
3. [Service Provider Pattern](#service-provider-pattern)
4. [Interface Definitions](#interface-definitions)
5. [Service Registration](#service-registration)
6. [Dependency Resolution](#dependency-resolution)
7. [Repository Pattern](#repository-pattern)
8. [Service Layer](#service-layer)
9. [Backward Compatibility](#backward-compatibility)
10. [Testing with DI](#testing-with-di)

## Overview

The Mobility Trailblazers plugin implements a modern dependency injection (DI) container following SOLID principles, providing loose coupling, testability, and maintainability.

### Design Principles
- **Inversion of Control (IoC)**: Dependencies injected, not created internally
- **Interface Segregation**: Small, focused interfaces
- **Single Responsibility**: Each class has one reason to change
- **Open/Closed**: Open for extension, closed for modification
- **Dependency Inversion**: Depend on abstractions, not concretions

## Container Implementation

### Core Container Class

```php
<?php
namespace MobilityTrailblazers\Core;

class MT_Container {
    /**
     * Container instance (Singleton)
     */
    private static $instance = null;
    
    /**
     * Registered bindings
     */
    private $bindings = [];
    
    /**
     * Shared instances (singletons)
     */
    private $instances = [];
    
    /**
     * Service providers
     */
    private $providers = [];
    
    /**
     * Get container instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Bind abstract to concrete implementation
     * 
     * @param string $abstract Interface or abstract class
     * @param mixed $concrete Concrete class or closure
     * @param bool $shared Whether to share instance (singleton)
     */
    public function bind($abstract, $concrete = null, $shared = false) {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }
        
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared
        ];
    }
    
    /**
     * Register singleton binding
     */
    public function singleton($abstract, $concrete = null) {
        $this->bind($abstract, $concrete, true);
    }
    
    /**
     * Resolve service from container
     * 
     * @param string $abstract
     * @return mixed
     * @throws \Exception
     */
    public function make($abstract) {
        // Return existing singleton if available
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        
        // Get concrete implementation
        $concrete = $this->get_concrete($abstract);
        
        // Build instance
        if ($concrete instanceof \Closure) {
            $object = $concrete($this);
        } else {
            $object = $this->build($concrete);
        }
        
        // Store singleton if shared
        if ($this->is_shared($abstract)) {
            $this->instances[$abstract] = $object;
        }
        
        return $object;
    }
    
    /**
     * Build class instance with dependency injection
     */
    private function build($concrete) {
        // Use reflection to inspect constructor
        $reflector = new \ReflectionClass($concrete);
        
        if (!$reflector->isInstantiable()) {
            throw new \Exception("Class {$concrete} is not instantiable");
        }
        
        $constructor = $reflector->getConstructor();
        
        if (is_null($constructor)) {
            return new $concrete;
        }
        
        $parameters = $constructor->getParameters();
        $dependencies = $this->resolve_dependencies($parameters);
        
        return $reflector->newInstanceArgs($dependencies);
    }
    
    /**
     * Resolve constructor dependencies
     */
    private function resolve_dependencies($parameters) {
        $dependencies = [];
        
        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();
            
            if ($dependency === null) {
                // Handle primitive types
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new \Exception("Cannot resolve parameter {$parameter->name}");
                }
            } else {
                // Recursively resolve class dependencies
                $dependencies[] = $this->make($dependency->name);
            }
        }
        
        return $dependencies;
    }
    
    /**
     * Register service provider
     */
    public function register_provider($provider) {
        if (is_string($provider)) {
            $provider = new $provider($this);
        }
        
        $provider->register();
        $this->providers[] = $provider;
    }
    
    /**
     * Boot all registered providers
     */
    public function boot() {
        foreach ($this->providers as $provider) {
            if (method_exists($provider, 'boot')) {
                $provider->boot();
            }
        }
    }
}
```

### Container Features

```yaml
Core Features:
  - Singleton pattern for container instance
  - Service binding (transient and singleton)
  - Automatic dependency resolution
  - Closure/factory support
  - Service provider registration
  - Recursive dependency injection
  
Advanced Features:
  - Constructor parameter inspection
  - Interface to concrete mapping
  - Lazy instantiation
  - Circular dependency detection
  - Method injection support
```

## Service Provider Pattern

### Abstract Service Provider

```php
<?php
namespace MobilityTrailblazers\Core;

abstract class MT_Service_Provider {
    /**
     * Container instance
     */
    protected $container;
    
    /**
     * Constructor
     */
    public function __construct(MT_Container $container) {
        $this->container = $container;
    }
    
    /**
     * Register services (must be implemented)
     */
    abstract public function register();
    
    /**
     * Bootstrap services (optional)
     */
    public function boot() {
        // Override in child classes if needed
    }
    
    /**
     * Helper: Bind service to container
     */
    protected function bind($abstract, $concrete = null, $singleton = false) {
        if ($singleton) {
            $this->container->singleton($abstract, $concrete);
        } else {
            $this->container->bind($abstract, $concrete);
        }
    }
    
    /**
     * Helper: Register singleton
     */
    protected function singleton($abstract, $concrete = null) {
        $this->container->singleton($abstract, $concrete);
    }
}
```

### Repository Provider Example

```php
<?php
namespace MobilityTrailblazers\Providers;

use MobilityTrailblazers\Core\MT_Service_Provider;

class MT_Repository_Provider extends MT_Service_Provider {
    /**
     * Register repository services
     */
    public function register() {
        // Evaluation Repository
        $this->singleton(
            'MobilityTrailblazers\Interfaces\MT_Evaluation_Repository_Interface',
            function($container) {
                return new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
            }
        );
        
        // Assignment Repository
        $this->singleton(
            'MobilityTrailblazers\Interfaces\MT_Assignment_Repository_Interface',
            function($container) {
                return new \MobilityTrailblazers\Repositories\MT_Assignment_Repository();
            }
        );
        
        // Candidate Repository
        $this->singleton(
            'MobilityTrailblazers\Interfaces\MT_Candidate_Repository_Interface',
            function($container) {
                return new \MobilityTrailblazers\Repositories\MT_Candidate_Repository();
            }
        );
        
        // Audit Log Repository
        $this->singleton(
            'MobilityTrailblazers\Interfaces\MT_Audit_Log_Repository_Interface',
            function($container) {
                return new \MobilityTrailblazers\Repositories\MT_Audit_Log_Repository();
            }
        );
    }
}
```

### Services Provider Example

```php
<?php
namespace MobilityTrailblazers\Providers;

class MT_Services_Provider extends MT_Service_Provider {
    /**
     * Register services with dependencies
     */
    public function register() {
        // Evaluation Service with dependencies
        $this->singleton(
            'MobilityTrailblazers\Services\MT_Evaluation_Service',
            function($container) {
                return new \MobilityTrailblazers\Services\MT_Evaluation_Service(
                    $container->make('MobilityTrailblazers\Interfaces\MT_Evaluation_Repository_Interface'),
                    $container->make('MobilityTrailblazers\Interfaces\MT_Assignment_Repository_Interface'),
                    $container->make('MobilityTrailblazers\Interfaces\MT_Audit_Log_Repository_Interface')
                );
            }
        );
        
        // Assignment Service
        $this->singleton(
            'MobilityTrailblazers\Services\MT_Assignment_Service',
            function($container) {
                return new \MobilityTrailblazers\Services\MT_Assignment_Service(
                    $container->make('MobilityTrailblazers\Interfaces\MT_Assignment_Repository_Interface'),
                    $container->make('MobilityTrailblazers\Interfaces\MT_Candidate_Repository_Interface'),
                    $container->make('MobilityTrailblazers\Interfaces\MT_Audit_Log_Repository_Interface')
                );
            }
        );
        
        // Import Service
        $this->singleton(
            'MobilityTrailblazers\Services\MT_Import_Service',
            function($container) {
                return new \MobilityTrailblazers\Services\MT_Import_Service(
                    $container->make('MobilityTrailblazers\Interfaces\MT_Candidate_Repository_Interface')
                );
            }
        );
    }
    
    /**
     * Bootstrap services after registration
     */
    public function boot() {
        // Register WordPress hooks for services
        $evaluation_service = $this->container->make('MobilityTrailblazers\Services\MT_Evaluation_Service');
        add_action('mt_evaluation_submitted', [$evaluation_service, 'handle_submission']);
    }
}
```

## Interface Definitions

### Base Interfaces

```php
<?php
namespace MobilityTrailblazers\Interfaces;

/**
 * Base service interface
 */
interface MT_Service_Interface {
    /**
     * Process main action
     * 
     * @param array $data Input data
     * @return mixed Result
     */
    public function process(array $data);
    
    /**
     * Validate input data
     * 
     * @param array $data
     * @return bool
     */
    public function validate(array $data): bool;
    
    /**
     * Get validation errors
     * 
     * @return array
     */
    public function get_errors(): array;
}

/**
 * Base repository interface
 */
interface MT_Repository_Interface {
    /**
     * Find single record by ID
     * 
     * @param int $id
     * @return object|null
     */
    public function find(int $id);
    
    /**
     * Find all records matching criteria
     * 
     * @param array $args Query arguments
     * @return array
     */
    public function find_all(array $args = []): array;
    
    /**
     * Create new record
     * 
     * @param array $data
     * @return int|false Insert ID or false
     */
    public function create(array $data);
    
    /**
     * Update existing record
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool;
    
    /**
     * Delete record
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;
}
```

### Specialized Interfaces

```php
<?php
namespace MobilityTrailblazers\Interfaces;

/**
 * Evaluation repository interface
 */
interface MT_Evaluation_Repository_Interface extends MT_Repository_Interface {
    /**
     * Find evaluation by jury member and candidate
     */
    public function find_by_jury_and_candidate(int $jury_id, int $candidate_id);
    
    /**
     * Get all evaluations for a jury member
     */
    public function get_by_jury_member(int $jury_id): array;
    
    /**
     * Get all evaluations for a candidate
     */
    public function get_by_candidate(int $candidate_id): array;
    
    /**
     * Calculate average score for candidate
     */
    public function calculate_average_score(int $candidate_id): float;
    
    /**
     * Get ranking of all candidates
     */
    public function get_rankings(array $filters = []): array;
}

/**
 * Assignment repository interface
 */
interface MT_Assignment_Repository_Interface extends MT_Repository_Interface {
    /**
     * Check if assignment exists
     */
    public function exists(int $jury_id, int $candidate_id): bool;
    
    /**
     * Get assignments for jury member
     */
    public function get_by_jury_member(int $jury_id): array;
    
    /**
     * Get unassigned candidates
     */
    public function get_unassigned_candidates(): array;
    
    /**
     * Bulk create assignments
     */
    public function bulk_create(array $assignments): int;
    
    /**
     * Get assignment statistics
     */
    public function get_statistics(): array;
}

/**
 * Evaluation service interface
 */
interface MT_Evaluation_Service_Interface extends MT_Service_Interface {
    /**
     * Save evaluation as draft
     */
    public function save_draft(array $data);
    
    /**
     * Submit final evaluation
     */
    public function submit_final(array $data);
    
    /**
     * Get evaluation criteria
     */
    public function get_criteria(): array;
    
    /**
     * Get jury member progress
     */
    public function get_jury_progress(int $jury_id): array;
    
    /**
     * Calculate weighted score
     */
    public function calculate_weighted_score(array $scores): float;
}

/**
 * Assignment service interface
 */
interface MT_Assignment_Service_Interface extends MT_Service_Interface {
    /**
     * Auto-assign candidates to jury
     */
    public function auto_assign(string $method = 'balanced', int $per_jury = 20): bool;
    
    /**
     * Manual assignment
     */
    public function assign_candidate(int $jury_id, int $candidate_id): bool;
    
    /**
     * Remove assignment
     */
    public function remove_assignment(int $jury_id, int $candidate_id): bool;
    
    /**
     * Rebalance assignments
     */
    public function rebalance_assignments(): bool;
    
    /**
     * Get assignment recommendations
     */
    public function get_recommendations(int $jury_id): array;
}
```

## Service Registration

### Bootstrap Process

```php
<?php
// In main plugin file (mobility-trailblazers.php)

// Initialize container early for AJAX
if (defined('DOING_AJAX') && DOING_AJAX) {
    $plugin = MobilityTrailblazers\Core\MT_Plugin::get_instance();
    $plugin->ensure_services_for_ajax();
}

// Full initialization on plugins_loaded
add_action('plugins_loaded', function() {
    $plugin = MobilityTrailblazers\Core\MT_Plugin::get_instance();
    $plugin->init();
}, 5);

// MT_Plugin class
class MT_Plugin {
    private static $instance = null;
    private $container;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        $this->container = MT_Container::get_instance();
        $this->register_services();
    }
    
    private function register_services() {
        // Register providers in order
        $providers = [
            'MobilityTrailblazers\Providers\MT_Core_Provider',
            'MobilityTrailblazers\Providers\MT_Repository_Provider',
            'MobilityTrailblazers\Providers\MT_Services_Provider',
            'MobilityTrailblazers\Providers\MT_Admin_Provider',
            'MobilityTrailblazers\Providers\MT_Ajax_Provider',
        ];
        
        foreach ($providers as $provider) {
            $this->container->register_provider($provider);
        }
    }
    
    public function init() {
        // Boot all providers
        $this->container->boot();
        
        // Initialize core components
        $this->init_post_types();
        $this->init_roles();
        $this->init_ajax();
        $this->init_admin();
    }
    
    public static function container() {
        return self::get_instance()->container;
    }
}
```

### Service Registration Order

```yaml
Registration Order:
  1. Core Services:
     - Logger
     - Cache
     - Config
     
  2. Data Layer:
     - Repositories
     - Query builders
     
  3. Business Logic:
     - Services
     - Validators
     
  4. Presentation:
     - Controllers
     - AJAX handlers
     
  5. Integration:
     - WordPress hooks
     - Third-party services
```

## Dependency Resolution

### Resolution Process

```php
<?php
// Example: Resolving a service with nested dependencies

// 1. Request service
$service = $container->make('MobilityTrailblazers\Services\MT_Evaluation_Service');

// 2. Container checks for existing instance (singleton)
// 3. If not found, examines constructor:
/*
public function __construct(
    MT_Evaluation_Repository_Interface $evaluation_repo,
    MT_Assignment_Repository_Interface $assignment_repo,
    MT_Audit_Log_Interface $audit_log
)
*/

// 4. Recursively resolves each dependency:
$evaluation_repo = $container->make('MT_Evaluation_Repository_Interface');
$assignment_repo = $container->make('MT_Assignment_Repository_Interface');
$audit_log = $container->make('MT_Audit_Log_Interface');

// 5. Creates service instance with dependencies
$service = new MT_Evaluation_Service($evaluation_repo, $assignment_repo, $audit_log);

// 6. Stores as singleton if configured
```

### Circular Dependency Prevention

```php
class MT_Container {
    private $resolving = [];
    
    public function make($abstract) {
        // Check for circular dependency
        if (isset($this->resolving[$abstract])) {
            throw new \Exception("Circular dependency detected: $abstract");
        }
        
        $this->resolving[$abstract] = true;
        
        try {
            $object = $this->resolve($abstract);
        } finally {
            unset($this->resolving[$abstract]);
        }
        
        return $object;
    }
}
```

## Repository Pattern

### Base Repository Implementation

```php
<?php
namespace MobilityTrailblazers\Repositories;

abstract class MT_Base_Repository implements MT_Repository_Interface {
    protected $table;
    protected $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    
    public function find(int $id) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE id = %d",
                $id
            )
        );
    }
    
    public function find_all(array $args = []): array {
        $defaults = [
            'limit' => 100,
            'offset' => 0,
            'orderby' => 'id',
            'order' => 'ASC'
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table} 
                ORDER BY {$args['orderby']} {$args['order']} 
                LIMIT %d OFFSET %d",
                $args['limit'],
                $args['offset']
            )
        );
    }
    
    public function create(array $data) {
        $result = $this->wpdb->insert(
            $this->table,
            $this->prepare_data($data),
            $this->get_formats($data)
        );
        
        return $result ? $this->wpdb->insert_id : false;
    }
    
    public function update(int $id, array $data): bool {
        return (bool) $this->wpdb->update(
            $this->table,
            $this->prepare_data($data),
            ['id' => $id],
            $this->get_formats($data),
            ['%d']
        );
    }
    
    public function delete(int $id): bool {
        return (bool) $this->wpdb->delete(
            $this->table,
            ['id' => $id],
            ['%d']
        );
    }
    
    abstract protected function prepare_data(array $data): array;
    abstract protected function get_formats(array $data): array;
}
```

### Concrete Repository Example

```php
<?php
namespace MobilityTrailblazers\Repositories;

class MT_Evaluation_Repository extends MT_Base_Repository 
    implements MT_Evaluation_Repository_Interface {
    
    protected $table;
    
    public function __construct() {
        parent::__construct();
        $this->table = $this->wpdb->prefix . 'mt_evaluations';
    }
    
    public function find_by_jury_and_candidate(int $jury_id, int $candidate_id) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table} 
                WHERE jury_member_id = %d AND candidate_id = %d",
                $jury_id,
                $candidate_id
            )
        );
    }
    
    public function get_by_jury_member(int $jury_id): array {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT e.*, p.post_title as candidate_name
                FROM {$this->table} e
                JOIN {$this->wpdb->posts} p ON e.candidate_id = p.ID
                WHERE e.jury_member_id = %d
                ORDER BY e.updated_at DESC",
                $jury_id
            )
        );
    }
    
    public function calculate_average_score(int $candidate_id): float {
        $result = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT AVG(total_score) 
                FROM {$this->table} 
                WHERE candidate_id = %d AND status = 'completed'",
                $candidate_id
            )
        );
        
        return $result ? (float) $result : 0.0;
    }
    
    protected function prepare_data(array $data): array {
        $prepared = [];
        
        if (isset($data['jury_member_id'])) {
            $prepared['jury_member_id'] = (int) $data['jury_member_id'];
        }
        
        if (isset($data['candidate_id'])) {
            $prepared['candidate_id'] = (int) $data['candidate_id'];
        }
        
        // Convert 0-10 scores to 0-20 for storage (0.5 increments)
        foreach (['courage', 'innovation', 'implementation', 'relevance', 'visibility'] as $criterion) {
            if (isset($data[$criterion . '_score'])) {
                $prepared[$criterion . '_score'] = (int) ($data[$criterion . '_score'] * 2);
            }
        }
        
        if (isset($data['comments'])) {
            $prepared['comments'] = sanitize_textarea_field($data['comments']);
        }
        
        if (isset($data['status'])) {
            $prepared['status'] = sanitize_text_field($data['status']);
        }
        
        // Calculate total score
        if ($this->has_all_scores($prepared)) {
            $prepared['total_score'] = $this->calculate_total($prepared);
        }
        
        return $prepared;
    }
    
    protected function get_formats(array $data): array {
        return [
            'jury_member_id' => '%d',
            'candidate_id' => '%d',
            'courage_score' => '%d',
            'innovation_score' => '%d',
            'implementation_score' => '%d',
            'relevance_score' => '%d',
            'visibility_score' => '%d',
            'total_score' => '%f',
            'comments' => '%s',
            'status' => '%s'
        ];
    }
}
```

## Service Layer

### Service Implementation

```php
<?php
namespace MobilityTrailblazers\Services;

class MT_Evaluation_Service implements MT_Evaluation_Service_Interface {
    private $evaluation_repo;
    private $assignment_repo;
    private $audit_log;
    private $errors = [];
    
    public function __construct(
        MT_Evaluation_Repository_Interface $evaluation_repo,
        MT_Assignment_Repository_Interface $assignment_repo,
        MT_Audit_Log_Interface $audit_log
    ) {
        $this->evaluation_repo = $evaluation_repo;
        $this->assignment_repo = $assignment_repo;
        $this->audit_log = $audit_log;
    }
    
    public function process(array $data) {
        // Validate input
        if (!$this->validate($data)) {
            return false;
        }
        
        // Check assignment exists
        if (!$this->assignment_repo->exists($data['jury_member_id'], $data['candidate_id'])) {
            $this->errors[] = __('You are not assigned to evaluate this candidate', 'mobility-trailblazers');
            return false;
        }
        
        // Check for existing evaluation
        $existing = $this->evaluation_repo->find_by_jury_and_candidate(
            $data['jury_member_id'],
            $data['candidate_id']
        );
        
        // Save or update
        if ($existing) {
            $result = $this->evaluation_repo->update($existing->id, $data);
            $evaluation_id = $existing->id;
        } else {
            $evaluation_id = $this->evaluation_repo->create($data);
            $result = $evaluation_id !== false;
        }
        
        if ($result) {
            // Log action
            $this->audit_log->log('evaluation_saved', [
                'evaluation_id' => $evaluation_id,
                'jury_member_id' => $data['jury_member_id'],
                'candidate_id' => $data['candidate_id'],
                'status' => $data['status']
            ]);
            
            // Clear caches
            $this->clear_evaluation_caches($data['jury_member_id'], $data['candidate_id']);
            
            return $evaluation_id;
        }
        
        return false;
    }
    
    public function validate(array $data): bool {
        $this->errors = [];
        
        // Required fields
        $required = ['jury_member_id', 'candidate_id', 'status'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->errors[] = sprintf(__('Field %s is required', 'mobility-trailblazers'), $field);
            }
        }
        
        // Validate scores (0-10 range)
        $score_fields = ['courage', 'innovation', 'implementation', 'relevance', 'visibility'];
        foreach ($score_fields as $field) {
            if (isset($data[$field . '_score'])) {
                $score = (float) $data[$field . '_score'];
                if ($score < 0 || $score > 10) {
                    $this->errors[] = sprintf(__('%s score must be between 0 and 10', 'mobility-trailblazers'), $field);
                }
            }
        }
        
        // Validate status
        $valid_statuses = ['draft', 'completed', 'submitted'];
        if (!in_array($data['status'], $valid_statuses)) {
            $this->errors[] = __('Invalid status', 'mobility-trailblazers');
        }
        
        return empty($this->errors);
    }
    
    public function get_errors(): array {
        return $this->errors;
    }
    
    private function clear_evaluation_caches($jury_id, $candidate_id) {
        delete_transient('mt_jury_rankings_' . $jury_id);
        delete_transient('mt_candidate_score_' . $candidate_id);
        delete_transient('mt_evaluation_stats');
    }
}
```

## Backward Compatibility

### Facade Pattern for Legacy Code

```php
<?php
namespace MobilityTrailblazers\Legacy;

/**
 * Backward compatibility facade
 * Provides static methods for legacy code during migration
 */
class MT_Backward_Compatibility {
    /**
     * Get evaluation service instance
     */
    public static function get_evaluation_service() {
        $container = MT_Plugin::container();
        return $container->make('MobilityTrailblazers\Services\MT_Evaluation_Service');
    }
    
    /**
     * Get assignment service instance
     */
    public static function get_assignment_service() {
        $container = MT_Plugin::container();
        return $container->make('MobilityTrailblazers\Services\MT_Assignment_Service');
    }
    
    /**
     * Get repository instance
     */
    public static function get_repository($type) {
        $container = MT_Plugin::container();
        
        $repositories = [
            'evaluation' => 'MobilityTrailblazers\Interfaces\MT_Evaluation_Repository_Interface',
            'assignment' => 'MobilityTrailblazers\Interfaces\MT_Assignment_Repository_Interface',
            'candidate' => 'MobilityTrailblazers\Interfaces\MT_Candidate_Repository_Interface',
            'audit' => 'MobilityTrailblazers\Interfaces\MT_Audit_Log_Repository_Interface'
        ];
        
        if (isset($repositories[$type])) {
            return $container->make($repositories[$type]);
        }
        
        throw new \Exception("Unknown repository type: $type");
    }
}

// Legacy usage example
$service = MT_Backward_Compatibility::get_evaluation_service();
$result = $service->process($data);
```

### Migration Strategy

```yaml
Migration Phases:
  Phase 1 - Setup (Week 1):
    - Implement container
    - Create interfaces
    - Setup providers
    
  Phase 2 - New Code (Weeks 2-4):
    - All new features use DI
    - Create services with interfaces
    - Use repository pattern
    
  Phase 3 - Legacy Migration (Weeks 5-8):
    - Identify legacy dependencies
    - Create facade methods
    - Gradually refactor legacy code
    
  Phase 4 - Cleanup (Week 9):
    - Remove facade
    - Delete legacy code
    - Update documentation
```

## Testing with DI

### Unit Testing with Mocks

```php
<?php
use PHPUnit\Framework\TestCase;

class MT_Evaluation_Service_Test extends TestCase {
    private $container;
    private $evaluation_service;
    
    protected function setUp(): void {
        // Create test container
        $this->container = new MT_Container();
        
        // Register mock repositories
        $this->container->singleton(
            'MobilityTrailblazers\Interfaces\MT_Evaluation_Repository_Interface',
            function() {
                $mock = $this->createMock(MT_Evaluation_Repository_Interface::class);
                $mock->method('find_by_jury_and_candidate')->willReturn(null);
                $mock->method('create')->willReturn(123);
                return $mock;
            }
        );
        
        $this->container->singleton(
            'MobilityTrailblazers\Interfaces\MT_Assignment_Repository_Interface',
            function() {
                $mock = $this->createMock(MT_Assignment_Repository_Interface::class);
                $mock->method('exists')->willReturn(true);
                return $mock;
            }
        );
        
        $this->container->singleton(
            'MobilityTrailblazers\Interfaces\MT_Audit_Log_Interface',
            function() {
                return $this->createMock(MT_Audit_Log_Interface::class);
            }
        );
        
        // Create service with mocked dependencies
        $this->evaluation_service = new MT_Evaluation_Service(
            $this->container->make('MobilityTrailblazers\Interfaces\MT_Evaluation_Repository_Interface'),
            $this->container->make('MobilityTrailblazers\Interfaces\MT_Assignment_Repository_Interface'),
            $this->container->make('MobilityTrailblazers\Interfaces\MT_Audit_Log_Interface')
        );
    }
    
    public function test_save_evaluation_with_valid_data() {
        $data = [
            'jury_member_id' => 1,
            'candidate_id' => 2,
            'courage_score' => 8.5,
            'innovation_score' => 9.0,
            'implementation_score' => 7.5,
            'relevance_score' => 8.0,
            'visibility_score' => 8.5,
            'status' => 'completed'
        ];
        
        $result = $this->evaluation_service->process($data);
        
        $this->assertEquals(123, $result);
    }
    
    public function test_validation_fails_with_invalid_score() {
        $data = [
            'jury_member_id' => 1,
            'candidate_id' => 2,
            'courage_score' => 11, // Invalid: > 10
            'status' => 'completed'
        ];
        
        $result = $this->evaluation_service->validate($data);
        
        $this->assertFalse($result);
        $this->assertContains('courage score must be between 0 and 10', $this->evaluation_service->get_errors());
    }
}
```

### Integration Testing

```php
class MT_Container_Integration_Test extends WP_UnitTestCase {
    private $container;
    
    public function setUp(): void {
        parent::setUp();
        
        // Use real container with real services
        $this->container = MT_Container::get_instance();
        
        // Register real providers
        $this->container->register_provider(new MT_Repository_Provider($this->container));
        $this->container->register_provider(new MT_Services_Provider($this->container));
    }
    
    public function test_container_resolves_service_with_dependencies() {
        $service = $this->container->make('MobilityTrailblazers\Services\MT_Evaluation_Service');
        
        $this->assertInstanceOf(MT_Evaluation_Service::class, $service);
    }
    
    public function test_singleton_returns_same_instance() {
        $service1 = $this->container->make('MobilityTrailblazers\Services\MT_Evaluation_Service');
        $service2 = $this->container->make('MobilityTrailblazers\Services\MT_Evaluation_Service');
        
        $this->assertSame($service1, $service2);
    }
}
```

## Implementation Checklist

- [ ] Create MT_Container class
- [ ] Implement service provider base class
- [ ] Define all interfaces
- [ ] Create repository provider
- [ ] Create services provider
- [ ] Implement repositories with interfaces
- [ ] Implement services with dependency injection
- [ ] Add backward compatibility facade
- [ ] Update AJAX handlers to use container
- [ ] Write unit tests with mocks
- [ ] Write integration tests
- [ ] Document container usage
- [ ] Create migration plan for legacy code

---

**Next Document**: [04-security-architecture.md](04-security-architecture.md) - Security implementation and best practices