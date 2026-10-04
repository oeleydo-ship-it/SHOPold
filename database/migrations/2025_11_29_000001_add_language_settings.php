<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add default language settings
        $settings = [
            ['key' => 'store.default_language', 'value' => 'en', 'type' => 'string', 'group' => 'store'],
            ['key' => 'store.available_languages', 'value' => json_encode(['en' => 'English']), 'type' => 'json', 'group' => 'store'],
            ['key' => 'store.show_language_switcher', 'value' => '1', 'type' => 'boolean', 'group' => 'store'],
            ['key' => 'store.show_currency_switcher', 'value' => '1', 'type' => 'boolean', 'group' => 'store'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'store.default_language',
            'store.available_languages',
            'store.show_language_switcher',
            'store.show_currency_switcher',
        ])->delete();
    }
};
