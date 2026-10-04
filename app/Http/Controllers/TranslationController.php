<?php

namespace App\Http\Controllers;

use App\Services\TranslationService;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    protected TranslationService $translator;

    public function __construct(TranslationService $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Translate text via AJAX
     */
    public function translate(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:5000',
            'target' => 'required|string|size:2',
            'source' => 'nullable|string|size:2',
        ]);

        if (!$this->translator->isAvailable()) {
            return response()->json([
                'success' => false,
                'error' => 'AI translation not configured',
                'translated' => $request->text
            ]);
        }

        $translated = $this->translator->translate(
            $request->text,
            $request->target,
            $request->source ?? 'en'
        );

        return response()->json([
            'success' => true,
            'translated' => $translated,
            'source' => $request->source ?? 'en',
            'target' => $request->target
        ]);
    }

    /**
     * Translate multiple texts at once
     */
    public function translateBatch(Request $request)
    {
        $request->validate([
            'texts' => 'required|array',
            'texts.*' => 'string|max:5000',
            'target' => 'required|string|size:2',
            'source' => 'nullable|string|size:2',
        ]);

        if (!$this->translator->isAvailable()) {
            return response()->json([
                'success' => false,
                'error' => 'AI translation not configured',
                'translated' => $request->texts
            ]);
        }

        $translated = $this->translator->translateBatch(
            $request->texts,
            $request->target,
            $request->source ?? 'en'
        );

        return response()->json([
            'success' => true,
            'translated' => $translated,
            'source' => $request->source ?? 'en',
            'target' => $request->target
        ]);
    }

    /**
     * Check if translation is available
     */
    public function status()
    {
        return response()->json([
            'available' => $this->translator->isAvailable(),
            'provider' => setting('ai.provider', 'openai')
        ]);
    }
}
