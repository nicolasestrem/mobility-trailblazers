# Mobility Trailblazers - Architecture Documentation

**Version:** 2.2.1
**Last Updated:** August 2025

## Table of Contents
1. [Overview](#overview)
2. [Architecture Principles](#architecture-principles)
3. [System Architecture](#system-architecture)
4. [Directory Structure](#directory-structure)
5. [Core Components](#core-components)
6. [Data Flow](#data-flow)
7. [Security Architecture](#security-architecture)
8. [Performance Considerations](#performance-considerations)

## Related Documentation
- **[Developer Guide](mt-developer-guide.md)** - Development practices and code examples
- **[Customization Guide](mt-customization-guide.md)** - UI customization and theming
- **[Error Handling System](error-handling-system.md)** - Error management implementation

## Overview

The Mobility Trailblazers plugin follows a modern, layered architecture designed for maintainability, scalability, and security. Built on WordPress 5.8+ and PHP 7.4+, it implements industry-standard design patterns while respecting WordPress conventions.

## Architecture Principles

### SOLID Principles

1. **Single Responsibility Principle (SRP)**
   - Each class has one reason to change
   - Clear separation between data access, business logic, and presentation

2. **Open/Closed Principle (OCP)**
   - Classes are open for extension but closed for modification
   - Extensive use of hooks and filters for extensibility

3. **Liskov Substitution Principle (LSP)**
   - Interfaces define contracts that implementations must follow
   - Repository and Service interfaces ensure consistency

4. **Interface Segregation Principle (ISP)**
   - Focused interfaces prevent unnecessary dependencies
   - Separate interfaces for repositories and services

5. **Dependency Inversion Principle (DIP)**
   - High-level modules depend on abstractions
   - Services depend on repository interfaces, not implementations

### Design Patterns

1. **Repository Pattern**
   - Encapsulates data access logic
   - Provides consistent API for database operations
   - Enables easy testing and maintenance

2. **Service Layer Pattern**
   - Contains business logic
   - Orchestrates between repositories
   - Handles validation and authorization

3. **Singleton Pattern**
   - Main plugin class ensures single instance
   - Prevents multiple initializations

4. **Factory Pattern**
   - Used in autoloader for class instantiation
   - Flexible object creation

## System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      Presentation Layer                      │
│  ┌─────────────────┐  ┌──────────────┐  ┌───────────────┐ │
│  │  Admin Views    │  │  Shortcodes  │  │  AJAX Handlers│ │
│  └─────────────────┘  └──────────────┘  └───────────────┘ │
└─────────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────────────────────────────────────┐
│                      Business Logic Layer                    │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                    Service Classes                    │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌───────────┐ │   │
│  │  │ Evaluation   │  │ Assignment   │  │Statistics │ │   │
│  │  │  Service     │  │  Service     │  │  Service   │ │   │
│  │  └──────────────┘  └──────────────┘  └───────────┘ │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────────────────────────────────────┐
│                      Data Access Layer                       │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                 Repository Classes                    │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌───────────┐ │   │
│  │  │ Evaluation   │  │ Assignment   │  │ Candidate  │ │   │
│  │  │ Repository   │  │ Repository   │  │ Repository │ │   │
│  │  └──────────────┘  └──────────────┘  └───────────┘ │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────────────────────────────────────┐
│                         Database                             │
│  ┌──────────────┐  ┌──────────────┐  ┌─────────────────┐  │
│  │ wp_posts     │  │wp_mt_        │  │wp_mt_jury_      │  │
│  │ (candidates, │  │evaluations   │  │assignments      │  │
│  │ jury members)│  │              │  │                 │  │
│  └──────────────┘  └──────────────┘  └─────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

## Migration System (v2.2.1+)

### Overview
The plugin includes a robust migration system for database schema changes and optimizations.

### Architecture
```
┌──────────────────────────────────────────┐
│         MT_Migration_Runner              │
│  - Checks database version               │
│  - Executes pending migrations           │
│  - Manages migration lifecycle           │
└──────────────────────────────────────────┘
                    │
┌──────────────────────────────────────────┐
│         Migration Classes                │
│  ┌────────────────────────────────────┐ │
│  │  MT_Migration_Add_Indexes          │ │
│  │  - Adds performance indexes        │ │
│  │  - Supports rollback               │ │
│  └────────────────────────────────────┘ │
└──────────────────────────────────────────┘
```

### Key Features
- **Automatic execution** on plugin activation or update
- **Version tracking** to prevent duplicate migrations
- **Rollback support** for safe schema changes
- **Cache clearing** after migrations complete
- **Admin notifications** for migration status

### Migration Process
1. Version check on `admin_init`
2. Execute pending migrations sequentially
3. Update database version
4. Clear all caches
5. Display admin notice

## Directory Structure

```
mobility-trailblazers/
│
├── mobility-trailblazers.php    # Main plugin file
│
├── includes/                     # PHP source files
│   ├── class-mt-autoloader.php   # PSR-4 autoloader
│   │
│   ├── core/                     # Core functionality
│   │   ├── class-mt-plugin.php         # Main plugin class
│   │   ├── class-mt-activator.php      # Activation logic
│   │   ├── class-mt-deactivator.php    # Deactivation logic
│   │   ├── class-mt-uninstaller.php    # Uninstall logic
│   │   ├── class-mt-post-types.php     # Custom post types
│   │   ├── class-mt-taxonomies.php     # Custom taxonomies
│   │   ├── class-mt-roles.php          # Roles & capabilities
│   │   └── class-mt-shortcodes.php     # Shortcode handlers
│   │
│   ├── interfaces/               # PHP interfaces
│   │   ├── interface-mt-repository.php  # Repository contract
│   │   └── interface-mt-service.php     # Service contract
│   │
│   ├── repositories/             # Data access layer
│   │   ├── class-mt-evaluation-repository.php
│   │   └── class-mt-assignment-repository.php
│   │
│   ├── services/                 # Business logic layer
│   │   ├── class-mt-evaluation-service.php
│   │   └── class-mt-assignment-service.php
│   │
│   ├── admin/                    # Admin functionality
│   │   └── class-mt-admin.php          # Admin interface
│   │
│   └── ajax/                     # AJAX handlers
│       ├── class-mt-base-ajax.php       # Base AJAX class
│       ├── class-mt-evaluation-ajax.php # Evaluation AJAX
│       ├── class-mt-assignment-ajax.php # Assignment AJAX
│       └── class-mt-admin-ajax.php      # Admin AJAX
│
├── assets/                       # Frontend assets
│   ├── css/
│   │   ├── frontend.css         # Frontend styles
│   │   └── admin.css            # Admin styles
│   └── js/
│       ├── frontend.js          # Frontend scripts
│       └── admin.js             # Admin scripts
│
├── templates/                    # PHP templates
│   ├── admin/                   # Admin templates
│   │   ├── dashboard.php
│   │   ├── evaluations.php
│   │   ├── assignments.php
│   │   ├── import-export.php
│   │   └── settings.php
│   └── frontend/                # Frontend templates
│       ├── jury-dashboard.php
│       ├── candidates-grid.php
│       ├── evaluation-stats.php
│       └── winners-display.php
│
└── languages/                    # Translations
    └── mobility-trailblazers.pot
```

## Core Components

### 1. Autoloader (PSR-4)

```php
namespace MobilityTrailblazers\Core;

class MT_Autoloader {
    // Converts namespace to file path
    // MobilityTrailblazers\Services\MT_Evaluation_Service
    // → includes/services/class-mt-evaluation-service.php
}
```

### 2. Plugin Initialization

```php
// Main plugin file
add_action('plugins_loaded', function() {
    $plugin = MobilityTrailblazers\Core\MT_Plugin::get_instance();
    $plugin->init();
});
```

### 3. Repository Pattern Implementation

```php
interface MT_Repository_Interface {
    public function find($id);
    public function find_all($args = []);
    public function create($data);
    public function update($id, $data);
    public function delete($id);
}

class MT_Evaluation_Repository implements MT_Repository_Interface {
    // Implementation details
}
```

### 4. Service Layer Implementation

```php
interface MT_Service_Interface {
    public function process($data);
    public function validate($data);
    public function get_errors();
}

class MT_Evaluation_Service implements MT_Service_Interface {
    private $repository;
    
    public function __construct() {
        $this->repository = new MT_Evaluation_Repository();
    }
}
```

### 5. AJAX Architecture

```php
abstract class MT_Base_Ajax {
    protected function verify_nonce($nonce_name);
    protected function check_permission($capability);
    protected function success($data, $message);
    protected function error($message, $data);
}
```

## Data Flow

### Standard Request Flow

1. **User Interaction** → Frontend JavaScript
2. **AJAX Request** → WordPress AJAX Handler
3. **Validation** → Service Layer
4. **Data Processing** → Repository Layer
5. **Database Operation** → WordPress Database
6. **Response** → Frontend JavaScript

### Jury Evaluation Flow

The jury evaluation process follows a specific flow optimized for dynamic form handling and inline evaluation controls:

1. **User Interaction** → Jury member clicks evaluate or uses inline controls
2. **Frontend Processing** → JavaScript loads form dynamically or adjusts scores
3. **AJAX Communication** → Secure request to backend with validation
4. **Service Processing** → Business logic validation and data processing
5. **Repository Update** → Database operations with assignment verification
6. **Response Handling** → Success feedback and UI updates

### Form Submission Architecture

The system supports both traditional form submission and inline evaluation controls:

#### Dynamic Form Creation
- AJAX-loaded candidate details with proper data binding
- Nested data structure handling for complex responses
- Secure nonce verification for all requests

#### Robust Data Collection
- Manual field collection for dynamically created forms
- Fallback form selection with multiple selectors
- Comprehensive validation on both client and server side

*For detailed implementation examples, see [Developer Guide](mt-developer-guide.md)*

### Debugging Architecture

The system includes comprehensive debugging capabilities:

#### Multi-Level Debugging
- **Client-Side**: Form selection, field collection, and data validation logging
- **Server-Side**: AJAX handler debugging with detailed POST data logging
- **Repository**: Database query debugging and assignment validation
- **Error Handling**: Structured error responses with user-friendly messages

*For detailed debugging examples and troubleshooting, see [Developer Guide](mt-developer-guide.md)*

### Error Handling Architecture

Multi-layered error handling approach:

1. **Client-Side Validation**: Form validation before submission with user feedback
2. **Server-Side Validation**: AJAX handler validation with nonce and permission checks
3. **Service Layer Validation**: Business logic validation with structured error responses
4. **Database Constraint Validation**: Repository-level validation and constraint handling

*For detailed error handling examples, see [Error Handling System](error-handling-system.md)*

## Security Architecture

### Input Validation

1. **Frontend Validation**
   - HTML5 form validation
   - JavaScript validation

2. **Backend Validation**
   - Service layer validation
   - WordPress sanitization functions

### Authentication & Authorization

1. **Nonce Verification**
   - All AJAX requests require valid nonce
   - Form submissions include nonce fields

2. **Capability Checks**
   - Role-based access control
   - Custom capabilities for fine-grained control

3. **Data Sanitization**
   ```php
   // Input sanitization
   $candidate_id = intval($_POST['candidate_id']);
   $comments = sanitize_textarea_field($_POST['comments']);
   
   // Output escaping
   echo esc_html($candidate->post_title);
   echo esc_attr($evaluation->status);
   ```

### SQL Injection Prevention

All database queries use WordPress prepared statements:

```php
$wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$this->table_name} WHERE id = %d",
    $id
));
```

## Performance Considerations

### Database Optimization

1. **Indexed Columns** (v2.2.1+)
   - Primary indexes on ID fields
   - Foreign key indexes (jury_member_id, candidate_id)
   - Composite indexes for common query patterns:
     - `idx_jury_status` (jury_member_id, status)
     - `idx_candidate_status` (candidate_id, status)
     - `idx_total_score` (total_score)
     - `idx_status_score` (status, total_score)
     - `idx_jury_date` (jury_member_id, assigned_at)
     - `idx_assigned_by` (assigned_by)

2. **Efficient Queries**
   - JOIN operations for related data
   - Aggregate functions for statistics
   - Limit and offset for pagination
   - Prepared statements for security and performance

### Caching Strategy (Enhanced in v2.2.1)

1. **Transient API Implementation**
   - **Assignment Repository Caching:**
     - `get_by_jury_member()` - 1 hour cache
     - `get_statistics()` - 30 minutes cache
     - Cache key pattern: `mt_jury_assignments_{jury_id}`
   - **Evaluation Repository Caching:**
     - `get_ranked_candidates_for_jury()` - 30 minutes cache
     - Cache key pattern: `mt_jury_rankings_{jury_id}_{limit}`
   - Automatic cache invalidation on data modifications

2. **Cache Invalidation Strategy**
   - Smart cache clearing on create/update/delete operations
   - Targeted invalidation (only affected jury members)
   - Full cache flush during migrations

3. **Object Caching**
   - Compatible with Redis/Memcached
   - Reduces database load by up to 80% for repeated queries
   - Graceful fallback to database when cache misses

### Asset Loading

1. **Conditional Loading**
   - Scripts/styles only on relevant pages
   - Check page context before enqueueing

2. **Minification Ready**
   - Clean, structured CSS/JS
   - Ready for build process integration

### Scalability Considerations

1. **Stateless Design**
   - No server-side sessions
   - All state in database or client

2. **Horizontal Scaling**
   - Works with load balancers
   - No file system dependencies

3. **Background Processing Ready**
   - Service layer supports async operations
   - Can integrate with job queues

## Best Practices

### Code Organization

1. **Namespace Everything**
   - Prevents naming conflicts
   - Clear code organization

2. **Single Responsibility**
   - Each class has one job
   - Easy to test and maintain

3. **Dependency Injection Ready**
   - Services can accept injected dependencies
   - Facilitates testing

### Error Handling

1. **Graceful Degradation**
   - Never show PHP errors to users
   - Log errors for debugging

2. **User-Friendly Messages**
   - Translated error messages
   - Clear action items

### Extensibility

1. **WordPress Hooks**
   - Actions for key events
   - Filters for data modification

2. **Service Interfaces**
   - Easy to swap implementations
   - Supports custom services

This architecture provides a solid foundation for a maintainable, secure, and scalable WordPress plugin while following both WordPress and general PHP best practices. 