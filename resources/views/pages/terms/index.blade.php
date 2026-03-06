@extends('layouts.app')
@section('title', 'Terms')
@section('content_header_title', 'Terms')
@section('content_body')
<div class="card">
  <div class="card-header d-flex justify-content-between">
    <h3 class="card-title">Terms</n3>
    <a href="{{ route('terms.create') }}" class="btn btn-primary">New Term</a>
  </div>
  <div class="card-body">
    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
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
        @foreach($terms as $term)
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
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
