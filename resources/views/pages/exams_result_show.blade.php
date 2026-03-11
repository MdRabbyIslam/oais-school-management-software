@extends('layouts.app')

@section('title', 'Result Details')
@section('content_header_title', 'Result Details')
@section('content_header_subtitle', ($studentEnrollment->student->name ?? 'Student') . ' - ' . $examAssessmentClass->schoolClass->name)

@section('content_body')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Result Summary</h3>
        <div>
            <a href="{{ route('exam-assessment-classes.results.download', [$examAssessmentClass, $studentEnrollment]) }}" class="btn btn-sm btn-primary">Download PDF</a>
            <a href="{{ route('exam-assessment-classes.results.index', $examAssessmentClass) }}" class="btn btn-sm btn-secondary">Back</a>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3"><strong>Roll:</strong> {{ $studentEnrollment->roll_number ?? '-' }}</div>
            <div class="col-md-3"><strong>Total:</strong> {{ $result->total_obtained }}/{{ $result->total_marks }}</div>
            <div class="col-md-2"><strong>%:</strong> {{ $result->percentage }}</div>
            <div class="col-md-2"><strong>GPA:</strong> {{ $result->gpa }}</div>
            <div class="col-md-2"><strong>Grade:</strong> {{ $result->final_grade }}</div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Total</th>
                        <th>Pass</th>
                        <th>Obtained</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subjectRows as $subject)
                        @php($obtained = $subject['is_absent'] ? null : $subject['obtained_marks'])
                        <tr>
                            <td>{{ $subject['subject'] }}</td>
                            <td>{{ $subject['total_marks'] }}</td>
                            <td>{{ $subject['pass_marks'] }}</td>
                            <td>{{ $subject['is_absent'] ? 'ABSENT' : ($obtained ?? '-') }}</td>
                            <td>
                                @if($subject['is_absent'])
                                    <span class="badge badge-danger">FAIL</span>
                                @else
                                    <span class="badge {{ (float) ($obtained ?? 0) >= (float) $subject['pass_marks'] ? 'badge-success' : 'badge-danger' }}">
                                        {{ (float) ($obtained ?? 0) >= (float) $subject['pass_marks'] ? 'PASS' : 'FAIL' }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

