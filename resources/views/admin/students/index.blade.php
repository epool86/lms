@extends('layouts.app')

@section('title', 'Manage Students')

@section('content')
<div class="row">
    <div class="col-lg-3 theiaStickySidebar">
        @include('layouts.partials.sidebar')
    </div>

    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Manage Students</h5>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-5">
                        <input type="text" name="search" class="form-control" placeholder="Search name/email" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-4">
                        <select name="status" class="form-select">
                            <option value="">All status</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th># Enrollments</th>
                                <th>Total Spent</th>
                                <th>Joined</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                                <tr>
                                    <td>{{ $student->name }}</td>
                                    <td>{{ $student->email }}</td>
                                    <td>{{ $student->enrollments_count }}</td>
                                    <td>RM {{ number_format($student->total_spent ?? 0, 2) }}</td>
                                    <td>{{ $student->created_at->format('d M Y') }}</td>
                                    <td>
                                        <span class="badge {{ $student->suspended_at ? 'bg-danger' : 'bg-success' }}">
                                            {{ $student->suspended_at ? 'Suspended' : 'Active' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('app.admin.students.show', $student) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        <a href="{{ route('app.admin.students.edit', $student) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No students found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $students->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
