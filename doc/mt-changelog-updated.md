# Mobility Trailblazers - Changelog

All notable changes to the Mobility Trailblazers plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.6] - 2025-06-23

### Fixed
- **Assignment Management Page**: Completely restored all non-functional buttons and features
  - **Auto-Assignment**: Fixed both balanced and random assignment methods
  - **Clear All**: Fixed nonce verification and added double confirmation
  - **Manual Assignment**: Fixed array parameter handling for bulk assignments
  - **Export to CSV**: Fixed fatal errors and missing repository methods
  - **AJAX Handler Registration**: Fixed handlers only loading during AJAX requests
  - **Progress Display**: Now correctly shows evaluation completion percentage

### Technical Details
- **AJAX Registration Fix**: Removed conditional `wp_doing_ajax()` check in plugin initialization
- **Post Type Correction**: Changed from `mt_jury` to `mt_jury_member` in queries
- **Array Parameter Fix**: Updated `get_array_param()` to handle arrays without sanitization
- **Repository Methods**: Added missing `get_by_jury_and_candidate()` method
- **Nonce Standardization**: All AJAX endpoints now use `mt_admin_nonce`
- **Permission Checks**: Standardized to use `manage_options` for admin functions

### Files Modified
- `includes/core/class-mt-plugin.php` - Always initialize AJAX handlers
- `includes/ajax/class-mt-assignment-ajax.php` - Complete overhaul of all methods
- `includes/ajax/class-mt-base-ajax.php` - Fixed array parameter handling
- `includes/repositories/class-mt-assignment-repository.php` - Added missing methods
- `templates/admin/assignments.php` - Updated UI descriptions
- `doc/assignment-management-fixes.md` - Comprehensive documentation created

### Impact
- All assignment management features now fully functional
- Administrators can efficiently manage jury-candidate assignments
- Export functionality provides complete assignment data with evaluation status
- Auto-assignment respects configured limits and distribution methods

## [2.0.5] - 2025-06-22

### Fixed
- **Jury Evaluation Form Submission**: Fixed "You do not have permission to evaluate this candidate" errors
  - **AJAX Response Data Structure**: Fixed nested data structure issue where candidate data was under `response.data.data` instead of `response.data`
  - **Form Data Collection**: Replaced `serializeArray()` with manual field collection to ensure all form fields are included
  - **Form Selection**: Implemented robust form selection using multiple selectors (`#mt-evaluation-form`, `.mt-evaluation-form`) to handle dynamically created forms
  - **Hidden Input Field**: Fixed candidate_id hidden input not being included in form submission
  - **Score Fields**: Ensured all evaluation score fields (courage_score, innovation_score, etc.) are properly collected and submitted
  - **Comments Field**: Fixed textarea comments field not being included in submission

### Technical Details
- **Data Structure Fix**: JavaScript now correctly accesses candidate data from `response.data.data` instead of `response.data`
- **Form Field Collection**: Manual iteration through all form fields (`input`, `textarea`, `select`) ensures no fields are missed
- **Form Selection Strategy**: Multiple fallback selectors prevent form not found issues with dynamically generated content
- **Debug Logging**: Added comprehensive console logging to track form data collection and submission process
- **Error Prevention**: Form validation now works correctly with proper field collection

### Files Modified
- `assets/js/frontend.js` - Complete form submission overhaul with robust field collection
- `includes/ajax/class-mt-evaluation-ajax.php` - Added debugging for POST data analysis

### Impact
- Jury members can now successfully submit evaluations without permission errors
- All form fields (scores, comments, candidate_id) are properly included in submissions
- Draft saving functionality works correctly
- Form submission is now reliable and consistent

## [2.0.4] - 2025-06-21

### Fixed
- **jQuery Event Binding Errors**: Fixed "Cannot create property 'guid' on string" errors
  - Resolved incorrect jQuery event handler binding in frontend JavaScript
  - Fixed event handlers being passed as string references instead of function references
  - Added proper context binding using `self.call()` to maintain correct scope
  - Resolves jQuery migration warnings and prevents JavaScript errors on evaluation form
  
