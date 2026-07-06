<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('/_test-locale', function () {
            return response()->json([
                'locale' => app()->getLocale(),
            ]);
        });
    }

    public function test_locale_defaults_to_vi_when_cookie_is_missing(): void
    {
        app()->setLocale('ja');

        $this->get('/_test-locale')
            ->assertOk()
            ->assertJsonPath('locale', 'vi');
    }

    public function test_locale_uses_en_when_cookie_is_en(): void
    {
        $this->withCookie('locale', 'en')
            ->get('/_test-locale')
            ->assertOk()
            ->assertJsonPath('locale', 'en');
    }

    public function test_locale_falls_back_to_vi_when_cookie_is_invalid(): void
    {
        app()->setLocale('ja');

        $this->withCookie('locale', 'invalid')
            ->get('/_test-locale')
            ->assertOk()
            ->assertJsonPath('locale', 'vi');
    }

    public function test_set_locale_route_sets_cookie_and_redirects_back(): void
    {
        $this->from('/_test-locale')
            ->get(route('locale.switch', ['locale' => 'en']))
            ->assertRedirect('/_test-locale')
            ->assertCookie('locale', 'en');
    }

    public function test_set_locale_route_rejects_invalid_locale_and_does_not_set_cookie(): void
    {
        $this->from('/_test-locale')
            ->get(route('locale.switch', ['locale' => 'invalid']))
            ->assertStatus(422)
            ->assertCookieMissing('locale');
    }
}
