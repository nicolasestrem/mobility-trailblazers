# Mobility Trailblazers Plugin Changelog

## Version 2.2.0 - Jury Synchronization & Bug Fixes (January 2025)

### 🔄 Jury Synchronization System
- **Automatic User-Jury Post Sync**: Implemented comprehensive synchronization between users with `mt_jury_member` role and jury posts
  - Auto-creates jury posts when users get jury member role
  - Auto-removes jury member role when jury posts are deleted/trashed
  - Restores jury posts when users regain jury member role
  - Handles user deletion by cleaning up associated jury posts
- **Bidirectional Synchronization**: Changes to either users or jury posts automatically sync to the other
- **Data Preservation**: Uses trash instead of deletion to preserve evaluation data and assignments
- **Orphan Cleanup**: Identifies and manages orphaned jury posts without valid users
- **Manual Sync Tools**: Admin interface for manual synchronization with detailed status reporting

### 🐛 Critical Bug Fixes
- **Namespace Conflicts Resolved**: Fixed "Class not found" errors in namespaced files
  - Fixed `WP_Query` references in `class-shortcodes.php` (lines 115, 180)
  - Fixed `WP_Error` references in `class-vote-reset-manager.php` (5 instances)
  - Fixed `VoteAuditLogger` namespace issues in vote management classes
- **Missing Asset Files**: Created missing frontend CSS and JavaScript files
  - Added `assets/css/frontend.css` with voting interface and candidate display styles
  - Added `assets/js/frontend.js` with frontend functionality and AJAX utilities
- **Template Property Access**: Fixed PHP warnings in jury dashboard template
  - Fixed taxonomy name mismatches (`candidate_category` → `mt_category`, `vote_round` → `mt_phase`)
  - Added safety checks for array vs object property access
  - Enhanced error handling for missing taxonomy data

### 🔧 Diagnostic System Enhancement
- **Comprehensive Diagnostic Page**: Fully implemented MT Diagnostic page with detailed system checks
  - Database table existence and record counts
  - File permissions and directory access checks
  - Plugin dependency verification (WordPress, PHP, MySQL versions)
  - Plugin configuration status (post types, taxonomies, versions)
  - System information display (memory limits, debug settings, etc.)
  - Jury synchronization status monitoring
- **Visual Status Indicators**: Color-coded status indicators (✓ green, ⚠ orange, ✗ red)
- **Sync Management**: Direct access to jury synchronization tools from diagnostic page

### 🗄️ Database Integration Improvements
- **Audit Logger Table Structure**: Updated database schema for vote reset logs
  - Added missing columns: `initiated_by_role`, `affected_candidate_id`, `voting_phase`, etc.
  - Enhanced audit trail with IP address and user agent tracking
  - Improved data integrity with proper column definitions
- **Table Update System**: Automatic database updates for existing installations
- **Proper Class Loading**: Fixed audit logger class inclusion and namespace issues

### 🎨 Frontend Enhancements
- **Jury Dashboard Widget**: Resolved loading issues with MT Jury Dashboard Elementor widget
  - Fixed shortcode namespace references (`Roles::is_jury_member()` → `\MobilityTrailblazers\Roles::is_jury_member()`)
  - Enhanced template compatibility with both array and object data formats
  - Added fallback content for missing core classes
  - Improved error handling and user feedback
- **Template Safety**: Added comprehensive safety checks for data structure variations
- **Responsive Design**: Enhanced mobile compatibility in frontend CSS

### 🛡️ Security & Validation Enhancements
- **Input Validation**: Enhanced validation for all user inputs and form submissions
- **Error Handling**: Improved error handling with proper WordPress error responses
- **Data Sanitization**: Enhanced sanitization for all database operations
- **Access Control**: Strengthened permission checks for jury synchronization operations

### 📊 Monitoring & Logging
- **Sync Activity Logging**: Comprehensive logging of all jury synchronization activities
  - User role changes and jury post creation/deletion
  - Automatic cleanup operations and orphan detection
  - Manual sync operations with success/error counts
- **Diagnostic Integration**: Real-time sync status monitoring in diagnostic page
- **Action Hooks**: Added extensibility hooks for jury post lifecycle events