- **Nonce Security Verification**: Fixed "Security check failed" errors on evaluation submission
  - Corrected nonce mismatch between JavaScript and server-side verification
  - Changed JavaScript to use `mt_ajax.nonce` instead of form field `mt_nonce`
  - Ensured consistent nonce name usage (`mt_ajax_nonce`) across all AJAX calls
  - Added safety checks to prevent undefined nonce errors
  
- **Missing Event Handlers**: Added missing `loadEvaluation` method
  - Implemented `loadEvaluation` method to handle evaluate button clicks
  - Fixed event handler reference that was causing JavaScript errors
  - Properly handles candidate ID extraction and form loading
  
- **CSS Selector Mismatches**: Fixed incorrect CSS selectors in JavaScript
  - Updated selectors to match actual HTML structure from templates
  - Changed `.mt-criterion` to `.mt-criterion-card` for proper element targeting
  - Fixed score slider and mark click handlers to use correct parent elements
  - Ensured character count functionality targets correct textarea ID

### Technical Details
- **Event Binding**: All jQuery event handlers now properly maintain context
- **Nonce Flow**: Shortcode → `mt_ajax.nonce` → AJAX handler → `wp_verify_nonce()`
- **Safety Checks**: Added `typeof mt_ajax === 'undefined'` checks in all AJAX functions
- **Form Structure**: Dynamically generated forms now match template structure
- **Error Handling**: Improved error messages for configuration issues

### Files Modified
- `assets/js/frontend.js` - Complete event binding and nonce handling overhaul

## [2.0.3] - 2025-06-21

### Fixed
- **Missing Admin JavaScript**: Fixed 404 error for admin.js file
  - Created missing `assets/js/admin.js` file that was referenced by the main plugin class
  - Updated AJAX URL reference from `mt_admin.ajaxUrl` to `mt_admin.url` to match localization
  - Updated error message reference from `mt_admin.i18n.error` to `mt_admin.strings.error`
  - Resolves "GET http://192.168.1.7:9989/wp-content/plugins/mobility-trailblazers/assets/js/admin.js?ver=2.0.2 net::ERR_ABORTED 404 (Not Found)" error
  - Restores functionality for manual candidate assignment to jury members on mt-assignment page

### Technical Details
- **File Created**: `assets/js/admin.js` (11KB, 333 lines)
- **Features Restored**: Tooltips, tabs, modals, confirmations, AJAX forms, utility functions
- **Localization**: Properly configured to work with `mt_admin` object from PHP
- **Compatibility**: Maintains all existing functionality from archive version

## [2.0.2] - 2025-06-21

### Fixed
- **Assignment Service**: Fixed missing `auto_assign()` method
  - Added `auto_assign()` method to `MT_Assignment_Service` class
  - Resolves fatal error when accessing assignments admin page
  - Method properly handles balanced and random assignment methods
- **Database Column Errors**: Fixed "Unknown column 'assigned_at'" errors
  - Made `find_all()` method robust to handle missing columns gracefully
  - Falls back to `id` ordering when `assigned_at` column doesn't exist
  - Added force database upgrade option in diagnostics page
  - Improved error handling for database schema mismatches

## [2.0.1] - 2025-06-21

### Added
- **Complete Jury Dashboard System**: Modern, responsive interface for jury members
  - Beautiful evaluation form with real-time score tracking
  - Visual progress indicators showing completion status
  - Advanced search and filtering for assigned candidates
  - Draft saving with auto-save functionality
  - Character count for comment fields
  - Score sliders with visual feedback
  - Total score calculation in real-time
  - Mobile-optimized responsive design
  
- **Enhanced Shortcode System**: Comprehensive shortcodes with full parameter support
  - `[mt_jury_dashboard]` - Full-featured jury member interface
  - `[mt_candidates_grid]` - Public candidate display with customization
  - `[mt_evaluation_stats]` - Statistics visualization for admins
  - `[mt_winners_display]` - Winners showcase with ranking
  
