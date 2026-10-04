<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\File;

/**
 * Storefront themes.
 *
 * A theme is a folder in resources/views/themes/<slug> with a theme.json manifest. It can
 *  - override any storefront Blade view (a file at themes/<slug>/home.blade.php wins over views/home.blade.php),
 *  - ship its own customizer defaults ("defaults" in theme.json), and
 *  - add customizer sections ("sections" in theme.json, same shape as SECTIONS below).
 *
 * Customizer values are saved per theme (setting "themes.options.<slug>") and, for the active theme,
 * overlaid on the global "theme.*" settings at runtime, so every storefront template that reads
 * setting('theme.xyz') automatically follows the active theme.
 */
class ThemeService
{
    public const FONTS = ['Inter', 'Roboto', 'Open Sans', 'Poppins', 'Nunito', 'Lato', 'Montserrat', 'Playfair Display', 'Source Sans Pro', 'Oswald', 'Raleway'];

    protected string $themesPath;

    public function __construct()
    {
        $this->themesPath = resource_path('views/themes');
    }

    /* ------------------------------------------------------------------ active theme */

    /** The theme being rendered: an admin's preview, otherwise the activated theme. */
    public function getActive(): string
    {
        $preview = $this->previewSlug();
        if ($preview) {
            return $preview;
        }

        return $this->getActivated();
    }

    /** The theme visitors see. */
    public function getActivated(): string
    {
        $slug = (string) Setting::get('theme.active', 'default');

        return $this->exists($slug) ? $slug : 'default';
    }

    public function setActive(string $theme): void
    {
        if ($this->exists($theme)) {
            Setting::set('theme.active', $theme, 'string', 'theme');
        }
    }

    public function exists(string $theme): bool
    {
        return (bool) preg_match('/^[a-z0-9_-]+$/i', $theme) && File::isDirectory($this->themesPath . '/' . $theme);
    }

    /* ------------------------------------------------------------------ preview (admins only) */

    public function previewSlug(): ?string
    {
        if (!app()->bound('request') || !request()->hasSession()) {
            return null;
        }
        $user = request()->user();
        $slug = request()->session()->get('theme_preview');

        return ($user && $user->is_admin && is_string($slug) && $this->exists($slug)) ? $slug : null;
    }

    public function isPreviewing(): bool
    {
        return $this->previewSlug() !== null;
    }

    /** Unsaved customizer values an admin is trying out in the customizer's live preview. */
    protected function draftOptions(string $slug): array
    {
        if (!$this->isPreviewing() || request()->session()->get('theme_preview') !== $slug) {
            return [];
        }

        return (array) request()->session()->get('theme_preview_options', []);
    }

    /* ------------------------------------------------------------------ manifests */

    /** @return array<string, array> slug => manifest */
    public function getAll(): array
    {
        $themes = [];
        if (!File::isDirectory($this->themesPath)) {
            return $themes;
        }

        foreach (File::directories($this->themesPath) as $directory) {
            $slug = basename($directory);
            $themes[$slug] = $this->manifest($slug);
        }
        ksort($themes);

        return $themes;
    }

    public function manifest(string $slug): array
    {
        $manifest = [
            'name' => ucfirst($slug), 'version' => '1.0.0', 'author' => 'Unknown', 'description' => '',
            'preview' => null, 'colors' => [], 'fonts' => [], 'defaults' => [], 'sections' => [],
        ];
        $file = $this->themesPath . '/' . $slug . '/theme.json';
        if (File::exists($file)) {
            $json = json_decode(File::get($file), true);
            if (is_array($json)) {
                $manifest = array_merge($manifest, $json);
            }
        }

        return $manifest;
    }

    /** Back-compat: the manifest of a theme (default: the active one). */
    public function getConfig(?string $theme = null): array
    {
        return $this->manifest($theme ?? $this->getActive());
    }

    /* ------------------------------------------------------------------ customizer schema */

