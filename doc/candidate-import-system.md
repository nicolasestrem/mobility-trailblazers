# Candidate Import System Documentation

## Overview
This document describes the candidate import system implemented for the Mobility Trailblazers platform, including the database structure, import functionality, and frontend display enhancements.

## Version
- **Implementation Date**: August 2025
- **Plugin Version**: 2.5.26
- **Author**: Nicolas Estrem

## System Components

### 1. Database Structure

#### New Table: `wp_mt_candidates`
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
    description_sections longtext DEFAULT NULL COMMENT 'JSON with 6 German sections',
    photo_attachment_id bigint(20) DEFAULT NULL,
    import_id varchar(100) DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_slug (slug),
    KEY idx_name (name),
    KEY idx_organization (organization)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Key Features:**
- LONGTEXT field for `description_sections` to prevent truncation
- JSON storage for 6 German evaluation criteria sections
- utf8mb4 charset for proper German character support
- Indexes on name and organization for performance

### 2. Import System Architecture

#### File Structure
```
includes/
├── repositories/
│   └── class-mt-candidate-repository.php    # CRUD operations
├── services/
│   └── class-mt-candidate-import-service.php # Import logic
├── admin/
│   └── class-mt-candidate-importer.php      # Admin interface
└── cli/
    └── class-mt-cli-commands.php            # WP-CLI commands
```

#### Key Classes

**MT_Candidate_Repository**
- Implements repository pattern for database operations
- Methods: `find()`, `find_all()`, `create()`, `update()`, `delete()`, `find_by_slug()`, `find_by_name()`, `truncate()`
- Handles JSON encoding/decoding for description sections

**MT_Candidate_Import_Service**
- Reads Excel files using PhpSpreadsheet
- Smart header detection (finds data starting at any row)
- Parses German description sections using regex patterns
- Handles photo import from directory
- Creates backup before deletion

**MT_Candidate_Importer**
- Admin interface at: WordPress Admin → Mobility Trailblazers → Import Candidates
- Supports dry-run mode for testing
- File upload or path-based import
- Docker-compatible default paths

### 3. German Section Parsing

The system automatically parses and stores 6 German evaluation criteria sections:

1. **Überblick** (Overview)
2. **Mut & Pioniergeist** (Courage & Pioneer Spirit)
3. **Innovationsgrad** (Innovation Level)
4. **Umsetzungskraft & Wirkung** (Implementation & Impact)
5. **Relevanz für die Mobilitätswende** (Relevance for Mobility Transformation)
6. **Vorbildfunktion & Sichtbarkeit** (Role Model & Visibility)

#### Parsing Logic
```php
$patterns = [
    'ueberblick' => '/(?:^|\n)(?:Überblick|Ueberblick)\s*:?\s*\n?(.*?)(?=\n(?:Mut\s*&|Innovationsgrad|Umsetzungs|Relevanz|Vorbild|Sichtbarkeit)|$)/isu',
    'mut_pioniergeist' => '/(?:^|\n)Mut\s*&\s*Pioniergeist\s*:?\s*\n?(.*?)(?=\n(?:Innovationsgrad|Umsetzungs|Relevanz|Vorbild|Sichtbarkeit)|$)/isu',
    // ... additional patterns
];
```

### 4. Frontend Display System

#### Enhanced Template v2
**File**: `templates/frontend/single/single-mt_candidate-enhanced-v2.php`

**Features:**
- Automatic German section formatting
- Two-column layout with sidebar
- Evaluation criteria displayed as cards in grid
- Responsive design
- Category badges with gradients
- Social links (LinkedIn, Website)

#### CSS Architecture
**File**: `assets/css/candidate-enhanced-v2.css`

**Key Components:**
- Hero section with gradient background (max-height: 400px)
- 2-column grid for evaluation criteria cards
- Color-coded left borders for each criterion
- Sticky sidebar with Quick Facts and Navigation
- "View All Candidates" button prominently displayed

