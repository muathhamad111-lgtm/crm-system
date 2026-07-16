<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case PendingConfirmation = 'pending_confirmation';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';
    case Rescheduled = 'rescheduled';
}
