# Mobility Trailblazers Changelog

> **Note**: Version 2.2.7b represents a hotfix that was deployed on the same day as 2.2.7. The duplicate version number has been corrected with the 'b' suffix to maintain chronological accuracy.

## [2.2.25] - 2025-08-13

### Refactored
- **Import System Consolidation**: Reduced from 7 import files to 4 with clear separation of concerns
  - Consolidated all import logic into `MT_Import_Handler` class
  - Removed duplicate and unused import classes
  - Moved `parse_evaluation_criteria()` method to centralized location
  - Updated all references to use single import handler

### Removed
- **Deprecated Import Classes**:
  - `class-mt-profile-importer.php` - Unused legacy importer
  - `class-mt-enhanced-profile-importer.php` - Functionality moved to MT_Import_Handler
  - Created deprecation notice documenting migration path

### Changed
- **Import Architecture**:
  - All import operations now use `MT_Import_Handler` as single source of truth
  - `MT_Import_Ajax` updated to use MT_Import_Handler instead of MT_Enhanced_Profile_Importer
  - `MT_Candidate_Columns` now uses MT_Import_Handler for CSV processing
  - `import-profiles.php` template updated to use consolidated handler
  - Debug files updated to reference new import handler

### Technical Improvements
- **Code Organization**:
  - MT_Import_Handler: Core import logic and CSV processing
  - MT_Import_Export: Admin UI and export functionality
  - MT_CSV_Import_Ajax: AJAX with progress tracking
  - MT_Import_Ajax: Quick import functionality
  
- **Method Consolidation**:
  - `parse_evaluation_criteria()` now in MT_Import_Handler (public static method)
  - Field mappings centralized as class constants
  - Consistent error handling across all import paths

### Files Updated
- `includes/ajax/class-mt-import-ajax.php` - Uses MT_Import_Handler
- `includes/admin/class-mt-candidate-columns.php` - Uses MT_Import_Handler
- `includes/admin/class-mt-import-export.php` - References MT_Import_Handler
- `includes/admin/class-mt-import-handler.php` - Added parse_evaluation_criteria()
- `templates/admin/import-profiles.php` - Updated to use MT_Import_Handler
- `debug/*.php` - Updated references to new handler

## [2.2.24] - 2025-08-13

### Added
- **Complete AJAX CSV Import System**: Implemented comprehensive CSV import with real-time progress tracking
  - Created `MT_CSV_Import_Ajax` class with full security and validation
  - JavaScript module with progress modal and file validation
  - CSS for professional import UI with animated progress bars
  - Support for both standard form submission and AJAX imports

- **Import Handler Class**: Created `MT_Import_Handler` for centralized CSV processing
  - Handles both jury members and candidates imports
  - UTF-8 BOM detection and removal for Excel compatibility
  - Field mapping with support for German headers
  - Comprehensive error tracking with row-specific details

- **CSV Templates with German Support**: Updated templates with proper formatting
  - Candidates template with exact headers: ID, Name, Organisation, Position, LinkedIn-Link, Webseite, Article about coming of age, Description, Category, Status
  - Jury members template with standard fields
  - UTF-8 BOM included for Excel compatibility

### Enhanced
- **Import/Export Page**: Complete overhaul of import functionality
  - Fixed template download links to serve actual CSV files
  - Added fallback template generation if files not found
  - Dual import methods: standard form and AJAX with progress
  - Help text with exact CSV format requirements

- **Script Enqueuing**: Proper JavaScript and CSS loading
  - CSV import scripts loaded only on import/export page
  - Localized strings for all UI messages
  - Progress tracking and error reporting

### Fixed
- **Template Download Issues**: Resolved file naming mismatches
  - Fixed jury-members.csv vs jury_members.csv conflicts
  - Added multiple fallback methods for template serving
  - Dynamic template generation if files missing

- **Import Errors**: Comprehensive error handling
  - Better error messages with specific details
  - Row-by-row error tracking
  - Support for various CSV encodings

### Technical Details
- **New Files Created**:
  - `includes/ajax/class-mt-csv-import-ajax.php` - AJAX handler
  - `includes/admin/class-mt-import-handler.php` - Import processor
  - `assets/js/csv-import.js` - JavaScript module
  - `assets/css/csv-import.css` - Import UI styles

- **Files Modified**:
  - `includes/core/class-mt-plugin.php` - Added AJAX handler initialization
  - `includes/admin/class-mt-import-export.php` - Enhanced with new field mappings
  - `data/templates/candidates.csv` - Updated with correct headers
  - `data/templates/jury-members.csv` - Standardized format

## [2.2.23] - 2025-08-13

### Added
- **Comprehensive Import/Export System**: Created `class-mt-import-export.php` for unified CSV import/export
  - Support for both Jury Members and Candidates import types
  - Admin post handlers for form-based imports
  - AJAX handler for async CSV imports with progress feedback
  - Export functionality for candidates, evaluations, and assignments
  - Template download system for CSV formats
  - Proper UTF-8 BOM handling for Excel compatibility
  
