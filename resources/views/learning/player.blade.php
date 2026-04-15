@extends('layouts.app')

@section('title', $material->title . ' - ' . $course->title)

@section('content')
<div class="row"
     data-learning-player
     data-material-id="{{ $material->id }}"
     data-last-position="{{ $currentProgress->last_position }}"
     data-completed="{{ $currentProgress->completed ? '1' : '0' }}"
     data-progress-url="{{ route('app.learn.progress.update', ['course' => $course->slug, 'material' => $material->id]) }}"
     data-complete-url="{{ route('app.learn.progress.complete', ['course' => $course->slug, 'material' => $material->id]) }}">
    <!-- Materials sidebar -->
    <div class="col-lg-3 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-1 text-truncate">{{ $course->title }}</h6>
                <div class="d-flex justify-content-between small text-muted mt-2 mb-1">
                    <span>Progress</span>
                    <span class="fw-semibold" data-course-progress-label>{{ $enrollment->progress_percentage }}%</span>
                </div>
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar {{ $enrollment->status === 'completed' ? 'bg-success' : 'bg-primary' }}"
                         role="progressbar"
                         data-course-progress-bar
                         style="width: {{ $enrollment->progress_percentage }}%"></div>
                </div>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($materials as $m)
                        @php
                            $p = $progressMap->get($m->id);
                            $isCompleted = $p && $p->completed;
                            $isCurrent = $m->id === $material->id;
                            $isLocked = ! $m->isAccessibleFor($enrollment);
                        @endphp
                        <li class="list-group-item d-flex align-items-center gap-2 {{ $isCurrent ? 'bg-light-primary' : '' }}"
                            data-material-row="{{ $m->id }}">
                            <span class="flex-shrink-0">
                                @if($isCompleted)
                                    <i class="isax isax-tick-circle5 text-success fs-18" data-material-icon></i>
                                @elseif($isLocked)
                                    <i class="isax isax-lock text-muted fs-18" data-material-icon></i>
                                @elseif($m->type === 'video')
                                    <i class="isax isax-play-circle text-primary fs-18" data-material-icon></i>
                                @else
                                    <i class="isax isax-document-text text-primary fs-18" data-material-icon></i>
                                @endif
                            </span>
                            <div class="flex-grow-1 min-w-0">
                                @if($isLocked)
                                    <span class="text-muted d-block text-truncate" title="{{ $m->title }}">{{ $m->title }}</span>
                                    <small class="text-muted">Locked</small>
                                @else
                                    <a href="{{ route('app.learn.material', ['course' => $course->slug, 'material' => $m->id]) }}"
                                       class="d-block text-truncate text-body {{ $isCurrent ? 'fw-semibold' : '' }}"
                                       title="{{ $m->title }}">
                                        {{ $m->title }}
                                    </a>
                                    <small class="text-muted">
                                        {{ ucfirst($m->type) }} &middot; {{ $m->file_size_formatted }}
                                    </small>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="card-footer">
                <a href="{{ route('app.my-courses.index') }}" class="btn btn-sm btn-outline-secondary w-100">
                    <i class="isax isax-arrow-left-2 me-1"></i> Back to My Courses
                </a>
            </div>
        </div>
    </div>
    <!-- /Materials sidebar -->

    <!-- Main viewer -->
    <div class="col-lg-9">
        <div class="card mb-3">
            <div class="card-body p-0">
                @if($material->type === 'video')
                    <video controls
                           class="w-100 d-block bg-dark"
                           style="max-height: 70vh;"
                           src="{{ route('app.learn.stream', ['course' => $course->slug, 'material' => $material->id]) }}">
                        Your browser does not support the video tag.
                    </video>
                @elseif(in_array($material->mime_type, ['application/pdf']))
                    <iframe src="{{ route('app.learn.stream', ['course' => $course->slug, 'material' => $material->id]) }}"
                            class="w-100 border-0"
                            style="height: 70vh;"></iframe>
                @else
                    <div class="text-center py-5">
                        <i class="isax isax-document-text display-1 text-primary mb-3"></i>
                        <h5 class="mb-2">{{ $material->file_name }}</h5>
                        <p class="text-muted mb-3">{{ $material->file_size_formatted }} &middot; {{ $material->mime_type }}</p>
                        <a href="{{ route('app.learn.stream', ['course' => $course->slug, 'material' => $material->id]) }}"
                           target="_blank"
                           class="btn btn-primary">
                            <i class="isax isax-document-download me-1"></i> Open File
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <h4 class="mb-0">{{ $material->title }}</h4>
                    <span class="badge bg-primary-transparent text-primary">{{ ucfirst($material->type) }}</span>
                </div>

                <!-- Action bar -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pt-3 border-top">
                    <div>
                        @if($previousMaterial)
                            <a href="{{ route('app.learn.material', ['course' => $course->slug, 'material' => $previousMaterial->id]) }}"
                               class="btn btn-outline-secondary">
                                <i class="isax isax-arrow-left-2 me-1"></i> Previous
                            </a>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button"
                                class="btn btn-success"
                                data-complete-btn
                                @if($currentProgress->completed) disabled @endif>
                            <i class="isax isax-tick-circle me-1"></i>
                            {{ $currentProgress->completed ? 'Completed' : 'Mark as Complete' }}
                        </button>
                        @if($nextMaterial)
                            <a href="{{ route('app.learn.material', ['course' => $course->slug, 'material' => $nextMaterial->id]) }}"
                               class="btn btn-primary">
                                Next <i class="isax isax-arrow-right-3 ms-1"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Main viewer -->
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/learning-player.js') }}"></script>
@endpush
