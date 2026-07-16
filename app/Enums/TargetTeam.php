<?php

namespace App\Enums;

enum TargetTeam: string
{
    case SupportTeam = 'support team';
    case CustomerExperienceTeam = 'customer_experience team';
    case ProductTeam = 'product team';
    case TechTeam = 'tech team';
    case FinanceTeam = 'finance team';
    case ManagementTeam = 'management team';
}
