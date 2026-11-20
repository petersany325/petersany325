<?php

namespace App\Support\DesktopSync;

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

    /**
     * String names, not ::class. A shop that does not have one of these models
     * must still be able to load the registry and answer ping.
     *
     * @var array<string, class-string>
     */
    private const MODELS = [
        'customers' => 'App\\Models\\Customer',
        'referral_sources' => 'App\\Models\\ReferralSource',
        'receptions' => 'App\\Models\\Reception',
        'reception_status_logs' => 'App\\Models\\ReceptionStatusLog',
        'reception_work_reports' => 'App\\Models\\ReceptionWorkReport',
        'reception_parts' => 'App\\Models\\ReceptionPart',
        'reception_cost_stages' => 'App\\Models\\ReceptionCostStage',
        'cost_approvals' => 'App\\Models\\CostApproval',
        'device_handoffs' => 'App\\Models\\DeviceHandoff',
        'payments' => 'App\\Models\\Payment',
        'payment_receipts' => 'App\\Models\\PaymentReceipt',
        'customer_messages' => 'App\\Models\\CustomerMessage',
        'users' => 'App\\Models\\User',
        'technicians' => 'App\\Models\\Technician',
        'interns' => 'App\\Models\\Intern',
        'lookup_options' => 'App\\Models\\LookupOption',
        'fault_types' => 'App\\Models\\FaultType',
        'daily_log_categories' => 'App\\Models\\DailyLogCategory',
        'sms_status_rules' => 'App\\Models\\SmsStatusRule',
        'daily_log_entries' => 'App\\Models\\DailyLogEntry',
        'parts' => 'App\\Models\\Part',
        'warehouses' => 'App\\Models\\Warehouse',
        'stock_movements' => 'App\\Models\\StockMovement',
        'accounts' => 'App\\Models\\Account',
        'journal_entries' => 'App\\Models\\JournalEntry',
        'journal_lines' => 'App\\Models\\JournalLine',
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
