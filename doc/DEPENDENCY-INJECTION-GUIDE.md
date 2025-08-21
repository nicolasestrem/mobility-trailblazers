# Dependency Injection Guide

**Version:** 2.5.37+  
**Last Updated:** 2025-01-20  
**Author:** Mobility Trailblazers - Nicolas Estrem

## Table of Contents

1. [Introduction](#introduction)
2. [Quick Start](#quick-start)
3. [Container Usage](#container-usage)
4. [Service Registration](#service-registration)
5. [Service Resolution](#service-resolution)
6. [Service Providers](#service-providers)
7. [Testing with DI](#testing-with-di)
8. [Best Practices](#best-practices)
9. [Troubleshooting](#troubleshooting)
10. [Examples](#examples)

## Introduction

The Mobility Trailblazers plugin uses a lightweight dependency injection container to manage service dependencies and improve code organization, testability, and maintainability.

### Why Dependency Injection?

- **Loose Coupling**: Services depend on interfaces, not concrete implementations
- **Testability**: Easy to mock dependencies for unit testing
- **Configuration**: Centralized service configuration
- **Lifecycle Management**: Automatic handling of singleton instances
- **Flexibility**: Easy to swap implementations without changing dependent code

### Core Concepts

- **Container**: Central registry that manages service instances and dependencies
- **Service**: Any class that provides functionality (business logic, data access, etc.)
- **Binding**: Association between an interface/class name and its implementation
- **Resolution**: Process of creating instances with their dependencies
- **Provider**: Class that registers related services with the container

## Quick Start

### Basic Usage

```php
// 1. Get the container instance
$container = MT_Plugin::container();

// 2. Resolve a service
$evaluation_service = $container->make('MobilityTrailblazers\Services\MT_Evaluation_Service');

// 3. Use the service (dependencies already injected)
$result = $evaluation_service->process($evaluation_data);
```

### In AJAX Handlers

```php
class MT_Custom_Ajax extends MT_Base_Ajax {
    private function get_my_service() {
        $container = MT_Plugin::container();
        return $container->make('MobilityTrailblazers\Services\MT_Custom_Service');
    }
    
    public function handle_request() {
        $service = $this->get_my_service();
        // Service is ready to use with all dependencies
    }
}
```

### Legacy Code Migration

```php
// Old way (direct instantiation)
$service = new MT_Evaluation_Service();

// New way (using container)
$container = MT_Plugin::container();
$service = $container->make('MobilityTrailblazers\Services\MT_Evaluation_Service');

// Transitional way (using backward compatibility facade)
$service = MT_Backward_Compatibility::get_evaluation_service();
```

## Container Usage

### Getting the Container

The container is a singleton that can be accessed in several ways:

```php
// Primary method (recommended)
$container = MT_Plugin::container();

// Direct access (if needed)
$container = MT_Container::get_instance();
```

### Checking Service Availability

```php
// Check if a service is registered
if ($container->has('MobilityTrailblazers\Services\MT_Custom_Service')) {
    $service = $container->make('MobilityTrailblazers\Services\MT_Custom_Service');
}
```

### Container Methods

| Method | Description | Usage |
|--------|-------------|-------|
| `make($abstract)` | Resolve a service | `$container->make('ServiceClass')` |
| `get($abstract)` | Alias for make | `$container->get('ServiceClass')` |
| `has($abstract)` | Check if service exists | `$container->has('ServiceClass')` |
| `bind($abstract, $concrete, $shared)` | Register service | `$container->bind('Interface', 'Implementation')` |
| `singleton($abstract, $concrete)` | Register singleton | `$container->singleton('Service', $factory)` |

## Service Registration

### Simple Binding

```php
// Bind interface to implementation
$container->bind(
    'MobilityTrailblazers\Interfaces\MT_Custom_Interface',
    'MobilityTrailblazers\Services\MT_Custom_Service'
);

// Bind class to itself
$container->bind('MobilityTrailblazers\Services\MT_Custom_Service');
```

### Singleton Registration

```php
// Register as singleton (shared instance)
$container->singleton(
    'MobilityTrailblazers\Services\MT_Custom_Service',
    function($container) {
        return new MT_Custom_Service(
            $container->make('MobilityTrailblazers\Interfaces\MT_Repository_Interface')
        );
    }
);
```

### Factory Functions

```php
// Complex initialization with factory
// Note: Email service removed in v2.5.38
// Example using Assignment Service instead
$container->singleton('MobilityTrailblazers\Services\MT_Assignment_Service', function($container) {
    $repository = $container->make('MobilityTrailblazers\Repositories\MT_Assignment_Repository');
    $evaluation_service = $container->make('MobilityTrailblazers\Services\MT_Evaluation_Service');
    
    $service = new MT_Assignment_Service($repository, $evaluation_service);
    $service->configure([
        'auto_assign' => get_option('mt_auto_assign_candidates', false),
        'max_assignments' => get_option('mt_max_assignments_per_jury', 20)
    ]);
    
    return $service;
});
```

### Conditional Registration

```php
// Register different implementations based on environment
if (defined('WP_DEBUG') && WP_DEBUG) {
    $container->singleton('Logger', 'DebugLogger');
} else {
    $container->singleton('Logger', 'ProductionLogger');
}
```

## Service Resolution

### Automatic Dependency Injection

The container automatically resolves constructor dependencies:

```php
class MT_Custom_Service {
    public function __construct(
        MT_Repository_Interface $repository,
        MT_Logger_Interface $logger
    ) {
        $this->repository = $repository;
        $this->logger = $logger;
    }
}

// Container automatically injects dependencies
$service = $container->make('MT_Custom_Service');
```

### Resolution Process

1. **Check Bindings**: Look for registered binding
2. **Analyze Constructor**: Use reflection to examine constructor parameters
3. **Resolve Dependencies**: Recursively resolve each parameter
4. **Create Instance**: Instantiate with resolved dependencies
5. **Store if Singleton**: Cache instance for future resolution

### Type Hints and Defaults

```php
class MT_Service_With_Defaults {
    public function __construct(
        MT_Repository_Interface $repository,
        string $config_value = 'default',
        ?MT_Optional_Service $optional = null
    ) {
        $this->repository = $repository;
        $this->config = $config_value;
        $this->optional = $optional;
    }
}
```

### Manual Resolution

```php
// When automatic resolution isn't sufficient
$container->singleton('ComplexService', function($container) {
    $repository = $container->make('RepositoryInterface');
    $config = get_option('plugin_config', []);
    
    return new ComplexService($repository, $config);
});
```

## Service Providers

Service providers organize related service registrations:

### Creating a Service Provider

```php
class MT_Custom_Provider extends MT_Service_Provider {
    
    public function register() {
        // Register repositories
        $this->singleton(
            'MobilityTrailblazers\Interfaces\MT_Custom_Repository_Interface',
            'MobilityTrailblazers\Repositories\MT_Custom_Repository'
        );
        
        // Register services
        $this->singleton(
            'MobilityTrailblazers\Services\MT_Custom_Service',
            function($container) {
                return new MT_Custom_Service(
                    $container->make('MobilityTrailblazers\Interfaces\MT_Custom_Repository_Interface')
                );
            }
        );
    }
    
    public function boot() {
        // Post-registration initialization
        $service = $this->container->make('MobilityTrailblazers\Services\MT_Custom_Service');
        $service->initialize();
    }
}
```

### Registering Providers

```php
// In plugin initialization
$container = MT_Container::get_instance();
$container->register_provider(new MT_Custom_Provider($container));

// Or by class name
$container->register_provider('MobilityTrailblazers\Providers\MT_Custom_Provider');
```

### Provider Lifecycle

1. **Registration Phase**: `register()` method called
2. **Boot Phase**: `boot()` method called after all providers registered
3. **Service Resolution**: Services available for resolution

## Testing with DI

### Unit Testing Services

```php
class MT_Evaluation_Service_Test extends WP_UnitTestCase {
    
    public function test_evaluation_processing() {
        // Create mock dependencies
        $mock_repository = $this->createMock(MT_Evaluation_Repository_Interface::class);
        $mock_assignment_repo = $this->createMock(MT_Assignment_Repository_Interface::class);
        
        // Configure mock behavior
        $mock_repository->expects($this->once())
                       ->method('save')
                       ->willReturn(123);
        
        // Inject mocks into service
        $service = new MT_Evaluation_Service($mock_repository, $mock_assignment_repo);
        
        // Test the service
        $result = $service->process($test_data);
        $this->assertEquals(123, $result);
    }
}
```

### Integration Testing with Container

```php
class MT_Integration_Test extends WP_UnitTestCase {
    
    public function setUp(): void {
        parent::setUp();
        
        // Create test container
        $this->container = new MT_Container();
        
        // Register test implementations
        $this->container->singleton(
            'MobilityTrailblazers\Interfaces\MT_Repository_Interface',
            'Tests\Mocks\MT_Mock_Repository'
        );
    }
    
    public function test_service_integration() {
        $service = $this->container->make('MobilityTrailblazers\Services\MT_Test_Service');
        // Test with real dependencies but mock data sources
    }
}
```

### Test Doubles

```php
// Spy implementation for testing
class MT_Repository_Spy implements MT_Repository_Interface {
    public $called_methods = [];
    
    public function find($id) {
        $this->called_methods[] = ['method' => 'find', 'args' => [$id]];
        return new stdClass();
    }
    
    // ... implement other interface methods
}
```

## Best Practices

### 1. Interface Design

```php
// Good: Focused interface
interface MT_Email_Service_Interface {
    public function send_notification($to, $subject, $message);
    public function queue_bulk_email($recipients, $template, $data);
}

// Avoid: Overly broad interface
interface MT_Huge_Service_Interface {
    public function send_email();
    public function process_payments();
    public function generate_reports();
    // ... too many responsibilities
}
```

### 2. Service Design

```php
// Good: Stateless service with clear dependencies
class MT_Evaluation_Service {
    public function __construct(
        MT_Evaluation_Repository_Interface $repository,
        MT_Assignment_Repository_Interface $assignment_repository
    ) {
        $this->repository = $repository;
        $this->assignment_repository = $assignment_repository;
    }
    
    public function process($data) {
        // Stateless operation
    }
}

// Avoid: Service with internal state
class MT_Bad_Service {
    private $current_user_data; // State that shouldn't be here
    
    public function set_user($user) {
        $this->current_user_data = $user; // Problematic state
    }
}
```

### 3. Registration Patterns

```php
// Good: Register through service providers
class MT_Services_Provider extends MT_Service_Provider {
    public function register() {
        $this->register_repositories();
        $this->register_services();
    }
    
    private function register_repositories() {
        // Group related registrations
    }
}

// Avoid: Scattered registrations
function random_function() {
    $container->bind('SomeService', 'Implementation'); // Hard to track
}
```

### 4. Dependency Management

```php
// Good: Constructor injection
class MT_Good_Service {
    public function __construct(MT_Repository_Interface $repository) {
        $this->repository = $repository;
    }
}

// Avoid: Service locator pattern within services
class MT_Bad_Service {
    public function process() {
        $repository = MT_Plugin::container()->make('Repository'); // Anti-pattern
    }
}
```

### 5. Error Handling

```php
// Good: Graceful handling of missing dependencies
public function get_optional_service() {
    if ($this->container->has('OptionalService')) {
        return $this->container->make('OptionalService');
    }
    
    return new NullOptionalService(); // Null object pattern
}

// Provide fallbacks for backward compatibility
public function get_service_with_fallback() {
    try {
        return $this->container->make('PreferredService');
    } catch (Exception $e) {
        return new LegacyService(); // Fallback implementation
    }
}
```

## Troubleshooting

### Common Issues

#### 1. Circular Dependencies

```php
// Problem: Service A depends on Service B, Service B depends on Service A
class ServiceA {
    public function __construct(ServiceB $serviceB) { }
}

class ServiceB {
    public function __construct(ServiceA $serviceA) { }
}

// Solution: Use events or refactor to remove circular dependency
class ServiceA {
    public function __construct(MT_Event_Dispatcher $events) {
        $this->events = $events;
    }
    
    public function doSomething() {
        $this->events->dispatch('service_a_action', $data);
    }
}
```

#### 2. Interface Not Found

```php
// Error: Interface 'NonExistentInterface' not found

// Solution: Check interface is properly loaded and namespace is correct
use MobilityTrailblazers\Interfaces\MT_Correct_Interface;

// Or check if service is registered
if (!$container->has('InterfaceName')) {
    // Register the service or check provider
}
```

#### 3. Constructor Parameter Cannot Be Resolved

```php
// Problem: Cannot resolve primitive parameter
class ProblematicService {
    public function __construct(string $api_key) { } // Cannot auto-resolve
}

// Solution: Use factory function
$container->singleton('ProblematicService', function() {
    return new ProblematicService(get_option('api_key'));
});
```

#### 4. Service Not Found

```php
// Check registration
if (!$container->has('ServiceName')) {
    error_log('Service not registered: ServiceName');
}

// Check provider is loaded
// Verify namespace and class name
// Check for typos in service name
```

### Debugging

#### 1. Container State

```php
// Debug container bindings (development only)
if (defined('WP_DEBUG') && WP_DEBUG) {
    $reflection = new ReflectionClass($container);
    $bindings_property = $reflection->getProperty('bindings');
    $bindings_property->setAccessible(true);
    $bindings = $bindings_property->getValue($container);
    
    error_log('Container bindings: ' . print_r(array_keys($bindings), true));
}
```

#### 2. Resolution Tracing

```php
// Add logging to understand resolution
class Debug_Container extends MT_Container {
    public function make($abstract) {
        error_log("Resolving: {$abstract}");
        $result = parent::make($abstract);
        error_log("Resolved: {$abstract} to " . get_class($result));
        return $result;
    }
}
```

#### 3. Provider Loading

```php
// Verify providers are loading
add_action('init', function() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $container = MT_Plugin::container();
        
        // Check if expected services are available
        $expected_services = [
            'MobilityTrailblazers\Services\MT_Evaluation_Service',
            'MobilityTrailblazers\Services\MT_Assignment_Service'
        ];
        
        foreach ($expected_services as $service) {
            if (!$container->has($service)) {
                error_log("Missing service: {$service}");
            }
        }
    }
});
```

## Examples

### Example 1: Creating a New Service

```php
// 1. Create interface
interface MT_Notification_Service_Interface {
    public function send_notification($user_id, $message, $type = 'info');
    public function queue_notification($user_id, $message, $type = 'info', $send_at = null);
}

// 2. Create implementation
class MT_Notification_Service implements MT_Notification_Service_Interface {
    public function __construct(
        MT_User_Repository_Interface $user_repository,
        MT_Email_Service_Interface $email_service
    ) {
        $this->user_repository = $user_repository;
        $this->email_service = $email_service;
    }
    
    public function send_notification($user_id, $message, $type = 'info') {
        $user = $this->user_repository->find($user_id);
        if (!$user) {
            throw new InvalidArgumentException('User not found');
        }
        
        return $this->email_service->send($user->email, 'Notification', $message);
    }
    
    public function queue_notification($user_id, $message, $type = 'info', $send_at = null) {
        // Implementation
    }
}

// 3. Register in service provider
class MT_Services_Provider extends MT_Service_Provider {
    public function register() {
        // Register the service
        $this->singleton(
            'MobilityTrailblazers\Services\MT_Notification_Service',
            function($container) {
                return new MT_Notification_Service(
                    $container->make('MobilityTrailblazers\Interfaces\MT_User_Repository_Interface'),
                    $container->make('MobilityTrailblazers\Interfaces\MT_Email_Service_Interface')
                );
            }
        );
        
        // Bind interface to implementation
        $this->singleton(
            'MobilityTrailblazers\Interfaces\MT_Notification_Service_Interface',
            function($container) {
                return $container->make('MobilityTrailblazers\Services\MT_Notification_Service');
            }
        );
    }
}

// 4. Use in other services or AJAX handlers
class MT_Custom_Ajax extends MT_Base_Ajax {
    public function send_notification() {
        $container = MT_Plugin::container();
        $notification_service = $container->make('MobilityTrailblazers\Interfaces\MT_Notification_Service_Interface');
        
        $user_id = $this->get_int_param('user_id');
        $message = $this->get_text_param('message');
        
        try {
            $notification_service->send_notification($user_id, $message);
            $this->success([], 'Notification sent successfully');
        } catch (Exception $e) {
            $this->error('Failed to send notification: ' . $e->getMessage());
        }
    }
}
```

### Example 2: Testing with Mocks

```php
class MT_Notification_Service_Test extends WP_UnitTestCase {
    
    public function test_send_notification_success() {
        // Create mocks
        $user_repository_mock = $this->createMock(MT_User_Repository_Interface::class);
        $email_service_mock = $this->createMock(MT_Email_Service_Interface::class);
        
        // Configure mocks
        $user = (object) ['id' => 1, 'email' => 'test@example.com'];
        $user_repository_mock->expects($this->once())
                            ->method('find')
                            ->with(1)
                            ->willReturn($user);
        
        $email_service_mock->expects($this->once())
                          ->method('send')
                          ->with('test@example.com', 'Notification', 'Test message')
                          ->willReturn(true);
        
        // Create service with mocks
        $service = new MT_Notification_Service($user_repository_mock, $email_service_mock);
        
        // Test
        $result = $service->send_notification(1, 'Test message');
        $this->assertTrue($result);
    }
    
    public function test_send_notification_user_not_found() {
        $user_repository_mock = $this->createMock(MT_User_Repository_Interface::class);
        $email_service_mock = $this->createMock(MT_Email_Service_Interface::class);
        
        $user_repository_mock->expects($this->once())
                            ->method('find')
                            ->with(999)
                            ->willReturn(null);
        
        $service = new MT_Notification_Service($user_repository_mock, $email_service_mock);
        
        $this->expectException(InvalidArgumentException::class);
        $service->send_notification(999, 'Test message');
    }
}
```

### Example 3: Migrating Legacy Code

```php
// Before: Legacy code with direct instantiation
class MT_Legacy_Handler {
    public function process_evaluation() {
        $repository = new MT_Evaluation_Repository();
        $assignment_repository = new MT_Assignment_Repository();
        $service = new MT_Evaluation_Service($repository, $assignment_repository);
        
        return $service->process($_POST);
    }
}

// After: Using dependency injection
class MT_Modern_Handler {
    private $evaluation_service;
    
    public function __construct(MT_Evaluation_Service_Interface $evaluation_service) {
        $this->evaluation_service = $evaluation_service;
    }
    
    public function process_evaluation() {
        return $this->evaluation_service->process($_POST);
    }
}

// Registration
$container->singleton('MT_Modern_Handler', function($container) {
    return new MT_Modern_Handler(
        $container->make('MobilityTrailblazers\Interfaces\MT_Evaluation_Service_Interface')
    );
});

// Usage
$container = MT_Plugin::container();
$handler = $container->make('MT_Modern_Handler');
$result = $handler->process_evaluation();
```

## Performance Considerations

### Container Overhead

- Minimal overhead for service resolution
- Singleton pattern prevents duplicate instantiation
- Reflection is cached after first resolution

### Memory Management

```php
// Services are created only when needed
$container->singleton('ExpensiveService', function($container) {
    // This factory only runs when service is actually requested
    return new ExpensiveService();
});

// Avoid eager instantiation in providers
public function register() {
    // Good: Lazy loading
    $this->singleton('Service', function($container) {
        return new Service();
    });
    
    // Avoid: Eager instantiation
    $this->singleton('Service', new Service()); // Created immediately
}
```

### Caching Strategies

```php
// Repository caching doesn't interfere with DI
class MT_Cached_Repository implements MT_Repository_Interface {
    private $cache = [];
    
    public function find($id) {
        if (!isset($this->cache[$id])) {
            $this->cache[$id] = $this->fetch_from_database($id);
        }
        return $this->cache[$id];
    }
}
```

This guide provides comprehensive coverage of using the dependency injection container in the Mobility Trailblazers plugin. The container enables clean, testable, and maintainable code while preserving WordPress compatibility and plugin performance.