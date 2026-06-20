<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;

/**
 * Unified document shape consumed by the admin + student Vue pages.
 *
 * @mixin \App\Models\Document
 */
class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isAdmin = $user && $user->isAdmin();
        $downloadRoute = $isAdmin ? 'admin.documents.download' : 'documents.download';
        $previewRoute = $isAdmin ? 'admin.documents.preview' : 'documents.preview';

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            // Many-to-many departments. `department` is a human-readable summary
            // ("All Departments" when none are targeted) for list/detail views;
            // `departments`/`departmentIds` drive multi-select editing + filters.
            'department' => $this->whenLoaded('departments', fn () => $this->departmentSummary()),
            'departments' => $this->whenLoaded(
                'departments',
                fn () => $this->departments->map(fn ($d) => ['id' => $d->id, 'name' => $d->name])->values()
            ),
            'departmentIds' => $this->whenLoaded('departments', fn () => $this->departments->pluck('id')->values()),
            'type' => $this->file_type,
            'fileSize' => $this->humanFileSize(),
            'fileSizeBytes' => $this->file_size,
            'pages' => $this->pages,
            'version' => $this->version,
            'status' => $this->status->value,
            'visibility' => $this->visibility,
            'tags' => $this->tags ?? [],
            'downloads' => $this->downloads,
            'views' => $this->views,
            'isBookmarked' => (bool) ($this->is_bookmarked ?? false),
            'uploadedBy' => $this->whenLoaded('uploader', fn () => $this->uploader?->name),
            'uploadedAt' => $this->created_at?->toDateString(),
            'approvedAt' => $this->approved_at?->toDateString(),
            'rejectionReason' => $this->rejection_reason,
            'downloadUrl' => Route::has($downloadRoute) ? route($downloadRoute, $this->id) : null,
            'previewUrl' => Route::has($previewRoute) ? route($previewRoute, $this->id) : null,
        ];
    }

    private function departmentSummary(): string
    {
        $names = $this->departments->pluck('name')->filter()->values();

        return $names->isEmpty() ? 'All Departments' : $names->join(', ');
    }

    private function humanFileSize(): string
    {
        $bytes = (int) $this->file_size;
        if ($bytes <= 0) {
            return '—';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $power), 1).' '.$units[$power];
    }
}
