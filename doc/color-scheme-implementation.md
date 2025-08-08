# Mobility Trailblazers Color Scheme Implementation

## Overview

The Mobility Trailblazers plugin has been updated to use a custom color scheme that matches your website's branding. This document outlines the implementation details and usage guidelines.

## Color Palette

The following colors have been implemented throughout the plugin:

### Primary Colors
- **Primary**: `#003C3D` - Dark teal, used for main headings and primary UI elements
- **Secondary**: `#004C5F` - Deep blue, used for secondary elements and links
- **Body Text**: `#302C37` - Dark purple-gray, used for body text
- **Accent**: `#C1693C` - Burnt orange, used for call-to-action buttons and highlights

### Additional Colors
- **Kupfer Soft**: `#B86F52` - Soft copper, used for hover states and warnings
- **Kupfer Bold**: `#C1693C` - Same as accent, used for emphasis
- **Overlay BG**: `#004C5FCC` - Semi-transparent secondary color for overlays
- **Blue Accent**: `#A4DCD5` - Light blue-green, used for backgrounds and borders
- **BG Beige**: `#F5E6D3` - Light beige, used for container backgrounds
- **BG Base**: `#FFFFFF` - White, used for card backgrounds

## CSS Variables

All colors are defined as CSS custom properties (variables) in both admin and frontend stylesheets:

```css
:root {
    --mt-primary: #003C3D;
    --mt-secondary: #004C5F;
    --mt-body-text: #302C37;
    --mt-accent: #C1693C;
    --mt-kupfer-soft: #B86F52;
    --mt-kupfer-bold: #C1693C;
    --mt-overlay-bg: #004C5FCC;
    --mt-blue-accent: #A4DCD5;
    --mt-bg-beige: #F5E6D3;
    --mt-bg-base: #FFFFFF;
    --mt-full-transparency: rgba(255, 255, 255, 0.1);
    --mt-deep-blue: #004C5F;
}
```

## Implementation Details

### CSS Specificity
The CSS has been carefully crafted to:
1. Only target Mobility Trailblazers elements using specific class prefixes (`.mt-`)
2. Avoid conflicts with WordPress admin styles
3. Use `!important` sparingly and only where necessary for button overrides
4. Include proper box-sizing resets to prevent layout issues

### Key CSS Classes
- `.mt-admin-dashboard` - Main wrapper for admin pages
- `.mt-container` - Frontend container wrapper
- `.mt-card` - Card components
- `.mt-stat-box` - Statistics boxes
- `.mt-stat-card` - Statistics cards with gradients

## Implementation Areas

### Admin Interface
1. **Dashboard**: Statistics boxes with hover effects and gradient cards
2. **Tables**: Custom styled tables with primary color headers
3. **Navigation**: Tab styling for specific MT admin pages
4. **Buttons**: Primary (accent color) and secondary (deep blue) buttons
5. **Forms**: Input fields with blue accent borders
6. **Status Badges**: Color-coded status indicators
7. **Assignment Management Page** (v2.0.14):
   - White background statistics cards with accent borders
   - Icons in secondary color for visibility
   - Accessible status badges with proper contrast
   - Gradient progress bars using brand colors
   - Clean modal designs with consistent styling

### Frontend Display
1. **Candidate Cards**: Headers use a gradient from primary to secondary
2. **View Buttons**: Use the accent color with hover effect
3. **Jury Dashboard**: Welcome section uses the primary/secondary gradient
4. **Evaluation Forms**: Criteria sections use beige backgrounds
5. **Winners Display**: Cards use the primary/secondary gradient
6. **Progress Indicators**: Active steps use primary color, completed use accent

### Notifications
- **Success**: Uses primary color border
- **Error**: Uses accent color border
- **Warning**: Uses kupfer soft color border
- **Info**: Uses secondary color border

## Usage Guidelines

### When to Use Each Color
1. **Primary (#003C3D)**: Main headings, active states, primary actions
2. **Secondary (#004C5F)**: Links, secondary headings, hover states
3. **Accent (#C1693C)**: Call-to-action buttons, important highlights
4. **Body Text (#302C37)**: All regular text content
5. **Blue Accent (#A4DCD5)**: Borders, subtle backgrounds, inactive states

### Accessibility Considerations
- All color combinations meet WCAG AA contrast requirements
- White text is used on dark backgrounds (primary, secondary, accent)
- Dark text is used on light backgrounds (blue accent, beige)

## Troubleshooting

### Common Issues
1. **Layout Breaking**: Ensure all MT elements have proper box-sizing
2. **Colors Not Applying**: Check that parent containers have the correct MT classes
3. **Conflicts with Theme**: Use more specific selectors if needed

## Customization

To modify colors in the future:
1. Update the CSS variables in `assets/css/admin.css` and `assets/css/frontend.css`
2. The changes will automatically apply throughout the plugin
3. No need to modify individual component styles

## File Locations

- **Admin Styles**: `/assets/css/admin.css`
- **Frontend Styles**: `/assets/css/frontend.css`

## Recent Updates

### Version 2.0.14 (August 2025)
- **Assignment Management Page Fix**: Resolved visibility issues caused by dark teal backgrounds
- **Statistics Cards**: Changed from dark backgrounds to white with accent borders
- **Status Badges**: Updated to use accessible color combinations
- **Table Improvements**: Enhanced contrast for better readability
- **Modal Styling**: Consistent white backgrounds with brand color accents
- **Progress Bars**: Now use brand gradient for visual consistency

## Browser Support

The color scheme implementation uses CSS custom properties, which are supported in:
- Chrome 49+
- Firefox 31+
- Safari 9.1+
- Edge 15+
- All modern mobile browsers

For older browsers, fallback colors are automatically applied. 