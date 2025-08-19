# Mobility Trailblazers - Import Guide

## Quick Start

### Access the Import Page
1. Go to WordPress Admin
2. Navigate to **Mobility Trailblazers → Import Candidates**
3. Or directly visit: http://localhost:8080/wp-admin/admin.php?page=mt-candidate-importer

## Import Process

### Step 1: Dry Run (Recommended First)
1. Keep the default file paths:
   - **Excel:** `E:\OneDrive\CoWorkSpace\Tech Stack\Platform\plugin\mobility-trailblazers\.internal\Kandidatenliste Trailblazers 2025_08_18_List for Nicolas.xlsx`
   - **Photos:** `E:\OneDrive\CoWorkSpace\Tech Stack\Platform\plugin\mobility-trailblazers\.internal\Photos_candidates`

2. **✓ Check "Dry Run"** checkbox (should be checked by default)

3. **✓ Check "Also import candidate photos"** (recommended)

4. Click **"Import Candidates"** button

5. Review the results:
   - Should show **48 candidates** would be created
   - All **6 German sections** should be populated
   - **0 errors** expected

### Step 2: Actual Import
1. After reviewing dry run results
2. **✗ Uncheck "Dry Run"** checkbox
3. Keep other settings the same
4. Click **"Import Candidates"** button
5. Wait for import to complete

## Expected Results

### Successful Import Shows:
- **Created:** 48 candidates
- **Photos Attached:** 48-54 photos (some may not match)
- **Errors:** 0

### German Sections Imported:
Each candidate will have these sections properly stored:
- Überblick
- Mut & Pioniergeist
- Innovationsgrad
- Umsetzungskraft & Wirkung
- Relevanz für die Mobilitätswende
- Vorbildfunktion & Sichtbarkeit

## Verification

After import, verify by:
1. Go to **Posts → All Posts**
2. Filter by **"MT Candidates"** post type
3. Check that 48 candidates are listed
4. Click on any candidate to view their data

## Delete and Re-import

If you need to start over:
1. Scroll down to **"Delete All Candidates"** section
2. **✓ Check "Dry Run"** first to preview
3. **✗ Uncheck "Dry Run"** to actually delete
4. Click **"Delete All Candidates"** (will create backup first)
5. Then re-import as above

## Troubleshooting

### If Import Shows 0 Candidates:
- Check that Excel file exists at the specified path
- Verify file permissions

### If Photos Don't Attach:
- Photos only attach to existing candidates
- Run import first, then photos separately if needed

### If German Sections Are Missing:
- Check that Excel file has "Description" column
- Verify sections are formatted with headers like "Überblick:" etc.

## Direct Database Check

To verify data in database:
```sql
-- Check candidates table
SELECT COUNT(*) FROM wp_mt_candidates;

-- Check first candidate's sections
SELECT name, description_sections FROM wp_mt_candidates LIMIT 1;
```

---

**Support:** Check `/wp-content/debug.log` for any errors