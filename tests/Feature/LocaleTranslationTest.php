<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocaleTranslationTest extends TestCase
{
    // ── VI (default) ──────────────────────────────────────────────────────────

    public function test_vi_nav_logout_returns_vietnamese(): void
    {
        app()->setLocale('vi');
        $this->assertSame('Đăng xuất', __('ui.nav.logout'));
    }

    public function test_vi_status_pending_returns_vietnamese(): void
    {
        app()->setLocale('vi');
        $this->assertSame('Chờ duyệt', __('ui.status.pending'));
    }

    public function test_vi_status_approved_returns_vietnamese(): void
    {
        app()->setLocale('vi');
        $this->assertSame('Đã duyệt', __('ui.status.approved'));
    }

    public function test_vi_status_rejected_returns_vietnamese(): void
    {
        app()->setLocale('vi');
        $this->assertSame('Từ chối', __('ui.status.rejected'));
    }

    public function test_vi_auth_login_returns_vietnamese(): void
    {
        app()->setLocale('vi');
        $this->assertSame('Đăng nhập', __('ui.auth.login'));
    }

    public function test_vi_dashboard_welcome_returns_vietnamese(): void
    {
        app()->setLocale('vi');
        $this->assertSame('Xin chào,', __('ui.dashboard.welcome'));
    }

    public function test_vi_nav_admin_dashboard_returns_vietnamese(): void
    {
        app()->setLocale('vi');
        $this->assertSame('Dashboard Tổng hợp', __('ui.nav.admin_dashboard'));
    }

    // ── EN ───────────────────────────────────────────────────────────────────

    public function test_en_nav_logout_returns_english(): void
    {
        app()->setLocale('en');
        $this->assertSame('Logout', __('ui.nav.logout'));
    }

    public function test_en_status_pending_returns_english(): void
    {
        app()->setLocale('en');
        $this->assertSame('Pending', __('ui.status.pending'));
    }

    public function test_en_status_approved_returns_english(): void
    {
        app()->setLocale('en');
        $this->assertSame('Approved', __('ui.status.approved'));
    }

    public function test_en_status_rejected_returns_english(): void
    {
        app()->setLocale('en');
        $this->assertSame('Rejected', __('ui.status.rejected'));
    }

    public function test_en_auth_login_returns_english(): void
    {
        app()->setLocale('en');
        $this->assertSame('Login', __('ui.auth.login'));
    }

    public function test_en_auth_employee_id_returns_english(): void
    {
        app()->setLocale('en');
        $this->assertSame('Employee ID', __('ui.auth.employee_id'));
    }

    public function test_en_dashboard_welcome_returns_english(): void
    {
        app()->setLocale('en');
        $this->assertSame('Hello,', __('ui.dashboard.welcome'));
    }

    public function test_en_nav_employees_returns_english(): void
    {
        app()->setLocale('en');
        $this->assertSame('Manage Employees', __('ui.nav.employees'));
    }

    public function test_en_team_approval_title_returns_english(): void
    {
        app()->setLocale('en');
        $this->assertSame('Team Approval', __('ui.team_approval.title'));
    }

    public function test_en_table_employee_returns_english(): void
    {
        app()->setLocale('en');
        $this->assertSame('Employee', __('ui.table.employee'));
    }

    public function test_en_flash_save_success_returns_english(): void
    {
        app()->setLocale('en');
        $this->assertSame('Saved successfully.', __('ui.flash.save_success'));
    }

    // ── JA ───────────────────────────────────────────────────────────────────

    public function test_ja_nav_logout_returns_japanese(): void
    {
        app()->setLocale('ja');
        $this->assertSame('ログアウト', __('ui.nav.logout'));
    }

    public function test_ja_status_pending_returns_japanese(): void
    {
        app()->setLocale('ja');
        $this->assertSame('承認待ち', __('ui.status.pending'));
    }

    public function test_ja_status_approved_returns_japanese(): void
    {
        app()->setLocale('ja');
        $this->assertSame('承認済み', __('ui.status.approved'));
    }

    public function test_ja_auth_login_returns_japanese(): void
    {
        app()->setLocale('ja');
        $this->assertSame('ログイン', __('ui.auth.login'));
    }

    public function test_ja_auth_employee_id_returns_japanese(): void
    {
        app()->setLocale('ja');
        $this->assertSame('社員番号', __('ui.auth.employee_id'));
    }

    public function test_ja_dashboard_welcome_returns_japanese(): void
    {
        app()->setLocale('ja');
        $this->assertSame('こんにちは、', __('ui.dashboard.welcome'));
    }

    public function test_ja_nav_employees_returns_japanese(): void
    {
        app()->setLocale('ja');
        $this->assertSame('社員管理', __('ui.nav.employees'));
    }

    public function test_ja_team_approval_no_ot_returns_japanese(): void
    {
        app()->setLocale('ja');
        $this->assertSame('承認待ちの残業申請はありません。', __('ui.team_approval.no_ot'));
    }

    public function test_ja_table_hours_returns_japanese(): void
    {
        app()->setLocale('ja');
        $this->assertSame('時間数', __('ui.table.hours'));
    }

    public function test_ja_flash_save_success_returns_japanese(): void
    {
        app()->setLocale('ja');
        $this->assertSame('保存しました。', __('ui.flash.save_success'));
    }

    // ── Fallback ─────────────────────────────────────────────────────────────

    public function test_missing_key_returns_key_string_as_fallback(): void
    {
        app()->setLocale('en');
        $this->assertSame('ui.nonexistent.key', __('ui.nonexistent.key'));
    }

    // ── Locale switch via cookie changes translation output ───────────────────

    public function test_locale_cookie_en_affects_translation_via_middleware(): void
    {
        \Illuminate\Support\Facades\Route::middleware('web')->get('/_test-trans', function () {
            return response()->json(['logout' => __('ui.nav.logout')]);
        });

        $this->withCookie('locale', 'en')
            ->get('/_test-trans')
            ->assertOk()
            ->assertJsonPath('logout', 'Logout');
    }

    public function test_locale_cookie_ja_affects_translation_via_middleware(): void
    {
        \Illuminate\Support\Facades\Route::middleware('web')->get('/_test-trans-ja', function () {
            return response()->json(['logout' => __('ui.nav.logout')]);
        });

        $this->withCookie('locale', 'ja')
            ->get('/_test-trans-ja')
            ->assertOk()
            ->assertJsonPath('logout', 'ログアウト');
    }
}
