# Mobility Trailblazers Changelog

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
