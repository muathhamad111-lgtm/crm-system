<?php

namespace App\Services\Sla;

use App\Models\BusinessCalendar;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * SLA business-hours engine — port of the original Postgres compute_sla_deadline /
 * business_add_minutes. Adds working minutes to a start time, skipping non-working
 * days and holidays according to a business calendar's weekly schedule.
 *
 * Defaults (mirrors original): Asia/Riyadh, Sun–Thu, 08:00–17:00.
 */
class SlaService
{
    /** @var array<int,array{enabled:bool,start:string,end:string}> keyed by ISO day (0=Sun..6=Sat) */
    private array $weekly;

    private string $timezone;

    /** @var array<string> holiday dates (Y-m-d) that fully exclude SLA */
    private array $holidays;

    public function __construct(?BusinessCalendar $calendar = null)
    {
        $calendar ??= BusinessCalendar::query()->where('is_default', true)->first();

        $this->timezone = $calendar->timezone ?? 'Asia/Riyadh';
        $this->weekly = $this->normalizeWeekly($calendar->weekly_schedule ?? null);
        $this->holidays = $this->loadHolidays($calendar?->id);
    }

    /**
     * Add $minutes of working time to $start, respecting business hours & holidays.
     * If $businessHoursOnly is false, returns a plain start+minutes.
     */
    public function computeDeadline(CarbonInterface $start, int $minutes, bool $businessHoursOnly = true): Carbon
    {
        $cursor = Carbon::parse($start)->setTimezone($this->timezone);

        if (! $businessHoursOnly || $minutes <= 0) {
            return $cursor->copy()->addMinutes(max(0, $minutes));
        }

        $remaining = $minutes;
        $guard = 0;

        while ($remaining > 0 && $guard < 3660) { // ~10 years of days
            $guard++;
            $dow = (int) $cursor->dayOfWeek; // 0=Sun .. 6=Sat
            $day = $this->weekly[$dow] ?? ['enabled' => false];

            if (! ($day['enabled'] ?? false) || $this->isHoliday($cursor)) {
                $cursor = $cursor->copy()->addDay()->startOfDay();
                continue;
            }

            [$sh, $sm] = $this->parseTime($day['start']);
            [$eh, $em] = $this->parseTime($day['end']);
            $winStart = $cursor->copy()->setTime($sh, $sm);
            $winEnd = $cursor->copy()->setTime($eh, $em);

            if ($cursor->lt($winStart)) {
                $cursor = $winStart;
            }
            if ($cursor->gte($winEnd)) {
                $cursor = $cursor->copy()->addDay()->startOfDay();
                continue;
            }

            $available = $cursor->diffInMinutes($winEnd);
            if ($remaining <= $available) {
                return $cursor->copy()->addMinutes($remaining);
            }

            $remaining -= $available;
            $cursor = $cursor->copy()->addDay()->startOfDay();
        }

        return $cursor;
    }

    private function isHoliday(CarbonInterface $d): bool
    {
        return in_array($d->format('Y-m-d'), $this->holidays, true)
            || in_array($d->format('m-d'), $this->holidays, true);
    }

    private function parseTime(?string $t): array
    {
        $t = $t ?: '08:00';
        $parts = explode(':', $t);

        return [(int) ($parts[0] ?? 8), (int) ($parts[1] ?? 0)];
    }

    /** Normalize a weekly_schedule JSON (various shapes) into a 0..6 keyed map. */
    private function normalizeWeekly($schedule): array
    {
        $default = [
            0 => ['enabled' => true, 'start' => '08:00', 'end' => '17:00'], // Sun
            1 => ['enabled' => true, 'start' => '08:00', 'end' => '17:00'], // Mon
            2 => ['enabled' => true, 'start' => '08:00', 'end' => '17:00'], // Tue
            3 => ['enabled' => true, 'start' => '08:00', 'end' => '17:00'], // Wed
            4 => ['enabled' => true, 'start' => '08:00', 'end' => '17:00'], // Thu
            5 => ['enabled' => false, 'start' => '08:00', 'end' => '17:00'], // Fri
            6 => ['enabled' => false, 'start' => '08:00', 'end' => '17:00'], // Sat
        ];

        if (! is_array($schedule)) {
            return $default;
        }

        $dayMap = ['sun' => 0, 'mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6];
        $out = $default;
        foreach ($schedule as $k => $v) {
            $idx = is_numeric($k) ? (int) $k : ($dayMap[strtolower(substr((string) $k, 0, 3))] ?? null);
            if ($idx === null || ! is_array($v)) {
                continue;
            }
            $out[$idx] = [
                'enabled' => (bool) ($v['enabled'] ?? true),
                'start' => $v['start'] ?? '08:00',
                'end' => $v['end'] ?? '17:00',
            ];
        }

        return $out;
    }

    private function loadHolidays(?string $calendarId): array
    {
        try {
            return DB::table('holidays')
                ->when($calendarId, fn ($q) => $q->where(fn ($w) => $w->where('calendar_id', $calendarId)->orWhereNull('calendar_id')))
                ->where('exclude_sla', true)
                ->get(['holiday_date', 'is_recurring'])
                ->map(fn ($h) => $h->is_recurring
                    ? Carbon::parse($h->holiday_date)->format('m-d')
                    : Carbon::parse($h->holiday_date)->format('Y-m-d'))
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
