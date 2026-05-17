<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminBackupTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    private function regularUser(): User
    {
        return User::factory()->create();
    }

    public function test_admin_can_generate_backup(): void
    {
        $admin = $this->adminUser();

        $this->mock(BackupService::class, function ($mock) {
            $tmpPath = tempnam(sys_get_temp_dir(), 'backup_test_') . '.sql';
            file_put_contents($tmpPath, '-- SQL test backup');

            $mock->shouldReceive('generate')
                ->once()
                ->andReturn([
                    'path'     => $tmpPath,
                    'filename' => 'backup_test.sql',
                    'method'   => 'php',
                ]);
        });

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/backup');

        $response->assertOk();
    }

    public function test_backup_is_registered_in_activity_log(): void
    {
        $admin = $this->adminUser();

        $this->mock(BackupService::class, function ($mock) {
            $tmpPath = tempnam(sys_get_temp_dir(), 'backup_test_') . '.sql';
            file_put_contents($tmpPath, '-- SQL test backup');

            $mock->shouldReceive('generate')
                ->once()
                ->andReturn([
                    'path'     => $tmpPath,
                    'filename' => 'backup_test.sql',
                    'method'   => 'php',
                ]);
        });

        $this->actingAs($admin)->postJson('/api/v1/admin/backup');

        $this->assertTrue(
            Activity::where('log_name', 'admin')
                ->where('event', 'backup.generated')
                ->where('causer_id', $admin->id)
                ->exists()
        );
    }

    public function test_regular_user_cannot_generate_backup(): void
    {
        $user = $this->regularUser();

        $this->actingAs($user)->postJson('/api/v1/admin/backup')->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_generate_backup(): void
    {
        $this->postJson('/api/v1/admin/backup')->assertUnauthorized();
    }
}