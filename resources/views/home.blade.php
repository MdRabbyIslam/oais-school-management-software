@extends('layouts.app')

@section('plugins.Select2', true)
@section('plugins.Chartjs', true)

@section('subtitle', 'Dashboard')
@section('content_header_title', 'Dashboard')

@section('content_body')
    <div class="row">

        {{-- SMS Balance --}}
        <div class="col-lg-4 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $smsBalance }}</h3>
                    <p>SMS Balance</p>
                </div>
                <div class="icon"><i class="fas fa-sms"></i></div>
                <a href="{{ route('sms.logs') }}" class="small-box-footer">
                    SMS Logs <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        {{-- Total Students --}}
        <div class="col-lg-4 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totalStudents }}</h3>
                    <p>Total Students</p>
                </div>
                <div class="icon"><i class="fas fa-user-graduate"></i></div>
                <a href="{{ route('students.index') }}" class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        {{-- Total Teachers --}}
        <div class="col-lg-4 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $totalTeachers }}</h3>
                    <p>Total Teachers</p>
                </div>
                <div class="icon"><i class="fas fa-user-tie"></i></div>
                <a href="#" class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        @php
            use Carbon\Carbon;
            $today = Carbon::today()->toDateString(); // e.g. "2025-06-28"
            $monthStart = Carbon::now()->startOfMonth()->toDateString(); // e.g. "2025-06-01"
            $monthEnd = Carbon::now()->endOfMonth()->toDateString(); // e.g. "2025-06-30"
        @endphp
        {{-- Collected Today --}}
        {{-- <div class="col-lg-4 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format($collectToday, 2) }}</h3>
                    <p>Fee Collected Today</p>
                </div>
                <div class="icon"><i class="fas fa-dollar-sign"></i></div>
                <a href="{{ route('payments.index', [
                    'from_date' => $today,
                    'to_date' => $today,
                ]) }}"
                    class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div> --}}

        {{-- Collected This Month --}}
        {{-- <div class="col-lg-4 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ number_format($collectMonth, 2) }}</h3>
                    <p>Fee Collected This Month</p>
                </div>
                <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                <a href="{{ route('payments.index', [
                    'from_date' => $monthStart,
                    'to_date' => $monthEnd,
                ]) }}"
                    class="small-box-footer">
                    More info <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div> --}}
        {{-- Total Due --}}
        {{-- <div class="col-lg-4 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ number_format($totalDue, 2) }}</h3>
                    <p>Total Outstanding Fees</p>
                </div>
                <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
                <a href="{{ route('invoices.index',['status' => 'unpaid']) }}" class="small-box-footer">
                    Invoice List <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div> --}}
    </div>

    <div class="row">
        {{-- Monthly Collections Chart --}}
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Monthly Fee Collections</h3>
                </div>
                <div class="card-body">
                    <canvas id="monthlyChart" style="min-height:250px"></canvas>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        const labels = @json($labels);
        const data = @json($data);

        const ctx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Collection Amount',
                    data: data,
                    fill: false,
                    tension: 0.1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
@endpush
