<?php

namespace Database\Seeders;

use App\Domain\Chat\Document\Services\DocumentProcessingService;
use App\Domain\User\Models\User;
use App\Enums\DocumentStatus;
use App\Infrastructure\FileStorage\DocumentStorageService;
use App\Models\Department;
use App\Models\Document;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

/**
 * Seeds the knowledge base from the demo document set in public/demo_files.
 *
 * Each file is ingested exactly like an admin upload: the source file is copied
 * into the private document storage (UUID-named, never touching demo_files),
 * a Document row is created with its metadata, departments are synced through
 * the many-to-many pivot, visibility is stored as a multi-audience array, and
 * the document is processed (chunked + embedded) so it is queryable by the RAG
 * pipeline.
 *
 * Re-running is idempotent: existing demo documents (matched by title) are
 * fully removed — stored file, chunks, embeddings and pivot rows — and then
 * re-inserted fresh, so the seeder never duplicates data.
 */
class KnowledgeBaseSeeder extends Seeder
{
    private const SOURCE_DIRECTORY = 'demo_files';

    /**
     * Map the short department codes used in the metadata to their full
     * department names as seeded by RBACSeeder. An empty `departments` list
     * means "All Departments" (university-wide, no pivot rows).
     */
    private const DEPARTMENT_NAMES = [
        'CSE' => 'Computer Science Engineering',
        'EEE' => 'Electrical Engineering',
        'BBA' => 'Business Administration',
    ];

    public function run(): void
    {
        $this->command->info('Seeding knowledge base documents...');

        $admin = User::where('email', 'admin@university.edu')->first();
        if (! $admin) {
            $this->command->warn('   Demo admin missing; run RBACSeeder first.');

            return;
        }

        $documents = $this->documentDefinitions();

        $storage = app(DocumentStorageService::class);
        $this->removeExisting($documents, $storage);

        $processor = app(DocumentProcessingService::class);
        $departmentIds = $this->departmentIdMap();

        $seeded = 0;

        foreach ($documents as $data) {
            $source = public_path(self::SOURCE_DIRECTORY.DIRECTORY_SEPARATOR.$data['file']);

            if (! is_file($source)) {
                $this->command->warn("   Skipping {$data['file']} — file not found in ".self::SOURCE_DIRECTORY.'.');

                continue;
            }

            // Mirror the admin upload path: store a copy on the private disk with
            // a UUID name. UploadedFile in test mode reads (does not move) the
            // source, so the demo_files folder is left untouched.
            $stored = $storage->store(new UploadedFile($source, $data['file'], null, null, true));

            $document = Document::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'category' => $data['category'],
                'visibility' => $data['visibility'],
                'tags' => $data['tags'],
                'file_path' => $stored['path'],
                'file_type' => $stored['file_type'],
                'file_size' => $stored['file_size'],
                'original_filename' => $stored['original_filename'],
                'file_hash' => hash_file('sha256', $source),
                'status' => DocumentStatus::PROCESSING,
                'uploaded_by' => $admin->id,
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ]);

            $document->departments()->sync(
                $this->resolveDepartmentIds($data['departments'], $departmentIds)
            );

