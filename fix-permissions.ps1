# PowerShell script to fix WordPress file permissions in Docker container
# Run this if you encounter plugin installation issues again

Write-Host "Fixing WordPress file permissions..." -ForegroundColor Green

# Navigate to the devcontainer directory
Set-Location "E:\OneDrive\CoWorkSpace\Tech Stack\Platform\plugin\mobility-trailblazers\.devcontainer"

# Fix ownership (everything should be owned by www-data)
Write-Host "Setting correct ownership..." -ForegroundColor Yellow
docker-compose exec wordpress chown -R www-data:www-data /var/www/html/wp-content

# Fix permissions for directories and files
Write-Host "Setting correct permissions..." -ForegroundColor Yellow
docker-compose exec wordpress find /var/www/html/wp-content -type d -exec chmod 755 {} \;
docker-compose exec wordpress find /var/www/html/wp-content -type f -exec chmod 644 {} \;

# Make plugins and uploads directories writable
Write-Host "Making plugins and uploads directories writable..." -ForegroundColor Yellow
docker-compose exec wordpress chmod -R 775 /var/www/html/wp-content/plugins
docker-compose exec wordpress chmod -R 775 /var/www/html/wp-content/uploads

# Verify permissions
Write-Host "Verifying permissions..." -ForegroundColor Green
docker-compose exec wordpress ls -la /var/www/html/wp-content/

Write-Host "✓ File permissions fixed!" -ForegroundColor Green
Write-Host "You should now be able to install plugins in WordPress." -ForegroundColor Cyan

# Keep window open
Read-Host "Press Enter to continue..."
