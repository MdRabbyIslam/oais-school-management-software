@extends('adminlte::page')

{{-- Extend and customize the browser title --}}

@section('title')
    {{ config('adminlte.title') }}
    @hasSection('subtitle') | @yield('subtitle') @endif
@stop

{{-- Extend and customize the page content header --}}

@section('content_header')
    @hasSection('content_header_title')
        <h1 class="text-muted">
            @yield('content_header_title')

            @hasSection('content_header_subtitle')
                <small class="text-dark">
                    <i class="fas fa-xs fa-angle-right text-muted"></i>
                    @yield('content_header_subtitle')
                </small>
            @endif
        </h1>
    @endif
@stop

{{-- Rename section content to content_body --}}

@section('content')
    @yield('content_body')
@stop

{{-- Create a common footer --}}

@section('footer')
    <div class="float-right">
        Version: {{ config('app.version', '1.0.0') }}
    </div>

    <strong>
        <a href="{{ config('app.company_url', '#') }}">
            {{ config('app.company_name', 'Oasis Model School') }}
        </a>
    </strong>
@stop

{{-- Add common Javascript/Jquery code --}}

@push('js')
<script>
    // add this script to main layout file to enable select2 focus all the time on open and close
    jQuery(document).ready(function () {
        $(document).on('select2:open', (e) => {
            document.querySelector('.select2-container--open .select2-search__field').focus();
            $(e.target).select2('focus');
        });

        $(document).on('select2:close', (e) => {
            if (document.querySelector('.select2-container--open .select2-search__field')) {
                document.querySelector('.select2-container--open .select2-search__field').focus();
            }
        });
    })
</script>
@endpush

{{-- Add common CSS customizations --}}

@push('css')
<style type="text/css">

    {{-- You can add AdminLTE customizations here --}}
    /*
    .card-header {
        border-bottom: none;
    }
    .card-title {
        font-weight: 600;
    }
    */

    .select2-container .select2-selection--single {
        height:auto;
    }

    .brand-text{
        font-weight: 600!important;
    }

</style>
@endpush
