# Storefront themes

A theme is a folder in `resources/views/themes/<slug>/`. The admin lists every folder under **Themes**, where you can preview, activate and customise each one. Only super admins can do this.

## 1. Create the folder

```
resources/views/themes/mytheme/
    theme.json              required
public/themes/mytheme/
    preview.png             optional screenshot for the admin gallery (.png, .jpg, .webp or .svg)
```

The slug may only contain letters, numbers, `-` and `_`.

## 2. theme.json

```json
{
    "name": "My Theme",
    "version": "1.0.0",
    "author": "You",
    "description": "One line shown in the admin gallery",
    "defaults": {
        "primary_color": "#0f766e",
        "font_family": "Poppins",
        "border_radius": "lg",
        "header_style": "centered"
    },
    "hide": ["footer_style"],
    "sections": []
}
```

- **defaults**: this theme's starting values for any customizer field (see the field list in `App\Services\ThemeService::baseSections()`). Fields you leave out fall back to the store-wide Appearance settings.
- **hide**: customizer fields that do nothing in your theme, for example `footer_style` when the theme ships its own footer.
- **sections**: extra customizer sections, in the same shape as `baseSections()`: `id`, `title`, and `fields` with `key`, `label`, `type` and `default`. Values are available to templates as `setting('theme.<key>')`.

Customizer values are saved per theme, so switching themes never loses them. When a theme is rendered, its values override the store-wide `theme.*` settings. Because of that, any template that reads `setting('theme.xyz')` or `$appSettings['theme'][...]` follows the active theme automatically.

## 3. Override templates

Any storefront view can be replaced by putting a file with the same path inside the theme folder. The theme's file wins and everything else falls back to the shared view. The admin area is never themed.

Most pages are built from small shared **blocks**. A theme usually rearranges the blocks in its own page layout and adds a `<style>` block, rather than copying the blocks. Copy a block only when its markup itself must change.

| Page | Theme page layout | Shared blocks you can include or replace |
|---|---|---|
| Header / footer | `partials/header.blade.php`, `partials/footer.blade.php` | `partials/header/base` (the full header); footer data from `App\View\StoreFooter::data($appSettings)` |
| Homepage | `home/partials/skin.blade.php` (styles) | `home/sections/*` (hero, features, categories, featured, flash_deals, top_rated, two_column_banners, promo, arrivals, blog, newsletter) |
| Product card | `components/product-card.blade.php` | |
| Shop | `shop/index.blade.php` | `shop/listing/*` (styles, hero, active-filters, mobile-filters, sidebar, sort-bar, products); wrap them in `<x-shop-state>` |
| Category | `shop/category.blade.php` | `shop/category-page/*` (breadcrumb, banner, products, other-categories) |
| Product | `shop/show.blade.php` | `shop/product/*` (breadcrumb, gallery, info, tabs, reviews, cross-sell, related, mobile-bar). Always include `mobile-bar`, because it carries the page scripts. |
| Cart | `cart/index.blade.php` | `cart/blocks/*` (items, actions, cross-sell, summary, empty) |
| Checkout | `checkout/index.blade.php`, `checkout/success.blade.php` | `checkout/blocks/*`, see the note below |
| Account | `layouts/account.blade.php`, `account/partials/skin.blade.php` | pages fill `account_heading` / `account_content` |
| Login / register | `layouts/auth.blade.php`, `auth/partials/skin.blade.php` | pages fill `auth_card` |
| Blog | `blog/index.blade.php`, `blog/show.blade.php` | `blog/blocks/*`, `blog/post/*` |
| About, contact, CMS pages | `pages/partials/skin.blade.php` (styles) | |

**Checkout must keep its working parts.** A checkout layout has to keep these, or checkout stops working:

- the root `x-data="checkoutForm()"`;
- one `<form action="{{ route('checkout.process') }}" method="POST" x-ref="checkoutForm">` with `@csrf`, wrapped around `shipping-info`, `delivery`, `payment`, `notes` and `summary`;
- `@include('checkout.blocks.scripts')` after the form.

Never edit field names in the blocks.

**Print stored HTML safely.** When a theme template prints HTML that admins, editors or vendors wrote (blog posts, CMS pages, product descriptions), always use `{!! safe_html($html) !!}`, never raw `{!! $html !!}`. `safe_html()` keeps formatting such as headings, lists, links, images, tables and the class attribute. It strips scripts, iframes, inline styles, event handlers and `javascript:` links. Everything else should use normal escaped output: `{{ $value }}`.

**Respect switched-off modules.** Wrap any markup that belongs to an optional module in `@module('<key>') … @endmodule`, for example `@module('wishlist')` around a wishlist button. That way it disappears when the module is switched off in **Admin › Modules**. The keys are listed in `App\Support\Modules::all()`: `reviews`, `wishlist`, `compare`, `newsletter`, `blog`, `digital_products`, `multi_vendor`, `driver_portal`, `ai_chat`, and the rest. The module's own pages and form endpoints already return 404 when it is off.

## 4. Style with the theme's colours

Templates can use the CSS variables set from the customizer: `--color-primary`, `--color-secondary`, `--color-accent`, `--font-family`, `--font-heading` and `--border-radius`. The Tailwind `primary-*` colours also follow the theme's primary colour.

## 5. Check it

1. Open **Admin › Themes**, then choose **Preview** on your theme. Only you see it, and a bar at the bottom lets you exit.
2. Go through: home, shop, a category, a product with and without variations, the cart, checkout, the account pages, login, the blog, and a CMS page.
3. Use **Customize** to try the settings, then **Activate** when ready.
