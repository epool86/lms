@extends('layouts.app')

@section('title', 'Student Details')

@section('content')
<div class="row">
    <div class="col-lg-3 theiaStickySidebar">
        @include('layouts.partials.sidebar')
    </div>

    <div class="col-lg-9">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Student Details</h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('app.admin.students.edit', $student) }}" class="btn btn-sm btn-primary">Edit</a>
                    <a href="{{ route('app.admin.students.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
                </div>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <div class="row mb-4">
                    <div class="col-md-6">
                        <p class="mb-1 text-muted">Name</p>
                        <p class="fw-semibold">{{ $student->name }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1 text-muted">Email</p>
                        <p class="fw-semibold">{{ $student->email }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1 text-muted">Total Spent</p>
                        <p class="fw-semibold">RM {{ number_format($totalSpent, 2) }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1 text-muted">Status</p>
                        <p>
                            <span class="badge {{ $student->suspended_at ? 'bg-danger' : 'bg-success' }}">
                                {{ $student->suspended_at ? 'Suspended' : 'Active' }}
                            </span>
                        </p>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mb-4">
                    @if($student->suspended_at)
                        <form method="POST" action="{{ route('app.admin.students.restore', $student) }}">
                            @csrf
                            <button class="btn btn-success">Restore Student</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('app.admin.students.suspend', $student) }}" class="d-flex gap-2">
                            @csrf
                            <input type="text" name="reason" class="form-control" placeholder="Suspend reason (optional)">
                            <button class="btn btn-warning">Suspend Student</button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('app.admin.students.destroy', $student) }}" onsubmit="return confirm('Delete this student?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger">Delete Student</button>
                    </form>
                </div>

                <h6 class="mb-3">Enrollment History</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Order Ref</th>
                                <th>Course</th>
                                <th>Trainer</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Amount</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($enrollments as $enrollment)
                                <tr>
                                    <td>{{ $enrollment->order_reference }}</td>
                                    <td>{{ $enrollment->course->title ?? '-' }}</td>
                                    <td>{{ $enrollment->course->trainer->name ?? '-' }}</td>
                                    <td>{{ ucfirst($enrollment->status) }}</td>
                                    <td>{{ ucfirst($enrollment->payment_status) }}</td>
                                    <td>RM {{ number_format($enrollment->amount, 2) }}</td>
                                    <td>{{ $enrollment->created_at->format('d M Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No enrollment history found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $enrollments->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
