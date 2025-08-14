# Debug Center Technical Documentation

## Architecture Overview

The Debug Center is a comprehensive developer tools system built for the Mobility Trailblazers WordPress plugin (v2.3.0). It provides a unified interface for system diagnostics, debug script management, and maintenance operations.

## Component Architecture

### Core Services Layer

#### MT_Diagnostic_Service
**Location:** `includes/services/class-mt-diagnostic-service.php`  
**Namespace:** `MobilityTrailblazers\Services`  
**Pattern:** Singleton

Key responsibilities:
- System health monitoring
- Performance metrics collection
- Security assessment
- Error log analysis
- Environment detection

**Public Methods:**
```php
get_instance()                    // Singleton accessor
run_full_diagnostic()             // Complete system check
run_diagnostic($type)             // Specific diagnostic
export_diagnostics($diagnostics)  // JSON export
```

**Diagnostic Types:**
- `environment` - PHP, server, extensions
- `wordpress` - WP configuration and health
- `database` - Table integrity and orphaned data
- `plugin` - Component registration status
- `filesystem` - Directory permissions
- `performance` - Memory and query metrics
- `security` - File permissions and capabilities
- `errors` - Error log analysis

#### MT_Debug_Manager
**Location:** `includes/admin/class-mt-debug-manager.php`  
**Namespace:** `MobilityTrailblazers\Admin`

Key responsibilities:
- Script execution management
- Environment-based filtering
- Audit logging
- Security enforcement

**Public Methods:**
```php
get_environment()                     // Current environment
is_production()                       // Production check
get_script_categories()               // Available categories
is_script_allowed($script)            // Permission check
get_script_info($script)              // Script metadata
execute_script($script, $params)      // Safe execution
get_audit_log($limit)                 // Execution history
clear_audit_log()                     // Clear history
```

**Script Categories:**
- `generators` - Test data creation
- `migrations` - Data structure updates
- `diagnostics` - System checks
- `repairs` - Fix utilities
- `imports` - Data import scripts
- `testing` - Functionality tests

#### MT_Maintenance_Tools
**Location:** `includes/admin/class-mt-maintenance-tools.php`  
**Namespace:** `MobilityTrailblazers\Admin`

Key responsibilities:
- Database operations
- Cache management
- Data import/export
- System resets

**Public Methods:**
```php
get_operations()                                  // Available operations
execute_operation($category, $operation, $params) // Run operation
```

**Operation Categories:**
- `database` - Optimize, repair, cleanup
- `cache` - Clear caches, regenerate indexes
- `import_export` - Data backup and restore
- `reset` - Clear evaluations, assignments, factory reset

### Utility Layer

#### MT_Database_Health
**Location:** `includes/utilities/class-mt-database-health.php`  
**Namespace:** `MobilityTrailblazers\Utilities`

**Public Methods:**
```php
check_all_tables()                    // Check all plugin tables
get_connection_info()                 // Database connection details
get_database_stats()                  // Size and row counts
analyze_table($table_name)            // Table analysis
get_fragmentation_info($table_name)   // Fragmentation check
get_slow_queries($limit)              // Slow query detection
```

#### MT_System_Info
**Location:** `includes/utilities/class-mt-system-info.php`  
**Namespace:** `MobilityTrailblazers\Utilities`

**Public Methods:**
```php
get_system_info()      // Complete system information
export_as_text()       // Text format export
```

**Information Categories:**
- PHP configuration
- WordPress settings
- Server information
- Database details
- Plugin list
- Theme information
- Important constants
- Network status

### AJAX Handler

#### MT_Debug_Ajax
**Location:** `includes/ajax/class-mt-debug-ajax.php`  
**Namespace:** `MobilityTrailblazers\Ajax`  
**Extends:** `MT_Base_Ajax`

**AJAX Actions:**
- `mt_run_diagnostic` - Run system diagnostics
- `mt_execute_debug_script` - Execute debug script
- `mt_run_maintenance` - Run maintenance operation
- `mt_export_diagnostics` - Export diagnostic data
- `mt_get_error_stats` - Get error statistics
- `mt_clear_debug_logs` - Clear log files
- `mt_get_database_health` - Database health check
- `mt_get_system_info` - System information
- `mt_refresh_debug_widget` - Refresh dashboard widget

## Frontend Architecture

### JavaScript Module
**Location:** `assets/js/debug-center.js`

**Core Object:** `MTDebugCenter`

**Key Methods:**
```javascript
init()                          // Initialize module
bindEvents()                    // Event binding
runDiagnostic(e)               // Run diagnostic
executeScript(e)               // Execute script
runMaintenance(e)              // Run maintenance
exportDiagnostics(e)           // Export results
showNotification(msg, type)    // User feedback
```

**Event Handlers:**
- Form submissions
- Button clicks
- Tab navigation
- Widget refresh

### CSS Styling
**Location:** `assets/css/debug-center.css`

**Key Components:**
- Environment badges
- Tab navigation
- Diagnostic sections
- Script cards
- Maintenance grids
- Progress indicators
- Modal dialogs
- Responsive breakpoints

## Template Structure

### Main Template
**Location:** `templates/admin/debug-center.php`

**Components:**
- Environment detection and display
- Tab navigation
- Tab content container
- Footer with system info

### Tab Templates
**Location:** `templates/admin/debug-center/`

Complete implementation (v2.3.0):
- `tab-diagnostics.php` - System diagnostics interface with health monitoring
- `tab-database.php` - Database tools with optimization and fragmentation analysis
- `tab-scripts.php` - Script runner with environment-based filtering
- `tab-errors.php` - Error monitor with filtering and log management
- `tab-tools.php` - Maintenance tools with cache and scheduled task management
- `tab-info.php` - System information with export capabilities