- **Frontend Templates**: New template system for all shortcodes
  - `jury-evaluation-form.php` - Comprehensive evaluation interface
  - `candidates-grid.php` - Flexible candidate grid display
  - `winners-display.php` - Award winners presentation
  - `evaluation-stats.php` - Statistics and analytics display
  
- **Enhanced AJAX Handlers**: Improved evaluation submission
  - Combined draft and final submission endpoint
  - Better error handling and validation
  - Real-time feedback for user actions
  
- **Diagnostics Page**: Comprehensive debugging interface for administrators and jury admins
  - System information display (PHP version, WordPress version, memory limits)
  - Database table verification with row counts
  - Post types and taxonomies registration status
  - User roles and capabilities checker
  - Plugin settings overview
  - AJAX endpoints documentation
  - Recent activity monitoring
  - Error log viewer (filters plugin-specific errors)
  - Quick tests for database operations, AJAX calls, and permissions
  - Export diagnostic data for support

### Fixed
- **Autoloader Interface Loading**: Fixed fatal error when loading interface files
  - Updated autoloader to properly handle `_Interface` suffix in class names
  - Now correctly maps interface classes to `interface-*.php` files
  - Resolves "Interface not found" errors that prevented admin dashboard from loading
- **PHP Deprecated Warnings**: Fixed deprecated warnings for null values in round() function
  - Added null checks in evaluation statistics calculation
  - Ensures compatibility with PHP 8.1+
- **Missing Admin Templates**: Created missing admin template files
  - Added `evaluations.php` template for evaluations management
  - Added `assignments.php` template for jury assignments
  - Added `import-export.php` template for data import/export
  - Added `settings.php` template for plugin settings
- **Data Management**: Added AJAX handler for clearing data
  - Implemented `mt_clear_data` action for clearing evaluations and assignments
  - Added proper permission checks and nonce verification
- **Version Constant**: Updated MT_VERSION constant to match plugin version
- **Database Schema Issues**: Fixed missing columns in existing tables
  - Added automatic database upgrade system
  - Checks and adds missing `comments` column to evaluations table
  - Checks and adds missing `assigned_at` and `assigned_by` columns to assignments table
  - Adds missing indexes for better performance
  - Database upgrades run automatically on plugin initialization
- **Assignment Service**: Fixed missing `auto_assign()` method
  - Added `auto_assign()` method to `MT_Assignment_Service` class
  - Resolves fatal error when accessing assignments admin page
  - Method properly handles balanced and random assignment methods
- **Database Column Errors**: Fixed "Unknown column 'assigned_at'" errors
  - Made `find_all()` method robust to handle missing columns gracefully
  - Falls back to `id` ordering when `assigned_at` column doesn't exist
  - Added force database upgrade option in diagnostics page
  - Improved error handling for database schema mismatches

### Technical Details
- Fixed regex pattern in `MT_Autoloader::autoload()` to be case-insensitive
- Interface files now load correctly: `MT_Repository_Interface` → `interface-mt-repository.php`
- Added null coalescing operators in `get_statistics()` method to prevent deprecated warnings
- All admin pages now render correctly without template not found errors
- Created `MT_Database_Upgrade` class to handle schema migrations
- Added database operation buttons to diagnostics page for maintenance tasks

## [2.0.0] - 2024-01-21

### 🎉 Major Release - Complete Rebuild

This version represents a complete architectural rebuild of the Mobility Trailblazers plugin, focusing on modern development practices, improved performance, and enhanced maintainability.

### Added
- **Modern Architecture**
  - PSR-4 autoloading with proper namespaces
  - Repository pattern for data access layer
  - Service layer for business logic
  - Clean separation of concerns
  
- **Enhanced Jury System**
  - Beautiful, responsive jury dashboard
  - Real-time search and filtering
  - Progress tracking with visual indicators
  - Draft support for evaluations
  - Mobile-optimized interface
  
- **Improved Admin Interface**
  - Streamlined dashboard with key metrics
  - Better assignment management tools
  - Auto-assignment with balanced distribution
  - Enhanced import/export functionality
  
