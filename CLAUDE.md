# Mobility Trailblazers Development Workflow

When implementing features or fixes in this WordPress plugin, follow this structured workflow:

## 1. EXPLORE
Before making any changes:
- Search for existing implementations using pattern matching (MT_*, includes/, admin/, public/)
- Review relevant documentation in /doc/ directory
- Understand the Repository-Service-Controller architecture pattern
- Check for existing similar features or patterns to maintain consistency

## 2. PLAN
Create a detailed implementation plan that includes:
- Database schema changes (if needed) with mt_ prefix
- WordPress hooks and filters to use
- Security measures (nonces, capability checks, sanitization)
- Internationalization requirements (mobility-trailblazers text domain)
- Impact on existing features (assignments, evaluations, candidates)

## 3. CODE
Follow these project-specific standards:

### Naming Conventions
- **Classes**: PascalCase with MT_ prefix (e.g., MT_Assignment_Service)
- **Methods**: snake_case (e.g., process_auto_assignment())
- **Files**: kebab-case with class- prefix (e.g., class-mt-assignment-service.php)
- **Database tables**: mt_ prefix (e.g., mt_assignments)
- **CSS classes**: mt- prefix with BEM structure (e.g., mt-assignment__header)

### Security Requirements
- ALWAYS verify nonces for all AJAX and form submissions
- Check user capabilities before any operation
- Sanitize ALL input data using WordPress functions
- Escape ALL output using appropriate WordPress escaping functions
- Use prepared statements for database queries

### Code Patterns
```php
// AJAX Handler Pattern
public function handle_ajax_request() {
    // 1. Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'mt_ajax_nonce')) {
        wp_send_json_error(['message' => __('Security check failed', 'mobility-trailblazers')]);
    }
    
    // 2. Check capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Insufficient permissions', 'mobility-trailblazers')]);
    }
    
    // 3. Sanitize input
    $data = array_map('sanitize_text_field', $_POST['data']);
    
    // 4. Process with error handling
    try {
        $result = $this->service->process($data);
        wp_send_json_success($result);
    } catch (Exception $e) {
        MT_Error_Handler::log_error($e);
        wp_send_json_error(['message' => __('An error occurred', 'mobility-trailblazers')]);
    }
}
```

### Repository Pattern
```php
class MT_Assignment_Repository {
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'mt_assignments';
    }
    
    public function find_by_id($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ));
    }
}
```

## 4. TEST
Run comprehensive tests before marking complete:

### Security Scans
```bash
composer security-scan          # Full security audit
composer check-nonce            # Verify nonce implementations
composer check-escaping         # Check output escaping
composer check-sql              # SQL injection prevention
composer fix-security           # Auto-fix security issues
```

### Manual Testing Checklist
- [ ] Test with different user roles (admin, editor, subscriber)
- [ ] Verify AJAX operations work correctly
- [ ] Check responsive design on mobile/tablet
- [ ] Test with WordPress debug mode enabled
- [ ] Verify internationalization (text appears translatable)
- [ ] Check error handling (invalid inputs, network issues)
- [ ] Test bulk operations with large datasets (100+ records)

### Color Scheme Verification
Ensure UI elements follow the brand colors:
- Primary: #26a69a (teal)
- Primary Dark: #00897b
- Primary Light: #4db6ac
- Text on Primary: #ffffff

## 5. DOCUMENT
Update relevant documentation:
- Add/update inline code documentation
- Update /doc/ files if architecture changes
- Add entries to mt-changelog-updated.md
- Document any new hooks/filters for developers

## Important Commands

```bash
# Development
composer install --dev          # Install dev dependencies
composer security-scan          # Run security audit
composer check-escaping         # Verify output escaping

# Database
wp db query "SHOW TABLES LIKE '%mt_%'"  # List plugin tables
wp db optimize                          # Optimize database

# Debugging
wp config set WP_DEBUG true --raw       # Enable debug mode
tail -f wp-content/debug.log            # Monitor debug log
```

## Project-Specific Features

### Evaluation System
- Inline evaluation with scoring (0-100)
- Automated vs manual evaluation modes
- Progress tracking and completion status
- Comprehensive jury rankings with multiple criteria

### Assignment Management
- Auto-assignment based on availability
- Conflict checking and resolution
- Color-coded status indicators
- Bulk operations support

### Candidate Profiles
- Multi-section profiles with rich data
- Document management system
- Import/export capabilities
- Advanced search and filtering

## Error Handling
Use the multi-layer error handling system:
1. Try-catch blocks for all operations
2. Log errors using MT_Error_Handler::log_error()
3. Show user-friendly messages via admin notices
4. Return proper HTTP status codes for AJAX

## Performance Considerations
- Use transients for expensive queries
- Implement pagination for large datasets (50 items default)
- Optimize database queries with proper indexes
- Lazy load resources when possible

## Emergency Procedures
If something breaks:
1. Check wp-content/debug.log for errors
2. Verify database integrity (wp db check)
3. Clear transients (wp transient delete --all)
4. Check for JavaScript errors in browser console
5. Verify nonce expiration issues

## Key Documentation References
- Architecture: /doc/mt-architecture-docs.md
- Developer Guide: /doc/mt-developer-guide.md
- Error Handling: /doc/error-handling-system.md
- Color Scheme: /doc/color-scheme-implementation.md
- Bulk Operations: /doc/bulk-operations-implementation.md