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

Customizer values are saved per theme, so switching themes never loses them. When a theme is rendered, its values override the store-wide `theme.*` settings, and the storefront receives them in the shared `sf.theme.options` page prop.

## 3. Style the theme

The storefront is a React (Inertia) app: pages in `resources/js/Pages/Storefront`, shared parts in `resources/js/Storefront`. Every theme renders the same pages. A theme changes their look in two ways.

**CSS skin (most themes need only this).** The page wrapper carries the class `sf-theme-<slug>`. Add your rules to `resources/css/storefront-themes.css`, scoped to that class, like the bundled `modern` and `starter` skins:

```css
.sf-theme-mytheme .sf-card { border-radius: 0; }
.sf-theme-mytheme main .shadow-sm { box-shadow: none; }
```

Useful hooks: `sf-header`, `sf-nav-link` / `sf-nav-active`, `sf-footer`, `sf-card` (with `sf-card-media`, `sf-card-badge`, `sf-card-sale`, `sf-card-actions`, `sf-card-actions-row`, `sf-card-price`, `sf-card-cta`), `sf-features`, `sf-shop-hero`, and the page wrappers `product-page`, `account-page`, `auth-page`, `content-page`. Run `npm run build` afterwards.

**Component overrides (when the markup must change).** Register a replacement in `resources/js/Storefront/theme/registry.js`:

```js
import MyCard from './mytheme/ProductCard';
export const overrides = { mytheme: { ProductCard: MyCard } };
```

Components look up their themed version by name with `useThemed(name, Base)`. Overridable names include `Header`, `Footer`, `ProductCard`, `Product.Gallery` / `Product.Info` / `Product.Tabs` / `Product.Reviews`, `Cart.Items` / `Cart.Summary`, `Checkout.ShippingInfo` / `Checkout.Payment` / `Checkout.Summary`, `Account.Nav`, `Auth.Card`, `Blog.PostCard`, `Vendor.Banner`. A replacement receives the same props as the component it replaces.

**Print stored HTML safely.** HTML written by admins, editors or vendors (blog posts, CMS pages, product descriptions) must be sanitised on the server with `safe_html()` (or `safe_page_html()` for CMS pages) before it is sent as a prop; only then may a component render it with `dangerouslySetInnerHTML`. Everything else is plain text, which React escapes.

**Respect switched-off modules.** Optional features come with flags in the shared `sf.features` prop (`wishlist`, `newsletter`, `reviews`, `blog`, `multiVendor`, ...). Hide the related markup when a flag is off. The module's own pages and endpoints already return 404 when it is off.

## 4. Theme colours

`App\View\StoreTheme` writes the customizer values as CSS variables: `--sf-primary-50` ... `--sf-primary-900`, `--sf-accent-*`, `--sf-font`, `--sf-heading-font`, `--sf-radius`, `--sf-button-radius`. The Tailwind `primary-*` and `accent-*` colours and the `sf-btn` class follow them.

## 5. Check it

1. Open **Admin › Themes**, then choose **Preview** on your theme. Only you see it, and a bar at the bottom lets you exit.
2. Go through: home, shop, a category, a product with and without variations, the cart, checkout, the account pages, login, the blog, a CMS page and a vendor store page.
3. Use **Customize** to try the settings, then **Activate** when ready.
