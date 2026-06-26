<?php

namespace Database\Seeders;

use App\Enums\DocumentStatus;
use App\Infrastructure\FileStorage\DocumentStorageService;
use App\Models\Document;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Seeds the demo student's own document submissions from the reference set in
 * public/demo_files/student. Each file is ingested exactly like a student upload
 * through the portal: the source file is copied into the private document storage
 * (never read from demo_files at access time), a Document row is created owned by
 * the demo student, and it lands in the PENDING approval queue — so the upload →
 * review flow is populated end-to-end for the demo.
 *
 * Re-running is idempotent: the storage filename is derived deterministically
 * from the source filename, and any previously seeded copy (matched by title,
 * scoped to the demo student) is removed — file, chunks, approvals and pivot
 * rows — before re-inserting. Byte-identical source files are de-duplicated by
 * hash so the same document is never seeded twice.
 */
class StudentDocumentSeeder extends Seeder
{
    private const SOURCE_DIRECTORY = 'demo_files/student';

    private const DEMO_STUDENT_EMAIL = 'student@university.edu';

    public function run(): void
    {
        $this->command->info('Seeding demo student documents...');

        $student = \App\Domain\User\Models\User::where('email', self::DEMO_STUDENT_EMAIL)->first();
        if (! $student) {
            $this->command->warn('   Demo student missing; run RBACSeeder first.');

            return;
        }

        $sourceDir = public_path(self::SOURCE_DIRECTORY);
        if (! is_dir($sourceDir)) {
            $this->command->warn('   No '.self::SOURCE_DIRECTORY.' directory; skipping.');

            return;
        }

        $files = glob($sourceDir.DIRECTORY_SEPARATOR.'*');
        sort($files); // deterministic order so de-dup keeps a stable winner.

        $definitions = $this->buildDefinitions($files);

        $storage = app(DocumentStorageService::class);
        $this->removeExisting($definitions, $student->id, $storage);

        $seeded = 0;

        foreach ($definitions as $data) {
            // Mirror the portal upload: copy the source into document storage with
            // a deterministic name (UploadedFile in test mode reads, never moves,
            // so demo_files stays intact), then create the Document row.
            $stored = $storage->store(
                file: new UploadedFile($data['source'], $data['original'], null, null, true),
                name: $data['original'],
            );

            $document = Document::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'category' => $data['category'],
                'visibility' => ['students'],
                'tags' => $data['tags'],
                'file_path' => $stored['path'],
                'file_type' => $stored['file_type'],
                'file_size' => $stored['file_size'],
                'original_filename' => $stored['original_filename'],
                'file_hash' => $data['hash'],
                'status' => DocumentStatus::PENDING,
                'uploaded_by' => $student->id,
            ]);

            $document->departments()->sync(array_filter([$student->department_id]));
            $seeded++;
        }

        $this->command->info("   ✓ Demo student documents seeded ({$seeded} submissions)");
    }

    /**
     * Build one definition per unique source file (byte-identical files are
     * collapsed to their first occurrence by content hash).
     *
     * @param  array<int, string>  $files
     * @return array<int, array<string, mixed>>
     */
    private function buildDefinitions(array $files): array
    {
        $definitions = [];
        $seenHashes = [];

        foreach ($files as $source) {
            if (! is_file($source)) {
                continue;
            }

            $hash = hash_file('sha256', $source);
            if (isset($seenHashes[$hash])) {
                continue; // duplicate content — skip.
            }
            $seenHashes[$hash] = true;

            $original = basename($source);
            $title = $this->titleFromFilename($original);

            $definitions[] = [
                'source' => $source,
                'original' => $original,
                'hash' => $hash,
                'title' => $title,
                'description' => "{$title} — submitted by the demo student.",
                'category' => 'Reading',
                'tags' => $this->tagsFromTitle($title),
            ];
        }

        return $definitions;
    }

    /**
     * A clean, human title from a messy filename: drop the extension, strip
     * duplicate "(1)" markers, normalise separators and title-case it.
     */
    private function titleFromFilename(string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $name = preg_replace('/\s*\(\d+\)/', '', $name);   // remove " (1)" groups
        $name = str_replace(['_', '-'], ' ', (string) $name);
        $name = trim((string) preg_replace('/\s+/', ' ', $name));

        return Str::title($name);
    }

    /**
     * @return array<int, string>
     */
    private function tagsFromTitle(string $title): array
    {
        return collect(explode(' ', Str::lower($title)))
            ->reject(fn (string $word) => mb_strlen($word) < 3)
            ->map(fn (string $word) => trim($word, '()'))
            ->filter()
            ->unique()
            ->take(5)
            ->values()
            ->all();
    }

    /**
     * Remove any previously seeded copies of these documents (scoped to the demo
     * student) so the seeder always re-inserts a fresh, non-duplicated set.
     *
     * @param  array<int, array<string, mixed>>  $definitions
     */
    private function removeExisting(array $definitions, int $studentId, DocumentStorageService $storage): void
    {
        $titles = array_column($definitions, 'title');

        Document::withTrashed()
            ->where('uploaded_by', $studentId)
            ->whereIn('title', $titles)
            ->get()
            ->each(function (Document $document) use ($storage): void {
                if ($document->file_path) {
                    $storage->delete($document->file_path);
                }

                $document->embeddings()->delete();
                $document->chunks()->delete();
                $document->approvals()->delete();
                $document->departments()->detach();
                $document->forceDelete();
            });
    }
}