### Features
- **Import Types**: Dropdown now properly shows "Jury Members" and "Candidates" options
- **CSV Parsing**: Uses PHP's native `fgetcsv()` for reliable parsing
- **Validation**: Required field checking, email validation, URL sanitization
- **User Creation**: Automatic WordPress user creation for jury members with role assignment
- **Duplicate Handling**: Option to update existing records or skip duplicates
- **Error Reporting**: Detailed error messages with row numbers for failed imports
- **Security**: Full nonce verification, capability checks, and input sanitization

### Changed
- **Plugin Initialization**: Added `MT_Import_Export::init()` to core plugin class
- **Template Files**: Created CSV templates in `data/templates/` for jury members and candidates

### Files Modified
- `includes/admin/class-mt-import-export.php` - New comprehensive import/export handler
- `includes/core/class-mt-plugin.php` - Added import/export initialization
- `data/templates/jury-members.csv` - Template with 20 jury members
- `data/templates/candidates.csv` - Template with sample candidates

## [2.2.22] - 2025-08-13

### Fixed
- **Debug Code Cleanup**: Removed all remaining console.log/warn/error statements from production JavaScript
  - Cleaned admin.js debug statements
  - Removed all console logging from frontend.js
  - Production code now free of debug output
- **SQL Injection Prevention**: Fixed unescaped SQL queries in error monitor
  - Added proper `$wpdb->prepare()` for SHOW TABLES queries
  - All dynamic SQL now properly escaped
- **Performance Optimization**: Added limits to unbounded queries
  - Assignment display queries limited to 1000 records
  - Prevents memory exhaustion on large datasets

### Security
- **XSS Prevention**: Identified and documented areas using `.html()` that need sanitization
- **SQL Safety**: All table name interpolation now uses prepare statements

### Code Quality
- **Debug Cleanup**: All debug output removed or commented out
- **Query Optimization**: Large result sets now properly limited

## [2.2.21] - 2025-08-13

### Fixed
- **Duplicate AJAX Actions**: Removed duplicate `mt_bulk_export_assignments` handler - now uses centralized Admin AJAX export
- **Documentation Links**: Fixed broken documentation links in README - all now point to existing files
- **CSV Export Memory**: Improved CSV export to stream data in chunks (100 records at a time) instead of loading all into memory
  - Added plugin version and export date to CSV metadata
  - Prevents memory exhaustion on large datasets

### Added
- **Assignment Distribution Diagnostic**: Added debug tool in Assignments page showing:
  - Current distribution per jury member
  - Average, min, and max assignments
  - Test distribution algorithm with seed for reproducibility
  
### Verified
- **AJAX Security Pattern**: All bulk operations follow proper nonce → capability → sanitize pattern
- **Email Reminders**: Confirmed complete removal - no dead UI elements remain
- **Shortcode Registration**: All shortcodes in README are properly registered
- **Nonce Consistency**: Standardized on `mt_admin_nonce` for admin, `mt_ajax_nonce` for frontend
- **Data Retention**: Uninstall properly respects `mt_remove_data_on_uninstall` setting with clear UI warnings

### Changed
- **Code Organization**: Consolidated export functionality to Admin AJAX handler only

## [2.2.20] - 2025-08-13

### Fixed
- **Import Permission Check**: Fixed incorrect capability check in MT_Import_Ajax - now properly uses 'edit_posts' capability
- **JavaScript Alert Replacement**: Replaced all browser alert() calls with WordPress admin notices using mtShowNotification
  - Updated admin.js to use proper notification types (success, error, warning, info)
  - Provides better user experience with dismissible notices
- **Debug Code Cleanup**: Removed console.log statements from production JavaScript files
  - Cleaned frontend.js debug logging
  - Cleaned candidate-import.js debug logging
  - Improves security by not exposing internal information
- **Uninstaller Bug**: Fixed table name typo (mt_error_logs → mt_error_log) ensuring complete data removal

### Changed
- **Code Quality**: Improved JavaScript notification handling for consistent user feedback
- **Security**: Removed debug output that could expose sensitive information

### Verified
- **View Details Modal**: Confirmed working properly with full implementation
- **AJAX Response Format**: Verified all handlers use standardized success/error format from base class

## [2.2.19] - 2025-08-13

### Fixed
- **"Save as Draft" Feature**: Verified AJAX endpoint registration - feature is working correctly with proper nonce verification
- **CSV Export Links**: Fixed broken export links in Import/Export admin page
  - Added direct download handlers via admin-post.php actions
  - Export handlers now output CSV files directly with proper headers
  - Added UTF-8 BOM for proper encoding in Excel
  
- **Missing JavaScript Function**: Implemented mtShowNotification function in admin.js
  - Added WordPress-style admin notifications
  - Auto-dismissal for success messages after 5 seconds
  - Proper styling matching WordPress admin interface

### Changed
- **CSV Import Documentation**: Updated to properly document both import methods
  - Quick AJAX import on All Candidates page
  - Advanced Import Profiles page with dry run capability
  - Clear distinction between the two import options

