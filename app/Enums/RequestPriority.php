<?php

namespace App\Enums;

enum RequestPriority: string
{
    case Urgent = 'urgent';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';
}
