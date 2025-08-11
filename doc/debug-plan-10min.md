# 10-Minute Debug Plan for Evaluations Page Refactoring

## Quick Test Checklist (10 minutes)

### 1. Initial Page Load (2 minutes)
- [ ] Navigate to WordPress Admin > Mobility Trailblazers > Evaluations
- [ ] Open browser console (F12)
- [ ] Check for JavaScript errors in console
- [ ] Verify you see: `"MTEvaluationManager initializing..."` in console
- [ ] Verify you see: `"MTEvaluationManager initialized"` in console

### 2. Checkbox Functionality (2 minutes)
- [ ] Click "Select All" checkbox at top of table
  - Verify all evaluation checkboxes get checked
- [ ] Uncheck "Select All"
  - Verify all checkboxes get unchecked
- [ ] Check 2-3 individual evaluation checkboxes
  - Verify "Select All" remains unchecked
- [ ] Check remaining checkboxes manually
  - Verify "Select All" auto-checks when all are selected

### 3. View Details Button (1 minute)
- [ ] Click "View Details" button on any evaluation
- [ ] Verify alert appears with evaluation ID (temporary implementation)
- [ ] Close alert

### 4. Bulk Actions (3 minutes)
- [ ] Select 2-3 evaluations with checkboxes
- [ ] Choose "Approve" from bulk actions dropdown
- [ ] Click "Apply"
- [ ] Verify confirmation dialog appears
- [ ] Cancel the action
- [ ] Try again with "Delete" action
- [ ] Verify different confirmation message
- [ ] Check console for any errors

### 5. Quick Validation (2 minutes)
- [ ] Refresh the page (F5)
- [ ] Check console for initialization messages again
- [ ] Try one more bulk action to ensure persistence
- [ ] Verify no functionality was lost

## If Issues Found:

### Console Errors:
1. Check if `mt_admin` object exists: Type `mt_admin` in console
2. Check if jQuery loaded: Type `jQuery` in console
3. Look for 404 errors loading admin.js file

### Initialization Not Working:
1. Check page detection: Run in console:
   ```javascript
   $('.wrap h1:contains("Evaluations")').length
   $('.wp-list-table').length
   $('input[name="evaluation[]"]').length
   ```
   All should return numbers > 0

### Checkboxes Not Working:
1. Verify selectors in console:
   ```javascript
   $('#cb-select-all-1').length
   $('input[name="evaluation[]"]').length
   ```

### Quick Fixes:
- Hard refresh: Ctrl+Shift+R (clear cache)
- Check admin.js is loaded: View page source, search for "admin.js"
- Verify no PHP errors: Check debug.log in wp-content

## Success Indicators:
✅ No red errors in console
✅ Initialization messages appear
✅ All checkboxes work
✅ Bulk actions show confirmation
✅ View Details shows alert