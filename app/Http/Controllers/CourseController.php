<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Course;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class CourseController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $courses = Course::where('user_id', $user->id)
            ->with('category')
            ->latest()
            ->paginate(10);

        // Get user's plan limits
        $plan = Plan::where('name', $user->plan)->first();
        $courseLimit = $plan->course_limit ?? 0;
        $currentCourseCount = Course::where('user_id', $user->id)->count();

        return view('courses.index', compact('courses', 'courseLimit', 'currentCourseCount'));
    }

    public function create()
    {
        $user = Auth::user();

        // Check course limit
        $plan = Plan::where('name', $user->plan)->first();
        $courseLimit = $plan->course_limit ?? 0;
        $currentCourseCount = Course::where('user_id', $user->id)->count();

        if ($currentCourseCount >= $courseLimit) {
            return redirect()->route('app.courses.index')
                ->with('error', 'You have reached your course limit. Please upgrade your plan.');
        }

        $categories = Category::all();

        return view('courses.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Check course limit
        $plan = Plan::where('name', $user->plan)->first();
        $courseLimit = $plan->course_limit ?? 0;
        $currentCourseCount = Course::where('user_id', $user->id)->count();

        if ($currentCourseCount >= $courseLimit) {
            return redirect()->route('app.courses.index')
                ->with('error', 'You have reached your course limit. Please upgrade your plan.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:open,closed',
            'sequential_unlock' => 'nullable|boolean',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240'
        ]);

        $courseData = [
            'user_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'price' => $request->price,
            'status' => $request->status,
            'sequential_unlock' => $request->boolean('sequential_unlock'),
        ];

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            $imagePaths = $this->processCoverImage($request->file('cover_image'), $user->id);
            $courseData['cover_image'] = $imagePaths['cover'];
            $courseData['cover_image_thumbnail'] = $imagePaths['thumbnail'];
        }

        Course::create($courseData);

        return redirect()->route('app.courses.index')
            ->with('success', 'Course created successfully!');
    }

    public function show(Course $course)
    {
        // Check if user owns this course
        if ($course->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        return view('courses.show', compact('course'));
    }

    public function edit(Course $course)
    {
        // Check if user owns this course
        if ($course->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $categories = Category::all();

        return view('courses.edit', compact('course', 'categories'));
    }

    public function update(Request $request, Course $course)
    {
        // Check if user owns this course
        if ($course->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:open,closed',
            'sequential_unlock' => 'nullable|boolean',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240'
        ]);

        $courseData = $request->only([
            'title',
            'description',
            'category_id',
            'price',
            'status'
        ]);
        $courseData['sequential_unlock'] = $request->boolean('sequential_unlock');

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            // Delete old images if they exist
            $this->deleteCoverImages($course);

            $imagePaths = $this->processCoverImage($request->file('cover_image'), Auth::id());
            $courseData['cover_image'] = $imagePaths['cover'];
            $courseData['cover_image_thumbnail'] = $imagePaths['thumbnail'];
        }

        // Handle cover image removal
        if ($request->has('remove_cover_image') && $request->remove_cover_image) {
            $this->deleteCoverImages($course);
            $courseData['cover_image'] = null;
            $courseData['cover_image_thumbnail'] = null;
        }

        $course->update($courseData);

        return redirect()->route('app.courses.index')
            ->with('success', 'Course updated successfully!');
    }

    public function destroy(Course $course)
    {
        // Check if user owns this course
        if ($course->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Delete cover images
        $this->deleteCoverImages($course);

        $course->delete();

        return redirect()->route('app.courses.index')
            ->with('success', 'Course deleted successfully!');
    }

    /**
     * Process and resize cover image
     * Creates a full-size image (1200x675) and thumbnail (400x225)
     */
    private function processCoverImage($file, $userId)
    {
        $filename = uniqid() . '_' . time();
        $directory = 'course-covers/' . $userId;

        // Ensure directory exists
        Storage::disk('public')->makeDirectory($directory);

        // Process full-size image (1200x675 - 16:9 ratio)
        $coverImage = Image::read($file);
        $coverImage->cover(1200, 675);
        $coverPath = $directory . '/' . $filename . '_cover.jpg';
        Storage::disk('public')->put($coverPath, $coverImage->toJpeg(85));

        // Process thumbnail (400x225 - 16:9 ratio)
        $thumbnail = Image::read($file);
        $thumbnail->cover(400, 225);
        $thumbnailPath = $directory . '/' . $filename . '_thumb.jpg';
        Storage::disk('public')->put($thumbnailPath, $thumbnail->toJpeg(80));

        return [
            'cover' => $coverPath,
            'thumbnail' => $thumbnailPath
        ];
    }

    /**
     * Delete cover images from storage
     */
    private function deleteCoverImages(Course $course)
    {
        if ($course->cover_image) {
            Storage::disk('public')->delete($course->cover_image);
        }
        if ($course->cover_image_thumbnail) {
            Storage::disk('public')->delete($course->cover_image_thumbnail);
        }
    }

    /**
     * Stream cover image (full size or thumbnail)
     */
    public function coverImage(Course $course, $type = 'thumbnail')
    {
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
}
