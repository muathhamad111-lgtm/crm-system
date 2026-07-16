<?php

namespace App\Enums;

enum KbArticleStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Archived = 'archived';
}
