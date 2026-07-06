<?php

namespace Tests\Unit\Services;

use App\Models\OtRequest;
use App\Models\User;
use App\Services\OtRegisterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtRegisterServiceTest extends TestCase
{
    use RefreshDatabase;

    private OtRegisterService $service;
    private User $manager;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new OtRegisterService();
        $this->manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $this->employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
    }

    public function test_store_creates_ot_request_with_pending_status(): void
    {
        $otRequest = $this->service->store([
            'employee_id' => $this->employee->id,
            'ot_date' => '2026-03-20',
            'hours' => 2.5,
            'reason' => 'Release support',
        ]);

        $this->assertEquals(OtRequest::STATUS_PENDING, $otRequest->status);
        $this->assertDatabaseHas('ot_requests', [
            'id' => $otRequest->id,
            'status' => OtRequest::STATUS_PENDING,
        ]);
    }

    public function test_store_returns_created_ot_request_with_correct_attributes(): void
    {
        $otRequest = $this->service->store([
            'employee_id' => $this->employee->id,
            'ot_date' => '2026-03-21',
            'hours' => 4.0,
            'reason' => 'Quarter-end deployment',
        ]);

        $this->assertInstanceOf(OtRequest::class, $otRequest);
        $this->assertNotNull($otRequest->id);
        $this->assertEquals($this->employee->id, $otRequest->user_id);
        $this->assertEquals('2026-03-21', $otRequest->ot_date->toDateString());
        $this->assertEquals('4.0', $otRequest->hours);
        $this->assertEquals('Quarter-end deployment', $otRequest->reason);
        $this->assertEquals(OtRequest::STATUS_PENDING, $otRequest->status);
    }

    public function test_store_throws_exception_when_duplicate_ot_exists(): void
    {
        OtRequest::create([
            'user_id' => $this->employee->id,
            'ot_date' => '2026-03-22',
            'hours' => 2.0,
            'reason' => 'Existing OT',
            'status' => OtRequest::STATUS_PENDING,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Duplicate OT: user_id=' . $this->employee->id . ' ot_date=2026-03-22'
        );

        $this->service->store([
            'employee_id' => $this->employee->id,
            'ot_date' => '2026-03-22',
            'hours' => 3.0,
            'reason' => 'Duplicate attempt',
        ]);
    }

    public function test_store_different_employees_same_date_is_allowed(): void
    {
        $employeeTwo = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $first = $this->service->store([
            'employee_id' => $this->employee->id,
            'ot_date' => '2026-03-23',
            'hours' => 2.0,
            'reason' => 'First employee OT',
        ]);

        $second = $this->service->store([
            'employee_id' => $employeeTwo->id,
            'ot_date' => '2026-03-23',
            'hours' => 3.5,
            'reason' => 'Second employee OT',
        ]);

        $this->assertNotEquals($first->user_id, $second->user_id);
        $this->assertDatabaseCount('ot_requests', 2);
    }

    public function test_store_same_employee_different_dates_is_allowed(): void
    {
        $first = $this->service->store([
            'employee_id' => $this->employee->id,
            'ot_date' => '2026-03-24',
            'hours' => 2.0,
            'reason' => 'Sprint closeout',
        ]);

        $second = $this->service->store([
            'employee_id' => $this->employee->id,
            'ot_date' => '2026-03-25',
            'hours' => 1.5,
            'reason' => 'Production monitoring',
        ]);

        $this->assertEquals($this->employee->id, $first->user_id);
        $this->assertEquals($this->employee->id, $second->user_id);
        $this->assertDatabaseCount('ot_requests', 2);
    }
}
