@extends('layouts.app')

@section('title', $course->title)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="isax isax-book-1 display-1 text-muted mb-3"></i>
                <h4 class="mb-2">{{ $course->title }}</h4>
                <p class="text-muted mb-4">This course doesn't have any materials yet. Please check back later.</p>
                <a href="{{ route('app.my-courses.index') }}" class="btn btn-primary">Back to My Courses</a>
            </div>
        </div>
    </div>
</div>
@endsection
