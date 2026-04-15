@extends('layouts.app')

@section('title', 'Edit Trainer')

@section('content')
<div class="row">
    <div class="col-lg-3 theiaStickySidebar">
        @include('layouts.partials.sidebar')
    </div>

    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Edit Trainer</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('app.admin.trainers.update', $trainer) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $trainer->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $trainer->email) }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Plan</label>
                        <input type="text" name="plan" class="form-control @error('plan') is-invalid @enderror" value="{{ old('plan', $trainer->plan) }}">
                        @error('plan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Validity Date</label>
                        <input type="date" name="validity" class="form-control @error('validity') is-invalid @enderror" value="{{ old('validity', optional($trainer->validity)->format('Y-m-d')) }}">
                        @error('validity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Active Status</label>
                        <select name="is_active" class="form-select @error('is_active') is-invalid @enderror" required>
                            <option value="1" {{ old('is_active', $trainer->suspended_at ? '0' : '1') === '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('is_active', $trainer->suspended_at ? '0' : '1') === '0' ? 'selected' : '' }}>Suspended</option>
                        </select>
                        @error('is_active')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('app.admin.trainers.show', $trainer) }}" class="btn btn-secondary">Cancel</a>
                        <button class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
