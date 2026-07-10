<?php

declare(strict_types=1);

namespace App\Domain\RAG\Ingestion;

use App\Domain\Academic\Services\CourseManagementService;
use App\Domain\Chat\Document\Chunking\ChunkingService;
use App\Domain\RAG\Embeddings\EmbeddingService;
use App\Enums\DocumentStatus;
use App\Infrastructure\FileStorage\DocumentTextExtractor;
use App\Models\CourseMaterial;
use App\Models\Document;
use App\Models\Note;
use Illuminate\Support\Facades\Storage;

/**
 * Ingests personal content into the RAG index as "shadow" documents so student
 * notes and course-material files answer questions through the exact same
 * chunk → embed → retrieve → cite pipeline as the knowledge-base library.
 *
 * Shadow documents are hidden from every library/admin surface by the
 * {@see Document::LIBRARY_SCOPE} global scope; only retrieval (scoped to the
 * owner / enrolled sections) ever sees them.
 */
class PersonalCorpusService
{
    /**
     * Disk course-material uploads are stored on — must match
     * {@see CourseManagementService::storeFile()}.
     */
    private const MATERIAL_DISK = 'local';

    /**
     * Material file types with extractable text. Anything else (video, images,
     * archives) is skipped rather than indexed as binary noise.
     */
    private const EXTRACTABLE_TYPES = ['pdf', 'docx', 'txt', 'md', 'markdown', 'text'];

    public function __construct(
        private readonly ChunkingService $chunker,
        private readonly EmbeddingService $embeddings,
        private readonly DocumentTextExtractor $extractor,
    ) {}

    /**
     * Index (or re-index) a note. Returns the number of embeddings written;
     * 0 when the note is empty (its shadow is dropped) or already up to date.
     */
    public function syncNote(Note $note): int
    {
        $body = trim((string) $note->content);
        if ($body === '') {
            $this->forgetNote($note->id);

            return 0;
        }

        $text = trim($note->title."\n\n".$body);
        $hash = sha1($text);

        if ($this->isCurrent(Document::SOURCE_NOTE, $note->id, $hash)) {
            return 0;
        }

        $shadow = Document::allSources()->updateOrCreate(
            ['source_type' => Document::SOURCE_NOTE, 'source_id' => $note->id],
            [
                'owner_id' => $note->user_id,
                'uploaded_by' => $note->user_id,
                'title' => $note->title !== '' ? $note->title : 'Untitled note',
                'category' => 'Personal Note',
                'file_type' => 'note',
                'file_path' => null,
                'file_hash' => $hash,
                'visibility' => [],
                'status' => DocumentStatus::APPROVED,
                'processed_at' => now(),
            ],
        );

        return $this->replaceChunks($shadow, $this->chunker->chunkText($text));
    }

    public function forgetNote(int $noteId): void
    {
        $this->forgetShadow(Document::SOURCE_NOTE, $noteId);
    }

    /**
     * Index (or re-index) a course material's uploaded file. Unpublished
     * materials and files without extractable text are removed from the index.
     */
    public function syncMaterial(CourseMaterial $material): int
    {
        $fileType = strtolower(pathinfo((string) $material->file_path, PATHINFO_EXTENSION));

        if (! $material->is_published
            || $material->file_path === null
            || ! in_array($fileType, self::EXTRACTABLE_TYPES, true)
            || ! Storage::disk(self::MATERIAL_DISK)->exists($material->file_path)) {
            $this->forgetMaterial($material->id);

            return 0;
        }

        $hash = (string) hash_file('sha256', Storage::disk(self::MATERIAL_DISK)->path($material->file_path));

        if ($this->isCurrent(Document::SOURCE_MATERIAL, $material->id, $hash)) {
            return 0;
        }

        $pages = $this->extractor->extractFromDisk(self::MATERIAL_DISK, $material->file_path, $fileType);
        if (empty($pages)) {
            $this->forgetMaterial($material->id);

            return 0;
        }

        $shadow = Document::allSources()->updateOrCreate(
            ['source_type' => Document::SOURCE_MATERIAL, 'source_id' => $material->id],
            [
                'owner_id' => null,
                'uploaded_by' => $material->uploaded_by,
                'title' => $material->title,
                'description' => $material->description,
                'category' => 'Course Material',
                'file_type' => $fileType,
                'file_path' => $material->file_path,
                'original_filename' => $material->original_filename,
                'file_size' => $material->file_size ?? 0,
                'file_hash' => $hash,
                'visibility' => [],
                'status' => DocumentStatus::APPROVED,
                'pages' => count($pages),
                'processed_at' => now(),
            ],
        );

        return $this->replaceChunks($shadow, $this->chunker->chunkPages($pages));
    }

    public function forgetMaterial(int $materialId): void
    {
        $this->forgetShadow(Document::SOURCE_MATERIAL, $materialId);
    }

    /**
     * Whether a shadow already indexes this exact content with the active
     * embedding model — in which case re-processing would be a no-op API spend.
     */
    private function isCurrent(string $sourceType, int $sourceId, string $hash): bool
    {
        $shadow = $this->findShadow($sourceType, $sourceId);

        return $shadow !== null
            && $shadow->file_hash === $hash
            && $shadow->embeddings()->where('model', $this->embeddings->model())->exists();
    }

    /**
     * Replace a shadow's chunks and re-embed them.
     *
     * @param  array<int, array{content: string, page: ?int, section: ?string, token_count: int, chunk_index?: int}>  $chunks
     */
    private function replaceChunks(Document $shadow, array $chunks): int
    {
        $shadow->chunks()->delete();

        foreach ($chunks as $i => $chunk) {
            $shadow->chunks()->create([
                'chunk_index' => $chunk['chunk_index'] ?? $i,
                'content' => $chunk['content'],
                'page' => $chunk['page'] ?? null,
                'section' => $chunk['section'] ?? null,
                'token_count' => $chunk['token_count'] ?? 0,
            ]);
        }

        return $this->embeddings->embedDocument($shadow);
    }

    private function forgetShadow(string $sourceType, int $sourceId): void
    {
        // Hard delete: chunks/embeddings cascade at the DB level, and the
        // DocumentObserver bumps the corpus version to invalidate caches.
        $this->findShadow($sourceType, $sourceId)?->forceDelete();
    }

    private function findShadow(string $sourceType, int $sourceId): ?Document
    {
        return Document::allSources()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->first();
    }
}
