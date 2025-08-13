# AJAX CSV Import Guide

## Overview
The Mobility Trailblazers platform provides an AJAX-based CSV import system for candidate data, featuring real-time progress feedback, comprehensive validation, and German language support.

## Quick Start

### For Users

1. Navigate to **Candidates** in the WordPress admin menu
2. Click the **Import CSV** button next to "Add New"
3. Select your CSV file from the file picker dialog
4. Wait for the import to complete
5. Review the import statistics

### CSV Format Requirements

Your CSV file must include these exact column headers:
```
ID,Name,Organisation,Position,LinkedIn-Link,Webseite,Article about coming of age,Description,Category,Status
```

## Technical Implementation

### Frontend Architecture

#### File: `assets/js/candidate-import.js`

The JavaScript module handles the entire import workflow client-side:

```javascript
// Initialize on page load
jQuery(document).ready(function($) {
    // Only run on candidates list page
    if (!$('body').hasClass('edit-php') || 
        !$('body').hasClass('post-type-mt_candidate')) {
        return;
    }
    
    // Add import button dynamically
    var importButton = '<a href="#" id="mt-import-candidates" class="page-title-action">Import CSV</a>';
    $('.wrap .page-title-action').first().after(importButton);
});
```

### Backend Architecture

#### File: `includes/ajax/class-mt-import-ajax.php`

The AJAX handler extends `MT_Base_Ajax` for security and consistency:

```php
class MT_Import_Ajax extends MT_Base_Ajax {
    public function handle_candidate_import() {
        // Security checks
        if (!$this->verify_nonce('mt_ajax_nonce')) {
            $this->error('Security check failed');
            return;
        }
        
        // File validation
        // Size limits
        // MIME type checking
        // CSV processing
    }
}
```

## Security Implementation

### Multi-Layer Validation

1. **Nonce Verification**
   - Uses WordPress nonce system
   - Token: `mt_ajax_nonce`

2. **Permission Checks**
   - Requires `edit_posts` capability
   - User context logging

3. **File Validation**
   - Extension check (.csv)
   - MIME type validation
   - Size limit (10MB)

4. **Content Sanitization**
   - All input sanitized before database storage
   - URL validation and normalization
   - HTML stripping from text fields

## Data Processing

### Field Mapping System

The import maps CSV columns to WordPress meta fields:

| CSV Column | WordPress Field | Validation |
|------------|----------------|------------|
| ID | `_mt_candidate_id` | Alphanumeric |
| Name | `post_title` | Required, sanitized |
| Organisation | `_mt_organization` | Text |
| Position | `_mt_position` | Text |
| LinkedIn-Link | `_mt_linkedin_url` | URL format |
| Webseite | `_mt_website_url` | URL format |
| Article | `_mt_article_url` | URL format |
| Description | `post_content` | HTML allowed |
| Category | `_mt_category_type` | Enum validation |
| Status | `_mt_top_50_status` | Boolean |

### German Text Extraction

The system parses evaluation criteria from the Description field:

```php
// Extract "Mut & Pioniergeist" section
$pattern = '/Mut\s*&\s*Pioniergeist:\s*(.+?)(?=(?:Innovationsgrad:|$))/isu';
preg_match($pattern, $description, $matches);
if ($matches) {
    update_post_meta($post_id, '_mt_evaluation_courage', trim($matches[1]));
}
```

## Error Handling

### User-Friendly Messages

All errors are translated and provide actionable information:

```javascript
// JavaScript error display
if (!response.success) {
    var message = response.data.message || mt_import.i18n.import_error;
    alert(message);
    
    // Show detailed errors if available
    if (response.data.error_details) {
        console.error('Import errors:', response.data.error_details);
    }
}
```

### Import Statistics

After import, users see:
- Number of candidates created
- Number of candidates updated
- Number of rows skipped
- Number of errors encountered
- Detailed error information

## Performance Optimization

### Client-Side
- File size validation before upload
- Progress overlay for user feedback
- Asynchronous processing

### Server-Side
- Row-by-row processing to manage memory
- Database operations use WordPress caching
- Audit logging is deferred

## Troubleshooting Guide

### Common Issues and Solutions

#### Issue: "Invalid file type" error
**Solution**: Ensure your file has a `.csv` extension and is saved as CSV format, not Excel.

#### Issue: German characters (ä, ö, ü) appear corrupted
**Solution**: Save your CSV as UTF-8 with BOM. In Excel, use "Save As" → "CSV UTF-8 (Comma delimited)".

#### Issue: URLs not importing correctly
**Solution**: Ensure all URLs start with `http://` or `https://`. The system will auto-add `https://` if missing.

#### Issue: Categories not recognized
**Solution**: Use exact values: `Startup`, `Gov`, or `Tech` (case-insensitive).

#### Issue: Import times out
**Solution**: Split large files into smaller batches (max 500 rows recommended).

## Developer Reference

### Extending the Import

#### Adding Custom Fields

1. Update the field mapping:
```php
// In class-mt-enhanced-profile-importer.php
public static function get_field_mapping() {
    return [
        // ... existing fields
        'Custom Field' => '_mt_custom_field',
    ];
}
```

2. Add validation if needed:
```php
// Validate custom field
if (!empty($data['Custom Field'])) {
    $data['Custom Field'] = sanitize_text_field($data['Custom Field']);
}
```

#### Custom Import Hooks

While not currently implemented, you can add filters:
```php
// Proposed filter for extending import
$data = apply_filters('mt_import_candidate_data', $data, $row);
```

### Testing the Import

#### Unit Testing Checklist
- [ ] File upload with valid CSV
- [ ] File rejection for non-CSV
- [ ] Size limit enforcement
- [ ] German character preservation
- [ ] URL validation
- [ ] Category mapping
- [ ] Duplicate handling
- [ ] Error reporting

#### Sample Test Data
```csv
ID,Name,Organisation,Position,LinkedIn-Link,Webseite,Article about coming of age,Description,Category,Status
TEST001,Anna Müller,StartUp GmbH,CEO,https://linkedin.com/in/anna,https://startup.de,https://article.de/anna,"Mut & Pioniergeist: Führt Innovation...",Startup,Top50
TEST002,Hans Schmidt,Gov Agency,Director,,,,"Innovationsgrad: Entwickelt neue...",Gov,
```

## Best Practices

### For Users
1. Always backup before large imports
2. Test with a small sample first
3. Verify data in Excel before importing
4. Use the exact column headers provided

### For Developers
1. Always use the base AJAX class for handlers
2. Implement comprehensive validation
3. Provide detailed error messages
4. Log all import operations for audit trail
5. Test with various character encodings

## Version History

- **v2.2.16** (2025-08-13): Initial AJAX import implementation
  - File picker dialog
  - Real-time progress
  - German text parsing
  - Comprehensive validation

## Related Documentation

- [CSV Import Guide](csv-import-guide.md) - General CSV import documentation
- [Developer Guide](developer-guide.md) - Complete technical reference
- [Candidate Management](general_index.md#administrative-interface) - Overview of candidate system

---
*Last Updated: August 13, 2025 | Version 2.2.16*