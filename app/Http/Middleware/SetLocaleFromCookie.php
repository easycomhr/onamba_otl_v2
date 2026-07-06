<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromCookie
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = Cookie::get('locale');
        $supportedLocales = ['vi', 'en', 'ja'];

        if (! in_array($locale, $supportedLocales, true)) {
            $locale = 'vi';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
