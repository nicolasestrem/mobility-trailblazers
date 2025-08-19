# Changelog

All notable changes to the Mobility Trailblazers plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.5.27] - 2025-08-19

### Fixed
- **Candidate Profile Page Layout** - Complete redesign of individual candidate pages for improved readability and professional appearance
  - Restored proper photo display: 180px square with rounded corners (16px border-radius) instead of small circular images
  - Improved typography and readability across all content sections:
    - Hero name increased to 36px (desktop) / 28px (tablet) / 24px (mobile)
    - Section content increased to 15px with 1.7 line-height for better readability
    - Evaluation criteria text increased to 14px for improved legibility
  - Enhanced responsive design with proper breakpoints:
    - Desktop: Full layout with sidebar (>992px)
    - Tablet: Stacked layout with adjusted font sizes (768px-992px)
    - Mobile: Optimized spacing and 140px photos (<480px)
  - Optimized hero section height (260px-320px) for better visual balance
  - Added text shadows to hero text for improved contrast
  - Removed excessive animations and hover effects for cleaner UX

### Added
- **New CSS Override System** - Created `candidate-profile-override.css` with high-specificity selectors to ensure consistent styling
- **Mobile-First Responsive Styles** - Comprehensive media queries for all screen sizes
- **Accessibility Improvements** - Better color contrast and text sizing for readability

### Changed
- Updated template file `single-mt_candidate-enhanced-v2.php` to load new override CSS
- Adjusted content section padding and margins for better visual hierarchy
- Simplified evaluation criteria cards with cleaner borders and spacing

### Technical Details
- Created two new CSS files:
  - `candidate-profile-fresh.css` - Initial attempt at clean layout
  - `candidate-profile-override.css` - Final solution with maximum specificity overrides
- Modified template enqueue to load override CSS with version 2.5.26.2
- Used `body.single-mt_candidate` prefix for all selectors to ensure specificity
- All styles use `!important` to override existing problematic styles

### Developer Notes
- The override approach was necessary due to multiple conflicting CSS files being loaded
- Future refactoring should consolidate these styles into a single, well-organized CSS file
- Consider removing unused CSS files in next major version to reduce complexity