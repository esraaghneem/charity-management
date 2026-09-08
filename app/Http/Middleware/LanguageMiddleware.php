<?php
/*
namespace App\Http\Middleware;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Closure;

class LanguageMiddleware {
    public function handle($request, Closure $next) {
        // ✅ جلب اللغة من الجلسة أو تعيينها إلى اللغة الافتراضية
        $locale = Session::get('locale', config('app.locale'));
        App::setLocale($locale);

        return $next($request);
    }
}
*/


namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;

class LanguageMiddleware {
    public function handle($request, Closure $next) {
        // ✅ جلب اللغة من Header أو تعيين اللغة الافتراضية
        $locale = $request->header('Accept-Language', config('app.locale'));

        if (in_array($locale, ['en', 'ar'])) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
