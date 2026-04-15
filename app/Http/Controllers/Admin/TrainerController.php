<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class TrainerController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $query = User::query()->whereJsonContains('roles', 'trainer');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('plan')) {
            $query->where('plan', $request->plan);
        }

        if ($request->filled('status')) {
            if ($request->status === 'suspended') {
                $query->whereNotNull('suspended_at');
            } elseif ($request->status === 'active') {
                $query->whereNull('suspended_at');
            }
        }

        $trainers = $query
            ->with(['courses' => function ($courseQuery) {
                $courseQuery->withCount('enrollments');
            }])
            ->withCount('courses')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $plans = User::whereJsonContains('roles', 'trainer')
            ->whereNotNull('plan')
            ->distinct()
            ->pluck('plan');

        return view('admin.trainers.index', compact('trainers', 'plans'));
    }

    public function show(User $user)
    {
        $this->authorize('view', $user);
        $this->ensureTrainer($user);

        $user->load([
            'courses.category',
            'courses' => function ($courseQuery) {
                $courseQuery->withCount('enrollments');
            },
        ]);

        $totalStudents = $user->courses->sum('enrollments_count');
        $totalRevenue = $user->courses->sum(function ($course) {
            return $course->enrollments()->where('payment_status', 'paid')->sum('amount');
        });

        return view('admin.trainers.show', [
            'trainer' => $user,
            'totalStudents' => $totalStudents,
            'totalRevenue' => $totalRevenue,
        ]);
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);
        $this->ensureTrainer($user);

        return view('admin.trainers.edit', ['trainer' => $user]);
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);
        $this->ensureTrainer($user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'plan' => 'nullable|string|max:255',
            'validity' => 'nullable|date',
            'is_active' => 'required|in:0,1',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'plan' => $validated['plan'] ?: $user->plan,
            'validity' => $validated['validity'],
            'suspended_at' => $validated['is_active'] === '1' ? null : now(),
        ]);

        return redirect()->route('app.admin.trainers.show', $user)
            ->with('success', 'Trainer updated successfully.');
    }

    public function suspend(Request $request, User $user)
    {
        $this->authorize('suspend', $user);
        $this->ensureTrainer($user);

        $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $user->update([
            'suspended_at' => now(),
            'suspension_reason' => $request->reason,
        ]);

        return back()->with('success', 'Trainer suspended successfully.');
    }

    public function restore(User $user)
    {
        $this->authorize('suspend', $user);
        $this->ensureTrainer($user);

        $user->update([
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        return back()->with('success', 'Trainer restored successfully.');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);
        $this->ensureTrainer($user);

        $user->delete();

        return redirect()->route('app.admin.trainers.index')
            ->with('success', 'Trainer deleted successfully.');
    }

    private function ensureTrainer(User $user): void
    {
        if (! in_array('trainer', $user->roles ?? [])) {
            abort(404);
        }
    }
}
