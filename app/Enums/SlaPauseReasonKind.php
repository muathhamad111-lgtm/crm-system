<?php

namespace App\Enums;

enum SlaPauseReasonKind: string
{
    case AwaitingCustomer = 'awaiting_customer';
    case AwaitingExternalParty = 'awaiting_external_party';
    case AwaitingApproval = 'awaiting_approval';
    case Other = 'other';
}
