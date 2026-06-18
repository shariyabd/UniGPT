<?php

namespace Tests\Feature;

use App\Domain\Academic\Services\CourseService;
use App\Domain\User\Models\User;
use App\Models\Section;
use App\Models\Term;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TermRolloverTest extends TestCase
{
    use DatabaseTransactions;

    private function user(string $email): User
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->markTestSkipped("Demo user {$email} not seeded.");
        }

        return $user;
    }

    private function currentTerm(): Term
    {
        $term = Term::where('is_current', true)->first();

        if (! $term) {
            $this->markTestSkipped('No current term seeded.');
        }

        return $term;
    }

    public function test_admin_can_view_terms(): void
    {
        $this->actingAs($this->user('admin@university.edu'))
            ->get('/admin/terms')
            ->assertOk();
    }

    public function test_set_current_is_exclusive(): void
    {
        $other = Term::where('is_current', false)->first();

        if (! $other) {
            $this->markTestSkipped('Need a second term.');
        }

        $this->actingAs($this->user('admin@university.edu'))
            ->patch(route('admin.terms.current', $other->id))
            ->assertRedirect();

        $this->assertTrue($other->fresh()->is_current);
        $this->assertSame(1, Term::where('is_current', true)->count());
    }

    public function test_closing_a_term_completes_enrollments_and_archives_sections(): void
    {
        $term = $this->currentTerm();

        $activeBefore = DB::table('course_user')->where('term_id', $term->id)->where('status', 'enrolled')->count();
        if ($activeBefore === 0) {
            $this->markTestSkipped('No active enrollments in the current term.');
        }

        $this->actingAs($this->user('admin@university.edu'))
            ->post(route('admin.terms.close', $term->id))
            ->assertRedirect();

        $this->assertSame(0, DB::table('course_user')->where('term_id', $term->id)->where('status', 'enrolled')->count());
        $this->assertSame(0, Section::where('term_id', $term->id)->where('is_active', true)->count());
        $this->assertFalse($term->fresh()->is_current);
    }

    public function test_close_can_promote_the_next_term(): void
    {
        $term = $this->currentTerm();
        $next = Term::where('is_current', false)->first();

        if (! $next) {
            $this->markTestSkipped('Need a next term to promote.');
        }

        $this->actingAs($this->user('admin@university.edu'))
            ->post(route('admin.terms.close', $term->id), ['next_term_id' => $next->id])
            ->assertRedirect();

        $this->assertTrue($next->fresh()->is_current);
        $this->assertSame(1, Term::where('is_current', true)->count());
    }

    public function test_after_rollover_the_students_courses_become_past(): void
    {
        $student = $this->user('student@university.edu');
        $term = $this->currentTerm();

        $currentBefore = app(CourseService::class)->studentCourses($student)->where('isCurrent', true)->count();
        if ($currentBefore === 0) {
            $this->markTestSkipped('Student has no current-term courses.');
        }

        $this->actingAs($this->user('admin@university.edu'))
            ->post(route('admin.terms.close', $term->id))
            ->assertRedirect();

        // Their completed enrollments are now "past".
        $this->assertSame(0, app(CourseService::class)->studentCourses($student)->where('isCurrent', true)->count());
    }
}
