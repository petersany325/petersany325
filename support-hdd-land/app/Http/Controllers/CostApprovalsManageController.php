<?php

namespace App\Http\Controllers;

use App\Models\CostApproval;
use App\Models\Reception;
use App\Services\CostApprovalService;
use App\Support\CostApprovalSettings;
use Illuminate\Http\Request;

class CostApprovalsManageController extends Controller
{
    public function index(Request $request)
    {
        $status = trim((string) $request->get('status', ''));
        $q = normalize_receipt_search_query((string) $request->get('q', ''));

        $approvals = CostApproval::query()
            ->with(['reception.customer', 'customer', 'creator'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('approval_code', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhereHas('reception', function ($r) use ($q) {
                            $r->where('ticket_no', 'like', "%{$q}%")
                                ->orWhere('receipt_no', 'like', "%{$q}%")
                                ->orWhere('serial_number', 'like', "%{$q}%")
                                ->orWhere('service_type', 'like', "%{$q}%")
                                ->orWhere('repair_type', 'like', "%{$q}%");
                        })
                        ->orWhereHas('customer', function ($c) use ($q) {
                            $c->where('name', 'like', "%{$q}%")
                                ->orWhere('phone', 'like', "%{$q}%");
                        });
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'pending' => CostApproval::query()->whereIn('status', ['sent', 'viewed'])->count(),
            'approved' => CostApproval::query()->where('status', 'approved')->count(),
            'rejected' => CostApproval::query()->where('status', 'rejected')->count(),
            'total' => CostApproval::query()->count(),
        ];

        $needsApproval = Reception::query()
            ->with(['customer', 'latestCostApproval'])
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->where(function ($query) {
                $services = CostApprovalSettings::enabledServices();
                if ($services === []) {
                    $query->whereRaw('1=0');

                    return;
                }
                $query->where(function ($inner) use ($services) {
                    foreach ($services as $name) {
                        $inner->orWhere('service_type', 'like', '%'.$name.'%')
                            ->orWhere('repair_type', 'like', '%'.$name.'%');
                    }
                });
            })
            ->where(function ($query) {
                $query->whereNull('cost_approval_status')
                    ->orWhereNotIn('cost_approval_status', ['approved']);
            })
            ->latest('id')
            ->limit(30)
            ->get()
            ->filter(fn (Reception $r) => CostApprovalSettings::receptionRequiresApproval($r))
            ->values();

        return view('cost-approvals.index', [
            'approvals' => $approvals,
            'stats' => $stats,
            'status' => $status,
            'q' => $q,
            'needsApproval' => $needsApproval,
            'enabledServices' => CostApprovalSettings::enabledServices(),
            'statusLabels' => CostApproval::statusLabels(),
        ]);
    }

    public function settings()
    {
        return view('cost-approvals.settings', [
            'enabled' => CostApprovalSettings::enabledServices(),
            'options' => CostApprovalSettings::selectableServiceNames(),
            'terms' => \App\Models\AppSetting::getValue(
                'cost_approval_terms',
                app(CostApprovalService::class)->defaultTerms()
            ),
        ]);
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'services' => ['nullable', 'array'],
            'services.*' => ['string', 'max:120'],
            'custom_service' => ['nullable', 'string', 'max:120'],
            'terms' => ['nullable', 'string', 'max:2000'],
        ]);

        $services = $data['services'] ?? [];
        if (! empty($data['custom_service'])) {
            $services[] = $data['custom_service'];
        }
        CostApprovalSettings::setEnabledServices($services);

        if (array_key_exists('terms', $data)) {
            \App\Models\AppSetting::setValue('cost_approval_terms', (string) ($data['terms'] ?? ''));
        }

        return redirect()
            ->route('cost-approvals.settings')
            ->with('success', 'خدمات مشمول تأیید هزینه ذخیره شد.');
    }

    public function send(Request $request, Reception $reception, CostApprovalService $approvals)
    {
        if (! CostApprovalSettings::receptionRequiresApproval($reception) && ! $request->boolean('force')) {
            return back()->withErrors([
                'cost_approval' => 'این قبض در فهرست خدمات مشمول تأیید (مثل جراحی/بازیابی) نیست. یا خدمت را درست کنید یا با تأیید اجباری ارسال کنید.',
            ]);
        }

        $data = $request->validate([
            'description' => ['nullable', 'string', 'max:1000'],
            'send_sms' => ['nullable', 'boolean'],
            'force' => ['nullable', 'boolean'],
        ]);

        $result = $approvals->requestAndSend(
            $reception->fresh(['customer', 'parts', 'faultType', 'technician']),
            $data['description'] ?? null,
            $request->boolean('send_sms', true),
            $request->boolean('force') || CostApprovalSettings::receptionRequiresApproval($reception)
        );

        if (! ($result['ok'] ?? false)) {
            return back()->withErrors(['cost_approval' => $result['message'] ?? 'ارسال ناموفق بود.']);
        }

        return back()->with('success', $result['message']);
    }
}
