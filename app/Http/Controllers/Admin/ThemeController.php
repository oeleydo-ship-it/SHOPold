<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ThemeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class ThemeController extends Controller
{
    public function __construct(protected ThemeService $themes)
    {
    }

    public function index()
    {
        $activated = $this->themes->getActivated();

        return Inertia::render('Admin/Themes/Index', [
            'themes' => collect($this->themes->getAll())->map(function ($m, $slug) use ($activated) {
                $values = $this->themes->values($slug);

                return [
                    'slug' => $slug, 'name' => $m['name'], 'description' => $m['description'], 'version' => $m['version'], 'author' => $m['author'],
                    'active' => $slug === $activated,
                    'preview_image' => $this->previewImage($slug),
                    'swatches' => array_values(array_filter([$values['primary_color'] ?? null, $values['secondary_color'] ?? null, $values['accent_color'] ?? null])),
                    'fonts' => [$values['heading_font'] ?: ($values['font_family'] ?? ''), $values['font_family'] ?? ''],
                    'customized' => count($this->themes->savedOptions($slug)) > 0,
                    'overrides' => $this->overrideCount($slug),
                ];
            })->values(),
            'previewing' => $this->themes->previewSlug(),
        ]);
    }

    public function customize(string $theme)
    {
        $this->themeOrFail($theme);
        $manifest = $this->themes->manifest($theme);

        return Inertia::render('Admin/Themes/Customize', [
            'theme' => ['slug' => $theme, 'name' => $manifest['name'], 'active' => $theme === $this->themes->getActivated()],
            'sections' => $this->themes->schema($theme),
            'values' => (object) $this->themes->values($theme),
            'defaults' => (object) $this->themes->effectiveDefaults($theme),
            'saved' => array_keys($this->themes->savedOptions($theme)),
            'storeName' => (string) setting('store.name', 'Store'),
        ]);
    }

    public function update(Request $request, string $theme)
    {
        $this->themeOrFail($theme);
        $result = $this->themes->sanitize($theme, (array) $request->input('values', []));

        if ($result['errors']) {
            return back()->withErrors($result['errors']);
        }

        // keep only what differs from the theme's defaults, so later default changes still apply
        $defaults = $this->themes->effectiveDefaults($theme);
        $saved = $this->themes->savedOptions($theme);
        foreach ($result['values'] as $key => $value) {
            if (array_key_exists($key, $saved) || $value !== ($defaults[$key] ?? null)) {
                $saved[$key] = $value;
            }
        }
        $this->themes->saveOptions($theme, $saved);
        $request->session()->forget('theme_preview_options');

        return back()->with('success', 'Theme settings saved.');
    }

    public function reset(Request $request, string $theme)
    {
        $this->themeOrFail($theme);
        $this->themes->saveOptions($theme, []);
        $request->session()->forget('theme_preview_options');

        return back()->with('success', 'Theme settings reset to the defaults.');
    }

    public function activate(Request $request, string $theme)
    {
        $this->themeOrFail($theme);
        $this->themes->setActive($theme);
        $request->session()->forget(['theme_preview', 'theme_preview_options']);

        return back()->with('success', ucfirst($theme) . ' is now your storefront theme.');
    }

    /** Start previewing a theme on the storefront (only this admin sees it). */
    public function preview(Request $request, string $theme)
    {
        $this->themeOrFail($theme);
        $request->session()->put('theme_preview', $theme);
        $request->session()->forget('theme_preview_options');

        return redirect('/');
    }

    /** Customizer live preview: remember unsaved values for the preview iframe. */
    public function draft(Request $request, string $theme)
    {
        $this->themeOrFail($theme);
        $result = $this->themes->sanitize($theme, (array) $request->input('values', []));
        $request->session()->put('theme_preview', $theme);
        $request->session()->put('theme_preview_options', $result['values']);

        return response()->json(['ok' => true, 'errors' => $result['errors']]);
    }

    public function stopPreview(Request $request)
    {
        $request->session()->forget(['theme_preview', 'theme_preview_options']);

        return $request->header('X-Inertia') || !$request->expectsJson() ? back() : response()->json(['ok' => true]);
    }

    /** First screenshot found in public/themes/<slug>/ (preview.png, .jpg, .webp or .svg). */
    protected function previewImage(string $slug): ?string
    {
        foreach (['png', 'jpg', 'webp', 'svg'] as $ext) {
            if (File::exists(public_path("themes/{$slug}/preview.{$ext}"))) {
                return asset("themes/{$slug}/preview.{$ext}");
            }
        }

        return null;
    }

    protected function themeOrFail(string $theme): void
    {
        abort_unless($this->themes->exists($theme), 404);
    }

    /** Number of storefront views this theme replaces. */
    protected function overrideCount(string $slug): int
    {
        $dir = resource_path("views/themes/{$slug}");

        return collect(File::exists($dir) ? File::allFiles($dir) : [])->filter(fn ($f) => str_ends_with($f->getFilename(), '.blade.php'))->count();
    }
}
