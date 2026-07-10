<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Analytics\Services\EarlyWarningService;
use App\Domain\Analytics\Services\FacultyAnalyticsService;
use App\Domain\User\Models\User;
use App\Models\Assignment;
use App\Models\AttendanceRecord;
use App\Models\ClassTest;
use App\Models\Course;
use App\Models\Section;
use App\Models\Term;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Early-warning system: students with poor attendance, missed deadlines, low
 * test averages or failing grades are flagged with reasons and a risk level.
 */
class EarlyWarningTest extends TestCase
{
    use DatabaseTransactions;

    private User $faculty;

    private User $struggling;

    private User $healthy;

    private Course $course;

    private Section $section;

    protected function setUp(): void
    {
        parent::setUp();

        // A fully synthetic fixture (own faculty, course, section, students)
        // so seeded demo data can never skew the signals under test.
        $this->faculty = $this->makeUser('ew.faculty@test.edu', 'faculty');
        $this->struggling = $this->makeUser('ew.struggling@test.edu', 'student');
        $this->healthy = $this->makeUser('ew.healthy@test.edu', 'student');

        $this->course = Course::create([
            'code' => 'EW101',
            'name' => 'Early Warning Fixtures',
            'faculty_id' => $this->faculty->id,
            'credits' => 3,
            'max_enrollment' => 30,
            'is_active' => true,
        ]);

        $this->section = Section::create([
            'course_id' => $this->course->id,
            'term_id' => Term::query()->value('id'),
            'faculty_id' => $this->faculty->id,
            'label' => 'A',
            'max_enrollment' => 30,
            'is_active' => true,
        ]);

        $this->enroll($this->struggling, grade: 'F');
        $this->enroll($this->healthy, grade: 'A');

        // Attendance: struggling 1/5 present, healthy 5/5.
        foreach (range(1, 5) as $day) {
            AttendanceRecord::create([
                'course_id' => $this->course->id,
                'section_id' => $this->section->id,
                'user_id' => $this->struggling->id,
                'date' => now()->subDays($day)->toDateString(),
                'status' => $day === 1 ? 'present' : 'absent',
            ]);
            AttendanceRecord::create([
                'course_id' => $this->course->id,
                'section_id' => $this->section->id,
                'user_id' => $this->healthy->id,
                'date' => now()->subDays($day)->toDateString(),
                'status' => 'present',
            ]);
        }

        // Two past-due assignments: healthy submitted both, struggling neither.
        foreach ([10, 5] as $daysAgo) {
            $assignment = Assignment::create([
                'course_id' => $this->course->id,
                'section_id' => $this->section->id,
                'title' => "Homework due {$daysAgo}d ago",
                'type' => 'homework',
                'total_points' => 100,
                'due_at' => now()->subDays($daysAgo),
                'status' => 'published',
                'created_by' => $this->faculty->id,
            ]);
            $assignment->submissions()->create([
                'user_id' => $this->healthy->id,
                'content' => 'On time',
                'status' => 'submitted',
                'submitted_at' => now()->subDays($daysAgo + 1),
            ]);
        }

        // Class test: struggling scored 2/10, healthy 9/10.
        $test = ClassTest::create([
            'course_id' => $this->course->id,
            'section_id' => $this->section->id,
            'title' => 'Quiz 1',
            'duration_minutes' => 30,
            'total_marks' => 10,
            'pass_marks' => 4,
            'status' => 'published',
            'created_by' => $this->faculty->id,
        ]);
        $test->attempts()->create([
            'user_id' => $this->struggling->id,
            'status' => 'submitted',
            'started_at' => now()->subDay(),
            'submitted_at' => now()->subDay(),
            'score' => 2,
            'total_marks' => 10,
        ]);
        $test->attempts()->create([
            'user_id' => $this->healthy->id,
            'status' => 'submitted',
            'started_at' => now()->subDay(),
            'submitted_at' => now()->subDay(),
            'score' => 9,
            'total_marks' => 10,
        ]);
    }

    private function makeUser(string $email, string $role): User
    {
        $user = User::create([
            'name' => ucfirst(explode('@', $email)[0]),
            'email' => $email,
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function enroll(User $student, string $grade): void
    {
        $this->course->students()->attach($student->id, [
            'role' => 'student',
            'status' => 'enrolled',
            'grade' => $grade,
            'section_id' => $this->section->id,
            'term_id' => $this->section->term_id,
            'enrolled_at' => now(),
        ]);
    }

    private function flagged(): array
    {
        $students = $this->course->students()
            ->wherePivotIn('section_id', collect([$this->section->id]))
            ->get();

        return app(EarlyWarningService::class)->flag(collect([$this->section->id]), $students);
    }

    public function test_struggling_student_is_flagged_high_risk_with_all_four_signals(): void
    {
        $flagged = $this->flagged();

        $this->assertCount(1, $flagged);

        $row = $flagged[0];
        $this->assertSame($this->struggling->id, $row['id']);
        $this->assertSame('high', $row['riskLevel']);
        $this->assertCount(4, $row['reasons']);
        $this->assertSame(20, $row['attendanceRate']);
        $this->assertSame(2, $row['missedAssignments']);
        $this->assertSame(20, $row['testAverage']);
        $this->assertSame('F', $row['grade']);
    }

    public function test_healthy_student_is_not_flagged(): void
    {
        $ids = array_column($this->flagged(), 'id');

        $this->assertNotContains($this->healthy->id, $ids);
    }

    public function test_single_signal_marks_a_student_as_watch(): void
    {
        // Clear everything except the failing grade for the struggling student.
        AttendanceRecord::where('user_id', $this->struggling->id)->delete();
        Assignment::where('section_id', $this->section->id)->get()
            ->each(fn (Assignment $a) => $a->submissions()->create([
                'user_id' => $this->struggling->id,
                'content' => 'Late but submitted',
                'status' => 'submitted',
                'submitted_at' => now(),
            ]));
        $test = ClassTest::where('section_id', $this->section->id)->first();
        $test->attempts()->where('user_id', $this->struggling->id)->update(['score' => 8]);

        $flagged = collect($this->flagged())->firstWhere('id', $this->struggling->id);

        $this->assertNotNull($flagged);
        $this->assertSame('watch', $flagged['riskLevel']);
        $this->assertSame(['Grade F'], $flagged['reasons']);
    }

    public function test_faculty_analytics_report_carries_the_risk_fields(): void
    {
        $report = app(FacultyAnalyticsService::class)->build($this->faculty, $this->course->id)['report'];

        $this->assertNotNull($report);
        $this->assertNotEmpty($report['atRisk']);
        $this->assertArrayHasKey('riskLevel', $report['atRisk'][0]);
        $this->assertArrayHasKey('missedAssignments', $report['atRisk'][0]);
        $this->assertArrayHasKey('testAverage', $report['atRisk'][0]);
    }

    public function test_dashboard_count_reports_unique_flagged_students(): void
    {
        $this->assertSame(1, app(EarlyWarningService::class)->countForFaculty($this->faculty));
    }

    public function test_dashboard_page_renders_with_at_risk_stat(): void
    {
        $this->actingAs($this->faculty)
            ->get('/faculty/dashboard')
            ->assertOk()
            ->assertSee('At-Risk Students');
    }
}
