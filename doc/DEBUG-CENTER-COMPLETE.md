# Debug Center Complete Documentation
*Last Updated: August 16, 2025 | Version 2.3.2*

## Table of Contents
1. [Overview](#overview)
2. [Key Features](#key-features)
3. [Architecture](#architecture)
4. [User Guide](#user-guide)
5. [Technical Implementation](#technical-implementation)
6. [Troubleshooting](#troubleshooting)
7. [Security Features](#security-features)
8. [Debug Scripts](#debug-scripts)
9. [Maintenance Operations](#maintenance-operations)
10. [API Reference](#api-reference)
11. [Testing & Validation](#testing--validation)
12. [Version History](#version-history)

---

## Overview

The Debug Center provides a unified, professional interface for all debugging, diagnostic, and maintenance operations in the Mobility Trailblazers platform. It replaces previously scattered debug tools with a centralized, secure, and environment-aware system.

### Purpose
- Centralize all developer tools and diagnostics
- Provide environment-aware security controls
- Enable safe maintenance operations
- Track and audit all debug activities
- Facilitate troubleshooting and support

### Access Requirements
- **Location**: MT Award System → Developer Tools
- **Capability**: `manage_options` (Administrator role)
- **Future**: `mt_debug_access` capability for granular control

---

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

---

## Architecture

### Core Component Architecture

The Debug Center follows a modular architecture with clear separation of concerns:

```
Debug Center Architecture
├── Services Layer
│   ├── MT_Diagnostic_Service (Singleton)
│   └── MT_Error_Monitor
├── Admin Layer
│   ├── MT_Debug_Manager
│   └── MT_Maintenance_Tools
├── Utilities Layer
│   ├── MT_Database_Health
│   └── MT_System_Info
├── AJAX Handler
│   └── MT_Debug_Ajax
└── Frontend
    ├── JavaScript (MTDebugCenter)
    └── Templates (6 tabs)
```

### Core Classes

#### MT_Diagnostic_Service
**Location:** `includes/services/class-mt-diagnostic-service.php`  
**Namespace:** `MobilityTrailblazers\Services`  
**Pattern:** Singleton

Responsibilities:
- System health monitoring
- Performance metrics collection
- Security assessment
- Error log analysis
- Environment detection

Key Methods:
```php
get_instance()                    // Singleton accessor
run_full_diagnostic()             // Complete system check
run_diagnostic($type)             // Specific diagnostic
export_diagnostics($diagnostics)  // JSON export
```

#### MT_Debug_Manager
**Location:** `includes/admin/class-mt-debug-manager.php`  
**Namespace:** `MobilityTrailblazers\Admin`

Responsibilities:
- Script execution management
- Environment-based filtering
- Audit logging
- Security enforcement

Key Methods:
```php
get_environment()                     // Current environment
is_production()                       // Production check
get_script_categories()               // Available categories
is_script_allowed($script)            // Permission check
execute_script($script, $params)      // Safe execution
get_audit_log($limit)                 // Execution history
```

#### MT_Maintenance_Tools
**Location:** `includes/admin/class-mt-maintenance-tools.php`  
**Namespace:** `MobilityTrailblazers\Admin`

Responsibilities:
- Database operations
- Cache management
- Data import/export
- System resets

---

## User Guide

### Interface Tabs

#### 1. System Diagnostics Tab
Perform comprehensive system health checks:

- Environment information (PHP, server, extensions)
- WordPress health status
- Database integrity and table status
- Plugin component validation
- Filesystem permissions
- Performance metrics
- Security recommendations
- Error log analysis

**Usage:**
1. Select diagnostic type (Full/Specific)
2. Click "Run Diagnostic"
3. Review results
4. Export as JSON if needed

#### 2. Database Tools Tab
Manage database operations:

- Optimize tables
- Repair corrupted tables
- Clean orphaned data
- Sync evaluations with assignments
- Rebuild indexes
- Run migrations
- Delete all candidates (v2.3.5)

**Delete All Candidates Feature:**
- Red danger button styling for visibility
- Requires typing "DELETE" to confirm
- Deletes all candidates and associated data
- Uses database transactions for safety
- Full rollback on error

#### 3. Debug Scripts Tab
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

#### 4. Error Monitor Tab
Monitor and analyze errors:

- Real-time error tracking
- Filter by severity level
- Search error messages
- Export error logs
- Clear old logs

#### 5. Maintenance Tools Tab
System maintenance operations:

**Database Operations:**
- Optimize all tables
- Repair tables
- Clean orphaned data
- Rebuild indexes

**Cache Operations:**
- Clear all caches
- Clear transients
- Regenerate cache indexes

**Import/Export:**
- Export all data
- Create backups
- Restore from backup

**Reset Operations:**
- Reset evaluations
- Reset assignments
- Factory reset (password required)

#### 6. System Info Tab
View detailed system information:

- PHP configuration
- WordPress settings
- Server information
- Database details
- Plugin list
- Theme information

---

## Technical Implementation

### Frontend Architecture

#### JavaScript Module
**Location:** `assets/js/debug-center.js`

**Core Object:** `MTDebugCenter`

```javascript
const MTDebugCenter = {
    init: function() {
        this.bindEvents();
        this.initActiveTab();
        this.initTooltips();
        this.startAutoRefresh();
    },
    
    runDiagnostic: function(e) {
        // Handles diagnostic execution
    },
    
    executeScript: function(e) {
        // Manages debug script execution
        // v2.3.2: Fixed output display issue
    },
    
    showScriptOutput: function(data) {
        // v2.3.2: Now renders HTML output properly
        const $modal = $('<div class="mt-modal">').html(`
            <div class="mt-script-output">${data.output || '<p>No output generated</p>'}</div>
        `);
    }
};
```

### AJAX Implementation

#### Available Actions
- `mt_run_diagnostic` - Execute system diagnostics
- `mt_execute_debug_script` - Run debug scripts
- `mt_run_maintenance` - Run maintenance operation
- `mt_export_diagnostics` - Export diagnostic data
- `mt_get_error_stats` - Get error statistics
- `mt_clear_debug_logs` - Clear log files
- `mt_get_database_health` - Database health check
- `mt_get_system_info` - System information
- `mt_refresh_debug_widget` - Refresh dashboard widget

#### Data Flow Example
```javascript
// Diagnostic Request Flow
1. User clicks "Run Diagnostic"
2. JavaScript sends AJAX request
3. MT_Debug_Ajax::run_diagnostic() handles request
4. MT_Diagnostic_Service::run_diagnostic() executes
5. Results cached in transient
6. JSON response sent to frontend
7. JavaScript displays results or triggers reload
```

### Database Schema

#### Custom Tables Monitored
- `wp_mt_evaluations` - Evaluation data
- `wp_mt_jury_assignments` - Assignment records
- `wp_mt_votes` - Voting data
- `wp_mt_candidate_scores` - Score calculations
- `wp_mt_vote_backups` - Vote backups
- `wp_vote_reset_logs` - Reset logs
- `wp_mt_error_log` - Error logging

#### Options Table Entries
- `mt_debug_center_settings` - Configuration
- `mt_debug_script_audit` - Audit log
- `mt_last_diagnostic_{user_id}` - Cached diagnostics (transient)
- `mt_last_sysinfo_{user_id}` - Cached system info (transient)

---

## Troubleshooting

### Fixed Issues (v2.3.2)

#### Debug Scripts Showing Empty Info Box

**Symptoms:**
- Clicking "Execute" on debug scripts shows an empty modal
- Script executes but output is not visible

**Root Cause:** JavaScript was escaping HTML output

**Solution Applied:**
```javascript
// OLD (Incorrect)
showScriptOutput: function(data) {
    const $modal = $('<div class="mt-modal">').html(`
        <pre>${this.escapeHtml(data.output || '')}</pre>
    `);
}

// NEW (Fixed)
showScriptOutput: function(data) {
    const $modal = $('<div class="mt-modal">').html(`
        <div class="mt-script-output">${data.output || '<p>No output generated</p>'}</div>
    `);
}
```

#### Operation Not Found Error

**Symptoms:**
- Maintenance operation buttons trigger "Operation not found" error

**Root Cause:** Template accessing operations at wrong path

**Solution Applied:**
```php
// OLD (Incorrect path)
<?php foreach ($operations['cache'] as $op_key => $operation): ?>

// NEW (Correct path)  
<?php foreach ($operations['cache']['operations'] as $op_key => $operation): ?>
```

### Fixed Issues (v2.3.1)

1. **PHP Fatal Error**: Implemented required `init()` method in MT_Debug_Ajax
2. **JavaScript Errors**: Removed bindings for non-existent methods
3. **PHP Warnings**: Added defensive programming with isset() checks
4. **Database Connection Errors**: Added graceful fallbacks
5. **Private Method Access**: Changed method visibility to public

### Common Issues and Solutions

#### Scripts Not Executing
**Check:**
1. Verify user has `manage_options` capability
2. Check browser console for AJAX errors
3. Ensure nonce is valid (refresh page if stale)

**Debug:**
```javascript
// Check in browser console
console.log(mt_debug.nonce);
console.log(mt_debug.ajax_url);
```

#### Maintenance Operations Failing
**Check:**
1. Verify operation exists in MT_Maintenance_Tools
2. Check if operation requires confirmation
3. Ensure proper data attributes

#### Tab Content Not Loading
**Check:**
1. Verify template file exists
2. Check for PHP errors in template
3. Enable WP_DEBUG for details

---

## Security Features

### Environment Detection
The system detects environment through:
1. `MT_ENVIRONMENT` constant (highest priority)
2. `WP_ENVIRONMENT_TYPE` constant
3. URL pattern detection (localhost, .local, staging)
4. Default to production (safest)

### Environment Controls
| Environment | Detection | Available Scripts |
|------------|-----------|------------------|
| Development | localhost, .local, .test | All scripts |
| Staging | staging in URL | Most scripts |
| Production | Default | Limited scripts |

### Audit Logging
All debug operations are logged with:
- User ID and username
- Timestamp
- Operation performed
- IP address
- Environment
- Result/errors

**Storage:** WordPress option `mt_debug_script_audit`  
**Retention:** Last 100 entries

### Access Control
- Role-based permissions
- Environment-based restrictions
- Operation-specific confirmations
- Password verification for destructive operations

---

## Debug Scripts

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

### Script Registry Format
```json
{
  "script-name.php": {
    "title": "Script Title",
    "description": "What this script does",
    "category": "diagnostics",
    "dangerous": false,
    "allow_production": false,
    "requires_backup": false
  }
}
```

### Adding New Scripts

1. **Create the Script:**
```php
<?php
// Security check
if (!defined('ABSPATH')) {
    die('Direct access forbidden.');
}

// Your script logic here
echo "<h3>Script Output</h3>";
echo "<p>Operation completed successfully.</p>";
```

2. **Register in registry.json:**
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

3. **Place in Correct Directory:**
- `generators/` - For data generation
- `diagnostics/` - For system checks
- `repairs/` - For fixing issues
- `migrations/` - For data migration

---

## Maintenance Operations

### Available Operations

#### Database Operations
- Optimize tables
- Repair tables
- Clean orphaned data
- Sync evaluations
- Rebuild indexes

#### Cache Operations
- Clear all caches
- Clear transients
- Clear object cache
- Regenerate indexes

#### Import/Export
- Export all data
- Create backups
- Import data
- Restore backups

#### Reset Operations
- Reset evaluations
- Reset assignments
- Factory reset (password required)

### Adding New Operations

```php
// In MT_Maintenance_Tools::register_operations()
'your_operation' => [
    'title' => __('Your Operation', 'mobility-trailblazers'),
    'description' => __('What it does', 'mobility-trailblazers'),
    'icon' => 'admin-tools',
    'button_text' => __('Execute', 'mobility-trailblazers'),
    'confirm' => __('Are you sure?', 'mobility-trailblazers'),
    'dangerous' => true,
    'requires_password' => false
]
```

---

## API Reference

### Running Diagnostics Programmatically

```php
// Get diagnostic service instance
$diagnostic_service = \MobilityTrailblazers\Services\MT_Diagnostic_Service::get_instance();

// Run full diagnostic
$results = $diagnostic_service->run_full_diagnostic();

// Run specific diagnostic
$results = $diagnostic_service->run_diagnostic('database');

// Export results
$json = $diagnostic_service->export_diagnostics($results);
```

### Executing Maintenance Operations

```php
// Create maintenance tools instance
$maintenance = new \MobilityTrailblazers\Admin\MT_Maintenance_Tools();

// Execute operation
$result = $maintenance->execute_operation('database', 'optimize_tables');

// Check result
if ($result['success']) {
    echo $result['message'];
} else {
    error_log($result['message']);
}
```

### Managing Debug Scripts

```php
// Create debug manager instance
$debug_manager = new \MobilityTrailblazers\Admin\MT_Debug_Manager();

// Check if script is allowed
if ($debug_manager->is_script_allowed('test-db-connection.php')) {
    // Execute script
    $result = $debug_manager->execute_script('test-db-connection.php');
    
    // Check result
    if ($result['success']) {
        echo $result['output'];
    }
}

// Get audit log
$audit_log = $debug_manager->get_audit_log(50);
```

---

## Testing & Validation

### Quick Test Checklist (10 minutes)

#### Initial Page Load (2 minutes)
- [ ] Navigate to Developer Tools
- [ ] Open browser console (F12)
- [ ] Check for JavaScript errors
- [ ] Verify initialization messages

#### Tab Navigation (2 minutes)
- [ ] Click through all 6 tabs
- [ ] Verify content loads for each
- [ ] Check for PHP warnings/errors
- [ ] Confirm responsive layout

#### Diagnostics Test (2 minutes)
- [ ] Run full diagnostic
- [ ] Verify results display
- [ ] Export results as JSON
- [ ] Check cached results

#### Script Execution (2 minutes)
- [ ] Execute a safe script
- [ ] Verify output displays
- [ ] Check audit log entry
- [ ] Test dangerous script warning

#### Maintenance Operation (2 minutes)
- [ ] Clear cache operation
- [ ] Verify confirmation dialog
- [ ] Check success message
- [ ] Test cancel functionality

### Automated Testing

#### Unit Tests
```php
// Test diagnostic service
$diagnostic = MT_Diagnostic_Service::get_instance();
$this->assertInstanceOf('MT_Diagnostic_Service', $diagnostic);

// Test environment detection
$debug_manager = new MT_Debug_Manager();
$env = $debug_manager->get_environment();
$this->assertContains($env, ['development', 'staging', 'production']);
```

#### Integration Tests
- AJAX endpoint responses
- Script execution sandbox
- Database operations
- Cache management

### Manual Testing Checklist
- [ ] Environment detection works
- [ ] All tabs load properly
- [ ] Diagnostics execute
- [ ] Scripts filter by environment
- [ ] Dangerous operations show warnings
- [ ] Export functionality works
- [ ] Error monitoring displays
- [ ] Responsive design functions

---

## Version History

### v2.3.2 (August 14, 2025)
- Fixed script output display issue (HTML rendering)
- Fixed operation path in maintenance tools
- Improved error handling in AJAX responses
- Enhanced CSS for script output display

### v2.3.1 (August 14, 2025)
- Fixed abstract method implementation in MT_Debug_Ajax
- Removed non-existent JavaScript method bindings
- Added defensive programming for array access
- Fixed database connection error handling
- Changed private methods to public visibility

### v2.3.0 (August 14, 2025)
- Initial Debug Center implementation
- Complete tab system with 6 functional tabs
- Environment-aware security system
- Comprehensive diagnostic service
- Debug script management
- Maintenance tools suite
- Audit logging system

### Future Roadmap

#### v2.3.3 (Planned)
- Scheduled diagnostics with email reports
- Performance baseline tracking
- Automated issue detection

#### v2.4.0 (Planned)
- REST API endpoints
- Remote monitoring integration
- Custom diagnostic plugins
- Advanced reporting features

---

## Best Practices

### For Developers
1. **Always check environment** before running dangerous operations
2. **Review audit logs** regularly for unauthorized access
3. **Export diagnostics** before making changes
4. **Create backups** before running migrations or repairs
5. **Test in development** first
6. **Document all operations** performed
7. **Use defensive programming** for array access
8. **Implement confirmation dialogs** for destructive actions

### For Production
1. **Limit access** to Debug Center
2. **Monitor audit logs** for suspicious activity
3. **Schedule maintenance** during low-traffic periods
4. **Document all operations** performed
5. **Keep backups** before major operations
6. **Test changes in staging** first

### Security Guidelines
1. **Never share** debug outputs containing sensitive data
2. **Rotate passwords** after factory resets
3. **Review permissions** regularly
4. **Monitor access logs** for unusual patterns
5. **Keep WordPress and plugins** updated

---

## Configuration

### Environment Setup
Define environment in `wp-config.php`:
```php
// Force environment type
define('MT_ENVIRONMENT', 'development'); // or 'staging', 'production'

// WordPress debug settings
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

## Support

For issues or questions about the Debug Center:
1. Check this documentation
2. Review error logs in the Error Monitor tab
3. Run System Diagnostics for health check
4. Check browser console for JavaScript errors
5. Review wp-content/debug.log for PHP errors
6. Contact development team with diagnostic export

### Common Support Scenarios

#### "Debug Center not loading"
1. Check user permissions
2. Verify plugin activation
3. Check for PHP fatal errors
4. Clear browser cache

#### "Scripts not showing"
1. Check environment detection
2. Verify registry.json exists
3. Ensure script files present
4. Check file permissions

#### "Operations failing"
1. Review error messages
2. Check database connectivity
3. Verify user permissions
4. Check server resources

---

*Debug Center Complete Documentation*  
*Mobility Trailblazers WordPress Plugin*  
*Last Updated: August 16, 2025 - Version 2.3.2*