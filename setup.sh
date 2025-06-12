#!/bin/bash

# ========================================
# Mobility Trailblazers Staging Environment Setup Script
# For DietPi Docker Environment
# ========================================

set -e  # Exit on any error

# Configuration
STAGING_PATH="/mnt/dietpi_userdata/docker-files/STAGING"
PLUGIN_NAME="mobility-trailblazers"
WP_CONTAINER="staging_wp"
DB_CONTAINER="staging_db"
DOCKER_COMPOSE_FILE="$STAGING_PATH/docker-compose.yml"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

error() {
    echo -e "${RED}[ERROR] $1${NC}" >&2
}

warning() {
    echo -e "${YELLOW}[WARNING] $1${NC}"
}

info() {
    echo -e "${BLUE}[INFO] $1${NC}"
}

# Check if running as root or with sudo
check_permissions() {
    if [[ $EUID -ne 0 ]]; then
        error "This script must be run as root or with sudo"
        exit 1
    fi
}

# Check if staging directory exists
check_staging_directory() {
    if [[ ! -d "$STAGING_PATH" ]]; then
        error "Staging directory does not exist: $STAGING_PATH"
        error "Please create the staging environment first"
        exit 1
    fi
    log "Staging directory found: $STAGING_PATH"
}

# Create necessary directories
create_directories() {
    log "Creating necessary directories..."
    
    # Plugin directory
    mkdir -p "$STAGING_PATH/wp-content/plugins/$PLUGIN_NAME"
    mkdir -p "$STAGING_PATH/wp-content/plugins/$PLUGIN_NAME/assets"
    mkdir -p "$STAGING_PATH/wp-content/plugins/$PLUGIN_NAME/languages"
    
    # MySQL initialization directory
    mkdir -p "$STAGING_PATH/mysql-init"
    
    # Uploads and cache directories
    mkdir -p "$STAGING_PATH/wp-content/uploads"
    mkdir -p "$STAGING_PATH/wp-content/cache"
    
    # Backup directory
    mkdir -p "$STAGING_PATH/backups"
    
    log "Directories created successfully"
}

# Set proper permissions
set_permissions() {
    log "Setting proper file permissions..."
    
    # WordPress files - www-data user (33:33)
    chown -R 33:33 "$STAGING_PATH/wp-content"
    find "$STAGING_PATH/wp-content" -type d -exec chmod 755 {} \;
    find "$STAGING_PATH/wp-content" -type f -exec chmod 644 {} \;
    
    # Plugin files
    chown -R 33:33 "$STAGING_PATH/wp-content/plugins/$PLUGIN_NAME"
    chmod -R 755 "$STAGING_PATH/wp-content/plugins/$PLUGIN_NAME"
    
    # MySQL initialization files
    chown -R 999:999 "$STAGING_PATH/mysql-init"
    chmod -R 755 "$STAGING_PATH/mysql-init"
    
    log "Permissions set successfully"
}

# Create plugin files
create_plugin_files() {
    log "Creating plugin files..."
    
    # Main plugin file will be created separately
    # Here we create the basic structure
    
    # Create uploads.ini for PHP configuration
    cat > "$STAGING_PATH/uploads.ini" << 'EOF'
file_uploads = On
memory_limit = 512M
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300
max_input_vars = 3000
EOF

    # Create wp-config-extra.php for additional WordPress configuration
    cat > "$STAGING_PATH/wp-config-extra.php" << 'EOF'
<?php
// Additional WordPress configuration for Mobility Trailblazers

// Redis configuration
define('WP_REDIS_HOST', 'redis');
define('WP_REDIS_PORT', 6379);
define('WP_REDIS_PASSWORD', 'Rd7nM4kP9qX6yB3wE8zA5uC2sF1gH');
define('WP_REDIS_DATABASE', 0);
define('WP_REDIS_TIMEOUT', 1);
define('WP_REDIS_READ_TIMEOUT', 1);

// Cache configuration
define('WP_CACHE', true);
define('WP_CACHE_KEY_SALT', 'mobility_trailblazers_staging');

// Additional security
define('DISALLOW_FILE_EDIT', true);
define('FORCE_SSL_ADMIN', false); // Set to true in production
define('WP_POST_REVISIONS', 5);
define('AUTOSAVE_INTERVAL', 300);

// Mobility Trailblazers specific settings
define('MT_ENVIRONMENT', 'staging');
define('MT_DEBUG', true);
define('MT_VERSION', '1.0.0');

// Email configuration for staging
define('WP_MAIL_SMTP_MAILER', 'mail');
define('WPMS_ON', true);

// Increase memory for plugin operations
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);
EOF

    # Create wp-cli.yml
    cat > "$STAGING_PATH/wp-cli.yml" << 'EOF'
