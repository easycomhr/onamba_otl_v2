<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;

class LocaleController extends Controller
{
    public function __call(string $method, array $parameters): RedirectResponse
    {
        if ($method === 'switch') {
            return $this->switchLocale(...$parameters);
        }

        abort(404);
    }

    public function switchLocale(string $locale): RedirectResponse
    {
        $supportedLocales = config('app.supported_locales', ['vi', 'en', 'ja']);

        abort_unless(in_array($locale, $supportedLocales, true), 422);

        Cookie::queue('locale', $locale, 60 * 24 * 365);

        return redirect()->back();
    }
}
