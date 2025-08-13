# CSV Import Guide for Mobility Trailblazers

## Overview
The Mobility Trailblazers platform supports comprehensive CSV import functionality for bulk candidate data management. This guide covers the specific CSV format requirements and import process.

## CSV Format Specification

### Required Column Headers (Case-Sensitive)
The CSV file must contain the following column headers in the first row:

| Column Header | Description | Required | Example |
|--------------|-------------|----------|---------|
| **ID** | Unique candidate identifier | No | CAND-001 |
| **Name** | Full name of the candidate | **Yes** | Dr. Maria Müller |
| **Organisation** | Company/Institution name | No | Beispiel Mobility GmbH |
| **Position** | Job title/role | No | CEO & Gründerin |
| **LinkedIn-Link** | LinkedIn profile URL | No | https://linkedin.com/in/mariamueller |
| **Webseite** | Company/personal website | No | https://beispiel-mobility.de |
| **Article about coming of age** | Related article URL | No | https://example.com/article |
| **Description** | Full candidate description | No | Detailed bio text... |
| **Category** | Category type | No | Startup/Gov/Tech |
| **Status** | Top 50 status | No | Ja/Nein (Yes/No) |

### Data Mapping
The CSV columns are mapped to WordPress post meta fields as follows:

- **ID** → `_mt_candidate_id`
- **Name** → `post_title`
- **Organisation** → `_mt_organization`
- **Position** → `_mt_position`
- **LinkedIn-Link** → `_mt_linkedin_url`
- **Webseite** → `_mt_website_url`
- **Article** → `_mt_article_url`
- **Description** → `_mt_description_full`
- **Category** → `_mt_category_type`
- **Status** → `_mt_top_50_status`

## Import Features

### Character Encoding
- **UTF-8 Support**: Full support for German special characters (ä, ö, ü, ß)
- **BOM Handling**: Automatically detects and handles UTF-8 BOM
- **Encoding Conversion**: Auto-converts to UTF-8 if different encoding detected

### URL Validation
- Validates all URL fields (LinkedIn, Website, Article)
- Automatically adds `https://` protocol if missing
- Only saves valid URLs to the database
- Empty URLs are handled gracefully

### Category Mapping
The system automatically maps category variations to standardized values:

| Input Variations | Mapped To |
|-----------------|-----------|
| startup, start-up, Start-ups | **Startup** |
| gov, government, verwaltung, Governance | **Gov** |
| tech, technology, technologie | **Tech** |

### Status Mapping
Top 50 status values are normalized:

| Input Values | Mapped To |
|-------------|-----------|
| Ja, Yes, 1, true, Top 50 | **yes** |
| Nein, No, 0, false | **no** |

## Import Process

### Option 1: Quick Import (AJAX)
**Location**: WordPress Admin → Mobility Trailblazers → All Candidates

1. Click the **Import CSV** button next to "Add New"
2. Select your CSV file
3. Choose import options:
   - **Update existing candidates**: Updates existing entries (matched by name)
   - **Skip duplicates**: Skips entries that already exist
4. Click "Import" to start the process
5. Review results in the notification

### Option 2: Advanced Import with Dry Run
**Location**: WordPress Admin → Mobility Trailblazers → Import Profiles

This enhanced importer provides:
- **Dry Run Mode**: Preview what will be imported without making changes
- **Field Mapping**: Visual confirmation of how CSV columns map to database fields
- **Validation Report**: See potential issues before importing
- **Photo Import**: Option to import candidate photos from URLs
- **URL Validation**: Validates all URLs before import
- **Skip Empty Fields**: Option to preserve existing data when CSV field is empty

#### Advanced Import Steps:
1. Navigate to **Import Profiles** page
2. Upload your CSV file
3. Configure import options:
   - **Update existing**: Update existing records
   - **Skip empty fields**: Don't overwrite with empty values
   - **Validate URLs**: Check all URLs are valid
   - **Import photos**: Download and attach photos from URLs
4. Click **Dry Run** to preview the import
5. Review the validation report
6. If satisfied, click **Import** to process the data

### Import Results
Both importers will show:
- Number of candidates imported
- Number of candidates updated
- Number of entries skipped
- Any validation errors or warnings

## CSV File Requirements

### Technical Specifications
- **File Type**: .csv (comma-separated values)
- **Encoding**: UTF-8 (with or without BOM)
- **Delimiters**: Comma (,), Semicolon (;), Tab, or Pipe (|) - auto-detected
- **Maximum Size**: 10MB
- **Header Row**: Required in first valid row

### Best Practices
1. **Use UTF-8 encoding** when saving from Excel/LibreOffice
2. **Include header row** with exact column names
3. **Validate URLs** before import for better success rate
4. **Use consistent date formats** if including dates
5. **Test with small batch** before full import

## Sample CSV Format

