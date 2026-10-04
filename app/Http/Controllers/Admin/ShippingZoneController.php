<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingZone;
use App\Models\ShippingMethod;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShippingZoneController extends Controller
{
    public function index()
    {
        $zones = ShippingZone::with('methods')->orderBy('sort_order')->get();
        return Inertia::render('Admin/Shipping/Index', [
            'zones' => $zones->map(fn ($z) => [
                'id' => $z->id, 'name' => $z->name, 'is_active' => (bool) $z->is_active,
                'all_countries' => in_array('*', $z->countries ?? []), 'countries_count' => count($z->countries ?? []),
                'methods' => $z->methods->map(fn ($m) => $this->methodRow($m))->values(),
            ])->values(),
        ]);
    }

    public function create()
    {
        $countries = $this->getCountries();
        $allowedCountries = json_decode(setting('shipping.allowed_countries', '[]'), true) ?: [];
        $defaultCountry = setting('shipping.default_country', 'US');
        
        return Inertia::render('Admin/Shipping/Form', [
            'zone' => null, 'methods' => [],
            'countries' => $this->countryList($countries), 'allowedCountries' => array_values($allowedCountries), 'defaultCountry' => $defaultCountry,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'countries' => 'required|array',
        ]);

        $zone = ShippingZone::create([
            'name' => $request->name,
            'countries' => $request->countries,
            'regions' => $request->regions ?? [],
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return redirect()->route('admin.shipping.edit', $zone)->with('success', 'Shipping zone created successfully!');
    }

    public function edit(ShippingZone $zone)
    {
        $countries = $this->getCountries();
        $allowedCountries = json_decode(setting('shipping.allowed_countries', '[]'), true) ?: [];
        $defaultCountry = setting('shipping.default_country', 'US');
        $zone->load('methods');
        
        return Inertia::render('Admin/Shipping/Form', [
            'zone' => [
                'id' => $zone->id, 'name' => $zone->name, 'countries' => array_values($zone->countries ?? []),
                'is_active' => (bool) $zone->is_active, 'sort_order' => (int) $zone->sort_order,
            ],
            'methods' => $zone->methods->map(fn ($m) => $this->methodRow($m))->values(),
            'countries' => $this->countryList($countries), 'allowedCountries' => array_values($allowedCountries), 'defaultCountry' => $defaultCountry,
        ]);
    }

    public function update(Request $request, ShippingZone $zone)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'countries' => 'required|array',
        ]);

        $zone->update([
            'name' => $request->name,
            'countries' => $request->countries,
            'regions' => $request->regions ?? [],
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return redirect()->back()->with('success', 'Shipping zone updated successfully!');
    }

    public function destroy(ShippingZone $zone)
    {
        $zone->delete();
        return redirect()->route('admin.shipping.index')->with('success', 'Shipping zone deleted successfully!');
    }

    // Shipping Methods
    public function storeMethod(Request $request, ShippingZone $zone)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:flat_rate,free,weight_based,price_based,per_item',
            'cost' => 'nullable|numeric|min:0',
        ]);

        $zone->methods()->create([
            'name' => $request->name,
            'type' => $request->type,
            'cost' => $request->cost ?? 0,
            'min_order_amount' => $request->min_order_amount,
            'per_kg_rate' => $request->per_kg_rate,
            'per_item_rate' => $request->per_item_rate,
            'delivery_time' => $request->delivery_time,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return redirect()->back()->with('success', 'Shipping method added successfully!');
    }

    public function updateMethod(Request $request, ShippingMethod $method)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:flat_rate,free,weight_based,price_based,per_item',
        ]);

        $method->update([
            'name' => $request->name,
            'type' => $request->type,
            'cost' => $request->cost ?? 0,
            'min_order_amount' => $request->min_order_amount,
            'per_kg_rate' => $request->per_kg_rate,
            'per_item_rate' => $request->per_item_rate,
            'delivery_time' => $request->delivery_time,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return redirect()->back()->with('success', 'Shipping method updated successfully!');
    }

    public function destroyMethod(ShippingMethod $method)
    {
        $zoneId = $method->shipping_zone_id;
        $method->delete();
        return redirect()->route('admin.shipping.edit', $zoneId)->with('success', 'Shipping method deleted successfully!');
    }

    private function methodRow(ShippingMethod $m): array
    {
        return [
            'id' => $m->id, 'name' => $m->name, 'type' => $m->type, 'is_active' => (bool) $m->is_active,
            'cost' => (string) ($m->cost ?? 0), 'min_order_amount' => $m->min_order_amount === null ? '' : (string) $m->min_order_amount,
            'per_kg_rate' => $m->per_kg_rate === null ? '' : (string) $m->per_kg_rate, 'per_item_rate' => $m->per_item_rate === null ? '' : (string) $m->per_item_rate,
            'delivery_time' => (string) ($m->delivery_time ?? ''), 'sort_order' => (int) $m->sort_order,
            'cost_label' => format_currency($m->cost ?? 0), 'min_label' => $m->min_order_amount ? format_currency($m->min_order_amount) : null,
            'per_kg_label' => $m->per_kg_rate ? format_currency($m->per_kg_rate) : null,
        ];
    }

    private function countryList(array $countries): array
    {
        return collect($countries)->map(fn ($name, $code) => ['code' => (string) $code, 'name' => $name])->values()->all();
    }

    private function getCountries(): array
    {
        return [
            '*' => 'All Countries',
            'AF' => 'Afghanistan', 'AL' => 'Albania', 'DZ' => 'Algeria', 'AD' => 'Andorra', 'AO' => 'Angola',
            'AR' => 'Argentina', 'AM' => 'Armenia', 'AU' => 'Australia', 'AT' => 'Austria', 'AZ' => 'Azerbaijan',
            'BS' => 'Bahamas', 'BH' => 'Bahrain', 'BD' => 'Bangladesh', 'BB' => 'Barbados', 'BY' => 'Belarus',
            'BE' => 'Belgium', 'BZ' => 'Belize', 'BJ' => 'Benin', 'BT' => 'Bhutan', 'BO' => 'Bolivia',
            'BA' => 'Bosnia and Herzegovina', 'BW' => 'Botswana', 'BR' => 'Brazil', 'BN' => 'Brunei', 'BG' => 'Bulgaria',
            'CA' => 'Canada', 'CL' => 'Chile', 'CN' => 'China', 'CO' => 'Colombia', 'CR' => 'Costa Rica',
            'HR' => 'Croatia', 'CU' => 'Cuba', 'CY' => 'Cyprus', 'CZ' => 'Czech Republic', 'DK' => 'Denmark',
            'DO' => 'Dominican Republic', 'EC' => 'Ecuador', 'EG' => 'Egypt', 'SV' => 'El Salvador', 'EE' => 'Estonia',
            'ET' => 'Ethiopia', 'FI' => 'Finland', 'FR' => 'France', 'DE' => 'Germany', 'GH' => 'Ghana',
            'GR' => 'Greece', 'GT' => 'Guatemala', 'HN' => 'Honduras', 'HK' => 'Hong Kong', 'HU' => 'Hungary',
            'IS' => 'Iceland', 'IN' => 'India', 'ID' => 'Indonesia', 'IR' => 'Iran', 'IQ' => 'Iraq',
            'IE' => 'Ireland', 'IL' => 'Israel', 'IT' => 'Italy', 'JM' => 'Jamaica', 'JP' => 'Japan',
            'JO' => 'Jordan', 'KZ' => 'Kazakhstan', 'KE' => 'Kenya', 'KR' => 'South Korea', 'KW' => 'Kuwait',
            'LV' => 'Latvia', 'LB' => 'Lebanon', 'LY' => 'Libya', 'LT' => 'Lithuania', 'LU' => 'Luxembourg',
            'MY' => 'Malaysia', 'MV' => 'Maldives', 'MT' => 'Malta', 'MX' => 'Mexico', 'MA' => 'Morocco',
            'NL' => 'Netherlands', 'NZ' => 'New Zealand', 'NG' => 'Nigeria', 'NO' => 'Norway', 'OM' => 'Oman',
            'PK' => 'Pakistan', 'PA' => 'Panama', 'PY' => 'Paraguay', 'PE' => 'Peru', 'PH' => 'Philippines',
            'PL' => 'Poland', 'PT' => 'Portugal', 'QA' => 'Qatar', 'RO' => 'Romania', 'RU' => 'Russia',
            'SA' => 'Saudi Arabia', 'RS' => 'Serbia', 'SG' => 'Singapore', 'SK' => 'Slovakia', 'SI' => 'Slovenia',
            'ZA' => 'South Africa', 'ES' => 'Spain', 'LK' => 'Sri Lanka', 'SE' => 'Sweden', 'CH' => 'Switzerland',
            'TW' => 'Taiwan', 'TH' => 'Thailand', 'TR' => 'Turkey', 'UA' => 'Ukraine', 'AE' => 'United Arab Emirates',
            'GB' => 'United Kingdom', 'US' => 'United States', 'UY' => 'Uruguay', 'VE' => 'Venezuela', 'VN' => 'Vietnam',
            'YE' => 'Yemen', 'ZM' => 'Zambia', 'ZW' => 'Zimbabwe',
        ];
    }
}
