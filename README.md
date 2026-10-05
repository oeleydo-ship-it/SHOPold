# Laravel Shop - E-Commerce Application

A comprehensive e-commerce application built with Laravel 12 and React (Inertia): a themeable, server-rendered storefront and apps for the admin, vendor and driver portals.

## Features

### Frontend
- 🏠 Beautiful responsive homepage with featured products
- 🛍️ Product catalog with category filtering and search
- 🛒 Shopping cart with quantity management
- 💳 Checkout with order processing
- 👤 User account dashboard
- ❤️ Wishlist functionality
- ⭐ Product reviews and ratings
- 🎫 Coupon/discount code support

- 🎨 Multiple storefront themes (default, modern, starter) with a live customizer; see `resources/views/themes/README.md`
- 🔎 Server-side rendering, `/sitemap.xml`, `/robots.txt` and resized WebP product images (`/img/{width}/{path}`)
- 🏪 Vendor store pages and a customer helpdesk, both following the active theme
- ✉️ Branded emails (store logo, name and the active theme's colour)

### Admin Dashboard (React)
- 📊 Dashboard with sales analytics and charts
- 📦 Products, variations, digital files, categories, brands, attributes, stock
- 🛒 Orders, invoices (screen, print and PDF), shipping labels
- 👥 Users with roles: super admin, manager, editor (enforced on every /admin route)
- 🧩 Modules (turn features such as blog, vendors, drivers, loyalty on or off) and themes
- 🎫 Coupons, reviews, blog, pages, media library, helpdesk, reports, settings

### Vendor Portal (React, `/vendor`)
- Registration and approval, dashboard, products and variations, orders and fulfilment, stock, payouts, store settings

### Driver Portal (React, mobile-first, `/driver`)
- Deliveries with navigation/call buttons, pick-up and proof of delivery (photo + signature), earnings, notifications, schedule

## Requirements

- PHP 8.2+
- MySQL 5.7+ or MariaDB 10.3+
- Composer
- Node.js 20+ & NPM (builds the admin, vendor and driver apps)

## Installation

1. **Clone or navigate to the project directory**
   ```bash
   cd "c:\Program Files\Ampps\www\shop"
   ```

2. **Copy environment file**
   ```bash
   copy .env.example .env
   ```

3. **Install PHP dependencies**
   ```bash
   composer install
   ```

4. **Generate application key**
   ```bash
   php artisan key:generate
   ```

5. **Create database**
   - Create a MySQL database named `shop`
   - Update `.env` file with your database credentials:
     ```
     DB_DATABASE=shop
     DB_USERNAME=root
     DB_PASSWORD=mysql
     ```

6. **Run migrations and seeders**
   ```bash
   php artisan migrate --seed
   ```

7. **Create storage link**
   ```bash
   php artisan storage:link
   ```

8. **Build the front-end apps** (or run `npm run dev` while developing)
   ```bash
   npm install
   npm run build
   ```

9. **Start the development server**
   ```bash
   php artisan serve
   ```

10. **Access the application**
   - Frontend: http://localhost:8000
   - Admin: http://localhost:8000/admin

## Default Login Credentials

### Admin Account
- Email: `admin@shop.com`
- Password: `password`

### Customer Account
- Email: `customer@shop.com`
- Password: `password`

## Background email

Emails are sent while the customer waits unless **Admin > Settings > Email > Send emails in the background** is on. Then they go to a database queue, are retried 3 times (after 1 and 5 minutes), and anything that still fails appears under **Settings > Email > Failed emails** with Retry / Remove.

The queue needs the scheduler running every minute:

- **Windows:** schedule `scripts\run-scheduler.bat` (one-off, in a Command Prompt):
  ```
  schtasks /Create /SC MINUTE /MO 1 /TN "Shop scheduler" /TR "\"C:\Program Files\Ampps\www\shop\scripts\run-scheduler.bat\""
  ```
- **Linux:** cron `* * * * * cd /path/to/shop && php artisan schedule:run >> /dev/null 2>&1`

The Email settings page shows when the worker last ran; switch background sending on only after it shows a recent run.

## Storefront server-side rendering

Storefront pages are rendered on the server for search engines and a fast first paint. `npm run build` builds both the browser and the server bundle (`bootstrap/ssr`). In production keep the render server running (for example with a process manager):

```bash
php artisan inertia:start-ssr
```

`php artisan inertia:check-ssr` reports whether it is up. Without it, pages still work: they render in the browser, and the HTML shell still prints the title, description and canonical tags.

## Tests

Tests use Pest and run against a separate MySQL database, `shoping_test` (set in `phpunit.xml`; never point it at the live database). Create it once, then:

```bash
php vendor/bin/pest
```

## Project Structure

```
shop/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/          # Admin controllers
│   │   │   ├── Auth/           # Authentication
│   │   │   └── ...             # Frontend controllers
│   │   └── Middleware/
│   └── Models/                  # Eloquent models
├── database/
│   ├── migrations/              # Database migrations
│   └── seeders/                 # Database seeders
├── resources/
│   └── views/
│       ├── admin/               # Print/PDF documents (invoice, shipping label)
│       ├── emails/              # Branded emails (layouts/branded + partials)
│       ├── themes/<slug>/       # Storefront theme manifests (theme.json) + theme guide
│       ├── storefront.blade.php # HTML shell of the storefront (SEO tags, theme CSS, analytics)
│       └── app.blade.php        # HTML shell of the admin / vendor / driver apps
│   ├── css/storefront*.css      # Storefront Tailwind build + theme skins
│   └── js/
│       ├── Pages/Storefront/    # Storefront pages (Inertia, server-rendered)
│       ├── Storefront/          # Storefront layout, header, footer, cards, theme registry
│       ├── Pages/Admin|Vendor|Driver/   # React pages (Inertia)
│       ├── Layouts/             # AdminLayout, PortalLayout (vendor/driver)
│       └── Components/          # Shared UI
└── routes/
    └── web.php                  # Web routes
```

## Available Coupon Codes

- `WELCOME10` - 10% off orders over $50
- `SAVE20` - $20 off orders over $100

## Technologies Used

- **Backend**: Laravel 12
- **Storefront**: Inertia + React 19 with server-side rendering, Vite, Tailwind CSS 4, lucide icons
- **Admin / vendor / driver**: Inertia + React 19, Vite, Tailwind CSS 4, lucide icons
- **PDF**: barryvdh/laravel-dompdf
- **Tests**: Pest

## License

This project is open-sourced software licensed under the MIT license.
