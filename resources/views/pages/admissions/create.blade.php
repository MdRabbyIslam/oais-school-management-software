@extends('layouts.app')
@section('title', 'New Admission Application')

@section('content_header_title', 'New Admission')
@section('content_header_subtitle', 'Create application')

@section('content_body')
<div class="card">
    <div class="card-header"><h3 class="card-title">Create Application</h3></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admissions.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="mb-3 p-2 border rounded ">
                <h5 class="mb-2">Student Details</h5>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="academic_year_id">Academic Year</label>
                        <select name="academic_year_id" id="academic_year_id" class="form-control">
                            @foreach($academicYears as $ay)
                                <option value="{{ $ay->id }}">{{ $ay->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="preferred_class_id">Preferred Class</label>
                        <select name="preferred_class_id" id="preferred_class_id" class="form-control">
                            <option value="">--</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="name">Student Name</label>
                        <input type="text" name="name" id="name" class="form-control" required placeholder="Full name">
                    </div>

                    <div class="form-group col-md-3">
                        <label for="dob">DOB</label>
                        <input type="date" name="dob" id="dob" class="form-control">
                    </div>

                    <div class="form-group col-md-9">
                        <label for="address">Address</label>
                        <input type="text" name="address" id="address" class="form-control" placeholder="Street, City, Postal">
                        {{-- <small class="form-text text-muted"></small> --}}
                    </div>
                </div>
            </div>

            <div class="mb-3 p-2 border rounded ">
                <h5 class="mb-2">Guardian Details</h5>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="primary_guardian_name">Primary Guardian</label>
                        <input type="text" name="primary_guardian_name" id="primary_guardian_name" class="form-control" placeholder="Name">
                    </div>

                    <div class="form-group col-md-2">
                        <label for="primary_guardian_relation">Relation</label>
                        <input type="text" name="primary_guardian_relation" id="primary_guardian_relation" class="form-control" placeholder="e.g. Father, Mother">
                    </div>

                    <div class="form-group col-md-3">
                        <label for="primary_guardian_contact">Guardian Contact</label>
                        <input type="text" name="primary_guardian_contact" id="primary_guardian_contact" class="form-control" placeholder="Phone number">
                    </div>

                    <div class="form-group col-md-3">
                        <label for="secondary_guardian_name">Secondary Guardian</label>
                        <input type="text" name="secondary_guardian_name" id="secondary_guardian_name" class="form-control" placeholder="Name">
                    </div>

                    <div class="form-group col-md-3">
                        <label for="secondary_guardian_relation">Secondary Relation</label>
                        <input type="text" name="secondary_guardian_relation" id="secondary_guardian_relation" class="form-control" placeholder="Relation">
                    </div>

                    <div class="form-group col-md-3">
                        <label for="secondary_guardian_contact">Secondary Contact</label>
                        <input type="text" name="secondary_guardian_contact" id="secondary_guardian_contact" class="form-control" placeholder="Phone number">
                    </div>
                </div>
            </div>

            <div class="mb-3 p-2 border rounded ">
                <h5 class="mb-2">Other Information</h5>
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="blood_group">Blood Group</label>
                        <input type="text" name="blood_group" id="blood_group" class="form-control" placeholder="e.g. A+, O-">
                        <small class="form-text text-muted">Enter if known.</small>
                    </div>
                </div>
            </div>

            <div class="mb-3 p-2 border rounded">
                <h5 class="mb-2">Documents</h5>
                <div class="form-row">
                    @foreach(\App\Models\AdmissionDocument::TYPES as $key => $label)
                        <div class="form-group col-md-6">
                            <label for="documents[{{ $key }}]">{{ $label }}</label>
                            <input type="file" name="documents[{{ $key }}]" class="form-control-file">
                            <small class="form-text text-muted">Upload {{ strtolower($label) }} if available.</small>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-3">
                <button class="btn btn-primary">Submit Application</button>
                <a href="{{ route('admissions.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@stop
