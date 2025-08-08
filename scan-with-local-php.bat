@echo off
REM Direct PHP Security Scanner
REM Uses your local PHP installation directly

echo ==========================================
echo Mobility Trailblazers Security Scanner
echo Using Local PHP Installation
echo ==========================================
echo.

REM Set the full path to your PHP executable
set PHP_EXE=E:\OneDrive\php-8.4.11-nts-Win32-vs17-x64\php.exe

REM Check if PHP exists
if not exist "%PHP_EXE%" (
    echo ERROR: PHP not found at %PHP_EXE%
    echo Please check the path and try again.
    pause
    exit /b 1
)

REM Navigate to plugin directory
cd /d "%~dp0"

echo Using PHP from: %PHP_EXE%
echo Current directory: %CD%
echo.

REM Run the manual security scanner
echo Running security scan...
echo ------------------------
"%PHP_EXE%" manual-security-scan.php

echo.
echo ==========================================
echo.

REM Offer to install Composer locally if needed
if not exist composer.phar (
    echo Would you like to install Composer for more comprehensive scanning? (Y/N)
    set /p INSTALL_COMPOSER=
    if /i "%INSTALL_COMPOSER%"=="Y" (
        echo.
        echo Downloading Composer...
        "%PHP_EXE%" -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
        "%PHP_EXE%" composer-setup.php
        "%PHP_EXE%" -r "unlink('composer-setup.php');"
        
        if exist composer.phar (
            echo.
            echo Composer installed! Installing security tools...
            "%PHP_EXE%" composer.phar install --no-interaction
            
            echo.
            echo You can now run comprehensive scans with:
            echo   %PHP_EXE% composer.phar run security-scan
        )
    )
) else (
    echo.
    echo Composer is already installed locally.
    echo.
    echo Run comprehensive scans with:
    echo   %PHP_EXE% composer.phar run security-scan
    echo   %PHP_EXE% composer.phar run check-nonce
    echo   %PHP_EXE% composer.phar run check-escaping
    echo   %PHP_EXE% composer.phar run check-sql
)

echo.
pause
