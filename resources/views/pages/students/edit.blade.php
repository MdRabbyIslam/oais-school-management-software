@extends('layouts.app')

@section('title', 'Edit Student')

@section('content_header_title', 'Students')
@section('content_header_subtitle', 'Edit')

@section('content_body')
<div class="card">
    <div class="card-body">

        {{-- show errors --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        {{-- show success message --}}
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        {{-- show error message --}}
        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('students.update', $student->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
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
