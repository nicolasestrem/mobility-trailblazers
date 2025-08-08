@echo off
REM PHP Configuration Checker and Composer Setup
REM For Mobility Trailblazers Plugin

echo ==========================================
echo PHP Configuration and Composer Setup
echo ==========================================
echo.

REM Set PHP path
set PHP_PATH=E:\OneDrive\php-8.4.11-nts-Win32-vs17-x64

REM Check if PHP is accessible
echo Checking PHP installation...
"%PHP_PATH%\php.exe" -v
if %errorlevel% neq 0 (
    echo ERROR: PHP not found at %PHP_PATH%
    echo Please check the PHP installation path.
    pause
    exit /b 1
)

echo.
echo PHP Version found!
echo.

REM Check PHP configuration
echo Checking PHP configuration...
echo -------------------------------
"%PHP_PATH%\php.exe" -r "echo 'PHP ini file: ' . php_ini_loaded_file() . PHP_EOL;"
echo.

REM Check required extensions
echo Checking required extensions...
echo -------------------------------
"%PHP_PATH%\php.exe" -r "echo 'curl: ' . (extension_loaded('curl') ? 'OK' : 'MISSING') . PHP_EOL;"
"%PHP_PATH%\php.exe" -r "echo 'mbstring: ' . (extension_loaded('mbstring') ? 'OK' : 'MISSING') . PHP_EOL;"
"%PHP_PATH%\php.exe" -r "echo 'openssl: ' . (extension_loaded('openssl') ? 'OK' : 'MISSING') . PHP_EOL;"
"%PHP_PATH%\php.exe" -r "echo 'json: ' . (extension_loaded('json') ? 'OK' : 'MISSING') . PHP_EOL;"
"%PHP_PATH%\php.exe" -r "echo 'zip: ' . (extension_loaded('zip') ? 'OK' : 'MISSING') . PHP_EOL;"
echo.

REM List all loaded extensions
echo All loaded extensions:
echo ----------------------
"%PHP_PATH%\php.exe" -m
echo.

REM Navigate to plugin directory
cd /d "%~dp0"
echo Current directory: %CD%
echo.

REM Check if Composer is installed
echo Checking Composer installation...
where composer >nul 2>nul
if %errorlevel% neq 0 (
    echo.
    echo Composer is not installed globally.
    echo.
    echo Would you like to download Composer? (Y/N)
    set /p INSTALL_COMPOSER=
    if /i "%INSTALL_COMPOSER%"=="Y" (
        echo.
        echo Downloading Composer installer...
        "%PHP_PATH%\php.exe" -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
        
        echo Installing Composer locally...
        "%PHP_PATH%\php.exe" composer-setup.php
        
        echo Cleaning up...
        "%PHP_PATH%\php.exe" -r "unlink('composer-setup.php');"
        
        if exist composer.phar (
            echo.
            echo Composer installed successfully as composer.phar
            echo You can now use: php composer.phar install
            
            echo.
            echo Installing dependencies...
            "%PHP_PATH%\php.exe" composer.phar install --no-interaction
        ) else (
            echo ERROR: Composer installation failed.
        )
    )
) else (
    echo Composer is installed globally.
    echo.
    echo Installing dependencies...
    composer install --no-interaction
)

echo.
echo ==========================================
echo Setup Complete!
echo ==========================================
echo.
echo Next steps:
echo 1. If all extensions show OK, you can run security scans
echo 2. If any extensions show MISSING, check the ext folder in your PHP directory
echo 3. Make sure the required DLL files are in the ext folder
echo.
echo To run security scan:
echo   - With Composer: composer run security-scan
echo   - Manual scan: php manual-security-scan.php
echo.
pause
