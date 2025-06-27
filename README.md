# Mobility Trailblazers - Award Management Platform

**Version:** 2.0.11
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
- **5x2 Grid Layout**: Revolutionary rankings display with fixed 10-candidate grid
- **Inline Evaluation Controls**: Direct score adjustment without page navigation
- **Real-time Rankings**: Dynamic rankings that update automatically after evaluations
- **5-Criteria Evaluation System**:
  - Mut & Pioniergeist (Courage & Pioneer Spirit)
  - Innovationsgrad (Innovation Degree)
  - Umsetzungskraft & Wirkung (Implementation & Impact)
  - Relevanz für Mobilitätswende (Mobility Transformation Relevance)
  - Vorbildfunktion & Sichtbarkeit (Role Model & Visibility)
- **Progress Ring Visualizations**: SVG-based circular progress indicators
- **Draft Support**: Save evaluations as drafts before final submission
- **Progress Tracking**: Visual indicators showing evaluation completion
- **Real-time Search**: Filter and find assigned candidates instantly
- **Dashboard Customization**: Full visual customization with color theming

### 🛠️ Administration
- **Assignment Management**: Flexible candidate-to-jury assignment system
- **Bulk Operations**: Auto-assignment with balanced or random distribution
- **Import/Export**: CSV support for data management
- **Comprehensive Settings**: Full control over evaluation criteria weights
- **Statistics Dashboard**: Real-time insights into evaluation progress
- **Dashboard Customization System**: Complete visual customization for jury interface
- **Diagnostics Tools**: Comprehensive debugging interface for administrators
- **Performance Monitoring**: System health checks and optimization tools

## 📦 Installation

### Requirements
- WordPress 5.8 or higher
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Modern browser (Chrome 90+, Firefox 88+, Safari 14+, Edge 90+)

### Installation Steps
1. **Upload Plugin**: Upload the `mobility-trailblazers` folder to `/wp-content/plugins/`
2. **Activate Plugin**: Activate through the 'Plugins' menu in WordPress
3. **Automatic Setup**: The plugin will automatically:
   - Create necessary database tables (`wp_mt_evaluations`, `wp_mt_jury_assignments`)
   - Set up required user roles and capabilities
   - Configure default settings and customization options
   - Initialize the diagnostics system

### Post-Installation Verification
- Visit **Mobility Trailblazers** → **Diagnostics** to verify installation
- Check that all database tables were created successfully
- Ensure user roles and capabilities are properly configured

## 🚀 Quick Start

### Initial Setup

1. **Configure Settings**
   - Navigate to **Mobility Trailblazers** → **Settings**
   - Set evaluation criteria weights
   - Configure dashboard customization (colors, layout, etc.)
   - Enable/disable rankings and set display limits

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
   - Configure candidates per jury member (supports bulk operations)

6. **Customize Dashboard**
   - Configure visual appearance in Settings
   - Set up header styles, color themes, and layout preferences
   - Enable inline evaluation controls for improved workflow

### For Jury Members

1. **Access Dashboard**: Use the `[mt_jury_dashboard]` shortcode on any page
2. **View Rankings**: See real-time rankings in the 5x2 grid layout with medal indicators
3. **Inline Evaluations**: Adjust scores directly from the rankings view using +/- controls
4. **Review Candidates**: Browse assigned candidates with search and filter options
5. **Submit Evaluations**: Score candidates on all five criteria with visual progress rings
6. **Save Drafts**: Save progress and return later to complete evaluations
7. **Track Progress**: Monitor evaluation completion with visual indicators

## 🏗️ Architecture

### Modern PHP Structure
- **Namespaces**: Full PSR-4 autoloading support
- **Repository Pattern**: Clean data access layer
- **Service Layer**: Business logic separation
- **AJAX Handlers**: Structured AJAX communication

### Database Schema
```sql
-- Evaluations Table (Enhanced with inline evaluation support)
wp_mt_evaluations
- id (Primary Key)
- jury_member_id (Foreign Key)
- candidate_id (Foreign Key)
- courage_score (0-10, decimal precision)
- innovation_score (0-10, decimal precision)
- implementation_score (0-10, decimal precision)
- relevance_score (0-10, decimal precision)
- visibility_score (0-10, decimal precision)
- total_score (calculated average)
- comments (TEXT)
- status (draft/completed)
- created_at (DATETIME)
- updated_at (DATETIME)

-- Assignments Table (Enhanced with tracking)
wp_mt_jury_assignments
- id (Primary Key)
- jury_member_id (Foreign Key)
- candidate_id (Foreign Key)
- assigned_at (DATETIME)
- assigned_by (User ID)
- status (active/inactive)
```

