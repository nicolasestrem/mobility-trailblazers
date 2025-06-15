# Mobility Trailblazers Award System

A comprehensive WordPress plugin for managing the prestigious "25 Mobility Trailblazers in 25" award platform, designed to recognize and celebrate the most innovative mobility shapers in the DACH (Germany, Austria, Switzerland) region.

## 📋 Table of Contents

- [Overview](#-overview)
- [Key Features](#-key-features)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [User Guides](#-user-guides)
- [Recent Updates](#-recent-updates)
- [API Reference](#-api-reference)
- [Troubleshooting](#-troubleshooting)
- [Security](#-security)
- [Contributing](#-contributing)

## 🚀 Overview

The Mobility Trailblazers Award System is an enterprise-grade WordPress plugin that provides complete digital infrastructure for managing a multi-stage award selection process. Built with modern PHP practices and designed for scalability, it handles everything from candidate nominations through jury evaluations to public announcements.

### Project Vision
To create a transparent, efficient, and engaging platform that identifies and celebrates the 25 most impactful mobility innovators who are shaping the future of transportation and urban mobility in the DACH region.

### Key Statistics
- **490+ Candidates**: Nominated across various mobility sectors
- **22 Expert Jury Members**: Industry leaders and innovation experts
- **3 Award Categories**: Comprehensive coverage of the mobility ecosystem
- **5 Evaluation Criteria**: Holistic assessment framework (50 points maximum)
- **Multi-Phase Process**: 200→50→25 candidate evaluation leading to October 30, 2025 ceremony

## 🎯 Key Features

### 1. Comprehensive Candidate Management
- **Detailed Profiles**: Company, position, location, contact details, innovation documentation
- **Impact Metrics**: Quantifiable achievements and KPIs
- **Media Management**: Photos, videos, and presentation materials
- **Advanced Search & Filtering**: Multi-parameter search with sorting options
- **Category Classification**: Automatic and manual categorization
- **Status Tracking**: From nomination through final selection

### 2. Sophisticated Jury System
- **Profile Management**: Expertise areas, biography, credentials
- **Role-Based Access**: President, Vice-President, Members
- **Assignment Algorithms**: Intelligent candidate distribution with workload balancing
- **Conflict Management**: Prevents conflicts of interest

#### 5-Criteria Scoring System (1-10 points each):
1. **Mut & Pioniergeist** (Courage & Pioneer Spirit)
2. **Innovationsgrad** (Degree of Innovation)
3. **Umsetzungskraft & Wirkung** (Implementation & Impact)
4. **Relevanz für Mobilitätswende** (Mobility Transformation Relevance)
5. **Vorbildfunktion & Sichtbarkeit** (Role Model & Visibility)

### 3. Advanced Assignment Management
- **Visual Interface**: Drag-and-drop candidate-to-jury matching
- **Multiple Assignment Algorithms**: Balanced distribution, expertise-based matching, random assignment
- **Real-Time Updates**: Live assignment status with complete audit trail
- **Bulk Operations**: Efficient mass assignments with undo/redo functionality

### 4. Multi-Interface Dashboard System
- **Admin Dashboard**: Complete system overview with user management and configuration
- **Jury Dashboard (Admin Panel)**: Personal assignment view with evaluation interface
- **Jury Dashboard (Frontend)**: Public-facing, mobile-responsive interface with offline capability
- **Auto-Save Feature**: Never lose progress with automatic saving

### 5. Elementor Page Builder Integration
- **Custom Widgets**: MT Jury Dashboard, MT Candidate Grid, MT Evaluation Statistics
- **Live Preview**: Real-time changes with style customization
- **Responsive Controls**: Device-specific settings with dynamic content

### 6. Vote Reset System (Complete Implementation)
- **Multi-Level Reset Options**: Individual, bulk user, bulk candidate, phase transition, full system
- **Data Integrity & Safety**: Soft delete architecture with comprehensive backup system
- **Audit Trail**: Complete logging with IP tracking and user agents
- **Transaction Support**: Database consistency guaranteed
- **REST API Integration**: Complete endpoint coverage for all operations

### 7. Enhanced Jury Management System
- **Advanced Profiles**: Extended information fields with organization tracking
- **Automated User Management**: One-click WordPress user creation with role assignment
- **Communication Hub**: Built-in email system with customizable templates
- **Data Management**: Export functionality with advanced filtering
- **Performance Tracking**: Individual completion rates and activity monitoring

### 8. Backup & Recovery System
- **Comprehensive Backup Management**: Real-time statistics with manual backup creation
- **Backup History Viewer**: Modal display with individual restore capabilities
- **Export Functionality**: JSON and CSV formats with automatic file download
- **Browser-Based UI**: No external dependencies required

## 🔧 Installation

### Prerequisites
- **PHP**: 8.2+ (7.4 minimum)
- **WordPress**: 5.8+
- **MySQL/MariaDB**: 5.7+/10.3+
- **Memory Limit**: 256MB minimum
- **Redis**: 7.0+ (optional, for caching)

### Docker Installation (Recommended)

1. **Clone and Configure**
   ```bash
   git clone https://github.com/your-org/mobility-trailblazers.git
   cd mobility-trailblazers
   cp .env.example .env
   ```

2. **Deploy with Docker**
   ```bash
   cd /mnt/dietpi_userdata/docker-files/STAGING/
   docker-compose up -d
   ```

3. **Install Plugin**
   ```bash
   docker cp ./mobility-trailblazers mobility_wordpress_STAGING:/var/www/html/wp-content/plugins/
   docker exec mobility_wordpress_STAGING chown -R www-data:www-data /var/www/html/wp-content/plugins/mobility-trailblazers
   docker exec mobility_wpcli_STAGING wp plugin activate mobility-trailblazers
   ```

4. **Database Setup**
   ```bash
   docker exec -i mobility_mariadb_STAGING mariadb -u root -pRt9mK3nQ8xY7bV5cZ2wE4rT6yU1i wordpress_db < /mnt/dietpi_userdata/docker-files/STAGING/mysql-init/02-vote-reset-tables.sql
   ```

### Manual Installation
1. Upload plugin ZIP file via WordPress Admin → Plugins → Add New
2. Activate the plugin
3. Run the setup wizard at MT Award System → Setup

### Post-Installation
```bash
# Configure basic settings
docker exec mobility_wpcli_STAGING wp option update mt_current_award_year 2025
docker exec mobility_wpcli_STAGING wp rewrite structure '/%postname%/'
docker exec mobility_wpcli_STAGING wp rewrite flush
```

## ⚙️ Configuration

### Plugin Settings
Navigate to **MT Award System → Settings**:

- **General Settings**: Award year, phase, public voting, registration status
- **Evaluation Settings**: Criteria weights, minimum evaluations, deadlines, auto-reminders
- **Email Settings**: SMTP configuration, templates, sender details
- **Display Settings**: Pagination, date format, language preferences

### User Roles & Capabilities

#### Administrator
- Full system access including user management and system configuration

#### MT Award Admin
- Award-specific administration: candidate/jury management, assignments, evaluation oversight

#### MT Jury Member
- Jury-specific access: view assigned candidates, submit evaluations, access dashboard

### Custom Capabilities
```php
// Candidate Management
'edit_mt_candidate', 'read_mt_candidate', 'delete_mt_candidate'
'edit_mt_candidates', 'edit_others_mt_candidates', 'publish_mt_candidates'

// Jury Management
'edit_mt_jury', 'read_mt_jury', 'delete_mt_jury', 'manage_mt_jury_members'

// Evaluation Capabilities
'mt_submit_evaluations', 'mt_view_candidates', 'mt_access_jury_dashboard'

// Administrative Capabilities
'mt_manage_awards', 'mt_manage_assignments', 'mt_view_all_evaluations'
```

## 📚 User Guides

### For Administrators

#### Initial Setup Workflow
1. Configure award settings (year, phases, criteria)
2. Import candidates (CSV bulk upload or manual creation)
3. Setup jury members with expertise areas
4. Configure assignments using preferred algorithm
5. Monitor progress and send reminders

#### Managing Evaluations
- Access **MT Award System → Voting Results**
- Filter by category, jury member, or score
- Export results for analysis
- Send targeted reminders

### For Jury Members

#### Getting Started
1. Receive login credentials via email
2. Complete profile information
3. Access dashboard via admin menu or frontend page

#### Evaluation Process
1. Review assigned candidate profiles and supporting materials
2. Score each criterion using 1-10 scale with scoring guidelines
3. Add private notes documenting reasoning
4. Submit evaluation (can edit until deadline)
5. Track progress and monitor deadlines

#### Best Practices
- Apply criteria uniformly for consistent scoring
- Submit evaluations before deadlines
- Document reasoning in detailed notes
- Maintain objectivity and avoid conflicts of interest

### For Candidates

#### Nomination Process
1. Complete application form with innovation details
2. Provide quantifiable impact metrics and supporting documents
3. Create compelling narrative with professional presentation
4. Monitor application status and respond to requests

## 📈 Recent Updates

### Version 1.0.1 (June 15, 2025) - Major Updates

#### 🚀 Vote Reset System - Complete Implementation
- **Multi-Level Reset Options**: Individual, bulk, phase transition, full system reset
- **Data Safety**: Soft delete architecture with automatic backups before any reset
- **Audit Trail**: Complete logging with IP tracking and user attribution
- **REST API Integration**: Full endpoint coverage for all reset operations
- **Professional UI**: Admin interface with real-time statistics and progress tracking

#### 🎯 Enhanced Jury Management System
- **Advanced Profiles**: Extended fields including organization, position, LinkedIn, biography
- **Automated User Management**: One-click WordPress user creation with proper role assignment
- **Communication System**: Built-in email system with customizable templates and bulk messaging
- **Data Export**: CSV export with UTF-8 BOM for Excel compatibility
- **Performance Tracking**: Individual completion rates and activity monitoring

#### 🔐 Backup & Recovery System
- **Comprehensive Backup Management**: Real-time statistics with manual backup creation
- **History Viewer**: Modal interface showing all backups with individual restore options
- **Export Capabilities**: JSON/CSV export with automatic file download
- **Browser-Based UI**: No external dependencies, works with native browser dialogs

#### 🐛 Code Cleanup & Bug Fixes
- Fixed duplicate menu registration issues
- Consolidated evaluation counting functions
- Removed ~200 lines of duplicate code
- Improved code organization and maintainability
- Enhanced security recommendations for Docker configuration

#### 📊 Data Management Enhancements
- Fixed non-working data management buttons in Assignment Management
- Added comprehensive progress tracking modal
- Implemented system synchronization functionality
- Enhanced assignment reset with double confirmation
- Added proper error handling and user notifications

### Technical Metrics
- **Total Files**: 15+ new/modified files
- **Lines of Code**: ~6,000+ lines added
- **Database Tables**: 3 new tables, 2 modified with soft delete support
- **API Endpoints**: 6 new REST endpoints
- **UI Components**: 8 major interface sections

## 🔌 API Reference

### REST API Endpoints

#### Authentication
All API requests require authentication via WordPress Application Passwords, JWT tokens, or OAuth.

#### Candidates Endpoint
```
GET    /wp-json/mt/v1/candidates
GET    /wp-json/mt/v1/candidates/{id}
POST   /wp-json/mt/v1/candidates
PUT    /wp-json/mt/v1/candidates/{id}
DELETE /wp-json/mt/v1/candidates/{id}
```

#### Vote Reset Endpoints
```
POST /wp-json/mobility-trailblazers/v1/reset-vote
POST /wp-json/mobility-trailblazers/v1/admin/bulk-reset
GET  /wp-json/mobility-trailblazers/v1/reset-history
```

#### Evaluations Endpoint
```
GET  /wp-json/mt/v1/evaluations
POST /wp-json/mt/v1/evaluations
PUT  /wp-json/mt/v1/evaluations/{id}
```

### PHP Hooks

#### Actions
```php
// Evaluation hooks
do_action('mt_before_evaluation_save', $evaluation_data, $candidate_id, $jury_member_id);
do_action('mt_after_evaluation_save', $evaluation_id, $evaluation_data);
do_action('mt_evaluation_completed', $candidate_id, $jury_member_id, $total_score);

// Assignment hooks
do_action('mt_before_candidate_assignment', $candidate_id, $jury_member_id);
do_action('mt_after_candidate_assignment', $candidate_id, $jury_member_id);

// Reset hooks
do_action('mt_before_vote_reset', $reset_type, $affected_data);
do_action('mt_after_vote_reset', $reset_id, $reset_data);
```

#### Filters
```php
// Data filters
add_filter('mt_evaluation_data', 'function_name', 10, 3);
add_filter('mt_candidates_query_args', 'function_name', 10, 1);
add_filter('mt_jury_dashboard_data', 'function_name', 10, 2);

// Display filters
add_filter('mt_candidate_card_html', 'function_name', 10, 2);
add_filter('mt_evaluation_form_fields', 'function_name', 10, 1);
```

## 🔍 Troubleshooting

### Common Issues

#### Installation Issues
```bash
# Check PHP version
docker exec mobility_wordpress_STAGING php -v

# Check WordPress version
docker exec mobility_wpcli_STAGING wp core version

# Verify database tables
docker exec mobility_wpcli_STAGING wp db query "SHOW TABLES LIKE 'wp_mt_%'"
```

#### Menu and Navigation Issues
```bash
# Clear caches
docker exec mobility_redis_STAGING redis-cli FLUSHALL
docker exec mobility_wpcli_STAGING wp cache flush

# Check user capabilities
docker exec mobility_wpcli_STAGING wp user list-caps {user_id}

# Reset user role
docker exec mobility_wpcli_STAGING wp user set-role {user_id} mt_jury_member
```

#### Evaluation Issues
```bash
# Run consistency check
docker exec mobility_wpcli_STAGING wp eval '
if (class_exists("MT_Jury_Consistency")) {
    $consistency = MT_Jury_Consistency::get_instance();
    $issues = $consistency->check_sync_issues();
    print_r($issues);
}'

# Force sync
docker exec mobility_wpcli_STAGING wp eval 'do_action("mt_sync_all_evaluations");'
```

### Debug Mode
Enable debug mode for detailed logging:
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('MT_DEBUG', true);
```

### System Diagnostic Tool
Access comprehensive diagnostics at: **Admin → MT Award System → Diagnostic**

## 🛡️ Security

### Security Features
- **Data Protection**: Input sanitization, output escaping, SQL injection prevention
- **Access Control**: Role-based permissions, IP restrictions, session management
- **API Security**: Authentication required, rate limiting, input validation
- **Audit Trail**: Complete logging of all actions with IP tracking

### Security Best Practices
1. **Regular Updates**: Keep WordPress core, plugins, and themes updated
2. **Strong Passwords**: Minimum 12 characters with complexity requirements
3. **File Permissions**: Proper directory and file permissions
4. **Database Security**: Change default table prefix, regular backups, restricted privileges
5. **Monitoring**: Activity logs, failed login attempts, file change detection

### Security Audit Checklist
- [ ] All user inputs sanitized
- [ ] Database queries use prepared statements
- [ ] File uploads restricted and validated
- [ ] Admin area protected with SSL
- [ ] Strong password policy enforced
- [ ] Regular security updates applied
- [ ] Backups configured and tested
- [ ] Activity monitoring enabled



### Coding Standards
- Follow WordPress Coding Standards
- Use PHP CodeSniffer for PHP code
- ES6+ syntax for JavaScript
- PHPDoc comments for documentation

### Testing
```bash
# Run PHPUnit tests
./vendor/bin/phpunit

# Run ESLint
npm run lint

# Run E2E tests
npm run cypress:open
```

### Git Workflow
- Branch naming: `feature/description`, `bugfix/description`, `hotfix/description`
- Commit format: `type(scope): subject`
- Pull request process: feature branch → code review → CI/CD → squash and merge

## 📄 License

This plugin is licensed under the GNU General Public License v2 or later.

## 🙏 Acknowledgments

- **Nicolas Estrém** - Technical Implementation
- **Handelsblatt** - Media Partner
- All jury members and candidates
- Open source community

## 📞 Support

For technical support or questions:
- **Email**: support@mobilitytrailblazers.de

---

**Mobility Trailblazers** - Shaping the future of mobility in the DACH region 🚀

*Last updated: June 15, 2025*
