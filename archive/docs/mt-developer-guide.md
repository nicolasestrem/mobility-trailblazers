# Mobility Trailblazers - Developer Guide

## Table of Contents
1. [Getting Started](#getting-started)
2. [Creating Custom Repositories](#creating-custom-repositories)
3. [Creating Custom Services](#creating-custom-services)
4. [Extending Existing Functionality](#extending-existing-functionality)
5. [Adding New Features](#adding-new-features)
6. [Testing](#testing)
7. [Common Patterns](#common-patterns)
8. [Troubleshooting](#troubleshooting)

## Getting Started

### Development Environment Setup

1. **Enable Debug Mode**
   ```php
   // In wp-config.php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   define('MT_DEBUG', true);
   ```

2. **Check Plugin Structure**
   ```bash
   mobility-trailblazers/
   ├── includes/
   │   ├── interfaces/
   │   ├── repositories/
   │   ├── services/
   │   └── class-mt-autoloader.php
   ```

3. **Understand Namespacing**
   ```php
   // All custom classes use this namespace structure
   namespace MobilityTrailblazers\{Type}\{ClassName};
   
   // Examples:
   namespace MobilityTrailblazers\Services\MT_Custom_Service;
   namespace MobilityTrailblazers\Repositories\MT_Custom_Repository;
   ```

## Creating Custom Repositories

### Step 1: Create Repository Interface (if needed)

```php
<?php
// File: includes/interfaces/interface-mt-custom-repository.php

namespace MobilityTrailblazers\Interfaces;

interface MT_Custom_Repository_Interface extends MT_Repository_Interface {
    public function custom_method($param);
}
```

### Step 2: Implement Repository Class

```php
<?php
// File: includes/repositories/class-mt-custom-repository.php

namespace MobilityTrailblazers\Repositories;

use MobilityTrailblazers\Interfaces\MT_Repository_Interface;

class MT_Custom_Repository implements MT_Repository_Interface {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'mt_custom_table';
    }
    
    /**
     * Find record by ID
     */
    public function find($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ));
    }
    
    /**
     * Find all records with filters
     */
    public function find_all($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => null,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Build query
        $where_clauses = array('1=1');
        $values = array();
        
        if ($args['status'] !== null) {
            $where_clauses[] = 'status = %s';
            $values[] = $args['status'];
        }
        
        $where = implode(' AND ', $where_clauses);
        $orderby = sprintf('%s %s', 
            esc_sql($args['orderby']), 
            esc_sql($args['order'])
        );
        
        $query = "SELECT * FROM {$this->table_name} 
                  WHERE {$where} 
                  ORDER BY {$orderby} 
                  LIMIT %d OFFSET %d";
        
        $values[] = $args['limit'];
        $values[] = $args['offset'];
        
        return $wpdb->get_results(
            $wpdb->prepare($query, $values)
        );
    }
    
    /**
     * Create new record
     */
    public function create($data) {
        global $wpdb;
        
        $defaults = array(
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $result = $wpdb->insert(
            $this->table_name,
            $data,
            $this->get_column_formats($data)
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Update record
     */
    public function update($id, $data) {
        global $wpdb;
        
        $data['updated_at'] = current_time('mysql');
        
        return $wpdb->update(
            $this->table_name,
            $data,
            array('id' => $id),
            $this->get_column_formats($data),
            array('%d')
        );
    }
    
    /**
     * Delete record
     */
    public function delete($id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
    }
    
    /**
     * Get column formats for wpdb
     */
    private function get_column_formats($data) {
        $formats = array();
        
        foreach ($data as $key => $value) {
            if (is_int($value)) {
                $formats[] = '%d';
            } elseif (is_float($value)) {
                $formats[] = '%f';
            } else {
                $formats[] = '%s';
            }
        }
        
        return $formats;
    }
    
    /**
     * Custom method example: Bulk update status
     */
    public function bulk_update_status($ids, $status) {
        global $wpdb;
        
        if (empty($ids)) {
            return 0;
        }
        
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $values = $ids;
        $values[] = $status;
        
        return $wpdb->query($wpdb->prepare(
            "UPDATE {$this->table_name} 
             SET status = %s, updated_at = NOW() 
             WHERE id IN ({$placeholders})",
            $values
        ));
    }
}
```

### Step 3: Create Database Table

```php
// In your activation hook or database class
public function create_custom_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mt_custom_table';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id int(11) NOT NULL AUTO_INCREMENT,
        user_id int(11) NOT NULL,
        status varchar(50) DEFAULT 'active',
        data longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY status (status)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
```

## Creating Custom Services

### Step 1: Create Service Class

```php
<?php
// File: includes/services/class-mt-custom-service.php

namespace MobilityTrailblazers\Services;

use MobilityTrailblazers\Interfaces\MT_Service_Interface;
use MobilityTrailblazers\Repositories\MT_Custom_Repository;

class MT_Custom_Service implements MT_Service_Interface {
    
    private $repository;
    private $errors = array();
    
    public function __construct() {
        $this->repository = new MT_Custom_Repository();
    }
    
    /**
     * Process the main action
     */
    public function process($data) {
        $this->errors = array();
        
        // Validate
        if (!$this->validate($data)) {
            return false;
        }
        
        // Business logic
        $processed_data = $this->prepare_data($data);
        
        // Check business rules
        if (!$this->check_business_rules($processed_data)) {
            return false;
        }
        
        // Save using repository
        $result = $this->repository->create($processed_data);
        
        if ($result) {
            // Trigger action for other plugins
            do_action('mt_custom_processed', $result, $processed_data);
            
            // Maybe send notification
            $this->maybe_send_notification($result);
        }
        
        return $result;
    }
    
    /**
     * Validate input data
     */
    public function validate($data) {
        $valid = true;
        
        // Required fields
        if (empty($data['user_id'])) {
            $this->errors[] = __('User ID is required', 'mobility-trailblazers');
            $valid = false;
        }
        
        // Validate user exists and has permission
        if (!empty($data['user_id'])) {
            $user = get_user_by('id', $data['user_id']);
            if (!$user) {
                $this->errors[] = __('Invalid user', 'mobility-trailblazers');
                $valid = false;
            } elseif (!user_can($user, 'mt_use_custom_feature')) {
                $this->errors[] = __('User lacks permission', 'mobility-trailblazers');
                $valid = false;
            }
        }
        
        // Custom validation
        $valid = apply_filters('mt_custom_validate', $valid, $data, $this);
        
        return $valid;
    }
    
    /**
     * Get validation errors
     */
    public function get_errors() {
        return $this->errors;
    }
    
    /**
     * Prepare data for storage
     */
    private function prepare_data($data) {
        $prepared = array(
            'user_id' => intval($data['user_id']),
            'status' => 'pending',
            'data' => json_encode($data)
        );
        
        // Allow filtering
        return apply_filters('mt_custom_prepare_data', $prepared, $data);
    }
    
    /**
     * Check business rules
     */
    private function check_business_rules($data) {
        // Example: Check user hasn't exceeded limit
        $user_count = $this->repository->get_user_count($data['user_id']);
        
        if ($user_count >= 10) {
            $this->errors[] = __('User has reached the maximum limit', 'mobility-trailblazers');
            return false;
        }
        
        // Example: Check time restrictions
        $current_hour = date('H');
        if ($current_hour < 8 || $current_hour > 18) {
            $this->errors[] = __('This action is only available during business hours', 'mobility-trailblazers');
            return false;
        }
        
        return true;
    }
    
    /**
     * Send notification if needed
     */
    private function maybe_send_notification($id) {
        $send_notification = apply_filters('mt_custom_should_notify', true, $id);
        
        if ($send_notification) {
            $notification_service = new MT_Notification_Service();
            $notification_service->send_custom_notification($id);
        }
    }
    
    /**
     * Batch process multiple items
     */
    public function batch_process($items) {
        $results = array(
            'success' => 0,
            'failed' => 0,
            'errors' => array()
        );
        
        foreach ($items as $item) {
            $result = $this->process($item);
            
            if ($result) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = array(
                    'item' => $item,
                    'errors' => $this->get_errors()
                );
            }
            
            // Clear errors for next iteration
            $this->errors = array();
        }
        
        return $results;
    }
}
```

### Step 2: Create AJAX Handler

```php
<?php
// File: includes/ajax/class-mt-custom-ajax.php

namespace MobilityTrailblazers\Ajax;

class MT_Custom_Ajax extends MT_Base_Ajax {
    
    public function __construct() {
        add_action('wp_ajax_mt_custom_action', array($this, 'handle_custom_action'));
        add_action('wp_ajax_nopriv_mt_custom_action', array($this, 'handle_custom_action_public'));
    }
    
    /**
     * Handle custom action for logged-in users
     */
    public function handle_custom_action() {
        $this->verify_nonce('mt_custom_nonce');
        $this->check_permission('mt_use_custom_feature');
        
        $data = array(
            'user_id' => get_current_user_id(),
            'field1' => sanitize_text_field($_POST['field1'] ?? ''),
            'field2' => intval($_POST['field2'] ?? 0)
        );
        
        $service = new \MobilityTrailblazers\Services\MT_Custom_Service();
        $result = $service->process($data);
        
        if ($result) {
            $this->success(
                array('id' => $result),
                __('Action completed successfully', 'mobility-trailblazers')
            );
        } else {
            $this->error(
                __('Action failed', 'mobility-trailblazers'),
                array('errors' => $service->get_errors())
            );
        }
    }
    
    /**
     * Handle public version
     */
    public function handle_custom_action_public() {
        $this->verify_nonce('mt_custom_public_nonce');
        
        // Different logic for non-logged-in users
        $this->error(__('Please log in to use this feature', 'mobility-trailblazers'));
    }
}
```

## Extending Existing Functionality

### Adding New Evaluation Criteria

```php
// In your plugin or theme
add_filter('mt_evaluation_criteria', function($criteria) {
    // Add new criterion
    $criteria['sustainability'] = array(
        'label' => __('Sustainability Impact', 'my-plugin'),
        'description' => __('Environmental and social sustainability', 'my-plugin'),
        'weight' => 1.2, // 20% more weight than standard criteria
        'max_score' => 10
    );
    
    // Modify existing criterion
    $criteria['innovation']['weight'] = 2.0; // Double the weight
    
    return $criteria;
});
```

### Customizing Assignment Distribution

```php
// Hook into assignment distribution
add_filter('mt_assignment_distribution_algorithm', function($assignments, $jury_members, $candidates) {
    // Custom distribution logic
    $custom_assignments = array();
    
    // Example: Assign based on expertise matching
    foreach ($jury_members as $jury_id) {
        $expertise = get_user_meta($jury_id, 'expertise_areas', true);
        
        foreach ($candidates as $candidate_id) {
            $category = wp_get_post_terms($candidate_id, 'mt_category', array('fields' => 'slugs'));
            
            if (array_intersect($expertise, $category)) {
                $custom_assignments[] = array(
                    'jury_member_id' => $jury_id,
                    'candidate_id' => $candidate_id
                );
            }
        }
    }
    
    return $custom_assignments;
}, 10, 3);
```

### Adding Custom Validation

```php
// Add validation to evaluation submission
add_filter('mt_evaluation_validate', function($valid, $data, $service) {
    // Custom validation rule
    if (isset($data['scores']['innovation']) && $data['scores']['innovation'] > 8) {
        // Require justification for high innovation scores
        if (empty($data['innovation_justification'])) {
            $service->add_error(__('Please justify high innovation score', 'my-plugin'));
            return false;
        }
    }
    
    return $valid;
}, 10, 3);
```

## Adding New Features

### Example: Adding a Review System

```php
// 1. Create Review Repository
namespace MyPlugin\Repositories;

use MobilityTrailblazers\Interfaces\MT_Repository_Interface;

class Review_Repository implements MT_Repository_Interface {
    // Implementation similar to above
}

// 2. Create Review Service
namespace MyPlugin\Services;

use MobilityTrailblazers\Interfaces\MT_Service_Interface;

class Review_Service implements MT_Service_Interface {
    
    private $repository;
    private $evaluation_repo;
    
    public function __construct() {
        $this->repository = new \MyPlugin\Repositories\Review_Repository();
        $this->evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
    }
    
    public function process($data) {
        // Get evaluation
        $evaluation = $this->evaluation_repo->find($data['evaluation_id']);
        
        if (!$evaluation) {
            $this->errors[] = __('Evaluation not found', 'my-plugin');
            return false;
        }
        
        // Check reviewer permission
        if (!current_user_can('mt_review_evaluations')) {
            $this->errors[] = __('Permission denied', 'my-plugin');
            return false;
        }
        
        // Create review
        return $this->repository->create(array(
            'evaluation_id' => $evaluation->id,
            'reviewer_id' => get_current_user_id(),
            'status' => $data['status'],
            'comments' => sanitize_textarea_field($data['comments'])
        ));
    }
    
    public function validate($data) {
        // Validation logic
    }
    
    public function get_errors() {
        return $this->errors;
    }
}

// 3. Add UI Hook
add_action('mt_after_evaluation_display', function($evaluation) {
    if (current_user_can('mt_review_evaluations')) {
        include 'templates/review-form.php';
    }
});
```

## Testing

### Unit Testing Services

```php
class Test_Custom_Service extends WP_UnitTestCase {
    
    private $service;
    
    public function setUp() {
        parent::setUp();
        $this->service = new \MobilityTrailblazers\Services\MT_Custom_Service();
    }
    
    public function test_validation_fails_without_user_id() {
        $result = $this->service->validate(array());
        
        $this->assertFalse($result);
        $this->assertContains('User ID is required', $this->service->get_errors());
    }
    
    public function test_process_creates_record() {
        // Create test user
        $user_id = $this->factory->user->create(array(
            'role' => 'administrator'
        ));
        
        // Add capability
        $user = get_user_by('id', $user_id);
        $user->add_cap('mt_use_custom_feature');
        
        // Test data
        $data = array(
            'user_id' => $user_id,
            'field1' => 'test value',
            'field2' => 123
        );
        
        // Process
        $result = $this->service->process($data);
        
        // Assert
        $this->assertNotFalse($result);
        $this->assertIsInt($result);
    }
    
    public function test_batch_process() {
        $items = array(
            array('user_id' => 1, 'field1' => 'test1'),
            array('user_id' => 2, 'field1' => 'test2'),
            array('user_id' => 0, 'field1' => 'invalid') // Should fail
        );
        
        $results = $this->service->batch_process($items);
        
        $this->assertEquals(2, $results['success']);
        $this->assertEquals(1, $results['failed']);
        $this->assertCount(1, $results['errors']);
    }
}
```

### Integration Testing

```php
class Test_Custom_Integration extends WP_UnitTestCase {
    
    public function test_complete_workflow() {
        // 1. Create test data
        $user = $this->factory->user->create_and_get(array(
            'role' => 'mt_jury_member'
        ));
        
        $candidate = $this->factory->post->create(array(
            'post_type' => 'mt_candidate',
            'post_status' => 'publish'
        ));
        
        // 2. Create assignment
        $assignment_service = new \MobilityTrailblazers\Services\MT_Assignment_Service();
        $assignment_service->process(array(
            'jury_member_id' => $user->ID,
            'candidate_id' => $candidate
        ));
        
        // 3. Submit evaluation
        wp_set_current_user($user->ID);
        
        $evaluation_service = new \MobilityTrailblazers\Services\MT_Evaluation_Service();
        $evaluation_id = $evaluation_service->process(array(
            'jury_member_id' => $user->ID,
            'candidate_id' => $candidate,
            'scores' => array(
                'courage' => 8,
                'innovation' => 9,
                'implementation' => 7,
                'relevance' => 8,
                'visibility' => 9
            )
        ));
        
        // 4. Verify
        $this->assertNotFalse($evaluation_id);
        
        // 5. Check if can evaluate again
        $this->assertFalse(mt_user_can_evaluate($candidate, $user->ID));
    }
}
```

### Testing AJAX Endpoints

```php
class Test_Custom_Ajax extends WP_Ajax_UnitTestCase {
    
    public function test_ajax_custom_action() {
        // Set user
        $user_id = $this->factory->user->create(array(
            'role' => 'administrator'
        ));
        wp_set_current_user($user_id);
        
        // Set POST data
        $_POST = array(
            'action' => 'mt_custom_action',
            'nonce' => wp_create_nonce('mt_custom_nonce'),
            'field1' => 'test value',
            'field2' => '123'
        );
        
        // Capture response
        try {
            $this->_handleAjax('mt_custom_action');
        } catch (WPAjaxDieContinueException $e) {
            // Expected
        }
        
        // Check response
        $response = json_decode($this->_last_response, true);
        
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('id', $response['data']);
    }
}
```

## Common Patterns

### Singleton Pattern for Heavy Services

```php
class MT_Heavy_Service {
    
    private static $instance = null;
    private $cache = array();
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Heavy initialization
        $this->load_cache();
    }
}

// Usage
$service = MT_Heavy_Service::get_instance();
```

### Factory Pattern for Creating Objects

```php
class MT_Notification_Factory {
    
    public static function create($type, $data) {
        switch ($type) {
            case 'email':
                return new MT_Email_Notification($data);
            case 'sms':
                return new MT_SMS_Notification($data);
            case 'push':
                return new MT_Push_Notification($data);
            default:
                throw new InvalidArgumentException("Unknown notification type: {$type}");
        }
    }
}

// Usage
$notification = MT_Notification_Factory::create('email', $data);
$notification->send();
```

### Observer Pattern with WordPress Hooks

```php
class MT_Evaluation_Observer {
    
    public function __construct() {
        add_action('mt_evaluation_submitted', array($this, 'on_evaluation_submitted'), 10, 3);
        add_action('mt_evaluation_updated', array($this, 'on_evaluation_updated'), 10, 2);
    }
    
    public function on_evaluation_submitted($evaluation_id, $candidate_id, $jury_member_id) {
        // Update statistics
        $this->update_candidate_statistics($candidate_id);
        
        // Check if all evaluations complete
        if ($this->all_evaluations_complete($candidate_id)) {
            do_action('mt_candidate_evaluation_complete', $candidate_id);
        }
    }
    
    public function on_evaluation_updated($evaluation_id, $old_data) {
        // Recalculate scores if needed
        $evaluation = $this->get_evaluation($evaluation_id);
        if ($evaluation->total_score !== $old_data->total_score) {
            $this->recalculate_candidate_average($evaluation->candidate_id);
        }
    }
}

// Initialize observer
new MT_Evaluation_Observer();
```

### Decorator Pattern for Extending Functionality

```php
abstract class MT_Repository_Decorator implements MT_Repository_Interface {
    
    protected $repository;
    
    public function __construct(MT_Repository_Interface $repository) {
        $this->repository = $repository;
    }
    
    public function find($id) {
        return $this->repository->find($id);
    }
    
    public function find_all($args = array()) {
        return $this->repository->find_all($args);
    }
    
    // Delegate other methods...
}

class MT_Cached_Repository extends MT_Repository_Decorator {
    
    private $cache_group = 'mt_repository';
    private $cache_ttl = 300; // 5 minutes
    
    public function find($id) {
        $cache_key = "find_{$id}";
        $cached = wp_cache_get($cache_key, $this->cache_group);
        
        if (false !== $cached) {
            return $cached;
        }
        
        $result = parent::find($id);
        wp_cache_set($cache_key, $result, $this->cache_group, $this->cache_ttl);
        
        return $result;
    }
}

// Usage
$repository = new MT_Evaluation_Repository();
$cached_repository = new MT_Cached_Repository($repository);
$evaluation = $cached_repository->find(123); // Uses cache
```

## Troubleshooting

### Common Issues and Solutions

#### 1. Class Not Found Errors

**Problem:**
```
Fatal error: Class 'MobilityTrailblazers\Services\MT_Custom_Service' not found
```

**Solution:**
```php
// Check file naming
// Class: MT_Custom_Service
// File: class-mt-custom-service.php (lowercase with hyphens)

// Check namespace
namespace MobilityTrailblazers\Services; // Exact case matters

// Check autoloader is loaded
if (!class_exists('MT_Autoloader')) {
    require_once plugin_dir_path(__FILE__) . 'includes/class-mt-autoloader.php';
}
```

#### 2. Database Errors

**Problem:**
```
WordPress database error: Table 'wp_mt_custom' doesn't exist
```

**Solution:**
```php
// Add table creation to activation hook
register_activation_hook(__FILE__, 'my_plugin_activate');

function my_plugin_activate() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-database.php';
    $db = new MT_Database();
    $db->create_tables();
}

// Check table exists before queries
global $wpdb;
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}mt_custom'");
if (!$table_exists) {
    // Create table or show error
}
```

#### 3. Permission Errors

**Problem:**
```
You do not have permission to perform this action
```

**Solution:**
```php
// Add capability to role
$role = get_role('administrator');
$role->add_cap('mt_custom_capability');

// Check capability exists
if (!current_user_can('mt_custom_capability')) {
    $user = wp_get_current_user();
    $user->add_cap('mt_custom_capability');
}

// Debug capabilities
$user = wp_get_current_user();
error_log('User caps: ' . print_r($user->allcaps, true));
```

#### 4. AJAX Not Working

**Problem:**
```
400 Bad Request or 0 response
```

**Solution:**
```php
// Check action is registered
add_action('wp_ajax_mt_custom_action', 'handle_custom_action');
add_action('wp_ajax_nopriv_mt_custom_action', 'handle_custom_action'); // For non-logged users

// Check nonce name matches
wp_create_nonce('mt_custom_nonce'); // Creating
check_ajax_referer('mt_custom_nonce', 'nonce'); // Verifying

// Debug AJAX
add_action('wp_ajax_mt_custom_action', function() {
    error_log('AJAX action triggered');
    error_log('POST data: ' . print_r($_POST, true));
    wp_die(); // Always end with wp_die()
});
```

### Debug Helpers

```php
// Enable debug logging
if (!function_exists('mt_debug_log')) {
    function mt_debug_log($message, $data = null) {
        if (defined('MT_DEBUG') && MT_DEBUG) {
            $log = date('[Y-m-d H:i:s] ') . $message;
            if ($data !== null) {
                $log .= ' | Data: ' . print_r($data, true);
            }
            error_log($log);
        }
    }
}

// Usage in your code
mt_debug_log('Processing evaluation', array(
    'user_id' => $user_id,
    'candidate_id' => $candidate_id
));

// Query monitor
add_filter('query', function($query) {
    if (strpos($query, 'mt_') !== false) {
        mt_debug_log('MT Query', $query);
    }
    return $query;
});

// Hook monitor
add_action('all', function($hook) {
    if (strpos($hook, 'mt_') === 0) {
        mt_debug_log('MT Hook fired', $hook);
    }
});
```

## Best Practices Summary

1. **Always use namespaces** for new classes
2. **Follow single responsibility principle** - one class, one purpose
3. **Use dependency injection** where possible
4. **Validate all input** in services, not repositories
5. **Handle errors gracefully** with meaningful messages
6. **Document your code** with PHPDoc blocks
7. **Write tests** for critical functionality
8. **Use WordPress coding standards**
9. **Leverage existing services** rather than reimplementing
10. **Hook into existing actions/filters** for extensibility

## Resources

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [PHP The Right Way](https://phptherightway.com/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- [PHPUnit Testing](https://phpunit.de/documentation.html)
- [Repository Pattern](https://martinfowler.com/eaaCatalog/repository.html)
- [Service Layer Pattern](https://martinfowler.com/eaaCatalog/serviceLayer.html)