<?php

namespace App\Policies;

use App\Domain\User\Models\User;
use App\Models\Course;

class CoursePolicy
{
    /**
     * View a course: admins, any faculty teaching one of its sections (or the
     * legacy owning faculty), or an enrolled student.
     */
    public function view(User $user, Course $course): bool
    {
        return $user->isAdmin()
            || $this->teaches($user, $course)
            || $course->students()->whereKey($user->id)->exists();
    }

    /**
     * Manage (grade, add materials, mark attendance): admins or a faculty member
     * teaching one of the course's sections.
     */
    public function manage(User $user, Course $course): bool
    {
        return $user->isAdmin() || $this->teaches($user, $course);
    }

    /**
     * Whether the user teaches this course — via an assigned section, or the
     * legacy course.faculty_id owner (retained for backward compatibility).
     */
    private function teaches(User $user, Course $course): bool
    {
        return $course->faculty_id === $user->id
            || $course->sections()->where('faculty_id', $user->id)->exists();
    }
}
