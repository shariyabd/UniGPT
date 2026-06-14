<?php

namespace App\Infrastructure\FileStorage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores uploaded knowledge-base documents on the configured (private) disk
 * and exposes path/existence helpers used by the document pipeline.
 */
class DocumentStorageService
{
    private const DISK = 'local';

    private const DIRECTORY = 'documents';

    /**
     * Persist an uploaded file and return its metadata.
     *
     * @return array{path: string, file_type: string, file_size: int, original_filename: string}
     */
    public function store(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $name = Str::uuid()->toString().'.'.$extension;
        $path = $file->storeAs(self::DIRECTORY, $name, self::DISK);

        return [
            'path' => $path,
            'file_type' => $extension,
            'file_size' => $file->getSize() ?: 0,
            'original_filename' => $file->getClientOriginalName(),
        ];
    }

    public function disk(): string
    {
        return self::DISK;
    }

    public function exists(string $path): bool
    {
        return Storage::disk(self::DISK)->exists($path);
    }

    public function absolutePath(string $path): string
    {
        return Storage::disk(self::DISK)->path($path);
    }

    public function delete(string $path): void
    {
        Storage::disk(self::DISK)->delete($path);
    }
}
