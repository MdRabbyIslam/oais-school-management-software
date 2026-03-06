@extends('layouts.app')

@section('title', 'Promotion Request')

@section('content_header_title', 'Promotion Request')
@section('content_header_subtitle', 'Detail')

@section('content_body')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Promotion Request #{{ $promotion->id }}</h3>
        <a href="{{ route('students.edit', $promotion->student->id) }}" class="btn btn-sm btn-secondary">Back to Student</a>
    </div>

    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row">
            <div class="col-md-6">
                <h5>Student</h5>
                <p><strong>Name:</strong> {{ $promotion->student->name }}</p>
                <p><strong>Student ID:</strong> {{ $promotion->student->student_id }}</p>
                <p><strong>Current Section:</strong> {{ $promotion->fromEnrollment->section->section_name ?? '-' }} ({{ $promotion->fromEnrollment->schoolClass->name ?? '-' }})</p>
                <p><strong>From Enrollment ID:</strong> {{ $promotion->from_enrollment_id }}</p>
            </div>

            <div class="col-md-6">
                <h5>Target</h5>
                <p><strong>Academic Year:</strong> {{ $promotion->targetAcademicYear->name ?? '-' }}</p>
                <p><strong>Class:</strong> {{ $promotion->targetClass->name ?? '-' }}</p>
                <p><strong>Section:</strong> {{ $promotion->targetSection->section_name ?? '-' }}</p>
                <p><strong>Requested By:</strong> {{ $promotion->requestedBy->name ?? 'System' }} at {{ optional($promotion->requested_at)->toDateTimeString() }}</p>
            </div>
        </div>

        <hr />

        <div class="mb-3">
            <h5>Reason</h5>
            <p>{{ $promotion->reason ?? '-' }}</p>
        </div>

        <div class="mb-3">
            <h5>Status</h5>
            <p>{{ ucfirst($promotion->status) }}</p>
        </div>

        @if($promotion->meta)
            <div class="mb-3">
                <h5>Meta</h5>
                <pre>{{ json_encode($promotion->meta, JSON_PRETTY_PRINT) }}</pre>
            </div>
        @endif

        @if($promotion->status === 'pending')
            <div class="row">
                <div class="col-md-6">
                    <div class="card card-outline card-success">
                        <div class="card-header"><strong>Approve</strong></div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('promotions.approve', $promotion->id) }}">
                                @csrf
                                <div class="form-group">
                                    <label>New Roll Number (optional)</label>
                                    <input type="number" name="roll_number" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Enrollment Date (optional)</label>
                                    <input type="date" name="enrollment_date" class="form-control">
                                </div>
                                <button class="btn btn-success">Approve</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card card-outline card-danger">
                        <div class="card-header"><strong>Reject</strong></div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('promotions.reject', $promotion->id) }}">
                                @csrf
                                <div class="form-group">
                                    <label>Reason (optional)</label>
                                    <textarea name="reason" class="form-control"></textarea>
                                </div>
                                <button class="btn btn-danger">Reject</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>
@stop
