# Mobility Trailblazers Plugin - Architecture Documentation

## Table of Contents
1. [Overview](#overview)
2. [Architecture Principles](#architecture-principles)
3. [Directory Structure](#directory-structure)
4. [Core Components](#core-components)
5. [Design Patterns](#design-patterns)
6. [Code Examples](#code-examples)
7. [Migration Guide](#migration-guide)

## Overview

As of version 1.0.7, the Mobility Trailblazers plugin has been refactored to follow modern PHP best practices and SOLID principles. The architecture now features:

- **Repository Pattern** for data access layer
- **Service Layer** for business logic
- **Namespace Support** with PSR-4 autoloading
- **Dependency Injection** for better testability
- **Separation of Concerns** with single responsibility per class

## Architecture Principles

### 1. Single Responsibility Principle (SRP)
Each class has one reason to change:
- Repositories handle only database operations
- Services contain only business logic
- AJAX handlers only manage HTTP request/response

### 2. Open/Closed Principle (OCP)
Classes are open for extension but closed for modification:
- Interfaces define contracts
- New features extend existing classes rather than modifying them

### 3. Dependency Inversion Principle (DIP)
High-level modules depend on abstractions:
- Services depend on repository interfaces
- AJAX handlers depend on service interfaces

## Directory Structure

```
mobility-trailblazers/
├── includes/
│   ├── interfaces/              # Interface definitions
│   │   ├── interface-mt-repository.php
│   │   └── interface-mt-service.php
│   ├── repositories/            # Data access layer
│   │   ├── class-mt-evaluation-repository.php
│   │   ├── class-mt-assignment-repository.php
│   │   ├── class-mt-candidate-repository.php
│   │   ├── class-mt-jury-repository.php
│   │   └── class-mt-voting-repository.php
│   ├── services/                # Business logic layer
│   │   ├── class-mt-evaluation-service.php
│   │   ├── class-mt-assignment-service.php
│   │   ├── class-mt-voting-service.php
│   │   └── class-mt-notification-service.php
│   ├── ajax/                    # AJAX handlers (future)
│   │   ├── class-mt-base-ajax.php
│   │   ├── class-mt-evaluation-ajax.php
│   │   ├── class-mt-assignment-ajax.php
│   │   └── class-mt-voting-ajax.php
│   ├── class-mt-autoloader.php # PSR-4 autoloader
│   └── class-mt-ajax-handlers.php # Legacy AJAX (being refactored)
```

## Core Components

### 1. Interfaces

#### Repository Interface
```php
namespace MobilityTrailblazers\Interfaces;

interface MT_Repository_Interface {
    public function find($id);
    public function find_all($args = array());
    public function create($data);
    public function update($id, $data);
    public function delete($id);
}
```

#### Service Interface
```php
namespace MobilityTrailblazers\Interfaces;

interface MT_Service_Interface {
    public function process($data);
    public function validate($data);
    public function get_errors();
}
```

### 2. Repositories

Repositories handle all database operations and return raw data.

#### Key Features:
- No business logic
- Prepared statements for security
- Consistent return types
- Database table abstraction

#### Example Methods:
```php
// MT_Evaluation_Repository
- exists($jury_member_id, $candidate_id)
- get_by_jury_member($jury_member_id)
- get_by_candidate($candidate_id)
- get_average_score_for_candidate($candidate_id)

// MT_Assignment_Repository
- bulk_create($assignments)
- delete_by_jury_member($jury_member_id)
- get_statistics()

// MT_Voting_Repository
- has_voted($voter_email, $candidate_id)
- get_vote_counts($category_id = null)
- create_backup()
- clear_all()
```

### 3. Services

Services contain business logic and coordinate between repositories.

#### Key Features:
- Validation logic
- Business rules enforcement
- Transaction coordination
- Event triggering

#### Example Methods:
```php
// MT_Evaluation_Service
- process($data) // Submit evaluation
- save_draft($data) // Save as draft
- calculate_total_score($scores) // Business logic

// MT_Assignment_Service
- distribute_candidates($jury_members, $candidates, $per_jury)
- process_auto_assignment($data)
- remove_assignment($jury_id, $candidate_id)

// MT_Voting_Service
- process_vote($data)
- validate_voter($data)
- calculate_results($category_id)
- reset_votes()
```

### 4. Autoloader

The custom autoloader supports PSR-4 namespacing:

```php
// Namespace: MobilityTrailblazers\Services\MT_Evaluation_Service
// File: includes/services/class-mt-evaluation-service.php

// Usage:
use MobilityTrailblazers\Services\MT_Evaluation_Service;
$service = new MT_Evaluation_Service();
```

## Design Patterns

### 1. Repository Pattern
**Purpose**: Encapsulate data access logic

```php
class MT_Evaluation_Repository implements MT_Repository_Interface {
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'mt_evaluations';
    }
    
    public function find($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ));
    }
}
```

### 2. Service Layer Pattern
**Purpose**: Encapsulate business logic

```php
class MT_Evaluation_Service implements MT_Service_Interface {
    private $repository;
    private $errors = array();
    
    public function __construct() {
        $this->repository = new MT_Evaluation_Repository();
    }
    
    public function process($data) {
        if (!$this->validate($data)) {
            return false;
        }
        
        // Business logic here
        $total_score = $this->calculate_total_score($data['scores']);
        
        // Use repository for data access
        return $this->repository->create($data);
    }
}
```

### 3. Dependency Injection
**Purpose**: Improve testability and flexibility

```php
// Future improvement - allow injection
class MT_Evaluation_Service {
    private $repository;
    
    public function __construct(MT_Repository_Interface $repository = null) {
        $this->repository = $repository ?: new MT_Evaluation_Repository();
    }
}
```

## Code Examples

### 1. Processing an Evaluation

```php
// In AJAX handler
public function submit_evaluation() {
    $this->verify_nonce();
    $this->check_permission('mt_submit_evaluations');
    
    // Prepare data
    $data = array(
        'jury_member_id' => get_current_user_id(),
        'candidate_id' => intval($_POST['candidate_id']),
        'scores' => $_POST['scores'],
        'comments' => $_POST['comments']
    );
    
    // Use service
    $service = new \MobilityTrailblazers\Services\MT_Evaluation_Service();
    $result = $service->process($data);
    
    if (!$result) {
        wp_send_json_error(array(
            'message' => __('Failed to save evaluation', 'mobility-trailblazers'),
            'errors' => $service->get_errors()
        ));
    }
    
    wp_send_json_success(array(
        'message' => __('Evaluation submitted successfully!', 'mobility-trailblazers')
    ));
}
```

### 2. Auto-Assigning Candidates

```php
// Using assignment service
$service = new \MobilityTrailblazers\Services\MT_Assignment_Service();

$result = $service->process(array(
    'assignment_type' => 'auto',
    'candidates_per_jury' => 5,
    'clear_existing' => true
));

if (!$result) {
    $errors = $service->get_errors();
    // Handle errors
}
```

### 3. Getting Statistics

```php
// Using repository directly for read operations
$repository = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
$stats = $repository->get_statistics();

// Returns:
// [
//     'total' => 150,
//     'completed' => 120,
//     'drafts' => 30,
//     'average_score' => 7.5
// ]
```

## Migration Guide

### For Developers

#### 1. Updating Direct Database Queries

**Before:**
```php
global $wpdb;
$table = $wpdb->prefix . 'mt_evaluations';
$result = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table} WHERE jury_member_id = %d",
    $jury_id
));
```

**After:**
```php
use MobilityTrailblazers\Repositories\MT_Evaluation_Repository;

$repository = new MT_Evaluation_Repository();
$result = $repository->get_by_jury_member($jury_id);
```

#### 2. Updating Business Logic

**Before:**
```php
// Mixed concerns in AJAX handler
public function submit_evaluation() {
    // Validation
    if (empty($_POST['scores'])) {
        wp_send_json_error('Invalid scores');
    }
    
    // Business logic
    $total = 0;
    foreach ($_POST['scores'] as $score) {
        $total += $score;
    }
    
    // Database operation
    global $wpdb;
    $wpdb->insert(...);
}
```

**After:**
```php
// Clean separation
public function submit_evaluation() {
    $service = new \MobilityTrailblazers\Services\MT_Evaluation_Service();
    $result = $service->process($_POST);
    
    if (!$result) {
        wp_send_json_error($service->get_errors());
    }
    
    wp_send_json_success();
}
```

### For Users

No changes required. All existing functionality remains the same with improved performance and reliability.

## Best Practices

### 1. Always Use Services for Business Logic
```php
// Good
$service = new MT_Evaluation_Service();
$result = $service->process($data);

// Bad - bypassing business logic
$repository = new MT_Evaluation_Repository();
$repository->create($data); // Skips validation!
```

### 2. Handle Errors Properly
```php
$service = new MT_Assignment_Service();
$result = $service->process($data);

if (!$result) {
    $errors = $service->get_errors();
    foreach ($errors as $error) {
        // Log or display error
    }
}
```

### 3. Use Namespaces
```php
// At top of file
use MobilityTrailblazers\Services\MT_Evaluation_Service;
use MobilityTrailblazers\Services\MT_Notification_Service;

// In code
$evaluation_service = new MT_Evaluation_Service();
$notification_service = new MT_Notification_Service();
```

### 4. Maintain Backward Compatibility
```php
// Wrapper function for legacy code
function mt_get_evaluation_stats($candidate_id) {
    $repository = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
    return $repository->get_statistics(array('candidate_id' => $candidate_id));
}
```

## Performance Considerations

### 1. Lazy Loading
Services and repositories are instantiated only when needed:
```php
public function __construct() {
    // Don't instantiate here
}

public function process($data) {
    // Instantiate when needed
    $this->repository = new MT_Evaluation_Repository();
}
```

### 2. Query Optimization
Repositories use optimized queries:
```php
// Single query with JOIN instead of multiple queries
public function get_candidate_with_scores($candidate_id) {
    global $wpdb;
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT c.*, AVG(e.total_score) as avg_score
         FROM {$wpdb->posts} c
         LEFT JOIN {$this->table_name} e ON c.ID = e.candidate_id
         WHERE c.ID = %d AND c.post_type = 'mt_candidate'
         GROUP BY c.ID",
        $candidate_id
    ));
}
```

### 3. Caching Strategy
Future implementation will include caching:
```php
public function get_statistics($args = array()) {
    $cache_key = 'mt_eval_stats_' . md5(serialize($args));
    $cached = wp_cache_get($cache_key);
    
    if (false !== $cached) {
        return $cached;
    }
    
    $stats = $this->calculate_statistics($args);
    wp_cache_set($cache_key, $stats, '', 300); // 5 minutes
    
    return $stats;
}
```

## Testing

### Unit Testing Services
Services can be tested in isolation:
```php
class MT_Evaluation_Service_Test extends WP_UnitTestCase {
    
    public function test_validation() {
        $service = new MT_Evaluation_Service();
        
        // Test invalid data
        $result = $service->validate(array());
        $this->assertFalse($result);
        $this->assertNotEmpty($service->get_errors());
        
        // Test valid data
        $result = $service->validate(array(
            'jury_member_id' => 1,
            'candidate_id' => 1,
            'scores' => array(5, 5, 5, 5, 5)
        ));
        $this->assertTrue($result);
    }
}
```

### Integration Testing
Test complete workflows:
```php
public function test_evaluation_workflow() {
    // Create test data
    $jury_id = $this->factory->user->create(array('role' => 'mt_jury_member'));
    $candidate_id = $this->factory->post->create(array('post_type' => 'mt_candidate'));
    
    // Test submission
    $service = new MT_Evaluation_Service();
    $result = $service->process(array(
        'jury_member_id' => $jury_id,
        'candidate_id' => $candidate_id,
        'scores' => array(8, 7, 9, 8, 7),
        'comments' => 'Test evaluation'
    ));
    
    $this->assertNotFalse($result);
    
    // Verify in database
    $repository = new MT_Evaluation_Repository();
    $evaluation = $repository->find($result);
    
    $this->assertEquals(39, $evaluation->total_score);
}
```

## Future Improvements

### 1. Dependency Injection Container
```php
// Future implementation
$container = new MT_Container();
$container->bind('evaluation_repository', MT_Evaluation_Repository::class);
$container->bind('evaluation_service', MT_Evaluation_Service::class);

$service = $container->get('evaluation_service');
```

### 2. Event System
```php
// Future implementation
class MT_Evaluation_Service {
    public function process($data) {
        // Before processing
        do_action('mt_before_evaluation_process', $data);
        
        $result = $this->repository->create($data);
        
        // After processing
        do_action('mt_after_evaluation_process', $result, $data);
        
        return $result;
    }
}
```

### 3. API Versioning
```php
// Future implementation
namespace MobilityTrailblazers\Api\V2;

class MT_Evaluation_Service {
    // Version 2 implementation
}
```

## Troubleshooting

### Common Issues

1. **Class not found errors**
   - Ensure autoloader is registered
   - Check namespace and file naming
   - Clear any opcode caches

2. **Database errors**
   - Verify tables exist
   - Check column names match
   - Ensure proper permissions

3. **Service errors**
   - Check error messages with `get_errors()`
   - Verify data validation rules
   - Check repository methods

### Debug Mode

Enable debug logging:
```php
define('MT_DEBUG', true);

// In service
if (defined('MT_DEBUG') && MT_DEBUG) {
    error_log('MT_Evaluation_Service: ' . print_r($data, true));
}
```

## Conclusion

The refactored architecture provides:
- **Better maintainability** through separation of concerns
- **Improved testability** with dependency injection
- **Enhanced security** through centralized validation
- **Greater flexibility** for future enhancements
- **Consistent code style** across the plugin

For questions or contributions, please refer to the project's GitHub repository.