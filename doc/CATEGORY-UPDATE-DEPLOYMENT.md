# Category Update Deployment Guide

## Overview
This guide provides instructions for updating candidate categories in the Mobility Trailblazers plugin database. The update assigns candidates to one of three official categories based on the Excel source of truth.

## Valid Categories
1. **Etablierte Unternehmen** (15 candidates)
2. **Governance & Verwaltungen, Politik, öffentliche Unternehmen** (11 candidates)
3. **Start-ups, Scale-ups & Katalysatoren** (22 candidates)

## Testing on Staging (COMPLETED ✅)

The category update has been successfully tested on the staging environment (localhost:8080) with the following results:
- ✅ All 48 candidates updated with correct categories
- ✅ Encoding issue fixed (öffentliche properly displayed)
- ✅ Frontend display working correctly
- ✅ No impact on existing evaluations or assignments

## Production Deployment via phpMyAdmin

### Prerequisites
- Access to phpMyAdmin for production database
- Database credentials: 
  - Host: `j3a4.your-database.de`
  - Database: `wp_mobil_db1`
  - User: `wp_mobil_1`

### Step 1: Backup Current Data
First, create a backup of existing category data:

```sql
-- Create backup table with timestamp
CREATE TABLE wp_postmeta_category_backup_20250821 AS 
SELECT * FROM wp_postmeta 
WHERE meta_key = '_mt_category_type';

-- Verify backup
SELECT COUNT(*) FROM wp_postmeta_category_backup_20250821;
```

### Step 2: Apply Category Updates

The SQL file `sql/production-category-update.sql` contains all necessary UPDATE statements. The file uses INSERT ... ON DUPLICATE KEY UPDATE syntax for safety.

Execute the SQL file in phpMyAdmin or run the statements manually. Here's the pattern used:

```sql
-- Example for one candidate
INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
SELECT ID, '_mt_category_type', 'Etablierte Unternehmen'
FROM wp_posts
WHERE post_title = 'André Schwämmlein' 
AND post_type = 'mt_candidate'
ON DUPLICATE KEY UPDATE meta_value = 'Etablierte Unternehmen';
```

### Step 3: Verify Updates

After running the updates, verify the results:

```sql
-- Check category distribution
SELECT meta_value as category, COUNT(*) as count
FROM wp_postmeta
WHERE meta_key = '_mt_category_type'
AND meta_value IS NOT NULL
GROUP BY meta_value
ORDER BY count DESC;

-- Expected results:
-- Start-ups, Scale-ups & Katalysatoren: 22
-- Etablierte Unternehmen: 15
-- Governance & Verwaltungen, Politik, öffentliche Unternehmen: 11
```

### Step 4: Clear WordPress Cache

After the database updates, clear the WordPress cache:

1. Via WP-CLI (if available):
   ```bash
   wp cache flush
   ```

2. Or via WordPress admin:
   - Go to WordPress Admin
   - Navigate to Tools → Clear Cache (if cache plugin installed)
   - Or deactivate/reactivate cache plugin

### Step 5: Verify Frontend Display

1. Visit a candidate profile page on production
2. Check that the category is displayed in the "Quick Facts" section
3. Verify the category text is properly formatted (check for "öffentliche" not "ööffentliche")

## Rollback Procedure (If Needed)

If you need to rollback the changes:

```sql
-- Option 1: Restore from backup
DELETE FROM wp_postmeta 
WHERE meta_key = '_mt_category_type';

INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
SELECT post_id, meta_key, meta_value 
FROM wp_postmeta_category_backup_20250821;

-- Option 2: Remove all categories
DELETE FROM wp_postmeta 
WHERE meta_key = '_mt_category_type';
```

## Important Notes

1. **No Impact on Core Functionality**: Categories are display-only metadata and don't affect:
   - Jury evaluations
   - Candidate assignments
   - Voting functionality
   - Award calculations

2. **Template Files Updated**: The following template files have been modified to display categories from the `_mt_category_type` meta field:
   - `templates/frontend/single/single-mt_candidate-enhanced-v2.php`
   - `templates/frontend/single/single-mt_candidate-enhanced.php`

3. **Character Encoding**: The correct spelling is "öffentliche" (with one ö). If you see "ööffentliche", run the encoding fix script.

## Troubleshooting

### Categories Not Displaying
- Check that `_mt_category_type` meta field exists for candidates
- Verify template files are using the updated code
- Clear all caches (WordPress, browser, CDN)

### Encoding Issues
If you see "ööffentliche" instead of "öffentliche":
```sql
UPDATE wp_postmeta 
SET meta_value = 'Governance & Verwaltungen, Politik, öffentliche Unternehmen'
WHERE meta_key = '_mt_category_type' 
AND meta_value = 'Governance & Verwaltungen, Politik, ööffentliche Unternehmen';
```

### Missing Candidates
Some candidates might not have categories if they:
- Were added after the Excel export
- Have different names in the database vs Excel
- Are test entries (like "Test3")

## Support

For issues or questions:
1. Check the Debug Center in WordPress Admin
2. Review error logs at `wp-content/debug.log`
3. Verify database queries in phpMyAdmin

## Files Reference

- **Update Script**: `scripts/update-categories-standalone.php`
- **SQL Export**: `sql/production-category-update.sql`
- **Source Data**: `temp_candidates_categories.csv`
- **Original Excel**: `Kandidatenliste Trailblazers 2025_08_20_List for Nicolas.xlsx`