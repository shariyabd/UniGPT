<?php

namespace App\Domain\Academic\Services;

use App\Domain\User\Models\User;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\Section;
use App\Models\Term;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Write operations for the academic domain: course create/edit/delete and
 * faculty course-material management. Reads live in {@see CourseService}.
 */
class CourseManagementService
{
    /**
     * Create a catalog course (admin-owned). Sections (offerings) and faculty
     * assignment are created separately via {@see createSection()}.
     *
     * @param  array<string, mixed>  $data
     */
    public function createCourse(array $data): Course
    {
        return Course::create(array_merge($data, [
            'faculty_id' => $data['faculty_id'] ?? null,
            'schedule' => $this->normalizeSchedule($data['schedule'] ?? null),
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCourse(Course $course, array $data): Course
    {
        $course->update(array_merge($data, [
            'schedule' => $this->normalizeSchedule($data['schedule'] ?? null),
        ]));

        return $course->fresh();
    }

    public function deleteCourse(Course $course): void
    {
        $course->delete();
    }

    /**
     * Create a section (offering) of a course and assign a faculty member.
     *
     * @param  array<string, mixed>  $data
     */
    public function createSection(Course $course, array $data): Section
    {
        return $course->sections()->create([
            'term_id' => $data['term_id'] ?? Term::query()->where('is_current', true)->value('id'),
            'faculty_id' => $data['faculty_id'] ?? null,
            'label' => $this->normalizeLabel($data['label'] ?? null) ?? 'A',
            'schedule' => $this->normalizeSchedule($data['schedule'] ?? null) ?? $course->schedule,
            'max_enrollment' => $data['max_enrollment'] ?? $course->max_enrollment ?? 60,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSection(Section $section, array $data): Section
    {
        $section->update([
            'term_id' => $data['term_id'] ?? $section->term_id,
            'faculty_id' => array_key_exists('faculty_id', $data) ? $data['faculty_id'] : $section->faculty_id,
            'label' => $this->normalizeLabel($data['label'] ?? null) ?? $section->label,
            'max_enrollment' => $data['max_enrollment'] ?? $section->max_enrollment,
            'is_active' => $data['is_active'] ?? $section->is_active,
            'schedule' => array_key_exists('schedule', $data)
                ? $this->normalizeSchedule($data['schedule'])
                : $section->schedule,
        ]);

        return $section->fresh();
    }

    public function deleteSection(Section $section): void
    {
        $section->delete();
    }

    /**
     * Persist an AI-generated quiz/assignment as a published assignment on the
     * course so it appears in the course's assignment list and the grading queue.
     *
     * @param  array<string, mixed>  $data
     */
    public function publishAssignment(Course $course, User $faculty, array $data): Assignment
    {
        return Assignment::create([
            'course_id' => $course->id,
            'section_id' => $course->sectionFor($faculty)?->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'homework',
            'total_points' => $data['total_points'] ?? 100,
            'due_at' => $data['due_at'] ?? null,
            'rubric' => $data['rubric'] ?? null,
            'status' => 'published',
            'created_by' => $faculty->id,
        ]);
    }

    /**
     * Add a material to a course, optionally storing an uploaded file.
     *
     * @param  array<string, mixed>  $data
     */
    public function addMaterial(Course $course, User $faculty, array $data, ?UploadedFile $file = null, ?Section $section = null): CourseMaterial
    {
        $payload = array_merge($this->materialAttributes($data), [
            'course_id' => $course->id,
            // Attach to the given section, or the one this faculty teaches, so the
            // material reaches its own students — never the course's first section
            // by accident.
            'section_id' => ($section ?? $course->sectionFor($faculty))?->id,
            'uploaded_by' => $faculty->id,
        ]);

        if ($file) {
            $payload = array_merge($payload, $this->storeFile($file));
        }

        return CourseMaterial::create($payload);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateMaterial(CourseMaterial $material, array $data, ?UploadedFile $file = null): CourseMaterial
    {
        $payload = $this->materialAttributes($data);

        if ($file) {
            $this->deleteFile($material);
            $payload = array_merge($payload, $this->storeFile($file));
        }

        $material->update($payload);

        return $material->fresh();
    }

    public function deleteMaterial(CourseMaterial $material): void
    {
        $this->deleteFile($material);
        $material->delete();
    }

    public function recordMaterialDownload(CourseMaterial $material): void
    {
        $material->increment('downloads');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function materialAttributes(array $data): array
    {
        return [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'lecture',
            'week' => $data['week'] ?? null,
            'is_published' => $data['is_published'] ?? true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function storeFile(UploadedFile $file): array
    {
        return [
            'file_path' => $file->store('course-materials', 'local'),
            'original_filename' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
        ];
    }

    private function deleteFile(CourseMaterial $material): void
    {
        if ($material->file_path && Storage::disk('local')->exists($material->file_path)) {
            Storage::disk('local')->delete($material->file_path);
        }
    }

    /**
     * Normalize a section label to a trimmed, uppercase value (e.g. " b " → "B")
     * so labels stay consistent regardless of how they were entered.
     */
    private function normalizeLabel(?string $label): ?string
    {
        $label = strtoupper(trim((string) $label));

        return $label === '' ? null : $label;
    }

    /**
     * Normalize the schedule sub-fields into a clean JSON-castable array.
     *
     * @param  array<string, mixed>|null  $schedule
     * @return array<string, mixed>|null
     */
    private function normalizeSchedule(?array $schedule): ?array
    {
        if (! $schedule) {
            return null;
        }

        return array_filter([
            'lectures' => $schedule['lectures'] ?? null,
            'classroom' => $schedule['classroom'] ?? null,
            'office_hours' => $schedule['office_hours'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
