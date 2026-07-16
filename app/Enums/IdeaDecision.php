<?php

namespace App\Enums;

enum IdeaDecision: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Postponed = 'postponed';
    case Merged = 'merged';
}
