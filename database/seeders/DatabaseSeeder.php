<?php

namespace Database\Seeders;

use App\Models\LeaveRequest;
use App\Models\OtRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Real employees from tblusers.sql ──────────────────
        $this->call(UserSeeder::class);

        // ── Manager ───────────────────────────────────────────
        $manager = User::firstOrCreate(
            ['employee_code' => 'QL00001'],
            [
                'name'                 => 'Nguyễn Văn Quản Lý',
                'email'                => 'manager@company.com',
                'password'             => Hash::make('password'),
                'role'                 => 'manager',
                'department'           => 'Ban Quản lý',
                'position'             => 'Quản lý hệ thống',
                'annual_leave_balance' => 15,
            ]
        );

        // ── Admin Roles ───────────────────────────────────────
        $this->call(AdminRoleSeeder::class);

        // ── Teams ─────────────────────────────────────────────
        $this->call(TeamSeeder::class);

        // ── Employees ─────────────────────────────────────────
        $rawEmployees = [
            ['name'=>'Nguyễn Văn A', 'employee_code'=>'NV12345', 'dept'=>'Xưởng A',  'position'=>'Công nhân',    'leave'=>5],
            ['name'=>'Trần Thị B',   'employee_code'=>'NV12346', 'dept'=>'Xưởng B',  'position'=>'Tổ trưởng',    'leave'=>8],
            ['name'=>'Lê Văn C',     'employee_code'=>'NV12347', 'dept'=>'Kho Vận',  'position'=>'Công nhân',    'leave'=>3],
            ['name'=>'Phạm Thị D',   'employee_code'=>'NV12348', 'dept'=>'Phòng QC', 'position'=>'Kiểm soát CL', 'leave'=>10],
        ];

        $employees = [];
        foreach ($rawEmployees as $emp) {
            $employees[] = User::firstOrCreate(
                ['employee_code' => $emp['employee_code']],
                [
                    'name'                 => $emp['name'],
                    'email'                => strtolower($emp['employee_code']) . '@company.com',
                    'password'             => Hash::make('password'),
                    'role'                 => 'employee',
                    'department'           => $emp['dept'],
                    'position'             => $emp['position'],
                    'annual_leave_balance' => $emp['leave'],
                ]
            );
        }

        // ── OT Requests ───────────────────────────────────────
        // Case 1a: Đăng ký đúng hạn — pending < 32h, isOverdue()=false
        $ot1 = OtRequest::create([
            'user_id' => $employees[0]->id,
            'ot_date' => '2026-03-28',
            'hours'   => 2.5,
            'reason'  => '[ĐÚNG HẠN] Hỗ trợ dây chuyền lắp ráp xưởng A',
            'status'  => 'pending',
        ]);
        DB::table('ot_requests')->where('id', $ot1->id)->update(['created_at' => now()->subHours(10)]);

        // Case 1b: Đăng ký đúng hạn — pending < 32h (gần hết hạn ~30h)
        $ot2 = OtRequest::create([
            'user_id' => $employees[1]->id,
            'ot_date' => '2026-03-27',
            'hours'   => 4.0,
            'reason'  => '[ĐÚNG HẠN] Bảo trì máy đóng gói định kỳ',
            'status'  => 'pending',
        ]);
        DB::table('ot_requests')->where('id', $ot2->id)->update(['created_at' => now()->subHours(30)]);

        // Case 2a: Quá hạn — pending >= 32h, chưa SOS (isSosEligible()=true)
        $ot3 = OtRequest::create([
            'user_id' => $employees[2]->id,
            'ot_date' => '2026-03-20',
            'hours'   => 3.0,
            'reason'  => '[QUÁ HẠN - Chưa SOS] Kiểm tra hàng xuất kho',
            'status'  => 'pending',
        ]);
        DB::table('ot_requests')->where('id', $ot3->id)->update(['created_at' => now()->subHours(48)]);

        // Case 2b: Quá hạn — pending >= 32h, đã gửi SOS (isSosEligible()=false)
        $ot4 = OtRequest::create([
            'user_id'          => $employees[3]->id,
            'ot_date'          => '2026-03-18',
            'hours'            => 5.0,
            'reason'           => '[QUÁ HẠN - Đã SOS] Hoàn thành đơn hàng cuối tháng',
            'status'           => 'pending',
            'sos_requested_at' => now()->subHours(5),
            'sos_reason'       => 'Cần duyệt gấp vì chưa nhận được phản hồi.',
        ]);
        DB::table('ot_requests')->where('id', $ot4->id)->update(['created_at' => now()->subHours(72)]);

        // Case 3: Đã duyệt (baseline)
        OtRequest::create([
            'user_id'        => $employees[0]->id,
            'approved_by'    => $manager->id,
            'ot_date'        => '2026-03-10',
            'hours'          => 3.0,
            'approved_hours' => 3.0,
            'reason'         => 'Hoàn thành đơn hàng gấp xưởng A',
            'manager_note'   => 'Đồng ý, nhớ tắt máy trước khi về.',
            'status'         => 'approved',
            'approved_at'    => now()->subDays(3),
        ]);

        // Case 4: Từ chối (baseline)
        OtRequest::create([
            'user_id'      => $employees[2]->id,
            'approved_by'  => $manager->id,
            'ot_date'      => '2026-03-05',
            'hours'        => 4.0,
            'reason'       => 'Kiểm tra hàng xuất kho',
            'manager_note' => 'Đã đủ người, không cần làm thêm.',
            'status'       => 'rejected',
            'rejected_at'  => now()->subDays(5),
        ]);

        // ── Leave Requests ────────────────────────────────────
        // Case 1a: Đăng ký đúng hạn — pending < 32h
        $lv1 = LeaveRequest::create([
            'user_id'    => $employees[0]->id,
            'leave_type' => 'annual',
            'from_date'  => '2026-03-31',
            'to_date'    => '2026-04-01',
            'reason'     => '[ĐÚNG HẠN] Việc gia đình',
            'status'     => 'pending',
        ]);
        DB::table('leave_requests')->where('id', $lv1->id)->update(['created_at' => now()->subHours(8)]);

        // Case 1b: Đăng ký đúng hạn — pending < 32h (gần hết hạn ~28h)
        $lv2 = LeaveRequest::create([
            'user_id'    => $employees[1]->id,
            'leave_type' => 'sick',
            'from_date'  => '2026-03-31',
            'to_date'    => '2026-03-31',
            'reason'     => '[ĐÚNG HẠN] Khám sức khỏe định kỳ',
            'status'     => 'pending',
        ]);
        DB::table('leave_requests')->where('id', $lv2->id)->update(['created_at' => now()->subHours(28)]);

        // Case 2a: Quá hạn — pending >= 32h, chưa SOS (isSosEligible()=true)
        $lv3 = LeaveRequest::create([
            'user_id'    => $employees[2]->id,
            'leave_type' => 'annual',
            'from_date'  => '2026-03-25',
            'to_date'    => '2026-03-26',
            'reason'     => '[QUÁ HẠN - Chưa SOS] Việc cá nhân quan trọng',
            'status'     => 'pending',
        ]);
        DB::table('leave_requests')->where('id', $lv3->id)->update(['created_at' => now()->subHours(50)]);

        // Case 2b: Quá hạn — pending >= 32h, đã gửi SOS (isSosEligible()=false)
        $lv4 = LeaveRequest::create([
            'user_id'          => $employees[3]->id,
            'leave_type'       => 'personal',
            'from_date'        => '2026-03-24',
            'to_date'          => '2026-03-24',
            'reason'           => '[QUÁ HẠN - Đã SOS] Việc cá nhân cấp bách',
            'status'           => 'pending',
            'sos_requested_at' => now()->subHours(3),
            'sos_reason'       => 'Xin duyệt gấp, đã đợi 3 ngày chưa có phản hồi.',
        ]);
        DB::table('leave_requests')->where('id', $lv4->id)->update(['created_at' => now()->subHours(80)]);

        // Case 3: Đã duyệt (baseline)
        LeaveRequest::create([
            'user_id'     => $employees[2]->id,
            'approved_by' => $manager->id,
            'leave_type'  => 'annual',
            'from_date'   => '2026-03-05',
            'to_date'     => '2026-03-05',
            'reason'      => 'Việc cá nhân',
            'status'      => 'approved',
            'approved_at' => now()->subDays(10),
        ]);
    }
}
