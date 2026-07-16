<?php

namespace App\Enums;

enum IdeaStage: string
{
    case Received = 'received';
    case UnderReview = 'under_review';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Implemented = 'implemented';
    case Archived = 'archived';
    case UnderStudy = 'under_study';
    case Shortlisted = 'shortlisted';
    case Published = 'published';
    case Voting = 'voting';
    case Approved = 'approved';
    case OnRoadmap = 'on_roadmap';
    case Postponed = 'postponed';
}
