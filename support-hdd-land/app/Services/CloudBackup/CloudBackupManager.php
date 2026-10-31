<?php

namespace App\Services\CloudBackup;

use App\Support\BackupSettings;

class CloudBackupManager
{
    public function __construct(
        private GoogleDriveDestination $google = new GoogleDriveDestination,
        private OneDriveDestination $onedrive = new OneDriveDestination,
        private MegaDestination $mega = new MegaDestination,
    ) {
    }

    public function google(): GoogleDriveDestination
    {
        return $this->google;
    }

    public function onedrive(): OneDriveDestination
    {
        return $this->onedrive;
    }

    public function mega(): MegaDestination
    {
        return $this->mega;
    }

    /**
     * Upload a local backup to every enabled & connected cloud.
     *
     * @return array{ok:bool,message:string,details:list<string>}
     */
    public function uploadToEnabled(string $localPath): array
    {
        $details = [];
        $anyEnabled = false;
        $allOk = true;

        foreach (['google', 'onedrive', 'mega'] as $key) {
            $cfg = BackupSettings::cloud($key);
            if (! $cfg['enabled']) {
                continue;
            }
            $anyEnabled = true;
            if (! $cfg['connected'] && $key !== 'mega') {
                $allOk = false;
                $details[] = BackupSettings::CLOUD_LABELS[$key].': هنوز متصل نشده';
                continue;
            }
            $result = match ($key) {
                'google' => $this->google->upload($localPath),
                'onedrive' => $this->onedrive->upload($localPath),
                'mega' => $this->mega->upload($localPath),
            };
            $details[] = ($result['ok'] ? '✓ ' : '✗ ').($result['message'] ?? '');
            if (! ($result['ok'] ?? false)) {
                $allOk = false;
            }
        }

        if (! $anyEnabled) {
            return ['ok' => true, 'message' => 'هیچ کلود فعالی برای آپلود انتخاب نشده.', 'details' => []];
        }

        return [
            'ok' => $allOk,
            'message' => $allOk ? 'آپلود به کلودهای فعال موفق بود.' : 'آپلود به بعضی کلودها ناموفق بود.',
            'details' => $details,
        ];
    }
}
