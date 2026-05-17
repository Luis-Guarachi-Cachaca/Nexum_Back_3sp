<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class VerifyEmailQueued extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    protected function verificationUrl($notifiable)
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        
        $verificationUrl = parent::verificationUrl($notifiable);
        
        $parsedUrl = parse_url($verificationUrl);
        parse_str($parsedUrl['query'] ?? '', $params);
        
        return $frontendUrl . '/auth/verify-email?' . http_build_query([
            'id' => $params['id'] ?? $notifiable->getKey(),
            'hash' => $params['hash'] ?? sha1($notifiable->getEmailForVerification()),
            'expires' => $params['expires'] ?? now()->addMinutes(60)->timestamp,
            'signature' => $params['signature'] ?? '',
        ]);
    }
}