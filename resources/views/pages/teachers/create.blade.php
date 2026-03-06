@extends('layouts.app')

@section('title', 'Create Teacher')
@section('content_header_title', 'Teachers')
@section('content_header_subtitle', 'Create')

@section('content_body')
<div class="card">
    <div class="card-body">
        <form action="{{ route('teachers.store') }}" method="POST">
            @csrf
            @include('pages.teachers.form')
        </form>
    </div>
</div>
@endsection
