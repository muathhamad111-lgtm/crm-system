<?php

namespace App\Enums;

enum RequestChannel: string
{
    case Portal = 'portal';
    case Email = 'email';
    case Phone = 'phone';
    case Whatsapp = 'whatsapp';
    case InProduct = 'in_product';
    case Other = 'other';
}
