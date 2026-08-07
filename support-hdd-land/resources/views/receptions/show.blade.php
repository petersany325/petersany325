@extends('layouts.app')

@section('title', $reception->ticket_no.' | سرزمین هارد')
@section('page_title', 'جزئیات قبض '.$reception->ticket_no)
@section('window_title', 'قبض '.$reception->ticket_no.' — تغییر وضعیت و پیامک')

@section('content')
<div class="split-2">
    <div class="stack">
        <div class="panel detail-box">
            <div class="detail-box-head">
                <div>
                    <h2>{{ $reception->ticket_no }}</h2>
                    <p class="lead" style="margin:2px 0 0;">{{ $reception->product_name }} — {{ $reception->brand }} {{ $reception->model }}</p>
                </div>
                <div class="report-head-actions">
                    <span class="badge badge-{{ $reception->status }}">{{ $reception->statusLabel() }}</span>
                    <a class="btn btn-secondary" href="{{ route('receptions.print', $reception) }}" target="_blank">چاپ قبض</a>
                    @if($reception->isDelivered())
                        <button class="btn btn-primary" type="button" onclick="document.getElementById('cancel-delivery-box').scrollIntoView({behavior:'smooth'})">لغو تحویل</button>
                    @endif
                </div>
            </div>
            <div class="detail-kv">
                <div><span class="muted">مشتری</span><div>{{ $reception->customer->name }}</div></div>
                <div><span class="muted">تلفن</span><div dir="ltr">{{ $reception->customer->phone }}</div></div>
                <div><span class="muted">شماره قبض</span><div>{{ $reception->receipt_no ?: '—' }}</div></div>
                <div><span class="muted">نوع پذیرش</span><div>{{ $reception->admission_type ?: '—' }}</div></div>
                <div><span class="muted">سریال</span><div dir="ltr">{{ $reception->serial_number ?: '—' }}</div></div>
                <div><span class="muted">مدل</span><div>{{ $reception->brand }} {{ $reception->model }}</div></div>
                <div><span class="muted">خدمات / تعمیر</span><div>{{ $reception->service_type ?: '—' }} / {{ $reception->repair_type ?: '—' }}</div></div>
                <div><span class="muted">ظرفیت</span><div>{{ $reception->hdd_capacity ?: '—' }}</div></div>
                <div><span class="muted">گارانتی</span><div>{{ $reception->warranty_type ?: '—' }}</div></div>
                <div><span class="muted">تعمیرکار</span><div>{{ $reception->technician?->name ?: '—' }}</div></div>
                <div><span class="muted">محل دستگاه</span><div>{{ $reception->custodyLabel() }}</div></div>
                <div><span class="muted">پذیرش</span><div>{{ jalali_like($reception->received_at) }}</div></div>
                <div style="grid-column:1/-1"><span class="muted">عیب اظهار مشتری</span><div>{{ $reception->reported_fault ?: '—' }}</div></div>
                <div style="grid-column:1/-1"><span class="muted">لوازم همراه</span><div>{{ $reception->accessories ?: '—' }}</div></div>
                <div style="grid-column:1/-1"><span class="muted">وضعیت ظاهری</span><div>{{ $reception->appearance_notes ?: '—' }}</div></div>
                @if($reception->photo_path)
                    <div style="grid-column:1/-1"><span class="muted">عکس</span><div><img src="{{ asset('storage/'.$reception->photo_path) }}" alt="photo" style="max-width:220px;border:1px solid #c5ccd6;border-radius:2px;"></div></div>
                @endif
            </div>
        </div>

        @if(auth()->user()->canAccess('receptions') || auth()->user()->canAccess('handoffs'))
        <div class="panel" style="margin-bottom:12px;">
            <h3 style="margin-top:0;">ارجاع به همکار (Chain of Custody)</h3>
            <p class="muted">منشی دستگاه را به تعمیرکار ارجاع می‌دهد؛ تعمیرکار باید دریافت با همین سریال را تأیید کند. در پایان، تعمیرکار دستگاه را به پذیرش برمی‌گرداند.</p>

            @if(!empty($pendingHandoff))
                <div class="p-alert" style="background:#fff7ed;padding:10px;border-radius:10px;margin-bottom:10px;">
                    ارجاع باز: {{ $pendingHandoff->directionLabel() }}
                    @if($pendingHandoff->toTechnician) → {{ $pendingHandoff->toTechnician->name }} @endif
                    — {{ $pendingHandoff->statusLabel() }}
                    <div class="muted">سریال: <span dir="ltr">{{ $pendingHandoff->serial_snapshot ?: '—' }}</span></div>
                </div>
            @endif

            @if(auth()->user()->canAccess('receptions') && ($reception->custody ?? 'front_desk') !== 'with_technician' && empty($pendingHandoff))
                <form method="POST" action="{{ route('receptions.handoffs.store', $reception) }}" class="form-grid" style="grid-template-columns:1fr 1fr auto;align-items:end;">
                    @csrf
                    <input type="hidden" name="direction" value="to_bench">
                    <div>
                        <label>ارجاع به تعمیرکار</label>
                        <select name="technician_id" required>
                            <option value="">— انتخاب تعمیرکار —</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>یادداشت ارجاع</label>
                        <input type="text" name="note" placeholder="مثلاً: شروع بازیابی اطلاعات">
                    </div>
                    <button class="btn btn-primary" type="submit">ارجاع و درخواست تأیید دریافت</button>
                </form>
            @endif

            @if(auth()->user()->technician && (int) $reception->custody_technician_id === (int) auth()->user()->technician->id && empty($pendingHandoff))
                <form method="POST" action="{{ route('receptions.handoffs.store', $reception) }}" class="actions" style="margin-top:8px;">
                    @csrf
                    <input type="hidden" name="direction" value="to_front_desk">
                    <input type="text" name="note" placeholder="یادداشت بازگشت به منشی" style="min-width:220px;">
                    <button class="btn btn-primary" type="submit">ارجاع به پذیرش برای تحویل مشتری</button>
                </form>
            @elseif(auth()->user()->canAccess('receptions') && ($reception->custody ?? '') === 'with_technician' && empty($pendingHandoff))
                <form method="POST" action="{{ route('receptions.handoffs.store', $reception) }}" class="actions" style="margin-top:8px;">
                    @csrf
                    <input type="hidden" name="direction" value="to_front_desk">
                    <input type="text" name="note" placeholder="دستگاه از تعمیرکار برگشت؟" style="min-width:220px;">
                    <button class="btn btn-primary" type="submit">ثبت بازگشت به پذیرش (تحویل از تعمیرکار)</button>
                </form>
            @endif

            @if($reception->handoffs->count())
                <div class="table-wrap" style="margin-top:12px;">
                    <table class="compact-table">
                        <thead>
                        <tr><th>زمان</th><th>نوع</th><th>از</th><th>به</th><th>سریال</th><th>وضعیت</th></tr>
                        </thead>
                        <tbody>
                        @foreach($reception->handoffs as $h)
                            <tr>
                                <td>{{ $h->created_at?->format('Y/m/d H:i') }}</td>
                                <td>{{ $h->directionLabel() }}</td>
                                <td>{{ $h->fromUser?->name }}</td>
                                <td>{{ $h->toTechnician?->name ?: ($h->toUser?->name ?: 'پذیرش') }}</td>
                                <td dir="ltr">{{ $h->serial_snapshot ?: '—' }}</td>
                                <td>{{ $h->statusLabel() }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        @endif

        @if($reception->isDelivered())
        <div class="panel" id="cancel-delivery-box" style="border-color:#f5d59a;background:linear-gradient(180deg,#fffaf0,#fff);">
            <h3 style="margin-top:0;">لغو تحویل / بازگشت به چرخه تعمیر</h3>
            <p class="lead" style="margin:0 0 8px;">مشتری قطعه/دستگاه را برگردانده؟ بدون قبض جدید — همان سریال روی همین قبض به تعمیر برمی‌گردد. قطعات و هزینه‌های ثبت‌شده حفظ می‌شوند.</p>
            <form method="POST" action="{{ route('receptions.cancel-delivery', $reception) }}" class="accept-row accept-row-3" style="align-items:end;" data-confirm="تحویل لغو شود و دستگاه به چرخه تعمیر برگردد؟">
                @csrf
                <div>
                    <label>بازگشت به وضعیت</label>
                    <select name="restore_to">
                        <option value="repairing">در حال تعمیر</option>
                        <option value="waiting_part">منتظر قطعه</option>
                        <option value="ready">آماده تحویل</option>
                        <option value="received">پذیرش‌شده</option>
                    </select>
                </div>
                <div class="full">
                    <label>دلیل لغو تحویل</label>
                    <input type="text" name="reason" placeholder="مثلاً قطعه خراب بود / مشتری برگشت داد" required>
                </div>
                <div class="actions" style="margin:0;">
                    <button class="btn btn-primary" type="submit">ثبت لغو تحویل</button>
                </div>
            </form>
            @if($reception->delivery_cancel_count)
                <p class="muted" style="margin:8px 0 0;font-size:11px;">تعداد لغو تحویل قبلی: {{ $reception->delivery_cancel_count }}</p>
            @endif
        </div>
        @endif

        <div class="panel">
            <h3 style="margin-top:0;">تاریخچه وضعیت دستگاه</h3>
            @forelse(($statusLogs ?? collect()) as $log)
                <div class="rx-timeline-item">
                    <div class="rx-timeline-dot"></div>
                    <div>
                        <strong>{{ $log->displayTitle() }}</strong>
                        <div class="muted" style="font-size:11px;">
                            {{ $log->created_at?->timezone('Asia/Tehran')->format('Y/m/d H:i') }}
                            @if($log->actor) · {{ $log->actor->name }} @endif
                            @if($log->fromStatusLabel()) · از {{ $log->fromStatusLabel() }} @endif
                            → {{ $log->toStatusLabel() }}
                        </div>
                        @if($log->note)
                            <div style="font-size:12px;margin-top:2px;">{{ $log->note }}</div>
                        @endif
                    </div>
                </div>
            @empty
                <p class="lead" style="margin:0;">هنوز تاریخچه‌ای ثبت نشده. از این به بعد تغییرات اینجا دیده می‌شود.</p>
            @endforelse
        </div>

        <div class="panel status-sms-panel" id="status-sms-panel"
             data-previews='@json($smsPreviews)'
             data-current="{{ $reception->status }}"
             data-master="{{ $smsMasterEnabled ? '1' : '0' }}">
            <div class="status-sms-head">
                <div>
                    <h3>تغییر وضعیت + پیامک مشتری</h3>
                    <p class="lead" style="margin:0;">وضعیت را انتخاب کنید، متن پیامک را ببینید، اجازه ارسال را روشن/خاموش کنید، سپس تایید و ثبت بزنید.</p>
                </div>
                @if(!$smsMasterEnabled)
                    <span class="pill pill-off">سوییچ اصلی پیامک خاموش است</span>
                @endif
            </div>

            <form method="POST" action="{{ route('receptions.status', $reception) }}" id="status-sms-form">
                @csrf
                <input type="hidden" name="status" id="selected-status" value="{{ $reception->status }}">

                <div class="status-chips" role="listbox" aria-label="وضعیت قبض">
                    @foreach($smsRules as $rule)
                        <button type="button"
                                class="status-chip color-{{ $rule->color }} {{ $reception->status === $rule->status_key ? 'is-active' : '' }}"
                                data-status-chip="{{ $rule->status_key }}"
                                data-auto-send="{{ $rule->auto_send ? '1' : '0' }}">
                            <span class="status-chip-title">{{ $rule->title }}</span>
                            <span class="status-chip-meta">{{ $rule->auto_send ? 'پیامک مجاز' : 'بدون پیامک پیش‌فرض' }}</span>
                        </button>
                    @endforeach
                    @foreach($statuses as $key => $label)
                        @if(!$smsRules->contains(fn ($r) => $r->status_key === $key))
                            <button type="button"
                                    class="status-chip color-slate {{ $reception->status === $key ? 'is-active' : '' }}"
                                    data-status-chip="{{ $key }}"
                                    data-auto-send="0">
                                <span class="status-chip-title">{{ $label }}</span>
                                <span class="status-chip-meta">بدون قالب پیامک</span>
                            </button>
                        @endif
                    @endforeach
                </div>

                <div class="sms-preview-box">
                    <div class="sms-preview-top">
                        <strong>پیش‌نمایش پیامک</strong>
                        <span class="muted" id="sms-preview-title">—</span>
                    </div>
                    <pre id="sms-preview-body" class="sms-preview-body">وضعیتی را انتخاب کنید…</pre>
                </div>

                <div class="accept-row accept-row-3" style="margin-top:8px;">
                    <div>
                        @include('partials.toggle', [
                            'name' => 'send_sms',
                            'label' => 'ارسال پیامک به مشتری پس از تایید',
                            'checked' => false,
                            'on' => 'برود',
                            'off' => 'نرود',
                            'id' => 'send_sms_toggle',
                        ])
                    </div>
                    <div>
                        @include('partials.toggle', [
                            'name' => 'send_price_sms',
                            'label' => 'پیامک مبلغ + لینک تأیید هزینه',
                            'checked' => false,
                            'on' => 'برود',
                            'off' => 'نرود',
                        ])
                    </div>
                    <div>
                        @include('partials.toggle', [
                            'name' => 'force_without_cost',
                            'label' => 'تحویل حتی بدون هزینه مشخص',
                            'checked' => false,
                            'on' => 'اجازه',
                            'off' => 'خیر',
                        ])
                    </div>
                    <div>
                        <label>تعمیرکار</label>
                        <select name="technician_id">
                            <option value="">—</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->id }}" @selected($reception->technician_id == $tech->id)>{{ $tech->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>نوع ایراد نهایی</label>
                        <select name="fault_type_id">
                            <option value="">—</option>
                            @foreach($faultTypes as $fault)
                                <option value="{{ $fault->id }}" @selected($reception->fault_type_id == $fault->id)>{{ $fault->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>اجرت تعمیر (تومان)</label>
                        <input type="number" name="labor_cost" min="0" value="{{ $reception->labor_cost }}">
                        @if(!$reception->hasCostSet())
                            <div class="muted" style="color:#8a5a12;font-size:11px;">هزینه هنوز مشخص نشده — قبل از تحویل ثبت کنید.</div>
                        @else
                            <div class="muted" style="font-size:11px;">مبلغ کل: {{ number_format($reception->total_amount) }} تومان</div>
                        @endif
                    </div>
                    <div>
                        <label>تخفیف</label>
                        <input type="number" name="discount" min="0" value="{{ $reception->discount }}">
                    </div>
                    <div>
                        <label>دلیل تخفیف</label>
                        <input type="text" name="discount_reason" value="{{ $reception->discount_reason }}" placeholder="اختیاری">
                    </div>
                    <div class="full">
                        <label>ایراد نهایی / شرح کار</label>
                        <textarea name="final_fault">{{ $reception->final_fault }}</textarea>
                    </div>
                    <div class="full">
                        <label>یادداشت تعمیرکار</label>
                        <textarea name="technician_notes">{{ $reception->technician_notes }}</textarea>
                    </div>
                </div>

                <div class="actions">
                    <button class="btn btn-primary" type="submit">تایید و ثبت وضعیت</button>
                    @if(auth()->user()->canAccess('sms.statuses'))
                        <a class="btn btn-ghost" href="{{ route('sms-statuses.index') }}">تعریف تغییر وضعیت / پیامک</a>
                    @endif
                </div>
            </form>

            @if($smsLogs->count())
                <div class="sms-ticket-logs">
                    <h4>پیامک‌های این قبض</h4>
                    <ul>
                        @foreach($smsLogs as $log)
                            <li>
                                <span class="{{ $log->ok ? 'pill pill-ok' : 'pill pill-off' }}">{{ $log->ok ? 'موفق' : 'ناموفق' }}</span>
                                <span class="muted">{{ $log->created_at?->format('Y/m/d H:i') }}</span>
                                — {{ $log->status_key === 'cost_approval' ? 'لینک تأیید هزینه' : ($log->rule?->title ?: $log->status_key) }}
                                <div class="muted" style="font-size:11px;white-space:pre-wrap;margin-top:2px;">{{ \Illuminate\Support\Str::limit($log->message, 180) }}</div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="cost-approval-box" style="margin-top:12px;border-top:1px solid #d7dde6;padding-top:10px;">
                <h4 style="margin:0 0 8px;font-size:12.5px;">تأیید هزینه توسط مشتری</h4>
                @php
                    $requiresApproval = \App\Support\CostApprovalSettings::receptionRequiresApproval($reception);
                    $latestApproval = ($costApprovals ?? collect())->first();
                    $apStatus = $reception->cost_approval_status;
                    $apLabels = \App\Models\CostApproval::statusLabels();
                @endphp
                @if($requiresApproval)
                    <div class="alert" style="background:#fff6e5;border-color:#efd7a4;color:#8a5a12;margin-bottom:8px;">
                        این قبض خدمت مشمول تأیید است
                        ({{ $reception->service_type ?: $reception->repair_type ?: '—' }}).
                        قبل از ادامه کار پرهزینه لینک تأیید بفرستید.
                        <a href="{{ route('cost-approvals.index') }}">کارتابل تأیید هزینه</a>
                    </div>
                @else
                    <div class="muted" style="font-size:11.5px;margin-bottom:8px;">
                        این خدمت در فهرست مشمول تأیید نیست.
                        برای جراحی/بازیابی از منوی
                        <a href="{{ route('cost-approvals.settings') }}">خدمات مشمول</a>
                        فعال کنید، یا در صورت نیاز با اجبار ارسال کنید.
                    </div>
                @endif
                <div class="muted" style="font-size:11.5px;margin-bottom:8px;line-height:1.7;">
                    وضعیت فعلی:
                    <strong>{{ $apLabels[$apStatus] ?? ($apStatus ?: 'ارسال نشده') }}</strong>
                    @if($reception->customer_cost_approved_at)
                        — تأیید {{ number_format((int) $reception->customer_cost_approved_amount) }} تومان
                        در {{ $reception->customer_cost_approved_at->format('Y/m/d H:i') }}
                    @endif
                </div>
                <form method="POST" action="{{ route('receptions.cost-approval', $reception) }}" class="accept-row accept-row-2" style="margin-bottom:8px;">
                    @csrf
                    <div class="full">
                        <label>شرح کار برای مشتری (اختیاری)</label>
                        <textarea name="description" rows="2" placeholder="مثلاً جراحی هارد / بازیابی اطلاعات / تعویض قطعه…">{{ old('description', $reception->final_fault) }}</textarea>
                    </div>
                    <div>
                        @include('partials.toggle', [
                            'name' => 'send_sms',
                            'label' => 'ارسال SMS لینک یک‌بارمصرف',
                            'checked' => true,
                            'on' => 'برود',
                            'off' => 'نرود',
                        ])
                    </div>
                    @unless($requiresApproval)
                    <div>
                        @include('partials.toggle', [
                            'name' => 'force',
                            'label' => 'ارسال اجباری (خارج از فهرست خدمات)',
                            'checked' => false,
                            'on' => 'اجبار',
                            'off' => 'خیر',
                        ])
                    </div>
                    @else
                        <input type="hidden" name="force" value="1">
                    @endunless
                    <div class="actions" style="align-items:end;">
                        <button class="btn btn-primary" type="submit">ارسال لینک تأیید هزینه</button>
                        <a class="btn btn-ghost" href="{{ route('cost-approvals.index') }}">منوی تأیید هزینه</a>
                    </div>
                </form>
                @if(($costApprovals ?? collect())->count())
                    <div class="table-wrap">
                        <table class="data compact-table">
                            <thead>
                                <tr>
                                    <th>نسخه</th>
                                    <th>مبلغ</th>
                                    <th>وضعیت</th>
                                    <th>ارسال</th>
                                    <th>مشاهده</th>
                                    <th>تصمیم</th>
                                    <th>کد</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($costApprovals as $ap)
                                <tr>
                                    <td>V{{ $ap->version }}</td>
                                    <td>{{ number_format((int) $ap->amount) }}</td>
                                    <td>{{ $ap->statusLabel() }}</td>
                                    <td>{{ $ap->sent_at?->format('Y/m/d H:i') ?: '—' }}</td>
                                    <td>{{ $ap->viewed_at?->format('Y/m/d H:i') ?: '—' }}</td>
                                    <td>{{ $ap->decided_at?->format('Y/m/d H:i') ?: '—' }}</td>
                                    <td dir="ltr">{{ $ap->approval_code ?: '—' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="panel">
            <h3>مراحل هزینه (چندمرحله‌ای)</h3>
            <p class="lead" style="margin:0 0 8px;">مثلاً خرید برد، بازیابی و تست — هر کدام مبلغ جدا. جمع در فاکتور خروج لحاظ می‌شود.</p>
            <div class="table-wrap">
                <table class="data compact-table">
                    <thead><tr><th>مرحله</th><th>مبلغ</th><th>وضعیت</th><th>یادداشت</th><th></th></tr></thead>
                    <tbody>
                    @forelse(($costStages ?? collect()) as $stage)
                        <tr>
                            <td><span class="chip">{{ $stage->mark() }}</span> {{ $stage->stage_label }}</td>
                            <td>{{ toman($stage->amount) }}</td>
                            <td>{{ $stage->statusLabel() }}</td>
                            <td>{{ $stage->note ?: '—' }}</td>
                            <td>
                                @unless($reception->isDelivered())
                                <form method="POST" action="{{ route('receptions.cost-stages.destroy', [$reception, $stage]) }}" data-confirm="این مرحله هزینه حذف شود؟">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger" type="submit">حذف</button>
                                </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">مرحله هزینه‌ای ثبت نشده.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @unless($reception->isDelivered())
            <form method="POST" action="{{ route('receptions.cost-stages', $reception) }}" style="margin-top:10px;" class="accept-row accept-row-4" >
                @csrf
                <div>
                    <label>نوع مرحله</label>
                    <select name="stage_key" id="stage-key-select">
                        @foreach($stageDefs as $key => $def)
                            <option value="{{ $key }}">{{ $def['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="custom-label-wrap" style="display:none;">
                    <label>عنوان سفارشی</label>
                    <input type="text" name="custom_label" placeholder="مثلاً تعمیر PCB">
                </div>
                <div>
                    <label>مبلغ (تومان)</label>
                    <input type="number" name="amount" min="0" value="0" required>
                </div>
                <div>
                    <label>وضعیت تأیید</label>
                    <select name="status">
                        <option value="waived">ثبت مستقیم (بدون تأیید)</option>
                        <option value="pending_approval">منتظر تأیید مشتری</option>
                        <option value="approved">تأییدشده</option>
                        <option value="draft">پیش‌نویس</option>
                    </select>
                </div>
                <div class="full">
                    <label>یادداشت</label>
                    <input type="text" name="note" placeholder="توضیح مرحله">
                </div>
                <div class="actions" style="margin:0;">
                    <button class="btn btn-secondary" type="submit">افزودن مرحله هزینه</button>
                </div>
            </form>
            @else
                <p class="muted" style="margin:8px 0 0;">برای ویرایش هزینه‌ها ابتدا لغو تحویل بزنید.</p>
            @endunless
        </div>

        <div class="panel">
            <h3>قطعات خرج‌شده</h3>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr><th>قطعه</th><th>تعداد</th><th>فی</th><th>جمع</th><th>تاریخ</th></tr>
                    </thead>
                    <tbody>
                    @forelse($reception->parts as $part)
                        <tr>
                            <td>{{ $part->part_name }}</td>
                            <td>{{ $part->quantity }}</td>
                            <td>{{ toman($part->unit_price) }}</td>
                            <td>{{ toman($part->total_price) }}</td>
                            <td>{{ optional($part->used_at)->format('Y/m/d') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">قطعه‌ای ثبت نشده.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($reception->canEditParts())
            <form method="POST" action="{{ route('receptions.parts', $reception) }}" style="margin-top:1rem;">
                @csrf
                <div class="form-grid">
                    <div>
                        <label>از انبار</label>
                        <select name="part_id">
                            <option value="">— قطعه آزاد —</option>
                            @foreach($parts as $part)
                                <option value="{{ $part->id }}">{{ $part->name }} (موجودی: {{ $part->stock }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>نام قطعه آزاد</label>
                        <input type="text" name="part_name">
                    </div>
                    <div>
                        <label>تعداد</label>
                        <input type="number" name="quantity" min="1" value="1" required>
                    </div>
                    <div>
                        <label>فی (تومان)</label>
                        <input type="number" name="unit_price" min="0" value="0">
                    </div>
                    <div>
                        <label>تاریخ مصرف</label>
                        <input type="date" name="used_at" value="{{ now()->toDateString() }}">
                    </div>
                </div>
                <div style="margin-top:8px;">
                    @include('partials.toggle', [
                        'name' => 'send_price_sms',
                        'label' => 'پیامک مبلغ + لینک تأیید هزینه',
                        'checked' => true,
                        'on' => 'برود',
                        'off' => 'نرود',
                    ])
                </div>
                <div class="actions">
                    <button class="btn btn-secondary" type="submit">افزودن قطعه</button>
                </div>
            </form>
            @else
                <div class="alert" style="margin-top:10px;background:#fff6e5;border-color:#efd7a4;color:#8a5a12;">
                    این قبض تحویل شده و قطعات قفل هستند. برای بازگشت به چرخه تعمیر از «لغو تحویل» استفاده کنید.
                </div>
            @endif
        </div>
    </div>

    <div class="stack">
        <div class="panel">
            <h3>خلاصه مالی / فاکتور خروج</h3>
            <div class="form-grid" style="grid-template-columns:1fr;">
                <div><span class="muted">بیعانه</span><div>{{ toman($reception->deposit) }}</div></div>
                <div><span class="muted">اجرت</span><div>{{ toman($reception->labor_cost) }}</div></div>
                <div><span class="muted">قطعات انبار</span><div>{{ toman($reception->parts_cost) }}</div></div>
                <div><span class="muted">مراحل هزینه</span><div>{{ toman($reception->stages_cost) }}</div></div>
                <div><span class="muted">تخفیف</span><div>{{ toman($reception->discount) }}@if($reception->discount_reason)<small class="muted"> — {{ $reception->discount_reason }}</small>@endif</div></div>
                <div><span class="muted">جمع کل</span><div style="font-size:1.2rem;font-weight:700;">{{ toman($reception->total_amount) }}</div></div>
                <div><span class="muted">پرداخت‌شده</span><div>{{ toman($reception->paid_amount) }}</div></div>
                <div><span class="muted">مانده</span><div style="font-weight:700;">{{ toman($reception->remainingAmount()) }}</div></div>
            </div>
        </div>

        <div class="panel">
            <h3>ثبت پرداخت / تخفیف / تحویل</h3>
            @if(\App\Support\PaymentGateways::showOnReception())
                @include('partials.payment-links', ['payTitle' => 'لینک بانک‌ها'])
            @endif
            @if(\App\Support\PaymentGateways::zarinpal()['configured'] && $reception->remainingAmount() >= 1000)
                <form method="POST" action="{{ route('receptions.zarinpal', $reception) }}" style="margin-bottom:10px;">
                    @csrf
                    <button class="btn btn-primary" type="submit" style="width:100%;">
                        درگاه اینترنتی (زرین‌پال) — {{ number_format($reception->remainingAmount()) }} تومان
                    </button>
                </form>
            @endif
            <form method="POST" action="{{ route('receptions.payments', $reception) }}">
                @csrf
                <div class="form-grid" style="grid-template-columns:1fr;">
                    <div>
                        <label>نوع پرداخت</label>
                        <select name="type">
                            @foreach($paymentTypes as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>روش پرداخت</label>
                        <select name="method">
                            @foreach($paymentMethods as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>مبلغ دریافتی</label>
                        <input type="number" name="amount" min="1" value="{{ $reception->remainingAmount() ?: 0 }}" required>
                    </div>
                    <div>
                        <label>تخفیف (تومان)</label>
                        <input type="number" name="discount" min="0" value="{{ (int) $reception->discount }}">
                    </div>
                    <div>
                        <label>دلیل تخفیف</label>
                        <input type="text" name="discount_reason" value="{{ $reception->discount_reason }}" placeholder="اختیاری">
                    </div>
                    <div>
                        <label>توضیح</label>
                        <input type="text" name="note" placeholder="شماره پیگیری کارت‌به‌کارت و …">
                    </div>
                </div>
                <div class="actions">
                    <button class="btn btn-primary" type="submit">ثبت پرداخت</button>
                </div>
            </form>
            <div class="table-wrap" style="margin-top:1rem;">
                <table style="min-width:0;">
                    <thead><tr><th>نوع</th><th>مبلغ</th><th>زمان</th></tr></thead>
                    <tbody>
                    @forelse($reception->payments as $payment)
                        <tr>
                            <td>{{ $payment->typeLabel() }} / {{ $payment->methodLabel() }}</td>
                            <td>{{ toman($payment->amount) }}</td>
                            <td>{{ jalali_like($payment->paid_at) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3">پرداختی نیست.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var stageSel = document.getElementById('stage-key-select');
    var customWrap = document.getElementById('custom-label-wrap');
    if (stageSel && customWrap) {
        var sync = function () {
            customWrap.style.display = stageSel.value === 'custom' ? '' : 'none';
        };
        stageSel.addEventListener('change', sync);
        sync();
    }
    var panel = document.getElementById('status-sms-panel');
    if (!panel) return;
    var previews = {};
    try { previews = JSON.parse(panel.getAttribute('data-previews') || '{}'); } catch (e) {}
    var masterOn = panel.getAttribute('data-master') === '1';
    var selected = document.getElementById('selected-status');
    var titleEl = document.getElementById('sms-preview-title');
    var bodyEl = document.getElementById('sms-preview-body');
    var toggleField = panel.querySelector('[data-toggle-field]');

    function setSendSms(on) {
        if (!toggleField || !window) return;
        var input = toggleField.querySelector('[data-toggle-input]');
        var btn = toggleField.querySelector('[data-toggle-btn]');
        if (!input || !btn) return;
        input.checked = !!on;
        btn.classList.toggle('is-on', !!on);
        btn.classList.toggle('is-off', !on);
        btn.setAttribute('aria-pressed', on ? 'true' : 'false');
        var state = btn.querySelector('.toggle-state');
        if (state) state.textContent = on ? (btn.getAttribute('data-on-text') || 'برود') : (btn.getAttribute('data-off-text') || 'نرود');
    }

    function applyStatus(key, autoDefault) {
        selected.value = key;
        panel.querySelectorAll('[data-status-chip]').forEach(function (chip) {
            chip.classList.toggle('is-active', chip.getAttribute('data-status-chip') === key);
        });
        var preview = previews[key];
        if (preview) {
            titleEl.textContent = preview.title || key;
            bodyEl.textContent = preview.message || '—';
            setSendSms(masterOn && !!preview.auto_send);
        } else {
            titleEl.textContent = key;
            bodyEl.textContent = 'برای این وضعیت قالب پیامک تعریف نشده است. از منوی «تعریف تغییر وضعیت / پیامک» اضافه کنید.';
            setSendSms(false);
        }
        if (typeof autoDefault === 'boolean') {
            setSendSms(masterOn && autoDefault);
        }
    }

    panel.querySelectorAll('[data-status-chip]').forEach(function (chip) {
        chip.addEventListener('click', function () {
            applyStatus(chip.getAttribute('data-status-chip'), chip.getAttribute('data-auto-send') === '1');
        });
    });

    applyStatus(panel.getAttribute('data-current') || selected.value);
})();
</script>
@endpush
