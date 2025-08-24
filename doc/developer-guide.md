# Mobility Trailblazers Developer Guide

*Version 4.1.0+ | Last Updated: August 23, 2025*

> **📋 Architecture Reference**: For comprehensive system architecture documentation, see [Architecture Guide](architecture.md).

## Table of Contents

1. [Development Environment Setup](#development-environment-setup)
2. [JavaScript Architecture](#javascript-architecture)
3. [PHP Development Patterns](#php-development-patterns)
4. [Frontend Assets & Styling](#frontend-assets--styling)
5. [UI Templates & Components](#ui-templates--components)
6. [Photo Management System](#photo-management-system)
7. [Auto-Assignment System](#auto-assignment-system)
8. [Rich Text Editor](#rich-text-editor)
9. [Testing Infrastructure](#testing-infrastructure)
10. [Debug Center](#debug-center)
11. [Development Workflow](#development-workflow)
12. [Troubleshooting](#troubleshooting)
13. [Best Practices](#best-practices)

## Development Environment Setup

### Requirements

- **PHP**: 7.4+ (8.2+ recommended)
- **WordPress**: 5.8+
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Memory**: 256MB minimum
- **Node.js**: 16+ (for asset building)
- **Composer**: For PHP dependencies

### Local Development Setup

#### 1. Docker Environment (Recommended)

```bash
# Clone repository
git clone https://github.com/nicolasestrem/mobility-trailblazers.git
cd mobility-trailblazers

# Start Docker containers
docker-compose up -d

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Build assets
npm run build
```

#### 2. Traditional LAMP/XAMPP Setup

```bash
# Navigate to WordPress plugins directory
cd /wp-content/plugins/

# Clone or extract plugin
git clone https://github.com/nicolasestrem/mobility-trailblazers.git

# Install dependencies
cd mobility-trailblazers
composer install
npm install
```

#### 3. Environment Configuration

Create `wp-config-local.php` with development settings:

```php
// Development constants
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);

// Plugin-specific settings
define('MT_ENVIRONMENT', 'development');
define('MT_DEBUG_MODE', true);
define('MT_DISABLE_CACHE', true);
```

### Development URLs

- **Local Development**: http://localhost/
- **Docker Environment**: http://localhost:8080/
- **Staging**: http://localhost:8080/ (Docker staging)
- **Production**: https://mobilitytrailblazers.de/vote/

### Essential Commands

```bash
# Database operations
wp eval "MobilityTrailblazers\Utilities\MT_Database_Health::check_health();"
wp cache flush

# Asset building
npm run dev          # Development build with watch
npm run build        # Production build
npm run lint         # Code linting

# Translation compilation
.\scripts\compile-mo-local.ps1

# Import/export operations
wp mt import-candidates --dry-run --file=test.csv
php scripts/debug-db-create.php
```

## JavaScript Architecture

### Module Structure

```javascript
// Base Module Pattern
var MT = MT || {};

MT.Evaluation = (function($) {
    'use strict';
    
    var settings = {
        ajaxUrl: mt_ajax.ajax_url,
        nonce: mt_ajax.nonce
    };
    
    function init() {
        bindEvents();
        initializeComponents();
    }
    
    function bindEvents() {
        $(document).on('change', '.mt-criterion-score', handleScoreChange);
        $(document).on('click', '.mt-save-evaluation', saveEvaluation);
    }
    
    return {
        init: init
    };
})(jQuery);

// Initialize on document ready
jQuery(document).ready(function() {
    MT.Evaluation.init();
});
```

### AJAX Request Pattern

```javascript
function saveEvaluation(e) {
    e.preventDefault();
    
    var $button = $(this);
    var evaluationId = $button.data('evaluation-id');
    
    $.ajax({
        url: mt_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'mt_save_evaluation',
            nonce: mt_ajax.nonce,
            evaluation_id: evaluationId,
            scores: collectScores()
        },
        beforeSend: function() {
            $button.prop('disabled', true);
            showSpinner();
        },
        success: function(response) {
            if (response.success) {
                showNotification('success', response.data.message);
                updateUI(response.data);
            } else {
                showNotification('error', response.data.message);
            }
        },
        error: function() {
            showNotification('error', 'Network error occurred');
        },
        complete: function() {
            $button.prop('disabled', false);
            hideSpinner();
        }
    });
}
```

## PHP Development Patterns

> **📋 Architecture Details**: For comprehensive PHP architecture documentation, see [Architecture Guide](architecture.md).

### Coding Standards

The plugin follows WordPress Coding Standards with modern PHP practices:

```php
// Use type declarations
public function save_evaluation(int $jury_id, int $candidate_id, array $scores): bool

// Use strict comparison
if ($status === 'submitted') {
    // Process submission
}

// Use null coalescing operator
$comments = $data['comments'] ?? '';

// Use array destructuring
[$created, $updated] = $this->get_timestamps();
```

### Service Usage Patterns

```php
// Get service from container
$container = MT_Container::get_instance();
$evaluation_service = $container->make('MobilityTrailblazers\Services\MT_Evaluation_Service');

// Use service in WordPress hooks
add_action('wp_ajax_mt_save_evaluation', function() use ($evaluation_service) {
    $result = $evaluation_service->save_evaluation($_POST['data']);
    wp_send_json($result);
});

// Service method chaining
$result = $evaluation_service
    ->validate($data)
    ->process()
    ->save()
    ->getResult();
```

### WordPress Integration Patterns

```php
// Custom post type registration
add_action('init', function() {
    register_post_type('mt_candidate', [
        'public' => true,
        'show_in_rest' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
        'capability_type' => 'mt_candidate',
        'map_meta_cap' => true
    ]);
});

// Meta box registration
add_action('add_meta_boxes', function() {
    add_meta_box(
        'mt_candidate_details',
        __('Candidate Details', 'mobility-trailblazers'),
        'mt_render_candidate_meta_box',
        'mt_candidate',
        'normal',
        'high'
    );
});

// AJAX handler pattern
add_action('wp_ajax_mt_get_candidates', function() {
    check_ajax_referer('mt_ajax_nonce');
    
    if (!current_user_can('mt_view_candidates')) {
        wp_die(__('Insufficient permissions', 'mobility-trailblazers'));
    }
    
    $service = MT_Container::get_instance()->make('MT_Candidate_Service');
    $candidates = $service->get_candidates($_GET['filters']);
    
    wp_send_json_success($candidates);
});
```

## Frontend Assets & Styling

> **🎨 CSS Guide**: For comprehensive styling documentation, see [CSS Guide](css-guide.md).

### Container Implementation

```php
// Container initialization
$container = MT_Container::get_instance();

// Service registration through service providers
$container->register_provider(new MT_Core_Service_Provider());
$container->register_provider(new MT_Evaluation_Service_Provider());
$container->register_provider(new MT_Assignment_Service_Provider());

// Automatic dependency resolution
$evaluation_service = $container->get('MT_Evaluation_Service');
```

### Service Provider Pattern

```php
class MT_Evaluation_Service_Provider implements MT_Service_Provider_Interface {
    public function register(MT_Container_Interface $container): void {
        // Register repository
        $container->singleton('MT_Evaluation_Repository_Interface', function($container) {
            return new MT_Evaluation_Repository();
        });
        
        // Register validator
        $container->singleton('MT_Validator_Interface', function($container) {
            return new MT_Validator();
        });
        
        // Register service with dependencies
        $container->singleton('MT_Evaluation_Service', function($container) {
            return new MT_Evaluation_Service(
                $container->get('MT_Evaluation_Repository_Interface'),
                $container->get('MT_Logger_Interface'),
                $container->get('MT_Validator_Interface')
            );
        });
    }
    
    public function boot(MT_Container_Interface $container): void {
        // Bootstrap logic after all services are registered
        $evaluation_service = $container->get('MT_Evaluation_Service');
        $evaluation_service->initialize();
    }
}
```

### Interface-Based Design

```php
// Service interfaces define contracts
interface MT_Evaluation_Service_Interface {
    public function save_evaluation(int $id, array $scores, string $comments = ''): bool;
    public function get_evaluation(int $id): ?MT_Evaluation;
    public function calculate_average_score(array $scores): float;
}

// Repository interfaces for data access
interface MT_Evaluation_Repository_Interface {
    public function find(int $id): ?MT_Evaluation;
    public function create(array $data): int;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}

// Logger interface for consistent logging
interface MT_Logger_Interface {
    public function debug(string $message, array $context = []): void;
    public function info(string $message, array $context = []): void;
    public function warning(string $message, array $context = []): void;
    public function error(string $message, array $context = []): void;
    public function critical(string $message, array $context = []): void;
}
```

### Dependency Resolution Examples

```php
// Automatic constructor injection
class MT_Assignment_Service {
    public function __construct(
        private MT_Assignment_Repository_Interface $repository,
        private MT_Logger_Interface $logger,
        private MT_Cache_Interface $cache,
        private MT_Event_Dispatcher_Interface $events
    ) {}
}

// Container resolves all dependencies automatically
$assignment_service = $container->get('MT_Assignment_Service');

// Manual dependency injection for specific use cases
$custom_service = $container->make('MT_Custom_Service', [
    'custom_parameter' => $specific_value
]);
```

### Service Configuration

```php
// Configuration-based service registration
class MT_Core_Service_Provider implements MT_Service_Provider_Interface {
    public function register(MT_Container_Interface $container): void {
        // Core services
        $container->singleton('MT_Logger_Interface', MT_Logger::class);
        $container->singleton('MT_Cache_Interface', MT_Cache::class);
        $container->singleton('MT_Validator_Interface', MT_Validator::class);
        
        // Event system
        $container->singleton('MT_Event_Dispatcher_Interface', function($container) {
            $dispatcher = new MT_Event_Dispatcher();
            $dispatcher->add_subscriber(new MT_Evaluation_Subscriber());
            $dispatcher->add_subscriber(new MT_Assignment_Subscriber());
            return $dispatcher;
        });
        
        // Database services
        $container->bind('MT_Database_Migration_Interface', MT_Database_Migration::class);
    }
}
```

### Testing with Dependency Injection

```php
class MT_Evaluation_Service_Test extends WP_UnitTestCase {
    private $container;
    private $evaluation_service;
    
    public function setUp(): void {
        parent::setUp();
        
        // Create test container
        $this->container = new MT_Container();
        
        // Register test doubles
        $this->container->singleton('MT_Evaluation_Repository_Interface', function() {
            return $this->createMock(MT_Evaluation_Repository_Interface::class);
        });
        
        $this->container->singleton('MT_Logger_Interface', function() {
            return $this->createMock(MT_Logger_Interface::class);
        });
        
        $this->container->singleton('MT_Validator_Interface', function() {
            return $this->createMock(MT_Validator_Interface::class);
        });
        
        // Get service under test
        $this->evaluation_service = $this->container->get('MT_Evaluation_Service');
    }
    
    public function test_save_evaluation_with_valid_data(): void {
        // Arrange
        $repository = $this->container->get('MT_Evaluation_Repository_Interface');
        $repository->expects($this->once())
                  ->method('update')
                  ->with(1, ['scores' => [8.5, 9.0, 7.5]])
                  ->willReturn(true);
        
        // Act
        $result = $this->evaluation_service->save_evaluation(1, [8.5, 9.0, 7.5]);
        
        // Assert
        $this->assertTrue($result);
    }
}
```

### Migration from Legacy Code

```php
// Legacy pattern (before DI)
class MT_Old_Service {
    public function __construct() {
        $this->repository = new MT_Evaluation_Repository(); // Hard dependency
        $this->logger = new MT_Logger(); // Hard dependency
    }
}

// Modern pattern (with DI)
class MT_New_Service {
    public function __construct(
        private MT_Evaluation_Repository_Interface $repository,
        private MT_Logger_Interface $logger
    ) {
        // Dependencies injected, easily testable
    }
}

// Migration helper for gradual adoption
class MT_Legacy_Bridge {
    public static function get_evaluation_service(): MT_Evaluation_Service {
        $container = MT_Container::get_instance();
        return $container->get('MT_Evaluation_Service');
    }
}

// In legacy code
$service = MT_Legacy_Bridge::get_evaluation_service(); // Gradual migration
```

### Container Configuration

```php
// Container initialization in main plugin class
class MT_Plugin {
    private MT_Container $container;
    
    public function __construct() {
        $this->container = MT_Container::get_instance();
        $this->register_services();
        $this->boot_services();
    }
    
    private function register_services(): void {
        // Register all service providers
        $providers = [
            new MT_Core_Service_Provider(),
            new MT_Database_Service_Provider(),
            new MT_Evaluation_Service_Provider(),
            new MT_Assignment_Service_Provider(),
            new MT_Import_Service_Provider(),
            new MT_Admin_Service_Provider(),
            new MT_Ajax_Service_Provider(),
        ];
        
        foreach ($providers as $provider) {
            $this->container->register_provider($provider);
        }
    }
    
    private function boot_services(): void {
        $this->container->boot();
    }
    
    public function get_container(): MT_Container {
        return $this->container;
    }
}
```

### Helper Methods for Service Access

```php
// Global helper function
function mt_container(): MT_Container {
    return MT_Container::get_instance();
}

// Service accessor helpers
function mt_service(string $service_name) {
    return mt_container()->get($service_name);
}

// Specific service helpers
function mt_evaluation_service(): MT_Evaluation_Service {
    return mt_service('MT_Evaluation_Service');
}

function mt_assignment_service(): MT_Assignment_Service {
    return mt_service('MT_Assignment_Service');
}

function mt_logger(): MT_Logger_Interface {
    return mt_service('MT_Logger_Interface');
}

// Usage in WordPress hooks
add_action('wp_ajax_mt_save_evaluation', function() {
    $service = mt_evaluation_service();
    // ... handle request
});
```

## AJAX System

### Security Implementation

```php
class MT_Ajax_Base {
    public function verify_request() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mt_ajax_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'mobility-trailblazers')]);
        }
        
        // Check capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'mobility-trailblazers')]);
        }
        
        return true;
    }
    
    public function sanitize_input($data) {
        return array_map('sanitize_text_field', $data);
    }
}
```

### Modern AJAX Handler with Container

```php
class MT_Evaluation_Ajax extends MT_Base_Ajax {
    private MT_Container $container;
    
    public function __construct(MT_Container $container) {
        $this->container = $container;
        parent::__construct();
    }
    
    public function handle_save_evaluation() {
        $this->verify_request();
        
        $evaluation_id = intval($_POST['evaluation_id']);
        $scores = array_map('floatval', $_POST['scores']);
        $comments = sanitize_textarea_field($_POST['comments'] ?? '');
        
        try {
            // Get service from container
            $service = $this->container->get('MT_Evaluation_Service');
            $result = $service->save_evaluation($evaluation_id, $scores, $comments);
            
            wp_send_json_success([
                'message' => __('Evaluation saved successfully', 'mobility-trailblazers'),
                'data' => $result
            ]);
        } catch (Exception $e) {
            // Logger is also injected through container
            $logger = $this->container->get('MT_Logger_Interface');
            $logger->error('Evaluation save failed', [
                'evaluation_id' => $evaluation_id,
                'user_id' => get_current_user_id(),
                'error' => $e->getMessage()
            ]);
            
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
    
    // Helper method for getting services
    protected function get_service(string $service_name) {
        return $this->container->get($service_name);
    }
}

// Registration in service provider
class MT_Ajax_Service_Provider implements MT_Service_Provider_Interface {
    public function register(MT_Container_Interface $container): void {
        $container->singleton('MT_Evaluation_Ajax', function($container) {
            return new MT_Evaluation_Ajax($container);
        });
    }
    
    public function boot(MT_Container_Interface $container): void {
        // Register AJAX handlers
        $evaluation_ajax = $container->get('MT_Evaluation_Ajax');
        add_action('wp_ajax_mt_save_evaluation', [$evaluation_ajax, 'handle_save_evaluation']);
        add_action('wp_ajax_nopriv_mt_save_evaluation', [$evaluation_ajax, 'handle_save_evaluation']);
    }
}
```

## Database Schema

### Custom Tables

```sql
-- Evaluations Table
CREATE TABLE wp_mt_evaluations (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    candidate_id bigint(20) NOT NULL,
    jury_member_id bigint(20) NOT NULL,
    criterion_1 decimal(3,1) DEFAULT NULL,
    criterion_2 decimal(3,1) DEFAULT NULL,
    criterion_3 decimal(3,1) DEFAULT NULL,
    criterion_4 decimal(3,1) DEFAULT NULL,
    criterion_5 decimal(3,1) DEFAULT NULL,
    comments text,
    status varchar(20) DEFAULT 'draft',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_candidate (candidate_id),
    KEY idx_jury_member (jury_member_id),
    KEY idx_status (status)
);

-- Assignments Table
CREATE TABLE wp_mt_assignments (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    jury_member_id bigint(20) NOT NULL,
    candidate_id bigint(20) NOT NULL,
    assigned_by bigint(20) DEFAULT NULL,
    status varchar(20) DEFAULT 'pending',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_assignment (jury_member_id, candidate_id),
    KEY idx_status (status)
);
```

## Security Implementation

### Input Validation

```php
// Sanitize all inputs
$candidate_name = sanitize_text_field($_POST['name']);
$email = sanitize_email($_POST['email']);
$bio = wp_kses_post($_POST['biography']);
$score = floatval($_POST['score']);

// Validate score range
if ($score < 0 || $score > 10) {
    wp_die(__('Invalid score value', 'mobility-trailblazers'));
}
```

### Output Escaping

```php
// Always escape output
echo esc_html($candidate_name);
echo esc_url($candidate_website);
echo esc_attr($css_class);

// For complex HTML
$allowed_html = [
    'a' => ['href' => [], 'title' => []],
    'strong' => [],
    'em' => []
];
echo wp_kses($content, $allowed_html);
```

### SQL Security

```php
// Always use prepare() for queries
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->mt_evaluations} 
        WHERE jury_member_id = %d 
        AND status = %s",
        $user_id,
        'submitted'
    )
);
```

## Frontend Assets

### CSS v4 Framework (New in 4.1.0)

> **📚 CSS v4 Guide**: For comprehensive CSS v4 documentation, see [CSS v4 Framework Guide](CSS-V4-GUIDE.md).

The plugin now uses a modern, token-based CSS architecture with mobile-first design:

```css
/* CSS v4 Token System */
:root {
    --mt-color-primary: #26a69a;
    --mt-space: 16px;
    --mt-touch-target: 44px;
    --mt-radius: 8px;
}

/* Mobile-First Responsive Design (v4.1.0) */
@media (max-width: 767px) {
    .mt-evaluation-table {
        display: block;
    }
    
    .mt-evaluation-table tr {
        display: block;
        background: var(--mt-color-white);
        border-radius: var(--mt-radius);
        padding: var(--mt-space);
        margin-bottom: var(--mt-space);
    }
}
```

#### CSS v4 File Structure

```
assets/css/v4/
├── mt-tokens.css              # Design tokens
├── mt-reset.css               # CSS reset
├── mt-base.css                # Base styles
├── mt-components.css          # Components
├── mt-pages.css               # Page styles
└── mt-mobile-jury-dashboard.css # Mobile styles (v4.1.0)
```

### Legacy CSS Architecture (Pre-v4)

```css
/* Component Structure */
.mt-evaluation {
    /* Block */
}

.mt-evaluation__header {
    /* Element */
}

.mt-evaluation--submitted {
    /* Modifier */
}

/* Responsive Grid */
.mt-candidates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
}

@media (max-width: 768px) {
    .mt-candidates-grid {
        grid-template-columns: 1fr;
    }
}
```

### JavaScript Loading

```php
// Enqueue scripts with dependencies
wp_enqueue_script(
    'mt-evaluation',
    MT_PLUGIN_URL . 'assets/js/mt-evaluation.js',
    ['jquery', 'jquery-ui-sortable'],
    MT_VERSION,
    true
);

// Localize script
wp_localize_script('mt-evaluation', 'mt_ajax', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('mt_ajax_nonce'),
    'strings' => [
        'saving' => __('Saving...', 'mobility-trailblazers'),
        'saved' => __('Saved!', 'mobility-trailblazers')
    ]
]);
```

## UI Templates & Components

### Component Library

```php
// Card Component
<div class="mt-card">
    <div class="mt-card__header">
        <h3 class="mt-card__title"><?php echo esc_html($title); ?></h3>
    </div>
    <div class="mt-card__body">
        <?php echo wp_kses_post($content); ?>
    </div>
    <div class="mt-card__footer">
        <button class="mt-button mt-button--primary">
            <?php esc_html_e('Save', 'mobility-trailblazers'); ?>
        </button>
    </div>
</div>

// Grid Layout
<div class="mt-grid mt-grid--3-cols">
    <?php foreach ($items as $item): ?>
        <div class="mt-grid__item">
            <?php include 'card.php'; ?>
        </div>
    <?php endforeach; ?>
</div>
```

### Design System

```css
/* Color Variables */
:root {
    --mt-primary: #26a69a;
    --mt-success: #4caf50;
    --mt-warning: #ff9800;
    --mt-error: #f44336;
    --mt-text: #212529;
    --mt-text-light: #6c757d;
    --mt-border: #dee2e6;
}

/* Typography */
.mt-heading-1 { font-size: 2.5rem; }
.mt-heading-2 { font-size: 2rem; }
.mt-heading-3 { font-size: 1.75rem; }

/* Spacing */
.mt-p-1 { padding: 0.25rem; }
.mt-p-2 { padding: 0.5rem; }
.mt-p-3 { padding: 1rem; }
```

## Photo Management System

### Upload System

```php
class MT_Photo_Manager {
    public function handle_upload($file, $candidate_id) {
        // Validate file
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        
        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('Invalid file type');
        }
        
        // Process upload
        $upload = wp_handle_upload($file, ['test_form' => false]);
        
        if (!empty($upload['error'])) {
            throw new Exception($upload['error']);
        }
        
        // Create attachment
        $attachment_id = wp_insert_attachment([
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name($file['name']),
            'post_content' => '',
            'post_status' => 'inherit'
        ], $upload['file'], $candidate_id);
        
        // Generate metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        wp_update_attachment_metadata($attachment_id, $metadata);
        
        // Set as featured image
        set_post_thumbnail($candidate_id, $attachment_id);
        
        return $attachment_id;
    }
}
```

### Display Templates

```php
// Candidate Card with Photo
<div class="mt-candidate-card">
    <?php if (has_post_thumbnail()): ?>
        <div class="mt-candidate-card__image">
            <?php the_post_thumbnail('medium', ['class' => 'mt-photo']); ?>
        </div>
    <?php else: ?>
        <div class="mt-candidate-card__placeholder">
            <img src="<?php echo MT_PLUGIN_URL; ?>assets/img/placeholder.svg" alt="">
        </div>
    <?php endif; ?>
    
    <div class="mt-candidate-card__content">
        <h3><?php the_title(); ?></h3>
        <p><?php echo esc_html(get_post_meta(get_the_ID(), 'company', true)); ?></p>
    </div>
</div>
```

## Auto-Assignment System

### Algorithm

```php
class MT_Auto_Assignment {
    private $max_per_jury = 20;
    private $min_per_candidate = 3;
    
    public function distribute_candidates($jury_members, $candidates) {
        $assignments = [];
        $candidate_counts = array_fill_keys($candidates, 0);
        
        // Round-robin distribution
        foreach ($jury_members as $jury_id) {
            $assigned = 0;
            
            foreach ($candidates as $candidate_id) {
                if ($assigned >= $this->max_per_jury) break;
                
                if ($candidate_counts[$candidate_id] < $this->min_per_candidate) {
                    $assignments[] = [
                        'jury_member_id' => $jury_id,
                        'candidate_id' => $candidate_id
                    ];
                    
                    $candidate_counts[$candidate_id]++;
                    $assigned++;
                }
            }
        }
        
        return $assignments;
    }
}
```

### Usage

```php
// In admin interface
$service = new MT_Assignment_Service();
$result = $service->auto_assign_all();

echo sprintf(
    __('Auto-assigned %d candidates to %d jury members', 'mobility-trailblazers'),
    $result['total_assignments'],
    $result['jury_count']
);
```

## Rich Text Editor

### Overview

The Rich Text Editor provides a lightweight, bulletproof content editing solution for candidate content management. It features a comprehensive formatting toolbar and graceful fallback for older browsers.

### Architecture

```php
// Backend Handler
class MT_Candidate_Editor {
    // Registers AJAX handlers for content management
    public function __construct() {
        add_action('wp_ajax_mt_update_candidate_content', [$this, 'ajax_update_content']);
        add_action('wp_ajax_mt_get_candidate_content', [$this, 'ajax_get_content']);
    }
}
```

```javascript
// Frontend Implementation
var MTCandidateEditor = {
    editorSupported: true,     // ContentEditable support detection
    historyStack: {},          // Undo/redo history per tab
    historyIndex: {},          // Current position in history
    
    executeCommand: function(command) {
        // Handles all formatting commands
        // Falls back to markdown syntax for unsupported browsers
    }
}
```

### Features

- **Rich Text Formatting**: Bold, italic, headings, lists, links
- **Keyboard Shortcuts**: Ctrl+B, Ctrl+I, Ctrl+K, Ctrl+Z, Ctrl+Y
- **History Management**: Up to 50 undo/redo states per editor
- **HTML Sanitization**: Client and server-side security
- **Graceful Fallback**: Textarea with markdown support for older browsers

### Security Implementation

```javascript
// Client-side HTML sanitization
cleanHTML: function(html) {
    var temp = document.createElement('div');
    temp.innerHTML = html;
    
    // Remove dangerous elements and attributes
    var scripts = temp.getElementsByTagName('script');
    var styles = temp.getElementsByTagName('style');
    // ... removal logic
    
    return temp.innerHTML;
}
```

```php
// Server-side sanitization
$content = wp_kses_post($_POST['content']);
update_post_meta($post_id, $field_map[$field], $content);
```

### Usage

```javascript
// Initialize editor on candidate admin page
if (typeof mtCandidateEditor !== 'undefined' && 
    $('body').hasClass('post-type-mt_candidate')) {
    MTCandidateEditor.init();
}
```

### Files

- `includes/admin/class-mt-candidate-editor.php` - Backend handler
- `assets/js/candidate-editor.js` - Frontend implementation and modal integration
- `assets/js/mt-rich-editor.js` - Rich text editor core module (v2.5.32)
- `assets/css/mt-rich-editor.css` - Editor styling (v2.5.32)
- Modal HTML embedded in admin footer for performance

### Documentation

For detailed information about the Rich Text Editor implementation, features, and API, see [Rich Text Editor Documentation](rich-text-editor.md)

## Testing Infrastructure

### PHPUnit Setup

```xml
<!-- phpunit.xml -->
<phpunit bootstrap="tests/bootstrap.php">
    <testsuites>
        <testsuite name="unit">
            <directory>tests/unit</directory>
        </testsuite>
        <testsuite name="integration">
            <directory>tests/integration</directory>
        </testsuite>
    </testsuites>
    <filter>
        <whitelist>
            <directory>includes</directory>
        </whitelist>
    </filter>
</phpunit>
```

### Unit Tests

```php
class MT_Evaluation_Service_Test extends WP_UnitTestCase {
    private $service;
    
    public function setUp() {
        parent::setUp();
        $this->service = new MT_Evaluation_Service();
    }
    
    public function test_calculate_average_score() {
        $scores = [8.5, 9.0, 7.5, 8.0, 9.5];
        $average = $this->service->calculate_average($scores);
        
        $this->assertEquals(8.5, $average);
    }
    
    public function test_validate_score_range() {
        $this->assertTrue($this->service->validate_score(5.5));
        $this->assertFalse($this->service->validate_score(11));
        $this->assertFalse($this->service->validate_score(-1));
    }
}
```

### Integration Tests

```php
class MT_Assignment_Integration_Test extends WP_UnitTestCase {
    public function test_full_assignment_workflow() {
        // Create test data
        $jury_id = $this->factory->user->create(['role' => 'mt_jury_member']);
        $candidate_id = $this->factory->post->create(['post_type' => 'mt_candidate']);
        
        // Test assignment
        $service = new MT_Assignment_Service();
        $result = $service->assign_candidate($jury_id, $candidate_id);
        
        $this->assertTrue($result);
        
        // Verify database
        global $wpdb;
        $assignment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->mt_assignments} 
            WHERE jury_member_id = %d AND candidate_id = %d",
            $jury_id,
            $candidate_id
        ));
        
        $this->assertNotNull($assignment);
        $this->assertEquals('pending', $assignment->status);
    }
}
```

### JavaScript Testing

```javascript
// tests/js/evaluation.test.js
describe('MT.Evaluation', function() {
    beforeEach(function() {
        // Setup DOM
        setFixtures('<div class="mt-evaluation-form"></div>');
    });
    
    it('should validate score range', function() {
        expect(MT.Evaluation.validateScore(5.5)).toBe(true);
        expect(MT.Evaluation.validateScore(11)).toBe(false);
    });
    
    it('should calculate average correctly', function() {
        var scores = [8, 9, 7, 8, 9];
        expect(MT.Evaluation.calculateAverage(scores)).toBe(8.2);
    });
});
```

## Debug Center

### Overview

The Debug Center provides comprehensive diagnostics and maintenance tools for the Mobility Trailblazers platform.

### Features

- **System Health Check**: Database, file permissions, WordPress configuration
- **Data Integrity**: Orphan detection, relationship validation
- **Performance Monitoring**: Query analysis, cache status
- **Maintenance Tools**: Cache clearing, data cleanup, export tools

### Usage

```php
// Access Debug Center
// Navigate to: MT Award System > Debug Center

// Programmatic usage
$debug = new MT_Debug_Center();

// Run health check
$health = $debug->check_system_health();

// Clean orphaned data
$cleaned = $debug->clean_orphaned_data();

// Export debug report
$report = $debug->generate_debug_report();
```

### Diagnostic Queries

```php
// Check for orphaned evaluations
SELECT e.* FROM wp_mt_evaluations e
LEFT JOIN wp_posts p ON e.candidate_id = p.ID
WHERE p.ID IS NULL;

// Find incomplete assignments
SELECT * FROM wp_mt_assignments
WHERE status = 'pending'
AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

// Performance analysis
SELECT COUNT(*) as count, 
       AVG(criterion_1 + criterion_2 + criterion_3 + criterion_4 + criterion_5) / 5 as avg_score
FROM wp_mt_evaluations
WHERE status = 'submitted';
```

## Code Cleanup History

### August 20, 2025 - Major Code Quality Refactoring

**Version**: 2.5.37
**Type**: Comprehensive Plugin Cleanup and Optimization

#### Email Service Removal
**Reason**: Streamlined operation without external dependencies

**Files Removed**:
- `includes/services/class-mt-email-service.php` - Complete email service infrastructure
- `templates/emails/` - Entire directory with all email templates
- Email-related hooks, cron jobs, and scheduled notifications removed

**Files Modified**:
- Coaching dashboard templates - Removed "Send Reminder" and bulk email buttons
- Admin interface classes - Removed email action handlers
- JavaScript files - Removed email-related functions

**Impact**: Plugin now operates without any email dependencies, reducing complexity and external service requirements

#### JavaScript Performance Fixes
**Problem**: Race conditions, memory leaks, and event handler conflicts

**Solutions Implemented**:
- **Race Condition Prevention**: Added submission flags in `assets/js/evaluation-fixes.js` and `assets/js/frontend.js`
- **Memory Leak Resolution**: Implemented `window.mtCleanup` function and Page Visibility API
- **Event Handler Conflicts**: Used namespaced events (`.mt-evaluation`) in `assets/js/evaluation-rating-fix.js`

#### CSS Architecture Consolidation
**Problem**: 40+ CSS files causing performance issues

**Solution**:
- Created `assets/css/mt-hotfixes-consolidated.css` combining 6 hotfix files
- Updated loading logic in `includes/core/class-mt-plugin.php`
- Removed duplicate and redundant stylesheets

#### Debug Logging Standardization
**Problem**: Inconsistent logging across codebase

**Solution**:
- Replaced all `error_log()` calls with structured `MT_Logger` methods across 17 files
- Added severity levels: debug, info, warning, error, critical
- Improved debugging and production monitoring capabilities

#### Elementor Widget Cleanup
**Problem**: Duplicate widget directories and registration

**Solution**:
- Removed redundant directory `includes/integrations/elementor/widgets/`
- Maintained 4 core widgets in `includes/elementor/widgets/`
- Consolidated widget registration and loading logic

### August 18, 2025 - Scroll-to-Top Removal

**Version**: 2.5.31
**Reason**: Feature was causing conflicts and not properly integrated

**Files Removed**:
- `assets/css/mt-scroll-to-top.css` - Ultra-aggressive CSS with excessive specificity
- `assets/js/mt-scroll-to-top.js` - JavaScript implementation
- `includes/integrations/elementor/widgets/class-mt-widget-scroll-to-top.php` - Elementor widget
- `doc/scroll-to-top-implementation.md` - Documentation

**Files Modified**:
- `includes/integrations/elementor/class-mt-elementor-loader.php` - Removed widget registration
- `doc/CHANGELOG.md` - Updated to reflect removal

**Key Notes**:
- The scroll-to-top feature was never properly enqueued in `class-mt-plugin.php`
- CSS used excessive `!important` declarations that could conflict with themes
- Multiple redundant implementations existed
- Decision made to completely remove rather than fix due to unnecessary complexity

**Preserved Working Features**:
- Animation system (v2.5.29) remains intact and functional
- Spacing fixes that resolved white bar issues were preserved
- All other Elementor widgets continue to function

## Troubleshooting

### Common Issues

#### Modal Visibility Issues

**Problem**: Assignment modal not appearing or immediately closing

**Solutions**:
1. Check z-index conflicts:
```css
.mt-modal-overlay {
    z-index: 999998 !important;
}
.mt-modal {
    z-index: 999999 !important;
}
```

2. Verify event handlers:
```javascript
// Prevent propagation
$('.mt-modal').on('click', function(e) {
    e.stopPropagation();
});
```

3. Check for JavaScript errors in console

#### Import Failures

**Problem**: CSV import fails or produces errors

**Solutions**:
1. Verify CSV encoding (must be UTF-8)
2. Check for BOM characters
3. Validate delimiter (comma, semicolon, tab)
4. Ensure required columns present

#### AJAX Errors

**Problem**: AJAX requests failing with 403 or 400 errors

**Solutions**:
1. Verify nonce is current:
```javascript
// Refresh nonce if expired
$.post(ajaxurl, {action: 'mt_refresh_nonce'}, function(response) {
    mt_ajax.nonce = response.data.nonce;
});
```

2. Check user capabilities
3. Verify admin-ajax.php accessibility

#### CSS Unit Spacing Issues

**Problem**: Invalid CSS units causing layout problems

**Common Patterns**:
- `300 px` instead of `300px`
- `1 rem` instead of `1rem`
- `0.5 fr` instead of `0.5fr`

**Solution**: Use regex pattern to detect and fix:
```bash
# Find invalid unit spacing
grep -r "\d\s\+(px|rem|em|fr|deg|s|ms|%|vw|vh)" assets/css/

# Fix with sed (backup first)
sed -i 's/\([0-9]\)\s\+\([a-z%]\)/\1\2/g' file.css
```

#### Evaluation Criteria Missing

**Problem**: Jury evaluation pages missing candidate criteria details

**Root Cause**: Template looking for content in wrong meta fields

**Solution**:
1. Verify criteria meta fields exist:
```php
$criteria = [
    '_mt_criterion_courage',
    '_mt_criterion_innovation', 
    '_mt_criterion_implementation',
    '_mt_criterion_relevance',
    '_mt_criterion_visibility'
];

foreach ($criteria as $field) {
    $value = get_post_meta($candidate_id, $field, true);
    if (empty($value)) {
        MT_Logger::warning("Missing {$field} for candidate {$candidate_id}");
    }
}
```

2. Update template to retrieve correct fields
3. Add fallback display if criteria missing

#### JavaScript Memory Leaks

**Problem**: Browser becomes slow after extended use

**Symptoms**:
- Increasing memory usage
- Slower response times
- Browser crashes

**Solutions**:
1. Implement cleanup functions:
```javascript
// Global cleanup function
window.mtCleanup = function() {
    // Clear intervals
    if (window.mtIntervals) {
        window.mtIntervals.forEach(clearInterval);
        window.mtIntervals = [];
    }
    
    // Remove event listeners
    $(document).off('.mt-namespace');
    
    // Clear cached data
    if (window.mtCache) {
        window.mtCache = {};
    }
};
```

2. Use Page Visibility API:
```javascript
document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'hidden') {
        // Pause operations when tab hidden
        pausePolling();
    } else {
        // Resume when visible
        resumePolling();
    }
});
```

#### Double Submission Issues

**Problem**: Forms submit multiple times causing data corruption

**Solution**: Implement submission flags:
```javascript
var isSubmitting = false;

function handleFormSubmit(e) {
    e.preventDefault();
    
    if (isSubmitting) {
        console.log('Submission already in progress');
        return false;
    }
    
    isSubmitting = true;
    var $button = $(this);
    
    $.ajax({
        // ... ajax config
        complete: function() {
            isSubmitting = false;
            $button.prop('disabled', false);
        }
    });
}
```

#### Image Positioning Problems

**Problem**: Candidate faces cropped in photos

**Solution**: Use object-position adjustment:
```css
/* Specific candidate fix */
body.postid-4627 .mt-candidate-hero-photo {
    object-position: center 25% !important;
}

/* Global improvement */
.mt-candidate-hero-photo {
    object-position: center 30% !important; /* Better default */
}
```

#### Cache-Related Issues

**Problem**: Changes not appearing or stale data

**Solutions**:
1. Clear WordPress cache:
```bash
wp cache flush
```

2. Clear plugin transients:
```php
delete_transient('mt_all_candidates');
delete_transient('mt_evaluation_stats');
```

3. Force reload assets:
```php
// Update version number in plugin file
define('MT_VERSION', '2.5.38-' . time());
```

#### Settings Not Saving

**Problem**: Admin settings form not persisting changes

**Debug Steps**:
1. Check nonce verification
2. Verify user capabilities
3. Check for PHP errors:
```bash
tail -f /var/log/wordpress/debug.log
```

4. Test option saving directly:
```php
update_option('mt_test_setting', 'test_value');
echo get_option('mt_test_setting'); // Should output 'test_value'
```

## Performance Best Practices

### Database Optimization

#### Query Optimization

**Use Prepared Statements Always**:
```php
// Bad - SQL injection risk
$results = $wpdb->get_results("SELECT * FROM {$table} WHERE id = {$user_input}");

// Good - Secure and optimized
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$table} WHERE id = %d",
        $user_input
    )
);
```

**Index Usage**:
```sql
-- Add indexes for frequently queried columns
ALTER TABLE wp_mt_evaluations ADD INDEX idx_status_updated (status, updated_at);
ALTER TABLE wp_mt_assignments ADD INDEX idx_jury_status (jury_member_id, status);
```

**Query Analysis**:
```php
// Enable query debugging
define('SAVEQUERIES', true);

// Analyze slow queries
foreach ($wpdb->queries as $query) {
    if ($query[1] > 0.1) { // Queries taking > 100ms
        MT_Logger::warning('Slow query detected', [
            'query' => $query[0],
            'time' => $query[1]
        ]);
    }
}
```

#### Caching Strategies

**Transient Caching**:
```php
// Cache expensive calculations
function get_evaluation_statistics() {
    $cache_key = 'mt_eval_stats_' . md5(serialize(func_get_args()));
    $stats = get_transient($cache_key);
    
    if (false === $stats) {
        $stats = calculate_evaluation_statistics();
        set_transient($cache_key, $stats, HOUR_IN_SECONDS);
    }
    
    return $stats;
}
```

**Object Caching**:
```php
// Use object cache for frequently accessed data
function get_candidate_meta($candidate_id, $key) {
    $cache_key = "mt_candidate_meta_{$candidate_id}_{$key}";
    $value = wp_cache_get($cache_key, 'mt_candidates');
    
    if (false === $value) {
        $value = get_post_meta($candidate_id, $key, true);
        wp_cache_set($cache_key, $value, 'mt_candidates', 300); // 5 minutes
    }
    
    return $value;
}
```

### Asset Optimization

#### CSS Architecture & Unified Container System (v2.5.38)

**Unified Container Pattern**:
```css
/* Consistent 1200px max-width container for all dashboard widgets */
.mt-jury-dashboard__container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    box-sizing: border-box;
}

/* Applied to all major dashboard elements */
.mt-dashboard-header,
.mt-stats-grid,
.mt-rankings-section,
.mt-evaluation-table-wrap,
.mt-search-filters {
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
    padding-left: 20px;
    padding-right: 20px;
    box-sizing: border-box;
}
```

**CSS Best Practices**:
- Avoid excessive use of `!important` declarations
- Use CSS custom properties for overridable values
- Follow BEM methodology for class naming
- Implement mobile-first responsive design
- Use proper CSS specificity instead of `!important`

**Responsive Breakpoints**:
```css
/* Desktop first, then scale down */
@media (max-width: 1200px) { /* Tablets landscape */ }
@media (max-width: 768px) { /* Tablets portrait */ }
@media (max-width: 480px) { /* Mobile devices */ }
```

#### CSS Loading Strategy

**Conditional Loading**:
```php
// Load only necessary CSS
public function enqueue_styles() {
    // Always load core styles
    wp_enqueue_style('mt-core', MT_PLUGIN_URL . 'assets/css/core.css');
    
    // Conditional loading based on page type
    if (is_singular('mt_candidate')) {
        wp_enqueue_style('mt-candidate', MT_PLUGIN_URL . 'assets/css/candidate.css');
    }
    
    if ($this->is_using_elementor_widgets()) {
        wp_enqueue_style('mt-v3-system', MT_PLUGIN_URL . 'assets/css/v3/mt-tokens.css');
    }
}
```

**CSS Consolidation**:
```php
// Combine related CSS files
wp_enqueue_style(
    'mt-hotfixes-consolidated',
    MT_PLUGIN_URL . 'assets/css/mt-hotfixes-consolidated.css',
    ['mt-frontend'],
    MT_VERSION
);
```

#### JavaScript Optimization

**Module Pattern with Cleanup**:
```javascript
var MTModule = (function($) {
    'use strict';
    
    var intervals = [];
    var timeouts = [];
    var isInitialized = false;
    
    function init() {
        if (isInitialized) return;
        
        bindEvents();
        startPolling();
        isInitialized = true;
    }
    
    function startPolling() {
        var intervalId = setInterval(updateData, 5000);
        intervals.push(intervalId);
    }
    
    function cleanup() {
        intervals.forEach(clearInterval);
        timeouts.forEach(clearTimeout);
        intervals = [];
        timeouts = [];
        isInitialized = false;
    }
    
    // Page Visibility API for resource management
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden') {
            cleanup();
        } else if (!isInitialized) {
            init();
        }
    });
    
    return {
        init: init,
        cleanup: cleanup
    };
})(jQuery);
```

### Memory Management

**PHP Memory Optimization**:
```php
// Free memory after large operations
function process_large_dataset($data) {
    foreach (array_chunk($data, 100) as $chunk) {
        process_chunk($chunk);
        unset($chunk); // Free memory
    }
    
    // Force garbage collection
    if (function_exists('gc_collect_cycles')) {
        gc_collect_cycles();
    }
}
```

**JavaScript Memory Management**:
```javascript
// Prevent memory leaks in event handlers
function bindEvents() {
    // Use namespaced events for easy cleanup
    $(document).on('click.mt-evaluation', '.evaluate-btn', handleEvaluation);
    $(document).on('change.mt-evaluation', '.score-input', handleScoreChange);
}

function unbindEvents() {
    $(document).off('.mt-evaluation');
}
```

### Performance Monitoring

**Query Performance Tracking**:
```php
class MT_Performance_Monitor {
    private static $queries = [];
    
    public static function log_query($query, $time) {
        self::$queries[] = [
            'query' => $query,
            'time' => $time,
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
        ];
    }
    
    public static function get_slow_queries($threshold = 0.1) {
        return array_filter(self::$queries, function($q) use ($threshold) {
            return $q['time'] > $threshold;
        });
    }
}
```

## CSS and Styling Guidelines

### CSS Architecture

#### Design Token System

**Color Tokens**:
```css
:root {
    /* Brand Colors */
    --mt-primary: #003C3D;
    --mt-secondary: #004C5F;
    --mt-accent: #C1693C;
    --mt-kupfer-soft: #BB6F52;
    
    /* Background Colors */
    --mt-bg-beige: #F8F0E3;
    --mt-card-bg: #FFFFFF;
    --mt-overlay-bg: rgba(0, 60, 61, 0.9);
    
    /* Text Colors */
    --mt-text: #302C37;
    --mt-text-light: #6c757d;
    --mt-text-inverse: #FFFFFF;
    
    /* Border Colors */
    --mt-border-soft: #E8DCC9;
    --mt-border-focus: #26a69a;
}
```

**Spacing Tokens**:
```css
:root {
    /* Spacing Scale */
    --mt-space-1: 4px;
    --mt-space-2: 8px;
    --mt-space-3: 12px;
    --mt-space-4: 16px;
    --mt-space-6: 24px;
    --mt-space-8: 32px;
    --mt-space-12: 48px;
    --mt-space-16: 64px;
    
    /* Component Sizes */
    --mt-avatar-size: 104px;
    --mt-card-border-radius: 16px;
    --mt-button-height: 44px;
}
```

#### Component-Based CSS

**BEM Methodology**:
```css
/* Block */
.mt-candidate-card {
    background: var(--mt-card-bg);
    border-radius: var(--mt-card-border-radius);
    padding: var(--mt-space-6);
}

/* Element */
.mt-candidate-card__image {
    width: var(--mt-avatar-size);
    height: var(--mt-avatar-size);
    border-radius: 50%;
    object-fit: cover;
    object-position: center 30%;
}

/* Modifier */
.mt-candidate-card--featured {
    border: 2px solid var(--mt-accent);
    transform: scale(1.02);
}
```

**Scoped Styles for Widgets**:
```css
/* Scope all styles to widget container */
.elementor-widget-mt_candidates_grid {
    /* Widget-specific styles here */
}

.elementor-widget-mt_candidates_grid .mt-candidate-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: var(--mt-space-6);
}
```

### Responsive Design Patterns

**Mobile-First Approach**:
```css
/* Mobile default */
.mt-candidate-grid {
    grid-template-columns: 1fr;
    gap: var(--mt-space-4);
}

/* Tablet */
@media (min-width: 768px) {
    .mt-candidate-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: var(--mt-space-6);
    }
}

/* Desktop */
@media (min-width: 1024px) {
    .mt-candidate-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
```

**Container Queries (Future)**:
```css
/* When container query support improves */
@container (min-width: 600px) {
    .mt-candidate-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
```

### CSS Performance Guidelines

#### Specificity Management

**Avoid !important**:
```css
/* Bad - High specificity, hard to override */
.mt-card .mt-card__title.mt-title-large {
    font-size: 24px !important;
}

/* Good - Scoped specificity */
.elementor-widget-mt_candidates_grid .mt-card__title {
    font-size: 24px;
}
```

**Use CSS Custom Properties for Overrides**:
```css
/* Base component */
.mt-button {
    background: var(--mt-button-bg, var(--mt-primary));
    color: var(--mt-button-color, white);
}

/* Context-specific override */
.mt-hero-section {
    --mt-button-bg: var(--mt-accent);
    --mt-button-color: white;
}
```

#### Selector Optimization

**Efficient Selectors**:
```css
/* Good - Specific, shallow */
.mt-candidate-card .mt-card__title {}

/* Bad - Deep nesting, slow */
.mt-section .mt-container .mt-grid .mt-card .mt-content .mt-title {}

/* Good - Class-based targeting */
.mt-card-title-large {}
```

### Animation Guidelines

**Performance-Conscious Animations**:
```css
/* Use transform and opacity for smooth animations */
.mt-card {
    transition: transform 0.2s ease, opacity 0.2s ease;
    will-change: transform; /* Hint to browser for optimization */
}

.mt-card:hover {
    transform: translateY(-4px) scale(1.02);
}

/* Respect user preferences */
@media (prefers-reduced-motion: reduce) {
    .mt-card {
        transition: none;
    }
}
```

**GPU-Accelerated Properties**:
```css
/* Use these properties for smooth animations */
.mt-animated-element {
    transform: translateZ(0); /* Force GPU acceleration */
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        transform: translateX(-100%) translateZ(0);
        opacity: 0;
    }
    to {
        transform: translateX(0) translateZ(0);
        opacity: 1;
    }
}
```

### CSS Quality Assurance

**Stylelint Configuration**:
```json
{
  "extends": "stylelint-config-standard",
  "rules": {
    "declaration-no-important": true,
    "color-named": "never",
    "selector-max-id": 0,
    "selector-max-universal": 1,
    "custom-property-pattern": "^mt-[a-z-]+$"
  }
}
```

**CSS Validation Workflow**:
```bash
# Install stylelint
npm install -g stylelint stylelint-config-standard

# Validate CSS files
stylelint "assets/css/**/*.css"

# Auto-fix common issues
stylelint "assets/css/**/*.css" --fix
```

## Removed Functionality (v2.5.38)

### Email Service System
**Complete removal of all email functionality to streamline plugin operation**

#### What Was Removed
- **Email Service Class**: `includes/services/class-mt-email-service.php`
- **Email Templates**: Entire `templates/emails/` directory
- **Email Actions**: All "Send Reminder" buttons and bulk email operations
- **Email Hooks**: WordPress hooks for email scheduling and delivery
- **Cron Jobs**: Scheduled email reminders and notifications

#### Impact on Functionality
- **Coaching Dashboard**: Simplified interface without email action buttons
- **Jury Management**: No automatic email notifications for assignments
- **Evaluation Reminders**: No scheduled reminder emails
- **Assignment Notifications**: No email alerts for new assignments

#### Migration Notes
If email functionality needs to be restored in the future:
1. Re-implement `MT_Email_Service` class with proper WordPress mail functions
2. Recreate email templates in `templates/emails/` directory
3. Add email action buttons back to coaching dashboard
4. Implement proper email queue and cron job scheduling
5. Add email settings to admin configuration

#### Alternative Solutions
- Use external email marketing platforms for jury communications
- Implement in-dashboard notification system instead of emails
- Use WordPress built-in user notification system
- Manual email communication through standard email clients

## New Development Patterns (v2.5.38)

### Structured Logging Pattern
Replace direct `error_log()` calls with structured logging:

```php
// Old Pattern (Deprecated)
error_log('Something happened: ' . $data);

// New Pattern (Required)
MT_Logger::info('Operation completed successfully', [
    'operation' => 'assignment_creation',
    'user_id' => get_current_user_id(),
    'data' => $sanitized_data
]);

// Severity Levels
MT_Logger::debug('Debug information');    // Development only
MT_Logger::info('General information');   // Normal operations
MT_Logger::warning('Warning condition'); // Attention needed
MT_Logger::error('Error condition');     // Error occurred
MT_Logger::critical('Critical failure'); // System failure
```

### JavaScript Memory Management
Implement proper cleanup patterns:

```javascript
// Memory Leak Prevention Pattern
var MTModule = {
    intervals: [],
    timeouts: [],
    
    init: function() {
        // Store references for cleanup
        this.intervals.push(setInterval(this.updateData, 5000));
        this.timeouts.push(setTimeout(this.initialize, 1000));
        
        // Page Visibility API for resource management
        document.addEventListener('visibilitychange', this.handleVisibilityChange.bind(this));
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', this.cleanup.bind(this));
    },
    
    cleanup: function() {
        // Clear all intervals and timeouts
        this.intervals.forEach(clearInterval);
        this.timeouts.forEach(clearTimeout);
        this.intervals = [];
        this.timeouts = [];
        
        // Remove event listeners
        document.removeEventListener('visibilitychange', this.handleVisibilityChange);
    },
    
    handleVisibilityChange: function() {
        if (document.visibilityState === 'hidden') {
            // Pause operations when tab is hidden
            this.pauseOperations();
        } else {
            // Resume when tab becomes visible
            this.resumeOperations();
        }
    }
};

// Global cleanup function
window.mtCleanup = function() {
    if (typeof MTModule !== 'undefined') {
        MTModule.cleanup();
    }
};
```

### Double-Submission Prevention
Prevent race conditions in form submissions:

```javascript
// Double-Submission Prevention Pattern
var isSubmitting = false;

function handleFormSubmit(e) {
    e.preventDefault();
    
    // Check if already submitting
    if (isSubmitting) {
        console.log('Form submission already in progress');
        return false;
    }
    
    isSubmitting = true;
    var $button = $(this);
    var originalText = $button.text();
    
    // Update UI to show processing
    $button.prop('disabled', true).text('Submitting...');
    
    $.ajax({
        // ... ajax configuration
        complete: function() {
            // Always reset state in complete handler
            isSubmitting = false;
            $button.prop('disabled', false).text(originalText);
        }
    });
}
```

### Namespaced Event Handling
Prevent event handler conflicts:

```javascript
// Namespaced Events Pattern
$(document).off('.mt-evaluation'); // Remove only MT evaluation events

$(document).on('click.mt-evaluation', '.evaluate-button', function() {
    // Handler logic
});

$(document).on('change.mt-evaluation', '.score-input', function() {
    // Handler logic
});

// Cleanup specific namespace
function cleanupEvaluationEvents() {
    $(document).off('.mt-evaluation');
}
```

### CSS Consolidation Pattern
Combine related stylesheets to reduce HTTP requests:

```php
// CSS Consolidation Pattern
// Before: Multiple files
wp_enqueue_style('mt-hotfix-1', ...);
wp_enqueue_style('mt-hotfix-2', ...);
wp_enqueue_style('mt-hotfix-3', ...);

// After: Consolidated file
wp_enqueue_style(
    'mt-hotfixes-consolidated',
    MT_PLUGIN_URL . 'assets/css/mt-hotfixes-consolidated.css',
    ['mt-frontend'],
    MT_VERSION
);
```

## Mobile Development (v4.1.0)

### Mobile-First Approach

The v4.1.0 release introduces comprehensive mobile-first design patterns:

```php
// PHP Mobile Detection
class MT_Mobile_Styles {
    public function inject_critical_mobile_css() {
        // Critical CSS injection for mobile
        if ($this->is_mobile_view()) {
            echo '<style id="mt-mobile-critical-css">';
            include MT_PLUGIN_DIR . 'assets/css/critical-mobile.css';
            echo '</style>';
        }
    }
}
```

### Touch Optimization

All interactive elements follow touch-friendly guidelines:

```css
/* Minimum touch target sizes */
button, input, select, a {
    min-height: 44px;  /* iOS recommendation */
    min-width: 44px;
}

/* Prevent zoom on input focus (iOS) */
input, select, textarea {
    font-size: 16px;
}
```

### Responsive Breakpoints

```scss
// Mobile-first breakpoints
$breakpoints: (
    'xs': 320px,   // Small phones
    'sm': 375px,   // Standard phones
    'md': 414px,   // Large phones
    'lg': 768px,   // Tablets
    'xl': 1024px,  // Desktop
    'xxl': 1200px  // Wide screens
);
```

### Table-to-Card Pattern

Transform tables into cards on mobile:

```javascript
// JavaScript enhancement
function transformTableToCards() {
    if (window.innerWidth <= 767) {
        $('.mt-evaluation-table tr').each(function() {
            $(this).addClass('mt-mobile-card');
            // Add data labels for accessibility
            $(this).find('td').each(function(index) {
                $(this).attr('data-label', headers[index]);
            });
        });
    }
}
```

## Best Practices

### Code Standards

1. **Always escape output**: Use appropriate WordPress escaping functions
2. **Sanitize input**: Never trust user input
3. **Use nonces**: All forms and AJAX requests must verify nonces
4. **Check capabilities**: Verify user permissions before operations
5. **Prepare SQL**: Always use $wpdb->prepare() for queries
6. **Use structured logging**: Replace error_log() with MT_Logger methods
7. **Implement cleanup**: Always provide cleanup methods for JavaScript modules
8. **Prevent race conditions**: Use submission flags in AJAX operations

### Development Workflow

1. **Review existing code** before implementing new features
2. **Follow naming conventions**: mt_ prefix for all custom elements
3. **Document changes**: Update changelog and relevant documentation
4. **Test thoroughly**: Unit tests, integration tests, manual testing
5. **Consider backwards compatibility**: Don't break existing functionality

### Performance Guidelines

1. **Minimize database queries**: Use caching where appropriate
2. **Optimize assets**: Minify CSS/JS in production
3. **Lazy load images**: Especially for candidate grids
4. **Use pagination**: Don't load all records at once
5. **Profile regularly**: Monitor slow queries and bottlenecks

### Security Checklist

- [ ] All inputs sanitized
- [ ] All outputs escaped
- [ ] Nonces verified
- [ ] Capabilities checked
- [ ] SQL injection prevented
- [ ] XSS protection implemented
- [ ] CSRF tokens used
- [ ] File upload validation
- [ ] Directory traversal prevented
- [ ] Error messages sanitized

## Additional Documentation

### Related Documentation Files

- **[Dependency Injection Guide](DEPENDENCY-INJECTION-GUIDE.md)** - Comprehensive guide to the DI architecture
- **[API Reference](API-REFERENCE.md)** - Complete API documentation for all services and interfaces
- **[Migration Guide](MIGRATION-GUIDE.md)** - Step-by-step guide for migrating legacy code to DI patterns
- **[Testing Strategies](TESTING-STRATEGIES.md)** - Best practices for testing with dependency injection
- **[Rich Text Editor Documentation](rich-text-editor.md)** - Detailed implementation guide for the rich text editor

### External Resources

- **[WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)** - WordPress development standards
- **[PSR-11 Container Interface](https://www.php-fig.org/psr/psr-11/)** - Container interface specification
- **[SOLID Principles](https://en.wikipedia.org/wiki/SOLID)** - Object-oriented design principles

---

*For additional support, refer to the WordPress Plugin Handbook, the related documentation files above, and the project's GitHub repository.*