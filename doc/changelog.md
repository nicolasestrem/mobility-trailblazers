# Mobility Trailblazers Changelog

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

## [2.2.7] - 2025-08-12

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
