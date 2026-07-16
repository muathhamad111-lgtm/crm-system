<?php

namespace App\Enums;

enum KbUsageAction: string
{
    case View = 'view';
    case InsertSolution = 'insert_solution';
    case SendToCustomer = 'send_to_customer';
    case Copy = 'copy';
    case SearchHit = 'search_hit';
}
