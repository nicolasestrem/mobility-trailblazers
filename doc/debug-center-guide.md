# Debug Center Guide - Mobility Trailblazers v2.3.0

## Overview

The Debug Center provides a unified, professional interface for all debugging, diagnostic, and maintenance operations in the Mobility Trailblazers platform. It replaces the previously scattered debug tools with a centralized, secure, and environment-aware system.

## Key Features

### 🔒 Environment-Aware Security
- Automatic environment detection (Development/Staging/Production)
- Restricted operations in production environments
- Dangerous operations require additional confirmation
- Complete audit logging of all debug operations

### 📊 System Diagnostics
- Comprehensive health checks
- Real-time performance monitoring
- Database integrity verification
- Security status assessment
- Error log analysis

### 🛠️ Maintenance Tools
- Database optimization and repair
- Cache management
- Orphaned data cleanup
- Data export/import utilities
- Factory reset capability (with password protection)

### 📝 Debug Script Management
- Categorized script organization
- Environment-based script filtering
- Safe execution sandbox
- Execution audit trail

## Accessing the Debug Center

### Location
Navigate to **MT Award System → Developer Tools** in the WordPress admin menu.

### Required Permissions
- Capability: `manage_options` (Administrator role)
- Future: `mt_debug_access` capability for granular control

### Environment Restrictions
The Debug Center automatically detects the environment and adjusts available features:

- **Development**: Full access to all features
- **Staging**: Most features available with warnings
- **Production**: Limited features, dangerous operations require confirmation

## Interface Tabs

### 1. System Diagnostics Tab
Perform comprehensive system health checks:

```
- Environment information (PHP, server, extensions)
- WordPress health status
- Database integrity and table status
- Plugin component validation
- Filesystem permissions
- Performance metrics
- Security recommendations
- Error log analysis
```

**Usage:**
1. Select diagnostic type (Full/Specific)
2. Click "Run Diagnostic"
3. Review results
4. Export as JSON if needed

### 2. Database Tools Tab
Manage database operations:

```
- Optimize tables
- Repair corrupted tables
- Clean orphaned data
- Sync evaluations with assignments
- Rebuild indexes
- Run migrations
```

### 3. Debug Scripts Tab
Execute categorized debug scripts:

**Categories:**
- **Generators**: Test data creation
- **Migrations**: Data structure updates
- **Diagnostics**: System checks
- **Repairs**: Fix data issues
- **Deprecated**: Old scripts (read-only)

**Security Features:**
- Scripts filtered by environment
- Dangerous scripts require confirmation
- All executions logged with user/IP/timestamp

### 4. Error Monitor Tab
Monitor and analyze errors:

```
- Real-time error tracking
- Filter by severity level
- Search error messages
- Export error logs
- Clear old logs
```

### 5. Maintenance Tools Tab
System maintenance operations:

```
Database Operations:
- Optimize all tables
- Repair tables
- Clean orphaned data
- Rebuild indexes

Cache Operations:
- Clear all caches
- Clear transients
- Regenerate cache indexes

Import/Export:
- Export all data
- Create backups
- Restore from backup

Reset Operations:
- Reset evaluations
- Reset assignments
- Factory reset (password required)
```

### 6. System Info Tab
View detailed system information:

```
- PHP configuration
- WordPress settings
- Server information
- Database details
- Plugin list
- Theme information
```

## Architecture

### Core Classes

#### MT_Diagnostic_Service
Located at: `includes/services/class-mt-diagnostic-service.php`

Responsibilities:
- System health monitoring
- Performance metrics collection
- Security assessment
- Error log analysis

Key Methods:
```php
run_full_diagnostic()     // Complete system check
run_diagnostic($type)     // Specific diagnostic
export_diagnostics()      // Export as JSON
```

#### MT_Debug_Manager
Located at: `includes/admin/class-mt-debug-manager.php`

Responsibilities:
- Script execution management
- Environment detection
- Security enforcement
- Audit logging

Key Methods:
```php
get_environment()           // Get current environment
is_script_allowed($script)  // Check script permissions
execute_script($script)     // Safe script execution
get_audit_log()            // Retrieve execution history
```

#### MT_Maintenance_Tools
Located at: `includes/admin/class-mt-maintenance-tools.php`

Responsibilities:
- Database maintenance
- Cache operations
- Data management
- System resets

