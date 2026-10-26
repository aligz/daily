<?php

/*
|--------------------------------------------------------------------------
| Test Bootstrap
|--------------------------------------------------------------------------
|
| Container dev meng-inject APP_ENV=local sebagai real env var (supaya
| Laravel load .env.local). PHPUnit `force="true"` hanya menulis $_ENV dan
| putenv, sedangkan Dotenv membaca $_SERVER lebih dulu — jadi APP_ENV=local
| tetap menang dan test bisa nyasar ke DB dev.
|
| Set di sini, sebelum autoload/Laravel boot, supaya `php artisan test`
| (dan vendor/bin/pest) selalu load .env.testing -> DB daily_test.
|
*/

$_SERVER['APP_ENV'] = 'testing';
$_ENV['APP_ENV'] = 'testing';
putenv('APP_ENV=testing');

require __DIR__.'/../vendor/autoload.php';
