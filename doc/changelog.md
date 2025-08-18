# Mobility Trailblazers Changelog

> **Note**: Version 2.2.7b represents a hotfix that was deployed on the same day as 2.2.7. The duplicate version number has been corrected with the 'b' suffix to maintain chronological accuracy.

## [2.5.18] - 2025-08-18
### Fixed
- **Medal Icons and Position Numbers**: Fixed display issues with ranking badges
  - Removed non-functional SVG medal icons
  - Simplified position badges to show numbers only
  - Enhanced medal styling with proper gradients for gold, silver, and bronze positions
  - Added proper CSS for position badge visibility
  - Fixed badge sizing and alignment in tables
  - New file: `assets/css/mt-medal-fix.css`
  - Modified: `templates/frontend/partials/jury-rankings.php`

## [2.5.17] - 2025-08-18
### Added
- **Elementor Template Integration**: New tool for creating Elementor templates from MT shortcodes
  - Added MT Elementor Templates tool under Tools menu
  - Creates importable Elementor templates for all 4 MT shortcodes
  - Supports both container and section modes based on Elementor settings
  - Templates wrap shortcodes to maintain full functionality
  - Added CSS wrapper classes for customizable template styling
  - New files:
    - `includes/admin/tools/class-mt-elementor-templates.php`
    - `assets/css/mt-elementor-templates.css`
    - `debug/create-elementor-templates.php`
  - Modified `includes/admin/class-mt-admin.php` to load the tool

## [2.5.16] - 2025-01-20
### Removed
- **Email Functionality**: Completely removed all email features from the plugin
  - Deleted MT_Email_Service class and email service infrastructure
  - Removed all email templates from templates/emails/ directory
  - Removed "Send Reminder" buttons from coaching dashboard
  - Removed email-related JavaScript functions from coaching.js
  - Cleaned up AJAX handlers for email sending
  - Plugin will no longer send any email notifications or reminders
  - As requested, the plugin now operates without any email features

### Changed  
- **Coaching Dashboard**: Simplified interface without email actions
  - Removed individual "Send Reminder" buttons from jury member rows
  - Removed bulk email action buttons ("Send Reminders to Incomplete", "Remind About Drafts")
  - Kept only "Export Coaching Report" and "Refresh Statistics" functionality
  - Cleaner, more focused dashboard for tracking progress

## [2.5.15] - 2025-01-20
### Fixed
- **View Details Button**: Fixed non-working View Details button in evaluations admin page
  - Changed MTEvaluationManager from const to window object for proper scope
  - Fixed initialization to ensure event handlers are properly bound
  
- **Debug Center Scripts**: Fixed JavaScript initialization for debug script buttons
  - Buttons now properly execute debug scripts when clicked
  - Event handlers correctly bound to mt-execute-script class

### Removed
- **Error Monitor Tab**: Removed deprecated Error Monitor tab from Debug Center
  - Tab was no longer functional and has been replaced by standard WordPress debug logging
  - Cleaner interface with only relevant debug tools

### Improved
- **JavaScript Initialization**: Better module initialization for admin pages
- **Code Quality**: Removed unnecessary complexity in favor of simpler solutions

