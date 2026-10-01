<?php

namespace App\Http\Controllers;

use App\Models\CostApproval;
use App\Services\CostApprovalService;
use Illuminate\Http\Request;

class CostApprovalController extends Controller
{
    public function show(string $token, CostApprovalService $approvals)
    {
        $approval = $approvals->findByPlainToken($token);
        if (! $approval) {
            return response()
                ->view('approvals.invalid', ['reason' => 'not_found'])
                ->setStatusCode(404);
        }

        $approval = $approvals->markViewed($approval, request());

        if ($approval->status === CostApproval::STATUS_APPROVED) {
            return view('approvals.done', [
                'approval' => $approval,
                'mode' => 'approved',
            ]);
        }

        if ($approval->status === CostApproval::STATUS_REJECTED) {
            return view('approvals.done', [
                'approval' => $approval,
                'mode' => 'rejected',
            ]);
        }

        if ($approval->isExpired() || $approval->status === CostApproval::STATUS_EXPIRED) {
            return view('approvals.invalid', [
                'reason' => 'expired',
                'approval' => $approval,
            ]);
        }

        if ($approval->status === CostApproval::STATUS_SUPERSEDED) {
            return view('approvals.invalid', [
                'reason' => 'superseded',
                'approval' => $approval,
            ]);
        }

        return view('approvals.show', [
            'approval' => $approval,
            'token' => $token,
            'reception' => $approval->reception,
        ]);
    }

    public function approve(string $token, Request $request, CostApprovalService $approvals)
    {
        $approval = $approvals->findByPlainToken($token);
        if (! $approval) {
            return redirect()->route('approvals.show', $token);
        }

        $data = $request->validate([
            'accept_terms' => ['accepted'],
        ], [
            'accept_terms.accepted' => 'برای تأیید باید شرایط را بپذیرید.',
        ]);

        $result = $approvals->approve($approval, $request);
        if (! ($result['ok'] ?? false)) {
            return back()->withErrors(['approval' => $result['message'] ?? 'تأیید ممکن نیست.']);
        }

        return redirect()
            ->route('approvals.show', $token)
            ->with('success', $result['message']);
    }

    public function reject(string $token, Request $request, CostApprovalService $approvals)
    {
        $approval = $approvals->findByPlainToken($token);
        if (! $approval) {
            return redirect()->route('approvals.show', $token);
        }

        $data = $request->validate([
            'reject_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $approvals->reject($approval, $request, $data['reject_reason'] ?? null);
        if (! ($result['ok'] ?? false)) {
            return back()->withErrors(['approval' => $result['message'] ?? 'ثبت رد ممکن نیست.']);
        }

        return redirect()
            ->route('approvals.show', $token)
            ->with('success', $result['message']);
    }
}
