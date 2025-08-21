# CSS v3 Implementation Guide

**Date**: January 19, 2025  
**Version**: 2.5.26  
**Author**: Mobility Trailblazers - Nicolas Estrem

## Overview

Complete CSS redesign implementing a clean, modern design system scoped specifically to Elementor widgets. This v3 implementation replaces all legacy CSS with a token-based, maintainable architecture.

## Design Principles

### Visual Design
- **Canvas**: Beige background (#F8F0E3) throughout the site
- **Cards**: Clean white cards with soft beige borders
- **No Pills**: Removed all pill-style elements and teal outlines
- **Rounded Corners**: Consistent 16px border radius
- **Shadows**: Subtle shadows for depth (0 2px 10px rgba(0,0,0,.07))

### Technical Architecture
- **Scoped Styles**: All CSS scoped to Elementor widget wrappers
- **Token-Based**: Design tokens for colors, spacing, and effects
- **Mobile-First**: Responsive grid (1 column mobile, 3 columns desktop)
- **Performance**: Minimal CSS footprint, no legacy code

## File Structure

```
assets/css/v3/
├── mt-tokens.css           # Design tokens and CSS variables
├── mt-reset.css            # Base styles and typography
├── mt-widget-candidates-grid.css  # Candidates grid widget styles
├── mt-widget-jury-dashboard.css   # Jury dashboard widget styles
└── mt-compat.css           # Compatibility layer for beige background
```

## Design Tokens

### Color Palette
```css
--mt-bg-beige: #F8F0E3;      /* Main canvas background */
--mt-primary: #003C3D;        /* Primary brand color */
--mt-secondary: #004C5F;      /* Secondary brand color */
--mt-text: #302C37;           /* Body text color */
--mt-accent: #C1693C;         /* Accent color */
--mt-kupfer-soft: #BB6F52;    /* Soft copper accent */
--mt-card-bg: #FFFFFF;        /* Card background */
--mt-border-soft: #E8DCC9;    /* Soft border color */
```

### Spacing System
```css
--mt-space-2: 8px;
--mt-space-3: 12px;
--mt-space-4: 16px;
--mt-space-6: 24px;
--mt-space-8: 32px;
```

## Widget Scoping

### Candidates Grid Widget
- **Widget Name**: `mt_candidates_grid`
- **Wrapper Class**: `.elementor-widget-mt_candidates_grid`
- **Key Elements**:
  - `.mt-candidates-grid` - Grid container
  - `.mt-candidate-card` - Individual cards
  - `.mt-card__image` - Candidate photos (104x104px circles)
  - `.mt-card__title` - Candidate names
  - `.mt-card__role`, `.mt-card__org` - Role and organization

### Jury Dashboard Widget
- **Widget Name**: `mt_jury_dashboard`
- **Wrapper Class**: `.elementor-widget-mt_jury_dashboard`
- **Key Elements**:
  - `.mt-voting-card` - Voting interface cards
  - `.mt-vote-button` - Action buttons

## Elementor Controls Added

Both widgets now include advanced style controls:

1. **Card Gap** - Responsive slider (8-48px)
2. **Image Fit** - Cover/Contain/Fill options
3. **Image Position** - Custom positioning (default: 30% 50%)

These controls allow editors to fine-tune the appearance without breaking the design system.

## Implementation Details

### Enqueue System
Updated in `includes/public/renderers/class-mt-shortcode-renderer.php`:

```php
$base = MT_PLUGIN_URL . 'assets/css/v3/';
wp_enqueue_style('mt-v3-tokens', $base . 'mt-tokens.css', [], MT_VERSION);
wp_enqueue_style('mt-v3-reset', $base . 'mt-reset.css', ['mt-v3-tokens'], MT_VERSION);
wp_enqueue_style('mt-v3-grid', $base . 'mt-widget-candidates-grid.css', ['mt-v3-reset'], MT_VERSION);
wp_enqueue_style('mt-v3-jury', $base . 'mt-widget-jury-dashboard.css', ['mt-v3-grid'], MT_VERSION);
wp_enqueue_style('mt-v3-compat', $base . 'mt-compat.css', ['mt-v3-jury'], MT_VERSION);
```

### Responsive Breakpoints
- **Mobile**: Default, single column
- **Desktop**: 1024px+, three columns for grid

### Accessibility
- AA compliant text contrast
- Clear focus states
- Semantic HTML structure maintained

## Quality Assurance

### Stylelint Configuration
Created `.stylelintrc.json` for CSS linting:
- Enforces no `!important` declarations
- Requires CSS variables for colors
- No ID selectors allowed
- Standard CSS formatting rules

### Testing Checklist
- [x] Beige background displays correctly
- [x] White cards with soft borders
- [x] Three-column grid on desktop
- [x] Single column on mobile
- [x] No pills or teal outlines visible
- [x] Hover effects working
- [x] Images display correctly (circular with border)
- [x] Text passes AA contrast requirements

## Migration Notes

### Removed Files
The following legacy CSS files are no longer loaded:
- `assets/css/frontend.css` (replaced by v3 system)

### Backward Compatibility
The v3 system is completely independent and won't affect:
- Admin styles
- Single candidate pages (unless they use the widgets)
- Other plugin functionality

### Future Considerations
1. Consider migrating other widgets to v3 system
2. Add dark mode support using CSS variables
3. Create additional widget-specific style files as needed

## Troubleshooting

### Issue: Styles Not Loading
1. Clear WordPress cache
2. Clear browser cache
3. Check if plugin version updated (MT_VERSION constant)

### Issue: Wrong Colors Appearing
1. Check for inline styles overriding CSS
2. Verify no legacy CSS files are being loaded
3. Check Elementor widget settings for custom colors

### Issue: Grid Not Responsive
1. Verify viewport meta tag is present
2. Check for conflicting grid CSS from theme
3. Ensure media queries are not being overridden

## Browser Support
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Performance Impact
- **Total CSS Size**: ~3KB (minified)
- **Render Time**: No measurable impact
- **Paint Time**: Improved due to simpler selectors