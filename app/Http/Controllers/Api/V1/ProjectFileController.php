<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectFileResource;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Notifications\StorageAlmostFull;
use App\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectFileController extends Controller
{
    private const MAX_FILES         = 10;
    private const MAX_IMAGE_BYTES   = 2 * 1024 * 1024;   // 2 MB
    private const MAX_PDF_BYTES     = 16 * 1024 * 1024;  // 16 MB
    private const MAX_STORAGE_BYTES = 700 * 1024 * 1024; // 700 MB

    public function __construct(private readonly CloudinaryService $cloudinary) {}

    /**
     * Lista los archivos de un proyecto.
     */
    public function index(Request $request, Project $project): AnonymousResourceCollection|JsonResponse
    {
        if (! $this->ownsProject($request, $project)) {
            return response()->json(['message' => 'Project not found.'], 404);
        }

        return ProjectFileResource::collection($project->files);
    }

    /**
     * Sube uno o más archivos (imágenes o PDFs) al proyecto.
     * Acepta multipart con campo "files[]".
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        if (! $this->ownsProject($request, $project)) {
            return response()->json(['message' => 'Project not found.'], 404);
        }

        $request->validate([
            'files'   => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:16384'],
        ]);

        $currentCount = $project->files()->count();
        $incoming     = count($request->file('files', []));

        if ($currentCount + $incoming > self::MAX_FILES) {
            return response()->json([
                'message' => 'A project can have at most ' . self::MAX_FILES . ' files. '
                    . "Currently has {$currentCount}.",
            ], 422);
        }

        // Verify per-file size limits before uploading anything
        foreach ($request->file('files') as $index => $file) {
            $isPdf = strtolower($file->getClientOriginalExtension()) === 'pdf';
            $limit = $isPdf ? self::MAX_PDF_BYTES : self::MAX_IMAGE_BYTES;
            $label = $isPdf ? '16 MB' : '2 MB';

            if ($file->getSize() > $limit) {
                return response()->json([
                    'message' => "File \"{$file->getClientOriginalName()}\" exceeds the {$label} limit for " . ($isPdf ? 'PDFs' : 'images') . '.',
                ], 422);
            }
        }

        // Verify 700 MB total storage limit for this user's project files
        $usedBytes    = $this->userStorageUsed($request->user()->id);
        $incomingSize = collect($request->file('files'))->sum(fn ($f) => $f->getSize());

        if ($usedBytes + $incomingSize > self::MAX_STORAGE_BYTES) {
            $remainingMB = round((self::MAX_STORAGE_BYTES - $usedBytes) / 1024 / 1024, 1);
            return response()->json([
                'message' => "Storage limit reached. You have {$remainingMB} MB remaining.",
            ], 422);
        }

        $created = [];

        foreach ($request->file('files') as $file) {
            $isPdf = strtolower($file->getClientOriginalExtension()) === 'pdf';

            $uploaded = $isPdf
                ? $this->cloudinary->uploadProjectPdf($file)
                : $this->cloudinary->uploadProjectImage($file);

            $created[] = $project->files()->create([
                'type'                 => $isPdf ? 'pdf' : 'image',
                'url'                  => $uploaded['url'],
                'cloudinary_public_id' => $uploaded['public_id'],
                'original_name'        => $file->getClientOriginalName(),
                'order'                => $currentCount + count($created),
                'size'                 => $uploaded['size'],
            ]);
        }

        // Check if storage is almost full (90% of 700 MB) and notify user
        $usedBytes = $this->userStorageUsed($request->user()->id);
        $storageThreshold = self::MAX_STORAGE_BYTES * 0.9; // 90% of 700 MB

        if ($usedBytes >= $storageThreshold) {
            $request->user()->notify(new StorageAlmostFull($usedBytes, self::MAX_STORAGE_BYTES));
        }

        return response()->json([
            'data' => ProjectFileResource::collection(collect($created)),
        ], 201);
    }

    /**
     * Elimina un archivo del proyecto y lo borra de Cloudinary.
     */
    public function destroy(Request $request, Project $project, ProjectFile $file): JsonResponse
    {
        if (! $this->ownsProject($request, $project) || $file->project_id !== $project->id) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        $this->cloudinary->deleteProjectFile($file->cloudinary_public_id, $file->type);
        $file->delete();

        return response()->json(['message' => 'File deleted successfully.']);
    }

    private function ownsProject(Request $request, Project $project): bool
    {
        $portfolio = $request->user()->portfolio;

        return $portfolio && $project->portfolio_id === $portfolio->id;
    }

    private function userStorageUsed(int $userId): int
    {
        return (int) \App\Models\ProjectFile::whereHas('project.portfolio', fn ($q) => $q->where('user_id', $userId))
            ->sum('size');
    }
}