```csv
ID,Name,Organisation,Position,LinkedIn-Link,Webseite,Article about coming of age,Description,Category,Status
"CAND-001","Dr. Maria Müller","Beispiel Mobility GmbH","CEO & Gründerin","https://linkedin.com/in/mariamueller","https://beispiel-mobility.de","https://example.com/article-maria","Dr. Maria Müller ist eine Pionierin der nachhaltigen Mobilität.","Startup","Ja"
```

## Export Functionality

### Exporting Candidates
1. Go to **All Candidates** page
2. Select candidates to export (or leave unselected for all)
3. Choose **Export to CSV** from bulk actions
4. File downloads with all candidate data

### Export Format
The export includes all fields in the same format as import:
- Maintains UTF-8 encoding with BOM
- Includes all custom meta fields
- Ready for re-import if needed

## Troubleshooting

### Common Issues

#### Special Characters Display Incorrectly
- **Solution**: Ensure CSV is saved with UTF-8 encoding
- In Excel: Save As → CSV UTF-8 (Comma delimited)
- In LibreOffice: Save As → Text CSV → Character Set: UTF-8

#### URLs Not Importing
- **Issue**: Invalid URL format
- **Solution**: Ensure URLs start with http:// or https://
- System will auto-add https:// if missing

#### Categories Not Mapping
- **Issue**: Category name not recognized
- **Solution**: Use standard values: Startup, Gov, or Tech

#### Import Fails Completely
- **Check**:
  - File size under 10MB
  - CSV format (not Excel .xlsx)
  - Header row present
  - Name column has values

### Debug Mode
For debugging imports, check WordPress debug log:
```
wp-content/debug.log
```

Import messages are logged with prefix: `MT CSV Import:`

## Admin Display

### Custom Columns
After import, the All Candidates page displays:
- **Import ID**: Unique identifier with styled display
- **Organization**: Company/institution name
- **Position**: Role/title
- **Category**: Color-coded icons
  - Startup: Green lightbulb
  - Gov: Blue building
  - Tech: Red desktop
- **Top 50**: Checkmark if selected
- **Links**: Icons for LinkedIn, Website, Article

### Sorting
All custom columns are sortable for easy data management.

## Security Considerations

### Data Validation
- All input data is sanitized
- URLs are validated before storage
- SQL injection prevention via prepared statements
- XSS protection through proper escaping

### User Permissions
- Import requires `edit_posts` capability
- Export requires `edit_posts` capability
- Only administrators can bulk delete

## Developer Notes (v2.2.25)

### Import System Architecture
The import system has been consolidated into a single handler:
- `MT_Import_Handler`: Core import logic for all types
- `MT_Import_Export`: Admin UI and exports
- `MT_CSV_Import_Ajax`: AJAX with progress tracking
- `MT_Import_Ajax`: Quick import functionality

### Using the Import Handler
```php
// Import candidates
$handler = new \MobilityTrailblazers\Admin\MT_Import_Handler();
$result = $handler->process_csv_import($file, 'candidates', $update_existing);

// Import jury members
$result = $handler->process_csv_import($file, 'jury_members', $update_existing);

// Parse evaluation criteria from description
$criteria = MT_Import_Handler::parse_evaluation_criteria($description);
```

### Field Mappings
Access field mappings via class constants:
```php
// Candidate fields
MT_Import_Handler::CANDIDATE_FIELD_MAPPING

// Jury member fields  
MT_Import_Handler::JURY_FIELD_MAPPING
```

## Support

For issues or questions:
1. Check the debug log for detailed error messages
2. Verify CSV format matches specifications
3. Test with the sample CSV file first
4. Contact support with error details and sample data

---

## Version 2.2.24 Update

### New AJAX Import System
Version 2.2.24 introduces a comprehensive AJAX-based import system with real-time progress tracking:

#### Features
- **Progress Modal**: Visual progress bar with percentage completion
- **Real-time Updates**: Live status messages during import
- **Error Details**: Row-by-row error reporting
- **Batch Processing**: Automatic handling of large files
- **File Validation**: Pre-upload validation for size and type

#### Using the AJAX Import
1. Navigate to **Mobility Trailblazers → Import/Export**
2. Click **Import via AJAX** button
3. Select file and import type
4. Watch real-time progress
5. Review detailed results

### Jury Members Import
New support for importing jury members via CSV:

**Required Headers:**
```csv
name,title,organization,email,role
```

**Features:**
- Automatic WordPress user creation
- Role assignment (mt_jury_member)
- Email validation and duplicate checking
- Update existing members option

### WP-CLI Support
Version 2.2.24 adds WP-CLI command support for server-side imports:

```bash
# Import with WP-CLI
wp eval "\$handler = new \\MobilityTrailblazers\\Admin\\MT_Import_Handler(); \$result = \$handler->process_csv_import('/path/to/file.csv', 'candidates', false); print_r(\$result);"
```

---

*Last Updated: Version 2.2.24*