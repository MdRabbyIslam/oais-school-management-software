@extends('layouts.app')

@section('title', 'Bulk Class Test Marks Entry')
@section('content_header_title', 'Class Test Marks Entry')
@section('content_header_subtitle', $classTest->name . ' (All Subjects)')

@section('content_body')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Bulk Marks Entry (All Subjects)</h3>
        <div>
            <span class="badge badge-light mr-1">{{ $classTest->academicYear->name ?? '-' }}</span>
            <span class="badge badge-light mr-1">{{ $classTest->term->name ?? '-' }}</span>
            <span class="badge badge-light mr-1">{{ $classTest->schoolClass->name ?? '-' }}</span>
            <a href="{{ route('class-tests.marks.create', $classTest) }}" class="btn btn-sm btn-secondary ml-1">Single Subject View</a>
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

        <div class="mb-3">
            <strong>Test:</strong> {{ $classTest->name }}
            <span class="mx-2">|</span>
            <strong>Date:</strong> {{ optional($classTest->test_date)->format('d M Y') ?? '-' }}
            <span class="mx-2">|</span>
            <strong>Subjects:</strong> {{ $relatedClassTests->count() }}
        </div>

        <div class="mb-3">
            @foreach($relatedClassTests as $test)
                <span class="badge mr-1 {{ $test->status === 'published' ? 'badge-success' : ($test->status === 'locked' ? 'badge-dark' : 'badge-secondary') }}">
                    {{ $test->subject->name ?? ('Subject #' . $test->subject_id) }} ({{ strtoupper($test->status) }})
                </span>
            @endforeach
        </div>

        <div class="alert alert-info">
            Locked subjects are read-only and will be skipped. If a subject is Published, saving marks will move it to Draft for republish review.
        </div>

        <form method="POST" action="{{ route('class-tests.marks.bulk.store', $classTest) }}">
            @csrf
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Roll</th>
                            <th>Student</th>
                            @foreach($relatedClassTests as $test)
                                <th>
                                    <div>{{ $test->subject->name ?? ('Subject #' . $test->subject_id) }}</div>
                                    <small>
                                        Total: {{ number_format((float) $test->total_marks, 2) }},
                                        Pass: {{ $test->pass_marks !== null ? number_format((float) $test->pass_marks, 2) : '-' }}
                                    </small>
                                    @if($test->status === 'locked')
                                        <div><small class="text-danger">Locked</small></div>
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($enrollments as $i => $enrollment)
                            <tr>
                                <td>{{ $enrollment->roll_number ?? '-' }}</td>
                                <td>
                                    {{ $enrollment->student->name ?? 'Student #' . $enrollment->student_id }}
                                    <input type="hidden" name="rows[{{ $i }}][student_enrollment_id]" value="{{ $enrollment->id }}">
                                </td>
                                @foreach($relatedClassTests as $test)
                                    @php($mark = optional($existingMarksByTest->get($test->id))->get($enrollment->id))
                                    <td>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="form-control form-control-sm"
                                            name="rows[{{ $i }}][marks][{{ $test->id }}]"
                                            value="{{ old("rows.$i.marks.$test->id", optional($mark)->marks_obtained) }}"
                                            {{ $test->status === 'locked' ? 'disabled' : '' }}
                                        >
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 2 + $relatedClassTests->count() }}" class="text-center">No active enrollments found for this class and academic year.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <button type="submit" class="btn btn-success">Save Bulk Marks</button>
            <a href="{{ route('class-tests.index') }}" class="btn btn-secondary">Back</a>
        </form>
    </div>
</div>
@endsection
