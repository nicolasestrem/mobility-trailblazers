@echo off
REM =====================================================
REM FINAL CLEANUP - Remove trash files permanently
REM Date: 2025-08-20
REM =====================================================

echo Starting final cleanup...
echo.

cd /d "E:\OneDrive\CoWorkSpace\Tech Stack\Platform\plugin\mobility-trailblazers"

REM Remove all _trash files and directories
echo Removing trash files...
for /f "delims=" %%i in ('dir /b _trash*') do (
    if exist "%%i\*" (
        rmdir /s /q "%%i"
        echo Removed directory: %%i
    ) else (
        del /q "%%i"
        echo Removed file: %%i
    )
)

echo.
echo Cleanup complete!
echo.
echo Repository is now clean. Remember to:
echo   1. git add -A
echo   2. git commit -m "chore: remove debug files and sensitive credentials"
echo   3. git push
echo.
pause
