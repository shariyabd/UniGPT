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
            $request->safe()->except('file'),
            $request->file('file'),
        );

        $this->activity->log('material.created', "Added material to {$course->code}", $material, [], $request->user());

        // Notify enrolled students when a material is published (not for drafts).
        if ($material->is_published) {
            $this->notifications->notifyMany(
                users: $course->students()->get(),
                type: NotificationType::MATERIAL,
                title: "New material in {$course->code}",
                message: "\"{$material->title}\" is now available.",
                link: route('materials'),
                data: ['course_id' => $course->id, 'material_id' => $material->id],
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
}
