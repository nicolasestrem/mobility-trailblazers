# Mobility Trailblazers WordPress Plugin

**Version:** 2.5.4  
**Author:** Nicolas Estrem  
**License:** GPL v2 or later  
**WordPress Version:** 5.8+  
**PHP Version:** 7.4+ (8.2+ recommended)  

A comprehensive WordPress plugin for managing the prestigious "25 Mobility Trailblazers in 25" award platform, designed to recognize and celebrate the most innovative mobility shapers in the DACH (Germany, Austria, Switzerland) region.

## 🎯 Overview

The Mobility Trailblazers Award System is an enterprise-grade WordPress plugin that provides complete digital infrastructure for managing a multi-stage award selection process. Built with modern PHP practices and designed for scalability, it handles everything from candidate nominations through jury evaluations to public announcements.

### Mission
To create a transparent, efficient, and engaging platform that identifies and celebrates the 25 most impactful mobility innovators who are shaping the future of transportation and urban mobility in the DACH region.

### Award Statistics
- **490+ Candidates**: Nominated across various mobility sectors
- **24 Expert Jury Members**: Industry leaders and innovation experts  
- **3 Award Categories**: Comprehensive coverage of the mobility ecosystem
- **5 Evaluation Criteria**: Holistic assessment framework (0-10 scale with 0.5 increments)
- **October 30, 2025**: Final award ceremony

## 🚀 Key Features

### Core Functionality
- **Candidate Management**: Custom post type with comprehensive profiles, media management, and bulk operations
- **Jury System**: Role-based access, assignment management, personalized dashboards
- **Evaluation Framework**: 5-criteria scoring system with draft support and progress tracking
- **Assignment Management**: Visual drag-and-drop interface with intelligent auto-assignment algorithms
- **Import/Export System**: Advanced CSV handling with BOM support and bilingual field mapping
- **Dashboard Widgets**: Custom WordPress widgets for jury and admin interfaces

### Recent Enhancements (v2.2.14 - v2.2.28)

#### Security & Reliability (v2.2.28)
- **Enhanced AJAX Security**: Comprehensive nonce verification and permission checks
- **File Upload Validation**: MIME type checking, size limits, malicious content detection
- **Base AJAX Class**: Centralized validation with `validate_upload()` method
- **Database Integrity**: Cleanup methods for orphaned records and data consistency

#### CSV Import Improvements (v2.2.28)
- **BOM Handling**: Automatic detection and removal for Excel compatibility
- **Smart Delimiter Detection**: Supports comma, semicolon, tab, and pipe delimiters
- **Field Mapping**: Case-insensitive with support for alternate field names
- **Bilingual Support**: Recognizes both English and German headers

#### JavaScript Enhancements (v2.2.27-28)
- **Event Delegation**: Improved performance with dynamic content
- **Widget Management**: AJAX-powered widget refresh with loading states
- **Standardized Localization**: Consistent `ajax_url` usage across all scripts
- **Error Handling**: Better user feedback and fallback mechanisms

#### Import System Consolidation (v2.2.24-25)
- **Unified Architecture**: Consolidated from 7 files to 4 with clear separation
- **MT_Import_Handler**: Single source of truth for all CSV processing
- **Progress Tracking**: Real-time import progress with visual feedback
- **Dual Methods**: Standard form and AJAX-based imports

## 📁 Architecture

### Modern Modular Structure
```
mobility-trailblazers/
├── includes/
│   ├── core/              # MT_Plugin, Activator, Database
│   ├── admin/             # Admin interfaces and columns
│   ├── ajax/              # AJAX handlers with base class
│   ├── repositories/      # Data access layer
│   ├── services/          # Business logic
│   ├── widgets/           # Dashboard widgets
│   └── utilities/         # Helper functions
├── templates/             # Frontend templates
├── assets/               
│   ├── css/              # Stylesheets
│   └── js/               # JavaScript files
├── languages/            # i18n support (German/English)
└── doc/                  # Comprehensive documentation
```

### Database Schema
```sql
-- Core WordPress Tables (Extended)
wp_posts (mt_candidate, mt_jury_member)
wp_postmeta (candidate/jury metadata)

-- Custom Plugin Tables
wp_mt_evaluations (5 criteria scores, comments, status)
wp_mt_assignments (jury_member_id, candidate_id, assigned_by)
wp_mt_audit_log (comprehensive activity tracking)
wp_mt_error_log (centralized error logging)
```

## 🔧 Installation

