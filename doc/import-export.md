# Import/Export & Data Management Guide

**Version:** 2.5.39+  
**Last Updated:** 2025-08-22  
**Author:** Mobility Trailblazers Development Team

> **⚠️ SECURITY UPDATE**: Critical security vulnerabilities have been fixed in v2.5.39. All import/export operations now require administrator-level permissions and use prepared SQL statements.

## Table of Contents

1. [Quick Start](#quick-start)
2. [Import System Architecture](#import-system-architecture)
3. [CSV Format Specifications](#csv-format-specifications)
4. [Import Methods](#import-methods)
5. [Export Functionality](#export-functionality)
6. [Database Structure](#database-structure)
7. [Field Mapping & Validation](#field-mapping--validation)
8. [German Localization](#german-localization)
9. [Photo Management](#photo-management)
10. [Security & Permissions](#security--permissions)
11. [Troubleshooting](#troubleshooting)
12. [Developer Reference](#developer-reference)

## Quick Start

### For Users

#### Method 1: Quick Import (Simple)
1. Navigate to **Candidates** in WordPress admin
2. Click the **Import CSV** button next to "Add New"
3. Select your CSV file from the file picker
4. Wait for import to complete
5. Review import statistics

#### Method 2: Advanced Import (With Preview)
1. Navigate to **Mobility Trailblazers → Import Profiles**
2. Upload your CSV file
3. Configure import options:
   - Update existing records
   - Skip empty fields
   - Validate URLs
   - Import photos
4. Click **Dry Run** to preview
5. Review validation report
6. Click **Import** to process

#### Method 3: Excel Import (Recommended)
1. Navigate to **Mobility Trailblazers → Import Candidates**
2. Use default file paths or browse to your Excel file
3. Enable "Also import candidate photos" if photos are available
4. Click **Import** and monitor progress
5. Review success/error report

### For Developers

```bash
# CLI Import
wp mt import-candidates --file=path/to/candidates.csv --dry-run

# With photos
wp mt import-candidates --file=path/to/candidates.csv --photos=path/to/photos/ --update-existing

# Export data
wp mt export-candidates --format=csv --output=export.csv
```

## Import System Architecture

### Core Components

```
includes/
├── admin/
│   ├── class-mt-import-handler.php      # Main import orchestrator
│   ├── class-mt-candidate-importer.php  # Candidate-specific logic
│   └── class-mt-import-export.php       # Unified import/export system
├── ajax/
│   ├── class-mt-csv-import-ajax.php     # AJAX import handler
│   └── class-mt-import-ajax.php         # Legacy AJAX support
├── services/
│   └── class-mt-candidate-import-service.php  # Import business logic
└── repositories/
    └── class-mt-candidate-repository.php      # Data access layer
```

### Import Flow

```
1. File Upload & Validation
   ├── MIME type checking
   ├── File size validation
   ├── CSV/Excel format detection
   └── BOM removal (Excel compatibility)

2. Data Processing
   ├── Header mapping (English/German)
   ├── Field validation & sanitization
   ├── URL validation
   └── Photo path resolution

3. Database Operations
   ├── Duplicate detection
   ├── WordPress post creation
   ├── Custom table insertion
   └── Photo attachment handling

4. Progress Tracking
   ├── Real-time AJAX updates
   ├── Error logging
   ├── Success statistics
   └── Rollback capabilities
```

## CSV Format Specifications

### Required Headers

The system supports both English and German headers:

| English | German | Required | Description |
|---------|--------|----------|-------------|
| `name` | `Name` | ✅ | Candidate full name |
| `organization` | `Unternehmen` | ✅ | Company/Organization |
| `position` | `Position` | ⚪ | Job title |
| `country` | `Land` | ⚪ | Country code (DE, AT, CH) |
| `linkedin_url` | `LinkedIn URL` | ⚪ | LinkedIn profile URL |
| `website_url` | `Website URL` | ⚪ | Personal/company website |
| `article_url` | `Artikel URL` | ⚪ | Related article/press URL |
| `description` | `Beschreibung` | ⚪ | Candidate description |
| `photo_filename` | `Foto Dateiname` | ⚪ | Photo file reference |

### Sample CSV Format

```csv
name,organization,position,country,linkedin_url,website_url,description,photo_filename
"Dr. Maria Schmidt","TechFlow GmbH","CEO","DE","https://linkedin.com/in/maria-schmidt","https://techflow.de","Innovative leader in mobility solutions","maria_schmidt.jpg"
"Andreas Müller","SwissMobility AG","CTO","CH","https://linkedin.com/in/andreas-mueller","https://swissmobility.ch","Pioneer in sustainable transport","andreas_mueller.jpg"
```

### CSV Requirements

- **Encoding**: UTF-8 with or without BOM
- **Delimiter**: Comma (,), semicolon (;), tab, or pipe (|) - auto-detected
- **Quote Character**: Double quotes (")
- **Line Endings**: Windows (CRLF), Unix (LF), or Mac (CR)
- **Max File Size**: 50MB (configurable)

## Import Methods

### 1. Standard WordPress Admin Import

**Location**: WordPress Admin → Candidates → Import CSV

**Features**:
- Simple drag-and-drop interface
- Basic validation and error reporting
- Immediate processing
- Success/error statistics

**Use Case**: Small datasets (< 100 records), one-time imports

### 2. Advanced Import Interface

**Location**: WordPress Admin → Mobility Trailblazers → Import Profiles

**Features**:
- Dry run capability for testing
- Advanced field mapping
- Duplicate handling options
- Photo import coordination
- Progress tracking with AJAX

**Configuration Options**:
```php
$import_options = [
    'update_existing' => true,      // Update existing records
    'skip_empty_fields' => false,   // Skip fields with empty values
    'validate_urls' => true,        // Validate URL formats
    'import_photos' => true,        // Process photo attachments
    'create_thumbnails' => true,    // Generate WordPress thumbnails
    'dry_run' => false             // Test run without saving
];
```

### 3. Excel Direct Import

**Location**: WordPress Admin → Mobility Trailblazers → Import Candidates

**Features**:
- Direct Excel file processing (.xlsx, .xls)
- Automatic sheet detection
- Column header mapping
- Batch processing for large files
- Resume interrupted imports

**Excel Requirements**:
- First row must contain headers
- Data starts from row 2
- Empty rows are skipped
- Supports merged cells (uses first cell value)

### 4. CLI Import (WP-CLI)

**Commands**:
```bash
# Basic import
wp mt import-candidates --file=/path/to/file.csv

# Advanced import with options
wp mt import-candidates \
  --file=/path/to/file.csv \
  --photos=/path/to/photos/ \
  --update-existing \
  --dry-run

# Import with specific delimiter
wp mt import-candidates \
  --file=/path/to/file.csv \
  --delimiter=";" \
  --encoding="UTF-8"

# Import with progress reporting
wp mt import-candidates \
  --file=/path/to/file.csv \
  --progress \
  --verbose
```

## Export Functionality

### Export Options

#### 1. Candidate Export

**Available Formats**:
- CSV (UTF-8 with BOM for Excel compatibility)
- JSON (structured data)
- Excel (.xlsx)

**Export Fields**:
```csv
id,name,organization,position,country,linkedin_url,website_url,article_url,
description,photo_url,category,status,created_at,updated_at
```

#### 2. Evaluation Export

**Data Included**:
- Evaluation ID and status
- Jury member name and ID
- Candidate name and ID
- All 5 criteria scores (0-10 scale)
- Total calculated score
- Comments and feedback
- Submission timestamps

#### 3. Assignment Export

**Data Included**:
- Assignment ID and date
- Jury member details
- Candidate details
- Assignment status
- Admin who created assignment

### Export CLI Commands

```bash
# Export all candidates
wp mt export-candidates --format=csv --output=candidates.csv

# Export evaluations with filters
wp mt export-evaluations --status=submitted --format=json

# Export assignments for specific jury member
wp mt export-assignments --jury-member=123 --format=excel
```

## Database Structure

### Core Tables

#### wp_mt_candidates
```sql
CREATE TABLE wp_mt_candidates (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    post_id bigint(20) DEFAULT NULL,          -- Links to wp_posts
    slug varchar(255) NOT NULL,               -- URL slug
    name varchar(255) NOT NULL,               -- Full name
    organization varchar(255) DEFAULT NULL,   -- Company/Organization
    position varchar(255) DEFAULT NULL,       -- Job title
    country varchar(100) DEFAULT NULL,        -- Country code
    linkedin_url text DEFAULT NULL,           -- LinkedIn profile
    website_url text DEFAULT NULL,            -- Website URL
    article_url text DEFAULT NULL,            -- Article/press URL
    description_sections longtext DEFAULT NULL COMMENT 'JSON with German sections',
    photo_attachment_id bigint(20) DEFAULT NULL,  -- WordPress attachment ID
    import_id varchar(100) DEFAULT NULL,      -- Import batch identifier
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    UNIQUE KEY unique_slug (slug),
    KEY idx_name (name),
    KEY idx_organization (organization),
    KEY idx_import_id (import_id),
    
    FOREIGN KEY (post_id) REFERENCES wp_posts(ID) ON DELETE SET NULL,
    FOREIGN KEY (photo_attachment_id) REFERENCES wp_posts(ID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### wp_mt_import_logs
```sql
CREATE TABLE wp_mt_import_logs (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    import_id varchar(100) NOT NULL,          -- Unique import session ID
    user_id bigint(20) NOT NULL,             -- User who performed import
    file_name varchar(255) NOT NULL,         -- Original filename
    total_records int(11) DEFAULT 0,         -- Total records in file
    processed_records int(11) DEFAULT 0,     -- Successfully processed
    failed_records int(11) DEFAULT 0,        -- Failed to process
    status varchar(20) DEFAULT 'pending',    -- pending, processing, completed, failed
    error_log longtext DEFAULT NULL,         -- JSON array of errors
    started_at datetime DEFAULT CURRENT_TIMESTAMP,
    completed_at datetime DEFAULT NULL,
    
    PRIMARY KEY (id),
    KEY idx_import_id (import_id),
    KEY idx_user_id (user_id),
    KEY idx_status (status),
    
    FOREIGN KEY (user_id) REFERENCES wp_users(ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### WordPress Integration

#### Custom Post Types
- **mt_candidate**: Public post type for candidate profiles
- **mt_jury_member**: Private post type for jury member profiles

#### Custom Meta Fields
```php
// Candidate meta fields
add_post_meta($post_id, 'mt_candidate_organization', $organization);
add_post_meta($post_id, 'mt_candidate_position', $position);
add_post_meta($post_id, 'mt_candidate_country', $country);
add_post_meta($post_id, 'mt_candidate_linkedin_url', $linkedin_url);
add_post_meta($post_id, 'mt_candidate_website_url', $website_url);
add_post_meta($post_id, 'mt_candidate_article_url', $article_url);
```

## Field Mapping & Validation

### Automatic Header Detection

The system recognizes multiple header variants:

```php
private $field_mappings = [
    'name' => ['name', 'full_name', 'candidate_name', 'Name', 'Vollständiger Name'],
    'organization' => ['organization', 'company', 'employer', 'Organisation', 'Unternehmen', 'Firma'],
    'position' => ['position', 'title', 'job_title', 'Position', 'Titel', 'Jobtitel'],
    'country' => ['country', 'nation', 'Land', 'Staat'],
    'linkedin_url' => ['linkedin', 'linkedin_url', 'linkedin_profile', 'LinkedIn', 'LinkedIn URL'],
    'website_url' => ['website', 'website_url', 'homepage', 'Website', 'Homepage'],
    'article_url' => ['article', 'article_url', 'press_url', 'Artikel', 'Artikel URL'],
    'description' => ['description', 'bio', 'biography', 'Beschreibung', 'Biografie']
];
```

### Data Validation Rules

#### Name Validation
```php
private function validate_name(string $name): array {
    $errors = [];
    
    if (empty(trim($name))) {
        $errors[] = 'Name is required';
    }
    
    if (strlen($name) > 255) {
        $errors[] = 'Name must be less than 255 characters';
    }
    
    if (!preg_match('/^[a-zA-ZÀ-ÿ\s\.-]+$/u', $name)) {
        $errors[] = 'Name contains invalid characters';
    }
    
    return $errors;
}
```

#### URL Validation
```php
private function validate_url(string $url): array {
    $errors = [];
    
    if (!empty($url)) {
        $sanitized_url = filter_var($url, FILTER_SANITIZE_URL);
        
        if (!filter_var($sanitized_url, FILTER_VALIDATE_URL)) {
            $errors[] = 'Invalid URL format';
        }
        
        // Check for allowed domains
        $allowed_domains = ['linkedin.com', 'xing.com', 'twitter.com'];
        $domain = parse_url($sanitized_url, PHP_URL_HOST);
        
        if ($domain && !in_array($domain, $allowed_domains, true)) {
            $errors[] = "Domain '{$domain}' is not allowed";
        }
    }
    
    return $errors;
}
```

#### Country Code Validation
```php
private function validate_country(string $country): array {
    $errors = [];
    $allowed_countries = ['DE', 'AT', 'CH', 'LI'];
    
    if (!empty($country) && !in_array(strtoupper($country), $allowed_countries, true)) {
        $errors[] = 'Country must be one of: ' . implode(', ', $allowed_countries);
    }
    
    return $errors;
}
```

## German Localization

### Text Processing

The system handles German text with special attention to:

#### Character Encoding
- UTF-8 with proper collation (utf8mb4_unicode_ci)
- Automatic BOM removal from Excel exports
- Proper handling of umlauts (ä, ö, ü, ß)

#### German-Specific Validation
```php
private function process_german_text(string $text): string {
    // Normalize line endings
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    
    // Clean up excess whitespace
    $text = preg_replace('/\s+/', ' ', $text);
    
    // Handle German quotation marks
    $text = str_replace(['„', '"'], '"', $text);
    
    // Normalize German apostrophes
    $text = str_replace([''', '´'], "'", $text);
    
    return trim($text);
}
```

#### German Section Processing
```php
private function parse_german_sections(string $description): array {
    $sections = [
        'beruflicher_werdegang' => '',
        'innovation_impact' => '',
        'vision_future' => '',
        'mobility_expertise' => '',
        'leadership_achievements' => '',
        'personal_motivation' => ''
    ];
    
    // Parse sections using German keywords
    $keywords = [
        'beruflicher_werdegang' => ['Beruflicher Werdegang', 'Karriere', 'Laufbahn'],
        'innovation_impact' => ['Innovation', 'Impact', 'Wirkung', 'Einfluss'],
        'vision_future' => ['Vision', 'Zukunft', 'Ausblick'],
        'mobility_expertise' => ['Mobilität', 'Expertise', 'Fachwissen'],
        'leadership_achievements' => ['Führung', 'Leadership', 'Erfolge'],
        'personal_motivation' => ['Motivation', 'Persönlich', 'Antrieb']
    ];
    
    foreach ($keywords as $section => $terms) {
        foreach ($terms as $term) {
            if (stripos($description, $term) !== false) {
                // Extract section content using patterns
                $sections[$section] = $this->extract_section_content($description, $term);
                break;
            }
        }
    }
    
    return $sections;
}
```

### Localized Error Messages

```php
private $german_messages = [
    'import_success' => 'Import erfolgreich abgeschlossen',
    'import_failed' => 'Import fehlgeschlagen',
    'validation_error' => 'Validierungsfehler in Zeile %d',
    'duplicate_found' => 'Duplikat gefunden: %s',
    'photo_not_found' => 'Foto nicht gefunden: %s',
    'invalid_email' => 'Ungültige E-Mail-Adresse',
    'required_field' => 'Pflichtfeld ist leer'
];
```

## Photo Management

### Photo Import Process

#### 1. File Detection
```php
private function detect_photo_files(string $photo_dir, string $filename): ?string {
    $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $base_name = pathinfo($filename, PATHINFO_FILENAME);
    
    foreach ($extensions as $ext) {
        $variations = [
            $filename,                          // exact match
            $base_name . '.' . $ext,           // different extension
            strtolower($base_name) . '.' . $ext, // lowercase
            str_replace(' ', '_', $base_name) . '.' . $ext, // spaces to underscores
            str_replace(' ', '-', $base_name) . '.' . $ext  // spaces to hyphens
        ];
        
        foreach ($variations as $variation) {
            $full_path = $photo_dir . '/' . $variation;
            if (file_exists($full_path)) {
                return $full_path;
            }
        }
    }
    
    return null;
}
```

#### 2. WordPress Media Library Integration
```php
private function import_photo_to_media_library(string $file_path, int $post_id): ?int {
    // Validate file
    if (!file_exists($file_path)) {
        return null;
    }
    
    $file_type = wp_check_filetype(basename($file_path), null);
    if (!$file_type['ext']) {
        return null;
    }
    
    // Upload to WordPress uploads directory
    $upload_dir = wp_upload_dir();
    $filename = basename($file_path);
    $new_file_path = $upload_dir['path'] . '/' . $filename;
    
    if (!copy($file_path, $new_file_path)) {
        return null;
    }
    
    // Create attachment post
    $attachment_data = [
        'post_mime_type' => $file_type['type'],
        'post_title' => sanitize_file_name($filename),
        'post_content' => '',
        'post_status' => 'inherit',
        'post_parent' => $post_id
    ];
    
    $attachment_id = wp_insert_attachment($attachment_data, $new_file_path, $post_id);
    
    if (!is_wp_error($attachment_id)) {
        // Generate metadata and thumbnails
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $new_file_path);
        wp_update_attachment_metadata($attachment_id, $attachment_metadata);
        
        // Set as featured image
        set_post_thumbnail($post_id, $attachment_id);
        
        return $attachment_id;
    }
    
    return null;
}
```

#### 3. Photo Processing Options
```php
$photo_options = [
    'max_width' => 800,           // Maximum width in pixels
    'max_height' => 600,          // Maximum height in pixels
    'quality' => 85,              // JPEG quality (1-100)
    'generate_thumbnails' => true, // Create WordPress thumbnails
    'create_webp' => true,        // Generate WebP versions
    'optimize' => true,           // Apply compression
    'watermark' => false          // Add watermark (if enabled)
];
```

## Security & Permissions

### Access Control

#### Required Capabilities
```php
// Import operations
if (!current_user_can('manage_options')) {
    wp_die('Insufficient permissions for import operations');
}

// Export operations  
if (!current_user_can('export')) {
    wp_die('Insufficient permissions for export operations');
}

// File upload validation
if (!current_user_can('upload_files')) {
    wp_die('Insufficient permissions for file uploads');
}
```

#### File Security Measures
```php
private function validate_upload_security(array $file): array {
    $errors = [];
    
    // MIME type validation
    $allowed_types = ['text/csv', 'application/csv', 'text/plain'];
    if (!in_array($file['type'], $allowed_types, true)) {
        $errors[] = 'Invalid file type. Only CSV files are allowed.';
    }
    
    // File size validation (50MB limit)
    $max_size = 50 * 1024 * 1024; // 50MB
    if ($file['size'] > $max_size) {
        $errors[] = 'File size exceeds 50MB limit.';
    }
    
    // File extension validation
    $allowed_extensions = ['csv', 'txt'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_extensions, true)) {
        $errors[] = 'Invalid file extension. Only .csv and .txt files are allowed.';
    }
    
    // Content scanning for malicious patterns
    $content_sample = file_get_contents($file['tmp_name'], false, null, 0, 1024);
    if (preg_match('/<\?php|<script|javascript:/i', $content_sample)) {
        $errors[] = 'File contains potentially malicious content.';
    }
    
    return $errors;
}
```

#### SQL Injection Prevention
```php
// Always use prepared statements
private function insert_candidate_record(array $data): int {
    global $wpdb;
    
    $result = $wpdb->insert(
        $wpdb->prefix . 'mt_candidates',
        [
            'name' => sanitize_text_field($data['name']),
            'organization' => sanitize_text_field($data['organization']),
            'position' => sanitize_text_field($data['position']),
            'country' => sanitize_text_field($data['country']),
            'linkedin_url' => esc_url_raw($data['linkedin_url']),
            'website_url' => esc_url_raw($data['website_url']),
            'description_sections' => wp_json_encode($data['sections'])
        ],
        [
            '%s', '%s', '%s', '%s', '%s', '%s', '%s'
        ]
    );
    
    return $wpdb->insert_id;
}
```

#### Nonce Verification
```php
// AJAX import security
public function handle_ajax_import(): void {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'mt_import_nonce')) {
        wp_die('Security check failed');
    }
    
    // Check capabilities
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }
    
    // Process import
    $this->process_import_request();
}
```

## Troubleshooting

### Common Issues

#### 1. File Upload Errors

**Issue**: "File upload failed" or "File too large"
**Solutions**:
```php
// Check PHP limits
echo 'Max file size: ' . ini_get('upload_max_filesize') . PHP_EOL;
echo 'Max post size: ' . ini_get('post_max_size') . PHP_EOL;
echo 'Memory limit: ' . ini_get('memory_limit') . PHP_EOL;

// Increase limits in wp-config.php
ini_set('upload_max_filesize', '50M');
ini_set('post_max_size', '50M');
ini_set('memory_limit', '256M');
```

#### 2. Character Encoding Issues

**Issue**: German characters appear as question marks or corrupted
**Solutions**:
```php
// Force UTF-8 encoding
$content = mb_convert_encoding($content, 'UTF-8', 'auto');

// Remove BOM if present
if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
    $content = substr($content, 3);
}

// Validate encoding
if (!mb_check_encoding($content, 'UTF-8')) {
    throw new Exception('Invalid UTF-8 encoding detected');
}
```

#### 3. CSV Parsing Errors

**Issue**: CSV data not parsing correctly
**Diagnostic Steps**:
```php
// Debug CSV parsing
private function debug_csv_parsing(string $file_path): array {
    $handle = fopen($file_path, 'r');
    $delimiter = $this->detect_delimiter($file_path);
    
    echo "Detected delimiter: " . $delimiter . PHP_EOL;
    
    $line_count = 0;
    while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
        $line_count++;
        if ($line_count <= 3) { // Show first 3 lines
            echo "Line {$line_count}: " . print_r($data, true) . PHP_EOL;
        }
    }
    
    fclose($handle);
    
    return ['lines' => $line_count, 'delimiter' => $delimiter];
}
```

#### 4. Memory Exhaustion

**Issue**: Import fails with "Fatal error: Out of memory"
**Solutions**:
```php
// Batch processing for large files
private function process_large_import(string $file_path, int $batch_size = 50): array {
    $handle = fopen($file_path, 'r');
    $header = fgetcsv($handle);
    $batch = [];
    $processed = 0;
    
    while (($data = fgetcsv($handle)) !== false) {
        $batch[] = $data;
        
        if (count($batch) >= $batch_size) {
            $this->process_batch($batch, $header);
            $batch = [];
            $processed += $batch_size;
            
            // Clear memory
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
            
            // Update progress
            $this->update_progress($processed);
        }
    }
    
    // Process remaining records
    if (!empty($batch)) {
        $this->process_batch($batch, $header);
    }
    
    fclose($handle);
    
    return ['processed' => $processed];
}
```

#### 5. Photo Import Issues

**Issue**: Photos not importing or incorrect associations
**Debug Steps**:
```php
private function debug_photo_import(string $photo_dir, array $candidates): array {
    $report = [];
    
    foreach ($candidates as $candidate) {
        $photo_file = $candidate['photo_filename'];
        $found_path = $this->detect_photo_files($photo_dir, $photo_file);
        
        $report[] = [
            'candidate' => $candidate['name'],
            'expected_file' => $photo_file,
            'found_path' => $found_path,
            'exists' => $found_path !== null,
            'readable' => $found_path && is_readable($found_path),
            'size' => $found_path ? filesize($found_path) : 0
        ];
    }
    
    return $report;
}
```

### Error Logging

#### Import Error Tracking
```php
private function log_import_error(string $import_id, int $line_number, string $error, array $data = []): void {
    global $wpdb;
    
    $wpdb->insert(
        $wpdb->prefix . 'mt_import_errors',
        [
            'import_id' => $import_id,
            'line_number' => $line_number,
            'error_message' => $error,
            'row_data' => wp_json_encode($data),
            'created_at' => current_time('mysql')
        ],
        ['%s', '%d', '%s', '%s', '%s']
    );
}
```

#### Error Report Generation
```php
public function generate_error_report(string $import_id): array {
    global $wpdb;
    
    $errors = $wpdb->get_results($wpdb->prepare(
        "SELECT line_number, error_message, row_data, created_at 
         FROM {$wpdb->prefix}mt_import_errors 
         WHERE import_id = %s 
         ORDER BY line_number ASC",
        $import_id
    ));
    
    $report = [
        'total_errors' => count($errors),
        'import_id' => $import_id,
        'generated_at' => current_time('mysql'),
        'errors' => []
    ];
    
    foreach ($errors as $error) {
        $report['errors'][] = [
            'line' => $error->line_number,
            'message' => $error->error_message,
            'data' => json_decode($error->row_data, true),
            'timestamp' => $error->created_at
        ];
    }
    
    return $report;
}
```

## Developer Reference

### Hooks and Filters

#### Actions
```php
// Before import starts
do_action('mt_before_import', $import_id, $file_path);

// After each record is processed
do_action('mt_import_record_processed', $candidate_id, $record_data, $import_id);

// After import completes
do_action('mt_after_import', $import_id, $statistics);

// Import error occurred
do_action('mt_import_error', $error_message, $line_number, $import_id);
```

#### Filters
```php
// Modify import options
$options = apply_filters('mt_import_options', $default_options, $import_id);

// Custom field mapping
$field_map = apply_filters('mt_import_field_mapping', $default_mapping);

// Validate record before processing
$is_valid = apply_filters('mt_validate_import_record', true, $record_data, $line_number);

// Modify candidate data before saving
$candidate_data = apply_filters('mt_import_candidate_data', $data, $import_id);

// Custom photo processing
$photo_path = apply_filters('mt_import_photo_path', $default_path, $filename, $candidate_data);
```

### API Examples

#### Programmatic Import
```php
// Initialize import service
$import_service = new MT_Candidate_Import_Service();

// Configure options
$options = [
    'update_existing' => true,
    'import_photos' => true,
    'photo_directory' => '/path/to/photos',
    'dry_run' => false
];

// Execute import
$result = $import_service->import_from_file('/path/to/candidates.csv', $options);

// Check results
if ($result['success']) {
    echo "Imported {$result['imported']} candidates successfully.";
    if ($result['errors']) {
        echo "Encountered {$result['error_count']} errors.";
    }
} else {
    echo "Import failed: " . $result['message'];
}
```

#### Custom Validation
```php
// Add custom validation hook
add_filter('mt_validate_import_record', function($is_valid, $data, $line_number) {
    // Custom business logic validation
    if (empty($data['organization'])) {
        // Log error
        MT_Import_Logger::log_error(
            "Organization is required for candidate: {$data['name']}",
            $line_number
        );
        return false;
    }
    
    // Check for duplicate LinkedIn profiles
    if (!empty($data['linkedin_url'])) {
        $existing = get_posts([
            'post_type' => 'mt_candidate',
            'meta_query' => [
                [
                    'key' => 'mt_candidate_linkedin_url',
                    'value' => $data['linkedin_url'],
                    'compare' => '='
                ]
            ]
        ]);
        
        if (!empty($existing)) {
            MT_Import_Logger::log_error(
                "Duplicate LinkedIn URL found: {$data['linkedin_url']}",
                $line_number
            );
            return false;
        }
    }
    
    return $is_valid;
}, 10, 3);
```

#### Progress Tracking
```php
// Custom progress callback
add_action('mt_import_record_processed', function($candidate_id, $data, $import_id) {
    static $processed = 0;
    $processed++;
    
    // Update progress every 10 records
    if ($processed % 10 === 0) {
        update_option("mt_import_progress_{$import_id}", [
            'processed' => $processed,
            'current_candidate' => $data['name'],
            'timestamp' => time()
        ]);
        
        // Send real-time update via WebSocket or AJAX
        wp_remote_post('https://your-app.com/import-progress', [
            'body' => json_encode([
                'import_id' => $import_id,
                'processed' => $processed,
                'candidate' => $data['name']
            ])
        ]);
    }
}, 10, 3);
```

---

*This guide provides comprehensive documentation for the Mobility Trailblazers import/export system. For additional technical details, see the [Architecture Guide](architecture.md) and [Developer Guide](developer-guide.md).*