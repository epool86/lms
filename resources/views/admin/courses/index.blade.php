@extends('layouts.app')

@section('title', 'Manage Courses')

@section('content')
<div class="row">
    <div class="col-lg-3 theiaStickySidebar">
        @include('layouts.partials.sidebar')
    </div>

    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Manage Courses</h5>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Search title/description" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <select name="trainer" class="form-select">
                            <option value="">Trainer</option>
                            @foreach($trainers as $trainer)
                                <option value="{{ $trainer->id }}" {{ (string) request('trainer') === (string) $trainer->id ? 'selected' : '' }}>{{ $trainer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="category" class="form-select">
                            <option value="">Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ (string) request('category') === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="">Status</option>
                            <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                            <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <input type="number" step="0.01" min="0" name="price_min" class="form-control" placeholder="Min" value="{{ request('price_min') }}">
                    </div>
                    <div class="col-md-1">
                        <input type="number" step="0.01" min="0" name="price_max" class="form-control" placeholder="Max" value="{{ request('price_max') }}">
                    </div>
                    <div class="col-md-12">
                        <button class="btn btn-primary">Filter</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Trainer</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th># Enrollments</th>
                                <th>Created</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($courses as $course)
                                <tr>
                                    <td>{{ $course->title }}</td>
                                    <td>{{ $course->trainer->name ?? '-' }}</td>
                                    <td>{{ $course->category->name ?? '-' }}</td>
                                    <td>RM {{ number_format($course->price, 2) }}</td>
                                    <td>
                                        <span class="badge {{ $course->status === 'open' ? 'bg-success' : 'bg-secondary' }}">
                                            {{ ucfirst($course->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $course->enrollments_count }}</td>
                                    <td>{{ $course->created_at->format('d M Y') }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('app.admin.courses.show', $course) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        <a href="{{ route('app.admin.courses.edit', $course) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No courses found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $courses->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
