<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProfileService $service;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ProfileService();
        $this->employee = User::factory()->create([
            'role' => User::ROLE_EMPLOYEE,
            'employee_code' => fake()->unique()->numerify('EMP#####'),
            'password' => Hash::make('old-password'),
        ]);
    }

    public function test_change_password_updates_user_password_hash(): void
    {
        $originalHash = $this->employee->password;

        $this->service->changePassword($this->employee, 'new-password');

        $this->employee->refresh();

        $this->assertNotSame($originalHash, $this->employee->password);
    }

    public function test_change_password_stores_bcrypt_hash_not_plain_text(): void
    {
        $this->service->changePassword($this->employee, 'new-password');

        $this->employee->refresh();

        $this->assertNotSame('new-password', $this->employee->password);
        $this->assertStringStartsWith('$2y$', $this->employee->password);
    }

    public function test_new_password_hash_verifies_correctly(): void
    {
        $this->service->changePassword($this->employee, 'new-password');

        $this->employee->refresh();

        $this->assertTrue(Hash::check('new-password', $this->employee->password));
    }

    public function test_old_password_no_longer_matches_after_change(): void
    {
        $this->service->changePassword($this->employee, 'new-password');

        $this->employee->refresh();

        $this->assertFalse(Hash::check('old-password', $this->employee->password));
    }
}
