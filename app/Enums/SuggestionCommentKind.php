<?php

namespace App\Enums;

enum SuggestionCommentKind: string
{
    case UseCase = 'use_case';
    case Feedback = 'feedback';
    case Challenge = 'challenge';
    case ComplementaryIdea = 'complementary_idea';
    case General = 'general';
}
