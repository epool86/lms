<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LearningProgressController extends Controller
{
    public function update(Request $request, Course $course, CourseMaterial $material): JsonResponse
    {
        $validated = $request->validate([
            'last_position' => 'nullable|integer|min:0',
            'time_spent_delta' => 'nullable|integer|min:0|max:600',
        ]);

        [$enrollment, $progress] = $this->resolve($course, $material);

        $updates = ['last_accessed_at' => now()];

        if (array_key_exists('last_position', $validated) && $validated['last_position'] !== null) {
            $updates['last_position'] = $validated['last_position'];
        }

        if (! empty($validated['time_spent_delta'])) {
            $updates['time_spent'] = $progress->time_spent + $validated['time_spent_delta'];
        }

        $progress->update($updates);
        $enrollment->update(['last_accessed_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function complete(Course $course, CourseMaterial $material): JsonResponse
    {
        [$enrollment, $progress] = $this->resolve($course, $material);

        $progress->markCompleted();
        $enrollment->recalculateProgress();
        $enrollment->refresh();

        return response()->json([
            'ok' => true,
            'completed' => true,
            'progress_percentage' => $enrollment->progress_percentage,
            'course_completed' => $enrollment->status === 'completed',
        ]);
    }

    /**
     * @return array{0: Enrollment, 1: LessonProgress}
     */
    protected function resolve(Course $course, CourseMaterial $material): array
    {
        if ($material->course_id !== $course->id) {
            abort(404);
        }

        $enrollment = Enrollment::active()
            ->where('user_id', Auth::id())
            ->where('course_id', $course->id)
            ->first();

        if (! $enrollment) {
            abort(403);
        }

        if (! $material->isAccessibleFor($enrollment)) {
            abort(403);
        }

        $progress = LessonProgress::firstOrCreate([
            'enrollment_id' => $enrollment->id,
            'course_material_id' => $material->id,
        ]);

        return [$enrollment, $progress];
    }
}
