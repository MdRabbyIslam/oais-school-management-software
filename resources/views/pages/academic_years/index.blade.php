@extends('layouts.app')
@section('title', 'Academic Years')
@section('content_header_title', 'Academic Years')
@section('content_body')
<div class="card">
  <div class="card-header d-flex justify-content-between">
    <h3 class="card-title">Academic Years</h3>
    <a href="{{ route('academic_years.create') }}" class="btn btn-primary">New Academic Year</a>
  </div>
  <div class="card-body">
    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Name</th>
          <th>Start Date</th>
          <th>End Date</th>
          <th>Current?</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($years as $year)
        <tr>
          <td>{{ $year->name }}</td>
          <td>{{ $year->start_date->format('Y-m-d') }}</n><td>{{ $year->end_date->format('Y-m-d') }}</td>
          <td>{!! $year->is_current ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-secondary">No</span>' !!}</td>
          <td>
            <a href="{{ route('academic_years.edit', $year) }}" class="btn btn-sm btn-warning">Edit</a>
            <form action="{{ route('academic_years.destroy', $year) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this Academic Year?');">
              @csrf @method('DELETE')
              <button class="btn btn-sm btn-danger">Delete</button>
            </form>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
