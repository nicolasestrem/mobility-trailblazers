# Changelog

## [1.0.6] - 2025-06-20

### Changed
- **Naming Convention Standardization** - Completed Phase 2 of major refactoring
  - All global functions now use `mt_` prefix consistently
  - CSS classes updated to use `.mt-` prefix with BEM methodology
  - JavaScript variables converted from snake_case to camelCase
  - WordPress hooks standardized with `mt_` prefix
  - Added backward compatibility layer for deprecated functions

### Added
- `includes/mt-compatibility-functions.php` - Deprecated function wrappers for backward compatibility
- `NAMING_CONVENTIONS.md` - Comprehensive naming standards documentation

### Fixed
- Circular reference bug in compatibility functions
- Multiple naming inconsistencies across PHP, CSS, and JavaScript files

### Deprecated
- `get_jury_nomenclature()` - Use `mt_get_jury_nomenclature()` instead
- `get_jury_member_meta_key()` - Use `mt_get_jury_member_meta_key()` instead
- `MT_get_evaluation_criteria()` - Use `mt_get_evaluation_criteria()` instead

### Technical Details
- Modified 9 files with automated naming convention fixes
- Manual corrections applied to compatibility layer
- All changes maintain backward compatibility through deprecation wrappers
# Changelog Update - June 19, 2025

## [1.0.5] - 2025-06-19

### Critical Elementor Integration Fix

This release resolves critical issues preventing Elementor from functioning properly with the Mobility Trailblazers plugin.

### Fixed

#### Elementor REST API Authentication Issues
- **Problem**: Elementor editor was receiving 403 Forbidden errors on all REST API endpoints
- **Root Cause**: Multiple conflicting authentication filters and corrupted user sessions
- **Solutions Implemented**:
  1. Removed problematic REST API filter in `class-mt-elementor-integration.php`
  2. Added emergency override in main plugin file to bypass REST filters during Elementor sessions
  3. Created must-use plugins for aggressive REST API authentication fixes
  4. Fixed corrupted user sessions for existing admin accounts

#### Widget Registration Issues
- **Problem**: MT Evaluation Statistics and MT Jury Dashboard widgets not appearing in Elementor
- **Fixes**:
  - Corrected widget naming inconsistency (hyphens vs underscores)
  - Fixed malformed PHP code in `evaluation-stats.php` (line 135)
  - Added missing helper functions `mt_get_user_evaluation_count()` and `mt_get_user_assignments_count()`

#### User Session Corruption
- **Problem**: Existing admin accounts couldn't use Elementor while new accounts worked fine
- **Solution**: Implemented comprehensive user session cleanup and capability refresh system
- **Added**: Auto-repair functionality for user capabilities on login

### Added

#### Must-Use Plugins
1. **elementor-emergency-fix.php** - Forces REST API access for Elementor routes
2. **force-elementor-rest-auth.php** - Aggressive authentication bypass for logged-in users
3. **fix-user-elementor.php** - Automatic user capability repair on login

#### New Utility Functions
- `mt_get_user_evaluation_count()` - Returns evaluation count for a user
- `mt_get_user_assignments_count()` - Returns assignment count for a user

### Changed

- Updated `class-mt-elementor-integration.php` to remove conflicting REST API filters
- Modified widget registration to use consistent naming convention
- Enhanced error handling in utility functions

### Technical Details

#### Files Modified
1. `/includes/elementor/class-mt-elementor-integration.php`
2. `/mobility-trailblazers.php`
3. `/templates/shortcodes/evaluation-stats.php`
4. `/includes/elementor/widgets/jury-dashboard.php`
5. `/includes/mt-utility-functions.php`
6. `/wp-content/mu-plugins/` (new emergency fixes)

#### Database Changes
- None required

#### Breaking Changes
- None - all changes are backward compatible

### Upgrade Instructions

1. Update the plugin files
2. Clear all caches (Redis, WordPress transients, Elementor CSS)
3. All existing admin users must:
   - Logout completely from WordPress
   - Clear browser cache and cookies for the site
   - Login again to receive fresh authentication tokens
4. Deactivate and reactivate the plugin to ensure all fixes are applied

### Notes

- This fix addresses a complex interaction between WordPress REST API authentication, Elementor's requirements, and user session management
- The must-use plugins provide failsafe mechanisms to ensure Elementor access
- Future updates will include more robust REST API handling to prevent similar issues

All notable changes to the Mobility Trailblazers plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


and this project adheres to Semantic Versioning.
[1.0.4] - 2025-06-18
Critical Infrastructure Update
This release addresses critical database and capability issues that were preventing proper operation of the assignment management and evaluation systems.
Fixed
Database Schema Issues

Missing wp_mt_evaluations table: Added missing table definition for storing jury evaluations

Includes all evaluation scores (courage, innovation, implementation, relevance, visibility)
Supports draft and final submission states
Includes automatic total score calculation via database triggers


Missing wp_mt_jury_assignments table: Added missing table for managing jury-candidate assignments

