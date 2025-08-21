# Mobility Trailblazers WordPress Plugin

**Version:** 2.5.38
**Author:** Mobility Trailblazers - Nicolas Estrem  
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
- **Debug Center**: Comprehensive diagnostics and maintenance tools for system health monitoring

### Recent Enhancements (v2.5.38 - v2.2.28)

#### CSS Architecture & UI Improvements (v2.5.38)
- **Unified Container System**: Implemented 1200px max-width container for consistent dashboard widget alignment
- **Dashboard Improvements**: Fixed negative margins on rankings header and improved element centering
- **CSS Refactoring**: Removed excessive !important declarations following WordPress best practices
- **Mobile-First Responsive**: Enhanced responsive design with proper breakpoints for all screen sizes
- **Search/Filter Integration**: Added mt-search-input and mt-filter-select to unified container system
- **Production CSS Restoration**: Recovered and restored CSS from production snapshots after corruption

#### Dependency Injection Architecture (v2.5.37)
- **Container System**: Lightweight DI container for service management
- **Service Providers**: Organized service registration and bootstrapping
- **Interface-Based Design**: All services and repositories implement interfaces
- **SOLID Principles**: Complete adherence to SOLID design principles
- **Improved Testability**: Easy mocking and isolation for unit testing
- **Backward Compatibility**: Facade pattern preserves legacy code functionality

#### Code Quality Refactoring (v2.5.37)
- **Email Service Removal**: Complete elimination of email functionality for streamlined operation
- **JavaScript Performance**: Fixed race conditions, memory leaks, and event handler conflicts
- **CSS Consolidation**: Reduced from 40+ files with consolidated hotfixes and optimized loading
- **Debug Logging**: Standardized logging across 17 files with structured MT_Logger implementation
- **Elementor Cleanup**: Removed duplicate widgets and consolidated integration architecture

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
│   ├── core/              # MT_Plugin, Container, Service Provider
│   ├── providers/         # Service provider implementations
│   ├── interfaces/        # Service and repository interfaces
│   ├── admin/             # Admin interfaces and columns
│   ├── ajax/              # AJAX handlers with base class
│   ├── repositories/      # Data access layer (interface-based)
│   ├── services/          # Business logic (DI-enabled)
│   ├── widgets/           # Dashboard widgets
│   ├── legacy/            # Backward compatibility layer
│   └── utilities/         # Helper functions
├── templates/             # Frontend templates
├── assets/               
│   ├── css/              # Stylesheets
│   └── js/               # JavaScript files
├── languages/            # i18n support (German/English)
└── doc/                  # Comprehensive documentation
```

### Dependency Injection Architecture
- **Container**: `MT_Container` manages service lifecycle and dependencies
- **Service Providers**: Organized registration of services and repositories
- **Interfaces**: All major components implement interfaces for flexibility
- **Auto-Resolution**: Automatic dependency injection through reflection
- **Testing Support**: Easy mocking and test double injection

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
- **[Developer Guide](doc/developer-guide.md)** - Complete technical reference, architecture, testing, and troubleshooting
- **[Architecture Overview](doc/ARCHITECTURE.md)** - System architecture and design patterns
- **[Dependency Injection Guide](doc/DEPENDENCY-INJECTION-GUIDE.md)** - DI container usage and patterns
- **[API Reference](doc/API-REFERENCE.md)** - Complete API documentation for all components
- **[Migration Guide](doc/MIGRATION-GUIDE.md)** - Step-by-step migration to DI architecture
- **[Testing Strategies](doc/TESTING-STRATEGIES.md)** - Testing patterns with dependency injection
- **[Import/Export Guide](doc/import-export-guide.md)** - CSV/Excel handling and German localization
- **[Changelog](doc/changelog.md)** - Complete version history (v2.5.37 to v2.2.0)

### Archived Documentation
- **[Archived](doc/archived/)** - Historical documentation and dated fix reports

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

**Current Version**: 2.5.37 (August 20, 2025)
**Status**: Production Ready ✅

### Recent Updates
- ✅ Dependency injection architecture (v2.5.37) - Container, service providers, interfaces
- ✅ Major code quality refactoring (v2.5.37) - Email removal, performance fixes, CSS consolidation
- ✅ Testing infrastructure with PHPUnit and live diagnostics
- ✅ German localization (1000+ strings translated)
- ✅ Security hardening and production cleanup
- ✅ CSS architecture consolidation
- ✅ UI/UX improvements and photo management system

### Upcoming Milestones
- **August 18, 2025**: Critical platform launch deadline
- **September 2025**: Jury pre-selection workflow
- **October 30, 2025**: Final award ceremony
- **November 2025**: Post-award archive system

---

**Developed for the Mobility Trailblazers initiative** - Recognizing pioneers in mobility transformation across the DACH region.

*Last updated: August 20, 2025 | Version 2.5.37*
