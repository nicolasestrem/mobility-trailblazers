# PowerShell script to view WordPress debug logs
# This script helps you monitor WordPress debug logs in real-time

param(
    [int]$Lines = 50,
    [switch]$Follow,
    [switch]$Clear
)

$ContainerName = "mobility_wordpress_dev"
$LogPaths = @(
    "/var/www/html/wp-content/debug.log",
    "/var/www/html/wp-content/debug/debug.log", 
    "/var/www/html/wp-content/plugins/mobility-trailblazers/debug.log"
)

Write-Host "WordPress Debug Log Viewer" -ForegroundColor Green
Write-Host "===========================" -ForegroundColor Green

if ($Clear) {
    Write-Host "Clearing debug logs..." -ForegroundColor Yellow
    foreach ($LogPath in $LogPaths) {
        docker exec $ContainerName sh -c "truncate -s 0 $LogPath 2>/dev/null || true"
    }
    Write-Host "Debug logs cleared!" -ForegroundColor Green
    return
}

# Check which log files exist
$ExistingLogs = @()
foreach ($LogPath in $LogPaths) {
    $Result = docker exec $ContainerName sh -c "test -f $LogPath && echo 'exists' || echo 'missing'"
    if ($Result.Trim() -eq 'exists') {
        $ExistingLogs += $LogPath
        Write-Host "Found log: $LogPath" -ForegroundColor Cyan
    }
}

if ($ExistingLogs.Count -eq 0) {
    Write-Host "No debug log files found. Logs will be created when errors occur." -ForegroundColor Yellow
    Write-Host "Expected locations:" -ForegroundColor Gray
    foreach ($LogPath in $LogPaths) {
        Write-Host "  - $LogPath" -ForegroundColor Gray
    }
    return
}

# Display logs
foreach ($LogPath in $ExistingLogs) {
    Write-Host "`nShowing last $Lines lines from: $LogPath" -ForegroundColor Yellow
    Write-Host ("-" * 60) -ForegroundColor Gray
    
    if ($Follow) {
        Write-Host "Following log file (Ctrl+C to stop)..." -ForegroundColor Green
        docker exec $ContainerName tail -f -n $Lines $LogPath
    } else {
        docker exec $ContainerName tail -n $Lines $LogPath
    }
}

if (-not $Follow) {
    Write-Host "`nTo follow logs in real-time, use: .\debug-logs.ps1 -Follow" -ForegroundColor Cyan
    Write-Host "To clear logs, use: .\debug-logs.ps1 -Clear" -ForegroundColor Cyan
}
