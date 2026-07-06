<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

class OtExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnWidths,
    WithTitle
{
    public function __construct(private Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'Báo Cáo Tăng Ca';
    }

    public function headings(): array
    {
        return ['Mã NV', 'Tên Nhân Viên', 'Phòng Ban', 'Ngày OT', 'Trạng Thái', 'Số Giờ OT'];
    }

    public function map($row): array
    {
        return [
            $row->employee->employee_code ?? '',
            $row->employee->name ?? '',
            $row->employee->department ?? '',
            $row->ot_date,
            $row->status_label ?? $row->status,
            $row->status === 'approved' ? $row->approved_hours : $row->hours,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 28,
            'C' => 22,
            'D' => 22,
            'E' => 18,
            'F' => 14,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $this->rows->count() + 1; // +1 for header row
        $lastCol = 'F';
        $dataRange = "A1:{$lastCol}{$lastRow}";

        // ── Header style ──────────────────────────────────────────────────────
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['argb' => 'FFFFFFFF'],
                'size'  => 11,
                'name'  => 'Calibri',
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1E3A5F'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color'       => ['argb' => 'FF0D2340'],
                ],
            ],
        ]);

        // ── Set header row height ─────────────────────────────────────────────
        $sheet->getRowDimension(1)->setRowHeight(24);

        // ── Freeze header row ─────────────────────────────────────────────────
        $sheet->freezePane('A2');

        // ── Alternate row colors & borders for data rows ──────────────────────
        for ($row = 2; $row <= $lastRow; $row++) {
            $fillColor = ($row % 2 === 0) ? 'FFF0F4FA' : 'FFFFFFFF';

            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => $fillColor],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['argb' => 'FFB0BEC5'],
                    ],
                ],
            ]);

            // Row height
            $sheet->getRowDimension($row)->setRowHeight(20);
        }

        // ── Outer border for the whole table ──────────────────────────────────
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color'       => ['argb' => 'FF1E3A5F'],
                ],
            ],
        ]);

        // ── Number column: center align ───────────────────────────────────────
        $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D2:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E2:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F2:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }
}
