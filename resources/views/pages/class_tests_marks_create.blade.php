@extends('layouts.app')

@section('title', 'Class Test Marks Entry')
@section('content_header_title', 'Class Test Marks Entry')
@section('content_header_subtitle', $classTest->name)

@section('content_body')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Enter Marks</h3>
        <div>
            <span class="badge badge-light mr-1">{{ $classTest->academicYear->name ?? '-' }}</span>
            <span class="badge badge-light mr-1">{{ $classTest->term->name ?? '-' }}</span>
            <span class="badge badge-light mr-1">{{ $classTest->schoolClass->name ?? '-' }}</span>
            <span class="badge badge-info mr-1">{{ $classTest->subject->name ?? '-' }}</span>
            <span class="badge {{ $classTest->status === 'published' ? 'badge-success' : ($classTest->status === 'locked' ? 'badge-dark' : 'badge-secondary') }}">
                {{ strtoupper($classTest->status) }}
            </span>
            <a href="{{ route('class-tests.print', $classTest) }}" target="_blank" class="btn btn-sm btn-info ml-2">Print Result</a>
            <a href="{{ route('class-tests.print-blank', $classTest) }}" target="_blank" class="btn btn-sm btn-secondary ml-1">Print Blank Sheet</a>
            @if(isset($relatedClassTests) && $relatedClassTests->count() > 1)
                <a href="{{ route('class-tests.marks.bulk.create', $classTest) }}" class="btn btn-sm btn-primary ml-1">Bulk Marks (All Subjects)</a>
            @endif
        </div>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        @if($classTest->status === 'locked')
            <div class="alert alert-warning">Class test is locked. Marks cannot be modified.</div>
        @elseif($classTest->status === 'published')
            <div class="alert alert-info">Editing marks will move status to Draft for republish review.</div>
        @endif

        <div class="mb-2">
            <strong>Total Marks:</strong> {{ number_format((float) $classTest->total_marks, 2) }}
            <span class="mx-2">|</span>
            <strong>Pass Marks:</strong> {{ $classTest->pass_marks !== null ? number_format((float) $classTest->pass_marks, 2) : '-' }}
        </div>

        <form method="POST" action="{{ route('class-tests.marks.store', $classTest) }}">
            @csrf
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Roll</th>
                            <th>Student</th>
                            <th>Marks</th>
                            <th>Absent</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($enrollments as $i => $enrollment)
                            @php($mark = $existingMarks->get($enrollment->id))
                            <tr>
                                <td>{{ $enrollment->roll_number ?? '-' }}</td>
                                <td>{{ $enrollment->student->name ?? 'Student #' . $enrollment->student_id }}</td>
                                <td>
                                    <input type="hidden" name="rows[{{ $i }}][student_enrollment_id]" value="{{ $enrollment->id }}">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="form-control form-control-sm"
                                        name="rows[{{ $i }}][marks_obtained]"
                                        value="{{ old("rows.$i.marks_obtained", optional($mark)->marks_obtained) }}"
                                    >
                                </td>
                                <td class="text-center">
                                    <input
                                        type="checkbox"
                                        name="rows[{{ $i }}][is_absent]"
                                        value="1"
                                        {{ old("rows.$i.is_absent", optional($mark)->is_absent) ? 'checked' : '' }}
                                    >
                                </td>
                                <td>
                                    <input
                                        type="text"
                                        class="form-control form-control-sm"
                                        maxlength="255"
                                        name="rows[{{ $i }}][remarks]"
                                        value="{{ old("rows.$i.remarks", optional($mark)->remarks) }}"
                                    >
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No active enrollments found for this class and academic year.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($classTest->status !== 'locked')
                <button type="submit" class="btn btn-success">Save Marks</button>
            @else
                <button type="button" class="btn btn-secondary" disabled>Save Marks (Locked)</button>
            @endif
            <a href="{{ route('class-tests.index') }}" class="btn btn-secondary">Back</a>
        </form>
    </div>
</div>
@endsection
