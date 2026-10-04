@echo off
REM Laravel Shop Installation Reset Script for Windows
REM This script resets the installation status for fresh installations

echo.
echo 🔄 Laravel Shop Installation Reset Tool
echo ======================================
echo.

REM Check if storage/installed exists
if exist "storage\installed" (
    echo 📁 Found installation marker: storage\installed
    echo.
    
    REM Ask for confirmation
    set /p confirm="⚠️  Are you sure you want to reset the installation? (y/N): "
    
    if /i "%confirm%"=="y" goto :reset
    if /i "%confirm%"=="yes" goto :reset
    
    echo ❌ Reset cancelled
    goto :end
    
    :reset
    REM Remove installation marker
    del /f "storage\installed" 2>nul
    if %errorlevel%==0 (
        echo ✅ Removed installation marker
    ) else (
        echo ❌ Failed to remove installation marker
    )
    
    REM Clear Laravel caches if artisan is available
    if exist "artisan" (
        echo 🧹 Clearing Laravel caches...
        php artisan config:clear >nul 2>&1
        php artisan cache:clear >nul 2>&1
        php artisan view:clear >nul 2>&1
        php artisan route:clear >nul 2>&1
        echo ✅ Cache clearing completed
    )
    
    echo.
    echo 🎉 Installation reset complete!
    echo You can now run a fresh installation by visiting:
    echo    • /install.php
    echo    • /install
    echo.
    echo 🔗 Quick links:
    echo    • Force install: /install.php?force=1
    echo    • Debug info: /debug-install.php
    echo.
    
) else (
    echo ℹ️  No installation marker found - app appears to be uninstalled
    echo You can proceed with installation normally
    echo.
)

echo 🛡️  Security reminder: Remove reset tools after use in production!

:end
pause