### Removed
- **Dead Code Cleanup**: Removed deprecated candidates.php template file
  - File was marked deprecated in v2.2.15
  - Removed reference from general_index.md documentation
  - Functionality already moved to post type interface

### Added
- **German Translations**: Completed missing translations for admin interface
  - Added Settings page translations
  - Added Dashboard customization translations
  - Added Import/Export interface translations
  - Added Assignment Management translations
  - Total of 60+ new German translations for DACH region users

## [2.2.18] - 2025-08-13

### Fixed
- **PHP Parse Error**: Fixed syntax error in class-mt-enhanced-profile-importer.php line 763
  - Cleaned up whitespace around array declaration to ensure proper parsing
  - Verified array syntax is correct with proper closing brackets
  
- **Header Already Sent Warning**: Fixed header output issue in import-export.php
  - Removed export logic from template file (should be handled via admin hooks)
  - Converted to use admin-post.php actions for proper header handling
  - Cleaned up template to only display UI elements
  
- **Import Button Not Working**: Fixed non-functional import button on All Candidates page
  - Changed event handler to use event delegation for dynamically added button
  - Fixed JavaScript localization object name mismatch (mt_import vs mt_ajax)
  - Added timing delay to ensure DOM is ready before adding buttons
  - Improved script enqueuing detection for candidates page
  - Added debugging console logs to help troubleshoot button creation
  - Added validation to check if mt_ajax object is available before using it

- **Duplicate AJAX Handler Conflict**: Fixed conflicting import handlers causing nonce verification failure
  - Removed duplicate `import_candidates` handler from MT_Admin_Ajax class
  - MT_Import_Ajax::handle_candidate_import is now the sole handler for CSV imports
  - This fixes the "Security Event: Nonce verification failed" error
  
- **Improved File Type Validation**: Enhanced error messages for wrong file types
  - Added specific message for Excel files (.xlsx, .xls) directing users to convert to CSV
  - Clearer guidance on how to convert Excel files using "Save As" → CSV format

- **Fixed MT_Import_Ajax Class Loading**: Resolved 400 Bad Request error on CSV import
  - Added explicit require_once for class-mt-import-ajax.php in MT_Plugin init
  - Fixed namespace issue in self-initialization (was missing namespace prefix)
  - Added debug logging to help troubleshoot FormData contents
  - This ensures the AJAX handler is properly registered before any import attempts

- **Fixed BOM (Byte Order Mark) Handling in CSV Import**: Resolved "No candidates imported" error
  - Fixed rewind() operation that was re-reading BOM after initially skipping it
  - Added BOM detection and removal in detect_delimiter() function
  - Added explicit BOM removal from header cells during parsing
  - CSV files exported from Excel with UTF-8 BOM are now properly handled
  - Headers like "\ufeffID" are now correctly recognized as "ID"

- **Fixed Case-Sensitivity in Field Validation**: Fixed "Name field not found" validation error
  - Changed field validation from checking 'name' to 'Name' to match actual field mapping
  - Added debug messages to show mapped fields and available headers for troubleshooting
  - This fixes imports failing even when the Name column is present

## [2.2.17] - 2025-08-13

### Fixed
- **Version Numbering**: Corrected duplicate version 2.2.7 entries in changelog
  - Second 2.2.7 entry renamed to 2.2.7b to maintain chronological accuracy
  - Added clarification note about the hotfix versioning
  
- **Critical Uninstall Bug**: Implemented missing data removal functionality
  - Added comprehensive `remove_all_data()` method to MT_Uninstaller class
  - Created `uninstall.php` file that properly checks the user preference
  - Method now removes all plugin data when option is enabled:
    - All custom post types (mt_candidate, mt_jury_member)
    - All database tables (evaluations, assignments, audit logs, error logs)
    - All plugin options and settings
    - User roles and capabilities
    - Uploaded files in custom directories
    - Scheduled cron events
    - Transients and cache data
  - Preserves data by default unless explicitly opted in

### Improved
- **Assignment Removal Efficiency**: Optimized assignment removal operations
  - Added `remove_by_id()` method to MT_Assignment_Service for direct ID-based deletion
  - Eliminated unnecessary database query in removal process
  - Deprecated `remove_assignment()` method that required jury_member_id and candidate_id lookup
  - Updated MT_Assignment_Ajax to use service layer instead of direct repository access
  - Maintained all audit logging and validation in service layer

### Fixed
- **Version 2.2.15 Issues**:
  - Removed outdated `debug/import-profiles.php` file that used old MT_Profile_Importer
  - Implemented missing bulk actions `mt_assign_category` and `mt_remove_category`
  - Added JavaScript UI for category selection in bulk actions
  - Added success message handling for bulk category operations
  - Note: Column sorting was already properly implemented via `custom_orderby()` method

### Technical Details
- **Performance Improvement**: Assignment removal now uses 1 query instead of 2
  - Before: `find_all()` to get assignment, then `delete()`
  - After: `find()` to get assignment for audit, then `delete()`
- **Code Architecture**: Improved separation of concerns
  - AJAX handler delegates to service layer
  - Service layer handles business logic and audit logging
  - Repository layer handles database operations

