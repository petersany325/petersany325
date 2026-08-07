<?php

namespace App\Http\Controllers;

use App\Models\Intern;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\NiazpardazSmsService;
use App\Support\StaffSmsTemplates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class InternController extends Controller
{
    public function index()
    {
        $interns = Intern::query()->orderByDesc('start_date')->orderByDesc('id')->paginate(20);
        $all = Intern::query()->get(['id', 'is_active', 'start_date', 'end_date']);
        $today = now('Asia/Tehran')->startOfDay();

        return view('interns.index', [
            'interns' => $interns,
            'stats' => [
                'total' => $all->count(),
                'active' => $all->where('is_active', true)->filter(fn ($i) => $i->start_date <= $today && $i->end_date >= $today)->count(),
                'upcoming' => $all->where('is_active', true)->filter(fn ($i) => $i->start_date > $today)->count(),
                'finished' => $all->filter(fn ($i) => $i->end_date < $today || ! $i->is_active)->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('interns.create');
    }

    public function store(Request $request, NiazpardazSmsService $sms)
    {
        $data = $this->validated($request);

        $intern = Intern::create([
            'name' => $data['name'],
            'phone' => User::normalizePhone($data['phone']),
            'email' => $data['email'] ?: null,
            'national_code' => $data['national_code'] ?: null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'department' => $data['department'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => Auth::id(),
        ]);

        $flash = 'کارآموز ثبت شد.';
        $ok = true;
        if ($request->boolean('send_welcome_sms', true)) {
            $result = $this->sendWelcome($sms, $intern);
            $ok = $result['ok'];
            $flash .= $ok
                ? ' پیامک خوش‌آمدگویی ارسال شد.'
                : ' پیامک ارسال نشد: '.($result['message'] ?? '');
        }

        return redirect()->route('interns.index')->with($ok ? 'success' : 'error', $flash);
    }

    public function edit(Intern $intern)
    {
        return view('interns.edit', ['intern' => $intern]);
    }

    public function update(Request $request, Intern $intern)
    {
        $data = $this->validated($request, $intern);

        $intern->update([
            'name' => $data['name'],
            'phone' => User::normalizePhone($data['phone']),
            'email' => $data['email'] ?: null,
            'national_code' => $data['national_code'] ?: null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'department' => $data['department'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('interns.index')->with('success', 'اطلاعات کارآموز به‌روزرسانی شد.');
    }

    public function destroy(Intern $intern)
    {
        $intern->delete();

        return redirect()->route('interns.index')->with('success', 'کارآموز حذف شد.');
    }

    public function sendWelcomeSms(Intern $intern, NiazpardazSmsService $sms)
    {
        $result = $this->sendWelcome($sms, $intern);

        return back()->with(
            $result['ok'] ? 'success' : 'error',
            $result['ok']
                ? 'پیامک خوش‌آمدگویی برای '.$intern->name.' ارسال شد.'
                : 'ارسال پیامک ناموفق بود: '.($result['message'] ?? '')
        );
    }

    /** @return array{ok:bool,message:string} */
    private function sendWelcome(NiazpardazSmsService $sms, Intern $intern): array
    {
        $phone = User::normalizePhone($intern->phone);
        if (! $phone) {
            return ['ok' => false, 'message' => 'شماره موبایل معتبر نیست.'];
        }

        $message = StaffSmsTemplates::renderIntern($intern);
        $result = $sms->send($phone, $message);

        SmsLog::create([
            'reception_id' => null,
            'customer_id' => null,
            'sms_status_rule_id' => null,
            'sent_by' => Auth::id(),
            'phone' => $phone,
            'status_key' => 'intern_welcome',
            'audience' => 'intern',
            'message' => $message,
            'ok' => (bool) ($result['ok'] ?? false),
            'provider_message' => $result['message'] ?? null,
        ]);

        return [
            'ok' => (bool) ($result['ok'] ?? false),
            'message' => (string) ($result['message'] ?? ''),
        ];
    }

    private function validated(Request $request, ?Intern $intern = null): array
    {
        $phone = User::normalizePhone((string) $request->input('phone', ''));
        $request->merge(['phone' => $phone]);

        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'min:10', 'max:20'],
            'email' => ['nullable', 'email', 'max:120'],
            'national_code' => ['nullable', 'string', 'max:20'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'department' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'name.required' => 'نام کارآموز الزامی است.',
            'phone.required' => 'موبایل الزامی است.',
            'start_date.required' => 'تاریخ شروع کارآموزی الزامی است.',
            'end_date.required' => 'تاریخ پایان کارآموزی الزامی است.',
            'end_date.after_or_equal' => 'تاریخ پایان باید بعد از شروع باشد.',
        ]);
    }
}
