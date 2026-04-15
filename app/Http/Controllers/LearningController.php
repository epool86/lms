<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LearningController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'all');

        $query = Auth::user()->enrollments()
            ->with(['course.trainer', 'course.category'])
            ->where('payment_status', 'paid')
            ->whereIn('status', ['active', 'completed']);

        if ($tab === 'in_progress') {
            $query->where('status', 'active')->where('progress_percentage', '<', 100);
        } elseif ($tab === 'completed') {
            $query->where('status', 'completed');
        }

        $enrollments = $query->orderByDesc('last_accessed_at')->orderByDesc('enrolled_at')->get();

        $counts = [
            'all' => Auth::user()->enrollments()->where('payment_status', 'paid')->whereIn('status', ['active', 'completed'])->count(),
            'in_progress' => Auth::user()->enrollments()->inProgress()->count(),
            'completed' => Auth::user()->enrollments()->completed()->count(),
        ];

        return view('learning.index', compact('enrollments', 'tab', 'counts'));
    }

    /**
     * Full purchase history (including pending / failed).
     */
    public function purchases()
    {
        $enrollments = Auth::user()->enrollments()
            ->with('course.trainer')
            ->orderByDesc('created_at')
            ->get();

        return view('learning.purchases', compact('enrollments'));
    }

    /**
     * Entry point: redirect to last-accessed (or first) material.
     */
    public function show(Course $course)
    {
        $enrollment = $this->currentEnrollment($course);

        $material = $enrollment->lastAccessedMaterial();

        if (! $material) {
            return view('learning.empty', compact('course', 'enrollment'));
        }

        return redirect()->route('app.learn.material', [
            'course' => $course->slug,
            'material' => $material->id,
        ]);
    }

    /**
     * Render the course player for a specific material.
     */
    public function material(Course $course, CourseMaterial $material)
    {
        if ($material->course_id !== $course->id) {
            abort(404);
        }

        $enrollment = $this->currentEnrollment($course);

        if (! $material->isAccessibleFor($enrollment)) {
            abort(403, 'Complete previous materials first.');
        }

        $materials = $course->orderedMaterials()->get();

        $progressMap = $enrollment->progress()
            ->get()
            ->keyBy('course_material_id');

        $progress = LessonProgress::firstOrCreate(
            [
                'enrollment_id' => $enrollment->id,
                'course_material_id' => $material->id,
            ],
            ['last_accessed_at' => now()]
        );

        $progress->update(['last_accessed_at' => now()]);
        $enrollment->update(['last_accessed_at' => now()]);

        // Prev / next based on ordered list
        $ids = $materials->pluck('id')->values();
        $currentIndex = $ids->search($material->id);
        $previousMaterial = $currentIndex > 0 ? $materials[$currentIndex - 1] : null;
        $nextMaterial = $currentIndex !== false && $currentIndex < $materials->count() - 1
            ? $materials[$currentIndex + 1]
            : null;

        return view('learning.player', [
            'course' => $course,
            'material' => $material,
            'materials' => $materials,
            'enrollment' => $enrollment,
            'progressMap' => $progressMap,
            'currentProgress' => $progress,
            'previousMaterial' => $previousMaterial,
            'nextMaterial' => $nextMaterial,
        ]);
    }

    /**
     * Stream a material file for an enrolled student.
     */
    public function stream(Course $course, CourseMaterial $material)
    {
        if ($material->course_id !== $course->id) {
            abort(404);
        }

        $enrollment = $this->currentEnrollment($course);

        if (! $material->isAccessibleFor($enrollment)) {
            abort(403);
        }

        $path = Storage::disk('public')->path($material->file_path);

        if (! file_exists($path)) {
            abort(404, 'File not found.');
        }

        return response()->file($path, [
            'Content-Type' => $material->mime_type,
            'Content-Disposition' => 'inline; filename="' . $material->file_name . '"',
        ]);
    }

    protected function currentEnrollment(Course $course): Enrollment
    {
        $enrollment = Enrollment::active()
            ->where('user_id', Auth::id())
            ->where('course_id', $course->id)
            ->first();

        if (! $enrollment) {
            abort(403, 'You are not enrolled in this course.');
        }

        return $enrollment;
    }
}
