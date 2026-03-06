@extends('layouts.app')

@section('title', 'Edit Teacher')
@section('content_header_title', 'Teachers')
@section('content_header_subtitle', 'Edit')

@section('content_body')
<div class="card">
    <div class="card-body">
        <form action="{{ route('teachers.update', $teacher->id) }}" method="POST">
            @csrf
            @method('PUT')
            @include('pages.teachers.form')
        </form>
    </div>
</div>
@endsection
