<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function show($slug)
    {
        $page = Page::where('slug', $slug)->where('is_active', true)->with('translations')->firstOrFail();
        
        return $this->renderPage($page);
    }

    public function about()
    {
        $page = Page::where('slug', 'about-us')->where('is_active', true)->with('translations')->first();
        
        if (!$page) {
            abort(404);
        }
        
        return $this->renderPage($page);
    }

    public function contact()
    {
        $page = Page::where('slug', 'contact-us')->where('is_active', true)->with('translations')->first();
        
        if (!$page) {
            abort(404);
        }
        
        return $this->renderPage($page);
    }

    protected function renderPage(Page $page)
    {
        return \Inertia\Inertia::render('Storefront/Page', [
            'page' => [
                'title' => $page->translated_title,
                'excerpt' => $page->excerpt,
                'image' => $page->featured_image ? $page->featured_image_url : null,
                // CMS HTML, sanitised; same-site POST forms get the CSRF token added
                'content' => safe_page_html($page->translated_content ?? $page->content),
                'template' => $page->template === 'full-width' ? 'full-width' : 'default',
            ],
            'seo' => [
                'title' => (string) ($page->getTranslatedAttribute('meta_title') ?? $page->translated_title),
                'description' => (string) ($page->getTranslatedAttribute('meta_description') ?? $page->excerpt ?? ''),
                'image' => $page->featured_image ? $page->featured_image_url : null,
                'canonical' => url()->current(),
            ],
        ]);
    }
}
