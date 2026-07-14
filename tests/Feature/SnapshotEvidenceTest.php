<?php

namespace Tests\Feature;

use App\Domain\Academic\Services\ExamSecurityService;
use App\Domain\User\Models\User;
use App\Models\ClassTest;
use App\Models\ClassTestAttempt;
use App\Models\ClassTestSnapshot;
use App\Models\Course;
use App\Models\Section;
use App\Models\Term;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SnapshotEvidenceTest extends TestCase
{
    use DatabaseTransactions;

    private function faculty(): User
    {
        return User::where('email', 'prof.smith@university.edu')->firstOr(
            fn () => $this->markTestSkipped('Demo faculty not seeded.')
        );
    }

    private function student(): User
    {
        return User::where('email', 'student@university.edu')->firstOr(
            fn () => $this->markTestSkipped('Demo student not seeded.')
        );
    }

    private function setUpSection(User $faculty, User $student): Section
    {
        $course = Course::create([
            'code' => 'SE'.substr((string) microtime(true), -6),
            'name' => 'Snapshot Evidence Course',
            'department_id' => $faculty->department_id,
            'faculty_id' => $faculty->id,
            'semester' => 5,
            'credits' => 3,
        ]);

        $section = Section::create([
            'course_id' => $course->id,
            'term_id' => Term::first()?->id,
            'faculty_id' => $faculty->id,
            'label' => 'A',
        ]);

        $student->enrolledSections()->attach($section->id, [
            'role' => 'student',
            'status' => 'enrolled',
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        return $section;
    }

    private function createTest(User $faculty, Section $section, array $security): ClassTest
    {
        $this->actingAs($faculty)
            ->post('/faculty/class-tests', [
                'section_id' => $section->id,
                'title' => 'Snapshot evidence test',
                'duration_minutes' => 15,
                'status' => 'published',
                'shuffle_questions' => false,
                'security_config' => $security,
                'questions' => [
                    ['type' => 'true_false', 'question_text' => 'The sky is blue.', 'marks' => 1, 'correct_answer' => 'true'],
                ],
            ])
            ->assertSessionHasNoErrors();

        return ClassTest::where('section_id', $section->id)->firstOrFail();
    }

    private function fakeFrame(): UploadedFile
    {
        return UploadedFile::fake()->image('frame.jpg', 320, 240);
    }

    public function test_snapshot_upload_is_rejected_when_layer_disabled(): void
    {
        $faculty = $this->faculty();
        $student = $this->student();
        $section = $this->setUpSection($faculty, $student);

        $test = $this->createTest($faculty, $section, ['webcam' => true]);
        $this->actingAs($student)->post("/class-tests/{$test->id}/start");

        $this->actingAs($student)
            ->post("/class-tests/{$test->id}/snapshot", [
                'trigger' => 'periodic',
                'sequence' => 0,
                'frame' => $this->fakeFrame(),
            ])
            ->assertForbidden();
    }

    public function test_snapshot_is_stored_and_visible_to_faculty(): void
    {
        Storage::fake('local');

        $faculty = $this->faculty();
        $student = $this->student();
        $section = $this->setUpSection($faculty, $student);

        $test = $this->createTest($faculty, $section, ['snapshot_evidence' => true]);
        $this->actingAs($student)->post("/class-tests/{$test->id}/start");

        $this->actingAs($student)
            ->post("/class-tests/{$test->id}/snapshot", [
                'trigger' => 'phone_detected',
                'sequence' => 0,
                'frame' => $this->fakeFrame(),
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'capped' => false]);

        $attempt = ClassTestAttempt::where('class_test_id', $test->id)->where('user_id', $student->id)->firstOrFail();
        $snapshot = $attempt->snapshots()->firstOrFail();
        $this->assertSame('phone_detected', $snapshot->trigger);
        Storage::disk('local')->assertExists($snapshot->path);

        // Faculty attempt review includes the photo strip and the image streams.
        $props = $this->actingAs($faculty)
            ->get("/faculty/class-tests/{$test->id}/attempts/{$attempt->id}")
            ->viewData('page')['props'];
        $this->assertCount(1, $props['snapshots']);
        $this->assertSame('phone_detected', $props['snapshots'][0]['trigger']);

        $this->actingAs($faculty)
            ->get("/faculty/class-tests/{$test->id}/attempts/{$attempt->id}/snapshots/{$snapshot->id}")
            ->assertOk();
    }

    public function test_snapshot_uploads_stop_at_the_per_attempt_cap(): void
    {
        Storage::fake('local');
        config(['exam_security.snapshots.max_per_attempt' => 2]);

        $faculty = $this->faculty();
        $student = $this->student();
        $section = $this->setUpSection($faculty, $student);

        $test = $this->createTest($faculty, $section, ['snapshot_evidence' => true]);
        $this->actingAs($student)->post("/class-tests/{$test->id}/start");

        foreach ([0, 1] as $seq) {
            $this->actingAs($student)
                ->post("/class-tests/{$test->id}/snapshot", [
                    'trigger' => 'periodic',
                    'sequence' => $seq,
                    'frame' => $this->fakeFrame(),
                ])
                ->assertJson(['ok' => true]);
        }

        $this->actingAs($student)
            ->post("/class-tests/{$test->id}/snapshot", [
                'trigger' => 'periodic',
                'sequence' => 2,
                'frame' => $this->fakeFrame(),
            ])
            ->assertJson(['ok' => false, 'capped' => true]);

        $this->assertSame(2, ClassTestSnapshot::whereHas('attempt', fn ($q) => $q->where('class_test_id', $test->id))->count());
    }

    public function test_phone_and_multiface_events_feed_the_risk_score(): void
    {
        $faculty = $this->faculty();
        $student = $this->student();
        $section = $this->setUpSection($faculty, $student);

        $test = $this->createTest($faculty, $section, ['phone_detection' => true, 'face_liveness' => true]);
        $this->actingAs($student)->post("/class-tests/{$test->id}/start");

        $this->actingAs($student)
            ->postJson("/class-tests/{$test->id}/events", [
                'events' => [
                    ['type' => 'phone_detected', 'severity' => 'warning', 'meta' => ['score' => 0.8]],
                    ['type' => 'multiple_faces', 'severity' => 'warning', 'meta' => ['faces' => 2]],
                ],
            ])
            ->assertOk();

        $attempt = ClassTestAttempt::where('class_test_id', $test->id)->where('user_id', $student->id)->firstOrFail();
        $this->assertSame(0, $attempt->violation_count); // review signals, not violations

        $result = app(ExamSecurityService::class)->computeRisk($attempt);
        $this->assertContains('phone_activity', array_column($result['factors'], 'key'));
    }

    public function test_prune_command_deletes_old_evidence_of_finalised_attempts(): void
    {
        Storage::fake('local');

        $faculty = $this->faculty();
        $student = $this->student();
        $section = $this->setUpSection($faculty, $student);

        $test = $this->createTest($faculty, $section, ['snapshot_evidence' => true]);
        $this->actingAs($student)->post("/class-tests/{$test->id}/start");
        $this->actingAs($student)->post("/class-tests/{$test->id}/snapshot", [
            'trigger' => 'periodic', 'sequence' => 0, 'frame' => $this->fakeFrame(),
        ]);
        $this->actingAs($student)->post("/class-tests/{$test->id}/submit", ['answers' => []]);

        $attempt = ClassTestAttempt::where('class_test_id', $test->id)->where('user_id', $student->id)->firstOrFail();
        $snapshot = $attempt->snapshots()->firstOrFail();

        // Age the attempt + snapshot past the retention window.
        $attempt->forceFill(['created_at' => now()->subDays(60)])->save();
        $snapshot->forceFill(['created_at' => now()->subDays(60)])->save();

        $this->artisan('exam:prune-evidence', ['--days' => 30])->assertSuccessful();

        $this->assertDatabaseMissing('class_test_snapshots', ['id' => $snapshot->id]);
        Storage::disk('local')->assertMissing($snapshot->path);
    }
}
