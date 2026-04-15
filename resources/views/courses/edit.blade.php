@extends('layouts.app')

@section('title', 'Edit Course')

@section('content')
<div class="row">
    <div class="col-lg-3 theiaStickySidebar">
        @include('layouts.partials.sidebar')
    </div>

    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Edit Course</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('app.courses.update', $course) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label for="cover_image" class="form-label">Cover Image</label>
                        <div class="cover-image-upload">
                            <div class="cover-preview-container mb-3" id="coverPreviewContainer" style="{{ $course->cover_image ? '' : 'display: none;' }}">
                                <img id="coverPreview" src="{{ $course->cover_image_url ?? '' }}" alt="Cover Preview" class="img-fluid rounded" style="max-height: 200px; width: 100%; object-fit: cover;">
                                <button type="button" class="btn btn-sm btn-danger mt-2" id="removeCoverBtn">
                                    <i class="isax isax-trash me-1"></i>Remove
                                </button>
                            </div>
                            <input type="hidden" name="remove_cover_image" id="remove_cover_image" value="0">
                            <input type="file"
                                   class="form-control @error('cover_image') is-invalid @enderror"
                                   id="cover_image"
                                   name="cover_image"
                                   accept="image/jpeg,image/png,image/jpg,image/webp">
                            @error('cover_image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Optional. Max 10MB. Recommended size: 1200x675 (16:9 ratio). Supported formats: JPEG, PNG, WebP</small>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="title" class="form-label">Course Title <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('title') is-invalid @enderror"
                               id="title"
                               name="title"
                               value="{{ old('title', $course->title) }}"
                               required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                        <select class="form-select @error('category_id') is-invalid @enderror"
                                id="category_id"
                                name="category_id"
                                required>
                            <option value="">Select a category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ old('category_id', $course->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                                  id="description"
                                  name="description"
                                  rows="5"
                                  required>{{ old('description', $course->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="price" class="form-label">Price (RM) <span class="text-danger">*</span></label>
                        <input type="number"
                               step="0.01"
                               class="form-control @error('price') is-invalid @enderror"
                               id="price"
                               name="price"
                               value="{{ old('price', $course->price) }}"
                               required>
                        @error('price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror"
                                id="status"
                                name="status"
                                required>
                            <option value="open" {{ old('status', $course->status) == 'open' ? 'selected' : '' }}>Open</option>
                            <option value="closed" {{ old('status', $course->status) == 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Open courses are visible to students, closed courses are hidden</small>
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input type="hidden" name="sequential_unlock" value="0">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="sequential_unlock" name="sequential_unlock" value="1"
                                   {{ old('sequential_unlock', $course->sequential_unlock) ? 'checked' : '' }}>
                            <label class="form-check-label" for="sequential_unlock">Sequential Unlock</label>
                        </div>
                        <small class="form-text text-muted">When enabled, students must complete each material in order before unlocking the next one.</small>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('app.courses.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="isax isax-save-2 me-2"></i>Update Course
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const coverInput = document.getElementById('cover_image');
    const previewContainer = document.getElementById('coverPreviewContainer');
    const preview = document.getElementById('coverPreview');
    const removeBtn = document.getElementById('removeCoverBtn');
    const removeCoverInput = document.getElementById('remove_cover_image');
    const hasExistingImage = {{ $course->cover_image ? 'true' : 'false' }};

    coverInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                previewContainer.style.display = 'block';
                removeCoverInput.value = '0';
            };
            reader.readAsDataURL(file);
        }
    });

    removeBtn.addEventListener('click', function() {
        coverInput.value = '';
        preview.src = '';
        previewContainer.style.display = 'none';
        if (hasExistingImage) {
            removeCoverInput.value = '1';
        }
    });
});
</script>
@endpush
