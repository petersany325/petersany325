<?php

namespace Plugins\Accounting\src\Http\Controllers\Account;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Plugins\Accounting\Plugin;
use Plugins\Accounting\src\Support\AccCommerce;
use Plugins\Accounting\src\Support\AccEngine;

class InvoiceController extends Controller
{
    public function __construct()
    {
        Plugin::ensureSchema();
    }

    public function index()
    {
        AccCommerce::syncPendingShopOrders(15);
        $uid = (int) Auth::id();
        $orderIds = [];
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'user_id')) {
            $orderIds = DB::table('orders')->where('user_id', $uid)->pluck('id')->all();
        }
        $docs = DB::table('acc_documents')
            ->whereIn('type', ['sale', 'proforma'])
            ->where(function ($q) use ($uid, $orderIds) {
                $q->where('party_user_id', $uid);
                if ($orderIds && Schema::hasColumn('acc_documents', 'order_id')) {
                    $q->orWhereIn('order_id', $orderIds);
                }
                $name = Auth::user()->name ?? '';
                if ($name !== '') {
                    $q->orWhere(function ($w) use ($name) {
                        $w->whereNull('party_user_id')->where('party_name', $name);
                    });
                }
            })
            ->orderByDesc('id')->limit(100)->get();

        return view('accounting::account.invoices', [
            'docs' => $docs,
            'types' => AccEngine::TYPES,
        ]);
    }

    public function show(int $id)
    {
        $uid = (int) Auth::id();
        $doc = DB::table('acc_documents')->where('id', $id)->whereIn('type', ['sale', 'proforma'])->first();
        abort_unless($doc, 404);
        $owns = (int) ($doc->party_user_id ?? 0) === $uid;
        if (! $owns && ! empty($doc->order_id) && Schema::hasTable('orders')) {
            $owns = DB::table('orders')->where('id', $doc->order_id)->where('user_id', $uid)->exists();
        }
        if (! $owns && empty($doc->party_user_id)) {
            $name = Auth::user()->name ?? '';
            $owns = $name !== '' && $doc->party_name === $name;
        }
        abort_unless($owns, 403);

        $order = null;
        if (! empty($doc->order_id) && Schema::hasTable('orders')) {
            $order = DB::table('orders')->where('id', $doc->order_id)->first();
        }

        return view('accounting::account.invoice-show', [
            'doc' => $doc,
            'order' => $order,
            'lines' => DB::table('acc_document_lines')->where('document_id', $id)->get(),
            'serials' => DB::table('acc_document_serials')->where('document_id', $id)->get(),
            'types' => AccEngine::TYPES,
        ]);
    }
}
