<?php

namespace App\Support\DesktopSync;

use App\Models\Customer;
use App\Models\FaultType;
use App\Models\LookupOption;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Reception;
use App\Models\ReferralSource;
use App\Models\Technician;

/**
 * Shop records a desktop program may keep in step with the website.
 * Passwords, licenses, and payment-gateway secrets are not in this list.
 */
class Catalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function resources(): array
    {
        return [
            self::item('referral_sources', ReferralSource::class, [
                'name' => 'string',
                'is_active' => 'bool',
            ], [], true),
            self::item('fault_types', FaultType::class, [
                'name' => 'string',
                'description' => 'text',
                'is_active' => 'bool',
            ], [], true),
            self::item('lookup_options', LookupOption::class, [
                'group_key' => 'string',
                'name' => 'string',
                'sort_order' => 'int',
                'is_active' => 'bool',
            ], [], true),
            self::item('technicians', Technician::class, [
                'name' => 'string',
                'phone' => 'string',
                'specialty' => 'string',
                'commission_percent' => 'int',
                'is_active' => 'bool',
            ], [], true),
            self::item('customers', Customer::class, [
                'name' => 'string',
                'phone' => 'string',
                'national_code' => 'string',
                'job' => 'string',
                'address' => 'text',
                'notes' => 'text',
            ], [
                'referral_source_sync_key' => ['column' => 'referral_source_id', 'model' => ReferralSource::class],
            ], true),
            self::item('parts', Part::class, [
                'code' => 'string',
                'name' => 'string',
                'brand' => 'string',
                'model' => 'string',
                'stock' => 'int',
                'purchase_price' => 'int',
                'sale_price' => 'int',
                'min_stock' => 'int',
                'is_active' => 'bool',
            ], [], true),
            self::item('receptions', Reception::class, [
                'ticket_no' => 'string',
                'receipt_no' => 'string',
                'batch_code' => 'string',
                'account_code' => 'string',
                'admission_type' => 'string',
                'service_type' => 'string',
                'repair_type' => 'string',
                'product_name' => 'string',
                'brand' => 'string',
                'model' => 'string',
                'serial_number' => 'string',
                'accessories' => 'string',
                'appearance_notes' => 'text',
                'delivered_by' => 'string',
                'pickup_name' => 'string',
                'pickup_phone' => 'string',
                'referrer' => 'string',
                'commission' => 'int',
                'hdd_capacity' => 'string',
                'warranty_return' => 'bool',
                'warranty_type' => 'string',
                'card_number' => 'string',
                'warranty_end_date' => 'date',
                'reported_fault' => 'text',
                'final_fault' => 'text',
                'technician_notes' => 'text',
                'status' => 'string',
                'custody' => 'string',
                'deposit' => 'int',
                'pos_amount' => 'int',
                'admission_fee' => 'int',
                'estimated_cost' => 'int',
                'labor_cost' => 'int',
                'parts_cost' => 'int',
                'stages_cost' => 'int',
                'discount' => 'int',
                'discount_reason' => 'string',
                'total_amount' => 'int',
                'paid_amount' => 'int',
                'payment_method' => 'string',
                'estimated_delivery_at' => 'date',
                'next_visit_at' => 'date',
                'received_at' => 'datetime',
                'delivered_at' => 'datetime',
            ], [
                'customer_sync_key' => ['column' => 'customer_id', 'model' => Customer::class, 'required' => true],
                'technician_sync_key' => ['column' => 'technician_id', 'model' => Technician::class],
                'fault_type_sync_key' => ['column' => 'fault_type_id', 'model' => FaultType::class],
            ], false),
            self::item('payments', Payment::class, [
                'type' => 'string',
                'method' => 'string',
                'amount' => 'int',
                'note' => 'text',
                'paid_at' => 'datetime',
            ], [
                'reception_sync_key' => ['column' => 'reception_id', 'model' => Reception::class, 'required' => true],
                'customer_sync_key' => ['column' => 'customer_id', 'model' => Customer::class, 'required' => true],
            ], false),
        ];
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_column(self::resources(), 'name');
    }

    /**
     * @return list<class-string>
     */
    public static function models(): array
    {
        return array_column(self::resources(), 'model');
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(string $name): ?array
    {
        foreach (self::resources() as $resource) {
            if ($resource['name'] === $name) {
                return $resource;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function forModel(object $model): ?array
    {
        $class = $model::class;
        foreach (self::resources() as $resource) {
            if ($resource['model'] === $class) {
                return $resource;
            }
        }

        return null;
    }

    /**
     * @param  array<string, string>  $fields
     * @param  array<string, array<string, mixed>>  $relations
     * @return array<string, mixed>
     */
    private static function item(string $name, string $model, array $fields, array $relations, bool $deletable): array
    {
        return [
            'name' => $name,
            'model' => $model,
            'fields' => $fields,
            'relations' => $relations,
            'deletable' => $deletable,
        ];
    }
}
