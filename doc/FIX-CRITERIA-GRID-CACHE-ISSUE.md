# Fix: Criteria Grid Cache Issue on Evaluation Pages

## Issue Description
The criteria grid content on individual evaluation pages (`/jury-dashboard/?evaluate=XXX`) was not updating after candidates were edited using the "Edit Content" button. This was caused by WordPress post meta caching that wasn't being cleared when candidate data was updated.

## Root Cause
1. WordPress caches post meta data for performance
2. When a candidate is edited (especially via Elementor), the post meta cache wasn't being invalidated
3. The evaluation form template pulls criteria content from post meta fields (`_mt_criterion_courage`, etc.)
4. Without cache clearing, the old cached values were displayed even after editing

## Solution Implemented

### 1. Server-Side Cache Clearing (PHP)
**File:** `includes/core/class-mt-performance-optimizer.php`

Added comprehensive cache clearing mechanisms:
- Clear post meta cache when candidates are saved
- Handle post meta updates directly
- Clear cache after Elementor saves
- Flush WordPress object cache

Key additions:
```php
// Clear post meta cache for specific post
wp_cache_delete($post_id, 'post_meta');
clean_post_cache($post_id);

// Added hooks for meta updates
add_action('updated_post_meta', [__CLASS__, 'clear_cache_on_meta_update'], 10, 4);
add_action('added_post_meta', [__CLASS__, 'clear_cache_on_meta_update'], 10, 4);

// Handle Elementor saves
add_action('elementor/editor/after_save', [__CLASS__, 'clear_cache_after_elementor_save'], 10, 2);
```

### 2. Client-Side Refresh Mechanism (JavaScript)
**File:** `assets/js/frontend.js`

Added automatic page refresh when returning from editing:
- Detects if user is coming back from WordPress editor or Elementor
- Forces page refresh to ensure fresh content
- Uses sessionStorage to prevent infinite refresh loops

```javascript
// Force refresh when returning from editing
if (window.location.href.includes('evaluate=')) {
    var referrer = document.referrer;
    if (referrer && (referrer.includes('action=edit') || referrer.includes('action=elementor'))) {
        if (!sessionStorage.getItem('mt_evaluation_refreshed')) {
            sessionStorage.setItem('mt_evaluation_refreshed', 'true');
            window.location.reload(true);
        }
    }
}
```

## Files Modified
1. `includes/core/class-mt-performance-optimizer.php` - Added cache clearing methods
2. `assets/js/frontend.js` - Added automatic refresh mechanism
3. `assets/min/js/frontend.min.js` - Minified version (auto-generated)

## Testing Instructions
1. Navigate to jury dashboard evaluation page: `/jury-dashboard/?evaluate=XXXX`
2. Note the content in "Bewertungskriterien Details" section
3. Click "Edit Content" button to edit the candidate
4. Modify one of the criteria fields (e.g., "Mut & Pioniergeist")
5. Save the changes
6. Return to the evaluation page
7. Verify the criteria grid shows the updated content

## Deployment Notes
- Run `.\scripts\minify-assets.ps1` to minify JavaScript files
- Clear WordPress cache after deployment: `wp cache flush`
- Test on staging before production deployment

## Date Fixed
January 20, 2025

## Related Issues
- Urgent fix for criteria grid not updating after candidate edits
- Affects jury evaluation process