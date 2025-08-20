# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Project**: Mobility Trailblazers WordPress Plugin  
**Version**: 2.5.36  
**Purpose**: Award platform for recognizing the 25 most innovative mobility shapers in DACH region  
**Critical Date**: October 30, 2025 - Live award ceremony  
**Tech Stack**: WordPress 5.8+, PHP 7.4+, MariaDB, Docker, Elementor  

## Critical Commands

### Development Environment

```bash
# PowerShell scripts for Windows development
./scripts/minify-assets.ps1           # Minify CSS/JS for production
./scripts/regenerate-mo.ps1           # Compile German translations
./scripts/production-backup.ps1       # Backup production data
./scripts/production-cleanup.ps1      # Clean production environment

# WordPress CLI (via Docker)
docker exec mobility_wordpress_dev wp cache flush
docker exec mobility_wordpress_dev wp plugin list
docker exec mobility_wordpress_dev wp db query "SELECT * FROM wp_mt_evaluations"
```

### Testing & Validation

```bash
# Run PHP scripts directly
php scripts/debug-db-create.php       # Check database tables
php scripts/compile-translations.php  # Compile .po to .mo files
php scripts/dry-run-import.php        # Test CSV import without saving
```

## High-Level Architecture

### Core Plugin Structure

The plugin follows a **Repository-Service-Controller** pattern with WordPress integration:

1. **Main Entry**: `mobility-trailblazers.php` - Plugin bootstrap
2. **Core Orchestration**: `/includes/core/class-mt-plugin.php` - Manages all plugin components
3. **Database Layer**: Custom tables (`wp_mt_evaluations`, `wp_mt_assignments`) + WordPress post types
4. **AJAX System**: Base class pattern with security validation in `/includes/ajax/`
5. **Frontend Integration**: Elementor widgets + shortcodes + templates

### Key Architectural Decisions

- **Custom Post Types**: `mt_candidate` and `mt_jury_member` for WordPress integration
- **Custom Tables**: Performance-optimized for 490+ candidates and complex evaluations
- **AJAX-First**: Real-time updates for jury evaluations and assignments
- **Security Pattern**: Base AJAX class enforces nonce + capability checks
- **Localization**: German-first with 1000+ translated strings

### Data Flow

```
User Action → AJAX Request → Base Validation → Service Layer → Repository → Database
                    ↓
            JavaScript Handler ← JSON Response ← Service Result
```

## Critical Development Rules

### Naming Conventions

```php
// Functions: ALWAYS prefix with mt_
mt_get_candidates()
mt_calculate_scores()

// Classes: ALWAYS prefix with MT_
class MT_Evaluations
class MT_Import_Handler

// Database: ALWAYS use wp_mt_ prefix for custom tables
$wpdb->prefix . 'mt_evaluations'

// CSS: ALWAYS use --mt- prefix for custom properties
--mt-primary-color
--mt-spacing-unit

// JavaScript: ALWAYS use mt prefix for globals
window.mtAjaxObject
window.mtEvaluations
```

### Security Requirements

```php
// ALWAYS verify nonces
wp_verify_nonce($_POST['nonce'], 'mt_action_name');

// ALWAYS check capabilities
if (!current_user_can('mt_submit_evaluations')) {
    wp_die(__('Insufficient permissions', 'mobility-trailblazers'));
}

// ALWAYS sanitize input
$candidate_id = absint($_POST['candidate_id']);
$evaluation_text = sanitize_textarea_field($_POST['comments']);

// ALWAYS use prepared statements
$wpdb->prepare("SELECT * FROM {$wpdb->prefix}mt_evaluations WHERE jury_id = %d", $jury_id);
```

### German Localization

```php
// ALWAYS wrap user-facing strings
__('Text in German', 'mobility-trailblazers')
_e('Echo text in German', 'mobility-trailblazers')

// ALWAYS use formal "Sie" form in German
// NEVER use informal "Du" form
```

## Import/Export System

### CSV Import Architecture

- **Primary Handler**: `/includes/admin/class-mt-import-handler.php`
- **AJAX Support**: `/includes/ajax/class-mt-import-ajax.php`
- **JavaScript**: `/assets/js/csv-import.js`

### Import Methods

1. **Quick Import**: Direct CSV upload via Candidates page
2. **Advanced Import**: Full validation with dry-run via Import Profiles
3. **Excel Support**: Client-side conversion before import

### Field Mapping

The import system recognizes both English and German field names:
- `name` / `Name` / `Kandidat`
- `email` / `E-Mail` / `Email`
- `biography` / `Biografie` / `Kurzbiografie`

## Database Schema

### Custom Tables

