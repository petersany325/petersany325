<?php

declare(strict_types=1);

function received_checks_extend(Database $db): bool
{
    $table = $db->one(
        'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
        ['ak_checks']
    );
    if (!$table) {
        return false;
    }
    $columns = [
        'sayad' => 'CHAR(16) NOT NULL DEFAULT ""',
        'branch' => 'VARCHAR(80) NOT NULL DEFAULT ""',
        'received_on' => 'DATE NULL',
    ];
    foreach ($columns as $name => $definition) {
        $row = $db->one(
            'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['ak_checks', $name]
        );
        if ($row) {
            continue;
        }
        $db->exec('ALTER TABLE `ak_checks` ADD COLUMN `' . $name . '` ' . $definition);
    }
    return true;
}

/** @return list<string> */
function received_check_banks(): array
{
    return [
        'ملی',
        'سپه',
        'ملت',
        'تجارت',
        'صادرات',
        'رفاه کارگران',
        'کشاورزی',
        'مسکن',
        'توسعه صادرات',
        'صنعت و معدن',
        'پست بانک',
        'توسعه تعاون',
        'پارسیان',
        'پاسارگاد',
        'اقتصاد نوین',
        'سامان',
        'سرمایه',
        'سینا',
        'شهر',
        'دی',
        'آینده',
        'گردشگری',
        'ایران زمین',
        'خاورمیانه',
        'کارآفرین',
        'قرض‌الحسنه مهر ایران',
        'قرض‌الحسنه رسالت',
        'مؤسسه اعتباری ملل',
        'مشترک ایران و ونزوئلا',
    ];
}
