<?php

namespace App\Enums;

enum RequestCategoryKey: string
{
    case TechnicalIssue = 'technical_issue';
    case Complaint = 'complaint';
    case Inquiry = 'inquiry';
    case Suggestion = 'suggestion';
    case ServiceRequest = 'service_request';
    case ModificationRequest = 'modification_request';
    case TrainingRequest = 'training_request';
    case OperationalSupport = 'operational_support';
    case FinancialContractual = 'financial_contractual';
    case Other = 'other';
}
