@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="row">
    <div class="col-lg-3 theiaStickySidebar">
        @include('layouts.partials.sidebar')
    </div>

    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Admin Dashboard</h4>
            <span class="badge bg-primary">Overview</span>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <p class="mb-1 text-muted">Total Users</p>
                        <h3 class="mb-2">{{ $totalUsers }}</h3>
                        <small class="text-muted">Admin: {{ $totalAdmins }} | Trainer: {{ $totalTrainers }} | Student: {{ $totalStudents }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <p class="mb-1 text-muted">Total Courses</p>
                        <h3 class="mb-2">{{ $totalCourses }}</h3>
                        <small class="text-muted">Published: {{ $publishedCourses }} | Draft: {{ $draftCourses }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <p class="mb-1 text-muted">Total Enrollments</p>
                        <h3 class="mb-2">{{ $totalEnrollments }}</h3>
                        <small class="text-muted">Paid: {{ $paidEnrollments }} | Pending: {{ $pendingEnrollments }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <p class="mb-1 text-muted">Total Revenue</p>
                        <h3 class="mb-2">RM {{ number_format($totalRevenue, 2) }}</h3>
                        <small class="text-muted">From paid enrollments</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Revenue (Last 30 Days)</h5>
            </div>
            <div class="card-body">
                <canvas id="adminRevenueChart" height="100"></canvas>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Recent Enrollments</h5>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Order Ref</th>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Status</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentEnrollments as $enrollment)
                            <tr>
                                <td>{{ $enrollment->order_reference }}</td>
                                <td>{{ $enrollment->student_name }}</td>
                                <td>{{ $enrollment->course->title ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $enrollment->payment_status === 'paid' ? 'bg-success' : 'bg-warning' }}">
                                        {{ ucfirst($enrollment->payment_status) }}
                                    </span>
                                </td>
                                <td>RM {{ number_format($enrollment->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No enrollments yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Recent Users</h5>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Roles</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentUsers as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ implode(', ', $user->roles ?? []) }}</td>
                                <td>{{ $user->created_at->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No users yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const adminRevenueCtx = document.getElementById('adminRevenueChart');
if (adminRevenueCtx) {
    new Chart(adminRevenueCtx, {
        type: 'bar',
        data: {
            labels: @json($chartDates),
            datasets: [{
                label: 'Revenue (RM)',
                data: @json($chartRevenue),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}
</script>
@endpush
