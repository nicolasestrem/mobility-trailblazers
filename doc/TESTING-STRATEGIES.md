# Testing Strategies - Dependency Injection Architecture

**Version:** 2.6.0+  
**Last Updated:** 2025-08-20  
**Architecture Version:** 2.0

## Table of Contents

1. [Overview](#overview)
2. [Testing Pyramid](#testing-pyramid)
3. [Unit Testing](#unit-testing)
4. [Integration Testing](#integration-testing)
5. [Functional Testing](#functional-testing)
6. [Mock Strategies](#mock-strategies)
7. [Container Testing](#container-testing)
8. [Test Data Management](#test-data-management)
9. [Testing Tools](#testing-tools)
10. [Best Practices](#best-practices)

## Overview

This document outlines testing strategies for the Mobility Trailblazers plugin with the new dependency injection architecture. The DI system significantly improves testability by enabling proper isolation and mocking of dependencies.

### Testing Philosophy

- **Test in Isolation**: Each component tested independently with mocked dependencies
- **Fast Feedback**: Unit tests run quickly without database or network access
- **Comprehensive Coverage**: Tests cover happy paths, edge cases, and error conditions
- **Maintainable Tests**: Tests are readable, focused, and easy to update
- **Production Parity**: Integration tests verify actual behavior in WordPress environment

## Testing Pyramid

```
         /\
        /  \  E2E Tests (5%)
       /    \ Browser automation, full system
      /──────\
     /        \ Functional Tests (15%)
    /          \ WordPress integration, AJAX
   /────────────\
  /              \ Integration Tests (30%)
 /                \ Service + Repository
/──────────────────\
                    \ Unit Tests (50%)
                     \ Individual classes
```

## Unit Testing

### Service Testing

Test services in complete isolation using mocked dependencies:

```php
class MT_Evaluation_Service_Test extends PHPUnit\Framework\TestCase {
    
    private $mockRepository;
    private $mockAssignmentRepo;
    private $service;
    
    public function setUp(): void {
        // Create mocks
        $this->mockRepository = $this->createMock(
            MT_Evaluation_Repository_Interface::class
        );
        $this->mockAssignmentRepo = $this->createMock(
            MT_Assignment_Repository_Interface::class
        );
        
        // Inject mocks into service
        $this->service = new MT_Evaluation_Service(
            $this->mockRepository,
            $this->mockAssignmentRepo
        );
    }
    
    public function test_process_valid_evaluation() {
        // Arrange
        $evaluation_data = [
            'jury_member_id' => 1,
            'candidate_id' => 2,
            'criterion_1' => 8.5,
            'criterion_2' => 9.0,
            'criterion_3' => 7.5,
            'criterion_4' => 8.0,
            'criterion_5' => 9.5
        ];
        
        // Configure mock expectations
        $this->mockAssignmentRepo->expects($this->once())
            ->method('exists')
            ->with(1, 2)
            ->willReturn(true);
        
        $this->mockRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function($data) {
                return $data['total_score'] === 42.5;
            }))
            ->willReturn(123);
        
        // Act
        $result = $this->service->process($evaluation_data);
        
        // Assert
        $this->assertEquals(123, $result);
        $this->assertEmpty($this->service->get_errors());
    }
    
    public function test_process_invalid_scores() {
        // Test validation with invalid scores
        $invalid_data = [
            'jury_member_id' => 1,
            'candidate_id' => 2,
            'criterion_1' => 11, // Invalid: > 10
        ];
        
        $result = $this->service->process($invalid_data);
        
        $this->assertFalse($result);
        $this->assertContains(
            'Score must be between 0 and 10',
            $this->service->get_errors()
        );
    }
    
    public function test_process_unassigned_jury() {
        // Test permission checking
        $this->mockAssignmentRepo->expects($this->once())
            ->method('exists')
            ->willReturn(false);
        
        $result = $this->service->process([
            'jury_member_id' => 1,
            'candidate_id' => 2
        ]);
        
        $this->assertFalse($result);
        $this->assertContains(
            'Not assigned to this candidate',
            $this->service->get_errors()
        );
    }
}
```

### Repository Testing

Test repositories with mocked database interactions:

```php
class MT_Evaluation_Repository_Test extends PHPUnit\Framework\TestCase {
    
    private $wpdbMock;
    private $repository;
    
    public function setUp(): void {
        // Mock global $wpdb
        $this->wpdbMock = $this->createMock(wpdb::class);
        $this->wpdbMock->prefix = 'wp_';
        
        // Inject mock via reflection or setter
        $this->repository = new MT_Evaluation_Repository();
        $this->injectWpdb($this->repository, $this->wpdbMock);
    }
    
    public function test_find_returns_evaluation() {
        $expected = (object) [
            'id' => 1,
            'jury_member_id' => 10,
            'candidate_id' => 20,
            'total_score' => 42.5
        ];
        
        $this->wpdbMock->expects($this->once())
            ->method('prepare')
            ->with(
                $this->stringContains('SELECT * FROM'),
                1
            )
            ->willReturn('PREPARED_QUERY');
        
        $this->wpdbMock->expects($this->once())
            ->method('get_row')
            ->with('PREPARED_QUERY')
            ->willReturn($expected);
        
        $result = $this->repository->find(1);
        
        $this->assertEquals($expected, $result);
    }
    
    public function test_create_returns_insert_id() {
        $this->wpdbMock->insert_id = 123;
        
        $this->wpdbMock->expects($this->once())
            ->method('insert')
            ->willReturn(1);
        
        $result = $this->repository->create(['data' => 'value']);
        
        $this->assertEquals(123, $result);
    }
    
    private function injectWpdb($repository, $wpdbMock) {
        $reflection = new ReflectionClass($repository);
        $property = $reflection->getProperty('wpdb');
        $property->setAccessible(true);
        $property->setValue($repository, $wpdbMock);
    }
}
```

### AJAX Handler Testing

Test AJAX handlers with container mocking:

```php
class MT_Evaluation_Ajax_Test extends WP_Ajax_UnitTestCase {
    
    private $containerMock;
    private $serviceMock;
    private $ajax;
    
    public function setUp(): void {
        parent::setUp();
        
        // Mock container and service
        $this->containerMock = $this->createMock(MT_Container::class);
        $this->serviceMock = $this->createMock(MT_Evaluation_Service::class);
        
        // Configure container to return mock service
        $this->containerMock->expects($this->any())
            ->method('make')
            ->with('MobilityTrailblazers\Services\MT_Evaluation_Service')
            ->willReturn($this->serviceMock);
        
        // Inject mock container
        MT_Plugin::set_test_container($this->containerMock);
        
        $this->ajax = new MT_Evaluation_Ajax();
    }
    
    public function test_submit_evaluation_success() {
        // Set up request
        $_POST['nonce'] = wp_create_nonce('mt_ajax_nonce');
        $_POST['evaluation_data'] = [
            'jury_member_id' => 1,
            'candidate_id' => 2,
            'scores' => [8, 9, 7, 8, 9]
        ];
        
        // Configure service mock
        $this->serviceMock->expects($this->once())
            ->method('process')
            ->willReturn(123);
        
        // Make AJAX call
        try {
            $this->_handleAjax('mt_submit_evaluation');
        } catch (WPAjaxDieContinueException $e) {
            // Expected
        }
        
        // Check response
        $response = json_decode($this->_last_response, true);
        $this->assertTrue($response['success']);
        $this->assertEquals(123, $response['data']['evaluation_id']);
    }
}
```

## Integration Testing

### Service Integration Tests

Test services with real repositories but test database:

```php
class MT_Evaluation_Service_Integration_Test extends WP_UnitTestCase {
    
    private $container;
    private $service;
    
    public function setUp(): void {
        parent::setUp();
        
        // Use real container with test database
        $this->container = new MT_Container();
        
        // Register real implementations
        $this->container->singleton(
            'MobilityTrailblazers\Repositories\MT_Evaluation_Repository',
            function() {
                return new MT_Evaluation_Repository();
            }
        );
        
        $this->container->singleton(
            'MobilityTrailblazers\Services\MT_Evaluation_Service',
            function($container) {
                return new MT_Evaluation_Service(
                    $container->make('MobilityTrailblazers\Repositories\MT_Evaluation_Repository'),
                    $container->make('MobilityTrailblazers\Repositories\MT_Assignment_Repository')
                );
            }
        );
        
        $this->service = $this->container->make(
            'MobilityTrailblazers\Services\MT_Evaluation_Service'
        );
        
        // Set up test data
        $this->create_test_data();
    }
    
    public function test_complete_evaluation_workflow() {
        // Create assignment
        $assignment_id = $this->create_assignment(1, 2);
        
        // Submit evaluation
        $evaluation_data = [
            'jury_member_id' => 1,
            'candidate_id' => 2,
            'criterion_1' => 8.5,
            'criterion_2' => 9.0,
            'criterion_3' => 7.5,
            'criterion_4' => 8.0,
            'criterion_5' => 9.5
        ];
        
        $evaluation_id = $this->service->process($evaluation_data);
        
        // Verify evaluation saved
        $this->assertIsInt($evaluation_id);
        
        // Retrieve and verify
        $saved = $this->service->get_evaluation($evaluation_id);
        $this->assertEquals(42.5, $saved->total_score);
        
        // Test update
        $update_data = array_merge($evaluation_data, [
            'id' => $evaluation_id,
            'criterion_1' => 9.0
        ]);
        
        $updated = $this->service->update($update_data);
        $this->assertTrue($updated);
        
        // Verify update
        $after_update = $this->service->get_evaluation($evaluation_id);
        $this->assertEquals(43.0, $after_update->total_score);
    }
}
```

### Container Integration Tests

Test container resolution and dependency chains:

```php
class MT_Container_Integration_Test extends WP_UnitTestCase {
    
    private $container;
    
    public function setUp(): void {
        parent::setUp();
        
        // Initialize container with providers
        $this->container = MT_Container::get_instance();
        $this->container->register_provider(
            new MT_Repository_Provider($this->container)
        );
        $this->container->register_provider(
            new MT_Services_Provider($this->container)
        );
    }
    
    public function test_service_resolution_chain() {
        // Test complete dependency chain resolution
        $service = $this->container->make(
            'MobilityTrailblazers\Services\MT_Evaluation_Service'
        );
        
        $this->assertInstanceOf(MT_Evaluation_Service::class, $service);
        
        // Verify dependencies were injected
        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('repository');
        $property->setAccessible(true);
        $repository = $property->getValue($service);
        
        $this->assertInstanceOf(
            MT_Evaluation_Repository_Interface::class,
            $repository
        );
    }
    
    public function test_singleton_behavior() {
        $service1 = $this->container->make('SingletonService');
        $service2 = $this->container->make('SingletonService');
        
        $this->assertSame($service1, $service2);
    }
    
    public function test_interface_resolution() {
        $implementation = $this->container->make(
            'MobilityTrailblazers\Interfaces\MT_Service_Interface'
        );
        
        $this->assertInstanceOf(
            MT_Service_Interface::class,
            $implementation
        );
    }
}
```

## Functional Testing

### WordPress Integration Tests

Test complete features in WordPress environment:

```php
class MT_Functional_Test extends WP_UnitTestCase {
    
    public function test_jury_evaluation_submission() {
        // Create jury member user
        $jury_user_id = $this->factory->user->create([
            'role' => 'mt_jury_member'
        ]);
        
        // Create candidate post
        $candidate_id = $this->factory->post->create([
            'post_type' => 'mt_candidate',
            'post_status' => 'publish'
        ]);
        
        // Log in as jury member
        wp_set_current_user($jury_user_id);
        
        // Create assignment
        $assignment_service = MT_Plugin::container()->make(
            'MobilityTrailblazers\Services\MT_Assignment_Service'
        );
        $assignment_service->assign($jury_user_id, $candidate_id);
        
        // Submit evaluation via AJAX
        $_POST = [
            'action' => 'mt_submit_evaluation',
            'nonce' => wp_create_nonce('mt_ajax_nonce'),
            'jury_member_id' => $jury_user_id,
            'candidate_id' => $candidate_id,
            'scores' => [8, 9, 7, 8, 9]
        ];
        
        // Process AJAX request
        do_action('wp_ajax_mt_submit_evaluation');
        
        // Verify evaluation saved
        global $wpdb;
        $evaluation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}mt_evaluations 
             WHERE jury_member_id = %d AND candidate_id = %d",
            $jury_user_id,
            $candidate_id
        ));
        
        $this->assertNotNull($evaluation);
        $this->assertEquals(41, $evaluation->total_score);
    }
}
```

## Mock Strategies

### Service Mocks

Create reusable mock builders:

```php
class MockBuilder {
    
    public static function createEvaluationService($test) {
        $mock = $test->getMockBuilder(MT_Evaluation_Service::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $mock->method('validate')
            ->willReturnCallback(function($data) {
                return isset($data['jury_member_id']) 
                    && isset($data['candidate_id']);
            });
        
        return $mock;
    }
    
    public static function createRepository($test, $returnData = []) {
        $mock = $test->createMock(MT_Repository_Interface::class);
        
        $mock->method('find')
            ->willReturnCallback(function($id) use ($returnData) {
                return $returnData[$id] ?? null;
            });
        
        $mock->method('create')
            ->willReturnCallback(function($data) {
                return rand(1, 1000);
            });
        
        return $mock;
    }
}
```

### Spy Pattern

Use spies to verify interactions:

```php
class RepositorySpy implements MT_Repository_Interface {
    
    public $calls = [];
    
    public function find($id) {
        $this->calls[] = ['method' => 'find', 'args' => [$id]];
        return (object) ['id' => $id];
    }
    
    public function create($data) {
        $this->calls[] = ['method' => 'create', 'args' => [$data]];
        return 123;
    }
    
    public function wasCalled($method, $args = null) {
        foreach ($this->calls as $call) {
            if ($call['method'] === $method) {
                if ($args === null || $call['args'] === $args) {
                    return true;
                }
            }
        }
        return false;
    }
    
    public function getCallCount($method) {
        return count(array_filter($this->calls, function($call) use ($method) {
            return $call['method'] === $method;
        }));
    }
}
```

### Stub Pattern

Create simple stubs for testing:

```php
class LoggerStub implements MT_Logger_Interface {
    public function log($level, $message, $context = []) {
        // Do nothing - stub implementation
    }
    
    public function error($message, $context = []) {
        // Do nothing
    }
    
    public function info($message, $context = []) {
        // Do nothing
    }
}
```

## Container Testing

### Test Container Setup

Create isolated test containers:

```php
class TestContainerFactory {
    
    public static function create() {
        $container = new MT_Container();
        
        // Register test implementations
        $container->singleton('Logger', 'TestLogger');
        $container->singleton('Cache', 'InMemoryCache');
        
        return $container;
    }
    
    public static function createWithMocks($test) {
        $container = new MT_Container();
        
        // Register mocks
        $container->singleton('Logger', function() use ($test) {
            return $test->createMock(MT_Logger_Interface::class);
        });
        
        $container->singleton('Repository', function() use ($test) {
            return $test->createMock(MT_Repository_Interface::class);
        });
        
        return $container;
    }
}
```

### Container Verification

Test container configuration:

```php
class ContainerConfigTest extends PHPUnit\Framework\TestCase {
    
    public function test_all_services_registered() {
        $container = MT_Plugin::container();
        
        $required_services = [
            'MobilityTrailblazers\Services\MT_Evaluation_Service',
            'MobilityTrailblazers\Services\MT_Assignment_Service',
            'MobilityTrailblazers\Repositories\MT_Evaluation_Repository',
            'MobilityTrailblazers\Repositories\MT_Assignment_Repository'
        ];
        
        foreach ($required_services as $service) {
            $this->assertTrue(
                $container->has($service),
                "Service not registered: {$service}"
            );
        }
    }
    
    public function test_interfaces_bound() {
        $container = MT_Plugin::container();
        
        $interfaces = [
            'MobilityTrailblazers\Interfaces\MT_Service_Interface',
            'MobilityTrailblazers\Interfaces\MT_Repository_Interface'
        ];
        
        foreach ($interfaces as $interface) {
            $implementation = $container->make($interface);
            $this->assertInstanceOf($interface, $implementation);
        }
    }
}
```

## Test Data Management

### Test Fixtures

Create reusable test data:

```php
class TestFixtures {
    
    public static function createJuryMember($overrides = []) {
        return wp_insert_user(array_merge([
            'user_login' => 'jury_' . uniqid(),
            'user_email' => 'jury_' . uniqid() . '@test.com',
            'role' => 'mt_jury_member',
            'first_name' => 'Test',
            'last_name' => 'Jury'
        ], $overrides));
    }
    
    public static function createCandidate($overrides = []) {
        return wp_insert_post(array_merge([
            'post_type' => 'mt_candidate',
            'post_title' => 'Test Candidate ' . uniqid(),
            'post_status' => 'publish',
            'meta_input' => [
                'company' => 'Test Company',
                'innovation' => 'Test Innovation'
            ]
        ], $overrides));
    }
    
    public static function createEvaluation($jury_id, $candidate_id, $scores = []) {
        global $wpdb;
        
        $data = [
            'jury_member_id' => $jury_id,
            'candidate_id' => $candidate_id,
            'criterion_1' => $scores[0] ?? 8,
            'criterion_2' => $scores[1] ?? 9,
            'criterion_3' => $scores[2] ?? 7,
            'criterion_4' => $scores[3] ?? 8,
            'criterion_5' => $scores[4] ?? 9,
            'total_score' => array_sum($scores ?: [8, 9, 7, 8, 9]),
            'status' => 'submitted',
            'created_at' => current_time('mysql')
        ];
        
        $wpdb->insert("{$wpdb->prefix}mt_evaluations", $data);
        return $wpdb->insert_id;
    }
}
```

### Database Transactions

Use transactions for test isolation:

```php
abstract class TransactionalTestCase extends WP_UnitTestCase {
    
    public function setUp(): void {
        parent::setUp();
        global $wpdb;
        $wpdb->query('START TRANSACTION');
    }
    
    public function tearDown(): void {
        global $wpdb;
        $wpdb->query('ROLLBACK');
        parent::tearDown();
    }
}
```

## Testing Tools

### PHPUnit Configuration

```xml
<?xml version="1.0"?>
<phpunit
    bootstrap="tests/bootstrap.php"
    backupGlobals="false"
    colors="true"
    convertErrorsToExceptions="true"
    convertNoticesToExceptions="true"
    convertWarningsToExceptions="true"
>
    <testsuites>
        <testsuite name="unit">
            <directory suffix="Test.php">tests/unit</directory>
        </testsuite>
        <testsuite name="integration">
            <directory suffix="Test.php">tests/integration</directory>
        </testsuite>
        <testsuite name="functional">
            <directory suffix="Test.php">tests/functional</directory>
        </testsuite>
    </testsuites>
    
    <filter>
        <whitelist>
            <directory suffix=".php">includes</directory>
            <exclude>
                <directory>includes/legacy</directory>
            </exclude>
        </whitelist>
    </filter>
    
    <logging>
        <log type="coverage-html" target="tests/coverage"/>
        <log type="coverage-clover" target="tests/logs/clover.xml"/>
    </logging>
</phpunit>
```

### Test Bootstrap

```php
// tests/bootstrap.php
$_tests_dir = getenv('WP_TESTS_DIR');

if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

// Load test library
require_once $_tests_dir . '/includes/functions.php';

// Load plugin
function _manually_load_plugin() {
    require dirname(dirname(__FILE__)) . '/mobility-trailblazers.php';
    
    // Initialize test container
    $container = MT_Container::get_instance();
    $container->register_provider(new Test_Service_Provider($container));
}
tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment
require $_tests_dir . '/includes/bootstrap.php';

// Load test helpers
require_once dirname(__FILE__) . '/helpers/class-test-case.php';
require_once dirname(__FILE__) . '/helpers/class-mock-builder.php';
require_once dirname(__FILE__) . '/helpers/class-test-fixtures.php';
```

## Best Practices

### 1. Test Organization

```
tests/
├── unit/
│   ├── services/
│   │   ├── EvaluationServiceTest.php
│   │   └── AssignmentServiceTest.php
│   ├── repositories/
│   │   └── EvaluationRepositoryTest.php
│   └── ajax/
│       └── EvaluationAjaxTest.php
├── integration/
│   ├── ContainerTest.php
│   └── ServiceIntegrationTest.php
├── functional/
│   └── EvaluationWorkflowTest.php
└── helpers/
    ├── MockBuilder.php
    └── TestFixtures.php
```

### 2. Test Naming

```php
// Method: test_[method]_[scenario]_[expected_result]
public function test_process_valid_evaluation_returns_id() { }
public function test_validate_missing_required_fields_returns_false() { }
public function test_save_database_error_throws_exception() { }
```

### 3. Assertion Patterns

```php
// Use specific assertions
$this->assertInstanceOf(ServiceClass::class, $service);
$this->assertCount(3, $results);
$this->assertArrayHasKey('error', $response);

// Custom assertions
$this->assertServiceValid($service);
$this->assertRepositoryContains($repository, $expected);
```

### 4. Mock Configuration

```php
// Configure mocks clearly
$mock->expects($this->once())        // Frequency
     ->method('save')                 // Method
     ->with($this->equalTo($data))   // Parameters
     ->willReturn(123);               // Return value

// Use argument matchers
->with(
    $this->greaterThan(0),
    $this->stringContains('test'),
    $this->anything()
)
```

### 5. Test Documentation

```php
/**
 * @test
 * @covers MT_Evaluation_Service::process
 * @dataProvider valid_evaluation_provider
 */
public function it_processes_valid_evaluations($data, $expected) {
    // Test implementation
}

/**
 * @group slow
 * @requires PHP 7.4
 */
public function test_complex_integration() {
    // Test implementation
}
```

This comprehensive testing strategy guide ensures robust testing of the dependency injection architecture while maintaining high code quality and reliability.