<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array('admin', $user->roles ?? []) || in_array('trainer', $user->roles ?? []);
    }

    public function view(User $user, Course $course): bool
    {
        if (in_array('admin', $user->roles ?? [])) {
            return true;
        }

        return $course->user_id === $user->id;
    }

    public function update(User $user, Course $course): bool
    {
        return $this->view($user, $course);
    }

    public function delete(User $user, Course $course): bool
    {
        return $this->view($user, $course);
    }
}
