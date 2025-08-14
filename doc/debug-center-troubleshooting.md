# Debug Center Troubleshooting Guide

## Overview
This document provides solutions to common issues encountered with the Debug Center and documents the fixes applied during the v2.3.1 and v2.3.2 updates.

## Fixed Issues (v2.3.2)

### 1. Debug Scripts Showing Empty Info Box

**Symptoms:**
- Clicking "Execute" on debug scripts shows an empty modal
- Script executes but output is not visible
- Raw HTML code appears instead of formatted output

**Root Causes:**
1. The JavaScript was escaping HTML output and displaying it in a `<pre>` tag
2. AJAX response was double-wrapping data, causing incorrect data access path

**Solutions Applied:**

#### JavaScript Fix:
```javascript
// OLD (Incorrect)
showScriptOutput: function(data) {
    const $modal = $('<div class="mt-modal">').html(`
        <pre>${this.escapeHtml(data.output || '')}</pre>
    `);
}

// NEW (Fixed)
showScriptOutput: function(data) {
    const $modal = $('<div class="mt-modal">').html(`
        <div class="mt-script-output">${data.output || '<p>No output generated</p>'}</div>
    `);
}
```

#### AJAX Handler Fix:
```php
// OLD (Double-wrapped)
if ($result['success']) {
    $this->success($result);  // This wraps $result in another layer
}

// NEW (Direct)
if ($result['success']) {
    wp_send_json_success($result);  // Send directly
}
```

**Files Modified:**
- `assets/js/debug-center.js`: Changed output rendering method
- `assets/css/debug-center.css`: Added `.mt-script-output` styling
- `includes/ajax/class-mt-debug-ajax.php`: Fixed response structure

### 2. Operation Not Found Error

**Symptoms:**
- Clicking maintenance operation buttons triggers "Operation not found" error
- Cache and Reset operations not working

**Root Cause:**
Template was accessing operations at wrong path in the data structure.

**Solution Applied:**
```php
// OLD (Incorrect path)
<?php foreach ($operations['cache'] as $op_key => $operation): ?>

// NEW (Correct path)
<?php foreach ($operations['cache']['operations'] as $op_key => $operation): ?>
```

**Files Modified:**
- `templates/admin/debug-center/tab-tools.php`: Fixed operation paths

## Fixed Issues (v2.3.1)

### 2. PHP Fatal Error: Abstract Method Not Implemented

**Error:**
```
PHP Fatal error: Class MT_Debug_Ajax contains 1 abstract method and must therefore be declared abstract or implement the remaining methods (MT_Base_Ajax::init)
```

**Solution:**
Implemented the required `init()` method in MT_Debug_Ajax class.

### 3. JavaScript Errors: Undefined Methods

**Error:**
```
TypeError: Cannot read properties of undefined (reading 'bind')
```

**Solution:**
Removed bindings for non-existent methods:
- `viewScript`
- `confirmOperation`
- `analyzeTable`
- `optimizeTable`

### 4. PHP Warnings: Undefined Array Keys

**Errors:**
Multiple warnings about undefined array keys in tab templates.

**Solution:**
Added defensive programming with isset() checks:
```php
// Before
$value = $array['key'];

// After
$value = isset($array['key']) ? $array['key'] : 'default';
```

### 5. Database Connection Errors

**Error:**
```
mysqli_real_connect(): (HY000/2002): Connection refused
```

**Solution:**
Added graceful fallbacks when database is unavailable:
```php
if (!is_array($db_stats)) {
    $db_stats = [
        'total_tables' => 0,
        'total_rows' => 0,
        'total_size_formatted' => 'N/A',
        'plugin_tables' => 0
    ];
}
```

### 6. Private Method Access Errors

**Error:**
```
Call to private method MT_Error_Monitor::get_error_statistics()
```

**Solution:**
Changed method visibility from private to public in affected classes.

## Common Issues and Solutions

### Issue: Scripts Not Executing

**Check:**
1. Verify user has `manage_options` capability
2. Check browser console for AJAX errors
3. Ensure nonce is valid (refresh page if stale)

**Debug:**
```javascript
// Check in browser console
console.log(mt_debug.nonce);
console.log(mt_debug.ajax_url);
```

### Issue: Maintenance Operations Failing

**Check:**
1. Verify operation exists in MT_Maintenance_Tools
2. Check if operation requires confirmation or password
3. Ensure proper data-category and data-operation attributes

**Debug:**
```php
// Check registered operations
$tools = new MT_Maintenance_Tools();
$operations = $tools->get_operations();
error_log(print_r($operations, true));
```

### Issue: Tab Content Not Loading

**Check:**
1. Verify template file exists
2. Check for PHP errors in template
3. Ensure proper WordPress environment

**Debug:**
Enable WP_DEBUG to see template errors:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## Environment Detection

The Debug Center uses environment detection to control script availability:

| Environment | Detection | Available Scripts |
|------------|-----------|------------------|
| Development | localhost, .local, .test | All scripts |
| Staging | staging in URL | Most scripts (no test generators) |
| Production | Default | Limited scripts (diagnostics only) |

Override with constant:
```php
define('MT_ENVIRONMENT', 'development'); // Force environment
```

## File Structure

```
mobility-trailblazers/
├── includes/
│   ├── admin/
│   │   ├── class-mt-debug-manager.php      # Script management
│   │   └── class-mt-maintenance-tools.php  # Maintenance operations
│   ├── ajax/
│   │   └── class-mt-debug-ajax.php        # AJAX handler
│   ├── services/
│   │   └── class-mt-diagnostic-service.php # Diagnostics
│   └── utilities/
│       ├── class-mt-database-health.php    # Database monitoring
│       └── class-mt-system-info.php        # System information
├── templates/admin/debug-center/
│   ├── tab-diagnostics.php
│   ├── tab-database.php
│   ├── tab-scripts.php
│   ├── tab-errors.php
│   ├── tab-tools.php
│   └── tab-info.php
├── assets/
│   ├── js/
│   │   └── debug-center.js                 # Frontend logic
│   └── css/
│       └── debug-center.css                # Styling
└── debug/
    ├── generators/                          # Test data scripts
    ├── diagnostics/                         # Diagnostic scripts
    ├── migrations/                          # Migration scripts
    ├── repairs/                            # Repair scripts
    └── registry.json                        # Script registry

```

## Testing Checklist

After making changes to the Debug Center:

- [ ] All tabs load without errors
- [ ] Diagnostics run successfully
- [ ] Database operations complete
- [ ] Scripts execute and show output
- [ ] Error logs display correctly
- [ ] Maintenance tools function
- [ ] System info exports properly
- [ ] No JavaScript console errors
- [ ] No PHP warnings/errors in debug.log
- [ ] Environment detection works correctly

## Support

For additional support or to report issues:
1. Check the error logs in WP_CONTENT_DIR/debug.log
2. Review browser console for JavaScript errors
3. Verify all required files are present
4. Ensure proper permissions on debug/ directory

## Version History

- **v2.3.2** (2025-08-14): Fixed script output display issue
- **v2.3.1** (2025-08-14): Fixed multiple runtime errors
- **v2.3.0** (2025-08-14): Initial Debug Center implementation