# Migration Guide - Dependency Injection Architecture

**Version:** 2.6.0+  
**Last Updated:** 2025-01-20  
**Author:** Development Team

## Table of Contents

1. [Overview](#overview)
2. [Migration Strategy](#migration-strategy)
3. [Code Migration Patterns](#code-migration-patterns)
4. [Service Migration](#service-migration)
5. [Repository Migration](#repository-migration)
6. [AJAX Handler Migration](#ajax-handler-migration)
7. [Testing Migration](#testing-migration)
8. [Backward Compatibility](#backward-compatibility)
9. [Common Migration Issues](#common-migration-issues)
10. [Migration Checklist](#migration-checklist)

## Overview

This guide helps developers migrate existing code from the legacy direct instantiation pattern to the new dependency injection architecture while maintaining backward compatibility and plugin stability.

### Migration Goals

- **Zero Downtime**: Maintain full functionality during migration
- **Gradual Transition**: Migrate components incrementally
- **Backward Compatibility**: Preserve existing API surfaces
- **Improved Testability**: Enable proper unit testing
- **Code Quality**: Reduce coupling and improve maintainability

### Migration Phases

1. **Phase 1**: Core infrastructure setup (✅ Complete)
2. **Phase 2**: Service and repository migration (✅ Complete)
3. **Phase 3**: AJAX layer migration (✅ Complete)
4. **Phase 4**: Legacy code migration (🔄 Ongoing)
5. **Phase 5**: Deprecation cleanup (🔮 Future)

## Migration Strategy

### Incremental Approach

The migration follows an incremental approach where new components use dependency injection while existing components continue to work unchanged:

```
┌─────────────────┐    ┌─────────────────┐
│   New Code      │    │  Legacy Code    │
│ (Uses DI)       │    │ (Direct Inst.)  │
├─────────────────┤    ├─────────────────┤
│ Container       │    │ Backward        │
│ Services        │◄──►│ Compatibility   │
│ Repositories    │    │ Facade          │
└─────────────────┘    └─────────────────┘
```

### Compatibility Layers

1. **Backward Compatibility Facade**: Static methods for legacy access
2. **Constructor Fallbacks**: Services work with or without DI
3. **Interface Preservation**: Existing method signatures maintained
4. **Graceful Degradation**: Fallback to direct instantiation if container unavailable

## Code Migration Patterns

### Pattern 1: Direct Instantiation to DI

**Before:**
```php
class SomeHandler {
    public function handle_request() {
        $repository = new MT_Evaluation_Repository();
        $service = new MT_Evaluation_Service($repository);
        return $service->process($_POST);
    }
}
```

**After:**
```php
class SomeHandler {
    private $evaluation_service;
    
    public function __construct(MT_Evaluation_Service $evaluation_service = null) {
        // Use DI if provided, fallback to container, then legacy
        if ($evaluation_service) {
            $this->evaluation_service = $evaluation_service;
        } else {
            $container = MT_Plugin::container();
            $this->evaluation_service = $container->make('MobilityTrailblazers\Services\MT_Evaluation_Service');
        }
    }
    
    public function handle_request() {
        return $this->evaluation_service->process($_POST);
    }
}
```

**Transitional (Backward Compatible):**
```php
class SomeHandler {
    public function handle_request() {
        // Use facade for gradual migration
        $service = MT_Backward_Compatibility::get_evaluation_service();
        return $service->process($_POST);
    }
}
```

### Pattern 2: Static Method to Instance Method

**Before:**
```php
class UtilityClass {
    public static function process_evaluation($data) {
        $repository = new MT_Evaluation_Repository();
        // Processing logic
    }
}

// Usage
UtilityClass::process_evaluation($data);
```

**After:**
```php
class UtilityClass {
    private $evaluation_repository;
    
    public function __construct(MT_Evaluation_Repository_Interface $repository) {
        $this->evaluation_repository = $repository;
    }
    
    public function process_evaluation($data) {
        // Processing logic using $this->evaluation_repository
    }
}

// Registration
$container->singleton('UtilityClass', function($container) {
    return new UtilityClass(
        $container->make('MobilityTrailblazers\Interfaces\MT_Evaluation_Repository_Interface')
    );
});

// Usage
$utility = $container->make('UtilityClass');
$utility->process_evaluation($data);
```

### Pattern 3: Global Function to Service Method

**Before:**
```php
function mt_process_evaluation($data) {
    $repository = new MT_Evaluation_Repository();
    $service = new MT_Evaluation_Service($repository);
    return $service->process($data);
}
```

**After:**
```php
// Keep function for backward compatibility
function mt_process_evaluation($data) {
    $container = MT_Plugin::container();
    $service = $container->make('MobilityTrailblazers\Services\MT_Evaluation_Service');
    return $service->process($data);
}

// New usage pattern
$container = MT_Plugin::container();
$service = $container->make('MobilityTrailblazers\Services\MT_Evaluation_Service');
$result = $service->process($data);
```

## Service Migration

### Step 1: Add Constructor DI Support

Update service constructors to accept dependencies while maintaining backward compatibility:

```php
class MT_Custom_Service {
    private $repository;
    private $logger;
    
    public function __construct(
        MT_Repository_Interface $repository = null,
        MT_Logger_Interface $logger = null
    ) {
        // Try dependency injection first
        if ($repository && $logger) {
            $this->repository = $repository;
            $this->logger = $logger;
            return;
        }
        
        // Fallback to container resolution
        try {
            $container = MT_Plugin::container();
            $this->repository = $repository ?: $container->make('RepositoryInterface');
            $this->logger = $logger ?: $container->make('LoggerInterface');
        } catch (Exception $e) {
            // Final fallback to direct instantiation
            $this->repository = $repository ?: new MT_Repository();
            $this->logger = $logger ?: new MT_Logger();
        }
    }
}
```

### Step 2: Create Interface

If not already present, create an interface for the service:

```php
interface MT_Custom_Service_Interface {
    public function process($data);
    public function validate($data);
    public function get_errors();
}

class MT_Custom_Service implements MT_Custom_Service_Interface {
    // Implementation
}
```

### Step 3: Register with Container

Add service registration to appropriate service provider:

```php
class MT_Services_Provider extends MT_Service_Provider {
    public function register() {
        // Register service
        $this->singleton(
            'MobilityTrailblazers\Services\MT_Custom_Service',
            function($container) {
                return new MT_Custom_Service(
                    $container->make('RepositoryInterface'),
                    $container->make('LoggerInterface')
                );
            }
        );
        
        // Bind interface
        $this->singleton(
            'MobilityTrailblazers\Interfaces\MT_Custom_Service_Interface',
            function($container) {
                return $container->make('MobilityTrailblazers\Services\MT_Custom_Service');
            }
        );
    }
}
```

### Step 4: Update Backward Compatibility

Add service to the backward compatibility facade:

```php
class MT_Backward_Compatibility {
    public static function get_custom_service() {
        $container = MT_Plugin::container();
        return $container->make('MobilityTrailblazers\Services\MT_Custom_Service');
    }
}
```

## Repository Migration

### Step 1: Implement Interface

Ensure repository implements the appropriate interface:

```php
class MT_Custom_Repository implements MT_Repository_Interface {
    // Implement required methods: find, find_all, create, update, delete
    
    public function find($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE id = %d", 
            $id
        ));
    }
    
    // ... other methods
}
```

### Step 2: Add Repository-Specific Interface

Create extended interface for specific repository needs:

```php
interface MT_Custom_Repository_Interface extends MT_Repository_Interface {
    public function find_by_custom_field($value);
    public function get_statistics();
}

class MT_Custom_Repository implements MT_Custom_Repository_Interface {
    // Implement base and extended methods
}
```

### Step 3: Register Repository

Add to repository provider:

```php
class MT_Repository_Provider extends MT_Service_Provider {
    public function register() {
        $this->singleton(
            'MobilityTrailblazers\Repositories\MT_Custom_Repository',
            function($container) {
                return new MT_Custom_Repository();
            }
        );
        
        $this->singleton(
            'MobilityTrailblazers\Interfaces\MT_Custom_Repository_Interface',
            function($container) {
                return $container->make('MobilityTrailblazers\Repositories\MT_Custom_Repository');
            }
        );
    }
}
```

## AJAX Handler Migration

### Step 1: Update AJAX Class

Modify AJAX handlers to use container for service resolution:

**Before:**
```php
class MT_Custom_Ajax extends MT_Base_Ajax {
    public function handle_request() {
        $service = new MT_Custom_Service();
        $repository = new MT_Custom_Repository();
        
        // Handle request
    }
}
```

**After:**
```php
class MT_Custom_Ajax extends MT_Base_Ajax {
    private function get_custom_service() {
        $container = MT_Plugin::container();
        return $container->make('MobilityTrailblazers\Services\MT_Custom_Service');
    }
    
    private function get_custom_repository() {
        $container = MT_Plugin::container();
        return $container->make('MobilityTrailblazers\Interfaces\MT_Custom_Repository_Interface');
    }
    
    public function handle_request() {
        $service = $this->get_custom_service();
        $repository = $this->get_custom_repository();
        
        // Handle request - services already have dependencies injected
    }
}
```

### Step 2: Use Interfaces

Always resolve interfaces rather than concrete classes:

```php
// Good: Use interface
$service = $container->make('MobilityTrailblazers\Interfaces\MT_Service_Interface');

// Avoid: Direct class resolution
$service = $container->make('MobilityTrailblazers\Services\MT_Service');
```

### Step 3: Error Handling

Add proper error handling for service resolution:

```php
private function get_service_safely() {
    try {
        $container = MT_Plugin::container();
        return $container->make('ServiceInterface');
    } catch (Exception $e) {
        MT_Logger::error('Service resolution failed', [
            'service' => 'ServiceInterface',
            'error' => $e->getMessage()
        ]);
        
        // Fallback to legacy instantiation
        return new LegacyService();
    }
}
```

## Testing Migration

### Step 1: Update Test Setup

Modify test classes to use dependency injection:

**Before:**
```php
class ServiceTest extends WP_UnitTestCase {
    public function test_service_functionality() {
        $service = new ServiceClass();
        // Test without mocks
    }
}
```

**After:**
```php
class ServiceTest extends WP_UnitTestCase {
    public function test_service_functionality() {
        // Create mocks
        $mock_repository = $this->createMock(RepositoryInterface::class);
        $mock_logger = $this->createMock(LoggerInterface::class);
        
        // Configure mock behavior
        $mock_repository->expects($this->once())
                       ->method('find')
                       ->willReturn($expected_data);
        
        // Inject mocks
        $service = new ServiceClass($mock_repository, $mock_logger);
        
        // Test with controlled dependencies
        $result = $service->process($test_data);
        $this->assertEquals($expected_result, $result);
    }
}
```

### Step 2: Container Testing

Test container resolution in integration tests:

```php
class ContainerTest extends WP_UnitTestCase {
    public function test_service_resolution() {
        $container = MT_Plugin::container();
        
        // Test service can be resolved
        $service = $container->make('ServiceInterface');
        $this->assertInstanceOf('ServiceClass', $service);
        
        // Test singleton behavior
        $service2 = $container->make('ServiceInterface');
        $this->assertSame($service, $service2);
    }
}
```

### Step 3: Mock Container for Tests

Create mock container for isolated testing:

```php
class TestCase extends WP_UnitTestCase {
    protected $container;
    
    public function setUp(): void {
        parent::setUp();
        
        // Create test container
        $this->container = new MT_Container();
        
        // Register test implementations
        $this->container->singleton('ServiceInterface', 'TestServiceImplementation');
    }
}
```

## Backward Compatibility

### Facade Pattern Usage

The backward compatibility facade provides static access to DI services:

```php
// Legacy code can continue using:
$service = MT_Backward_Compatibility::get_evaluation_service();

// Which internally uses:
public static function get_evaluation_service() {
    $container = MT_Plugin::container();
    return $container->make('MobilityTrailblazers\Services\MT_Evaluation_Service');
}
```

### Constructor Fallbacks

Services maintain compatibility through constructor fallbacks:

```php
class MT_Service {
    public function __construct($dependency = null) {
        if ($dependency) {
            // Use provided dependency (DI)
            $this->dependency = $dependency;
        } else {
            // Fallback to container or direct instantiation
            try {
                $container = MT_Plugin::container();
                $this->dependency = $container->make('DependencyInterface');
            } catch (Exception $e) {
                $this->dependency = new LegacyDependency();
            }
        }
    }
}
```

### Method Signature Preservation

All public methods maintain their original signatures:

```php
// Original method
public function process_evaluation($data, $options = []) {
    // Implementation
}

// Signature preserved in new version
public function process_evaluation($data, $options = []) {
    // New implementation with DI, same signature
}
```

## Common Migration Issues

### Issue 1: Circular Dependencies

**Problem:**
```php
class ServiceA {
    public function __construct(ServiceB $serviceB) { }
}

class ServiceB {
    public function __construct(ServiceA $serviceA) { }
}
```

**Solution:**
```php
// Use events to break circular dependency
class ServiceA {
    public function __construct(EventDispatcher $events) {
        $this->events = $events;
    }
    
    public function doSomething() {
        $this->events->dispatch('service_a_event', $data);
    }
}

class ServiceB {
    public function __construct(EventDispatcher $events) {
        $this->events = $events;
        $this->events->listen('service_a_event', [$this, 'handleEvent']);
    }
}
```

### Issue 2: Container Not Available

**Problem:**
```php
$container = MT_Plugin::container(); // May fail in early plugin loading
```

**Solution:**
```php
public function get_service() {
    try {
        $container = MT_Plugin::container();
        return $container->make('ServiceInterface');
    } catch (Exception $e) {
        // Fallback to direct instantiation
        MT_Logger::warning('Container not available, using fallback');
        return new ServiceClass();
    }
}
```

### Issue 3: Interface Not Found

**Problem:**
```php
$service = $container->make('NonExistentInterface');
```

**Solution:**
```php
if ($container->has('ServiceInterface')) {
    $service = $container->make('ServiceInterface');
} else {
    MT_Logger::error('Service not registered: ServiceInterface');
    $service = new DefaultService();
}
```

### Issue 4: Constructor Parameter Mismatch

**Problem:**
```php
// Old constructor
class Service {
    public function __construct($param1, $param2) { }
}

// New constructor with DI
class Service {
    public function __construct(Dependency $dep, $param1, $param2) { }
}
```

**Solution:**
```php
// Use factory function for complex parameter handling
$container->singleton('Service', function($container) {
    $dependency = $container->make('Dependency');
    $param1 = get_option('service_param1');
    $param2 = get_option('service_param2');
    
    return new Service($dependency, $param1, $param2);
});
```

### Issue 5: Testing with WordPress Dependencies

**Problem:**
```php
// Service depends on WordPress functions
class Service {
    public function process() {
        $current_user = wp_get_current_user(); // WordPress function
    }
}
```

**Solution:**
```php
// Abstract WordPress dependencies
interface WordPressInterface {
    public function getCurrentUser();
}

class WordPressAdapter implements WordPressInterface {
    public function getCurrentUser() {
        return wp_get_current_user();
    }
}

class Service {
    public function __construct(WordPressInterface $wp) {
        $this->wp = $wp;
    }
    
    public function process() {
        $current_user = $this->wp->getCurrentUser();
    }
}

// In tests, mock the WordPress interface
$mockWp = $this->createMock(WordPressInterface::class);
$service = new Service($mockWp);
```

## Migration Checklist

### For Each Service

- [ ] Add constructor dependency injection support
- [ ] Maintain backward compatibility in constructor
- [ ] Create or implement appropriate interface
- [ ] Register service in service provider
- [ ] Add to backward compatibility facade
- [ ] Update documentation
- [ ] Write unit tests with mocks
- [ ] Test integration with container

### For Each Repository

- [ ] Implement MT_Repository_Interface
- [ ] Create repository-specific interface if needed
- [ ] Register in repository provider
- [ ] Add to backward compatibility facade
- [ ] Update related services to use interface
- [ ] Test CRUD operations
- [ ] Verify caching behavior
- [ ] Update documentation

### For Each AJAX Handler

- [ ] Update to use container for service resolution
- [ ] Use interfaces instead of concrete classes
- [ ] Add error handling for service resolution
- [ ] Test AJAX endpoints
- [ ] Verify nonce and permission checking
- [ ] Update JavaScript if needed

### For Testing

- [ ] Update test setup to use dependency injection
- [ ] Create mocks for dependencies
- [ ] Test both unit and integration scenarios
- [ ] Verify backward compatibility
- [ ] Test error conditions
- [ ] Performance testing with container

### Project-Wide

- [ ] Update all service instantiations
- [ ] Remove direct instantiation where possible
- [ ] Update documentation
- [ ] Run full test suite
- [ ] Performance testing
- [ ] Code review for DI patterns
- [ ] Update deployment procedures
- [ ] Monitor logs for DI-related errors

## Best Practices During Migration

### 1. Incremental Migration

- Migrate one component at a time
- Test thoroughly after each migration
- Keep backward compatibility intact
- Use feature flags for gradual rollout

### 2. Documentation

- Update inline documentation
- Maintain migration notes
- Document breaking changes
- Update API reference

### 3. Testing

- Write tests before migration
- Test both old and new patterns
- Verify performance impact
- Test error conditions

### 4. Monitoring

- Log migration milestones
- Monitor for errors
- Track performance metrics
- Monitor memory usage

### 5. Rollback Plan

- Keep legacy code paths available
- Use feature flags for quick rollback
- Have rollback procedures documented
- Test rollback scenarios

This migration guide provides a comprehensive roadmap for transitioning existing code to use the new dependency injection architecture while maintaining stability and backward compatibility.