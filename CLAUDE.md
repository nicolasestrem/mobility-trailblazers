# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Mobility Trailblazers WordPress Plugin** - An enterprise-grade award management platform for recognizing mobility innovators in the DACH region. This WordPress plugin (v4.1.0) manages 50+ candidates, 24 jury members, evaluations, and the complete award selection process using modern dependency injection architecture.

**Key URLs:**
- **Production:** https://mobilitytrailblazers.de/vote/
- **Staging:** http://localhost:8080/
- **Local Dev:** http://localhost/

**Important Dates:**
- **Platform Launch:** August 18, 2025
- **Award Ceremony:** October 30, 2025

## Working with Claude Code Agents & MCP Servers

This project benefits from using multiple specialized agents and MCP servers in parallel depending on the task:

### Recommended Agents for Common Tasks

Always deploy one or several agents most relevant to the task

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
- `mcp__playwright` - E2E testing automation

**Version Control:**
- `mcp__github` - Repository management and pull requests

**Thinking:**
- `mcp__sequential-thinking` - Complex problem solving

## Quick Commands Reference

### Most Used Commands
```bash
# Testing
npm test                                             # Run Playwright E2E tests
npm run test:headed                                  # Run tests with browser visible
npm run test:debug                                   # Debug mode for tests
npm run test:local                                   # Test local environment
npm run test:staging                                 # Test staging environment
npm run lint                                         # Lint test files
npm run format                                       # Format test files

# Import/Export
wp mt import-candidates --dry-run                    # Test import without changes
wp mt import-candidates --excel=path --photos=path   # Full import with photos
php scripts/import-new-candidates.php                # Import new candidates
php scripts/dry-run-import.php                       # Dry run import test

# Database
wp mt db-upgrade                                     # Run database migrations
wp db export backup.sql                              # Backup database
php scripts/run-db-upgrade.php                       # Run database upgrades
php scripts/debug-db-create.php                      # Create debug tables

# Asset Management (Windows PowerShell)
.\scripts\minify-assets.ps1                          # Minify CSS/JS for production
.\scripts\production-cleanup.ps1                     # Remove debug code for production
.\scripts\production-backup.ps1                      # Create production backup

# Translations
.\scripts\compile-mo-local.ps1                       # Compile .po to .mo (Windows)
.\scripts\regenerate-mo.ps1                          # Regenerate all .mo files
php scripts/compile-translations.php                 # PHP-based compilation

# Development
wp cache flush                                       # Clear all caches
wp mt list-candidates                                # List all candidates
tail -f /wp-content/debug.log                        # Watch error logs
```

## Architecture Overview

### Modern Dependency Injection System
```php
// Service container pattern
$container = MT_Container::get_instance();
$service = $container->get(MT_Evaluation_Service::class);

// Service providers organize registration
includes/providers/
├── class-mt-admin-provider.php
├── class-mt-ajax-provider.php  
├── class-mt-core-provider.php
├── class-mt-public-provider.php
└── class-mt-service-provider.php
```

### Plugin Structure
```
mobility-trailblazers/
├── mobility-trailblazers.php        # Main plugin file (v4.1.0)
├── includes/
│   ├── core/                        # Core functionality + DI container
│   │   ├── class-mt-plugin.php      # Main plugin class
│   │   ├── class-mt-container.php   # Dependency injection container
│   │   ├── class-mt-autoloader.php  # PSR-4 autoloader
│   │   └── class-mt-database-upgrade.php # DB migrations
│   ├── interfaces/                  # Service interfaces
│   ├── providers/                   # Service providers
│   ├── admin/                       # Admin interface
│   ├── ajax/                        # AJAX handlers (extend MT_Base_Ajax)
│   ├── repositories/                # Data access layer
│   ├── services/                    # Business logic layer
│   ├── migrations/                  # Database migrations
│   ├── debug/                       # Debug utilities
│   ├── fixes/                       # Temporary fixes
│   ├── legacy/                      # Backward compatibility
│   └── elementor/                   # Elementor widgets
├── templates/                        # View templates
├── assets/                          # CSS/JS files
├── languages/                       # Translations (de_DE)
├── dev/tests/                       # Playwright E2E tests
├── doc/                             # Documentation
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

**3. User Roles**
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

## Testing Infrastructure

### E2E Testing with Playwright
```bash
# Configuration files
playwright.config.ts            # Default config
playwright.config.local.ts      # Local environment
playwright.config.staging.ts    # Staging environment
playwright.config.production.ts # Production environment

