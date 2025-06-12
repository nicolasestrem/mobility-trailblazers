# Mobility Trailblazers Platform - Technical Documentation v3.0

**Last Updated:** June 11, 2025  
**Plugin Version:** 2.0.1  
**Platform Status:** Production Ready

## 📋 Table of Contents

1. [Platform Overview](#platform-overview)
2. [Infrastructure Architecture](#infrastructure-architecture)
3. [Plugin Architecture](#plugin-architecture)
4. [API Documentation](#api-documentation)
5. [Voting System](#voting-system)
6. [Assignment System](#assignment-system)
7. [Database Schema](#database-schema)
8. [Installation Guide](#installation-guide)
9. [Configuration](#configuration)
10. [Maintenance & Troubleshooting](#maintenance-troubleshooting)

---


├── README.md
├── assets
│   ├── css
│   │   ├── assignment-interface.css
│   │   ├── assignment-matrix.css
│   │   └── voting-styles.css
│   └── js
│       ├── assignment-interface.js
│       ├── assignment-matrix-view.js
│       ├── assignment-public.js
│       └── voting-interface.js
├── create_dummy_data.sh
├── create_staging_final.sh
├── jury-assignment-interface.php
├── mobility-trailblazers.php
└── voting-system.php

4 directories, 13 files

4 directories, 13 files



## 🎯 Platform Overview

The Mobility Trailblazers platform is a sophisticated WordPress-based award management system designed to identify and celebrate 25 mobility innovators in the DACH region annually. The platform orchestrates a multi-stage selection process culminating in an award ceremony at the Smart Mobility Summit in Berlin on October 30, 2025

### Core Functionality

- **Multi-Stage Selection Process**: Database (2000+) → Shortlist (200) → Semifinal (50) → Final (25)
- **Weighted Scoring System**: Four criteria with configurable weights
- **Intelligent Assignment Management**: Multiple distribution algorithms
- **Real-time Analytics**: Progress tracking and insights
- **Role-Based Access Control**: Granular permissions for administrators and jury members

### Award Categories

1. **Established Companies**: Traditional mobility industry leaders driving innovation
2. **Start-ups & Scale-ups**: Emerging companies disrupting the mobility sector
3. **Politics & Public Companies**: Government and public sector mobility champions

---

## 🏗️ Infrastructure Architecture

### Docker Stack Configuration

The platform runs on a containerized architecture managed through Komodo, with Cloudflare providing secure web access and management capabilities.

```yaml
version: '3.8'

services:
  # WordPress Application
  wordpress:
    image: wordpress:6.4-php8.2-apache
    container_name: mobility_wp
    restart: unless-stopped
    user: "33:33"  # Non-root security
    ports:
      - "9080:80"
    environment:
      WORDPRESS_DB_HOST: db
      WORDPRESS_DB_USER: mt_db_user_2025
      WORDPRESS_DB_PASSWORD: "mT8kL9xP2qR7vN6wE3zY4uC1sA5fG"
      WORDPRESS_DB_NAME: mobility_trailblazers
      WORDPRESS_TABLE_PREFIX: mt_
      WORDPRESS_DEBUG: 1
      WORDPRESS_CONFIG_EXTRA: |
        define('WP_MEMORY_LIMIT', '512M');
        define('FS_METHOD', 'direct');
        define('WP_DEBUG_LOG', true);
        define('WP_DEBUG_DISPLAY', false);
    volumes:
      - /mnt/dietpi_userdata/docker-files/mobility-trailblazers/wp-content:/var/www/html/wp-content
      - /mnt/dietpi_userdata/docker-files/mobility-trailblazers/uploads.ini:/usr/local/etc/php/conf.d/uploads.ini

  # MySQL Database
  db:
    image: mysql:8.0
    container_name: mobility_db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: mobility_trailblazers
      MYSQL_USER: mt_db_user_2025
      MYSQL_PASSWORD: "mT8kL9xP2qR7vN6wE3zY4uC1sA5fG"
      MYSQL_ROOT_PASSWORD: "Rt9mK3nQ8xY7bV5cZ2wE4rT6yU1iO"
    volumes:
      - /mnt/dietpi_userdata/docker-files/mobility-trailblazers/mysql-data:/var/lib/mysql
    command: --default-authentication-plugin=mysql_native_password

  # Redis Cache
  redis:
    image: redis:7-alpine
    container_name: mobility_redis
    restart: unless-stopped
    command: redis-server --appendonly yes --requirepass "Rd7nM4kP9qX6yB3wE8zA5uC2sF1gH"
    volumes:
      - /mnt/dietpi_userdata/docker-files/mobility-trailblazers/redis-data:/data
    ports:
      - "9379:6379"

  # WP-CLI for Management
  wpcli:
    image: wordpress:cli-php8.2
    container_name: mobility_wpcli
    user: "33:33"
    volumes:
      - /mnt/dietpi_userdata/docker-files/mobility-trailblazers/wp-content:/var/www/html/wp-content
    environment:
      WORDPRESS_DB_HOST: db
      WORDPRESS_DB_USER: mt_db_user_2025
      WORDPRESS_DB_PASSWORD: "mT8kL9xP2qR7vN6wE3zY4uC1sA5fG"
      WORDPRESS_DB_NAME: mobility_trailblazers
    profiles:
      - cli

networks:
  mobility_network:
    driver: bridge
```

Staging environment:

version: '3.8'

services:
  wordpress:
    image: wordpress:php8.2-apache
    container_name: mobility_wordpress_STAGING
    restart: unless-stopped
    ports:
      - "9989:80"
    environment:
      WORDPRESS_DB_HOST: db
      WORDPRESS_DB_USER: wp_user
      WORDPRESS_DB_PASSWORD: Wp7kL9xP2qR7vN6wE3zY4uC1sA5f
      WORDPRESS_DB_NAME: wordpress_db
      WORDPRESS_CONFIG_EXTRA: |
        define('WP_REDIS_HOST', 'mobility_redis_STAGING');
        define('WP_REDIS_PORT', 6379);
        define('WP_TEMP_DIR', '/var/www/html/wp-content/uploads/tmp');
        define('UPLOADS', 'wp-content/uploads');
    volumes:
      - /mnt/dietpi_userdata/docker-files/STAGING/wordpress_data:/var/www/html
      - /mnt/dietpi_userdata/docker-files/STAGING/php.ini:/usr/local/etc/php/conf.d/custom-php.ini
      - /mnt/dietpi_userdata/docker-files/STAGING/tmp:/var/www/html/wp-content/uploads/tmp
    depends_on:
      - db
      - redis

  wpcli:
    image: wordpress:cli-php8.2
    container_name: mobility_wpcli_STAGING
    restart: "no"
    user: "33:33"
    environment:
      WORDPRESS_DB_HOST: db
      WORDPRESS_DB_USER: wp_user
      WORDPRESS_DB_PASSWORD: Wp7kL9xP2qR7vN6wE3zY4uC1sA5f
      WORDPRESS_DB_NAME: wordpress_db
      WORDPRESS_CONFIG_EXTRA: |
        define('WP_REDIS_HOST', 'mobility_redis_STAGING');
        define('WP_REDIS_PORT', 6379);
        define('WP_TEMP_DIR', '/var/www/html/wp-content/uploads/tmp');
        define('UPLOADS', 'wp-content/uploads');
    volumes:
      - /mnt/dietpi_userdata/docker-files/STAGING/wordpress_data:/var/www/html
    depends_on:
      - db
      - wordpress
      - redis
    command: tail -f /dev/null

  db:
    image: mariadb:11
    container_name: mobility_mariadb_STAGING
    restart: unless-stopped
    ports:
      - "9306:3306"
    environment:
      MARIADB_ROOT_PASSWORD: Rt9mK3nQ8xY7bV5cZ2wE4rT6yU1i
      MARIADB_DATABASE: wordpress_db
      MARIADB_USER: wp_user
      MARIADB_PASSWORD: Wp7kL9xP2qR7vN6wE3zY4uC1sA5f
    volumes:
      - /mnt/dietpi_userdata/docker-files/STAGING/db_data:/var/lib/mysql

  redis:
    image: redis:alpine
    container_name: mobility_redis_STAGING
    restart: unless-stopped
    ports:
      - "9191:6379"
    volumes:
      - /mnt/dietpi_userdata/docker-files/STAGING/redis_data:/data

  # 🔧 phpMyAdmin - FIXED for both wp_user and root access
  phpmyadmin:
    image: phpmyadmin/phpmyadmin:latest
    container_name: mobility_phpmyadmin_STAGING
    restart: unless-stopped
    ports:
      - "9081:80"
    environment:
      # Database connection settings
      PMA_HOST: db
      PMA_PORT: 3306
      
      # 🚨 REMOVED AUTO-LOGIN - Now shows login screen
      # PMA_USER: wp_user  # <- REMOVED
      # PMA_PASSWORD: Wp7kL9xP2qR7vN6wE3zY4uC1sA5f  # <- REMOVED
      
      # Root password for administrative access
      MYSQL_ROOT_PASSWORD: Rt9mK3nQ8xY7bV5cZ2wE4rT6yU1i
      
      # Interface configuration
      PMA_ABSOLUTE_URI: http://192.168.1.7:9081/
      UPLOAD_LIMIT: 100M
      MEMORY_LIMIT: 512M
      MAX_EXECUTION_TIME: 600
      
      # Security and features - Allow manual login
      PMA_ARBITRARY: 1
      HIDE_PHP_VERSION: 1
    depends_on:
      - db
    volumes:
      # Optional: Store phpMyAdmin sessions and configs
      - /mnt/dietpi_userdata/docker-files/STAGING/phpmyadmin_sessions:/sessions
    # Resource limits
    deploy:
      resources:
        limits:
          memory: 512M
        reservations:
          memory: 256M

volumes: {}


### Web Access & Security

- **Cloudflare Integration**: Provides secure tunneling, DDoS protection, and web-based management
- **Direct Access**: Port 9080 for local development and testing
- **Authentication**: WordPress native authentication with custom role capabilities
- **SSL/TLS**: Handled by Cloudflare at the edge

---

## 🔧 Plugin Architecture

### File Structure

```
├── README.md
├── assets
│   ├── css
│   │   ├── assignment-interface.css
│   │   ├── assignment-matrix.css
│   │   └── voting-styles.css
│   └── js
│       ├── assignment-interface.js
│       ├── assignment-matrix-view.js
│       ├── assignment-public.js
│       └── voting-interface.js
├── create_dummy_data.sh
├── create_staging_final.sh
├── jury-assignment-interface.php
├── mobility-trailblazers.php
└── voting-system.php

4 directories, 13 files
```

### Core Components

#### 1. Main Plugin Loader (`mobility-trailblazers.php`)

The plugin loader orchestrates all components with proper initialization:

```php
class MobilityTrailblazersLoader {
    // Singleton pattern for consistent instance
    private static $instance = null;
    
    public function init() {
        // Load core functionality
        new MobilityTrailblazers();
        
        // Load voting system
        $this->load_voting_system();
        
        // Load assignment interface
        $this->load_assignment_interface();
    }
    
    public function activate() {
        // Create database tables
        $this->create_database_tables();
        
        // Create user roles
        $this->create_user_roles();
        
        // Initialize default data
        $this->initialize_default_data();
    }
}
```

#### 2. Custom Post Types & Taxonomies

```php
// Candidate Post Type
register_post_type('candidate', [
    'labels' => [
        'name' => 'Candidates',
        'singular_name' => 'Candidate'
    ],
    'public' => true,
    'has_archive' => true,
    'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
    'show_in_rest' => true,
    'menu_icon' => 'dashicons-groups'
]);

// Candidate Category Taxonomy
register_taxonomy('candidate_category', ['candidate'], [
    'labels' => [
        'name' => 'Categories',
        'singular_name' => 'Category'
    ],
    'hierarchical' => true,
    'show_in_rest' => true
]);
```

#### 3. User Roles & Capabilities

```php
// Jury Member Role
add_role('jury_member', 'Jury Member', [
    'read' => true,
    'vote_on_candidates' => true,
    'view_assigned_candidates' => true,
    'edit_own_votes' => true
]);

// Administrator Enhancements
$admin_role = get_role('administrator');
$admin_capabilities = [
    'manage_voting_phases',
    'assign_candidates_to_jury',
    'view_all_votes',
    'manage_jury_members',
    'view_voting_reports',
    'export_voting_data'
];
```

---

## 📡 API Documentation

### Authentication & Security

All API endpoints require WordPress authentication using nonces:

```javascript
// Client-side API call example
fetch('/wp-json/mt/v1/admin/voting-progress', {
    method: 'GET',
    headers: {
        'X-WP-Nonce': wpApiSettings.nonce,
        'Content-Type': 'application/json'
    },
    credentials: 'same-origin'
});
```

### Voting Endpoints

#### GET `/wp-json/mt/v1/my-candidates`
Retrieves candidates assigned to the current jury member.

**Permission:** `jury_member` or `administrator`

**Response:**
```json
{
    "phase": {
        "id": 2,
        "phase_name": "Semi-Final Selection 2025",
        "stage": "semifinal",
        "end_date": "2025-07-15 23:59:59"
    },
    "candidates": [
        {
            "id": 123,
            "name": "Dr. Marcus Hartmann",
            "company": "Mercedes-Benz AG",
            "position": "CTO",
            "category": {
                "id": 1,
                "name": "Established Companies"
            },
            "innovation": "Level 3 autonomous driving systems",
            "is_voted": true,
            "is_final": false,
            "current_vote": {
                "pioneer_spirit": 8,
                "innovation_degree": 9,
                "implementation_power": 7,
                "role_model_function": 8,
                "total_score": 8.05,
                "comments": "Exceptional innovation in autonomous systems"
            }
        }
    ],
    "total_assigned": 15,
    "total_voted": 12
}
```

#### POST `/wp-json/mt/v1/vote`
Submit or update a vote for a candidate.

**Permission:** `jury_member` or `administrator`

**Request Body:**
```json
{
    "candidate_id": 123,
    "pioneer_spirit": 8,
    "innovation_degree": 9,
    "implementation_power": 7,
    "role_model_function": 8,
    "comments": "Exceptional innovation in autonomous systems",
    "is_final": false
}
```

**Validation Rules:**
- All scores must be integers between 1-10
- `candidate_id` must be assigned to the current user
- Cannot modify votes marked as `is_final`

**Response:**
```json
{
    "success": true,
    "vote_id": 456,
    "total_score": 8.05,
    "message": "Vote saved successfully"
}
```

### Assignment Management Endpoints

#### GET `/wp-json/mt/v1/admin/assignments`
Retrieves all assignments for a voting stage.

**Permission:** `administrator`

**Query Parameters:**
- `stage` (string): Voting stage (shortlist/semifinal/final)
- `jury_member_id` (int): Filter by specific jury member
- `candidate_id` (int): Filter by specific candidate

**Response:**
```json
{
    "assignments": [
        {
            "id": 789,
            "jury_member": {
                "id": 10,
                "name": "Dr. Andreas Müller",
                "email": "amueller@vw.de"
            },
            "candidate": {
                "id": 123,
                "name": "Dr. Marcus Hartmann",
                "category": "Established Companies"
            },
            "stage": "semifinal",
            "assigned_at": "2025-06-01 10:30:00",
            "assigned_by": 1,
            "vote_status": "completed"
        }
    ],
    "total": 150,
    "stats": {
        "total_assignments": 150,
        "completed_votes": 120,
        "pending_votes": 30,
        "completion_rate": 80
    }
}
```

#### POST `/wp-json/mt/v1/admin/bulk-assign`
Perform bulk assignment operations.

**Permission:** `administrator`

**Request Body:**
```json
{
    "assignments": {
        "10": [123, 124, 125],  // jury_member_id: [candidate_ids]
        "11": [126, 127, 128]
    },
    "stage": "semifinal",
    "mode": "add",  // add|replace|remove
    "validate_conflicts": true,
    "send_notifications": false
}
```

**Response:**
```json
{
    "success": true,
    "created": 6,
    "updated": 0,
    "removed": 0,
    "conflicts": [],
    "message": "Successfully assigned 6 candidates"
}
```

#### POST `/wp-json/mt/v1/admin/auto-assign`
Automatically distribute candidates using intelligent algorithms.

**Permission:** `administrator`

**Request Body:**
```json
{
    "stage": "semifinal",
    "candidates_per_jury": 15,
    "distribution_method": "balanced",  // balanced|random|expertise|category
    "clear_existing": false,
    "balance_categories": true,
    "respect_expertise": false,
    "optimization_level": "standard"  // standard|aggressive|conservative
}
```

**Distribution Methods:**
1. **Balanced**: Even distribution across all jury members
2. **Random**: Randomized assignment for fairness
3. **Expertise**: Match jury expertise with candidate categories
4. **Category**: Ensure proportional category representation

**Response:**
```json
{
    "success": true,
    "assignments_created": 300,
    "distribution": {
        "jury_members": 20,
        "candidates": 300,
        "avg_per_jury": 15,
        "min_assignments": 14,
        "max_assignments": 16
    },
    "category_balance": {
        "established": 0.40,
        "startups": 0.35,
        "politics": 0.25
    }
}
```

### Analytics & Reporting Endpoints

#### GET `/wp-json/mt/v1/admin/voting-progress`
Comprehensive voting progress and analytics.

**Permission:** `administrator`

**Query Parameters:**
- `stage` (string): Filter by voting stage
- `include_details` (bool): Include detailed breakdowns

**Response:**
```json
{
    "phase": {
        "id": 2,
        "phase_name": "Semi-Final Selection 2025",
        "stage": "semifinal",
        "days_remaining": 34
    },
    "overall_stats": {
        "total_assignments": 300,
        "total_votes": 245,
        "final_votes": 89,
        "completion_rate": 81.67,
        "finalization_rate": 29.67
    },
    "jury_stats": [
        {
            "jury_id": 10,
            "display_name": "Dr. Andreas Müller",
            "assigned_candidates": 15,
            "completed_votes": 15,
            "final_votes": 8,
            "completion_rate": 100,
            "avg_score": 7.85
        }
    ],
    "category_breakdown": [
        {
            "category": "Established Companies",
            "total_candidates": 120,
            "total_votes": 98,
            "avg_score": 7.65,
            "completion_rate": 81.67
        }
    ],
    "score_distribution": {
        "pioneer_spirit": {
            "avg": 7.45,
            "min": 3,
            "max": 10,
            "std_dev": 1.82
        }
    }
}
```

#### GET `/wp-json/mt/v1/admin/export-data`
Export voting data in various formats.

**Permission:** `administrator`

**Query Parameters:**
- `format` (string): csv|json|xlsx
- `stage` (string): Voting stage to export
- `include_comments` (bool): Include vote comments
- `anonymize` (bool): Anonymize jury member data

---

## 🗳️ Voting System

### Voting Process Workflow

#### 1. Assignment Phase
Administrators assign candidates to jury members using either manual selection or auto-assignment algorithms.

#### 2. Evaluation Phase
Jury members evaluate assigned candidates across four weighted criteria:

```php
// Scoring weights defined in the system
const SCORING_WEIGHTS = [
    'pioneer_spirit' => 0.25,      // 25% - Pioneer Spirit & Courage
    'innovation_degree' => 0.30,    // 30% - Degree of Innovation
    'implementation_power' => 0.25, // 25% - Implementation Power & Effect
    'role_model_function' => 0.20   // 20% - Role Model Function & Visibility
];
```

#### 3. Score Calculation
Total scores are automatically calculated using the weighted formula:

```php
public function calculate_total_score($vote_data) {
    $total = 0;
    
    foreach (self::SCORING_WEIGHTS as $criterion => $weight) {
        $score = $vote_data[$criterion] ?? 5; // Default to 5 if not set
        $total += $score * $weight;
    }
    
    return round($total, 2);
}
```

#### 4. Vote Submission States

1. **Draft State**: Scores saved but not submitted (auto-save every 30 seconds)
2. **Submitted State**: Vote complete but can be modified
3. **Final State**: Vote locked and cannot be changed

```javascript
// Frontend vote submission logic
class VotingInterface {
    async submitVote(candidateId, isFinal = false) {
        const voteData = {
            candidate_id: candidateId,
            pioneer_spirit: this.getScore('pioneer_spirit'),
            innovation_degree: this.getScore('innovation_degree'),
            implementation_power: this.getScore('implementation_power'),
            role_model_function: this.getScore('role_model_function'),
            comments: this.getComments(),
            is_final: isFinal
        };
        
        // Validate all scores are present
        if (!this.validateScores(voteData)) {
            throw new Error('All criteria must be scored');
        }
        
        // Submit to API
        return await this.api.post('/vote', voteData);
    }
}
```

### Voting Interface Features

- **Real-time Score Preview**: Total score updates as sliders move
- **Progress Indicators**: Visual feedback on voting completion
- **Keyboard Shortcuts**: Ctrl+S to save, arrow keys for navigation
- **Auto-save**: Prevents data loss with periodic saves
- **Mobile Responsive**: Touch-optimized for tablet voting

---

## 📊 Assignment System

### Assignment Management Interface

The assignment system provides administrators with powerful tools to manage the distribution of candidates to jury members efficiently.

#### Interface Layout

```
┌─────────────────────────────────────────────────────────────┐
│                 Assignment Management Interface              │
├─────────────────┬──────────────────┬───────────────────────┤
│   Candidates    │     Actions      │    Jury Members       │
│                 │                  │                       │
│ □ Candidate 1   │  [→ Assign →]    │  □ Jury Member A     │
│ ☑ Candidate 2   │  [← Remove ←]    │  □ Jury Member B     │
│ □ Candidate 3   │                  │  ☑ Jury Member C     │
│                 │  [Auto-Assign]   │                       │
│ Category Filter │  [Bulk Actions]  │  Expertise Filter    │
│ Search: [____]  │  [Export Data]   │  Search: [____]      │
└─────────────────┴──────────────────┴───────────────────────┘
```

**Document Version**: 4.0  
**Plugin Version**: 2.0.1  
**Last Updated**: June 11, 2025  
**Status**: Not Production Ready

*This documentation represents the complete technical specification of the Mobility Trailblazers platform, optimized for Cloudflare deployment without Elementor dependencies.*
