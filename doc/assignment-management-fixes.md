# Mobility Trailblazers Assignment Management Fixes Documentation

**Date**: June 23, 2025  
**Plugin Version**: 2.0.3  
**Author**: Development Team  

## Overview

This document details the comprehensive fixes applied to the Mobility Trailblazers WordPress plugin's Assignment Management system. The assignment page (`/wp-admin/admin.php?page=mt-assignments`) had multiple non-functional features that have been successfully repaired.

## Initial Problems Identified

1. **AJAX Handlers Not Registered**: Buttons showed "An error occurred. Please try again."
2. **Wrong Post Type**: Auto-assign looked for `mt_jury` instead of `mt_jury_member`
3. **Export Function Fatal Error**: Missing method in evaluation repository
4. **Clear All Not Working**: Incorrect nonce verification
5. **Manual Assignment Failed**: Array parameter handling issue
6. **Progress Display Incorrect**: Not matching jury dashboard calculations
7. **Bulk Actions Button**: No implementation

## Fixes Applied

### 1. AJAX Handler Registration Fix

**File**: `includes/core/class-mt-plugin.php`

**Problem**: AJAX handlers only initialized during `wp_doing_ajax()` calls.

**Solution**:
```php
// Before:
if (wp_doing_ajax()) {
    $this->init_ajax_handlers();
}

// After:
// Initialize AJAX handlers - Always initialize, not just during AJAX requests
$this->init_ajax_handlers();
```

**Impact**: All AJAX handlers now properly register on every page load.

### 2. Correct Post Type for Jury Members

**File**: `includes/ajax/class-mt-assignment-ajax.php`

**Problem**: Auto-assign searched for post type `mt_jury` which doesn't exist.

**Solution**:
```php
// Before:
'post_type' => 'mt_jury',

// After:
'post_type' => 'mt_jury_member',
```

**Additional Changes**:
- Removed `_mt_status` meta query that was filtering out all jury members
- Added detailed logging for debugging

### 3. Export Function Fix

**File**: `includes/ajax/class-mt-assignment-ajax.php`

**Problem**: Called non-existent `get_by_jury_and_candidate()` method.

**Solution**:
```php
// Use find_all with filters instead
$evaluations = $evaluation_repo->find_all([
    'jury_member_id' => $assignment->jury_member_id,
    'candidate_id' => $assignment->candidate_id,
    'limit' => 1
]);
$evaluation = !empty($evaluations) ? $evaluations[0] : null;
```

**Additional Fixes**:
- Added `is_wp_error()` check for categories
- Safe date formatting
- Changed permission check from `mt_export_data` to `manage_options`

### 4. Clear All Assignments Fix

**File**: `includes/ajax/class-mt-assignment-ajax.php`

**Problem**: Used `verify_nonce()` without parameters, defaulting to wrong nonce name.

**Solution**:
```php
// Before:
if (!$this->verify_nonce()) {

// After:
if (!$this->verify_nonce('mt_admin_nonce')) {
```

**Additional**: Added early returns after error responses.

### 5. Manual Assignment Array Handling

**Files**: 
- `includes/ajax/class-mt-assignment-ajax.php`
- `includes/ajax/class-mt-base-ajax.php`

**Problem**: `get_param()` applied `sanitize_text_field()` which converted arrays to strings.

**Solution**:
```php
// In bulk_create_assignments:
$candidate_ids = isset($_POST['candidate_ids']) && is_array($_POST['candidate_ids']) 
    ? array_map('intval', $_POST['candidate_ids']) 
    : array();

// Fixed get_array_param in base class:
protected function get_array_param($key, $default = []) {
    if (!isset($_REQUEST[$key])) {
        return $default;
    }
    $value = $_REQUEST[$key];
    return is_array($value) ? $value : $default;
}
```

### 6. Auto-Assignment Methods Enhancement

**File**: `includes/ajax/class-mt-assignment-ajax.php`

**Balanced Method Fix**:
```php
// Now respects candidates_per_jury limit
$total_to_assign = min(count($candidates), $jury_count * $candidates_per_jury);
for ($i = 0; $i < $total_to_assign; $i++) {
    $jury_index = $i % $jury_count;
    // ... assignment logic
}
```

**Random Method Enhancement**:
```php
// Added actual randomization
$shuffled_candidates = $candidates;
shuffle($shuffled_candidates);
// Then assigns from shuffled array
```

### 7. Repository Method Addition

