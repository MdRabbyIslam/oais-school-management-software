@extends('layouts.app')

@section('title', 'Fee Assignments — Print')

@section('css')
<link rel="stylesheet" media="print" href="{{ asset('/css/print.css') }}" />
<style>
  @media print {
    @page {
      size: A4 portrait;
      margin: 10mm;
    }
    body {
      margin: 0;
      padding: 0;
      background: transparent;
    }
    .main-header, .main-sidebar, .main-footer, .no-print {
      display: none !important;
    }
    .card, .card-body {
      border: 0 !important;
      box-shadow: none !important;
      padding: 0 !important;
    }
  }
  .print-header {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    margin-bottom: 8px;
  }
  .print-header .logo {
    position: absolute;
    left: 0;
    top: 0;
  }
  .print-header img {
    max-height: 70px;
    max-width: 120px;
  }
  .print-header .title {
    text-align: center;
    width: 100%;
  }
  .print-header .school-name {
    font-size: 18px;
    font-weight: 700;
    line-height: 1.1;
  }
  .print-header .subtitle {
    color: #555;
    font-size: 12px;
  }
  .print-header .report-title {
    font-size: 14px;
    color: #2874a6;
    margin-top: 4px;
  }
  .meta-line {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    margin: 6px 0 10px;
    color: #444;
  }
  .table-responsive {
    overflow-x: auto;
  }
  table.print-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
    text-align: center;
    white-space: nowrap;
  }
  table.print-table th, table.print-table td {
    border: 1px solid #000;
    padding: 4px 6px;
    vertical-align: middle;
  }
  table.print-table thead th {
    background: #e9f3fb;
    color: #000;
    font-weight: 700;
  }
  table.print-table tbody td {
    font-weight: 500;
    color: #000;
  }
  table.print-table tfoot td {
    font-weight: 700;
    background: #f7f7f7;
  }
</style>
@endsection

@section('content')
<div class="mb-2 mt-2 no-print">
  <a href="{{ url()->previous() }}" class="btn btn-sm btn-secondary">Back</a>
  <button onclick="window.print()" class="btn btn-info btn-sm ml-2">
    <i class="icon-printer mr-1"></i> Print
  </button>
</div>

<div class="print-header">
  <div class="logo">
    <img src="{{ asset('upload/images/Logo__Oysis.png') }}" alt="Logo">
  </div>
  <div class="title">
    <div class="school-name">
      <p>{{ config('app.school_name_bn', 'ওয়েসিস মডেল স্কুল') }}</p>
      <p>{{ config('app.school_name', 'Oasis Model School') }}</p>
    </div>
    <div class="subtitle">Fee Assignments Report</div>
    <div class="report-title">
      @php
        $filters = [];
        if ($filterValues['class'] && $filterValues['class'] !== 'All') $filters[] = 'Class: '.$filterValues['class'];
        if ($filterValues['section'] && $filterValues['section'] !== 'All') $filters[] = 'Section: '.$filterValues['section'];
        if ($filterValues['student'] && $filterValues['student'] !== 'All') $filters[] = 'Student: '.$filterValues['student'];
        if ($filterValues['fee'] && $filterValues['fee'] !== 'All') $filters[] = 'Fee: '.$filterValues['fee'];
        if ($filterValues['status'] && $filterValues['status'] !== 'All') $filters[] = 'Status: '.ucfirst($filterValues['status']);
        if ($filterValues['term'] && $filterValues['term'] !== 'All') $filters[] = 'Term: '.$filterValues['term'];
        echo implode(' | ', $filters);
      @endphp
    </div>
  </div>
  <div style="width:120px"></div>
</div>

<div class="meta-line">
  <div></div>
  <div><strong>Printed:</strong> {{ now()->format('d M Y, h:i A') }}</div>
</div>

<div class="table-responsive">
  <table class="print-table">
    <thead>
      <tr>
        <th>SL</th>
        <th>Student (ID & Name)</th>
        <th>Class / Section</th>
        <th>Academic Year</th>
        <th>Fee</th>
        <th>Term</th>
        <th class="text-right">Amount</th>
        <th>Due Date</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      @forelse($assignments as $assignment)
        <tr>
          <td>{{ $loop->iteration }}</td>
          <td>
            {{ $assignment->student->student_id ?? '-' }} – {{ $assignment->student->name }}
          </td>
          <td>
            {{ $assignment->student->schoolClass->name ?? '' }} /
            {{ $assignment->student->section->section_name ?? '' }}
          </td>
          <td>
            {{ $assignment->studentEnrollment?->academicYear?->name
                ?? $assignment->student->activeEnrollment?->academicYear?->name
                ?? '' }}
          </td>
          <td>{{ $assignment->fee->fee_name ?? '' }}</td>
          <td>{{ $assignment->term?->name ?? 'N/A' }}</td>
          <td class="text-right">{{ number_format($assignment->amount, 2) }}</td>
          <td>{{ optional($assignment->due_date)->format('M d, Y') }}</td>
          <td>
            <span class="badge badge-{{ $assignment->status == 'active' ? 'success' : 'secondary' }}">
              {{ ucfirst($assignment->status) }}
            </span>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="8" class="text-center text-muted">No assignments found.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
