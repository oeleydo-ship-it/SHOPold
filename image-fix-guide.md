# Live Server Image Fix Guide

## Quick Fix Steps

### 1. Upload and Run the Fix Script
1. Upload `live-server-fix.php` to your website root directory
2. Visit: `https://yourdomain.com/live-server-fix.php`
3. Click **"Fix All Issues"** button

### 2. What the Script Does
- ✅ Updates `APP_URL` in `.env` to match your live domain
- ✅ Clears Laravel caches (config, route, view, application)
- ✅ Creates storage symlink using multiple methods
- ✅ Sets proper file permissions
- ✅ Tests image access

### 3. Available Options

| Button | Purpose |
|--------|---------|
| **Fix All Issues** | Complete fix - recommended for most users |
| **Create Symlink Only** | Only attempts to create the storage symlink |
| **Run Tests** | Checks current setup without making changes |

## Common Issues & Solutions

### Issue 1: Images Still Not Showing
**Cause:** Browser cache or CDN cache
**Solution:** 
- Clear your browser cache (Ctrl+F5)
- If using Cloudflare, purge cache in dashboard

### Issue 2: Symlink Creation Failed
**Manual Fix via SSH:**
```bash
# Remove existing public/storage
rm -rf public/storage

# Create symlink
ln -sf /full/path/to/storage/app/public public/storage

# Or use Laravel command
php artisan storage:link
```

**Manual Fix via cPanel:**
1. Go to File Manager
2. Navigate to `public` folder
3. Delete `storage` folder if it exists
4. Create symbolic link: `storage` → `../storage/app/public`

### Issue 3: Permission Errors
**Fix via SSH:**
```bash
# Set storage permissions
chmod -R 755 storage/
chmod -R 755 public/storage/

# Set ownership (replace 'username' with your username)
chown -R username:username storage/
chown -R username:username public/storage/
```

### Issue 4: APP_URL Still Wrong
**Manual Fix:**
1. Edit `.env` file
2. Change `APP_URL=http://localhost:8000` 
3. To: `APP_URL=https://yourdomain.com`
4. Clear config cache: `php artisan config:clear`

## Verification Steps

### 1. Check Symlink
Visit: `https://yourdomain.com/storage/` 
- Should show "403 Forbidden" (this is correct)
- Should NOT show "404 Not Found"

### 2. Test Image URL
If you have images, try accessing:
`https://yourdomain.com/storage/products/imagename.jpg`

### 3. Check Admin Media Library
Go to your admin panel → Media Library
- Images should display properly
- No broken image icons

## Fallback Method

If symlinks don't work on your server:

1. The script automatically creates a fallback directory structure
2. Files are copied (not linked) from `storage/app/public` to `public/storage`
3. Use the generated `sync-storage.php` script to sync new uploads:
   - Visit: `https://yourdomain.com/sync-storage.php`
   - Run this after uploading new images

## Security Note

**Important:** Delete these files after use:
- `live-server-fix.php`
- `sync-storage.php` (if created)

## Still Having Issues?

1. Check your server error logs
2. Verify file permissions are correct
3. Contact your hosting provider about symlink support
4. Consider using the fallback directory method

## Server-Specific Notes

### Shared Hosting
- Some shared hosts disable symlink creation
- Use the fallback method if symlinks fail
- Check with hosting provider about symlink support

### VPS/Dedicated Servers
- Usually support all symlink methods
- May need to adjust file ownership
- SSH access recommended for troubleshooting

### cPanel Hosting
- File Manager can create symbolic links
- Look for "Create Link" option in File Manager
- Some hosts disable this feature