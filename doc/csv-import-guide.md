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

### Step 1: Access Import Interface
1. Navigate to **WordPress Admin → Mobility Trailblazers → All Candidates**
2. Click the **Import CSV** button next to "Add New"

### Step 2: Configure Import Options
- **Update existing candidates**: Check to update existing entries (matched by email)
- **Skip duplicates**: Check to skip entries that already exist

### Step 3: Upload CSV File
1. Click "Choose File" and select your CSV
2. Review the format requirements displayed
3. Click "Import" to start the process

### Step 4: Review Results
After import completion, you'll see:
- Number of candidates imported
- Number of candidates updated
- Number of entries skipped
- Any error messages

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

## Developer Notes

### Extending Import Functionality
The import system uses two main classes:
- `MT_Enhanced_Profile_Importer`: Core import logic
- `MT_Candidate_Columns`: UI and column management

### Filter Hooks
```php
// Modify import data before processing
apply_filters('mt_csv_import_data', $data, $row_number);

// Modify field mapping
apply_filters('mt_csv_field_mapping', $mapping, $headers);
```

### Custom Validation
Add custom validation in `MT_Enhanced_Profile_Importer::validate_candidate()`

## Support

For issues or questions:
1. Check the debug log for detailed error messages
2. Verify CSV format matches specifications
3. Test with the sample CSV file first
4. Contact support with error details and sample data

---

*Last Updated: Version 2.2.15*