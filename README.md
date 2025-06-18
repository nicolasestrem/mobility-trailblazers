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
- **Assignment Management**: Flexible candidate-to-jury assignment system with both database and post meta support

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
- **Self-Healing Capabilities**: Automatic repair of permissions and database issues

### 🎨 Elementor Integration
- **Custom Widgets**: Native Elementor widgets for all major components
- **Design Control**: Full styling options within Elementor
- **Responsive Design**: Mobile-first approach for all elements

## Installation

1. Upload the `mobility-trailblazers` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The plugin will automatically:
   - Create necessary database tables
   - Set up required user roles and capabilities
   - Configure default settings

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Elementor Pro (optional, for widget functionality)

## Database Architecture

The plugin uses a hybrid approach for data storage:

### Custom Tables
- `wp_mt_votes` - Public voting data
- `wp_mt_candidate_scores` - Jury scoring data (legacy)
- `wp_mt_evaluations` - Detailed jury evaluations
- `wp_mt_jury_assignments` - Candidate-to-jury assignments
- `wp_vote_reset_logs` - Voting reset audit trail
- `wp_mt_vote_backups` - Voting data backups

### Post Meta Storage
The plugin maintains backward compatibility by supporting assignment data in post meta as a fallback when custom tables are not available.

## Quick Start

### Setting Up Awards

1. Navigate to **Mobility Trailblazers** → **Categories**
2. Create award categories (e.g., "Innovation Leader", "Sustainability Champion")
3. Add candidates via **Mobility Trailblazers** → **Add New Candidate**
4. Create jury members and their user accounts
5. Assign candidates to jury members for evaluation

### Managing Assignments

The plugin offers two methods for managing jury assignments:

1. **Manual Assignment**: Select specific candidates for each jury member
2. **Auto-Assignment**: Use balanced or random algorithms to distribute candidates

### Evaluation Process

1. Jury members log in to their dashboard
2. They see their assigned candidates
3. For each candidate, they provide scores (1-10) on five criteria:
   - **Mut & Pioniergeist** (Courage & Pioneer Spirit)
   - **Innovationsgrad** (Innovation Degree)
   - **Umsetzungskraft & Wirkung** (Implementation & Impact)
   - **Relevanz für Mobilitätswende** (Mobility Transformation Relevance)
   - **Vorbildfunktion & Sichtbarkeit** (Role Model & Visibility)

## Troubleshooting

### Common Issues and Solutions

#### Database Tables Not Created
**Symptom**: Errors mentioning missing tables like `wp_mt_evaluations` or `wp_mt_jury_assignments`

**Solution**: The plugin now includes automatic table creation. Simply:
1. Deactivate and reactivate the plugin, OR
2. Visit any admin page - tables will be created automatically

#### Administrator Missing Capabilities
**Symptom**: "Administrator role missing edit_others_mt_jury_member capability" errors

**Solution**: The plugin now self-heals capabilities. Just:
1. Visit any WordPress admin page
2. Capabilities are automatically repaired on each page load

#### Assignments Not Showing
**Symptom**: Jury members don't see assigned candidates

**Solution**: The plugin now includes fallback methods:
1. If the assignments table exists, it will be used
2. If not, the plugin falls back to post meta storage
3. Both methods work seamlessly

### Debug Mode

Enable debug mode in `wp-config.php`:
```php
define('MT_DEBUG', true);
```

This will:
- Enable detailed error logging
- Show diagnostic information in the admin area
- Help troubleshoot issues

## Development

### Architecture Overview

The plugin follows WordPress coding standards and uses:
- **Object-oriented architecture** with clearly defined classes
- **Hybrid data storage** supporting both custom tables and post meta
- **Self-healing mechanisms** for automatic issue resolution
- **Comprehensive error handling** to prevent fatal errors

### Key Components

```
mobility-trailblazers/
├── includes/
│   ├── class-database.php         # Database table management
│   ├── class-roles.php           # Roles and capabilities
│   ├── class-post-types.php      # Custom post type definitions
│   ├── class-mt-ajax-handlers.php # AJAX endpoints
│   └── mt-utility-functions.php   # Helper functions with fallbacks
├── admin/
│   └── views/                    # Admin interface templates
└── templates/
    └── shortcodes/               # Frontend templates
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

## Version History

### 1.0.4 (Current)
- Critical infrastructure update
- Fixed missing database tables (evaluations, assignments)
- Fixed administrator capability issues
- Added self-healing mechanisms
- Improved backward compatibility

### 1.0.3
- Fixed assignment data type consistency
- Improved AJAX handlers

### 1.0.2
- Initial public release
- Full feature set implementation

See [CHANGELOG.md](CHANGELOG.md) for detailed version history.

## Support

For support and documentation:
- Check the diagnostic tool at **Mobility Trailblazers** → **Diagnostic**
- Enable debug mode for detailed error information
- Review the [CHANGELOG.md](CHANGELOG.md) for recent fixes

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed for the Mobility Trailblazers initiative in the DACH region, recognizing and celebrating pioneers in mobility transformation.