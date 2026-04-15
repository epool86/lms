@extends('layouts.app')

@section('title', 'My Courses')

@section('content')
<div class="row">
    <!-- Sidebar -->
    <div class="col-lg-3 theiaStickySidebar">
        @include('layouts.partials.sidebar')
    </div>
    <!-- /Sidebar -->

    <!-- Main Content -->
    <div class="col-lg-9">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
                    <h4 class="mb-0">My Courses</h4>
                    <a href="{{ route('courses.index') }}" class="btn btn-outline-primary">
                        <i class="isax isax-search-normal-1 me-1"></i> Browse Courses
                    </a>
                </div>

                <!-- Tabs -->
                <ul class="nav nav-pills mb-4 flex-wrap gap-2">
                    <li class="nav-item">
                        <a href="{{ route('app.my-courses.index') }}" class="nav-link {{ $tab === 'all' ? 'active' : '' }}">
                            All <span class="badge bg-light text-dark ms-1">{{ $counts['all'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('app.my-courses.index', ['tab' => 'in_progress']) }}" class="nav-link {{ $tab === 'in_progress' ? 'active' : '' }}">
                            In Progress <span class="badge bg-light text-dark ms-1">{{ $counts['in_progress'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('app.my-courses.index', ['tab' => 'completed']) }}" class="nav-link {{ $tab === 'completed' ? 'active' : '' }}">
                            Completed <span class="badge bg-light text-dark ms-1">{{ $counts['completed'] }}</span>
                        </a>
                    </li>
                </ul>

                @if($enrollments->isEmpty())
                    <!-- Empty state -->
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="isax isax-book-1 display-1 text-muted"></i>
                        </div>
                        <h5 class="mb-2">No courses yet</h5>
                        <p class="text-muted mb-3">
                            @if($tab === 'completed')
                                You haven't completed any courses yet. Keep learning!
                            @elseif($tab === 'in_progress')
                                You don't have any in-progress courses.
                            @else
                                Start your learning journey by enrolling in a course.
                            @endif
                        </p>
                        <a href="{{ route('courses.index') }}" class="btn btn-primary">Browse Courses</a>
                    </div>
                @else
                    <!-- Course cards -->
                    <div class="row">
                        @foreach($enrollments as $enrollment)
                            @php $course = $enrollment->course; @endphp
                            <div class="col-xl-4 col-md-6 d-flex mb-4">
                                <div class="card flex-fill w-100">
                                    <div class="position-relative">
                                        @if($course->cover_image)
                                            <img src="{{ route('courses.cover', ['course' => $course->id, 'type' => 'thumbnail']) }}"
                                                 class="card-img-top" alt="{{ $course->title }}"
                                                 style="height: 180px; object-fit: cover;">
                                        @else
                                            <div class="card-img-top bg-secondary-transparent d-flex align-items-center justify-content-center" style="height: 180px;">
                                                <i class="isax isax-book-1 display-3 text-secondary"></i>
                                            </div>
                                        @endif
                                        @if($enrollment->status === 'completed')
                                            <span class="badge bg-success position-absolute top-0 end-0 m-2">
                                                <i class="isax isax-tick-circle me-1"></i>Completed
                                            </span>
                                        @endif
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <h6 class="mb-2">
                                            <a href="{{ route('courses.show', $course->slug) }}" class="text-body">
                                                {{ $course->title }}
                                            </a>
                                        </h6>
                                        <p class="text-muted small mb-3">
                                            <i class="isax isax-teacher me-1"></i>
                                            {{ $course->trainer->name ?? 'Unknown Trainer' }}
                                        </p>

                                        <!-- Progress bar -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between small mb-1">
                                                <span class="text-muted">Progress</span>
                                                <span class="fw-semibold">{{ $enrollment->progress_percentage }}%</span>
                                            </div>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar {{ $enrollment->status === 'completed' ? 'bg-success' : 'bg-primary' }}"
                                                     role="progressbar"
                                                     style="width: {{ $enrollment->progress_percentage }}%"
                                                     aria-valuenow="{{ $enrollment->progress_percentage }}"
                                                     aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                        <div class="mt-auto">
                                            @if($enrollment->last_accessed_at)
                                                <p class="text-muted small mb-2">
                                                    <i class="isax isax-clock me-1"></i>
                                                    Last accessed {{ $enrollment->last_accessed_at->diffForHumans() }}
                                                </p>
                                            @endif
                                            <a href="{{ route('app.learn.show', $course->slug) }}" class="btn btn-primary w-100">
                                                {{ $enrollment->progress_percentage > 0 ? 'Continue Learning' : 'Start Learning' }}
                                                <i class="isax isax-arrow-right-3 ms-1"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
    <!-- /Main Content -->
</div>
@endsection
