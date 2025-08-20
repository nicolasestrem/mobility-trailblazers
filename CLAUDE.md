# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Mobility Trailblazers WordPress Plugin** - An enterprise-grade award management platform for recognizing mobility innovators in the DACH region. This WordPress plugin (v2.5.37) manages 490+ candidates, 24 jury members, evaluations, and the complete award selection process.

**Key URLs:**
- **Production:** https://mobilitytrailblazers.de/vote/
- **Staging:** http://localhost:8080/
- **Local Dev:** http://localhost/

**Important Dates:**
- **Platform Launch:** August 18, 2025
- **Award Ceremony:** October 30, 2025

## Working with Claude Code Agents & MCP Servers

This project benefits from using multiple specialized agents and MCP servers depending on the task:

### Recommended Agents for Common Tasks

**WordPress Development:**
- `wordpress-code-reviewer` - Review plugin code for WordPress best practices, security, and performance
- `security-audit-specialist` - Audit for SQL injection, XSS, and other vulnerabilities
- `localization-expert` - Handle German translations and i18n implementation

**Frontend Development:**
- `frontend-ui-specialist` - Review and optimize CSS/JS, React components, responsive design
- `fullstack-dev-expert` - Handle features spanning frontend and backend

**Code Quality:**
- `syntax-error-detector` - Check for syntax errors after writing code
- `code-refactoring-specialist` - Improve code structure and reduce duplication
- `documentation-writer` - Generate documentation for new features

**Project Management:**
- `project-manager-coordinator` - Plan development phases and timelines
- `product-owner-manager` - Gather requirements and prioritize features

### Available MCP Servers

**Database Operations:**
- `mcp__mysql` - Direct database queries and table management
- `mcp__docker` - Container management for local development
- `mcp__wordpress` - WP-CLI commands and WordPress-specific operations

**File Management:**
- `mcp__filesystem` - File operations and directory management

**Browser Testing:**
- `mcp__kapture` - Browser automation for testing frontend features

**Version Control:**
- `mcp__github` - Repository management and pull requests

### Example Usage Patterns

```bash
# For WordPress code review
# Use: wordpress-code-reviewer agent

# For database debugging
# Use: mcp__mysql server with mysql_query, mysql_describe

# For frontend testing
# Use: mcp__kapture for browser automation

# For security audits
# Use: security-audit-specialist agent

# For German translations
# Use: localization-expert agent
```

## Quick Commands Reference

### Most Used Commands
```bash
# Import/Export
wp mt import-candidates --dry-run                    # Test import without changes
php scripts/import-new-candidates.php               # Import new candidates
php scripts/dry-run-import.php                      # Dry run import test

# Database
wp db export backup.sql                             # Backup database
php scripts/run-db-upgrade.php                      # Run database upgrades
php scripts/debug-db-create.php                     # Create debug tables

# Asset Management
.\scripts\minify-assets.ps1                         # Minify CSS/JS for production
.\scripts\production-cleanup.ps1                    # Remove debug code for production
.\scripts\production-backup.ps1                     # Create production backup

# Translations
.\scripts\compile-mo-local.ps1                      # Compile .po to .mo (Windows)
.\scripts\regenerate-mo.ps1                         # Regenerate all .mo files
php scripts/compile-translations.php                # PHP-based compilation

# Development
wp cache flush                                      # Clear all caches
tail -f /wp-content/debug.log                      # Watch error logs
```

## Architecture Overview

### Plugin Structure
```
mobility-trailblazers/
├── mobility-trailblazers.php        # Main plugin file (entry point)
├── includes/
│   ├── core/                        # Core functionality
│   │   ├── class-mt-plugin.php      # Main plugin class
│   │   ├── class-mt-autoloader.php  # PSR-4 autoloader
│   │   ├── class-mt-database-upgrade.php # DB migrations
│   │   └── class-mt-roles.php       # User roles & capabilities
│   ├── admin/                       # Admin interface
│   │   ├── class-mt-admin.php       # Admin bootstrap
│   │   ├── class-mt-import-handler.php # CSV/Excel imports
│   │   └── class-mt-candidate-editor.php # Candidate management
│   ├── ajax/                        # AJAX handlers
│   │   ├── class-mt-base-ajax.php   # Base AJAX class (security)
│   │   ├── class-mt-evaluation-ajax.php # Evaluation endpoints
│   │   └── class-mt-assignment-ajax.php # Assignment endpoints
│   ├── repositories/                # Data access layer
│   ├── services/                    # Business logic
│   └── elementor/                   # Elementor widgets
├── templates/                        # View templates
├── assets/                          # CSS/JS files
├── languages/                       # Translations (de_DE)
└── scripts/                         # Utility scripts
```

### Key Components

**1. Custom Post Types**
- `mt_candidate` - Candidate profiles (public)
- `mt_jury_member` - Jury members (admin only)

**2. Custom Database Tables**
- `wp_mt_evaluations` - Jury evaluations (5 criteria, 0-10 scale)
- `wp_mt_jury_assignments` - Jury-candidate mappings
- `wp_mt_audit_log` - Activity tracking
- `wp_mt_error_log` - Error logging

