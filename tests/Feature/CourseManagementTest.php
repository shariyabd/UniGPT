<?php

namespace Tests\Feature;

use App\Domain\User\Models\User;
use App\Models\Course;
use App\Models\CourseMaterial;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourseManagementTest extends TestCase
{
    use DatabaseTransactions;

    private function faculty(): User
    {
        $faculty = User::where('email', 'prof.smith@university.edu')->first();

        if (! $faculty) {
            $this->markTestSkipped('Demo faculty not seeded; run php artisan db:seed.');
        }

        return $faculty;
    }

    private function student(): User
    {
        $student = User::where('email', 'student@university.edu')->first();

        if (! $student) {
            $this->markTestSkipped('Demo student not seeded; run php artisan db:seed.');
        }

        return $student;
    }

    public function test_faculty_can_create_a_course(): void
    {
        $faculty = $this->faculty();

        $this->actingAs($faculty)
            ->post('/faculty/courses', [
                'code' => 'TEST101',
                'name' => 'Intro to Testing',
                'credits' => 3,
                'max_enrollment' => 40,
                'semester' => 4,
                'is_active' => true,
                'schedule' => ['lectures' => 'Fri 09:00', 'classroom' => 'Lab 2', 'office_hours' => ''],
            ])
            ->assertRedirect();

        $course = Course::where('code', 'TEST101')->first();
        $this->assertNotNull($course);
        $this->assertEquals($faculty->id, $course->faculty_id);
        // Empty schedule sub-fields are filtered out.
        $this->assertSame(['lectures' => 'Fri 09:00', 'classroom' => 'Lab 2'], $course->schedule);
    }

    public function test_faculty_can_update_own_course(): void
    {
        $faculty = $this->faculty();
        $course = $faculty->teachingCourses()->first();

        $this->actingAs($faculty)
            ->patch("/faculty/courses/{$course->id}", [
                'code' => $course->code,
                'name' => 'Renamed Course',
                'credits' => 4,
                'max_enrollment' => 75,
            ])
            ->assertRedirect();

        $this->assertSame('Renamed Course', $course->fresh()->name);
        $this->assertSame(75, $course->fresh()->max_enrollment);
    }

    public function test_faculty_cannot_update_unowned_course(): void
    {
        $faculty = $this->faculty();
        $student = $this->student();

        $foreign = Course::create([
            'code' => 'ZZ100',
            'name' => 'Foreign',
            'faculty_id' => $student->id,
            'credits' => 3,
            'max_enrollment' => 30,
            'is_active' => true,
        ]);

        $this->actingAs($faculty)
            ->patch("/faculty/courses/{$foreign->id}", [
                'code' => 'ZZ100',
                'name' => 'Hijacked',
                'credits' => 3,
                'max_enrollment' => 30,
            ])
            ->assertForbidden();
    }

    public function test_faculty_can_delete_own_course(): void
    {
        $faculty = $this->faculty();

        $course = Course::create([
            'code' => 'DEL100',
            'name' => 'To Delete',
            'faculty_id' => $faculty->id,
            'credits' => 3,
            'max_enrollment' => 30,
            'is_active' => true,
        ]);

        $this->actingAs($faculty)
            ->delete("/faculty/courses/{$course->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    public function test_faculty_can_upload_a_material_with_a_file(): void
    {
        Storage::fake('local');
        $faculty = $this->faculty();
        $course = $faculty->teachingCourses()->first();

        $file = UploadedFile::fake()->create('lecture.pdf', 120, 'application/pdf');

        $this->actingAs($faculty)
            ->post("/faculty/courses/{$course->id}/materials", [
                'title' => 'Week 9 Notes',
                'type' => 'lecture',
                'week' => 9,
                'is_published' => true,
                'file' => $file,
            ])
            ->assertRedirect();

        $material = CourseMaterial::where('course_id', $course->id)->where('title', 'Week 9 Notes')->first();
        $this->assertNotNull($material);
        $this->assertNotNull($material->file_path);
        Storage::disk('local')->assertExists($material->file_path);
    }

    public function test_enrolled_student_can_download_a_material_file(): void
    {
        Storage::fake('local');
        $faculty = $this->faculty();
        $student = $this->student();
        $course = $faculty->teachingCourses()->whereHas('students')->first();

        if (! $course) {
            $this->markTestSkipped('No seeded course with an enrolled student.');
        }

        $path = UploadedFile::fake()->create('notes.pdf', 50)->store('course-materials', 'local');
        $material = CourseMaterial::create([
            'course_id' => $course->id,
            'title' => 'Downloadable',
            'type' => 'reading',
            'file_path' => $path,
            'original_filename' => 'notes.pdf',
            'is_published' => true,
        ]);

        $this->actingAs($student)
            ->get("/materials/{$material->id}/download")
            ->assertOk();

        $this->assertSame(1, $material->fresh()->downloads);
    }

    public function test_faculty_can_view_create_and_edit_forms(): void
    {
        $faculty = $this->faculty();
        $course = $faculty->teachingCourses()->first();

        $this->actingAs($faculty)->get('/faculty/courses/create')->assertOk();
        $this->actingAs($faculty)->get("/faculty/courses/{$course->id}/edit")->assertOk();
    }
}
