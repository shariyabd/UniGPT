<?php

namespace App\Http\Controllers\Faculty;

use App\Domain\Academic\Services\CourseManagementService;
use App\Domain\Notification\Services\NotificationService;
use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Faculty\StoreMaterialRequest;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CourseMaterialController extends Controller
{
    public function __construct(
        private readonly CourseManagementService $management,
        private readonly ActivityLogger $activity,
        private readonly NotificationService $notifications,
    ) {}

    public function store(StoreMaterialRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $material = $this->management->addMaterial(
            $course,
            $request->user(),
            $request->safe()->except(['file', 'section_id']),
            $request->file('file'),
            $this->targetSection($course, $request),
        );

        $this->activity->log('material.created', "Added material to {$course->code}", $material, [], $request->user());

        // Notify the students in this material's section (not every section of
        // the course) when it is published — drafts notify no one.
        if ($material->is_published) {
            $this->notifications->notifyMany(
                users: $this->sectionStudents($material),
                type: NotificationType::MATERIAL,
                title: "New material in {$course->code}",
                message: "\"{$material->title}\" is now available.",
                link: route('materials'),
                data: ['course_id' => $course->id, 'section_id' => $material->section_id, 'material_id' => $material->id],
            );
        }

        return back()->with('success', 'Material added.');
    }

    public function update(StoreMaterialRequest $request, Course $course, CourseMaterial $material): RedirectResponse
    {
        Gate::authorize('manage', $course);
        $this->ensureBelongsTo($course, $material);

        $this->management->updateMaterial(
            $material,
            $request->safe()->except('file'),
            $request->file('file'),
        );

        // Notify this section's students when a published material changes.
        if ($material->fresh()->is_published) {
            $this->notifications->notifyMany(
                users: $this->sectionStudents($material),
                type: NotificationType::MATERIAL,
                title: "Material updated in {$course->code}",
                message: "\"{$material->title}\" was updated.",
                link: route('materials'),
                data: ['course_id' => $course->id, 'section_id' => $material->section_id, 'material_id' => $material->id],
            );
        }

        return back()->with('success', 'Material updated.');
    }

    public function destroy(Course $course, CourseMaterial $material): RedirectResponse
    {
        Gate::authorize('manage', $course);
        $this->ensureBelongsTo($course, $material);

        $this->management->deleteMaterial($material);

        return back()->with('success', 'Material deleted.');
    }

    public function download(Course $course, CourseMaterial $material): StreamedResponse
    {
        Gate::authorize('view', $course);
        $this->ensureBelongsTo($course, $material);

        abort_unless($material->file_path !== null, 404);

        $this->management->recordMaterialDownload($material);

        return Storage::disk('local')->download(
            $material->file_path,
            $material->original_filename ?? $material->title,
        );
    }

    private function ensureBelongsTo(Course $course, CourseMaterial $material): void
    {
        abort_unless($material->course_id === $course->id, 404);
    }

    /**
     * The section to attach a new material to: the requested section when the
     * faculty teaches it for this course, otherwise their default section. This
     * lets a faculty who teaches several sections of one course target the
     * correct one, while never allowing another section to be addressed.
     */
    private function targetSection(Course $course, \Illuminate\Http\Request $request): ?\App\Models\Section
    {
        $sectionId = $request->integer('section_id') ?: null;

        if ($sectionId !== null) {
            $section = $course->sections()
                ->where('id', $sectionId)
                ->where('faculty_id', $request->user()->id)
                ->first();

            if ($section !== null) {
                return $section;
            }
        }

        return $course->sectionFor($request->user());
    }

    /**
     * The actively-enrolled students of the material's section — the precise
     * recipients for a material notification.
     *
     * @return \Illuminate\Support\Collection<int, \App\Domain\User\Models\User>
     */
    private function sectionStudents(CourseMaterial $material): \Illuminate\Support\Collection
    {
        return $material->section
            ? $material->section->students()->wherePivot('status', 'enrolled')->get()
            : collect();
    }
}
