@extends('layouts.welcome')

@section('title', 'Browse Trainers')

@section('content')
<!-- Breadcrumb -->
<div class="breadcrumb-bar text-center">
    <div class="container">
        <div class="row">
            <div class="col-md-12 col-12">
                <h2 class="breadcrumb-title mb-2">Available Trainers</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb justify-content-center mb-0">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Trainers</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>
<!-- /Breadcrumb -->

<section class="course-content section">
    <div class="container">
        <div class="showing-list mb-4">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h6 class="fw-medium mb-0">
                        Showing {{ $trainers->firstItem() ?? 0 }}-{{ $trainers->lastItem() ?? 0 }} of {{ $trainers->total() }} trainers
                    </h6>
                </div>
                <div class="col-lg-6">
                    <form action="{{ route('trainers.index') }}" method="GET" class="d-flex justify-content-lg-end mt-3 mt-lg-0">
                        <div class="search-group w-100" style="max-width: 360px;">
                            <i class="isax isax-search-normal-1"></i>
                            <input type="text"
                                   class="form-control"
                                   name="search"
                                   placeholder="Search trainer name, email, or company..."
                                   value="{{ request('search') }}">
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row">
            @forelse($trainers as $trainer)
                <div class="col-xl-4 col-md-6 d-flex">
                    <div class="course-item-two course-item mx-0 w-100">
                        <div class="course-content">
                            <div class="d-flex align-items-center mb-3">
                                <span class="avatar avatar-md me-3">
                                    @if($trainer->logo)
                                        <img src="{{ Storage::url($trainer->logo) }}" alt="{{ $trainer->name }}" class="img-fluid rounded-circle">
                                    @else
                                        <img src="{{ asset('assets/img/user/user-29.jpg') }}" alt="{{ $trainer->name }}" class="img-fluid rounded-circle">
                                    @endif
                                </span>
                                <div>
                                    <h6 class="mb-1">{{ $trainer->name }}</h6>
                                    @if($trainer->company_name)
                                        <p class="text-muted fs-13 mb-0">{{ $trainer->company_name }}</p>
                                    @endif
                                </div>
                            </div>

                            <p class="text-muted fs-14 mb-2">
                                <i class="isax isax-sms me-1"></i>{{ $trainer->email }}
                            </p>
                            <p class="text-muted fs-14 mb-3">
                                <i class="isax isax-book me-1"></i>{{ $trainer->open_courses_count }} open courses
                            </p>

                            <a href="{{ route('courses.index', ['trainer' => $trainer->id]) }}"
                               class="btn btn-dark btn-sm d-inline-flex align-items-center">
                                View Courses
                                <i class="isax isax-arrow-right-3 ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="isax isax-teacher fs-1 text-muted mb-3 d-block"></i>
                        <h5>No trainers found</h5>
                        <p class="text-muted">Try adjusting your search keyword.</p>
                        <a href="{{ route('trainers.index') }}" class="btn btn-primary">Clear Search</a>
                    </div>
                </div>
            @endforelse
        </div>

        @if($trainers->hasPages())
            <div class="row align-items-center mt-4">
                <div class="col-md-4">
                    <p class="pagination-text mb-0">Page {{ $trainers->currentPage() }} of {{ $trainers->lastPage() }}</p>
                </div>
                <div class="col-md-8">
                    <ul class="pagination lms-page justify-content-center justify-content-md-end mt-2 mt-md-0 mb-0">
                        @if($trainers->onFirstPage())
                            <li class="page-item prev disabled">
                                <span class="page-link"><i class="fas fa-angle-left"></i></span>
                            </li>
                        @else
                            <li class="page-item prev">
                                <a class="page-link" href="{{ $trainers->previousPageUrl() }}"><i class="fas fa-angle-left"></i></a>
                            </li>
                        @endif

                        @foreach($trainers->getUrlRange(max(1, $trainers->currentPage() - 2), min($trainers->lastPage(), $trainers->currentPage() + 2)) as $page => $url)
                            <li class="page-item {{ $page == $trainers->currentPage() ? 'active' : '' }}">
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endforeach

                        @if($trainers->hasMorePages())
                            <li class="page-item next">
                                <a class="page-link" href="{{ $trainers->nextPageUrl() }}"><i class="fas fa-angle-right"></i></a>
                            </li>
                        @else
                            <li class="page-item next disabled">
                                <span class="page-link"><i class="fas fa-angle-right"></i></span>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
