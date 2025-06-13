# Mobility Trailblazers Award System

WordPress plugin for managing the "25 Mobility Trailblazers in 25" award platform, recognizing mobility innovators in the DACH region.

## 🚀 Overview

A comprehensive jury evaluation system managing:
- **490+ candidates** across 3 categories
- **22 jury members** with secure evaluation interface
- **5-criteria scoring system** (50 points total)
- **Real-time evaluation tracking** and statistics

## 🏗️ Technical Stack

```yaml
# Docker Compose Stack (Managed via Komodo)
services:
  wordpress:    # Port 9989 - Main application
  wpcli:        # WordPress CLI
  db:           # MariaDB 11 (Port 9306)
  redis:        # Redis cache (Port 9191)
  phpmyadmin:   # Database UI (Port 9081)
```

## 📋 Core Features

### Jury Evaluation System
- **Secure Dashboard**: Role-based access for jury members
- **5 Evaluation Criteria** (1-10 scale each):
  - Mut & Pioniergeist (Courage & Pioneer Spirit)
  - Innovationsgrad (Innovation Degree)
  - Umsetzungskraft & Wirkung (Implementation & Impact)
  - Relevanz für Mobilitätswende (Mobility Transformation Relevance)
  - Vorbildfunktion & Sichtbarkeit (Role Model & Visibility)
- **Real-time Progress Tracking**: Live statistics and completion status
- **Multi-interface Access**: Admin panel and frontend dashboard

### Award Categories
1. **Established Companies** - Industry leaders and corporations
2. **Start-ups & New Makers** - Innovative newcomers
3. **Infrastructure/Politics/Public** - Public sector initiatives

## 🔧 Installation & Setup

### 1. Deploy Stack
```bash
cd /mnt/dietpi_userdata/docker-files/STAGING/
docker-compose up -d
```

### 2. Configure Jury Dashboard
1. Create a WordPress page titled "Jury Dashboard"
2. Add shortcode: `[mt_jury_dashboard]`
3. Publish the page

### 3. Access Points
- **WordPress**: http://192.168.1.7:9989
- **Admin Menu**: MT Award System → My Dashboard
- **Frontend Dashboard**: Your configured page URL

## 📝 Admin Quick Reference

### Managing Evaluations
```bash
# Check evaluation statistics
docker exec mobility_wpcli_STAGING wp eval '
$stats = mt_get_evaluation_statistics();
echo "Total Evaluations: " . $stats["total_evaluations"] . "\n";
echo "Active Jury Members: " . $stats["active_jury_members"] . "\n";
'

# Sync evaluation data
docker exec mobility_wpcli_STAGING wp eval '
if (class_exists("MT_Jury_Consistency")) {
    $consistency = MT_Jury_Consistency::get_instance();
    $result = $consistency->sync_all_evaluations();
    echo $result["message"];
}'
```

### Database Maintenance
```bash
# Check for issues
docker exec mobility_wpcli_STAGING wp db query "
SELECT COUNT(*) as duplicate_count 
FROM wp_mt_candidate_scores 
GROUP BY candidate_id, jury_member_id, evaluation_round 
HAVING COUNT(*) > 1
"

# Export evaluations
docker exec mobility_wpcli_STAGING wp db export evaluations_backup.sql --tables=wp_mt_candidate_scores
```

## 🔍 Troubleshooting

### Menu Issues
If "My Dashboard" appears twice:
1. Clear all caches (WordPress, Redis, browser)
2. Run: `wp menu fix-duplicates` (if using custom command)
3. Check theme's functions.php for conflicting menu additions

### Evaluation Sync Issues
Admin notice appears? Click "Fix Evaluation Data" or run:
```bash
docker exec mobility_wpcli_STAGING wp eval 'do_action("mt_sync_evaluations");'
```

## 🛡️ Security Features

- **Role-based Access Control**: Custom `mt_jury_member` role
- **Nonce Verification**: All AJAX requests validated
- **Data Sanitization**: Input/output escaping
- **SQL Injection Prevention**: Prepared statements

## 📊 Database Schema

### Key Tables
- `wp_mt_candidate_scores` - Evaluation data
- `wp_posts` - Candidates (mt_candidate) and Jury (mt_jury)
- `wp_postmeta` - Candidate/jury metadata
- `wp_users` - Jury member accounts

## 🚀 Upcoming Features

- **Public Results Display** - Post-evaluation phase
- **Export Functionality** - CSV/PDF reports
- **Email Notifications** - Automated reminders
- **Analytics Dashboard** - Advanced insights

## 🤝 Support

**Technical Lead**: Nicolas Estrém  
**Project Lead**: Prof. Dr. Andreas Herrmann (andreas.herrmann@unisg.ch)  
**Institution**: Institut für Mobilität, University of St. Gallen

## 📅 Timeline

- **Evaluation Phase**: March - September 2025
- **Finalist Selection**: September 2025
- **Award Ceremony**: October 30, 2025 (Berlin)

---

**Version**: 6.0.0 | **Last Updated**: June 2025