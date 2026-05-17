<?php

namespace Tests\Feature\Portfolio;

use App\Enums\PortfolioTheme;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioThemeTest extends TestCase
{
    use RefreshDatabase;

    private User $professional;
    private Portfolio $portfolio;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear los roles necesarios (RefreshDatabase los elimina)
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'professional', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->professional = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->professional->assignRole('professional');

        $this->portfolio = Portfolio::factory()->create([
            'user_id'        => $this->professional->id,
            'design_pattern' => 'light_mode',
            'global_privacy' => 'public',
        ]);
    }

    /** @test */
    public function lista_los_temas_disponibles(): void
    {
        $this->getJson('/api/v1/themes')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description', 'palette'],
                ],
            ]);
    }

    /** @test */
    public function get_themes_es_publico_sin_autenticacion(): void
    {
        $this->getJson('/api/v1/themes')->assertOk();
    }

    /** @test */
    public function profesional_puede_actualizar_su_tema(): void
    {
        $this->actingAs($this->professional)
            ->patchJson('/api/v1/portfolio/theme', [
                'design_pattern' => 'dark_mode',
            ])
            ->assertOk()
            ->assertJsonPath('data.design_pattern', 'dark_mode');

        $this->assertDatabaseHas('portfolios', [
            'id'             => $this->portfolio->id,
            'design_pattern' => 'dark_mode',
        ]);
    }

    /** @test */
    public function tema_invalido_devuelve_422(): void
    {
        $this->actingAs($this->professional)
            ->patchJson('/api/v1/portfolio/theme', [
                'design_pattern' => 'tema_inexistente',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['design_pattern']);
    }

    /** @test */
    public function design_pattern_vacio_devuelve_422(): void
    {
        $this->actingAs($this->professional)
            ->patchJson('/api/v1/portfolio/theme', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['design_pattern']);
    }

    /** @test */
    public function sin_autenticacion_devuelve_401(): void
    {
        $this->patchJson('/api/v1/portfolio/theme', [
            'design_pattern' => 'dark_mode',
        ])->assertUnauthorized();
    }

    /**
     * @test
     * CRITERIO DE ACEPTACIÓN HU3:
     * Verificar que el tema seleccionado se refleja en la vista pública.
     */
    public function tema_seleccionado_se_refleja_en_vista_publica(): void
    {
        $this->actingAs($this->professional)
            ->patchJson('/api/v1/portfolio/theme', [
                'design_pattern' => 'dark_mode',
            ])
            ->assertOk();

        $this->getJson("/api/v1/portfolios/{$this->portfolio->id}")
            ->assertOk()
            ->assertJsonPath('design_pattern', 'dark_mode');
    }
}