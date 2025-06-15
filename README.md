# Mobility Trailblazers Award System

A comprehensive WordPress plugin for managing award candidates, jury members, evaluations, and voting processes. This plugin provides a complete solution for running award competitions with multiple phases, jury evaluations, and administrative oversight.

## 🏗️ Project Structure

The plugin follows a modular, object-oriented architecture with clear separation of concerns:

```
mobility-trailblazers/
├── mobility-trailblazers.php          # Main plugin file (minimal bootstrapping)
├── composer.json                       # Autoloading and dependencies
├── README.md
├── LICENSE
├── .gitignore
│
├── includes/                           # Core plugin functionality
│   ├── class-plugin.php               # Main plugin class (singleton)
│   ├── class-activator.php            # Plugin activation logic
│   ├── class-deactivator.php          # Plugin deactivation logic
│   └── class-loader.php               # Hooks and filters loader
│
├── core/                              # Core business logic
│   ├── abstracts/                     # Abstract classes
│   │   ├── class-abstract-post-type.php
│   │   └── class-abstract-taxonomy.php
│   │
│   ├── interfaces/                    # Interfaces
│   │   ├── interface-registrable.php
│   │   └── interface-hookable.php
│   │
│   ├── traits/                        # Reusable traits
│   │   ├── trait-singleton.php
│   │   └── trait-ajax-handler.php
│   │
│   ├── post-types/                    # Custom post types
│   │   ├── class-candidate-post-type.php
│   │   └── class-jury-post-type.php
│   │
│   ├── taxonomies/                    # Custom taxonomies
│   │   ├── class-category-taxonomy.php
│   │   ├── class-phase-taxonomy.php
│   │   └── class-status-taxonomy.php
│   │
│   └── roles/                         # User roles and capabilities
│       ├── class-roles-manager.php
│       ├── class-jury-role.php
│       └── class-award-admin-role.php
│
├── modules/                           # Feature modules
│   ├── voting/                        # Voting system
│   │   ├── class-voting-manager.php
│   │   ├── class-vote-handler.php
│   │   ├── class-vote-calculator.php
│   │   └── api/
│   │       └── class-voting-api.php
│   │
│   ├── evaluation/                    # Evaluation system
│   │   ├── class-evaluation-manager.php
│   │   ├── class-evaluation-form.php
│   │   ├── class-evaluation-handler.php
│   │   └── class-evaluation-criteria.php
│   │
│   ├── jury/                          # Jury management
│   │   ├── class-jury-manager.php
│   │   ├── class-jury-dashboard.php
│   │   ├── class-jury-assignments.php
│   │   ├── class-jury-notifications.php
│   │   └── class-jury-consistency.php
│   │
│   ├── candidates/                    # Candidate management
│   │   ├── class-candidate-manager.php
│   │   ├── class-candidate-meta.php
│   │   └── class-candidate-display.php
│   │
│   ├── assignments/                   # Assignment system
│   │   ├── class-assignment-manager.php
│   │   ├── class-assignment-algorithm.php
│   │   ├── class-assignment-optimizer.php
│   │   └── class-assignment-exporter.php
│   │
│   ├── reset/                         # Vote reset functionality
│   │   ├── class-reset-manager.php
│   │   ├── class-reset-handler.php
│   │   ├── class-backup-manager.php
│   │   └── class-audit-logger.php
│   │
│   └── reports/                       # Reporting and analytics
│       ├── class-reports-manager.php
│       ├── class-statistics-calculator.php
│       └── class-export-handler.php
│
├── admin/                             # Admin functionality
│   ├── class-admin.php               # Main admin class
│   ├── menus/                        # Admin menus
│   │   ├── class-main-menu.php
│   │   ├── class-jury-menu.php
│   │   ├── class-assignments-menu.php
│   │   ├── class-voting-menu.php
│   │   ├── class-reset-menu.php
│   │   └── class-settings-menu.php
│   │
│   ├── pages/                        # Admin page templates
│   │   ├── dashboard.php
│   │   ├── jury-management.php
│   │   ├── assignments.php
│   │   ├── voting-results.php
│   │   ├── vote-reset.php
│   │   ├── settings.php
│   │   └── diagnostic.php
│   │
│   └── meta-boxes/                   # Meta boxes
│       ├── class-candidate-meta-box.php
│       └── class-jury-meta-box.php
│
├── public/                           # Frontend functionality
│   ├── class-public.php             # Main public class
│   ├── shortcodes/                  # Shortcode handlers
│   │   ├── class-voting-form-shortcode.php
│   │   ├── class-candidate-grid-shortcode.php
│   │   ├── class-jury-members-shortcode.php
│   │   ├── class-voting-results-shortcode.php
│   │   └── class-jury-dashboard-shortcode.php
│   │
│   └── widgets/                     # Widgets
│       └── class-voting-widget.php
│
├── api/                             # REST API endpoints
│   ├── class-api-manager.php
│   ├── endpoints/
│   │   ├── class-voting-endpoints.php
│   │   ├── class-evaluation-endpoints.php
│   │   ├── class-reset-endpoints.php
│   │   ├── class-backup-endpoints.php
│   │   └── class-assignment-endpoints.php
│   │
│   └── controllers/
│       ├── class-voting-controller.php
│       └── class-admin-controller.php
│
├── integrations/                    # Third-party integrations
│   ├── elementor/
│   │   ├── class-elementor-integration.php
│   │   └── widgets/
│   │       ├── class-candidate-grid-widget.php
│   │       ├── class-evaluation-stats-widget.php
│   │       └── class-jury-dashboard-widget.php
│   │
│   └── ajax/
│       ├── class-ajax-manager.php
│       └── class-ajax-fix.php
│
├── database/                        # Database operations
│   ├── class-database-manager.php
│   ├── migrations/
│   │   ├── class-migration-1-0-0.php
│   │   └── class-migration-handler.php
│   │
│   ├── tables/
│   │   ├── class-votes-table.php
│   │   ├── class-scores-table.php
│   │   ├── class-reset-logs-table.php
│   │   └── class-backup-tables.php
│   │
│   └── sql/
│       ├── create-tables.sql
│       └── vote-reset-tables.sql
│
├── assets/                          # Static assets
│   ├── css/
│   │   ├── admin/
│   │   │   ├── admin-global.css
│   │   │   ├── dashboard.css
│   │   │   ├── jury-management.css
│   │   │   ├── assignments.css
│   │   │   └── vote-reset.css
│   │   │
│   │   └── public/
│   │       ├── frontend-global.css
│   │       ├── voting-form.css
│   │       ├── candidate-grid.css
│   │       └── jury-dashboard.css
│   │
│   ├── js/
│   │   ├── admin/
│   │   │   ├── admin-global.js
│   │   │   ├── dashboard.js
│   │   │   ├── jury-management.js
│   │   │   ├── assignments.js
│   │   │   └── vote-reset.js
│   │   │
│   │   └── public/
│   │       ├── frontend-global.js
│   │       ├── voting.js
│   │       └── jury-dashboard.js
│   │
│   ├── images/
│   └── fonts/
│
├── templates/                       # Template files
│   ├── admin/
│   │   ├── dashboard/
│   │   ├── jury/
│   │   ├── assignments/
│   │   └── settings/
│   │
│   ├── public/
│   │   ├── voting/
│   │   ├── candidates/
│   │   └── jury/
│   │
│   └── emails/
│       ├── jury-invitation.php
│       ├── assignment-notification.php
│       └── vote-confirmation.php
│
└── tests/                          # Unit tests
    ├── bootstrap.php
    ├── unit/
    ├── integration/
    └── fixtures/
```

