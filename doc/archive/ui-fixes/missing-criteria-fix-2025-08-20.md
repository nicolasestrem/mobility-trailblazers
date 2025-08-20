# Fix: Missing Evaluation Criteria for 11 Candidates

## Issue Description
**Date Identified:** August 20, 2025
**Version:** 2.5.37
**Severity:** High - Affecting jury evaluation process

11 out of 48 candidates (23%) were missing the 4th and 5th evaluation criteria (`_mt_criterion_relevance` and `_mt_criterion_visibility`) in the database, causing incomplete display on evaluation pages.

## Root Cause Analysis

### 1. Import Script Meta Key Mismatch
The import script (`scripts/import-new-candidates.php`) was using incorrect meta key names:
- Used: `_mt_evaluation_relevance` and `_mt_evaluation_visibility`
- Required: `_mt_criterion_relevance` and `_mt_criterion_visibility`

### 2. Data Parsing Issues
The regex patterns in the import script failed to capture the last two criteria for some candidates due to:
- Format variations in the source Excel file
- Missing clear end markers for the visibility criterion
- Incomplete data in the source file for some candidates

## Affected Candidates
1. Dr. Corsin Sulser (ID: 4846)
2. Friedrich Dräxlmaier (ID: 4851)
3. Karel Dijkman (ID: 4856)
4. Léa Miggiano (ID: 4860)
5. Michael Klasa (ID: 4865)
6. Nic Knapp (ID: 4866)
7. Oliver Blume (ID: 4868)
8. Oliver May-Beckmann (ID: 4869)
9. Roy Uhlmann (ID: 4873)
10. Wen Han (ID: 4879)
11. Xanthi Doubara (ID: 4882)

## Solution Implemented

### 1. Fixed Meta Key Names in Import Script
**File:** `scripts/import-new-candidates.php`
- Lines 228-233: Changed from `_mt_evaluation_*` to `_mt_criterion_*`

### 2. Enhanced Import Script Validation
**File:** `scripts/import-new-candidates.php`
- Lines 91-117: Improved regex patterns for better format handling
- Added fallback pattern for simpler visibility format
- Added validation logging for missing criteria

### 3. Corrected Existing Data
- Renamed existing misnamed meta keys in database
- Added missing criteria data for all 11 affected candidates using WP-CLI

### 4. Data Addition Commands
```bash
# Example for Xanthi Doubara
docker exec mobility_wpcli_dev wp post meta add 4882 "_mt_criterion_relevance" "Doubara beweist, wie On-Demand-Mobilität den ÖPNV ergänzen und Mobilitätslücken schließen kann."
docker exec mobility_wpcli_dev wp post meta add 4882 "_mt_criterion_visibility" "Als Pionierin im Bereich autonomer und geteilter Mobilität ist sie sichtbare Vorreiterin der Verkehrswende."
```

## Files Modified
1. `scripts/import-new-candidates.php` - Fixed meta key names and improved validation
2. `scripts/fix-missing-evaluation-criteria.php` - Created script for batch fixes
3. `scripts/add-missing-criteria.sh` - Shell script for adding missing data

## Verification
After the fix:
- All 48 candidates now have complete evaluation criteria
- All 5 criteria are displayed correctly on evaluation pages
- The evaluation form shows all scoring categories

## Prevention Measures

### 1. Import Validation
- Added warning logs when criteria are missing during import
- Script now validates all 5 criteria are present before saving

### 2. Data Integrity Checks
- Regular database queries to check for missing criteria
- Admin dashboard widget can be added to monitor data completeness

### 3. Testing Checklist
Before importing new candidates:
1. Verify source Excel has all 5 criteria for each candidate
2. Run import with `--dry-run` flag first
3. Check logs for missing criteria warnings
4. Verify all candidates display 5 criteria after import

## Related Issues
- Initial cache issue was fixed in commit `aacd14d` (January 20, 2025)
- This fix addresses the underlying data issue

## Testing Instructions
1. Navigate to `/jury-dashboard/`
2. Click on any candidate's evaluation link
3. Verify "Bewertungskriterien Details" section shows 5 criteria:
   - Mut & Pioniergeist
   - Innovationsgrad
   - Umsetzungskraft & Wirkung
   - Relevanz für die Mobilitätswende
   - Vorbildfunktion & Sichtbarkeit
4. Verify the evaluation form shows all 5 scoring categories

## Deployment Notes
1. Run the fix script on production: `php scripts/fix-missing-evaluation-criteria.php`
2. Clear WordPress cache: `wp cache flush`
3. Test evaluation pages for all previously affected candidates
4. Monitor error logs for any issues

## Source of Truth
The master data for candidates is stored in:
`E:\OneDrive\CoWorkSpace\Kandidaten\Kandidatenliste Trailblazers 2025_08_18_List for Nicolas.xlsx`

All evaluation criteria text should match this source file exactly.