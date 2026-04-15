@extends('layouts.app')

@section('title', 'Enrollment Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <a href="{{ route('app.trainer.enrollments.index') }}" class="text-muted text-decoration-none">
                        <i class="isax isax-arrow-left me-1"></i>Back to Enrollments
                    </a>
                    <h4 class="mb-0 mt-2">Enrollment Details</h4>
                </div>
                @if($enrollment->payment_status === 'pending' && $enrollment->payment_method === 'manual')
                <div class="btn-group">
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                        <i class="isax isax-tick-circle me-1"></i>Approve
                    </button>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="isax isax-close-circle me-1"></i>Reject
                    </button>
                </div>
                @endif
            </div>

            <div class="row">
                <!-- Main Info -->
                <div class="col-lg-8">
                    <!-- Order Info -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Order Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="text-muted" width="150">Order Reference</td>
                                            <td><code>{{ $enrollment->order_reference }}</code></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Amount</td>
                                            <td>
                                                @if($enrollment->amount == 0)
                                                <span class="text-success fw-bold">Free</span>
                                                @else
                                                <span class="fw-bold">RM{{ number_format($enrollment->amount, 2) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Payment Method</td>
                                            <td>
                                                @if($enrollment->payment_method === 'chip')
                                                <span class="badge bg-primary">Online Payment (CHIP)</span>
                                                @else
                                                <span class="badge bg-secondary">Bank Transfer</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="text-muted" width="150">Payment Status</td>
                                            <td>
                                                @switch($enrollment->payment_status)
                                                    @case('paid')
                                                        <span class="badge bg-success fs-6">Paid</span>
                                                        @break
                                                    @case('pending')
                                                        <span class="badge bg-warning text-dark fs-6">Pending</span>
                                                        @break
                                                    @case('failed')
                                                        <span class="badge bg-danger fs-6">Failed</span>
                                                        @break
                                                    @case('cancelled')
                                                        <span class="badge bg-secondary fs-6">Cancelled</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-light text-dark fs-6">{{ $enrollment->payment_status }}</span>
                                                @endswitch
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Enrollment Status</td>
                                            <td>
                                                @if($enrollment->status === 'active')
                                                <span class="badge bg-success">Active</span>
                                                @else
                                                <span class="badge bg-secondary">{{ ucfirst($enrollment->status) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Created</td>
                                            <td>{{ $enrollment->created_at->format('d M Y, h:i A') }}</td>
                                        </tr>
                                        @if($enrollment->enrolled_at)
                                        <tr>
                                            <td class="text-muted">Enrolled</td>
                                            <td>{{ $enrollment->enrolled_at->format('d M Y, h:i A') }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Student Info -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Student Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                        <span class="text-white fw-bold fs-4">{{ substr($enrollment->user->name ?? 'N', 0, 1) }}</span>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <h5 class="mb-1">{{ $enrollment->user->name ?? 'N/A' }}</h5>
                                    <p class="text-muted mb-0">{{ $enrollment->user->email ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Course Info -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Course Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-shrink-0" style="width: 120px;">
                                    @if($enrollment->course->cover_image_thumbnail)
                                    <img src="{{ route('app.courses.cover', ['course' => $enrollment->course->id, 'type' => 'thumbnail']) }}" alt="{{ $enrollment->course->title }}" class="img-fluid rounded">
                                    @else
                                    <img src="{{ asset('assets/img/course/course-01.jpg') }}" alt="{{ $enrollment->course->title }}" class="img-fluid rounded">
                                    @endif
                                </div>
                                <div class="ms-3">
                                    <h5 class="mb-1">{{ $enrollment->course->title }}</h5>
                                    <p class="text-muted mb-2">
                                        Price:
                                        @if($enrollment->course->price == 0)
                                        <span class="text-success">Free</span>
                                        @else
                                        RM{{ number_format($enrollment->course->price, 2) }}
                                        @endif
                                    </p>
                                    <a href="{{ route('app.courses.show', $enrollment->course) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="isax isax-eye me-1"></i>View Course
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Data -->
                    @if($enrollment->payment_data)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Payment Data</h5>
                        </div>
                        <div class="card-body">
                            <pre class="bg-light p-3 rounded mb-0" style="max-height: 300px; overflow: auto;"><code>{{ json_encode($enrollment->payment_data, JSON_PRETTY_PRINT) }}</code></pre>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Payment Proof -->
                    @if($enrollment->payment_proof)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Payment Proof</h5>
                        </div>
                        <div class="card-body text-center">
                            <a href="{{ Storage::url($enrollment->payment_proof) }}" target="_blank">
                                <img src="{{ Storage::url($enrollment->payment_proof) }}" alt="Payment Proof" class="img-fluid rounded mb-3" style="max-height: 400px;">
                            </a>
                            <div>
                                <a href="{{ Storage::url($enrollment->payment_proof) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                    <i class="isax isax-export me-1"></i>View Full Size
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- CHIP Payment Info -->
                    @if($enrollment->chip_purchase_id)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">CHIP Payment</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted">Purchase ID</td>
                                    <td><code class="small">{{ $enrollment->chip_purchase_id }}</code></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    @endif

                    <!-- Actions -->
                    @if($enrollment->payment_status === 'pending' && $enrollment->payment_method === 'manual')
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Actions</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">This enrollment is awaiting payment verification.</p>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                                    <i class="isax isax-tick-circle me-1"></i>Approve Payment
                                </button>
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                    <i class="isax isax-close-circle me-1"></i>Reject Payment
                                </button>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if($enrollment->payment_status === 'pending' && $enrollment->payment_method === 'manual')
<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to approve this payment?</p>
                <p class="text-muted">The student will be granted access to the course immediately.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('app.trainer.enrollments.approve', $enrollment) }}" method="POST" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success">
                        <i class="isax isax-tick-circle me-1"></i>Approve Payment
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('app.trainer.enrollments.reject', $enrollment) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title">Reject Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to reject this payment?</p>
                    <div class="mb-3">
                        <label class="form-label">Reason for Rejection (Optional)</label>
                        <textarea name="rejection_reason" class="form-control" rows="3" placeholder="e.g., Payment proof unclear, amount mismatch..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="isax isax-close-circle me-1"></i>Reject Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
