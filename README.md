# Mobility Trailblazers - Award Management Platform

**Version:** 2.2.27
**Requires:** WordPress 5.8+, PHP 7.4+
**License:** GPL v2 or later
**Last Updated:** August 2025

A modern WordPress plugin for managing mobility innovation awards in the DACH region. This platform enables jury members to evaluate candidates through a sophisticated scoring system while providing administrators with powerful management tools.

## 🎯 Overview

Mobility Trailblazers is a complete award management solution designed for recognizing pioneers in mobility transformation. The platform focuses on jury-based evaluation with a streamlined, professional interface.

## 📁 Project Structure

```
E:\OneDrive\CoWorkSpace\Tech Stack\
├── 📁 Platform/
│   ├── 📁 plugin/                       # Current active plugin
│   │   └── 📁 mobility-trailblazers/    # Main plugin code
│   │       ├── 📁 assets/               # CSS, JS, images
│   │       ├── 📁 includes/             # PHP classes
│   │       ├── 📁 templates/            # HTML templates
│   │       ├── 📁 languages/            # Translation files
│   │       ├── 📁 doc/                  # Plugin documentation
│   │       ├── mobility-trailblazers.php # Main plugin file
│   │       └── README.md                # This file
│   ├── 📁 legacy/                       # Previous versions
│   └── 📁 infrastructure/               # Docker, configs, keys
├── 📁 Documentation/
│   ├── 📁 Project-Management/           # Business docs, reports
│   ├── 📁 Technical/                    # Architecture, guides
│   └── 📁 User-Manual/                  # End-user documentation
├── 📁 Backups/
│   └── 📁 Database/                     # Database backups
├── 📁 Assets/
│   └── 📁 Templates/                    # Templates, configs
├── 📁 Archive/                          # Historical files
└── 📁 VPN/                              # VPN configurations
```

## ✨ Key Features

### 🏆 Award Management
- **Candidate Profiles**: Comprehensive profiles with photos, biographies, and achievements
- **Category Management**: Organize candidates by award categories
- **Multi-criteria Evaluation**: 0-10 scoring system (with 0.5 increments) across 5 key innovation criteria

### 👥 Jury System
- **Modern Dashboard**: Responsive interface with 2x5 grid rankings display
- **Inline Evaluation Controls**: Direct score adjustment without page navigation
- **Real-time Rankings**: Dynamic rankings that update automatically
- **5-Criteria Evaluation System**: 
  - Courage & Pioneer Spirit
  - Innovation Degree
  - Implementation & Impact
  - Mobility Transformation Relevance
  - Role Model & Visibility
- **Progress Visualizations**: SVG-based circular progress indicators
- **Draft Support**: Save evaluations as drafts before final submission
- **Real-time Search**: Filter and find assigned candidates instantly

### 🛠️ Administration
- **Assignment Management**: Flexible candidate-to-jury assignment system
  - Auto-assignment with balanced or random distribution
  - Support for up to 50 candidates per jury member
  - Option to clear existing assignments before reassigning
  - Manual assignment with checkbox selection
- **Bulk Operations**: Comprehensive bulk actions for evaluations, assignments, and candidates
  - Bulk approve/reject evaluations
  - Bulk remove/reassign assignments
  - Bulk status changes for candidates
  - Category management in bulk
- **Import/Export**: CSV support for data management
- **Settings Control**: Evaluation criteria weights and dashboard customization
- **Statistics Dashboard**: Real-time insights into evaluation progress
- **Diagnostics Tools**: Comprehensive debugging and performance monitoring

## 📦 Installation

### Requirements
- WordPress 5.8+, PHP 7.4+, MySQL 5.7+
- Modern browser (Chrome 90+, Firefox 88+, Safari 14+, Edge 90+)

### Quick Installation
1. Upload the `mobility-trailblazers` folder to `/wp-content/plugins/`
2. Activate through the 'Plugins' menu in WordPress
3. The plugin automatically creates database tables and sets up user roles
4. Visit **Mobility Trailblazers** → **Diagnostics** to verify installation

*For detailed installation instructions, see [Developer Guide](doc/developer-guide.md)*

## 🚀 Quick Start

