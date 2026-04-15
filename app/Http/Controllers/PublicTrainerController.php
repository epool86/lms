<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class PublicTrainerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()
            ->whereJsonContains('roles', 'trainer')
            ->whereNull('suspended_at')
            ->whereHas('courses', function ($courseQuery) {
                $courseQuery->where('status', 'open');
            })
            ->withCount([
                'courses as open_courses_count' => function ($courseQuery) {
                    $courseQuery->where('status', 'open');
                },
            ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('company_name', 'like', '%' . $search . '%');
            });
        }

        $trainers = $query
            ->orderByDesc('open_courses_count')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('public.trainers.index', compact('trainers'));
    }
}
