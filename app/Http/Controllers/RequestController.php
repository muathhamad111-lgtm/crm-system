<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\FieldDefinition;
use App\Models\Product;
use App\Models\Request as CrmRequest;
use App\Models\RequestActivityLog;
use App\Models\RequestComment;
use App\Models\RequestFieldValue;
use App\Models\RequestRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class RequestController extends Controller
{
    /** Statuses considered "closed/terminal" for the SLA soft-status. */
    private const TERMINAL = ['closed', 'completed', 'rejected', 'cancelled'];

    /**
     * Ticket list / inbox. Status-tab filter (?status), search (?q on
     * title/request_number), pagination. Customers see their own rows only.
     */
    public function index(Request $httpRequest)
    {
        $user = $httpRequest->user();
        $isStaff = $user->isStaff();

        $status = $httpRequest->query('status');
        $q = trim((string) $httpRequest->query('q', ''));

        // Base (scoped) builder — cloned per use so filters don't leak.
        $base = fn () => CrmRequest::query()
            ->when(! $isStaff, fn ($b) => $b->where('customer_id', $user->id));

        // Counts per status (for the tabs) — scoped, ignores status/q filters.
        $rawCounts = $base()
            ->select('status', DB::raw('count(*) as c'))
            ->groupBy('status')
            ->pluck('c', 'status');
        $counts = ['all' => (int) $rawCounts->sum()];
        foreach ($rawCounts as $k => $v) {
            $counts[$k] = (int) $v;
        }

        $requests = $base()
            ->with([
                'category:id,name_ar,color,icon',
                'product:id,name_ar',
                'assignee:id,full_name',
            ])
            ->when($status && $status !== 'all', fn ($b) => $b->where('status', $status))
            ->when($q !== '', fn ($b) => $b->where(fn ($w) => $w
                ->where('title', 'like', "%{$q}%")
                ->orWhere('request_number', 'like', "%{$q}%")))
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($r) => [
                'id' => $r->id,
                'request_number' => $r->request_number,
                'title' => $r->title,
                'status' => $r->status?->value ?? $r->status,
                'priority' => $r->priority?->value ?? $r->priority,
                'updated_at' => $r->updated_at,
                'created_at' => $r->created_at,
                'category' => $r->category ? ['name_ar' => $r->category->name_ar, 'color' => $r->category->color] : null,
                'product' => $r->product ? ['name_ar' => $r->product->name_ar] : null,
                'assignee' => $isStaff && $r->assignee ? ['full_name' => $r->assignee->full_name] : null,
                // Staff see real SLA due; customers get a soft service status only.
                'due_at' => $isStaff ? $r->due_at : null,
                'service_status' => $isStaff ? null : $this->serviceStatus($r),
            ]);

        return Inertia::render('Requests/Index', [
            'requests' => $requests,
            'counts' => $counts,
            'filters' => ['status' => $status ?: 'all', 'q' => $q],
            'isStaff' => $isStaff,
        ]);
    }

    /** New-request form data. */
    public function create(Request $httpRequest)
    {
        $user = $httpRequest->user();
        $isStaff = $user->isStaff();

        $categories = Category::query()
            ->where('active', true)
            ->where('is_suggestion', false)
            ->orderBy('sort_order')
            ->get(['id', 'key', 'name_ar', 'description_ar', 'color', 'icon', 'default_priority', 'target_team']);

        $catIds = $categories->pluck('id');

        $subCats = DB::table('request_sub_categories')
            ->whereIn('category_id', $catIds)
            ->where('active', true)
            ->orderBy('sort_order')
            ->get(['id', 'category_id', 'name_ar'])
            ->groupBy('category_id');

        // Dynamic intake fields per category.
        $fields = FieldDefinition::query()
            ->whereIn('category_id', $catIds)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get(['id', 'category_id', 'field_key', 'field_type', 'label', 'help_text', 'options', 'required', 'visible_to_customer'])
            ->groupBy('category_id');

        $categoriesOut = $categories->map(fn ($c) => [
            'id' => $c->id,
            'name_ar' => $c->name_ar,
            'description_ar' => $c->description_ar,
            'color' => $c->color,
            'icon' => $c->icon,
            'default_priority' => $c->default_priority?->value ?? $c->default_priority,
            'sub_categories' => ($subCats[$c->id] ?? collect())->map(fn ($s) => [
                'id' => $s->id, 'name_ar' => $s->name_ar,
            ])->values(),
            'fields' => ($fields[$c->id] ?? collect())
                // Customers only ever see customer-visible fields.
                ->filter(fn ($f) => $isStaff || $f->visible_to_customer)
                ->map(fn ($f) => [
                    'id' => $f->id,
                    'field_key' => $f->field_key,
                    'field_type' => $f->field_type,
                    'label' => $f->label,
                    'help_text' => $f->help_text,
                    'options' => $f->options,
                    'required' => (bool) $f->required,
                ])->values(),
        ]);

        $products = Product::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name_ar', 'type']);

        $customers = [];
        if ($isStaff) {
            $customers = DB::table('profiles')
                ->join('user_roles', 'user_roles.user_id', '=', 'profiles.id')
                ->where('user_roles.role', 'customer')
                ->orderBy('profiles.full_name')
                ->limit(500)
                ->distinct()
                ->get(['profiles.id', 'profiles.full_name', 'profiles.email']);
        }

        return Inertia::render('Requests/New', [
            'categories' => $categoriesOut,
            'products' => $products,
            'customers' => $customers,
            'isStaff' => $isStaff,
        ]);
    }

    /** Create a request. */
    public function store(Request $httpRequest)
    {
        $user = $httpRequest->user();
        $isStaff = $user->isStaff();

        $data = $httpRequest->validate([
            'category_id' => ['required', 'string', 'exists:categories,id'],
            'sub_category_id' => ['nullable', 'string', 'exists:request_sub_categories,id'],
            'product_id' => ['nullable', 'string', 'exists:products,id'],
            'title' => ['required', 'string', 'min:5', 'max:200'],
            'description' => ['required', 'string', 'min:10'],
            'customer_id' => [$isStaff ? 'required' : 'nullable', 'string', 'exists:profiles,id'],
            'fields' => ['nullable', 'array'],
        ]);

        $category = Category::findOrFail($data['category_id']);
        $customerId = $isStaff ? $data['customer_id'] : $user->id;

        // Validate required dynamic fields for this category.
        $definitions = FieldDefinition::query()
            ->where('category_id', $category->id)
            ->where('is_active', true)
            ->when(! $isStaff, fn ($b) => $b->where('visible_to_customer', true))
            ->get(['id', 'label', 'required']);
        $values = $data['fields'] ?? [];
        foreach ($definitions as $def) {
            if ($def->required) {
                $v = $values[$def->id] ?? null;
                if ($v === null || $v === '' || $v === []) {
                    return back()->withErrors(["fields.{$def->id}" => "الحقل \"{$def->label}\" مطلوب"])->withInput();
                }
            }
        }

        $number = $this->generateRequestNumber($category);

        $request = DB::transaction(function () use ($category, $customerId, $data, $isStaff, $user, $number, $definitions, $values) {
            $req = CrmRequest::create([
                'request_number' => $number,
                'title' => $data['title'],
                'description' => $data['description'],
                'category_id' => $category->id,
                'sub_category_id' => $data['sub_category_id'] ?? null,
                'product_id' => $data['product_id'] ?? null,
                'customer_id' => $customerId,
                'status' => 'new',
                'priority' => $category->default_priority?->value ?? 'medium',
                'channel' => 'portal',
                'source' => $isStaff ? 'staff_on_behalf' : 'portal',
                'assigned_team' => $category->target_team?->value,
                'progress' => 0,
            ]);

            // Persist dynamic field values.
            foreach ($definitions as $def) {
                if (! array_key_exists($def->id, $values)) {
                    continue;
                }
                RequestFieldValue::create([
                    'request_id' => $req->id,
                    'field_id' => $def->id,
                    'value' => $values[$def->id],
                    'updated_by' => $user->id,
                ]);
            }

            $this->log($req->id, $user->id, 'created', null, 'new',
                $isStaff ? 'أُنشئ الطلب نيابةً عن العميل بواسطة الموظف' : 'تم إنشاء الطلب');

            return $req;
        });

        return redirect()
            ->route('requests.show', $request->id)
            ->with('success', "تم إنشاء الطلب {$request->request_number} بنجاح");
    }

    /** Full request detail workspace. */
    public function show(Request $httpRequest, CrmRequest $request)
    {
        $user = $httpRequest->user();
        $isStaff = $user->isStaff();
        $this->authorizeView($user, $request, $isStaff);

        $request->load([
            'category:id,name_ar,color,icon',
            'subCategory:id,name_ar',
            'product:id,name_ar,type',
            'customer:id,full_name,email,phone',
            'assignee:id,full_name',
        ]);

        $canViewInternal = $isStaff && $user->hasCapability('comments.view_internal');

        $comments = RequestComment::query()
            ->where('request_id', $request->id)
            ->whereNull('deleted_at')
            ->when(! $canViewInternal, fn ($b) => $b->where('is_internal', false))
            ->orderBy('created_at') // newest last
            ->get(['id', 'user_id', 'body', 'is_internal', 'author_name', 'created_at']);

        $authorIds = $comments->pluck('user_id')->filter()->unique();
        $authorNames = $authorIds->isEmpty()
            ? collect()
            : DB::table('profiles')->whereIn('id', $authorIds)->pluck('full_name', 'id');

        $commentsOut = $comments->map(fn ($c) => [
            'id' => $c->id,
            'body' => $c->body,
            'is_internal' => (bool) $c->is_internal,
            'author_name' => $c->author_name ?: ($authorNames[$c->user_id] ?? 'مستخدم'),
            'created_at' => $c->created_at,
        ]);

        $activity = RequestActivityLog::query()
            ->where('request_id', $request->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'user_id', 'action', 'from_value', 'to_value', 'notes', 'created_at']);

        $attachments = $request->attachments()
            ->orderByDesc('created_at')
            ->get(['id', 'file_name', 'file_url', 'file_size', 'mime_type', 'created_at']);

        $rating = RequestRating::query()
            ->where('request_id', $request->id)
            ->first(['id', 'stars', 'dissatisfaction_reasons', 'notes', 'created_at']);

        $status = $request->status?->value ?? $request->status;
        $isOwner = $request->customer_id === $user->id;

        // Dynamic field values with their definition labels.
        $fieldValues = DB::table('request_field_values as v')
            ->join('field_definitions as d', 'd.id', '=', 'v.field_id')
            ->where('v.request_id', $request->id)
            ->when(! $isStaff, fn ($b) => $b->where('d.visible_to_customer', true))
            ->orderBy('d.display_order')
            ->get(['d.label', 'd.field_type', 'v.value']);

        // Stage workflow ledger (staff only).
        $stages = $isStaff ? DB::table('request_stage_sla_log')
            ->where('request_id', $request->id)
            ->orderBy('stage_index')
            ->get(['stage_index', 'stage_name', 'started_at', 'due_at', 'ended_at', 'breached', 'final_minutes'])
            : collect();

        // Tasks + checklist (staff only).
        $tasks = collect();
        if ($isStaff) {
            $taskRows = DB::table('request_tasks')->where('request_id', $request->id)
                ->orderBy('position')->orderBy('created_at')
                ->get(['id', 'title', 'description', 'status', 'priority', 'progress_pct', 'due_at', 'assigned_to']);
            $checklist = DB::table('request_task_checklist')
                ->whereIn('task_id', $taskRows->pluck('id'))
                ->orderBy('position')
                ->get(['id', 'task_id', 'label', 'is_done'])
                ->groupBy('task_id');
            $tasks = $taskRows->map(fn ($t) => [
                'id' => $t->id, 'title' => $t->title, 'description' => $t->description,
                'status' => $t->status, 'priority' => $t->priority, 'progress_pct' => (int) $t->progress_pct,
                'due_at' => $t->due_at,
                'checklist' => ($checklist[$t->id] ?? collect())->map(fn ($c) => [
                    'id' => $c->id, 'label' => $c->label, 'is_done' => (bool) $c->is_done,
                ])->values(),
            ]);
        }

        // Reassignment candidates (staff for this category's team).
        $staffCandidates = [];
        if ($isStaff && ($user->hasCapability('request.assign') || $user->hasCapability('request.reassign'))) {
            $staffCandidates = DB::table('profiles')
                ->join('user_roles', 'user_roles.user_id', '=', 'profiles.id')
                ->where('user_roles.role', '!=', 'customer')
                ->where('profiles.suspended', false)
                ->orderBy('profiles.full_name')
                ->distinct()
                ->limit(200)
                ->get(['profiles.id', 'profiles.full_name']);
        }

        // SLA summary (staff): remaining minutes, breach/at-risk tier.
        $sla = null;
        if ($isStaff && $request->due_at && ! in_array($status, self::TERMINAL, true)) {
            $due = \Illuminate\Support\Carbon::parse($request->due_at);
            $created = \Illuminate\Support\Carbon::parse($request->created_at);
            $remaining = now()->diffInMinutes($due, false);
            $paused = (bool) $request->sla_paused_at;
            $tier = 'ok';
            if (! $paused) {
                if ($remaining < 0) {
                    $tier = 'breach';
                } else {
                    $window = max(1, $created->diffInMinutes($due));
                    $pct = 1 - ($remaining / $window);
                    $tier = $pct >= 0.9 ? 'critical' : ($pct >= 0.75 ? 'warn' : 'ok');
                }
            }
            $sla = [
                'due_at' => $request->due_at,
                'response_due_at' => $request->response_due_at,
                'first_response_at' => $request->first_response_at,
                'remaining_minutes' => $remaining,
                'paused' => $paused,
                'tier' => $tier,
            ];
        }

        return Inertia::render('Requests/Show', [
            'request' => [
                'id' => $request->id,
                'request_number' => $request->request_number,
                'title' => $request->title,
                'description' => $request->description,
                'status' => $status,
                'priority' => $request->priority?->value ?? $request->priority,
                'progress' => (int) ($request->progress ?? 0),
                'created_at' => $request->created_at,
                'updated_at' => $request->updated_at,
                'closed_at' => $request->closed_at,
                'due_at' => $isStaff ? $request->due_at : null,
                'service_status' => $isStaff ? null : $this->serviceStatus($request),
                'category' => $request->category ? ['name_ar' => $request->category->name_ar, 'color' => $request->category->color] : null,
                'sub_category' => $request->subCategory ? ['name_ar' => $request->subCategory->name_ar] : null,
                'product' => $request->product ? ['name_ar' => $request->product->name_ar] : null,
                'customer' => $isStaff && $request->customer ? [
                    'full_name' => $request->customer->full_name,
                    'email' => $request->customer->email,
                    'phone' => $request->customer->phone,
                ] : null,
                'assignee' => $request->assignee ? ['id' => $request->assignee->id, 'full_name' => $request->assignee->full_name] : null,
                'assigned_team' => $request->assigned_team?->value ?? $request->assigned_team,
                'attachments_count' => $attachments->count(),
                'reopened_count' => (int) ($request->reopened_count ?? 0),
                'escalation_level' => (int) ($request->escalation_level ?? 0),
                'escalated_at' => $request->escalated_at,
                'current_stage_index' => $request->current_stage_index,
                'current_stage_name' => $request->current_stage_name,
                'returned_to_customer_at' => $request->returned_to_customer_at,
                'return_reason' => $request->return_reason,
                'external_wait_party' => $request->external_wait_party,
                'closure_reason_public' => $request->closure_reason_public,
                'closure_reason_code' => $request->closure_reason_code,
            ],
            'fieldValues' => $fieldValues,
            'stages' => $stages,
            'tasks' => $tasks,
            'staffCandidates' => $staffCandidates,
            'sla' => $sla,
            'comments' => $commentsOut,
            'activity' => $activity,
            'attachments' => $attachments,
            'rating' => $rating,
            'isStaff' => $isStaff,
            'isOwner' => $isOwner,
            'can' => [
                'close' => $isStaff && $user->hasCapability('request.close'),
                'reopen' => ($isStaff && $user->hasCapability('request.reopen')) || ($isOwner && in_array($status, ['closed', 'completed'], true)),
                'assign' => $isStaff && ($user->hasCapability('request.assign') || $user->hasCapability('request.reassign')),
                'update_status' => $isStaff && $user->hasCapability('request.update_status'),
                'change_priority' => $isStaff && $user->hasCapability('request.change_priority'),
                'comment_internal' => $isStaff && $user->hasCapability('request.internal_comment'),
                'escalate' => $isStaff && $user->hasCapability('request.escalate'),
                'return_to_customer' => $isStaff && $user->hasCapability('request.return_to_customer'),
                'manage_tasks' => $isStaff,
                'transition_stage' => $isStaff && $user->hasCapability('request.update_status'),
                'rate' => $isOwner && ! $isStaff && in_array($status, ['completed', 'closed'], true),
                'verify' => $isOwner && ! $isStaff && in_array($status, ['awaiting_customer', 'in_progress', 'completed'], true),
            ],
        ]);
    }

    /** Staff: change status / priority / assignment (capability-gated). */
    public function update(Request $httpRequest, CrmRequest $request)
    {
        $user = $httpRequest->user();
        if (! $user->isStaff()) {
            abort(403);
        }

        $data = $httpRequest->validate([
            'status' => ['nullable', 'string', 'in:new,under_review,awaiting_customer,in_progress,awaiting_internal,escalated,completed,closed,rejected,reopened,cancelled'],
            'priority' => ['nullable', 'string', 'in:urgent,high,medium,low'],
            'assigned_to' => ['nullable', 'string', 'exists:profiles,id'],
        ]);

        $changes = [];

        if (! empty($data['status']) && $data['status'] !== ($request->status?->value ?? $request->status)) {
            if (! $user->hasCapability('request.update_status')) {
                abort(403, 'لا تملك صلاحية تغيير الحالة.');
            }
            $from = $request->status?->value ?? $request->status;
            $request->status = $data['status'];
            $changes[] = ['status', $from, $data['status']];
        }

        if (! empty($data['priority']) && $data['priority'] !== ($request->priority?->value ?? $request->priority)) {
            if (! $user->hasCapability('request.change_priority')) {
                abort(403, 'لا تملك صلاحية تغيير الأولوية.');
            }
            $from = $request->priority?->value ?? $request->priority;
            $request->priority = $data['priority'];
            $changes[] = ['priority', $from, $data['priority']];
        }

        if (array_key_exists('assigned_to', $data) && $data['assigned_to'] !== $request->assigned_to) {
            if (! $user->hasCapability('request.assign') && ! $user->hasCapability('request.reassign')) {
                abort(403, 'لا تملك صلاحية الإسناد.');
            }
            $from = $request->assigned_to;
            $request->assigned_to = $data['assigned_to'];
            $changes[] = ['assigned_to', $from, $data['assigned_to']];
        }

        if ($changes) {
            $request->save();
            foreach ($changes as [$field, $from, $to]) {
                $this->log($request->id, $user->id, "changed_{$field}", $from, $to);
            }
        }

        return back()->with('success', 'تم تحديث الطلب');
    }

    /** Add a comment. */
    public function comment(Request $httpRequest, CrmRequest $request)
    {
        $user = $httpRequest->user();
        $isStaff = $user->isStaff();
        $this->authorizeView($user, $request, $isStaff);

        $data = $httpRequest->validate([
            'body' => ['required', 'string', 'min:1', 'max:5000'],
            'is_internal' => ['nullable', 'boolean'],
        ]);

        $internal = ($data['is_internal'] ?? false)
            && $isStaff
            && $user->hasCapability('request.internal_comment');

        RequestComment::create([
            'request_id' => $request->id,
            'user_id' => $user->id,
            'body' => $data['body'],
            'is_internal' => $internal,
            'author_name' => $user->profile?->full_name ?? $user->name,
        ]);

        $this->log($request->id, $user->id, $internal ? 'internal_comment' : 'comment', null, null);

        return back()->with('success', 'تمت إضافة التعليق');
    }

    /** Close the request (route gated by cap:request.close). */
    public function close(Request $httpRequest, CrmRequest $request)
    {
        $user = $httpRequest->user();

        $data = $httpRequest->validate([
            'closure_reason_code' => ['nullable', 'string', 'max:100'],
            'closure_reason_public' => ['nullable', 'string', 'max:2000'],
        ]);

        $from = $request->status?->value ?? $request->status;

        $request->fill([
            'status' => 'closed',
            'progress' => 100,
            'closed_by' => $user->id,
            'closed_at' => now(),
            'closure_reason_code' => $data['closure_reason_code'] ?? null,
            'closure_reason_public' => $data['closure_reason_public'] ?? null,
        ]);
        if (! $request->first_closed_at) {
            $request->first_closed_at = now();
            $request->first_closed_by = $user->id;
        }
        $request->save();

        $this->log($request->id, $user->id, 'closed', $from, 'closed', $data['closure_reason_public'] ?? null);

        return back()->with('success', 'تم إغلاق الطلب');
    }

    /** Reopen a closed request. */
    public function reopen(Request $httpRequest, CrmRequest $request)
    {
        $user = $httpRequest->user();
        $isStaff = $user->isStaff();
        $this->authorizeView($user, $request, $isStaff);

        // Staff need the reopen capability; the owner may reopen their own ticket.
        if ($isStaff && ! $user->hasCapability('request.reopen')) {
            abort(403, 'لا تملك صلاحية إعادة الفتح.');
        }

        $data = $httpRequest->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $from = $request->status?->value ?? $request->status;

        $request->fill([
            'status' => 'reopened',
            'reopened_count' => (int) ($request->reopened_count ?? 0) + 1,
            'last_reopened_at' => now(),
            'last_reopen_reason' => $data['reason'] ?? null,
        ]);
        $request->save();

        $this->log($request->id, $user->id, 'reopened', $from, 'reopened', $data['reason'] ?? null);

        return back()->with('success', 'تمت إعادة فتح الطلب');
    }

    /** Customer rating (one per request). */
    public function rate(Request $httpRequest, CrmRequest $request)
    {
        $user = $httpRequest->user();

        if ($request->customer_id !== $user->id) {
            abort(403, 'يمكن لصاحب الطلب فقط تقييمه.');
        }

        $data = $httpRequest->validate([
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'dissatisfaction_reasons' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (RequestRating::where('request_id', $request->id)->exists()) {
            return back()->with('error', 'تم تقييم هذا الطلب مسبقاً');
        }

        RequestRating::create([
            'request_id' => $request->id,
            'customer_id' => $user->id,
            'stars' => $data['stars'],
            'dissatisfaction_reasons' => $data['dissatisfaction_reasons'] ?? null,
            'notes' => $data['notes'] ?? null,
            'needs_supervisor_review' => $data['stars'] <= 2,
        ]);

        $this->log($request->id, $user->id, 'rated', null, (string) $data['stars']);

        return back()->with('success', 'شكراً لتقييمك');
    }

    /** Assign the request to the current staff member. */
    public function assignSelf(Request $httpRequest, CrmRequest $request)
    {
        $user = $httpRequest->user();
        if (! $user->isStaff() || ! ($user->hasCapability('request.assign') || $user->hasCapability('request.reassign'))) {
            abort(403);
        }
        $from = $request->assigned_to;
        $request->assigned_to = $user->id;
        $request->save();
        $this->log($request->id, $user->id, 'assigned_self', $from, $user->id);

        return back()->with('success', 'تم إسناد الطلب إليك');
    }

    /** Escalate the request one level. */
    public function escalate(Request $httpRequest, CrmRequest $request)
    {
        $user = $httpRequest->user();
        if (! $user->isStaff() || ! $user->hasCapability('request.escalate')) {
            abort(403, 'لا تملك صلاحية التصعيد.');
        }
        $data = $httpRequest->validate(['reason' => ['nullable', 'string', 'max:2000']]);
        $bump = ['low' => 'medium', 'medium' => 'high', 'high' => 'urgent', 'urgent' => 'urgent'];
        $prio = $request->priority?->value ?? $request->priority;

        $request->fill([
            'status' => 'escalated',
            'escalation_level' => (int) ($request->escalation_level ?? 0) + 1,
            'escalated_at' => now(),
            'priority' => $bump[$prio] ?? $prio,
        ])->save();

        $this->log($request->id, $user->id, 'escalated', $prio, $request->priority?->value ?? $request->priority, $data['reason'] ?? null);
        app(\App\Services\NotificationService::class)->notifyRoles(
            ['system_admin', 'support_supervisor'], 'تم تصعيد طلب',
            "تم تصعيد الطلب {$request->request_number}.", $request->id, "/requests/{$request->id}"
        );

        return back()->with('success', 'تم تصعيد الطلب');
    }

    /** Return the request to the customer for more information. */
    public function returnToCustomer(Request $httpRequest, CrmRequest $request)
    {
        $user = $httpRequest->user();
        if (! $user->isStaff() || ! $user->hasCapability('request.return_to_customer')) {
            abort(403);
        }
        $data = $httpRequest->validate(['reason' => ['required', 'string', 'max:2000']]);

        $category = $request->category_id ? DB::table('categories')->where('id', $request->category_id)->first() : null;
        $days = $category->auto_close_days ?? 5;

        $request->fill([
            'status' => 'awaiting_customer',
            'returned_to_customer_at' => now(),
            'return_reason' => $data['reason'],
            'auto_close_due_at' => now()->addDays($days),
        ])->save();

        $this->log($request->id, $user->id, 'returned_to_customer', null, null, $data['reason']);
        app(\App\Services\NotificationService::class)->notify(
            $request->customer_id, 'طلبك بحاجة إلى ردّك',
            "الطلب {$request->request_number} بانتظار ردّك.", $request->id, "/requests/{$request->id}", 'request'
        );

        return back()->with('success', 'تمت إعادة الطلب للعميل');
    }

    /** Resume processing (from an awaiting state back to in_progress). */
    public function resume(Request $httpRequest, CrmRequest $request)
    {
        $user = $httpRequest->user();
        if (! $user->isStaff()) {
            abort(403);
        }
        $from = $request->status?->value ?? $request->status;
        $request->fill([
            'status' => 'in_progress',
            'auto_close_due_at' => null,
            'returned_to_customer_at' => null,
        ])->save();
        $this->log($request->id, $user->id, 'resumed', $from, 'in_progress');

        return back()->with('success', 'تم استئناف المعالجة');
    }

    /** Advance or roll back the current workflow stage. */
    public function transitionStage(Request $httpRequest, CrmRequest $request)
    {
        $user = $httpRequest->user();
        if (! $user->isStaff() || ! $user->hasCapability('request.update_status')) {
            abort(403);
        }
        $data = $httpRequest->validate([
            'stage_index' => ['required', 'integer', 'min:0', 'max:20'],
            'stage_name' => ['required', 'string', 'max:100'],
        ]);

        // Close the currently open stage log row.
        DB::table('request_stage_sla_log')
            ->where('request_id', $request->id)->whereNull('ended_at')
            ->update(['ended_at' => now(), 'updated_at' => now()]);

        // Resolve stage SLA and compute the due date via business hours.
        $cfg = DB::table('stage_sla_config')
            ->where('category_id', $request->category_id)
            ->where('stage_index', $data['stage_index'])
            ->first();
        $baseMinutes = (int) ($cfg->base_minutes ?? 480);
        $respectBh = (bool) ($cfg->respect_business_hours ?? true);
        $due = (new \App\Services\Sla\SlaService)->computeDeadline(now(), $baseMinutes, $respectBh);

        DB::table('request_stage_sla_log')->insert([
            'id' => (string) Str::uuid(),
            'request_id' => $request->id,
            'stage_index' => $data['stage_index'],
            'stage_name' => $data['stage_name'],
            'priority' => $request->priority?->value ?? $request->priority,
            'multiplier' => 1,
            'base_minutes' => $baseMinutes,
            'final_minutes' => $baseMinutes,
            'started_at' => now(),
            'due_at' => $due,
            'paused_total_seconds' => 0,
            'escalation_level' => 0,
            'created_at' => now(),
        ]);

        $request->fill([
            'current_stage_index' => $data['stage_index'],
            'current_stage_name' => $data['stage_name'],
            'current_stage_started_at' => now(),
            'current_stage_due_at' => $due,
            'stage_alert_75_sent_at' => null,
            'stage_alert_90_sent_at' => null,
        ])->save();

        $this->log($request->id, $user->id, 'stage_transition', null, $data['stage_name']);

        return back()->with('success', "تم الانتقال إلى مرحلة: {$data['stage_name']}");
    }

    /** Create a task on the request. */
    public function addTask(Request $httpRequest, CrmRequest $request)
    {
        $user = $httpRequest->user();
        if (! $user->isStaff()) {
            abort(403);
        }
        $data = $httpRequest->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'priority' => ['nullable', 'string', 'in:low,medium,high,urgent'],
        ]);
        DB::table('request_tasks')->insert([
            'id' => (string) Str::uuid(),
            'request_id' => $request->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'task_type' => 'main',
            'status' => 'todo',
            'priority' => $data['priority'] ?? 'medium',
            'sla_impact' => 'none',
            'sla_extension_applied' => false,
            'sla_extension_minutes' => 0,
            'created_by' => $user->id,
            'position' => (int) DB::table('request_tasks')->where('request_id', $request->id)->count(),
            'progress_pct' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->log($request->id, $user->id, 'task_created', null, $data['title']);

        return back()->with('success', 'تمت إضافة المهمة');
    }

    /** Change a task's status. */
    public function updateTask(Request $httpRequest, CrmRequest $request, string $task)
    {
        $user = $httpRequest->user();
        if (! $user->isStaff()) {
            abort(403);
        }
        $data = $httpRequest->validate(['status' => ['required', 'string', 'in:todo,in_progress,blocked,done,cancelled']]);
        DB::table('request_tasks')->where('id', $task)->where('request_id', $request->id)->update([
            'status' => $data['status'],
            'progress_pct' => $data['status'] === 'done' ? 100 : DB::raw('progress_pct'),
            'completed_at' => $data['status'] === 'done' ? now() : null,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'تم تحديث المهمة');
    }

    /** Toggle a task checklist item. */
    public function toggleChecklist(Request $httpRequest, CrmRequest $request, string $item)
    {
        $user = $httpRequest->user();
        if (! $user->isStaff()) {
            abort(403);
        }
        $row = DB::table('request_task_checklist')->where('id', $item)->first();
        if ($row) {
            DB::table('request_task_checklist')->where('id', $item)->update([
                'is_done' => ! $row->is_done,
                'done_at' => ! $row->is_done ? now() : null,
                'done_by' => ! $row->is_done ? $user->id : null,
                'updated_at' => now(),
            ]);
        }

        return back();
    }

    /** Customer confirms whether their issue was resolved (verification card). */
    public function verifySolution(Request $httpRequest, CrmRequest $request)
    {
        $user = $httpRequest->user();
        if ($request->customer_id !== $user->id) {
            abort(403);
        }
        $data = $httpRequest->validate([
            'resolved' => ['required', 'boolean'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['resolved']) {
            $request->fill(['status' => 'completed', 'progress' => 100])->save();
            $this->log($request->id, $user->id, 'customer_confirmed_resolved', null, null, $data['note'] ?? null);
            $msg = 'شكراً لتأكيدك، تم إغلاق المعالجة';
        } else {
            $request->fill(['status' => 'in_progress'])->save();
            $this->log($request->id, $user->id, 'customer_reported_unresolved', null, null, $data['note'] ?? null);
            $msg = 'تم إبلاغ الفريق بأن المشكلة لم تُحل بعد';
        }

        return back()->with('success', $msg);
    }

    // ----------------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------------

    /** Customers may only view their own requests; staff may view all. */
    private function authorizeView($user, CrmRequest $request, bool $isStaff): void
    {
        if (! $isStaff && $request->customer_id !== $user->id) {
            abort(403, 'لا تملك صلاحية الوصول لهذا الطلب.');
        }
    }

    /** Soft, SLA-free service status shown to customers. */
    private function serviceStatus(CrmRequest $r): string
    {
        $status = $r->status?->value ?? $r->status;
        if (in_array($status, self::TERMINAL, true)) {
            return 'done';
        }
        if ($status === 'awaiting_customer') {
            return 'awaiting_customer';
        }
        if ($r->due_at) {
            $diffH = now()->diffInHours($r->due_at, false);
            if ($diffH < 0) {
                return 'overdue';
            }
            if ($diffH <= 24) {
                return 'due_soon';
            }
        }

        return 'on_track';
    }

    /**
     * Generate REQ-YY-<catcode>-<seq> using a per-category counter that starts
     * at 1001. Uses a locked read to stay safe under concurrency.
     */
    private function generateRequestNumber(Category $category): string
    {
        $code = $category->code_prefix
            ?: Str::upper(collect(explode('_', (string) $category->key))
                ->map(fn ($p) => Str::substr($p, 0, 1))
                ->join(''));
        $code = $code ?: 'GEN';
        $yy = now()->format('y');

        $seq = DB::transaction(function () use ($category) {
            $row = DB::table('request_number_seqs')
                ->where('category_id', $category->id)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                $next = 1001;
                DB::table('request_number_seqs')->insert([
                    'category_id' => $category->id,
                    'last_value' => $next,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $next = ((int) $row->last_value) >= 1001 ? ((int) $row->last_value) + 1 : 1001;
                DB::table('request_number_seqs')
                    ->where('category_id', $category->id)
                    ->update(['last_value' => $next, 'updated_at' => now()]);
            }

            return $next;
        });

        return "REQ-{$yy}-{$code}-{$seq}";
    }

    /** Insert an activity-log row. */
    private function log(string $requestId, ?string $userId, string $action, $from = null, $to = null, ?string $notes = null): void
    {
        RequestActivityLog::create([
            'request_id' => $requestId,
            'user_id' => $userId,
            'action' => $action,
            'from_value' => $from,
            'to_value' => $to,
            'notes' => $notes,
            'created_at' => now(),
        ]);
    }
}
