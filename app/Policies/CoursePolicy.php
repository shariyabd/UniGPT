<?php

namespace App\Policies;

use App\Domain\User\Models\User;
use App\Models\Course;

class CoursePolicy
{
    /**
     * View a course: admins, the owning faculty, or an enrolled student.
     */
    public function view(User $user, Course $course): bool
    {
        return $user->isAdmin()
            || $course->faculty_id === $user->id
            || $course->students()->whereKey($user->id)->exists();
    }

    /**
     * Manage (edit, grade, add materials): admins or the owning faculty.
     */
    public function manage(User $user, Course $course): bool
    {
        return $user->isAdmin() || $course->faculty_id === $user->id;
    }
}
