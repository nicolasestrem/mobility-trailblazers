# Mobility Trailblazers Award Platform

> 🏆 **25 Mobility Trailblazers in 25** - Recognizing courage and innovation in mobility transformation across the DACH region

## Overview

The Mobility Trailblazers platform is a comprehensive WordPress-based award system designed to identify, evaluate, and celebrate the top 25 individuals driving mobility transformation in Germany, Austria, and Switzerland (DACH region). This platform serves as both a public communication hub and a secure jury evaluation system.

**Key Mission**: "Weil mobiler Wandel Mut braucht" (Because mobility transformation requires courage)

## 🎯 Project Goals

1. **Individual Recognition** - Honor 25 makers and shapers transforming mobility
2. **Innovation Showcase** - Highlight courageous innovations strengthening DACH's mobility competitiveness  
3. **Transformation Visibility** - Demonstrate the scope of mobility transformation achievements
4. **Award Ceremony** - October 30, 2025, in Berlin

## 🛠 Technical Stack

- **WordPress 6.4** with PHP 8.2
- **MariaDB 11** database
- **Redis** for caching
- **Docker** containerization with Komodo management
- **Custom WordPress Plugin** for award management
- **phpMyAdmin** for database management

### Docker Environment (STAGING)
- WordPress: Port 9989
- MariaDB: Port 9306
- Redis: Port 9191
- phpMyAdmin: Port 9081

## 🚀 Quick Start

### Prerequisites

- Docker and Docker Compose
- 4GB+ RAM recommended
- Port availability: 9989, 9306, 9191, 9081

### Installation

1. Navigate to the project directory:
```bash
cd /mnt/dietpi_userdata/docker-files/STAGING/
```

2. Start the Docker stack:
```bash
docker-compose up -d
```

3. Access the platform:
- WordPress: http://192.168.1.7:9989
- phpMyAdmin: http://192.168.1.7:9081

4. The plugin is already installed and activated at:
```
/wordpress_data/wp-content/plugins/mobility-trailblazers/
```

## 📋 Features

### Award Management System
- **Three-phase selection process**: 2000 → 200 → 50 → 25 candidates
- **Category-based evaluation**:
  - Established Companies
  - Start-ups & New Makers
  - Infrastructure/Politics/Public Companies
- **Current Status**: 490+ candidates, 22 jury members in system

### Jury Evaluation Platform ✅ 
- **Secure jury member dashboard** with personalized access
- **Real-time statistics**: Assigned, Evaluated, Pending, Progress %
- **Individual evaluation interface** with visual scoring system
- **Five evaluation criteria scoring** (1-10 scale):
  - Mut & Pioniergeist (Courage & Pioneer Spirit)
  - Innovationsgrad (Innovation Degree)
  - Umsetzungskraft & Wirkung (Implementation & Impact)
  - Relevanz für Mobilitätswende (Mobility Transformation Relevance)
  - Vorbildfunktion & Sichtbarkeit (Role Model & Visibility)
- **100% Dashboard Consistency** - Admin and frontend dashboards show identical data

### Jury Dashboard Access ✅ 
- **Admin Dashboard**: `http://192.168.1.7:9989/wp-admin/admin.php?page=mt-jury-dashboard`
- **Frontend Dashboard**: Any page with `[mt_jury_dashboard]` shortcode
- **Evaluation Page**: `http://192.168.1.7:9989/wp-admin/admin.php?page=mt-evaluate&candidate=[ID]`

## 🔧 Recent Updates

### Dashboard Consistency Implementation (June 13, 2025)
- ✅ Created unified evaluation counting system
- ✅ Fixed inconsistent jury member ID usage (user IDs vs post IDs)
- ✅ Implemented data synchronization for legacy evaluations
- ✅ Both admin and frontend dashboards now show identical counts

#### Technical Implementation
1. **Consistency Class**: `class-mt-jury-consistency.php`
   - Handles unified evaluation counting
   - Provides data synchronization capabilities
   - Ensures all new evaluations use WordPress user IDs

2. **Unified Functions**:
   - `mt_get_user_evaluation_count($user_id)` - Gets accurate evaluation count
   - `mt_has_jury_evaluated($user_id, $candidate_id)` - Checks evaluation status

3. **Database Improvements**:
   - Fixed missing `comments` column
   - Changed `total_score` from DECIMAL to INT
   - Standardized on WordPress user IDs for all evaluations

### Previous Updates

#### Database Schema Fixes (June 13, 2025)
- ✅ Fixed "Database error. Please try again" issue
- ✅ Added missing `comments` column to scores table
- ✅ Fixed `total_score` data type mismatch
- ✅ Improved AJAX handler field name handling

#### PHP 8.2 Compatibility (Previous Session)
- ✅ Fixed PHP 8.2 deprecation warnings
- ✅ Custom php.ini configuration
- ✅ Must-use plugin for warning suppression

## 📁 Project Structure

```
/mnt/dietpi_userdata/docker-files/STAGING/
├── docker-compose.yml
├── php.ini
├── wordpress_data/
│   ├── wp-config.php
│   └── wp-content/
│       ├── mu-plugins/
│       │   └── suppress-php82-warnings.php
│       └── plugins/
│           └── mobility-trailblazers/
│               ├── mobility-trailblazers.php (Main plugin file)
│               ├── includes/
│               │   ├── class-mt-ajax-fix.php
│               │   ├── class-mt-jury-consistency.php ✨ NEW
│               │   └── class-mt-jury-fix.php
│               └── templates/
│                   ├── jury-dashboard.php (Admin dashboard)
│                   └── jury-dashboard-frontend.php (Frontend shortcode)
```

## 📊 Database Schema

