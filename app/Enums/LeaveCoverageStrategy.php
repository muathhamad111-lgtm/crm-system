<?php

namespace App\Enums;

enum LeaveCoverageStrategy: string
{
    case MoveAll = 'move_all';
    case MoveCriticalOverdue = 'move_critical_overdue';
    case MoveOpen = 'move_open';
    case Manual = 'manual';
    case None = 'none';
}
