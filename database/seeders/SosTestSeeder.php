<?php

namespace Database\Seeders;

use App\Models\LeaveRequest;
use App\Models\OtRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seed pending OT/Leave requests (>32h old, no SOS) for Team Xưởng B members.
 * Purpose: test the SOS escalation feature on both employee and admin sides.
 *
 * Run: php artisan db:seed --class=SosTestSeeder
 */
class SosTestSeeder extends Seeder
{
    public function run(): void
    {
        // ── Find or create Team Xưởng B ───────────────────────────
        $team = Team::where('name', 'Team Xưởng B')->first();
        if (!$team) {
            $this->command->error('Team Xưởng B không tìm thấy. Hãy chạy TeamSeeder trước.');
            return;
        }

        // ── Find or create a manager (main approver) ──────────────
        $manager = User::where('role', 'manager')->first();
        if (!$manager) {
            $this->command->error('Không tìm thấy manager. Hãy chạy DatabaseSeeder trước.');
            return;
        }

        // ── Test employees for Team Xưởng B ──────────────────────
        $testEmployees = [
            ['name' => 'Nguyễn Văn SOS1', 'employee_code' => 'SOS001', 'position' => 'Công nhân',  'leave' => 12],
            ['name' => 'Trần Thị SOS2',   'employee_code' => 'SOS002', 'position' => 'Tổ trưởng',  'leave' => 10],
            ['name' => 'Lê Văn SOS3',     'employee_code' => 'SOS003', 'position' => 'Công nhân',  'leave' => 8],
        ];

        $employees = [];
        foreach ($testEmployees as $emp) {
            $user = User::firstOrCreate(
                ['employee_code' => $emp['employee_code']],
                [
                    'name'                 => $emp['name'],
                    'email'                => strtolower($emp['employee_code']) . '@company.com',
                    'password'             => Hash::make('password'),
                    'role'                 => 'employee',
                    'department'           => 'Xưởng B',
                    'position'             => $emp['position'],
                    'annual_leave_balance' => $emp['leave'],
                    'team_id'              => $team->id,
                ]
            );

            // Ensure existing users are also assigned to Team Xưởng B
            if ($user->team_id !== $team->id) {
                $user->update(['team_id' => $team->id]);
            }

            $employees[] = $user;
        }

        // ── Also grab real Team Xưởng B members (if any exist) ───
        $realMembers = User::where('team_id', $team->id)
            ->whereNotIn('employee_code', array_column($testEmployees, 'employee_code'))
            ->limit(2)
            ->get();

        $allTestUsers = array_merge($employees, $realMembers->all());

        // ── Overdue OT Requests (created 40h ago) ─────────────────
        $overdueCreatedAt = now()->subHours(40);

        $otScenarios = [
            // Employee 0: pending overdue, no SOS → SOS button visible
            [
                'user'          => $allTestUsers[0],
                'ot_date'       => now()->subDays(2)->format('Y-m-d'),
                'hours'         => 3.5,
                'reason'        => '[SOS TEST] Hỗ trợ dây chuyền xưởng B sự cố đêm khuya',
                'status'        => 'pending',
                'sos'           => false,
                'code_suffix'   => 'S1',
            ],
            // Employee 1: pending overdue, no SOS → SOS button visible
            [
                'user'          => $allTestUsers[1],
                'ot_date'       => now()->subDays(2)->format('Y-m-d'),
                'hours'         => 5.0,
                'reason'        => '[SOS TEST] Bảo trì khẩn cấp máy đóng gói xưởng B',
                'status'        => 'pending',
                'sos'           => false,
                'code_suffix'   => 'S2',
            ],
            // Employee 2: pending overdue, SOS already sent → shows "SOS đã gửi" badge
            [
                'user'          => $allTestUsers[2] ?? $allTestUsers[0],
                'ot_date'       => now()->subDays(3)->format('Y-m-d'),
                'hours'         => 2.0,
                'reason'        => '[SOS TEST] Làm thêm giờ xuất hàng gấp xưởng B',
                'status'        => 'pending',
                'sos'           => true,
                'sos_reason'    => 'Tổ trưởng đã nhắc 3 lần nhưng không có phản hồi, cần admin hỗ trợ duyệt gấp.',
                'code_suffix'   => 'S3',
            ],
        ];

        foreach ($otScenarios as $s) {
            $code = 'OT-SOS-' . $s['code_suffix'];
            if (OtRequest::where('code', $code)->exists()) {
                $this->command->line("  Skip OT {$code} (đã tồn tại)");
                continue;
            }

            OtRequest::create([
                'user_id'          => $s['user']->id,
                'code'             => $code,
                'ot_date'          => $s['ot_date'],
                'hours'            => $s['hours'],
                'reason'           => $s['reason'],
                'status'           => $s['status'],
                'created_at'       => $overdueCreatedAt,
                'updated_at'       => $overdueCreatedAt,
                'sos_requested_at' => $s['sos'] ? now()->subHours(2) : null,
                'sos_reason'       => $s['sos'] ? ($s['sos_reason'] ?? null) : null,
            ]);

            $this->command->line("  Created OT {$code} for {$s['user']->name}" . ($s['sos'] ? ' [SOS sent]' : ' [SOS pending]'));
        }

        // ── Overdue Leave Requests (created 40h ago) ──────────────
        $leaveScenarios = [
            // Employee 0: pending overdue, no SOS → SOS button visible
            [
                'user'        => $allTestUsers[0],
                'leave_type'  => 'annual',
                'from_date'   => now()->addDays(2)->format('Y-m-d'),
                'to_date'     => now()->addDays(3)->format('Y-m-d'),
                'days'        => 2,
                'reason'      => '[SOS TEST] Phép năm đã đăng ký trước 2 ngày, chưa được duyệt',
                'status'      => 'pending',
                'sos'         => false,
                'code_suffix' => 'S1',
            ],
            // Employee 1: pending overdue, SOS already sent → shows badge
            [
                'user'        => $allTestUsers[1],
                'leave_type'  => 'sick',
                'from_date'   => now()->subDays(1)->format('Y-m-d'),
                'to_date'     => now()->format('Y-m-d'),
                'days'        => 1,
                'reason'      => '[SOS TEST] Nghỉ ốm, đã nộp giấy khám bệnh',
                'status'      => 'pending',
                'sos'         => true,
                'sos_reason'  => 'Nghỉ ốm gấp, tổ trưởng không online, nhờ admin duyệt gấp.',
                'code_suffix' => 'S2',
            ],
        ];

        foreach ($leaveScenarios as $s) {
            $code = 'LV-SOS-' . $s['code_suffix'];
            if (LeaveRequest::where('code', $code)->exists()) {
                $this->command->line("  Skip Leave {$code} (đã tồn tại)");
                continue;
            }

            LeaveRequest::create([
                'user_id'          => $s['user']->id,
                'code'             => $code,
                'leave_type'       => $s['leave_type'],
                'from_date'        => $s['from_date'],
                'to_date'          => $s['to_date'],
                'days'             => $s['days'],
                'reason'           => $s['reason'],
                'status'           => $s['status'],
                'created_at'       => $overdueCreatedAt,
                'updated_at'       => $overdueCreatedAt,
                'sos_requested_at' => $s['sos'] ? now()->subHours(1) : null,
                'sos_reason'       => $s['sos'] ? ($s['sos_reason'] ?? null) : null,
            ]);

            $this->command->line("  Created Leave {$code} for {$s['user']->name}" . ($s['sos'] ? ' [SOS sent]' : ' [SOS pending]'));
        }

        $this->command->info('SosTestSeeder hoàn thành!');
        $this->command->info('Login với employee_code SOS001/SOS002/SOS003, password: password');
        $this->command->info('SOS001 & SOS002 sẽ thấy nút 🆘 SOS trên OT list và Leave list.');
        $this->command->info('Admin login vào /admin/approvals/ot để test chặn approve khi chưa có SOS.');
    }
}
