<?php

declare(strict_types=1);

namespace App\Domain\Chat\Services;

use App\Domain\Chat\Contracts\AIProviderInterface;
use Illuminate\Http\UploadedFile;

/**
 * Transcribes an uploaded image (e.g. a photo of handwritten notes) to text
 * using the active vision-capable AI provider. The image is read in-request and
 * not persisted — only the transcribed text is returned to the caller.
 */
class OcrService
{
    public function __construct(private readonly AIProviderInterface $provider) {}

    public function extractFromUpload(UploadedFile $image): string
    {
        return trim($this->provider->extractText(
            imagePath: $image->getRealPath(),
            mimeType: $image->getMimeType() ?: 'image/png',
        ));
    }
}
