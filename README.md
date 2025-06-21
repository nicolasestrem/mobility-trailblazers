# Mobility Trailblazers - Award Management Platform

**Version:** 2.0.0  
**Requires:** WordPress 5.8+, PHP 7.4+  
**License:** GPL v2 or later

A modern, clean WordPress plugin for managing mobility innovation awards in the DACH region. This platform enables jury members to evaluate candidates through a sophisticated scoring system while providing administrators with powerful management tools.

## 🎯 Overview

Mobility Trailblazers is a complete award management solution designed specifically for recognizing and celebrating pioneers in mobility transformation. The platform focuses on jury-based evaluation with a streamlined, professional interface.

## ✨ Key Features

### 🏆 Award Management
- **Candidate Profiles**: Comprehensive profiles with photos, biographies, and achievements
- **Category Management**: Organize candidates by award categories
- **Multi-criteria Evaluation**: 5-point evaluation system for thorough assessment

### 👥 Jury System
- **Modern Dashboard**: Beautiful, responsive interface for jury members
- **5-Criteria Evaluation System**:
  - Mut & Pioniergeist (Courage & Pioneer Spirit)
  - Innovationsgrad (Innovation Degree)
  - Umsetzungskraft & Wirkung (Implementation & Impact)
  - Relevanz für Mobilitätswende (Mobility Transformation Relevance)
  - Vorbildfunktion & Sichtbarkeit (Role Model & Visibility)
- **Draft Support**: Save evaluations as drafts before final submission
- **Progress Tracking**: Visual indicators showing evaluation completion
- **Real-time Search**: Filter and find assigned candidates instantly

### 🛠️ Administration
- **Assignment Management**: Flexible candidate-to-jury assignment system
- **Bulk Operations**: Auto-assignment with balanced or random distribution
- **Import/Export**: CSV support for data management
- **Comprehensive Settings**: Full control over evaluation criteria weights
- **Statistics Dashboard**: Real-time insights into evaluation progress

## 📦 Installation

1. Upload the `mobility-trailblazers` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The plugin will automatically:
   - Create necessary database tables
   - Set up required user roles and capabilities
   - Configure default settings

## 🚀 Quick Start

### Initial Setup

1. **Configure Settings**
   - Navigate to **Mobility Trailblazers** → **Settings**
   - Set evaluation criteria weights
   - Configure email notifications

2. **Create Award Categories**
   - Go to **Mobility Trailblazers** → **Award Categories**
   - Add categories like "Innovation Leader", "Sustainability Champion", etc.

3. **Add Candidates**
   - Navigate to **Mobility Trailblazers** → **Add New Candidate**
   - Fill in candidate details and assign categories
   - Upload candidate photo

4. **Setup Jury Members**
   - Create WordPress user accounts for jury members
   - Add jury member profiles via **Mobility Trailblazers** → **Add New Jury Member**
   - Link jury profiles to WordPress users

5. **Assign Candidates**
   - Go to **Mobility Trailblazers** → **Assignments**
   - Use manual assignment or auto-assignment feature
   - Configure candidates per jury member

### For Jury Members

1. **Access Dashboard**: Use the `[mt_jury_dashboard]` shortcode on any page
2. **Review Candidates**: Browse assigned candidates with search and filter options
3. **Submit Evaluations**: Score candidates on all five criteria
4. **Save Drafts**: Save progress and return later to complete evaluations

## 🏗️ Architecture

### Modern PHP Structure
- **Namespaces**: Full PSR-4 autoloading support
- **Repository Pattern**: Clean data access layer
- **Service Layer**: Business logic separation
- **AJAX Handlers**: Structured AJAX communication

### Database Schema
```sql
-- Evaluations Table
wp_mt_evaluations
- id (Primary Key)
- jury_member_id
- candidate_id
- courage_score (0-10)
- innovation_score (0-10)
- implementation_score (0-10)
- relevance_score (0-10)
- visibility_score (0-10)
- total_score (calculated)
- comments
- status (draft/completed)
- created_at
- updated_at

-- Assignments Table
wp_mt_jury_assignments
- id (Primary Key)
- jury_member_id
- candidate_id
- assigned_at
- assigned_by
```

### File Structure
```
mobility-trailblazers/
├── includes/
│   ├── core/           # Core functionality
│   ├── interfaces/     # PHP interfaces
│   ├── repositories/   # Data access layer
│   ├── services/       # Business logic
│   ├── admin/          # Admin functionality
│   └── ajax/           # AJAX handlers
├── assets/
│   ├── css/           # Stylesheets
│   └── js/            # JavaScript files
├── templates/
│   ├── admin/         # Admin templates
│   └── frontend/      # Frontend templates
└── languages/         # Translation files
```