    /**
     * Fields: key (stored as theme.<key>), label, type (color|select|toggle|text|textarea|number|image|code),
     * default (used when neither the theme nor the store settings define a value), options, min/max, hint.
     */
    public function schema(string $slug): array
    {
        // a theme can hide base fields it does not use (theme.json "hide": ["footer_style", ...])
        $hidden = (array) ($this->manifest($slug)['hide'] ?? []);
        $sections = [];
        foreach (static::baseSections() as $section) {
            $section['fields'] = array_values(array_filter($section['fields'], fn ($f) => !in_array($f['key'], $hidden, true)));
            if ($section['fields']) {
                $sections[] = $section;
            }
        }
        foreach ((array) ($this->manifest($slug)['sections'] ?? []) as $extra) {
            if (is_array($extra) && !empty($extra['id']) && !empty($extra['fields'])) {
                $sections[] = $extra;
            }
        }

        return $sections;
    }

    /** key => field, for validation. */
    public function fields(string $slug): array
    {
        $fields = [];
        foreach ($this->schema($slug) as $section) {
            foreach ($section['fields'] as $field) {
                $fields[$field['key']] = $field;
            }
        }

        return $fields;
    }

    protected static function opts(array $pairs): array
    {
        return array_map(fn ($k, $v) => [$k, $v], array_keys($pairs), $pairs);
    }

