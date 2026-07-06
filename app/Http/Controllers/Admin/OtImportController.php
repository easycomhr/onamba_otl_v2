<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportOtRequest;
use App\Services\OtImportService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OtImportController extends Controller
{
    public function __construct(private OtImportService $service) {}

    public function index()
    {
        return view('admin.imports.ot');
    }

    public function template(): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="mau_import_ot.csv"',
        ];

        $rows = [
            ['employee_code', 'ot_date',    'hours', 'reason'],
            ['NV12345',       '2026-03-15', '2.5',   'Hỗ trợ dây chuyền lắp ráp xưởng A'],
            ['NV12346',       '2026-03-16', '4',     'Bảo trì máy đóng gói định kỳ'],
            ['NV12347',       '2026-03-17', '3',     'Kiểm tra hàng xuất kho'],
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

    public function store(ImportOtRequest $request)
    {
        try {
            $storedPath = $request->file('file')->store('imports/ot');
            $result = $this->service->import(Storage::path($storedPath));

            return back()->with('importResult', $result);
        } catch (\Throwable $e) {
            Log::error('OtImport store error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['error' => 'Đã xảy ra lỗi khi import dữ liệu tăng ca.']);
        }
    }
}