## [2.2.16] - 2025-08-13

### Added
- **AJAX-Based CSV Import**: Implemented JavaScript-based CSV import with file picker dialog
  - Created `assets/js/candidate-import.js` with complete AJAX file upload functionality
  - File picker dialog using browser's native file selection
  - Real-time progress overlay during import process
  - Visual feedback with success/error statistics display
  - Support for up to 10MB CSV files
  - Comprehensive error reporting with row-specific details

- **Secure AJAX Import Handler**: New dedicated AJAX handler for CSV imports
  - Created `includes/ajax/class-mt-import-ajax.php` extending MT_Base_Ajax
  - Full security implementation with nonce verification and capability checks
  - Extensive file validation (type, size, MIME type)
  - Integration with MT_Logger for audit trail
  - Support for both create and update operations
  - Detailed error messages with internationalization

- **German Evaluation Criteria Parsing**: Enhanced CSV import with regex-based German text extraction
  - Added `parse_evaluation_criteria()` method for extracting evaluation fields from Description
  - Regex patterns for: Mut & Pioniergeist, Innovationsgrad, Umsetzungsstärke, Relevanz & Impact, Sichtbarkeit & Reichweite
  - Full UTF-8 support for German special characters (ä, ö, ü, ß)
  - Automatic trimming and formatting of extracted content

### Enhanced
- **Script Enqueuing System**: Improved JavaScript loading for candidates page
  - Updated both `class-mt-admin.php` and `class-mt-plugin.php` for proper script loading
  - Added localized strings for all import-related messages
  - Proper nonce generation and AJAX URL configuration
  - Conditional loading only on candidates list page

- **Field Mapping System**: Exact CSV column to meta field mapping
  - Added `get_field_mapping()` static method for consistent mapping
  - Support for German column headers: Organisation, LinkedIn-Link, Webseite
  - Proper handling of special post fields (post_title, post_content)
  - Category normalization for consistent data storage

### Technical Details
- **New Files Created**:
  - `assets/js/candidate-import.js` - AJAX import JavaScript module
  - `includes/ajax/class-mt-import-ajax.php` - AJAX handler class

- **Files Modified**:
  - `includes/admin/class-mt-admin.php` - Added script enqueuing for candidate import
  - `includes/admin/class-mt-enhanced-profile-importer.php` - Added field mapping and criteria parsing
  - `includes/core/class-mt-plugin.php` - Added MT_Import_Ajax initialization note

- **Security Features**:
  - MIME type validation for uploaded files
  - File size limits (10MB max)
  - Proper nonce verification
  - Capability checks for import permission
  - Comprehensive error logging

## [2.2.15] - 2025-08-13

### Added
- **Enhanced CSV Import System**: Complete overhaul of candidate CSV import functionality
  - Support for specific CSV format with German column headers
  - Proper handling of German special characters (ä, ö, ü, ß) with UTF-8 encoding
  - URL validation for LinkedIn, Website, and Article fields
  - Automatic protocol addition for URLs missing https://
  - Category mapping to standardized format (Startup/Gov/Tech)
  - Import ID field for unique candidate identification
  - Article URL field for "coming of age" articles
  - Full description field for detailed candidate information

- **Custom Columns for Candidates List**: New admin columns for better data visibility
  - Import ID with styled code display
  - Organization and Position fields
  - Category with color-coded icons (Startup: green/lightbulb, Gov: blue/building, Tech: red/desktop)
  - Top 50 status with checkmark indicator
  - Links column with icons for LinkedIn, Website, and Article URLs
  - All columns are sortable for better data management

- **CSV Export Improvements**: Updated export to include all new fields
  - Export includes Import ID, Category Type, Top 50 Status, Article URL
  - Maintains UTF-8 encoding with BOM for Excel compatibility
  - Properly formatted for reimport capability

### Changed
- **Menu Consolidation**: Removed duplicate "Candidates" menu item
  - Removed custom candidates page (admin.php?page=mt-candidates)
  - Now using native WordPress post type interface for candidates
  - Cleaner admin menu structure without duplicates

- **Import Dialog Enhancements**: Improved import interface
  - Updated column requirements display with exact field names
  - Added note about German character support
  - Clear indication of URL validation
  - Download sample CSV with proper format

### Technical Details
- Created `includes/admin/class-mt-candidate-columns.php` for custom columns and import
- Modified `includes/admin/class-mt-enhanced-profile-importer.php` with new column mapping
- Updated meta field mappings:
  - `_mt_candidate_id` for Import ID
  - `_mt_organization` for Organization
  - `_mt_position` for Position
  - `_mt_category_type` for Category
  - `_mt_top_50_status` for Top 50 status
  - `_mt_linkedin_url` for LinkedIn
  - `_mt_website_url` for Website
  - `_mt_article_url` for Article
  - `_mt_description_full` for Description
- Removed duplicate menu registration in `class-mt-admin.php`
- Updated sample CSV with German examples and proper column structure

## [2.2.14] - 2025-08-12

