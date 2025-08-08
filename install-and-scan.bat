@echo off
REM Installation and Security Scan Script for Mobility Trailblazers Plugin
REM This script will install dependencies and run security scans

echo ==========================================
echo Mobility Trailblazers Security Setup
echo ==========================================
echo.

REM Check if composer is installed
where composer >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: Composer is not installed.
    echo.
    echo Please install Composer first:
    echo 1. Download from: https://getcomposer.org/download/
    echo 2. Run the installer and follow the instructions
    echo 3. Restart your command prompt and run this script again
    echo.
    pause
    exit /b 1
)

REM Navigate to plugin directory
cd /d "%~dp0"

echo Current directory: %CD%
echo.

REM Check if vendor directory exists
if not exist "vendor" (
    echo Installing PHP CodeSniffer and WordPress Coding Standards...
    echo This may take a few minutes...
    echo.
    composer install --no-interaction
    if %errorlevel% neq 0 (
        echo ERROR: Failed to install dependencies.
        echo Please check your internet connection and try again.
        pause
        exit /b 1
    )
    echo.
    echo Installation complete!
    echo.
) else (
    echo Dependencies already installed.
    echo Updating to latest versions...
    composer update --no-interaction
    echo.
)

REM Check if phpcs is now available
if not exist "vendor\bin\phpcs.bat" (
    echo ERROR: PHP CodeSniffer was not installed properly.
    echo Please delete the vendor folder and try again.
    pause
    exit /b 1
)

echo ==========================================
echo Running Security Scans
echo ==========================================
echo.

REM Create reports directory if it doesn't exist
if not exist "security-reports" mkdir security-reports

REM Run different security scans
echo 1. General Security Scan
echo ------------------------
call vendor\bin\phpcs --standard=WordPress-Security --severity=5 --extensions=php --report=full --report-width=120 includes templates mobility-trailblazers.php > security-reports\general-security.txt 2>&1
echo    Report saved to: security-reports\general-security.txt
echo.

echo 2. Nonce Verification Check
echo ----------------------------
call vendor\bin\phpcs --standard=WordPress-Security --sniffs=WordPress.Security.NonceVerification --extensions=php --report=summary . > security-reports\nonce-verification.txt 2>&1
echo    Report saved to: security-reports\nonce-verification.txt
echo.

echo 3. Output Escaping Check
echo ------------------------
call vendor\bin\phpcs --standard=WordPress-Security --sniffs=WordPress.Security.EscapeOutput --extensions=php --report=summary . > security-reports\output-escaping.txt 2>&1
echo    Report saved to: security-reports\output-escaping.txt
echo.

echo 4. SQL Injection Check
echo ----------------------
call vendor\bin\phpcs --standard=WordPress-Security --sniffs=WordPress.DB.PreparedSQL --extensions=php --report=summary . > security-reports\sql-injection.txt 2>&1
echo    Report saved to: security-reports\sql-injection.txt
echo.

echo 5. Full Detailed Report
echo -----------------------
call vendor\bin\phpcs --standard=WordPress-Security --extensions=php --report=json . > security-reports\full-report.json 2>&1
echo    Report saved to: security-reports\full-report.json
echo.

echo ==========================================
echo Security scan complete!
echo ==========================================
echo.
echo All reports have been saved to the 'security-reports' folder.
echo.
echo You can also run specific scans using composer:
echo   composer run security-scan
echo   composer run check-nonce
echo   composer run check-escaping
echo   composer run check-sql
echo.
pause