### Initial Setup
1. **Configure Settings** → Set evaluation criteria weights and dashboard customization
   - **Data Management**: Choose whether to preserve or delete all plugin data on uninstall
   - **Criteria Weights**: Adjust importance of each evaluation criterion
   - **Dashboard Appearance**: Customize colors, layouts, and display options
2. **Create Award Categories** → Add categories like "Innovation Leader", "Sustainability Champion"
3. **Add Candidates** → Fill in details, assign categories, upload photos
4. **Setup Jury Members** → Create WordPress users and link to jury member profiles
5. **Assign Candidates** → Use manual or auto-assignment features
6. **Customize Dashboard** → Configure visual appearance and layout preferences

### For Jury Members
1. **Access Dashboard**: Use the `[mt_jury_dashboard]` shortcode
2. **View Rankings**: See real-time rankings in responsive grid layout
3. **Evaluate Candidates**: Use inline controls or detailed evaluation forms
4. **Track Progress**: Monitor completion with visual indicators

*For detailed setup instructions, see [Developer Guide](doc/developer-guide.md)*

## 🏗️ Architecture

The plugin follows modern PHP architecture with PSR-4 autoloading, Repository pattern, Service layer, and structured AJAX communication.

**Key Components:**
- **Core**: Plugin initialization and WordPress integration
- **Repositories**: Data access layer with optimized queries
- **Services**: Business logic and validation
- **AJAX Handlers**: Real-time features and form processing
- **Templates**: Admin and frontend interfaces

*For detailed architecture documentation, see [Architecture Guide](doc/developer-guide.md)*

## 🛡️ Security

The plugin implements comprehensive security measures including prepared statements, nonce verification, capability-based access control, input sanitization, and CSRF protection.