### Fixed
- **Auto-Assignment Functionality**: Resolved issue where auto-assignment failed when jury members already had existing assignments
  - Added "Clear existing assignments" checkbox option to Auto-Assignment modal
  - Allows users to either clear all assignments before reassigning or add to existing assignments
  - Fixed JavaScript to properly send the clear_existing parameter via AJAX

### Enhanced
- **Assignment Capacity**: Increased maximum candidates per jury member from 20 to 50
  - Updated form validation to accept up to 50 candidates per jury member
  - Changed default value from 5 to 10 candidates for better initial distribution
  - Supports larger evaluation pools for comprehensive jury review processes

### Technical Details
- Modified `templates/admin/assignments.php` to include clear_existing checkbox with warning message
- Updated `assets/js/admin.js` submitAutoAssignment() to capture and send clear_existing parameter
- Increased max attribute in candidates_per_jury input field from 20 to 50
- No database changes required - uses existing assignment infrastructure
- Maintains backward compatibility with existing assignments

## [2.2.7] - 2025-08-12

### Fixed
- **Evaluation Deletion Issues**: Fixed inability to delete evaluations when assignments have been modified
  - Added force delete capability to bypass constraint checks when needed
  - Implemented orphaned evaluation detection for evaluations without corresponding assignments
  - Fixed bulk delete action to use force delete for problematic evaluations

### Added
- **Orphaned Evaluation Handling**: New system to detect and clean up orphaned evaluations
  - Added `delete_orphaned_evaluations()` method to remove evaluations without assignments
  - Added `sync_with_assignments()` method to synchronize evaluation and assignment data
  - Added `force_delete()` method to bypass constraints when standard deletion fails
  - Added `can_delete()` method to check if evaluation can be safely deleted

- **Cascade Delete Options**: Assignment deletions can now optionally delete related evaluations
  - Updated `delete()` method in Assignment Repository with cascade option
  - Updated `delete_by_jury_member()` method with cascade option
  - Updated `clear_all()` method with cascade option for full cleanup

- **Database Sync Tool**: New admin tools page for database maintenance
  - Shows database health status with orphaned evaluation count
  - One-click sync to clean up orphaned evaluations
  - Visual indicators for database health (green when healthy, red when issues)
  - Quick links to related admin pages

- **Improved Bulk Actions**: Enhanced bulk evaluation actions with better error handling
  - Added bulk_evaluation_action AJAX handler
  - Support for approve, reject, reset-to-draft, and delete actions
  - Uses force delete to handle problematic evaluations
  - Better permission checks (mt_manage_evaluations or manage_options)

### Technical Details
- All new methods include proper MT_Logger integration for audit trail
- Cache clearing implemented for all affected operations
- No breaking changes - all existing functionality preserved
- Database queries optimized with proper JOIN operations
- WordPress coding standards maintained throughout

## [2.2.13] - 2025-08-12

### Added
- **Data Management Settings**: New settings section for controlling plugin data handling
  - Added 'Remove Data on Uninstall' option with comprehensive warning
  - Setting allows admins to choose whether to preserve or delete all plugin data on uninstall
  - Strong visual warnings about permanent data deletion consequences
  - Default is to preserve data (unchecked) for safety

### Improved
- **AJAX Error Handling Standardization**: Consistent error responses across all AJAX handlers
  - Replaced all direct wp_send_json_error() calls with $this->error() method
  - All AJAX errors now automatically logged through MT_Logger system
  - Standardized error message format and logging across platform
  - Better debugging capability with centralized error tracking

### Technical Details
- Settings page enhanced with Data Management section before System Information
- All AJAX handler classes (MT_Evaluation_Ajax, MT_Assignment_Ajax, MT_Admin_Ajax) now use base class error method
- Error logging includes action context, user ID, and additional debugging data
- No breaking changes - error response format remains compatible

## [2.2.12] - 2025-08-12

### Enhanced
- **Audit Logging Coverage**: Extended audit logging to cover all critical platform actions
  - Added comprehensive audit logging for bulk evaluation status changes (approve, reject, reset, delete)
  - Enhanced assignment removal to capture full context before deletion including names and user attribution
  - All evaluation status changes now tracked with previous and new status for complete traceability

### Security
- **Complete Audit Trail**: Ensured all destructive and status-changing operations are logged
  - Bulk evaluation actions now log each individual status change with full context
  - Assignment deletions capture jury member and candidate names for better readability
  - Added 'removed_by' field to track which admin performed assignment removals
  - Evaluation deletions preserve all data (score, status, participants) in audit log before removal

### Technical Details
- MT_Audit_Logger integrated into bulk_evaluation_action() in MT_Evaluation_Ajax class
- Enhanced remove_assignment() in both service layer and AJAX handler for complete coverage
- Audit logs capture entity details before deletion to maintain historical record
- All logged actions include user context via get_current_user_id()

## [2.2.11] - 2025-08-12

### Fixed
- **Database Integrity**: Verified assigned_by field is properly populated in bulk assignment operations
  - Confirmed bulk_create() method in MT_Assignment_Repository correctly sets assigned_by with current user ID
  - All bulk assignments now maintain proper audit trail with user attribution

