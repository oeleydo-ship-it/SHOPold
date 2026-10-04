#!/bin/bash

# Laravel Shop Installation Reset Script
# This script resets the installation status for fresh installations

echo "🔄 Laravel Shop Installation Reset Tool"
echo "======================================"

# Check if storage/installed exists
if [ -f "storage/installed" ]; then
    echo "📁 Found installation marker: storage/installed"
    
    # Ask for confirmation
    read -p "⚠️  Are you sure you want to reset the installation? (y/N): " confirm
    
    if [[ $confirm == [yY] || $confirm == [yY][eE][sS] ]]; then
        # Remove installation marker
        rm -f storage/installed
        echo "✅ Removed installation marker"
        
        # Clear Laravel caches if artisan is available
        if [ -f "artisan" ]; then
            echo "🧹 Clearing Laravel caches..."
            php artisan config:clear 2>/dev/null || echo "⚠️  Could not clear config cache"
            php artisan cache:clear 2>/dev/null || echo "⚠️  Could not clear application cache"
            php artisan view:clear 2>/dev/null || echo "⚠️  Could not clear view cache"
            php artisan route:clear 2>/dev/null || echo "⚠️  Could not clear route cache"
            echo "✅ Cache clearing completed"
        fi
        
        echo ""
        echo "🎉 Installation reset complete!"
        echo "You can now run a fresh installation by visiting:"
        echo "   • /install.php"
        echo "   • /install"
        echo ""
        echo "🔗 Quick links:"
        echo "   • Force install: /install.php?force=1"
        echo "   • Debug info: /debug-install.php"
        echo ""
    else
        echo "❌ Reset cancelled"
    fi
else
    echo "ℹ️  No installation marker found - app appears to be uninstalled"
    echo "You can proceed with installation normally"
fi

echo ""
echo "🛡️  Security reminder: Remove reset tools after use in production!"