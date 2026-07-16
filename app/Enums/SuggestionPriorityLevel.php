<?php

namespace App\Enums;

enum SuggestionPriorityLevel: string
{
    case Critical = 'critical';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';
}
