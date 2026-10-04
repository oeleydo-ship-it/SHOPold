# Laravel Shop - E-Commerce Application

A comprehensive e-commerce application built with Laravel 12, featuring a beautiful Tailwind CSS frontend and a full-featured admin dashboard.

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

### Admin Dashboard
- 📊 Dashboard with sales analytics and charts
- 📦 Product management (CRUD)
- 📁 Category management
- 🛒 Order management with status updates
- 👥 User management
- 🎫 Coupon management
- ⭐ Review moderation

## Requirements

- PHP 8.2+
- MySQL 5.7+ or MariaDB 10.3+
- Composer
- Node.js & NPM (optional, for asset compilation)

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

8. **Start the development server**
   ```bash
   php artisan serve
   ```

9. **Access the application**
   - Frontend: http://localhost:8000
   - Admin: http://localhost:8000/admin

## Default Login Credentials

### Admin Account
- Email: `admin@shop.com`
- Password: `password`

### Customer Account
- Email: `customer@shop.com`
- Password: `password`

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
│       ├── admin/               # Admin views
│       ├── auth/                # Auth views
│       ├── layouts/             # Layout templates
│       └── ...                  # Frontend views
└── routes/
    └── web.php                  # Web routes
```

## Available Coupon Codes

- `WELCOME10` - 10% off orders over $50
- `SAVE20` - $20 off orders over $100

## Technologies Used

- **Backend**: Laravel 12
- **Frontend**: Tailwind CSS (via CDN)
- **Icons**: Font Awesome 6
- **JavaScript**: Alpine.js
- **Charts**: Chart.js (Admin dashboard)

## License

This project is open-sourced software licensed under the MIT license.
