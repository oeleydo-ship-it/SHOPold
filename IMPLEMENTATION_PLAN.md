# Laravel Shop - Advanced Features Implementation Plan

## Overview
This document outlines the implementation plan for adding a comprehensive Settings System, Theme Support, and Admin Settings UI to the Laravel Shop application.

---

## Phase 1: Core Settings System

### 1.1 Database Structure

#### Settings Table Migration
```
- id (bigint, primary key)
- group (string) - e.g., 'store', 'theme', 'payment', 'shipping', 'seo', 'features'
- key (string, unique)
- value (text, nullable)
- type (enum: string, boolean, integer, float, json, file)
- created_at, updated_at
```

### 1.2 Setting Model & Helper

#### Model Features
- Automatic type casting based on `type` column
- Static methods: `get()`, `set()`, `has()`, `forget()`
- Grouped settings retrieval
- Cache integration for performance

#### Global Helper Function
```php
setting('store.name', 'Default Value')
setting(['store.name' => 'New Value'])
```

### 1.3 Settings Groups

| Group | Keys | Description |
|-------|------|-------------|
| `store` | name, tagline, email, phone, address, logo, favicon, currency, timezone | Basic store information |
| `theme` | active, primary_color, secondary_color, font_family | Theme configuration |
| `payment` | cod_enabled, stripe_enabled, paypal_enabled, bank_enabled | Payment toggles |
| `shipping` | default_method, free_threshold, flat_rate | Shipping configuration |
| `seo` | meta_title, meta_description, google_analytics, facebook_pixel | SEO settings |
| `social` | facebook, twitter, instagram, youtube | Social links |
| `features` | reviews, wishlist, compare, multi_vendor, blog | Feature toggles |

---

## Phase 2: Theme System

### 2.1 Theme Directory Structure

```
resources/views/themes/
├── default/
│   ├── layouts/
│   │   └── app.blade.php
│   ├── components/
│   │   ├── product-card.blade.php
│   │   ├── header.blade.php
│   │   └── footer.blade.php
│   ├── home.blade.php
│   ├── shop/
│   ├── cart/
│   ├── checkout/
│   ├── account/
│   ├── auth/
│   └── pages/
│
├── modern/
│   └── (same structure, different design)
│
└── starter_files/
    └── theme.json (theme metadata template)
```

### 2.2 Theme Configuration

Each theme has a `theme.json`:
```json
{
  "name": "Default Theme",
  "version": "1.0.0",
  "author": "LaravelShop",
  "description": "Clean and modern default theme",
  "preview": "preview.png",
  "colors": {
    "primary": "#2563eb",
    "secondary": "#1e40af"
  }
}
```

### 2.3 Theme Service Provider

- Register theme view namespace
- Resolve active theme from settings
- Provide `Theme` facade for easy access
- Theme switching without code changes

### 2.4 Themes to Create

1. **Default** - Current design (clean, minimal)
2. **Modern** - Bold colors, card shadows, animations
3. **Starter** - Basic template for custom themes

---

## Phase 3: Admin Settings UI

### 3.1 Settings Controller

```
Admin\SettingController
├── index() - Show settings dashboard
├── general() - Store info settings
├── appearance() - Theme & colors
├── payment() - Payment gateway settings
├── shipping() - Shipping configuration
├── seo() - SEO settings
├── social() - Social media links
├── features() - Feature toggles
├── update() - Save settings (POST)
└── clearCache() - Clear settings cache
```

### 3.2 Admin Routes

```
GET  /admin/settings              → Settings dashboard
GET  /admin/settings/general      → General settings form
GET  /admin/settings/appearance   → Theme settings form
GET  /admin/settings/payment      → Payment settings form
GET  /admin/settings/shipping     → Shipping settings form
GET  /admin/settings/seo          → SEO settings form
GET  /admin/settings/social       → Social settings form
GET  /admin/settings/features     → Feature toggles form
POST /admin/settings              → Save all settings
POST /admin/settings/clear-cache  → Clear cache
```

### 3.3 Settings Views

```
views/admin/settings/
├── index.blade.php      → Settings dashboard with tabs
├── general.blade.php    → Store information
├── appearance.blade.php → Themes & customization
├── payment.blade.php    → Payment gateways
├── shipping.blade.php   → Shipping options
├── seo.blade.php        → SEO configuration
├── social.blade.php     → Social media links
└── features.blade.php   → Feature toggles
```

---

## Phase 4: Integration

### 4.1 Layout Updates

- Update `layouts/app.blade.php` to use theme settings
- Dynamic primary colors via CSS variables
- Logo/favicon from settings
- Store name from settings

