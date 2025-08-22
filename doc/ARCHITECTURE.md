# Mobility Trailblazers Plugin Architecture

**Version:** 2.5.39+  
**Last Updated:** 2025-08-22  
**Author:** Mobility Trailblazers - Nicolas Estrem  
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
11. [Database Schema](#database-schema)
12. [Legacy Compatibility](#legacy-compatibility)
13. [Best Practices](#best-practices)

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

### 3. Modern Modular Structure

```
mobility-trailblazers/
├── includes/
│   ├── core/              # MT_Plugin, Container, Service Provider
│   ├── providers/         # Service provider implementations
│   ├── interfaces/        # Service and repository interfaces
│   ├── admin/             # Admin interfaces and columns
│   ├── ajax/              # AJAX handlers with base class
│   ├── repositories/      # Data access layer (interface-based)
│   ├── services/          # Business logic (DI-enabled)
│   ├── widgets/           # Dashboard widgets
│   ├── legacy/            # Backward compatibility layer
│   └── utilities/         # Helper functions
├── templates/             # Frontend templates
├── assets/               
│   ├── css/              # Stylesheets
│   └── js/               # JavaScript files
├── languages/            # i18n support (German/English)
└── docs/                 # Comprehensive documentation
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
// Register as singleton
$container->singleton(
    'MobilityTrailblazers\Services\MT_Cache_Service',
    function($container) {
        return new MT_Cache_Service();
    }
);
```

#### Factory Binding
```php
// Complex factory function
$container->bind('database_connection', function($container) {
    return new MT_Database_Connection(
        $container->make('config'),
        $container->make('logger')
    );
});
```

## Service Provider Pattern

Service providers organize the registration and bootstrapping of related services.

### Core Service Providers

- **MT_Repository_Provider**: Registers all repository implementations
- **MT_Services_Provider**: Registers business logic services
- **MT_Admin_Provider**: Registers admin-specific services
- **MT_Ajax_Provider**: Registers AJAX handlers

### Provider Implementation

```php
class MT_Repository_Provider implements MT_Service_Provider_Interface {
    
    public function register(MT_Container $container): void {
        // Register evaluation repository
        $container->singleton(
            'MobilityTrailblazers\Interfaces\MT_Evaluation_Repository_Interface',
            'MobilityTrailblazers\Repositories\MT_Evaluation_Repository'
        );
        
        // Register assignment repository
        $container->singleton(
            'MobilityTrailblazers\Interfaces\MT_Assignment_Repository_Interface',
            'MobilityTrailblazers\Repositories\MT_Assignment_Repository'
        );
    }
    
    public function boot(MT_Container $container): void {
        // Perform any post-registration setup
    }
}
```

## Interface-Based Design

All major components implement interfaces to ensure flexibility and testability.

### Repository Interfaces

```php
interface MT_Evaluation_Repository_Interface {
    public function find_by_id(int $id): ?MT_Evaluation;
    public function save(MT_Evaluation $evaluation): bool;
    public function find_by_jury_and_candidate(int $jury_id, int $candidate_id): ?MT_Evaluation;
    public function get_evaluations_by_status(string $status): array;
}
```

### Service Interfaces

```php
interface MT_Evaluation_Service_Interface {
    public function create_evaluation(int $jury_id, int $candidate_id): MT_Evaluation;
    public function submit_evaluation(int $evaluation_id): bool;
    public function calculate_total_score(MT_Evaluation $evaluation): float;
}
```

## Directory Structure

### Core Components

- **includes/core/**: Plugin initialization, container, and core services
- **includes/providers/**: Service provider implementations for DI registration
- **includes/interfaces/**: Interface definitions for all major components
- **includes/services/**: Business logic layer with dependency injection
- **includes/repositories/**: Data access layer implementing repository pattern
- **includes/admin/**: WordPress admin integration and interfaces
- **includes/ajax/**: AJAX handlers extending base security class
- **includes/widgets/**: WordPress dashboard widgets
- **includes/legacy/**: Backward compatibility facade for legacy code

### Frontend Components

- **templates/**: PHP templates for frontend rendering
- **assets/css/**: Stylesheets organized by component and version
- **assets/js/**: JavaScript files with proper dependency management
- **languages/**: Internationalization files (German primary)

## Component Lifecycle

### Plugin Initialization

```php
// 1. Plugin bootstrap (mobility-trailblazers.php)
register_activation_hook(__FILE__, ['MT_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['MT_Deactivator', 'deactivate']);

// 2. Main plugin class initialization
add_action('plugins_loaded', function() {
    MT_Plugin::get_instance()->run();
});

// 3. Service container setup
$container = MT_Container::get_instance();
$container->register_providers();
$container->boot_providers();
```

### Request Lifecycle

```php
// 1. WordPress loads plugin
// 2. Container resolves dependencies
// 3. Service providers register services
// 4. AJAX/Admin handlers are registered
// 5. Templates are rendered with injected services
```

## Service Layer

The service layer contains business logic and orchestrates repository interactions.

### Service Implementation Example

```php
class MT_Evaluation_Service implements MT_Evaluation_Service_Interface {
    
    private MT_Evaluation_Repository_Interface $evaluation_repository;
    private MT_Assignment_Repository_Interface $assignment_repository;
    
    public function __construct(
        MT_Evaluation_Repository_Interface $evaluation_repository,
        MT_Assignment_Repository_Interface $assignment_repository
    ) {
        $this->evaluation_repository = $evaluation_repository;
        $this->assignment_repository = $assignment_repository;
    }
    
    public function create_evaluation(int $jury_id, int $candidate_id): MT_Evaluation {
        // Business logic for evaluation creation
        $assignment = $this->assignment_repository->find_by_jury_and_candidate($jury_id, $candidate_id);
        
        if (!$assignment) {
            throw new MT_Assignment_Not_Found_Exception();
        }
        
        return $this->evaluation_repository->create([
            'jury_member_id' => $jury_id,
            'candidate_id' => $candidate_id,
            'status' => 'draft'
        ]);
    }
}
```

## Repository Layer

The repository layer handles all data access and database operations.

### Repository Implementation

```php
class MT_Evaluation_Repository implements MT_Evaluation_Repository_Interface {
    
    private $wpdb;
    private string $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'mt_evaluations';
    }
    
    public function find_by_id(int $id): ?MT_Evaluation {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        );
        
        $result = $this->wpdb->get_row($query);
        
        return $result ? MT_Evaluation::from_array((array) $result) : null;
    }
    
    public function save(MT_Evaluation $evaluation): bool {
        if ($evaluation->get_id()) {
            return $this->update($evaluation);
        }
        
        return $this->insert($evaluation);
    }
}
```

## AJAX Layer

All AJAX handlers extend the base `MT_Base_Ajax` class for security and consistency.

### Base AJAX Security

```php
abstract class MT_Base_Ajax {
    
    public function __construct() {
        $this->register_hooks();
    }
    
    protected function verify_nonce(): bool {
        return wp_verify_nonce($_POST['nonce'], 'mt_ajax_nonce');
    }
    
    protected function check_capabilities(): bool {
        return current_user_can($this->get_required_capability());
    }
    
    protected function handle_request(): void {
        if (!$this->verify_nonce() || !$this->check_capabilities()) {
            wp_die('Security check failed', 'Security Error', ['response' => 403]);
        }
        
        $this->process_request();
    }
    
    abstract protected function process_request(): void;
    abstract protected function get_required_capability(): string;
}
```

### AJAX Handler Implementation

```php
class MT_Evaluation_Ajax extends MT_Base_Ajax {
    
    private MT_Evaluation_Service_Interface $evaluation_service;
    
    public function __construct(MT_Evaluation_Service_Interface $evaluation_service) {
        $this->evaluation_service = $evaluation_service;
        parent::__construct();
    }
    
    protected function process_request(): void {
        $action = sanitize_text_field($_POST['mt_action']);
        
        switch ($action) {
            case 'save_evaluation':
                $this->save_evaluation();
                break;
            case 'submit_evaluation':
                $this->submit_evaluation();
                break;
        }
    }
    
    protected function get_required_capability(): string {
        return 'mt_submit_evaluations';
    }
}
```

## Database Schema

### Core WordPress Tables (Extended)

```sql
-- Extended WordPress posts table
wp_posts (
    -- Standard WordPress fields
    ID, post_title, post_content, post_status, post_type, post_date
    
    -- Custom post types used:
    -- post_type = 'mt_candidate' (candidate profiles)
    -- post_type = 'mt_jury_member' (jury member profiles)
)

-- Extended WordPress postmeta table
wp_postmeta (
    -- Standard WordPress fields
    meta_id, post_id, meta_key, meta_value
    
    -- Custom meta keys used:
    -- mt_candidate_category, mt_candidate_company, mt_candidate_description
    -- mt_jury_member_expertise, mt_jury_member_bio
)
```

### Custom Plugin Tables

```sql
-- Evaluation storage with 5 criteria scoring
wp_mt_evaluations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    jury_member_id BIGINT NOT NULL,           -- References mt_jury_member post
    candidate_id BIGINT NOT NULL,             -- References mt_candidate post
    criterion_1 DECIMAL(3,1) DEFAULT NULL,   -- Mut & Pioniergeist (0-10, 0.5 increments)
    criterion_2 DECIMAL(3,1) DEFAULT NULL,   -- Innovationsgrad (0-10, 0.5 increments)
    criterion_3 DECIMAL(3,1) DEFAULT NULL,   -- Umsetzungskraft & Wirkung (0-10, 0.5 increments)
    criterion_4 DECIMAL(3,1) DEFAULT NULL,   -- Relevanz für Mobilitätswende (0-10, 0.5 increments)
    criterion_5 DECIMAL(3,1) DEFAULT NULL,   -- Vorbildfunktion & Sichtbarkeit (0-10, 0.5 increments)
    total_score DECIMAL(4,1) GENERATED ALWAYS AS (
        COALESCE(criterion_1, 0) + COALESCE(criterion_2, 0) + 
        COALESCE(criterion_3, 0) + COALESCE(criterion_4, 0) + 
        COALESCE(criterion_5, 0)
    ) STORED,
    comments LONGTEXT,                        -- Optional feedback comments
    status VARCHAR(20) DEFAULT 'draft',      -- draft, submitted, approved, rejected
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    submitted_at DATETIME DEFAULT NULL,       -- Timestamp when evaluation was submitted
    
    UNIQUE KEY unique_evaluation (jury_member_id, candidate_id),
    INDEX idx_status (status),
    INDEX idx_total_score (total_score),
    INDEX idx_updated_at (updated_at),
    
    FOREIGN KEY (jury_member_id) REFERENCES wp_posts(ID) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES wp_posts(ID) ON DELETE CASCADE
);

-- Jury assignment management
wp_mt_jury_assignments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    jury_member_id BIGINT NOT NULL,           -- References mt_jury_member post
    candidate_id BIGINT NOT NULL,             -- References mt_candidate post
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    assigned_by BIGINT NOT NULL,              -- References user who made assignment
    
    UNIQUE KEY unique_assignment (jury_member_id, candidate_id),
    INDEX idx_jury_member (jury_member_id),
    INDEX idx_candidate (candidate_id),
    INDEX idx_assignment_date (assigned_at),
    
    FOREIGN KEY (jury_member_id) REFERENCES wp_posts(ID) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES wp_posts(ID) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES wp_users(ID) ON DELETE CASCADE
);

-- Comprehensive activity tracking
wp_mt_audit_log (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT,                           -- User who performed action (nullable for system actions)
    action VARCHAR(100) NOT NULL,             -- Action type (evaluation_created, assignment_made, etc.)
    object_type VARCHAR(50) NOT NULL,         -- Object affected (evaluation, assignment, candidate, etc.)
    object_id BIGINT NOT NULL,                -- ID of affected object
    old_values JSON,                          -- Previous state (for updates)
    new_values JSON,                          -- New state
    ip_address VARCHAR(45),                   -- User IP address
    user_agent TEXT,                          -- User agent string
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_object (object_type, object_id),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (user_id) REFERENCES wp_users(ID) ON DELETE SET NULL
);

-- Centralized error logging
wp_mt_error_log (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    level VARCHAR(10) NOT NULL,               -- error, warning, info, debug
    message TEXT NOT NULL,                    -- Error message
    context JSON,                             -- Additional context data
    file VARCHAR(255),                        -- File where error occurred
    line INT,                                 -- Line number
    user_id BIGINT,                          -- User associated with error (nullable)
    request_id VARCHAR(32),                   -- Unique request identifier
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_level (level),
    INDEX idx_created_at (created_at),
    INDEX idx_user_id (user_id),
    INDEX idx_request_id (request_id),
    
    FOREIGN KEY (user_id) REFERENCES wp_users(ID) ON DELETE SET NULL
);
```

### Database Indexes and Performance

The schema includes strategic indexes for optimal performance:

- **Primary Keys**: All tables have auto-incrementing primary keys
- **Unique Constraints**: Prevent duplicate evaluations and assignments
- **Foreign Key Constraints**: Maintain referential integrity
- **Performance Indexes**: Optimized for common query patterns
- **Generated Columns**: Automatic total score calculation

## Legacy Compatibility

The plugin maintains backward compatibility through facade patterns.

### Legacy Facade Example

```php
class MT_Legacy_Evaluation {
    
    private static MT_Evaluation_Service_Interface $service;
    
    public static function init(): void {
        self::$service = MT_Container::get_instance()->make(
            'MobilityTrailblazers\Services\MT_Evaluation_Service'
        );
    }
    
    // Legacy method maintained for backward compatibility
    public static function save_evaluation($data) {
        return self::$service->save_evaluation_from_array($data);
    }
}
```

## Best Practices

### Development Guidelines

1. **Always use dependency injection** for services and repositories
2. **Implement interfaces** for all major components
3. **Follow SOLID principles** in class design
4. **Use prepared statements** for all database queries
5. **Validate and sanitize** all input data
6. **Escape all output** to prevent XSS
7. **Check capabilities** before performing actions
8. **Use nonces** for AJAX security
9. **Write unit tests** for business logic
10. **Document complex algorithms** and business rules

### Code Examples

#### Service Registration
```php
// In service provider
$container->singleton(
    'MobilityTrailblazers\Interfaces\MT_Evaluation_Service_Interface',
    'MobilityTrailblazers\Services\MT_Evaluation_Service'
);
```

#### Service Usage
```php
// In controller or template
$evaluation_service = MT_Container::get_instance()->make(
    'MobilityTrailblazers\Interfaces\MT_Evaluation_Service_Interface'
);
```

#### Repository Query
```php
// Always use prepared statements
$query = $wpdb->prepare(
    "SELECT * FROM {$table_name} WHERE jury_member_id = %d AND candidate_id = %d",
    $jury_id,
    $candidate_id
);
```

### Security Checklist

- ✅ Nonce verification on all AJAX endpoints
- ✅ Capability checks before operations
- ✅ Input sanitization with appropriate WordPress functions
- ✅ Output escaping in templates
- ✅ Prepared statements for database queries
- ✅ File upload validation and restrictions
- ✅ Rate limiting on sensitive operations
- ✅ Audit logging for security events

---

*This architecture documentation provides the foundation for understanding and extending the Mobility Trailblazers plugin. For implementation details, see the [Developer Guide](developer-guide.md) and [API Reference](api-reference.md).*