@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="row">
    <div class="col-lg-3 theiaStickySidebar">
        @include('layouts.partials.sidebar')
    </div>

    <div class="col-lg-9">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Revenue Report</h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('app.admin.reports.export', ['section' => 'revenue', 'format' => 'csv']) }}" class="btn btn-sm btn-outline-primary">Export CSV</a>
                    <a href="{{ route('app.admin.reports.export', ['section' => 'revenue', 'format' => 'pdf']) }}" class="btn btn-sm btn-outline-danger">Export PDF</a>
                </div>
            </div>
            <div class="card-body">
                <p class="mb-3"><strong>Total Revenue:</strong> RM {{ number_format($revenue['total'], 2) }}</p>
                <canvas id="revenueByMonthChart" height="100"></canvas>
                <hr>
                <h6>Top 10 Trainers</h6>
                <ul class="mb-0">
                    @forelse($revenue['by_trainer'] as $row)
                        <li>{{ $row->name }} - RM {{ number_format($row->total_revenue, 2) }}</li>
                    @empty
                        <li class="text-muted">No data available.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Enrollment Report</h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('app.admin.reports.export', ['section' => 'enrollments', 'format' => 'csv']) }}" class="btn btn-sm btn-outline-primary">Export CSV</a>
                    <a href="{{ route('app.admin.reports.export', ['section' => 'enrollments', 'format' => 'pdf']) }}" class="btn btn-sm btn-outline-danger">Export PDF</a>
                </div>
            </div>
            <div class="card-body">
                <p><strong>Total Enrollments:</strong> {{ $enrollments['total'] }}</p>
                <h6>By Status</h6>
                <ul>
                    @forelse($enrollments['by_status'] as $row)
                        <li>{{ ucfirst($row->status) }} - {{ $row->total }}</li>
                    @empty
                        <li class="text-muted">No data available.</li>
                    @endforelse
                </ul>
                <h6>Top 10 Courses</h6>
                <ul class="mb-0">
                    @forelse($enrollments['by_course'] as $row)
                        <li>{{ $row->title }} - {{ $row->total }} enrollments</li>
                    @empty
                        <li class="text-muted">No data available.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">User Report</h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('app.admin.reports.export', ['section' => 'users', 'format' => 'csv']) }}" class="btn btn-sm btn-outline-primary">Export CSV</a>
                    <a href="{{ route('app.admin.reports.export', ['section' => 'users', 'format' => 'pdf']) }}" class="btn btn-sm btn-outline-danger">Export PDF</a>
                </div>
            </div>
            <div class="card-body">
                <p><strong>Total Users:</strong> {{ $users['total'] }}</p>
                <p><strong>Active vs Inactive:</strong> {{ $users['active'] }} active / {{ $users['inactive'] }} inactive</p>
                <canvas id="userGrowthChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const revenueByMonthLabels = @json(collect($revenue['by_month'])->pluck('label'));
const revenueByMonthData = @json(collect($revenue['by_month'])->pluck('total'));
const userGrowthLabels = @json(collect($users['growth'])->pluck('label'));
const userGrowthData = @json(collect($users['growth'])->pluck('total'));

const revenueByMonthChart = document.getElementById('revenueByMonthChart');
if (revenueByMonthChart) {
    new Chart(revenueByMonthChart, {
        type: 'bar',
        data: {
            labels: revenueByMonthLabels,
            datasets: [{
                label: 'Revenue (RM)',
                data: revenueByMonthData,
                borderWidth: 1
            }]
        }
    });
}

const userGrowthChart = document.getElementById('userGrowthChart');
if (userGrowthChart) {
    new Chart(userGrowthChart, {
        type: 'line',
        data: {
            labels: userGrowthLabels,
            datasets: [{
                label: 'New Users',
                data: userGrowthData,
                borderWidth: 2
            }]
        }
    });
}
</script>
@endpush
