<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Domain\RAG\Ingestion\PersonalCorpusService;
use App\Domain\RAG\Retrieval\RetrievalService;
use App\Domain\User\Models\User;
use App\Infrastructure\AI\MockProvider;
use App\Models\CourseMaterial;
use App\Models\Document;
use App\Models\Note;
use App\Models\Section;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * "Chat with my materials": personal notes and course-material files are
 * indexed as hidden shadow documents and retrievable only within the owner's /
 * enrolled students' RAG scope.
 */
class PersonalCorpusTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Force the keyless deterministic provider so tests never call OpenAI,
        // regardless of local .env / admin settings.
        $this->app->bind(AIProviderInterface::class, fn () => new MockProvider);
    }

    private function student(): User
    {
        $student = User::where('email', 'student@university.edu')->first();
        if (! $student) {
            $this->markTestSkipped('Demo student not seeded; run php artisan db:seed.');
        }

        return $student;
    }

    private function otherStudent(User $not): User
    {
        $other = User::where('id', '!=', $not->id)
            ->whereHas('roles', fn ($q) => $q->where('slug', 'student'))
            ->first();

        if (! $other) {
            $this->markTestSkipped('Second demo student not seeded.');
        }

        return $other;
    }

    public function test_saving_a_note_indexes_it_as_a_hidden_shadow_document(): void
    {
        $student = $this->student();
        $libraryCount = Document::count();

        $this->actingAs($student)
            ->post('/notes', [
                'title' => 'Dijkstra shortest path recap',
                'content' => 'Dijkstra uses a priority queue to relax edges in nondecreasing distance order.',
            ])
            ->assertRedirect();

        $note = Note::where('user_id', $student->id)->where('title', 'Dijkstra shortest path recap')->firstOrFail();

        $shadow = Document::allSources()
            ->where('source_type', Document::SOURCE_NOTE)
            ->where('source_id', $note->id)
            ->first();

        $this->assertNotNull($shadow, 'Note should have a shadow document.');
        $this->assertSame($student->id, $shadow->owner_id);
        $this->assertGreaterThan(0, $shadow->chunks()->count());
        $this->assertGreaterThan(0, $shadow->embeddings()->count());

        // The global library scope must hide the shadow from every normal query
        // (admin library, approvals, dashboards all build on the default scope).
        $this->assertSame($libraryCount, Document::count(), 'Shadow documents must not leak into library queries.');
    }

    public function test_note_is_retrievable_by_owner_but_not_by_other_students(): void
    {
        $student = $this->student();
        $other = $this->otherStudent($student);

        $note = $student->notes()->create([
            'title' => 'Zanzibar leopard sightings',
            'content' => 'The zanzibar leopard mnemonic maps cache eviction tiers to spotted fur patterns.',
        ]);
        app(PersonalCorpusService::class)->syncNote($note);

        $query = 'zanzibar leopard cache eviction mnemonic';

        $ownIds = app(RetrievalService::class)->retrieve($query, $student)
            ->map(fn (array $row) => $row['document']->id);
        $otherIds = app(RetrievalService::class)->retrieve($query, $other)
            ->map(fn (array $row) => $row['document']->id);

        $shadowId = Document::allSources()
            ->where('source_type', Document::SOURCE_NOTE)
            ->where('source_id', $note->id)
            ->value('id');

        $this->assertContains($shadowId, $ownIds->all(), 'Owner should retrieve their own note.');
        $this->assertNotContains($shadowId, $otherIds->all(), 'Another student must never retrieve someone else\'s note.');
    }

    public function test_deleting_a_note_drops_its_shadow_document(): void
    {
        $student = $this->student();

        $note = $student->notes()->create(['title' => 'Temp', 'content' => 'Throwaway body text for indexing.']);
        app(PersonalCorpusService::class)->syncNote($note);

        $this->assertNotNull($this->noteShadow($note));

        $this->actingAs($student)->delete("/notes/{$note->id}")->assertRedirect();

        $this->assertNull($this->noteShadow($note), 'Deleting a note must remove its shadow document.');
    }

    public function test_empty_note_is_not_indexed(): void
    {
        $student = $this->student();

        $note = $student->notes()->create(['title' => 'Only a title', 'content' => '']);
        app(PersonalCorpusService::class)->syncNote($note);

        $this->assertNull($this->noteShadow($note));
    }

    public function test_section_material_file_is_retrievable_by_enrolled_student_only(): void
    {
        $student = $this->student();
        $sectionId = $student->enrolledSectionIds()->first();
        if ($sectionId === null) {
            $this->markTestSkipped('Demo student has no enrolled sections.');
        }

        $other = $this->otherStudent($student);
        if ($other->enrolledSectionIds()->contains($sectionId)) {
            $this->markTestSkipped('Second student shares the section; cannot assert isolation.');
        }

        Storage::disk('local')->put(
            'course-materials/personal-corpus-test.txt',
            'The heliotrope compiler pass reorders basic blocks before register allocation.',
        );

        $material = CourseMaterial::create([
            'course_id' => Section::find($sectionId)->course_id,
            'section_id' => $sectionId,
            'title' => 'Compiler pass lecture notes',
            'type' => 'lecture',
            'is_published' => true,
            'file_path' => 'course-materials/personal-corpus-test.txt',
            'original_filename' => 'lecture.txt',
            'file_size' => 80,
        ]);

        try {
            app(PersonalCorpusService::class)->syncMaterial($material);

            $shadowId = Document::allSources()
                ->where('source_type', Document::SOURCE_MATERIAL)
                ->where('source_id', $material->id)
                ->value('id');
            $this->assertNotNull($shadowId, 'Material file should be indexed.');

            $query = 'heliotrope compiler pass register allocation';

            $enrolledIds = app(RetrievalService::class)->retrieve($query, $student)
                ->map(fn (array $row) => $row['document']->id);
            $outsiderIds = app(RetrievalService::class)->retrieve($query, $other)
                ->map(fn (array $row) => $row['document']->id);

            $this->assertContains($shadowId, $enrolledIds->all(), 'Enrolled student should retrieve section material.');
            $this->assertNotContains($shadowId, $outsiderIds->all(), 'Non-enrolled student must not retrieve the material.');
        } finally {
            Storage::disk('local')->delete('course-materials/personal-corpus-test.txt');
        }
    }

    public function test_unpublishing_a_material_removes_it_from_the_index(): void
    {
        $student = $this->student();
        $sectionId = $student->enrolledSectionIds()->first();
        if ($sectionId === null) {
            $this->markTestSkipped('Demo student has no enrolled sections.');
        }

        Storage::disk('local')->put(
            'course-materials/personal-corpus-unpublish.txt',
            'Draft content that will be unpublished.',
        );

        $material = CourseMaterial::create([
            'course_id' => Section::find($sectionId)->course_id,
            'section_id' => $sectionId,
            'title' => 'Draft notes',
            'type' => 'lecture',
            'is_published' => true,
            'file_path' => 'course-materials/personal-corpus-unpublish.txt',
            'original_filename' => 'draft.txt',
            'file_size' => 40,
        ]);

        try {
            $corpus = app(PersonalCorpusService::class);
            $corpus->syncMaterial($material);
            $this->assertNotNull($this->materialShadow($material));

            $material->update(['is_published' => false]);
            $corpus->syncMaterial($material->fresh());

            $this->assertNull($this->materialShadow($material), 'Unpublished material must leave the index.');
        } finally {
            Storage::disk('local')->delete('course-materials/personal-corpus-unpublish.txt');
        }
    }

    private function noteShadow(Note $note): ?Document
    {
        return Document::allSources()
            ->where('source_type', Document::SOURCE_NOTE)
            ->where('source_id', $note->id)
            ->first();
    }

    private function materialShadow(CourseMaterial $material): ?Document
    {
        return Document::allSources()
            ->where('source_type', Document::SOURCE_MATERIAL)
            ->where('source_id', $material->id)
            ->first();
    }
}
