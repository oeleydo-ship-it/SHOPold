<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TranslationController extends Controller
{
    protected TranslationService $translationService;

    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    public function index(Request $request)
    {
        $type = $request->get('type', 'products');
        $locale = $request->get('locale');
        $search = $request->get('search');
        $availableLocales = $this->getAvailableLocales();
        $defaultLocale = setting('store.default_language', config('app.fallback_locale', 'en'));
        
        // Set default locale to first non-default language
        if (!$locale) {
            $nonDefaultLocales = array_diff(array_keys($availableLocales), [$defaultLocale]);
            $locale = reset($nonDefaultLocales) ?: 'es';
        }
        
        $items = collect();

        switch ($type) {
            case 'products':
                $query = Product::query();
                if ($search) {
                    $query->where('name', 'like', "%{$search}%");
                }
                $items = $query->with(['translations' => fn($q) => $q->where('locale', $locale)])
                    ->orderBy('name')
                    ->paginate(20);
                break;
            case 'categories':
                $query = Category::query();
                if ($search) {
                    $query->where('name', 'like', "%{$search}%");
                }
                $items = $query->with(['translations' => fn($q) => $q->where('locale', $locale)])
                    ->orderBy('name')
                    ->paginate(20);
                break;
            case 'brands':
                $query = Brand::query();
                if ($search) {
                    $query->where('name', 'like', "%{$search}%");
                }
                $items = $query->with(['translations' => fn($q) => $q->where('locale', $locale)])
                    ->orderBy('name')
                    ->paginate(20);
                break;
            case 'pages':
                $query = Page::query();
                if ($search) {
                    $query->where('title', 'like', "%{$search}%");
                }
                $items = $query->with(['translations' => fn($q) => $q->where('locale', $locale)])
                    ->orderBy('title')
                    ->paginate(20);
                break;
        }

        $isPage = $type === 'pages';
        $strip = fn ($t) => $t ? Str::limit(strip_tags($t), 80) : null;

        return Inertia::render('Admin/Translations/Index', [
            'type' => $type, 'locale' => $locale, 'search' => (string) $search,
            'locales' => collect($availableLocales)->except($defaultLocale)->map(fn ($n, $c) => ['code' => (string) $c, 'name' => $n])->values(),
            'defaultName' => $availableLocales[$defaultLocale] ?? 'English',
            'localeName' => $availableLocales[$locale] ?? $locale,
            'items' => [
                'data' => $items->getCollection()->map(function ($item) use ($isPage, $type, $strip) {
                    $tr = $item->translations->first();
                    $trName = $tr ? ($tr->name ?? $tr->title) : null;
                    return [
                        'id' => $item->id,
                        'name' => $isPage ? $item->title : $item->name,
                        'excerpt' => $strip($item->description ?? $item->short_description ?? null),
                        'image' => $type === 'products' && $item->image ? asset('storage/' . $item->image) : null,
                        'translated' => (bool) ($tr && !empty($trName)),
                        'translation' => $trName,
                        'translation_excerpt' => $strip($tr?->description),
                    ];
                })->values(),
                'links' => $items->linkCollection()->toArray(), 'from' => $items->firstItem(), 'to' => $items->lastItem(), 'total' => $items->total(),
            ],
        ]);
    }

    public function edit(Request $request, string $type, int $id)
    {
        $locale = $request->get('locale', 'es');
        $availableLocales = $this->getAvailableLocales();
        $defaultLocale = setting('store.default_language', config('app.fallback_locale', 'en'));
        
        $item = $this->getItem($type, $id);
        if (!$item) {
            return redirect()->route('admin.translations.index')
                ->with('error', 'Item not found');
        }
        
        $translation = $item->translations()->where('locale', $locale)->first();
        $fields = $this->getTranslatableFields($type);
        
        return Inertia::render('Admin/Translations/Edit', [
            'type' => $type, 'locale' => $locale,
            'localeName' => $availableLocales[$locale] ?? $locale,
            'defaultCode' => $defaultLocale, 'defaultName' => $availableLocales[$defaultLocale] ?? 'English',
            'item' => ['id' => $item->id, 'name' => $type === 'pages' ? $item->title : $item->name],
            'locales' => collect($availableLocales)->except($defaultLocale)->map(fn ($n, $c) => ['code' => (string) $c, 'name' => $n, 'done' => $item->hasTranslation((string) $c)])->values(),
            'fields' => collect($fields)->map(fn ($label, $key) => [
                'key' => $key, 'label' => $label, 'original' => (string) ($item->{$key} ?? ''), 'value' => (string) ($translation->{$key} ?? ''),
                'multiline' => in_array($key, ['description', 'content', 'short_description', 'meta_description']),
            ])->values(),
        ]);
    }

    public function update(Request $request, string $type, int $id)
    {
        $locale = $request->input('locale');
        abort_unless(is_string($locale) && array_key_exists($locale, $this->getAvailableLocales()), 422, 'Unknown language.');
        $item = $this->getItem($type, $id);
        
        if (!$item) {
            return redirect()->route('admin.translations.index')
                ->with('error', 'Item not found');
        }
        
        $fields = $this->getTranslatableFields($type);
        $data = $request->only(array_keys($fields));
        
        $item->translations()->updateOrCreate(
            ['locale' => $locale],
            $data
        );
        
        return redirect()
            ->route('admin.translations.edit', ['type' => $type, 'id' => $id, 'locale' => $locale])
            ->with('success', 'Translation saved successfully');
    }

    public function autoTranslate(Request $request, string $type, int $id)
    {
        $locale = $request->input('locale');
        $item = $this->getItem($type, $id);
        
        if (!$item) {
            return response()->json(['error' => 'Item not found'], 404);
        }
        
        if (!$this->translationService->isAvailable()) {
            return response()->json(['error' => 'AI translation not configured. Please configure your AI provider in Settings.'], 400);
        }
        
        $fields = $this->getTranslatableFields($type);
        $translations = [];
        $defaultLocale = setting('store.default_language', config('app.fallback_locale', 'en'));
        
        foreach ($fields as $field => $label) {
            $originalValue = $item->{$field};
            if (!empty($originalValue)) {
                $translations[$field] = $this->translationService->translate(
                    $originalValue, 
                    $locale, 
                    $defaultLocale
                );
            }
        }
        
        if (!empty($translations)) {
            $item->translations()->updateOrCreate(
                ['locale' => $locale],
                $translations
            );
        }
        
        return response()->json(['translations' => $translations]);
    }

    public function bulkAutoTranslate(Request $request)
    {
        $type = $request->input('type');
        $locale = $request->input('locale');
        $ids = $request->input('ids', []);
        
        if (!$this->translationService->isAvailable()) {
            return response()->json(['error' => 'AI translation not configured. Please configure your AI provider in Settings.'], 400);
        }
        
        $translated = 0;
        $fields = $this->getTranslatableFields($type);
        $defaultLocale = setting('store.default_language', config('app.fallback_locale', 'en'));
        
        foreach ($ids as $id) {
            $item = $this->getItem($type, $id);
            if (!$item) continue;
            
            $translations = [];
            foreach ($fields as $field => $label) {
                $originalValue = $item->{$field};
                if (!empty($originalValue)) {
                    $translations[$field] = $this->translationService->translate(
                        $originalValue, 
                        $locale, 
                        $defaultLocale
                    );
                }
            }
            
            if (!empty($translations)) {
                $item->translations()->updateOrCreate(
                    ['locale' => $locale],
                    $translations
                );
                $translated++;
            }
        }
        
        return response()->json([
            'success' => true,
            'translated' => $translated
        ]);
    }

    public function stats()
    {
        $locales = $this->getAvailableLocales();
        $defaultLocale = setting('store.default_language', config('app.fallback_locale', 'en'));
        unset($locales[$defaultLocale]);
        
        $stats = [];
        
        foreach ($locales as $code => $name) {
            $stats[$code] = [
                'name' => $name,
                'products' => [
                    'total' => Product::count(),
                    'translated' => Product::whereHas('translations', fn($q) => $q->where('locale', $code))->count()
                ],
                'categories' => [
                    'total' => Category::count(),
                    'translated' => Category::whereHas('translations', fn($q) => $q->where('locale', $code))->count()
                ],
                'brands' => [
                    'total' => Brand::count(),
                    'translated' => Brand::whereHas('translations', fn($q) => $q->where('locale', $code))->count()
                ],
                'pages' => [
                    'total' => Page::count(),
                    'translated' => Page::whereHas('translations', fn($q) => $q->where('locale', $code))->count()
                ],
            ];
        }
        
        return Inertia::render('Admin/Translations/Stats', [
            'stats' => collect($stats)->map(fn ($s, $code) => ['code' => (string) $code] + $s)->values(),
        ]);
    }

    protected function getItem(string $type, int $id)
    {
        return match($type) {
            'products' => Product::find($id),
            'categories' => Category::find($id),
            'brands' => Brand::find($id),
            'pages' => Page::find($id),
            default => null
        };
    }

    protected function getTranslatableFields(string $type): array
    {
        return match($type) {
            'products' => [
                'name' => 'Name',
                'short_description' => 'Short Description',
                'description' => 'Description',
                'meta_title' => 'Meta Title',
                'meta_description' => 'Meta Description',
            ],
            'categories' => [
                'name' => 'Name',
                'description' => 'Description',
                'meta_title' => 'Meta Title',
                'meta_description' => 'Meta Description',
            ],
            'brands' => [
                'name' => 'Name',
                'description' => 'Description',
            ],
            'pages' => [
                'title' => 'Title',
                'content' => 'Content',
                'meta_title' => 'Meta Title',
                'meta_description' => 'Meta Description',
            ],
            default => []
        };
    }

    protected function getAvailableLocales(): array
    {
        // All available language names
        $allLanguages = [
            'en' => 'English',
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'nl' => 'Dutch',
            'ru' => 'Russian',
            'zh' => 'Chinese',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'ar' => 'Arabic',
            'hi' => 'Hindi',
            'tr' => 'Turkish',
            'pl' => 'Polish',
            'th' => 'Thai',
            'vi' => 'Vietnamese',
            'id' => 'Indonesian',
            'ms' => 'Malay',
            'fil' => 'Filipino',
        ];
        
        // Try to get from available_languages_list first (array of codes)
        $storedList = setting('store.available_languages_list');
        if ($storedList) {
            if (is_string($storedList)) {
                $codes = json_decode($storedList, true) ?? ['en'];
            } else {
                $codes = $storedList;
            }
            
            $result = [];
            foreach ($codes as $code) {
                $result[$code] = $allLanguages[$code] ?? ucfirst($code);
            }
            return $result;
        }
        
        // Fallback to available_languages (object with code => name)
        $storedLangs = setting('store.available_languages', '{"en":"English"}');
        if (is_string($storedLangs)) {
            $availableLanguages = json_decode($storedLangs, true) ?? ['en' => 'English'];
        } else {
            $availableLanguages = $storedLangs;
        }
        
        // Ensure we have at least English
        if (empty($availableLanguages)) {
            return ['en' => 'English'];
        }
        
        // Clean up language names (remove parenthetical translations)
        $cleanedLocales = [];
        foreach ($availableLanguages as $code => $name) {
            $cleanName = preg_replace('/\s*\([^)]+\)/', '', $name);
            $cleanedLocales[$code] = trim($cleanName);
        }
        
        return $cleanedLocales;
    }
}