### File Structure
```
mobility-trailblazers/
├── includes/
│   ├── core/           # Core functionality & plugin initialization
│   ├── interfaces/     # PHP interfaces for repositories & services
│   ├── repositories/   # Data access layer with optimized queries
│   ├── services/       # Business logic and validation
│   ├── admin/          # Admin functionality & settings
│   └── ajax/           # AJAX handlers for real-time features
├── assets/
│   ├── css/           # Modern CSS with Grid & Flexbox layouts
│   └── js/            # ES6+ JavaScript with AJAX functionality
├── templates/
│   ├── admin/         # Admin interface templates
│   ├── frontend/      # Public-facing templates
│   └── partials/      # Reusable template components
├── doc/               # Comprehensive documentation
├── debug/             # Development & debugging tools
└── languages/         # Translation files (German/English)
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

### Common Issues

#### Database Tables Not Created
- Deactivate and reactivate the plugin
- Check WordPress error logs for database errors
- Ensure MySQL user has CREATE TABLE permissions
- Use the Diagnostics page to force database upgrade

#### Assignments Not Showing
- Verify jury member is linked to WordPress user
- Check assignment exists in Assignments page
- Clear browser cache and check for JavaScript errors
- Use debug tools in `/debug/` folder for assignment validation

#### Evaluation Not Saving
- Check browser console for JavaScript errors
- Verify AJAX endpoint is accessible (`/wp-admin/admin-ajax.php`)
- Ensure jury member has proper capabilities (`mt_submit_evaluations`)
- Check nonce verification and security settings

#### Inline Evaluation Controls Not Working
- Ensure JavaScript is enabled and no console errors
- Check that AJAX endpoints are properly registered
- Verify candidate assignments are correct
- Clear browser cache to load latest assets

#### Rankings Not Updating
- Check that evaluations are marked as "completed" not "draft"
- Verify auto-refresh is enabled (30-second intervals)
- Check browser console for AJAX errors
- Ensure proper user permissions

### Diagnostics Tools
- **Admin Diagnostics Page**: Navigate to Mobility Trailblazers → Diagnostics
- **Debug Scripts**: Use scripts in `/debug/` folder for specific issues
- **Error Logs**: Check WordPress debug logs for plugin-specific errors
- **Browser Console**: Monitor for JavaScript errors and AJAX failures

### Performance Issues
- **Large Datasets**: Consider pagination for 100+ candidates
- **Slow AJAX**: Check server response times and database optimization
- **Memory Issues**: Increase PHP memory limit if needed
- **Cache Problems**: Clear all caches (browser, WordPress, server)

## 📝 Recent Updates

### Version 2.0.11 (2025-06-26)
- **5x2 Grid Layout System**: Revolutionary new rankings display with fixed 10-candidate grid
- **Inline Evaluation Controls**: Direct score adjustment without page navigation
- **AJAX-powered Inline Saves**: New backend infrastructure for seamless evaluation updates
- **Enhanced User Experience**: Dramatically improved workflow for jury members
- **Performance Optimization**: Improved efficiency and responsiveness

### Version 2.0.10 (2025-06-26)
- **Enhanced Visual Design**: Complete redesign with modern grid-based layout
- **Progress Ring Visualizations**: SVG-based circular progress indicators for criteria scores
- **Interactive Elements**: Enhanced interactivity and visual feedback
- **Responsive Design**: Optimized for all screen sizes with mobile-first approach

### Version 2.0.9 (2025-06-26)
- **Jury Rankings System**: Comprehensive dynamic rankings display for jury members
- **Real-time Updates**: Rankings update automatically after evaluation submissions
- **Visual Hierarchy**: Medal indicators for top 3 positions
- **Interactive Elements**: Clickable candidate names linking to evaluation forms

### Version 2.0.0 (2024-01-21)
- Complete rebuild with modern architecture
- Removed public voting system and Elementor dependencies
- Implemented Repository pattern and Service layer
- Modern, responsive UI with improved security and performance
- PSR-4 autoloading and clean codebase

*For complete changelog, see [doc/mt-changelog-updated.md](doc/mt-changelog-updated.md)*

## 📚 Documentation

### Complete Documentation Suite
- **[Developer Guide](doc/mt-developer-guide.md)** - Comprehensive development documentation
- **[Architecture Documentation](doc/mt-architecture-docs.md)** - Technical architecture details
- **[Customization Guide](doc/mt-customization-guide.md)** - Dashboard and interface customization
- **[Complete Changelog](doc/mt-changelog-updated.md)** - Detailed version history
- **[Known Issues](doc/Needed%20fixes.md)** - Current issues and planned fixes

### Feature-Specific Documentation
- **[5x2 Grid Implementation](doc/5x2-grid-implementation-summary.md)** - Grid layout system details
- **[Inline Evaluation System](doc/inline-evaluation-system.md)** - Inline controls documentation
- **[Jury Rankings System](doc/jury-rankings-system.md)** - Rankings display system
- **[Assignment Management](doc/assignment-management-fixes.md)** - Assignment system details

## 🤝 Support

### Getting Help
1. **Check Documentation**: Review the comprehensive guides in the `/doc/` folder
2. **Use Diagnostics**: Navigate to Mobility Trailblazers → Diagnostics for system health
3. **Review Error Logs**: Check WordPress debug logs for specific errors
4. **Debug Tools**: Use scripts in `/debug/` folder for troubleshooting

### Reporting Issues
- Include WordPress and PHP versions
- Provide error messages and steps to reproduce
- Use the Diagnostics page to export system information
- Check existing documentation before reporting

## 📄 License

This plugin is licensed under the GPL v2 or later.

## 🚀 Development Status

### Current Version: 2.0.11
- **Stable**: Production-ready with comprehensive testing
- **Active Development**: Regular updates and improvements
- **Feature Complete**: All core functionality implemented
- **Performance Optimized**: Efficient database queries and AJAX operations

### Upcoming Features (Roadmap)
- **Email Notification System** (v2.1.0)
- **Advanced Reporting Features** (v2.1.0)
- **API Endpoints** (v2.2.0)
- **Multi-language Support** (v2.2.0)
- **Mobile App Companion** (Future)

### Browser Compatibility
- **Full Support**: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- **Mobile Support**: iOS Safari, Android Chrome
- **Responsive Design**: Optimized for all screen sizes

---

**Developed for the Mobility Trailblazers initiative** - Recognizing and celebrating pioneers in mobility transformation across the DACH region.

*Last updated: June 2025 | Version 2.0.11*