@extends('layouts.app')
@section('title', 'Terms')
@section('content_header_title', 'Terms')
@section('content_body')
<div class="card">
  <div class="card-header d-flex justify-content-between">
    <h3 class="card-title">Terms</h3>
    <a href="{{ route('terms.create') }}" class="btn btn-primary">New Term</a>
  </div>
  <div class="card-body">
    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('terms.index') }}" class="form-row align-items-end mb-3">
      <div class="form-group col-md-4 mb-2 mb-md-0">
        <label for="academic_year_id" class="mb-1">Academic Year</label>
        <select name="academic_year_id" id="academic_year_id" class="form-control">
          <option value="">All Academic Years</option>
          @foreach($years as $year)
            <option value="{{ $year->id }}" {{ (string) $selectedAcademicYearId === (string) $year->id ? 'selected' : '' }}>
              {{ $year->name }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="form-group col-md-8 mb-0">
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="{{ route('terms.index') }}" class="btn btn-outline-secondary">Reset</a>
      </div>
    </form>

    <table class="table table-striped">
      <thead>
        <tr>
          <th>Order</th>
          <th>Name</th>
          <th>Academic Year</th>
          <th>Start Date</th>
          <th>End Date</th>
          <th>Fee Due Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($terms as $term)
        <tr>
          <td>{{ $term->order }}</td>
          <td>{{ $term->name }}</td>
          <td>{{ $term->academicYear->name }}</td>
          <td>{{ $term->start_date->format('Y-m-d') }}</td>
          <td>{{ $term->end_date->format('Y-m-d') }}</td>
          <td>{{ $term->fee_due_date?->format('Y-m-d') ?? '-' }}</td>
          <td>
            <a href="{{ route('terms.edit', $term) }}" class="btn btn-sm btn-warning">Edit</a>
            <form action="{{ route('terms.destroy', $term) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this term?');">
              @csrf @method('DELETE')
              <button class="btn btn-sm btn-danger">Delete</button>
            </form>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="7" class="text-center text-muted">No terms found for the selected academic year.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
