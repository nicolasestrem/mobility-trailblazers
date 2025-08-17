# Mobility Trailblazers - Master Developer Guide
*Last Updated: August 17, 2025 | Version 2.5.0*

## Table of Contents
1. [Architecture Overview](#architecture-overview)
2. [JavaScript Architecture](#javascript-architecture)
3. [PHP Architecture](#php-architecture)
4. [AJAX System](#ajax-system)
5. [Database Schema](#database-schema)
6. [Security Implementation](#security-implementation)
7. [Auto-Assignment System](#auto-assignment-system)
8. [Frontend Assets Management](#frontend-assets-management)
9. [Dashboard System](#dashboard-system)
10. [Audit Logging](#audit-logging)
11. [User Roles & Capabilities](#user-roles--capabilities)
12. [Plugin Settings](#plugin-settings)
13. [Testing & Debugging](#testing--debugging)
14. [Code Examples](#code-examples)
15. [Best Practices](#best-practices)
16. [Version History](#version-history)

---

## Architecture Overview

The Mobility Trailblazers platform follows a **Repository-Service-Controller** pattern:

### Core Architecture Pattern
- **Controllers** (AJAX handlers) - Handle requests and responses
- **Services** - Contain business logic and validation
- **Repositories** - Manage database operations  
- **Templates** - Present data to users

### Key Integration Points
1. **WordPress Hooks** - Plugin integrates via standard WP action/filter system
2. **AJAX Endpoints** - All AJAX calls route through wp-admin/admin-ajax.php
3. **Database Tables** - Custom tables prefixed with 'mt_' for data isolation
4. **User Roles** - Extends WordPress roles with custom capabilities
5. **Shortcodes** - [mt_jury_dashboard], [mt_voting_form], [mt_rankings]

### Directory Structure
```
mobility-trailblazers/
├── includes/
│   ├── admin/           # Admin interface classes
│   ├── ajax/            # AJAX handlers
│   ├── core/            # Core functionality
│   ├── repositories/    # Data access layer
│   ├── services/        # Business logic
│   └── utilities/       # Utility classes
├── templates/
│   ├── admin/           # Admin templates
│   └── frontend/        # Public templates
├── assets/
│   ├── css/             # Stylesheets
│   ├── js/              # JavaScript
│   └── images/          # Images
└── doc/                 # Documentation
```

---

## JavaScript Architecture

### Module Structure

The admin JavaScript is organized into modular components that load conditionally based on the current admin page.

#### General Utilities (Loaded on All Admin Pages)
```javascript
// Core utilities available globally
initTooltips()              // Initialize tooltip functionality
initTabs()                   // Tab navigation system
initModals()                 // Modal dialog handling
initConfirmations()          // Confirmation dialogs
initAjaxForms()             // AJAX form submissions
initMediaUpload()           // WordPress media library integration

// Global utility functions
mtShowNotification(msg, type)      // Display admin notices
mtHandleAjaxError(xhr)            // Standardized error handling
mtSerializeForm(form)              // Form data serialization
mtUpdateUrlParam(key, value)      // URL parameter management
mtGetUrlParam(key)                // Get URL parameter
mtFormatNumber(num)               // Number formatting for DACH region
mtDebounce(func, wait)            // Function debouncing utility
refreshDashboardWidget(id, cb)    // Refresh widget via AJAX (v2.2.28)
refreshDashboardWidgets(ids)      // Refresh multiple widgets (v2.2.28)
```

#### Assignment Management Module (`MTAssignmentManager`)
Complete assignment management functionality:

```javascript
MTAssignmentManager = {
    init: function() {
        // Entry point, sets up all event handlers
    },
    
    // Modal Management
    showAutoAssignModal: function() {},
    showManualAssignModal: function() {},
    
    // Assignment Operations  
    submitAutoAssignment: function() {},
    submitManualAssignment: function() {},
    removeAssignment: function($button) {},
    clearAllAssignments: function() {},
    exportAssignments: function() {},
    
    // Bulk Operations
    toggleBulkActions: function() {},
    applyBulkAction: function() {},
    bulkRemoveAssignments: function(ids) {},
    bulkExportAssignments: function(ids) {},
    
    // Filtering & Search
    filterAssignments: function(searchTerm) {},
    applyFilters: function() {}
}
```

#### Evaluation Management Module (`MTEvaluationManager`)
```javascript
MTEvaluationManager = {
    init: function() {},
    bindEvents: function() {},
    viewEvaluationDetails: function() {},
    handleSelectAll: function() {},
    updateSelectAllCheckbox: function() {},
    handleBulkAction: function() {},
    performBulkAction: function(action, evaluationIds) {}
}
```

### Conditional Loading

The system detects pages and loads appropriate modules:

```javascript
// Assignment Management page detection
if ($('#mt-auto-assign-btn').length > 0 ||
    $('.mt-assignments-table').length > 0 ||
    $('body').hasClass('mobility-trailblazers_page_mt-assignment-management')) {
    
    MTAssignmentManager.init();
    if ($('.mt-assignments-table').length > 0) {
        MTBulkOperations.init();
    }
}

// Evaluations page detection
if ($('.wrap h1:contains("Evaluations")').length > 0 && 
    $('.wp-list-table').length > 0) {
    MTEvaluationManager.init();
}
```

### Event Handling Patterns

#### Event Delegation (v2.2.28+)
```javascript
// Use delegation for dynamic content
$(document).on('click', '.mt-remove-assignment', (e) => {
    e.preventDefault();
    this.removeAssignment($(e.currentTarget));
});
```

#### Direct Binding
```javascript
// For static elements
$('#mt-auto-assign-btn').on('click', (e) => {
    e.preventDefault();
    this.showAutoAssignModal();
});
```

### AJAX Pattern

Standardized AJAX calls with proper error handling:

```javascript
$.ajax({
    url: mt_admin.ajax_url,
    type: 'POST',
    data: {
        action: 'mt_action_name',
        nonce: mt_admin.nonce,
        // additional data
    },
    beforeSend: () => {
        // Disable UI, show loading state
        $button.prop('disabled', true);
        $button.text('Processing...');
    },
    success: (response) => {
        if (response.success) {
            mtShowNotification(response.data.message, 'success');
            // Handle success
        } else {
            mtShowNotification(response.data.message, 'error');
        }
    },
    error: (xhr, status, error) => {
        mtHandleAjaxError(xhr);
    },
    complete: () => {
        // Re-enable UI
        $button.prop('disabled', false);
        $button.text(originalText);
    }
});
```

---

## PHP Architecture

### Core System Classes

#### Plugin Initialization
```php
// class-mt-plugin.php
class MT_Plugin {
    public static function init() {
        self::load_dependencies();
        self::define_admin_hooks();
        self::define_public_hooks();
        self::register_post_types();
        self::initialize_services();
    }
}
```

#### Service Layer Pattern
```php
// Example: MT_Evaluation_Service
class MT_Evaluation_Service {
    private $repository;
    
    public function __construct() {
        $this->repository = new MT_Evaluation_Repository();
    }
    
    public function submit_evaluation($data) {
        // Validation
        if (!$this->validate_data($data)) {
            return ['success' => false, 'message' => 'Invalid data'];
        }
        
        // Business logic
        $data = $this->calculate_scores($data);
        
        // Persistence
        $result = $this->repository->create($data);
        
        // Audit logging
        MT_Audit_Logger::log('evaluation_submitted', 'evaluation', $result['id']);
        
        return $result;
    }
}
```

#### Repository Pattern
```php
// Example: MT_Assignment_Repository
class MT_Assignment_Repository {
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'mt_jury_assignments';
    }
    
    public function find($id) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $id
            )
        );
    }
    
    // v2.2.28 additions
    public function cleanup_orphaned_assignments() {
        // Remove invalid assignment records
    }
    
    public function verify_integrity() {
        // Check for database integrity issues
    }
}
```

### Class Organization

#### Admin Classes
- `MT_Admin` - Admin menu setup and page rendering
- `MT_Admin_Notices` - System notifications
- `MT_Candidate_Columns` - Custom columns and import/export
- `MT_Import_Export` - CSV import/export handler
- `MT_Import_Handler` - Centralized CSV processing (v2.2.28)
- `MT_Debug_Manager` - Debug script management (v2.3.0)
- `MT_Maintenance_Tools` - System maintenance (v2.3.0)
- `MT_Coaching` - Jury coaching dashboard (v2.2.29)

#### Service Classes
- `MT_Evaluation_Service` - Evaluation processing
- `MT_Assignment_Service` - Assignment management (v2.2.29 - rebalancing)
- `MT_Import_Service` - Data import processing
- `MT_Diagnostic_Service` - System diagnostics (v2.3.0)

#### Utility Classes (v2.3.0)
- `MT_Database_Health` - Database monitoring
- `MT_System_Info` - System information gathering

---

## AJAX System

### Base AJAX Class (v2.2.28 Enhanced)

All AJAX handlers extend the base class for consistent security and error handling:

```php
abstract class MT_Base_Ajax {
    public function __construct() {
        $this->init();
    }
    
    abstract protected function init();
    
    protected function verify_nonce($nonce_name) {
        return wp_verify_nonce($_POST['nonce'] ?? '', $nonce_name);
    }
    
    protected function check_permission($capability) {
        if (!current_user_can($capability)) {
            $this->error(__('Permission denied', 'mobility-trailblazers'));
            return false;
        }
        return true;
    }
    
    // v2.2.28 addition
    protected function validate_upload($file, $allowed_types, $max_size) {
        // Comprehensive file validation
        // - Extension check
        // - MIME type validation
        // - Size limits
        // - Malicious content detection
        return true; // or error message
    }
    
    protected function success($data, $message = '') {
        wp_send_json_success([
            'message' => $message,
            'data' => $data
        ]);
    }
    
    protected function error($message, $data = null) {
        MT_Logger::log_error('AJAX Error', $message);
        wp_send_json_error([
            'message' => $message,
            'data' => $data
        ]);
    }
}
```

### AJAX Handler Implementation

```php
class MT_Assignment_Ajax extends MT_Base_Ajax {
    protected function init() {
        add_action('wp_ajax_mt_auto_assign', [$this, 'auto_assign']);
        add_action('wp_ajax_mt_remove_assignment', [$this, 'remove_assignment']);
    }
    
    public function auto_assign() {
        // Security first
        if (!$this->verify_nonce('mt_admin_nonce')) {
            $this->error('Security check failed');
            return;
        }
        
        if (!$this->check_permission('mt_manage_assignments')) {
            return; // check_permission already sends error
        }
        
        // Sanitize input
        $method = sanitize_text_field($_POST['method'] ?? 'balanced');
        $candidates_per_jury = intval($_POST['candidates_per_jury'] ?? 10);
        
        // Business logic
        $service = new MT_Assignment_Service();
        $result = $service->auto_assign($method, $candidates_per_jury);
        
        // Response
        if ($result['success']) {
            $this->success($result['data'], $result['message']);
        } else {
            $this->error($result['message']);
        }
    }
}
```

### Available AJAX Actions

#### Assignment Management
- `mt_auto_assign` - Auto-assign candidates to jury
- `mt_manual_assign` - Manual assignment
- `mt_remove_assignment` - Remove single assignment
- `mt_clear_all_assignments` - Clear all assignments
- `mt_bulk_remove_assignments` - Bulk remove

#### Evaluation Management
- `mt_submit_evaluation` - Submit evaluation
- `mt_save_draft` - Save as draft
- `mt_bulk_evaluation_action` - Bulk operations

#### Import/Export
- `mt_import_candidates` - Import CSV (v2.2.16)
- `mt_export_assignments` - Export to CSV
- `mt_csv_import_ajax` - Import with progress (v2.2.24)

#### Debug Center (v2.3.0)
- `mt_run_diagnostic` - Run system diagnostics
- `mt_execute_debug_script` - Execute debug script
- `mt_run_maintenance` - Run maintenance operation
- `mt_delete_all_candidates` - Delete all candidates (v2.3.5)

---

## Database Schema

### Custom Tables

#### wp_mt_evaluations
```sql
CREATE TABLE wp_mt_evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jury_member_id INT NOT NULL,
    candidate_id INT NOT NULL,
    criterion_1 DECIMAL(3,1),
    criterion_2 DECIMAL(3,1),
    criterion_3 DECIMAL(3,1),
    criterion_4 DECIMAL(3,1),
    criterion_5 DECIMAL(3,1),
    total_score DECIMAL(4,1),
    comments TEXT,
    status VARCHAR(20) DEFAULT 'draft',
    submitted_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_jury_member (jury_member_id),
    KEY idx_candidate (candidate_id),
    KEY idx_status (status)
);
```

#### wp_mt_jury_assignments
```sql
CREATE TABLE wp_mt_jury_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jury_member_id INT NOT NULL,
    candidate_id INT NOT NULL,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT,
    UNIQUE KEY unique_assignment (jury_member_id, candidate_id),
    KEY idx_jury_member (jury_member_id),
    KEY idx_candidate (candidate_id)
);
```

#### wp_mt_audit_log (v2.2.5)
```sql
CREATE TABLE wp_mt_audit_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT,
    action VARCHAR(100),
    object_type VARCHAR(50),
    object_id BIGINT,
    details LONGTEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY idx_user (user_id),
    KEY idx_action (action),
    KEY idx_object (object_type, object_id),
    KEY idx_created (created_at)
);
```

### Meta Fields

#### Candidate Meta Fields
- `_mt_candidate_id` - Import ID
- `_mt_organization` - Organization name
- `_mt_position` - Position/role
- `_mt_category_type` - Category (Startup/Gov/Tech)
- `_mt_top_50_status` - Top 50 indicator
- `_mt_linkedin_url` - LinkedIn profile
- `_mt_website_url` - Website
- `_mt_article_url` - Article link
- `_mt_description_full` - Full description
- `_mt_evaluation_courage` - Mut & Pioniergeist
- `_mt_evaluation_innovation` - Innovationsgrad
- `_mt_evaluation_implementation` - Umsetzungsstärke
- `_mt_evaluation_relevance` - Relevanz & Impact
- `_mt_evaluation_visibility` - Sichtbarkeit & Reichweite

---

## Security Implementation

### Nonce Verification
```php
// All forms and AJAX calls use nonces
if (!wp_verify_nonce($_POST['nonce'], 'mt_admin_nonce')) {
    wp_die('Security check failed');
}
```

### Capability Checking
```php
// Custom capabilities for fine-grained control
$capabilities = [
    'mt_manage_evaluations',
    'mt_manage_assignments', 
    'mt_manage_settings',
    'mt_view_reports',
    'mt_export_data',
    'mt_view_audit_logs',
    'mt_submit_evaluation',
    'mt_view_own_evaluations'
];

// Check in AJAX handlers
if (!current_user_can('mt_manage_assignments')) {
    wp_die('Permission denied');
}
```

### Input Sanitization
```php
// Sanitize all user input
$name = sanitize_text_field($_POST['name']);
$email = sanitize_email($_POST['email']);
$url = esc_url_raw($_POST['website']);
$content = wp_kses_post($_POST['description']);
```

### SQL Injection Prevention
```php
// Always use prepared statements
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$table} WHERE id = %d AND status = %s",
        $id,
        $status
    )
);
```

### File Upload Security (v2.2.28)
```php
// Use base class validation
$validation = $this->validate_upload(
    $_FILES['csv_file'],
    ['csv', 'txt'],
    10 * MB_IN_BYTES
);

if ($validation !== true) {
    $this->error($validation);
    return;
}
```

### XSS Protection
```php
// Escape output
echo esc_html($text);
echo esc_attr($attribute);
echo esc_url($url);
echo wp_kses_post($html);
```

---

## Auto-Assignment System

### Overview
The auto-assignment system automatically distributes candidates to jury members for evaluation.

### Distribution Methods

#### Balanced Distribution
Ensures fair distribution:
- Each jury member receives exactly `candidates_per_jury` candidates
- Candidates with fewer assignments are prioritized
- Even review coverage across all candidates

```php
// Algorithm
1. Track assignment count for each candidate
2. Sort candidates by assignment count (ascending)
3. Assign least-reviewed candidates first
4. Continue until each jury member has quota
```

#### Random Distribution
Provides unpredictable distribution:
- Random selection for each jury member
- Single-shuffle algorithm for performance
- No bias in candidate selection

```php
// Algorithm
1. Shuffle candidate list once
2. Each jury member picks sequentially
3. Skip already-assigned if not clearing
4. Continue until quota met
```

### Usage

#### JavaScript
```javascript
// Auto-assign via AJAX
jQuery.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'mt_auto_assign',
        method: 'balanced', // or 'random'
        candidates_per_jury: 5,
        clear_existing: 'true',
        nonce: mt_admin.nonce
    },
    success: function(response) {
        if (response.success) {
            console.log('Created:', response.data.created);
        }
    }
});
```

#### PHP Direct
```php
$service = new MT_Assignment_Service();
$result = $service->auto_assign('balanced', 5, true);
```

### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `method` | string | 'balanced' | Distribution method |
| `candidates_per_jury` | int | 10 | Candidates per jury (1-50) |
| `clear_existing` | bool | false | Clear existing assignments |

### Edge Cases Handled
- Insufficient candidates
- Existing assignments
- No candidates/jury members
- Database constraints

---

## Frontend Assets Management

### CSS Architecture (v2.5.0)

The frontend uses a layered CSS architecture with specific load order:

#### Base Stylesheets
```css
1. frontend.css          - Core frontend styles
2. enhanced-candidate-profile.css - Enhanced profile features
3. candidate-profile-fixes.css    - Layout fixes (v2.4.2)
4. design-improvements-2025.css   - UI/UX improvements (v2.5.0)
```

#### CSS Load Order
```php
// In class-mt-plugin.php
wp_enqueue_style('mt-frontend', ...);
wp_enqueue_style('mt-enhanced-candidate-profile', ['mt-frontend'], ...);
wp_enqueue_style('mt-candidate-profile-fixes', ['mt-frontend', 'mt-enhanced-candidate-profile'], ...);
wp_enqueue_style('mt-design-improvements', ['mt-frontend', 'mt-enhanced-candidate-profile', 'mt-candidate-profile-fixes'], ...);
```

### JavaScript Enhancements (v2.5.0)

#### design-enhancements.js Features
```javascript
// Smooth scrolling
$('a[href^="#"]').on('click', smoothScroll);

// Scroll animations
animateOnScroll();  // Fade-in animations for cards

// Progress indicators
MTEvaluationProgress.init();  // Visual progress tracking

// Auto-save feedback
MTSaveIndicator.show();  // Visual save confirmation

// Accessibility enhancements
initKeyboardNavigation();
initSkipToContent();
initFocusManagement();
```

### Responsive Breakpoints
```css
@media (max-width: 968px)  /* Tablet landscape */
@media (max-width: 768px)  /* Tablet portrait */
@media (max-width: 640px)  /* Mobile */
```

### SVG Icon Implementation (v2.5.0)
Social media icons now use inline SVGs instead of dashicons:

```php
// LinkedIn SVG
<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
    <path d="M19 0h-14c-2.761..."/>
</svg>

// Website/Globe SVG
<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
    <path d="M12 0c-6.627..."/>
</svg>
```

### Performance Optimizations
- GPU-accelerated CSS animations
- IntersectionObserver for lazy loading
- Debounced scroll events
- Critical CSS inlining ready

---

## Dashboard System

### Dashboard Widget (v2.2.10)
Synchronized with main dashboard:

```php
// Widget uses same data sources
$evaluation_stats = MT_Evaluation_Repository::get_statistics();
$recent_evaluations = MT_Evaluation_Repository::find_all(['limit' => 5]);
```

### Widget Refresh (v2.2.28)
```javascript
// Refresh single widget
refreshDashboardWidget('mt-stats-widget', function(success) {
    if (success) {
        console.log('Widget refreshed');
    }
});

// Refresh multiple widgets
refreshDashboardWidgets(['mt-stats-widget', 'mt-recent-activity']);
```

### Coaching Dashboard (v2.2.29)
Complete jury evaluation management:
- Real-time progress tracking
- Recent activity feed
- Send reminder emails
- Export reports as CSV
- Average score tracking

---

## Audit Logging

### System Overview (v2.2.5)
Comprehensive audit trail for compliance:

```php
use MobilityTrailblazers\Core\MT_Audit_Logger;

// Log an action
MT_Audit_Logger::log(
    'evaluation_approved',
    'evaluation', 
    $evaluation_id,
    [
        'jury_member_id' => $evaluation->jury_member_id,
        'candidate_id' => $evaluation->candidate_id,
        'previous_status' => 'draft',
        'new_status' => 'approved',
        'score' => $evaluation->score
    ]
);
```

### Logged Actions

#### Evaluations
- `evaluation_submitted` - Finalized submission
- `evaluation_saved_draft` - Draft saves
- `evaluation_approved` - Admin approval
- `evaluation_rejected` - Admin rejection
- `evaluation_reset` - Status reset
- `evaluation_deleted` - Removal

#### Assignments
- `assignment_created` - New assignment
- `assignment_removed` - Deletion with context
- `bulk_assignments_created` - Bulk operations

#### Profiles
- `candidate_updated` - Profile changes
- `jury_member_updated` - Modifications

### Viewing Audit Logs
Access via **Mobility Trailblazers → Audit Logs**:
- Filter by user, action, object type, date
- Sort by any column
- View detailed JSON data
- Export for compliance

---

## User Roles & Capabilities

### Role Definitions

#### Administrator
Full platform access:
```php
$admin_caps = [
    'mt_manage_evaluations',
    'mt_manage_assignments',
    'mt_manage_settings',
    'mt_view_reports',
    'mt_export_data',
    'mt_view_audit_logs'
];
```

#### Jury Admin (v2.2.9)
Intermediate role:
```php
$jury_admin_caps = [
    'mt_view_all_evaluations',
    'mt_manage_assignments',
    'mt_view_reports',
    'mt_export_data'
];
```

#### Jury Member
Limited to evaluations:
```php
$jury_member_caps = [
    'mt_submit_evaluation',
    'mt_view_own_evaluations'
];
```

### Capability Checks
```php
// In AJAX handlers
$this->check_permission('mt_manage_assignments');

// Direct checks
if (current_user_can('mt_export_data')) {
    // Allow export
}
```

---

## Plugin Settings

### Data Management (v2.2.13)
Control data preservation on uninstall:

```php
// Check setting
if (get_option('mt_remove_data_on_uninstall', '0') === '1') {
    MT_Uninstaller::remove_all_data();
}
```

Setting location: **Mobility Trailblazers → Settings → Data Management**

### All Settings
- `mt_criteria_weights` - Evaluation weights
- `mt_dashboard_settings` - Dashboard options
- `mt_candidate_presentation` - Display settings
- `mt_default_language` - Default language
- `mt_enable_language_switcher` - Language switcher
- `mt_auto_detect_language` - Browser detection
- `mt_evaluations_per_page` - Pagination
- `mt_remove_data_on_uninstall` - Data deletion

---

## Testing & Debugging

### Debug Center (v2.3.0)
Access: **MT Award System → Developer Tools**

Features:
- 6 complete tabs (Diagnostics, Database, Scripts, Errors, Tools, Info)
- Environment-aware (Development/Staging/Production)
- System diagnostics with real-time monitoring
- Database optimization tools
- Script execution with audit logging
- Error monitoring and statistics

Key Classes:
- `MT_Debug_Manager` - Script management
- `MT_Diagnostic_Service` - System health (Singleton)
- `MT_Maintenance_Tools` - Maintenance operations
- `MT_Debug_Ajax` - AJAX handler

### Console Debugging
```javascript
// Check initialization
console.log(mt_admin);
console.log(MTAssignmentManager);

// Module detection
if ($('#mt-auto-assign-btn').length > 0) {
    console.log('Assignment page detected');
}
```

### Error Logging
```php
// Use MT_Logger
MT_Logger::log_error('context', 'Error message');
MT_Logger::log_info('context', 'Info message');
MT_Logger::log_debug('context', 'Debug data');
```

### Testing Checklist

#### Auto-Assignment
- [ ] Create test jury members and candidates
- [ ] Test balanced distribution
- [ ] Test random distribution  
- [ ] Test edge cases (empty lists, insufficient candidates)

#### Import System
- [ ] Test CSV with BOM (Excel export)
- [ ] Test without BOM
- [ ] Test different delimiters
- [ ] Test German characters
- [ ] Test large files (1000+ records)

#### AJAX Operations
- [ ] Test nonce verification
- [ ] Test permission checks
- [ ] Test error handling
- [ ] Test success responses

---

## Code Examples

### Creating a New AJAX Handler
```php
class MT_Custom_Ajax extends MT_Base_Ajax {
    protected function init() {
        add_action('wp_ajax_mt_custom_action', [$this, 'handle_action']);
    }
    
    public function handle_action() {
        // Security
        if (!$this->verify_nonce('mt_ajax_nonce')) {
            $this->error('Security check failed');
            return;
        }
        
        if (!$this->check_permission('required_capability')) {
            return;
        }
        
        // Process
        $data = $this->process();
        
        // Response
        $this->success($data, 'Operation completed');
    }
}
```

### Adding a New Service
```php
class MT_New_Service {
    private $repository;
    
    public function __construct() {
        $this->repository = new MT_New_Repository();
    }
    
    public function process($data) {
        // Validate
        if (!$this->validate($data)) {
            return ['success' => false];
        }
        
        // Business logic
        $result = $this->repository->create($data);
        
        // Audit log
        MT_Audit_Logger::log('action', 'type', $result['id']);
        
        return ['success' => true, 'data' => $result];
    }
}
```

### JavaScript Module Pattern
```javascript
const MTNewModule = {
    init: function() {
        this.bindEvents();
        console.log('MTNewModule initialized');
    },
    
    bindEvents: function() {
        $(document).on('click', '.button-class', this.handleClick.bind(this));
    },
    
    handleClick: function(e) {
        e.preventDefault();
        // Handle click
    }
};

// Conditional initialization
jQuery(document).ready(function($) {
    if ($('.page-identifier').length > 0) {
        MTNewModule.init();
    }
});
```

---

## Best Practices

### PHP Development
1. **Always extend base classes** for consistency
2. **Use repository pattern** for database operations
3. **Implement service layer** for business logic
4. **Add audit logging** for critical actions
5. **Sanitize all input** and escape all output
6. **Use prepared statements** for database queries
7. **Check capabilities** before operations
8. **Follow WordPress coding standards**

### JavaScript Development
1. **Use modular pattern** for organization
2. **Implement conditional loading** for performance
3. **Use event delegation** for dynamic content
4. **Standardize AJAX calls** with base pattern
5. **Add loading states** for better UX
6. **Handle errors gracefully** with user feedback
7. **Use localization** for all strings
8. **Debounce expensive operations**

### Security
1. **Verify nonces** on all forms/AJAX
2. **Check permissions** with custom capabilities
3. **Sanitize input** with WordPress functions
4. **Escape output** based on context
5. **Validate file uploads** comprehensively
6. **Log security events** for audit trail
7. **Use HTTPS** in production
8. **Keep WordPress updated**

### Performance
1. **Cache expensive queries** with transients
2. **Use batch operations** for bulk actions
3. **Implement pagination** for large datasets
4. **Optimize database indexes**
5. **Lazy load where appropriate**
6. **Minimize HTTP requests**
7. **Use CDN for assets**
8. **Monitor with Debug Center**

---

## Version History

### Key Versions

#### v2.4.1 (2025-08-16)
- Fixed jury grid display issues
- Added interactive card functionality
- Comprehensive responsive design

#### v2.4.0 (2025-01-16)
- Complete photo management system
- Enhanced UI templates
- Interactive JavaScript features

#### v2.3.0 (2025-08-14)
- Complete Debug Center implementation
- 6 functional tabs with diagnostics
- Environment-aware security

#### v2.2.28 (2025-08-14)
- Enhanced file upload validation
- Database integrity methods
- Widget refresh functions
- Event delegation improvements

#### v2.2.24 (2025-08-13)
- Complete CSV import system
- Real-time progress tracking
- Template system with UTF-8 BOM

#### v2.2.16 (2025-08-13)
- AJAX-based CSV import
- German text parsing
- Field mapping system

#### v2.2.13 (2025-08-12)
- Data management settings
- AJAX error standardization
- Uninstall options

#### v2.2.10 (2025-08-12)
- Dashboard widget synchronization
- Recent evaluations display

#### v2.2.5 (2025-08-12)
- Comprehensive audit logging
- Security compliance features

See `CHANGELOG.md` for complete version history.

---

*End of Master Developer Guide*
