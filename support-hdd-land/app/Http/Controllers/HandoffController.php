<?php

namespace App\Http\Controllers;

use App\Models\DeviceHandoff;
use App\Models\Reception;
use App\Models\Technician;
use App\Services\StaffNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HandoffController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $ticket = trim((string) $request->input('ticket_no', ''));
        $serial = trim((string) $request->input('serial', ''));
        $q = trim((string) $request->input('q', ''));
        $status = (string) $request->input('status', 'pending');
        if (! in_array($status, ['pending', 'all', 'accepted', 'rejected', 'in_hand'], true)) {
            $status = 'pending';
        }

        $pendingQuery = DeviceHandoff::query()
            ->with(['reception.customer', 'fromUser', 'toTechnician'])
            ->where('status', DeviceHandoff::STATUS_PENDING)
            ->where(function ($qBuilder) use ($user) {
                $qBuilder->where('to_user_id', $user->id);
                if ($user->technician) {
                    $qBuilder->orWhere('to_technician_id', $user->technician->id);
                }
                if ($user->canAccess('receptions')) {
                    $qBuilder->orWhere(function ($q2) {
                        $q2->where('direction', DeviceHandoff::DIR_TO_FRONT)
                            ->whereNull('to_user_id');
                    });
                    $qBuilder->orWhere(function ($q2) {
                        $q2->where('direction', DeviceHandoff::DIR_TO_BENCH)
                            ->whereNull('to_user_id');
                    });
                }
            });

        $this->applyHandoffSearch($pendingQuery, $ticket, $serial, $q);
        $pending = $pendingQuery->latest('id')->limit(100)->get();

        $inHand = collect();
        if ($user->technician || $user->canAccess('receptions') || $user->canAccess('reports.custody')) {
            $inHandQuery = Reception::query()
                ->with(['customer', 'technician', 'custodyTechnician'])
                ->where('custody', 'with_technician')
                ->whereNotIn('status', ['delivered', 'cancelled']);

            if ($user->technician && ! $user->canAccess('receptions') && ! $user->isAdmin()) {
                $inHandQuery->where('custody_technician_id', $user->technician->id);
            }

            if ($ticket !== '') {
                $inHandQuery->where('ticket_no', 'like', '%'.$ticket.'%');
            }
            if ($serial !== '') {
                $inHandQuery->where('serial_number', 'like', '%'.$serial.'%');
            }
            if ($q !== '') {
                $inHandQuery->where(function ($inner) use ($q) {
                    $inner->where('ticket_no', 'like', '%'.$q.'%')
                        ->orWhere('serial_number', 'like', '%'.$q.'%')
                        ->orWhere('product_name', 'like', '%'.$q.'%')
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', '%'.$q.'%')->orWhere('phone', 'like', '%'.$q.'%'));
                });
            }

            $inHand = $inHandQuery->latest('id')->limit(80)->get();
        }

        $history = collect();
        if (in_array($status, ['all', 'accepted', 'rejected'], true) || $ticket !== '' || $serial !== '' || $q !== '') {
            $historyQuery = DeviceHandoff::query()
                ->with(['reception.customer', 'fromUser', 'toTechnician'])
                ->latest('id');

            if ($status === 'accepted') {
                $historyQuery->where('status', DeviceHandoff::STATUS_ACCEPTED);
            } elseif ($status === 'rejected') {
                $historyQuery->where('status', DeviceHandoff::STATUS_REJECTED);
            } elseif ($status === 'pending') {
                // keep empty when only searching pending; filled below if search terms
            } else {
                // all
            }

            $this->applyHandoffSearch($historyQuery, $ticket, $serial, $q);

            if ($status === 'pending' && ($ticket !== '' || $serial !== '' || $q !== '')) {
                $historyQuery->where('status', '!=', DeviceHandoff::STATUS_PENDING);
            }

            $history = $historyQuery->limit(60)->get();
        }

        $stats = [
            'pending' => DeviceHandoff::query()->where('status', DeviceHandoff::STATUS_PENDING)->count(),
            'accepted_today' => DeviceHandoff::query()
                ->where('status', DeviceHandoff::STATUS_ACCEPTED)
                ->whereDate('responded_at', now('Asia/Tehran')->toDateString())
                ->count(),
            'in_hand' => Reception::query()
                ->where('custody', 'with_technician')
                ->whereNotIn('status', ['delivered', 'cancelled'])
                ->count(),
            'rejected_today' => DeviceHandoff::query()
                ->where('status', DeviceHandoff::STATUS_REJECTED)
                ->whereDate('responded_at', now('Asia/Tehran')->toDateString())
                ->count(),
        ];

        return view('handoffs.index', compact(
            'pending', 'inHand', 'history', 'stats', 'ticket', 'serial', 'q', 'status'
        ));
    }

    private function applyHandoffSearch($query, string $ticket, string $serial, string $q): void
    {
        if ($ticket !== '') {
            $query->whereHas('reception', fn ($r) => $r->where('ticket_no', 'like', '%'.$ticket.'%'));
        }
        if ($serial !== '') {
            $query->where(function ($inner) use ($serial) {
                $inner->where('serial_snapshot', 'like', '%'.$serial.'%')
                    ->orWhereHas('reception', fn ($r) => $r->where('serial_number', 'like', '%'.$serial.'%'));
            });
        }
        if ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $inner->where('serial_snapshot', 'like', '%'.$q.'%')
                    ->orWhere('note', 'like', '%'.$q.'%')
                    ->orWhereHas('reception', function ($r) use ($q) {
                        $r->where('ticket_no', 'like', '%'.$q.'%')
                            ->orWhere('serial_number', 'like', '%'.$q.'%')
                            ->orWhere('product_name', 'like', '%'.$q.'%')
                            ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', '%'.$q.'%')->orWhere('phone', 'like', '%'.$q.'%'));
                    });
            });
        }
    }

    public function store(Request $request, Reception $reception, StaffNotifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'direction' => ['required', 'in:to_bench,to_front_desk'],
            'technician_id' => ['nullable', 'exists:technicians,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        abort_unless($user->canAccess('receptions') || $user->role === 'technician', 403);

        if ($data['direction'] === DeviceHandoff::DIR_TO_BENCH) {
            abort_unless($user->canAccess('receptions'), 403);
            $tech = Technician::query()->with('user')->findOrFail($data['technician_id'] ?? 0);
            abort_unless($tech->is_active, 422);

            $existing = DeviceHandoff::query()
                ->where('reception_id', $reception->id)
                ->where('status', DeviceHandoff::STATUS_PENDING)
                ->exists();
            if ($existing) {
                return back()->withErrors(['technician_id' => 'یک ارجاع در انتظار تأیید برای این قبض وجود دارد.']);
            }

            $handoff = DeviceHandoff::query()->create([
                'reception_id' => $reception->id,
                'from_user_id' => $user->id,
                'to_user_id' => $tech->user_id,
                'to_technician_id' => $tech->id,
                'direction' => DeviceHandoff::DIR_TO_BENCH,
                'serial_snapshot' => $reception->serial_number,
                'status' => DeviceHandoff::STATUS_PENDING,
                'note' => $data['note'] ?? null,
            ]);

            $targets = collect();
            if ($tech->user_id) {
                $targets->push($tech->user_id);
            } else {
                $targets = $notifier->deskUsers()->pluck('id');
            }

            $notifier->notifyMany(
                $targets,
                'handoff_pending',
                'ارجاع دستگاه برای دریافت',
                "قبض {$reception->ticket_no} — سریال: ".($reception->serial_number ?: '—')." — آیا دستگاه را دریافت کردید؟",
                route('handoffs.index'),
                ['handoff_id' => $handoff->id, 'reception_id' => $reception->id]
            );

            return back()->with('success', 'ارجاع به تعمیرکار ثبت شد. منتظر تأیید دریافت بمانید.');
        }

        // to_front_desk — technician returns device (or desk records physical return)
        $tech = $user->technician;
        $isOwningTech = $tech && (int) $reception->custody_technician_id === (int) $tech->id;
        $isDeskProxy = $user->canAccess('receptions')
            && ($reception->custody ?? '') === 'with_technician'
            && $reception->custody_technician_id;

        abort_unless($isOwningTech || $isDeskProxy, 403);

        $existing = DeviceHandoff::query()
            ->where('reception_id', $reception->id)
            ->where('status', DeviceHandoff::STATUS_PENDING)
            ->exists();
        if ($existing) {
            return back()->withErrors(['note' => 'ارجاع باز دیگری برای این قبض وجود دارد.']);
        }

        $techId = $isOwningTech ? $tech->id : (int) $reception->custody_technician_id;
        $techName = $isOwningTech
            ? $tech->name
            : (Technician::query()->find($techId)?->name ?? 'تعمیرکار');

        // Desk recording a physical return: accept immediately (receiver = desk)
        if ($isDeskProxy && ! $isOwningTech) {
            $handoff = DeviceHandoff::query()->create([
                'reception_id' => $reception->id,
                'from_user_id' => $user->id,
                'to_user_id' => $user->id,
                'to_technician_id' => $techId,
                'direction' => DeviceHandoff::DIR_TO_FRONT,
                'serial_snapshot' => $reception->serial_number,
                'status' => DeviceHandoff::STATUS_ACCEPTED,
                'note' => $data['note'] ?? 'ثبت بازگشت فیزیکی توسط پذیرش',
                'response_note' => 'تأیید دریافت توسط پذیرش',
                'responded_at' => now(),
            ]);

            $reception->forceFill([
                'custody' => 'front_desk',
                'custody_technician_id' => null,
            ])->save();

            return back()->with('success', "بازگشت دستگاه از {$techName} در پذیرش ثبت شد.");
        }

        $handoff = DeviceHandoff::query()->create([
            'reception_id' => $reception->id,
            'from_user_id' => $user->id,
            'to_user_id' => null,
            'to_technician_id' => $techId,
            'direction' => DeviceHandoff::DIR_TO_FRONT,
            'serial_snapshot' => $reception->serial_number,
            'status' => DeviceHandoff::STATUS_PENDING,
            'note' => $data['note'] ?? null,
        ]);

        $reception->forceFill(['custody' => 'returning'])->save();

        $desk = $notifier->deskUsers();
        $notifier->notifyMany(
            $desk,
            'handoff_pending',
            'بازگشت دستگاه از تعمیرکار',
            "قبض {$reception->ticket_no} توسط {$techName} برای پذیرش برگشت داده شد. دریافت را تأیید کنید.",
            route('handoffs.index'),
            ['handoff_id' => $handoff->id, 'reception_id' => $reception->id]
        );

        return back()->with('success', 'درخواست بازگشت به پذیرش ثبت شد.');
    }

    public function respond(Request $request, DeviceHandoff $handoff, StaffNotifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:accepted,rejected'],
            'response_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        abort_unless($handoff->isPending(), 422);

        $canRespond = false;
        if ($handoff->direction === DeviceHandoff::DIR_TO_BENCH) {
            $canRespond = (int) $handoff->to_user_id === (int) $user->id
                || ($user->technician && (int) $handoff->to_technician_id === (int) $user->technician->id)
                || ($user->canAccess('receptions') && ! $handoff->to_user_id);
        } else {
            $canRespond = $user->canAccess('receptions');
        }
        abort_unless($canRespond, 403);

        $handoff->forceFill([
            'status' => $data['decision'],
            'response_note' => $data['response_note'] ?? null,
            'responded_at' => now(),
            'to_user_id' => $handoff->to_user_id ?: $user->id,
        ])->save();

        $reception = $handoff->reception()->first();

        if ($data['decision'] === DeviceHandoff::STATUS_ACCEPTED && $reception) {
            if ($handoff->direction === DeviceHandoff::DIR_TO_BENCH) {
                $reception->forceFill([
                    'technician_id' => $handoff->to_technician_id,
                    'custody_technician_id' => $handoff->to_technician_id,
                    'custody' => 'with_technician',
                    'status' => $reception->status === 'received' ? 'repairing' : $reception->status,
                ])->save();
            } else {
                $reception->forceFill([
                    'custody' => 'front_desk',
                    'custody_technician_id' => null,
                ])->save();
            }
        } elseif ($data['decision'] === DeviceHandoff::STATUS_REJECTED && $reception) {
            if ($handoff->direction === DeviceHandoff::DIR_TO_FRONT) {
                $reception->forceFill(['custody' => 'with_technician'])->save();
            }
        }

        $notifier->notifyMany(
            [$handoff->from_user_id],
            'handoff_result',
            $data['decision'] === 'accepted' ? 'دریافت دستگاه تأیید شد' : 'دریافت دستگاه رد شد',
            "قبض {$reception?->ticket_no} — ".$handoff->directionLabel(),
            $reception ? route('receptions.show', $reception) : route('handoffs.index'),
            ['handoff_id' => $handoff->id]
        );

        return back()->with('success', $data['decision'] === 'accepted' ? 'دریافت تأیید شد.' : 'دریافت رد شد.');
    }
}