### Refactored
- **Assignment Removal Standardization**: Consolidated duplicate assignment removal methods
  - Removed redundant delete_assignment() method from MT_Assignment_Ajax class
  - Standardized on single remove_assignment() method that accepts assignment_id parameter
  - Eliminated duplicate AJAX action registration for mt_delete_assignment
  - JavaScript already correctly calls mt_remove_assignment action with assignment_id

### Technical Details
- bulk_create() method already implements assigned_by = get_current_user_id() for each assignment
- Consolidated assignment removal logic reduces code duplication and potential maintenance issues
- No breaking changes - JavaScript interface remains unchanged
- Maintains backward compatibility with existing AJAX calls

## [2.2.10] - 2025-08-12

### Enhanced
- **Dashboard Widget Synchronization**: Completed full synchronization between main admin dashboard and dashboard widget
  - Dashboard widget now displays dynamic evaluation count from database using MT_Evaluation_Repository
  - Added "Recent Evaluations" section to widget showing last 5 evaluations with jury member, candidate, and date
  - Widget layout updated to three-column grid matching main dashboard structure
  - Both interfaces now use consistent data sources for accurate real-time statistics

### Technical Details
- Widget uses same MT_Evaluation_Repository::get_statistics() method as main dashboard
- Recent evaluations fetched using repository's find_all() method with proper limit and ordering
- Maintained consistent UI/UX with existing widget sections (Recent Candidates, Recent Jury Members)
- No database changes required - uses existing data structures

## [2.2.9] - 2025-08-12

### Enhanced
- **Jury Admin Role Definition**: Formally defined the mt_jury_admin role with specific capabilities
  - Role includes: mt_view_all_evaluations, mt_manage_assignments, mt_view_reports, mt_export_data
  - Provides intermediate access level between administrators and regular jury members
  - Enables delegation of assignment management without full admin privileges

### Fixed
- **Code Consolidation**: Removed duplicate export_assignments function from MT_Assignment_Ajax class
  - Eliminated redundancy by centralizing export functionality in MT_Admin_Ajax class
  - Prevents potential conflicts from duplicate AJAX action registrations
  - Maintains single source of truth for assignment export logic

### Technical Details
- MT_Roles class already properly defines mt_jury_admin role during plugin activation
- Capability check in clear_all_assignments already uses standardized check_permission('mt_manage_settings')
- Removed duplicate wp_ajax_mt_export_assignments action hook registration from assignment handler
- All export functionality now properly routes through the admin AJAX handler

## [2.2.8] - 2025-08-12

### Security
- **User Role and Capability System Cleanup**: Standardized user roles and capability checks for better security and consistency
  - Defined missing 'Jury Admin' (mt_jury_admin) role with appropriate capabilities: mt_view_all_evaluations, mt_manage_assignments, mt_view_reports, mt_export_data
  - Standardized all AJAX capability checks to use consistent custom capabilities instead of generic WordPress capabilities
  - Export functions now consistently use mt_export_data capability across all AJAX handlers
  - Settings and data-clearing functions now use mt_manage_settings capability
  - Assignment management functions use mt_manage_assignments capability consistently

### Enhanced
- **Role-Based Access Control**: Improved granular permission system for different user types
  - Administrators retain full access to all capabilities
  - Jury Admins can manage assignments and view reports but not modify core settings
  - Jury Members maintain evaluation submission capabilities only
  - Editors can view evaluations and reports for content management

### Technical Details
- Updated MT_Roles class to properly define and manage mt_jury_admin role during plugin activation
- Replaced direct current_user_can('manage_options') checks with role-appropriate capability checks in MT_Assignment_Ajax
- Maintained backward compatibility - existing user permissions unchanged
- All capability checks now use the base AJAX class check_permission() method for consistent error handling and logging
- No database schema changes required - uses WordPress core user/role management

## [2.2.7b] - 2025-08-12

### Fixed
- **Dashboard Widget Data Synchronization**: Synchronized evaluation data between main dashboard and dashboard widget
  - Fixed hardcoded evaluation count (was 0) in dashboard widget by implementing proper database query using MT_Evaluation_Repository
  - Added missing 'Recent Evaluations' section to dashboard widget to match main dashboard functionality
  - Updated widget layout to accommodate third column for recent evaluations
  - Both main dashboard and widget now use consistent data sources ensuring accurate statistics

### Enhanced
- **User Experience**: Dashboard widget now provides comprehensive overview matching the main dashboard
  - Widget displays accurate total evaluation count from database
  - Recent evaluations section shows last 5 evaluations with jury member → candidate format
  - Improved visual layout with three-column grid for better information density

### Technical Details
- Dashboard widget now uses MT_Evaluation_Repository::get_statistics() for consistent data retrieval
- Added MT_Evaluation_Repository::find_all() call for recent evaluations matching main dashboard implementation
- Updated CSS grid from 2-column to 3-column layout for balanced presentation
- Maintained all existing security practices and WordPress coding standards
- No database schema changes - uses existing evaluation data structure

