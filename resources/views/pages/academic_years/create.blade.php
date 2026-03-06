@extends('layouts.app')
@section('title', 'New Academic Year')
@section('content_header_title', 'New Academic Year')
@section('content_body')
<div class="card">
  <div class="card-body">
    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('academic_years.store') }}" method="POST">
      @csrf
      <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
      </div>
      <div class="form-group">
        <label>Start Date</label>
        <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}" required>
      </div>
      <div class="form-group">
        <label>End Date</label>
        <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}" required>
      </div>
      <div class="form-check mb-3">
        <input type="checkbox" name="is_current" id="is_current" class="form-check-input" {{ old('is_current') ? 'checked' : '' }}>
        <label for="is_current" class="form-check-label">Set as Current Year</label>
      </div>
      <button type="submit" class="btn btn-success">Create</button>
      <a href="{{ route('academic_years.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
  </div>
</div>
@endsection
