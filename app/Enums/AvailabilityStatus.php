<?php

namespace App\Enums;

enum AvailabilityStatus: string
{
    case Available = 'available';
    case OnLeave = 'on_leave';
    case OffHours = 'off_hours';
    case Unavailable = 'unavailable';
    case Busy = 'busy';
    case Training = 'training';
    case Secondment = 'secondment';
}
