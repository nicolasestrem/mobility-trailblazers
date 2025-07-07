# Bulk Operations Implementation for Mobility Trailblazers

## Overview
This document describes the implementation of bulk operations functionality for the Mobility Trailblazers WordPress plugin v2.0.11. The bulk operations system allows administrators to perform actions on multiple items simultaneously, improving efficiency when managing evaluations, assignments, and candidates.

## Implementation Status

### ✅ Frontend Implementation (Complete)

#### 1. Evaluations Page (`templates/admin/evaluations.php`)
- Added checkboxes for each evaluation row
- Added "Select All" functionality in header and footer
- Added bulk actions dropdown with options:
  - Approve evaluations
  - Reject evaluations  
  - Reset to draft
  - Delete evaluations
- Integrated JavaScript for handling bulk operations

#### 2. Assignments Page (`templates/admin/assignments.php`)
- Converted from card-based to table-based layout
- Added bulk mode toggle button
- Added checkboxes (hidden by default, shown in bulk mode)
- Added bulk actions dropdown with options:
  - Remove selected assignments
  - Reassign to different jury member
  - Export selected assignments
- Added filtering by jury member and status

#### 3. Candidates Page (`templates/admin/candidates.php`)
- Created new candidates management page
- Added bulk mode toggle functionality
- Added bulk actions dropdown with options:
  - Publish/Draft status changes
  - Move to trash
  - Delete permanently
  - Add/Remove categories
  - Export selected candidates
- Added filtering by category and status
- Integrated with existing candidate post type

#### 4. JavaScript Bulk Operations (`assets/js/admin.js`)
- Added `MTBulkOperations` object for assignments page
- Handles bulk mode toggle
- Manages checkbox selection
- Shows selected item count
- Handles bulk actions via AJAX
- Includes confirmation dialogs for destructive actions

### ✅ Backend Implementation (Complete)

#### 1. Evaluation Bulk Operations (`includes/ajax/class-mt-evaluation-ajax.php`)
- Added `mt_bulk_evaluation_action` AJAX handler
- Supports bulk approve/reject/reset/delete operations
- Includes proper permission checks
- Returns success/error counts

#### 2. Assignment Bulk Operations (`includes/ajax/class-mt-assignment-ajax.php`)
- Added `mt_bulk_remove_assignments` handler
- Added `mt_bulk_reassign_assignments` handler
- Added `mt_bulk_export_assignments` handler
- Handles reassignment with duplicate checking
- Exports to CSV with full assignment details

#### 3. Candidate Bulk Operations (`includes/ajax/class-mt-admin-ajax.php`)
- Added `mt_bulk_candidate_action` handler
- Supports status changes (publish/draft/trash/delete)
- Supports category management (add/remove)
- Includes bulk export functionality
- Proper permission checks for each action

#### 4. Admin Menu Integration (`includes/admin/class-mt-admin.php`)
- Added Candidates submenu item
- Added `render_candidates_page` method
- Integrated with existing admin structure

## Security Features

1. **Nonce Verification**: All AJAX calls include nonce verification
2. **Permission Checks**: Each operation checks user capabilities
3. **Data Sanitization**: All input data is properly sanitized
4. **Confirmation Dialogs**: Destructive actions require user confirmation

## User Experience Features

1. **Bulk Mode Toggle**: Checkboxes only appear when bulk mode is active
2. **Selected Count Display**: Shows number of items selected
3. **Loading States**: Visual feedback during operations
4. **Success/Error Messages**: Clear feedback after operations
5. **Filtering Options**: Easy filtering by status, category, etc.

## Technical Details

### AJAX Endpoints

1. **Evaluations**: `wp_ajax_mt_bulk_evaluation_action`
2. **Assignments**: 
   - `wp_ajax_mt_bulk_remove_assignments`
   - `wp_ajax_mt_bulk_reassign_assignments`
   - `wp_ajax_mt_bulk_export_assignments`
3. **Candidates**: `wp_ajax_mt_bulk_candidate_action`

### JavaScript Integration

- Uses jQuery for DOM manipulation
- AJAX calls use WordPress's `ajaxurl`
- Proper error handling and fallbacks
- Form-based export for file downloads

### Database Operations

- Uses existing repository pattern
- Maintains data integrity during bulk operations
- Skips duplicates in reassignment operations
- Proper transaction handling for consistency

## Usage Instructions

### For Evaluations
1. Navigate to Mobility Trailblazers → Evaluations
2. Select evaluations using checkboxes
3. Choose bulk action from dropdown
4. Click Apply

### For Assignments
1. Navigate to Mobility Trailblazers → Assignments
2. Click "Bulk Actions" button to enable bulk mode
3. Select assignments using checkboxes
4. Choose action and click Apply

### For Candidates
1. Navigate to Mobility Trailblazers → Candidates
2. Click "Bulk Actions" to show checkboxes
3. Select candidates
4. Choose action (may require additional input like category)
5. Click Apply

## Testing Checklist

### Evaluations Bulk Operations
- [x] Select all functionality works
- [x] Individual checkbox selection works
- [x] Bulk approve updates database correctly
- [x] Bulk reject updates database correctly
- [x] Bulk reset to draft works
- [x] Bulk delete removes evaluations
- [x] Success/error counts display correctly

### Assignments Bulk Operations
- [x] Bulk mode toggle shows/hides checkboxes
- [x] Selected count updates correctly
- [x] Bulk remove deletes assignments
- [x] Bulk reassign shows modal and reassigns correctly
- [x] Bulk export generates CSV file
- [x] Duplicate assignments are skipped in reassign

### Candidates Bulk Operations
- [x] Bulk status changes work (publish/draft/trash)
- [x] Bulk delete permanently removes candidates
- [x] Bulk category add/remove works
- [x] Bulk export generates CSV file
- [x] Permission checks prevent unauthorized actions

## Version History

- v2.0.11: Complete bulk operations implementation
  - Added bulk operations for evaluations
  - Added bulk operations for assignments
  - Created candidates management page with bulk operations
  - Integrated with existing plugin architecture

## Troubleshooting

### Common Issues

1. **"Security check failed"**: Ensure user is logged in and has proper permissions
2. **No items processed**: Check if items are already in target state
3. **Export not downloading**: Check browser popup blocker settings

### Debug Mode

Enable WordPress debug mode to see detailed error logs:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Future Enhancements

1. **Batch Processing**: For very large operations, implement batch processing
2. **Progress Indicators**: Show progress for long-running operations
3. **Undo Functionality**: Allow reverting bulk operations
4. **Scheduled Bulk Operations**: Queue operations for off-peak processing
5. **Bulk Email Notifications**: Send notifications about bulk changes

## Summary

The bulk operations system is now fully implemented and functional across all three main areas of the plugin:
- **Evaluations**: Administrators can bulk approve, reject, reset, or delete evaluations
- **Assignments**: Administrators can bulk remove, reassign, or export jury-candidate assignments
- **Candidates**: Administrators can bulk update statuses, manage categories, and export candidate data

All operations include proper security checks, user feedback, and error handling to ensure a smooth and secure user experience. 