### 5. Import Process Workflow

1. **Data Preparation**
   - Excel file with candidate data
   - WebP photos in separate directory
   - Data starts at any row (smart detection)

2. **Import Steps**
   ```
   Admin Interface → Upload/Specify File → Dry Run → Verify → Import
   ```

3. **Data Processing**
   - Parse Excel headers
   - Map columns to database fields
   - Parse German description sections
   - Generate slugs from names
   - Create WordPress posts
   - Store in custom table
   - Import and attach photos

4. **Post-Import**
   - Data displayed on frontend
   - German sections auto-formatted
   - Photos attached as featured images

## Usage Instructions

### Admin Import
1. Navigate to: **WordPress Admin → Mobility Trailblazers → Import Candidates**
2. Specify Excel file path or upload file
3. Specify photos directory (optional)
4. Check "Dry Run" for testing
5. Click "Import Candidates"

### WP-CLI Commands
```bash
# Import candidates
wp mt import-candidates --excel=/path/to/file.xlsx --photos=/path/to/photos

# List candidates
wp mt list-candidates

# Database upgrade
wp mt db-upgrade
```

### Template Switching
The system uses `MT_Template_Loader` to automatically load the enhanced template for candidate profiles. The template can be disabled via:
```php
update_option('mt_use_enhanced_template', false);
```

## File Paths (Docker Environment)

**Default Excel Path:**
```
/var/www/html/wp-content/plugins/mobility-trailblazers/.internal/Kandidatenliste Trailblazers 2025_08_18_List for Nicolas.xlsx
```

**Default Photos Directory:**
```
/var/www/html/wp-content/plugins/mobility-trailblazers/.internal/Photos_candidates
```

## Data Integrity

### Backup System
- Automatic backup before deletion
- Backups stored in: `wp-content/uploads/mt-backups/`
- CSV format with UTF-8 BOM

### Character Encoding
- Database: utf8mb4_unicode_ci
- PHP: UTF-8 handling throughout
- Preserves German umlauts (ä, ö, ü, ß)

## Performance Considerations

1. **Database Indexes**
   - Index on `name` for quick lookups
   - Index on `organization` for filtering
   - Unique index on `slug` for URL generation

2. **Caching**
   - Compatible with WordPress object caching
   - Template caching via WordPress transients

3. **Batch Processing**
   - Import processes all candidates in single transaction
   - Bulk photo import with progress tracking

## Security Measures

1. **Input Validation**
   - Nonce verification on all forms
   - Capability checks (manage_options)
   - Sanitization of all input data

2. **SQL Security**
   - Prepared statements throughout
   - No direct SQL concatenation
   - Proper escaping of all database inputs

3. **File Security**
   - File type validation for photos (WebP only)
   - Path traversal prevention
   - Secure file upload handling

## Troubleshooting

### Common Issues

1. **Excel Import Fails**
   - Verify file format (.xlsx)
   - Check for UTF-8 encoding
   - Ensure headers contain "Name" column

2. **Photos Not Importing**
   - Verify directory path (Docker vs Windows)
   - Check file format (must be .webp)
   - Ensure filename matches candidate name

3. **German Characters Display Issues**
   - Verify database charset (utf8mb4)
   - Check PHP file encoding (UTF-8)
   - Ensure browser charset settings

4. **Layout Issues**
   - Clear browser cache
   - Check CSS file is loaded
   - Verify template is active

## Future Enhancements

1. **Planned Features**
   - Bulk edit interface
   - Category-based import
   - Photo optimization
   - Export functionality

2. **Performance Optimizations**
   - Lazy loading for photos
   - AJAX pagination
   - Database query optimization

## Support

For issues or questions:
- Check error logs: `/wp-content/debug.log`
- Review import messages in admin interface
- Contact: support@mobilitytrailblazers.de

---

*Last Updated: August 2025*
*Version: 2.5.26*