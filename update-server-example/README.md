# Laravel Shop Update Server

A production-ready update server for Laravel Shop with license verification, version management, rate limiting, and secure download handling.

## Table of Contents

- [Quick Start](#quick-start)
- [Server Requirements](#server-requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [API Endpoints](#api-endpoints)
- [Creating Update Packages](#creating-update-packages)
- [License Verification](#license-verification)
- [Security Best Practices](#security-best-practices)
- [Troubleshooting](#troubleshooting)

---

## Quick Start

1. Upload files to your server (e.g., `updates.yoursite.com`)
2. Create required directories: `releases/`, `logs/`, `cache/`
3. Configure `index.php` with your version info
4. Upload your update zip files to `releases/`
5. Update `UPDATE_SERVER` in your Laravel Shop installation

---

## Server Requirements

- PHP 8.2 or higher
- Apache/Nginx web server
- SSL certificate (HTTPS required for production)
- Minimum 100MB disk space for update files
- Write permissions for `logs/` and `cache/` directories

---

## Installation

### Step 1: Upload Files

Upload the following to your update server:

```
update-server/
├── index.php           # Main API handler
├── releases/           # Update zip files (create this)
├── logs/               # Request logs (create this)
├── cache/              # Rate limit cache (create this)
├── .htaccess           # Apache config (optional)
└── README.md
```

### Step 2: Create Directories

```bash
mkdir -p releases logs cache
chmod 755 releases logs cache
```

### Step 3: Configure Apache (Optional)

Create `.htaccess` for clean URLs:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ index.php?action=$1 [QSA,L]

# Protect sensitive directories
<FilesMatch "\.(log|json)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>
```

### Step 4: Configure Nginx (Alternative)

```nginx
location /api {
    try_files $uri $uri/ /index.php?action=$1&$query_string;
}

location ~ /\.(log|json)$ {
    deny all;
}
```

### Step 5: Update Laravel Shop

In your Laravel Shop installation, update `app/Services/UpdateService.php`:

```php
const UPDATE_SERVER = 'https://updates.yoursite.com';
```

---

## Configuration

Edit the `$config` array in `index.php`:

```php
$config = [
    // Current latest version
    'latest_version' => '1.2.0',
    
    // Minimum PHP version required
    'min_php_version' => '8.2',
    
    // Release date
    'release_date' => '2025-12-15',
    
    // Download base URL
    'download_base_url' => 'https://updates.yoursite.com/releases/',
    
    // Enable license verification
    'require_license' => true,
    
    // Rate limiting (requests per minute)
    'rate_limit' => 30,
    
    // Changelog entries
    'changelog' => [
        '1.2.0' => [
            'release_date' => '2025-12-15',
            'changes' => [
                'New feature 1',
                'Bug fix 1',
            ],
            'breaking_changes' => [],
            'upgrade_notes' => 'Run migrations after update',
        ],
    ],
];
```

---

## API Endpoints

### Check for Updates

```http
GET /?action=check&version=1.0.0
```

**Response:**
```json
{
    "success": true,
    "update_available": true,
    "latest_version": "1.2.0",
    "current_version": "1.0.0",
    "min_php_version": "8.2",
    "release_date": "2025-12-15",
    "changelog": ["New feature", "Bug fix"],
    "download_url": "https://updates.yoursite.com/releases/laravel-shop-1.2.0.zip",
    "file_hash": "sha256...",
    "file_size": 15728640
}
```

### Get Version History

```http
GET /?action=versions
```

**Response:**
```json
{
    "success": true,
    "versions": [
        {
            "version": "1.2.0",
            "release_date": "2025-12-15",
            "changes": ["..."],
            "is_latest": true
        }
    ]
}
```

### Download Update

```http
GET /?action=download&v=1.2.0&license=YOUR-LICENSE-KEY
```

Or with header:
```http
GET /?action=download&v=1.2.0
X-License-Key: YOUR-LICENSE-KEY
```

### Verify License

```http
GET /?action=verify-license&license=YOUR-LICENSE-KEY
```

### Get Changelog

```http
GET /?action=changelog&v=1.2.0
```

### Health Check

```http
GET /?action=health
```

---

## Creating Update Packages

### Step 1: Prepare Your Code

1. Update version in `app/Services/UpdateService.php`:
   ```php
   const VERSION = '1.2.0';
   ```

2. Run tests and ensure everything works

3. Update changelog in the update server config

### Step 2: Create the Zip File

**Important:** Exclude these directories/files:
- `vendor/`
- `node_modules/`
- `storage/` (except `storage/app/.gitignore`)
- `.env`
- `.git/`
- `tests/`

**Using Command Line:**

```bash
# From your Laravel Shop root directory
zip -r laravel-shop-1.2.0.zip . \
    -x "vendor/*" \
    -x "node_modules/*" \
    -x "storage/app/*" \
    -x "storage/framework/*" \
    -x "storage/logs/*" \
    -x ".env" \
    -x ".git/*" \
    -x "tests/*" \
    -x "*.log"
```

**Using PHP Script:**

```php
<?php
// create-release.php
$version = '1.2.0';
$excludes = ['vendor', 'node_modules', 'storage/app', 'storage/framework', 'storage/logs', '.env', '.git', 'tests'];

$zip = new ZipArchive();
$zip->open("laravel-shop-{$version}.zip", ZipArchive::CREATE | ZipArchive::OVERWRITE);

$rootPath = realpath('.');
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($rootPath),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $file) {
    if ($file->isDir()) continue;
    
    $filePath = $file->getRealPath();
    $relativePath = substr($filePath, strlen($rootPath) + 1);
    
    // Check exclusions
    $exclude = false;
    foreach ($excludes as $ex) {
        if (str_starts_with($relativePath, $ex)) {
            $exclude = true;
            break;
        }
    }
    
    if (!$exclude) {
        $zip->addFile($filePath, $relativePath);
    }
}

$zip->close();
echo "Created laravel-shop-{$version}.zip\n";
```

### Step 3: Upload and Configure

1. Upload zip to `releases/` directory
2. Update `$config['latest_version']` in `index.php`
3. Add changelog entry
4. Test the update process

---

## License Verification

### Enabling License Verification

Set in config:
```php
'require_license' => true,
```

### Adding License Keys

For simple setups, add to the config array:
```php
'valid_licenses' => [
    'LICENSE-KEY-1',
    'LICENSE-KEY-2',
],
```

### Database Integration (Recommended)

For production, replace the `verifyLicense` function:

```php
function verifyLicense($licenseKey, $config) {
    if (!$config['require_license']) {
        return true;
    }
    
    // Connect to your database
    $pdo = new PDO('mysql:host=localhost;dbname=licenses', 'user', 'pass');
    
    $stmt = $pdo->prepare('
        SELECT * FROM licenses 
        WHERE license_key = ? 
        AND status = "active" 
        AND (expires_at IS NULL OR expires_at > NOW())
    ');
    $stmt->execute([$licenseKey]);
    
    return $stmt->fetch() !== false;
}
```

---

## Security Best Practices

### 1. Always Use HTTPS

```apache
# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 2. Protect Sensitive Files

```apache
# Deny access to logs and cache
<FilesMatch "\.(log|json)$">
    Require all denied
</FilesMatch>

# Protect releases directory listing
Options -Indexes
```

### 3. Implement Signed Updates

Add signature verification:

```php
// When creating release
$signature = hash_hmac('sha256', file_get_contents($zipFile), $secretKey);

// When downloading
header('X-Signature: ' . $signature);

// In Laravel Shop, verify before applying
$expectedSignature = hash_hmac('sha256', $downloadedContent, $secretKey);
if (!hash_equals($expectedSignature, $receivedSignature)) {
    throw new Exception('Invalid update signature');
}
```

### 4. Rate Limiting

Already implemented - adjust `rate_limit` in config as needed.

### 5. IP Whitelisting (Optional)

```php
$allowedIPs = ['1.2.3.4', '5.6.7.8'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowedIPs)) {
    jsonResponse(['error' => 'Access denied'], 403);
}
```

---

## Troubleshooting

### Update Check Fails

1. Verify server is accessible: `curl https://updates.yoursite.com/?action=health`
2. Check PHP error logs
3. Verify SSL certificate is valid
4. Check firewall rules

### Download Fails

1. Verify zip file exists in `releases/`
2. Check file permissions (should be readable)
3. Verify `download_base_url` is correct
4. Check available disk space

### Rate Limit Issues

1. Clear cache: `rm -rf cache/*`
2. Increase `rate_limit` in config
3. Check for bot traffic

### License Verification Fails

1. Verify license key format
2. Check database connection (if using DB)
3. Verify license hasn't expired

---

## Directory Structure

```
update-server/
├── index.php              # Main API handler
├── .htaccess              # Apache configuration
├── README.md              # This file
├── releases/              # Update zip files
│   ├── laravel-shop-1.0.0.zip
│   ├── laravel-shop-1.1.0.zip
│   └── laravel-shop-1.2.0.zip
├── logs/                  # Request logs (auto-created)
│   └── 2025-12-15.log
└── cache/                 # Rate limit cache (auto-created)
    └── rate_*.json
```

---

## Support

For issues or questions:
- Check the troubleshooting section above
- Review server error logs
- Contact support at support@yoursite.com
