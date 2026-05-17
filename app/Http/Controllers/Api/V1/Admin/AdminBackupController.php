<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminBackupController extends Controller
{
    public function __construct(private readonly BackupService $backupService) {}

    public function generate(Request $request): BinaryFileResponse
    {
        $backup = $this->backupService->generate();

        // Registrar en activity_log usando el mismo patrón que el resto del proyecto
        activity('admin')
            ->causedBy($request->user())
            ->withProperties([
                'filename' => $backup['filename'],
                'method'   => $backup['method'], // 'pg_dump' o 'php'
                'ip'       => $request->ip(),
            ])
            ->event('backup.generated')
            ->log('Admin generated a database backup.');

        return response()
            ->download($backup['path'], $backup['filename'])
            ->deleteFileAfterSend(true);
    }
}