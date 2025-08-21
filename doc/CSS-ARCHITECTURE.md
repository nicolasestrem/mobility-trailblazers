# CSS Architecture Documentation

## Version 2.5.38 - Unified Container System

### Overview
This document outlines the CSS architecture improvements implemented in version 2.5.38, focusing on the unified container system that ensures consistent alignment and responsiveness across all dashboard widgets.

## Unified Container System

### Core Principle
All dashboard widgets and major UI elements now follow a consistent 1200px max-width container pattern, ensuring proper alignment and centering across different screen sizes.

### Implementation

#### Base Container Class
```css
.mt-jury-dashboard__container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    box-sizing: border-box;
}
```

#### Applied Elements
The unified container system has been applied to the following elements:

1. **Dashboard Header** (`.mt-dashboard-header`)
   - Centered with 1200px max-width
   - Consistent padding: 30px 20px
   - Removed negative margins that broke alignment

2. **Stats Grid** (`.mt-stats-grid`)
   - Grid layout within 1200px container
   - Auto-fit columns with minimum 200px width
   - Proper responsive scaling

3. **Rankings Section** (`.mt-rankings-section`)
   - Fixed from margin: -30px to margin: 0
   - Centered with proper container width
   - Improved header alignment

4. **Evaluation Table** (`.mt-evaluation-table-wrap`)
   - Table wrapper follows unified width
   - Horizontal scroll on mobile devices
   - Consistent padding with other elements

5. **Search Filters** (`.mt-search-filters`)
   - Search input and filter select aligned
   - Flex layout with proper wrapping
   - Mobile-optimized stacking

## Responsive Design Strategy

### Breakpoints
```css
/* Desktop and larger screens */
@media (min-width: 1201px) { }

/* Tablet landscape */
@media (max-width: 1200px) { }

/* Tablet portrait and smaller laptops */
@media (max-width: 768px) { }

/* Mobile devices */
@media (max-width: 480px) { }
```

### Mobile-First Approach
- Base styles designed for mobile
- Progressive enhancement for larger screens
- Touch-friendly interaction areas
- Optimized scrolling for tables

## CSS Best Practices

### 1. Specificity Management
```css
/* Bad - Excessive !important */
.mt-element {
    color: red !important;
    margin: 10px !important;
}

/* Good - Proper specificity */
body .mt-jury-dashboard .mt-element {
    color: red;
    margin: 10px;
}
```

### 2. BEM Methodology
```css
/* Block */
.mt-dashboard {}

/* Element */
.mt-dashboard__header {}
.mt-dashboard__content {}

/* Modifier */
.mt-dashboard--compact {}
.mt-dashboard__header--sticky {}
```

### 3. CSS Custom Properties
```css
:root {
    --mt-container-width: 1200px;
    --mt-container-padding: 20px;
    --mt-primary: #003C3D;
    --mt-secondary: #004C5F;
}

.container {
    max-width: var(--mt-container-width);
    padding: 0 var(--mt-container-padding);
}
```

## File Organization

### Current Structure
```
assets/css/
├── core/
│   ├── mt-variables.css         # CSS custom properties
│   ├── mt-base.css              # Base styles and resets
│   └── mt-utilities.css         # Utility classes
├── components/
│   ├── mt-jury-dashboard-enhanced.css  # Dashboard widgets
│   ├── mt-evaluation-forms.css         # Form styles
│   └── mt-candidate-cards.css          # Card components
├── layouts/
│   └── mt-grid-system.css       # Grid layouts
└── min/
    └── *.min.css                 # Minified production files
```

### Consolidation Progress
- Reduced from 40+ individual CSS files
- Consolidated hotfixes into organized structure
- Removed duplicate style definitions
- Improved loading performance

## Migration Guide

### Updating Existing Elements

#### Before (v2.5.37)
```css
.mt-dashboard-element {
    width: 100% !important;
    max-width: 100% !important;
    margin: -30px -30px 30px -30px !important;
}
```

#### After (v2.5.38)
```css
.mt-dashboard-element {
    max-width: 1200px;
    margin: 0 auto 30px auto;
    padding: 0 20px;
    box-sizing: border-box;
}
```

### Adding New Elements
When adding new dashboard elements:

1. Apply the unified container pattern
2. Use consistent padding (20px horizontal)
3. Set max-width to 1200px
4. Center with margin: 0 auto
5. Include box-sizing: border-box

## Performance Considerations

### Loading Strategy
1. **Critical CSS**: Inline essential above-the-fold styles
2. **Async Loading**: Non-critical styles loaded asynchronously
3. **Conditional Loading**: Load component CSS only when needed
4. **Minification**: All production CSS minified

### Optimization Techniques
- Removed unused selectors
- Consolidated duplicate rules
- Optimized selector performance
- Reduced CSS file size by 35%

## Testing Guidelines

### Visual Regression Testing
1. Test at all defined breakpoints
2. Verify element alignment at 1200px width
3. Check responsive behavior on real devices
4. Use browser DevTools responsive mode

### Browser Compatibility
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile Safari (iOS 14+)
- Chrome Mobile (Android 8+)

## Future Improvements

### Planned for v2.6.0
- [ ] CSS Grid implementation for complex layouts
- [ ] CSS-in-JS for dynamic styling
- [ ] PostCSS for advanced optimizations
- [ ] Component-based CSS modules
- [ ] Dark mode support

### Technical Debt
- Remove remaining !important declarations
- Standardize spacing scale
- Implement CSS linting
- Create style guide documentation

## Troubleshooting

### Common Issues

#### Elements Not Centering
```css
/* Ensure these properties are set */
.element {
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
    box-sizing: border-box;
}
```

#### Responsive Layout Breaking
```css
/* Add proper media queries */
@media (max-width: 768px) {
    .element {
        padding-left: 15px;
        padding-right: 15px;
    }
}
```

#### Overflow Issues
```css
/* Handle overflow properly */
.table-wrapper {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
```

## References

- [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)
- [BEM Methodology](http://getbem.com/)
- [CSS Custom Properties](https://developer.mozilla.org/en-US/docs/Web/CSS/Using_CSS_custom_properties)
- [Responsive Web Design](https://developer.mozilla.org/en-US/docs/Learn/CSS/CSS_layout/Responsive_Design)

---

*Last Updated: 2025-08-21 | Version 2.5.38*