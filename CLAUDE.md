# CLAUDE.md - Mobility Trailblazers WordPress Plugin

**AI Development Guide for Claude Code & Claude Desktop**  
**Version:** 2.2.5  
**Last Updated:** August 2025  
**Local Path:** `C:\Users\nicol\OneDrive\CoWorkSpace\Tech Stack\Platform\plugin\mobility-trailblazers` or `E:\OneDrive\CoWorkSpace\Tech Stack\Platform\plugin\mobility-trailblazers` depending on the computer Claude is running on.

## 🎯 Project Overview

You're working on the **Mobility Trailblazers Award Platform** - a WordPress plugin for managing awards recognizing mobility innovation pioneers in the DACH region (Germany, Austria, Switzerland). The platform handles jury evaluations, candidate management, and award administration.

### Current Status
- **Phase:** Platform Development In Progress
- **Infrastructure:** ✅ Complete (Docker, Database, Security)  
- **Core Features:** ✅ Complete (Evaluation System, Dashboard, Assignments)
- **Next Focus:** Content population, candidate profiles, event integration

### Business Context
- **Partnership:** Handelsblatt Media Group
- **Event:** Award ceremony November 2025
- **Stakeholders:** 25 jury members, 50+ candidates, media partners
- **Languages:** German (primary), English (secondary)

## 🏗️ Technical Architecture

