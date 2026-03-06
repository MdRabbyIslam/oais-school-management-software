@extends('layouts.app')

@section('title', 'Create Student')

@section('content_header_title', 'Students')
@section('content_header_subtitle', 'Create')

@section('content_body')
<div class="card">
    <div class="card-body">
        <form action="{{ route('students.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @include('pages.students.form')
        </form>
    </div>
</div>
@endsection

@section('js')
<script>
    $(function () {
     $('[data-toggle="tooltip"]').tooltip();
    });
</script>
@endsection
