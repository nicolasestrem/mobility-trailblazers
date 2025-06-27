# Error Handling System Documentation

## Overview

The Mobility Trailblazers plugin implements a comprehensive error handling and logging system designed to provide robust error management, monitoring, and debugging capabilities.

## Architecture

### 1. Centralized Logger (MT_Logger)

The `MT_Logger` class provides centralized logging functionality with multiple log levels:

- **DEBUG**: Development and debugging information
- **INFO**: General information messages
- **WARNING**: Warning conditions
- **ERROR**: Error conditions
- **CRITICAL**: Critical errors requiring immediate attention

#### Usage Examples

```php
use MobilityTrailblazers\Core\MT_Logger;

// Debug logging (only when WP_DEBUG is enabled)
MT_Logger::debug('User action performed', ['user_id' => 123, 'action' => 'evaluation_save']);

// Info logging
MT_Logger::info('Evaluation created successfully', ['evaluation_id' => 456]);

// Warning logging
MT_Logger::warning('Validation failed', ['errors' => $validation_errors]);

// Error logging
MT_Logger::error('Database operation failed', ['table' => 'mt_evaluations', 'operation' => 'INSERT']);

// Critical error logging (also stored in database)
MT_Logger::critical('System configuration error', ['missing_config' => 'database_tables']);
```

#### Specialized Logging Methods

```php
// AJAX-specific error logging
MT_Logger::ajax_error('mt_save_evaluation', 'Validation failed', ['user_id' => 123]);

// Database-specific error logging
MT_Logger::database_error('INSERT', 'mt_evaluations', $wpdb->last_error, ['data' => $insert_data]);

// Security event logging
MT_Logger::security_event('Unauthorized access attempt', ['ip' => $_SERVER['REMOTE_ADDR']]);
```

### 2. Repository Error Handling

Repository classes implement comprehensive error handling for database operations:

```php
public function find($id) {
    global $wpdb;
    
    try {
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ));
        
        if ($wpdb->last_error) {
            MT_Logger::database_error('SELECT', $this->table_name, $wpdb->last_error, ['id' => $id]);
            return false;
        }
        
        return $result;
        
    } catch (\Exception $e) {
        MT_Logger::critical('Exception in repository find method', [
            'id' => $id,
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return false;
    }
}
```

### 3. Service Layer Error Handling

Service classes provide validation and business logic error handling:

```php
public function validate($data) {
    $this->errors = []; // Clear previous errors
    $valid = true;
    
    try {
        // Validation logic
        if (empty($data['required_field'])) {
            $this->errors[] = __('Required field is missing.', 'mobility-trailblazers');
            $valid = false;
        }
        
        if (!$valid) {
            MT_Logger::warning('Validation failed', [
                'errors' => $this->errors,
                'data_keys' => array_keys($data)
            ]);
        }
        
        return $valid;
        
    } catch (\Exception $e) {
        MT_Logger::critical('Exception during validation', [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        $this->errors[] = __('Validation error occurred. Please try again.', 'mobility-trailblazers');
        return false;
    }
}
```

### 4. AJAX Error Handling

The base AJAX class provides standardized error handling:

```php
// Enhanced nonce verification with logging
protected function verify_nonce($nonce_name = 'mt_ajax_nonce') {
    try {
        $nonce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : '';
        $result = wp_verify_nonce($nonce, $nonce_name);
        
        if (!$result) {
            MT_Logger::security_event('Nonce verification failed', [
                'nonce_name' => $nonce_name,
                'action' => $_REQUEST['action'] ?? 'unknown'
            ]);
        }
        
        return $result;
        
    } catch (\Exception $e) {
        MT_Logger::critical('Exception during nonce verification', [
            'exception' => $e->getMessage(),
            'nonce_name' => $nonce_name
        ]);
        return false;
    }
}

// Exception handling helper
protected function handle_exception(\Exception $e, $context = '') {
    MT_Logger::critical('AJAX Exception: ' . $context, [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'action' => $_REQUEST['action'] ?? 'unknown',
        'user_id' => get_current_user_id(),
        'context' => $context
    ]);
    
    $this->error(__('An unexpected error occurred. Please try again.', 'mobility-trailblazers'));
}
```

### 5. Frontend Error Handling

JavaScript error handling provides user-friendly error management:

```javascript
// Global error handler
window.MTErrorHandler = {
    // Log errors to console and optionally to server
    logError: function(message, details) {
        var errorData = {
            message: message,
            details: details || {},
            timestamp: new Date().toISOString(),
            url: window.location.href,
            userAgent: navigator.userAgent
        };
        
        if (window.console && console.error) {
            console.error('MT Error:', errorData);
        }
    },
    
    // Show user-friendly error messages
    showUserError: function(message, type) {
        // Creates and displays error alerts
    },
    
    // Handle AJAX errors with context
    handleAjaxError: function(xhr, status, error, context) {
        // Extract meaningful error messages
        // Log detailed error information
        // Show user-friendly message
    }
};
```

## Error Monitoring and Reporting

### Admin Error Monitor

The error monitoring system provides:

1. **Error Statistics Dashboard**
   - Total errors
   - Errors today/this week
   - Critical error count
   - Error breakdown by level

2. **Recent Error Log**
   - Chronological error list
   - Error details and context
   - User information
   - Request context

3. **Error Management**
   - Export error logs to CSV
   - Clear error logs
   - Automatic cleanup of old logs

### Database Storage

Critical errors are stored in the `wp_mt_error_log` table:

```sql
CREATE TABLE wp_mt_error_log (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    level varchar(20) NOT NULL,
    message text NOT NULL,
    context longtext,
    user_id bigint(20) unsigned DEFAULT 0,
    request_uri varchar(500) DEFAULT '',
    user_agent varchar(500) DEFAULT '',
    created_at datetime NOT NULL,
    PRIMARY KEY (id),
    KEY level (level),
    KEY created_at (created_at),
    KEY user_id (user_id)
);
```

## Configuration

### WordPress Debug Settings

For optimal error handling, configure WordPress debug settings:

```php
// wp-config.php
define('WP_DEBUG', true);           // Enable debug mode
define('WP_DEBUG_LOG', true);       // Enable error logging
define('WP_DEBUG_DISPLAY', false);  // Don't display errors on frontend
```

### Log Retention

- Error logs are automatically cleaned up after 30 days
- Critical errors are stored in database for admin review
- Regular errors are logged to WordPress error log

## Best Practices

### 1. Error Logging

- Use appropriate log levels
- Include relevant context information
- Don't log sensitive information (passwords, tokens)
- Use structured logging with arrays for context

### 2. User-Facing Errors

- Provide clear, actionable error messages
- Avoid technical jargon
- Offer solutions when possible
- Log detailed errors separately from user messages

### 3. Exception Handling

- Catch specific exceptions when possible
- Always log exceptions with full context
- Provide fallback behavior
- Don't expose internal errors to users

### 4. AJAX Error Handling

- Validate all input parameters
- Use consistent error response format
- Log security-related failures
- Provide meaningful error messages

## Troubleshooting

### Common Issues

1. **Errors Not Logging**
   - Check WP_DEBUG_LOG is enabled
   - Verify file permissions on debug.log
   - Ensure error log directory is writable

2. **Database Errors**
   - Check database table existence
   - Verify database user permissions
   - Review SQL query syntax

3. **AJAX Errors**
   - Verify nonce generation and verification
   - Check user permissions
   - Validate request parameters

### Debug Tools

1. **Error Monitor Admin Page**
   - Access via Mobility Trailblazers → Error Monitor
   - View error statistics and recent errors
   - Export logs for analysis

2. **WordPress Debug Log**
   - Location: `/wp-content/debug.log`
   - Contains all MT-prefixed error messages
   - Includes full context and stack traces

3. **Browser Console**
   - JavaScript errors logged with MT Error prefix
   - AJAX error details and context
   - User-friendly error messages

## Integration Examples

### Custom Error Handling

```php
// In your custom code
try {
    // Your operation
    $result = perform_operation();
    
    if (!$result) {
        MT_Logger::warning('Operation failed', ['operation' => 'custom_operation']);
        return false;
    }
    
    return $result;
    
} catch (\Exception $e) {
    MT_Logger::critical('Custom operation exception', [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'operation' => 'custom_operation'
    ]);
    
    throw $e; // Re-throw if needed
}
```

### AJAX Error Response

```php
// In AJAX handler
public function handle_custom_action() {
    try {
        // Validate required parameters
        if (!$this->validate_required_params(['param1', 'param2'])) {
            return; // Error already sent
        }
        
        // Process request
        $result = $this->process_request();
        
        if ($result) {
            $this->success($result, __('Operation completed successfully.', 'mobility-trailblazers'));
        } else {
            $this->error(__('Operation failed. Please try again.', 'mobility-trailblazers'));
        }
        
    } catch (\Exception $e) {
        $this->handle_exception($e, 'custom_action');
    }
}
```

This comprehensive error handling system ensures robust error management, detailed logging, and effective monitoring for the Mobility Trailblazers plugin.