### wp_mt_candidate_scores
```sql
Field                Type           Null    Key     Default    Extra
id                   mediumint(9)   NO      PRI     NULL       auto_increment
candidate_id         bigint(20)     NO      MUL     NULL
jury_member_id       bigint(20)     NO      MUL     NULL       (WordPress user ID)
courage_score        tinyint(2)     NO              0
innovation_score     tinyint(2)     NO              0
implementation_score tinyint(2)     NO              0
visibility_score     tinyint(2)     NO              0
relevance_score      int(11)        NO              0
total_score          int(11)        NO              NULL
comments             text           YES             NULL
evaluation_round     tinyint(1)     NO              1          (unused)
evaluated_at         datetime       NO              NULL
```

## 🔒 Security & Permissions

### Role Hierarchy
- **Administrator**: Full access to all features
- **MT Award Admin**: Custom role with award management capabilities
- **MT Jury Member**: Limited access to assigned candidates only

### Access Control
- Menu items use 'read' capability for jury access
- Permission checks implemented inside page callbacks
- Jury members cannot access other jury members' assignments
- Evaluation data stored securely in custom database table

## 📝 Commands Reference

### Useful WP-CLI Commands
```bash
# Access WP-CLI container
docker exec -it mobility_wpcli_STAGING bash

# List jury members with role
docker exec mobility_wpcli_STAGING wp user list --role=mt_jury_member

# Check evaluation counts for all jury members
docker exec mobility_wpcli_STAGING wp eval '
$jury_users = get_users(array("role" => "mt_jury_member"));
foreach ($jury_users as $user) {
    $count = mt_get_user_evaluation_count($user->ID);
    echo "User {$user->ID} ({$user->display_name}): {$count} evaluations\n";
}
'

# Check for data sync issues
docker exec mobility_wpcli_STAGING wp eval '
global $wpdb;
$table = $wpdb->prefix . "mt_candidate_scores";
$high = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE jury_member_id > 100");
$low = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE jury_member_id <= 100");
echo "High IDs (needs sync): $high, Low IDs (correct): $low\n";
'

# View recent evaluations
docker exec mobility_wpcli_STAGING wp db query "SELECT * FROM wp_mt_candidate_scores ORDER BY evaluated_at DESC LIMIT 10"
```

### Docker Commands
```bash
# Restart WordPress container
docker-compose restart wordpress

# View container logs
docker logs mobility_wordpress_STAGING --tail 50 -f

# Check PHP version and modules
docker exec mobility_wordpress_STAGING php -v
```

## 🐛 Troubleshooting Guide

### Dashboard Count Mismatch
If admin and frontend dashboards show different counts:
1. Check for high ID evaluations (jury post IDs):
   ```bash
   docker exec mobility_wpcli_STAGING wp eval 'global $wpdb; $high = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores WHERE jury_member_id > 100"); echo "High ID evaluations: $high\n";'
   ```
2. Run data synchronization if needed
3. Clear any caching plugins
4. Verify both dashboards use unified functions

### Evaluation Save Errors
1. Check browser console for JavaScript errors
2. Verify AJAX handler accepts both field name variations:
   - `relevance_score` and `mobility_relevance_score`
3. Ensure database has all required columns
4. Check PHP error logs:
   ```bash
   docker logs mobility_wordpress_STAGING --tail 50
   ```

### Jury Access Issues
1. Verify user has `mt_jury_member` role
2. Check if user is linked to a jury member post
3. Run diagnostic tool: `admin.php?page=mt-diagnostic`
4. Verify capabilities:
   ```bash
   docker exec mobility_wpcli_STAGING wp user meta get [user_id] wp_capabilities
   ```

## 📝 Shortcodes Documentation

### [mt_jury_dashboard]
Displays the jury dashboard on frontend pages (requires login).
- Shows evaluation statistics
- Lists assigned candidates
- Provides evaluation links
- **Guaranteed to show same data as admin dashboard**

### [mt_candidate_grid]
Displays a responsive grid of candidates with filtering options.

**Parameters:**
- `category` - Filter by category slug
- `status` - Filter by status: 'longlist', 'shortlist', 'finalist', 'winner'
- `columns` - Number of columns: 2, 3, or 4 (default: 3)
- `show_filters` - Display category filter buttons (default: true)
- `limit` - Maximum number of candidates to display

### [mt_voting_form]
Renders the voting interface for jury members or public voting.

## 🚀 Roadmap

### Phase 1: Foundation ✅
- [x] Core plugin development
- [x] Candidate management system
- [x] Jury member management
- [x] Basic evaluation system

### Phase 2: Evaluation System ✅
- [x] Jury dashboard implementation
- [x] Evaluation interface with 5 criteria
- [x] Assignment management
- [x] Dashboard consistency fixes

### Phase 3: Advanced Features (In Progress)
- [ ] Export functionality for evaluations
- [ ] Advanced analytics dashboard
- [ ] Multi-round evaluation support
- [ ] Email notifications for jury

### Phase 4: Public Interface
- [ ] Public candidate showcase
- [ ] Winner announcement system
- [ ] Media kit generation
- [ ] Social media integration

## 🤝 Contributing

This is a private project. For access or contributions, please contact:
- Prof. Dr. Andreas Herrmann - andreas.herrmann@unisg.ch
- Technical Lead: Nicolas Estrem

## 🔗 Links

- [Institut für Mobilität](https://mobility.unisg.ch)
- [Smart Mobility Summit](https://smart-mobility-summit.de)
- Media Partner: [Handelsblatt](https://handelsblatt.com)

---

**Mobility Trailblazers** - Transforming mobility with courage and innovation in the DACH region 🚀

*Last updated: June 13, 2025 - Dashboard consistency implementation completed*