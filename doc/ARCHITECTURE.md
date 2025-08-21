# Mobility Trailblazers Plugin Architecture

**Version:** 2.5.37+  
**Last Updated:** 2025-08-20  
**Author:** Development Team  
**Architecture Version:** 2.0 (Dependency Injection Implementation)

## Table of Contents

1. [Overview](#overview)
2. [Architecture Patterns](#architecture-patterns)
3. [Dependency Injection Container](#dependency-injection-container)
4. [Service Provider Pattern](#service-provider-pattern)
5. [Interface-Based Design](#interface-based-design)
6. [Directory Structure](#directory-structure)
7. [Component Lifecycle](#component-lifecycle)
8. [Service Layer](#service-layer)
9. [Repository Layer](#repository-layer)
10. [AJAX Layer](#ajax-layer)
11. [Legacy Compatibility](#legacy-compatibility)
12. [Best Practices](#best-practices)

## Overview

The Mobility Trailblazers WordPress plugin has evolved from a traditional WordPress plugin architecture to a modern, enterprise-grade system using dependency injection, service providers, and interface-based design patterns. This transformation maintains complete backward compatibility while providing a robust foundation for future development.

### Key Architectural Principles (SOLID)

- **Single Responsibility Principle**: Each class has a single, well-defined purpose
- **Open/Closed Principle**: Extension through composition and interfaces rather than modification
- **Liskov Substitution Principle**: All implementations honor their interface contracts
- **Interface Segregation Principle**: All services and repositories implement focused interfaces
- **Dependency Inversion Principle**: Depend on abstractions (interfaces), not concretions
- **Separation of Concerns**: Clear boundaries between presentation, business logic, and data access
- **Testability**: All components can be unit tested in isolation with dependency injection

## Architecture Patterns

### 1. Layered Architecture

```
┌─────────────────────────────────────────────────────┐
│                 Presentation Layer                  │
│  (Templates, AJAX Handlers, Admin Interfaces)      │
├─────────────────────────────────────────────────────┤
│                  Service Layer                      │
│    (Business Logic, Domain Rules, Workflows)       │
├─────────────────────────────────────────────────────┤
│                 Repository Layer                    │
│     (Data Access, Query Logic, Persistence)        │
├─────────────────────────────────────────────────────┤
│                   Data Layer                        │
│    (WordPress Database, Custom Tables, Files)      │
└─────────────────────────────────────────────────────┘
```

### 2. Dependency Flow

```
Container → Service Providers → Services → Repositories → Database
    ↓
Interfaces ← Implementation Classes ← Constructor Injection
```

## Dependency Injection Container

The `MT_Container` class provides a lightweight, WordPress-compatible dependency injection container.

### Core Features

- **Singleton Pattern**: Ensures single container instance
- **Service Registration**: Bind interfaces to implementations
- **Automatic Resolution**: Resolves constructor dependencies automatically
- **Closure Support**: Factory functions for complex instantiation
- **Shared Instances**: Singleton service management

### Container Lifecycle

```php
// 1. Container Initialization (in plugin bootstrap)
$container = MT_Container::get_instance();

// 2. Service Provider Registration
$container->register_provider(new MT_Repository_Provider($container));
$container->register_provider(new MT_Services_Provider($container));

// 3. Service Resolution (throughout application)
$evaluation_service = $container->make('MobilityTrailblazers\Services\MT_Evaluation_Service');
```

### Registration Types

#### Basic Binding
```php
// Bind interface to concrete class
$container->bind(
    'MobilityTrailblazers\Interfaces\MT_Repository_Interface',
    'MobilityTrailblazers\Repositories\MT_Evaluation_Repository'
);
```

#### Singleton Binding
```php
// Register as singleton (shared instance)
$container->singleton(
    'MobilityTrailblazers\Services\MT_Evaluation_Service',
    function($container) {
        return new MT_Evaluation_Service(
            $container->make('MobilityTrailblazers\Interfaces\MT_Evaluation_Repository_Interface'),
            $container->make('MobilityTrailblazers\Interfaces\MT_Assignment_Repository_Interface')
        );
    }
);
```

## Service Provider Pattern

Service providers encapsulate dependency registration logic and bootstrap services.

### Provider Hierarchy

```
MT_Service_Provider (Abstract Base)
    ├── MT_Repository_Provider
    ├── MT_Services_Provider
    └── [Future Providers]
```

### Provider Lifecycle

1. **Registration Phase**: Services are bound to the container
2. **Boot Phase**: Additional initialization after all services are registered

### Example Provider

```php
class MT_Repository_Provider extends MT_Service_Provider {
    public function register() {
        // Bind repositories as singletons
        $this->singleton(
            'MobilityTrailblazers\Interfaces\MT_Evaluation_Repository_Interface',
            function($container) {
                return new MT_Evaluation_Repository();
            }
        );
    }
    
    public function boot() {
        // Post-registration initialization
    }
}
```

## Interface-Based Design

All major components implement interfaces to ensure loose coupling and testability.

### Interface Hierarchy

```
MT_Service_Interface
    ├── MT_Evaluation_Service_Interface
    ├── MT_Assignment_Service_Interface
    └── MT_Diagnostic_Service_Interface

MT_Repository_Interface
    ├── MT_Evaluation_Repository_Interface
    ├── MT_Assignment_Repository_Interface
    ├── MT_Candidate_Repository_Interface
    └── MT_Audit_Log_Repository_Interface
```

### Benefits of Interface-Based Design

- **Testability**: Easy to mock dependencies for unit testing
- **Flexibility**: Swap implementations without changing dependent code
- **Documentation**: Interfaces serve as contracts documenting expected behavior
- **IDE Support**: Better autocompletion and type checking

## Directory Structure

```
includes/
├── core/                           # Core framework components
│   ├── class-mt-container.php      # Dependency injection container
│   ├── class-mt-service-provider.php # Base service provider
│   ├── class-mt-plugin.php         # Main plugin class
│   └── class-mt-autoloader.php     # PSR-4 autoloader
├── providers/                      # Service providers
│   ├── class-mt-repository-provider.php
│   └── class-mt-services-provider.php
├── interfaces/                     # Interface definitions
│   ├── interface-mt-service.php
│   ├── interface-mt-repository.php
│   ├── interface-mt-evaluation-repository.php
│   └── interface-mt-assignment-repository.php
├── services/                       # Business logic layer
│   ├── class-mt-evaluation-service.php
│   ├── class-mt-assignment-service.php
│   └── class-mt-diagnostic-service.php
├── repositories/                   # Data access layer
│   ├── class-mt-evaluation-repository.php
│   ├── class-mt-assignment-repository.php
│   └── class-mt-candidate-repository.php
├── ajax/                          # AJAX handlers
│   ├── class-mt-base-ajax.php
│   ├── class-mt-evaluation-ajax.php
│   └── class-mt-assignment-ajax.php
├── legacy/                        # Backward compatibility
│   └── class-mt-backward-compatibility.php
└── [other directories...]
```

## Component Lifecycle

### 1. Plugin Initialization

```php
// mobility-trailblazers.php
function mt_init_plugin() {
    // 1. Initialize container
    $container = MT_Container::get_instance();
    
    // 2. Register service providers
    $container->register_provider(new MT_Repository_Provider($container));
    $container->register_provider(new MT_Services_Provider($container));
    
    // 3. Initialize plugin
    $plugin = new MT_Plugin($container);
    $plugin->init();
}
```

### 2. Service Resolution

```php
// In any part of the application
$container = MT_Plugin::container();
$evaluation_service = $container->make('MobilityTrailblazers\Services\MT_Evaluation_Service');
```

### 3. Automatic Dependency Injection

```php
class MT_Evaluation_Service {
    public function __construct(
        MT_Evaluation_Repository_Interface $evaluation_repository,
        MT_Assignment_Repository_Interface $assignment_repository
    ) {
        // Dependencies automatically injected by container
        $this->repository = $evaluation_repository;
        $this->assignment_repository = $assignment_repository;
    }
}
```

## Service Layer

Services contain business logic and orchestrate operations across repositories.

### Service Characteristics

- **Stateless**: Services don't maintain state between operations
- **Transactional**: Each method represents a complete business operation
- **Validated**: Input validation and business rule enforcement
- **Audited**: All operations are logged for audit trails

### Service Example

```php
class MT_Evaluation_Service implements MT_Service_Interface {
    private $repository;
    private $assignment_repository;
    private $errors = [];
    
    public function __construct(
        MT_Evaluation_Repository_Interface $evaluation_repository,
        MT_Assignment_Repository_Interface $assignment_repository
    ) {
        $this->repository = $evaluation_repository;
        $this->assignment_repository = $assignment_repository;
    }
    
    public function process($data) {
        // 1. Validate input
        if (!$this->validate($data)) {
            return false;
        }
        
        // 2. Check business rules
        if (!$this->check_permission($data['jury_member_id'], $data['candidate_id'])) {
            return false;
        }
        
        // 3. Process data
        $result = $this->repository->save($this->prepare_data($data));
        
        // 4. Log audit trail
        MT_Audit_Logger::log('evaluation_saved', 'evaluation', $result, $data);
        
        return $result;
    }
}
```

## Repository Layer

Repositories handle all data access and persistence operations.

### Repository Characteristics

- **Data-Focused**: Exclusively handle data operations
- **Technology-Agnostic**: Abstract underlying storage implementation
- **Cacheable**: Can implement caching strategies transparently
- **Queryable**: Provide flexible query interfaces

### Repository Example

```php
class MT_Evaluation_Repository implements MT_Evaluation_Repository_Interface {
    public function find($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'mt_evaluations';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $id
        ));
    }
    
    public function create($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'mt_evaluations';
        
        $result = $wpdb->insert($table, $this->sanitize_data($data));
        return $result ? $wpdb->insert_id : false;
    }
}
```

## AJAX Layer

AJAX handlers have been updated to use the container for service resolution.

### Before (Legacy)
```php
class MT_Evaluation_Ajax {
    public function submit_evaluation() {
        // Direct instantiation
        $service = new MT_Evaluation_Service();
        $repository = new MT_Evaluation_Repository();
    }
}
```

### After (DI-Enabled)
```php
class MT_Evaluation_Ajax extends MT_Base_Ajax {
    private function get_evaluation_service() {
        $container = MT_Plugin::container();
        return $container->make('MobilityTrailblazers\Services\MT_Evaluation_Service');
    }
    
    public function submit_evaluation() {
        $service = $this->get_evaluation_service();
        // Service already has all dependencies injected
    }
}
```

## Legacy Compatibility

The `MT_Backward_Compatibility` facade provides static methods for legacy code.

### Facade Pattern

```php
class MT_Backward_Compatibility {
    public static function get_evaluation_service() {
        $container = MT_Plugin::container();
        return $container->make('MobilityTrailblazers\Services\MT_Evaluation_Service');
    }
}

// Legacy code can still use:
$service = MT_Backward_Compatibility::get_evaluation_service();
```

### Migration Strategy

1. **Phase 1**: New code uses DI container
2. **Phase 2**: Legacy code uses facade for gradual migration
3. **Phase 3**: Legacy facade deprecated and removed

## Best Practices

### 1. Service Design

- Keep services focused on a single domain
- Use dependency injection for all dependencies
- Implement interfaces for all public services
- Validate inputs at service boundaries
- Log all significant operations

### 2. Repository Design

- One repository per aggregate root
- Use prepared statements for all queries
- Implement proper error handling
- Cache frequently accessed data
- Abstract database-specific logic

### 3. Container Usage

- Register services as singletons when stateless
- Use factory functions for complex initialization
- Prefer interface bindings over concrete classes
- Keep container configuration in service providers

### 4. Testing

- Mock all dependencies using interfaces
- Test services in isolation
- Use dependency injection for test doubles
- Verify audit logging in integration tests

### 5. Error Handling

- Use exceptions for exceptional circumstances
- Return WP_Error objects for expected failures
- Log all errors with appropriate context
- Provide user-friendly error messages

## Performance Considerations

### Container Overhead

- Minimal overhead for service resolution
- Singleton pattern prevents duplicate instantiation
- Reflection only used during first resolution

### Memory Usage

- Services are instantiated only when needed
- Shared instances reduce memory footprint
- Container itself is lightweight

### Caching Strategy

```php
// Repository-level caching
class MT_Evaluation_Repository {
    private $cache = [];
    
    public function find($id) {
        if (isset($this->cache[$id])) {
            return $this->cache[$id];
        }
        
        $result = $this->query_database($id);
        $this->cache[$id] = $result;
        return $result;
    }
}
```

## Future Architecture Considerations

### 1. Event System

```php
// Future event-driven architecture
$container->singleton('EventDispatcher', function() {
    return new MT_Event_Dispatcher();
});

// Services can dispatch domain events
$this->eventDispatcher->dispatch(new EvaluationSubmitted($evaluation));
```

### 2. Command/Query Separation

```php
// Separate read and write operations
interface MT_Evaluation_Command_Interface {
    public function save($data);
    public function delete($id);
}

interface MT_Evaluation_Query_Interface {
    public function find($id);
    public function search($criteria);
}
```

### 3. Background Processing

```php
// Queue support for long-running operations
$container->singleton('QueueManager', function() {
    return new MT_Queue_Manager();
});
```

## Conclusion

The new dependency injection architecture provides a solid foundation for maintaining and extending the Mobility Trailblazers plugin while preserving WordPress compatibility and ensuring backward compatibility with existing code.

Key benefits:

- **Maintainability**: Clear separation of concerns and dependency management
- **Testability**: Easy mocking and isolated testing
- **Extensibility**: New features can be added without modifying existing code
- **Performance**: Efficient service resolution and caching
- **Documentation**: Self-documenting through interfaces and type hints

This architecture positions the plugin for long-term success and easy adaptation to changing requirements.