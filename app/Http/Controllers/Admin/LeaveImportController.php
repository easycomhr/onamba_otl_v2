<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportLeaveRequest;
use App\Services\LeaveImportService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaveImportController extends Controller
{
    public function __construct(private LeaveImportService $service) {}

    public function index()
    {
        return view('admin.imports.leave');
    }

    public function template(): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="mau_import_nghi_phep.csv"',
        ];

        // leave_type hợp lệ: annual (Phép năm), sick (Nghỉ bệnh)
        $rows = [
            ['employee_code', 'leave_type', 'from_date',  'to_date',    'reason'],
            ['NV12345',       'annual',     '2026-03-18', '2026-03-19', 'Việc gia đình'],
            ['NV12346',       'sick',       '2026-03-20', '2026-03-20', 'Khám sức khỏe định kỳ'],
            ['NV12347',       'annual',     '2026-03-25', '2026-03-26', 'Việc cá nhân'],
        ];

        return response()->stream(function () use ($rows) {
            // BOM UTF-8 để Excel mở đúng tiếng Việt
            echo "\xEF\xBB\xBF";
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 200, $headers);
    }

    public function store(ImportLeaveRequest $request)
    {
        try {
            $path = $request->file('file')->store('imports/leave');
            $result = $this->service->import(storage_path('app/' . $path));

            return back()->with('importResult', $result);
        } catch (\Throwable $e) {
            Log::error('LeaveImport store error', ['error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Đã xảy ra lỗi khi import dữ liệu.']);
        }
    }
}
