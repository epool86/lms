@extends('layouts.app')

@section('title', 'Manage Trainers')

@section('content')
<div class="row">
    <div class="col-lg-3 theiaStickySidebar">
        @include('layouts.partials.sidebar')
    </div>

    <div class="col-lg-9">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Manage Trainers</h5>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Search name/email" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="plan" class="form-select">
                            <option value="">All plans</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan }}" {{ request('plan') === $plan ? 'selected' : '' }}>{{ $plan }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">All status</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Plan</th>
                                <th># Courses</th>
                                <th># Students</th>
                                <th>Joined</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($trainers as $trainer)
                                <tr>
                                    <td>{{ $trainer->name }}</td>
                                    <td>{{ $trainer->email }}</td>
                                    <td>{{ $trainer->plan ?? '-' }}</td>
                                    <td>{{ $trainer->courses_count }}</td>
                                    <td>{{ $trainer->courses->sum('enrollments_count') }}</td>
                                    <td>{{ $trainer->created_at->format('d M Y') }}</td>
                                    <td>
                                        <span class="badge {{ $trainer->suspended_at ? 'bg-danger' : 'bg-success' }}">
                                            {{ $trainer->suspended_at ? 'Suspended' : 'Active' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('app.admin.trainers.show', $trainer) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        <a href="{{ route('app.admin.trainers.edit', $trainer) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No trainers found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $trainers->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
