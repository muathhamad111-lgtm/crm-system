<?php

namespace App\Enums;

enum RequestStatus: string
{
    case New = 'new';
    case UnderReview = 'under_review';
    case AwaitingCustomer = 'awaiting_customer';
    case InProgress = 'in_progress';
    case AwaitingInternal = 'awaiting_internal';
    case Escalated = 'escalated';
    case Completed = 'completed';
    case Closed = 'closed';
    case Rejected = 'rejected';
    case Reopened = 'reopened';
    case Cancelled = 'cancelled';
}
