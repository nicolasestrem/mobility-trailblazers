# Mobility Trailblazers - Architecture Documentation

**Version:** 2.0.0  
**Last Updated:** June 2126 2025

## Table of Contents
1. [Overview](#overview)
2. [Architecture Principles](#architecture-principles)
3. [System Architecture](#system-architecture)
4. [Directory Structure](#directory-structure)
5. [Core Components](#core-components)
6. [Data Flow](#data-flow)
7. [Security Architecture](#security-architecture)
8. [Performance Considerations](#performance-considerations)

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
│  │  │ Evaluation   │  │ Assignment   │  │Notification│ │   │
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

The jury evaluation process follows a specific flow optimized for dynamic form handling:

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Jury Member   │    │  Frontend JS    │    │  AJAX Handler   │
│   Clicks        │───▶│  Loads Form     │───▶│  Returns        │
│   Evaluate      │    │  Dynamically    │    │  Candidate Data │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                                │                        │
                                ▼                        ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Form Created  │    │  User Fills     │    │  Form Data      │
│   with Hidden   │◀───│  Evaluation     │◀───│  Collected      │
│   candidate_id  │    │  Form           │    │  Manually       │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                                │                        │
                                ▼                        ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Form Submit   │    │  AJAX Request   │    │  Validation &   │
│   Event         │───▶│  with All       │───▶│  Processing     │
│   Triggered     │    │  Form Fields    │    │  in Service     │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                                │                        │
                                ▼                        ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Success       │    │  Database       │    │  Assignment     │
│   Response      │◀───│  Updated        │◀───│  Checked        │
│   to User       │    │  via Repository │    │  via Repository │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

### Form Submission Architecture

#### Dynamic Form Creation

The evaluation form is created dynamically via JavaScript to ensure proper data binding:

```javascript
// 1. Load candidate details via AJAX
$.post(mt_ajax.url, {
    action: 'mt_get_candidate_details',
    candidate_id: candidateId,
    nonce: mt_ajax.nonce
})
.done(function(response) {
    if (response.success) {
        // Handle nested data structure
        var candidateData = response.data.data || response.data;
        self.displayEvaluationForm(candidateData);
    }
});
```

#### Robust Form Data Collection

To handle dynamically created forms, the system uses manual field collection:

```javascript
// Manual field collection ensures all fields are captured
var formData = {};
$targetForm.find('input, textarea, select').each(function() {
    var $field = $(this);
    var name = $field.attr('name');
    var value = $field.val();
    
    if (name && value !== undefined) {
        formData[name] = value;
    }
});
```

#### Fallback Form Selection

Multiple selectors ensure the form is always found:

```javascript
// Try multiple selectors to find the form
var $targetForm = $('#mt-evaluation-form');
if ($targetForm.length === 0) {
    $targetForm = $('.mt-evaluation-form');
}
if ($targetForm.length === 0) {
    $targetForm = $form; // fallback to original reference
}
```

### Debugging Architecture

The system includes comprehensive debugging capabilities at multiple levels:

#### Client-Side Debugging

```javascript
// Form selection debugging
console.log('MT JS - Form element:', $form);
console.log('MT JS - Form ID:', $form.attr('id'));
console.log('MT JS - Form class:', $form.attr('class'));

// Field collection debugging
var allFields = $targetForm.find('input, textarea, select');
console.log('MT JS - Found form fields:', allFields.length);
allFields.each(function(index) {
    var $field = $(this);
    var name = $field.attr('name');
    var value = $field.val();
    console.log('MT JS - Field ' + index + ':', name, '=', value);
});

// Final form data debugging
console.log('MT JS - Form data being sent:', formData);
console.log('MT JS - Candidate ID in form data:', formData.candidate_id);
```

#### Server-Side Debugging

```php
// AJAX handler debugging
public function submit_evaluation() {
    // Debug: Log raw POST data
    error_log('MT AJAX - Raw POST data: ' . print_r($_POST, true));
    
    // Debug: Check candidate_id specifically
    $raw_candidate_id = $this->get_param('candidate_id');
    error_log('MT AJAX - Raw candidate_id from POST: ' . var_export($raw_candidate_id, true));
    $candidate_id = $this->get_int_param('candidate_id');
    error_log('MT AJAX - Processed candidate_id: ' . $candidate_id);
    
    // Debug: Jury member lookup
    $current_user_id = get_current_user_id();
    $jury_member = $this->get_jury_member_by_user_id($current_user_id);
    error_log('MT AJAX - Found jury member: ' . $jury_member->ID . ' for user: ' . $current_user_id);
    
    // Debug: Assignment check
    $assignment_repo = new \MobilityTrailblazers\Repositories\MT_Assignment_Repository();
    $has_assignment = $assignment_repo->exists($jury_member->ID, $candidate_id);
    error_log('MT AJAX - Assignment check: jury_member_id=' . $jury_member->ID . ', candidate_id=' . $candidate_id . ', has_assignment=' . ($has_assignment ? 'true' : 'false'));
}
```

#### Repository Debugging

```php
// Repository method debugging
public function exists($jury_member_id, $candidate_id) {
    $query = $this->wpdb->prepare(
        "SELECT COUNT(*) FROM {$this->table_name} 
         WHERE jury_member_id = %d AND candidate_id = %d",
        $jury_member_id, $candidate_id
    );
    
    error_log('MT Assignment Repository - Checking assignment with query: ' . $query);
    
    $count = $this->wpdb->get_var($query);
    
    error_log('MT Assignment Repository - Assignment count: ' . $count . ' for jury_member_id=' . $jury_member_id . ', candidate_id=' . $candidate_id);
    
    return (int) $count > 0;
}
```

### Error Handling Architecture

The system implements a multi-layered error handling approach:

#### 1. Client-Side Validation

```javascript
// Form validation before submission
var isValid = true;
$('.mt-score-slider').each(function() {
    var value = parseInt($(this).val());
    if (isNaN(value) || value < 0 || value > 10) {
        isValid = false;
        return false;
    }
});

if (!isValid) {
    MTJuryDashboard.showError('Please ensure all scores are between 0 and 10.');
    return;
}
```

#### 2. Server-Side Validation

```php
// AJAX handler validation
public function submit_evaluation() {
    $this->verify_nonce();
    $this->check_permission('mt_submit_evaluations');
    
    $candidate_id = $this->get_int_param('candidate_id');
    if (!$candidate_id) {
        $this->error(__('Invalid candidate ID.', 'mobility-trailblazers'));
    }
    
    // Additional validation in service layer
    $service = new MT_Evaluation_Service();
    if (!$service->validate($data)) {
        $this->error(implode(', ', $service->get_errors()));
    }
}
```

#### 3. Database Constraint Validation

```php
// Repository-level validation
public function create($data) {
    // Check for existing evaluation
    $existing = $this->find_all([
        'jury_member_id' => $data['jury_member_id'],
        'candidate_id' => $data['candidate_id'],
        'limit' => 1
    ]);
    
    if (!empty($existing)) {
        // Update existing evaluation
        return $this->update($existing[0]->id, $data);
    }
    
    // Create new evaluation
    return $this->insert($data);
}
```

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

1. **Indexed Columns**
   - Foreign keys (jury_member_id, candidate_id)
   - Status fields for filtering
   - Timestamps for sorting

2. **Efficient Queries**
   - JOIN operations for related data
   - Aggregate functions for statistics
   - Limit and offset for pagination

### Caching Strategy

1. **Transient API**
   - Cache expensive calculations
   - Store frequently accessed data

2. **Object Caching**
   - Compatible with Redis/Memcached
   - Reduces database load

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