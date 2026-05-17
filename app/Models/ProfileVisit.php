<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileVisit extends Model
{
    protected $fillable = [
        'portfolio_id',
        'user_id',
        'ip_address',
        'visited_at',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
    ];

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope para obtener visitantes recientes (últimos 5)
     */
    public function scopeRecent($query, int $limit = 5)
    {
        return $query->with('user')
            ->orderByDesc('visited_at')
            ->limit($limit);
    }

    /**
     * Scope para excluir admins
     */
    public function scopeExcludeAdmins($query)
    {
        return $query->whereHas('user', function ($q) {
            $q->whereDoesntHave('roles', function ($roleQuery) {
                $roleQuery->where('name', 'admin');
            });
        })->orWhereNull('user_id'); // Incluir visitantes anónimos
    }

    /**
     * Scope para excluir al dueño del perfil
     */
    public function scopeExcludeOwner($query, int $portfolioId)
    {
        return $query->where(function ($q) use ($portfolioId) {
            $q->whereNull('user_id')
              ->orWhereHas('user', function ($userQuery) use ($portfolioId) {
                  $userQuery->where('id', '!=', function ($subQuery) use ($portfolioId) {
                      $subQuery->select('user_id')
                               ->from('portfolios')
                               ->where('id', $portfolioId);
                  });
              });
        });
    }
}
