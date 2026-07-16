<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CalendarEvent extends Model
{
    use HasUuids;

    protected $table = 'calendar_events';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'all_day' => 'boolean',
            'created_at' => 'datetime',
            'ends_at' => 'datetime',
            'event_type' => \App\Enums\CalendarEventType::class,
            'starts_at' => 'datetime',
            'status' => \App\Enums\CalendarEventStatus::class,
            'updated_at' => 'datetime',
            'visibility' => \App\Enums\CalendarVisibility::class,
        ];
    }

    public function assignee()
    {
        return $this->belongsTo(Profile::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(Profile::class, 'created_by');
    }

    public function relatedCustomer()
    {
        return $this->belongsTo(Profile::class, 'related_customer_id');
    }

    public function relatedRequest()
    {
        return $this->belongsTo(Request::class, 'related_request_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}
