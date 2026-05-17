<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Portfolio extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'profession',
        'biography',
        'phone',
        'location',
        'avatar_path',
        'linkedin_url',
        'github_url',
        'design_pattern',
        'global_privacy',
        'show_projects',
        'show_skills',
        'show_experience',
        'show_certifications',
        'views_count',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('portfolio')
            ->logOnly(['profession', 'biography', 'phone', 'location', 'global_privacy', 'design_pattern', 'linkedin_url', 'github_url', 'avatar_path', 'show_projects', 'show_skills', 'show_experience', 'show_certifications'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(Certification::class)->active()->orderByDesc('issue_date');
    }

    public function skills(): HasMany
    {
        return $this->hasMany(PortfolioSkill::class)->active();
    }

    public function visits(): HasMany
    {
        return $this->hasMany(ProfileVisit::class);
    }

    /**
     * Relación con los enlaces adicionales
     */
    public function additionalLinks(): HasMany
    {
        return $this->hasMany(PortfolioLink::class);
    }

    protected function casts(): array
    {
        return [
            'show_projects' => 'boolean',
            'show_skills' => 'boolean',
            'show_experience' => 'boolean',
            'show_certifications' => 'boolean',
        ];
    }

    public function isSectionVisible(string $section): bool
    {
        // Si el perfil es completamente privado, nada es visible
        if ($this->global_privacy === 'private') {
        return false;
        }

        // Si el perfil es público, verificar configuración específica de la sección
        return match ($section) {
            'projects'       => (bool) ($this->show_projects ?? true),
            'skills'         => (bool) ($this->show_skills ?? true),
            'experience'     => (bool) ($this->show_experience ?? true),
            'certifications' => (bool) ($this->show_certifications ?? true),
            default          => false,
        };
    }
}