```sql
-- Evaluations table
CREATE TABLE wp_mt_evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jury_member_id INT NOT NULL,
    candidate_id INT NOT NULL,
    criterion_1 DECIMAL(3,1),  -- Mut & Pioniergeist
    criterion_2 DECIMAL(3,1),  -- Innovationsgrad
    criterion_3 DECIMAL(3,1),  -- Umsetzungskraft
    criterion_4 DECIMAL(3,1),  -- Relevanz
    criterion_5 DECIMAL(3,1),  -- Vorbildfunktion
    comments TEXT,
    status VARCHAR(20) DEFAULT 'draft',
    INDEX idx_jury_candidate (jury_member_id, candidate_id)
);

-- Assignments table
CREATE TABLE wp_mt_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jury_member_id INT NOT NULL,
    candidate_id INT NOT NULL,
    assigned_by INT,
    assigned_at DATETIME,
    INDEX idx_assignments (jury_member_id, candidate_id)
);
```

## Frontend Components

### Elementor Widgets

Located in `/includes/integrations/elementor/widgets/`:
- `class-mt-widget-candidates-grid.php` - Candidate grid display
- `class-mt-widget-jury-dashboard.php` - Jury member interface
- `class-mt-widget-evaluation-stats.php` - Statistics display

### Templates

- **Admin**: `/templates/admin/` - Backend interfaces
- **Frontend**: `/templates/frontend/` - Public-facing templates
- **Single**: `/templates/frontend/single/` - Individual candidate pages

## Performance Optimization

### Query Optimization

```php
// GOOD: Single query with joins
$candidates = $wpdb->get_results("
    SELECT p.*, pm.meta_value as score
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
    WHERE p.post_type = 'mt_candidate'
    AND pm.meta_key = '_mt_total_score'
    ORDER BY pm.meta_value DESC
");

// BAD: Multiple queries in loop
foreach ($candidates as $candidate) {
    $score = get_post_meta($candidate->ID, '_mt_total_score', true);
}
```

### Asset Loading

- CSS files are minified to `/assets/min/css/`
- JavaScript files are minified to `/assets/min/js/`
- Use `wp_enqueue_*` with proper dependencies

## Debugging

### Debug Mode

```php
// Enable in wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('MT_DEBUG', true);

// Use in code
if (defined('MT_DEBUG') && MT_DEBUG) {
    error_log('MT Debug: ' . print_r($data, true));
}
```

### Debug Center

Access via **MT Award System → Debug Center** for:
- Database health checks
- Error log viewing
- System diagnostics
- Script testing

## Common Tasks

### Adding a New Evaluation Criterion

1. Update database schema in `/includes/core/class-mt-activator.php`
2. Add field to evaluation form in `/templates/admin/jury-evaluation-form.php`
3. Update AJAX handler in `/includes/ajax/class-mt-evaluation-ajax.php`
4. Add translation strings to `/languages/mobility-trailblazers-de_DE.po`
5. Regenerate .mo file: `./scripts/regenerate-mo.ps1`

### Creating a New Elementor Widget

1. Create widget class in `/includes/integrations/elementor/widgets/`
2. Extend `MT_Widget_Base` class
3. Register in `/includes/integrations/elementor/class-mt-elementor-loader.php`
4. Add frontend template in `/templates/frontend/`
5. Add CSS to `/assets/css/` and enqueue properly

### Implementing a New Import Format

1. Extend `MT_Import_Handler` class
2. Override `parse_file()` and `map_fields()` methods
3. Add format detection in `detect_format()`
4. Update UI in `/templates/admin/import-export.php`
5. Add JavaScript handler if needed

## Testing Checklist

Before deployment, always verify:

- [ ] German translations are complete (`./scripts/regenerate-mo.ps1`)
- [ ] Assets are minified (`./scripts/minify-assets.ps1`)
- [ ] Database migrations run correctly
- [ ] AJAX endpoints return proper JSON
- [ ] Nonce verification works
- [ ] Capability checks are in place
- [ ] Import/export handles German characters
- [ ] Mobile responsive design works
- [ ] Elementor widgets render correctly
- [ ] Cache is cleared after updates

## Production Deployment

### Pre-deployment

```bash
# Backup current state
./scripts/production-backup.ps1

# Clean unnecessary files
./scripts/production-cleanup.ps1

# Minify assets
./scripts/minify-assets.ps1

# Compile translations
./scripts/regenerate-mo.ps1
```

### Post-deployment

```bash
# Clear all caches
docker exec mobility_wordpress_dev wp cache flush

# Verify database
docker exec mobility_wordpress_dev wp db query "SHOW TABLES LIKE 'wp_mt_%'"

# Check plugin status
docker exec mobility_wordpress_dev wp plugin list --status=active
```

## Critical Notes

1. **NEVER** modify database schema without migration script
2. **ALWAYS** test imports with German special characters (ä, ö, ü, ß)
3. **ALWAYS** verify mobile responsiveness for jury interfaces
4. **NEVER** hardcode URLs - use WordPress functions
5. **ALWAYS** use WordPress transients for caching, not custom solutions
6. **CRITICAL**: October 30, 2025 live event requires zero-downtime deployment