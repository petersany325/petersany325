<?php

namespace App\Observers;

use App\Models\Client;
use App\Services\NiazpardazSms;

class ClientObserver
{
    public function updated(Client $client): void
    {
        if (! $client->wasChanged('status') || $client->status !== 'confirmed') {
            return;
        }

        if ($client->confirmed_at === null) {
            $client->forceFill(['confirmed_at' => now()])->saveQuietly();
        }

        app(NiazpardazSms::class)->notifyAdvocacyConfirmed($client);
    }

    public function created(Client $client): void
    {
        if ($client->status !== 'confirmed') {
            return;
        }

        if ($client->confirmed_at === null) {
            $client->forceFill(['confirmed_at' => now()])->saveQuietly();
        }

        app(NiazpardazSms::class)->notifyAdvocacyConfirmed($client);
    }
}