### 4.2 Controller Updates

- Use theme-aware view resolution
- Read configurations from settings helper

### 4.3 Cache Strategy

- Cache all settings on first load
- Invalidate cache on settings update
- Use tagged cache for easy clearing

---

## Implementation Order

### Step 1: Database & Model
- [ ] Create settings migration
- [ ] Create Setting model with helpers
- [ ] Create global helper function
- [ ] Create SettingServiceProvider

### Step 2: Seeder
- [ ] Create SettingsSeeder with default values
- [ ] Update DatabaseSeeder to include settings

### Step 3: Theme System
- [ ] Move existing views to themes/default/
- [ ] Create Theme service class
- [ ] Create ThemeServiceProvider
- [ ] Update view resolution in controllers

### Step 4: Create Modern Theme
- [ ] Create themes/modern/ structure
- [ ] Design modern layout variations
- [ ] Add theme preview images

### Step 5: Admin Settings Controller
- [ ] Create SettingController
- [ ] Add admin routes for settings
- [ ] Create settings views (tabbed interface)

### Step 6: Integration & Testing
- [ ] Update frontend to use settings
- [ ] Test theme switching
- [ ] Test settings persistence
- [ ] Verify cache invalidation

---

## Files to Create/Modify

### New Files
1. `database/migrations/xxxx_create_settings_table.php`
2. `app/Models/Setting.php`
3. `app/Helpers/settings.php`
4. `app/Providers/SettingServiceProvider.php`
5. `app/Services/ThemeService.php`
6. `app/Providers/ThemeServiceProvider.php`
7. `app/Http/Controllers/Admin/SettingController.php`
8. `database/seeders/SettingsSeeder.php`
9. `resources/views/admin/settings/*.blade.php` (multiple)
10. `resources/views/themes/default/**` (reorganized)
11. `resources/views/themes/modern/**` (new theme)

### Modified Files
1. `routes/web.php` - Add settings routes
2. `config/app.php` - Register providers
3. `composer.json` - Add helpers autoload
4. `database/seeders/DatabaseSeeder.php` - Include SettingsSeeder
5. `resources/views/layouts/admin.blade.php` - Add settings nav

---

## Settings Default Values

```php
// Store
'store.name' => 'Laravel Shop',
'store.tagline' => 'Your one-stop shop',
'store.email' => 'info@laravelshop.com',
'store.phone' => '+1 234 567 890',
'store.address' => '123 Commerce St, City, Country',
'store.currency' => 'USD',
'store.currency_symbol' => '$',
'store.timezone' => 'UTC',

// Theme
'theme.active' => 'default',
'theme.primary_color' => '#2563eb',
'theme.secondary_color' => '#1e40af',
'theme.font_family' => 'Inter',

// Payment
'payment.cod_enabled' => true,
'payment.stripe_enabled' => false,
'payment.paypal_enabled' => false,
'payment.bank_enabled' => false,

// Shipping
'shipping.free_threshold' => 100,
'shipping.flat_rate' => 10,
'shipping.default_country' => 'US',

// SEO
'seo.meta_title' => 'Laravel Shop - Best Online Store',
'seo.meta_description' => 'Shop the best products online',
'seo.google_analytics' => '',
'seo.facebook_pixel' => '',

// Social
'social.facebook' => '',
'social.twitter' => '',
'social.instagram' => '',
'social.youtube' => '',

// Features
'features.reviews_enabled' => true,
'features.wishlist_enabled' => true,
'features.compare_enabled' => false,
'features.guest_checkout' => true,
'features.multi_vendor' => false,
'features.blog_enabled' => false,
```

---

## Timeline Estimate

| Phase | Estimated Time |
|-------|---------------|
| Phase 1: Core Settings | ~30 mins |
| Phase 2: Theme System | ~45 mins |
| Phase 3: Admin UI | ~60 mins |
| Phase 4: Integration | ~30 mins |
| **Total** | **~2.5-3 hours** |

---

## Next Steps After Completion

Once this foundation is complete, we can add:

1. **Enhanced Storefront** - Product variants, compare, Q&A
2. **Payment Gateways** - Stripe, PayPal integration
3. **Advanced Shipping** - Zones, weight-based, API integrations
4. **SEO Tools** - Sitemap, meta editor, schema markup
5. **Notifications** - Email templates, SMS, push
6. **Reports & Analytics** - Sales charts, customer stats
7. **Multi-Vendor** - Vendor dashboard, commissions
8. **Premium Features** - AI, loyalty, gift cards

---

*Document created: November 26, 2025*
*Status: Ready for Implementation*
