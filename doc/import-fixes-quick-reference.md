# Import System Fixes - Quick Reference

## Issue: "Wrong format" error when importing CSV

### Problem
The import system was rejecting valid CSV files with "wrong format" error due to:
1. Strict MIME type validation
2. Case-sensitive header detection
3. Poor delimiter detection
4. No BOM handling for Excel files

### Solution Applied (v2.2.2)

#### 1. File Type Validation Fixed
```php
// Before: Strict validation that failed
$allowed_mimes = ['text/csv', 'text/plain'];

// After: Accepts more types
$allowed_mimes = ['text/csv', 'text/plain', 'application/csv', 
                  'application/vnd.ms-excel', 'application/octet-stream'];
```

#### 2. Header Detection Improved
```php
// Before: Case-sensitive, exact match
if (in_array('ID', $data) || in_array('Name', $data))

// After: Case-insensitive, flexible
$cell_lower = strtolower(trim($cell));
if ($cell_lower === 'id' || strpos($cell_lower, 'name') !== false)
```

#### 3. Delimiter Detection Enhanced
- Now checks multiple lines (not just first)
- Considers consistency across lines
- Supports: comma, semicolon, tab, pipe

#### 4. UTF-8 BOM Handling Added
```php
$bom = file_get_contents($file_path, false, null, 0, 3);
if ($bom === "\xEF\xBB\xBF") {
    fread($handle, 3); // Skip BOM
}
```

## Issue: Menu buttons not working

### Problem
"Migrate Profiles", "Test Profile System", and "Generate Samples" menu items did nothing when clicked.

### Solution Applied
Created missing debug files:
- `debug/migrate-candidate-profiles.php`
- `debug/test-profile-system.php`
- `debug/generate-sample-profiles.php`

## Testing the Fixes

### 1. Test Import
```csv
ID,Name,Position,Organisation,Category
1,Test User,CEO,Test Company,Start-ups
```
Save as UTF-8 CSV and import.

### 2. Verify Headers
The import will show detected headers:
```
File info: MIME type: text/csv, Delimiter detected: ,
Found 5 columns in header row: ID, Name, Position, Organisation, Category
```

### 3. Use Debug Tools
1. **Migrate Profiles**: Updates existing profiles to new format
2. **Test Profile System**: Verifies meta fields are correct
3. **Generate Samples**: Creates test data

## Common Import Issues & Solutions

| Issue | Cause | Solution |
|-------|-------|----------|
| No data imported | Headers not recognized | Check header names match documentation |
| Special characters broken | Encoding issue | Save as UTF-8 |
| Only some fields imported | Delimiter mismatch | Use comma or semicolon consistently |
| "Name required" error | Empty name column | Ensure Name column has values |

## Supported CSV Format

### Required Headers
- `Name` (or any variation containing "name")

### Optional Headers
- `ID`, `Position`, `Organisation`, `Category`
- `Top 50` (Yes/No or Ja/Nein)
- `Nominator`, `LinkedIn-Link`, `Webseite`
- `Foto` (Yes/No or URL)
- `Description` (full text)

### Example CSV
```csv
ID,Name,Position,Organisation,Category,Top 50,LinkedIn-Link
1,Dr. Maria Schmidt,CEO,Tech GmbH,Start-ups,Ja,https://linkedin.com/in/mschmidt
2,Hans Weber,CTO,Innovation AG,Etablierte Unternehmen,Nein,https://linkedin.com/in/hweber
```

## Import Options

| Option | Default | Purpose |
|--------|---------|---------|
| Update existing | Yes | Update if candidate exists |
| Skip empty fields | No | Don't overwrite with empty |
| Validate URLs | Yes | Check URL format |
| Import photos | Yes | Download/attach photos |
| Dry run | No | Preview without saving |

## Quick Troubleshooting

### Import shows "0 imported"
1. Check headers are in first row
2. Verify Name column has values
3. Try dry run to see issues

### Special characters (ä, ö, ü) broken
1. Save CSV as UTF-8
2. Don't use Excel's default CSV export
3. Use a text editor to save as UTF-8

### Import is slow
1. Split large files (100+ rows)
2. Disable photo import for speed
3. Use dry run first to validate

## File Locations

### Import System
- Main importer: `includes/admin/class-mt-enhanced-profile-importer.php`
- Admin page: `templates/admin/import-profiles.php`
- Basic import: `templates/admin/import-export.php`

### Debug Tools
- `debug/migrate-candidate-profiles.php`
- `debug/test-profile-system.php`
- `debug/generate-sample-profiles.php`

### Documentation
- Complete guide: `doc/import-system-complete-guide.md`
- This reference: `doc/import-fixes-quick-reference.md`
- Changelog: `doc/mt-changelog-updated.md`

## Support

For issues not covered here:
1. Check the [Complete Import Guide](import-system-complete-guide.md)
2. Use Test Profile System to debug
3. Check Error Monitor for specific errors
4. Review the import results messages
