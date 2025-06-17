# Mobility Trailblazers - Award Management Platform

A comprehensive WordPress plugin for managing mobility innovation awards in the DACH region. Features include candidate management, jury evaluations, public voting, and full Elementor Pro integration.

## Overview

Mobility Trailblazers is a complete award management solution designed to:
- Showcase mobility innovators and shapers in the DACH region
- Enable jury members to evaluate candidates professionally
- Allow public voting for community favorites
- Provide comprehensive administration tools
- Integrate seamlessly with WordPress and Elementor

## Features

### 🏆 Award Management
- **Candidate Profiles**: Comprehensive profiles with photos, bios, and achievements
- **Category Management**: Organize candidates by award categories
- **Multi-stage Evaluation**: Support for jury evaluation and public voting phases

### 👥 Jury System
- **Modern Dashboard**: Beautiful, responsive interface for jury members
- **5-Criteria Evaluation**: Score candidates on courage, innovation, implementation, relevance, and visibility
- **Draft Support**: Save evaluations as drafts before final submission
- **Progress Tracking**: Visual indicators showing evaluation completion
- **Real-time Search**: Filter and find assigned candidates instantly

### 🗳️ Public Voting
- **User-friendly Interface**: Easy voting process for public participation
- **Vote Restrictions**: IP-based and cookie-based duplicate prevention
- **Real-time Results**: Live vote counting and statistics
- **Voting Periods**: Admin-controlled voting windows

### 🛠️ Administration
- **Assignment Management**: Easily assign candidates to jury members
- **Bulk Operations**: Auto-assignment and bulk management tools
- **Import/Export**: CSV support for data management
- **Comprehensive Settings**: Full control over all aspects of the awards

### 🎨 Elementor Integration
- **Custom Widgets**: Native Elementor widgets for all major components
- **Design Control**: Full styling options within Elementor
- **Responsive Design**: Mobile-first approach for all elements

## Installation

1. Upload the `mobility-trailblazers` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Run the setup wizard or configure manually via the admin menu

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Elementor Pro (optional, for widget functionality)

## Quick Start

### Setting Up Awards

1. Navigate to **Mobility Trailblazers** → **Categories**
2. Create award categories (e.g., "Innovation Leader", "Sustainability Champion")
3. Add candidates via **Mobility Trailblazers** → **Add New Candidate**
4. Create jury members and their user accounts
5. Assign candidates to jury members

### Using Shortcodes

```php
// Display candidates grid
[mt_candidates category="innovation" limit="12" columns="3"]

// Show jury dashboard
[mt_jury_dashboard show_stats="yes" show_progress="yes"]

// Display voting form
[mt_voting_form]

// Show voting results
[mt_voting_results show_chart="yes"]
```

### Elementor Widgets

Available widgets in the Elementor editor:
- **MT Candidates Grid** - Display candidates with filtering
- **MT Jury Dashboard** - Complete jury interface
- **MT Voting Form** - Public voting interface
- **MT Results Display** - Show voting results

## Jury Dashboard Guide

### For Jury Members

1. **Login**: Use your provided credentials to access the jury area
2. **Dashboard Overview**: 
   - View assigned candidates count
   - Track evaluation progress
   - See completion percentage
3. **Evaluating Candidates**:
   - Click on any candidate card
   - Score each of the 5 criteria (1-10 scale)
   - Add optional comments
   - Save as draft or submit final evaluation
4. **Search & Filter**:
   - Use the search box to find specific candidates
   - Filter by evaluation status (Pending/Draft/Completed)

### Evaluation Criteria

1. **Mut & Pioniergeist** (Courage & Pioneer Spirit) - Did they act against resistance?
2. **Innovationsgrad** (Innovation Degree) - How innovative is the contribution?
3. **Umsetzungskraft & Wirkung** (Implementation & Impact) - What results were achieved?
4. **Relevanz für Mobilitätswende** (Mobility Transformation Relevance) - DACH region impact?
5. **Vorbildfunktion & Sichtbarkeit** (Role Model & Visibility) - Public inspiration factor?

## Development

### File Structure

```
mobility-trailblazers/
├── assets/
│   ├── jury-dashboard.js      # Jury dashboard functionality
│   ├── jury-dashboard.css     # Jury dashboard styling
│   ├── frontend.js            # General frontend scripts
│   └── frontend.css           # General frontend styles
├── includes/
│   ├── class-mt-jury-system.php    # Jury system core
│   ├── class-mt-ajax-handlers.php  # AJAX endpoints
│   └── mt-utility-functions.php    # Helper functions
├── templates/
│   └── shortcodes/
│       └── jury-dashboard.php      # Jury dashboard template
└── elementor/
    └── widgets/
        └── jury-dashboard.php      # Elementor widget
```

### Hooks & Filters

```php
// Modify evaluation criteria
add_filter('mt_evaluation_criteria', function($criteria) {
    // Add or modify criteria
    return $criteria;
});

// After evaluation submission
add_action('mt_evaluation_submitted', function($candidate_id, $jury_member_id, $scores) {
    // Custom actions after evaluation
}, 10, 3);

// Customize jury dashboard
add_filter('mt_jury_dashboard_stats', function($stats, $jury_member_id) {
    // Modify dashboard statistics
    return $stats;
}, 10, 2);
```

### AJAX Endpoints

Available AJAX actions for custom development:
- `mt_get_jury_dashboard_data` - Retrieve dashboard statistics
- `mt_get_candidate_evaluation` - Get evaluation data
- `mt_save_evaluation` - Save evaluation (draft or final)
- `mt_manual_assign` - Manually assign candidates
- `mt_auto_assign` - Auto-assign candidates

## Troubleshooting

### Common Issues

**Jury Dashboard Not Loading**
- Check browser console for JavaScript errors
- Verify user has jury member permissions
- Ensure assets are loading (no 404 errors)

**Evaluations Not Saving**
- Check AJAX response in browser Network tab
- Verify nonce is being passed correctly
- Ensure database tables exist

**Styling Issues**
- Clear browser cache
- Check for CSS conflicts with theme
- Verify Elementor is not overriding styles

### Debug Mode

Enable debug mode in `wp-config.php`:
```php
define('MT_DEBUG', true);
```


## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed for the DACH Mobility Innovation Awards by [Your Organization]

### Contributors
- Lead Developer: [Name]
- UI/UX Design: [Name]
- Project Manager: [Name]

### Special Thanks
- Elementor Team for the excellent page builder
- WordPress Community for ongoing support

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for detailed version history.

---

Made with ❤️ for the future of mobility in the DACH region