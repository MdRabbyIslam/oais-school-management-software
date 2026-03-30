@extends('layouts.app')

@section('title', 'Student Enrollment History')

@section('content_header_title', 'Enrollment Management')
@section('content_header_subtitle', $student->full_name)

@section('content_body')
<div class="container-fluid">
    {{-- Success Message --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    {{-- Error Catching (Uniqueness or Selection) --}}
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <form action="{{ route('enrollments.update-roll', $student->id) }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="card shadow @error('selected_ids') border-danger @enderror">
            <div class="card-header bg-dark d-flex justify-content-between align-items-center">
                <h3 class="card-title text-white">Enrollment History for {{ $student->full_name }}</h3>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-save mr-1"></i> Save Selected Enrollment Settings
                </button>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="{{ $errors->has('selected_ids') ? 'bg-light-danger' : 'thead-light' }}">
                            <tr>
                                <th width="50" class="text-center">
                                    <input type="checkbox" id="select-all">
                                </th>
                                <th>Academic Year</th>
                                <th>Class & Section</th>
                                <th>Roll Number</th>
                                <th>4th Subject</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($student->enrollments as $index => $enrollment)
                            <tr class="{{ is_array(old('selected_ids')) && in_array($enrollment->id, old('selected_ids')) ? 'table-primary' : '' }}">
                                <td class="text-center">
                                    <input type="checkbox"
                                           name="selected_ids[]"
                                           value="{{ $enrollment->id }}"
                                           class="row-checkbox"
                                           {{ is_array(old('selected_ids')) && in_array($enrollment->id, old('selected_ids')) ? 'checked' : '' }}>

                                    <input type="hidden" name="enrollments[{{ $index }}][id]" value="{{ $enrollment->id }}">
                                </td>
                                <td>{{ $enrollment->academicYear->name }}</td>
                                <td>{{ $enrollment->schoolClass->name }} ({{ $enrollment->section->name }})</td>
                                <td>
                                    <input type="text"
                                           name="enrollments[{{ $index }}][roll_number]"
                                           value="{{ old("enrollments.$index.roll_number", $enrollment->roll_number) }}"
                                           class="form-control form-control-sm @error("enrollments.$index.roll_number") is-invalid @enderror">
                                    @error("enrollments.$index.roll_number")
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </td>
                                <td>
                                    @php($fourthSubjectOptions = $fourthSubjectsByClass[$enrollment->class_id] ?? collect())
                                    <select
                                        name="enrollments[{{ $index }}][optional_subject_id]"
                                        class="form-control form-control-sm @error("enrollments.$index.optional_subject_id") is-invalid @enderror"
                                    >
                                        <option value="">None</option>
                                        @foreach($fourthSubjectOptions as $policy)
                                            <option
                                                value="{{ $policy->subject_id }}"
                                                {{ (string) old("enrollments.$index.optional_subject_id", $enrollment->optional_subject_id) === (string) $policy->subject_id ? 'selected' : '' }}
                                            >
                                                {{ $policy->subject->name ?? ('Subject #' . $policy->subject_id) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($fourthSubjectOptions->isEmpty())
                                        <small class="text-muted d-block mt-1">
                                            No 4th-subject options available yet. Set up a grading policy for this class and enable "Can Be Chosen As 4th Subject" first.
                                        </small>
                                    @else
                                        <small class="text-muted d-block mt-1">
                                            Only subjects enabled in grading policy as "Can Be Chosen As 4th Subject" appear here.
                                        </small>
                                    @endif
                                    @error("enrollments.$index.optional_subject_id")
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </td>
                                <td>
                                    <span class="badge badge-{{ $enrollment->status === 'active' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($enrollment->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Simple Script for "Select All" functionality --}}
<script>
    document.getElementById('select-all').onclick = function() {
        var checkboxes = document.querySelectorAll('.row-checkbox');
        for (var checkbox of checkboxes) {
            checkbox.checked = this.checked;
        }
    }
</script>
@endsection
