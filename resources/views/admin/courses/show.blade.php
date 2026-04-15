@extends('layouts.app')

@section('title', 'Course Details')

@section('content')
<div class="row">
    <div class="col-lg-3 theiaStickySidebar">
        @include('layouts.partials.sidebar')
    </div>

    <div class="col-lg-9">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Course Details</h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('app.admin.courses.edit', $course) }}" class="btn btn-sm btn-primary">Edit</a>
                    <a href="{{ route('app.admin.courses.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
                </div>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <div class="row mb-4">
                    <div class="col-md-8">
                        <h5>{{ $course->title }}</h5>
                        <p class="text-muted mb-2">{{ $course->description }}</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <p class="mb-1"><strong>Price:</strong> RM {{ number_format($course->price, 2) }}</p>
                        <p class="mb-1"><strong>Status:</strong> {{ ucfirst($course->status) }}</p>
                        <p class="mb-0"><strong>Sequential Unlock:</strong> {{ $course->sequential_unlock ? 'Yes' : 'No' }}</p>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <p class="mb-1 text-muted">Total Enrollments</p>
                            <h4 class="mb-0">{{ $enrollmentStats['total'] }}</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <p class="mb-1 text-muted">Paid Enrollments</p>
                            <h4 class="mb-0">{{ $enrollmentStats['paid'] }}</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <p class="mb-1 text-muted">Revenue</p>
                            <h4 class="mb-0">RM {{ number_format($enrollmentStats['revenue'], 2) }}</h4>
                        </div>
                    </div>
                </div>

                <div class="mb-4 d-flex flex-wrap gap-2">
                    @if($course->status === 'open')
                        <form method="POST" action="{{ route('app.admin.courses.unpublish', $course) }}">
                            @csrf
                            <button class="btn btn-warning">Unpublish Course</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('app.admin.courses.publish', $course) }}">
                            @csrf
                            <button class="btn btn-success">Publish Course</button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('app.admin.courses.destroy', $course) }}" onsubmit="return confirm('Delete this course?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger">Delete Course</button>
                    </form>
                </div>

                <h6 class="mb-3">Course Materials</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Visibility</th>
                                <th>Order</th>
                                <th>Preview</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($course->materials as $material)
                                <tr>
                                    <td>{{ $material->title }}</td>
                                    <td>{{ strtoupper($material->type) }}</td>
                                    <td>{{ ucfirst($material->visibility) }}</td>
                                    <td>{{ $material->order }}</td>
                                    <td>{{ $material->is_preview ? 'Yes' : 'No' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No materials available.</td>
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