## 🛡️ Security

- All database queries use prepared statements
- Comprehensive nonce verification for AJAX requests
- Capability-based access control
- Input sanitization and output escaping
- CSRF protection on all forms

## 🔧 Configuration

### Shortcodes

#### 1. Jury Dashboard
`[mt_jury_dashboard]`

Displays the jury member dashboard with evaluation interface. Must be placed on a page accessible only to logged-in jury members.

**Features:**
- Progress tracking with visual statistics
- Candidate assignment list with search and filtering  
- Direct evaluation form access
- Draft saving capability
- Responsive design

#### 2. Candidates Grid
`[mt_candidates_grid]`

Shows a public grid of award candidates.

**Parameters:**
- `category` - Filter by award category slug (optional)
- `columns` - Number of columns: 2, 3, or 4 (default: 3)
- `limit` - Maximum candidates to show (default: -1 for all)
- `orderby` - Sort field: title, date, modified (default: title)
- `order` - Sort direction: ASC or DESC (default: ASC)
- `show_bio` - Display candidate bio: yes/no (default: yes)
- `show_category` - Display category: yes/no (default: yes)

**Example:**
```
[mt_candidates_grid category="innovation" columns="3" limit="6" show_bio="yes"]
```

#### 3. Evaluation Statistics
`[mt_evaluation_stats]`

Displays evaluation statistics (admin/jury admin only).

**Parameters:**
- `type` - Display type: summary, by-category, by-jury (default: summary)
- `show_chart` - Show visual charts: yes/no (default: yes)

**Example:**
```
[mt_evaluation_stats type="by-category" show_chart="yes"]
```

#### 4. Winners Display
`[mt_winners_display]`

Shows the top-scored candidates as award winners.

**Parameters:**
- `category` - Filter by category slug (optional)
- `year` - Award year (default: current year)
- `limit` - Number of winners to show (default: 3)
- `show_scores` - Display average scores: yes/no (default: no)

**Example:**
```
[mt_winners_display category="sustainability" limit="3" year="2024" show_scores="yes"]
```

### Hooks & Filters

```php
// Modify evaluation criteria
add_filter('mt_evaluation_criteria', function($criteria) {
    // Add or modify criteria
    return $criteria;
});

// After evaluation submission
add_action('mt_evaluation_submitted', function($evaluation_id, $data) {
    // Custom actions after evaluation
}, 10, 2);

// Customize validation
add_filter('mt_evaluation_validate', function($valid, $data, $service) {
    // Additional validation logic
    return $valid;
}, 10, 3);
```

## 📊 Admin Capabilities

### User Roles

**Jury Member** (`mt_jury_member`)
- View assigned candidates
- Submit evaluations
- Save drafts
- View own progress

**Administrator**
- All jury member capabilities
- Manage candidates and jury members
- Configure assignments
- View all evaluations
- Export data
- System settings

### Custom Capabilities
- `mt_manage_evaluations` - Manage all evaluations
- `mt_submit_evaluations` - Submit own evaluations
- `mt_view_all_evaluations` - View all evaluation data
- `mt_manage_assignments` - Manage jury assignments
- `mt_manage_settings` - Configure plugin settings
- `mt_export_data` - Export system data
- `mt_import_data` - Import candidates

## 🌐 Internationalization

The plugin is fully translatable with complete support for:
- German (primary)
- English
- Custom translations via .po/.mo files

Text domain: `mobility-trailblazers`

## 🐛 Troubleshooting

### Database Tables Not Created
- Deactivate and reactivate the plugin
- Check WordPress error logs for database errors
- Ensure MySQL user has CREATE TABLE permissions

### Assignments Not Showing
- Verify jury member is linked to WordPress user
- Check assignment exists in Assignments page
- Clear browser cache

### Evaluation Not Saving
- Check browser console for JavaScript errors
- Verify AJAX endpoint is accessible
- Ensure jury member has proper capabilities

## 📝 Changelog

### Version 2.0.0 (2024-01-21)
- Complete rebuild with modern architecture
- Removed public voting system
- Removed Elementor dependencies
- Implemented Repository pattern
- Added Service layer for business logic
- Modern, responsive UI
- Improved security and performance
- PSR-4 autoloading
- Clean codebase with no legacy dependencies

## 🤝 Support

For issues, feature requests, or contributions:
1. Check existing documentation
2. Review error logs
3. Contact development team

## 📄 License

This plugin is licensed under the GPL v2 or later.

---

**Developed for the Mobility Trailblazers initiative** - Recognizing and celebrating pioneers in mobility transformation across the DACH region. 