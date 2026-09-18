<?php

namespace Plugins\Accounting\src\Http\Controllers\Account;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Plugins\Accounting\Plugin;
use Plugins\Accounting\src\Support\AccEngine;

class InvoiceController extends Controller
{
    public function __construct()
    {
        Plugin::ensureSchema();
    }

    public function index()
    {
        $uid = Auth::id();
        $docs = DB::table('acc_documents')
            ->whereIn('type', ['sale', 'proforma'])
            ->where(function ($q) use ($uid) {
                $q->where('party_user_id', $uid);
                $name = Auth::user()->name ?? '';
                if ($name !== '') {
                    $q->orWhere('party_name', $name);
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
        $uid = Auth::id();
        $doc = DB::table('acc_documents')->where('id', $id)->whereIn('type', ['sale', 'proforma'])->first();
        abort_unless($doc, 404);
        if ((int) ($doc->party_user_id ?? 0) !== (int) $uid) {
            $name = Auth::user()->name ?? '';
            abort_unless($name !== '' && $doc->party_name === $name, 403);
        }

        return view('accounting::account.invoice-show', [
            'doc' => $doc,
            'lines' => DB::table('acc_document_lines')->where('document_id', $id)->get(),
            'serials' => DB::table('acc_document_serials')->where('document_id', $id)->get(),
            'types' => AccEngine::TYPES,
        ]);
    }
}
