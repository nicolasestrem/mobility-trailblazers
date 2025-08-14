# Debug Center Developer Guide

## Overview
The Debug Center is a comprehensive developer tools interface for the Mobility Trailblazers plugin, providing centralized access to diagnostics, debugging scripts, database tools, error monitoring, and system maintenance operations.

## Architecture

### Core Components

#### 1. MT_Debug_Manager (`includes/admin/class-mt-debug-manager.php`)
- Manages debug script registration and execution
- Environment-aware script filtering (Development/Staging/Production)
- Audit logging for all script executions
- Security controls for dangerous operations

#### 2. MT_Diagnostic_Service (`includes/services/class-mt-diagnostic-service.php`)
- Singleton pattern implementation
- Comprehensive system health checks
- Performance metrics collection
- Error log analysis
- Security status assessment

#### 3. MT_Maintenance_Tools (`includes/admin/class-mt-maintenance-tools.php`)
- Database optimization and repair operations
- Cache management utilities
- Data export/import functionality
- Factory reset with password verification

#### 4. MT_Debug_Ajax (`includes/ajax/class-mt-debug-ajax.php`)
- Extends MT_Base_Ajax for consistent error handling
- Handles all Debug Center AJAX operations
- Implements required abstract init() method
- Nonce verification for all operations

### Utility Classes

#### MT_Database_Health (`includes/utilities/class-mt-database-health.php`)
- Table health monitoring with fragmentation detection
- Slow query identification
- Database statistics gathering
- Connection information retrieval

#### MT_System_Info (`includes/utilities/class-mt-system-info.php`)
- PHP configuration details
- WordPress environment information
- Server specifications
- Plugin and theme information
- Export functionality for support

## Debug Center Tabs

### 1. Diagnostics Tab
**Template**: `templates/admin/debug-center/tab-diagnostics.php`
- Real-time system health monitoring
- Environment detection and display
- Quick diagnostic execution
- Results export as JSON

### 2. Database Tab
**Template**: `templates/admin/debug-center/tab-database.php`
- Table health visualization
- Fragmentation analysis
- Optimization operations
- Slow query monitoring

### 3. Scripts Tab
**Template**: `templates/admin/debug-center/tab-scripts.php`
- Categorized script listing (generators, diagnostics, repairs, etc.)
- Environment-based filtering
- Execution audit log
- Dangerous operation warnings

### 4. Errors Tab
**Template**: `templates/admin/debug-center/tab-errors.php`
- Error statistics dashboard
- Error type distribution
- Recent errors display
- Log management operations

### 5. Tools Tab
**Template**: `templates/admin/debug-center/tab-tools.php`
- Cache management operations
- Data import/export tools
- Reset operations with confirmations
- Scheduled task management

### 6. Info Tab
**Template**: `templates/admin/debug-center/tab-info.php`
- Complete system information display
- PHP extensions status
- WordPress configuration
- Export capabilities for support

## Known Issues and Solutions (v2.3.2)

### Script Output Display
**Issue**: Debug scripts showing empty info boxes when executed
**Solution**: Script output is now rendered as HTML instead of being escaped
- Output is displayed in a `<div class="mt-script-output">` container
- HTML formatting is preserved for proper display
- CSS styling ensures readability

### Common Runtime Errors (Fixed in v2.3.1)
1. **Missing init() method**: MT_Debug_Ajax now properly implements the abstract method
2. **Undefined JavaScript methods**: Removed non-existent method bindings
3. **Array access warnings**: Added defensive checks with isset() and default values
4. **Database connection failures**: Graceful fallbacks when database is unavailable

## Security Features

### Environment Controls
```php
// Environment detection in MT_Debug_Manager
$this->environment = $this->detect_environment();

// Script filtering based on environment
if ($this->environment === 'production' && !$script_info['allow_production']) {
    return ['success' => false, 'message' => 'Script not allowed in production'];
}
```

### Dangerous Operations Protection
- Confirmation dialogs for destructive operations
- Password verification for factory reset
- Audit logging with IP tracking
- Role-based access control (manage_options capability)

## Debug Scripts Organization

### Directory Structure
```
debug/
├── generators/       # Data generation scripts
├── migrations/      # Data migration utilities
├── diagnostics/     # System diagnostic scripts
├── repairs/         # Database and data repair scripts
├── imports/         # Import testing scripts
├── testing/         # Test scripts
└── deprecated/      # Old scripts (not shown in UI)
```

