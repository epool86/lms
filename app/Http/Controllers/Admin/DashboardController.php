<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $totalStudents = User::whereJsonContains('roles', 'student')->count();
        $totalTrainers = User::whereJsonContains('roles', 'trainer')->count();
        $totalAdmins = User::whereJsonContains('roles', 'admin')->count();

        $totalCourses = Course::count();
        $publishedCourses = Course::where('status', 'open')->count();
        $draftCourses = Course::where('status', 'closed')->count();

        $totalEnrollments = Enrollment::count();
        $paidEnrollments = Enrollment::where('payment_status', 'paid')->count();
        $pendingEnrollments = Enrollment::where('payment_status', 'pending')->count();
        $totalRevenue = Enrollment::where('payment_status', 'paid')->sum('amount');

        $recentEnrollments = Enrollment::with(['course.trainer', 'user'])
            ->latest()
            ->limit(10)
            ->get();

        $recentUsers = User::latest()
            ->limit(10)
            ->get();

        $chartDates = [];
        $chartRevenue = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $chartDates[] = $date->format('d M');
            $chartRevenue[] = (float) Enrollment::where('payment_status', 'paid')
                ->whereDate('updated_at', $date->toDateString())
                ->sum('amount');
        }

        return view('admin.dashboard', compact(
            'totalUsers',
            'totalStudents',
            'totalTrainers',
            'totalAdmins',
            'totalCourses',
            'publishedCourses',
            'draftCourses',
            'totalEnrollments',
            'paidEnrollments',
            'pendingEnrollments',
            'totalRevenue',
            'recentEnrollments',
            'recentUsers',
            'chartDates',
            'chartRevenue'
        ));
    }
}
