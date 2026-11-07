<?php

use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$storageBase = is_writable(__DIR__.'/../storage') ? __DIR__.'/../storage' : sys_get_temp_dir().'/storage';
foreach ([
    $storageBase.'/app/public',
    $storageBase.'/framework/cache/data',
    $storageBase.'/framework/sessions',
    $storageBase.'/framework/views',
    $storageBase.'/logs',
] as $dir) {
    if (! is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

if ($storageBase !== __DIR__.'/../storage') {
    $app->useStoragePath($storageBase);
}

$app->booted(function () use ($storageBase) {
    if (empty(config('app.key'))) {
        config(['app.key' => 'base64:'.base64_encode(str_repeat('pabwe2026stock32', 2))]);
    }

    try {
        DB::connection()->getPdo();
    } catch (\Throwable) {
        $sqlitePath = $storageBase.'/framework/database.sqlite';
        if (! file_exists($sqlitePath)) {
            @touch($sqlitePath);
        }
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $sqlitePath,
            'session.driver' => 'file',
            'cache.default' => 'file',
        ]);
        DB::purge('sqlite');
    }

    try {
        if (! Schema::hasTable('users') || ! Schema::hasTable('stocks') || ! Schema::hasTable('sessions') || ! Schema::hasTable('cache')) {
            Artisan::call('migrate', ['--seed' => true, '--force' => true]);
        } elseif (User::count() === 0) {
            Artisan::call('db:seed', ['--force' => true]);
        }
    } catch (\Throwable) {
        // Fallback if migration/seeding encounters a non-fatal lock
    }
});

$app->handleRequest(Request::capture());