# Test coverage areas
- Assignment management
- Authentication & login
- Candidate management  
- Database tables verification
- Debug center admin interface
- Elementor widgets
- German translations
- Import/export functionality
- Jury evaluation forms
- Navigation & routing
- Performance load testing
- Responsive design & accessibility
- Security vulnerability checks
```

## Development Workflow

### Environment Setup
```php
// Environment detection (auto-detected in main plugin file)
MT_ENVIRONMENT = 'development' | 'staging' | 'production'

// Development settings
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Before Starting Work
```bash
git pull origin main
wp mt db-upgrade                                    # Run any pending migrations
wp cache flush
npm install                                          # Install test dependencies
```

### During Development
1. **Debug Center:** Admin → MT Award System → Debug Center
2. **Watch logs:** `tail -f /wp-content/debug.log`
3. **Test imports:** Always use `--dry-run` first
4. **Run tests:** `npm test` after changes
5. **Check browser console** for JavaScript errors
6. **Use appropriate agents** for code review and security checks

## Critical Files & Their Purpose

### Core System Files
- `includes/core/class-mt-plugin.php` - Main plugin initialization
- `includes/core/class-mt-container.php` - Dependency injection container
- `includes/core/class-mt-database-upgrade.php` - Database migrations
- `includes/core/class-mt-autoloader.php` - PSR-4 class autoloading

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
- `includes/import/strategies/` - Import strategy patterns
- `includes/ajax/class-mt-csv-import-ajax.php` - AJAX import
- `scripts/import-new-candidates.php` - CLI import

### Emergency Fixes & Patches
- `includes/emergency-german-fixes.php` - Temporary German translation fixes
- `includes/fixes/class-mt-photo-fix.php` - Photo display issues
- `includes/fixes/class-mt-username-dot-fix.php` - Username handling
- `includes/german-translation-compatibility.php` - German fallback

## Database Schema

### Evaluations Table (wp_mt_evaluations)
```sql
id                BIGINT PRIMARY KEY
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
id                BIGINT PRIMARY KEY
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
- File size limits (configurable)
- Malicious content detection
- Proper file permissions

## Common Tasks

### Adding a New Feature
1. Create interface in `includes/interfaces/`
2. Create service class in `includes/services/`
3. Create repository if needed in `includes/repositories/`
4. Register in service provider (`includes/providers/`)
5. Add AJAX handler extending `MT_Base_Ajax`
6. Register hooks in `class-mt-plugin.php`
7. Add admin interface if needed
8. Create/update templates
9. Add JavaScript in `assets/js/`
10. Write Playwright tests in `dev/tests/`
11. Update translations
12. **Use wordpress-code-reviewer agent** to review implementation
13. **Use security-audit-specialist agent** for security check

### Debugging Issues
1. Check Debug Center: Admin → MT Award System → Debug Center
2. Review error logs: `wp_mt_error_log` table
3. Check browser console for JavaScript errors
4. Verify nonces and capabilities
5. Test with `WP_DEBUG` enabled
6. Run relevant Playwright tests: `npm run test:debug`
7. **Use mcp__mysql** for direct database inspection
8. **Use mcp__kapture** or **mcp__playwright** for frontend testing

### Import Troubleshooting
- **CSV not importing**: Check UTF-8 encoding, BOM handling
- **Photos not showing**: Run `php scripts/attach-existing-photos.php`
- **Duplicate candidates**: Use `--delete-existing` flag
- **Import failing**: Try dry run first with `--dry-run`
- **Large imports**: Use CLI instead of web interface

## Translation Workflow

### Files
- `languages/mobility-trailblazers.pot` - Template
- `languages/mobility-trailblazers-de_DE.po` - German translations
- `languages/mobility-trailblazers-de_DE.mo` - Compiled German

### Process
1. Edit `.po` file with Poedit or text editor
2. Compile: `.\scripts\compile-mo-local.ps1` (Windows)
3. Clear cache: `wp cache flush`
4. Test in German locale
5. **Use localization-expert agent** for translation reviews

## Performance Optimization

### Caching Strategy
```php
// Transient caching
get_transient('mt_cache_key');
set_transient('mt_cache_key', $data, HOUR_IN_SECONDS);

