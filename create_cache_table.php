<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

// Create cache table
if (!Schema::hasTable('cache')) {
    Schema::create('cache', function (Blueprint $table) {
        $table->string('key')->primary();
        $table->mediumText('value');
        $table->integer('expiration');
    });
    echo "Created cache table\n";
} else {
    echo "Cache table already exists\n";
}

// Create cache_locks table
if (!Schema::hasTable('cache_locks')) {
    Schema::create('cache_locks', function (Blueprint $table) {
        $table->string('key')->primary();
        $table->string('owner');
        $table->integer('expiration');
    });
    echo "Created cache_locks table\n";
} else {
    echo "Cache_locks table already exists\n";
}

// Create sessions table
if (!Schema::hasTable('sessions')) {
    Schema::create('sessions', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->foreignId('user_id')->nullable()->index();
        $table->string('ip_address', 45)->nullable();
        $table->text('user_agent')->nullable();
        $table->longText('payload');
        $table->integer('last_activity')->index();
    });
    echo "Created sessions table\n";
} else {
    echo "Sessions table already exists\n";
}

// Create settings table
if (!Schema::hasTable('settings')) {
    Schema::create('settings', function (Blueprint $table) {
        $table->id();
        $table->string('group')->default('general');
        $table->string('key')->unique();
        $table->text('value')->nullable();
        $table->string('type')->default('string');
        $table->timestamps();
    });
    echo "Created settings table\n";
} else {
    echo "Settings table already exists\n";
}

echo "\nDone!\n";