### WordPress Installation
1. Upload plugin to `/wp-content/plugins/mobility-trailblazers/`
2. Activate through WordPress Admin → Plugins
3. Run setup wizard at MT Award System → Setup
4. Configure settings and import initial data

### Docker Deployment
The plugin is compatible with Docker-based WordPress installations. Ensure proper file permissions and database configuration when deploying in containerized environments.

## 💼 User Roles & Capabilities

### Role Hierarchy
- **Administrator**: Full system access, all capabilities
- **MT Jury Admin**: Assignment management, evaluation oversight
- **MT Jury Member**: View assignments, submit evaluations

### Key Capabilities
```php
// Candidate Management
'edit_mt_candidate', 'publish_mt_candidates'

// Jury Operations  
'mt_submit_evaluations', 'mt_access_jury_dashboard'

// Administrative
'mt_manage_awards', 'mt_manage_assignments'
```

## 📊 Evaluation System

### Five Criteria Framework
1. **Mut & Pioniergeist** (Courage & Pioneer Spirit)
2. **Innovationsgrad** (Degree of Innovation)  
3. **Umsetzungskraft & Wirkung** (Implementation & Impact)
4. **Relevanz für Mobilitätswende** (Mobility Transformation Relevance)
5. **Vorbildfunktion & Sichtbarkeit** (Role Model & Visibility)

### Scoring System
- Scale: 0-10 with 0.5 increments
- Draft saving with auto-save functionality
- Comments and detailed feedback
- Progress tracking and completion status

## 🛠 Development

### Requirements
- PHP 7.4+ (8.2+ recommended)
- WordPress 5.8+
- MySQL 5.7+ / MariaDB 10.3+
- Memory Limit: 256MB minimum
- Node.js 16+ (for development)

### Coding Standards
```bash
# Run PHP CodeSniffer
./vendor/bin/phpcs --standard=WordPress .

# Run PHPUnit tests
./vendor/bin/phpunit

# Build assets
npm run build
```

### Critical Development Rules
- **ALWAYS** verify nonces in AJAX handlers
- **NEVER** remove features without confirmation
- **ALWAYS** use the Repository-Service pattern
- **ALWAYS** check existing code before implementing
- **ALWAYS** update documentation

## 🐛 Troubleshooting

### Diagnostics Tools
- **Admin Panel**: MT Award System → Diagnostics
- **Error Logs**: Check `wp-content/debug.log`
- **Browser Console**: Monitor for JavaScript errors
- **Database Check**: Verify table creation

### Common Issues
1. **Assignment Problems**: Clear cache and check browser console
2. **Import Failures**: Verify CSV format and UTF-8 encoding
3. **Evaluation Saving**: Check AJAX endpoints and capabilities
4. **Performance**: Enable Redis caching if available

## 📚 Documentation

### Core Documentation
- **[Developer Guide](doc/developer-guide.md)** - Architecture and development practices
- **[Changelog](doc/changelog.md)** - Complete version history
- **[General Index](doc/general_index.md)** - File and structure overview

### Project Documentation
Located in `../../Documentation/`:
- **Project-Management/** - Business documents and reports
- **Technical/** - Implementation details and diagrams
- **User-Manual/** - End-user guides

## 🤝 Support & Contributing

### Getting Help
1. Check `/doc/` folder for technical documentation
2. Review Diagnostics page for system health
3. Enable debug mode for detailed logging
4. Contact: support@mobilitytrailblazers.de

### Contributing
1. Fork the repository
2. Create feature branch (`feature/your-feature`)
3. Follow WordPress coding standards
4. Submit pull request with tests

## 🏆 Acknowledgments

- **DACH Mobility Community** - For nominations and support
- **Jury Members** - Industry experts dedicating their expertise
- **WordPress Community** - For the platform and standards

## 📈 Platform Status

**Current Status**: Platform Development Complete ✅

### Completed Features
- ✅ Technical Infrastructure (Docker, Database, Security)
- ✅ Core Plugin Development (v2.2.28)
- ✅ Jury Evaluation System
- ✅ CSV Import/Export with BOM handling
- ✅ Assignment Management with drag-and-drop
- ✅ Multi-language support (German/English)

### Upcoming Milestones
- **August 2025**: Candidate profiles and photography
- **September 2025**: Jury pre-selection workflow
- **October 2025**: Live event integration
- **November 2025**: Post-award archive system

---

**Developed for the Mobility Trailblazers initiative** - Recognizing pioneers in mobility transformation across the DACH region.

*Last updated: August 2025 | Version 2.2.28*
