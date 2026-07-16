<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\CapabilityMeta;
use App\Models\Category;
use App\Models\IntegrationSetting;
use App\Models\NotificationTemplate;
use App\Models\PriorityMultiplier;
use App\Models\Product;
use App\Models\Profile;
use App\Models\SystemIntegration;
use App\Models\SystemSetting;
use App\Models\Team;
use App\Models\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    /** Admin console — data for the tabbed UI. */
    public function index(Request $request): Response
    {
        // Request counts per category (single grouped query).
        $counts = DB::table('requests')
            ->select('category_id', DB::raw('count(*) as total'))
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $categories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->get([
                'id', 'key', 'name_ar', 'name_en', 'default_priority', 'target_team',
                'sla_hours', 'response_sla_hours', 'auto_close_days', 'is_suggestion',
                'active', 'sort_order', 'color', 'icon_name',
            ])
            ->map(function ($c) use ($counts) {
                $c->requests_count = (int) ($counts[$c->id] ?? 0);

                return $c;
            });

        $products = Product::query()
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'type', 'description_ar', 'sla_hours', 'escalation_hours', 'restricted', 'active', 'color', 'sort_order']);

        $teams = Team::query()
            ->orderBy('sort_order')
            ->get(['id', 'key', 'name_ar', 'description', 'color', 'icon', 'manager_id', 'active', 'sort_order']);

        // System settings as a key => value map.
        $settings = SystemSetting::query()->get(['key', 'value'])
            ->mapWithKeys(fn ($s) => [$s->key => $s->value]);

        $multipliers = PriorityMultiplier::query()->get(['priority', 'multiplier', 'label_ar']);

        // Staff directory: profiles that carry at least one non-customer role.
        $allRoles = UserRole::query()->get(['user_id', 'role']);
        $rolesByUser = $allRoles->groupBy('user_id')
            ->map(fn ($g) => $g->pluck('role')->map(fn ($r) => is_object($r) ? $r->value : $r)->values()->all());

        $staffIds = $allRoles
            ->filter(fn ($r) => ((is_object($r->role) ? $r->role->value : $r->role) !== 'customer'))
            ->pluck('user_id')->unique()->values();

        $staff = Profile::query()
            ->whereIn('id', $staffIds)
            ->orderBy('staff_sort_order')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'email', 'phone', 'team_id', 'account_status', 'suspended', 'staff_sort_order'])
            ->map(function ($p) use ($rolesByUser) {
                $p->role_keys = $rolesByUser[$p->id] ?? [];

                return $p;
            });

        // Capabilities matrix summary: allowed-capability counts per role + the capability catalogue.
        $capabilityCounts = DB::table('role_permissions')
            ->select('role', DB::raw('count(*) as total'))
            ->where('allowed', true)
            ->groupBy('role')
            ->pluck('total', 'role');

        $rolePermissions = DB::table('role_permissions')
            ->where('allowed', true)
            ->orderBy('role')
            ->orderBy('capability')
            ->get(['role', 'capability', 'action_type']);

        $capabilityMeta = CapabilityMeta::query()
            ->orderBy('capability')
            ->get(['capability', 'action_type', 'description']);

        $notificationTemplates = NotificationTemplate::query()
            ->orderBy('name_ar')
            ->get(['id', 'event_key', 'name_ar', 'recipient_type', 'channels', 'active']);

        $integrationSettings = IntegrationSetting::query()
            ->get(['id', 'key', 'label', 'config', 'enabled']);

        $systemIntegrations = SystemIntegration::query()
            ->get(['id', 'key', 'name_ar', 'config', 'active', 'manager_id']);

        $auditLog = AdminAuditLog::query()
            ->latest('created_at')
            ->limit(60)
            ->get(['id', 'action', 'entity_type', 'entity_id', 'actor_email', 'details', 'created_at']);

        // Role options for role-assignment / category-editor dropdowns.
        $roleOptions = [
            'customer', 'support_staff', 'support_supervisor', 'product_team',
            'product_manager', 'tech_team', 'tech_manager', 'management team', 'system_admin',
        ];
        $teamOptions = [
            'support team', 'customer_experience team', 'product team',
            'tech team', 'finance team', 'management team',
        ];
        $priorityOptions = ['urgent', 'high', 'medium', 'low'];

        return Inertia::render('Admin/Index', [
            'categories' => $categories,
            'products' => $products,
            'teams' => $teams,
            'settings' => $settings,
            'multipliers' => $multipliers,
            'staff' => $staff,
            'capabilityCounts' => $capabilityCounts,
            'rolePermissions' => $rolePermissions->groupBy('role'),
            'capabilityMeta' => $capabilityMeta,
            'notificationTemplates' => $notificationTemplates,
            'integrationSettings' => $integrationSettings,
            'systemIntegrations' => $systemIntegrations,
            'auditLog' => $auditLog,
            'options' => [
                'roles' => $roleOptions,
                'teams' => $teamOptions,
                'priorities' => $priorityOptions,
            ],
        ]);
    }

    /** Upsert a single system_settings key/value pair. */
    public function updateSetting(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:191'],
            'value' => ['nullable'],
        ]);

        // Accept raw JSON (object/array) or a plain scalar/string.
        $value = $data['value'];
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        SystemSetting::query()->updateOrCreate(
            ['key' => $data['key']],
            ['value' => $value, 'updated_at' => now()],
        );

        $this->audit($request, 'setting.update', 'system_settings', $data['key'], ['key' => $data['key']]);

        return back()->with('success', 'تم حفظ الإعداد.');
    }

    /** Update a category's basic configuration fields. */
    public function updateCategory(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:191'],
            'default_priority' => ['required', 'in:urgent,high,medium,low'],
            'target_team' => ['required', 'string', 'max:64'],
            'sla_hours' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'active' => ['required', 'boolean'],
        ]);

        $before = $category->only(['name_ar', 'default_priority', 'target_team', 'sla_hours', 'active']);

        $category->fill($data)->save();

        $this->audit($request, 'category.update', 'categories', $category->id, [
            'before' => $before,
            'after' => $data,
        ]);

        return back()->with('success', 'تم تحديث التصنيف.');
    }

    /** Update a priority multiplier value. */
    public function setPriorityMultiplier(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'priority' => ['required', 'in:urgent,high,medium,low'],
            'multiplier' => ['required', 'numeric', 'min:0', 'max:1000'],
            'label_ar' => ['nullable', 'string', 'max:191'],
        ]);

        $payload = [
            'multiplier' => $data['multiplier'],
            'updated_by' => $request->user()->id,
            'updated_at' => now(),
        ];
        if (array_key_exists('label_ar', $data) && $data['label_ar'] !== null) {
            $payload['label_ar'] = $data['label_ar'];
        }

        PriorityMultiplier::query()->updateOrCreate(
            ['priority' => $data['priority']],
            $payload,
        );

        $this->audit($request, 'priority_multiplier.update', 'priority_multipliers', $data['priority'], $data);

        return back()->with('success', 'تم تحديث معامل الأولوية.');
    }

    /** Assign a role to a profile (idempotent). */
    public function assignRole(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'string', 'exists:profiles,id'],
            'role' => ['required', 'string', 'max:64'],
        ]);

        UserRole::query()->firstOrCreate([
            'user_id' => $data['user_id'],
            'role' => $data['role'],
        ]);

        Cache::forget("caps:{$data['user_id']}");

        $this->audit($request, 'role.assign', 'user_roles', $data['user_id'], $data);

        return back()->with('success', 'تمت إضافة الدور.');
    }

    /** Remove a role from a profile. */
    public function removeRole(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'string', 'exists:profiles,id'],
            'role' => ['required', 'string', 'max:64'],
        ]);

        UserRole::query()
            ->where('user_id', $data['user_id'])
            ->where('role', $data['role'])
            ->delete();

        Cache::forget("caps:{$data['user_id']}");

        $this->audit($request, 'role.remove', 'user_roles', $data['user_id'], $data);

        return back()->with('success', 'تمت إزالة الدور.');
    }

    /** Write an admin audit-log entry. */
    private function audit(Request $request, string $action, string $entityType, ?string $entityId, array $details): void
    {
        try {
            AdminAuditLog::query()->create([
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'actor_id' => $request->user()?->id,
                'actor_email' => $request->user()?->email,
                'details' => $details,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Auditing must never break the primary action.
        }
    }
}
