# 🚀 Laravel Shop - Quick Installation Guide

## For New Live Servers & Fresh Installations

This guide covers the enhanced auto-installation system with database injection and requirements checking.

---

## 🔍 **Auto-Detection System**

Laravel Shop now automatically detects if it's installed and guides users accordingly:

- ✅ **Automatic redirection** to installer if not installed
- ✅ **Smart detection** of installation status
- ✅ **Multiple installation methods** for different scenarios
- ✅ **Force reinstallation** options
- ✅ **Reset tools** for fresh installations

---

## 📋 **Installation Methods**

### **Method 1: Automatic Web Installer (Recommended)**

1. **Upload files** to your web server
2. **Visit your domain** - you'll be automatically redirected to the installer
3. **Follow the guided process**:
   - Requirements check
   - Database configuration
   - Admin account setup
   - Store configuration
4. **Complete installation** and start using your store

**Direct URLs:**
- Main installer: `https://yourdomain.com/install.php`
- Force mode: `https://yourdomain.com/install.php?force=1`

### **Method 2: API Installation (For Control Panels)**

```bash
curl -X POST https://yourdomain.com/install.php?action=install \
  -H "Content-Type: application/json" \
  -d '{
    "db_host": "localhost",
    "db_port": 3306,
    "db_database": "your_database",
    "db_username": "your_username",
    "db_password": "your_password",
    "admin_name": "Admin User",
    "admin_email": "admin@yourdomain.com",
    "admin_password": "secure_password",
    "store_name": "Your Store Name",
    "store_currency": "USD",
    "app_url": "https://yourdomain.com"
  }'
```

### **Method 3: Laravel Routes**

If the web installer isn't available:
1. Visit: `https://yourdomain.com/install`
2. Follow the Laravel installation wizard

---

## 🔧 **Reset & Reinstallation Tools**

### **When You Need to Reset:**
- Moving to a new server
- Testing installations
- Fixing corrupted installations
- Starting fresh after errors

### **Reset Options:**

#### **1. Quick Reset (Fastest)**
```
https://yourdomain.com/reset-now.php
```
- Instant reset with one click
- Safe - doesn't delete data
- Works via web or command line

#### **2. Secure Reset Form**
```
https://yourdomain.com/reset-installation.php?show_form=1
```
- Form-based reset with confirmation
- Enter reset key: `reset-laravel-shop-installation`
- Step-by-step guidance

#### **3. Command Line Tools**
```bash
# Windows
reset-install.bat

# Linux/Mac
./reset-install.sh

# PHP (any system)
php reset-now.php
```

#### **4. Force Installation**
Add `?force=1` to any install URL:
```
https://yourdomain.com/install.php?force=1
https://yourdomain.com/install?force=1
```

---

## 🛠 **Troubleshooting Tools**

### **Debug Information**
```
https://yourdomain.com/debug-install.php
```
- Complete system diagnostics
- Requirements checking
- File system validation
- Laravel bootstrap testing

### **Simple Status Check**
```
https://yourdomain.com/simple-status.php
```
- Laravel-independent status
- Basic requirements validation
- JSON API response

### **Auto-Detection**
```
https://yourdomain.com/detect-installation.php
```
- Visual installation detection
- System status overview
- Guided next steps

---

## 📊 **API Endpoints**

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/install.php?action=status` | GET | Installation status |
| `/install.php?action=check` | GET | Requirements check |
| `/install.php?action=test-db` | POST | Database connection test |
| `/install.php?action=install` | POST | Run installation |
| `/install.php?action=debug` | GET | System debug info |
| `/simple-status.php` | GET | Simple status check |

---

## 🔐 **Security Features**

### **Safe Reset Process**
- ✅ Only removes installation markers
- ✅ Preserves database and files
- ✅ Clears caches safely
- ✅ No data loss

### **Installation Validation**
- ✅ Database connection testing
- ✅ Requirements verification
- ✅ Permission checking
- ✅ Error handling with rollback

### **Production Security**
- ⚠️ Remove reset tools after installation
- ⚠️ Change default passwords
- ⚠️ Secure file permissions
- ⚠️ Enable HTTPS

---

## 🎯 **Quick Start for New Servers**

### **Step 1: Upload & Access**
1. Upload all Laravel Shop files
2. Visit your domain
3. You'll be automatically redirected to installer

### **Step 2: If "Already Installed" Shows**
1. Visit: `https://yourdomain.com/reset-now.php`
2. Click through the reset process
3. Return to installer

