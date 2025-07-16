# Mobility Trailblazers Developer Guide

## Overview

This guide provides comprehensive information for developers working on the Mobility Trailblazers platform, including architecture, coding standards, and best practices.

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Coding Standards](#coding-standards)
3. [Internationalization (i18n)](#internationalization-i18n)
4. [Database Schema](#database-schema)
5. [AJAX Implementation](#ajax-implementation)
6. [Frontend Development](#frontend-development)
7. [Testing Guidelines](#testing-guidelines)

## Architecture Overview

### Plugin Structure
```
mobility-trailblazers/
├── includes/
│   ├── core/           # Core plugin functionality
│   ├── admin/          # Admin interface
│   ├── ajax/           # AJAX handlers
│   ├── repositories/   # Data access layer
│   └── services/       # Business logic
├── templates/
│   ├── admin/          # Admin templates
│   └── frontend/       # Frontend templates
├── assets/
│   ├── css/            # Stylesheets
│   └── js/             # JavaScript files
└── languages/          # Translation files
```

### Core Classes
- `MT_Plugin` - Main plugin class
- `MT_Admin` - Admin interface management
- `MT_Evaluation_Service` - Evaluation business logic
- `MT_Assignment_Service` - Assignment business logic

## Coding Standards

### PHP Standards
- Follow WordPress Coding Standards
- Use PSR-4 autoloading
- Namespace: `MobilityTrailblazers\`
- Class naming: `MT_ClassName`
- Method naming: `snake_case`

### JavaScript Standards
- Use jQuery for DOM manipulation
- Follow WordPress JavaScript standards
- Use ES6+ features where supported
- Implement proper error handling

## Internationalization (i18n)

### Overview

The Mobility Trailblazers platform has a comprehensive i18n foundation implemented in version 2.0.13. All user-facing strings are properly localized using WordPress i18n functions and JavaScript localization.

### Text Domain

**Always use**: `mobility-trailblazers`

### PHP i18n Patterns

#### 1. Echo Statements
```php
// ✅ Correct
echo '<div class="notice">' . esc_html__('Settings saved successfully!', 'mobility-trailblazers') . '</div>';

// ❌ Incorrect
echo '<div class="notice">Settings saved successfully!</div>';
```

#### 2. Variable Assignments
```php
// ✅ Correct
$message = __('An error occurred. Please try again.', 'mobility-trailblazers');

// ❌ Incorrect
$message = 'An error occurred. Please try again.';
```

#### 3. HTML Attributes
```php
// ✅ Correct
<input type="text" placeholder="<?php esc_attr_e('Search candidates...', 'mobility-trailblazers'); ?>">

// ❌ Incorrect
<input type="text" placeholder="Search candidates...">
```

#### 4. Complex Strings with Variables
```php
// ✅ Correct
$message = sprintf(
    __('Welcome, %s', 'mobility-trailblazers'),
    esc_html($user_name)
);

// ❌ Incorrect
$message = 'Welcome, ' . $user_name;
```

### JavaScript i18n Patterns

#### 1. Frontend Strings (`mt_ajax.i18n`)
```javascript
// ✅ Correct
alert(mt_ajax.i18n.error || 'An error occurred. Please try again.');

// ❌ Incorrect
alert('An error occurred. Please try again.');
```

#### 2. Admin Strings (`mt_admin.i18n`)
```javascript
// ✅ Correct
$btn.text(mt_admin.i18n.processing || 'Processing...');

// ❌ Incorrect
$btn.text('Processing...');
```

#### 3. HTML Generation
```javascript
// ✅ Correct
var html = '<div>' + (mt_ajax.i18n.loading || 'Loading...') + '</div>';

// ❌ Incorrect
var html = '<div>Loading...</div>';
```

### Adding New Strings

#### 1. PHP Strings
1. Add the string to the appropriate `wp_localize_script` call in `includes/core/class-mt-plugin.php`
2. Use the string in your code with proper i18n function
3. Update this documentation with the new string

```php
// In class-mt-plugin.php
'i18n' => [
    'new_string' => __('New string text', 'mobility-trailblazers'),
    // ... existing strings
]

// In your code
echo esc_html__('New string text', 'mobility-trailblazers');
```

#### 2. JavaScript Strings
1. Add the string to the appropriate i18n array in `includes/core/class-mt-plugin.php`
2. Use the string in JavaScript with fallback
3. Update this documentation

```php
// In class-mt-plugin.php
'i18n' => [
    'new_js_string' => __('New JavaScript string', 'mobility-trailblazers'),
    // ... existing strings
]

// In JavaScript
$element.text(mt_ajax.i18n.new_js_string || 'New JavaScript string');
```

### String Categories

#### Frontend Strings (50+)
- **Loading States**: `loading`, `saving`, `submitting`
- **Success Messages**: `success`, `saved`, `draft_saved`
- **Error Messages**: `error`, `security_error`, `network_error`
- **Form Labels**: `evaluation_criteria`, `additional_comments`
- **Status Messages**: `not_started`, `completed`, `pending`
- **Navigation**: `back_to_dashboard`, `start_evaluation`
- **Validation**: `please_rate_all`, `invalid_candidate`
- **Network**: `request_timeout`, `permission_denied`

#### Admin Strings (20+)
- **Processing States**: `processing`, `clearing`, `saving`
- **Confirmation Dialogs**: `confirm_delete`, `confirm_clear_all`
- **Success Messages**: `assignments_created`, `export_started`
- **Error Messages**: `error_occurred`, `error`
- **Action Buttons**: `assign_selected`, `run_auto_assignment`
- **Validation**: `select_bulk_action`, `select_assignments`

### Best Practices

#### 1. Always Use i18n Functions
- Never hardcode user-facing strings
- Always provide fallback text in JavaScript
- Use appropriate escaping functions

#### 2. Consistent Naming
- Use descriptive, lowercase keys with underscores
- Group related strings with common prefixes
- Follow existing naming patterns

#### 3. Context Matters
- Use `esc_html__()` for safe output
- Use `esc_attr__()` for HTML attributes
- Use `__()` for variable assignments

#### 4. Testing
- Test with different locales
- Verify fallback text works
- Check for missing translations

### Common Mistakes to Avoid

#### 1. Hardcoded Strings
```php
// ❌ Don't do this
echo 'Welcome to the dashboard';

// ✅ Do this instead
echo esc_html__('Welcome to the dashboard', 'mobility-trailblazers');
```

#### 2. Missing Fallbacks
```javascript
// ❌ Don't do this
alert(mt_ajax.i18n.error);

// ✅ Do this instead
alert(mt_ajax.i18n.error || 'An error occurred');
```

#### 3. Inconsistent Text Domain
```php
// ❌ Don't do this
__('String', 'different-text-domain');

// ✅ Do this instead
__('String', 'mobility-trailblazers');
```

### Future Translation Workflow

1. **Extract Strings**: Use `wp i18n make-pot` to generate .pot file
2. **Create .po Files**: Use `msginit` for each target language
3. **Translate**: Edit .po files with translations
4. **Compile**: Use `msgfmt` to create .mo files
5. **Deploy**: Place .mo files in `/languages/` directory

## Database Schema

### Core Tables

#### `wp_mt_evaluations`
- `id` (int, primary key)
- `jury_member_id` (int, foreign key)
- `candidate_id` (int, foreign key)
- `courage_score` (decimal)
- `innovation_score` (decimal)
- `implementation_score` (decimal)
- `relevance_score` (decimal)
- `visibility_score` (decimal)
- `comments` (text)
- `status` (varchar)
- `created_at` (datetime)
- `updated_at` (datetime)

#### `wp_mt_jury_assignments`
- `id` (int, primary key)
- `jury_member_id` (int, foreign key)
- `candidate_id` (int, foreign key)
- `created_at` (datetime)

### Custom Post Types

#### `mt_candidate`
- Post type for award candidates
- Meta fields: `_mt_organization`, `_mt_position`, `_mt_innovation_summary`
- Taxonomies: `mt_award_category`

#### `mt_jury_member`
- Post type for jury members
- Meta fields: `_mt_user_id`, `_mt_expertise`
- Taxonomies: `mt_jury_category`

## AJAX Implementation

### Security
- Always use nonce verification
- Check user capabilities
- Validate and sanitize input
- Use proper error handling

### Pattern
```php
class MT_Example_Ajax extends MT_Base_Ajax {
    public function init() {
        add_action('wp_ajax_mt_example_action', [$this, 'handle_action']);
    }
    
    public function handle_action() {
        $this->verify_nonce();
        $this->check_permission('required_capability');
        
        $data = $this->get_param('data');
        
        // Process data
        
        $this->success($result, __('Success message', 'mobility-trailblazers'));
    }
}
```

## Frontend Development

### CSS Guidelines
- Use CSS variables for colors
- Follow BEM methodology
- Implement responsive design
- Support RTL languages

### JavaScript Guidelines
- Use jQuery for DOM manipulation
- Implement proper error handling
- Use i18n for all user-facing strings
- Follow WordPress standards

## Testing Guidelines

### Unit Testing
- Test individual functions and methods
- Mock dependencies
- Test edge cases
- Maintain high coverage

### Integration Testing
- Test AJAX endpoints
- Test database operations
- Test user workflows
- Test error scenarios

### Manual Testing
- Test all user roles
- Test different browsers
- Test mobile devices
- Test accessibility

## Deployment

### Pre-deployment Checklist
- [ ] All strings properly localized
- [ ] No hardcoded text in JavaScript
- [ ] Security measures in place
- [ ] Performance optimized
- [ ] Accessibility tested
- [ ] Cross-browser tested

### Version Control
- Use semantic versioning
- Write clear commit messages
- Document breaking changes
- Update changelog

## Support and Maintenance

### Documentation
- Keep this guide updated
- Document new features
- Maintain API documentation
- Update user guides

### Code Review
- Review all changes
- Check i18n compliance
- Verify security measures
- Test functionality

### Performance Monitoring
- Monitor database queries
- Check JavaScript performance
- Monitor user experience
- Optimize as needed 