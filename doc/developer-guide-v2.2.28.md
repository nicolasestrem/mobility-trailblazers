# Mobility Trailblazers Developer Guide - v2.2.28 Updates

## New Features and Methods

### Enhanced File Upload Validation

The base AJAX class now includes a comprehensive file validation method that all upload handlers should use:

```php
// In any AJAX handler extending MT_Base_Ajax
$validation = $this->validate_upload($_FILES['csv_file'], ['csv', 'txt'], 10 * MB_IN_BYTES);
if ($validation !== true) {
    $this->error($validation);
    return;
}
```

#### Features:
- File type validation (extension and MIME type)
- File size limits
- Malicious content detection (PHP tags, script injection)
- Automatic logging of security events
- Standardized error messages

### Database Integrity Methods

New methods added to `MT_Assignment_Repository` for maintaining data integrity:

#### cleanup_orphaned_assignments()
Removes invalid assignment records and fixes data issues:

```php
$assignment_repo = new MT_Assignment_Repository();
$cleaned = $assignment_repo->cleanup_orphaned_assignments();
// Returns: ['orphaned_candidates' => n, 'orphaned_jury_members' => n, 'missing_assigned_by' => n]
```

#### verify_integrity()
Checks for database integrity issues without making changes:

```php
$issues = $assignment_repo->verify_integrity();
// Returns array of issues found:
// ['orphaned_candidates' => n, 'orphaned_jury_members' => n, 'duplicate_assignments' => n]
```

### Widget Refresh Functions

New JavaScript functions for refreshing dashboard widgets after AJAX operations:

```javascript
// Refresh a single widget
refreshDashboardWidget('mt-stats-widget', function(success) {
    if (success) {
        console.log('Widget refreshed');
    }
});

// Refresh multiple widgets
refreshDashboardWidgets(['mt-stats-widget', 'mt-recent-activity']);
```

#### CSS Classes:
- `.mt-widget-loading` - Applied during refresh
- Includes loading animation and visual feedback

### CSV Import Improvements

#### BOM Handling
The import handler now properly detects and handles Byte Order Mark (BOM) in CSV files:

```php
// Automatic BOM detection and removal
// No action needed - handled internally by MT_Import_Handler
```

#### Delimiter Detection
Automatic detection of CSV delimiters (comma, semicolon, tab, pipe):

```php
// The system automatically detects the delimiter
// Supports: , ; \t |
```

#### Case-Insensitive Field Mapping
Field mapping now handles different cases and alternate names:

```php
// These are all recognized:
'Name', 'name', 'NAME'
'Organisation', 'Organization'
'Website', 'Webseite'
```

### Event Delegation

All event handlers now use delegation for better dynamic element support:

```javascript
// Old way (don't use):
$('#mt-button').on('click', handler);

// New way (use this):
$(document).on('click', '#mt-button', handler);
```

### Security Enhancements

#### AJAX Security
All AJAX handlers must verify nonce and check permissions:

```php
public function handle_ajax_action() {
    // MUST BE FIRST
    if (!$this->verify_nonce('mt_ajax_nonce')) {
        $this->error(__('Security check failed', 'mobility-trailblazers'));
        return;
    }
    
    // MUST BE SECOND
    if (!$this->check_permission('required_capability')) {
        return; // check_permission already sends error
    }
    
    // Your handler logic here
}
```

#### Debug Endpoints
Debug endpoints now require admin permissions:
- `test_ajax` - Requires login
- `debug_user` - Requires `manage_options` capability

## Migration Guide for v2.2.28

### For Developers

1. **Update File Upload Handlers**:
   - Replace custom validation with `$this->validate_upload()`
   - Remove duplicate MIME type checks

2. **Update Event Handlers**:
   - Convert direct bindings to event delegation
   - Use `$(document).on()` pattern

3. **Add Widget Refresh**:
   - Call `refreshDashboardWidget()` after data changes
   - Add loading states to your widgets

4. **Database Maintenance**:
   - Schedule periodic `cleanup_orphaned_assignments()`
   - Monitor with `verify_integrity()`

### For Site Administrators

1. **Run Database Cleanup** (one-time):
```php
// In WP-CLI or custom script
$repo = new MT_Assignment_Repository();
$results = $repo->cleanup_orphaned_assignments();
```

2. **Verify Import Templates**:
   - Templates work with both BOM and non-BOM files
   - Supports multiple delimiter types
   - Case-insensitive field matching

## Troubleshooting

### CSV Import Issues

**Problem**: Headers not recognized
**Solution**: The system now handles BOM, different cases, and alternate field names automatically

**Problem**: Wrong delimiter detected
**Solution**: The system auto-detects delimiters. Ensure your CSV uses consistent delimiters throughout

### JavaScript Issues

**Problem**: mt_ajax not defined
**Solution**: v2.2.28 adds automatic fallback initialization

**Problem**: Events not firing on dynamic elements
**Solution**: All handlers now use event delegation

### Database Issues

**Problem**: Orphaned assignments
**Solution**: Run `cleanup_orphaned_assignments()` method

**Problem**: Missing assigned_by values
**Solution**: The cleanup method automatically fixes these

## Priority 3 Enhancements

### Batch Processing for Large Imports

The CSV import system now supports batch processing with progress tracking:

```javascript
// Progress tracking is automatic when using the import form
// Visual feedback includes:
// - Progress bar with percentage
// - Record counts (imported, updated, skipped, errors)
// - Real-time status messages
```

### Streaming Exports for Memory Optimization

New streaming export methods prevent memory exhaustion on large datasets:

```php
// Use streaming methods for large exports
MT_Import_Export::export_candidates_stream($args);
MT_Import_Export::export_evaluations_stream($args);

// Features:
// - Processes data in 100-record batches
// - Clears WordPress object cache between batches
// - Prevents PHP timeouts with set_time_limit()
// - Direct output to browser (no memory buffering)
```

### UI/UX Standardization

New CSS classes for consistent UI elements:

```css
/* Button variants */
.mt-btn-primary   /* Primary action buttons */
.mt-btn-secondary /* Secondary action buttons */
.mt-btn-danger    /* Destructive actions */
.mt-btn-loading   /* Applied during AJAX operations */

/* Progress bars */
.mt-progress-bar       /* Container */
.mt-progress-bar-fill  /* Animated fill */

/* Loading states */
.mt-spinner           /* Spinning loader */
.mt-loading-overlay   /* Full-screen loading overlay */
```

### German Translations

All new features include complete German translations:

```php
// Examples of new translations:
__('Processing batch %d of %d...', 'mobility-trailblazers')
// German: 'Verarbeite Stapel %d von %d...'

__('Streaming export in progress', 'mobility-trailblazers')
// German: 'Streaming-Export läuft'
```

## Testing Checklist

- [ ] Test CSV import with BOM files (Excel export)
- [ ] Test CSV import without BOM
- [ ] Test with different delimiters (, ; tab)
- [ ] Test file upload security (try uploading .php renamed to .csv)
- [ ] Test widget refresh after AJAX operations
- [ ] Verify event handlers work on dynamically added elements
- [ ] Run database integrity check
- [ ] Test with different user roles
- [ ] Test batch import with 1000+ records
- [ ] Test streaming export with 5000+ records
- [ ] Verify memory usage stays constant during large exports
- [ ] Test all button loading states
- [ ] Verify German translations display correctly
- [ ] Test progress bar animations
- [ ] Verify no memory leaks during batch operations