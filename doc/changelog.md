# Mobility Trailblazers Changelog

> **Note**: Version 2.2.7b represents a hotfix that was deployed on the same day as 2.2.7. The duplicate version number has been corrected with the 'b' suffix to maintain chronological accuracy.

## [2.4.1] - 2025-08-16 - Jury Grid Display Fix & Interactive Cards

### Fixed
- **Jury Grid Inconsistent Sizing**: Resolved issue where jury member cards had variable sizes based on content length
  - Implemented fixed minimum height (320px) for all grid items
  - Set consistent image container dimensions (150x150px)
  - Added text truncation for long names and organizations using CSS line-clamp
  - Proper flexbox layout ensures vertical alignment consistency

- **Missing Click Functionality**: Added interactivity to jury member cards
  - Wrapped entire card content in anchor tags linking to individual profiles
  - Cards now navigate to `/candidate/[name]/` URLs
  - Maintained semantic HTML structure

### Added
- **Visual Hover Effects**: Enhanced user experience with interactive feedback
  - Card lift animation with shadow on hover
  - Image scales to 1.05x on hover
  - Name color changes to accent color (#C1693C)
  - "View Profile" button appears on hover with smooth opacity transition
  - Cursor changes to pointer to indicate clickability

- **Responsive Grid Breakpoints**: Proper mobile and tablet support
  - 1400px: 5 columns → 4 columns
  - 1200px: 4 columns → 3 columns
  - 992px: 3 columns → 2 columns
  - 768px: 2 columns with reduced card dimensions
  - 480px: Single column layout

### Enhanced
- **CSS Architecture**: Comprehensive styling improvements
  - Added 255 lines of grid standardization CSS
  - Added 72 lines of clickability and hover effect styles
  - Used CSS Grid with minmax() for flexible responsive columns
  - Implemented CSS custom properties for consistent theming

### Files Modified
- `assets/css/frontend.css` - Added jury grid fixes and clickability styles
- `templates/frontend/candidates-grid.php` - Added link wrapper and data attributes

### Technical Details
- Used `object-fit: cover` for consistent image display
- Implemented `-webkit-line-clamp` for multi-line text truncation
- Added `flex-shrink: 0` to prevent image distortion
- Used `!important` flags to override theme conflicts
- Maintained WordPress coding standards throughout

## [2.4.0] - 2025-01-16 - Complete Photo Management & Enhanced UI Templates

### Added
- **Comprehensive Photo Attachment System**
  - Direct photo attachment script (`direct-photo-attach-complete.php`) with ID-based mapping for all 52 candidates
  - Enhanced photo matching script (`match-photos-updated.php`) with improved name variation handling
  - Photo verification tool (`verify-photo-matching.php`) for status reporting and missing photo detection
  - Support for Günther Schuh special case (ID: 4444)

- **Enhanced Candidates Grid Template** (`candidates-grid-enhanced.php`)
  - Modern card-based layout with 320px minimum width
  - Live search functionality with debounce (300ms)
  - Category filtering with smooth animations
  - Social media quick links (LinkedIn, Website)
  - Hover effects with image scaling (1.1x)
  - No results state with helpful messaging
  - Responsive breakpoints (768px, 480px)

- **Professional Jury Member Template** (`single-mt_jury.php`)
  - Circular profile photos (200px) with badge overlay
  - Real-time evaluation statistics dashboard
  - Expertise tags display system
  - Activity metrics (assigned, submitted, drafts, average score)
  - Gradient hero section with floating pattern animation

- **Interactive JavaScript Features** (`candidate-interactions.js`)
  - Intersection Observer for lazy loading images
  - Quick view modal system with AJAX loading
  - Keyboard navigation support (arrow keys, enter)
  - Search result highlighting with mark tags
  - Smooth scroll animations (800ms duration)
  - Sort functionality by name and date
  - Staggered card animations on filter

- **Criteria Parsing Tool** (`tools/parse-criteria.php`)
  - Automated extraction of evaluation criteria into structured fields
  - Support for multiple text formats and variations
  - Individual meta fields for each criterion (_mt_criterion_mut, etc.)
  - Verification mode for quality checking
  - Batch processing for all candidates

### Enhanced
- **Candidate Profile Template** (already enhanced in previous version)
  - Confirmed working with gradient backgrounds
  - Criteria cards with custom icons and colors
  - Responsive sidebar layout
  - Navigation between candidates

### Technical Improvements
- **Photo Management**
  - Handles special characters in names (umlauts, hyphens)
  - Automatic WordPress media library integration
  - Alt text generation for accessibility
  - Duplicate prevention checks

- **Performance Optimizations**
  - Debounced search inputs
  - Staggered animations to prevent jank
  - CSS Grid with auto-fill for responsive layouts
  - Optimized selector queries in JavaScript

### Files Created/Modified
- `/direct-photo-attach-complete.php` - Complete photo attachment with all candidates
- `/verify-photo-matching.php` - Photo status verification tool
- `/match-photos-updated.php` - Enhanced matching logic
- `/templates/frontend/candidates-grid-enhanced.php` - Modern grid layout
- `/templates/frontend/single/single-mt_jury.php` - Jury member profile
- `/assets/js/candidate-interactions.js` - Interactive features
- `/tools/parse-criteria.php` - Criteria parsing utility

## [2.3.6] - 2025-01-18 - Candidate Photo Integration & Modern UI Overhaul

### Added
- **Photo Management System**: Complete photo integration solution
  - Photo matching script (`match-photos.php`) for bulk photo-to-candidate association
  - Support for 50+ WebP format photos in `/Photos_candidates/webp/`
  - Automatic WordPress media library upload and featured image assignment
  - Handles name variations (Dr., Prof., umlauts) in matching algorithm

- **Enhanced Candidate Profile Pages**: Complete visual redesign
  - Hero section with gradient backgrounds and animated patterns
  - Floating photo frames with hover effects (280x280px)
  - Structured evaluation criteria cards with custom icons
  - Sidebar with quick facts, social links, and jury evaluation CTAs
  - Responsive two-column layout with mobile optimization

- **Modern Candidates Grid**: Card-based listing interface
  - Interactive category filtering with JavaScript
  - Hover animations and scale effects on cards
  - Social media quick links (LinkedIn, Website)
  - AJAX-ready "Load More" pagination
  - Responsive columns (1-4 based on viewport)

- **Jury Member Profile Template**: New dedicated template
  - Circular profile photo presentation
  - Biography and expertise sections
  - Evaluation activity statistics
  - Professional gradient header design

### Enhanced
- **Visual Design System**: Comprehensive UI upgrade
  - Color palette: Primary gradient (#3b82f6 to #1e3a8a)
  - Modern typography scale (2.5-3rem headings, 1.05rem body)
  - Consistent border radius (16-20px cards, 8-12px buttons)
  - Multi-layer shadows for depth perception
  - GPU-accelerated animations (0.3s ease transitions)

- **Information Architecture**: Improved content organization
  - Clear section separation with visual headers
  - Icon-based navigation cues
  - Progressive disclosure of evaluation criteria
  - Contextual CTAs based on user role

### Technical
- **Performance Optimizations**:
  - WebP format for optimal image compression
  - Lazy loading preparation in templates
  - CSS animations using transform/opacity for GPU acceleration
  - Minimal JavaScript for filtering operations

- **Database Integration**:
  - Uses existing post meta fields (no schema changes)
  - Featured image support for all candidate posts
  - Category taxonomy for filtering

### Documentation
- Created comprehensive photo integration guide (`doc/candidate-photo-integration.md`)
- Detailed template enhancement documentation
- Visual design system specifications
- Implementation instructions for administrators

## [2.3.5] - 2025-08-16 - Delete All Candidates Feature

### Added
- **Delete All Candidates Button**: Added to Debug Center Database Tools tab
  - Located in Database Operations section for easy access
  - Styled as a danger button (red) to indicate destructive action
  - Requires typing "DELETE" for confirmation to prevent accidental deletion
  - Uses database transactions for safe, atomic deletion

- **AJAX Handler** (`mt_delete_all_candidates`):
  - Verifies admin permissions and nonce for security
  - Double confirmation required (button click + type "DELETE")
  - Deletes all candidates with associated data (evaluations, assignments, meta)
  - Full transaction support with rollback on error
  - Comprehensive logging for audit trail

- **JavaScript Support**:
  - Interactive confirmation prompt
  - Progress indication during deletion
  - Success/error notifications
  - Console logging of deletion counts

### Security
- Multiple confirmation layers to prevent accidental data loss
- Admin-only access restriction
- Transaction-based deletion ensures data integrity

## [2.3.4] - 2025-08-16 - Excel Import Support

### Added
- **Excel to CSV Conversion Tools**: Complete solution for importing Excel candidate lists
  - Browser-based converter (`tools/excel-to-csv-converter.html`) with drag-and-drop interface
  - PHP script converter (`tools/excel-to-csv-converter.php`) for server-side processing
  - Visual preview with statistics showing total candidates, Top 50 count, and categories
  - Real-time conversion with instant CSV download

- **Category Mapping System**: Automatic translation of German categories to platform standards
  - "Governance & Verwaltungen, Politik, öffentliche Unternehmen" → Gov
  - "Etablierte Unternehmen" → Tech
  - "Start-ups, Scale-ups & Katalysatoren" → Startup
  - "Start-ups & Scale-ups" → Startup

- **Data Processing Features**:
  - UTF-8 encoding with BOM for Excel compatibility
  - Automatic URL validation and https:// prefixing
  - Top 50 status normalization (Ja/Nein)
  - Support for 51 candidates from Excel sheet "Kandidaten"
  - Handles special German characters (ä, ö, ü, ß)

### Documentation
- **Excel Import Guide** (`doc/excel-import-guide.md`): Comprehensive documentation
  - Expected Excel format and structure
  - Column mapping specifications
  - Step-by-step conversion process
  - Import troubleshooting guide
  - Sample data structures

## [2.3.3] - 2025-08-14 - Diagnostic System Fix

### Fixed
- **Run Diagnostic Button**: Fixed non-functional diagnostic execution
  - Corrected button selector logic in JavaScript for form submission
  - Implemented dynamic results display instead of page reload
  - Added comprehensive diagnostic results rendering with status indicators

### Enhanced
- **Diagnostic Display**:
  - Added dynamic DOM updating for diagnostic results
  - Implemented color-coded status indicators (success/warning/error)
  - Added formatted display for all diagnostic data types
  - Included timestamp and auto-scroll to results

## [2.3.2] - 2025-08-14 - Debug Center Complete Fixes

### Fixed
- **Debug Script Output Display**: Fixed empty info box issue when executing debug scripts
  - Fixed AJAX response structure - removed double wrapping of data
  - Changed from escaped HTML in `<pre>` tag to rendered HTML in `<div>` container
  - Added proper CSS styling for script output formatting
  - Script output now properly displays formatted results instead of raw HTML code

- **Maintenance Operations**: Fixed "Operation not found" error
  - Corrected template structure to access operations at correct path
  - Fixed cache operations: `$operations['cache']['operations']` instead of `$operations['cache']`
  - Fixed reset operations: `$operations['reset']['operations']` instead of `$operations['reset']`

### Enhanced
- **UI Improvements**: 
  - Added dedicated `.mt-script-output` CSS class with proper styling
  - Improved readability with appropriate typography and spacing
  - Added scrollable container for long output
  
- **Code Quality**:
  - Removed double data wrapping in AJAX responses
  - Standardized response structure across all Debug Center operations

## [2.3.1] - 2025-08-14 - Debug Center Bug Fixes and Stability

### Fixed
- **Runtime Errors Resolution**: Complete fix of all Debug Center runtime errors
  - Fixed MT_Debug_Ajax missing init() method implementation
  - Resolved parent::__construct() call on non-existent parent constructor
  - Fixed JavaScript undefined method bindings (viewScript, confirmOperation, analyzeTable, optimizeTable)
  - Resolved all PHP array access warnings with defensive programming
  - Fixed MT_Error_Monitor private method visibility issues
  - Corrected MT_Logger private method calls to use public API methods
  - Fixed array to string conversions in tab templates

- **Database Issues**:
  - Fixed incorrect table name references (wp_mt_assignments → wp_mt_jury_assignments)
  - Added proper error handling for database connection failures
  - Improved resilience when database is unavailable

- **Debug Script Execution**:
  - Removed unnecessary wp-load.php and wp-config.php requires (scripts run in AJAX context)
  - Fixed undefined variable $script_name in MT_Debug_Manager
  - Corrected operation key mismatches in MT_Maintenance_Tools

- **Template Improvements**:
  - Created proper templates for migrate-profiles and generate-samples pages
  - Added comprehensive array validation throughout all tab templates
  - Fixed jQuery UI tooltip dependency (made optional)

### Enhanced
- **Error Handling**: All Debug Center components now handle missing data gracefully
- **Code Quality**: Applied defensive programming patterns throughout
- **Compatibility**: Debug Center works even with partial database connectivity

### Technical Details
- Fixed 15+ distinct runtime errors across multiple components
- Improved error resilience with proper fallback values
- Enhanced array validation to prevent type errors
- Corrected all method visibility issues for proper encapsulation

## [2.3.0] - 2025-08-14 - Debug Center Complete Implementation

### Added
- **Unified Debug Center**: Professional developer tools interface with 6 complete tabs
  - Centralized access point for all debugging and diagnostic tools
  - Environment-aware security controls (Development/Staging/Production)
  - Comprehensive tabbed interface for organized tool access:
    - **Diagnostics Tab**: Real-time system health monitoring and performance metrics
    - **Database Tab**: Table optimization, fragmentation analysis, slow query detection
    - **Scripts Tab**: Categorized debug script execution with audit logging
    - **Errors Tab**: Error log monitoring, filtering, and management
    - **Tools Tab**: Maintenance operations, cache management, scheduled tasks
    - **Info Tab**: Complete system information export for support
  - Secure debug script execution with audit logging
  - Comprehensive maintenance tools for database and cache operations

- **New Service Classes**:
  - `MT_Diagnostic_Service`: Comprehensive system health checks (Singleton pattern)
    - Environment detection and information
    - WordPress health monitoring
    - Database integrity verification
    - Plugin component validation
    - Filesystem health checks
    - Performance metrics collection
    - Security status assessment
    - Error log analysis
  - `MT_Debug_Manager`: Secure debug script management
    - Environment-based script filtering
    - Script categorization and registry
    - Execution audit logging with IP tracking
    - Dangerous operation protection
  - `MT_Maintenance_Tools`: System maintenance operations
    - Database optimization and repair
    - Orphaned data cleanup
    - Cache management
    - Data export/import utilities
    - Factory reset capability with password verification

- **Utility Classes**:
  - `MT_Database_Health`: Database monitoring and analysis
    - Table health checks with fragmentation detection
    - Slow query identification
    - Database statistics and metrics
  - `MT_System_Info`: System information gathering
    - PHP, WordPress, Server, Database details
    - Plugin and theme information
    - Export as text functionality

### Changed
- **Debug Script Organization**: Complete restructuring
  - Scripts organized into categories: generators, migrations, diagnostics, repairs, deprecated
  - New registry.json for script metadata and environment controls
  - Deprecated scripts moved to separate directory with clear warnings
  - Enhanced security for production environment

### Technical Improvements
- Introduced environment detection (MT_ENVIRONMENT constant support)
- Added comprehensive audit logging for all debug operations
- Implemented role-based access control for debug tools
- Created modular tab-based template system for Debug Center
- Added JSON export capability for diagnostic results
- Complete JavaScript module (MTDebugCenter) for interactive functionality
- Professional CSS styling with environment badges and responsive design

### Security Enhancements
- Production environment restrictions for dangerous operations
- Required confirmations for destructive operations
- Password verification for factory reset
- IP-based audit logging for all debug script executions
- Nonce verification for all debug operations
- Script filtering based on environment settings

### Implementation Details
- **AJAX Integration**: MT_Debug_Ajax handler for all asynchronous operations
- **Frontend Assets**: Interactive JavaScript (debug-center.js) and professional CSS styling
- **Admin Integration**: Updated menu system with legacy redirects for backward compatibility
- **Auto-loading**: Smart class loading in plugin initialization for optimal performance
- **Template System**: Complete set of tab templates for all Debug Center functionality

### Files Added
- **Core Classes**:
  - `includes/services/class-mt-diagnostic-service.php` - System diagnostics engine
  - `includes/admin/class-mt-debug-manager.php` - Debug script management
  - `includes/admin/class-mt-maintenance-tools.php` - Maintenance operations
  - `includes/utilities/class-mt-database-health.php` - Database monitoring
  - `includes/utilities/class-mt-system-info.php` - System information gathering
  - `includes/ajax/class-mt-debug-ajax.php` - AJAX request handler

- **Templates** (all complete):
  - `templates/admin/debug-center.php` - Main interface template
  - `templates/admin/debug-center/tab-diagnostics.php` - System diagnostics
  - `templates/admin/debug-center/tab-database.php` - Database tools
  - `templates/admin/debug-center/tab-scripts.php` - Script runner
  - `templates/admin/debug-center/tab-errors.php` - Error monitoring
  - `templates/admin/debug-center/tab-tools.php` - Maintenance tools
  - `templates/admin/debug-center/tab-info.php` - System information

- **Frontend Assets**:
  - `assets/js/debug-center.js` - Interactive functionality
  - `assets/css/debug-center.css` - Professional styling
  - `debug/registry.json` - Script metadata and controls

### Files Modified
- `includes/admin/class-mt-admin.php` - Added Debug Center menu and asset loading
- `includes/core/class-mt-plugin.php` - Integrated Debug Center initialization
- Debug scripts reorganized into categorized directories

## [2.2.29] - 2025-08-14

### Added
- **Coaching Dashboard**: Complete jury evaluation management system
  - New coaching menu under Mobility Trailblazers for administrators
  - Real-time progress tracking for all jury members
  - Visual progress bars showing completion rates
  - Recent activity feed showing latest evaluations
  - Send reminder emails to jury members with incomplete evaluations
  - Export coaching reports as CSV with all statistics
  - Average score tracking per jury member
  - Last activity tracking to identify inactive jury members

- **Performance Testing Suite**: Comprehensive testing tools for large datasets
  - Test import performance with configurable record counts
  - Test export performance and memory usage
  - Database query performance testing
  - Memory usage profiling for different operations
  - Assignment distribution analysis
  - Index effectiveness testing
  - Visual test interface at `/debug/performance-test.php`

- **Assignment Validation & Rebalancing**: Advanced assignment management
  - `validate_assignment_distribution()` method to check distribution quality
  - `rebalance_assignments()` to automatically redistribute uneven assignments
  - `get_distribution_statistics()` for detailed distribution analysis
  - Standard deviation calculation to measure distribution fairness
  - Quality ratings: Excellent (≤1.5 SD), Good (≤3 SD), Fair (≤5 SD), Poor (>5 SD)
  - Automatic detection of over/under-assigned jury members

### Enhanced
- **German Translations**: Complete localization for v2.2.29 features
  - 80+ new German translations added
  - Coaching dashboard fully translated
  - Performance testing interface translated
  - Assignment validation messages translated
  - All error messages and notifications localized

- **Template Organization**: Standardized file naming
  - Template files now consistently use hyphenated names
  - Improved fallback handling for template loading

### Technical Improvements
- Coaching statistics use optimized SQL queries with proper JOINs
- Performance tests include memory profiling and execution time tracking
- Assignment rebalancing prevents duplicate assignments
- Audit logging for all coaching and rebalancing actions
- Proper capability checks for all new features

## [2.2.28] - 2025-08-14

### Fixed
- **CSV Import BOM Handling**:
  - Fixed BOM (Byte Order Mark) detection to prevent header misreading
  - Added automatic delimiter detection (comma, semicolon, tab, pipe)
  - Enhanced header cleaning to remove BOM, trim whitespace, and normalize spaces
  - Improved compatibility with Excel-generated CSV files

- **Field Validation & Mapping**:
  - Made CSV field mapping case-insensitive
  - Added support for alternate field names (Organisation/Organization, Website/Webseite)
  - Fixed field validation to handle both uppercase and lowercase field names
  - Improved robustness when importing from different CSV sources

- **AJAX Security Enhancements**:
  - Added nonce verification to test_ajax and debug_user endpoints
  - Added permission checks for debug endpoints (requires manage_options)
  - Enhanced file upload validation with comprehensive checks
  - Added malicious content detection for uploaded files

- **JavaScript Improvements**:
  - Added fallback initialization for mt_ajax object
  - Added validation for mt_ajax structure
  - Converted event handlers to use event delegation for dynamic elements
  - Improved handling of dynamically added DOM elements

- **Database Integrity**:
  - Added cleanup_orphaned_assignments() method to remove invalid records
  - Added verify_integrity() method to check for database issues
  - Ensured assigned_by field is always populated
  - Added methods to fix missing or invalid data

- **Widget Management**:
  - Added refreshDashboardWidget() function for AJAX widget updates
  - Added refreshDashboardWidgets() for batch widget updates
  - Added loading states and animations for widget refresh
  - Improved user feedback during data updates

### Added
- **Base AJAX Class Enhancement**:
  - Added validate_upload() method for standardized file validation
  - Supports MIME type checking, file size limits, and content scanning
  - Prevents PHP and script injection attempts
  - Centralized validation logic for all upload handlers

- **Database Cleanup Methods**:
  - cleanup_orphaned_assignments(): Remove assignments for deleted candidates/jury
  - verify_integrity(): Check for data consistency issues
  - clear_all_caches(): Clear assignment-related transients

- **CSS Enhancements**:
  - Added .mt-widget-loading class with visual feedback
  - Added pulse animation for loading states
  - Improved visual feedback during AJAX operations

### Enhanced (Priority 3)
- **Import/Export Features**:
  - Added batch processing capability for large CSV imports
  - Implemented progress tracking with visual feedback
  - Added streaming exports to optimize memory usage for large datasets
  - Export methods now process data in 100-record batches to prevent memory issues

- **UI/UX Standardization**:
  - Added comprehensive button styles (primary, secondary, danger variants)
  - Implemented consistent loading states across all buttons
  - Added progress bars with animated fill and percentage display
  - Created standardized modal styles with proper animations
  - Added spinner animations for loading indicators

- **German Translations**:
  - Added 50+ new German translations for all Priority 3 features
  - Complete localization for progress tracking UI elements
  - Translated all new button states and loading messages
  - Added German translations for batch processing feedback

- **Memory Optimization**:
  - Implemented streaming for candidate exports (export_candidates_stream method)
  - Added streaming for evaluation exports (export_evaluations_stream method)
  - Data now processed in chunks with periodic cache clearing
  - Added set_time_limit calls to prevent timeouts on large exports

### Security
- All AJAX handlers now properly extend MT_Base_Ajax
- Consistent use of verify_nonce() and check_permission() methods
- Enhanced file upload validation with multiple security layers
- Added logging for security events and suspicious activities

### Technical Improvements
- Event delegation improves performance with dynamic content
- Reduced memory usage through proper event handler management
- Better error handling and user feedback
- Improved code organization and reusability
- Batch processing prevents memory exhaustion on large datasets
- Streaming exports handle datasets of any size efficiently

## [2.2.27] - 2025-08-13

### Fixed
- **PHP Localization Consistency**:
  - Changed frontend wp_localize_script to use `ajax_url` instead of `url`
  - Removed redundant `url` key from mt_admin localization
  - Standardized all AJAX URL references to use `ajax_url` key
  - Added missing i18n strings (select_bulk_action, select_assignments) to admin localization

- **Documentation**:
  - Updated README.md version from 2.2.22 to 2.2.27 in both header and footer

- **i18n Consistency**:
  - Fixed hardcoded English strings in admin.js bulk actions
  - Now uses `mt_admin.i18n` for all user-facing strings

- **AJAX URL Standardization**:
  - Changed frontend.js to use `mt_ajax.ajax_url` instead of `mt_ajax.url`
  - Ensures consistency across all JavaScript files

- **Security Enhancement**:
  - Strengthened import capability check from `edit_posts` to `manage_options`
  - Import operations now require administrator access only
  - Updated error messages to clarify permission requirements

- **Documentation**:
  - Fixed license typo in README.md (removed extra period)
  - Consistent GPL v2 or later across all files

### Verified
- **mtShowNotification Function**: Confirmed globally available in admin.js (line 71)
- **CSS Units**: No spacing issues found in current codebase

## [2.2.26] - 2025-08-13

### Fixed
- **JavaScript Improvements**:
  - Removed redundant `typeof mtShowNotification === 'function'` checks in admin.js
  - Fixed modal display issues by adding proper initial `display: none` state
  - Improved modal accessibility with ARIA attributes and focus management

- **Documentation Updates**:
  - Corrected README scoring scale from "5-point" to "0-10 (with 0.5 increments)"
  - Updated evaluation criteria documentation to match implementation

- **Database Consistency**:
  - Fixed table name mismatch in uninstaller (added mt_audit_log to removal list)
  - Ensured consistent table naming across activator and uninstaller

- **Security Enhancements**:
  - Added capability checks to debug scripts (require `manage_options`)
  - Added nonce verification to debug pages
  - Enhanced import file validation with MIME type and size checks

- **UX Improvements**:
  - Added 1.5 second delay before page reload to show success messages
  - Modal fadeOut on successful operations before reload
  - Better visual feedback during operations

### Added
- **Development Files**:
  - Created `.distignore` file to exclude debug scripts from releases
  - Excludes `/debug/`, test files, and development artifacts

- **Import Validation**:
  - File size limit (10MB) in import-profiles.php template
  - MIME type validation for CSV imports
  - Enhanced security checks matching AJAX handlers

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
