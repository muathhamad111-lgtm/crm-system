<?php

namespace App\Enums;

enum SuggestionVoteType: string
{
    case Support = 'support';
    case StrongSupport = 'strong_support';
    case Against = 'against';
}
