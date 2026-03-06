@extends('layouts.app')

@section('subtitle', 'New Fee Assignment')
@section('content_header_title', 'New Fee Assignment')

@section('content_body')
<div class="card card-primary">
    <form method="POST" action="{{ route('fee-assignments.store') }}">
        @csrf

        <div class="card-body">
            <!-- Assignment Scope -->
            <div class="form-group">
                <label>Assign To*</label>
                <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                    <label class="btn btn-outline-primary active">
                        <input type="radio" name="scope" value="student" checked> Single Student
                    </label>
                    <label class="btn btn-outline-primary">
                        <input type="radio" name="scope" value="class"> Entire Class
                    </label>
                    <label class="btn btn-outline-primary">
                        <input type="radio" name="scope" value="section"> Section
                    </label>
                </div>
            </div>

            <!-- Dynamic Fields Based on Scope -->
            <div id="student-fields">
                <div class="form-group">
                    <label for="student_id">Student*</label>
                    <select name="student_id" id="student_id" class="form-control select2">
                        <option value="">Select Student</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}">
                                {{ $student->name }} ({{ $student->class->name }} - {{ $student->section->name }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div id="class-fields" style="display:none">
                <div class="form-group">
                    <label for="class_id">Class*</label>
                    <select name="class_id" id="class_id" class="form-control">
                        <option value="">Select Class</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div id="section-fields" style="display:none">
                <div class="form-group">
                    <label for="section_id">Section*</label>
                    <select name="section_id" id="section_id" class="form-control">
                        <option value="">Select Section</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->id }}">
                                {{ $section->class->name }} - {{ $section->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Fee Selection -->
            <div class="form-group">
                <label for="fee_id">Fee Type*</label>
                <select name="fee_id" id="fee_id" class="form-control" required>
                    <option value="">Select Fee</option>
                    @foreach($fees as $fee)
                        <option value="{{ $fee->id }}"
                            data-type="{{ $fee->billing_type }}"
                            data-mandatory="{{ $fee->is_mandatory ? '1' : '0' }}">
                            {{ $fee->fee_name }} ({{ ucfirst($fee->billing_type) }})
                            {{ $fee->is_mandatory ? '(Mandatory)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Term Selection (Conditional) -->
            <div class="form-group" id="term-group" style="display:none">
                <label for="term_id">Term*</label>
                <select name="term_id" id="term_id" class="form-control">
                    <option value="">Select Term</option>
                    @foreach($terms as $term)
                        <option value="{{ $term->id }}">
                            {{ $term->name }} ({{ $term->start_date->format('M d, Y') }} - {{ $term->end_date->format('M d, Y') }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Amount Field -->
            <div class="form-group">
                <label for="amount">Amount*</label>
                <div class="input-group">
                    <input type="number" step="0.01" min="0"
                           name="amount" id="amount"
                           class="form-control" required>
                    <button type="button" class="btn btn-outline-secondary"
                            id="fetch-amount-btn">
                        Fetch Class Amount
                    </button>
                </div>
                <small class="text-muted" id="amount-help"></small>
            </div>

            <!-- Due Date -->
            <div class="form-group">
                <label for="due_date">Due Date*</label>
                <input type="date" name="due_date" id="due_date"
                       class="form-control" required>
            </div>
        </div>

        <div class="card-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Assignment
            </button>
        </div>
    </form>
</div>

@push('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@push('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2();

    // Scope toggle
    $('input[name="scope"]').change(function() {
        $('#student-fields, #class-fields, #section-fields').hide();
        $('#' + this.value + '-fields').show();
    });

    // Term field visibility based on fee type
    $('#fee_id').change(function() {
        const feeType = $(this).find(':selected').data('type');
        $('#term-group').toggle(feeType === 'term-based');
    });

    // Fetch class amount
    $('#fetch-amount-btn').click(function() {
        const studentId = $('#student_id').val();
        const feeId = $('#fee_id').val();

        if (!studentId || !feeId) {
            alert('Please select both student and fee first');
            return;
        }

        $.get(`/api/students/${studentId}/class-fee/${feeId}`, function(data) {
            if (data.amount) {
                $('#amount').val(data.amount);
                $('#amount-help').text(`Standard amount for ${data.class_name}`);
            } else {
                $('#amount-help').text('No standard amount set for this class');
            }
        });
    });
});
</script>
@endpush
@stop
