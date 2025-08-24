# Technical Debt - Mobility Trailblazers

## CRITICAL: Remove !important CSS Declarations

**Priority: HIGH**  
**Date Added: 2025-08-24**  
**Affected Files:**
- `assets/css/frontend.css`
- `assets/css/mt-jury-dashboard-enhanced.css`
- `assets/css/jury-dashboard.css`
- `assets/min/css/frontend.min.css`
- Various other CSS files

### Problem
The codebase has excessive use of `!important` declarations, particularly:
```css
body .mt-jury-dashboard .mt-candidate-card {
    display: block !important;
}
```

This causes:
1. jQuery `.show()` and `.hide()` methods don't work
2. Requires hotfix CSS with more `!important` declarations
3. Creates maintenance nightmare
4. Makes debugging extremely difficult
5. Breaks standard JavaScript DOM manipulation

### Current Hotfix (TEMPORARY)
Created `mt-jury-filter-hotfix.css` as a band-aid solution. This should be removed once the root cause is fixed.

### Proper Solution
1. **Refactor CSS specificity** - Use proper CSS architecture instead of `!important`
2. **Use CSS custom properties** for dynamic states
3. **Implement BEM methodology** for better class naming
4. **Use data attributes** for state management
5. **Remove all `!important` declarations** except where absolutely necessary (max 1-2 in entire codebase)

### Recommended Approach
```css
/* Instead of this: */
.mt-candidate-card {
    display: block !important;
}

/* Use this: */
.mt-jury-dashboard .mt-candidates-list .mt-candidate-card {
    display: block;
}

.mt-jury-dashboard .mt-candidates-list .mt-candidate-card[data-hidden="true"] {
    display: none;
}
```

### Files to Refactor
1. Start with `frontend.css` - has the most `!important` declarations
2. Then `jury-dashboard.css`
3. Update all minified versions
4. Remove `mt-jury-filter-hotfix.css` once complete

### Estimated Effort
- 4-6 hours for complete refactoring
- Testing required on all pages
- Risk: May affect other parts of the site

### Notes
- This is causing active bugs in production
- Blocks proper JavaScript functionality
- Must be fixed before adding new features
- Consider using PostCSS or Sass for better CSS organization

## Other Technical Debt Items

### 1. Duplicate Jury Member Posts
- Multiple jury member posts can exist for same user
- Should have unique constraint
- Affects assignments and evaluations

### 2. Missing Database Indexes
- No composite index on (jury_member_id, candidate_id) in assignments table
- Slow queries for ranking calculations

### 3. Hardcoded German Text
- Still some hardcoded German text in templates
- Should use proper internationalization

### 4. No Error Boundaries
- JavaScript errors can break entire dashboard
- Need proper error handling and fallbacks