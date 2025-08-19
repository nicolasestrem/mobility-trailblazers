# Import Profiles Page Removal Documentation

## Date: August 19, 2025

## Summary
Removed the redundant `mt-import-profiles` admin page and its associated features while preserving the newer `mt-candidate-importer` functionality.

## Changes Made

### 1. Files Modified
- **includes/admin/class-mt-admin.php**
  - Removed Import Profiles menu registration (lines 144-152)
  - Removed `render_import_profiles_page()` method (lines 459-478)

### 2. Files Deleted
- **templates/admin/import-profiles.php** - Complete removal of the import profiles template

### 3. Files Preserved
- **includes/admin/class-mt-import-handler.php** - Kept intact as it's used by other components:
  - Import/Export page (`MT_Import_Export`)
  - AJAX handlers (`MT_Import_Ajax`, `MT_CSV_Import_Ajax`)
  - Candidate columns functionality (`MT_Candidate_Columns`)
  
- **includes/admin/class-mt-candidate-importer.php** - The newer import system remains fully functional:
  - Uses `MT_Candidate_Import_Service` for Excel/Photo imports
  - Has its own menu slug: `mt-candidate-importer`
  - Provides more advanced import features

## Technical Details

### Dependencies Analysis
The `MT_Import_Handler` class is still required because:
1. It provides CSV import functionality for the Import/Export page
2. Contains the static method `parse_evaluation_criteria()` used elsewhere
3. Handles jury member imports through CSV

### Import Systems Comparison
| Feature | Old (mt-import-profiles) | New (mt-candidate-importer) |
|---------|-------------------------|----------------------------|
| File Type | CSV only | Excel + Photos |
| Service Class | MT_Import_Handler | MT_Candidate_Import_Service |
| Menu Slug | mt-import-profiles | mt-candidate-importer |
| Status | Removed | Active |

## Testing Verification
- ✅ Import/Export page continues to function
- ✅ MT_Candidate_Importer remains accessible in admin menu
- ✅ No broken references to removed functionality
- ✅ MT_Import_Handler class still available for CSV operations

## Impact Assessment
- **No Breaking Changes**: All active import functionality preserved
- **Cleaner UI**: Removed redundant menu item reduces confusion
- **Maintained Compatibility**: CSV import through Import/Export page still works