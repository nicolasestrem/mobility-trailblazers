# PowerShell script to fix Docker Compose redis issue
# Navigate to the devcontainer directory
Set-Location "E:\OneDrive\CoWorkSpace\Tech Stack\Platform\plugin\mobility-trailblazers\.devcontainer"

Write-Host "Current directory: $(Get-Location)" -ForegroundColor Green

# Stop and remove all containers, networks, and volumes
Write-Host "Stopping and removing all containers..." -ForegroundColor Yellow
docker-compose down -v --remove-orphans

# Clean up Docker system
Write-Host "Cleaning up Docker system..." -ForegroundColor Yellow
docker system prune -af

# Remove any dangling images
Write-Host "Removing dangling images..." -ForegroundColor Yellow
docker image prune -af

# Build and start the services
Write-Host "Building and starting services..." -ForegroundColor Green
docker-compose up -d --build --force-recreate

# Check the status
Write-Host "Checking container status..." -ForegroundColor Green
docker-compose ps

Write-Host "Docker setup complete!" -ForegroundColor Green
Write-Host "WordPress should be available at: http://localhost:8080" -ForegroundColor Cyan
Write-Host "phpMyAdmin should be available at: http://localhost:8081" -ForegroundColor Cyan

# Keep window open
Read-Host "Press Enter to continue..."
