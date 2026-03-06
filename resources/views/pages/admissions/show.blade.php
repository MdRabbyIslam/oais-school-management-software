@extends('layouts.app')
@section('title', 'Application Detail')

@section('content_header_title', 'Application')
@section('content_header_subtitle', 'Detail')

@section('content_body')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Application #{{ $application->application_no }}</h3>
        <div>
            @if($application->status === 'pending')
                <a href="#approveModal" data-toggle="modal" class="btn btn-sm btn-success">Approve</a>
                <a href="#rejectModal" data-toggle="modal" class="btn btn-sm btn-danger">Reject</a>
            @endif
        </div>
    </div>
    <div class="card-body">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <dl class="row">
            <dt class="col-sm-3">Name</dt><dd class="col-sm-9">{{ $application->name }}</dd>
            <dt class="col-sm-3">DOB</dt><dd class="col-sm-9">{{ $application->dob }}</dd>
            <dt class="col-sm-3">Year</dt><dd class="col-sm-9">{{ $application->academicYear->name ?? '-' }}</dd>
            <dt class="col-sm-3">Status</dt><dd class="col-sm-9">{{ $application->status }}</dd>
            <dt class="col-sm-3">Submitted By</dt><dd class="col-sm-9">{{ optional($application->submittedBy)->name ?? '-' }}</dd>
        </dl>

        <h5>Documents</h5>
        <ul>
          @foreach($application->documents as $doc)
            <li>{{ ucfirst(str_replace('_',' ',$doc->type)) }}: <a href="{{ $doc->url ?? '/' . ltrim($doc->path, '/') }}" target="_blank">{{ $doc->original_name }}</a></li>
          @endforeach
        </ul>

        <h5>Logs</h5>
        <ul>
            @foreach($application->logs as $log)
                <li>{{ $log->created_at->toDateTimeString() }} — {{ $log->from_status }} → {{ $log->to_status }} — {{ $log->notes }}</li>
            @endforeach
        </ul>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <form method="POST" action="{{ route('admissions.approve', $application->id) }}">
        @csrf
        <div class="modal-content">
          <div class="modal-header"><h5 class="modal-title">Approve Application</h5></div>
          <div class="modal-body">
            <div class="form-group">
                <label for="section_id">Assign Section</label>
                <select name="section_id" id="section_id" class="form-control" required>
                    @forelse($sections as $s)
                        <option value="{{ $s->id }}" @if($application->preferred_section_id === $s->id) selected @endif>
                            {{ $s->schoolClass->name ?? '' }} - {{ $s->section_name }}
                        </option>
                    @empty
                        <option value="" disabled>No sections available for the preferred class</option>
                    @endforelse
                </select>
            </div>
            <div class="form-group">
                <label for="admission_date">Admission Date</label>
                <input type="date" name="admission_date" class="form-control" value="{{ now()->toDateString() }}" required>
            </div>
            <div class="form-group">
                <label for="roll_number">Roll Number (optional)</label>
                <input type="number" name="roll_number" class="form-control">
            </div>
            <div class="form-group">
                <label for="review_notes">Notes</label>
                <textarea name="review_notes" class="form-control"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success">Approve</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          </div>
        </div>
    </form>
  </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <form method="POST" action="{{ route('admissions.reject', $application->id) }}">
        @csrf
        <div class="modal-content">
          <div class="modal-header"><h5 class="modal-title">Reject Application</h5></div>
          <div class="modal-body">
            <div class="form-group">
                <label for="reason">Reason</label>
                <textarea name="reason" class="form-control"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-danger">Reject</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          </div>
        </div>
    </form>
  </div>
</div>

@stop