**3. User Roles** (as shown in screenshot)
- `Administrator` - Full access
- `Editor` - Content management
- `Author` - Limited content creation
- `Contributor` - Draft creation only
- `Subscriber` - Basic access
- `Jury Member (mt_jury_member)` - Can submit evaluations
- `Jury Admin (mt_jury_admin)` - Manage assignments & evaluations

**4. AJAX Security Pattern**
All AJAX handlers extend `MT_Base_Ajax` which enforces:
- Nonce verification (`mt_ajax_nonce`)
- Capability checks
- Input sanitization
- File upload validation

## Development Workflow

### Environment Setup
```php
// Environment detection (in main plugin file)
MT_ENVIRONMENT = 'development' | 'staging' | 'production'

// Development settings
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Before Starting Work
```bash
git pull origin main
wp eval "MobilityTrailblazers\Utilities\MT_Database_Health::check_health();"
wp cache flush
```

### During Development
1. **Debug Center:** Admin → MT Award System → Debug Center
2. **Watch logs:** `tail -f /wp-content/debug.log`
3. **Test imports:** Always use `--dry-run` first
4. **Check browser console** for JavaScript errors
5. **Use appropriate agents** for code review and security checks

### Before Committing
```powershell
.\scripts\production-cleanup.ps1    # Remove debug code
.\scripts\minify-assets.ps1         # Minify assets
.\scripts\compile-mo-local.ps1      # Compile translations
```

## Critical Files & Their Purpose

### Core System Files
- `includes/core/class-mt-plugin.php` - Main plugin initialization
- `includes/core/class-mt-database-upgrade.php` - Database migrations
- `includes/core/class-mt-autoloader.php` - Class autoloading

### Evaluation System
- `includes/services/class-mt-evaluation-service.php` - Business logic
- `includes/repositories/class-mt-evaluation-repository.php` - Data access
- `includes/ajax/class-mt-evaluation-ajax.php` - AJAX endpoints
- `templates/frontend/jury-evaluation-form.php` - Frontend form

### Assignment Management
- `includes/services/class-mt-assignment-service.php` - Assignment logic
- `includes/ajax/class-mt-assignment-ajax.php` - AJAX handlers
- `assets/js/mt-assignments.js` - Frontend JavaScript
- `templates/admin/assignments.php` - Admin interface

### Import/Export System
- `includes/admin/class-mt-import-handler.php` - CSV/Excel processing
- `includes/ajax/class-mt-csv-import-ajax.php` - AJAX import
- `scripts/import-new-candidates.php` - CLI import

### Emergency Fixes
- `includes/emergency-german-fixes.php` - Temporary German translation fixes (remove after proper fix)
- `includes/fixes/class-mt-photo-fix.php` - Photo display issues

## Database Schema

### Evaluations Table (wp_mt_evaluations)
```sql
jury_member_id    BIGINT    -- References jury member post
candidate_id      BIGINT    -- References candidate post
criterion_1-5     DECIMAL   -- Scores (0-10, 0.5 increments)
total_score       DECIMAL   -- Calculated total
comments          LONGTEXT  -- Optional feedback
status            VARCHAR   -- draft/submitted/approved/rejected
created_at        DATETIME  -- Creation timestamp
updated_at        DATETIME  -- Last update
submitted_at      DATETIME  -- Submission timestamp
```

### Assignments Table (wp_mt_jury_assignments)
```sql
jury_member_id    BIGINT    -- References jury member
candidate_id      BIGINT    -- References candidate
assigned_at       DATETIME  -- Assignment timestamp
assigned_by       BIGINT    -- Admin who assigned
UNIQUE KEY: (jury_member_id, candidate_id)
```

## Security Guidelines

### Always Required
1. **Nonce verification** in all AJAX handlers: `check_ajax_referer('mt_ajax_nonce')`
2. **Capability checks** before operations: `current_user_can('mt_submit_evaluations')`
3. **Input sanitization**: Use `sanitize_text_field()`, `esc_url_raw()`, etc.
4. **Output escaping**: Use `esc_html()`, `esc_attr()`, `esc_url()`
5. **Prepared statements** for database queries
6. **Use security-audit-specialist agent** for security reviews

### File Upload Security
- MIME type validation
- File size limits
- Malicious content detection
- Proper file permissions

## Common Tasks

### Adding a New Feature
1. Create service class in `includes/services/`
2. Create repository if needed in `includes/repositories/`
3. Add AJAX handler extending `MT_Base_Ajax`
4. Register hooks in `class-mt-plugin.php`
5. Add admin interface if needed
6. Create/update templates
7. Add JavaScript in `assets/js/`
8. Update translations
9. **Use wordpress-code-reviewer agent** to review implementation
10. **Use security-audit-specialist agent** for security check

### Debugging Issues
1. Check Debug Center: Admin → MT Award System → Debug Center
2. Review error logs: `wp_mt_error_log` table
3. Check browser console for JavaScript errors
4. Verify nonces and capabilities
5. Test with `WP_DEBUG` enabled
6. **Use mcp__mysql** for direct database inspection
7. **Use mcp__kapture** for frontend testing

### Import Troubleshooting
- **CSV not importing**: Check UTF-8 encoding, BOM handling
- **Photos not showing**: Run `php scripts/attach-existing-photos.php`
- **Duplicate candidates**: Use `--delete-existing` flag
- **Import failing**: Try dry run first with `--dry-run`

## Translation Workflow

### Files
- `languages/mobility-trailblazers.pot` - Template
- `languages/mobility-trailblazers-de_DE.po` - German translations
- `languages/mobility-trailblazers-de_DE.mo` - Compiled German

### Process
1. Edit `.po` file with Poedit or text editor
2. Compile: `.\scripts\compile-mo-local.ps1`
3. Clear cache: `wp cache flush`
4. Test in German locale
5. **Use localization-expert agent** for translation reviews

## Performance Optimization

### Caching
```php
// Transient caching
get_transient('mt_cache_key');
set_transient('mt_cache_key', $data, HOUR_IN_SECONDS);

