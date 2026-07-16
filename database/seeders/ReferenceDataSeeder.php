<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferenceDataSeeder extends Seeder
{
    /**
     * Tables loaded in FK-respecting order from JSON snapshots extracted
     * from the original Postgres seed migrations.
     */
    private array $order = [
        'teams',
        'products',
        'categories',
        'request_sub_categories',
        'priority_multipliers',
        'priority_sla',
        'stage_sla_config',
        'business_calendars',
        'leave_types',
        'appointment_types',
        'appointment_availability',
        'capability_meta',
        'role_permissions',
        'comment_templates',
        'notification_templates',
        'sms_customer_templates',
        'integration_settings',
        'system_integrations',
        'system_settings',
    ];

    /** Tables whose primary key is NOT a generated uuid `id` column. */
    private array $noIdTables = [
        'priority_multipliers', // pk: priority
        'capability_meta',      // pk: capability
        'system_settings',      // pk: key
    ];

    public function run(): void
    {
        $dir = database_path('seeders/data');

        foreach ($this->order as $table) {
            $path = "{$dir}/{$table}.json";
            if (! is_file($path)) {
                $this->command?->warn("skip {$table}: no data file");
                continue;
            }

            $rows = json_decode(file_get_contents($path), true) ?: [];
            if (! $rows) {
                continue;
            }

            $needsId = ! in_array($table, $this->noIdTables, true);

            foreach ($rows as &$row) {
                if ($needsId && empty($row['id'])) {
                    $row['id'] = (string) Str::uuid();
                }
                foreach ($row as $col => $val) {
                    $row[$col] = $this->normalizeValue($val);
                }
            }
            unset($row);

            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table($table)->insertOrIgnore($chunk);
            }

            $this->command?->info(sprintf('seeded %s: %d rows', $table, count($rows)));
        }
    }

    /**
     * Convert Postgres array literals ({a,b}) that are not valid JSON into
     * JSON arrays so they insert cleanly into MySQL json columns. Valid JSON
     * text (objects/arrays from jsonb columns) is passed through untouched.
     */
    private function normalizeValue(mixed $val): mixed
    {
        if (! is_string($val) || $val === '') {
            return $val;
        }
        $trimmed = trim($val);
        if (! str_starts_with($trimmed, '{') || ! str_ends_with($trimmed, '}')) {
            return $val;
        }
        // Already valid JSON (e.g. a jsonb object) — leave as-is.
        json_decode($trimmed);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $val;
        }
        // Postgres array literal -> JSON array.
        $inner = substr($trimmed, 1, -1);
        if (trim($inner) === '') {
            return '[]';
        }
        $parts = str_getcsv($inner, ',', '"', '\\');
        $parts = array_map(fn ($p) => trim($p), $parts);

        return json_encode($parts, JSON_UNESCAPED_UNICODE);
    }
}