### 🚀 Performance Optimizations
- **Conditional Loading**: Enhanced asset loading with better condition detection
- **Database Queries**: Optimized queries for jury synchronization operations
- **Caching**: Improved caching for frequently accessed jury member data
- **Error Prevention**: Proactive error prevention through better validation

## Version 2.1.0 - Backend Integration & Localization (January 2025)

### 🔧 Backend Integration Implementation
- **Complete AJAX Handler System**: Implemented all four requested AJAX handlers for jury evaluation functionality
  - `handle_evaluation_submission()` - Processes jury evaluation submissions with full validation
  - `handle_draft_save()` - Saves evaluation drafts as user metadata with timestamps
  - `handle_get_evaluation()` - Retrieves existing evaluations and drafts for form population
  - `handle_export_evaluations()` - Generates CSV exports of individual jury evaluations

### 🌐 Comprehensive Localization
- **JavaScript Localization**: Added complete `wp_localize_script` implementation with 16+ localized strings
- **Intelligent Script Loading**: Implemented `is_jury_dashboard_page()` method for conditional asset loading
- **Performance Optimization**: Scripts and styles only load when jury dashboard functionality is needed
- **Multilingual Support**: Full internationalization ready with proper text domain usage

### 🗄️ Database Integration Enhancements
- **Table Structure Alignment**: Updated all handlers to use correct database column names
  - Aligned with existing MySQL schema (`courage_score`, `innovation_score`, etc.)
  - Fixed date field references (`evaluation_date` vs `updated_at`)
  - Integrated with database triggers for automatic `total_score` calculation
- **Database Table Creation**: Added `mt_candidate_scores` table creation to Database class
- **Data Integrity**: All operations use prepared statements and proper sanitization

### 🛡️ Security & Validation
- **Nonce Verification**: All AJAX handlers verify `mt_jury_dashboard` nonce
- **Authorization Checks**: Verify user is jury member for all evaluation operations
- **Assignment Validation**: Ensure jury members can only evaluate assigned candidates
- **Input Sanitization**: All form data properly sanitized and validated (scores 1-10)
- **SQL Injection Prevention**: All database queries use prepared statements

### 📊 Feature Enhancements
- **Draft System**: Auto-save and manual draft saving with user metadata storage
- **Evaluation Loading**: Load existing evaluations for editing with dual format support
- **Progress Tracking**: Real-time progress updates after evaluation submissions
- **Export Functionality**: Individual jury member CSV exports with comprehensive data
- **Error Handling**: Comprehensive error messages and user feedback
- **Form Integration**: Updated jury dashboard frontend template with proper nonce handling

### 🚀 JavaScript Integration
- **AJAX Action Updates**: Updated JavaScript to use correct AJAX actions and endpoints
- **Nonce Handling**: Fixed nonce field references to use `mt_jury_dashboard.nonce`
- **Evaluation Loading**: Enhanced to handle both database records and draft formats
- **Form Compatibility**: Maintained compatibility with existing JavaScript functionality

### 🔄 Code Quality Improvements
- **Modular Architecture**: All handlers properly integrated into main plugin class
- **Error Logging**: Enhanced error handling with proper WordPress error responses
- **Code Documentation**: Comprehensive inline documentation for all new functions
- **Backward Compatibility**: All changes maintain existing functionality

## Recent Updates

### Core Class Structure Improvements
- Added proper property declarations in `MobilityTrailblazersPlugin` class to fix PHP 8.2+ dynamic property deprecation warnings
- Implemented core classes with proper namespacing:
  - `Evaluation` class for handling candidate evaluations
  - `JuryMember` class for managing jury member functionality
  - `Candidate` class for candidate data management
  - `Statistics` class for gathering and displaying statistics

### Jury Dashboard Enhancements
- Improved shortcode handler with proper attribute handling:
  - Added default values for all attributes
  - Converted string 'yes'/'no' values to proper booleans
  - Added proper type hints and documentation
- Enhanced jury dashboard template:
  - Added proper escaping for all output
  - Improved error handling and user checks
  - Added proper data fetching using core classes
  - Implemented filtering and pagination support
  - Added support for multiple display options:
    - Statistics display
    - Assigned candidates list
    - Evaluation progress
    - Round selector
    - Category filter
    - Search functionality
    - Sorting options