// Object caching
wp_cache_get($key, 'mt_group');
wp_cache_set($key, $data, 'mt_group');
```

### Asset Loading
- Development: Individual files
- Production: Minified versions (`*.min.css`, `*.min.js`)
- Conditional loading based on page context
- **Use frontend-ui-specialist agent** for CSS/JS optimization

### Database Indexes
Automatic indexes on:
- `mt_evaluations`: status, total_score, updated_at
- `mt_jury_assignments`: unique assignment, assignment_date

## Deployment Checklist

Before deploying to production:
1. ✅ Run `.\scripts\production-cleanup.ps1`
2. ✅ Minify assets: `.\scripts\minify-assets.ps1`
3. ✅ Compile translations: `.\scripts\compile-mo-local.ps1`
4. ✅ Test on staging (http://localhost:8080/)
5. ✅ Create backup: `.\scripts\production-backup.ps1`
6. ✅ Update version number in main plugin file
7. ✅ Clear all caches after deployment
8. ✅ Verify database migrations ran
9. ✅ Test critical user flows
10. ✅ Monitor error logs
11. ✅ **Use security-audit-specialist agent** for final security check

## Important Notes

1. **Never remove features without confirmation** - Plugin is in production
2. **Always check existing code** before implementing new features
3. **Emergency fixes** are temporarily in `includes/emergency-german-fixes.php`
4. **Use Repository-Service pattern** for new features
5. **AJAX handlers must extend** `MT_Base_Ajax` class
6. **All database operations** through repositories
7. **Follow WordPress coding standards**
8. **Test imports with dry run** before actual import
9. **Check browser console** for JavaScript errors
10. **Verify nonces** in all AJAX operations
11. **Use appropriate agents** for code review and testing

## Environment Constants

```php
// Plugin constants
MT_VERSION          // Current plugin version
MT_PLUGIN_DIR       // Plugin directory path
MT_PLUGIN_URL       // Plugin URL
MT_PLUGIN_BASENAME  // Plugin basename
MT_ENVIRONMENT      // development/staging/production
```

## WP-CLI Commands

```bash
# Custom commands
wp mt import-candidates [--dry-run] [--excel=path] [--photos=path]
wp mt export-candidates [--format=json|csv]
wp mt clear-evaluations [--confirm]
wp mt clear-assignments [--confirm]

# Useful WordPress commands
wp user list --role=mt_jury_member
wp post list --post_type=mt_candidate
wp db query "SELECT * FROM wp_mt_evaluations"
```

## MCP Server Commands

### MySQL Operations
```bash
# Use mcp__mysql for database operations
mcp__mysql__mysql_query - Execute SQL queries
mcp__mysql__mysql_tables - List all tables
mcp__mysql__mysql_describe - Describe table structure
mcp__mysql__wp_options - Get WordPress options
mcp__mysql__wp_posts - Query posts
mcp__mysql__mt_debug_check - Check MT tables
```

### Docker Operations
```bash
# Use mcp__docker for container management
mcp__docker__wp_logs - View WordPress logs
mcp__docker__db_logs - View database logs
mcp__docker__wp_cli - Run WP-CLI commands
mcp__docker__mobility_status - Check container status
```

### WordPress Operations
```bash
# Use mcp__wordpress for WP operations
mcp__wordpress__wp_cli - Execute WP-CLI commands
mcp__wordpress__wp_plugin_list - List plugins
mcp__wordpress__wp_cache_flush - Clear cache
mcp__wordpress__wp_debug_log - View debug log
```

## Support Resources

- **Developer Guide:** `doc/developer-guide.md`
- **Import Guide:** `doc/IMPORT-EXPORT-GUIDE.md`
- **Changelog:** `doc/CHANGELOG.md`
- **Debug Center:** Admin → MT Award System → Debug Center
- **Error Logs:** `wp-content/debug.log` and Debug Center
- **Use appropriate agents** for specialized help