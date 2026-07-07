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

        // Lấy mapping từ service (nguồn duy nhất)
        $map = SalaryImportService::columnMap();

        // Hàng 1: tiêu đề tiếng Việt (dùng để import)
        $labelRow = array_values($map);

        // Hàng 2: tên field nội bộ (chỉ để tham khảo, không dùng khi import)
        $fieldRow = array_map(fn ($f) => '[' . $f . ']', array_keys($map));

        // Hàng 3: dữ liệu mẫu (field name => giá trị mẫu)
        $sampleData = [
            'employee_code'                 => 'NV12345',
            'month'                         => '3',
            'year'                          => '2026',
            'payment_method'                => 'Chuyển khoản',
            'base_salary'                   => '5000000',
            'allowance_position'            => '500000',
            'allowance_proficiency'         => '200000',
            'allowance_steering'            => '0',
            'allowance_environment'         => '0',
            'allowance_special_work'        => '0',
            'allowance_scene'               => '0',
            'allowance_housing'             => '0',
            'allowance_lead'                => '0',
            'allowance_slippage'            => '0',
            'allowance_skill'               => '0',
            'allowance_language'            => '0',
            'allowance_attendance'          => '300000',
            'allowance_lunch'               => '150000',
            'allowance_heavy_work'          => '0',
            'allowance_seniority'           => '200000',
            'allowance_fuel'                => '0',
            'allowance_distance'            => '0',
            'allowance_phone'               => '0',
            'allowance_machine'             => '0',
            'allowance_other'               => '100000',
            'allowance_ot_meal'             => '0',
            'salary_13th_month'             => '0',
            'ot_hours_150'                  => '10',
            'ot_hours_200'                  => '4',
            'ot_hours_300'                  => '0',
            'ot_total'                      => '1500000',
            'income_total'                  => '7950000',
            'deduction_bhxh'                => '400000',
            'deduction_bhyt'                => '75000',
            'deduction_bhtn'                => '50000',
            'deduction_insurance_total'     => '525000',
            'h_insurance_included'          => '1',
            's_insurance_included'          => '1',
            'j_insurance_included'          => '1',
            'deduction_union_fee'           => '10000',
            'deduction_personal_exemption'  => '11000000',
            'deduction_dependent_exemption' => '0',
            'deduction_unpaid_leave'        => '0',
            'taxable_income'                => '0',
            'deduction_pit_regular'         => '0',
            'deduction_pit_irregular'       => '0',
            'deduction_advance'             => '1000000',
            'income_after_tax'              => '6415000',
            'regular_amount'                => '0',
            'adjustment'                    => '0',
            'parking_free'                  => '0',
            'net'                           => '6415000',
            'productivity_salary'           => '0',
            'working_days'                  => '26',
            'ot_night_allowance'            => '0',
            'deduction_penalty'             => '0',
            'deductions_total'              => '1535000',
        ];

        // Sắp xếp sample theo đúng thứ tự của map
        $sampleRow = array_map(fn ($field) => $sampleData[$field] ?? '', array_keys($map));

        return response()->stream(function () use ($labelRow, $fieldRow, $sampleRow) {
            echo "\xEF\xBB\xBF";
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $labelRow);   // Hàng 1: tiêu đề tiếng Việt
            fputcsv($handle, $fieldRow);   // Hàng 2: tên field (tham khảo)
            fputcsv($handle, $sampleRow);  // Hàng 3: dữ liệu mẫu
            fclose($handle);
        }, 200, $headers);
    }
}
