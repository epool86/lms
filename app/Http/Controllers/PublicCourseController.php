<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PublicCourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::with(['trainer', 'category'])
            ->where('status', 'open')
            ->whereHas('trainer', function ($trainerQuery) {
                $trainerQuery->whereNull('suspended_at');
            });

        // Search by title or description
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Filter by trainer
        if ($request->filled('trainer')) {
            $query->where('user_id', $request->trainer);
        }

        // Filter by price (free or paid)
        if ($request->filled('price')) {
            if ($request->price === 'free') {
                $query->where('price', 0);
            } elseif ($request->price === 'paid') {
                $query->where('price', '>', 0);
            }
        }

        // Sort
        $sort = $request->get('sort', 'latest');
        switch ($sort) {
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'title':
                $query->orderBy('title', 'asc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $courses = $query->paginate(9)->withQueryString();
        $categories = Category::withCount(['courses' => function ($q) {
            $q->where('status', 'open')
                ->whereHas('trainer', function ($trainerQuery) {
                    $trainerQuery->whereNull('suspended_at');
                });
        }])->get();
        $trainers = User::whereJsonContains('roles', 'trainer')
            ->whereNull('suspended_at')
            ->withCount(['courses' => function ($q) {
                $q->where('status', 'open');
            }])
            ->having('courses_count', '>', 0)
            ->get();

        return view('public.courses.index', compact('courses', 'categories', 'trainers'));
    }

    public function show(Course $course)
    {
        if (! $this->isPubliclyAvailableCourse($course)) {
            abort(404);
        }

        $course->load(['trainer', 'category', 'materials']);

        return view('public.courses.show', compact('course'));
    }

    /**
     * Stream cover image for public course (full size or thumbnail)
     */
    public function coverImage(Course $course, $type = 'thumbnail')
    {
        if (! $this->isPubliclyAvailableCourse($course)) {
            abort(404);
        }

        $path = $type === 'cover' ? $course->cover_image : $course->cover_image_thumbnail;

        if (!$path) {
            abort(404, 'Image not found.');
        }

        $fullPath = Storage::disk('public')->path($path);

        if (!file_exists($fullPath)) {
            abort(404, 'Image not found.');
        }

        return response()->file($fullPath, [
            'Content-Type' => 'image/jpeg',
        ]);
    }

    /**
     * Show public material for a course
     */
    public function showMaterial(Course $course, CourseMaterial $material)
    {
        if (! $this->isPubliclyAvailableCourse($course)) {
            abort(404);
        }

        // Check if material belongs to this course
        if ($material->course_id !== $course->id) {
            abort(404);
        }

        // Only show public materials
        if ($material->visibility !== 'public') {
            abort(403, 'This material is not publicly available.');
        }

        return view('public.courses.material', compact('course', 'material'));
    }

    /**
     * Stream public material for a course
     */
    public function streamMaterial(Course $course, CourseMaterial $material)
    {
        if (! $this->isPubliclyAvailableCourse($course)) {
            abort(404);
        }

        // Check if material belongs to this course
        if ($material->course_id !== $course->id) {
            abort(404);
        }

        // Only stream public materials
        if ($material->visibility !== 'public') {
            abort(403, 'This material is not publicly available.');
        }

        $path = Storage::disk('public')->path($material->file_path);

        if (!file_exists($path)) {
            abort(404, 'File not found.');
        }

        return response()->file($path, [
            'Content-Type' => $material->mime_type,
            'Content-Disposition' => 'inline; filename="' . $material->file_name . '"',
        ]);
    }

    private function isPubliclyAvailableCourse(Course $course): bool
    {
        if ($course->status !== 'open') {
            return false;
        }

        $course->loadMissing('trainer');

        if (! $course->trainer) {
            return false;
        }

        return is_null($course->trainer->suspended_at);
    }
}
