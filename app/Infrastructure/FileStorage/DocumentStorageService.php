<?php

namespace App\Infrastructure\FileStorage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Mime\MimeTypes;

/**
 * Stores uploaded knowledge-base documents on the configured disk and exposes
 * path/existence helpers used by the document pipeline. This `DISK` constant is
 * the single source of truth for where documents live — the controller, seeder
 * and text extractor all resolve the disk through this service.
 */
class DocumentStorageService
{
    private const DISK = 'public';

    private const DIRECTORY = 'documents';

    /**
     * Persist an uploaded file and return its metadata.
     *
     * Real uploads pass no name and get a collision-proof UUID filename.
     * Predefined/static documents (the knowledge-base seeder) pass a stable
     * $name so the stored path stays identical across re-seeds and the DB path
     * never drifts from what is on disk.
     *
     * @return array{path: string, file_type: string, file_size: int, original_filename: string}
     */
    public function store(UploadedFile $file, ?string $name = null): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $filename = $name !== null
            ? Str::slug(pathinfo($name, PATHINFO_FILENAME)).'.'.$extension
            : Str::uuid()->toString().'.'.$extension;
        $path = $file->storeAs(self::DIRECTORY, $filename, self::DISK);

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

    /**
     * Every stored document file (relative paths on the configured disk).
     *
     * @return array<int, string>
     */
    public function files(): array
    {
        return Storage::disk(self::DISK)->files(self::DIRECTORY);
    }

    public function absolutePath(string $path): string
    {
        return Storage::disk(self::DISK)->path($path);
    }

    public function delete(string $path): void
    {
        Storage::disk(self::DISK)->delete($path);
    }

    /**
     * Resolve a MIME type from a file's extension using Symfony's static map.
     *
     * This deliberately avoids Storage's mimeType()/finfo, which requires the
     * PHP `fileinfo` extension — often disabled on shared hosting. Passing the
     * result as an explicit Content-Type header keeps downloads/previews working
     * in production regardless of whether fileinfo is installed.
     */
    public static function contentType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return MimeTypes::getDefault()->getMimeTypes($extension)[0]
            ?? 'application/octet-stream';
    }
}
