@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<!-- Profile Box -->
<div class="profile-card overflow-hidden bg-blue-gradient2 mb-4 p-5">
    <div class="profile-card-bg">
        <img src="{{ asset('assets/img/bg/card-bg-01.png') }}" class="profile-card-bg-1" alt="">
    </div>
    <div class="row align-items-center row-gap-3">
        <div class="col-lg-6">
            <div class="d-flex align-items-center">
                <span class="avatar avatar-xxl avatar-rounded me-3 border border-white border-2 position-relative">
                    <img src="{{ asset('assets/img/user/user-02.jpg') }}" alt="">
                    <span class="verify-tick"><i class="isax isax-verify5"></i></span>
                </span>
                <div>
                    <h5 class="mb-1 text-white d-inline-flex align-items-center">
                        <a href="javascript:void(0);" class="text-white">{{ Auth::user()->name }}</a>
                        <a href="{{ route('app.profile.edit') }}" class="link-light fs-16 ms-2"><i class="isax isax-edit-2"></i></a>
                    </h5>
                    <p class="text-light">Student</p>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="d-flex align-items-center justify-content-lg-end flex-wrap gap-2">
                <a href="javascript:void(0);" class="btn btn-white rounded-pill me-3">Become Trainer</a>
            </div>
        </div>
    </div>
</div>
<!-- /Profile Box -->

<div class="row">
    <!-- Sidebar -->
    <div class="col-lg-3 theiaStickySidebar">
        @include('layouts.partials.sidebar')
    </div>
    <!-- /Sidebar -->

    <!-- Main Content -->
    <div class="col-lg-9">
        <!-- Dashboard Stats -->
        <div class="row mb-4">
            <div class="col-xl-3 col-sm-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-lg bg-secondary-transparent rounded-circle me-3">
                                <i class="isax isax-book-15 fs-24 text-secondary"></i>
                            </div>
                            <div>
                                <h6 class="mb-1 fs-14 fw-normal text-gray-9">Enrolled Courses</h6>
                                <h3 class="mb-0">{{ $enrolledCount ?? 0 }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-lg bg-warning-transparent rounded-circle me-3">
                                <i class="isax isax-medal-star5 fs-24 text-warning"></i>
                            </div>
                            <div>
                                <h6 class="mb-1 fs-14 fw-normal text-gray-9">In Progress</h6>
                                <h3 class="mb-0">{{ $inProgressCount ?? 0 }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-lg bg-success-transparent rounded-circle me-3">
                                <i class="isax isax-award5 fs-24 text-success"></i>
                            </div>
                            <div>
                                <h6 class="mb-1 fs-14 fw-normal text-gray-9">Completed</h6>
                                <h3 class="mb-0">{{ $completedCount ?? 0 }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-lg bg-info-transparent rounded-circle me-3">
                                <i class="isax isax-note-215 fs-24 text-info"></i>
                            </div>
                            <div>
                                <h6 class="mb-1 fs-14 fw-normal text-gray-9">Certificates</h6>
                                <h3 class="mb-0">0</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Dashboard Stats -->

        <!-- Continue Learning -->
        @if(!empty($continueLearning) && $continueLearning->count() > 0)
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Continue Learning</h5>
                <a href="{{ route('app.my-courses.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($continueLearning as $enrollment)
                        @php $course = $enrollment->course; @endphp
                        <div class="col-md-4 d-flex mb-3">
                            <div class="card flex-fill border">
                                @if($course->cover_image)
                                    <img src="{{ route('courses.cover', ['course' => $course->id, 'type' => 'thumbnail']) }}"
                                         class="card-img-top" alt="{{ $course->title }}"
                                         style="height: 140px; object-fit: cover;">
                                @else
                                    <div class="card-img-top bg-secondary-transparent d-flex align-items-center justify-content-center" style="height: 140px;">
                                        <i class="isax isax-book-1 display-4 text-secondary"></i>
                                    </div>
                                @endif
                                <div class="card-body d-flex flex-column">
                                    <h6 class="mb-2 text-truncate" title="{{ $course->title }}">{{ $course->title }}</h6>
                                    <p class="text-muted small mb-2">
                                        <i class="isax isax-teacher me-1"></i>{{ $course->trainer->name ?? '-' }}
                                    </p>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between small mb-1">
                                            <span class="text-muted">Progress</span>
                                            <span class="fw-semibold">{{ $enrollment->progress_percentage }}%</span>
                                        </div>
                                        <div class="progress" style="height: 5px;">
                                            <div class="progress-bar bg-primary"
                                                 style="width: {{ $enrollment->progress_percentage }}%"></div>
                                        </div>
                                    </div>
                                    <a href="{{ route('app.learn.show', $course->slug) }}" class="btn btn-sm btn-primary mt-auto">
                                        Continue <i class="isax isax-arrow-right-3 ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
        <!-- /Continue Learning -->

        <!-- Welcome Message -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="mb-0">Welcome to Your Dashboard!</h5>
                </div>
                <p class="mb-3">Hello <strong>{{ Auth::user()->name }}</strong>, you're logged in successfully!</p>
                <div class="alert alert-info mb-0">
                    <i class="isax isax-info-circle me-2"></i>
                    Start exploring courses and enhance your learning experience with AzbahriLMS.
                </div>
            </div>
        </div>
        <!-- /Welcome Message -->

    </div>
    <!-- /Main Content -->
</div>
@endsection

@push('scripts')
<script>
    // Initialize circle progress if needed
    if (typeof initCircleProgress !== 'undefined') {
        initCircleProgress();
    }
</script>
@endpush
