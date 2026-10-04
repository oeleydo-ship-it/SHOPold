# Shoppers Shop Installation Guide

## Requirements

- PHP 8.2 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Composer (for local development)
- Required PHP Extensions:
  - PDO, PDO_MySQL
  - OpenSSL
  - Mbstring
  - Tokenizer
  - JSON
  - cURL
  - Fileinfo
  - GD

## Installation Methods

### Method 1: Auto-Installer (Recommended for Control Panels)

The enhanced auto-installer provides one-click installation for hosting control panels and webservers.

#### Features:
- Comprehensive requirements checking
- Automatic database setup and injection
- Environment configuration
- Admin user creation
- Progress tracking with detailed logging
- Error handling and rollback capabilities

#### Usage:

1. **Direct Web Interface**
   - Upload `install.php` to your web root
   - Visit `https://yourdomain.com/install.php`
   - Follow the guided installation process

2. **API Integration (for Control Panels)**
   ```bash
   # Check installation status
   curl https://yourdomain.com/install/status
   
   # Run auto-installation
   curl -X POST https://yourdomain.com/install/auto \
     -H "Content-Type: application/json" \
     -d '{
       "db_host": "localhost",
       "db_port": 3306,
       "db_database": "shop_db",
       "db_username": "shop_user",
       "db_password": "secure_password",
       "admin_name": "Admin User",
       "admin_email": "admin@example.com",
       "admin_password": "admin_password",
       "store_name": "My Online Store",
       "store_currency": "USD",
       "app_url": "https://yourdomain.com"
     }'
   ```

3. **cPanel Integration**
   - Upload `cpanel-installer.php` for cPanel-specific features
   - Supports automatic database and user creation via cPanel API
   - Integrated with cPanel's MySQL management

### Method 2: Web Installer (Standard)

1. **Upload Files**
   - Upload all files to your web hosting (e.g., `public_html` folder)
   - Make sure `.htaccess` file is uploaded (it may be hidden)

2. **Set Permissions**
   ```
   chmod -R 755 storage
   chmod -R 755 bootstrap/cache
   chmod 644 .env
   ```

3. **Create Database**
   - In cPanel, go to "MySQL Databases"
   - Create a new database (e.g., `username_shop`)
   - Create a new user with a strong password
   - Add the user to the database with ALL PRIVILEGES

4. **Run Web Installer**
   - Visit `https://yourdomain.com/install`
   - Follow the installation wizard
   - Enter your database credentials
   - Create your admin account
   - Configure store settings

5. **Post-Installation Security**
   - Delete the `resources/views/install` folder
   - Remove install routes from `routes/web.php` (lines with `/install`)

### Method 3: Command Line (For VPS/Dedicated Servers)

1. **Clone or Upload Files**
   ```bash
   cd /var/www/html
   # Upload your files here
   ```

2. **Install Dependencies**
   ```bash
   composer install --optimize-autoloader --no-dev
   ```