### Stack
- **WordPress:** 5.8+ with modern PHP 7.4+
- **Frontend:** Vanilla JS, AJAX, Responsive CSS Grid
- **Database:** MySQL 5.7+ with custom tables (mt_ prefix)
- **Infrastructure:** Docker containers managed via Komodo
- **Design:** Corporate colors (Teal #00736C, Copper #C27A5E, Beige #F6E8DE)

### Plugin Structure
```
mobility-trailblazers/
├── assets/               # CSS, JS, images
│   ├── css/             # Admin and frontend styles
│   ├── js/              # Modular JavaScript
│   └── images/          # Logos, icons
├── includes/            # PHP classes (PSR-4 autoloading)
│   ├── admin/          # Admin functionality
│   ├── ajax/           # AJAX handlers
│   ├── core/           # Core plugin classes
│   ├── repositories/   # Data access layer
│   ├── services/       # Business logic
│   └── shortcodes/     # Frontend shortcodes
├── templates/           # PHP/HTML templates
│   ├── admin/          # Admin interface templates
│   └── frontend/       # Public-facing templates
├── languages/          # i18n files (de_DE, en_US)
├── doc/               # Technical documentation
└── mobility-trailblazers.php  # Main plugin file
```

## 📋 DEVELOPMENT WORKFLOW

### 1. EXPLORE
Before making any changes:
- Search for existing implementations using pattern matching (`MT_*`, `includes/`, `admin/`, `public/`)
- Review relevant documentation in `/doc/` directory
- Understand the Repository-Service-Controller architecture pattern
- Check for existing similar features or patterns to maintain consistency

```bash
# Search for similar implementations
grep -r "MT_" includes/
grep -r "mt_" templates/
# Check documentation
cat doc/mt-developer-guide.md
```

### 2. PLAN
Create a detailed implementation plan that includes:
- Database schema changes (if needed) with `mt_` prefix
- WordPress hooks and filters to use
- Security measures (nonces, capability checks, sanitization)
- Internationalization requirements (`mobility-trailblazers` text domain)
- Impact on existing features (assignments, evaluations, candidates)

### 3. CODE
Follow these project-specific standards:

#### Naming Conventions
- **Classes**: PascalCase with MT_ prefix (e.g., `MT_Assignment_Service`)
- **Methods**: snake_case (e.g., `process_auto_assignment()`)
- **Files**: kebab-case with class- prefix (e.g., `class-mt-assignment-service.php`)
- **Database tables**: mt_ prefix (e.g., `mt_assignments`)
- **CSS classes**: mt- prefix with BEM structure (e.g., `mt-assignment__header`)
- **JS Objects**: PascalCase (e.g., `MTAssignmentManager`)
- **Nonces**: `mt_ajax_nonce`, `mt_form_nonce`
- **Capabilities**: `mt_manage_evaluations`
- **Hooks**: `mt_evaluation_submitted`

#### Security Requirements
- ALWAYS verify nonces for all AJAX and form submissions
- Check user capabilities before any operation
- Sanitize ALL input data using WordPress functions
- Escape ALL output using appropriate WordPress escaping functions
- Use prepared statements for database queries

#### Code Patterns

**AJAX Handler Pattern:**
```php
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
    $candidate_id = absint($_POST['candidate_id']);
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

**Repository Pattern:**
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

### 4. TEST
Run comprehensive tests before marking complete:

#### Security Scans
```bash
composer security-scan          # Full security audit
composer check-nonce            # Verify nonce implementations
composer check-escaping         # Check output escaping
composer check-sql              # SQL injection prevention
composer fix-security           # Auto-fix security issues
```

#### Manual Testing Checklist
- [ ] Test with different user roles (admin, editor, subscriber)
- [ ] Verify AJAX operations work correctly
- [ ] Check responsive design on mobile/tablet (768px, 1024px, 1440px)
- [ ] Test with WordPress debug mode enabled
- [ ] Verify internationalization (text appears translatable)
- [ ] Check error handling (invalid inputs, network issues)
- [ ] Test bulk operations with large datasets (100+ records)
- [ ] No PHP errors in debug.log
- [ ] JavaScript console clean
- [ ] German translations provided

#### Color Scheme Verification
Ensure UI elements follow the brand colors:
- Primary: #26a69a (teal) / #00736C (deep teal)
- Primary Dark: #00897b
- Primary Light: #4db6ac
- Copper: #C27A5E
- Beige: #F6E8DE
- Text on Primary: #ffffff

### 5. DOCUMENT
Update relevant documentation:
- Add/update inline code documentation
- Update `/doc/` files if architecture changes
- Add entries to `mt-changelog-updated.md`
- Document any new hooks/filters for developers

## 🚀 Common Development Tasks

### Adding a New Admin Page
```php
// In includes/admin/class-mt-admin.php
add_submenu_page(
    'mobility-trailblazers',
    __('Page Title', 'mobility-trailblazers'),
    __('Menu Title', 'mobility-trailblazers'),
    'manage_options',
    'mt-new-page',
    [$this, 'render_new_page']
);
```

### Creating an AJAX Endpoint
```php
// 1. Register in includes/ajax/class-mt-admin-ajax.php
add_action('wp_ajax_mt_new_action', [$this, 'handle_new_action']);

// 2. Add handler method
public function handle_new_action() {
    $this->verify_nonce('mt_ajax_nonce');
    $this->check_permission('manage_options');
    // Process...
    wp_send_json_success($result);
}

// 3. Add JavaScript in assets/js/admin.js
jQuery.post(mt_admin.ajax_url, {
    action: 'mt_new_action',
    nonce: mt_admin.nonce,
    data: formData
});
```

### Adding a Database Table
```php
// In includes/core/class-mt-activator.php
$sql = "CREATE TABLE {$wpdb->prefix}mt_new_table (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) $charset_collate;";
dbDelta($sql);
```

### Creating a Shortcode
```php
// In includes/shortcodes/
class MT_New_Shortcode {
    public function render($atts) {
        $atts = shortcode_atts([
            'param' => 'default'
        ], $atts);
        
        ob_start();
        include MT_PLUGIN_DIR . 'templates/frontend/new-template.php';
        return ob_get_clean();
    }
}
```

## 🎯 Project-Specific Features

### Evaluation System
- Inline evaluation with scoring (0-100)
- Automated vs manual evaluation modes
- Progress tracking and completion status
- Comprehensive jury rankings with multiple criteria
- 5-Criteria System:
  - Courage & Pioneer Spirit
  - Innovation Degree
  - Implementation & Impact
  - Mobility Transformation Relevance
  - Role Model & Visibility

### Assignment Management
- Auto-assignment based on availability
- Balanced vs Random distribution methods
- Conflict checking and resolution
- Color-coded status indicators
- Bulk operations support
- Real-time filtering and search

### Candidate Profiles
- Multi-section profiles with rich data
- Document management system
- Import/export capabilities (CSV with UTF-8 support)
- Advanced search and filtering
- Photo management
- Category assignments

## 🔥 Current Focus Areas

### Immediate Priorities (July-August 2025)
1. **Content Management**
   - Populate candidate profiles with photos/bios
   - Complete jury member profiles
   - Set up nomination process workflow

2. **Communication Setup**
   - Newsletter integration (Mailchimp/similar)
   - LinkedIn company page content

3. **Platform Enhancements**
   - Public nomination form
   - Multi-language support (improve de_DE translations)
   - Search/filter capabilities
   - Performance optimization (CDN, caching)

## ⚠️ Error Handling & Performance

### Error Handling System
Use the multi-layer error handling system:
1. Try-catch blocks for all operations
2. Log errors using `MT_Error_Handler::log_error()`
3. Show user-friendly messages via admin notices
4. Return proper HTTP status codes for AJAX

### Performance Considerations
- Use transients for expensive queries
- Implement pagination for large datasets (50 items default)
- Optimize database queries with proper indexes
- Lazy load resources when possible
- Clear caches after major operations

## 🛠️ Important Commands

```bash
# Development
composer install --dev          # Install dev dependencies
composer security-scan          # Run security audit
composer check-escaping         # Verify output escaping

# Database
wp db query "SHOW TABLES LIKE '%mt_%'"  # List plugin tables
wp db optimize                          # Optimize database
wp db check                            # Verify database integrity

# Debugging
wp config set WP_DEBUG true --raw       # Enable debug mode
tail -f wp-content/debug.log            # Monitor debug log

# Cache & Transients
wp transient delete --all               # Clear all transients
wp cache flush                          # Clear object cache
```

## 🆘 Emergency Procedures

If something breaks:
1. Check `wp-content/debug.log` for errors
2. Verify database integrity (`wp db check`)
3. Clear transients (`wp transient delete --all`)
4. Check for JavaScript errors in browser console
5. Verify nonce expiration issues
6. Check user permissions and capabilities
7. Roll back to previous version if needed

## 🐛 Known Issues & Workarounds

### Current Bugs
1. **Assignment Distribution:** Recently fixed in v2.2.1 - verify balanced distribution works
2. **CSV Import:** Handle UTF-8 BOM in CSV files
3. **Modal Z-index:** Some modals appear behind WordPress admin bar

### Common Troubleshooting
- **Database Issues:** Use Diagnostics page to verify installation
- **Assignment Problems:** Check jury member linking and browser console
- **Evaluation Saving:** Verify AJAX endpoints and user capabilities
- **Performance:** Clear caches and check server response times

## 📚 Key Documentation References

### Internal Docs (./doc/)
- `mt-developer-guide.md` - Complete development reference
- `mt-architecture-docs.md` - System architecture & security
- `error-handling-system.md` - Error management patterns
- `color-scheme-implementation.md` - Brand colors and UI
- `bulk-operations-implementation.md` - Bulk actions guide
- `changelog.md` - Version history

### WordPress Hooks Used
- `init` - Plugin initialization
- `admin_menu` - Admin menu setup
- `wp_enqueue_scripts` - Asset loading
- `wp_ajax_*` - AJAX handlers
- `plugins_loaded` - Early initialization

### Custom Hooks Provided
- `mt_evaluation_submitted` - After evaluation saved
- `mt_evaluation_criteria` - Filter evaluation criteria
- `mt_assignment_created` - After assignment created
- `mt_before_candidate_save` - Pre-save filtering

## 📞 Quick Reference

### Database Tables
- `wp_mt_evaluations` - Jury scores
- `wp_mt_assignments` - Jury-candidate mappings
- `wp_mt_evaluation_weights` - Criteria weights
- `wp_mt_criteria` - Evaluation criteria definitions

### User Roles & Capabilities
- `administrator` - Full access
- `mt_jury_member` - Can evaluate assigned candidates
- `editor` - Can manage candidates
- `manage_options` - Admin capability
- `mt_manage_evaluations` - Custom evaluation capability

### Key Shortcodes
- `[mt_jury_dashboard]` - Jury evaluation interface
- `[mt_candidates_grid]` - Public candidate display
- `[mt_evaluation_stats]` - Admin statistics
- `[mt_winners_display]` - Top candidates

### AJAX Actions
- `mt_save_evaluation` - Save jury evaluation
- `mt_auto_assign` - Auto-assign candidates
- `mt_import_csv` - Import candidate data
- `mt_export_evaluations` - Export results
- `mt_bulk_operations` - Bulk actions handler

## 🤝 Working with Claude

### Best Practices
1. **Always review context** - Check README.md and /doc/ first
2. **Maintain features** - Never remove functionality without asking
3. **Use existing patterns** - Follow established code structure
4. **Test incrementally** - Verify each change works
5. **Document changes** - Update changelog and inline comments

### File Editing Strategy
When modifying code:
```php
// Instead of rewriting entire files, indicate specific changes:
// Line 142-156: Replace the existing method with:
public function new_improved_method() {
    // New implementation
}

// Or for small changes:
// Line 89: Change 'old_value' to 'new_value'
```

### Communication Style
- Use simple, friendly language
- Avoid complex technical jargon
- Write as a collaborative team member
- Focus on practical solutions

## 🔐 Security Checklist

For every code change, verify:
- [ ] User input sanitized
- [ ] Database queries use prepared statements
- [ ] Output properly escaped
- [ ] Nonces verified for forms/AJAX
- [ ] Capabilities checked
- [ ] File uploads validated
- [ ] No sensitive data in logs
- [ ] Error messages don't expose system info
- [ ] CSRF protection implemented
- [ ] SQL injection prevention

## 💡 Pro Tips

1. **Performance:** Use transients for expensive queries (12-hour default)
2. **Debugging:** Check `wp-content/debug.log` first
3. **Validation:** CSV imports need UTF-8 encoding without BOM
4. **Caching:** Clear browser cache after CSS/JS changes
5. **Testing:** Use staging environment for major changes
6. **Backup:** Export database before bulk operations
7. **Translations:** Always use text domain for strings
8. **Assets:** Version CSS/JS files for cache busting

---

**Remember:** This is a live platform for a major award event. Always test changes thoroughly and maintain backward compatibility. When in doubt, ask before making breaking changes!