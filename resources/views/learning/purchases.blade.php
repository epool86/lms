@extends('layouts.app')

@section('title', 'Purchase History')

@section('content')
@php
    $statusBadges = [
        'paid' => 'bg-success',
        'pending' => 'bg-warning',
        'failed' => 'bg-danger',
        'cancelled' => 'bg-secondary',
        'refunded' => 'bg-info',
    ];
@endphp
<div class="row">
    <!-- Sidebar -->
    <div class="col-lg-3 theiaStickySidebar">
        @include('layouts.partials.sidebar')
    </div>
    <!-- /Sidebar -->

    <!-- Main Content -->
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Purchase History</h4>
            </div>
            <div class="card-body">
                @if($enrollments->isEmpty())
                    <div class="text-center py-5">
                        <i class="isax isax-receipt-1 display-1 text-muted mb-3"></i>
                        <h5 class="mb-2">No purchases yet</h5>
                        <p class="text-muted mb-3">Your enrollment history will appear here.</p>
                        <a href="{{ route('courses.index') }}" class="btn btn-primary">Browse Courses</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Order Ref</th>
                                    <th>Course</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($enrollments as $enrollment)
                                    @php $course = $enrollment->course; @endphp
                                    <tr>
                                        <td><code class="small">{{ $enrollment->order_reference }}</code></td>
                                        <td>
                                            @if($course)
                                                <a href="{{ route('courses.show', $course->slug) }}" class="text-body">{{ $course->title }}</a>
                                                <div class="small text-muted">{{ $course->trainer->name ?? '-' }}</div>
                                            @else
                                                <span class="text-muted">Course removed</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($enrollment->amount > 0)
                                                RM {{ number_format($enrollment->amount, 2) }}
                                            @else
                                                <span class="badge bg-info-transparent text-info">Free</span>
                                            @endif
                                        </td>
                                        <td class="text-capitalize">{{ $enrollment->payment_method }}</td>
                                        <td>
                                            <span class="badge {{ $statusBadges[$enrollment->payment_status] ?? 'bg-secondary' }}">
                                                {{ ucfirst($enrollment->payment_status) }}
                                            </span>
                                        </td>
                                        <td>{{ $enrollment->created_at->format('d M Y') }}</td>
                                        <td class="text-end">
                                            @if($enrollment->payment_status === 'paid' && $course)
                                                <a href="{{ route('app.learn.show', $course->slug) }}" class="btn btn-sm btn-outline-primary">
                                                    Open
                                                </a>
                                            @elseif($enrollment->payment_status === 'pending')
                                                <a href="{{ route('enrollment.pending', $enrollment->order_reference) }}" class="btn btn-sm btn-outline-warning">
                                                    Details
                                                </a>
                                            @elseif($enrollment->payment_status === 'failed')
                                                <a href="{{ route('enrollment.failed', $enrollment->order_reference) }}" class="btn btn-sm btn-outline-danger">
                                                    Details
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <!-- /Main Content -->
</div>
@endsection
