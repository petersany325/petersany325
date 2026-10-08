<?php

namespace App\Filament\Resources\RecoveryJobs\Pages;

use App\Filament\Resources\RecoveryJobs\RecoveryJobResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRecoveryJobs extends ManageRecords
{
    protected static string $resource = RecoveryJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