### File Structure Changes
- Consolidated frontend JavaScript:
  - Merged functionality from `assets/js/frontend.js` into `assets/frontend.js`
  - Removed duplicate file `assets/js/frontend.js`
  - Maintained all existing functionality while improving code organization

### Database Improvements
- Enhanced database table creation process:
  - Added proper constraint handling
  - Improved table structure for `mt_votes` and `vote_reset_logs`
  - Added unique key for `unique_vote`
  - Implemented proper foreign key constraints

### Elementor Integration
- Improved Elementor widget loading:
  - Added proper checks for Elementor's existence
  - Enhanced widget registration process
  - Fixed class loading issues
  - Improved error handling

### Changed
- Reorganized asset files into proper directories:
  - Moved CSS files to `assets/css/`
  - Moved JavaScript files to `assets/js/`
- Updated all asset references in PHP files to use new paths
- Improved asset loading with proper dependencies
- Added proper versioning for all assets

### Removed
- Removed duplicate asset files from root assets directory
- Removed old asset paths from documentation

## Technical Details - Version 2.2.0

### Jury Synchronization Implementation
```php
// Automatic jury post creation when user gets jury role
class JurySync {
    public function handle_user_role_add($user_id, $role) {
        if ($role === 'mt_jury_member') {
            $this->create_jury_post_for_user($user_id);
        }
    }
    
    // Bidirectional sync on jury post changes
    public function handle_jury_post_trash($post_id) {
        if (get_post_type($post_id) !== 'mt_jury') return;
        
        $user_id = get_post_meta($post_id, '_mt_jury_user_id', true);
        if ($user_id) {
            $user = get_user_by('id', $user_id);
            if ($user && in_array('mt_jury_member', $user->roles)) {
                $user->remove_role('mt_jury_member');
            }
        }
    }
}
```

### Diagnostic System Structure
```php
// Comprehensive system checks
private function check_database_tables() {
    $tables = array(
        'mt_votes' => 'Votes',
        'mt_vote_backups' => 'Vote Backups',
        'vote_reset_logs' => 'Vote Reset Logs',
        'mt_vote_audit_log' => 'Vote Audit Log',
        'mt_candidate_scores' => 'Candidate Scores'
    );
    
    foreach ($tables as $table_suffix => $table_label) {
        $table_name = $wpdb->prefix . $table_suffix;
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        // Display status with color coding
    }
}
```

### Enhanced Database Schema
```sql
-- Updated vote_reset_logs table with comprehensive audit trail
CREATE TABLE IF NOT EXISTS `wp_vote_reset_logs` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `reset_type` varchar(50) NOT NULL,
    `initiated_by` bigint(20) NOT NULL,
    `initiated_by_role` varchar(50) DEFAULT NULL,
    `affected_user_id` bigint(20) DEFAULT NULL,
    `affected_candidate_id` bigint(20) DEFAULT NULL,
    `voting_phase` varchar(50) DEFAULT NULL,
    `votes_affected` int(11) DEFAULT 0,
    `reset_reason` text NOT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `reset_timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `initiated_by` (`initiated_by`),
    KEY `affected_user_id` (`affected_user_id`),
    KEY `affected_candidate_id` (`affected_candidate_id`)
);
```

## Usage Examples - Version 2.2.0

### Manual Jury Synchronization
```php
// Trigger manual sync via URL parameter
// Visit: /wp-admin/admin.php?page=mt-diagnostic&mt_sync_jury=1

// Programmatic sync
$jury_sync = \MobilityTrailblazers\JurySync::get_instance();
$result = $jury_sync->sync_all_jury_members();
// Returns: array('synced' => 5, 'errors' => 0)
```

### Diagnostic Page Integration
```php
// Access diagnostic information
// Visit: /wp-admin/admin.php?page=mt-diagnostic

