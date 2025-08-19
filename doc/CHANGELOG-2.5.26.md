# Changelog - Version 2.5.26

## Release Date: August 19, 2025

## Summary
Major enhancement to the candidate management system with new import functionality, database structure improvements, and frontend display enhancements for German evaluation criteria.

## New Features

### 1. Candidate Import System
- **Admin Interface**: New import page at WordPress Admin → Mobility Trailblazers → Import Candidates
- **Excel Import**: Support for .xlsx files with smart header detection
- **Photo Import**: Automatic WebP photo import and attachment
- **Dry Run Mode**: Test imports before executing
- **Backup System**: Automatic CSV backup before data deletion

### 2. Database Enhancements
- **New Table**: `wp_mt_candidates` with LONGTEXT fields to prevent data truncation
- **JSON Storage**: Structured storage for 6 German evaluation sections
- **UTF8MB4 Support**: Full support for German special characters
- **Performance Indexes**: Optimized queries with proper indexing

### 3. Frontend Template Improvements
- **Enhanced Template v2**: New candidate profile template with automatic German section formatting
- **Evaluation Criteria Cards**: Grid layout with color-coded sections
- **Sidebar Navigation**: Quick Facts widget and "View All Candidates" button
- **Responsive Design**: Mobile-optimized layout

### 4. German Section Parsing
Automatic parsing and display of 6 evaluation criteria:
- Überblick (Overview)
- Mut & Pioniergeist (Courage & Pioneer Spirit)
- Innovationsgrad (Innovation Level)
- Umsetzungskraft & Wirkung (Implementation & Impact)
- Relevanz für die Mobilitätswende (Relevance for Mobility Transformation)
- Vorbildfunktion & Sichtbarkeit (Role Model & Visibility)

## Files Added

### Core Files
- `includes/repositories/class-mt-candidate-repository.php`
- `includes/services/class-mt-candidate-import-service.php`
- `includes/admin/class-mt-candidate-importer.php`
- `templates/frontend/single/single-mt_candidate-enhanced-v2.php`
- `assets/css/candidate-enhanced-v2.css`

### Documentation
- `doc/candidate-import-system.md`
- `doc/CHANGELOG-2.5.26.md`

## Files Modified

### Core Updates
- `includes/core/class-mt-database-upgrade.php` - Added upgrade_to_2_5_26() method
- `includes/core/class-mt-template-loader.php` - Updated to load v2 template
- `includes/core/class-mt-plugin.php` - Initialized new importer class
- `composer.json` - Added PhpSpreadsheet dependency

## Database Changes

### New Table Structure
```sql
CREATE TABLE wp_mt_candidates (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    post_id bigint(20) DEFAULT NULL,
    slug varchar(255) NOT NULL,
    name varchar(255) NOT NULL,
    organization varchar(255) DEFAULT NULL,
    position varchar(255) DEFAULT NULL,
    country varchar(100) DEFAULT NULL,
    linkedin_url text DEFAULT NULL,
    website_url text DEFAULT NULL,
    article_url text DEFAULT NULL,
    description_sections longtext DEFAULT NULL,
    photo_attachment_id bigint(20) DEFAULT NULL,
    import_id varchar(100) DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_slug (slug),
    KEY idx_name (name),
    KEY idx_organization (organization)
)
```

## Technical Improvements

### Performance
- Optimized database queries with proper indexing
- Efficient batch processing for imports
- Reduced memory usage with streaming Excel reader

### Security
- Nonce verification on all forms
- Capability checks for admin operations
- Prepared statements for all database queries
- Input sanitization and validation

### Compatibility
- Docker environment support
- Windows/Linux path compatibility
- PHP 7.4+ compatibility
- WordPress 5.8+ compatibility

## Bug Fixes
- Fixed truncation issues with long German text descriptions
- Resolved character encoding problems with umlauts
- Fixed layout issues with overlapping sidebar
- Corrected category badge display problems

## Known Issues
- WP-CLI commands may not work in production environment
- Rankings page (/rankings/) returns 404 (not yet implemented)

## Migration Notes

### For Existing Installations
1. Backup database before upgrade
2. Run database upgrade via admin or WP-CLI
3. Clear all caches after upgrade
4. Re-import candidates if experiencing data issues

### Import Data Requirements
- Excel file must contain "Name" column
- Photos must be in WebP format
- German sections should be properly formatted in Excel

## Testing

### Test Coverage
- ✅ Import of 48 candidates successful
- ✅ German section parsing validated
- ✅ Photo attachment working
- ✅ Frontend display verified
- ✅ Mobile responsiveness tested

### Browser Compatibility
- Chrome/Edge: Fully tested
- Firefox: Fully tested
- Safari: Basic testing
- Mobile browsers: Responsive design verified

## Dependencies Added
- `phpoffice/phpspreadsheet`: ^1.29 (Excel file reading)

## Credits
- **Development**: Nicolas Estrem
- **Testing**: Mobility Trailblazers Team
- **Project Lead**: Tobias Tomczak

## Support
For issues related to this update:
- Check documentation in `/doc/candidate-import-system.md`
- Review error logs in `/wp-content/debug.log`
- Contact: support@mobilitytrailblazers.de

---

*This version represents a major enhancement to the candidate management system, providing robust import capabilities and improved display of German evaluation criteria.*