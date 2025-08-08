@echo off
REM Simple Security Scanner for Mobility Trailblazers Plugin
REM Runs without requiring PHP CodeSniffer

echo ==========================================
echo Mobility Trailblazers Security Scanner
echo ==========================================
echo.

REM Check if PHP is installed
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: PHP is not installed or not in PATH.
    echo.
    echo Please ensure PHP is installed and added to your system PATH.
    echo You can download PHP from: https://windows.php.net/download/
    echo.
    pause
    exit /b 1
)

REM Navigate to plugin directory
cd /d "%~dp0"

echo Running manual security scan...
echo.

REM Run the manual scanner
php manual-security-scan.php

echo.
echo ==========================================
echo.
echo Additional Options:
echo.
echo 1. To install PHP CodeSniffer and WordPress standards:
echo    Run: install-and-scan.bat
echo.
echo 2. To run scans with Composer (after installation):
echo    composer run security-scan
echo    composer run check-nonce
echo    composer run check-escaping
echo    composer run check-sql
echo.
pause
