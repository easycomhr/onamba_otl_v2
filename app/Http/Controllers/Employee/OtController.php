<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreOtRequest;
use App\Models\OtRequest;
use App\Services\OtService;
use App\Services\TeamsWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OtController extends Controller
{
    public function __construct(private OtService $service)
    {
    }

    public function index()
    {
        $requests = OtRequest::where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('employee.ot.list', compact('requests'));
    }

    public function create()
    {
        return view('employee.ot.register');
    }

    public function store(StoreOtRequest $request)
    {
        try {
            $otRequest = $this->service->store($request->validated(), Auth::user());
            $user = auth()->user()->load('team');
            if ($user->team && $user->team->ms_teams_webhook_url) {
                app(TeamsWebhookService::class)->sendOtNotification($user->team, $otRequest);
            }

            return redirect()->route('employee.ot.index')
                ->with('success', 'Đăng ký tăng ca thành công. Đang chờ duyệt.');
        } catch (\Throwable $e) {
            Log::error('Employee OT store error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->withErrors([
                'error' => 'Đã xảy ra lỗi khi đăng ký tăng ca.',
            ]);
        }
    }

    public function destroy(OtRequest $otRequest)
    {
        abort_unless($otRequest->user_id === Auth::id(), 403);
        abort_if($otRequest->status !== OtRequest::STATUS_PENDING, 422);

        $otRequest->delete();

        return redirect()->route('employee.ot.index')
            ->with('success', 'Đã xóa đơn tăng ca thành công.');
    }

    public function sos(Request $request, OtRequest $otRequest)
    {
        abort_unless($otRequest->user_id === Auth::id(), 403);

        if (!$otRequest->isSosEligible()) {
            return back()->with('error', 'Không thể gửi SOS cho đơn này.');
        }

        $request->validate([
            'sos_reason' => 'required|string|max:500',
        ]);

        try {
            $otRequest->update([
                'sos_requested_at' => now(),
                'sos_reason'       => $request->sos_reason,
            ]);

            app(TeamsWebhookService::class)->sendAdminSosOt($otRequest);

            return back()->with('success', 'Đã gửi yêu cầu SOS. Admin sẽ xem xét và hỗ trợ bạn.');
        } catch (\Throwable $e) {
            Log::error('Employee OT SOS error', ['ot_id' => $otRequest->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Đã xảy ra lỗi khi gửi SOS.');
        }
    }
}
