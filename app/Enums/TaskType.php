<?php

namespace App\Enums;

enum TaskType: string
{
    case Main = 'main';
    case Subtask = 'subtask';
    case Checklist = 'checklist';
    case Followup = 'followup';
    case Approval = 'approval';
    case Automated = 'automated';
}