    public static function baseSections(): array
    {
        $fonts = array_map(fn ($f) => [$f, $f], static::FONTS);
        $c = fn ($key, $label, $default, $extra = []) => array_merge(['key' => $key, 'label' => $label, 'type' => 'color', 'default' => $default], $extra);
        $t = fn ($key, $label, $default, $extra = []) => array_merge(['key' => $key, 'label' => $label, 'type' => 'toggle', 'default' => $default], $extra);
        $s = fn ($key, $label, $default, $options, $extra = []) => array_merge(['key' => $key, 'label' => $label, 'type' => 'select', 'default' => $default, 'options' => $options], $extra);
        $x = fn ($key, $label, $default, $extra = []) => array_merge(['key' => $key, 'label' => $label, 'type' => 'text', 'default' => $default, 'max' => 255], $extra);
        $n = fn ($key, $label, $default, $min, $max, $extra = []) => array_merge(['key' => $key, 'label' => $label, 'type' => 'number', 'default' => $default, 'min' => $min, 'max' => $max], $extra);
        // show a field only when another field has one of the given values
        $when = fn ($key, ...$values) => ['show_if' => ['key' => $key, 'in' => $values]];

        $isMega = $when('header_style', 'mega');
        $heroText = $when('hero_style', 'gradient', 'image', 'video', 'product_showcase');

        return [
            ['id' => 'colors', 'title' => 'Colors', 'fields' => [
                $c('primary_color', 'Primary', '#2563eb'), $c('secondary_color', 'Secondary', '#1e40af'), $c('accent_color', 'Accent', '#f59e0b'),
                $c('success_color', 'Success', '#10b981'), $c('warning_color', 'Warning', '#f59e0b'), $c('error_color', 'Error', '#ef4444'), $c('info_color', 'Info', '#3b82f6'),
            ]],
            ['id' => 'typography', 'title' => 'Typography', 'fields' => [
                $s('font_family', 'Body font', 'Inter', $fonts),
                $s('heading_font', 'Heading font', '', array_merge([['', 'Same as body']], $fonts)),
                $n('font_size', 'Base font size (px)', 16, 12, 22),
            ]],
            ['id' => 'style', 'title' => 'Style & layout', 'fields' => [
                $s('layout_width', 'Layout width', 'default', [['default', 'Default (1280px)'], ['wide', 'Wide (1536px)'], ['full', 'Full width'], ['auto', 'Auto (responsive)']]),
                $s('border_radius', 'Corner radius', 'md', [['none', 'None'], ['sm', 'Small'], ['md', 'Medium'], ['lg', 'Large'], ['xl', 'Extra large']]),
                $s('button_style', 'Buttons', 'rounded', [['rounded', 'Rounded'], ['pill', 'Pill'], ['square', 'Square'], ['soft', 'Soft']]),
                $s('card_style', 'Cards', 'shadow', [['shadow', 'Shadow'], ['border', 'Border'], ['flat', 'Flat'], ['elevated', 'Elevated']]),
                $s('animation_speed', 'Animations', 'normal', [['none', 'None'], ['fast', 'Fast'], ['normal', 'Normal'], ['slow', 'Slow']]),
                $t('enable_dark_mode', 'Dark mode switcher', false), $t('enable_rtl', 'Right-to-left layout', false),
            ]],
            ['id' => 'header', 'title' => 'Header', 'fields' => [
                $s('header_style', 'Header style', 'default', [['default', 'Default'], ['minimal', 'Minimal'], ['centered', 'Centered'], ['mega', 'Mega store'], ['transparent', 'Transparent (over hero)'], ['dark', 'Dark']]),
                $s('header_height', 'Height', 'normal', [['compact', 'Compact'], ['normal', 'Normal'], ['large', 'Large']]),
                $s('header_spacing', 'Logo / menu spacing', 'normal', [['tight', 'Tight'], ['normal', 'Normal'], ['wide', 'Wide']]),
                $t('sticky_header', 'Sticky header', true),
                $t('show_topbar', 'Top bar', true),
                $x('header_promo_text', 'Top bar message', 'Get up to 50% off using *SAVE50* this coupon code', $when('show_topbar', true)),
                $s('header_search_style', 'Search', 'simple', [['simple', 'Simple'], ['category', 'With category dropdown']]),
                $t('header_show_wishlist', 'Wishlist icon', true), $t('header_show_compare', 'Compare icon', true),
                $t('header_show_account_text', 'Account label next to the icon', true),
                $x('header_categories_text', 'Categories button text', 'Browse All Categories', $isMega),
            ]],
            ['id' => 'header_colors', 'title' => 'Header colors', 'fields' => [
                $c('header_bg_color', 'Background', '#ffffff'), $c('header_text_color', 'Text', '#374151'), $c('header_hover_color', 'Link hover', '#2563eb'),
                $c('header_topbar_bg', 'Top bar background', '#1f2937'),
                $c('header_menu_bg', 'Menu bar background', '#ffffff', $isMega), $c('header_menu_text', 'Menu link', '#374151', $isMega),
                $c('header_menu_hover', 'Menu link hover', '#2563eb', $isMega), $c('header_menu_active', 'Active menu link', '#2563eb', $isMega),
                $c('header_categories_bg', 'Categories button', '#2563eb', $isMega),
            ]],
            ['id' => 'hero', 'title' => 'Homepage hero', 'fields' => [
                $s('hero_style', 'Hero style', 'gradient', [['gradient', 'Gradient'], ['image', 'Image'], ['slider', 'Slider'], ['product_showcase', 'Product showcase'], ['fullwidth', 'Fullwidth banner'], ['video', 'Video']],
                    ['hint' => 'Slides for the Slider and Fullwidth styles are managed in Settings › Appearance.']),
                $s('hero_height', 'Height', 'large', [['auto', 'Auto'], ['small', 'Small'], ['medium', 'Medium'], ['large', 'Large'], ['full', 'Full screen']]),
                $x('hero_title', 'Title', 'Discover Premium Products', $heroText), $x('hero_subtitle', 'Subtitle', 'Shop the latest trends', $heroText),
                $x('hero_button_text', 'Button text', 'Shop Now', ['max' => 60] + $heroText), $x('hero_button_link', 'Button link', '/shop', $heroText),
                $x('hero_secondary_text', 'Second button text', '', ['max' => 60] + $heroText), $x('hero_secondary_link', 'Second button link', '', $heroText),
                $s('hero_text_align', 'Text alignment', 'left', [['left', 'Left'], ['center', 'Center'], ['right', 'Right']], $heroText),
                $c('hero_text_color', 'Text color', '#ffffff', $heroText),
                $c('hero_gradient_start', 'Gradient start', '#3b82f6', $when('hero_style', 'gradient')),
                $c('hero_gradient_end', 'Gradient end', '#1d4ed8', $when('hero_style', 'gradient')),
                ['key' => 'hero_image', 'label' => 'Background image', 'type' => 'image', 'default' => ''] + $when('hero_style', 'image', 'gradient'),
                $c('hero_overlay_color', 'Overlay color', '#000000', $when('hero_style', 'image', 'video')),
                $n('hero_overlay_opacity', 'Overlay opacity (%)', 40, 0, 100, $when('hero_style', 'image', 'video')),
                // slider / fullwidth
                $s('slider_interval', 'Auto-play interval', '5000', [['3000', '3 seconds'], ['5000', '5 seconds'], ['7000', '7 seconds'], ['10000', '10 seconds']], $when('hero_style', 'slider', 'fullwidth')),
                $s('slider_transition', 'Transition', 'fade', [['fade', 'Fade'], ['slide', 'Slide'], ['zoom', 'Zoom']], $when('hero_style', 'slider', 'fullwidth')),
                $s('slider_navigation', 'Navigation', 'both', [['both', 'Arrows & dots'], ['arrows', 'Arrows only'], ['dots', 'Dots only'], ['none', 'None']], $when('hero_style', 'slider', 'fullwidth')),
                $s('fullwidth_content_position', 'Content position', 'left', [['left', 'Left'], ['center', 'Center'], ['right', 'Right'], ['bottom-left', 'Bottom left'], ['bottom-center', 'Bottom center']], $when('hero_style', 'fullwidth')),
                $s('fullwidth_content_width', 'Content width', 'medium', [['narrow', 'Narrow'], ['medium', 'Medium'], ['wide', 'Wide'], ['full', 'Full']], $when('hero_style', 'fullwidth')),
                $t('fullwidth_show_overlay', 'Gradient overlay', false, $when('hero_style', 'fullwidth')),
                $t('fullwidth_show_badge', 'Badge above the title', true, $when('hero_style', 'fullwidth')),
                $t('fullwidth_parallax', 'Parallax effect', false, $when('hero_style', 'fullwidth')),
                $c('fullwidth_overlay_start', 'Overlay start', '#000000', $when('hero_style', 'fullwidth')),
                $c('fullwidth_overlay_end', 'Overlay end', '#00000000', $when('hero_style', 'fullwidth')),
                $s('fullwidth_overlay_opacity', 'Overlay strength', '0.5', [['0.3', 'Light'], ['0.5', 'Medium'], ['0.7', 'Dark']], $when('hero_style', 'fullwidth')),
                // product showcase
                $s('showcase_product_source', 'Products shown', 'featured', [['featured', 'Featured'], ['new_arrivals', 'New arrivals'], ['best_sellers', 'Best sellers'], ['on_sale', 'On sale'], ['random', 'Random']], $when('hero_style', 'product_showcase')),
                $n('showcase_product_count', 'Number of products', 4, 1, 12, $when('hero_style', 'product_showcase')),
                $c('showcase_bg_color', 'Section background', '#f9fafb', $when('hero_style', 'product_showcase')),
                $c('showcase_card_bg_color', 'Card background', '#ffffff', $when('hero_style', 'product_showcase')),
                $c('showcase_image_bg_color', 'Image background', '#f3f4f6', $when('hero_style', 'product_showcase')),
                // video
                ['key' => 'video_url', 'label' => 'Video file URL (MP4)', 'type' => 'url', 'default' => '', 'max' => 500] + $when('hero_style', 'video'),
                $x('video_embed_id', 'YouTube/Vimeo ID (alternative)', '', ['max' => 60] + $when('hero_style', 'video')),
                $t('video_loop', 'Loop', true, $when('hero_style', 'video')), $t('video_muted', 'Muted (needed for autoplay)', true, $when('hero_style', 'video')),
                $t('video_show_controls', 'Show controls', false, $when('hero_style', 'video')),
                ['key' => 'video_fallback_image', 'label' => 'Fallback image', 'type' => 'image', 'default' => ''] + $when('hero_style', 'video'),
            ]],
            ['id' => 'home', 'title' => 'Homepage sections', 'fields' => [
                $t('show_features', 'Features strip', true), $t('show_categories', 'Categories', true), $t('show_featured', 'Featured products', true),
                $t('show_new_arrivals', 'New arrivals', true), $t('show_newsletter', 'Newsletter signup', true),
            ]],
            ['id' => 'footer', 'title' => 'Footer', 'fields' => [
                $s('footer_style', 'Footer layout', 'default', [['default', 'Default (4 columns)'], ['minimal', 'Minimal (centered)'], ['expanded', 'Expanded (with newsletter)']]),
                $c('footer_bg_color', 'Background', '#111827'), $c('footer_text_color', 'Text', '#9ca3af'), $c('footer_link_hover', 'Link hover', '#ffffff'),
                $t('footer_hide_name', 'Hide store name', false), $t('footer_show_tagline', 'Show tagline', true),
                $t('footer_show_social', 'Social links', true), $t('footer_show_newsletter', 'Newsletter form', true),
                $x('footer_copyright', 'Copyright text', '© {year} {store_name}. All rights reserved.', ['hint' => 'Use {year} and {store_name}']),
            ]],
            ['id' => 'advanced', 'title' => 'Advanced', 'fields' => [
                ['key' => 'custom_css', 'label' => 'Custom CSS', 'type' => 'code', 'default' => '', 'max' => 20000, 'hint' => 'Added to every storefront page of this theme'],
            ]],
        ];
    }