3. **Configure Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Edit .env File**
   ```
   APP_NAME="Your Store Name"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://yourdomain.com

   DB_HOST=localhost
   DB_DATABASE=your_database
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Run Migrations & Seeders**
   ```bash
   php artisan migrate --force
   php artisan db:seed --force
   ```

6. **Create Admin User**
   ```bash
   php artisan tinker
   >>> \App\Models\User::create(['name'=>'Admin', 'email'=>'admin@example.com', 'password'=>bcrypt('password'), 'is_admin'=>true]);
   ```

7. **Set Permissions & Storage Link**
   ```bash
   chmod -R 755 storage bootstrap/cache
   php artisan storage:link
   ```

8. **Clear Caches**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

## cPanel Specific Instructions

### Setting Document Root
If your Laravel app is in a subfolder, you need to point your domain to the `public` folder:
1. Go to cPanel > Domains
2. Edit your domain
3. Set Document Root to `/public_html/public` (or wherever your public folder is)

### Alternative: Using .htaccess
If you can't change document root, add this to your root `.htaccess`:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

### PHP Version
Make sure PHP 8.2+ is selected:
1. Go to cPanel > Select PHP Version
2. Choose PHP 8.2 or higher
3. Enable required extensions

## Enhanced Auto-Install Features

### Comprehensive Requirements Checking

The auto-installer performs extensive system checks:

- **PHP Version & Configuration**: Checks PHP 8.2+, memory limits, execution time
- **Required Extensions**: Validates all necessary PHP extensions
- **File Permissions**: Ensures proper write permissions for storage and cache
- **Database Support**: Tests database connectivity and operations
- **Server Configuration**: Checks for HTTPS, mod_rewrite, and other server features

### Database Injection & Setup

- **Automatic Database Creation**: Creates database and user accounts (cPanel integration)
- **Connection Testing**: Validates database credentials before installation
- **Table Creation**: Sets up all required database tables and indexes
- **Data Seeding**: Populates initial data including settings, pages, and sample content
- **Migration Handling**: Runs all database migrations safely

### Environment Configuration

- **Automatic .env Setup**: Configures all environment variables
- **Security Keys**: Generates secure application keys
- **URL Configuration**: Sets up proper URLs and paths
- **Cache Configuration**: Optimizes caching settings
- **Mail Configuration**: Sets up email settings

### Progress Tracking & Logging

- **Real-time Progress**: Shows installation progress with detailed steps
- **Comprehensive Logging**: Logs all operations for debugging
- **Error Handling**: Graceful error handling with rollback capabilities
- **Status Reporting**: Provides detailed status information

### Post-Installation Optimization

- **Cache Optimization**: Pre-caches configurations, routes, and views
- **Storage Links**: Creates necessary symbolic links
- **Directory Creation**: Sets up all required directories
- **Permission Setting**: Applies proper file and directory permissions
- **Security Hardening**: Implements basic security measures

## Control Panel Integration

### cPanel Integration

The `cpanel-installer.php` script provides deep cPanel integration:

```php
// Example cPanel integration
$installer = new LaravelShopInstaller([
    'domain' => 'yourstore.com',
    'cpanel_api' => $cpanelApi,
    'db_prefix' => 'cpuser_',
    'admin_email' => 'admin@yourstore.com',
    'store_name' => 'Your Store Name',
]);

$result = $installer->install();
```

### Plesk Integration

For Plesk hosting, use the standard auto-installer with database credentials:

```bash
curl -X POST https://yourdomain.com/install/auto \
  -H "Content-Type: application/json" \
  -d @installation-config.json
```

### DirectAdmin Integration

Similar to Plesk, use the API endpoint with proper database configuration.

## API Endpoints

The enhanced installer provides several API endpoints:

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/install/status` | GET | Check installation status |
| `/install/auto` | POST | Run automatic installation |
| `/install?action=check` | GET | Check system requirements |
| `/install?action=test-db` | POST | Test database connection |

## Troubleshooting

### 500 Internal Server Error
- Check file permissions (755 for folders, 644 for files)
- Check PHP version (must be 8.2+)
- Check `.htaccess` file exists
- Check `storage/logs/laravel.log` for errors

### Database Connection Error
- Verify database credentials in `.env`
- For cPanel, database name is usually `cpanelusername_dbname`
- Make sure user has privileges on the database

### Blank Page
- Enable debug mode temporarily: `APP_DEBUG=true` in `.env`
- Check PHP error logs
- Run `php artisan config:clear`

### Storage/Upload Issues
- Run `php artisan storage:link`
- Check `storage` folder permissions
- Ensure `public/storage` symlink exists

## Default Credentials

After installation with seeders:
- **Admin**: admin@shop.com / password
- **Customer**: customer@shop.com / password

**Important**: Change these passwords immediately after installation!

---

## Automatic Updates

Laravel Shop includes a built-in update system that allows you to update your installation directly from the admin panel.

### How Updates Work