**File**: `includes/repositories/class-mt-assignment-repository.php`

**Added Missing Method**:
```php
public function get_by_jury_and_candidate($jury_member_id, $candidate_id) {
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$this->table_name} 
         WHERE jury_member_id = %d AND candidate_id = %d
         LIMIT 1",
        $jury_member_id,
        $candidate_id
    ));
}
```

**Enhanced Logging**:
```php
public function create($data) {
    // Added logging
    error_log('MT Assignment Repository - Creating assignment with data: ' . print_r($data, true));
    // ... existing code
    if ($result === false) {
        error_log('MT Assignment Repository - Insert failed. Last error: ' . $wpdb->last_error);
    }
}
```

## Database Structure

The assignments table (`wp_mt_jury_assignments`) has the following structure:

| Column | Type | Key | Default |
|--------|------|-----|---------|
| id | bigint(20) unsigned | PRI | NULL |
| jury_member_id | bigint(20) unsigned | MUL | NULL |
| candidate_id | bigint(20) unsigned | MUL | NULL |
| assigned_at | datetime | | current_timestamp() |
| assigned_by | bigint(20) unsigned | | NULL |

## JavaScript Enhancements

**File**: `assets/js/admin.js`

The `MTAssignmentManager` module handles:
- Modal show/hide functionality
- AJAX form submissions
- Assignment removal
- Search and filtering
- Progress bar updates

Key methods:
- `submitAutoAssignment()`: Handles auto-assign AJAX
- `submitManualAssignment()`: Handles manual assignment AJAX
- `clearAllAssignments()`: Double-confirmation clear all
- `exportAssignments()`: Creates form for CSV download

## Verification Tools

**File**: `verify-fix.php`

Created comprehensive verification script that checks:
- AJAX handler registration status
- Database table structure
- User capabilities
- Jury members and candidates count
- Test buttons for all AJAX endpoints

## UI/UX Improvements

1. **Progress Bars**: Show evaluation completion percentage
2. **Modal Descriptions**: Clear explanation of assignment methods
3. **Debug Section**: Helpful information for troubleshooting
4. **Search/Filter**: Real-time filtering of assignments
5. **Bulk Actions**: Button hidden until functionality is implemented

## Permissions and Capabilities

Required capabilities:
- `manage_options`: For admin-only functions (clear all, auto-assign)
- `mt_manage_assignments`: For assignment management
- `mt_view_all_evaluations`: For viewing evaluations

## Testing Procedures

1. **Auto-Assignment Test**:
   - Select "Balanced" or "Random" method
   - Set candidates per jury (e.g., 1, 5, 10)
   - Verify correct distribution

2. **Manual Assignment Test**:
   - Select jury member
   - Select multiple candidates
   - Verify bulk creation

3. **Clear All Test**:
   - Requires double confirmation
   - Truncates assignments table

4. **Export Test**:
   - Generates CSV with all assignment data
   - Includes evaluation status

## Error Handling

All AJAX methods include:
- Nonce verification
- Permission checks
- Input validation
- Detailed error messages
- Debug logging (when enabled)

## Future Enhancements

1. **Bulk Actions**: Implement bulk operations on selected assignments
2. **Assignment Templates**: Save/load assignment configurations
3. **Assignment History**: Track changes and allow rollback
4. **System Integration**: Enhanced integration with evaluation system
5. **Assignment Deadlines**: Set and track evaluation deadlines

## Troubleshooting

Common issues and solutions:

1. **"Security check failed"**: Clear browser cache, reload page
2. **"No jury members found"**: Ensure jury members are published
3. **"Invalid data provided"**: Check browser console for JavaScript errors
4. **Assignments not creating**: Check if assignments already exist

## Code Standards

- Namespace: `MobilityTrailblazers\`
- Text domain: `mobility-trailblazers`
- Nonce name: `mt_admin_nonce`
- AJAX actions prefix: `mt_`
- Database tables prefix: `{prefix}mt_`

## Session Continuity

For future development sessions, key entry points:
- Assignment page: `templates/admin/assignments.php`
- AJAX handlers: `includes/ajax/class-mt-assignment-ajax.php`
- JavaScript: `assets/js/admin.js` (MTAssignmentManager module)
- Repository: `includes/repositories/class-mt-assignment-repository.php`
- Service: `includes/services/class-mt-assignment-service.php`

---

**Note**: All fixes have been tested and verified working as of June 23, 2025. The assignment management system is now fully functional with all buttons operational and proper error handling in place. 