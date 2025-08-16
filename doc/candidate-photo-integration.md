# Candidate Photo Integration & UI Enhancement
**Date:** January 2025  
**Version:** 2.2.0

## Overview
Complete overhaul of candidate and jury member presentation with photo integration and modern UI design.

## Changes Implemented

### 1. Photo Management System
- **Location:** `/Photos_candidates/webp/` - Contains all candidate photos in WebP format
- **CSV Mapping:** `/Photos_candidates/mobility_trailblazers_candidates.csv` - Links photos to candidate data
- **Script:** `match-photos.php` - Automated photo-to-candidate matching utility

#### Photo Matching Process:
1. Reads candidate names from WordPress database
2. Matches against photo filenames (handles variations like "Dr.", umlauts)
3. Uploads photos to WordPress Media Library
4. Sets as featured image for each candidate
5. Activation: Visit `/wp-admin/?match_candidate_photos=1` as admin

### 2. Template Enhancements

#### Single Candidate Template (`single-mt_candidate.php`)
**Previous:** Basic text-only layout with minimal styling
**New Features:**
- Hero section with gradient background and animated patterns
- Floating photo frame with hover effects (280x280px)
- Structured evaluation criteria cards with icons
- Sidebar with quick facts and jury CTA
- Social media integration (LinkedIn, Website)
- Responsive grid layout (2-column on desktop, stacked on mobile)

#### Candidates Grid (`candidates-grid.php`)
**Previous:** Simple list view
**New Features:**
- Card-based design with 4:3 aspect ratio images
- Interactive category filtering (JavaScript-powered)
- Hover animations and scale effects
- Load More AJAX-ready pagination
- Social link buttons with event handlers
- Responsive columns (1-4 based on viewport)

#### Jury Member Template (`single-mt_jury.php`)
**New Addition:**
- Circular profile photo presentation
- Biography and expertise sections
- Evaluation activity statistics
- Professional gradient header
- Sidebar with member stats

### 3. Visual Design System

#### Color Palette:
- Primary: `#3b82f6` to `#1e3a8a` (gradient)
- Text: `#1f2937` (headings), `#4b5563` (body)
- Backgrounds: `#f9fafb`, `#fff`
- Accents: Category-specific colors

#### Typography:
- Headings: 2.5-3rem, weight 700
- Body: 1.05rem, line-height 1.8
- Cards: 0.95rem for metadata

#### Components:
- Border radius: 16-20px for cards, 8-12px for buttons
- Shadows: Multi-layer for depth (0 4px 6px for subtle, 0 20px 40px for prominent)
- Transitions: 0.3s ease for all interactive elements

### 4. Performance Optimizations
- WebP format for optimal image compression
- Lazy loading preparation in templates
- CSS animations use GPU-accelerated properties
- Minimal JavaScript for filtering

### 5. Database Structure
No schema changes required. Uses existing meta fields:
- `_mt_organization`
- `_mt_position`
- `_mt_overview`
- `_mt_evaluation_criteria`
- `_mt_linkedin`
- `_mt_website`

## File Structure
```
mobility-trailblazers/
├── Photos_candidates/
│   ├── webp/                    # 50+ candidate photos
│   └── mobility_trailblazers_candidates.csv
├── templates/frontend/
│   ├── single/
│   │   ├── single-mt_candidate.php  # Enhanced
│   │   └── single-mt_jury.php       # New
│   └── candidates-grid.php          # Enhanced
└── doc/
    └── candidate-photo-integration.md  # This file
```

## Usage Instructions

### For Administrators:
1. Run photo matching: `/wp-admin/?match_candidate_photos=1`
2. Clear cache after template updates
3. Verify featured images in Media Library

### For Developers:
1. Templates use WordPress standards (get_header(), get_footer())
2. Styles are embedded for portability (can be extracted to separate CSS)
3. JavaScript requires jQuery (WordPress default)
4. Responsive breakpoint: 768px

## Browser Compatibility
- Modern browsers (Chrome 90+, Firefox 88+, Safari 14+, Edge 90+)
- CSS Grid and Flexbox required
- JavaScript ES6 syntax used

## Future Enhancements
- [ ] AJAX load more implementation
- [ ] Advanced filtering (multiple categories, search)
- [ ] Voting integration on candidate cards
- [ ] Animation performance monitoring
- [ ] Dark mode support
