<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportSalaryRequest;
use App\Models\SalaryRecord;
use App\Models\User;
use App\Services\SalaryImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalaryController extends Controller
{
    public function __construct(private SalaryImportService $service) {}

    public function index(Request $request)
    {
        $month = (int) $request->get('month', now()->month);
        $year  = (int) $request->get('year',  now()->year);

        $salaries = SalaryRecord::with('employee')
            ->where('month', $month)
            ->where('year', $year)
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->whereHas('employee', fn ($u) => $u->where('name', 'like', '%' . $request->q . '%')
                    ->orWhere('employee_code', 'like', '%' . $request->q . '%'));
            })
            ->orderBy('id')
            ->get();

        return view('admin.salary.index', compact('salaries', 'month', 'year'));
    }

    public function import()
    {
        return view('admin.salary.import');
    }

    public function importStore(ImportSalaryRequest $request)
    {
        try {
            $storedPath = $request->file('file')->store('imports/salary');
            $result     = $this->service->import(
                Storage::path($storedPath),
                (int) $request->input('month'),
                (int) $request->input('year'),
            );

            return back()->with('importResult', $result);
        } catch (\Throwable $e) {
            Log::error('SalaryImport error', ['error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Đã xảy ra lỗi khi import bảng lương.']);
        }
    }

    public function template(): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="mau_import_luong.csv"',
        ];

        $columns = [
            'employee_code', 'month', 'year', 'payment_method',
            'base_salary', 'productivity_salary',
            'allowance_attendance', 'allowance_seniority', 'allowance_other', 'income_total',
            'working_days', 'ot_hours_150', 'ot_hours_200', 'ot_night_allowance', 'ot_total',
            'deduction_bhxh', 'deduction_penalty', 'deduction_advance', 'deductions_total', 'net',
        ];

        $sample = [
            'NV12345', '3', '2026', 'Chuyển khoản',
            '5000000', '2000000',
            '500000', '300000', '200000', '8000000',
            '24', '10', '4', '1000000', '4500000',
            '500000', '0', '1500000', '2000000', '10500000',
        ];

        return response()->stream(function () use ($columns, $sample) {
            echo "\xEF\xBB\xBF";
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);
            fputcsv($handle, $sample);
            fclose($handle);
        }, 200, $headers);
    }
}
