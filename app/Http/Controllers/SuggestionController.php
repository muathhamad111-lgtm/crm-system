<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Request as CrmRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SuggestionController extends Controller
{
    /** IDs of categories flagged as suggestion boards. */
    private function suggestionCategoryIds(): array
    {
        return Category::query()
            ->where('is_suggestion', true)
            ->pluck('id')
            ->all();
    }

    /** Base query scoped to suggestion categories. */
    private function baseQuery()
    {
        return CrmRequest::query()->whereIn('category_id', $this->suggestionCategoryIds());
    }

    /** Generate a per-category sequential SUG-YY-###### number. */
    private function nextRequestNumber(string $categoryId): string
    {
        return DB::transaction(function () use ($categoryId) {
            $seq = DB::table('request_number_seqs')
                ->where('category_id', $categoryId)
                ->lockForUpdate()
                ->first();

            $next = (int) ($seq->last_value ?? 0) + 1;

            if ($seq) {
                DB::table('request_number_seqs')
                    ->where('category_id', $categoryId)
                    ->update(['last_value' => $next]);
            } else {
                DB::table('request_number_seqs')->insert([
                    'category_id' => $categoryId,
                    'last_value' => $next,
                ]);
            }

            return sprintf('SUG-%s-%06d', date('y'), $next);
        });
    }

    /** Attach vote / comment / rating engagement metrics to a set of rows. */
    private function engagementFor(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $votes = DB::table('suggestion_votes')
            ->select('request_id', 'vote', DB::raw('count(*) as c'))
            ->whereIn('request_id', $ids)
            ->groupBy('request_id', 'vote')
            ->get();

        $comments = DB::table('suggestion_comments')
            ->select('request_id', DB::raw('count(*) as c'))
            ->whereIn('request_id', $ids)
            ->whereNull('deleted_at')
            ->groupBy('request_id')
            ->pluck('c', 'request_id');

        $ratings = DB::table('suggestion_ratings')
            ->select('request_id', DB::raw('avg(stars) as avg_stars'), DB::raw('count(*) as c'))
            ->whereIn('request_id', $ids)
            ->groupBy('request_id')
            ->get()
            ->keyBy('request_id');

        $map = [];
        foreach ($ids as $id) {
            $map[$id] = [
                'votes_count' => 0,
                'support_count' => 0,
                'comments_count' => (int) ($comments[$id] ?? 0),
                'avg_stars' => round((float) ($ratings[$id]->avg_stars ?? 0), 1),
                'ratings_count' => (int) ($ratings[$id]->c ?? 0),
            ];
        }
        foreach ($votes as $v) {
            if (! isset($map[$v->request_id])) {
                continue;
            }
            $map[$v->request_id]['votes_count'] += (int) $v->c;
            if ($v->vote === 'strong_support') {
                $map[$v->request_id]['support_count'] += 2 * (int) $v->c;
            } elseif ($v->vote === 'support') {
                $map[$v->request_id]['support_count'] += (int) $v->c;
            }
        }

        return $map;
    }

    /** Public idea board — published suggestions (staff see all). */
    public function board(Request $request)
    {
        $user = $request->user();
        $isStaff = $user->isStaff();

        $q = $this->baseQuery();
        if (! $isStaff) {
            $q->where('published_to_customers', true);
        }

        $rows = $q->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->get(['id', 'request_number', 'title', 'description', 'idea_stage', 'comments_locked', 'published_to_customers', 'published_at', 'created_at']);

        $engagement = $this->engagementFor($rows->pluck('id')->all());

        $suggestions = $rows->map(fn ($r) => array_merge([
            'id' => $r->id,
            'request_number' => $r->request_number,
            'title' => $r->title,
            'description' => $r->description,
            'idea_stage' => $r->idea_stage?->value ?? $r->idea_stage,
            'comments_locked' => (bool) $r->comments_locked,
            'published' => (bool) $r->published_to_customers,
            'published_at' => $r->published_at,
            'created_at' => $r->created_at,
        ], $engagement[$r->id] ?? []))->all();

        $totalRatings = array_sum(array_column($suggestions, 'ratings_count'));
        $weighted = array_sum(array_map(fn ($s) => $s['avg_stars'] * $s['ratings_count'], $suggestions));

        return Inertia::render('Suggestions/Board', [
            'suggestions' => $suggestions,
            'stats' => [
                'total' => count($suggestions),
                'avg' => $totalRatings ? round($weighted / $totalRatings, 1) : 0,
                'comments' => array_sum(array_column($suggestions, 'comments_count')),
            ],
            'isStaff' => $isStaff,
        ]);
    }

    /** Suggestion submission form. */
    public function create(Request $request)
    {
        $categories = Category::query()
            ->where('active', true)
            ->where('is_suggestion', true)
            ->orderBy('sort_order')
            ->get(['id', 'name_ar', 'description_ar', 'color', 'default_priority', 'target_team']);

        $products = Product::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name_ar', 'type']);

        return Inertia::render('Suggestions/New', [
            'categories' => $categories,
            'products' => $products,
        ]);
    }

    /** Persist a new suggestion as a request row in a suggestion category. */
    public function store(Request $request)
    {
        $catIds = $this->suggestionCategoryIds();

        $data = $request->validate([
            'category_id' => ['required', 'string', 'in:'.implode(',', $catIds ?: ['none'])],
            'sub_category_id' => ['nullable', 'string'],
            'product_id' => ['nullable', 'string'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'min:10'],
        ]);

        $user = $request->user();
        $category = Category::find($data['category_id']);

        $suggestion = new CrmRequest();
        $suggestion->id = (string) Str::uuid();
        $suggestion->request_number = $this->nextRequestNumber($data['category_id']);
        $suggestion->title = $data['title'];
        $suggestion->description = $data['description'];
        $suggestion->category_id = $data['category_id'];
        $suggestion->sub_category_id = $data['sub_category_id'] ?: null;
        $suggestion->product_id = $data['product_id'] ?: null;
        $suggestion->customer_id = $user->id;
        $suggestion->priority = $category?->default_priority?->value ?? 'medium';
        $suggestion->assigned_team = $category?->target_team?->value;
        $suggestion->status = 'new';
        $suggestion->channel = 'portal';
        $suggestion->source = 'in_app';
        $suggestion->idea_stage = 'received';
        $suggestion->decision = 'pending';
        $suggestion->save();

        return redirect()
            ->route('suggestions.show', $suggestion->id)
            ->with('success', 'تم إرسال مقترحك بنجاح — رقم المقترح '.$suggestion->request_number);
    }

    /** Current customer's own suggestions. */
    public function mine(Request $request)
    {
        $user = $request->user();

        $rows = $this->baseQuery()
            ->where('customer_id', $user->id)
            ->orderByDesc('created_at')
            ->get(['id', 'request_number', 'title', 'description', 'status', 'idea_stage', 'decision', 'published_to_customers', 'created_at']);

        $engagement = $this->engagementFor($rows->pluck('id')->all());

        $suggestions = $rows->map(fn ($r) => array_merge([
            'id' => $r->id,
            'request_number' => $r->request_number,
            'title' => $r->title,
            'description' => $r->description,
            'status' => $r->status?->value ?? $r->status,
            'idea_stage' => $r->idea_stage?->value ?? $r->idea_stage,
            'decision' => $r->decision?->value ?? $r->decision,
            'published' => (bool) $r->published_to_customers,
            'created_at' => $r->created_at,
        ], $engagement[$r->id] ?? []))->all();

        return Inertia::render('Suggestions/Mine', [
            'suggestions' => $suggestions,
        ]);
    }

    /** Staff triage list with stage / product / search filters. */
    public function inbox(Request $request)
    {
        $filters = [
            'stage' => $request->query('stage', 'all'),
            'product' => $request->query('product', 'all'),
            'q' => $request->query('q', ''),
        ];

        $q = $this->baseQuery();

        if ($filters['stage'] !== 'all') {
            $q->where('idea_stage', $filters['stage']);
        }
        if ($filters['product'] !== 'all') {
            $q->where('product_id', $filters['product']);
        }
        if ($filters['q'] !== '') {
            $term = '%'.$filters['q'].'%';
            $q->where(fn ($w) => $w->where('title', 'like', $term)
                ->orWhere('request_number', 'like', $term)
                ->orWhere('description', 'like', $term));
        }

        $rows = $q->orderByDesc('created_at')
            ->get(['id', 'request_number', 'title', 'description', 'status', 'priority', 'idea_stage', 'decision', 'published_to_customers', 'customer_id', 'product_id', 'created_at']);

        $engagement = $this->engagementFor($rows->pluck('id')->all());

        $customerNames = DB::table('profiles')
            ->whereIn('id', $rows->pluck('customer_id')->filter()->unique()->all())
            ->pluck('full_name', 'id');

        $suggestions = $rows->map(fn ($r) => array_merge([
            'id' => $r->id,
            'request_number' => $r->request_number,
            'title' => $r->title,
            'description' => $r->description,
            'status' => $r->status?->value ?? $r->status,
            'priority' => $r->priority?->value ?? $r->priority,
            'idea_stage' => $r->idea_stage?->value ?? $r->idea_stage,
            'decision' => $r->decision?->value ?? $r->decision,
            'published' => (bool) $r->published_to_customers,
            'customer_name' => $customerNames[$r->customer_id] ?? null,
            'created_at' => $r->created_at,
        ], $engagement[$r->id] ?? []))->all();

        // Stage counts (unfiltered by stage, keeps product/search scope).
        $countScope = $this->baseQuery();
        if ($filters['product'] !== 'all') {
            $countScope->where('product_id', $filters['product']);
        }
        $stageCounts = $countScope
            ->select('idea_stage', DB::raw('count(*) as c'))
            ->groupBy('idea_stage')
            ->pluck('c', 'idea_stage');

        return Inertia::render('Suggestions/Inbox', [
            'suggestions' => $suggestions,
            'filters' => $filters,
            'products' => Product::where('active', true)->orderBy('sort_order')->get(['id', 'name_ar']),
            'stats' => [
                'total' => count($suggestions),
                'received' => (int) ($stageCounts['received'] ?? 0),
                'under_review' => (int) ($stageCounts['under_review'] ?? 0),
                'accepted' => (int) ($stageCounts['accepted'] ?? 0),
                'in_progress' => (int) ($stageCounts['in_progress'] ?? 0),
                'implemented' => (int) ($stageCounts['implemented'] ?? 0),
                'rejected' => (int) ($stageCounts['rejected'] ?? 0),
            ],
        ]);
    }

    /** Dual-mode detail: customer engages; staff also classify / score / publish. */
    public function show(Request $request, string $id)
    {
        $user = $request->user();
        $isStaff = $user->isStaff();

        $suggestion = $this->baseQuery()->findOrFail($id);

        // Customers may only view published suggestions or their own.
        if (! $isStaff && ! $suggestion->published_to_customers && $suggestion->customer_id !== $user->id) {
            abort(403, 'هذا المقترح غير متاح.');
        }

        // Comments (flat, with author names) — deleted shown as tombstones.
        $comments = DB::table('suggestion_comments as sc')
            ->leftJoin('profiles as p', 'p.id', '=', 'sc.user_id')
            ->where('sc.request_id', $id)
            ->orderBy('sc.created_at')
            ->get(['sc.id', 'sc.user_id', 'sc.body', 'sc.kind', 'sc.parent_id', 'sc.deleted_at', 'sc.created_at', 'p.full_name as author_name']);

        // Votes.
        $voteRows = DB::table('suggestion_votes')
            ->select('vote', DB::raw('count(*) as c'))
            ->where('request_id', $id)
            ->groupBy('vote')
            ->pluck('c', 'vote');
        $myVote = DB::table('suggestion_votes')
            ->where('request_id', $id)->where('user_id', $user->id)->value('vote');

        // Ratings.
        $ratingAgg = DB::table('suggestion_ratings')
            ->select(DB::raw('avg(stars) as avg_stars'), DB::raw('count(*) as c'))
            ->where('request_id', $id)->first();
        $myStars = DB::table('suggestion_ratings')
            ->where('request_id', $id)->where('customer_id', $user->id)->value('stars');

        $customer = $suggestion->customer_id
            ? DB::table('profiles')->where('id', $suggestion->customer_id)
                ->first(['full_name', 'email', 'phone', 'business_field', 'region', 'city'])
            : null;

        // RICE score = reach * value(impact) * confidence / effort.
        $reach = $suggestion->reach;
        $impact = $suggestion->value_score;
        $confidence = $suggestion->confidence;
        $effort = $suggestion->effort;
        $rice = ($reach && $impact && $confidence && $effort)
            ? round(($reach * $impact * $confidence) / $effort, 1)
            : null;

        $decisionsLog = $isStaff
            ? DB::table('suggestion_decisions_log as dl')
                ->leftJoin('profiles as p', 'p.id', '=', 'dl.actor_id')
                ->where('dl.request_id', $id)
                ->orderByDesc('dl.created_at')
                ->get(['dl.id', 'dl.action', 'dl.from_value', 'dl.to_value', 'dl.notes', 'dl.created_at', 'p.full_name as actor_name'])
            : [];

        return Inertia::render('Suggestions/Show', [
            'suggestion' => [
                'id' => $suggestion->id,
                'request_number' => $suggestion->request_number,
                'title' => $suggestion->title,
                'description' => $suggestion->description,
                'status' => $suggestion->status?->value ?? $suggestion->status,
                'idea_stage' => $suggestion->idea_stage?->value ?? $suggestion->idea_stage,
                'decision' => $suggestion->decision?->value ?? $suggestion->decision,
                'decision_reason' => $suggestion->decision_reason,
                'comments_locked' => (bool) $suggestion->comments_locked,
                'published' => (bool) $suggestion->published_to_customers,
                'published_at' => $suggestion->published_at,
                'target_release' => $suggestion->target_release,
                'reach' => $reach,
                'value_score' => $impact,
                'confidence' => $confidence,
                'effort' => $effort,
                'rice_score' => $rice,
                'created_at' => $suggestion->created_at,
                'updated_at' => $suggestion->updated_at,
            ],
            'customer' => $customer,
            'comments' => $comments,
            'votes' => [
                'support' => (int) ($voteRows['support'] ?? 0),
                'strong_support' => (int) ($voteRows['strong_support'] ?? 0),
                'against' => (int) ($voteRows['against'] ?? 0),
                'mine' => $myVote,
            ],
            'ratings' => [
                'avg' => round((float) ($ratingAgg->avg_stars ?? 0), 1),
                'count' => (int) ($ratingAgg->c ?? 0),
                'mine' => $myStars ? (int) $myStars : null,
            ],
            'decisionsLog' => $decisionsLog,
            'isStaff' => $isStaff,
            'canModerate' => $isStaff,
        ]);
    }

    /** Upsert the user's vote. */
    public function vote(Request $request, string $id)
    {
        $data = $request->validate([
            'vote' => ['required', 'in:support,strong_support,against'],
        ]);
        $suggestion = $this->baseQuery()->findOrFail($id);
        $user = $request->user();

        DB::table('suggestion_votes')->updateOrInsert(
            ['request_id' => $suggestion->id, 'user_id' => $user->id],
            ['vote' => $data['vote'], 'updated_at' => now(), 'created_at' => now()],
        );

        return back()->with('success', 'تم تسجيل تصويتك');
    }

    /** Add a comment (kind defaults to general). */
    public function comment(Request $request, string $id)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'kind' => ['nullable', 'in:use_case,feedback,challenge,complementary_idea,general'],
            'parent_id' => ['nullable', 'string'],
        ]);
        $suggestion = $this->baseQuery()->findOrFail($id);

        if ($suggestion->comments_locked) {
            return back()->with('error', 'التعليقات مقفلة على هذا المقترح.');
        }

        DB::table('suggestion_comments')->insert([
            'id' => (string) Str::uuid(),
            'request_id' => $suggestion->id,
            'user_id' => $request->user()->id,
            'body' => $data['body'],
            'kind' => $data['kind'] ?? 'general',
            'parent_id' => $data['parent_id'] ?: null,
            'created_at' => now(),
        ]);

        return back()->with('success', 'تم نشر تعليقك');
    }

    /** Upsert the user's star rating. */
    public function rate(Request $request, string $id)
    {
        $data = $request->validate([
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
        ]);
        $suggestion = $this->baseQuery()->findOrFail($id);
        $user = $request->user();

        DB::table('suggestion_ratings')->updateOrInsert(
            ['request_id' => $suggestion->id, 'customer_id' => $user->id],
            ['stars' => $data['stars'], 'updated_at' => now(), 'created_at' => now()],
        );

        return back()->with('success', 'شكراً على تقييمك');
    }

    /** Staff: advance stage / record decision and RICE scoring; log the change. */
    public function advance(Request $request, string $id)
    {
        $data = $request->validate([
            'idea_stage' => ['nullable', 'in:received,under_review,accepted,rejected,scheduled,in_progress,implemented,archived,under_study,shortlisted,published,voting,approved,on_roadmap,postponed'],
            'decision' => ['nullable', 'in:pending,accepted,rejected,postponed,merged'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'reach' => ['nullable', 'numeric'],
            'value_score' => ['nullable', 'numeric'],
            'confidence' => ['nullable', 'numeric'],
            'effort' => ['nullable', 'numeric'],
            'target_release' => ['nullable', 'string', 'max:120'],
        ]);
        $suggestion = $this->baseQuery()->findOrFail($id);
        $user = $request->user();

        $logs = [];

        if (! empty($data['idea_stage']) && $data['idea_stage'] !== ($suggestion->idea_stage?->value ?? $suggestion->idea_stage)) {
            $logs[] = [
                'action' => 'stage_change',
                'from_value' => $suggestion->idea_stage?->value ?? $suggestion->idea_stage,
                'to_value' => $data['idea_stage'],
            ];
            $suggestion->idea_stage = $data['idea_stage'];
        }

        if (! empty($data['decision']) && $data['decision'] !== ($suggestion->decision?->value ?? $suggestion->decision)) {
            $logs[] = [
                'action' => 'decision',
                'from_value' => $suggestion->decision?->value ?? $suggestion->decision,
                'to_value' => $data['decision'],
            ];
            $suggestion->decision = $data['decision'];
            $suggestion->decision_at = now();
            $suggestion->decision_by = $user->id;
            if (array_key_exists('reason', $data)) {
                $suggestion->decision_reason = $data['reason'];
            }
        }

        foreach (['reach', 'value_score', 'confidence', 'effort', 'target_release'] as $f) {
            if (array_key_exists($f, $data)) {
                $suggestion->{$f} = $data[$f] === '' ? null : $data[$f];
            }
        }

        $suggestion->save();

        foreach ($logs as $log) {
            DB::table('suggestion_decisions_log')->insert([
                'id' => (string) Str::uuid(),
                'request_id' => $suggestion->id,
                'actor_id' => $user->id,
                'action' => $log['action'],
                'from_value' => $log['from_value'],
                'to_value' => $log['to_value'],
                'notes' => $data['reason'] ?? null,
                'created_at' => now(),
            ]);
        }

        return back()->with('success', 'تم تحديث المقترح');
    }

    /**
     * Staff: RICE scoring. Stores reach / impact (value_score) / confidence /
     * effort; the score is derived as (reach × impact × confidence) / effort.
     */
    public function score(Request $request, string $id)
    {
        $data = $request->validate([
            'reach' => ['required', 'numeric', 'min:0'],
            'value_score' => ['required', 'numeric', 'min:0'],       // impact factor
            'confidence' => ['required', 'numeric', 'min:0', 'max:1'], // 0..1
            'effort' => ['required', 'numeric', 'min:0.1'],           // person-weeks
        ]);

        $suggestion = $this->baseQuery()->findOrFail($id);
        $user = $request->user();

        $suggestion->fill([
            'reach' => $data['reach'],
            'value_score' => $data['value_score'],
            'confidence' => $data['confidence'],
            'effort' => $data['effort'],
        ])->save();

        $rice = round(($data['reach'] * $data['value_score'] * $data['confidence']) / $data['effort'], 1);

        DB::table('suggestion_decisions_log')->insert([
            'id' => (string) Str::uuid(),
            'request_id' => $suggestion->id,
            'actor_id' => $user->id,
            'action' => 'rice_scored',
            'to_value' => (string) $rice,
            'notes' => "R={$data['reach']} · I={$data['value_score']} · C={$data['confidence']} · E={$data['effort']}",
            'created_at' => now(),
        ]);

        return back()->with('success', "تم احتساب درجة RICE: {$rice}");
    }

    /** Staff: publish (or unpublish) a suggestion to customers. */
    public function publish(Request $request, string $id)
    {
        $data = $request->validate([
            'publish' => ['required', 'boolean'],
        ]);
        $suggestion = $this->baseQuery()->findOrFail($id);
        $user = $request->user();

        $suggestion->published_to_customers = $data['publish'];
        if ($data['publish']) {
            $suggestion->published_at = now();
            $suggestion->published_by = $user->id;
        }
        $suggestion->save();

        DB::table('suggestion_decisions_log')->insert([
            'id' => (string) Str::uuid(),
            'request_id' => $suggestion->id,
            'actor_id' => $user->id,
            'action' => $data['publish'] ? 'published' : 'unpublished',
            'from_value' => null,
            'to_value' => $data['publish'] ? 'true' : 'false',
            'notes' => null,
            'created_at' => now(),
        ]);

        return back()->with('success', $data['publish'] ? 'تم نشر المقترح للعملاء' : 'تم إلغاء النشر');
    }
}
