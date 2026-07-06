<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('ui.nav.reports_ot') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 18px; text-align: center; margin-bottom: 2px; }
        .company { text-align: center; font-size: 13px; margin-bottom: 2px; }
        .meta { text-align: center; font-size: 11px; color: #555; margin-bottom: 16px; }
        h2 { font-size: 13px; margin-top: 20px; margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { background-color: #4a5568; color: #fff; padding: 6px 8px; text-align: left; }
        td { padding: 5px 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background-color: #f7fafc; }
    </style>
</head>
<body>
    <h1>{{ __('ui.nav.reports_ot') }}</h1>
    <div class="company">OTL System</div>
    <div class="meta">
        {{ __('ui.leave.from_date') }}: {{ $fromDate }} &mdash; {{ __('ui.leave.to_date') }}: {{ $toDate }}<br>
        {{ __('ui.report.export') }}: {{ now()->format('d/m/Y H:i') }}
    </div>

    <h2>{{ __('ui.ot.detail_title') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('ui.employee.code_short') }}</th>
                <th>{{ __('ui.employee.full_name') }}</th>
                <th>{{ __('ui.employee.department') }}</th>
                <th>{{ __('ui.ot.ot_date') }}</th>
                <th>{{ __('ui.common.status') }}</th>
                <th>{{ __('ui.ot.approved_hours') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($otRequests as $row)
            <tr>
                <td>{{ $row->employee->employee_code ?? '' }}</td>
                <td>{{ $row->employee->name ?? '' }}</td>
                <td>{{ $row->employee->department ?? '' }}</td>
                <td>{{ $row->ot_date }}</td>
                <td>{{ $row->status_label ?? $row->status }}</td>
                <td>{{ $row->status === 'approved' ? $row->approved_hours : $row->hours }}</td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;">{{ __('ui.report.no_data') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>{{ __('ui.table.employee') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('ui.employee.full_name') }}</th>
                <th>{{ __('ui.report.total_ot_days') }}</th>
                <th>{{ __('ui.report.total_ot_hours') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($summary as $item)
            <tr>
                <td>{{ $item->name ?? '' }}</td>
                <td>{{ $item->total_days ?? 0 }}</td>
                <td>{{ $item->total_hours ?? 0 }}</td>
            </tr>
            @empty
            <tr><td colspan="3" style="text-align:center;">{{ __('ui.report.no_data') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
