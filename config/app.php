<?php
return [

    'name' => env('APP_NAME', 'Laravel'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost'),

    'timezone' => 'UTC',


    'image_storage_path' => env('IMAGE_STORAGE_PATH', 'storage/projects'),

    /*
    |--------------------------------------------------------------------------
    | إعدادات اللغات
    |--------------------------------------------------------------------------
    |
    | تم تعديل هذا الجزء لدعم تغيير اللغة واتجاه الصفحة تلقائيًا.
    |
    */

    'locale' => env('APP_LOCALE', 'ar'), // اللغة الافتراضية
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'), // اللغة الاحتياطية
    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    // تحديد اتجاه النص حسب اللغة الحالية
    'direction' => env('APP_LOCALE', 'ar') === 'ar' ? 'rtl' : 'ltr',

    /*
    |--------------------------------------------------------------------------
    | مفاتيح التشفير
    |--------------------------------------------------------------------------
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | وضع الصيانة
    |--------------------------------------------------------------------------
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
