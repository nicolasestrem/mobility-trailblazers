# Mobility Trailblazers Award Platform

> 🏆 **25 Mobility Trailblazers in 25** - Recognizing courage and innovation in mobility transformation across the DACH region

## Overview

The Mobility Trailblazers platform is a comprehensive WordPress-based award system designed to identify, evaluate, and celebrate the top 25 individuals driving mobility transformation in Germany, Austria, and Switzerland (DACH region). This platform serves as both a public communication hub and a secure jury evaluation system.

**Key Mission**: "Weil mobiler Wandel Mut braucht" (Because mobility transformation requires courage)

## 🎯 Project Goals

1. **Individual Recognition** - Honor 25 makers and shapers transforming mobility
2. **Innovation Showcase** - Highlight courageous innovations strengthening DACH's mobility competitiveness  
3. **Transformation Visibility** - Demonstrate the scope of mobility transformation achievements

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
- **Current Status**: 490 candidates, 22 jury members in system

### Jury Evaluation Platform ✅ NEW
- **Secure jury member dashboard** with personalized access
- **Real-time statistics**: Assigned, Evaluated, Pending, Progress %
- **Individual evaluation interface** with visual scoring system
- **Five evaluation criteria scoring** (1-10 scale):
  - Mut & Pioniergeist (Courage & Pioneer Spirit)
  - Innovationsgrad (Innovation Degree)
  - Umsetzungskraft & Wirkung (Implementation & Impact)
  - Relevanz für Mobilitätswende (Mobility Transformation Relevance)
  - Vorbildfunktion & Sichtbarkeit (Role Model & Visibility)

### Jury Dashboard Access ✅ NEW
- **Primary URL**: `http://192.168.1.7:9989/wp-admin/admin.php?page=mt-jury-dashboard`
- **Direct Access**: `http://192.168.1.7:9989/wp-admin/admin.php?mt_jury_direct=1`
- **Evaluation Page**: `http://192.168.1.7:9989/wp-admin/admin.php?page=mt-evaluate&candidate=[ID]`

### Assignment Management
- Advanced assignment interface for jury-candidate allocation
- Auto-assignment algorithms with multiple distribution strategies
- Real-time progress tracking and analytics
- Export functionality for assignments and evaluations
- **Current**: 10 test assignments created for development

## 🔧 Recent Updates (Latest Session)

### Bug Fixes
- ✅ Fixed critical plugin crash caused by duplicate function definitions
- ✅ Resolved menu registration issues for jury dashboard
- ✅ Corrected PHP syntax errors at end of main plugin file

### New Features Implemented
1. **Jury Dashboard System**
   - Complete dashboard interface for jury members
   - Statistics cards showing evaluation progress
   - Grid view of assigned candidates
   - Status indicators (Evaluated/Pending)
   
2. **Evaluation System**
   - Individual candidate evaluation pages
   - Visual score selection (1-10) for each criterion
   - Comments field for additional feedback
   - Edit functionality for previously submitted evaluations
   
3. **Database Integration**
   - Properly stores evaluations in `wp_mt_candidate_scores` table
   - Tracks evaluation timestamps
   - Prevents duplicate evaluations

### System Diagnostic Tool
- Added comprehensive diagnostic page at: `admin.php?page=mt-diagnostic`
- Checks database tables, user linking, assignments
- Quick fix buttons for common issues

## 📝 Shortcodes Documentation

### [mt_candidate_grid]
Displays a responsive grid of candidates with filtering options.

**Parameters:**
- `category` (string) - Filter by specific category slug
- `status` (string) - Filter by status: 'longlist', 'shortlist', 'finalist', 'winner'
- `year` (string) - Filter by award year (default: current year)
- `columns` (int) - Number of columns: 2, 3, or 4 (default: 3)
- `show_filters` (bool) - Display category filter buttons (default: true)
- `show_status` (bool) - Show candidate status badges (default: true)
- `orderby` (string) - Order by: 'name', 'score', 'random' (default: 'name')
- `limit` (int) - Maximum number of candidates to display

### [mt_voting_form]
Renders the voting interface for jury members or public voting.

### [mt_jury_dashboard] ✅ NEW
Displays the jury dashboard on frontend pages (requires login).

## 🗄️ Database Schema

### Custom Tables
- `wp_mt_votes` - Jury voting records
- `wp_mt_public_votes` - Public voting data
- `wp_mt_candidate_scores` - Detailed evaluation scores (actively used)
- `wp_mt_assignments` - Jury-candidate assignments
- `wp_mt_voting_sessions` - Voting session tracking

### Key Meta Fields
- `_mt_assigned_jury_member` - Links candidates to jury members
- `_mt_jury_user_id` - Links jury members to WordPress users

## 👥 Jury System

The platform features a distinguished 22-member jury led by:
- **President**: Prof. Dr. Andreas Herrmann (University of St. Gallen)
- **Vice President**: Prof. em. Dr. Dr. h.c. Torsten Tomczak
- **Patron**: Winfried Hermann (Transport Minister Baden-Württemberg)

### Test Account
- **Username**: Admin user (ID: 1)
- **Email**: nicolas.estrem@gmail.com
- **Linked to**: jury01 (first jury member)
- **Test Assignments**: 10 candidates

## 🧪 Testing

### Quick Test Procedure
1. Login as admin user
2. Navigate to MT Award System menu
3. Click "My Dashboard" or use direct URL
4. Evaluate a candidate using the "Evaluate Now" button
5. Check that scores save correctly

### WP-CLI Commands
```bash
# Access container
docker exec -it mobility_wpcli_STAGING bash

# List candidates
wp post list --post_type=mt_candidate

# Check jury members  
wp post list --post_type=mt_jury

# Database queries
wp db query "SELECT * FROM wp_mt_candidate_scores"
```

## 🚧 Roadmap

### Completed ✅
- [x] Core award management system
- [x] Jury evaluation platform
- [x] Assignment management interface  
- [x] Jury dashboard implementation
- [x] Evaluation scoring system
- [x] Database structure for evaluations

### In Progress 🔄
- [ ] Frontend jury portal templates
- [ ] Email notifications for assignments
- [ ] Export functionality for evaluations

### Upcoming 📅
- [ ] Public voting interface
- [ ] REST API implementation
- [ ] Advanced analytics dashboard
- [ ] Multi-language support (DE/EN)
- [ ] Mobile-responsive jury interface

## 📦 File Structure

```
/mnt/dietpi_userdata/docker-files/STAGING/
├── docker-compose.yml
├── wordpress_data/
│   └── wp-content/
│       └── plugins/
│           └── mobility-trailblazers/
│               ├── mobility-trailblazers.php (main plugin file)
│               ├── assets/
│               │   ├── admin.js
│               │   ├── admin.css
│               │   ├── frontend.js
│               │   └── frontend.css
│               └── templates/ (to be created)
│                   ├── jury-dashboard-frontend.php
│                   └── jury-member-profile.php
└── db_data/
```

## 🔐 Security & Permissions

- WordPress user roles integration
- Jury member authentication via user ID linking
- Secure evaluation with duplicate prevention
- GDPR-compliant data handling
- Admin override capabilities for testing

## 📄 License

Proprietary - Institut für Mobilität, Universität St. Gallen

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

*Last updated: Current session - Jury dashboard implementation completed*