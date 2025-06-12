## 📋 Table of Contents

1. [Platform Overview](#platform-overview)
2. [Infrastructure Architecture](#infrastructure-architecture)

---




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


```


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


1. **Draft State**: Scores saved but not submitted (auto-save every 30 seconds)
2. **Submitted State**: Vote complete but can be modified
3. **Final State**: Vote locked and cannot be changed



### Voting Interface Features

- **Real-time Score Preview**: Total score updates as sliders move
- **Progress Indicators**: Visual feedback on voting completion
- **Keyboard Shortcuts**: Ctrl+S to save, arrow keys for navigation
- **Auto-save**: Prevents data loss with periodic saves
- **Mobile Responsive**: Touch-optimized for tablet voting


## 📊 Assignment System

### Assignment Management Interface

The assignment system provides administrators with powerful tools to manage the distribution of candidates to jury members efficiently.

