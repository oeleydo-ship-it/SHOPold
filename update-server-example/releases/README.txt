Update Server Releases Directory

This directory should contain the actual update zip files:
- laravel-shop-1.3.5.zip
- laravel-shop-1.3.4.zip
- etc.

For testing purposes, you can create dummy files or disable updates by removing the files.

To create a proper update package:
1. Zip your Laravel application files (excluding vendor, node_modules, storage, .env)
2. Name it laravel-shop-VERSION.zip
3. Place it in this directory
4. Update the version in index.php configuration