@extends('layouts.app')

@section('subtitle', 'Edit Assignment')
@section('content_header_title', 'Edit Fee Assignment')

@section('content_body')
<div class="card card-primary">
    <form method="POST" action="{{ route('fee-assignments.update', $assignment->id) }}">
        @csrf
        @method('PUT')

        <div class="card-body">
            <!-- Display (non-editable) -->
            <div class="form-group">
                <label>Student</label>
                <input type="text" class="form-control" readonly
                       value="{{ $assignment->student->name }} ({{ $assignment->student->section->schoolClass->name }} - {{ $assignment->student->section->section_name }})">
            </div>

            <div class="form-group">
                <label>Fee Type</label>
                <input type="text" class="form-control" readonly
                       value="{{ $assignment->fee->fee_name }} ({{ ucfirst($assignment->fee->billing_type) }})">
            </div>

            <!-- Editable Fields -->
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="amount">Amount*</label>
                        <input type="number" step="0.01" min="0"
                               name="amount" class="form-control"
                               value="{{ old('amount', $assignment->amount) }}" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="due_date">Due Date*</label>
                        <input type="date" name="due_date" class="form-control"
                               value="{{ old('due_date', $assignment->due_date->format('Y-m-d')) }}" required>
                    </div>
                </div>
            </div>

            <!-- Term Field (conditional) -->
            @if($assignment->fee->billing_type === 'term-based')
            <div class="form-group">
                <label for="term_id">Term*</label>
                <select name="term_id" class="form-control" required>
                    @foreach($terms as $term)
                        <option value="{{ $term->id }}"
                            {{ $assignment->term_id == $term->id ? 'selected' : '' }}>
                            {{ $term->name }} ({{ $term->start_date->format('M d, Y') }} - {{ $term->end_date->format('M d, Y') }})
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <!-- Status -->
            <div class="form-group">
                <label for="status">Status*</label>
                <select name="status" class="form-control" required>
                    <option value="active" {{ $assignment->status === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="completed" {{ $assignment->status === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ $assignment->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <div class="form-group">
                <label for="enrollment_id">Student Enrollment (optional)</label>
                <select name="enrollment_id" class="form-control">
                    <option value="">--</option>
                    @foreach($studentEnrollments as $en)
                        <option value="{{ $en->id }}" {{ $assignment->student_enrollment_id == $en->id ? 'selected' : '' }}>
                            {{ $en->academicYear?->name ?? 'AY '.$en->academic_year_id }} — {{ $en->status }} ({{ optional($en->enrollment_date)->format('Y-m-d') }} → {{ optional($en->completion_date)->format('Y-m-d') ?? 'ongoing' }})
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted">If set, this assignment will be linked to the selected enrollment.</small>
            </div>

            <!-- Audit Fields -->
            <div class="form-group">
                <label for="update_reason">Update Reason</label>
                <textarea name="update_reason" class="form-control"
                          placeholder="Brief reason for modification">{{ old('update_reason') }}</textarea>
                <small class="text-muted">Required for audit purposes when changing amount or due date</small>
            </div>
        </div>

        <div class="card-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Update Assignment
            </button>

            {{-- @if($assignment->payments()->doesntExist())
            <button type="button" class="btn btn-danger float-right"
                    data-toggle="modal" data-target="#deleteModal">
                <i class="fas fa-trash"></i> Delete
            </button>
            @endif --}}
        </div>
    </form>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('fee-assignments.destroy', $assignment->id) }}" method="POST">
                @csrf @method('DELETE')
                <div class="modal-body">
                    <p>Are you sure you want to delete this assignment?</p>
                    <div class="form-group">
                        <label>Deletion Reason*</label>
                        <textarea name="deletion_reason" class="form-control" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>
@stop
