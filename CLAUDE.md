# CLAUDE.md - Mobility Trailblazers AI Context

## CRITICAL PROJECT CONTEXT

**PROJECT**: WordPress Plugin - Mobility Trailblazers Award Platform  
**DEADLINE**: August 21st, 2025 (MISSED - IMMEDIATE CRISIS)  
**CURRENT VERSION**: 2.5.34  
**PRIMARY LANGUAGE**: German (DACH region)  

## IMMEDIATE PRIORITIES

1. **DESIGN UX** - Critical for jury engagement
2. **MOBILE OPTIMIZATION** - Critical for jury evaluation workflow
3. **GERMAN LOCALIZATION** - 1000+ strings, cultural adaptation required
4. **PERFORMANCE** - Database queries must handle 200+ candidates efficiently
5. **LIVE EVENT PREP** - October 30, 2025 real-time voting system

## DEVELOPMENT CONSTRAINTS

- **NO EMAIL FUNCTIONALITY**: Never implement email features
- **GERMAN-FIRST**: All UI/UX designed for German speakers
- **MOBILE-FIRST**: 70% traffic expected from mobile
- **WORDPRESS STANDARDS**: Follow WP coding standards strictly
- **PREFIX EVERYTHING**: Use `mt_` for functions, `MT_` for classes, `--mt-` for CSS

## FILE STRUCTURE CONTEXT

```
/includes/core/
├── class-mt-plugin.php           # Main plugin orchestration
├── class-mt-database.php         # Database operations (CRITICAL)
├── class-mt-evaluations.php      # Jury scoring logic
└── class-mt-import.php           # Excel import system (ACTIVE DEV)

/includes/admin/
├── class-mt-admin.php            # Admin dashboard
├── class-mt-candidates.php       # Candidate management (CRITICAL)
└── class-mt-jury.php            # Jury workflow

/assets/
├── css/admin-styles.css          # Uses --mt- CSS variables
├── js/design-enhancements.js     # Frontend interactions
└── images/                       # WebP format preferred

/templates/
├── admin/candidate-list.php      # Candidate management UI
└── public/voting-interface.php   # Public voting (mobile-critical)
```

## DATABASE SCHEMA

```sql
-- Core candidate data
wp_posts (post_type: 'mt_candidate')
wp_postmeta (photos, German descriptions)

-- Custom tables (wp_mt_ prefix required)
wp_mt_evaluations (jury_id, candidate_id, scores 1-10, 5 criteria)
wp_mt_assignments (jury-candidate relationships)
wp_mt_votes (public voting data)
```

## CODING PATTERNS

### Function Naming
```php
// CORRECT patterns
mt_get_candidates()
mt_calculate_scores() 
mt_import_excel_data()
MT_Evaluations::process_scoring()

// INCORRECT patterns
get_candidates() // Missing prefix
mobility_get_data() // Wrong prefix
```

### Database Operations
```php
// ALWAYS use prepared statements
$wpdb->prepare("SELECT * FROM wp_mt_evaluations WHERE jury_id = %d", $jury_id);

// NEVER use direct queries
$wpdb->get_results("SELECT * FROM wp_mt_evaluations WHERE jury_id = " . $jury_id);
```

### German Localization
```php
// CORRECT
__('Kandidat erfolgreich gespeichert', 'mobility-trailblazers')
_e('Bewertung abgeschlossen', 'mobility-trailblazers')

// All strings must be in German .po file
```

## CURRENT ACTIVE DEVELOPMENT

### Excel Import System (HIGH PRIORITY)
- **File**: `/includes/admin/class-mt-candidates.php`
- **Dependency**: `phpoffice/phpspreadsheet`
- **Requirements**: Handle German content, photos, UTF-8 encoding
- **Test Data**: 48 candidates successfully imported

### Performance Issues (CRITICAL)
- **Problem**: Database queries slow with 200+ candidates
- **Solution**: Query optimization, proper indexing
- **Files to check**: `class-mt-database.php`, `class-mt-evaluations.php`

### Mobile UX (URGENT)
- **Problem**: Jury evaluation interface not touch-optimized
- **Files**: `/templates/admin/jury-evaluation.php`, `/assets/css/admin-styles.css`
- **Requirement**: Touch-friendly on tablets/phones

## TESTING REQUIREMENTS

```bash
# ALWAYS run before committing
phpunit                           # Full test suite
phpunit tests/test-import.php     # Import functionality
phpunit tests/test-evaluations.php # Scoring system
```

### Test Coverage Required
- Import system with German content
- Jury evaluation workflow
- Vote counting accuracy
- Mobile responsiveness

## SECURITY REQUIREMENTS

```php
// ALWAYS verify capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('Insufficient permissions', 'mobility-trailblazers'));
}

// ALWAYS use nonces
wp_verify_nonce($_POST['nonce'], 'mt_action_name');

// ALWAYS sanitize input
$candidate_name = sanitize_text_field($_POST['name']);
```

