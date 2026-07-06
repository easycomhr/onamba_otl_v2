<?php

namespace App\Http\Controllers\Admin;

use App\Exports\OtExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OtReportExportRequest;
use App\Http\Requests\Admin\OtReportFilterRequest;
use App\Models\User;
use App\Services\OtReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class OtReportController extends Controller
{
    public function __construct(private OtReportService $service) {}

    public function index(OtReportFilterRequest $request)
    {
        $otRequests = collect();
        $summary    = collect();

        try {
            $departments = $this->service->getDepartments();
            $employees   = User::where('role', 'employee')->orderBy('name')->select('id', 'name')->get();

            if ($request->filled(['from_date', 'to_date'])) {
                $otRequests = $this->service->getData($request->validated());
                $summary    = $this->service->getSummary($otRequests);
            }
        } catch (\Throwable $e) {
            Log::error('OtReport index error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->withErrors(['error' => 'Đã xảy ra lỗi khi tải báo cáo.']);
        }

        return view('admin.reports.ot', compact('otRequests', 'summary', 'employees', 'departments'));
    }

    public function export(OtReportExportRequest $request)
    {
        try {
            $rows    = $this->service->getData($request->validated());
            $summary = $this->service->getSummary($rows);
            $format  = $request->format;

            return match ($format) {
                'xlsx' => Excel::download(new OtExport($rows), 'ot-report.xlsx'),
                'csv'  => Excel::download(new OtExport($rows), 'ot-report.csv', \Maatwebsite\Excel\Excel::CSV),
                'pdf'  => Pdf::loadView('admin.reports.ot-pdf', [
                    'otRequests' => $rows,
                    'summary'    => $summary,
                    'fromDate'   => $request->from_date,
                    'toDate'     => $request->to_date,
                ])->download('ot-report.pdf'),
            };
        } catch (\Throwable $e) {
            Log::error('OtReport export error', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Đã xảy ra lỗi khi xuất báo cáo.']);
        }
    }
}
