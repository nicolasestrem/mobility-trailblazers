# Mobility Trailblazers - Award Management Platform

**Version:** 2.0.12
**Requires:** WordPress 5.8+, PHP 7.4+
**License:** GPL v2 or later
**Last Updated:** July 2025

A modern WordPress plugin for managing mobility innovation awards in the DACH region. This platform enables jury members to evaluate candidates through a sophisticated scoring system while providing administrators with powerful management tools.

## 🎯 Overview

Mobility Trailblazers is a complete award management solution designed for recognizing pioneers in mobility transformation. The platform focuses on jury-based evaluation with a streamlined, professional interface.

## ✨ Key Features

### 🏆 Award Management
- **Candidate Profiles**: Comprehensive profiles with photos, biographies, and achievements
- **Category Management**: Organize candidates by award categories
- **Multi-criteria Evaluation**: 5-point scoring system across key innovation criteria

### 👥 Jury System
- **Modern Dashboard**: Responsive interface with 2x5 grid rankings display
- **Inline Evaluation Controls**: Direct score adjustment without page navigation
- **Real-time Rankings**: Dynamic rankings that update automatically
- **5-Criteria Evaluation System**: Courage & Pioneer Spirit, Innovation Degree, Implementation & Impact, Mobility Transformation Relevance, Role Model & Visibility
- **Progress Visualizations**: SVG-based circular progress indicators
- **Draft Support**: Save evaluations as drafts before final submission
- **Real-time Search**: Filter and find assigned candidates instantly

### 🛠️ Administration
- **Assignment Management**: Flexible candidate-to-jury assignment system
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

*For detailed installation instructions, see [Developer Guide](doc/mt-developer-guide.md)*

## 🚀 Quick Start

### Initial Setup
1. **Configure Settings** → Set evaluation criteria weights and dashboard customization
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

*For detailed setup instructions, see [Developer Guide](doc/mt-developer-guide.md)*

## 🏗️ Architecture

The plugin follows modern PHP architecture with PSR-4 autoloading, Repository pattern, Service layer, and structured AJAX communication.

**Key Components:**
- **Core**: Plugin initialization and WordPress integration
- **Repositories**: Data access layer with optimized queries
- **Services**: Business logic and validation
- **AJAX Handlers**: Real-time features and form processing
- **Templates**: Admin and frontend interfaces

*For detailed architecture documentation, see [Architecture Guide](doc/mt-architecture-docs.md)*

## 🛡️ Security

The plugin implements comprehensive security measures including prepared statements, nonce verification, capability-based access control, input sanitization, and CSRF protection.

*For detailed security information, see [Architecture Guide](doc/mt-architecture-docs.md#security-architecture)*

## 🔧 Configuration

### Available Shortcodes

- **`[mt_jury_dashboard]`** - Jury member dashboard with evaluation interface
- **`[mt_candidates_grid]`** - Public candidate display grid with filtering options
- **`[mt_evaluation_stats]`** - Evaluation statistics (admin only)
- **`[mt_winners_display]`** - Top-scored candidates display

*For detailed shortcode parameters and examples, see [Developer Guide](doc/mt-developer-guide.md)*

### Hooks & Filters

The plugin provides extensive hooks and filters for customization including `mt_evaluation_criteria`, `mt_evaluation_submitted`, and `mt_evaluation_validate`.

*For complete hooks and filters documentation, see [Developer Guide](doc/mt-developer-guide.md#adding-hooks--filters)*

## 📊 User Roles & Capabilities

- **Jury Member**: View assigned candidates, submit evaluations, save drafts
- **Administrator**: Full system access including candidate management, assignments, and settings

*For detailed capabilities documentation, see [Developer Guide](doc/mt-developer-guide.md)*

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

*For detailed troubleshooting guide, see [Developer Guide](doc/mt-developer-guide.md)*

## 📝 Recent Updates

### Version 2.0.11 (July 2025)
- **2x5 Grid Layout System**: Responsive rankings display with inline evaluation controls
- **Enhanced User Experience**: Direct score adjustment without page navigation
- **Performance Optimization**: Improved efficiency and AJAX-powered updates

### Version 2.0.0 (January 2024)
- Complete rebuild with modern architecture and Repository pattern
- Removed public voting system dependencies
- Modern, responsive UI with improved security

*For complete changelog, see [Changelog](doc/mt-changelog-updated.md)*

## 📚 Documentation

### Core Documentation
- **[Developer Guide](doc/mt-developer-guide.md)** - Development, customization, and troubleshooting
- **[Architecture Documentation](doc/mt-architecture-docs.md)** - Technical architecture and security
- **[Customization Guide](doc/mt-customization-guide.md)** - Dashboard and interface customization
- **[Changelog](doc/mt-changelog-updated.md)** - Version history and updates

### Feature Documentation
- **[Grid Layout System](doc/5x2-grid-implementation-summary.md)** - Implementation details
- **[Inline Evaluation System](doc/inline-evaluation-system.md)** - Inline controls and AJAX
- **[Rankings System](doc/jury-rankings-system.md)** - Rankings display system
- **[Error Handling](doc/error-handling-system.md)** - Error management and logging

### Documentation Hierarchy
```
README.md (Overview & Quick Start)
├── Developer Guide (Development & Troubleshooting)
├── Architecture Docs (Technical Details & Security)
├── Customization Guide (UI Customization)
└── Feature Docs (Implementation Specifics)
```

## 🤝 Support

### Getting Help
1. Check documentation in `/doc/` folder
2. Use Diagnostics page for system health checks
3. Review WordPress debug logs for errors
4. Include WordPress/PHP versions when reporting issues

## 📄 License

GPL v2 or later

## 🚀 Development Status

**Current Version: 2.0.11** - Production-ready with active development

**Browser Compatibility:** Chrome 90+, Firefox 88+, Safari 14+, Edge 90+

---

**Developed for the Mobility Trailblazers initiative** - Recognizing pioneers in mobility transformation across the DACH region.

*Last updated: July 2025 | Version 2.0.11*