## Security Implementation

### Environment Detection
```php
1. Check MT_ENVIRONMENT constant
2. Check WP_ENVIRONMENT_TYPE constant
3. URL pattern detection (localhost, .local, staging)
4. Default to production (safest)
```

### Access Control
- Capability: `manage_options` (admin only)
- Future: `mt_debug_access` for granular control
- Nonce verification: `mt_debug_nonce`
- Script filtering by environment
- Dangerous operation confirmations
- Password verification for factory reset

### Audit Logging
**Storage:** WordPress option `mt_debug_script_audit`  
**Retention:** Last 100 entries

**Log Entry Structure:**
```php
[
    'script' => string,
    'category' => string,
    'user_id' => int,
    'user_login' => string,
    'environment' => string,
    'timestamp' => string,
    'ip_address' => string
]
```

## Data Flow

### Diagnostic Request Flow
1. User clicks "Run Diagnostic"
2. JavaScript sends AJAX request
3. `MT_Debug_Ajax::run_diagnostic()` handles request
4. `MT_Diagnostic_Service::run_diagnostic()` executes
5. Results cached in transient
6. JSON response sent to frontend
7. JavaScript displays results or triggers reload

### Script Execution Flow
1. User selects script to execute
2. Environment check performed
3. Dangerous operation confirmation (if needed)
4. Script file included in sandbox
5. Output captured and errors logged
6. Audit log entry created
7. Results displayed in modal

### Maintenance Operation Flow
1. User selects operation
2. Permission and environment checks
3. Password verification (if required)
4. Operation callback executed
5. Database/cache operations performed
6. Success/error feedback to user

## Database Schema

### Custom Tables Monitored
- `wp_mt_evaluations` - Evaluation data
- `wp_mt_jury_assignments` - Assignment records
- `wp_mt_votes` - Voting data
- `wp_mt_candidate_scores` - Score calculations
- `wp_mt_vote_backups` - Vote backups
- `wp_vote_reset_logs` - Reset logs
- `wp_mt_error_log` - Error logging

### Options Table Entries
- `mt_debug_center_settings` - Configuration
- `mt_debug_script_audit` - Audit log
- `mt_last_diagnostic_{user_id}` - Cached diagnostics (transient)
- `mt_last_sysinfo_{user_id}` - Cached system info (transient)

## Performance Considerations

### Caching Strategy
- Diagnostic results cached for 1 hour
- System info cached for 1 hour
- Widget refresh every 30 seconds (optional)
- Transient cleanup on cache clear

### Query Optimization
- Indexed lookups for table checks
- Limited result sets (10-50 records)
- Prepared statements for all queries
- Batch operations where possible

### Resource Management
- Memory limit checks before operations
- Execution time monitoring
- Output buffering for script execution
- Error handler restoration

## Integration Points

### Admin Menu System
**File:** `includes/admin/class-mt-admin.php`

Changes:
- Added Developer Tools menu item
- Legacy redirects for old pages
- Asset enqueuing for Debug Center
- Localization for JavaScript

### Plugin Initialization
**File:** `includes/core/class-mt-plugin.php`

Changes:
- Conditional loading of Debug classes
- AJAX handler initialization
- Utility class loading

### Script Registry
**File:** `debug/registry.json`

Structure:
```json
{
  "version": "2.3.0",
  "categories": {
    "category_name": {
      "title": string,
      "scripts": {
        "script.php": {
          "title": string,
          "environments": array,
          "dangerous": boolean
        }
      }
    }
  }
}
```

## Error Handling

### JavaScript Error Handling
```javascript
try {
    // Operation
} catch (error) {
    this.showNotification(error.message, 'error');
}
```

### PHP Error Handling
```php
try {
    // Operation
} catch (\Exception $e) {
    MT_Logger::log_error('context', $e->getMessage());
    $this->send_error($e->getMessage());
}
```

### Script Execution Error Capture
```php
set_error_handler(function($severity, $message, $file, $line) {
    // Capture to errors array
});
```

## Testing Considerations

### Unit Test Coverage
- Diagnostic service methods
- Script permission checks
- Database health checks
- System info gathering

### Integration Tests
- AJAX endpoint responses
- Script execution sandbox
- Maintenance operations
- Cache clearing

### Manual Testing Checklist
- [ ] Environment detection
- [ ] Tab navigation
- [ ] Diagnostic execution
- [ ] Script filtering by environment
- [ ] Dangerous operation confirmations
- [ ] Export functionality
- [ ] Error display
- [ ] Responsive design

## Future Enhancements

### Planned Features
1. Scheduled diagnostics with email reports
2. Performance baseline tracking
3. Automated issue detection
4. REST API endpoints
5. Custom diagnostic plugins
6. Remote monitoring integration

### Technical Debt
1. Complete remaining tab templates
2. Add unit test coverage
3. Implement scheduled cleanup
4. Add more detailed logging
5. Create diagnostic plugins system

## Deployment Notes

### Environment Configuration
```php
// wp-config.php
define('MT_ENVIRONMENT', 'development'); // or 'staging', 'production'
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Required Permissions
- File system: Read/write for logs
- Database: CREATE, ALTER, DROP for maintenance
- PHP: exec() for certain scripts (optional)

### Production Considerations
- Limit access to admin users only
- Monitor audit logs regularly
- Schedule maintenance during low traffic
- Keep backups before operations
- Test all operations in staging first

---

*Technical Documentation - Version 2.3.0*  
*Last Updated: January 14, 2025*