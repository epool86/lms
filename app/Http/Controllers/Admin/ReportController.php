<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index()
    {
        $revenue = $this->getRevenueData();
        $enrollments = $this->getEnrollmentData();
        $users = $this->getUserData();

        return view('admin.reports.index', compact('revenue', 'enrollments', 'users'));
    }

    public function export(string $section, string $format)
    {
        if (! in_array($section, ['revenue', 'enrollments', 'users'])) {
            abort(404);
        }

        if (! in_array($format, ['csv', 'pdf'])) {
            abort(404);
        }

        if ($section === 'revenue') {
            $data = $this->getRevenueData();
        } elseif ($section === 'enrollments') {
            $data = $this->getEnrollmentData();
        } else {
            $data = $this->getUserData();
        }

        if ($format === 'csv') {
            return $this->downloadCsv($section, $data);
        }

        return $this->downloadPdf($section, $data);
    }

    private function getRevenueData(): array
    {
        $total = Enrollment::where('payment_status', 'paid')->sum('amount');

        $byTrainer = Enrollment::query()
            ->selectRaw('users.id, users.name, SUM(enrollments.amount) as total_revenue')
            ->join('courses', 'courses.id', '=', 'enrollments.course_id')
            ->join('users', 'users.id', '=', 'courses.user_id')
            ->where('enrollments.payment_status', 'paid')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        $byMonth = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $byMonth[] = [
                'label' => $month->format('M Y'),
                'total' => (float) Enrollment::where('payment_status', 'paid')
                    ->whereYear('updated_at', $month->year)
                    ->whereMonth('updated_at', $month->month)
                    ->sum('amount'),
            ];
        }

        $byPaymentMethod = Enrollment::query()
            ->selectRaw('payment_method, SUM(amount) as total_revenue')
            ->where('payment_status', 'paid')
            ->groupBy('payment_method')
            ->orderByDesc('total_revenue')
            ->get();

        return [
            'total' => $total,
            'by_trainer' => $byTrainer,
            'by_month' => $byMonth,
            'by_payment_method' => $byPaymentMethod,
        ];
    }

    private function getEnrollmentData(): array
    {
        $total = Enrollment::count();

        $byStatus = Enrollment::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get();

        $byCourse = Enrollment::query()
            ->selectRaw('courses.id, courses.title, COUNT(enrollments.id) as total')
            ->join('courses', 'courses.id', '=', 'enrollments.course_id')
            ->groupBy('courses.id', 'courses.title')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $byMonth = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $byMonth[] = [
                'label' => $month->format('M Y'),
                'total' => Enrollment::whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count(),
            ];
        }

        return [
            'total' => $total,
            'by_status' => $byStatus,
            'by_course' => $byCourse,
            'by_month' => $byMonth,
        ];
    }

    private function getUserData(): array
    {
        $growth = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $growth[] = [
                'label' => $month->format('M Y'),
                'total' => User::whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count(),
            ];
        }

        return [
            'total' => User::count(),
            'active' => User::whereNull('suspended_at')->count(),
            'inactive' => User::whereNotNull('suspended_at')->count(),
            'growth' => $growth,
        ];
    }

    private function downloadCsv(string $section, array $data): StreamedResponse
    {
        $filename = $section . '-report-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($section, $data) {
            $file = fopen('php://output', 'w');

            if ($section === 'revenue') {
                fputcsv($file, ['Metric', 'Value']);
                fputcsv($file, ['Total Revenue', $data['total']]);
                fputcsv($file, []);
                fputcsv($file, ['Top Trainers', 'Revenue']);
                foreach ($data['by_trainer'] as $row) {
                    fputcsv($file, [$row->name, $row->total_revenue]);
                }
            } elseif ($section === 'enrollments') {
                fputcsv($file, ['Metric', 'Value']);
                fputcsv($file, ['Total Enrollments', $data['total']]);
                fputcsv($file, []);
                fputcsv($file, ['Status', 'Total']);
                foreach ($data['by_status'] as $row) {
                    fputcsv($file, [$row->status, $row->total]);
                }
                fputcsv($file, []);
                fputcsv($file, ['Course', 'Total']);
                foreach ($data['by_course'] as $row) {
                    fputcsv($file, [$row->title, $row->total]);
                }
            } else {
                fputcsv($file, ['Metric', 'Value']);
                fputcsv($file, ['Total Users', $data['total']]);
                fputcsv($file, ['Active Users', $data['active']]);
                fputcsv($file, ['Inactive Users', $data['inactive']]);
                fputcsv($file, []);
                fputcsv($file, ['Month', 'New Users']);
                foreach ($data['growth'] as $row) {
                    fputcsv($file, [$row['label'], $row['total']]);
                }
            }

            fclose($file);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function downloadPdf(string $section, array $data)
    {
        $pdf = Pdf::loadView('admin.reports.pdf', [
            'section' => $section,
            'data' => $data,
        ]);

        return $pdf->download($section . '-report-' . now()->format('Ymd-His') . '.pdf');
    }
}