*For detailed security information, see [Architecture Guide](doc/mt-architecture-docs.md#security-architecture)*

## 🔧 Configuration

### Available Shortcodes

- **`[mt_jury_dashboard]`** - Jury member dashboard with evaluation interface
- **`[mt_candidates_grid]`** - Public candidate display grid with filtering options
- **`[mt_evaluation_stats]`** - Evaluation statistics (admin only)
- **`[mt_winners_display]`** - Top-scored candidates display

*For detailed shortcode parameters and examples, see [Developer Guide](doc/developer-guide.md)*

### Hooks & Filters

The plugin provides extensive hooks and filters for customization including `mt_evaluation_criteria`, `mt_evaluation_submitted`, and `mt_evaluation_validate`.

*For complete hooks and filters documentation, see [Developer Guide](doc/mt-developer-guide.md#adding-hooks--filters)*

## 📊 User Roles & Capabilities

- **Jury Member**: View assigned candidates, submit evaluations, save drafts
- **Administrator**: Full system access including candidate management, assignments, and settings

*For detailed capabilities documentation, see [Developer Guide](doc/developer-guide.md)*

## 🌐 Internationalization

Fully translatable with German (primary) and English support. Text domain: `mobility-trailblazers`

## 🐛 Troubleshooting

### Quick Fixes
- **Database Issues**: Use Diagnostics page to verify installation
- **Assignment Problems**: Check jury member linking and browser console
- **Evaluation Saving**: Verify AJAX endpoints and user capabilities
- **Performance**: Clear caches and check server response times

### Diagnostics Tools
- **Admin Diagnostics Page**: Mobility Trailblazers → Diagnostics
- **Error Logs**: WordPress debug logs for plugin-specific errors
- **Browser Console**: Monitor for JavaScript and AJAX errors

*For detailed troubleshooting guide, see [Developer Guide](doc/developer-guide.md)*

## 📝 Recent Updates

### Version 2.2.14 (August 2025)
- **Auto-Assignment Fix**: Resolved issue where auto-assignment failed with existing assignments
- **Clear Assignments Option**: Added checkbox to optionally clear all assignments before auto-assigning
- **Increased Capacity**: Maximum candidates per jury member increased from 20 to 50
- **Improved Defaults**: Default candidates per jury changed from 5 to 10 for better distribution

### Version 2.2.1 (August 2025)
- **Auto-Assignment Algorithm Fix**: Complete refactoring of jury assignment distribution
- **Balanced Distribution**: Fair and even candidate distribution with assignment tracking
- **True Random Distribution**: Proper randomization with performance improvements
- **Enhanced Logging**: Detailed debugging information for assignment operations

### Version 2.2.0 (August 2025)
- **Enhanced CSV Import System**: Complete bulk import functionality with intelligent field mapping
- **Bilingual Support**: Automatic recognition of English and German CSV headers
- **Import Validation**: Dry-run mode, URL validation, and duplicate detection
- **CSV Formatter Tool**: Standalone utility for data preparation

### Version 2.0.13 (July 2025)
- **📁 Project Organization**: Restructured Tech Stack folder for better organization
- **📚 Documentation Update**: Improved README with clear folder structure
- **🔧 Code Organization**: Better file structure and namespace consistency
- **🧹 Cleanup**: Systematic file organization and improved project navigation

### Version 2.0.12 (July 2025)
- **2x5 Grid Layout System**: Responsive rankings display with inline evaluation controls
- **Enhanced User Experience**: Direct score adjustment without page navigation
- **Performance Optimization**: Improved efficiency and AJAX-powered updates

### Version 2.0.0 (June 2025)
- Complete rebuild with modern architecture and Repository pattern
- Focus on jury evaluation system
- Modern, responsive UI with improved security

*For complete changelog, see [Changelog](doc/changelog.md)*

## 📚 Documentation

### Core Documentation
- **[Developer Guide](doc/developer-guide.md)** - Development, customization, and troubleshooting
- **[Architecture Documentation](doc/developer-guide.md)** - Technical architecture and security
- **[Customization Guide](doc/developer-guide.md)** - Dashboard and interface customization
- **[Changelog](doc/changelog.md)** - Version history and updates

### Feature Documentation
- **[Grid Layout System](doc/developer-guide.md)** - Implementation details
- **[Inline Evaluation System](doc/developer-guide.md)** - Inline controls and AJAX
- **[Rankings System](doc/developer-guide.md)** - Rankings display system
- **[Error Handling](doc/developer-guide.md)** - Error management and logging

### Project Documentation
Located in `../../Documentation/`:
- **Project-Management/**: Business documentation, reports, progress tracking
- **Technical/**: Technical guides, implementation details
- **User-Manual/**: End-user documentation and guides

### Documentation Hierarchy
```
README.md (Overview & Quick Start)
├── Developer Guide (Development & Troubleshooting)
├── Architecture Docs (Technical Details & Security)
├── Customization Guide (UI Customization)
├── Feature Docs (Implementation Specifics)
└── Project Docs (Business & Management)
```

## 🚀 Development Workflow

### Working with Current Plugin
1. **Main development** happens in `Platform/plugin/mobility-trailblazers/`
2. **Documentation** is in both local `doc/` and project-wide `Documentation/`
3. **Testing** using local WordPress setup or staging environment
4. **Version control** with Git in the main plugin directory

### File Locations
- **Plugin Code**: `Platform/plugin/mobility-trailblazers/`
- **Documentation**: `Documentation/` (project-wide) and `doc/` (plugin-specific)
- **Backups**: `Backups/Database/`
- **Assets**: `Assets/Templates/`
- **Legacy Code**: `Platform/legacy/`

## 🤝 Support

### Getting Help
1. Check documentation in `/doc/` folder (plugin-specific)
2. Review project documentation in `../../Documentation/` folder
3. Use Diagnostics page for system health checks
4. Review WordPress debug logs for errors
5. Include WordPress/PHP versions when reporting issues

### Development Support
- **Setup Guide**: See [Developer Guide](doc/developer-guide.md)
- **Architecture**: See [Architecture Documentation](doc/developer-guide.md)
- **Troubleshooting**: Common issues and solutions in documentation

## 📄 License

GPL v2 or later. See the LICENSE file.

## 🚀 Development Status

**Current Version: 2.2.27** - Production-ready with active development

**Browser Compatibility:** Chrome 90+, Firefox 88+, Safari 14+, Edge 90+

---

**Developed for the Mobility Trailblazers initiative** - Recognizing pioneers in mobility transformation across the DACH region.

*Last updated: August 2025 | Version 2.2.22*
