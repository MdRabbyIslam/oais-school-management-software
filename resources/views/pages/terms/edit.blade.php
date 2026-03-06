@extends('layouts.app')
@section('title', 'Edit Term')
@section('content_header_title', 'Edit Term')
@section('content_body')
<div class="card">
  <div class="card-body">
    <form action="{{ route('terms.update', $term) }}" method="POST">
      @csrf
      @method('PUT')
      <div class="form-group">
        <label for="academic_year_id">Academic Year*</label>
        <select name="academic_year_id" id="academic_year_id" class="form-control" required>
          @foreach($years as $y)
            <option value="{{ $y->id }}" {{ old('academic_year_id', $term->academic_year_id)==$y->id?'selected':'' }}>{{ $y->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group">
        <label for="order">Order*</label>
        <input type="number" name="order" class="form-control" value="{{ old('order', $term->order) }}" required>
      </div>
      <div class="form-group">
        <label for="name">Name*</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $term->name) }}" required>
      </div>
      <div class="form-row">
        <div class="form-group col-md-4">
          <label for="start_date">Start Date*</label>
          <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $term->start_date->format('Y-m-d')) }}" required>
        </div>
        <div class="form-group col-md-4">
          <label for="end_date">End Date*</label>
          <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $term->end_date->format('Y-m-d')) }}" required>
        </div>
        <div class="form-group col-md-4">
          <label for="fee_due_date">Fee Due Date</label>
          <input type="date" name="fee_due_date" class="form-control" value="{{ old('fee_due_date', optional($term->fee_due_date)->format('Y-m-d')) }}">
        </div>
      </div>
      <button type="submit" class="btn btn-success">Update</button>
      <a href="{{ route('terms.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
  </div>
</div>
@endsection
