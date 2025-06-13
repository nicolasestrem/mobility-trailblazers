#!/bin/bash

# =============================================================================
# MOBILITY TRAILBLAZERS - JURY IMPORT FOR YOUR DOCKER SETUP
# =============================================================================

echo "🚀 Starting Jury Import Process for Mobility Trailblazers"
echo "📋 Your Docker Setup Configuration:"
echo "   - Container: mobility_wordpress_STAGING"
echo "   - WP-CLI: mobility_wpcli_STAGING" 
echo "   - Database: wordpress_db"
echo "   - User: wp_user"
echo "   - Port: 9989"
echo ""

# =============================================================================
# STEP 1: VERIFY CONTAINERS ARE RUNNING
# =============================================================================

echo "🔍 Step 1: Checking container status..."

# Check if containers are running
WORDPRESS_STATUS=$(docker ps --filter "name=mobility_wordpress_STAGING" --format "table {{.Status}}" | grep -v STATUS)
WPCLI_STATUS=$(docker ps --filter "name=mobility_wpcli_STAGING" --format "table {{.Status}}" | grep -v STATUS)
DB_STATUS=$(docker ps --filter "name=mobility_mariadb_STAGING" --format "table {{.Status}}" | grep -v STATUS)

if [ -z "$WORDPRESS_STATUS" ]; then
    echo "❌ ERROR: WordPress container not running. Start with: docker-compose up -d"
    exit 1
fi

if [ -z "$WPCLI_STATUS" ]; then
    echo "❌ ERROR: WP-CLI container not running. Start with: docker-compose up -d"
    exit 1
fi

if [ -z "$DB_STATUS" ]; then
    echo "❌ ERROR: Database container not running. Start with: docker-compose up -d"
    exit 1
fi

echo "✅ All containers are running"
echo "   - WordPress: $WORDPRESS_STATUS"
echo "   - WP-CLI: $WPCLI_STATUS" 
echo "   - Database: $DB_STATUS"
echo ""

# =============================================================================
# STEP 2: CHECK WORDPRESS INSTALLATION
# =============================================================================

echo "🔍 Step 2: Verifying WordPress installation..."

# Check if WordPress is installed
WP_INSTALLED=$(docker exec mobility_wpcli_STAGING wp core is-installed --path="/var/www/html" 2>/dev/null && echo "true" || echo "false")

if [ "$WP_INSTALLED" = "false" ]; then
    echo "❌ ERROR: WordPress is not installed yet."
    echo "📝 Install WordPress first with:"
    echo "   docker exec mobility_wpcli_STAGING wp core install \\"
    echo "     --path='/var/www/html' \\"
    echo "     --url='http://localhost:9989' \\"
    echo "     --title='Mobility Trailblazers' \\"
    echo "     --admin_user='admin' \\"
    echo "     --admin_password='admin123!' \\"
    echo "     --admin_email='admin@mobility-trailblazers.local' \\"
    echo "     --skip-email"
    exit 1
fi

echo "✅ WordPress is installed and ready"
echo ""

# =============================================================================
# STEP 3: CHECK PLUGIN STATUS
# =============================================================================

echo "🔍 Step 3: Checking Mobility Trailblazers plugin..."

# Check if plugin exists
PLUGIN_EXISTS=$(docker exec mobility_wpcli_STAGING ls /var/www/html/wp-content/plugins/ 2>/dev/null | grep -c "mobility-trailblazers" || echo "0")

if [ "$PLUGIN_EXISTS" = "0" ]; then
    echo "❌ ERROR: Mobility Trailblazers plugin not found"
    echo "📝 Deploy the plugin first to:"
    echo "   /mnt/dietpi_userdata/docker-files/STAGING/wordpress_data/wp-content/plugins/mobility-trailblazers/"
    exit 1
fi

# Check if plugin is activated
PLUGIN_ACTIVE=$(docker exec mobility_wpcli_STAGING wp plugin is-active mobility-trailblazers --path="/var/www/html" 2>/dev/null && echo "true" || echo "false")

if [ "$PLUGIN_ACTIVE" = "false" ]; then
    echo "⚠️  Plugin exists but not activated. Activating now..."
    docker exec mobility_wpcli_STAGING wp plugin activate mobility-trailblazers --path="/var/www/html"
    if [ $? -eq 0 ]; then
        echo "✅ Plugin activated successfully"
    else
        echo "❌ ERROR: Failed to activate plugin"
        exit 1
    fi
else
    echo "✅ Plugin is already activated"
fi

# Verify custom post types are registered
echo "🔍 Checking custom post types..."
POST_TYPES=$(docker exec mobility_wpcli_STAGING wp post-type list --path="/var/www/html" --format=csv | grep "mt_")

if echo "$POST_TYPES" | grep -q "mt_jury"; then
    echo "✅ Custom post type 'mt_jury' is registered"
else
    echo "❌ ERROR: Custom post type 'mt_jury' not found"
    echo "📝 The plugin may not be working correctly"
    exit 1
fi
echo ""

# =============================================================================
# STEP 4: DEPLOY JURY IMPORT SCRIPT
# =============================================================================

echo "🔍 Step 4: Deploying jury import script..."

# Check if jury-import.php exists
IMPORT_SCRIPT_PATH="/mnt/dietpi_userdata/docker-files/STAGING/wordpress_data/wp-content/jury-import.php"

