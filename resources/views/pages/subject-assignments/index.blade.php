@extends('layouts.app')

@section('title', 'Subject Assignment')
@section('content_header_title', 'Subject Assignments')
@section('content_header_subtitle', 'Assign Teacher to Subject')

@section('content_body')
<div class="card">
    <div class="card-body">
        <form method="GET" class="form-inline mb-3">
            <label for="section_id" class="mr-2">Select Section:</label>
            <select name="section_id" class="form-control mr-2" onchange="this.form.submit()">
                <option value="">-- Choose Section --</option>
                @foreach($sections as $section)
                    <option value="{{ $section->id }}" {{ request('section_id') == $section->id ? 'selected' : '' }}>
                        {{ $section->section_name }} (Class: {{ $section->schoolClass->name ?? '-' }})
                    </option>
                @endforeach
            </select>
        </form>

        @if(!empty($subjects) && count($subjects) > 0)
            <form method="POST" action="{{ route('subject-assignments.store') }}">
                @csrf
                <input type="hidden" name="section_id" value="{{ request('section_id') }}">

                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Assigned Teacher</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subjects as $subject)
                            <tr>
                                <td>{{ $subject->name }}</td>
                                <td>
                                    <select name="assignments[{{ $subject->id }}]" class="form-control">
                                        <option value="">-- Choose Teacher --</option>
                                        @foreach($teachers as $teacher)
                                            <option value="{{ $teacher->id }}"
                                                @if(isset($existing[$subject->id]) && $existing[$subject->id] == $teacher->id)
                                                    selected
                                                @endif
                                            >
                                                {{ $teacher->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="text-right">
                    <button type="submit" class="btn btn-success">Save Assignments</button>
                </div>
            </form>
        @elseif(request('section_id'))
            <p>No subjects available for this section.</p>
        @endif
    </div>
</div>
@endsection
