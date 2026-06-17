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
            'department' => $this->whenLoaded('department', fn () => $this->department?->name),
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
            'uploadedBy' => $this->whenLoaded('uploader', fn () => $this->uploader?->name),
            'uploadedAt' => $this->created_at?->toDateString(),
            'approvedAt' => $this->approved_at?->toDateString(),
            'rejectionReason' => $this->rejection_reason,
            'downloadUrl' => Route::has($downloadRoute) ? route($downloadRoute, $this->id) : null,
            'previewUrl' => Route::has($previewRoute) ? route($previewRoute, $this->id) : null,
        ];
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