if [ ! -f "$IMPORT_SCRIPT_PATH" ]; then
    echo "❌ ERROR: jury-import.php not found at:"
    echo "   $IMPORT_SCRIPT_PATH"
    echo ""
    echo "📝 You need to create this file with the jury import script content."
    echo "   The script should contain all 20 jury members from the PDF."
    exit 1
fi

echo "✅ Jury import script found"

# Set correct permissions
sudo chown 33:33 "$IMPORT_SCRIPT_PATH"
sudo chmod 644 "$IMPORT_SCRIPT_PATH"
echo "✅ Permissions set correctly"
echo ""

# =============================================================================
# STEP 5: EXECUTE JURY IMPORT
# =============================================================================

echo "🚀 Step 5: Executing jury import..."

# Run the jury import script
echo "📝 Running: docker exec mobility_wpcli_STAGING wp eval-file wp-content/jury-import.php --path='/var/www/html'"
echo ""

docker exec mobility_wpcli_STAGING wp eval-file wp-content/jury-import.php --path="/var/www/html"

if [ $? -eq 0 ]; then
    echo ""
    echo "🎉 Jury import completed successfully!"
else
    echo ""
    echo "❌ ERROR: Jury import failed"
    echo "📝 Check the error messages above for details"
    exit 1
fi

# =============================================================================
# STEP 6: VERIFICATION
# =============================================================================

echo ""
echo "🔍 Step 6: Verifying import results..."

# Count jury members
JURY_COUNT=$(docker exec mobility_wpcli_STAGING wp post list --post_type=mt_jury --format=count --path="/var/www/html" 2>/dev/null || echo "0")
echo "📊 Total jury members imported: $JURY_COUNT"

if [ "$JURY_COUNT" != "20" ]; then
    echo "⚠️  WARNING: Expected 20 jury members, but found $JURY_COUNT"
else
    echo "✅ Correct number of jury members imported"
fi

# Check president
PRESIDENT_COUNT=$(docker exec mobility_wpcli_STAGING wp post list --post_type=mt_jury --meta_key=_mt_jury_is_president --meta_value=1 --format=count --path="/var/www/html" 2>/dev/null || echo "0")
echo "👨‍💼 Presidents found: $PRESIDENT_COUNT"

if [ "$PRESIDENT_COUNT" = "1" ]; then
    PRESIDENT_NAME=$(docker exec mobility_wpcli_STAGING wp post list --post_type=mt_jury --meta_key=_mt_jury_is_president --meta_value=1 --field=post_title --path="/var/www/html" 2>/dev/null)
    echo "✅ President: $PRESIDENT_NAME"
else
    echo "⚠️  WARNING: Expected 1 president, found $PRESIDENT_COUNT"
fi

# Check vice president
VP_COUNT=$(docker exec mobility_wpcli_STAGING wp post list --post_type=mt_jury --meta_key=_mt_jury_is_vice_president --meta_value=1 --format=count --path="/var/www/html" 2>/dev/null || echo "0")
echo "👨‍💼 Vice Presidents found: $VP_COUNT"

if [ "$VP_COUNT" = "1" ]; then
    VP_NAME=$(docker exec mobility_wpcli_STAGING wp post list --post_type=mt_jury --meta_key=_mt_jury_is_vice_president --meta_value=1 --field=post_title --path="/var/www/html" 2>/dev/null)
    echo "✅ Vice President: $VP_NAME"
else
    echo "⚠️  WARNING: Expected 1 vice president, found $VP_COUNT"
fi

# =============================================================================
# STEP 7: ACCESS INFORMATION
# =============================================================================

echo ""
echo "🌐 Step 7: Access Information"
echo "================================"
echo "🔗 WordPress Frontend: http://localhost:9989"
echo "🔗 WordPress Admin: http://localhost:9989/wp-admin"
echo "🔗 phpMyAdmin: http://localhost:9081"
echo ""
echo "🔑 Login Credentials:"
echo "   WordPress Admin: admin / admin123!"
echo "   Database: wp_user / Wp7kL9xP2qR7vN6wE3zY4uC1sA5f"
echo "   Database Root: root / Rt9mK3nQ8xY7bV5cZ2wE4rT6yU1i"
echo ""
echo "📋 Next Steps:"
echo "1. Visit WordPress Admin: http://localhost:9989/wp-admin"
echo "2. Go to 'MT Award System' menu to see the jury members"
echo "3. Test jury functionality with evaluation interface"
echo "4. Use shortcode [mt_jury_members] on any page to display jury"
echo ""

# =============================================================================
# STEP 8: TESTING COMMANDS
# =============================================================================

echo "🧪 Testing Commands (run these manually to verify):"
echo "================================"
echo ""
echo "# List all jury members:"
echo "docker exec mobility_wpcli_STAGING wp post list --post_type=mt_jury --path='/var/www/html'"
echo ""
echo "# Show jury member details:"
echo "docker exec mobility_wpcli_STAGING wp post list --post_type=mt_jury --format=table --path='/var/www/html'"
echo ""
echo "# Check president specifically:"
echo "docker exec mobility_wpcli_STAGING wp post list --post_type=mt_jury --meta_key=_mt_jury_is_president --meta_value=1 --format=table --path='/var/www/html'"
echo ""
echo "# Check database tables:"
echo "docker exec mobility_mariadb_STAGING mysql -u wp_user -pWp7kL9xP2qR7vN6wE3zY4uC1sA5f wordpress_db -e 'SHOW TABLES;'"
echo ""

echo "🎯 Import process completed!"