### **Step 3: Complete Installation**
1. Fill in database credentials
2. Create admin account
3. Configure store settings
4. Start selling!

---

## 📞 **Need Help?**

### **Common Issues:**
- **"Already Installed"**: Use reset tools above
- **Database Errors**: Check credentials and permissions
- **File Permissions**: Ensure storage/ and bootstrap/cache/ are writable
- **Missing Extensions**: Install required PHP extensions

### **Debug Steps:**
1. Visit `/debug-install.php` for system info
2. Check `/simple-status.php` for basic status
3. Use `/reset-now.php` to reset if needed
4. Try force mode: `/install.php?force=1`

### **Support Resources:**
- 📖 Full documentation: `INSTALLATION.md`
- 🔧 Debug tools: Multiple diagnostic scripts
- 🔄 Reset options: Several reset methods
- 🛡️ Security guide: Built-in security features

---

## ✨ **What's New in Enhanced Installer**

- 🚀 **Auto-detection** on fresh servers
- 🔄 **Multiple reset options** for flexibility
- 🛠 **Comprehensive debugging** tools
- 🔌 **API integration** for control panels
- 🛡️ **Enhanced security** with safe resets
- 📊 **Real-time status** checking
- 🎯 **Force installation** modes
- 📱 **Mobile-friendly** interfaces

**Perfect for hosting providers, developers, and end users!**

---

## 🖼️ **Image Display Issues & Symlink Fixes**

If images are not displaying after installation, it's likely a symlink issue. Laravel requires a symbolic link from `public/storage` to `storage/app/public`.

### **Quick Fixes:**

#### **Option 1: Enhanced Symlink Creator (Recommended)**
1. Visit: `https://yourdomain.com/create_symlink.php`
2. Click "Create Symlink" button
3. Test image access
4. **Delete the file** after use for security

#### **Option 2: Automatic Symlink Fixer**
1. Visit: `https://yourdomain.com/fix-symlink.php`
2. The script will automatically detect and fix symlink issues
3. **Delete the file** after use for security

#### **Option 3: Laravel Artisan Command**
```bash
php artisan storage:link
```

#### **Option 4: Manual Symlink (SSH/Terminal)**
```bash
ln -s /path/to/your/site/storage/app/public /path/to/your/site/public/storage
```

#### **Option 5: cPanel File Manager**
1. Go to cPanel → File Manager
2. Navigate to your site's root directory
3. Create a symbolic link:
   - **Link Path:** `public/storage`
   - **Target:** `storage/app/public`

### **For Hosting Without Symlink Support:**

If your hosting doesn't support symlinks, the scripts will automatically create an alternative directory structure and provide instructions for manual file copying.

### **Troubleshooting Image Issues:**

1. **Check symlink status:**
   ```bash
   ls -la public/storage
   ```

2. **Verify target directory exists:**
   ```bash
   ls -la storage/app/public
   ```

3. **Test file access:**
   - Create a test file in `storage/app/public/test.txt`
   - Try accessing `https://yourdomain.com/storage/test.txt`

4. **Check file permissions:**
   ```bash
   chmod -R 755 storage/
   chmod -R 755 public/
   ```

### **Common Symlink Error Messages:**

- **"symlink(): No such file or directory"** → Target directory doesn't exist
- **"symlink(): Operation not permitted"** → Insufficient permissions or symlinks disabled
- **"symlink(): File exists"** → A file/directory already exists at the link location

### **Alternative Solutions:**

If symlinks continue to fail:

1. **Use direct file copying** (not recommended for production)
2. **Configure web server** to serve files directly from storage
3. **Contact hosting provider** about symlink support
4. **Use cloud storage** (S3, etc.) for file uploads

---

## 🔧 **Post-Installation Checklist**

After successful installation:

- [ ] **Test image uploads** in admin panel
- [ ] **Verify symlink** is working (`public/storage` → `storage/app/public`)
- [ ] **Check file permissions** (755 for directories, 644 for files)
- [ ] **Delete installation files** for security:
  - `install.php`
  - `create_symlink.php` 
  - `fix-symlink.php`
  - `reset-installation.php`
  - `test-installation.php`
- [ ] **Configure email settings** in admin panel
- [ ] **Set up SSL certificate** for secure connections
- [ ] **Configure backup system**
