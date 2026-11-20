<?php

namespace App\Support\DesktopSync;

use App\Models\Account;
use App\Models\CostApproval;
use App\Models\Customer;
use App\Models\CustomerMessage;
use App\Models\DailyLogCategory;
use App\Models\DailyLogEntry;
use App\Models\DeviceHandoff;
use App\Models\FaultType;
use App\Models\Intern;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\LookupOption;
use App\Models\Part;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use App\Models\Reception;
use App\Models\ReceptionCostStage;
use App\Models\ReceptionPart;
use App\Models\ReceptionStatusLog;
use App\Models\ReceptionWorkReport;
use App\Models\ReferralSource;
use App\Models\SmsStatusRule;
use App\Models\StockMovement;
use App\Models\Technician;
use App\Models\User;
use App\Models\Warehouse;

class SyncTableRegistry
{
    /** @var list<string> */
    public const READ = [
        'customers', 'referral_sources',
        'receptions', 'reception_status_logs', 'reception_work_reports', 'reception_parts',
        'reception_cost_stages', 'cost_approvals', 'device_handoffs',
        'payments', 'payment_receipts',
        'customer_messages', 'remote_part_preorders', 'portal_invite_batches', 'portal_invite_sends',
        'partners', 'network_referrals',
        'users', 'technicians', 'interns',
        'lookup_options', 'fault_types', 'daily_log_categories', 'price_tiers', 'sms_status_rules',
        'parts', 'warehouses', 'stock_movements',
        'accounts', 'journal_entries', 'journal_lines',
        'daily_log_entries',
        'app_settings',
    ];

    /** @var array<string, class-string> */
    private const MODELS = [
        'customers' => Customer::class,
        'referral_sources' => ReferralSource::class,
        'receptions' => Reception::class,
        'reception_status_logs' => ReceptionStatusLog::class,
        'reception_work_reports' => ReceptionWorkReport::class,
        'reception_parts' => ReceptionPart::class,
        'reception_cost_stages' => ReceptionCostStage::class,
        'cost_approvals' => CostApproval::class,
        'device_handoffs' => DeviceHandoff::class,
        'payments' => Payment::class,
        'payment_receipts' => PaymentReceipt::class,
        'customer_messages' => CustomerMessage::class,
        'users' => User::class,
        'technicians' => Technician::class,
        'interns' => Intern::class,
        'lookup_options' => LookupOption::class,
        'fault_types' => FaultType::class,
        'daily_log_categories' => DailyLogCategory::class,
        'sms_status_rules' => SmsStatusRule::class,
        'daily_log_entries' => DailyLogEntry::class,
        'parts' => Part::class,
        'warehouses' => Warehouse::class,
        'stock_movements' => StockMovement::class,
        'accounts' => Account::class,
        'journal_entries' => JournalEntry::class,
        'journal_lines' => JournalLine::class,
    ];

    /** @var array<string, list<string>> */
    private const OPS = [
        'customers' => ['upsert'],
        'referral_sources' => ['upsert'],
        'receptions' => ['upsert'],
        'reception_status_logs' => ['upsert'],
        'reception_work_reports' => ['upsert'],
        'reception_parts' => ['upsert'],
        'reception_cost_stages' => ['upsert'],
        'device_handoffs' => ['upsert'],
        'payments' => ['upsert'],
        'parts' => ['upsert'],
        'warehouses' => ['upsert'],
        'technicians' => ['upsert'],
        'interns' => ['upsert'],
        'sms_status_rules' => ['upsert'],
        'customer_messages' => ['upsert'],
        'remote_part_preorders' => ['update'],
        'payment_receipts' => ['update'],
        'partners' => ['upsert'],
        'network_referrals' => ['upsert'],
        'daily_log_entries' => ['upsert'],
        'daily_log_categories' => ['upsert'],
        'lookup_options' => ['upsert'],
        'fault_types' => ['upsert'],
    ];

    /** @var list<string> */
    public const USER_COLUMNS = [
        'id', 'name', 'email', 'phone', 'role', 'permissions', 'is_active', 'created_at', 'updated_at',
    ];

    /** @var list<string> */
    public const SETTING_KEYS = [
        'receipt_prefix', 'calendar', 'calendar_type', 'use_jalali', 'jalali_calendar',
        'digits', 'digit_style', 'persian_digits', 'portal_phone', 'portal_mobile',
        'portal_contact', 'portal_address', 'portal_email', 'invoice_shop_name',
    ];

    /** @var list<string> */
    public const REVIEW_FIELDS = [
        'payment_receipts' => ['status', 'review_note', 'reviewed_at', 'payment_id'],
        'remote_part_preorders' => ['status', 'admin_note', 'match_result', 'arrived_at', 'reviewed_at', 'reception_id'],
    ];

    /**
     * @return list<class-string>
     */
    public static function models(): array
    {
        return array_values(self::MODELS);
    }

    public static function model(string $table): ?string
    {
        return self::MODELS[$table] ?? null;
    }

    public static function readable(string $table): bool
    {
        return in_array($table, self::READ, true);
    }

    /**
     * @return list<string>
     */
    public static function ops(string $table): array
    {
        return self::OPS[$table] ?? [];
    }
}
