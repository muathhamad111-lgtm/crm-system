<?php

namespace App\Enums;

enum CalendarEventType: string
{
    case Visit = 'visit';
    case Meeting = 'meeting';
    case Call = 'call';
    case Reminder = 'reminder';
    case Task = 'task';
    case Other = 'other';
}
