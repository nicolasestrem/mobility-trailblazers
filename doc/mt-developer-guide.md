# Mobility Trailblazers - Developer Guide

**Version:** 2.0.0  
**Last Updated:** June 21, 2025

## Table of Contents
1. [Getting Started](#getting-started)
2. [Development Environment](#development-environment)
3. [Code Standards](#code-standards)
4. [Working with Custom Post Types](#working-with-custom-post-types)
5. [Repository Pattern Usage](#repository-pattern-usage)
6. [Service Layer Development](#service-layer-development)
7. [AJAX Implementation](#ajax-implementation)
8. [Creating Templates](#creating-templates)
9. [Adding Hooks & Filters](#adding-hooks--filters)
10. [Testing Guidelines](#testing-guidelines)
11. [Common Tasks](#common-tasks)

## Getting Started

### Prerequisites

- WordPress 5.8 or higher
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Node.js 14+ (for build tools)
- Composer (optional, for dependencies)

### Installation for Development

1. Clone the repository:
```bash
git clone [repository-url]
cd mobility-trailblazers
```

2. Set up local WordPress environment:
```bash
# Using Local by Flywheel, XAMPP, or Docker
# Configure wp-config.php with database credentials
```

3. Activate the plugin:
```bash
wp plugin activate mobility-trailblazers
```

## Development Environment

### Recommended Tools

1. **IDE/Editor**
   - PHPStorm (recommended)
   - VS Code with PHP extensions
   - Sublime Text with PHP packages

2. **Debugging**
   - Query Monitor plugin
   - Debug Bar plugin
   - Xdebug configuration

3. **Version Control**
   - Git with conventional commits
   - Feature branch workflow

### Local Development Setup

```php
// wp-config.php additions for development
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
define('SAVEQUERIES', true);
```

## Code Standards

### PHP Standards

Follow WordPress Coding Standards with these additions:

1. **Namespace Convention**
```php
namespace MobilityTrailblazers\Services;

use MobilityTrailblazers\Interfaces\MT_Service_Interface;
use MobilityTrailblazers\Repositories\MT_Evaluation_Repository;
```

2. **Class Naming**
```php
// File: includes/services/class-mt-evaluation-service.php
class MT_Evaluation_Service implements MT_Service_Interface {
    // Implementation
}
```

3. **Method Documentation**
```php
/**
 * Process evaluation submission.
 *
 * @since 2.0.0
 * @param array $data Evaluation data.
 * @return int|WP_Error Evaluation ID or error.
 */
public function process($data) {
    // Method implementation
}
```

### JavaScript Standards

1. **ES6+ Syntax**
```javascript
// Use const/let instead of var
const MTEvaluation = {
    init() {
        this.bindEvents();
    },
    
    bindEvents() {
        document.addEventListener('DOMContentLoaded', () => {
            this.setupForm();
        });
    }
};
```

2. **jQuery Usage**
```javascript
// Wrap jQuery code properly
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Your code here
    });
})(jQuery);
```

### CSS Standards

1. **BEM Methodology**
```css
/* Block */
.mt-evaluation-form {}

/* Element */
.mt-evaluation-form__field {}

/* Modifier */
.mt-evaluation-form--loading {}
```

2. **Responsive Design**
```css
/* Mobile-first approach */
.mt-card {
    padding: 1rem;
}

@media (min-width: 768px) {
    .mt-card {
        padding: 2rem;
    }
}
```

## Working with Custom Post Types

### Registering Post Types

Post types are registered in `includes/core/class-mt-post-types.php`:

```php
public function register_candidate_post_type() {
    $args = [
        'labels' => $this->get_candidate_labels(),
        'public' => true,
        'has_archive' => false,
        'show_in_rest' => true,
        'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
        'menu_icon' => 'dashicons-awards',
        'capability_type' => 'post',
        'map_meta_cap' => true,
    ];
    
    register_post_type('mt_candidate', $args);
}
```

### Adding Meta Boxes

```php
// In your admin class
public function add_candidate_meta_boxes() {
    add_meta_box(
        'mt_candidate_details',
        __('Candidate Details', 'mobility-trailblazers'),
        [$this, 'render_candidate_details_meta_box'],
        'mt_candidate',
        'normal',
        'high'
    );
}

public function render_candidate_details_meta_box($post) {
    wp_nonce_field('mt_candidate_details', 'mt_candidate_details_nonce');
    
    $innovation_summary = get_post_meta($post->ID, '_mt_innovation_summary', true);
    ?>
    <label for="mt_innovation_summary">
        <?php _e('Innovation Summary', 'mobility-trailblazers'); ?>
    </label>
    <textarea id="mt_innovation_summary" name="mt_innovation_summary" rows="5" style="width: 100%;">
        <?php echo esc_textarea($innovation_summary); ?>
    </textarea>
    <?php
}
```

## Repository Pattern Usage

### Creating a New Repository

1. **Define the Repository Class**
```php
namespace MobilityTrailblazers\Repositories;

use MobilityTrailblazers\Interfaces\MT_Repository_Interface;

class MT_Candidate_Repository implements MT_Repository_Interface {
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'posts';
    }
    
    public function find($id) {
        return get_post($id);
    }
    
    public function find_all($args = []) {
        $defaults = [
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ];
        
        $args = wp_parse_args($args, $defaults);
        return get_posts($args);
    }
    
    public function create($data) {
        return wp_insert_post($data);
    }
    
    public function update($id, $data) {
        $data['ID'] = $id;
        return wp_update_post($data);
    }
    
    public function delete($id) {
        return wp_delete_post($id, true);
    }
}
```

### Using Repositories

```php
// In a service class
class MT_Candidate_Service {
    private $repository;
    
    public function __construct() {
        $this->repository = new MT_Candidate_Repository();
    }
    
    public function get_candidates_by_category($category_id) {
        return $this->repository->find_all([
            'tax_query' => [
                [
                    'taxonomy' => 'mt_award_category',
                    'field' => 'term_id',
                    'terms' => $category_id,
                ],
            ],
        ]);
    }
}
```

## Service Layer Development

### Creating a New Service

1. **Define the Service Interface**
```php
namespace MobilityTrailblazers\Services;

use MobilityTrailblazers\Interfaces\MT_Service_Interface;

class MT_Notification_Service implements MT_Service_Interface {
    private $errors = [];
    
    public function process($data) {
        if (!$this->validate($data)) {
            return false;
        }
        
        return $this->send_notification($data);
    }
    
    public function validate($data) {
        if (empty($data['recipient'])) {
            $this->errors[] = __('Recipient is required', 'mobility-trailblazers');
            return false;
        }
        
        if (!is_email($data['recipient'])) {
            $this->errors[] = __('Invalid email address', 'mobility-trailblazers');
            return false;
        }
        
        return true;
    }
    
    public function get_errors() {
        return $this->errors;
    }
    
    private function send_notification($data) {
        $subject = sprintf(
            __('New Evaluation for %s', 'mobility-trailblazers'),
            $data['candidate_name']
        );
        
        $message = $this->build_message($data);
        
        return wp_mail($data['recipient'], $subject, $message);
    }
}
```

### Service Integration

```php
// In AJAX handler
public function handle_evaluation_submission() {
    $evaluation_service = new MT_Evaluation_Service();
    $notification_service = new MT_Notification_Service();
    
    $result = $evaluation_service->process($_POST);
    
    if (is_wp_error($result)) {
        return $this->error($result->get_error_message());
    }
    
    // Send notification
    $notification_service->process([
        'recipient' => get_option('admin_email'),
        'candidate_name' => get_the_title($_POST['candidate_id']),
        'jury_member' => wp_get_current_user()->display_name,
    ]);
    
    return $this->success(['evaluation_id' => $result]);
}
```

## AJAX Implementation

### Creating AJAX Handlers

1. **Backend Handler**
```php
namespace MobilityTrailblazers\Ajax;

class MT_Custom_Ajax extends MT_Base_Ajax {
    
    public function __construct() {
        parent::__construct();
        $this->register_ajax_handlers();
    }
    
    protected function register_ajax_handlers() {
        add_action('wp_ajax_mt_custom_action', [$this, 'handle_custom_action']);
        add_action('wp_ajax_nopriv_mt_custom_action', [$this, 'handle_custom_action']);
    }
    
    public function handle_custom_action() {
        // Verify nonce
        if (!$this->verify_nonce('mt_custom_nonce')) {
            return;
        }
        
        // Check permissions
        if (!$this->check_permission('read')) {
            return;
        }
        
        // Process data
        $data = $this->sanitize_data($_POST);
        
        // Perform action
        $result = $this->perform_action($data);
        
        if ($result) {
            $this->success($result, __('Action completed', 'mobility-trailblazers'));
        } else {
            $this->error(__('Action failed', 'mobility-trailblazers'));
        }
    }
}
```

2. **Frontend JavaScript**
```javascript
// AJAX request example
const MTAjax = {
    performAction(data) {
        const formData = new FormData();
        formData.append('action', 'mt_custom_action');
        formData.append('nonce', mt_ajax.nonce);
        
        Object.keys(data).forEach(key => {
            formData.append(key, data[key]);
        });
        
        return fetch(mt_ajax.ajax_url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                mtShowNotification('Evaluation saved successfully!', 'success');
            } else {
                mtShowNotification(response.data.message, 'error');
            }
        })
        .catch(error => {
            console.error('AJAX error:', error);
        });
    }
};
```

## Form Submission Debugging

### Common Issues and Solutions

The jury evaluation form submission process has been optimized to handle several common issues that can occur with dynamically generated forms.

#### 1. AJAX Response Data Structure

**Issue**: Candidate data not being accessed correctly from AJAX response.

**Problem**: The AJAX response structure has nested data:
```javascript
// Incorrect access
response.data.id  // undefined

// Correct access  
response.data.data.id  // actual candidate ID
```

**Solution**: Always check the response structure and access data correctly:
```javascript
.done(function(response) {
    if (response.success) {
        // Handle nested data structure
        var candidateData = response.data.data || response.data;
        console.log('Candidate data:', candidateData);
        self.displayEvaluationForm(candidateData);
    }
});
```

#### 2. Form Field Collection

**Issue**: Form fields not being included in submission, especially hidden inputs.

**Problem**: `serializeArray()` may not capture all fields in dynamically created forms.

**Solution**: Use manual field collection:
```javascript
// Get form data including all fields
var formData = {};

// Add all form fields manually
$form.find('input, textarea, select').each(function() {
    var $field = $(this);
    var name = $field.attr('name');
    var value = $field.val();
    
    if (name && value !== undefined) {
        formData[name] = value;
    }
});

// Add required AJAX fields
formData.action = 'mt_submit_evaluation';
formData.nonce = mt_ajax.nonce;
formData.status = 'completed';
```

#### 3. Form Selection Issues

**Issue**: Form not being found after dynamic creation.

**Problem**: jQuery selectors may not find dynamically inserted forms.

**Solution**: Use multiple fallback selectors:
```javascript
// Try multiple selectors to find the form
var $targetForm = $('#mt-evaluation-form');
if ($targetForm.length === 0) {
    $targetForm = $('.mt-evaluation-form');
}
if ($targetForm.length === 0) {
    $targetForm = $form; // fallback to original reference
}

console.log('Target form found:', $targetForm.length);
```

#### 4. Debugging Form Submission

**Add comprehensive logging**:
```javascript
// Debug form selection
console.log('MT JS - Form element:', $form);
console.log('MT JS - Form ID:', $form.attr('id'));
console.log('MT JS - Form class:', $form.attr('class'));

// Debug field collection
var allFields = $targetForm.find('input, textarea, select');
console.log('MT JS - Found form fields:', allFields.length);
allFields.each(function(index) {
    var $field = $(this);
    var name = $field.attr('name');
    var value = $field.val();
    console.log('MT JS - Field ' + index + ':', name, '=', value);
});

// Debug final form data
console.log('MT JS - Form data being sent:', formData);
console.log('MT JS - Candidate ID in form data:', formData.candidate_id);
```

**Server-side debugging**:
```php
// In AJAX handler
public function submit_evaluation() {
    // Debug: Log raw POST data
    error_log('MT AJAX - Raw POST data: ' . print_r($_POST, true));
    
    // Debug: Check candidate_id specifically
    $raw_candidate_id = $this->get_param('candidate_id');
    error_log('MT AJAX - Raw candidate_id from POST: ' . var_export($raw_candidate_id, true));
    $candidate_id = $this->get_int_param('candidate_id');
    error_log('MT AJAX - Processed candidate_id: ' . $candidate_id);
}
```

#### 5. Permission Error Troubleshooting

**Common causes of "You do not have permission to evaluate this candidate"**:

1. **Missing candidate_id**: Form not sending candidate_id field
2. **Wrong candidate_id**: Form sending wrong or null candidate_id
3. **Assignment mismatch**: Jury member not assigned to candidate
4. **User role issues**: User doesn't have required capabilities

**Debugging steps**:
```php
// Check jury member lookup
$current_user_id = get_current_user_id();
$jury_member = $this->get_jury_member_by_user_id($current_user_id);
error_log('MT AJAX - Found jury member: ' . $jury_member->ID . ' for user: ' . $current_user_id);

// Check assignment
$assignment_repo = new \MobilityTrailblazers\Repositories\MT_Assignment_Repository();
$has_assignment = $assignment_repo->exists($jury_member->ID, $candidate_id);
error_log('MT AJAX - Assignment check: jury_member_id=' . $jury_member->ID . ', candidate_id=' . $candidate_id . ', has_assignment=' . ($has_assignment ? 'true' : 'false'));

// List all assignments for debugging
$all_assignments = $assignment_repo->get_by_jury_member($jury_member->ID);
foreach ($all_assignments as $assignment) {
    error_log('MT AJAX - Assignment: jury_member_id=' . $assignment->jury_member_id . ', candidate_id=' . $assignment->candidate_id);
}
```

### Best Practices

1. **Always validate form data on both client and server side**
2. **Use comprehensive logging for debugging dynamic forms**
3. **Implement fallback selectors for form elements**
4. **Test form submission with different user roles and scenarios**
5. **Monitor WordPress debug log for server-side issues**
6. **Use browser developer tools to inspect form data being sent**

## JavaScript Assets

### File Structure

The plugin includes two main JavaScript files:

```
assets/js/
├── frontend.js    # Frontend functionality (jury dashboard, forms)
└── admin.js       # Admin interface functionality
```

### Frontend JavaScript (`frontend.js`)

**Purpose**: Handles frontend user interactions, primarily for jury members.

**Key Features**:
- Evaluation form handling with real-time validation
- AJAX form submissions with loading states
- Score calculation and display
- Character counting for comment fields
- Mobile-responsive interactions

**Usage Example**:
```javascript
// Evaluation form submission
$('#mt-evaluation-form').on('submit', function(e) {
    e.preventDefault();
    
    const $form = $(this);
    const $submit = $form.find('[type="submit"]');
    
    // Show loading state
    $submit.prop('disabled', true).text('Submitting...');
    
    // Submit via AJAX
    $.ajax({
        url: mt_ajax.url,
        type: 'POST',
        data: new FormData(this),
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                mtShowNotification('Evaluation saved successfully!', 'success');
            } else {
                mtShowNotification(response.data.message, 'error');
            }
        },
        complete: function() {
            $submit.prop('disabled', false).text('Submit Evaluation');
        }
    });
});
```

### Admin JavaScript (`admin.js`)

**Purpose**: Handles admin interface functionality for managing assignments, evaluations, and settings.

**Key Features**:
- Tooltip initialization and positioning
- Tab navigation with localStorage persistence
- Modal opening/closing functionality
- Confirmation dialogs for destructive actions
- AJAX form handling with error management
- Utility functions for notifications and data handling

**Usage Example**:
```javascript
// Manual assignment form
$('.mt-assignment-form').on('submit', function(e) {
    e.preventDefault();
    
    const $form = $(this);
    const formData = new FormData(this);
    formData.append('action', 'mt_manual_assignment');
    formData.append('nonce', mt_admin.nonce);
    
    $.ajax({
        url: mt_admin.url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                mtShowNotification('Assignment created successfully!', 'success');
                // Refresh the assignments list
                location.reload();
            } else {
                mtShowNotification(response.data.message, 'error');
            }
        },
        error: function() {
            mtShowNotification(mt_admin.strings.error, 'error');
        }
    });
});
```

### Localization

Both JavaScript files receive localized data from PHP:

**Frontend Localization**:
```php
wp_localize_script('mt-frontend', 'mt_ajax', [
    'url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('mt_ajax_nonce')
]);
```

**Admin Localization**:
```php
wp_localize_script('mt-admin', 'mt_admin', [
    'url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('mt_admin_nonce'),
    'strings' => [
        'confirm_delete' => __('Are you sure you want to delete this?', 'mobility-trailblazers'),
        'saving' => __('Saving...', 'mobility-trailblazers'),
        'saved' => __('Saved!', 'mobility-trailblazers'),
        'error' => __('An error occurred. Please try again.', 'mobility-trailblazers')
    ]
]);
```

### Utility Functions

The admin.js file provides several utility functions:

```javascript
// Show notifications
mtShowNotification('Operation completed!', 'success');

// Handle AJAX errors
mtHandleAjaxError(xhr, textStatus, errorThrown);

// Serialize form data
const data = mtSerializeForm($('#my-form'));

// Update URL parameters
mtUpdateUrlParam('page', '2');

// Get URL parameters
const page = mtGetUrlParam('page');

// Format numbers
const formatted = mtFormatNumber(1234.56); // "1.234,56"

// Debounce function calls
const debouncedSearch = mtDebounce(function(query) {
    // Perform search
}, 300);
```

### Development Guidelines

1. **jQuery Usage**: Wrap all jQuery code in IIFE:
```javascript
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Your code here
    });
})(jQuery);
```

2. **Error Handling**: Always include error handling for AJAX requests
3. **Loading States**: Show loading indicators during AJAX operations
4. **Accessibility**: Ensure keyboard navigation and screen reader support
5. **Mobile First**: Test on mobile devices and ensure responsive behavior

## Creating Templates

### Template Structure

1. **Frontend Template Example**
```php
<?php
/**
 * Template: Evaluation Form
 * 
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get current user
$current_user = wp_get_current_user();
$jury_member_id = MT_Jury_Member::get_by_user_id($current_user->ID);

if (!$jury_member_id) {
    echo '<p>' . esc_html__('You must be a jury member to access this form.', 'mobility-trailblazers') . '</p>';
    return;
}
?>

<div class="mt-evaluation-form-wrapper">
    <form id="mt-evaluation-form" class="mt-evaluation-form">
        <?php wp_nonce_field('mt_evaluation_nonce', 'mt_nonce'); ?>
        
        <input type="hidden" name="jury_member_id" value="<?php echo esc_attr($jury_member_id); ?>">
        
        <div class="mt-form-group">
            <label for="candidate_id">
                <?php esc_html_e('Select Candidate', 'mobility-trailblazers'); ?>
            </label>
            <select name="candidate_id" id="candidate_id" required>
                <option value=""><?php esc_html_e('Choose...', 'mobility-trailblazers'); ?></option>
                <?php foreach ($candidates as $candidate) : ?>
                    <option value="<?php echo esc_attr($candidate->ID); ?>">
                        <?php echo esc_html($candidate->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Evaluation criteria fields -->
        <?php mt_render_evaluation_criteria_fields(); ?>
        
        <button type="submit" class="mt-button mt-button--primary">
            <?php esc_html_e('Submit Evaluation', 'mobility-trailblazers'); ?>
        </button>
    </form>
</div>
```

### Template Functions

```php
/**
 * Render evaluation criteria fields.
 *
 * @since 2.0.0
 */
function mt_render_evaluation_criteria_fields() {
    $criteria = mt_get_evaluation_criteria();
    
    foreach ($criteria as $key => $label) {
        ?>
        <div class="mt-form-group">
            <label for="<?php echo esc_attr($key); ?>_score">
                <?php echo esc_html($label); ?>
            </label>
            <input 
                type="range" 
                name="<?php echo esc_attr($key); ?>_score" 
                id="<?php echo esc_attr($key); ?>_score"
                min="0" 
                max="10" 
                step="1" 
                value="5"
                class="mt-range-slider"
            >
            <span class="mt-range-value">5</span>
        </div>
        <?php
    }
}
```

## Adding Hooks & Filters

### Action Hooks

```php
// Define custom actions
do_action('mt_before_evaluation_save', $evaluation_data, $jury_member_id);
do_action('mt_after_evaluation_save', $evaluation_id, $evaluation_data);

// Hook into actions
add_action('mt_after_evaluation_save', function($evaluation_id, $data) {
    // Log evaluation
    error_log(sprintf(
        'Evaluation %d saved by jury member %d for candidate %d',
        $evaluation_id,
        $data['jury_member_id'],
        $data['candidate_id']
    ));
}, 10, 2);
```

### Filter Hooks

```php
// Define filters
$criteria = apply_filters('mt_evaluation_criteria', [
    'courage' => __('Mut & Pioniergeist', 'mobility-trailblazers'),
    'innovation' => __('Innovationsgrad', 'mobility-trailblazers'),
    'implementation' => __('Umsetzungskraft & Wirkung', 'mobility-trailblazers'),
    'relevance' => __('Relevanz für Mobilitätswende', 'mobility-trailblazers'),
    'visibility' => __('Vorbildfunktion & Sichtbarkeit', 'mobility-trailblazers'),
]);

// Use filters
add_filter('mt_evaluation_criteria', function($criteria) {
    // Add custom criterion
    $criteria['sustainability'] = __('Sustainability Impact', 'mobility-trailblazers');
    return $criteria;
});
```

## Testing Guidelines

### Unit Testing

```php
// tests/test-evaluation-service.php
class Test_MT_Evaluation_Service extends WP_UnitTestCase {
    
    private $service;
    
    public function setUp() {
        parent::setUp();
        $this->service = new MT_Evaluation_Service();
    }
    
    public function test_validate_evaluation_data() {
        $valid_data = [
            'jury_member_id' => 1,
            'candidate_id' => 2,
            'courage_score' => 8,
            'innovation_score' => 7,
            'implementation_score' => 9,
            'relevance_score' => 8,
            'visibility_score' => 7,
        ];
        
        $this->assertTrue($this->service->validate($valid_data));
    }
    
    public function test_invalid_score_range() {
        $invalid_data = [
            'jury_member_id' => 1,
            'candidate_id' => 2,
            'courage_score' => 15, // Invalid: > 10
        ];
        
        $this->assertFalse($this->service->validate($invalid_data));
        $errors = $this->service->get_errors();
        $this->assertContains('Invalid score range', $errors[0]);
    }
}
```

### Integration Testing

```php
// Test AJAX endpoints
public function test_evaluation_submission_ajax() {
    // Set up user
    $user_id = $this->factory->user->create(['role' => 'mt_jury_member']);
    wp_set_current_user($user_id);
    
    // Prepare request
    $_POST = [
        'action' => 'mt_submit_evaluation',
        'nonce' => wp_create_nonce('mt_evaluation_nonce'),
        'candidate_id' => $this->factory->post->create(['post_type' => 'mt_candidate']),
        'courage_score' => 8,
        // ... other scores
    ];
    
    // Execute AJAX handler
    $handler = new MT_Evaluation_Ajax();
    $handler->handle_evaluation_submission();
    
    // Check response
    $this->expectOutputRegex('/"success":true/');
}
```

## Common Tasks

### Adding a New Evaluation Criterion

1. **Update the database schema** (if needed):
```php
// In activation class
$sql = "ALTER TABLE {$wpdb->prefix}mt_evaluations 
        ADD COLUMN new_criterion_score TINYINT(2) DEFAULT 0";
$wpdb->query($sql);
```

2. **Update the repository**:
```php
// In MT_Evaluation_Repository
public function create($data) {
    // Add new field to insert data
    $insert_data['new_criterion_score'] = intval($data['new_criterion_score']);
}
```

3. **Update the service validation**:
```php
// In MT_Evaluation_Service
public function validate($data) {
    // Add validation for new criterion
    if (!isset($data['new_criterion_score']) || 
        $data['new_criterion_score'] < 0 || 
        $data['new_criterion_score'] > 10) {
        $this->errors[] = __('Invalid new criterion score', 'mobility-trailblazers');
        return false;
    }
}
```

4. **Update the frontend form**:
```php
// Add to evaluation criteria filter
add_filter('mt_evaluation_criteria', function($criteria) {
    $criteria['new_criterion'] = __('New Criterion Name', 'mobility-trailblazers');
    return $criteria;
});
```

### Creating a Custom Report

```php
// Create a new service for reports
class MT_Report_Service {
    private $evaluation_repo;
    
    public function __construct() {
        $this->evaluation_repo = new MT_Evaluation_Repository();
    }
    
    public function generate_candidate_report($candidate_id) {
        $evaluations = $this->evaluation_repo->find_all([
            'candidate_id' => $candidate_id,
            'status' => 'completed',
        ]);
        
        if (empty($evaluations)) {
            return null;
        }
        
        // Calculate averages
        $totals = array_fill_keys(['courage', 'innovation', 'implementation', 'relevance', 'visibility'], 0);
        
        foreach ($evaluations as $evaluation) {
            foreach ($totals as $key => &$total) {
                $total += $evaluation->{$key . '_score'};
            }
        }
        
        $count = count($evaluations);
        $averages = array_map(function($total) use ($count) {
            return round($total / $count, 2);
        }, $totals);
        
        return [
            'candidate_id' => $candidate_id,
            'evaluation_count' => $count,
            'averages' => $averages,
            'total_average' => round(array_sum($averages) / count($averages), 2),
        ];
    }
}
```

### Implementing Caching

```php
// Cache expensive operations
class MT_Cache_Helper {
    
    public static function get_cached_data($key, $callback, $expiration = HOUR_IN_SECONDS) {
        $cached = get_transient($key);
        
        if (false !== $cached) {
            return $cached;
        }
        
        $data = call_user_func($callback);
        set_transient($key, $data, $expiration);
        
        return $data;
    }
    
    public static function clear_cache($key) {
        delete_transient($key);
    }
}

// Usage
$statistics = MT_Cache_Helper::get_cached_data(
    'mt_evaluation_statistics',
    function() {
        $service = new MT_Statistics_Service();
        return $service->calculate_all_statistics();
    },
    HOUR_IN_SECONDS
);
```

## Debugging Tips

### Enable Debug Logging

```php
// Custom debug function
function mt_debug_log($message, $data = null) {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    $log_entry = sprintf(
        "[%s] Mobility Trailblazers: %s",
        date('Y-m-d H:i:s'),
        $message
    );
    
    if ($data !== null) {
        $log_entry .= "\n" . print_r($data, true);
    }
    
    error_log($log_entry);
}

// Usage
mt_debug_log('Evaluation submitted', [
    'jury_member' => $jury_member_id,
    'candidate' => $candidate_id,
    'scores' => $scores,
]);
```

### Database Query Debugging

```php
// Log slow queries
add_filter('query', function($query) {
    if (strpos($query, 'mt_evaluations') !== false) {
        mt_debug_log('Evaluation query', $query);
    }
    return $query;
});
```

## Jury Rankings System

### Overview

The Jury Rankings System provides dynamic, personalized rankings for jury members. It displays candidates in order of their evaluation scores with visual indicators and detailed score breakdowns.

### Architecture

#### Repository Methods

```php
// Get personalized rankings for a jury member
$evaluation_repo = new MT_Evaluation_Repository();
$rankings = $evaluation_repo->get_ranked_candidates_for_jury($jury_member_id, $limit);

// Get overall rankings across all juries
$overall_rankings = $evaluation_repo->get_overall_rankings($limit);
```

#### AJAX Endpoint

```php
// AJAX action for dynamic updates
add_action('wp_ajax_mt_get_jury_rankings', [$this, 'get_jury_rankings']);

// Handler method
public function get_jury_rankings() {
    // Security checks
    if (!check_ajax_referer('mt_ajax_nonce', 'nonce', false)) {
        wp_send_json_error(__('Security check failed', 'mobility-trailblazers'));
    }
    
    if (!current_user_can('mt_submit_evaluations')) {
        wp_send_json_error(__('Permission denied', 'mobility-trailblazers'));
    }
    
    // Get rankings
    $evaluation_repo = new MT_Evaluation_Repository();
    $rankings = $evaluation_repo->get_ranked_candidates_for_jury($jury_member_id, $limit);
    
    wp_send_json_success([
        'rankings' => $rankings,
        'html' => $this->render_rankings_html($rankings)
    ]);
}
```

### Customizing Rankings Display

#### Adding Custom Fields

```php
// Extend the repository query to include custom fields
public function get_ranked_candidates_for_jury($jury_member_id, $limit = 10) {
    global $wpdb;
    
    $query = "SELECT 
                c.ID as candidate_id,
                c.post_title as candidate_name,
                e.total_score,
                // ... existing fields ...
                pm3.meta_value as custom_field
              FROM {$wpdb->posts} c
              INNER JOIN {$this->table_name} e ON c.ID = e.candidate_id
              LEFT JOIN {$wpdb->postmeta} pm1 ON c.ID = pm1.post_id AND pm1.meta_key = '_mt_organization'
              LEFT JOIN {$wpdb->postmeta} pm2 ON c.ID = pm2.post_id AND pm2.meta_key = '_mt_position'
              LEFT JOIN {$wpdb->postmeta} pm3 ON c.ID = pm3.post_id AND pm3.meta_key = '_mt_custom_field'
              WHERE e.jury_member_id = %d
                AND c.post_type = 'mt_candidate'
                AND c.post_status = 'publish'
                AND e.status = 'completed'
              ORDER BY e.total_score DESC
              LIMIT %d";
    
    return $wpdb->get_results($wpdb->prepare($query, $jury_member_id, $limit));
}
```

#### Custom Template Rendering

```php
// Create a custom template for different ranking styles
public function render_custom_rankings_html($rankings, $style = 'default') {
    ob_start();
    
    switch ($style) {
        case 'compact':
            include MT_PLUGIN_DIR . 'templates/frontend/partials/rankings-compact.php';
            break;
        case 'detailed':
            include MT_PLUGIN_DIR . 'templates/frontend/partials/rankings-detailed.php';
            break;
        default:
            include MT_PLUGIN_DIR . 'templates/frontend/partials/jury-rankings.php';
    }
    
    return ob_get_clean();
}
```

#### Custom CSS Classes

```css
/* Custom ranking styles */
.mt-ranking-item.custom-style {
    background: linear-gradient(135deg, #custom-color1 0%, #custom-color2 100%);
    border: 2px solid #custom-border;
}

.mt-ranking-item.custom-style .mt-position-number {
    font-size: 28px;
    color: #custom-text;
}

.mt-ranking-item.custom-style .mt-medal-icon {
    background-image: url('path/to/custom-medal.svg');
}
```

### Extending Rankings Functionality

#### Custom Ranking Algorithms

```php
// Create a custom ranking service
class MT_Custom_Ranking_Service {
    private $evaluation_repo;
    
    public function __construct() {
        $this->evaluation_repo = new MT_Evaluation_Repository();
    }
    
    public function get_weighted_rankings($jury_member_id, $weights = []) {
        $evaluations = $this->evaluation_repo->find_all([
            'jury_member_id' => $jury_member_id,
            'status' => 'completed'
        ]);
        
        // Apply custom weighting
        foreach ($evaluations as $evaluation) {
            $evaluation->weighted_score = $this->calculate_weighted_score($evaluation, $weights);
        }
        
        // Sort by weighted score
        usort($evaluations, function($a, $b) {
            return $b->weighted_score <=> $a->weighted_score;
        });
        
        return $evaluations;
    }
    
    private function calculate_weighted_score($evaluation, $weights) {
        $default_weights = [
            'courage' => 1,
            'innovation' => 1,
            'implementation' => 1,
            'relevance' => 1,
            'visibility' => 1
        ];
        
        $weights = array_merge($default_weights, $weights);
        
        $weighted_sum = 0;
        $weight_total = 0;
        
        foreach ($weights as $criterion => $weight) {
            $score_field = $criterion . '_score';
            if (isset($evaluation->$score_field)) {
                $weighted_sum += $evaluation->$score_field * $weight;
                $weight_total += $weight;
            }
        }
        
        return $weight_total > 0 ? $weighted_sum / $weight_total : 0;
    }
}
```

#### Custom AJAX Handlers

```php
// Add custom ranking endpoints
add_action('wp_ajax_mt_get_custom_rankings', function() {
    if (!check_ajax_referer('mt_ajax_nonce', 'nonce', false)) {
        wp_send_json_error(__('Security check failed', 'mobility-trailblazers'));
    }
    
    $jury_member_id = get_current_user_id();
    $weights = isset($_POST['weights']) ? $_POST['weights'] : [];
    
    $ranking_service = new MT_Custom_Ranking_Service();
    $rankings = $ranking_service->get_weighted_rankings($jury_member_id, $weights);
    
    wp_send_json_success([
        'rankings' => $rankings,
        'html' => render_custom_rankings_html($rankings)
    ]);
});
```

#### JavaScript Extensions

```javascript
// Custom ranking update function
function updateCustomRankings(weights = {}) {
    jQuery.ajax({
        url: mt_ajax.url,
        type: 'POST',
        data: {
            action: 'mt_get_custom_rankings',
            nonce: mt_ajax.nonce,
            weights: weights
        },
        success: function(response) {
            if (response.success && response.data.html) {
                $('#mt-rankings-container').html(response.data.html);
                
                // Custom animations
                $('.mt-ranking-item').each(function(index) {
                    $(this).css('opacity', '0')
                           .delay(index * 100)
                           .animate({opacity: 1}, 500);
                });
            }
        }
    });
}

// Trigger custom rankings update
$(document).on('mt:custom_rankings:update', function(e, weights) {
    updateCustomRankings(weights);
});
```

### Performance Optimization

#### Caching Rankings

```php
// Cache rankings for better performance
class MT_Rankings_Cache {
    private static $cache_group = 'mt_rankings';
    
    public static function get_rankings($jury_member_id, $limit = 10) {
        $cache_key = "rankings_{$jury_member_id}_{$limit}";
        
        $rankings = wp_cache_get($cache_key, self::$cache_group);
        
        if (false === $rankings) {
            $evaluation_repo = new MT_Evaluation_Repository();
            $rankings = $evaluation_repo->get_ranked_candidates_for_jury($jury_member_id, $limit);
            
            wp_cache_set($cache_key, $rankings, self::$cache_group, HOUR_IN_SECONDS);
        }
        
        return $rankings;
    }
    
    public static function clear_rankings_cache($jury_member_id = null) {
        if ($jury_member_id) {
            wp_cache_delete("rankings_{$jury_member_id}_*", self::$cache_group);
        } else {
            wp_cache_flush_group(self::$cache_group);
        }
    }
}

// Usage
$rankings = MT_Rankings_Cache::get_rankings($jury_member_id, 10);

// Clear cache when evaluation is submitted
add_action('mt:evaluation:submitted', function($evaluation_id) {
    $evaluation = (new MT_Evaluation_Repository())->find($evaluation_id);
    if ($evaluation) {
        MT_Rankings_Cache::clear_rankings_cache($evaluation->jury_member_id);
    }
});
```

#### Database Indexing

```sql
-- Add indexes for better query performance
ALTER TABLE wp_mt_evaluations 
ADD INDEX idx_jury_candidate_status (jury_member_id, candidate_id, status),
ADD INDEX idx_total_score (total_score DESC),
ADD INDEX idx_status_created (status, created_at DESC);
```

### Testing Rankings

```php
// Unit test for rankings functionality
class Test_MT_Rankings extends WP_UnitTestCase {
    
    public function test_get_ranked_candidates_for_jury() {
        // Create test data
        $jury_member_id = $this->factory->post->create(['post_type' => 'mt_jury_member']);
        $candidate_id = $this->factory->post->create(['post_type' => 'mt_candidate']);
        
        // Create evaluation
        $evaluation_repo = new MT_Evaluation_Repository();
        $evaluation_id = $evaluation_repo->create([
            'jury_member_id' => $jury_member_id,
            'candidate_id' => $candidate_id,
            'total_score' => 8.5,
            'status' => 'completed'
        ]);
        
        // Test rankings
        $rankings = $evaluation_repo->get_ranked_candidates_for_jury($jury_member_id, 10);
        
        $this->assertNotEmpty($rankings);
        $this->assertEquals($candidate_id, $rankings[0]->candidate_id);
        $this->assertEquals(8.5, $rankings[0]->total_score);
    }
    
    public function test_rankings_ajax_endpoint() {
        // Set up user
        $user_id = $this->factory->user->create(['role' => 'mt_jury_member']);
        wp_set_current_user($user_id);
        
        // Mock AJAX request
        $_POST = [
            'action' => 'mt_get_jury_rankings',
            'nonce' => wp_create_nonce('mt_ajax_nonce'),
            'limit' => 5
        ];
        
        // Test AJAX response
        $ajax_handler = new MT_Evaluation_Ajax();
        $ajax_handler->get_jury_rankings();
        
        $this->expectOutputRegex('/"success":true/');
    }
}
```

This developer guide provides comprehensive information for working with the Mobility Trailblazers plugin. Follow these guidelines to maintain code quality and consistency across the project. 