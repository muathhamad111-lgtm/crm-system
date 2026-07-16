<?php

namespace App\Enums;

enum TaskSlaImpact: string
{
    case None = 'none';
    case PauseSla = 'pause_sla';
    case ExtendSla = 'extend_sla';
    case AwaitingCustomer = 'awaiting_customer';
    case AwaitingExternal = 'awaiting_external';
    case BlocksStage = 'blocks_stage';
}
