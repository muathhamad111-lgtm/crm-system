<?php

namespace App\Enums;

enum AppointmentMode: string
{
    case Phone = 'phone';
    case Video = 'video';
    case Onsite = 'onsite';
    case Training = 'training';
    case Followup = 'followup';
    case Other = 'other';
}
