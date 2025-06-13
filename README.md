# Mobility Trailblazers WordPress Plugin

A comprehensive award management system for the "25 Mobility Trailblazers in 25" award platform, recognizing the best mobility shapers in the DACH region.

## 🚀 Overview

This WordPress plugin provides a complete solution for managing an awards program with:
- **Candidate management** (490+ nominees)
- **Jury evaluation system** (22 jury members)
- **Multi-criteria scoring** (5 criteria, 1-10 scale each)
- **Dashboard consistency** across admin and frontend interfaces
- **Docker-based deployment** with Komodo stack management

## 🏗️ Architecture

### Docker Stack Configuration
```yaml
services:
  wordpress:     # Port 9989 - Main WordPress site
  wpcli:        # WordPress CLI for management
  db:           # MariaDB 11 database (Port 9306)
  redis:        # Redis cache (Port 9191)
  phpmyadmin:   # Database management (Port 9081)
```

### Plugin Structure
```
/wp-content/plugins/mobility-trailblazers/
├── mobility-trailblazers.php      # Main plugin file
├── includes/
│   ├── class-mt-core.php          # Core functionality
│   ├── class-mt-jury-consistency.php  # Dashboard sync handler
│   └── class-mt-ajax-handler.php  # AJAX evaluation handling
├── templates/
│   ├── jury-dashboard.php         # Admin dashboard
│   ├── jury-dashboard-frontend.php # Frontend dashboard
│   └── evaluate-candidate.php     # Evaluation interface
└── assets/
    ├── css/
    └── js/
```

## 📋 Features

### Award Management System
- **Three-phase selection process**: 2000 → 200 → 50 → 25 candidates
- **Category-based evaluation**:
  - Established Companies
  - Start-ups & New Makers
  - Infrastructure/Politics/Public Companies
- **Current Status**: 490+ candidates, 22 jury members

### Jury Evaluation Platform ✅
- **Secure jury member dashboard** with role-based access
- **Real-time evaluation statistics**
- **Five evaluation criteria** (1-10 scale each, 50 points total):
  - Mut & Pioniergeist (Courage & Pioneer Spirit)
  - Innovationsgrad (Innovation Degree)
  - Umsetzungskraft & Wirkung (Implementation & Impact)
  - Relevanz für Mobilitätswende (Mobility Transformation Relevance)
  - Vorbildfunktion & Sichtbarkeit (Role Model & Visibility)
- **100% Dashboard Consistency** between admin and frontend views

### Dashboard Consistency System ✅
- **Unified evaluation counting** across all interfaces
- **Automatic ID synchronization** (jury post IDs → user IDs)
- **Duplicate evaluation handling** with newest-wins strategy
- **Orphaned evaluation preservation** for data integrity
- **Real-time sync status monitoring** with admin notices

## 🔧 Recent Implementation (June 13, 2025)

### Dashboard Consistency Enhancement

#### Problem Solved
- Admin and frontend dashboards showed different evaluation counts
- Some evaluations used jury post IDs (549, 555, 558) instead of user IDs
- Duplicate entries caused database constraint violations
- Jury member deletion orphaned evaluations

#### Solution Implemented

1. **Enhanced Consistency Class** (`class-mt-jury-consistency.php`):
   ```php
   // Unified evaluation counting
   mt_get_user_evaluation_count($user_id)
   mt_has_jury_evaluated($user_id, $candidate_id)
   mt_get_user_evaluation($user_id, $candidate_id)
   ```

2. **Intelligent Sync System**:
   - Detects evaluations using jury post IDs
   - Handles duplicate entries (keeps newest)
   - Preserves orphaned evaluations
   - Transaction-based sync for data integrity

3. **Admin Interface Integration**:
   - Real-time sync status notices
   - One-click sync button
   - Detailed sync reporting
   - Handles jury member additions/deletions

4. **Database Improvements**:
   - Fixed missing `comments` column
   - Standardized on WordPress user IDs
   - Maintains unique constraints
   - Preserves historical data

## 🛠️ Installation & Setup

### 1. Prerequisites
- Docker and Docker Compose
- Komodo for stack management
- Access to server at 192.168.1.7

### 2. Deploy the Stack
```bash
cd /mnt/dietpi_userdata/docker-files/STAGING/
docker-compose up -d
```

### 3. Access Points
- **WordPress**: http://192.168.1.7:9989
- **phpMyAdmin**: http://192.168.1.7:9081
- **Admin Dashboard**: http://192.168.1.7:9989/wp-admin/admin.php?page=mt-jury-dashboard
- **Frontend Dashboard**: Any page with `[mt_jury_dashboard]` shortcode

## 📝 Usage Guide

### For Administrators

#### Managing Jury Members
```bash
# List all jury members
docker exec mobility_wpcli_STAGING wp user list --role=mt_jury_member

# Check evaluation counts
docker exec mobility_wpcli_STAGING wp eval '
$users = get_users(array("role" => "mt_jury_member"));
foreach ($users as $user) {
    $count = mt_get_user_evaluation_count($user->ID);
    echo "User {$user->ID} ({$user->display_name}): {$count} evaluations\n";
}
'
```

