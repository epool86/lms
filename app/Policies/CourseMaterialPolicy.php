<?php

namespace App\Policies;

use App\Models\CourseMaterial;
use App\Models\Enrollment;
use App\Models\User;

class CourseMaterialPolicy
{
    public function view(?User $user, CourseMaterial $material): bool
    {
        if ($material->is_preview) {
            return true;
        }

        if (! $user) {
            return false;
        }

        $enrollment = Enrollment::active()
            ->where('user_id', $user->id)
            ->where('course_id', $material->course_id)
            ->first();

        if (! $enrollment) {
            return false;
        }

        return $material->isAccessibleFor($enrollment);
    }
}
