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
                    <a class="btn btn-secondary" href="{{ route('receptions.history', $reception) }}" target="_blank" rel="noopener">تاریخچه / گزارش</a>
                    <a class="btn btn-secondary" href="{{ route('receptions.print', $reception) }}" target="_blank">چاپ قبض</a>
                    @if($reception->isDelivered())
                        <form method="POST" action="{{ route('receptions.cancel-delivery', $reception) }}" style="display:inline;" data-confirm="تحویل لغو شود و دستگاه به چرخه تعمیر برگردد؟">
                            @csrf
                            <input type="hidden" name="restore_to" value="repairing">
                            <input type="hidden" name="reason" value="لغو تحویل از صفحه قبض">
                            <button class="btn btn-primary" type="submit">لغو تحویل</button>
                        </form>
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
        @php $c = $custodyChecklist ?? []; $isAdmin = auth()->user()->isAdmin(); @endphp
        <div class="panel" style="margin-bottom:12px;">
            @if($isAdmin)
                <h3 style="margin-top:0;">مدیریت مستقیم تعمیر (بدون ارجاع اجباری)</h3>
                <p class="muted">به‌عنوان مدیر اصلی می‌توانید تعمیر، گزارش کار، هزینه و وضعیت را مستقیم ثبت کنید. ارجاع برای بقیه کارمندان همچنان اختیاری/کارتابلی است.</p>
                @unless($reception->isDelivered())
                <form method="POST" action="{{ route('receptions.work-report', $reception) }}" style="margin-top:8px;">
                    @csrf
                    <div class="accept-row accept-row-2">
                        <div style="grid-column:1/-1">
                            <label>گزارش کار / شرح تعمیر</label>
                            <input type="text" name="summary" required maxlength="500" placeholder="مثلاً: تعمیر PCB و تست نهایی توسط مدیر">
                        </div>
                        <div style="grid-column:1/-1">
                            <label>جزئیات</label>
                            <textarea name="details" rows="2" placeholder="اختیاری"></textarea>
                        </div>
                        <div>
                            <label>وضعیت بعد از کار</label>
                            <select name="result_status">
                                <option value="repairing">در حال تعمیر</option>
                                <option value="waiting_part">منتظر قطعه</option>
                                <option value="ready" selected>آماده تحویل</option>
                                <option value="unrepairable">غیرقابل تعمیر</option>
                            </select>
                        </div>
                        <div>
                            @include('partials.toggle', ['name' => 'needs_part', 'label' => 'نیاز به قطعه دارد', 'checked' => false])
                        </div>
                    </div>
                    <div class="actions" style="margin-top:8px;">
                        <button class="btn btn-primary" type="submit">ثبت گزارش و ادامه</button>
                        <a class="btn btn-ghost" href="{{ route('receptions.history', $reception) }}" target="_blank" rel="noopener">مشاهده تاریخچه</a>
                    </div>
                </form>
                @endunless
                <details style="margin-top:12px;">
                    <summary class="muted" style="cursor:pointer;">ارجاع اختیاری به تعمیرکار / کارتابل</summary>
                    <div style="margin-top:8px;">
            @else
                <h3 style="margin-top:0;">ارجاع قبض — زنجیره تأیید</h3>
                <p class="muted">پذیرش → ارجاع به تعمیرکار → تأیید تعمیرکار → گزارش کار → ارجاع بازگشت → تأیید منشی/حسابدار → اعلام هزینه / تحویل</p>
                <div class="accept-row accept-row-4" style="margin-bottom:10px;gap:8px;">
                    <div><span class="pill {{ !empty($c['assigned_confirmed']) ? 'pill-ok' : 'pill-off' }}">۱) تأیید دریافت تعمیرکار</span></div>
                    <div><span class="pill {{ !empty($c['work_report']) ? 'pill-ok' : 'pill-off' }}">۲) گزارش کار</span></div>
                    <div><span class="pill {{ !empty($c['return_confirmed']) ? 'pill-ok' : 'pill-off' }}">۳) تأیید بازگشت پذیرش</span></div>
                    <div><span class="pill {{ !empty($c['ready_for_delivery']) ? 'pill-ok' : 'pill-off' }}">۴) آماده تحویل</span></div>
                </div>
                @if(!empty($c['delivery_block']))
                    <div class="alert alert-error" style="margin-bottom:10px;">{{ $c['delivery_block'] }}</div>
                @elseif(!empty($c['cost_block']))
                    <div class="alert alert-error" style="margin-bottom:10px;">{{ $c['cost_block'] }}</div>
                @endif
            @endif

            @if(!empty($pendingHandoff))
                <div class="p-alert" style="background:#fff7ed;padding:10px;border-radius:10px;margin-bottom:10px;">
                    ارجاع باز: {{ $pendingHandoff->directionLabel() }}
                    @if($pendingHandoff->toTechnician) → {{ $pendingHandoff->toTechnician->name }} @endif
                    — {{ $pendingHandoff->statusLabel() }}
                    <div class="muted">سریال: <span dir="ltr">{{ $pendingHandoff->serial_snapshot ?: '—' }}</span>
                        · <a href="{{ route('handoffs.index') }}">رفتن به کارتابل تأیید</a>
                    </div>
                </div>
            @endif

            @if(auth()->user()->canAccess('receptions') && ($reception->custody ?? 'front_desk') !== 'with_technician' && ($reception->custody ?? '') !== 'returning' && empty($pendingHandoff))
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

            @if(auth()->user()->technician && (int) $reception->custody_technician_id === (int) auth()->user()->technician->id && ($reception->custody ?? '') === 'with_technician')
                <div class="panel" style="margin-top:10px;padding:10px;border:1px dashed #c5ccd6;">
                    <h3 style="margin:0 0 6px;">گزارش کار تعمیرکار (اجباری)</h3>
                    <form method="POST" action="{{ route('receptions.work-report', $reception) }}">
                        @csrf
                        <div class="accept-row accept-row-2">
                            <div style="grid-column:1/-1">
                                <label>خلاصه کار انجام‌شده</label>
                                <input type="text" name="summary" required maxlength="500" placeholder="مثلاً: PCB تعویض شد، تست رید پاس شد">
                            </div>
                            <div style="grid-column:1/-1">
                                <label>جزئیات</label>
                                <textarea name="details" rows="3" placeholder="شرح فنی، قطعه مصرفی، نتیجه تست…"></textarea>
                            </div>
                            <div>
                                <label>وضعیت پیشنهادی بعد از کار</label>
                                <select name="result_status">
                                    <option value="">— بدون تغییر وضعیت —</option>
                                    <option value="repairing">در حال تعمیر</option>
                                    <option value="waiting_part">منتظر قطعه</option>
                                    <option value="ready">آماده تحویل</option>
                                    <option value="unrepairable">غیرقابل تعمیر</option>
                                </select>
                            </div>
                            <div>
                                @include('partials.toggle', ['name' => 'needs_part', 'label' => 'نیاز به قطعه دارد', 'checked' => false])
                            </div>
                        </div>
                        <div class="actions" style="margin-top:8px;">
                            <button class="btn btn-primary" type="submit">ثبت گزارش کار</button>
                        </div>
                    </form>
                </div>

                @if(empty($pendingHandoff))
                    <form method="POST" action="{{ route('receptions.handoffs.store', $reception) }}" class="actions" style="margin-top:8px;">
                        @csrf
                        <input type="hidden" name="direction" value="to_front_desk">
                        <input type="text" name="note" placeholder="یادداشت بازگشت به منشی/حسابدار" style="min-width:220px;">
                        <button class="btn btn-secondary" type="submit" @disabled(empty($c['work_report']))>ارجاع بازگشت به پذیرش</button>
                    </form>
                    @if(empty($c['work_report']))
                        <p class="muted" style="margin:4px 0 0;">برای ارجاع بازگشت، اول گزارش کار را ثبت کنید.</p>
                    @endif
                @endif
            @elseif(! $isAdmin && auth()->user()->canAccess('receptions') && ($reception->custody ?? '') === 'with_technician')
                <p class="muted" style="margin-top:8px;">دستگاه نزد تعمیرکار است. بازگشت فقط با ارجاع خود تعمیرکار و تأیید شما در کارتابل ارجاع انجام می‌شود.</p>
            @endif

            @if($isAdmin)
                    </div>
                </details>
            @endif

            <div class="actions" style="margin-top:10px;">
                <a class="btn btn-ghost" href="{{ route('receptions.history', $reception) }}" target="_blank" rel="noopener">تاریخچه کامل در پنجره جدا</a>
            </div>
        </div>
        @endif

        @if($reception->isDelivered())
        <div class="panel" id="cancel-delivery-box" style="border-color:#f5d59a;background:linear-gradient(180deg,#fffaf0,#fff);">
            <h3 style="margin-top:0;">لغو تحویل / بازگشت به چرخه تعمیر</h3>
            <p class="lead" style="margin:0 0 8px;">مشتری قطعه/دستگاه را برگردانده؟ بدون قبض جدید — همان سریال روی همین قبض به تعمیر برمی‌گردد. قطعات و هزینه‌های ثبت‌شده حفظ می‌شوند. دکمه «لغو تحویل» بالای صفحه هم همین کار را می‌کند.</p>
            @if($reception->settlement_mode)
                <p class="muted" style="margin:0 0 8px;">تسویه هنگام تحویل: {{ \App\Services\ReceptionSettlementService::MODES[$reception->settlement_mode] ?? $reception->settlement_mode }}@if($reception->settlement_note) — {{ $reception->settlement_note }}@endif</p>
            @endif
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
                    <label>دلیل لغو تحویل (اختیاری)</label>
                    <input type="text" name="reason" placeholder="مثلاً قطعه خراب بود / مشتری برگشت داد" value="{{ old('reason') }}">
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
                        @if($rule->status_key === 'delivered')
                            @continue
                        @endif
                        <button type="button"
                                class="status-chip color-{{ $rule->color }} {{ $reception->status === $rule->status_key ? 'is-active' : '' }}"
                                data-status-chip="{{ $rule->status_key }}"
                                data-auto-send="{{ $rule->auto_send ? '1' : '0' }}">
                            <span class="status-chip-title">{{ $rule->title }}</span>
                            <span class="status-chip-meta">{{ $rule->auto_send ? 'پیامک مجاز' : 'بدون پیامک پیش‌فرض' }}</span>
                        </button>
                    @endforeach
                    @foreach($statuses as $key => $label)
                        @if($key === 'delivered')
                            @continue
                        @endif
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
                <p class="muted" style="margin:8px 0 0;font-size:11.5px;">
                    وضعیت «تحویل‌شده» از اینجا اجرا نمی‌شود — فقط بعد از ثبت تسویه و تأیید خروج کالا در پنل «تسویه و تحویل».
                </p>

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
                        <div class="muted" style="font-size:11px;padding-top:18px;">تحویل فقط از پنل «تسویه و تحویل».</div>
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

            <div class="sms-ticket-logs">
                <div style="display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;align-items:center;">
                    <h4 style="margin:0;">پیامک‌های این قبض</h4>
                    @if(auth()->user()->canAccess('reports.sms'))
                        <a class="btn btn-secondary" href="{{ route('reports.sms', ['reception_id' => $reception->id]) }}">گزارش کامل پیامک این قبض</a>
                    @endif
                </div>
                <p class="muted" style="margin:6px 0 0;font-size:11.5px;">
                    فهرست کامل، فیلتر و آمار پیامک‌ها در منوی
                    <strong>پیامک‌ها ← گزارش پیامک قبض‌ها</strong>
                    نمایش داده می‌شود.
                </p>
            </div>
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
                        در {{ jalali_like($reception->customer_cost_approved_at) }}
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
                                    <td>{{ jalali_like($ap->sent_at) }}</td>
                                    <td>{{ jalali_like($ap->viewed_at) }}</td>
                                    <td>{{ jalali_like($ap->decided_at) }}</td>
                                    <td dir="ltr">{{ $ap->approval_code ?: '—' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="panel" id="cost-stages-box">
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

        <div class="panel" id="parts-box">
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
                            <td>{{ jalali_date($part->used_at) }}</td>
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

    <div class="stack rx-finance-rail">
        <div class="panel" id="rx-finance">
            <h3>خلاصه مالی / فاکتور خروج</h3>
            @php
                $gross = $reception->grossCost();
                $stageRows = $costStages ?? collect();
            @endphp
            <div class="rx-cost-board">
                <div class="rx-cost-row"><span>اجرت / خدمات</span><strong>{{ toman($reception->labor_cost) }}</strong></div>
                <div class="rx-cost-row"><span>قطعات انبار</span><strong>{{ toman($reception->parts_cost) }}</strong></div>
                @forelse($stageRows as $stage)
                    <div class="rx-cost-row soft">
                        <span>{{ $stage->stage_label }}</span>
                        <strong>{{ toman($stage->amount) }}</strong>
                    </div>
                @empty
                    <div class="rx-cost-row soft"><span>مراحل هزینه</span><strong>{{ toman($reception->stages_cost) }}</strong></div>
                @endforelse
                @if((int) $reception->admission_fee > 0)
                    <div class="rx-cost-row"><span>حق پذیرش</span><strong>{{ toman($reception->admission_fee) }}</strong></div>
                @endif
                <div class="rx-cost-row total-line"><span>جمع هزینه‌های ثبت‌شده</span><strong>{{ toman($gross) }}</strong></div>
                <div class="rx-cost-row"><span>بیعانه ثبت‌شده</span><strong>{{ toman($reception->deposit) }}</strong></div>
                <div class="rx-cost-row"><span>تخفیف</span><strong>{{ toman($reception->discount) }}@if($reception->discount_reason)<small class="muted"> — {{ $reception->discount_reason }}</small>@endif</strong></div>
                <div class="rx-cost-row grand"><span>جمع فاکتور</span><strong>{{ toman($reception->total_amount) }}</strong></div>
                <div class="rx-cost-row"><span>پرداخت‌شده</span><strong>{{ toman($reception->paid_amount) }}</strong></div>
                <div class="rx-cost-row remain"><span>مانده</span><strong>{{ toman($reception->remainingAmount()) }}</strong></div>
            </div>
        </div>

        @php
            $dueGross = max(0, $gross - (int) $reception->paid_amount);
            $remain = $reception->remainingAmount();
            $canDeliverNow = ! $reception->isDelivered();
        @endphp

        @if($canDeliverNow)
        <div class="panel" id="rx-settle" style="border-color:#9ec3e8;background:linear-gradient(180deg,#f4f9ff,#fff);">
            <h3 style="margin-top:0;">تسویه و تحویل / خروج کالا</h3>
            <p class="muted" style="margin:0 0 10px;">اینجا حساب‌کتاب، تأیید خروج دستگاه و قطعات، و تحویل نهایی با هم ثبت می‌شود.</p>
            <div class="rx-pay-due">
                <span>مانده قابل تسویه</span>
                <strong>{{ number_format($remain) }} تومان</strong>
            </div>

            <div style="margin-bottom:10px;padding:8px 10px;border:1px solid #c5ccd6;border-radius:3px;background:#fff;">
                <strong style="font-size:12px;">خروج کالا از کارگاه</strong>
                <div class="muted" style="font-size:11px;margin:4px 0 6px;">
                    دستگاه:
                    {{ $reception->product_name }} {{ $reception->brand }} {{ $reception->model }}
                    @if($reception->serial_number)
                        · سریال <span dir="ltr">{{ $reception->serial_number }}</span>
                    @endif
                </div>
                @if($reception->parts->count())
                    <div class="table-wrap">
                        <table class="compact-table">
                            <thead><tr><th>قطعه خروجی</th><th>تعداد</th><th>مبلغ</th></tr></thead>
                            <tbody>
                            @foreach($reception->parts as $rp)
                                <tr>
                                    <td>{{ $rp->part?->name ?: ($rp->part_name ?: '—') }}</td>
                                    <td>{{ $rp->quantity }}</td>
                                    <td>{{ toman($rp->total_price) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="muted" style="margin:0;font-size:11px;">قطعه انباری روی این قبض نیست — فقط خروج خود دستگاه تأیید می‌شود.</p>
                @endif
                @if($reception->accessories)
                    <p class="muted" style="margin:6px 0 0;font-size:11px;">لوازم همراه پذیرش: {{ $reception->accessories }}</p>
                @endif
            </div>

            <form method="POST" action="{{ route('receptions.settle-deliver', $reception) }}" id="rx-settle-form" data-confirm="تسویه، خروج کالا و تحویل ثبت شود؟">
                @csrf
                <div class="form-grid" style="grid-template-columns:1fr;">
                    <div>
                        <label>نحوه تسویه</label>
                        <select name="settlement_mode" id="rx-settle-mode" required>
                            @foreach($settlementModes as $key => $label)
                                <option value="{{ $key }}" @selected(old('settlement_mode', 'paid') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div data-settle-paid>
                        <label>روش دریافت</label>
                        <select name="method">
                            @foreach($paymentMethods as $key => $label)
                                <option value="{{ $key }}" @selected(old('method') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div data-settle-paid>
                        <label>مبلغ دریافتی (برای دریافت کامل = مانده)</label>
                        <input type="number" name="amount" min="0" value="{{ old('amount', $remain) }}" @if($remain > 0) required @endif>
                    </div>
                    <div>
                        <label>نام تحویل‌گیرنده</label>
                        <input type="text" name="pickup_name" value="{{ old('pickup_name', $reception->customer->name) }}" placeholder="نام شخص">
                    </div>
                    <div>
                        <label>موبایل تحویل‌گیرنده</label>
                        <input type="text" name="pickup_phone" value="{{ old('pickup_phone', $reception->customer->phone) }}" dir="ltr" style="text-align:left;">
                    </div>
                    <div>
                        <label>یادداشت خروج / لوازم همراه</label>
                        <input type="text" name="accessories_exit_note" value="{{ old('accessories_exit_note', $reception->accessories) }}" placeholder="مثلاً: کابل + جعبه همراه تحویل شد">
                    </div>
                    <div>
                        <label>توضیح تسویه (برای نسیه یا بخشش مهم است)</label>
                        <input type="text" name="note" id="rx-settle-note" value="{{ old('note') }}" placeholder="شماره پیگیری / دلیل نسیه یا بخشش">
                    </div>
                    <div>
                        @include('partials.toggle', [
                            'name' => 'confirm_goods_exit',
                            'label' => 'تأیید خروج دستگاه و قطعات همراه از کارگاه',
                            'checked' => (bool) old('confirm_goods_exit'),
                            'on' => 'تأیید',
                            'off' => 'خیر',
                        ])
                    </div>
                    <div>
                        @include('partials.toggle', [
                            'name' => 'send_sms',
                            'label' => 'پیامک تحویل به مشتری',
                            'checked' => true,
                            'on' => 'برود',
                            'off' => 'نرود',
                        ])
                    </div>
                </div>
                <div class="actions" style="margin-top:8px;">
                    <button class="btn btn-primary" type="submit" style="width:100%;">ثبت تسویه + خروج کالا + تحویل</button>
                </div>
                <p class="muted" style="margin:8px 0 0;font-size:11px;" id="rx-settle-hint">
                    دریافت کامل: مبلغ به صندوق می‌رود، خروج کالا تأیید و قبض تحویل می‌شود.
                </p>
            </form>
        </div>
        @endif

        <div class="panel" id="rx-pay">
            <h3>ثبت پرداخت / بیعانه</h3>
            <p class="muted" style="margin:0 0 8px;">برای بیعانه یا پرداخت جزئی. تحویل نهایی فقط از پنل بالا پس از مشخص شدن حساب‌کتاب.</p>
            <div class="rx-pay-due">
                <span>مبلغ قابل دریافت از مشتری</span>
                <strong data-due-gross="{{ $dueGross }}">{{ number_format($dueGross) }} تومان</strong>
            </div>
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
            @unless($reception->isDelivered())
            <form method="POST" action="{{ route('receptions.payments', $reception) }}" id="rx-payment-form">
                @csrf
                <input type="hidden" name="auto_discount" value="1">
                <div class="form-grid" style="grid-template-columns:1fr;">
                    <div>
                        <label>نوع پرداخت</label>
                        <select name="type">
                            @foreach($paymentTypes as $key => $label)
                                <option value="{{ $key }}" @selected($key === 'partial')>{{ $label }}</option>
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
                        <input type="number" name="amount" id="rx-pay-amount" min="1" value="{{ $dueGross }}" required>
                    </div>
                    <div>
                        <label>تخفیف تسویه نهایی (تومان)</label>
                        <input type="number" name="discount" id="rx-pay-discount" min="0" value="{{ (int) $reception->discount }}" readonly>
                        <div class="muted" style="font-size:11px;margin-top:3px;">فقط وقتی نوع پرداخت «تسویه نهایی» است؛ اگر کمتر بگیرید اختلاف تخفیف می‌شود.</div>
                    </div>
                    <div>
                        <label>دلیل تخفیف</label>
                        <input type="text" name="discount_reason" id="rx-pay-discount-reason" value="{{ $reception->discount_reason }}" placeholder="اختیاری">
                    </div>
                    <div>
                        <label>توضیح / پیگیری</label>
                        <input type="text" name="note" placeholder="شماره پیگیری کارت‌به‌کارت و …">
                    </div>
                </div>
                <div class="actions">
                    <button class="btn btn-secondary" type="submit">ثبت پرداخت</button>
                </div>
            </form>
            @else
                <p class="muted">قبض تحویل شده — برای تغییر مالی ابتدا لغو تحویل بزنید.</p>
            @endunless
            <div class="table-wrap" style="margin-top:1rem;">
                <table style="min-width:0;">
                    <thead><tr><th>نوع</th><th>مبلغ</th><th>زمان</th><th>عملیات</th></tr></thead>
                    <tbody>
                    @forelse($reception->payments as $payment)
                        <tr>
                            <td>
                                {{ $payment->typeLabel() }} / {{ $payment->methodLabel() }}
                                @if($payment->note)
                                    <div class="muted" style="font-size:11px;">{{ $payment->note }}</div>
                                @endif
                            </td>
                            <td>{{ toman($payment->amount) }}</td>
                            <td>{{ jalali_like($payment->paid_at) }}</td>
                            <td style="white-space:nowrap;">
                                <button type="button" class="btn btn-ghost" style="padding:4px 8px;font-size:11px;" data-open-modal="#pay-edit-{{ $payment->id }}">ویرایش</button>
                                <form method="POST" action="{{ route('receptions.payments.destroy', [$reception, $payment]) }}" style="display:inline;" data-confirm="این پرداخت حذف شود؟ مانده قبض دوباره محاسبه می‌شود.">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger" type="submit" style="padding:4px 8px;font-size:11px;">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">پرداختی نیست.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @foreach($reception->payments as $payment)
                <div class="app-modal" id="pay-edit-{{ $payment->id }}" hidden>
                    <div class="app-modal-dialog" role="dialog" aria-modal="true">
                        <div class="app-modal-head">
                            <strong>ویرایش پرداخت — {{ toman($payment->amount) }}</strong>
                            <button type="button" class="app-modal-close" data-close-modal aria-label="بستن">×</button>
                        </div>
                        <div class="app-modal-body">
                            <form method="POST" action="{{ route('receptions.payments.update', [$reception, $payment]) }}">
                                @csrf
                                @method('PUT')
                                <div class="form-grid" style="grid-template-columns:1fr;">
                                    <div>
                                        <label>نوع پرداخت</label>
                                        <select name="type" required>
                                            @foreach($paymentTypes as $key => $label)
                                                <option value="{{ $key }}" @selected($payment->type === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label>روش پرداخت</label>
                                        <select name="method" required>
                                            @foreach($paymentMethods as $key => $label)
                                                <option value="{{ $key }}" @selected($payment->method === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label>مبلغ</label>
                                        <input type="number" name="amount" min="1" value="{{ abs((int) $payment->amount) }}" required>
                                    </div>
                                    <div>
                                        <label>توضیح / پیگیری</label>
                                        <input type="text" name="note" value="{{ $payment->note }}" placeholder="اختیاری">
                                    </div>
                                </div>
                                <div class="actions" style="margin-top:10px;">
                                    <button class="btn btn-primary" type="submit">ذخیره اصلاح</button>
                                    <button class="btn btn-ghost" type="button" data-close-modal>انصراف</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
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
        var syncCustom = function () {
            customWrap.style.display = stageSel.value === 'custom' ? '' : 'none';
        };
        stageSel.addEventListener('change', syncCustom);
        syncCustom();
    }

    var settleMode = document.getElementById('rx-settle-mode');
    var settleHint = document.getElementById('rx-settle-hint');
    var settleNote = document.getElementById('rx-settle-note');
    if (settleMode) {
        var paidBlocks = document.querySelectorAll('[data-settle-paid]');
        var hints = {
            paid: 'دریافت کامل: مبلغ به صندوق می‌رود و قبض تحویل می‌شود.',
            credit: 'نسیه: دستگاه تحویل می‌شود و مانده در حساب مشتری بدهکار می‌ماند.',
            waive: 'بخشش: مانده صفر می‌شود (تخفیف) — ذکر دلیل الزامی است.'
        };
        var syncSettle = function () {
            var mode = settleMode.value;
            paidBlocks.forEach(function (el) {
                el.style.display = mode === 'paid' ? '' : 'none';
                el.querySelectorAll('input,select').forEach(function (inp) {
                    if (inp.name === 'amount') inp.required = mode === 'paid' && parseInt(inp.value || '0', 10) >= 0;
                });
            });
            if (settleHint) settleHint.textContent = hints[mode] || '';
            if (settleNote) settleNote.required = mode === 'waive';
        };
        settleMode.addEventListener('change', syncSettle);
        syncSettle();
    }

    var amountEl = document.getElementById('rx-pay-amount');
    var discountEl = document.getElementById('rx-pay-discount');
    var reasonEl = document.getElementById('rx-pay-discount-reason');
    var dueEl = document.querySelector('[data-due-gross]');
    var typeEl = document.querySelector('#rx-payment-form select[name="type"]');
    if (amountEl && discountEl && dueEl) {
        var due = parseInt(dueEl.getAttribute('data-due-gross') || '0', 10) || 0;
        var syncDiscount = function () {
            var amount = parseInt(amountEl.value || '0', 10) || 0;
            var isFinal = !typeEl || typeEl.value === 'final';
            var diff = isFinal ? Math.max(0, due - amount) : 0;
            discountEl.value = String(diff);
            if (diff > 0 && reasonEl && !reasonEl.value) {
                reasonEl.placeholder = 'تخفیف تسویه خودکار';
            }
        };
        amountEl.addEventListener('input', syncDiscount);
        if (typeEl) typeEl.addEventListener('change', syncDiscount);
        syncDiscount();
    }

    document.querySelectorAll('[data-status-chip]').forEach(function (chip) {
        chip.addEventListener('click', function () {
            if (chip.getAttribute('data-status-chip') !== 'delivered') return;
            var settle = document.getElementById('rx-settle');
            if (settle) {
                settle.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

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
        if (!toggleField) return;
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
