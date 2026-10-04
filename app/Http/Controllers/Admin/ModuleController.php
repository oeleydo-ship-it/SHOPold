<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Modules;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ModuleController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Modules/Index', [
            'modules' => collect(Modules::all())->map(fn ($m, $key) => [
                'key' => $key, 'name' => $m['name'], 'description' => $m['description'], 'group' => $m['group'], 'icon' => $m['icon'],
                'link' => $m['link'] ?? null,
                'enabled' => Modules::enabled($key),
                'options' => collect($m['options'] ?? [])->map(fn ($o) => $o)->values(),
                'values' => (object) Modules::optionValues($m),
            ])->values(),
        ]);
    }

    public function toggle(Request $request, string $module)
    {
        $def = Modules::get($module);
        abort_unless($def, 404);
        $request->validate(['enabled' => 'required|boolean']);

        $on = $request->boolean('enabled');
        Setting::set($def['toggle'], $on, 'boolean', 'features');

        return back()->with('success', $def['name'] . ($on ? ' enabled.' : ' disabled.'));
    }

    public function options(Request $request, string $module)
    {
        $def = Modules::get($module);
        abort_unless($def && !empty($def['options']), 404);

        foreach (Modules::sanitizeOptions($def, (array) $request->input('values', [])) as $key => $value) {
            Setting::set($key, $value, is_bool($value) ? 'boolean' : (is_int($value) ? 'integer' : 'string'), 'features');
        }

        return back()->with('success', $def['name'] . ' settings saved.');
    }
}
