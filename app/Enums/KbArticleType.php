<?php

namespace App\Enums;

enum KbArticleType: string
{
    case Faq = 'faq';
    case Sop = 'sop';
    case KnownIssue = 'known_issue';
    case Resolution = 'resolution';
    case Macro = 'macro';
    case Policy = 'policy';
    case UserGuide = 'user_guide';
}
