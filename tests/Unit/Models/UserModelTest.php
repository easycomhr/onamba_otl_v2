<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_relation_is_belongs_to(): void
    {
        $user = new User();

        $this->assertInstanceOf(BelongsTo::class, $user->manager());
    }

    public function test_manager_returns_null_when_manager_id_is_null(): void
    {
        $employee = User::factory()->create(['manager_id' => null]);

        $this->assertNull($employee->manager);
    }

    public function test_manager_returns_manager_user_when_manager_id_is_set(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $employee = User::factory()->create(['manager_id' => $manager->id]);

        $this->assertInstanceOf(User::class, $employee->manager);
        $this->assertEquals($manager->id, $employee->manager->id);
    }
}
