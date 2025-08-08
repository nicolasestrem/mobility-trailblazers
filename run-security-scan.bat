@echo off
REM Security Scan Script for Mobility Trailblazers Plugin (Windows)
REM Runs PHP CodeSniffer with WordPress Security Standards

echo ======================================
echo Mobility Trailblazers Security Scanner
echo ======================================
echo.

REM Check if phpcs is installed
where phpcs >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: PHP CodeSniffer ^(phpcs^) is not installed.
    echo Please install it using: composer global require squizlabs/php_codesniffer
    exit /b 1
)

REM Set the plugin directory
set PLUGIN_DIR=%~dp0

echo Scanning plugin directory: %PLUGIN_DIR%
echo.

REM Run security-focused scan
echo 1. Running Security Scan...
echo ---------------------------
phpcs --standard=WordPress-Security --severity=5 --extensions=php --report=full --report-width=120 -p "%PLUGIN_DIR%includes" "%PLUGIN_DIR%templates" "%PLUGIN_DIR%mobility-trailblazers.php"

REM Run nonce verification check
echo.
echo 2. Checking Nonce Verification...
echo ----------------------------------
phpcs --standard=WordPress-Security --sniffs=WordPress.Security.NonceVerification --extensions=php --report=summary "%PLUGIN_DIR%"

REM Run escape output check
echo.
echo 3. Checking Output Escaping...
echo -------------------------------
phpcs --standard=WordPress-Security --sniffs=WordPress.Security.EscapeOutput --extensions=php --report=summary "%PLUGIN_DIR%"

REM Run SQL injection check
echo.
echo 4. Checking SQL Queries...
echo ---------------------------
phpcs --standard=WordPress-Security --sniffs=WordPress.DB.PreparedSQL --extensions=php --report=summary "%PLUGIN_DIR%"

REM Generate detailed report
echo.
echo 5. Generating Detailed Report...
echo ---------------------------------
phpcs --standard=WordPress-Security --severity=1 --extensions=php --report=json --report-file="%PLUGIN_DIR%security-report.json" "%PLUGIN_DIR%"

echo.
echo Security scan complete!
echo Detailed report saved to: security-report.json
echo.
echo ======================================
pause