## [2.5.14] - 2025-01-20
### Fixed
- **Candidate Card Backgrounds**: Applied consistent cream background (#F8F0E3) with blue accent borders
  - Fixed missing background colors on candidate link cards
  - Added hover effects with white background and copper borders
  - Improved text color hierarchy for better readability
  
- **Jury Dashboard Title**: Enhanced header presentation with professional styling
  - Implemented gradient background (deep teal to blue)
  - Added Poppins font for improved typography
  - Created visual depth with pattern overlay and shadows
  - Styled progress bar with copper accent gradient

- **Evaluation Button**: Removed inappropriate evaluation button from candidate pages
  - Button was showing for all jury members regardless of assignment
  - Evaluations now properly restricted to jury dashboard

### Enhanced
- **Brand Consistency**: Maintained color palette across all UI elements
- **Visual Hierarchy**: Improved distinction between different content areas
- **User Experience**: Smoother transitions and clearer interactive states

## [2.5.13] - 2025-01-20
### Security
- **Debug Center Audit**: Complete security overhaul of admin debug interface
  - Enhanced nonce verification on all AJAX endpoints
  - Added capability checks for all operations
  - Proper input sanitization and output escaping
  - SQL injection prevention with prepared statements

### Removed
- **Delete All Candidates**: Permanently removed dangerous bulk delete operation
  - Removed AJAX handler method
  - Removed JavaScript function  
  - Removed UI button from database tab
  - Too risky even with confirmations

### Fixed
- **Debug Script Execution**: Now requires MT_DEV_TOOLS constant in production
- **Log Clearing**: Restricted to non-destructive operations only
- **System Info**: Removed sensitive data from responses
- **Database Queries**: All queries now use prepared statements

### Improved
- **Security**: All AJAX handlers properly verify nonces and capabilities
- **Internationalization**: All strings use proper text domain
- **Error Handling**: Consistent error messages and logging
- **Code Quality**: Removed deprecated and unsafe code patterns

## [2.5.12] - 2025-08-17
### Fixed
- **Alignment Issues**: Fixed center alignment problems in candidate cards
  - Text and elements now properly centered
  - Improved visual hierarchy and layout consistency
  
- **Padding Problems**: Added proper padding to content boxes
  - Increased padding from 20px to 25-30px for better breathing room
  - Fixed content being too close to borders
  - Applied consistent padding across all components
  
- **Color Corrections**: Updated to strict brand color palette
  - Primary: #003C3D (deep teal)
  - Secondary: #004C5F (deep blue)
  - Body Text: #302C37 (dark gray)
  - Accent: #C1693C (kupfer/copper)
  - Background: #F8F0E3 (cream)
  - Borders: #A4DCD5 (blue accent)

### Changed
- **Candidate Links**: Updated link button colors
  - LinkedIn links use Secondary color (#004C5F)
  - Website links use Accent color (#C1693C)
  - Proper hover states with brand colors
  
- **Card Backgrounds**: Changed from white to BG Beige (#F8F0E3)
  - Better consistency with main website
  - Softer, more inviting appearance
  
### Added
- **New Stylesheet**: Created `mt-brand-fixes.css` for targeted fixes
  - Comprehensive fixes for alignment, padding, and colors
  - High specificity overrides for consistency
  - Responsive adjustments for mobile devices

## [2.5.11] - 2025-08-17
### Added
- **Brand Alignment Styles**: New `mt-brand-alignment.css` stylesheet to match main website design
  - Implemented main website color palette (#f8f0e3, #003c3d, #004c5f, #c1693c)
  - Added Poppins font for headings matching main site typography
  - Created consistent hover effects and transitions across all components

### Changed
- **Background Colors**: Updated to soft cream (#F8F0E3) for:
  - `.mt-jury-dashboard` - Jury dashboard main container
  - `.mt-candidates-list` - Candidates list container
  - `.mt-candidate-grid-item` - Individual candidate grid items
  - `.mt-criteria-stats` - Criteria statistics sections
  - `.mt-winners-header` - Winners header section
  
### Enhanced
- **Design Consistency**: Aligned plugin visual design with main website
  - Updated button styles to use terracotta accent color (#c1693c)
  - Improved card designs with consistent border colors and shadows
  - Added gradient effects to headers matching main site
  - Enhanced form inputs with brand-consistent focus states
  
### Fixed
- **Biography Display**: Fixed HTML entity encoding issues in candidate biographies
- **Score Display**: Updated to show "Average Score" with evaluation count for clarity

## [2.5.10] - 2025-08-17
### Removed
- **Repository Cleanup**: Removed all stale and unused files from project root
  - Deleted 9 one-time PHP migration/utility scripts (create-jury-member-posts.php, direct-photo-attach.php, etc.)
  - Removed 6 duplicate "Copy" files 
  - Deleted 9 unused PowerShell/batch scripts and configuration files
  - Total: 24 files removed from root directory
  
### Improved
- **Repository Structure**: Clean, professional root directory
  - Only essential plugin files retained (main plugin, uninstall.php)
  - Kept core project files (.gitignore, LICENSE, README.md, etc.)
  - No broken includes or requires - all removed files were verified as unreferenced
  - Plugin remains fully functional after cleanup

## [2.5.9] - 2025-08-17
### Changed
- **Documentation Consolidation**: Reduced from 23 to 5 core files
  - Created comprehensive `developer-guide.md` merging 6 technical guides
  - Enhanced `import-export-guide.md` with German localization section
  - Archived 19 historical documentation files for reference
  - Updated README.md with simplified navigation structure

### Added
- **Developer Guide**: New consolidated technical reference with 15 sections
- **Archive System**: Created `/doc/archived/` with index for historical docs
- **Consolidation Report**: Detailed documentation of the consolidation process

### Improved
- **Documentation Quality**: Removed 11,000+ lines of duplicate content
- **Navigation**: Clear 3-tier structure (README → Guides → Archive)
- **Internal Links**: Fixed all references and cross-document links
- **Formatting**: Standardized markdown across all documentation

## [2.5.8] - 2025-08-17
### Added
- **Testing Framework Implementation**: Comprehensive PHPUnit testing infrastructure
  - Created `phpunit.xml` configuration for test suites
  - Added `tests/` directory structure with unit, integration, and e2e subdirectories
  - Implemented base test case class (`MT_Test_Case`) with WordPress-specific utilities
  - Added test factory (`MT_Test_Factory`) for generating mock data
  - Created test helpers trait (`MT_Test_Helpers`) with 20+ custom assertions
- **Unit Tests**: Core plugin functionality and evaluation service tests
- **Integration Tests**: Complete workflow testing implementation

### Enhanced
- **German Localization Completed**: Over 1000 strings translated covering all plugin features
  - Fixed duplicate entries and syntax errors in German .po file
  - Successfully compiled German .mo file
  - Set WordPress language to German (de_DE)

### Fixed
- **jQuery UI Tooltip Error**: Added conditional check for tooltip function availability
  - Implemented fallback to native browser tooltips
  - Fixed error in `assets/js/design-enhancements.js`

### Security
- **Database Issues Identified**: Assignment table schema mismatch noted for future migration

## [2.5.7] - 2025-08-17
### Removed
- **Error Monitor Feature**: Complete removal of custom error monitoring system
  - Removed error monitor class, templates, and database table
  - Simplified error handling to use standard WordPress debug logging
  - Updated Debug Center to use deprecation notice for error monitoring

### Fixed
- **Assignment Modal Visibility**: Resolved modal display issues
  - Created new modal implementation with unique class names
  - Fixed conflicts with WordPress admin styles and JavaScript execution order
  - Both auto-assignment and manual assignment modals now fully functional

## [2.5.6] - 2025-08-17
### Fixed
- **Biography Display**: Improved biography display on evaluation pages
  - Added word limit (80 words) for long biographies
  - Biography now shows from either meta field or post content
- **Evaluation Criteria Layout**: Centered "Evaluation Criteria" title and cards
  - Improved visual hierarchy with centered content

## [2.5.5] - 2025-08-17
### Enhanced
- **UI/UX Readability**: Major improvements to text readability across interface
  - Table headers: Changed from dark gradient to white background with dark text
  - Score inputs: Enhanced borders and proper contrast for different states
  - Action buttons: Updated to use standard Bootstrap colors
  - Cell borders: Changed to lighter gray for reduced visual noise
- **Typography**: Increased font weights and improved color contrast
- **Accessibility**: All text now meets WCAG AA contrast requirements

### Fixed
- **Evaluation Table Layout**: Improved candidate cell spacing and rating input centering
  - Added proper padding to candidate names
  - Implemented biography display in evaluation table
  - Centered rating inputs for professional appearance

## [2.5.4] - 2025-08-17
### Security
- **Production Cleanup**: Removed 13 console.log statements across 7 JavaScript files
  - Prevented information leakage through browser console
  - Debug information now only logged in development environments
- **WordPress Best Practices**: Replaced all exit statements with wp_die()
  - Proper script termination allows WordPress hooks to run

### Changed
- **PHP Debug Logging**: Wrapped all error_log() calls in WP_DEBUG checks
  - Debug logs now only when WP_DEBUG is enabled

## [2.5.3] - 2025-08-17
### Security
- **Critical CSS Syntax Fixes**: Fixed invalid CSS with spaces between values and units
- **XSS Prevention**: Added wp_kses_post() sanitization to Debug Center script output
- **AJAX Standardization**: Unified response format across all Debug Center handlers
- **Version Synchronization**: Updated all version references to 2.5.3

### Enhanced
- **Security Audit**: Verified all Debug Center handlers have proper nonce and capability checks
  - Confirmed registry path resolution uses absolute paths only

## [2.5.2] - 2025-08-17
### Fixed
- **Fatal Error Prevention**: Added null checks in evaluation details to prevent crashes when posts are deleted
- **Nonce Standardization**: Fixed inconsistent nonce names in export functions (now using `mt_admin_nonce`)
- **Script Termination**: Changed `exit` to `wp_die()` after redirects for proper WordPress handling
- **Variable Naming**: Renamed `$stats` to `$coaching_data` in MT_Coaching class for clarity
- **Code Cleanup**: Removed test_handler placeholder from MT_Assignment_Ajax

## [2.5.1] - 2025-08-17
### Fixed
- **Hero Section Height**: Fixed excessive hero section taking up entire viewport
  - Reduced padding and added max-height constraint of 400px
- **Evaluation Criteria Text**: Fixed bunched text without proper line breaks
  - Changed white-space to pre-line to preserve line breaks
- **Top Ranked Color Contrast**: Fixed unreadable text on rank badges
  - Gold rank now uses dark text for better contrast
- **Candidate Grid Layout**: Fixed broken grid layout issues
  - Implemented proper CSS Grid with responsive breakpoints
- **Biography/Web Fields**: Verified fields are properly present in evaluation form

## [2.5.0] - 2025-08-17
### Added
- **Comprehensive Design Improvements**: Major design overhaul for better user experience
  - Created `design-improvements-2025.css` with enhanced visual hierarchy
  - Added `design-enhancements.js` for interactive animations and smooth scrolling
  - Implemented progress indicators for evaluation forms
  - Enhanced accessibility with keyboard navigation and screen reader support

### Fixed
- **Excessive Top Spacing**: Reduced padding on candidate profile pages
- **Evaluation Criteria Text**: Improved text flow and readability
- **Duplicate Biography Sections**: Consolidated into single display
- **Social Media Icons**: Replaced broken dashicons with inline SVG icons

### Changed
- **Asset Enqueuing**: Modified asset loading in plugin core for proper dependency chain

## [2.4.5] - 2025-08-17
### Documentation
- **Complete Candidate Page Design Fixes**: Created comprehensive documentation for all layout improvements
  - Documented three major issues: excessive top spacing, evaluation criteria formatting, missing social icons
  - Created detailed technical implementation guide with CSS load order and responsive design specifications
  - Added stakeholder requirements analysis and rollback instructions

## [2.4.4] - 2025-08-17
### Fixed
- **Candidate Profile Display Issues**: Fixed critical field mapping issues preventing candidate data display
  - Updated template to use correct database field names for biography, LinkedIn, and website links
  - Fixed individual evaluation criteria display from separate fields
  - Updated jury evaluation form field mappings

### Added
- **Data Migration Script**: Created migration script for field mapping compatibility
  - Ensures backward compatibility with both old and new field naming conventions
  - Successfully migrated all 50 candidates in database

## [2.4.3] - 2025-08-16
### Enhanced
- **Complete Translation System**: Added comprehensive i18n support for all user-facing strings
  - Updated all frontend templates with proper translation functions
  - Enhanced JavaScript localization with complete string translations
  - Created multilingual email template system
  - Expanded German translations with 50+ new translations

### Added
- **Email Service System**: New MT_Email_Service class for handling localized emails
  - Email templates for evaluation reminders and assignment notifications

## [2.4.2] - 2025-08-16
### Documentation
- **Major Documentation Consolidation**: Reduced from 19 to 7 main documentation files (63% reduction)
  - Created consolidated guides for developer documentation, import/export, debug center
  - Improved documentation discoverability and maintainability
  - Moved 18 original files to archive folder for reference

## [2.4.1] - 2025-08-16
### Fixed
- **Jury Grid Display**: Resolved inconsistent sizing and missing click functionality
  - Implemented fixed minimum height (320px) for all grid items
  - Added text truncation for long names using CSS line-clamp
  - Wrapped cards in anchor tags for navigation functionality

### Added
- **Visual Hover Effects**: Enhanced user experience with interactive feedback
  - Card lift animation with shadow on hover
  - "View Profile" button appears on hover
  - Responsive grid breakpoints for mobile and tablet support

## [2.4.0] - 2025-01-16
### Added
- **Comprehensive Photo Management System**: Complete photo integration solution
  - Photo matching script for bulk photo-to-candidate association
  - Support for 50+ WebP format photos with automatic WordPress media library upload
  - Enhanced candidate profile pages with modern UI redesign
  - Professional jury member profile template

### Enhanced
- **Modern Visual Design System**: Comprehensive UI upgrade
  - Color palette with primary gradient (#3b82f6 to #1e3a8a)
  - Modern typography scale and consistent border radius
  - GPU-accelerated animations and multi-layer shadows

## [2.3.6] - 2025-01-18
### Added
- **Photo Integration**: Complete photo management system for candidates
  - Photo matching script for bulk association
  - Enhanced candidate profile pages with hero sections and gradient backgrounds
  - Modern candidates grid with interactive filtering

### Enhanced
- **Visual Design**: Comprehensive UI upgrade with modern styling
  - Improved information architecture and consistent design system

## [2.3.5] - 2025-08-16
### Added
- **Delete All Candidates Feature**: Added to Debug Center Database Tools
  - Requires typing "DELETE" for confirmation to prevent accidental deletion
  - Uses database transactions for safe, atomic deletion

## [2.3.4] - 2025-08-16
### Added
- **Excel Import Support**: Complete solution for importing Excel candidate lists
  - Browser-based converter with drag-and-drop interface
  - PHP script converter for server-side processing
  - Category mapping system for German categories

## [2.3.3] - 2025-08-14
### Fixed
- **Run Diagnostic Button**: Fixed non-functional diagnostic execution
  - Corrected button selector logic and implemented dynamic results display

## [2.3.2] - 2025-08-14
### Fixed
- **Debug Script Output Display**: Fixed empty info box issue when executing debug scripts
- **Maintenance Operations**: Fixed "Operation not found" error
  - Corrected template structure to access operations at correct path

## [2.3.1] - 2025-08-14
### Fixed
- **Runtime Errors Resolution**: Complete fix of all Debug Center runtime errors
  - Fixed MT_Debug_Ajax missing init() method implementation
  - Resolved JavaScript undefined method bindings
  - Fixed database table name references and array access warnings

## [2.3.0] - 2025-08-14
### Added
- **Unified Debug Center**: Professional developer tools interface with 6 complete tabs
  - Centralized access point for debugging and diagnostic tools
  - Environment-aware security controls
  - Comprehensive tabbed interface: Diagnostics, Database, Scripts, Errors, Tools, Info

### Added
- **New Service Classes**:
  - MT_Diagnostic_Service: Comprehensive system health checks
  - MT_Debug_Manager: Secure debug script management
  - MT_Maintenance_Tools: System maintenance operations
  - MT_Database_Health: Database monitoring and analysis
  - MT_System_Info: System information gathering

### Security
- **Enhanced Security**: Production environment restrictions and audit logging
  - Required confirmations for destructive operations
  - IP-based audit logging for all debug script executions

## [2.2.29] - 2025-08-14
### Added
- **Coaching Dashboard**: Complete jury evaluation management system
  - Real-time progress tracking for all jury members
  - Send reminder emails and export coaching reports
  - Average score tracking and activity monitoring

### Enhanced
- **Performance Testing Suite**: Comprehensive testing tools for large datasets
- **Assignment Validation & Rebalancing**: Advanced assignment management
- **German Translations**: Complete localization for v2.2.29 features

## [2.2.28] - 2025-08-14
### Fixed
- **CSV Import BOM Handling**: Fixed BOM detection and automatic delimiter detection
- **Field Validation & Mapping**: Made CSV field mapping case-insensitive
- **AJAX Security**: Added nonce verification and permission checks
- **Database Integrity**: Added cleanup methods for orphaned assignments

### Enhanced
- **Import/Export Features**: Added batch processing for large CSV imports
- **UI/UX Standardization**: Comprehensive button styles and loading states
- **Memory Optimization**: Implemented streaming for exports

## [2.2.27] - 2025-08-13
### Fixed
- **PHP Localization Consistency**: Standardized AJAX URL references
- **Security Enhancement**: Strengthened import capability check to require administrator access
- **Documentation**: Fixed version references and license formatting

## [2.2.26] - 2025-08-13
### Fixed
- **JavaScript Improvements**: Removed redundant checks and fixed modal display issues
- **Documentation**: Corrected README scoring scale documentation
- **Security**: Added capability checks to debug scripts and enhanced import validation

## [2.2.25] - 2025-08-13
### Refactored
- **Import System Consolidation**: Reduced from 7 import files to 4 with clear separation
  - Consolidated all import logic into MT_Import_Handler class
  - Updated all references to use single import handler

## [2.2.24] - 2025-08-13
### Added
- **Complete AJAX CSV Import System**: Comprehensive CSV import with real-time progress tracking
- **Import Handler Class**: Centralized CSV processing for jury members and candidates
- **CSV Templates with German Support**: Updated templates with proper formatting

## [2.2.23] - 2025-08-13
### Added
- **Comprehensive Import/Export System**: Unified CSV import/export functionality
  - Support for both Jury Members and Candidates import types
  - Template download system and UTF-8 BOM handling

## [2.2.22] - 2025-08-13
### Fixed
- **Debug Code Cleanup**: Removed all console.log statements from production JavaScript
- **SQL Injection Prevention**: Fixed unescaped SQL queries with proper prepare statements
- **Performance Optimization**: Added limits to unbounded queries

## [2.2.21] - 2025-08-13
### Fixed
- **Duplicate AJAX Actions**: Removed duplicate handlers and improved CSV export memory usage
- **Assignment Distribution**: Added diagnostic tool for assignment distribution analysis

## [2.2.20] - 2025-08-13
### Fixed
- **Import Permission Check**: Fixed capability check in MT_Import_Ajax
- **JavaScript Notifications**: Replaced browser alerts with WordPress admin notices
- **Debug Code Cleanup**: Removed console.log statements from production files

## [2.2.19] - 2025-08-13
### Fixed
- **CSV Export Links**: Fixed broken export links in Import/Export admin page
- **Missing JavaScript Function**: Implemented mtShowNotification function

### Added
- **German Translations**: Completed missing translations for admin interface

## [2.2.18] - 2025-08-13
### Fixed
- **PHP Parse Error**: Fixed syntax error in enhanced profile importer
- **Header Output Issue**: Fixed header already sent warning in import-export
- **Import Button**: Fixed non-functional import button on candidates page
- **BOM Handling**: Fixed CSV import issues with Excel-exported files

## [2.2.17] - 2025-08-13
### Fixed
- **Version Numbering**: Corrected duplicate version entries in changelog
- **Critical Uninstall Bug**: Implemented missing data removal functionality
- **Assignment Removal**: Optimized assignment removal operations

## [2.2.16] - 2025-08-13
### Added
- **AJAX-Based CSV Import**: JavaScript-based CSV import with file picker
- **Secure AJAX Import Handler**: Dedicated handler with full security implementation
- **German Evaluation Criteria Parsing**: Enhanced CSV import with regex-based text extraction

## [2.2.15] - 2025-08-13
### Added
- **Enhanced CSV Import System**: Complete overhaul with German column headers support
- **Custom Columns**: New admin columns for better candidate data visibility
- **CSV Export Improvements**: Updated export to include all new fields

### Changed
- **Menu Consolidation**: Removed duplicate "Candidates" menu item

## [2.2.14] - 2025-08-12
### Fixed
- **Auto-Assignment Functionality**: Added "Clear existing assignments" option
### Enhanced
- **Assignment Capacity**: Increased maximum candidates per jury member from 20 to 50

## [2.2.13] - 2025-08-12
### Added
- **Data Management Settings**: New settings for controlling plugin data handling
### Improved
- **AJAX Error Handling**: Standardized error responses across all handlers

## [2.2.12] - 2025-08-12
### Enhanced
- **Audit Logging Coverage**: Extended logging to cover all critical platform actions
### Security
- **Complete Audit Trail**: All destructive operations now logged with full context

## [2.2.11] - 2025-08-12
### Fixed
- **Database Integrity**: Verified assigned_by field population in bulk operations
### Refactored
- **Assignment Removal**: Consolidated duplicate removal methods

## [2.2.10] - 2025-08-12
### Enhanced
- **Dashboard Widget Sync**: Completed synchronization with main admin dashboard

## [2.2.9] - 2025-08-12
### Enhanced
- **Jury Admin Role**: Formally defined mt_jury_admin role with specific capabilities
### Fixed
- **Code Consolidation**: Removed duplicate export function

## [2.2.8] - 2025-08-12
### Security
- **User Role System Cleanup**: Standardized user roles and capability checks
### Enhanced
- **Role-Based Access Control**: Improved granular permission system

## [2.2.7b] - 2025-08-12
### Fixed
- **Dashboard Widget Data**: Synchronized evaluation data between main dashboard and widget

## [2.2.7] - 2025-08-12
### Fixed
- **Evaluation Deletion Issues**: Fixed inability to delete evaluations when assignments modified
### Added
- **Orphaned Evaluation Handling**: System to detect and clean up orphaned evaluations
- **Database Sync Tool**: New admin tools page for database maintenance

## [2.2.6] - 2025-08-12
### Fixed
- **Assignment Management Buttons**: Completed JavaScript implementation for all buttons
### Added
- **Complete Assignment Management Module**: Full AJAX functionality for all operations

## [2.2.5] - 2025-08-12
### Added
- **Comprehensive Audit Logging System**: Complete audit trail for platform security
- **Audit Log Admin Interface**: Professional viewer with filtering and pagination
- **MT_Audit_Log_Repository**: Full-featured repository for audit log management

## [2.2.4] - 2025-08-12
### Refactored
- **Error Monitoring Architecture**: Consolidated into dedicated MT_Error_Monitor class

## [2.2.3] - 2025-08-12
### Fixed
- **Frontend JavaScript Scope**: Fixed "getI18nText is not defined" error in Jury Rankings

## [2.2.2] - 2025-08-11
### Refactored
- **Admin JavaScript Architecture**: Restructured for better maintainability and performance

## [2.2.1] - 2025-08-11
### Fixed
- **Auto-Assignment Algorithm**: Complete rewrite of assignment functionality
### Changed
- **Distribution Methods**: Improved balanced and random distribution algorithms

## [2.2.0] - 2025-08-01
### Added
- Enhanced CSV Import System with intelligent field mapping
- Bilingual Support for English and German CSV headers
- Import Validation with dry-run mode and duplicate detection
- CSV Formatter Tool for data preparation

## Previous Versions
See README.md for earlier version history