1. **Check for Updates**: Go to Admin Panel → Settings → Updates
2. **Review Changes**: See changelog and requirements for the new version
3. **Backup**: System automatically creates a backup before updating
4. **Install**: Click "Install Update" to download and apply the update
5. **Verify**: Check that everything works correctly after the update

### Update Requirements

- PHP version must meet the minimum requirement for the new version
- Sufficient disk space for backup and update files
- Write permissions on application directories

### Manual Update (Alternative)

If automatic updates don't work, you can update manually:

1. **Download Update Package**
   - Download the latest version zip file from your update server
   - Or from your purchase location

2. **Create Backup**
   ```bash
   # Backup your current installation
   cp -r /var/www/html/shop /var/www/html/shop-backup
   mysqldump -u user -p database > backup.sql
   ```

3. **Upload New Files**
   - Extract the update zip
   - Upload files, excluding:
     - `.env` (keep your existing one)
     - `storage/` folder contents
     - `vendor/` folder (will be regenerated)

4. **Update Dependencies**
   ```bash
   composer install --optimize-autoloader --no-dev
   ```

5. **Run Migrations**
   ```bash
   php artisan migrate --force
   ```

6. **Clear Caches**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   php artisan route:clear
   ```

### Rollback an Update

If something goes wrong after an update:

1. **From Admin Panel**: Go to Settings → Updates → Backups, select a backup and click "Restore"

2. **Manual Rollback**:
   ```bash
   # Restore files
   rm -rf /var/www/html/shop
   cp -r /var/www/html/shop-backup /var/www/html/shop
   
   # Restore database
   mysql -u user -p database < backup.sql
   
   # Clear caches
   php artisan config:clear
   php artisan cache:clear
   ```

---

## Setting Up Your Own Update Server

If you're distributing Laravel Shop to clients, you can set up your own update server.

### Quick Setup

1. **Upload Update Server Files**
   - Copy `update-server-example/` to your server (e.g., `updates.yoursite.com`)
   - Create directories: `releases/`, `logs/`, `cache/`

2. **Configure the Server**
   Edit `index.php` and update:
   ```php
   $config = [
       'latest_version' => '1.2.0',
       'download_base_url' => 'https://updates.yoursite.com/releases/',
       'require_license' => true,  // Enable for paid products
       // ... other settings
   ];
   ```

3. **Create Update Packages**
   ```bash
   # From your Laravel Shop directory
   zip -r laravel-shop-1.2.0.zip . \
       -x "vendor/*" \
       -x "node_modules/*" \
       -x "storage/app/*" \
       -x "storage/framework/*" \
       -x "storage/logs/*" \
       -x ".env" \
       -x ".git/*"
   ```

4. **Upload to Releases**
   - Upload `laravel-shop-1.2.0.zip` to `releases/` folder

5. **Update Client Installations**
   In each client's `app/Services/UpdateService.php`:
   ```php
   const UPDATE_SERVER = 'https://updates.yoursite.com';
   ```

### Update Server API Endpoints

| Endpoint | Description |
|----------|-------------|
| `?action=check&version=1.0.0` | Check for available updates |
| `?action=versions` | Get all available versions |
| `?action=download&v=1.2.0` | Download specific version |
| `?action=changelog&v=1.2.0` | Get changelog for version |
| `?action=health` | Server health check |

### Security Recommendations

1. **Always use HTTPS** for your update server
2. **Enable license verification** for paid products
3. **Implement rate limiting** (already included)
4. **Sign your update packages** for integrity verification
5. **Keep logs** for troubleshooting and analytics

For detailed update server documentation, see `update-server-example/README.md`.

---

## Environment Variables Reference

Key `.env` settings for updates:

```env
# Application
APP_VERSION=1.0.0

# Update Server (optional - for custom update servers)
UPDATE_SERVER_URL=https://updates.yoursite.com
UPDATE_LICENSE_KEY=your-license-key
```

---

## Support

For issues and questions:
- Check the documentation in this file
- Review `update-server-example/README.md` for update server setup
- Check `storage/logs/laravel.log` for error details
- Create an issue on GitHub for bugs
