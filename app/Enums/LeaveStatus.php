<?php

namespace App\Enums;

enum LeaveStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Active = 'active';
    case Completed = 'completed';
}
