<?php

namespace App\Enums;

enum CalendarEventStatus: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Rescheduled = 'rescheduled';
}