## 🚀 Features

### Core Functionality
- **Candidate Management**: Complete candidate lifecycle management with custom fields
- **Jury Management**: Comprehensive jury member administration and assignment
- **Multi-Phase Voting**: Support for multiple evaluation phases (200→50→25→Winners)
- **Evaluation System**: Detailed scoring across 5 criteria with weighted calculations
- **Assignment System**: Intelligent assignment of candidates to jury members
- **Vote Reset System**: Comprehensive vote reset with backup and audit logging

### Admin Features
- **Dashboard**: Overview of all system statistics and activities
- **Jury Management**: Add, edit, and manage jury members with detailed profiles
- **Assignment Management**: Automated and manual assignment tools
- **Vote Reset Interface**: Safe vote reset with multiple confirmation levels
- **Reporting**: Comprehensive reports and data export capabilities
- **Diagnostic Tools**: System health monitoring and troubleshooting

### Frontend Features
- **Jury Dashboard**: Dedicated interface for jury members
- **Candidate Evaluation**: Intuitive evaluation forms with progress tracking
- **Responsive Design**: Mobile-friendly interface for all user types
- **Shortcodes**: Easy integration with any WordPress theme

## 📋 Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher
- **Memory**: 128MB minimum (256MB recommended)

## 🔧 Installation

1. **Download** the plugin files
2. **Upload** to `/wp-content/plugins/mobility-trailblazers/`
3. **Activate** the plugin through WordPress admin
4. **Configure** settings in MT Award System menu