## COMMON DEBUG PATTERNS

```php
// Enable debug logging
if (defined('MT_DEBUG') && MT_DEBUG) {
    error_log('MT Debug: ' . $message);
}

// Database debug
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log($wpdb->last_query);
    error_log($wpdb->last_error);
}
```

## PERFORMANCE OPTIMIZATION

### Database Queries
```php
// GOOD: Use WP_Query properly
$candidates = new WP_Query([
    'post_type' => 'mt_candidate',
    'posts_per_page' => -1,
    'meta_query' => $meta_conditions
]);

// BAD: Multiple queries in loops
foreach ($candidates as $candidate) {
    $meta = get_post_meta($candidate->ID); // Avoid in loops
}
```

### Image Handling
```php
// REQUIRED: WebP format for photos
// REQUIRED: Multiple sizes for responsive
// REQUIRED: Lazy loading for mobile
```

## GERMAN LOCALIZATION CONTEXT

### UI Text Requirements
- Formal "Sie" form, not "Du"
- Professional business German
- Cultural sensitivity for DACH region
- Industry-specific mobility terminology

### Files to Update
```
/languages/mobility-trailblazers-de_DE.po  # Source translations
/languages/mobility-trailblazers-de_DE.mo  # Compiled (auto-generated)
```

## ELEMENTOR INTEGRATION

### Custom Widgets (4 active)
```
/includes/integrations/elementor/
├── candidate-grid-widget.php    # Public candidate display
├── voting-widget.php           # Public voting interface
├── results-widget.php          # Live results display
└── jury-stats-widget.php       # Admin statistics
```

## ERROR HANDLING PATTERNS

```php
// REQUIRED error handling
try {
    $result = mt_process_import($file);
    if (is_wp_error($result)) {
        wp_die($result->get_error_message());
    }
} catch (Exception $e) {
    error_log('MT Error: ' . $e->getMessage());
    wp_die(__('Import failed', 'mobility-trailblazers'));
}
```

## DEPLOYMENT CONTEXT

### Production Environment
- **Server**: Docker containers via Komodo
- **Database**: MariaDB with Redis caching
- **SSL**: HTTPS required
- **Monitoring**: Custom diagnostics system

### Pre-deployment Checklist
```bash
1. Run full test suite
2. Check German translations
3. Test mobile interfaces
4. Verify database migrations
5. Test import system with sample data
```

## LIVE EVENT REQUIREMENTS (Oct 30, 2025)

### Real-time Features Needed
- Public voting with live updates
- Results calculation and display
- Winner announcement system
- Media export capabilities

### Technical Requirements
- Zero downtime tolerance
- Sub-second response times
- Mobile-optimized interfaces
- Accurate vote counting (99.9%)

## TROUBLESHOOTING QUICK REFERENCE

### Import Issues
```bash
# Check file encoding
file -bi uploaded_file.xlsx

# Verify photo formats
ls -la /wp-content/uploads/mobility-trailblazers/

# Database check
wp db query "SELECT COUNT(*) FROM wp_posts WHERE post_type='mt_candidate'"
```

### Performance Issues
```bash
# Enable query debugging
define('SAVEQUERIES', true);

# Check slow queries
wp db query "SHOW PROCESSLIST"

# Clear caches
wp cache flush
```

## CURRENT BUGS & KNOWN ISSUES

1. **Assignment table schema mismatch** - Requires migration
2. **Mobile touch events** - jQuery UI tooltip conflicts
3. **Large dataset performance** - Query optimization needed
4. **German character encoding** - UTF-8 validation required

## AI DEVELOPMENT INSTRUCTIONS

### When modifying code:
1. **Check existing patterns** in similar files first
2. **Test mobile interfaces** - majority of users on mobile
3. **Verify German translations** - add new strings to .po file
4. **Run tests** - PHPUnit required before committing
5. **Follow WordPress standards** - use WordPress functions, not pure PHP
6. **Optimize for performance** - consider 200+ candidates impact

### When debugging:
1. **Enable debug mode** - Check WP_DEBUG and MT_DEBUG
2. **Check error logs** - /wp-content/debug.log
3. **Test with sample data** - Use /data/ folder samples
4. **Verify database state** - Check custom table integrity

### Critical success factors:
- **Deadline adherence** - August 18, 2025 deadline MISSED - emergency mode
- **Mobile experience** - Touch-optimized interfaces required IMMEDIATELY
- **German localization** - Professional, culturally appropriate
- **Performance** - Handle 200+ candidates efficiently
- **Reliability** - Zero tolerance for live event failures

---

**CONTEXT UPDATE FREQUENCY**: Update this file with each significant change  
**LAST UPDATED**: August 19, 2025  
**CRITICAL STATUS**: DEADLINE MISSED - EMERGENCY MODE  
**IMMEDIATE ACTION**: Deploy platform TODAY for jury onboarding