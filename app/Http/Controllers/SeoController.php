<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\Vendor;
use App\Support\Modules;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/** /sitemap.xml and /robots.txt for search engines. */
class SeoController extends Controller
{
    /** Pages search engines should not crawl: private areas, carts and forms. */
    public const PRIVATE_PATHS = ['/admin', '/vendor', '/driver', '/account', '/cart', '/checkout', '/helpdesk', '/install', '/api/', '/login', '/register', '/email/', '/download/', '/payment/'];

    public function sitemap(): Response
    {
        // Rebuilt at most once an hour; large catalogues stay cheap to crawl.
        $xml = Cache::remember('seo.sitemap', 3600, fn () => $this->buildSitemap());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $lines = ['User-agent: *'];
        foreach (self::PRIVATE_PATHS as $path) {
            $lines[] = 'Disallow: ' . $path;
        }
        $lines[] = '';
        $lines[] = 'Sitemap: ' . url('sitemap.xml');

        return response(implode("\n", $lines) . "\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    protected function buildSitemap(): string
    {
        $urls = [
            [url('/'), null, 'daily', '1.0'],
            [route('shop.index'), null, 'daily', '0.9'],
        ];

        Category::query()->active()->get(['slug', 'updated_at'])
            ->each(function ($c) use (&$urls) { $urls[] = [route('shop.category', $c->slug), $c->updated_at, 'weekly', '0.8']; });

        Product::query()->active()->select(['slug', 'updated_at'])->orderBy('id')
            ->chunk(1000, function ($products) use (&$urls) {
                foreach ($products as $p) {
                    $urls[] = [route('shop.show', $p->slug), $p->updated_at, 'weekly', '0.7'];
                }
            });

        Page::query()->where('is_active', true)->get(['slug', 'updated_at'])->each(function ($p) use (&$urls) {
            $urls[] = [match ($p->slug) { 'about-us' => route('about'), 'contact-us' => route('contact'), default => route('page.show', $p->slug) }, $p->updated_at, 'monthly', '0.5'];
        });

        if (Modules::enabled('blog')) {
            $urls[] = [route('blog.index'), null, 'weekly', '0.6'];
            BlogCategory::query()->where('is_active', true)->get(['slug', 'updated_at'])
                ->each(function ($c) use (&$urls) { $urls[] = [route('blog.category', $c->slug), $c->updated_at, 'weekly', '0.5']; });
            BlogPost::published()->get(['slug', 'updated_at'])
                ->each(function ($p) use (&$urls) { $urls[] = [route('blog.show', $p->slug), $p->updated_at, 'monthly', '0.6']; });
        }

        if (Modules::enabled('multi_vendor')) {
            Vendor::query()->where('status', 'approved')->get(['slug', 'updated_at'])
                ->each(function ($v) use (&$urls) { $urls[] = [route('shop.vendor', $v->slug), $v->updated_at, 'weekly', '0.5']; });
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as [$loc, $modified, $freq, $priority]) {
            $xml .= '  <url><loc>' . htmlspecialchars($loc, ENT_XML1) . '</loc>'
                . ($modified ? '<lastmod>' . $modified->toAtomString() . '</lastmod>' : '')
                . "<changefreq>{$freq}</changefreq><priority>{$priority}</priority></url>\n";
        }

        return $xml . '</urlset>' . "\n";
    }
}