            $processor->process($document);
            $seeded++;
        }

        $this->pruneOrphanFiles($storage);

        $this->command->info("   ✓ Knowledge base seeded ({$seeded} documents indexed)");
    }

    /**
     * Delete any file in the document storage directory that no document row
     * (including soft-deleted ones) references. This keeps re-runs from leaving
     * orphaned files behind and self-heals strays from earlier runs, so the
     * storage folder never accumulates duplicates.
     */
    private function pruneOrphanFiles(DocumentStorageService $storage): void
    {
        $referenced = Document::withTrashed()
            ->whereNotNull('file_path')
            ->pluck('file_path')
            ->flip();

        $removed = 0;
        foreach ($storage->files() as $path) {
            if (! $referenced->has($path)) {
                $storage->delete($path);
                $removed++;
            }
        }

        if ($removed > 0) {
            $this->command->info("   ✓ Pruned {$removed} orphaned document file(s).");
        }
    }

    /**
     * Delete any previously seeded demo documents (and everything attached to
     * them) so the seeder always inserts a fresh, non-duplicated set.
     *
     * @param  array<int, array<string, mixed>>  $documents
     */
    private function removeExisting(array $documents, DocumentStorageService $storage): void
    {
        $titles = array_column($documents, 'title');

        Document::withTrashed()->whereIn('title', $titles)->get()->each(function (Document $document) use ($storage): void {
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

    /**
     * Resolve the metadata department codes (e.g. ["CSE", "EEE"]) to their
     * database ids. An empty input stays empty = All Departments.
     *
     * @param  array<int, string>  $codes
     * @param  array<string, int>  $departmentIds
     * @return array<int, int>
     */
    private function resolveDepartmentIds(array $codes, array $departmentIds): array
    {
        return collect($codes)
            ->map(fn (string $code) => $departmentIds[self::DEPARTMENT_NAMES[$code] ?? $code] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, int> Department name => id.
     */
    private function departmentIdMap(): array
    {
        return Department::pluck('id', 'name')->all();
    }

    /**
     * The demo document set and its upload metadata, grouped per
     * documents/document_upload_metadata.md. An empty `departments` array
     * means the document targets every department.
     *
     * @return array<int, array<string, mixed>>
     */
    private function documentDefinitions(): array
    {
        return [
            [
                'file' => '01_exam_notice_midterm_final.pdf',
                'title' => 'Midterm & Final Exam Notice',
                'description' => 'Official notice for midterm and final examinations, including the registration deadline.',
                'category' => 'Schedule',
                'departments' => [],
                'visibility' => ['students', 'faculty', 'admins'],
                'tags' => ['exam', 'midterm', 'final', 'registration deadline', 'spring 2026'],
            ],
            [
                'file' => '02_dept_exam_schedule.pdf',
                'title' => 'Departmental Final Exam Schedule',
                'description' => 'Final examination schedule with course codes and room assignments for CSE, EEE and BBA.',
                'category' => 'Schedule',
                'departments' => ['CSE', 'EEE', 'BBA'],
                'visibility' => ['students', 'faculty', 'admins'],
                'tags' => ['final exam', 'schedule', 'course code', 'room', 'spring 2026'],
            ],
            [
                'file' => '03_admission_notice.pdf',
                'title' => 'Admission Notice',
                'description' => 'Admission intake notice for Summer 2026, with registration deadline and fee information.',
                'category' => 'General',
                'departments' => [],
                'visibility' => ['students', 'admins'],
                'tags' => ['admission', 'intake', 'summer 2026', 'registration deadline', 'fees'],
            ],
            [
                'file' => '04_attendance_policy.pdf',
                'title' => 'Attendance Policy',
                'description' => 'University attendance policy covering the 70% eligibility threshold and penalties.',
                'category' => 'Policy',
                'departments' => [],
                'visibility' => ['students', 'faculty', 'admins'],
                'tags' => ['attendance', '70% threshold', 'eligibility', 'penalty'],
            ],
            [
                'file' => '05_attendance_sheet_sample.pdf',
                'title' => 'Attendance Sheet Sample (CSE-2103)',
                'description' => 'Sample attendance sheet for CSE-2103, section B — staff reference only.',
                'category' => 'General',
                'departments' => ['CSE'],
                'visibility' => ['faculty', 'admins'],
                'tags' => ['attendance sheet', 'CSE-2103', 'eligibility', 'section B'],
            ],
            [
                'file' => '06_cse_course_structure.docx',
                'title' => 'CSE Course Structure',
                'description' => 'Computer Science Engineering course structure with credits, semesters and sections.',
                'category' => 'Syllabus',
                'departments' => ['CSE'],
                'visibility' => ['students', 'faculty', 'admins'],
                'tags' => ['CSE', 'course structure', 'credits', 'semester', 'section'],
            ],
            [
                'file' => '07_departments_overview.docx',
                'title' => 'Departments Overview',
                'description' => 'Overview of EEE and BBA programs, course distribution and credit requirements.',
                'category' => 'Syllabus',
                'departments' => ['EEE', 'BBA'],
                'visibility' => ['students', 'faculty', 'admins'],
                'tags' => ['EEE', 'BBA', 'English', 'credits', 'course distribution'],
            ],
            [
                'file' => '08_fee_policy.docx',
                'title' => 'Fee Policy',
                'description' => 'Tuition fee policy covering installments, payment rules and waivers.',
                'category' => 'Policy',
                'departments' => [],
                'visibility' => ['students', 'admins'],
                'tags' => ['fee', 'tuition', 'installment', 'payment rules', 'waiver'],
            ],
            [
                'file' => '09_registration_credit_probation_policy.docx',
                'title' => 'Registration, Credit & Probation Policy',
                'description' => 'Policy on late registration, extra credit, academic probation and warnings.',
                'category' => 'Policy',
                'departments' => [],
                'visibility' => ['students', 'faculty', 'admins'],
                'tags' => ['late registration', 'extra credit', 'probation', 'academic warning'],
            ],
            [
                'file' => '10_faculty_section_overview.docx',
                'title' => 'Faculty Section Overview',
                'description' => 'Faculty assignment and section overview for CSE, EEE and BBA — staff reference only.',
                'category' => 'Schedule',
                'departments' => ['CSE', 'EEE', 'BBA'],
                'visibility' => ['faculty', 'admins'],
                'tags' => ['faculty assignment', 'section size', 'course registration'],
            ],
        ];
    }
}
