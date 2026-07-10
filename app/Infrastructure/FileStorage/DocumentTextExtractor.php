<?php

namespace App\Infrastructure\FileStorage;

use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;
use ZipArchive;

/**
 * Extracts plain text (page-aware where possible) from uploaded documents.
 * Supports PDF (smalot/pdfparser), DOCX (OOXML), and plain text/markdown.
 */
class DocumentTextExtractor
{
    public function __construct(private readonly DocumentStorageService $storage) {}

    /**
     * @return array<int, array{page: int, text: string}>
     */
    public function extract(string $path, string $fileType): array
    {
        if (! $this->storage->exists($path)) {
            return [];
        }

        return $this->fromAbsolute($this->storage->absolutePath($path), $fileType);
    }

    /**
     * Extract from a file on an arbitrary disk — course-material uploads live
     * on `local`, not the knowledge-base disk this service defaults to.
     *
     * @return array<int, array{page: int, text: string}>
     */
    public function extractFromDisk(string $disk, string $path, string $fileType): array
    {
        if (! Storage::disk($disk)->exists($path)) {
            return [];
        }

        return $this->fromAbsolute(Storage::disk($disk)->path($path), $fileType);
    }

    /**
     * @return array<int, array{page: int, text: string}>
     */
    private function fromAbsolute(string $absolute, string $fileType): array
    {
        return match (strtolower($fileType)) {
            'pdf' => $this->fromPdf($absolute),
            'docx' => $this->fromDocx($absolute),
            default => $this->fromPlainText($absolute),
        };
    }

    /**
     * @return array<int, array{page: int, text: string}>
     */
    private function fromPdf(string $absolute): array
    {
        try {
            $pdf = (new PdfParser)->parseFile($absolute);
            $pages = $pdf->getPages();

            if (empty($pages)) {
                return $this->wrap($pdf->getText());
            }

            $result = [];
            foreach ($pages as $i => $page) {
                $text = trim($page->getText());
                if ($text !== '') {
                    $result[] = ['page' => $i + 1, 'text' => $text];
                }
            }

            return $result ?: $this->wrap($pdf->getText());
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<int, array{page: int, text: string}>
     */
    private function fromDocx(string $absolute): array
    {
        try {
            $zip = new ZipArchive;
            if ($zip->open($absolute) !== true) {
                return [];
            }

            $xml = $zip->getFromName('word/document.xml');
            $zip->close();

            if ($xml === false) {
                return [];
            }

            // Paragraph breaks → newlines, then strip tags.
            $xml = str_replace(['</w:p>', '<w:br/>'], "\n", $xml);
            $text = trim(html_entity_decode(strip_tags($xml)));

            return $this->wrap($text);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<int, array{page: int, text: string}>
     */
    private function fromPlainText(string $absolute): array
    {
        $text = @file_get_contents($absolute);

        return $text === false ? [] : $this->wrap($text);
    }

    /**
     * @return array<int, array{page: int, text: string}>
     */
    private function wrap(string $text): array
    {
        $text = trim($text);

        return $text === '' ? [] : [['page' => 1, 'text' => $text]];
    }
}