Properly tracks which candidates are assigned to which jury members
Includes assignment metadata (date, assigned by, active status)
Unique constraint prevents duplicate assignments



Administrator Capability Issues

Fixed missing capabilities: Administrator role was missing critical capabilities

Added edit_others_mt_candidates capability
Added edit_others_mt_jury_members capability
Added all MT-specific capabilities to administrator role
Implemented automatic capability repair on every admin page load
Added support for both mt_jury and mt_jury_member post type naming conventions



Code Improvements

Database version management: Bumped database version from 1.0.2 to 1.0.3 to force schema updates
Backward compatibility: Added fallback methods in utility functions

mt_get_assigned_candidates() now checks for table existence and falls back to post meta queries
mt_has_evaluated() and mt_get_evaluation() include similar fallback logic
Ensures plugin operates during transition period



Added

Self-healing capabilities: Administrator capabilities are now checked and repaired automatically on admin_init
Table existence checks: All database queries now verify table existence before execution
Comprehensive error handling: Prevents PHP errors when tables are missing

Technical Details
Files Modified

includes/class-database.php

Added create_evaluations_table() method
Added create_jury_assignments_table() method
Updated create_tables() to include new tables
Updated drop_tables() and get_table_name() methods


includes/class-roles.php

Added add_admin_capabilities() method that runs on every admin_init
Created get_all_mt_capabilities() to centralize capability definitions
Modified create_roles() to ensure administrator has all capabilities


includes/mt-utility-functions.php

Rewrote mt_get_assigned_candidates() with table existence checking
Added fallback to post meta queries when tables don't exist
Updated mt_has_evaluated() and mt_get_evaluation() with similar logic
Modified mt_get_evaluation_statistics() to handle missing tables gracefully



Migration Notes

The update will automatically create missing tables upon plugin activation or first admin page load
Existing data in post meta will continue to work via fallback methods
No manual intervention required - all fixes are self-applying
## [1.1.0] - 2025-06-17

### Added
- **Enhanced Jury Dashboard** - Complete redesign with modern UI/UX
  - Real-time candidate search and filtering
  - Interactive evaluation form with 5 criteria sliders
  - Draft evaluation support with auto-save capability
  - Progress tracking with visual indicators
  - Animated statistics dashboard
  - Modal-based evaluation interface
  - Mobile-responsive design
- **New JavaScript Module** (`assets/jury-dashboard.js`)
  - MTJuryDashboard object with complete evaluation workflow
  - AJAX integration for seamless data operations
  - Real-time form validation
  - Notification system for user feedback
- **Professional Styling** (`assets/jury-dashboard.css`)
  - Modern gradient-based design system
  - Card-based layouts with hover effects
  - Smooth CSS animations and transitions
  - Responsive grid system
  - Accessibility-friendly color contrasts
- **AJAX Endpoints**
  - `mt_get_jury_dashboard_data` - Retrieve dashboard statistics
  - `mt_get_candidate_evaluation` - Load evaluation data
  - `mt_save_evaluation` - Save draft or final evaluations

### Changed
- **Jury Dashboard Template** (`templates/shortcodes/jury-dashboard.php`)
  - Removed inline styles and scripts
  - Restructured HTML for better semantics
  - Added proper data attributes for JavaScript interaction
  - Implemented WordPress localization for strings
- **Asset Loading** (`includes/class-mt-jury-system.php`)
  - Updated to load new dedicated CSS/JS files
  - Added proper script localization with nonces

### Fixed
- Non-functional evaluation form now fully operational
- Missing visual feedback for user actions
- Poor mobile experience on jury dashboard
- Lack of progress tracking for evaluations
- No draft save functionality
- Missing search and filter capabilities

### Technical Details
- **Database**: No schema changes required
- **Dependencies**: jQuery (existing WordPress dependency)
- **Browser Support**: Modern browsers with CSS Grid support
- **Performance**: Optimized animations with CSS transforms
- **Security**: AJAX calls protected with nonce verification

## [1.0.3] - 2025-06-17

### Fixed
- Assignment management system button functionality
- Auto-assignment "No candidates or jury members found" error
- Assignment display showing 0 assignments despite data existing
- Data type consistency issues in assignment functions

### Added
- Manual assignment functionality with modal interface
- Proper AJAX handlers for assignment operations
- Assignment data validation and error handling

### Changed
- Separated inline CSS/JS into proper asset files
- Improved code organization for assignment management

## [1.0.2] - 2025-06-16

### Added
- Initial public release
- Complete award management system
- Candidate and jury member management
- Public voting system
- Evaluation criteria system
- Elementor Pro integration
- Comprehensive admin tools

### Features
- Custom post types for candidates and jury members
- Voting system with IP-based restrictions
- CSV import/export functionality
- Multi-language support (WPML ready)
- Email notification system
- Role-based access control

## [1.0.1] - 2025-06-15

### Added
- Beta testing version
- Core plugin architecture
- Database schema installation

### Fixed
- Initial bug fixes from alpha testing
- Performance optimizations

## [1.0.0] - 2025-06-01

### Added
- Initial development version
- Basic plugin structure
- Database design