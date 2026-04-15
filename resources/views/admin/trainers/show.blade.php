@extends('layouts.app')

@section('title', 'Trainer Details')

@section('content')
<div class="row">
    <div class="col-lg-3 theiaStickySidebar">
        @include('layouts.partials.sidebar')
    </div>

    <div class="col-lg-9">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Trainer Details</h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('app.admin.trainers.edit', $trainer) }}" class="btn btn-sm btn-primary">Edit</a>
                    <a href="{{ route('app.admin.trainers.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
                </div>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <div class="row mb-4">
                    <div class="col-md-6">
                        <p class="mb-1 text-muted">Name</p>
                        <p class="fw-semibold">{{ $trainer->name }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1 text-muted">Email</p>
                        <p class="fw-semibold">{{ $trainer->email }}</p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1 text-muted">Plan</p>
                        <p class="fw-semibold">{{ $trainer->plan ?? '-' }}</p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1 text-muted">Total Courses</p>
                        <p class="fw-semibold">{{ $trainer->courses->count() }}</p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1 text-muted">Total Students</p>
                        <p class="fw-semibold">{{ $totalStudents }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1 text-muted">Total Revenue</p>
                        <p class="fw-semibold">RM {{ number_format($totalRevenue, 2) }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1 text-muted">Status</p>
                        <p>
                            <span class="badge {{ $trainer->suspended_at ? 'bg-danger' : 'bg-success' }}">
                                {{ $trainer->suspended_at ? 'Suspended' : 'Active' }}
                            </span>
                        </p>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mb-4">
                    @if($trainer->suspended_at)
                        <form method="POST" action="{{ route('app.admin.trainers.restore', $trainer) }}">
                            @csrf
                            <button class="btn btn-success">Restore Trainer</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('app.admin.trainers.suspend', $trainer) }}" class="d-flex gap-2">
                            @csrf
                            <input type="text" name="reason" class="form-control" placeholder="Suspend reason (optional)">
                            <button class="btn btn-warning">Suspend Trainer</button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('app.admin.trainers.destroy', $trainer) }}" onsubmit="return confirm('Delete this trainer?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger">Delete Trainer</button>
                    </form>
                </div>

                <h6 class="mb-3">Trainer Courses</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Price</th>
                                <th>Enrollments</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($trainer->courses as $course)
                                <tr>
                                    <td>{{ $course->title }}</td>
                                    <td>{{ $course->category->name ?? '-' }}</td>
                                    <td>{{ ucfirst($course->status) }}</td>
                                    <td>RM {{ number_format($course->price, 2) }}</td>
                                    <td>{{ $course->enrollments_count }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('app.admin.courses.show', $course) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No courses found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
