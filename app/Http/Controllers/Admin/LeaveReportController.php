<?php

namespace App\Http\Controllers\Admin;

use App\Exports\LeaveExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LeaveReportExportRequest;
use App\Http\Requests\Admin\LeaveReportFilterRequest;
use App\Models\User;
use App\Services\LeaveReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class LeaveReportController extends Controller
{
    public function __construct(private LeaveReportService $service) {}

    public function index(LeaveReportFilterRequest $request)
    {
        $leaveRequests = collect();
        $summary       = collect();

        try {
            $departments = $this->service->getDepartments();
            $leaveTypes  = $this->service->getLeaveTypes();
            $employees   = User::where('role', 'employee')->orderBy('name')->select('id', 'name')->get();

            if ($request->filled(['from_date', 'to_date'])) {
                $leaveRequests = $this->service->getData($request->validated());
                $summary       = $this->service->getSummary($leaveRequests);
            }
        } catch (\Throwable $e) {
            Log::error('LeaveReport index error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->withErrors(['error' => 'Đã xảy ra lỗi khi tải báo cáo.']);
        }

        return view('admin.reports.leave', compact('leaveRequests', 'summary', 'employees', 'departments', 'leaveTypes'));
    }

    public function export(LeaveReportExportRequest $request)
    {
        try {
            $rows    = $this->service->getData($request->validated());
            $summary = $this->service->getSummary($rows);
            $format  = $request->format;

            return match ($format) {
                'xlsx' => Excel::download(new LeaveExport($rows), 'leave-report.xlsx'),
                'csv'  => Excel::download(new LeaveExport($rows), 'leave-report.csv', \Maatwebsite\Excel\Excel::CSV),
                'pdf'  => Pdf::loadView('admin.reports.leave-pdf', [
                    'leaveRequests' => $rows,
                    'summary'       => $summary,
                    'fromDate'      => $request->from_date,
                    'toDate'        => $request->to_date,
                ])->download('leave-report.pdf'),
            };
        } catch (\Throwable $e) {
            Log::error('LeaveReport export error', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Đã xảy ra lỗi khi xuất báo cáo.']);
        }
    }
}
