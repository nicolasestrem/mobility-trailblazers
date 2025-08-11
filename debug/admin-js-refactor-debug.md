# Admin JS Refactoring - Quick Debug Plan
**Time Required:** 10 minutes  
**Date:** August 11, 2025

## Pre-Test Checklist (2 minutes)
- [ ] Clear browser cache (Ctrl+Shift+Delete)
- [ ] Open browser console (F12)
- [ ] Disable any browser extensions that might interfere
- [ ] Have WordPress admin credentials ready

## Test Sequence

### 1. General Admin Pages Test (2 minutes)
**Navigate to:** Dashboard → Settings → Mobility Trailblazers

**Check:**
- [ ] Console shows: "Admin JS loading..."
- [ ] Console shows: "Document ready - initializing general admin functions"
- [ ] Console shows: "Not on assignment page, skipping assignment-specific modules"
- [ ] Tabs work when clicked
- [ ] Tooltips appear on hover (if any exist)
- [ ] No JavaScript errors in console

### 2. Assignment Management Page Test (3 minutes)
**Navigate to:** Mobility Trailblazers → Assignment Management

**Check:**
- [ ] Console shows: "Assignment Management page detected, initializing assignment modules..."
- [ ] Console shows: "MTAssignmentManager initializing..."
- [ ] Console shows button detection (autoAssign, manualAssign, clearAll, export counts)
- [ ] Auto-Assign button has red border (debug styling - temporary)
- [ ] Click "Auto-Assign" button - modal appears
- [ ] Click "Manual Assign" button - modal appears
- [ ] Close modals with X button - they close properly
- [ ] Click outside modal - it closes

### 3. Assignment Actions Test (2 minutes)
**On Assignment Management page:**

**Quick Actions:**
- [ ] Search box - type a jury name, cards filter in real-time
- [ ] Remove assignment button (if any exist) - shows confirmation
- [ ] Export button - triggers download dialog
- [ ] Clear All button - shows double confirmation (cancel at second prompt)

**Auto-Assignment Modal:**
- [ ] Open Auto-Assign modal
- [ ] Check console for: "showAutoAssignModal called"
- [ ] Select "Balanced" method
- [ ] Set 3 candidates per jury
- [ ] Submit (can cancel if you don't want to create assignments)
- [ ] Check console for AJAX logs

### 4. Error Scenarios Test (1 minute)
**Check these don't break the page:**
- [ ] Navigate to a different admin page and back
- [ ] Refresh the Assignment Management page (F5)
- [ ] Open browser console - no uncaught errors
- [ ] Check Network tab - all JS files load with 200 status

## Debug Commands
If something doesn't work, run these in console:

```javascript
// Check if jQuery loaded
console.log('jQuery version:', jQuery.fn.jquery);

// Check if mt_admin object exists
console.log('mt_admin:', window.mt_admin);

// Check if modules loaded
console.log('MTAssignmentManager:', typeof MTAssignmentManager);

// Force initialize assignment manager (if not auto-loaded)
MTAssignmentManager.init();

// Check for assignment page elements
console.log('Auto-assign button:', $('#mt-auto-assign-btn').length);
console.log('Assignment table:', $('.mt-assignments-table').length);
```

## Common Issues & Quick Fixes

| Issue | Likely Cause | Fix |
|-------|-------------|-----|
| Modules not loading | Page detection failing | Check body class and URL |
| Modals not opening | Event binding issue | Check console for init messages |
| AJAX errors | Nonce expired | Refresh page and retry |
| Buttons not working | jQuery conflict | Check for other JS errors |
| Red border stays on button | Debug code left in | Normal - temporary debug indicator |

## Success Indicators
✅ All general utilities work on every admin page  
✅ Assignment modules only load on Assignment Management page  
✅ No console errors  
✅ All modals open and close properly  
✅ AJAX operations show proper loading states  
✅ Console shows clear initialization flow

## Post-Test Cleanup
- [ ] Remove red border debug code from line 372 in admin.js (optional)
- [ ] Clear browser console
- [ ] Note any issues found for follow-up

---
**If all tests pass:** System is working correctly!  
**If issues found:** Check console logs and use debug commands above.