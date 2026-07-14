<?php

namespace Tests\Feature;

use App\Domain\Academic\Services\ExamSecurityService;
use App\Domain\User\Models\User;
use App\Models\ClassTest;
use App\Models\ClassTestAttempt;
use App\Models\Course;
use App\Models\Section;
use App\Models\Term;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FaceLivenessTest extends TestCase
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
            'code' => 'FL'.substr((string) microtime(true), -6),
            'name' => 'Face Liveness Course',
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

    /**
     * @param  array<string, bool>  $security
     * @return array<string, mixed>
     */
    private function payload(int $sectionId, array $security = [], int $maxWarnings = 3): array
    {
        return [
            'section_id' => $sectionId,
            'title' => 'Proctored liveness test',
            'duration_minutes' => 15,
            'status' => 'published',
            'shuffle_questions' => false,
            'max_warnings' => $maxWarnings,
            'security_config' => $security,
            'questions' => [
                [
                    'type' => 'true_false',
                    'question_text' => 'The sky is blue.',
                    'marks' => 1,
                    'correct_answer' => 'true',
                ],
            ],
        ];
    }

    private function createTest(User $faculty, Section $section, array $security, int $maxWarnings = 3): ClassTest
    {
        $this->actingAs($faculty)
            ->post('/faculty/class-tests', $this->payload($section->id, $security, $maxWarnings))
            ->assertSessionHasNoErrors();

        return ClassTest::where('section_id', $section->id)->firstOrFail();
    }

    public function test_face_liveness_works_without_the_webcam_recording_layer(): void
    {
        $service = app(ExamSecurityService::class);

        // Camera-based detection layers self-provide camera access via consent —
        // they deliberately do NOT require continuous webcam recording.
        $selection = $service->sanitizeSelection(['face_liveness' => true, 'webcam' => false]);
        $this->assertTrue($selection['face_liveness']);
    }

    public function test_camera_flag_is_set_when_any_camera_layer_is_on(): void
    {
        $faculty = $this->faculty();
        $student = $this->student();
        $section = $this->setUpSection($faculty, $student);

        // Liveness only, no recording: camera requested, webcam recording off.
        $test = $this->createTest($faculty, $section, ['face_liveness' => true]);

        $this->actingAs($student)->post("/class-tests/{$test->id}/start");
        $recording = $this->actingAs($student)
            ->get("/class-tests/{$test->id}/take")
            ->viewData('page')['props']['security']['recording'];

        $this->assertTrue($recording['camera']);
        $this->assertFalse($recording['webcam']);
        $this->assertGreaterThan(0, $recording['videoBitsPerSecond']);
    }

    public function test_client_config_includes_liveness_payload_only_when_enabled(): void
    {
        $faculty = $this->faculty();
        $student = $this->student();
        $section = $this->setUpSection($faculty, $student);

        $test = $this->createTest($faculty, $section, ['webcam' => true, 'face_liveness' => true]);

        $this->actingAs($student)->post("/class-tests/{$test->id}/start");
        $security = $this->actingAs($student)
            ->get("/class-tests/{$test->id}/take")
            ->viewData('page')['props']['security'];

        $this->assertTrue($security['layers']['face_liveness']);
        $this->assertNotNull($security['liveness']);
        $this->assertSame((int) config('exam_security.liveness.soft_warning_seconds'), $security['liveness']['softWarningSeconds']);
        $this->assertSame((int) config('exam_security.liveness.grace_seconds'), $security['liveness']['graceSeconds']);
        $this->assertSame((int) config('exam_security.liveness.free_warnings'), $security['liveness']['freeWarnings']);
        $this->assertSame((int) config('exam_security.liveness.no_blink_spoof_seconds'), $security['liveness']['noBlinkSpoofSeconds']);
        $this->assertSame((int) config('exam_security.liveness.gate_bypass_seconds'), $security['liveness']['gateBypassSeconds']);
        $this->assertGreaterThan($security['liveness']['softWarningSeconds'], $security['liveness']['graceSeconds']);
        $this->assertNotEmpty($security['liveness']['wasmPath']);
        $this->assertNotEmpty($security['liveness']['modelPath']);
    }

    public function test_client_config_omits_liveness_payload_when_disabled(): void
    {
        $faculty = $this->faculty();
        $student = $this->student();
        $section = $this->setUpSection($faculty, $student);

        $test = $this->createTest($faculty, $section, ['webcam' => true]);

        $this->actingAs($student)->post("/class-tests/{$test->id}/start");
        $security = $this->actingAs($student)
            ->get("/class-tests/{$test->id}/take")
            ->viewData('page')['props']['security'];

        $this->assertFalse($security['layers']['face_liveness']);
        $this->assertNull($security['liveness']);
    }

    public function test_face_liveness_violations_increment_count_and_disqualify_past_threshold(): void
    {
        $faculty = $this->faculty();
        $student = $this->student();
        $section = $this->setUpSection($faculty, $student);

        $test = $this->createTest($faculty, $section, ['webcam' => true, 'face_liveness' => true], maxWarnings: 1);

        $this->actingAs($student)->post("/class-tests/{$test->id}/start");

        // First violation — within the threshold, not disqualified yet.
        $this->actingAs($student)
            ->postJson("/class-tests/{$test->id}/events", [
                'events' => [
                    ['type' => 'face_liveness_violation', 'severity' => 'violation', 'meta' => ['incident' => 3]],
                ],
            ])
            ->assertOk()
            ->assertJson(['violationCount' => 1, 'disqualified' => false]);

        // Second violation crosses max_warnings = 1.
        $this->actingAs($student)
            ->postJson("/class-tests/{$test->id}/events", [
                'events' => [
                    ['type' => 'face_liveness_violation', 'severity' => 'violation', 'meta' => ['incident' => 4]],
                ],
            ])
            ->assertOk()
            ->assertJson(['violationCount' => 2, 'disqualified' => true]);

        $attempt = ClassTestAttempt::where('class_test_id', $test->id)->where('user_id', $student->id)->firstOrFail();
        $this->assertSame(2, $attempt->violation_count);
        $this->assertSame(2, $attempt->events()->where('type', 'face_liveness_violation')->count());
    }

    public function test_risk_score_includes_face_loss_factor_beyond_free_warnings(): void
    {
        $faculty = $this->faculty();
        $student = $this->student();
        $section = $this->setUpSection($faculty, $student);

        $test = $this->createTest($faculty, $section, ['webcam' => true, 'face_liveness' => true]);

        $this->actingAs($student)->post("/class-tests/{$test->id}/start");

        // Three face-loss incidents — one beyond the two free warnings.
        $this->actingAs($student)
            ->postJson("/class-tests/{$test->id}/events", [
                'events' => [
                    ['type' => 'face_lost', 'severity' => 'warning', 'meta' => ['incident' => 1]],
                    ['type' => 'face_lost', 'severity' => 'warning', 'meta' => ['incident' => 2]],
                    ['type' => 'face_lost', 'severity' => 'warning', 'meta' => ['incident' => 3]],
                ],
            ])
            ->assertOk();

        $attempt = ClassTestAttempt::where('class_test_id', $test->id)->where('user_id', $student->id)->firstOrFail();

        $result = app(ExamSecurityService::class)->computeRisk($attempt);

        $keys = array_column($result['factors'], 'key');
        $this->assertContains('face_loss', $keys);
        $this->assertGreaterThan(0, $result['score']);
    }
}
