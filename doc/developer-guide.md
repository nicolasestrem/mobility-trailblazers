# Mobility Trailblazers Developer Guide

*Version 2.5.38 | Last Updated: August 20, 2025*

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [File Structure](#file-structure)
3. [JavaScript Architecture](#javascript-architecture)
4. [PHP Architecture](#php-architecture)
5. [AJAX System](#ajax-system)
6. [Database Schema](#database-schema)
7. [Security Implementation](#security-implementation)
8. [Frontend Assets](#frontend-assets)
9. [UI Templates & Components](#ui-templates--components)
10. [Photo Management System](#photo-management-system)
11. [Auto-Assignment System](#auto-assignment-system)
12. [Rich Text Editor](#rich-text-editor)
13. [Testing Infrastructure](#testing-infrastructure)
14. [Debug Center](#debug-center)
15. [Troubleshooting](#troubleshooting)
16. [Best Practices](#best-practices)

## Architecture Overview

The Mobility Trailblazers plugin follows a modern MVC architecture with clear separation of concerns.

### Core Design Patterns

- **Repository-Service-Controller**: Clean data access and business logic separation
- **WordPress Integration**: Leverages WordPress APIs while maintaining modularity
- **AJAX-First**: Real-time updates without page refreshes
- **Security-First**: Comprehensive nonce verification and capability checks

### Key Integration Points

```php
// WordPress Hooks
add_action('init', [$this, 'register_post_types']);
add_action('admin_menu', [$this, 'add_admin_menus']);
add_action('wp_ajax_mt_*', [$this, 'handle_ajax']);

// Custom Tables
global $wpdb;
$wpdb->mt_evaluations
$wpdb->mt_assignments
```

## File Structure

```
mobility-trailblazers/
├── mobility-trailblazers.php      # Main plugin file
├── includes/
│   ├── core/
│   │   ├── class-mt-plugin.php          # Core plugin class
│   │   ├── class-mt-activator.php       # Activation hooks
│   │   ├── class-mt-database.php        # Database operations
│   │   └── class-mt-deactivator.php     # Deactivation cleanup
│   ├── admin/
│   │   ├── class-mt-admin.php           # Admin interface
│   │   ├── class-mt-admin-columns.php   # Custom columns
│   │   └── class-mt-admin-menus.php     # Menu registration
│   ├── ajax/
│   │   ├── class-mt-ajax-base.php       # Base AJAX handler
│   │   ├── class-mt-ajax-evaluation.php # Evaluation AJAX
│   │   └── class-mt-ajax-assignment.php # Assignment AJAX
│   ├── repositories/
│   │   ├── class-mt-candidate-repository.php
│   │   ├── class-mt-evaluation-repository.php
│   │   └── class-mt-assignment-repository.php
│   ├── services/
│   │   ├── class-mt-assignment-service.php
│   │   ├── class-mt-evaluation-service.php
│   │   └── class-mt-import-service.php
│   ├── widgets/
│   │   ├── class-mt-widget-evaluation-progress.php
│   │   └── class-mt-widget-jury-assignments.php
│   └── utilities/
│       ├── class-mt-import-handler.php
│       └── class-mt-export-handler.php
├── templates/
│   ├── admin/
│   │   ├── dashboard-jury.php
│   │   └── assignments-manager.php
│   └── public/
│       ├── candidate-single.php
│       └── candidate-archive.php
├── assets/
│   ├── css/
│   │   ├── mt-admin.css
│   │   └── mt-public.css
│   └── js/
│       ├── mt-evaluation.js
│       ├── mt-assignments.js
│       └── mt-import.js
└── languages/
    ├── mobility-trailblazers-de_DE.po
    └── mobility-trailblazers-de_DE.mo
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

## PHP Architecture

### Service Layer Pattern

```php
class MT_Assignment_Service {
    private $repository;
    
    public function __construct(MT_Assignment_Repository $repository) {
        $this->repository = $repository;
    }
    
    public function auto_assign_candidates($jury_member_id, $count = 10) {
        // Business logic
        $available = $this->repository->get_unassigned_candidates();
        $conflicts = $this->check_conflicts($jury_member_id);
        
        // Filter and assign
        $filtered = array_diff($available, $conflicts);
        $selected = array_slice($filtered, 0, $count);
        
        return $this->repository->create_assignments($jury_member_id, $selected);
    }
}
```

### Repository Pattern

```php
class MT_Evaluation_Repository {
    private $wpdb;
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'mt_evaluations';
    }
    
    public function find($id) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $id
            )
        );
    }
    
    public function update($id, $data) {
        return $this->wpdb->update(
            $this->table_name,
            $data,
            ['id' => $id],
            ['%s', '%d', '%d', '%d', '%d', '%d', '%s'],
            ['%d']
        );
    }
}
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

### AJAX Handler Example

```php
public function handle_save_evaluation() {
    $this->verify_request();
    
    $evaluation_id = intval($_POST['evaluation_id']);
    $scores = array_map('floatval', $_POST['scores']);
    
    try {
        $service = new MT_Evaluation_Service();
        $result = $service->save_evaluation($evaluation_id, $scores);
        
        wp_send_json_success([
            'message' => __('Evaluation saved successfully', 'mobility-trailblazers'),
            'data' => $result
        ]);
    } catch (Exception $e) {
        wp_send_json_error([
            'message' => $e->getMessage()
        ]);
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

### CSS Architecture

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

**Version**: 2.5.38
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

### Performance Optimization

```php
// Enable query caching
define('MT_ENABLE_CACHE', true);

// Optimize database queries
$wpdb->query("OPTIMIZE TABLE {$wpdb->mt_evaluations}");

// Use transients for expensive operations
$candidates = get_transient('mt_all_candidates');
if (false === $candidates) {
    $candidates = get_posts(['post_type' => 'mt_candidate', 'posts_per_page' => -1]);
    set_transient('mt_all_candidates', $candidates, HOUR_IN_SECONDS);
}
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

---

*For additional support, refer to the WordPress Plugin Handbook and the project's GitHub repository.*