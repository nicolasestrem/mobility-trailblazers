# CSS v4 Framework Rollout Documentation

**Version**: 4.0.0  
**Date**: 2025-08-22  
**Author**: Mobility Trailblazers Development Team

## Overview

The CSS v4 framework is a complete reimplementation of the plugin's styling system, designed to:
- Load CSS only on plugin-specific routes (not site-wide)
- Work completely independent of Elementor
- Reduce CSS footprint from ~50KB to <5KB
- Use modern CSS custom properties for theming
- Provide easy rollback mechanism

## Architecture

### File Structure
```
assets/css/v4/
├── mt-tokens.css       # CSS custom properties (0.5KB)
├── mt-reset.css        # Scoped reset styles (0.5KB)
├── mt-base.css         # Core components (1.5KB)
├── mt-components.css   # Specific components (1.5KB)
└── mt-pages.css        # Page-specific styles (1KB)
```

### Key Components

1. **MT_Public_Assets Class** (`includes/public/class-mt-public-assets.php`)
   - Handles conditional CSS loading
   - Detects plugin routes
   - Manages v4/v3 switching
   - Provides color customization

2. **Route Detection**
   - Plugin pages: `/vote/`, `/jury-dashboard/`, `/rankings/`
   - Custom post types: `mt_candidate`, `mt_jury_member`
   - Shortcode detection in content
   - URL parameters: `?evaluate=`

3. **Template Wrappers**
   - All templates wrapped with `.mt-root` class
   - Provides CSS scope isolation
   - Prevents style conflicts with theme

## Implementation Status

### ✅ Completed
- [x] Git checkpoint tag created (`css-v4-prep`)
- [x] v4 CSS files created
- [x] MT_Public_Assets class implemented
- [x] Integration with main plugin
- [x] Shortcode renderer updated
- [x] Template wrappers added
- [x] Test script created
- [x] Documentation written

### 🔄 Testing Required
- [ ] Test on `/vote/` page
- [ ] Test on candidate archive
- [ ] Test on single candidate pages
- [ ] Test on jury dashboard
- [ ] Test mobile responsiveness
- [ ] Verify no CSS on non-plugin pages

## Rollback Mechanisms

### Method 1: Filter Toggle (Recommended)
Add to theme's `functions.php` or create a mu-plugin:

```php
// Disable v4 CSS and revert to v3
add_filter('mt_enable_css_v4', '__return_false');
```

### Method 2: Git Rollback
```bash
# Restore to checkpoint before v4 implementation
git checkout css-v4-prep

# Or revert specific commits
git revert HEAD~1
```

### Method 3: Manual File Restoration
1. Delete `assets/css/v4/` directory
2. Remove `includes/public/class-mt-public-assets.php`
3. Revert changes in `class-mt-plugin.php`
4. Revert changes in `class-mt-shortcode-renderer.php`
5. Remove `.mt-root` wrappers from templates

## Testing Checklist

### Pre-Deployment Testing
1. **CSS Loading**
   ```bash
   # Run test script
   php test-v4-css.php
   ```

2. **Visual Testing**
   - [ ] Vote page displays correctly
   - [ ] Candidate cards show properly
   - [ ] Evaluation forms styled correctly
   - [ ] Rankings table formatted properly
   - [ ] Mobile view works (480/768/1024px)

3. **Performance Testing**
   - [ ] Total CSS < 5KB
   - [ ] No CSS loaded on homepage
   - [ ] No CSS loaded on blog posts
   - [ ] CSS only on plugin pages

### Browser Testing
- [ ] Chrome 90+
- [ ] Firefox 88+
- [ ] Safari 14+
- [ ] Edge 90+
- [ ] Mobile Safari (iOS 14+)
- [ ] Chrome Mobile (Android 8+)

## Deployment Steps

### Staging Deployment
1. Backup current site
2. Deploy code to staging
3. Run test script
4. Visual QA on all pages
5. Performance testing
6. Mobile testing

### Production Deployment
1. Create production backup
2. Deploy during low-traffic period
3. Keep v3 files temporarily (don't delete yet)
4. Monitor for 48 hours
5. After confirmation, remove legacy files

### Post-Deployment
1. Monitor error logs
2. Check browser console for CSS errors
3. Verify mobile experience
4. Test evaluation submissions
5. Confirm no style regressions

## Troubleshooting

### Issue: Styles Not Loading
```php
// Check if v4 is enabled
$enabled = apply_filters('mt_enable_css_v4', true);
var_dump($enabled);

// Check if on plugin route
$assets = new MT_Public_Assets();
$is_route = $assets->is_mt_public_route();
var_dump($is_route);
```

### Issue: Broken Layout
1. Check browser console for 404 errors
2. Verify `.mt-root` wrapper present
3. Confirm v4 CSS files exist
4. Check for conflicting theme styles

### Issue: Wrong Colors
1. Check `mt_dashboard_settings` option
2. Verify dynamic tokens printing
3. Check for inline style overrides
4. Clear any caching plugins

## Migration from v3 to v4

### For Developers
1. Update any custom CSS targeting plugin elements
2. Use new CSS class names (`.mt-` prefix)
3. Test custom integrations
4. Update any JavaScript selectors

### For Site Admins
1. Clear all caches after deployment
2. Test custom color settings still work
3. Verify all shortcodes display correctly
4. Check mobile experience

## Legacy CSS Files to Remove (After Stable)

The following files can be removed once v4 is confirmed stable:

### v3 System (7 files)
- `assets/css/v3/` - Entire directory

### Legacy Main Files (30+ files)
- `frontend.css`, `frontend-new.css`
- `mt-candidate-grid.css`
- `mt-evaluation-forms.css`
- `mt-jury-dashboard-enhanced.css`
- `mt-hotfixes-consolidated.css`
- All other legacy CSS files

## Benefits of v4

1. **Performance**
   - 90% reduction in CSS size
   - CSS only loads where needed
   - Faster page load times
   - Better Core Web Vitals scores

2. **Maintainability**
   - Clean, organized structure
   - Token-based design system
   - No more hotfix accumulation
   - Easy to customize

3. **Compatibility**
   - No Elementor dependency
   - Works with any theme
   - Scoped styles prevent conflicts
   - Future-proof architecture

## Support

For issues or questions:
1. Check this documentation
2. Run the test script
3. Review error logs
4. Use rollback if needed
5. Report issues with details

## Version History

- **4.0.0** (2025-08-22): Initial v4 implementation
- **3.0.0** (2025-01-19): Previous v3 system (Elementor-scoped)
- **2.x**: Legacy accumulated CSS system