## [2.2.6] - 2025-08-12

### Fixed
- **Assignment Management Button Functionality**: Completed JavaScript implementation for all assignment management buttons
  - Fixed non-functional "Manual Assignment", "Bulk Actions", and "Export" buttons
  - Implemented complete event handlers for all assignment management operations
  - Added missing AJAX handlers for manual assignment, bulk operations, and export functionality
  - Fixed modal interactions for auto-assign and manual assign dialogs
  - **Fixed 400 Bad Request error**: Added missing `mt_manual_assign` AJAX handler registration and implementation
  - **Fixed TypeError for bulk reassignment**: Added missing `showReassignModal`, `createReassignModal`, and `submitReassignment` methods

### Added
- **Complete Assignment Management JavaScript Module**:
  - `submitAutoAssignment()`: Handles auto-assignment form submission with AJAX
  - `submitManualAssignment()`: Processes manual candidate-to-jury assignments
  - `removeAssignment()`: Individual assignment removal with confirmation
  - `clearAllAssignments()`: Bulk clear with double confirmation for safety
  - `exportAssignments()`: CSV export via form submission
  - `toggleBulkActions()`: Show/hide bulk action interface with checkboxes
  - `applyBulkAction()`: Process bulk remove, export, or reassign operations
  - `bulkRemoveAssignments()`: Batch removal of selected assignments
  - `bulkExportAssignments()`: Export selected assignments to CSV
  - `filterAssignments()`: Real-time search filtering
  - `applyFilters()`: Advanced filtering by jury member and status
  - `showReassignModal()`: Display modal for bulk reassignment
  - `createReassignModal()`: Dynamically create reassignment modal HTML
  - `submitReassignment()`: Process bulk reassignment to new jury member

### Enhanced
- **User Experience**:
  - All buttons now provide visual feedback during processing
  - Proper loading states with disabled buttons during AJAX operations
  - Confirmation dialogs for destructive actions (remove, clear all)
  - Success/error notifications for all operations
  - Smooth animations for row removal and modal transitions

### Technical Details
- Completed partial JavaScript refactoring from v2.2.2
- Maintained module architecture with MTAssignmentManager object
- All AJAX calls use proper nonce verification
- Error handling with user-friendly messages
- No breaking changes - enhancement of existing functionality

## [2.2.5] - 2025-08-12

### Added
- **Comprehensive Audit Logging System**: Complete audit trail for platform security and compliance
  - New MT_Audit_Logger class provides centralized logging for all platform actions
  - Automatically tracks assignment creation/removal, evaluation submissions, and profile updates
  - Audit logs stored in dedicated wp_mt_audit_log database table with proper indexes
  - Full admin interface with advanced filtering, sorting, and pagination capabilities

- **Audit Log Admin Interface**: Professional audit log viewer under Mobility Trailblazers menu
  - Filter by user, action type, object type, and date range
  - Sortable columns with WordPress-standard pagination
  - JSON details viewer with collapsible format for detailed action data
  - Color-coded object type badges for visual organization
  - Restricted to admin users only (manage_options capability)

- **MT_Audit_Log_Repository**: Full-featured repository for audit log management
  - Pagination support with configurable items per page
  - Advanced filtering with multiple criteria
  - Statistics and cleanup utilities for log maintenance
  - Optimized database queries with proper JOIN operations

### Enhanced
- **Platform Security**: All critical actions now generate audit entries automatically
  - Assignment operations (create/remove) logged with complete context
  - Evaluation submissions tracked with draft/final status distinction
  - Candidate and jury member profile updates monitored
  - User context preserved for accountability

### Cleaned
- **Code Maintenance**: Removed legacy automated reminder system references
  - Cleaned wp_clear_scheduled_hook('mt_evaluation_reminder') from deactivator
  - Removed "Email automation for jury reminders" from CLAUDE.md focus areas
  - Updated documentation to remove email template references

### Technical Details
- Database schema includes new wp_mt_audit_log table with:
  - Auto-incrementing ID, user tracking, action classification
  - Object type/ID linking, JSON details storage, timestamp indexing
- Audit logging integrated at service layer for comprehensive coverage
- Repository pattern maintains separation of concerns
- WordPress coding standards throughout with proper sanitization and escaping
- No breaking changes - purely additive functionality

## [2.2.4] - 2025-08-12

### Refactored
- **Error Monitoring Architecture**: Consolidated all error monitoring functionality into dedicated MT_Error_Monitor class
  - Moved all error monitoring methods from MT_Admin class to MT_Error_Monitor class for better separation of concerns
  - Fixed menu registration to use correct parent slug 'mobility-trailblazers' instead of 'mt-dashboard'
  - Removed duplicate error monitoring code and AJAX handlers from MT_Admin class
  - Updated MT_Plugin initialization to properly instantiate MT_Error_Monitor for admin users
  - Maintained all existing functionality while improving code organization

