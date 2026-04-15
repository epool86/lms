<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Course::class);

        $query = Course::with(['trainer', 'category'])->withCount('enrollments');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('trainer')) {
            $query->where('user_id', $request->trainer);
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->price_min);
        }

        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->price_max);
        }

        $courses = $query->latest()->paginate(12)->withQueryString();
        $trainers = User::whereJsonContains('roles', 'trainer')->orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('admin.courses.index', compact('courses', 'trainers', 'categories'));
    }

    public function show(Course $course)
    {
        $this->authorize('view', $course);

        $course->load(['trainer', 'category', 'materials']);
        $enrollmentStats = [
            'total' => $course->enrollments()->count(),
            'paid' => $course->enrollments()->where('payment_status', 'paid')->count(),
            'pending' => $course->enrollments()->where('payment_status', 'pending')->count(),
            'revenue' => $course->enrollments()->where('payment_status', 'paid')->sum('amount'),
        ];

        return view('admin.courses.show', compact('course', 'enrollmentStats'));
    }

    public function edit(Course $course)
    {
        $this->authorize('update', $course);

        $categories = Category::orderBy('name')->get();
        $trainers = User::whereJsonContains('roles', 'trainer')->orderBy('name')->get();

        return view('admin.courses.edit', compact('course', 'categories', 'trainers'));
    }

    public function update(Request $request, Course $course)
    {
        $this->authorize('update', $course);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'user_id' => 'required|exists:users,id',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:open,closed',
            'sequential_unlock' => 'nullable|boolean',
        ]);

        $course->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'user_id' => $validated['user_id'],
            'category_id' => $validated['category_id'],
            'price' => $validated['price'],
            'status' => $validated['status'],
            'sequential_unlock' => $request->boolean('sequential_unlock'),
        ]);

        return redirect()->route('app.admin.courses.show', $course)
            ->with('success', 'Course updated successfully.');
    }

    public function publish(Course $course)
    {
        $this->authorize('update', $course);

        $course->update(['status' => 'open']);

        return back()->with('success', 'Course published successfully.');
    }

    public function unpublish(Course $course)
    {
        $this->authorize('update', $course);

        $course->update(['status' => 'closed']);

        return back()->with('success', 'Course unpublished successfully.');
    }

    public function destroy(Course $course)
    {
        $this->authorize('delete', $course);

        $course->delete();

        return redirect()->route('app.admin.courses.index')
            ->with('success', 'Course deleted successfully.');
    }
}
