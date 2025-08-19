# CSS Unit Spacing Fix - August 19, 2025

## Overview
Fixed invalid CSS unit spacing issues across the codebase that were causing browser rendering problems on single candidate pages.

## Problem
CSS files contained invalid spacing between numeric values and their units (e.g., `300 px` instead of `300px`), which browsers ignore, causing layout issues.

## Solution Implemented

### 1. CSS Unit Fixes
- **Total Files Fixed:** 12 CSS files
- **Total Issues Resolved:** 
  - 34 minmax() function fixes
  - 82 linear-gradient() fixes
  - General unit spacing fixes for px, rem, em, fr, deg, s, ms, %, vw, vh

### 2. Files Modified

#### CSS Files with Unit Fixes:
- `admin.css` - 14 fixes (7 minmax, 7 gradient)
- `enhanced-candidate-profile.css` - 7 gradient fixes
- `frontend.css` - 30 fixes (11 minmax, 19 gradient)
- `jury-dashboard.css` - 10 fixes (4 minmax, 6 gradient)
- `mt-jury-dashboard-enhanced.css` - 16 fixes (5 minmax, 11 gradient)
- Plus 7 additional CSS files with various fixes

### 3. Hotfix Stylesheet Added
Created `assets/css/candidate-single-hotfix.css` with targeted fixes for:
- Hero section height limitation (max 400px)
- Profile header spacing adjustments
- Photo frame dimensions (220x220px)
- Content section positioning and styling
- Criterion content text formatting with proper line breaks
- Hero pattern overflow handling

### 4. Enqueue Implementation
Modified `includes/core/class-mt-template-loader.php`:
- Added hotfix stylesheet enqueue in `enqueue_enhanced_styles()` method
- Lines 103-111 of the file
- Only loads on single candidate pages (`is_singular('mt_candidate')`)
- Version tagged as '2025-08-19' for cache busting

## Technical Details

### Regex Patterns Used
```python
# Unit spacing detection and removal
unit_re = r"(?<=\d)\s+(?=(px|rem|em|fr|deg|s|ms|%|vw|vh))"

# minmax() function normalization
minmax_re = r"minmax\(\s*([0-9.]+)\s*px\s*,\s*([0-9.]+)\s*fr\s*\)"

# linear-gradient() normalization  
grad_re = r"linear-gradient\(\s*([0-9.]+)\s*deg"
```

### Verification
Post-fix scan confirmed zero remaining unit spacing issues in all CSS files.

## Impact
- Restored proper CSS rendering on single candidate pages
- Fixed layout issues without removing features
- Maintained all existing functionality
- No template modifications required

## Commit Reference
```
Commit: d28ba3c
Message: fix css candidate single remove spaces before units add targeted hotfix stylesheet and enqueue on single mt_candidate
```