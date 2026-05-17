<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioLink extends Model
{
    protected $fillable = [
        'portfolio_id',
        'url',
        'platform',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con el portfolio
     */
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    /**
     * Detectar automáticamente la plataforma de un URL
     */
    public static function detectPlatform(string $url): string
    {
        $platforms = [
            'github'       => 'github.com',
            'gitlab'       => 'gitlab.com',
            'bitbucket'    => 'bitbucket.org',
            'kaggle'       => 'kaggle.com',
            'huggingface'  => 'huggingface.co',
            'behance'      => 'behance.net',
            'dribbble'     => 'dribbble.com',
            'figma'        => 'figma.com',
            'linkedin'     => 'linkedin.com',
            'devto'        => 'dev.to',
            'medium'       => 'medium.com',
            'vercel'       => 'vercel.app',
            'vercel'       => 'vercel.com',
            'netlify'      => 'netlify.app',
            'heroku'       => 'heroku.com',
        ];

        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? '';

        foreach ($platforms as $platform => $domain) {
            if (str_contains($host, $domain)) {
                return $platform;
            }
        }

        return 'website';
    }
}
