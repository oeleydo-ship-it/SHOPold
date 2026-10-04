<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add email notification settings
        $settings = [
            ['key' => 'email.order_confirmation', 'value' => '1', 'type' => 'boolean'],
            ['key' => 'email.order_shipped', 'value' => '1', 'type' => 'boolean'],
            ['key' => 'email.order_delivered', 'value' => '1', 'type' => 'boolean'],
            ['key' => 'email.order_notifications', 'value' => '1', 'type' => 'boolean'],
            ['key' => 'email.admin_new_order', 'value' => '1', 'type' => 'boolean'],
            ['key' => 'email.admin_email', 'value' => '', 'type' => 'string'],
            ['key' => 'email.welcome_email', 'value' => '1', 'type' => 'boolean'],
            ['key' => 'email.password_reset', 'value' => '1', 'type' => 'boolean'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
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
        Setting::whereIn('key', [
            'email.order_confirmation',
            'email.order_shipped',
            'email.order_delivered',
            'email.order_notifications',
            'email.admin_new_order',
            'email.admin_email',
            'email.welcome_email',
            'email.password_reset',
        ])->delete();
    }
};
