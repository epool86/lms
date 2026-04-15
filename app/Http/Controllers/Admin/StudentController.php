<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $query = User::query()->whereJsonContains('roles', 'student');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'suspended') {
                $query->whereNotNull('suspended_at');
            } elseif ($request->status === 'active') {
                $query->whereNull('suspended_at');
            }
        }

        $students = $query
            ->withCount('enrollments')
            ->withSum([
                'enrollments as total_spent' => function ($enrollmentQuery) {
                    $enrollmentQuery->where('payment_status', 'paid');
                },
            ], 'amount')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.students.index', compact('students'));
    }

    public function show(User $user)
    {
        $this->authorize('view', $user);
        $this->ensureStudent($user);

        $enrollments = Enrollment::with(['course.trainer'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        $totalSpent = Enrollment::where('user_id', $user->id)
            ->where('payment_status', 'paid')
            ->sum('amount');

        return view('admin.students.show', [
            'student' => $user,
            'enrollments' => $enrollments,
            'totalSpent' => $totalSpent,
        ]);
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);
        $this->ensureStudent($user);

        return view('admin.students.edit', ['student' => $user]);
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);
        $this->ensureStudent($user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'is_active' => 'required|in:0,1',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'suspended_at' => $validated['is_active'] === '1' ? null : now(),
        ]);

        return redirect()->route('app.admin.students.show', $user)
            ->with('success', 'Student updated successfully.');
    }

    public function suspend(Request $request, User $user)
    {
        $this->authorize('suspend', $user);
        $this->ensureStudent($user);

        $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $user->update([
            'suspended_at' => now(),
            'suspension_reason' => $request->reason,
        ]);

        return back()->with('success', 'Student suspended successfully.');
    }

    public function restore(User $user)
    {
        $this->authorize('suspend', $user);
        $this->ensureStudent($user);

        $user->update([
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        return back()->with('success', 'Student restored successfully.');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);
        $this->ensureStudent($user);

        $user->delete();

        return redirect()->route('app.admin.students.index')
            ->with('success', 'Student deleted successfully.');
    }

    private function ensureStudent(User $user): void
    {
        if (! in_array('student', $user->roles ?? [])) {
            abort(404);
        }
    }
}
