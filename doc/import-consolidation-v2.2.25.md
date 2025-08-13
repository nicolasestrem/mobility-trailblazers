# Import System Consolidation - v2.2.25

## Overview

Version 2.2.25 consolidates the CSV import system from 7 different import classes down to 4 with clear separation of concerns and no duplicate functionality.

## What Changed

### Removed Classes
1. **MT_Profile_Importer** (`class-mt-profile-importer.php`)
   - Status: Deleted - was not used anywhere in the codebase
   - Legacy code from v2.1.0

2. **MT_Enhanced_Profile_Importer** (`class-mt-enhanced-profile-importer.php`)
   - Status: Deleted - functionality moved to MT_Import_Handler
   - The `parse_evaluation_criteria()` method has been moved to MT_Import_Handler
   - All import logic consolidated into MT_Import_Handler

### Updated Classes

#### MT_Import_Handler
**File**: `includes/admin/class-mt-import-handler.php`
**Changes**:
- Added `parse_evaluation_criteria()` method (moved from MT_Enhanced_Profile_Importer)
- Now the single source of truth for all CSV import operations
- Handles both candidates and jury members
- Contains all field mappings as class constants

#### MT_Import_Ajax
**File**: `includes/ajax/class-mt-import-ajax.php`
**Changes**:
- Updated to use MT_Import_Handler instead of MT_Enhanced_Profile_Importer
- Changed import statement from `use MT_Enhanced_Profile_Importer` to `use MT_Import_Handler`
- Updated import method call to use new handler

#### MT_Candidate_Columns
**File**: `includes/admin/class-mt-candidate-columns.php`
**Changes**:
- Updated `process_csv_import()` to use MT_Import_Handler
- Removed dependency on MT_Enhanced_Profile_Importer

#### MT_Import_Export
**File**: `includes/admin/class-mt-import-export.php`
**Changes**:
- Updated evaluation criteria parsing to use MT_Import_Handler
- Removed class_exists check for MT_Enhanced_Profile_Importer

### Template Updates

#### import-profiles.php
**File**: `templates/admin/import-profiles.php`
**Changes**:
- Updated to use MT_Import_Handler for imports
- Modified to work with new consolidated handler
- Simplified import logic

## Migration Guide

### For Developers

If you have custom code using the old import classes, update as follows:

#### Old Way (Deprecated)
```php
// Using MT_Profile_Importer
$result = MT_Profile_Importer::import_csv($file);

// Using MT_Enhanced_Profile_Importer
$result = MT_Enhanced_Profile_Importer::import_csv($file, $options);
$criteria = MT_Enhanced_Profile_Importer::parse_evaluation_criteria($description);
$mapping = MT_Enhanced_Profile_Importer::get_field_mapping();
```

#### New Way (v2.2.25+)
```php
// All imports now use MT_Import_Handler
$handler = new \MobilityTrailblazers\Admin\MT_Import_Handler();

// Import candidates
$result = $handler->process_csv_import($file, 'candidates', $update_existing);

// Import jury members
$result = $handler->process_csv_import($file, 'jury_members', $update_existing);

// Parse evaluation criteria (now a static method)
$criteria = MT_Import_Handler::parse_evaluation_criteria($description);

// Field mappings are now class constants
$candidate_fields = MT_Import_Handler::CANDIDATE_FIELD_MAPPING;
$jury_fields = MT_Import_Handler::JURY_FIELD_MAPPING;
```

## Architecture After Consolidation

### 4 Main Import Components

1. **MT_Import_Handler** (`includes/admin/class-mt-import-handler.php`)
   - Core import processing engine
   - CSV parsing and validation
   - Field mapping definitions
   - BOM and delimiter detection
   - Parse evaluation criteria

2. **MT_Import_Export** (`includes/admin/class-mt-import-export.php`)
   - Admin UI page handling
   - Form submissions
   - Template downloads
   - Export operations

3. **MT_CSV_Import_Ajax** (`includes/ajax/class-mt-csv-import-ajax.php`)
   - Modern AJAX handler with progress tracking
   - Real-time import updates
   - Comprehensive error reporting
   - Uses MT_Import_Handler for processing

4. **MT_Import_Ajax** (`includes/ajax/class-mt-import-ajax.php`)
   - Quick import button on All Candidates page
   - Lightweight AJAX import
   - Uses MT_Import_Handler for processing

## Benefits of Consolidation

1. **Reduced Complexity**: From 7 import files to 4
2. **Clear Separation**: Each file has a specific, non-overlapping purpose
3. **Single Source of Truth**: All import logic in MT_Import_Handler
4. **Easier Maintenance**: No duplicate code to maintain
5. **Better Testing**: Centralized logic easier to test
6. **Consistent Behavior**: All imports follow same processing path

## Testing Checklist

After updating to v2.2.25, test the following:

- [ ] CSV import via Import/Export page (standard form)
- [ ] CSV import via Import/Export page (AJAX button)
- [ ] Quick import button on All Candidates page
- [ ] Import profiles page functionality
- [ ] Template downloads for candidates and jury members
- [ ] Export functionality for candidates, evaluations, and assignments
- [ ] Evaluation criteria parsing from German descriptions
- [ ] Field mapping for all CSV columns
- [ ] Error handling and validation messages

## Rollback Instructions

If you need to rollback to the previous version:

1. Restore the deleted files from git:
   - `includes/admin/class-mt-profile-importer.php`
   - `includes/admin/class-mt-enhanced-profile-importer.php`

2. Revert the changes in:
   - `includes/ajax/class-mt-import-ajax.php`
   - `includes/admin/class-mt-candidate-columns.php`
   - `includes/admin/class-mt-import-export.php`
   - `includes/admin/class-mt-import-handler.php`
   - `templates/admin/import-profiles.php`

3. Remove the `parse_evaluation_criteria()` method from MT_Import_Handler

## Support

For questions or issues related to this consolidation:
1. Check the error logs at `/wp-content/debug.log`
2. Review this migration guide
3. Test with sample CSV files first
4. Contact development team with specific error messages