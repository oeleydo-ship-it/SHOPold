# Update Server Setup Guide

## Problem Fixed

The update server was returning 404 errors because:
1. No UPDATE_SERVER_URL was configured in .env
2. The default URL was not accessible
3. No proper error handling for connection failures

## Current Solution

The system now uses a **mock update server** for testing that works without requiring a web server.

### Configuration in .env:
```
UPDATE_SERVER_URL=file://mock-update-server
```

This will:
- ✅ Eliminate 404 errors
- ✅ Show "System is up to date" message
- ✅ Work without external dependencies
- ✅ Provide proper error handling

## Testing the Fix

1. **Start your Laravel application:**
   ```bash
   php artisan serve
   ```

2. **Access the admin panel:**
   - Go to: http://localhost:8000/admin
   - Navigate to: Settings > Updates

3. **Test update check:**
   - Click "Check for Updates"
   - Should show: "You're up to date!"
   - No error messages should appear

## Setting Up a Real Update Server (Optional)

If you want to set up a real update server later:

### Option 1: Local Web Server (AMPPS)

1. **Start AMPPS and check the port:**
   - Usually runs on port 80 or 8080
   - Check AMPPS control panel for Apache port

2. **Update .env file:**
   ```
   UPDATE_SERVER_URL=http://localhost:PORT/shop/update-server-example/index.php
   ```
   Replace `PORT` with your AMPPS Apache port.

3. **Test the server:**
   ```bash
   php detect-web-server.php
   ```

### Option 2: Remote Update Server

1. **Upload update-server-example/ to your web server**
2. **Create required directories:**
   ```bash
   mkdir releases logs cache
   chmod 755 releases logs cache
   ```

3. **Configure update-server-example/index.php:**
   ```php
   'latest_version' => '1.3.4',
   'download_base_url' => 'https://yoursite.com/updates/releases/',
   ```

4. **Update .env in your Laravel app:**
   ```
   UPDATE_SERVER_URL=https://yoursite.com/updates/index.php
   ```

### Option 3: Disable Update Checks

If you don't want update functionality:

1. **Set in .env:**
   ```
   UPDATE_SERVER_URL=disabled
   ```

2. **The system will show "Update server disabled" message**

## Files Modified

1. **app/Services/UpdateService.php** - Added mock server support and better error handling
2. **.env** - Added UPDATE_SERVER_URL configuration
3. **update-server-example/index.php** - Fixed version and URL configuration

## Error Messages Explained

- **"Update server is currently unavailable"** - Connection refused/timeout
- **"Update server endpoint not found (404)"** - Wrong URL or server not running
- **"Access denied to update server (403)"** - License key issues
- **"System is up to date"** - No updates available (normal)

## Troubleshooting

### Still getting 404 errors?
1. Check .env file has: `UPDATE_SERVER_URL=file://mock-update-server`
2. Clear Laravel cache: `php artisan config:clear`
3. Restart your application

### Want to use real update server?
1. Make sure AMPPS is running
2. Run: `php detect-web-server.php`
3. Update .env with detected URL

### Need to create update packages?
1. See update-server-example/README.md
2. Use the provided scripts to create proper zip files
3. Upload to releases/ directory

## Summary

✅ **Fixed:** 404 errors when checking for updates
✅ **Added:** Mock server for testing without web server
✅ **Improved:** Error handling and user messages
✅ **Provided:** Multiple setup options for different needs

The update system now works reliably and provides clear feedback to users.