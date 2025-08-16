# Excel Import Guide for Mobility Trailblazers Platform

## Overview
This guide documents the Excel to CSV conversion process for importing candidate data from Excel files into the Mobility Trailblazers platform.

## Excel File Format (Input)

### Expected Sheet Name
- Primary sheet: `Kandidaten`
- Fallback: First sheet if "Kandidaten" not found

### Column Structure
The Excel file should have these columns (starting from row 7):

| Column | Field Name | Description |
|--------|------------|-------------|
| A | ID | Numeric identifier (1, 2, 3...) |
| B | Name | Full name of candidate |
| C | Position | Job title/role |
| D | Organisation | Company/institution name |
| E | Linkedin-Link | LinkedIn profile URL |
| F | Webseite | Company/personal website |
| G | Category | Category classification |
| H | Nominator | Person who nominated |
| I | Description | Full description text |
| J | Nachricht Nominator | Nominator's message |
| K | Top 50 | "Ja" if in Top 50 |
| L | Foto für Datenbank | Photo availability |

## Conversion Tools

### 1. Browser-Based Converter (Recommended)
Located in: `tools/excel-to-csv-converter.html`

**Features:**
- Drag-and-drop Excel upload
- Visual preview with statistics
- Instant CSV download
- No server processing needed
- UTF-8 encoding with BOM

**Usage:**
1. Open the HTML file in any modern browser
2. Drag your Excel file onto the upload area
3. Review the preview (shows first 10 records)
4. Click "Download CSV" to get the converted file

### 2. PHP Script Converter
Located in: `tools/excel-to-csv-converter.php`

**Usage via CLI:**
```bash
php tools/excel-to-csv-converter.php
```

## Data Transformations

### Category Mapping
Excel categories are mapped to platform standards:

| Excel Category | Platform Category |
|----------------|-------------------|
| Governance & Verwaltungen, Politik, öffentliche Unternehmen | Gov |
| Etablierte Unternehmen | Tech |
| Start-ups, Scale-ups & Katalysatoren | Startup |
| Start-ups & Scale-ups | Startup |
| (Other/Unknown) | Tech (default) |

### Status Mapping
Top 50 status normalization:

| Excel Value | CSV Output |
|-------------|------------|
| Ja, ja, Yes, yes, 1, true | Ja |
| (Any other value) | Nein |

### URL Processing
- Automatically adds `https://` if no protocol present
- Validates URL format
- Empty URLs handled gracefully

### Text Field Processing
- Preserves UTF-8 characters (ä, ö, ü, ß)
- Escapes quotes and commas for CSV
- Maintains line breaks in descriptions

## Output CSV Format

### Headers
```csv
ID,Name,Organisation,Position,LinkedIn-Link,Webseite,Article about coming of age,Description,Category,Status
```

### Field Mapping
| Excel Field | CSV Field | Notes |
|-------------|-----------|-------|
| ID | ID | Direct copy |
| Name | Name | Trimmed, escaped |
| Organisation | Organisation | Trimmed, escaped |
| Position | Position | Trimmed, escaped |
| Linkedin-Link | LinkedIn-Link | URL validated |
| Webseite | Webseite | URL validated |
| (not in Excel) | Article about coming of age | Empty field |
| Description | Description | Full text, escaped |
| Category | Category | Mapped to Gov/Tech/Startup |
| Top 50 | Status | Mapped to Ja/Nein |

## Import Process

After converting Excel to CSV:

1. **Navigate to WordPress Admin**
   - Go to: Mobility Trailblazers → Import/Export

2. **Choose Import Method:**
   
   **Option A: Quick Import**
   - Click "Import CSV" button
   - Select converted CSV file
   - Choose "Update existing candidates" if merging data
   - Review import results

   **Option B: Advanced Import with Dry Run**
   - Go to Import Profiles page
   - Upload CSV file
   - Run "Dry Run" first to preview
   - Review validation report
   - Click "Import" if satisfied

3. **Verify Import**
   - Check All Candidates page
   - Confirm category icons display correctly
   - Verify Top 50 checkmarks

## File Locations

```
mobility-trailblazers/
├── tools/
│   ├── excel-to-csv-converter.php     # PHP converter script
│   └── excel-to-csv-converter.html    # Browser converter (save artifact)
└── doc/
    ├── excel-import-guide.md          # This guide
    └── csv-import-guide.md            # General CSV import docs
```

## Troubleshooting

### Common Issues

**Excel file not reading:**
- Ensure sheet named "Kandidaten" exists
- Check data starts at row 7
- Verify .xlsx format (not .xls)

**Special characters corrupted:**
- Browser converter adds UTF-8 BOM automatically
- For manual conversion, save as UTF-8

**Categories not mapping:**
- Check exact spelling in Excel
- Unknown categories default to "Tech"

**Import failing:**
- Verify CSV headers match exactly
- Check file size < 10MB
- Ensure Name column populated

### Debug Logging
Check WordPress debug log for import details:
```
wp-content/debug.log
```

## Sample Data Structure

Excel (row 7+):
```
1 | Alexander Möller | Geschäftsführer | VDV | https://linkedin... | https://vdv.de | Gov... | Hans... | Description... | | Ja | ja
```

Converted CSV:
```csv
1,"Alexander Möller","VDV","Geschäftsführer",https://linkedin...,https://vdv.de,,"Description...",Gov,Ja
```

## Version History

- **v1.0.0** (2025-08-16): Initial Excel import functionality
  - Browser-based converter tool
  - PHP script for server-side conversion
  - Category and status mapping
  - UTF-8 support with special characters

---

*Last Updated: August 16, 2025*