// Check specific sync status
$jury_sync = \MobilityTrailblazers\JurySync::get_instance();
$jury_post_id = $jury_sync->get_jury_post_for_user($user_id);
```

### Action Hooks for Extensions
```php
// Hook into jury post lifecycle
add_action('mt_jury_post_created', function($jury_post_id, $user_id) {
    // Custom logic when jury post is created
    update_post_meta($jury_post_id, 'custom_field', 'value');
});

add_action('mt_jury_post_deactivated', function($jury_post_id, $user_id) {
    // Custom logic when jury post is deactivated
    // e.g., send notification email
});
```

### Frontend Asset Integration
```php
// Enhanced frontend JavaScript with AJAX utilities
window.MobilityTrailblazers = {
    ajaxRequest: function(action, data, callback) {
        $.ajax({
            url: mtFrontend.ajax_url,
            type: 'POST',
            data: {
                action: action,
                nonce: mtFrontend.nonce,
                ...data
            },
            success: callback,
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
            }
        });
    }
};
```

## Bug Fixes - Version 2.2.0

### Namespace Resolution
```php
// Before (causing errors)
new WP_Query($args);
new WP_Error('code', 'message');
new VoteAuditLogger();

// After (properly namespaced)
new \WP_Query($args);
new \WP_Error('code', 'message');
new \MT_Vote_Audit_Logger();
```

### Template Safety Checks
```php
// Enhanced template compatibility
$candidate_id = is_object($candidate) ? $candidate->ID : 
                (is_array($candidate) ? $candidate['id'] : 0);
$candidate_title = is_object($candidate) ? $candidate->post_title : 
                   (is_array($candidate) ? $candidate['title'] : '');
```

## Technical Details - Version 2.1.0

### Property Declarations
```php
class MobilityTrailblazersPlugin {
    private $evaluation;
    private $jury_member;
    private $candidate;
    private $statistics;
    // ... other properties
}
```

### Shortcode Attributes
```php
$atts = shortcode_atts(array(
    'show_stats' => 'yes',
    'show_assignments' => 'yes',
    'show_evaluations' => 'yes',
    'show_public_voting' => 'yes',
    'show_round_selector' => 'yes',
    'show_category_filter' => 'yes',
    'show_search' => 'yes',
    'show_sort' => 'yes',
    'show_pagination' => 'yes',
    'items_per_page' => 10,
    // ... other attributes
), $atts, 'jury_dashboard');
```

### Database Tables
```sql
CREATE TABLE IF NOT EXISTS `wp_mt_votes` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `candidate_id` bigint(20) NOT NULL,
    `jury_member_id` bigint(20) NOT NULL,
    `vote_round` varchar(50) NOT NULL,
    `score` decimal(5,2) NOT NULL,
    `comments` text,
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_vote` (`candidate_id`, `jury_member_id`, `vote_round`)
);

CREATE TABLE IF NOT EXISTS `wp_vote_reset_logs` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `initiated_by` bigint(20) NOT NULL,
    `affected_user_id` bigint(20) NOT NULL,
    `reset_type` varchar(50) NOT NULL,
    `reset_reason` text,
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`id`)
);
```

## Usage Examples

### Jury Dashboard Shortcode
```php
[jury_dashboard show_stats="yes" show_assignments="yes" show_evaluations="yes"]
```

### Elementor Widget
```php
add_action('elementor/widgets/widgets_registered', function($widgets_manager) {
    if (class_exists('\Elementor\Widget_Base')) {
        $widgets_manager->register(new \MobilityTrailblazers\Integrations\Elementor\Widgets\JuryDashboardWidget());
    }
});
```

## Security Improvements
- Added proper nonce verification
- Implemented proper capability checks
- Added input sanitization
- Enhanced output escaping
- Improved error handling

## Performance Optimizations
- Consolidated JavaScript files
- Improved database queries
- Enhanced caching mechanisms
- Optimized template loading

## Future Considerations
1. Implement caching for frequently accessed data
2. Add more comprehensive error logging
3. Enhance the statistics gathering system
4. Improve the user interface for better usability
5. Add more customization options for the jury dashboard

## Known Issues
- None currently reported

## Dependencies
- WordPress 5.0+
- PHP 7.4+
- Elementor (optional)
- jQuery (included with WordPress)

## Support
For support, please contact the plugin maintainers or create an issue in the repository. 