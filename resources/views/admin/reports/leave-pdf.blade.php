<?php

use App\Models\LeaveRequest;
?>
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('ui.nav.reports_leave') }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #222;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0 0 6px;
            font-size: 20px;
        }
        .header p {
            margin: 2px 0;
        }
        .section-title {
            margin: 18px 0 8px;
            font-size: 14px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        th, td {
            border: 1px solid #444;
            padding: 6px;
            text-align: left;
        }
        th {
            background: #f0f0f0;
        }
        .footer {
            margin-top: 16px;
            font-size: 11px;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('ui.nav.reports_leave') }}</h1>
        <p>OTL System</p>
        <p>{{ __('ui.leave.from_date') }}: {{ $fromDate }} | {{ __('ui.leave.to_date') }}: {{ $toDate }}</p>
    </div>

    <div class="section-title">{{ __('ui.leave.detail_title') }}</div>
    <table>
        <thead>
            <tr>
                <th>{{ __('ui.employee.code_short') }}</th>
                <th>{{ __('ui.employee.full_name') }}</th>
                <th>{{ __('ui.employee.department') }}</th>
                <th>{{ __('ui.leave.type_short') }}</th>
                <th>{{ __('ui.leave.from_date') }}</th>
                <th>{{ __('ui.leave.to_date') }}</th>
                <th>{{ __('ui.leave.total_days') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($leaveRequests as $row)
                <tr>
                    <td>{{ $row->employee->employee_code ?? '' }}</td>
                    <td>{{ $row->employee->name ?? '' }}</td>
                    <td>{{ $row->employee->department ?? '' }}</td>
                    <td>{{ LeaveRequest::LEAVE_TYPES[$row->leave_type] ?? $row->leave_type }}</td>
                    <td>{{ $row->from_date }}</td>
                    <td>{{ $row->to_date }}</td>
                    <td>{{ $row->days }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">{{ __('ui.table.employee') }}</div>
    <table>
        <thead>
            <tr>
                <th>{{ __('ui.employee.full_name') }}</th>
                <th>{{ __('ui.employee.department') }}</th>
                <th>{{ __('ui.report.total_leave_times') }}</th>
                <th>{{ __('ui.report.total_leave_days') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($summary as $row)
                <tr>
                    <td>{{ $row['employee']->name ?? '' }}</td>
                    <td>{{ $row['employee']->department ?? '' }}</td>
                    <td>{{ $row['total_times'] }}</td>
                    <td>{{ $row['total_days'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generated at: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