Key Methods:
```php
execute_operation($category, $operation)  // Run maintenance task
optimize_tables()                         // Optimize DB tables
clear_all_caches()                       // Clear caches
export_all_data()                        // Export data
```

## Debug Script Organization

### Directory Structure
```
debug/
├── registry.json        # Script metadata
├── README.md           # Usage guidelines
├── generators/         # Test data scripts
├── migrations/         # Data migrations
├── diagnostics/        # System checks
├── repairs/           # Fix utilities
└── deprecated/        # Old scripts
```

### Script Registry (registry.json)
Each script is registered with:
- Title and description
- Allowed environments
- Danger level
- Version information
- Author details

Example:
```json
{
  "fake-candidates-generator.php": {
    "title": "Generate Fake Candidates",
    "description": "Creates test candidate data",
    "environments": ["development", "staging"],
    "dangerous": false,
    "version": "1.0.0"
  }
}
```

## Security Features

### Environment Detection
The system detects environment through:
1. `MT_ENVIRONMENT` constant (highest priority)
2. `WP_ENVIRONMENT_TYPE` constant
3. URL pattern detection (localhost, .local, staging)
4. Default to production (safest)

### Audit Logging
All debug operations are logged with:
- User ID and username
- Timestamp
- Operation performed
- IP address
- Environment
- Result/errors

### Access Control
- Role-based permissions
- Environment-based restrictions
- Operation-specific confirmations
- Password verification for destructive operations

## Best Practices

### For Developers

1. **Always check environment** before running dangerous operations
2. **Review audit logs** regularly for unauthorized access
3. **Export diagnostics** before making changes
4. **Create backups** before running migrations or repairs
5. **Test in development** first

### For Production

1. **Limit access** to Debug Center
2. **Monitor audit logs** for suspicious activity
3. **Schedule maintenance** during low-traffic periods
4. **Document all operations** performed
5. **Keep backups** before major operations

## Troubleshooting

### Common Issues

**Debug Center not accessible:**
- Check user permissions (need `manage_options`)
- Verify plugin is activated
- Check for PHP errors

**Scripts not showing:**
- Verify environment detection
- Check script registry.json
- Ensure script files exist

**Operations failing:**
- Check error logs
- Verify database connectivity
- Ensure sufficient permissions

### Error Messages

| Error | Cause | Solution |
|-------|-------|----------|
| "Script not allowed" | Environment restriction | Run in development/staging |
| "Dangerous operation" | Safety check | Confirm operation |
| "Invalid password" | Factory reset protection | Enter correct admin password |
| "Table missing" | Database issue | Run database migrations |

## API Usage

### Running Diagnostics Programmatically

```php
$diagnostic_service = \MobilityTrailblazers\Services\MT_Diagnostic_Service::get_instance();
$results = $diagnostic_service->run_full_diagnostic();
```

### Executing Maintenance Operations

```php
$maintenance = new \MobilityTrailblazers\Admin\MT_Maintenance_Tools();
$result = $maintenance->execute_operation('database', 'optimize_tables');
```

### Managing Debug Scripts

```php
$debug_manager = new \MobilityTrailblazers\Admin\MT_Debug_Manager();
if ($debug_manager->is_script_allowed('test-db-connection.php')) {
    $result = $debug_manager->execute_script('test-db-connection.php');
}
```

## Configuration

### Environment Setup

Define environment in `wp-config.php`:
```php
define('MT_ENVIRONMENT', 'development'); // or 'staging', 'production'
```

### Debug Settings

Enable debug logging:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## Future Enhancements

### Planned Features
- [ ] Scheduled diagnostics with email reports
- [ ] Performance baseline tracking
- [ ] Automated issue detection and alerts
- [ ] Integration with external monitoring services
- [ ] Custom diagnostic plugins
- [ ] REST API endpoints for remote monitoring

### Version Roadmap
- **v2.3.1**: Complete all tab implementations
- **v2.3.2**: Add AJAX operations for real-time updates
- **v2.3.3**: Implement scheduled diagnostics
- **v2.4.0**: REST API integration

## Support

For issues or questions about the Debug Center:
1. Check this documentation
2. Review error logs in the Error Monitor tab
3. Run System Diagnostics for health check
4. Contact development team with diagnostic export

---

*Last Updated: January 14, 2025 - Version 2.3.0*