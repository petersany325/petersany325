<?php

namespace App\Http\Controllers;

use App\Models\Reception;
use App\Models\ReceptionWorkReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkReportController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $visibility = (string) $request->get('visibility', '');
        $user = Auth::user();

        $reports = ReceptionWorkReport::query()
            ->with(['reception.customer', 'user', 'technician'])
            ->when($visibility !== '' && isset(ReceptionWorkReport::VISIBILITIES[$visibility]), fn ($query) => $query->where('visibility', $visibility))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('summary', 'like', "%{$q}%")
                        ->orWhere('details', 'like', "%{$q}%")
                        ->orWhereHas('reception', function ($r) use ($q) {
                            $r->where('ticket_no', 'like', "%{$q}%")
                                ->orWhere('serial_number', 'like', "%{$q}%")
                                ->orWhere('model', 'like', "%{$q}%")
                                ->orWhere('reported_fault', 'like', "%{$q}%");
                        });
                });
            })
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        // Hide private notes of others
        $reports->setCollection(
            $reports->getCollection()->filter(fn (ReceptionWorkReport $r) => $r->isVisibleTo($user))
        );

        return view('work-reports.index', [
            'reports' => $reports,
            'q' => $q,
            'visibility' => $visibility,
        ]);
    }

    public function print(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $user = Auth::user();
        $reports = ReceptionWorkReport::query()
            ->with(['reception.customer', 'user', 'technician'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('summary', 'like', "%{$q}%")
                        ->orWhere('details', 'like', "%{$q}%");
                });
            })
            ->latest('id')
            ->limit(500)
            ->get()
            ->filter(fn (ReceptionWorkReport $r) => $r->isVisibleTo($user));

        return view('work-reports.print', compact('reports', 'q'));
    }

    public function similar(Request $request, Reception $reception)
    {
        $user = Auth::user();
        $fault = trim((string) ($reception->reported_fault ?: $reception->final_fault ?: ''));
        $model = trim((string) ($reception->model ?: ''));
        $faultTypeId = $reception->fault_type_id;

        $similar = ReceptionWorkReport::query()
            ->with(['reception', 'user', 'technician'])
            ->where('reception_id', '!=', $reception->id)
            ->where(function ($q) use ($fault, $model, $faultTypeId) {
                if ($faultTypeId) {
                    $q->orWhereHas('reception', fn ($r) => $r->where('fault_type_id', $faultTypeId));
                }
                if ($model !== '') {
                    $q->orWhereHas('reception', fn ($r) => $r->where('model', 'like', '%'.$model.'%'));
                }
                if ($fault !== '') {
                    $words = preg_split('/\s+/u', $fault, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                    foreach (array_slice($words, 0, 4) as $w) {
                        if (mb_strlen($w) < 3) {
                            continue;
                        }
                        $q->orWhere('summary', 'like', '%'.$w.'%')
                            ->orWhere('details', 'like', '%'.$w.'%')
                            ->orWhereHas('reception', fn ($r) => $r->where('reported_fault', 'like', '%'.$w.'%'));
                    }
                }
            })
            ->whereIn('visibility', ['internal', 'public'])
            ->latest('id')
            ->limit(20)
            ->get()
            ->filter(fn (ReceptionWorkReport $r) => $r->isVisibleTo($user));

        return view('work-reports.similar', [
            'reception' => $reception,
            'similar' => $similar,
        ]);
    }
}
