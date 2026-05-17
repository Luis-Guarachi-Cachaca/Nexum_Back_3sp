<?php
 
namespace Tests\Feature;
 
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
 
class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;
 
    // -------------------------------------------------------------------------
    // Helpers — mismo patrón que AdminSkillTest
    // -------------------------------------------------------------------------
 
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
 
    // -------------------------------------------------------------------------
    // GET /api/v1/admin/dashboard
    // -------------------------------------------------------------------------
 
    public function test_admin_can_access_dashboard(): void
    {
        $admin = $this->adminUser();
 
        $response = $this->actingAs($admin)->getJson('/api/v1/admin/dashboard');
 
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'users'      => ['total', 'active', 'inactive'],
                    'portfolios',
                    'activity'   => ['total_actions', 'recent'],
                ],
            ]);
    }
 
    public function test_dashboard_returns_correct_user_counts(): void
    {
        $admin = $this->adminUser();
 
        // 2 usuarios activos adicionales al admin
        User::factory()->count(2)->create(['is_active' => true]);
        // 1 usuario inactivo
        User::factory()->create(['is_active' => false]);
 
        $response = $this->actingAs($admin)->getJson('/api/v1/admin/dashboard');
 
        $response->assertOk()
            // total: admin + 2 activos + 1 inactivo = 4
            ->assertJsonPath('data.users.total', 4)
            ->assertJsonPath('data.users.active', 3)
            ->assertJsonPath('data.users.inactive', 1);
    }
 
    public function test_dashboard_returns_correct_portfolio_count(): void
    {
        $admin = $this->adminUser();
        Portfolio::factory()->count(3)->create();
 
        $response = $this->actingAs($admin)->getJson('/api/v1/admin/dashboard');
 
        $response->assertOk()
            ->assertJsonPath('data.portfolios', 3);
    }
 
    public function test_regular_user_cannot_access_dashboard(): void
    {
        $user = $this->regularUser();
 
        $this->actingAs($user)->getJson('/api/v1/admin/dashboard')->assertForbidden();
    }
 
    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $this->getJson('/api/v1/admin/dashboard')->assertUnauthorized();
    }
}