// Object caching
wp_cache_get($key, 'mt_group');
wp_cache_set($key, $data, 'mt_group');
```

### Asset Loading
- Development: Individual files for debugging
- Production: Minified versions (`*.min.css`, `*.min.js`)
- Conditional loading based on page context
- **Use frontend-ui-specialist agent** for CSS/JS optimization

### Database Indexes
Automatic indexes on:
- `mt_evaluations`: status, total_score, updated_at
- `mt_jury_assignments`: unique assignment, assignment_date

## Deployment Checklist

Before deploying to production:
1. ✅ Run all tests: `npm test`
2. ✅ Run `.\scripts\production-cleanup.ps1`
3. ✅ Minify assets: `.\scripts\minify-assets.ps1`
4. ✅ Compile translations: `.\scripts\compile-mo-local.ps1`
5. ✅ Test on staging (http://localhost:8080/)
6. ✅ Create backup: `.\scripts\production-backup.ps1`
7. ✅ Update version number in main plugin file
8. ✅ Clear all caches after deployment
9. ✅ Verify database migrations ran: `wp mt db-upgrade`
10. ✅ Test critical user flows
11. ✅ Monitor error logs
12. ✅ **Use security-audit-specialist agent** for final security check

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
12. **Write E2E tests** for new features using Playwright

## Environment Constants

```php
// Plugin constants (auto-generated)
MT_VERSION          // Current plugin version (4.1.0)
MT_PLUGIN_FILE      // Main plugin file path
MT_PLUGIN_DIR       // Plugin directory path
MT_PLUGIN_URL       // Plugin URL
MT_PLUGIN_BASENAME  // Plugin basename
MT_ENVIRONMENT      // development/staging/production
```

## WP-CLI Commands

```bash
# Custom commands
wp mt import-candidates [options]
  --excel=<path>        # Excel file path
  --photos=<path>       # Photos directory path
  --dry-run             # Test without changes
  --backup              # Create backup (default: true)
  --delete-existing     # Delete existing candidates

wp mt db-upgrade                                    # Run database migrations
wp mt list-candidates                               # List all candidates
wp mt export-candidates [--format=json|csv]         # Export candidates
wp mt clear-evaluations [--confirm]                 # Clear all evaluations
wp mt clear-assignments [--confirm]                 # Clear all assignments

# Useful WordPress commands
wp user list --role=mt_jury_member
wp post list --post_type=mt_candidate
wp db query "SELECT * FROM wp_mt_evaluations"
```

## MCP Server Commands

### MySQL Operations
```bash
# Use mcp__mysql for database operations
mcp__mysql__mysql_query      # Execute SQL queries
mcp__mysql__mysql_tables     # List all tables
mcp__mysql__mysql_describe   # Describe table structure
mcp__mysql__wp_options       # Get WordPress options
mcp__mysql__wp_posts         # Query posts
mcp__mysql__mt_debug_check   # Check MT tables health
```

### Docker Operations
```bash
# Use mcp__docker for container management
mcp__docker__wp_logs         # View WordPress logs
mcp__docker__db_logs         # View database logs
mcp__docker__wp_cli          # Run WP-CLI commands
mcp__docker__mobility_status # Check container status
```

### WordPress Operations
```bash
# Use mcp__wordpress for WP operations
mcp__wordpress__wp_cli              # Execute WP-CLI commands
mcp__wordpress__wp_plugin_list      # List plugins
mcp__wordpress__wp_cache_flush      # Clear cache
mcp__wordpress__wp_debug_log        # View debug log
```

## Additional Documentation

### Technical Guides
- **API Reference:** `doc/API-REFERENCE.md`
- **Category Editor:** `doc/CATEGORY-EDITOR-IMPLEMENTATION.md`
- **CSS Framework:** `doc/CSS-V4-GUIDE.md`
- **Dependency Injection:** `doc/DEPENDENCY-INJECTION-GUIDE.md`
- **Email System:** `doc/EMAIL-GUIDE.md`
- **Migration Guide:** `doc/MIGRATION-GUIDE.md`
- **Testing Strategies:** `doc/TESTING-STRATEGIES.md`

### Project Documentation
- **Developer Guide:** `doc/developer-guide.md`
- **Import/Export Guide:** `doc/IMPORT-EXPORT-GUIDE.md`
- **Changelog:** `doc/CHANGELOG.md`
- **Technical Debt:** `doc/TECHNICAL-DEBT.md`
- **Jury Handbook:** `doc/jury-handbook-2025.md`

### Support Resources
- **Debug Center:** Admin → MT Award System → Debug Center
- **Error Logs:** `wp-content/debug.log` and Debug Center
- **Test Reports:** `npm run test:report`
- **Use appropriate agents** for specialized help