- **Developer Features**
  - Comprehensive hook system
  - Well-documented codebase
  - Extensive inline documentation
  - Clear architectural patterns
  
- **Security Enhancements**
  - Improved nonce verification
  - Better capability checks
  - Enhanced data sanitization
  - Prepared statements for all queries

### Changed
- **Complete Codebase Rewrite**
  - Migrated from procedural to OOP approach
  - Implemented SOLID principles
  - Updated to PHP 7.4+ standards
  - Modern JavaScript with ES6+
  
- **Database Structure**
  - Optimized table schemas
  - Better indexing for performance
  - Cleaner data relationships
  
- **User Interface**
  - Complete UI overhaul
  - Modern, clean design
  - Improved accessibility
  - Better responsive behavior

### Removed
- **Voting System** - All public voting functionality removed
  - Vote tracking
  - Vote forms and shortcodes
  - Vote-related database tables
  - Vote reset functionality
  
- **Elementor Integration** - Complete removal
  - All Elementor widgets
  - Elementor-specific code
  - Webpack compatibility fixes
  
- **Legacy Code**
  - Old procedural functions
  - Deprecated features
  - Unused database tables
  - Legacy compatibility code

### Fixed
- All known bugs from version 1.x
- Performance issues with large datasets
- Memory leaks in evaluation processing
- AJAX endpoint conflicts
- Database query inefficiencies

### Security
- Fixed potential SQL injection vulnerabilities
- Improved authentication checks
- Enhanced data validation
- Better error handling

### Technical Details
- **PHP Version**: 7.4+ required (previously 5.6)
- **WordPress Version**: 5.8+ required (previously 4.9)
- **Database Changes**: New optimized schema
- **Dependencies**: Removed all external dependencies

## [1.0.12] - 2024-01-15 [DEPRECATED]

### Changed
- Last version before complete rebuild
- Various bug fixes and patches

### Deprecated
- This version is no longer supported
- Users should upgrade to 2.0.0

## Migration Guide

### From 1.x to 2.0.0

**⚠️ Important**: Version 2.0.0 is a major release with breaking changes. Please backup your database before upgrading.

#### Pre-Migration Steps
1. **Backup your database**
2. **Export any important data** using the old export functionality
3. **Document your current settings**

#### Migration Process
1. **Deactivate** the old plugin version
2. **Delete** the old plugin files (data will be preserved)
3. **Upload** the new plugin version
4. **Activate** the plugin
5. **Run** the migration tool (if applicable)

#### Post-Migration Steps
1. **Verify** all candidates and jury members are intact
2. **Reconfigure** settings as needed
3. **Test** evaluation functionality
4. **Update** any custom code using plugin hooks

#### Breaking Changes
- All Elementor widgets removed - replace with shortcodes
- Voting functionality removed - no replacement
- Some hooks renamed - check developer guide
- Database schema changes - automatic migration on activation

#### Data Migration
- Candidates: Automatically migrated
- Jury Members: Automatically migrated
- Evaluations: Preserved with new schema
- Assignments: Recreate if needed
- Votes: Permanently removed

### Support

For migration assistance or issues:
1. Check the documentation
2. Review error logs
3. Contact support team

## Version Numbering

This project uses Semantic Versioning:
- **Major** (X.0.0): Breaking changes
- **Minor** (0.X.0): New features, backwards compatible
- **Patch** (0.0.X): Bug fixes, backwards compatible

## Roadmap

### Planned for 2.1.0
- Email notification system
- Advanced reporting features
- Bulk evaluation tools
- API endpoints

### Planned for 2.2.0
- Multi-language evaluation forms
- Advanced statistics dashboard
- Custom evaluation criteria builder
- Integration with third-party services

### Long-term Goals
- Machine learning for evaluation insights
- Real-time collaboration features
- Advanced analytics and predictions
- Mobile app companion

---

For more information, see the [README](README.md) and [Developer Guide](mt-developer-guide.md). 