#### Sync Evaluation Data
1. Navigate to any MT admin page
2. Look for "Evaluation Data Sync Required" notice
3. Click "Fix Evaluation Data" to sync
4. Or use WP-CLI:
   ```bash
   docker exec mobility_wpcli_STAGING wp eval '
   if (class_exists("MT_Jury_Consistency")) {
       $consistency = MT_Jury_Consistency::get_instance();
       $result = $consistency->sync_all_evaluations();
       echo $result["message"];
   }
   '
   ```

### For Jury Members

1. **Login** with provided credentials
2. **Access Dashboard** via admin menu or frontend page
3. **Review Candidates** in your assigned list
4. **Evaluate** using the 5-criteria scoring system
5. **Track Progress** with real-time statistics

## 🔍 Troubleshooting

### Dashboard Count Mismatch
```bash
# Check for sync issues
docker exec mobility_wpcli_STAGING wp eval '
if (class_exists("MT_Jury_Consistency")) {
    $consistency = MT_Jury_Consistency::get_instance();
    $issues = $consistency->check_sync_issues();
    echo "Sync issues: " . $issues["total"] . "\n";
    echo "- High IDs: " . $issues["high_ids"] . "\n";
    echo "- Orphaned: " . $issues["orphaned"] . "\n";
}
'
```

### Database Integrity Check
```bash
# Check for duplicate evaluations
docker exec mobility_wpcli_STAGING wp eval '
global $wpdb;
$table = $wpdb->prefix . "mt_candidate_scores";
$duplicates = $wpdb->get_results("
    SELECT candidate_id, jury_member_id, COUNT(*) as count
    FROM $table
    GROUP BY candidate_id, jury_member_id, evaluation_round
    HAVING count > 1
");
if ($duplicates) {
    echo "Found " . count($duplicates) . " duplicate entries\n";
} else {
    echo "No duplicates found\n";
}
'
```

### Backup Before Major Operations
```bash
# Full database backup
docker exec mobility_mariadb_STAGING mysqldump -uroot -pRt9mK3nQ8xY7bV5cZ2wE4rT6yU1i wordpress_db > backup_$(date +%Y%m%d_%H%M%S).sql

# Evaluation table only
docker exec mobility_mariadb_STAGING mysqldump -uroot -pRt9mK3nQ8xY7bV5cZ2wE4rT6yU1i wordpress_db wp_mt_candidate_scores > scores_backup_$(date +%Y%m%d_%H%M%S).sql
```

## 🚀 Roadmap

### Phase 1: Foundation ✅
- [x] Core plugin architecture
- [x] Candidate management system
- [x] Jury member management
- [x] Basic evaluation system

### Phase 2: Evaluation System ✅
- [x] Jury dashboard implementation
- [x] 5-criteria evaluation interface
- [x] Assignment management
- [x] Dashboard consistency across all interfaces
- [x] Duplicate evaluation handling
- [x] Orphaned data preservation

### Phase 3: Advanced Features (In Progress)
- [ ] Export functionality for evaluations
- [ ] Advanced analytics dashboard
- [ ] Multi-round evaluation support
- [ ] Email notifications for jury
- [ ] Automated reminder system

### Phase 4: Public Interface (Planned)
- [ ] Public candidate showcase
- [ ] Winner announcement system
- [ ] Media kit generation
- [ ] Social media integration
- [ ] Award ceremony integration (Oct 30, 2025)

## 📊 System Architecture

### Database Schema
```sql
-- Main evaluation table
CREATE TABLE wp_mt_candidate_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidate_id INT NOT NULL,
    jury_member_id INT NOT NULL,
    evaluation_round INT DEFAULT 1,
    courage_score INT,
    innovation_score INT,
    implementation_score INT,
    mobility_relevance_score INT,
    visibility_score INT,
    total_score INT,
    comments TEXT,
    evaluated_at DATETIME,
    UNIQUE KEY unique_evaluation (candidate_id, jury_member_id, evaluation_round)
);
```

### User Roles & Capabilities
- **Administrator**: Full system access
- **MT Admin**: Award management capabilities
- **MT Jury Member**: Limited to assigned candidates

## 🔒 Security Features

- Role-based access control
- Nonce verification on all AJAX calls
- Prepared statements for database queries
- Evaluation data isolation by user
- Secure session handling

## 📝 Shortcodes

### [mt_jury_dashboard]
Displays the jury evaluation dashboard on frontend pages.
- Shows same data as admin dashboard
- Requires user authentication
- Responsive design

### [mt_candidate_grid]
Public candidate display grid.
```
[mt_candidate_grid category="startups" columns="3" limit="12"]
```

### [mt_voting_form]
Public voting interface (future implementation).

## 🤝 Contributing

This is a private project for the Institut für Mobilität, University of St. Gallen.

### Contact
- **Prof. Dr. Andreas Herrmann** - andreas.herrmann@unisg.ch
- **Technical Lead**: Nicolas Estrém

## 🔗 Links

- [Institut für Mobilität](https://mobility.unisg.ch)
- [Smart Mobility Summit](https://smart-mobility-summit.de)
- **Award Ceremony**: October 30, 2025, Berlin
- **Media Partner**: [Handelsblatt](https://handelsblatt.com)

## 📅 Timeline

- **Project Start**: December 2024
- **Evaluation Phase**: January - September 2025
- **Finalist Selection**: September 2025
- **Award Ceremony**: October 30, 2025

---

**Mobility Trailblazers** - Shaping the future of mobility in the DACH region 🚀

*Last updated: June 13, 2025 - Enhanced dashboard consistency with intelligent sync system*