### Script Registry (`debug/registry.json`)
```json
{
  "scripts": {
    "script-name.php": {
      "title": "Script Title",
      "description": "What this script does",
      "category": "diagnostics",
      "dangerous": false,
      "allow_production": false,
      "requires_backup": false
    }
  }
}
```

## JavaScript Implementation

### MTDebugCenter Object (`assets/js/debug-center.js`)
```javascript
const MTDebugCenter = {
    init: function() {
        this.bindEvents();
        this.initActiveTab();
        this.initTooltips(); // Optional jQuery UI
        this.startAutoRefresh();
    },
    
    runDiagnostic: function(e) {
        // Handles diagnostic execution
    },
    
    executeScript: function(e) {
        // Manages debug script execution
    },
    
    runMaintenance: function(e) {
        // Executes maintenance operations
    }
};
```

## AJAX Operations

### Available Actions
- `mt_run_diagnostic` - Execute system diagnostics
- `mt_export_diagnostics` - Export diagnostic results
- `mt_execute_debug_script` - Run debug scripts
- `mt_run_maintenance` - Execute maintenance operations
- `mt_clear_debug_logs` - Clear error logs
- `mt_get_error_stats` - Retrieve error statistics
- `mt_refresh_debug_widget` - Refresh dashboard widgets

### Example AJAX Call
```javascript
$.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'mt_run_diagnostic',
        nonce: mt_debug.nonce,
        diagnostic_type: 'full'
    },
    success: function(response) {
        // Handle response
    }
});
```

## Error Handling

### Defensive Programming
All Debug Center components implement defensive programming patterns:
```php
// Example from tab templates
$db_stats = $db_health->get_database_stats();
if (!is_array($db_stats)) {
    $db_stats = [
        'total_tables' => 0,
        'total_rows' => 0,
        'total_size_formatted' => 'N/A',
        'plugin_tables' => 0
    ];
}
```

### Database Connection Resilience
The Debug Center remains functional even when database connection is unavailable:
- Graceful fallbacks for missing data
- Default values for all statistics
- Error state visualization

## Adding New Debug Scripts

### 1. Create the Script
```php
<?php
// Security check
if (!defined('ABSPATH')) {
    die('Direct access forbidden.');
}

// Your script logic here
echo "Script output";
```

### 2. Register in registry.json
```json
{
  "your-script.php": {
    "title": "Your Script",
    "description": "What it does",
    "category": "diagnostics",
    "dangerous": false,
    "allow_production": false
  }
}
```

### 3. Place in Correct Directory
- generators/ - For data generation
- diagnostics/ - For system checks
- repairs/ - For fixing issues
- migrations/ - For data migration

## Maintenance Operations

### Available Operations
- **Cache Operations**: Clear transients, object cache, plugin cache
- **Database Operations**: Optimize tables, repair tables, clean orphaned data
- **Import/Export**: Export all data, create backups, import data
- **Reset Operations**: Reset evaluations, assignments, factory reset

### Adding New Operations
```php
// In MT_Maintenance_Tools::register_operations()
'your_operation' => [
    'title' => __('Your Operation', 'mobility-trailblazers'),
    'description' => __('What it does', 'mobility-trailblazers'),
    'icon' => 'admin-tools',
    'button_text' => __('Execute', 'mobility-trailblazers'),
    'confirm' => __('Are you sure?', 'mobility-trailblazers')
]
```

## Troubleshooting

### Common Issues

1. **Scripts not executing**: Check environment settings and script registry
2. **Database errors**: Verify table names (use wp_mt_jury_assignments not wp_mt_assignments)
3. **JavaScript errors**: Ensure jQuery UI is loaded if using tooltips
4. **Array errors**: Check array validation in templates
5. **Method visibility**: Use public MT_Logger methods (info, error, warning)

### Debug Mode
Enable WordPress debug mode for detailed error logging:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## Best Practices

1. **Always verify nonces** in AJAX handlers
2. **Check capabilities** before operations
3. **Use defensive programming** for array access
4. **Log important operations** for audit trail
5. **Provide user feedback** for all operations
6. **Test in all environments** before deployment
7. **Document dangerous operations** clearly
8. **Implement confirmation dialogs** for destructive actions

## Version History

- **v2.3.0** - Initial Debug Center implementation
- **v2.3.1** - Bug fixes and stability improvements
  - Fixed runtime errors
  - Improved error handling
  - Added missing templates
  - Enhanced array validation