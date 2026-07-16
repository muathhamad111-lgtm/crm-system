<?php

namespace App\Enums;

enum AppRole: string
{
    case Customer = 'customer';
    case SupportStaff = 'support_staff';
    case SupportSupervisor = 'support_supervisor';
    case ProductTeam = 'product_team';
    case TechTeam = 'tech_team';
    case ManagementTeam = 'management team';
    case SystemAdmin = 'system_admin';
    case TechManager = 'tech_manager';
    case ProductManager = 'product_manager';
}
