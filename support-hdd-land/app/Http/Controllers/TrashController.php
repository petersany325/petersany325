<?php

namespace App\Http\Controllers;

use App\Services\TrashService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TrashController extends Controller
{
    public function __construct(private TrashService $trash) {}

    public function index()
    {
        $data = $this->trash->inventory();

        return view('trash.index', $data);
    }

    public function restore(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:reception,journal,customer'],
            'id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $msg = match ($data['type']) {
                'reception' => 'قبض '.$this->trash->restoreReception((int) $data['id'])->ticket_no.' بازیابی شد.',
                'journal' => 'سند '.$this->trash->restoreJournal((int) $data['id'])->entry_no.' بازیابی شد.',
                'customer' => 'مشتری «'.$this->trash->restoreCustomer((int) $data['id'])->name.'» بازیابی شد.',
            };
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->with('error', 'بازیابی ناموفق بود: '.$e->getMessage());
        }

        return back()->with('success', $msg);
    }

    public function forceDestroy(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:reception,journal,customer'],
            'id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            match ($data['type']) {
                'reception' => $this->trash->forceDeleteReception((int) $data['id']),
                'journal' => $this->trash->forceDeleteJournal((int) $data['id']),
                'customer' => $this->trash->forceDeleteCustomer((int) $data['id']),
            };
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->with('error', 'حذف دائم ناموفق بود: '.$e->getMessage());
        }

        return back()->with('success', 'آیتم برای همیشه حذف شد.');
    }
}
