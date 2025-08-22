# Mobility Trailblazers - Import/Export & Localization Guide
*Last Updated: August 22, 2025 | Version 2.5.39*

> **⚠️ SECURITY UPDATE**: Critical security vulnerabilities have been fixed in v2.5.39. All import/export operations now require administrator-level permissions and use prepared SQL statements.

## Table of Contents
1. [Quick Start](#quick-start)
2. [CSV Format Specifications](#csv-format-specifications)
3. [Excel to CSV Conversion](#excel-to-csv-conversion)
4. [Import Methods](#import-methods)
5. [Migration Procedures](#migration-procedures)
6. [AJAX Import with Progress](#ajax-import-with-progress)
7. [Field Mapping & Validation](#field-mapping--validation)
8. [German Text Parsing](#german-text-parsing)
9. [Export Functionality](#export-functionality)
10. [German Localization](#german-localization)
11. [System Architecture](#system-architecture)
12. [Troubleshooting](#troubleshooting)
13. [Admin Cleanup Procedures](#admin-cleanup-procedures)
14. [Developer Reference](#developer-reference)

---

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

#### Method 3: Advanced Excel Import (Recommended)
1. Navigate to **Mobility Trailblazers → Import Candidates**
2. Use default file paths or browse to your Excel file
3. Enable "Also import candidate photos" if photos are available
4. Run dry run first to preview changes
5. Execute actual import after verification

#### Method 4: Excel to CSV Conversion
1. Open `tools/excel-to-csv-converter.html` in browser
2. Drag your Excel file onto the upload area
3. Review preview (first 10 records)
4. Click "Download CSV" for converted file
5. Import using Method 1 or 2

---

## CSV Format Specifications

### Required Column Headers

The CSV file must contain these exact column headers (case-sensitive):

```csv
ID,Name,Organisation,Position,LinkedIn-Link,Webseite,Article about coming of age,Description,Category,Status
```

### Column Descriptions

| Column Header | Description | Required | Format | Example |
|--------------|-------------|----------|--------|---------|
| **ID** | Unique candidate identifier | No | Alphanumeric | CAND-001 |
| **Name** | Full name of candidate | **Yes** | Text | Dr. Maria Müller |
| **Organisation** | Company/Institution | No | Text | Beispiel Mobility GmbH |
| **Position** | Job title/role | No | Text | CEO & Gründerin |
| **LinkedIn-Link** | LinkedIn profile URL | No | URL | https://linkedin.com/in/maria |
| **Webseite** | Company/personal website | No | URL | https://beispiel.de |
| **Article about coming of age** | Related article URL | No | URL | https://article.com/maria |
| **Description** | Full candidate description with evaluation criteria | No | Text | See format below |
| **Category** | Category type | No | Enum | Startup/Gov/Tech |
| **Status** | Top 50 status | No | Yes/No | Ja/Nein |

### Description Field Format

The Description field can contain structured evaluation criteria:

```
Mut & Pioniergeist: [Text about courage and pioneering spirit]
Innovationsgrad: [Text about innovation level]
Umsetzungsstärke: [Text about implementation strength]
Relevanz & Impact: [Text about relevance and impact]
Sichtbarkeit & Reichweite: [Text about visibility and reach]
```

### Sample CSV Data

```csv
ID,Name,Organisation,Position,LinkedIn-Link,Webseite,Article about coming of age,Description,Category,Status
MT001,Dr. Maria Müller,TechStart GmbH,CEO & Gründerin,https://linkedin.com/in/mariamueller,https://techstart.de,https://article.com/maria,"Mut & Pioniergeist: Revolutionäre Ideen in der Mobilität. Innovationsgrad: Entwicklung einer KI-basierten Verkehrsoptimierung.",Startup,Ja
MT002,Hans Schmidt,Gov Agency,Director,,,,"Innovationsgrad: Digitalisierung der Verwaltung.",Gov,Nein
```

---

## Excel to CSV Conversion

### Excel File Format (Input)

#### Expected Structure
- **Sheet Name**: "Kandidaten" (or first sheet if not found)
- **Data Start**: Row 7
- **Column Mapping**:

| Excel Column | CSV Field | Notes |
|-------------|-----------|-------|
| A (ID) | ID | Numeric identifier |
| B (Name) | Name | Full name |
| C (Position) | Position | Job title |
| D (Organisation) | Organisation | Company |
| E (Linkedin-Link) | LinkedIn-Link | Profile URL |
| F (Webseite) | Webseite | Website |
| G (Category) | Category | Mapped to standard |
| H (Nominator) | - | Not imported |
| I (Description) | Description | Full text |
| J (Nachricht) | - | Not imported |
| K (Top 50) | Status | Ja/Nein |
| L (Foto) | - | Not imported |

### Browser-Based Converter

**Location**: `tools/excel-to-csv-converter.html`

**Features**:
- Drag-and-drop interface
- Visual preview with statistics
- Instant CSV download
- UTF-8 encoding with BOM
- No server processing needed

**Usage**:
```javascript
// The converter automatically:
1. Reads Excel file
2. Finds "Kandidaten" sheet
3. Starts from row 7
4. Maps categories:
   - "Governance & Verwaltungen..." → "Gov"
   - "Etablierte Unternehmen" → "Tech"
   - "Start-ups..." → "Startup"
5. Normalizes Top 50 status
6. Validates URLs
7. Generates downloadable CSV
```

### PHP Script Converter

**Location**: `tools/excel-to-csv-converter.php`

**CLI Usage**:
```bash
php tools/excel-to-csv-converter.php
```

---

## Import Methods

### Method 1: Quick AJAX Import

**Location**: WordPress Admin → All Candidates → Import CSV

**Implementation**: `assets/js/candidate-import.js`

```javascript
// Automatic process:
1. File picker dialog opens
2. File uploads via AJAX
3. Progress overlay appears
4. Real-time import processing
5. Statistics displayed on completion
```

**Features**:
- File size validation (max 10MB)
- MIME type checking
- Progress overlay
- Error reporting
- Success statistics

### Method 2: Advanced Import with Dry Run

**Location**: WordPress Admin → Import Profiles

**Implementation**: `templates/admin/import-profiles.php`

**Process**:
```php
// Two-step import:
1. Dry Run Mode:
   - Preview what will be imported
   - Validate all data
   - Show potential issues
   - No database changes

2. Actual Import:
   - Process validated data
   - Update/create records
   - Generate report
```

**Options**:
- **Update existing**: Update existing candidates
- **Skip empty fields**: Preserve existing data
- **Validate URLs**: Check URL format
- **Import photos**: Download from URLs

### Method 3: Advanced Excel Import System

**Location**: WordPress Admin → Mobility Trailblazers → Import Candidates

**Implementation**: `includes/admin/class-mt-candidate-importer.php`

**Features**:
- Direct Excel file processing (no CSV conversion needed)
- Photo import from directory
- German criteria parsing
- Dry run capability
- Comprehensive validation
- Backup creation before operations

**Process**:
1. Select Excel file and photo directory
2. Choose import options (photos, dry run)
3. Execute import with real-time feedback
4. Review detailed results and statistics

### Method 4: AJAX Import with Progress Tracking

**Location**: Import/Export page → Import via AJAX

**Implementation**: `includes/ajax/class-mt-csv-import-ajax.php`

**Features**:
- Real-time progress bar
- Percentage completion
- Row-by-row processing
- Detailed error messages
- Pause/resume capability

---

## Migration Procedures

### Overview
Complete migration of candidate data from Excel to WordPress, including evaluation criteria extraction and photo attachment.

### Migration Steps

#### Phase 1: Preparation
1. **Backup existing data**:
   ```bash
   wp db export backup-$(date +%Y%m%d).sql
   ```

2. **Prepare migration scripts**:
   - `scripts/delete-all-candidates.php` - Safe candidate removal
   - `scripts/import-new-candidates.php` - CSV import with criteria parsing
   - `scripts/attach-existing-photos.php` - Photo attachment
   - `scripts/fix-meta-field-names.php` - Field name compatibility

#### Phase 2: Data Migration
1. **Delete existing candidates** (preserves jury members):
   ```bash
   php scripts/delete-all-candidates.php
   ```

2. **Import new candidates**:
   ```bash
   php scripts/import-new-candidates.php
   ```

3. **Attach photos from media library**:
   ```bash
   php scripts/attach-existing-photos.php
   ```

#### Phase 3: Validation
1. **Verify candidate count and data integrity**
2. **Check photo attachments**
3. **Validate German criteria extraction**
4. **Test template rendering**

### Migration Best Practices

#### Meta Field Compatibility
Save data in multiple field formats for template compatibility:
```php
// Save for both old and new templates
update_post_meta($post_id, '_mt_overview', $overview);
update_post_meta($post_id, '_mt_description_full', $overview);
```

#### Biography Generation
For candidates missing biography text, generate from role/organization:
```php
$biography = sprintf(
    "%s ist %s bei %s und trägt zur Mobilitätswende bei.",
    $name, $position, $organization
);
```

#### Evaluation Criteria Handling
Extract structured criteria from German description text:
- Mut & Pioniergeist
- Innovationsgrad  
- Umsetzungsstärke & Wirkung
- Relevanz für die Mobilitätswende
- Sichtbarkeit & Reichweite

### Post-Migration Cleanup

#### Database Tables to Reset
- `wp_mt_evaluations` - Clear for new evaluation cycle
- `wp_mt_votes` - Reset voting data
- `wp_mt_candidate_scores` - Clear calculated scores
- `wp_mt_jury_assignments` - Reset assignments

#### Temporary Files to Remove
- Browser-accessible migration scripts
- Emergency fix files
- Development backup files

---

## AJAX Import with Progress

### Frontend Implementation

```javascript
// assets/js/csv-import.js
MTCSVImport = {
    startImport: function(formData) {
        // Show progress modal
        this.showProgressModal();
        
        $.ajax({
            url: mt_csv_import.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = evt.loaded / evt.total * 100;
                        MTCSVImport.updateProgress(percentComplete);
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                MTCSVImport.handleResponse(response);
            }
        });
    }
}
```

### Backend Handler

```php
// includes/ajax/class-mt-csv-import-ajax.php
class MT_CSV_Import_Ajax extends MT_Base_Ajax {
    public function handle_import() {
        // Update progress
        $this->update_progress(10, 'Validating file...');
        
        // Validate
        if (!$this->validate_file($_FILES['csv_file'])) {
            $this->error('Invalid file');
            return;
        }
        
        // Parse CSV
        $this->update_progress(30, 'Parsing CSV...');
        $data = $this->parse_csv($_FILES['csv_file']['tmp_name']);
        
        // Import rows
        foreach ($data as $index => $row) {
            $percent = 30 + (($index / count($data)) * 60);
            $this->update_progress($percent, "Importing row $index...");
            
            $this->import_row($row);
        }
        
        // Complete
        $this->update_progress(100, 'Import complete!');
    }
    
    private function update_progress($percentage, $message) {
        $progress_key = 'mt_import_progress_' . get_current_user_id();
        set_transient($progress_key, [
            'percentage' => $percentage,
            'message' => $message,
            'timestamp' => time()
        ], 300);
    }
}
```

### Progress Modal UI

```html
<!-- Progress modal structure -->
<div class="mt-import-progress-modal">
    <div class="mt-progress-header">
        <h3>Importing CSV File</h3>
    </div>
    <div class="mt-progress-body">
        <div class="mt-progress-bar">
            <div class="mt-progress-fill" style="width: 0%"></div>
        </div>
        <div class="mt-progress-text">Initializing...</div>
        <div class="mt-progress-stats">
            <span class="imported">Imported: 0</span>
            <span class="updated">Updated: 0</span>
            <span class="skipped">Skipped: 0</span>
            <span class="errors">Errors: 0</span>
        </div>
    </div>
</div>
```

---

## Field Mapping & Validation

### System Architecture (v2.5.38)

The import system has been consolidated into 3 main classes:

1. **MT_Import_Handler** - Core import engine (handles all CSV processing)
2. **MT_CSV_Import_Ajax** - AJAX handler with progress tracking
3. **MT_Import_Export** - Admin UI and export functionality (no longer handles AJAX imports)

### Field Mapping

```php
// Candidate field mapping
const CANDIDATE_FIELD_MAPPING = [
    'ID' => '_mt_candidate_id',
    'Name' => 'post_title',
    'Organisation' => '_mt_organization',
    'Position' => '_mt_position',
    'LinkedIn-Link' => '_mt_linkedin_url',
    'Webseite' => '_mt_website_url',
    'Article about coming of age' => '_mt_article_url',
    'Description' => 'post_content',
    'Category' => '_mt_category_type',
    'Status' => '_mt_top_50_status'
];

// Jury member field mapping
const JURY_FIELD_MAPPING = [
    'name' => 'display_name',
    'title' => '_mt_title',
    'organization' => '_mt_organization',
    'email' => 'user_email',
    'role' => 'role'
];
```

### Character Encoding (v2.2.28)

```php
// BOM detection and removal
$handle = fopen($file, 'r');
$bom = fread($handle, 3);
if ($bom !== "\xEF\xBB\xBF") {
    rewind($handle);
}

// Header cleaning
$headers = array_map(function($header) {
    // Remove BOM
    $header = str_replace("\xEF\xBB\xBF", '', $header);
    // Trim whitespace
    $header = trim($header);
    // Normalize spaces
    $header = preg_replace('/\s+/', ' ', $header);
    return $header;
}, $headers);
```

### Delimiter Detection

```php
// Automatic delimiter detection
private function detect_delimiter($sample) {
    $delimiters = [',', ';', "\t", '|'];
    $counts = [];
    
    foreach ($delimiters as $delimiter) {
        $counts[$delimiter] = substr_count($sample, $delimiter);
    }
    
    return array_search(max($counts), $counts);
}
```

### URL Validation

```php
// URL validation and normalization
private function validate_url($url) {
    if (empty($url)) {
        return '';
    }
    
    // Add protocol if missing
    if (!preg_match('/^https?:\/\//', $url)) {
        $url = 'https://' . $url;
    }
    
    // Validate format
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return '';
    }
    
    return esc_url_raw($url);
}
```

### Category Mapping

```php
// Category normalization
private function map_category($category) {
    $category = strtolower(trim($category));
    
    $mappings = [
        'startup' => ['startup', 'start-up', 'start-ups'],
        'gov' => ['gov', 'government', 'verwaltung', 'governance'],
        'tech' => ['tech', 'technology', 'technologie', 'etablierte']
    ];
    
    foreach ($mappings as $standard => $variations) {
        foreach ($variations as $variation) {
            if (strpos($category, $variation) !== false) {
                return ucfirst($standard);
            }
        }
    }
    
    return 'Tech'; // Default
}
```

### Status Mapping

```php
// Top 50 status normalization
private function map_status($status) {
    $status = strtolower(trim($status));
    
    $yes_values = ['ja', 'yes', '1', 'true', 'top 50'];
    
    return in_array($status, $yes_values) ? 'yes' : 'no';
}
```

---

## German Text Parsing

### Evaluation Criteria Extraction

The system extracts structured evaluation criteria from German description text:

```php
// MT_Import_Handler::parse_evaluation_criteria()
public static function parse_evaluation_criteria($description) {
    if (empty($description)) {
        return [];
    }
    
    $criteria = [];
    
    // Pattern definitions with German text
    $patterns = [
        'mut' => '/Mut\s*&\s*Pioniergeist:\s*(.+?)(?=(?:Innovationsgrad:|Umsetzungsstärke:|Relevanz|Sichtbarkeit|$))/isu',
        'innovation' => '/Innovationsgrad:\s*(.+?)(?=(?:Mut\s*&\s*Pioniergeist:|Umsetzungsstärke:|Relevanz|Sichtbarkeit|$))/isu',
        'implementation' => '/Umsetzungsstärke\s*&?\s*Wirkung:\s*(.+?)(?=(?:Mut\s*&\s*Pioniergeist:|Innovationsgrad:|Relevanz|Sichtbarkeit|$))/isu',
        'relevance' => '/Relevanz\s*(?:&\s*Impact|für die Mobilitätswende)?:\s*(.+?)(?=(?:Mut\s*&\s*Pioniergeist:|Innovationsgrad:|Umsetzungsstärke:|Sichtbarkeit|$))/isu',
        'visibility' => '/Sichtbarkeit\s*&\s*Reichweite:\s*(.+?)$/isu'
    ];
    
    foreach ($patterns as $key => $pattern) {
        if (preg_match($pattern, $description, $matches)) {
            $criteria[$key] = trim($matches[1]);
        }
    }
    
    return $criteria;
}
```

### Usage Example

```php
// During import
$description = $row['Description'];
$criteria = MT_Import_Handler::parse_evaluation_criteria($description);

// Save to meta fields
if (!empty($criteria['mut'])) {
    update_post_meta($post_id, '_mt_evaluation_courage', $criteria['mut']);
}
if (!empty($criteria['innovation'])) {
    update_post_meta($post_id, '_mt_evaluation_innovation', $criteria['innovation']);
}
// ... etc
```

### Supported Criteria Fields

| German Text | Meta Field | English |
|------------|------------|---------|
| Mut & Pioniergeist | `_mt_evaluation_courage` | Courage & Pioneering Spirit |
| Innovationsgrad | `_mt_evaluation_innovation` | Innovation Level |
| Umsetzungsstärke & Wirkung | `_mt_evaluation_implementation` | Implementation & Impact |
| Relevanz & Impact | `_mt_evaluation_relevance` | Relevance & Impact |
| Sichtbarkeit & Reichweite | `_mt_evaluation_visibility` | Visibility & Reach |

---

## Export Functionality

### Export Methods

#### Standard Export
```php
// Export all candidates
public function export_candidates() {
    $candidates = get_posts([
        'post_type' => 'mt_candidate',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ]);
    
    $this->output_csv($candidates, 'candidates.csv');
}
```

#### Streaming Export (v2.2.28)
For large datasets to prevent memory issues:

```php
// Stream export in chunks
public function export_candidates_stream() {
    // Set headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="candidates.csv"');
    
    // Add BOM for Excel
    echo "\xEF\xBB\xBF";
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Write headers
    fputcsv($output, $this->get_csv_headers());
    
    // Process in batches
    $offset = 0;
    $batch_size = 100;
    
    while (true) {
        $candidates = get_posts([
            'post_type' => 'mt_candidate',
            'posts_per_page' => $batch_size,
            'offset' => $offset
        ]);
        
        if (empty($candidates)) {
            break;
        }
        
        foreach ($candidates as $candidate) {
            fputcsv($output, $this->format_candidate_row($candidate));
        }
        
        // Clear cache
        wp_cache_flush();
        
        $offset += $batch_size;
    }
    
    fclose($output);
    exit;
}
```

### Export Format

The export maintains the same CSV format as import:

```csv
ID,Name,Organisation,Position,LinkedIn-Link,Webseite,Article about coming of age,Description,Category,Status
"MT001","Dr. Maria Müller","TechStart GmbH","CEO & Gründerin","https://linkedin.com/in/mariamueller","https://techstart.de","https://article.com/maria","Full description...","Startup","Ja"
```

### Export Options

Available exports:
- **Candidates** - All candidate data
- **Evaluations** - Jury evaluations with scores
- **Assignments** - Jury-candidate mappings
- **Audit Log** - Security audit trail

---

## German Localization

### Overview
The plugin includes complete German localization (1000+ strings) for the DACH region market.

### Language Files
```
/languages/
├── mobility-trailblazers-de_DE.po    # Translation source
└── mobility-trailblazers-de_DE.mo    # Compiled translation
```

### Activation
1. **Install German Language Pack:**
```bash
wp language core install de_DE --activate
```

2. **Set Site Language:**
```bash
wp option update WPLANG de_DE
```

3. **Clear Cache:**
```bash
wp cache flush
```

### Language Detection Priority
1. URL parameter (`?mt_lang=de_DE`)
2. User meta preference
3. Browser cookie
4. Site language setting
5. Default to German

### Translation Coverage
- ✅ All evaluation criteria and scoring
- ✅ Jury dashboard and assignments
- ✅ Admin interface and settings
- ✅ Email templates and notifications
- ✅ Error messages and confirmations

---

## System Architecture

### Class Hierarchy

```
MT_Base_Ajax (Abstract)
├── MT_CSV_Import_Ajax (AJAX import with progress tracking)
└── Other AJAX handlers (evaluations, assignments)

MT_Import_Handler (Core engine)
├── process_csv_import()
├── import_candidates()
├── import_jury_members()
├── parse_evaluation_criteria()
└── sanitize_csv_value() (security)

MT_Import_Export (Admin UI & Export only)
├── render_page()
├── download_template()
└── export_data()
Note: AJAX import removed in v2.5.38 (handled by MT_CSV_Import_Ajax)
```

### Data Flow

```
1. User selects CSV file
   ↓
2. JavaScript validates file
   ↓
3. AJAX upload to server
   ↓
4. PHP validates security
   ↓
5. MT_Import_Handler parses CSV
   ↓
6. Row-by-row processing
   ↓
7. Database operations
   ↓
8. Progress updates (transients)
   ↓
9. Results returned to user
```

### File Locations

```
/includes/
├── admin/
│   ├── class-mt-import-handler.php      # Core engine
│   ├── class-mt-import-export.php       # Admin UI
│   └── class-mt-candidate-columns.php   # Column display
├── ajax/
│   ├── class-mt-import-ajax.php         # Quick import
│   └── class-mt-csv-import-ajax.php     # Progress import
/assets/
├── js/
│   ├── candidate-import.js              # Quick import JS
│   └── csv-import.js                    # Progress import JS
├── css/
│   └── csv-import.css                   # Import UI styles
/data/templates/
├── candidates.csv                       # Template file
└── jury-members.csv                     # Template file
/tools/
├── excel-to-csv-converter.html          # Browser converter
└── excel-to-csv-converter.php           # PHP converter
```

---

## Troubleshooting

### Quick Import Issues

#### Issue: "Excel file not found" error
**Causes**:
- Incorrect file path
- File moved or renamed
- Permission issues

**Solutions**:
```bash
# Verify file exists
ls -la "path/to/excel/file.xlsx"

# Check permissions
chmod 644 "path/to/excel/file.xlsx"

# Use full absolute path
```

#### Issue: German criteria sections missing
**Causes**:
- Description field empty
- Wrong section headers
- Encoding issues

**Solutions**:
```
Ensure Excel Description column contains sections with headers:
- Überblick:
- Mut & Pioniergeist:
- Innovationsgrad:
- Umsetzungskraft & Wirkung:
- Relevanz für die Mobilitätswende:
- Vorbildfunktion & Sichtbarkeit:
```

#### Issue: Photos not attaching during import
**Causes**:
- Photo directory not found
- Filename mismatch
- Permission issues

**Solutions**:
```bash
# Verify photo directory structure
ls -la "path/to/photos/"

# Check for candidate name matches
# Photos should match: "firstname-lastname.webp"

# Run separate photo attachment
php scripts/attach-existing-photos.php
```

### Legacy Import Issues

#### Issue: Import page shows "mt-import-profiles" not found
**Solution**: This page was removed in v2.5.37. Use the newer "Import Candidates" page instead:
- **Old URL**: `admin.php?page=mt-import-profiles` (removed)
- **New URL**: `admin.php?page=mt-candidate-importer` (active)

### Common Issues and Solutions

#### Issue: "Invalid file type" error
**Causes**:
- File is not CSV format
- File extension incorrect
- MIME type mismatch

**Solutions**:
```bash
# Ensure .csv extension
# Save as "CSV UTF-8 (Comma delimited)" in Excel
# Check MIME type is text/csv or text/plain
```

#### Issue: German characters corrupted (ä, ö, ü, ß)
**Causes**:
- Wrong encoding
- Missing BOM
- Excel export issues

**Solutions**:
```php
// Save with UTF-8 BOM in Excel:
1. File → Save As
2. Choose "CSV UTF-8 (Comma delimited)"
3. This adds BOM automatically

// Or add BOM manually:
echo "\xEF\xBB\xBF" . $csv_content;
```

#### Issue: Headers not recognized
**Causes**:
- BOM in headers
- Case mismatch
- Extra spaces

**Solutions**:
```php
// System now handles automatically:
- BOM removal
- Case-insensitive matching
- Whitespace trimming
- Space normalization
```

#### Issue: Categories not mapping
**Causes**:
- Unknown category names
- Language variations

**Solutions**:
```
Use standard values:
- "Startup" (or Start-up, Start-ups)
- "Gov" (or Government, Verwaltung)
- "Tech" (or Technology, Etablierte)
```

#### Issue: URLs not importing
**Causes**:
- Missing protocol
- Invalid format

**Solutions**:
```
System auto-adds https:// if missing
Ensure valid URL format:
- Good: https://example.com
- Good: example.com (auto-fixed)
- Bad: htp://example (typo)
```

#### Issue: Import times out
**Causes**:
- File too large
- Server timeout
- Memory limit
- Photo processing time

**Solutions**:
```php
// Split into smaller files (500 rows max)
// Or increase PHP limits:
set_time_limit(300);
ini_set('memory_limit', '256M');

// For photo imports, process separately:
// 1. Import candidates first (without photos)
// 2. Run photo attachment script separately
```

#### Issue: Duplicate entries created
**Causes**:
- Missing update flag
- No unique identifier

**Solutions**:
```
Enable "Update existing candidates"
Ensure ID or Name field is unique
Use dry run to preview first
```

### Debug Mode

Enable WordPress debug logging:

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Check logs at:
// wp-content/debug.log
```

Import-specific logs:
```
MT CSV Import: Starting import...
MT CSV Import: Found 50 rows
MT CSV Import: Row 5 - Invalid email format
MT CSV Import: Completed - 48 imported, 2 errors
```

---

## Admin Cleanup Procedures

### Removing Import Pages

When consolidating import functionality, follow this process to safely remove redundant pages:

#### 1. Identify Dependencies
Before removing any import page, check dependencies:
```bash
grep -r "mt-import-profiles" includes/
grep -r "MT_Import_Handler" includes/
```

#### 2. Preserve Core Classes
Keep essential classes that may be used by multiple systems:
- `MT_Import_Handler` - Used by Import/Export page and AJAX handlers
- Core service classes with static methods
- Database migration utilities

#### 3. Safe Removal Process
1. **Remove menu registration** from `class-mt-admin.php`
2. **Delete template files** (e.g., `templates/admin/import-profiles.php`)
3. **Keep handler classes** if used elsewhere
4. **Update documentation** to reflect changes

#### 4. Testing Checklist
- ✅ Remaining import systems function
- ✅ No broken menu links
- ✅ AJAX handlers still operational
- ✅ Export functionality preserved

### Import System Comparison

Current active import systems:

| System | Menu Location | File Types | Features |
|--------|--------------|------------|----------|
| **Quick Import** | All Candidates → Import CSV | CSV | Simple upload, basic validation |
| **Import/Export** | MT Award System → Import/Export | CSV | Advanced options, dry run |
| **Candidate Importer** | MT Award System → Import Candidates | Excel + Photos | Full workflow, criteria parsing |

### Backup Procedures

Before any major import operation:

```bash
# Database backup
wp db export backup-$(date +%Y%m%d-%H%M%S).sql

# Media library backup (if processing photos)
tar -czf media-backup-$(date +%Y%m%d).tar.gz wp-content/uploads/

# Plugin state backup
cp -r wp-content/plugins/mobility-trailblazers/ ../plugin-backup/
```

### Recovery Procedures

If import fails or causes issues:

1. **Database Recovery**:
   ```bash
   wp db import backup-YYYYMMDD-HHMMSS.sql
   ```

2. **Clear corrupted data**:
   ```bash
   wp post delete $(wp post list --post_type=mt_candidate --format=ids) --force
   ```

3. **Reset plugin state**:
   ```bash
   wp plugin deactivate mobility-trailblazers
   wp plugin activate mobility-trailblazers
   ```

---

## Developer Reference

### Using the Import Handler

```php
// Direct usage
$handler = new \MobilityTrailblazers\Admin\MT_Import_Handler();

// Import candidates
$result = $handler->process_csv_import(
    $file,           // File path or $_FILES array
    'candidates',    // Import type
    true            // Update existing
);

// Import jury members
$result = $handler->process_csv_import($file, 'jury_members', false);

// Parse evaluation criteria
$criteria = MT_Import_Handler::parse_evaluation_criteria($description);
```

### Extending the Import

#### Adding Custom Fields

1. Update field mapping:
```php
// In MT_Import_Handler
const CANDIDATE_FIELD_MAPPING = [
    // ... existing fields
    'Custom Field' => '_mt_custom_field',
];
```

2. Add validation:
```php
private function validate_custom_field($value) {
    // Validation logic
    return sanitize_text_field($value);
}
```

3. Handle in import:
```php
if (!empty($data['Custom Field'])) {
    update_post_meta($post_id, '_mt_custom_field', $data['Custom Field']);
}
```

#### Custom Import Hooks

While not currently implemented, you can add:

```php
// Proposed filters
$data = apply_filters('mt_import_candidate_data', $data, $row);
$post_id = apply_filters('mt_import_candidate_post_id', $post_id, $data);

// Proposed actions
do_action('mt_before_import_candidate', $data);
do_action('mt_after_import_candidate', $post_id, $data);
```

### AJAX Integration

```javascript
// Custom import handler
function customImport(file) {
    var formData = new FormData();
    formData.append('action', 'mt_custom_import');
    formData.append('csv_file', file);
    formData.append('nonce', mt_admin.nonce);
    
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                console.log('Imported:', response.data.imported);
            }
        }
    });
}
```

### WP-CLI Support

```bash
# Import via WP-CLI
wp eval "
\$handler = new \\MobilityTrailblazers\\Admin\\MT_Import_Handler();
\$result = \$handler->process_csv_import('/path/to/file.csv', 'candidates', false);
print_r(\$result);
"

# Export via WP-CLI
wp eval "
\$export = new \\MobilityTrailblazers\\Admin\\MT_Import_Export();
\$export->export_candidates();
"
```

### Testing Imports

```php
// Test import with sample data
$test_data = [
    ['Name' => 'Test User', 'Organisation' => 'Test Org'],
    // ... more test rows
];

$handler = new MT_Import_Handler();
foreach ($test_data as $row) {
    $result = $handler->import_candidate($row, false);
    assert($result['success'] === true);
}
```

---

## Security Updates (v2.5.39)

### Critical Security Fixes Applied

#### 1. SQL Injection Prevention
All database queries now use prepared statements:
```php
// Before (VULNERABLE):
$wpdb->get_results("SELECT * FROM {$table} WHERE id IN (" . implode(',', $ids) . ")");

// After (SECURE):
$placeholders = array_fill(0, count($ids), '%d');
$wpdb->get_results($wpdb->prepare(
    "SELECT * FROM %i WHERE id IN (" . implode(',', $placeholders) . ")",
    array_merge([$table], $ids)
));
```

#### 2. Path Traversal Protection
File paths are now validated against allowed directories:
```php
private function validate_file_path($path) {
    $real_path = realpath($path);
    $allowed_dirs = [
        wp_normalize_path(ABSPATH),
        wp_normalize_path(wp_upload_dir()['basedir']),
        wp_normalize_path(WP_CONTENT_DIR)
    ];
    
    foreach ($allowed_dirs as $allowed_dir) {
        if (strpos($real_path, realpath($allowed_dir)) === 0) {
            return $real_path;
        }
    }
    return false;
}
```

#### 3. Permission Requirements
All import/export operations now require administrator access:
```php
// All operations now check:
if (!current_user_can('manage_options')) {
    wp_die(__('Administrator access required', 'mobility-trailblazers'));
}
```

### New Data Exchange Architecture

#### Unified Service Layer
A new `MT_Data_Exchange_Service` provides centralized import/export:
- Strategy pattern for different data types
- Consistent validation and sanitization
- Transaction support with rollback capability
- Import history tracking

#### Available Export Types
- **Candidates**: Full metadata with photos
- **Jury Members**: User data and roles
- **Assignments**: Jury-candidate mappings
- **Evaluations**: All scores and comments
- **Audit Log**: System activity tracking
- **Error Log**: Application errors
- **Coaching**: Progress and statistics
- **Settings**: Plugin configuration backup

---

*End of Import/Export Guide*
