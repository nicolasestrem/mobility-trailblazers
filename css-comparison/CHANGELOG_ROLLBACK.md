# Candidate Layout Rollback Change Log

## Version 1.0.0 - 2025-01-19

### Purpose
Restore individual candidate page layouts to match staging environment appearance.

### Affected Pages
- Individual candidate pages (e.g., `/candidate/christine-von-breitenbuch/`)
- URL pattern: `http://localhost:8080/candidate/*/`

### CSS Selectors Reverted

#### Hero Section
- `.mt-candidate-hero, .mt-hero-section`
  - `padding`: 80px 0 60px → from 40px 0 30px
  - `min-height`: 450px → from auto
  - `max-height`: removed constraint (was 400px)
  
- `.mt-hero-pattern`
  - `opacity`: 0.15 → from 0.05
  - `max-height`: removed constraint

- `.mt-hero-content, .mt-profile-header-enhanced`
  - `grid-template-columns`: 300px 1fr → from 280px 1fr
  - `gap`: 50px → from 30px
  - `padding`: 20px 0 → from 15px 0

#### Photo Frame
- `.mt-photo-frame`
  - `width/height`: 280px → from 220px

- `.mt-photo-border`
  - `width/height`: 280px → from 220px
  - `padding`: 12px → from 8px

#### Typography
- `.mt-hero-name, .mt-profile-name-enhanced`
  - `font-size`: 3.5rem → from 2.8rem
  - `margin-bottom`: 15px → from 10px

- `.mt-hero-title`
  - `font-size`: 1.6rem → from 1.4rem
  - `margin-bottom`: 30px → from 25px

#### Content Sections
- `.mt-content-section`
  - `margin-top`: 0 → from -30px (removed pull-up effect)
  - `border-radius`: 16px → from 30px 30px 0 0
  - `background`: white → from #f8f9fa
  - `padding`: 45px → from 35px

- `.mt-candidate-content`
  - `padding`: 100px 0 → from 40px 0

- `.mt-content-container`
  - `gap`: 80px → from 60px

#### Sidebar
- `.mt-sidebar-widget`
  - `padding`: 35px → from 25px
  - `margin-bottom`: 30px → from 20px

#### Evaluation Criteria
- `.mt-criteria-grid`
  - `gap`: 30px → from 20px

- `.mt-criterion-card`
  - `padding`: 30px → from 25px
  - `border-left-width`: 8px → from 6px

- `.mt-criterion-icon`
  - `width/height`: 56px → from 48px
  - `font-size`: 28px → from 24px

- `.mt-criterion-title`
  - `font-size`: 20px → from 18px

- `.mt-criterion-content`
  - `font-size`: 16px → from 15px

#### Section Headers
- `.mt-section-header`
  - `margin-bottom`: 35px → from 25px
  - `padding-bottom`: 25px → from 15px

- `.mt-section-header i`
  - `font-size`: 32px → from 28px
  - `width/height`: 60px → from 52px
  - `padding`: 14px → from 12px

- `.mt-section-header h2`
  - `font-size`: 28px → from 24px

#### Categories & Social Links
- `.mt-hero-categories`
  - `margin-bottom`: 35px → from 30px

- `.mt-category-badge`
  - `padding`: 10px 24px → from 8px 20px
  - `font-size`: 0.95rem → from 0.9rem

- `.mt-social-link:hover`
  - `background`: white → from rgba(255, 255, 255, 0.2)
  - `color`: var(--mt-primary) → from white

#### Animations
- `.mt-enhanced-candidate-profile`
  - `animation-duration`: 0.6s → from 0.8s

- `.mt-content-section`
  - `animation-duration`: 0.5s → from 0.6s
  - Reduced animation delays for faster loading

- `.mt-sidebar-widget`
  - `animation-duration`: 0.5s → from 0.6s
  - Reduced animation delays

#### Box Shadows
- `.mt-content-section`
  - `box-shadow`: 0 2px 12px rgba(0, 0, 0, 0.08) → from 0 4px 20px

- `.mt-sidebar-widget`
  - `box-shadow`: 0 2px 12px rgba(0, 0, 0, 0.08) → from 0 4px 20px

### Responsive Breakpoints Added
- **1280px**: Content container adjustments
- **1024px**: Hero layout switch to single column on tablets
- **768px**: Mobile layout with centered content
- **480px**: Small mobile optimizations

### Implementation Details

#### File Location
`/assets/css/mt_candidate_rollback.css`

#### Loading Order
Enqueued last in WordPress to ensure proper cascade:
```php
// Only loads on single candidate pages
if (is_singular('mt_candidate')) {
    wp_enqueue_style(
        'mt-candidate-rollback',
        MT_PLUGIN_URL . 'assets/css/mt_candidate_rollback.css',
        ['mt-variables', 'mt-components', 'mt-frontend', 'mt-enhanced-candidate-profile'],
        '1.0.0'
    );
}
```

#### Modified Files
1. `/assets/css/mt_candidate_rollback.css` - Created
2. `/includes/core/class-mt-plugin.php` - Added enqueue statement

### Testing Notes
- All changes use `!important` to ensure override priority
- Maintains existing HTML structure - CSS only changes
- Preserves all functionality and content
- Responsive behavior matches staging at all breakpoints
- No impact on other templates (jury dashboard, candidate grid, etc.)

### Rollback Instructions
To remove these changes:
1. Delete `/assets/css/mt_candidate_rollback.css`
2. Remove the enqueue statement from `class-mt-plugin.php`

### Known Limitations
- Only affects single candidate pages
- Does not modify admin views
- Preserves all existing JavaScript functionality