# Mobility Trailblazers Platform - General Index

## 📁 Root Files

- **mobility-trailblazers.php** - Main WordPress plugin file that initializes the platform and defines constants
- **README.md** - Complete documentation with features, installation guide, and development workflow
- **composer.json** - PHP dependency management for development tools and code standards
- **.gitignore** - Defines which files to exclude from version control
- **.gitmodules** - Git submodule configuration for the plugin repository
- **CODING_INSTRUCTIONS** - Development standards and workflow guidelines for the project

## 📁 /includes/ - Core PHP Classes

### Core System Files
- **class-mt-autoloader.php** - PSR-4 autoloader for automatic class loading
- **class-mt-plugin.php** - Main plugin initialization and component registration
- **class-mt-activator.php** - Handles plugin activation setup and database creation
- **class-mt-deactivator.php** - Manages plugin deactivation cleanup tasks
- **class-mt-uninstaller.php** - Complete removal of plugin data when uninstalled

### Administrative Interface
- **admin/class-mt-admin.php** - Admin menu setup and page rendering management
- **admin/class-mt-admin-notices.php** - System for displaying admin notifications
- **admin/class-mt-profile-importer.php** - CSV import functionality for candidate profiles
- **admin/class-mt-enhanced-profile-importer.php** - Advanced profile import with validation

### AJAX Handlers
- **ajax/class-mt-base-ajax.php** - Base class with common AJAX functionality
- **ajax/class-mt-evaluation-ajax.php** - Handles evaluation form submissions via AJAX
- **ajax/class-mt-assignment-ajax.php** - Manages jury-candidate assignment operations
- **ajax/class-mt-admin-ajax.php** - General admin AJAX operations and bulk actions

### Business Logic Services
- **services/class-mt-evaluation-service.php** - Core evaluation processing and calculation
- **services/class-mt-assignment-service.php** - Assignment creation and management logic
- **services/class-mt-import-service.php** - Data import processing and validation

### Data Access Layer
- **repositories/class-mt-evaluation-repository.php** - Database operations for evaluations
- **repositories/class-mt-assignment-repository.php** - Database operations for assignments
- **repositories/class-mt-candidate-repository.php** - Candidate data management

### Core Components
- **core/class-mt-database.php** - Database table creation and management
- **core/class-mt-database-upgrade.php** - Handles database schema updates
- **core/class-mt-logger.php** - Error logging and debugging system
- **core/class-mt-post-types.php** - Registers candidate and jury member post types
- **core/class-mt-taxonomies.php** - Category and tag taxonomy registration
- **core/class-mt-roles.php** - User role and capability management
- **core/class-mt-shortcodes.php** - Platform shortcode implementations
- **core/class-mt-i18n.php** - Internationalization and translation setup

## 📁 /templates/ - Display Templates

### Admin Templates
- **admin/dashboard.php** - Main admin dashboard with statistics
- **admin/candidates.php** - Candidate management interface
- **admin/evaluations.php** - Evaluation review and management
- **admin/assignments.php** - Jury assignment management page
- **admin/import-export.php** - Data import/export interface
- **admin/settings.php** - Plugin configuration settings
- **admin/diagnostics.php** - System health and debugging tools
- **admin/error-monitor.php** - Error log viewer and monitoring
- **admin/import-profiles.php** - Profile import wizard interface

### Frontend Templates
- **frontend/jury-dashboard.php** - Main jury member evaluation interface
- **frontend/jury-evaluation-form.php** - Individual candidate evaluation form
- **frontend/candidate-profile.php** - Public candidate profile display
- **frontend/voting-form.php** - Public voting interface
- **frontend/rankings.php** - Display of current candidate rankings

## 📁 /assets/ - Static Resources

### Stylesheets
- **css/admin.css** - Admin interface styling with brand colors
- **css/frontend.css** - Public-facing interface styles
- **css/jury-dashboard.css** - Specific styling for jury evaluation interface

### JavaScript
- **js/admin.js** - Admin functionality including bulk operations and AJAX
- **js/frontend.js** - Public interface interactions and form handling
- **js/jury-evaluation.js** - Evaluation form validation and submission
- **js/charts.js** - Data visualization for statistics and progress

### Images
- **images/logo.png** - Platform logo and branding assets
- **images/icons/** - Interface icons and UI elements

## 📁 /languages/ - Internationalization

- **mobility-trailblazers.pot** - Translation template file
- **mobility-trailblazers-de_DE.po** - German translations
- **mobility-trailblazers-de_DE.mo** - Compiled German translations

## 📁 /doc/ - Documentation

### Developer Documentation
- **mt-developer-guide.md** - Complete development guide with architecture
- **mt-architecture-docs.md** - Technical architecture and security details
- **mt-customization-guide.md** - UI and dashboard customization instructions
- **mt-changelog-updated.md** - Version history and release notes

### Feature Documentation
- **5x2-grid-implementation-summary.md** - Grid layout system documentation
- **inline-evaluation-system.md** - Inline evaluation controls guide
- **jury-rankings-system.md** - Rankings calculation and display
- **error-handling-system.md** - Error management implementation

## 📁 /debug/ - Development Tools

- **generate-sample-profiles.php** - Creates test candidate data
- **test-profile-system.php** - Profile system testing utilities
- **migrate-candidate-profiles.php** - Database migration tools
- **import-profiles.php** - Bulk profile import functionality

## 📁 /infrastructure/ - Deployment Configuration

### Docker Setup
- **docker/docker-compose.yml** - Container orchestration configuration
- **docker/Dockerfile** - WordPress container definition
- **docker/nginx/default.conf** - Web server configuration

### Komodo Management
- **komodo/stacks.json** - Stack management configuration
- **komodo/deployment.yml** - Deployment automation settings

## 📁 External Folders

### Documentation (../../Documentation/)
- **Project-Management/** - Business documents and stakeholder reports
- **Technical/** - Architecture diagrams and technical specifications
- **User-Manual/** - End-user guides and training materials

### Backups (../../Backups/)
- **Database/** - Regular database backup files
- **Code/** - Version snapshots and rollback points

### Assets (../../Assets/)
- **Templates/** - Communication materials and content templates  
- **Branding/** - Logo files and brand guidelines

## 🔑 Key Integration Points

1. **WordPress Hooks** - Plugin integrates via standard WP action/filter system
2. **AJAX Endpoints** - All AJAX calls route through wp-admin/admin-ajax.php
3. **Database Tables** - Custom tables prefixed with 'mt_' for data isolation
4. **User Roles** - Extends WordPress roles with custom capabilities
5. **Shortcodes** - [mt_jury_dashboard], [mt_voting_form], [mt_rankings]

## 🏗️ Architecture Pattern

The platform follows a **Repository-Service-Controller** pattern:
- **Controllers** (AJAX handlers) - Handle requests and responses
- **Services** - Contain business logic and validation
- **Repositories** - Manage database operations
- **Templates** - Present data to users

## 🔒 Security Features

- Nonce verification on all forms and AJAX calls
- Capability checking for user permissions
- Input sanitization using WordPress functions
- SQL injection prevention via prepared statements
- XSS protection through output escaping

---
*Last Updated: August 2025 | Version 2.2.1*