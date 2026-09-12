<?php

namespace Plugins\Accounting\src\Http\Controllers\Staff;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Plugins\Accounting\Plugin;
use Plugins\Accounting\src\Support\AccEngine;

class AccountingController extends Controller
{
    public function __construct()
    {
        Plugin::ensureSchema();
        Plugin::seedDefaults();
    }

    public function hub()
    {
        return view('accounting::staff.hub', [
            'stats' => AccEngine::dashboardStats(),
            'types' => AccEngine::TYPES,
            'recent' => DB::table('acc_documents')->orderByDesc('id')->limit(10)->get(),
        ]);
    }

    public function docs(Request $request)
    {
        $type = (string) $request->query('type', '');
        $q = DB::table('acc_documents')->orderByDesc('id');
        if ($type !== '' && isset(AccEngine::TYPES[$type])) {
            $q->where('type', $type);
        }

        return view('accounting::staff.docs', [
            'docs' => $q->limit(80)->get(),
            'type' => $type,
            'types' => AccEngine::TYPES,
        ]);
    }

    public function show(int $id)
    {
        $doc = DB::table('acc_documents')->where('id', $id)->first();
        abort_unless($doc, 404);

        return view('accounting::staff.doc-show', [
            'doc' => $doc,
            'lines' => DB::table('acc_document_lines')->where('document_id', $id)->get(),
            'serials' => DB::table('acc_document_serials')->where('document_id', $id)->get(),
            'types' => AccEngine::TYPES,
        ]);
    }

    public function stock()
    {
        return view('accounting::staff.stock', [
            'balances' => DB::table('acc_stock_balances as b')
                ->leftJoin('acc_warehouses as w', 'w.id', '=', 'b.warehouse_id')
                ->select('b.*', 'w.name as warehouse_name')
                ->orderBy('w.name')->limit(150)->get(),
        ]);
    }

    public function reports()
    {
        $from = now()->startOfMonth()->toDateString();
        $to = now()->toDateString();

        return view('accounting::staff.reports', [
            'sales' => (int) DB::table('acc_documents')->where('type', 'sale')->where('status', 'issued')->whereBetween('doc_date', [$from, $to])->sum('total'),
            'purchase' => (int) DB::table('acc_documents')->where('type', 'purchase')->where('status', 'issued')->whereBetween('doc_date', [$from, $to])->sum('total'),
            'expense' => (int) DB::table('acc_documents')->where('type', 'expense')->where('status', 'issued')->whereBetween('doc_date', [$from, $to])->sum('total'),
            'from' => $from,
            'to' => $to,
        ]);
    }
}