### Composer Installation

```bash
composer install --no-dev --optimize-autoloader
```

## ⚙️ Configuration

### Initial Setup

1. **User Roles**: The plugin creates custom roles:
   - `mt_jury_member`: For jury members
   - `mt_award_admin`: For award administrators

2. **Post Types**: Custom post types are created:
   - `mt_candidate`: Award candidates
   - `mt_jury`: Jury member profiles

3. **Taxonomies**: Custom taxonomies for organization:
   - `mt_category`: Candidate categories
   - `mt_phase`: Voting phases
   - `mt_status`: Status tracking

### Database Tables

The plugin creates several custom tables:
- `wp_mt_votes`: Voting records
- `wp_mt_candidate_scores`: Detailed evaluation scores
- `wp_vote_reset_logs`: Audit trail for vote resets
- `wp_mt_votes_history`: Backup of reset votes
- `wp_mt_candidate_scores_history`: Backup of reset scores

## 🎯 Usage

### For Administrators

1. **Add Candidates**: Create candidate profiles with detailed information
2. **Manage Jury**: Add jury members and assign them to categories
3. **Create Assignments**: Use the assignment system to distribute candidates
4. **Monitor Progress**: Track evaluation progress through the dashboard
5. **Generate Reports**: Export data and generate comprehensive reports

### For Jury Members

1. **Access Dashboard**: Log in to view assigned candidates
2. **Evaluate Candidates**: Score candidates across 5 criteria
3. **Track Progress**: Monitor evaluation completion status
4. **Reset Votes**: Reset individual votes if needed (with audit trail)

## 🔌 API Endpoints

The plugin provides REST API endpoints for integration:

- `GET /wp-json/mobility-trailblazers/v1/candidates`
- `POST /wp-json/mobility-trailblazers/v1/evaluate`
- `POST /wp-json/mobility-trailblazers/v1/reset-vote`
- `GET /wp-json/mobility-trailblazers/v1/assignments`

## 🎨 Customization

### Hooks and Filters

The plugin provides numerous hooks for customization:

```php
// Modify evaluation criteria
add_filter('mt_evaluation_criteria', 'custom_criteria');

// Customize assignment algorithm
add_filter('mt_assignment_algorithm', 'custom_algorithm');

// Modify email templates
add_filter('mt_email_template', 'custom_email_template');
```

### Template Override

Templates can be overridden in your theme:

```
your-theme/
└── mobility-trailblazers/
    ├── jury-dashboard.php
    ├── candidate-grid.php
    └── evaluation-form.php
```

## 🧪 Testing

Run the test suite:

```bash
composer test
```

Code quality checks:

```bash
composer cs      # Check coding standards
composer cbf     # Fix coding standards
composer analyze # Static analysis
```

## 🔒 Security

- **Nonce Verification**: All forms use WordPress nonces
- **Capability Checks**: Proper permission verification
- **Data Sanitization**: All input is sanitized and validated
- **SQL Injection Prevention**: Prepared statements throughout
- **XSS Protection**: Output escaping and validation

## 📊 Performance

- **Optimized Queries**: Efficient database queries with proper indexing
- **Caching**: Transient caching for expensive operations
- **Lazy Loading**: Components loaded only when needed
- **Asset Optimization**: Minified CSS/JS in production

## 🐛 Troubleshooting

### Common Issues

1. **Menu Not Showing**: Check user permissions and role assignments
2. **Database Errors**: Verify table creation during activation
3. **Assignment Issues**: Check candidate-jury category matching
4. **Email Problems**: Verify WordPress mail configuration

### Debug Mode

Enable debug mode in `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Diagnostic Page

Access the diagnostic page at: `Admin → MT Award System → Diagnostic`

## 📝 Changelog

### Version 2.0.0
- **Complete restructure**: Modular architecture implementation
- **Enhanced security**: Comprehensive security improvements
- **Better performance**: Optimized queries and caching
- **Improved UX**: Modern, responsive interface
- **Advanced features**: Vote reset system, comprehensive reporting

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass
6. Submit a pull request

## 📄 License

This plugin is licensed under the GPL v2 or later.

## 🆘 Support

For support and documentation:
- **Documentation**: [Plugin Documentation](https://example.com/docs)
- **Issues**: [GitHub Issues](https://github.com/example/mobility-trailblazers/issues)
- **Email**: support@example.com

---

**Mobility Trailblazers Award System** - Empowering the future of mobility through recognition and innovation.
