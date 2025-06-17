# Mobility Trailblazers Award System

A comprehensive WordPress plugin for managing the prestigious "25 Mobility Trailblazers in 25" award platform, designed to identify and celebrate 25 mobility innovators in the DACH region.

## Overview

The Mobility Trailblazers Award System is an enterprise-grade WordPress plugin that provides a complete solution for managing multi-stage award selection processes. It features candidate management, jury evaluation systems, public voting, and comprehensive administrative tools.

## Features

### 🏆 Core Features

- **Candidate Management System**
  - Custom post type for candidates with extensive metadata
  - Company details, innovation information, and impact metrics
  - Media management for photos and documentation
  - Status tracking (pending, approved, winner)

- **Jury Evaluation System**
  - Dedicated jury member profiles with expertise tracking
  - 5-criteria scoring system (Courage, Innovation, Implementation, Relevance, Visibility)
  - Draft saving and evaluation management
  - Progress tracking and statistics

- **Public Voting System**
  - Enable/disable public voting globally
  - Real-time vote counting
  - AJAX-powered voting interface
  - Vote tracking and prevention of duplicate votes

- **Assignment Management**
  - Visual drag-and-drop interface for candidate-jury assignments
  - Multiple assignment algorithms (random, balanced, category-based)
  - Real-time statistics and workload visualization
  - Bulk operations support

### 📊 Administrative Features

- **Dashboard & Analytics**
  - Comprehensive statistics dashboard
  - Evaluation progress tracking
  - Category-wise and criteria-wise analytics
  - Export functionality for results

- **Vote Reset System**
  - Individual, bulk, and full system reset options
  - Automatic backup creation before resets
  - Detailed audit trail and logging
  - Email notifications for affected parties

- **Import/Export Tools**
  - Bulk candidate import via CSV
  - Jury member import functionality
  - Data export in multiple formats
  - Backup and restore capabilities

- **System Diagnostics**
  - Health check system
  - Database integrity verification
  - Permission and capability checks
  - Performance monitoring

### 🎨 Frontend Features

- **Shortcodes** (8 available)
  - `[mt_candidates]` - Candidates grid with filtering
  - `[mt_jury_dashboard]` - Jury member evaluation interface
  - `[mt_voting_form]` - Public voting form
  - `[mt_registration_form]` - Candidate registration
  - `[mt_evaluation_stats]` - Statistics display
  - `[mt_winners]` - Winners showcase
  - `[mt_jury_members]` - Jury members grid
  - `[mt_candidate_profile]` - Individual candidate profiles

- **Elementor Pro Integration**
  - 8 custom Elementor widgets
  - Dynamic tags for candidate and jury data
  - Full style customization options
  - Live preview in Elementor editor

### 🔧 Technical Features

- **Custom Database Tables**
  - `mt_votes` - Evaluation data storage
  - `mt_candidate_scores` - Aggregated scoring
  - `vote_reset_logs` - Reset audit trail
  - `mt_vote_backups` - Backup storage

- **REST API Endpoints**
  - Full CRUD operations for candidates
  - Evaluation submission and retrieval
  - Assignment management
  - Backup and restore operations

- **AJAX Handlers**
  - Real-time evaluation submission
  - Draft saving functionality
  - Vote processing
  - Dynamic content loading

- **Security Features**
  - Nonce verification on all requests
  - Capability-based permissions
  - Input sanitization and validation
  - SQL injection prevention

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher (PHP 8.2 recommended)
- MySQL 5.7 or higher
- Modern browser with JavaScript enabled

## Installation

1. Upload the `mobility-trailblazers` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The plugin will automatically:
   - Create necessary database tables
   - Set up custom post types and taxonomies
   - Create user roles and capabilities
   - Initialize default settings

## Configuration

### Initial Setup

1. Navigate to **Mobility Trailblazers → Settings**
2. Configure the following:
   - Current award year
   - Award phases (nomination, evaluation, selection, announcement)
   - Email settings for notifications
   - Public voting options

### User Roles

The plugin creates two custom roles:

- **MT Award Admin** (`mt_award_admin`)
  - Full plugin management capabilities
  - Access to all administrative features
  - Can manage candidates, jury, and settings

- **MT Jury Member** (`mt_jury_member`)
  - Can submit and edit evaluations
  - Access to jury dashboard
  - View assigned candidates only

### Capabilities

Custom capabilities added:
- `mt_manage_awards` - Overall plugin management
- `mt_submit_evaluations` - Submit jury evaluations
- `mt_reset_votes` - Perform vote resets

## Usage

### Managing Candidates

1. Go to **Mobility Trailblazers → Candidates**
2. Add new candidates with:
   - Personal information
   - Company details
   - Innovation description
   - Supporting documentation
3. Set appropriate categories and status

### Jury Management

1. Create jury members at **Mobility Trailblazers → Jury Members**
2. Link to WordPress user accounts
3. Assign candidates using the Assignment Template
4. Monitor evaluation progress

### Public Voting

1. Enable public voting in Settings
2. Add voting form to pages using:
   - Shortcode: `[mt_voting_form]`
   - Elementor widget: "Voting Form"
3. Monitor votes in the admin dashboard

### Displaying Content

Use shortcodes or Elementor widgets to display:
- Candidate grids with filtering
- Jury member profiles
- Voting interfaces
- Statistics and results
- Winner announcements

## Development

### File Structure

```
mobility-trailblazers/
├── admin/
│   └── views/          # Admin interface templates
├── assets/
│   ├── css/           # Stylesheets
│   └── js/            # JavaScript files
├── includes/
│   ├── elementor/     # Elementor integration
│   │   ├── widgets/   # Custom widgets
│   │   └── tags/      # Dynamic tags
│   └── *.php          # Core functionality classes
├── languages/         # Translation files
└── templates/
    └── shortcodes/    # Shortcode templates
```

### Hooks and Filters

The plugin provides numerous hooks for customization:

- `mt_plugin_activated` - Fired on plugin activation
- `mt_before_evaluation_save` - Before saving evaluations
- `mt_after_vote_reset` - After vote reset operations
- `mt_candidates_query_args` - Modify candidate queries

### REST API

Example endpoints:
- `GET /wp-json/mt/v1/candidates` - List candidates
- `POST /wp-json/mt/v1/evaluations` - Submit evaluation
- `GET /wp-json/mt/v1/statistics` - Get statistics

## Troubleshooting

### Common Issues

1. **Missing Capabilities**
   - Run the diagnostic tool
   - Use "Fix Capabilities" option if needed

2. **Database Errors**
   - Check table creation in diagnostic
   - Verify MySQL permissions

3. **Elementor Widgets Not Showing**
   - Ensure Elementor is active
   - Clear Elementor cache

### Diagnostic Tools

Access diagnostics at **Mobility Trailblazers → Diagnostic**:
- Database table checks
- User capability verification
- System requirements validation
- Performance metrics

## Support

For support and documentation:
- Plugin URI: https://mobilitytrailblazers.de
- Documentation: [Coming Soon]
- Support Email: [Configure in Settings]

## Changelog

### Version 1.0.2
- Initial public release
- Complete award management system
- Elementor Pro integration
- Comprehensive admin tools

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

## Credits

Developed by Nicolas Estrém for the Mobility Trailblazers Award Platform.

---

*This plugin is designed to streamline the entire award selection process, from candidate submission through final winner announcement, providing a professional and efficient platform for recognizing innovation in mobility.*