    /* ------------------------------------------------------------------ values */

    /** Values the admin saved for this theme (only customised keys). */
    public function savedOptions(string $slug): array
    {
        $raw = Setting::getFresh('themes.options.' . $slug, []);
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }

        return is_array($raw) ? $raw : [];
    }

    public function saveOptions(string $slug, array $values): void
    {
        Setting::set('themes.options.' . $slug, (object) $values, 'json', 'theme');
    }

    /** The theme's own defaults (theme.json "defaults"), limited to known fields. */
    public function themeDefaults(string $slug): array
    {
        $known = $this->fields($slug);

        return array_intersect_key((array) ($this->manifest($slug)['defaults'] ?? []), $known);
    }

    /**
     * What the customizer shows as "default" for each field: the theme's own default, else the store-wide
     * theme.* setting (the Appearance page), else the schema default.
     */
    public function effectiveDefaults(string $slug): array
    {
        $own = $this->themeDefaults($slug);
        $out = [];
        foreach ($this->fields($slug) as $key => $field) {
            $out[$key] = $own[$key] ?? Setting::getFresh('theme.' . $key, $field['default'] ?? '');
        }

        return $out;
    }

    /** Fully resolved values for a theme (saved → theme default → store setting → schema default). */
    public function values(string $slug): array
    {
        return array_merge($this->effectiveDefaults($slug), $this->savedOptions($slug));
    }

    /** theme.* overrides to apply at runtime for the theme being rendered. */
    public function overlay(): array
    {
        $slug = $this->getActive();
        $values = array_merge($this->themeDefaults($slug), $this->savedOptions($slug), $this->draftOptions($slug));
        $known = $this->fields($slug);
        $overlay = [];
        foreach ($values as $key => $value) {
            if (isset($known[$key])) {
                $overlay['theme.' . $key] = $value;
            }
        }

        return $overlay;
    }

    /** Validate and normalise submitted customizer values; unknown keys are dropped. */
    public function sanitize(string $slug, array $input): array
    {
        $clean = [];
        $errors = [];
        foreach ($this->fields($slug) as $key => $f) {
            if (!array_key_exists($key, $input)) {
                continue;
            }
            $v = $input[$key];
            $label = $f['label'];
            switch ($f['type']) {
                case 'color':
                    if (!is_string($v) || !preg_match('/^#([0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $v)) { $errors[$key] = "$label must be a hex color like #2563eb."; continue 2; }
                    $clean[$key] = strtolower($v);
                    break;
                case 'toggle':
                    $clean[$key] = filter_var($v, FILTER_VALIDATE_BOOLEAN);
                    break;
                case 'select':
                    $allowed = array_map(fn ($o) => (string) $o[0], $f['options'] ?? []);
                    if (!is_scalar($v) || !in_array((string) $v, $allowed, true)) { $errors[$key] = "$label has an invalid option."; continue 2; }
                    $clean[$key] = (string) $v;
                    break;
                case 'number':
                    if (!is_numeric($v) || $v < ($f['min'] ?? PHP_INT_MIN) || $v > ($f['max'] ?? PHP_INT_MAX)) { $errors[$key] = "$label must be between {$f['min']} and {$f['max']}."; continue 2; }
                    $clean[$key] = (int) $v;
                    break;
                case 'image':
                    $v = is_string($v) ? trim($v) : '';
                    if ($v !== '' && (strlen($v) > 255 || preg_match('/^\s*(javascript|data|vbscript):/i', $v))) { $errors[$key] = "$label is not a valid image path."; continue 2; }
                    $clean[$key] = $v;
                    break;
                case 'url':
                    $v = is_string($v) ? trim($v) : '';
                    if ($v !== '' && (strlen($v) > ($f['max'] ?? 500) || !preg_match('#^(https?://|/)#i', $v))) { $errors[$key] = "$label must start with https:// or /."; continue 2; }
                    $clean[$key] = $v;
                    break;
                case 'code':
                    $v = is_string($v) ? $v : '';
                    // the CSS is printed inside a <style> tag, so it must not be able to close it
                    if (str_contains($v, '<') || strlen($v) > ($f['max'] ?? 20000)) { $errors[$key] = "$label must not contain '<' and must be under " . ($f['max'] ?? 20000) . ' characters.'; continue 2; }
                    $clean[$key] = $v;
                    break;
                default: // text
                    $v = is_scalar($v) ? trim((string) $v) : '';
                    if (strlen($v) > ($f['max'] ?? 255)) { $errors[$key] = "$label is too long."; continue 2; }
                    $clean[$key] = $v;
            }
        }

        return ['values' => $clean, 'errors' => $errors];
    }

    /* ------------------------------------------------------------------ rendering helpers */

    /** Make `view('home')` etc. resolve to the active theme's override first, falling back to the shared views. */
    public function registerViewPath(): void
    {
        $slug = $this->getActive();
        if ($slug !== 'default' || File::isDirectory($this->themesPath . '/default')) {
            $finder = view()->getFinder();
            $path = $this->themesPath . '/' . $slug;
            // drop earlier theme folders and cached lookups so switching themes within one process works too
            $finder->flush();
            $themesDir = str_replace('\\', '/', $this->themesPath) . '/';
            $paths = array_values(array_filter($finder->getPaths(), fn ($p) => !str_starts_with(str_replace('\\', '/', $p), $themesDir)));
            $finder->setPaths($paths);
            $finder->prependLocation($path);
        }
    }

    public function viewPath(string $view): string
    {
        return "themes.{$this->getActive()}.{$view}";
    }

    public function viewExists(string $view): bool
    {
        return view()->exists($this->viewPath($view));
    }

    public function asset(string $path): string
    {
        return asset("themes/{$this->getActive()}/{$path}");
    }

    public function getCssVariables(): string
    {
        $primaryColor = Setting::get('theme.primary_color', '#2563eb');
        $secondaryColor = Setting::get('theme.secondary_color', '#1e40af');
        $accentColor = Setting::get('theme.accent_color', '#f59e0b');
        $successColor = Setting::get('theme.success_color', '#10b981');
        $warningColor = Setting::get('theme.warning_color', '#f59e0b');
        $errorColor = Setting::get('theme.error_color', '#ef4444');
        $infoColor = Setting::get('theme.info_color', '#3b82f6');

        $fontFamily = Setting::get('theme.font_family', 'Inter');
        $headingFont = Setting::get('theme.heading_font') ?: $fontFamily;
        $fontSize = Setting::get('theme.font_size', '16');

        $borderRadius = $this->getBorderRadiusValue(Setting::get('theme.border_radius', 'md'));
        $animationSpeed = $this->getAnimationSpeedValue(Setting::get('theme.animation_speed', 'normal'));

        return "
            :root {
                /* Primary Colors */
                --color-primary: {$primaryColor};
                --color-primary-500: {$primaryColor};
                --color-primary-600: {$primaryColor};
                --color-primary-700: {$secondaryColor};
                --color-secondary: {$secondaryColor};
                --color-accent: {$accentColor};

                /* Status Colors */
                --color-success: {$successColor};
                --color-warning: {$warningColor};
                --color-error: {$errorColor};
                --color-info: {$infoColor};

                /* Typography */
                --font-family: '{$fontFamily}', sans-serif;
                --font-heading: '{$headingFont}', sans-serif;
                --font-size-base: {$fontSize}px;

                /* UI */
                --border-radius: {$borderRadius};
                --animation-speed: {$animationSpeed};
            }
        ";
    }

    protected function getBorderRadiusValue(string $size): string
    {
        return match ($size) {
            'none' => '0px', 'sm' => '4px', 'md' => '8px', 'lg' => '12px', 'xl' => '16px', default => '8px',
        };
    }

    protected function getAnimationSpeedValue(string $speed): string
    {
        return match ($speed) {
            'none' => '0ms', 'fast' => '150ms', 'normal' => '300ms', 'slow' => '500ms', default => '300ms',
        };
    }

    public function getCustomCss(): string
    {
        return Setting::get('theme.custom_css', '');
    }

    public function getCustomJs(): string
    {
        return Setting::get('theme.custom_js', '');
    }

    public function isDarkModeEnabled(): bool
    {
        return (bool) Setting::get('theme.enable_dark_mode', false);
    }

    public function isRtlEnabled(): bool
    {
        return (bool) Setting::get('theme.enable_rtl', false);
    }
}
