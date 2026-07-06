<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\EmployeeDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_composer_sets_is_approver_true_for_authenticated_approver(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $fakeService = new class extends EmployeeDashboardService {
            public ?int $calledWith = null;

            public function isApprover(int $userId): bool
            {
                $this->calledWith = $userId;

                return true;
            }
        };

        $this->app->instance(EmployeeDashboardService::class, $fakeService);

        $this->actingAs($user);
        $view = view('layouts.employee');
        $html = $view->render();

        $this->assertTrue($view->getData()['isApprover']);
        $this->assertStringContainsString(route('employee.team-approvals.index'), $html);
        $this->assertStringContainsString('Duyệt OT/Leave', $html);

        $this->assertSame($user->id, $fakeService->calledWith);
    }

    public function test_view_composer_sets_is_approver_false_for_authenticated_non_approver(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $fakeService = new class extends EmployeeDashboardService {
            public ?int $calledWith = null;

            public function isApprover(int $userId): bool
            {
                $this->calledWith = $userId;

                return false;
            }
        };

        $this->app->instance(EmployeeDashboardService::class, $fakeService);

        $this->actingAs($user);
        $view = view('layouts.employee');
        $html = $view->render();

        $this->assertFalse($view->getData()['isApprover']);
        $this->assertStringNotContainsString('Duyệt OT/Leave', $html);

        $this->assertSame($user->id, $fakeService->calledWith);
    }

    public function test_view_composer_sets_is_approver_false_for_guests(): void
    {
        auth()->logout();
        $view = view('layouts.employee');
        $html = $view->render();

        $this->assertFalse($view->getData()['isApprover']);
        $this->assertStringNotContainsString('Duyệt OT/Leave', $html);
    }

    public function test_employee_layout_renders_locale_switcher_and_html_lang(): void
    {
        app()->setLocale('en');
        $html = view('layouts.employee', ['isApprover' => false])->render();

        $this->assertStringContainsString('<html lang="en">', $html);
        $this->assertStringContainsString(route('locale.switch', ['locale' => 'vi']), $html);
        $this->assertStringContainsString(route('locale.switch', ['locale' => 'en']), $html);
        $this->assertStringContainsString(route('locale.switch', ['locale' => 'ja']), $html);
    }
}
