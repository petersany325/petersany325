<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Services\NiazpardazSms;

class AppointmentObserver
{
    public function created(Appointment $appointment): void
    {
        app(NiazpardazSms::class)->notifyAppointmentCreated($appointment);
    }

    public function updated(Appointment $appointment): void
    {
        if (! $appointment->wasChanged('status')) {
            return;
        }

        if ($appointment->status === 'confirmed') {
            app(NiazpardazSms::class)->notifyAppointmentConfirmed($appointment);
        }
    }
}