### Cleaned
- **Code Organization**: Removed unused debug file jury-import-simple.php (corrupted placeholder)
- **Single Responsibility**: Error monitoring now has its own dedicated class following plugin architecture patterns
- **Maintainability**: Easier to maintain and extend error monitoring features with consolidated codebase

### Technical Details
- MT_Error_Monitor class now handles:
  - Admin menu registration under 'mobility-trailblazers' parent
  - All AJAX actions: mt_clear_error_logs, mt_export_error_logs, mt_get_error_stats
  - Scheduled cleanup event registration and handling (mt_cleanup_error_logs)
  - Error statistics and reporting methods
- MT_Admin class cleaned of all error monitoring responsibilities
- Plugin initialization updated to include error monitor for users with 'manage_options' capability
- No functional changes - purely architectural improvement

## [2.2.3] - 2025-08-12

### Fixed
- **Frontend JavaScript Scope Issue**: Fixed "getI18nText is not defined" error in Jury Rankings Table
  - Made `getI18nText` function globally accessible by attaching it to the window object
  - Resolved scope issue between multiple IIFE closures in frontend.js
  - Fixed vote modification arrows functionality in the evaluation table
  - Maintained backward compatibility with all existing code

### Technical Details
- The issue occurred because `getI18nText` was defined inside the first IIFE closure and wasn't accessible to the second IIFE that handles Jury Rankings Table interactions
- Solution: Attached the function to `window.getI18nText` making it globally available while keeping a local reference for the first closure
- Affected functionality: Vote adjustment arrows in the jury evaluation table were throwing console errors when clicked
- No functionality changes - only scope accessibility fix

## [2.2.2] - 2025-08-11

### Refactored
- **Admin JavaScript Module Architecture**: Restructured `assets/js/admin.js` for better maintainability and performance
  - Encapsulated assignment-specific functionality into dedicated `MTAssignmentManager` object
  - Implemented conditional loading - assignment modules only initialize on Assignment Management page
  - Consolidated multiple `$(document).ready()` calls into single main initialization
  - Separated general utilities from page-specific modules

### Improved  
- **Code Organization**:
  - Assignment Management logic now fully contained in `MTAssignmentManager` object with single `init()` entry point
  - Bulk Operations logic encapsulated in `MTBulkOperations` object
  - General utilities (tooltips, modals, tabs) remain globally available for all admin pages
  - Clear separation of concerns between different functional areas

- **Performance**:
  - Reduced memory footprint by only loading assignment-specific code when needed
  - Eliminated potential conflicts from global scope pollution
  - Faster page loads on non-assignment admin pages

- **Maintainability**:
  - Single source of truth for assignment page detection logic
  - Easier debugging with modular structure
  - Better code reusability and testability
  - Consistent initialization pattern across all modules

### Technical Details
- Assignment page detection checks for multiple indicators:
  - Presence of `#mt-auto-assign-btn` button
  - Existence of `.mt-assignments-table` element  
  - `.mt-assignment-management` wrapper class
  - Body class `mobility-trailblazers_page_mt-assignment-management`
  - URL containing "mt-assignment-management"
- No functionality changes - pure refactoring for code quality
- Maintains backward compatibility with all existing features
- Preserves all event bindings and AJAX interactions

## [2.2.1] - 2025-08-11

### Fixed
- **Auto-Assignment Algorithm Refactoring**: Complete rewrite of the auto-assignment functionality in `class-mt-assignment-ajax.php`
  - Fixed "Balanced" distribution logic to ensure fair and even distribution of candidates across jury members
  - Fixed "Random" distribution to be truly random and more efficient
  - Improved performance by eliminating redundant shuffling operations

### Changed
- **Balanced Distribution Method**:
  - Now tracks assignment counts for each candidate to ensure even review coverage
  - Prioritizes candidates with fewer existing assignments
  - Each jury member receives exactly the specified number of candidates
  - Ensures all candidates get roughly equal number of reviews

- **Random Distribution Method**:
  - Implements true randomization by shuffling candidate list once at the beginning
  - Each jury member randomly selects from the pre-shuffled list
  - Significantly improved performance (O(n) instead of O(n²))
  - Properly respects the candidates_per_jury limit

### Improved
- **Edge Case Handling**:
  - Better handling of scenarios with insufficient candidates
  - Proper tracking of existing assignments when not clearing
  - Clear warning messages when jury members cannot receive full allocation
  
- **Debugging and Logging**:
  - Added detailed logging at key decision points
  - Logs distribution method, candidate/jury counts, and assignment progress
  - Warning logs for edge cases and insufficient candidates
  - Final statistics logging for troubleshooting

### Technical Details
- Maintained all existing security checks (nonce verification, capability checks)
- Preserved backward compatibility with existing AJAX endpoints
- No database schema changes required
- Code follows WordPress coding standards and plugin conventions
- Replaced direct SQL queries with repository methods for better maintainability

## [2.2.0] - 2025-08-01

### Added
- Enhanced CSV Import System with intelligent field mapping
- Bilingual Support for English and German CSV headers
- Import Validation with dry-run mode and duplicate detection
- CSV Formatter Tool for data preparation

## Previous Versions
See README.md for earlier version history
