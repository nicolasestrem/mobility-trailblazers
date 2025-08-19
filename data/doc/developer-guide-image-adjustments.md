# Developer Guide: Candidate Image Adjustments

## Overview
This guide explains how to fix candidate profile images where faces are not properly visible due to CSS cropping.

## Problem
The default `object-fit: cover` CSS property with centered positioning can crop candidate photos in a way that cuts off faces, especially when the person is positioned in the upper or lower portion of the original image.

## Solution
We've created a dedicated CSS file (`candidate-image-adjustments.css`) that allows for specific positioning adjustments using the `object-position` property.

## File Structure

### CSS File Location
- `assets/css/candidate-image-adjustments.css`

### Modified Files
- `includes/core/class-mt-plugin.php` - Added stylesheet enqueue

## How to Add New Candidate Adjustments

### 1. Find the Candidate's Post ID
```sql
SELECT ID, post_title FROM wp_posts 
WHERE post_type = 'mt_candidate' 
AND post_title LIKE '%Candidate Name%';
```

### 2. Test Object Position Values
Use browser DevTools to test different `object-position` values:
- `center 20%` - Focus on upper portion (faces near top)
- `center 30%` - Slightly upper focus (default in our fix)
- `center 40%` - Upper-middle focus
- `center 50%` - Center (original default)
- `center 60%` - Lower-middle focus
- `center 70%` - Focus on lower portion

### 3. Add CSS Rule
Add a new rule in `candidate-image-adjustments.css`:

```css
/* Candidate Name - Brief description of issue */
body.postid-XXXX .mt-candidate-hero-photo,
body[class*="candidate-slug"] .mt-candidate-hero-photo,
.candidate-candidate-slug .mt-candidate-hero-photo {
    object-position: center YY% !important;
}

/* Grid view adjustment */
.mt-candidate-grid-item a[href*="candidate-slug"] .mt-candidate-photo,
.mt-candidate-grid-item a[href*="candidate-slug"] .mt-candidate-image img {
    object-position: center YY% !important;
}
```

Replace:
- `XXXX` with the post ID
- `candidate-slug` with the URL slug
- `YY` with the percentage value that works best
- `Candidate Name` with the actual name

## Example: Friedrich Dräxlmaier Fix

```css
/* Friedrich Dräxlmaier - Face visible in upper portion */
body.postid-4627 .mt-candidate-hero-photo,
body[class*="friedrich-draexlmaier"] .mt-candidate-hero-photo,
.candidate-friedrich-draexlmaier .mt-candidate-hero-photo {
    object-position: center 25% !important;
}
```

## Testing Process

1. **Local Testing**
   - Add the CSS rule
   - Clear WordPress cache: `wp cache flush`
   - Test on candidate profile page
   - Test on candidate grid/archive page
   - Test responsive views (mobile, tablet)

2. **Staging Verification**
   - Deploy to staging environment
   - Verify the fix works across different browsers
   - Check that it doesn't affect other candidates negatively

3. **Production Deployment**
   - Upload modified files via FTP
   - Clear production cache if needed

## Responsive Considerations

The file includes responsive adjustments:
```css
@media (max-width: 768px) {
    /* Adjust positioning for smaller images on mobile */
    body.postid-XXXX .mt-candidate-hero-photo {
        object-position: center (YY+5)% !important;
    }
}
```

Mobile images are smaller, so you may need slightly different positioning.

## Default Improvements

We've also improved the default positioning for ALL candidates:
```css
.mt-candidate-hero-photo,
.mt-candidate-photo,
.mt-candidate-image img {
    object-position: center 30% !important;
}
```

This provides better face visibility by default, as most professional headshots have faces in the upper portion of the image.

## Troubleshooting

### Image Still Cropped
- Check if the CSS file is properly loaded (browser DevTools > Network)
- Verify the selector is specific enough (use more specific selectors if needed)
- Clear all caches (WordPress, browser, CDN if applicable)

### Wrong Part of Image Showing
- Adjust the percentage value in smaller increments (5% at a time)
- Consider using horizontal adjustments too: `object-position: 45% 30%`

### Changes Not Appearing
1. Clear WordPress cache
2. Hard refresh browser (Ctrl+Shift+R)
3. Check if CSS file is enqueued properly in `class-mt-plugin.php`

## Related Files
- `assets/css/enhanced-candidate-profile.css` - Main profile styles
- `assets/css/mt-candidate-grid.css` - Grid view styles
- `templates/frontend/candidates-grid.php` - Grid template

## Version History
- v1.0.0 (2025-08-18) - Initial implementation with Friedrich Dräxlmaier fix