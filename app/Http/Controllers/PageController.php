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
        // Choose template based on page setting
        $template = 'pages.templates.' . $page->template;
        
        // Fallback to default if template doesn't exist
        if (!view()->exists($template)) {
            $template = 'pages.templates.default';
        }
        
        return view($template, compact('page'));
    }
}