path: /var/www/html
url: http://localhost:9090
debug: true
color: true
core config:
  dbhost: db
  dbname: mobility_trailblazers
  dbuser: mt_db_user_2025
  dbpass: mT8kL9xP2qR7vN6wE3zY4uC1sA5fG
  dbprefix: mt_
  extra-php: |
    define('WP_DEBUG', true);
    define('WP_DEBUG_LOG', true);
    define('MT_ENVIRONMENT', 'staging');
EOF

    # Create MySQL custom configuration
    cat > "$STAGING_PATH/mysql.cnf" << 'EOF'
[mysqld]
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
innodb_flush_log_at_trx_commit = 1
innodb_lock_wait_timeout = 50
max_connections = 200
query_cache_size = 32M
query_cache_type = 1
slow_query_log = 1
slow_query_log_file = /var/log/mysql/mysql-slow.log
long_query_time = 2
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
default-time-zone = '+01:00'

[mysql]
default-character-set = utf8mb4

[client]
default-character-set = utf8mb4
EOF

    log "Configuration files created successfully"
}

# Create Nginx configuration
create_nginx_config() {
    log "Creating Nginx configuration..."
    
    # Main nginx.conf
    cat > "$STAGING_PATH/nginx.conf" << 'EOF'
user nginx;
worker_processes auto;
error_log /var/log/nginx/error.log warn;
pid /var/run/nginx.pid;

events {
    worker_connections 1024;
    use epoll;
    multi_accept on;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                    '$status $body_bytes_sent "$http_referer" '
                    '"$http_user_agent" "$http_x_forwarded_for"';

    access_log /var/log/nginx/access.log main;

    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;
    client_max_body_size 64m;

    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types
        text/plain
        text/css
        text/xml
        text/javascript
        application/json
        application/javascript
        application/xml+rss
        application/atom+xml
        image/svg+xml;

    include /etc/nginx/conf.d/*.conf;
}
EOF

    # Site-specific configuration
    cat > "$STAGING_PATH/nginx-site.conf" << 'EOF'
server {
    listen 80;
    server_name localhost;
    root /var/www/html;
    index index.php index.html index.htm;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # WordPress specific rules
    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    # Handle PHP files
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_intercept_errors on;
        fastcgi_pass wordpress:80;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param HTTPS off;
    }

    # WordPress media handling
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # WordPress security
    location ~ /\.(ht|git) {
        deny all;
    }

    location ~ /wp-config.php {
        deny all;
    }

    # Mobility Trailblazers API endpoints
    location /wp-json/mobility-trailblazers/ {
        try_files $uri $uri/ /index.php?$args;
        add_header Access-Control-Allow-Origin *;
        add_header Access-Control-Allow-Methods "GET, POST, OPTIONS";
        add_header Access-Control-Allow-Headers "Origin, X-Requested-With, Content-Type, Accept, Authorization";
    }

    # Health check endpoint
    location /health {
        access_log off;
        return 200 "healthy\n";
        add_header Content-Type text/plain;
    }
}
EOF

    log "Nginx configuration created successfully"
}

# Create docker-compose.yml if it doesn't exist
create_docker_compose() {
    if [[ ! -f "$DOCKER_COMPOSE_FILE" ]]; then
        log "Creating docker-compose.yml for staging..."
        
        cat > "$DOCKER_COMPOSE_FILE" << 'EOF'
version: '3.8'

services:
  # WordPress Application
  wordpress:
    image: wordpress:6.4-php8.2-apache
    container_name: staging_wp
    restart: unless-stopped
    user: "33:33"
    ports:
      - "9090:80"
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
        define('MT_ENVIRONMENT', 'staging');
    volumes:
      - ./wp-content:/var/www/html/wp-content
      - ./uploads.ini:/usr/local/etc/php/conf.d/uploads.ini
      - ./wp-config-extra.php:/var/www/html/wp-config-extra.php
    depends_on:
      - db
      - redis
    networks:
      - staging_network

  # MySQL Database
  db:
    image: mysql:8.0
    container_name: staging_db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: mobility_trailblazers
      MYSQL_USER: mt_db_user_2025
      MYSQL_PASSWORD: "mT8kL9xP2qR7vN6wE3zY4uC1sA5fG"
      MYSQL_ROOT_PASSWORD: "Rt9mK3nQ8xY7bV5cZ2wE4rT6yU1iO"
      MYSQL_CHARSET: utf8mb4
      MYSQL_COLLATION: utf8mb4_unicode_ci
    volumes:
      - ./mysql-data:/var/lib/mysql
      - ./mysql-init:/docker-entrypoint-initdb.d
      - ./mysql.cnf:/etc/mysql/conf.d/custom.cnf
    ports:
      - "9306:3306"
    command: --default-authentication-plugin=mysql_native_password
    networks:
      - staging_network

  # Redis Cache
  redis:
    image: redis:7-alpine
    container_name: staging_redis
    restart: unless-stopped
    command: redis-server --appendonly yes --requirepass "Rd7nM4kP9qX6yB3wE8zA5uC2sF1gH"
    volumes:
      - ./redis-data:/data
    ports:
      - "9379:6379"
    networks:
      - staging_network

  # phpMyAdmin
  phpmyadmin:
    image: phpmyadmin/phpmyadmin:latest
    container_name: staging_pma
    restart: unless-stopped
    environment:
      PMA_HOST: db
      PMA_USER: mt_db_user_2025
      PMA_PASSWORD: "mT8kL9xP2qR7vN6wE3zY4uC1sA5fG"
      MYSQL_ROOT_PASSWORD: "Rt9mK3nQ8xY7bV5cZ2wE4rT6yU1iO"
      PMA_ABSOLUTE_URI: http://localhost:9081/
    ports:
      - "9081:80"
    depends_on:
      - db
    networks:
      - staging_network

  # WP-CLI
  wpcli:
    image: wordpress:cli-php8.2
    container_name: staging_wpcli
    user: "33:33"
    volumes:
      - ./wp-content:/var/www/html/wp-content
      - ./wp-cli.yml:/var/www/html/wp-cli.yml
    environment:
      WORDPRESS_DB_HOST: db
      WORDPRESS_DB_USER: mt_db_user_2025
      WORDPRESS_DB_PASSWORD: "mT8kL9xP2qR7vN6wE3zY4uC1sA5fG"
      WORDPRESS_DB_NAME: mobility_trailblazers
    depends_on:
      - db
      - wordpress
    networks:
      - staging_network
    profiles:
      - cli

networks:
  staging_network:
    driver: bridge

volumes: {}
EOF
        log "Docker Compose file created"
    else
        log "Docker Compose file already exists"
    fi
}

# Start Docker services
start_services() {
    log "Starting Docker services..."
    
    cd "$STAGING_PATH"
    docker-compose down --remove-orphans
    docker-compose pull
    docker-compose up -d
    
    # Wait for services to be ready
    log "Waiting for services to be ready..."
    sleep 30
    
    # Check service health
    if docker-compose ps | grep -q "Up"; then
        log "Docker services started successfully"
    else
        error "Failed to start Docker services"
        docker-compose logs
        exit 1
    fi
}

# Install WordPress if not already installed
install_wordpress() {
    log "Checking WordPress installation..."
    
    cd "$STAGING_PATH"
    
    # Check if WordPress is already installed
    if docker-compose exec --no-TTY wordpress wp core is-installed 2>/dev/null; then
        log "WordPress is already installed"
    else
        log "Installing WordPress..."
        
        # Download WordPress core
        docker-compose exec --no-TTY wordpress wp core download --force
        
        # Create wp-config.php
        docker-compose exec --no-TTY wordpress wp config create \
            --dbname=mobility_trailblazers \
            --dbuser=mt_db_user_2025 \
            --dbpass=mT8kL9xP2qR7vN6wE3zY4uC1sA5fG \
            --dbhost=db \
            --dbprefix=mt_ \
            --force
        
        # Install WordPress
        docker-compose exec --no-TTY wordpress wp core install \
            --url=http://localhost:9090 \
            --title="Mobility Trailblazers Staging" \
            --admin_user=admin \
            --admin_password=admin123 \
            --admin_email=admin@mobility-trailblazers.local \
            --skip-email
        
        log "WordPress installed successfully"
    fi
}

# Deploy plugin files
deploy_plugin() {
    log "Deploying Mobility Trailblazers plugin..."
    
    # The plugin files should be copied manually or via another method
    # This function prepares the environment for plugin deployment
    
    # Create plugin directory structure
    mkdir -p "$STAGING_PATH/wp-content/plugins/$PLUGIN_NAME/assets"
    
    # Set proper permissions
    chown -R 33:33 "$STAGING_PATH/wp-content/plugins/$PLUGIN_NAME"
    chmod -R 755 "$STAGING_PATH/wp-content/plugins/$PLUGIN_NAME"
    
    log "Plugin directory prepared. Please copy plugin files to:"
    log "$STAGING_PATH/wp-content/plugins/$PLUGIN_NAME/"
}

# Activate plugin via WP-CLI
activate_plugin() {
    log "Activating Mobility Trailblazers plugin..."
    
    cd "$STAGING_PATH"
    
    # Check if plugin files exist
    if [[ ! -f "$STAGING_PATH/wp-content/plugins/$PLUGIN_NAME/$PLUGIN_NAME.php" ]]; then
        warning "Plugin files not found. Please deploy plugin files first."
        return 1
    fi
    
    # Activate plugin
    if docker-compose exec --no-TTY wordpress wp plugin activate $PLUGIN_NAME 2>/dev/null; then
        log "Plugin activated successfully"
    else
        error "Failed to activate plugin"
        return 1
    fi
}

# Verify installation
verify_installation() {
    log "Verifying installation..."
    
    cd "$STAGING_PATH"
    
    # Check WordPress
    if docker-compose exec --no-TTY wordpress wp core is-installed 2>/dev/null; then
        log "✓ WordPress is installed and working"
    else
        error "✗ WordPress installation failed"
        return 1
    fi
    
    # Check database connection
    if docker-compose exec --no-TTY db mysql -h localhost -u mt_db_user_2025 -pmT8kL9xP2qR7vN6wE3zY4uC1sA5fG mobility_trailblazers -e "SELECT 1;" >/dev/null 2>&1; then
        log "✓ Database connection working"
    else
        error "✗ Database connection failed"
        return 1
    fi
    
    # Check Redis connection
    if docker-compose exec --no-TTY redis redis-cli -a "Rd7nM4kP9qX6yB3wE8zA5uC2sF1gH" ping | grep -q "PONG"; then
        log "✓ Redis connection working"
    else
        warning "Redis connection failed - cache may not work properly"
    fi
    
    # Check web server
    if curl -s http://localhost:9090 | grep -q "WordPress" || curl -s http://localhost:9090 | grep -q "Mobility Trailblazers"; then
        log "✓ Web server is responding"
    else
        error "✗ Web server not responding"
        return 1
    fi
    
    log "Installation verification completed"
}

# Create backup script
create_backup_script() {
    log "Creating backup script..."
    
    cat > "$STAGING_PATH/backup.sh" << 'EOF'
#!/bin/bash

# Mobility Trailblazers Staging Backup Script

BACKUP_DIR="/mnt/dietpi_userdata/docker-files/STAGING/backups"
DATE=$(date +%Y%m%d_%H%M%S)
STAGING_PATH="/mnt/dietpi_userdata/docker-files/STAGING"

mkdir -p "$BACKUP_DIR"

echo "Starting backup at $(date)"

# Backup database
echo "Backing up database..."
cd "$STAGING_PATH"
docker-compose exec --no-TTY db mysqldump -u mt_db_user_2025 -pmT8kL9xP2qR7vN6wE3zY4uC1sA5fG mobility_trailblazers > "$BACKUP_DIR/database_$DATE.sql"

# Backup wp-content
echo "Backing up wp-content..."
tar -czf "$BACKUP_DIR/wp-content_$DATE.tar.gz" -C "$STAGING_PATH" wp-content

# Cleanup old backups (keep last 7 days)
find "$BACKUP_DIR" -name "*.sql" -mtime +7 -delete
find "$BACKUP_DIR" -name "*.tar.gz" -mtime +7 -delete

echo "Backup completed at $(date)"
echo "Files saved to: $BACKUP_DIR"
EOF

    chmod +x "$STAGING_PATH/backup.sh"
    log "Backup script created at $STAGING_PATH/backup.sh"
}

# Main execution function
main() {
    log "Starting Mobility Trailblazers Staging Environment Setup"
    log "=================================================="
    
    check_permissions
    check_staging_directory
    create_directories
    create_plugin_files
    create_nginx_config
    create_docker_compose
    set_permissions
    start_services
    install_wordpress
    deploy_plugin
    create_backup_script
    verify_installation
    
    log "=================================================="
    log "Setup completed successfully!"
    log ""
    log "Access Points:"
    log "- WordPress Admin: http://localhost:9090/wp-admin (admin/admin123)"
    log "- phpMyAdmin: http://localhost:9081"
    log "- Website: http://localhost:9090"
    log ""
    log "Next Steps:"
    log "1. Copy plugin files to: $STAGING_PATH/wp-content/plugins/$PLUGIN_NAME/"
    log "2. Run: cd $STAGING_PATH && docker-compose exec wordpress wp plugin activate $PLUGIN_NAME"
    log "3. Copy the database initialization SQL to: $STAGING_PATH/mysql-init/"
    log "4. Restart database container to run initialization"
    log ""
    log "Backup script available at: $STAGING_PATH/backup.sh"
}

# Run main function
main "$@"