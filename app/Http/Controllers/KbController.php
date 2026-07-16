<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\KbArticle;
use App\Models\KbArticleFeedback;
use App\Models\KbArticleRating;
use App\Models\KbGapReport;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class KbController extends Controller
{
    /** Article list with filters, categories sidebar, KPIs and capability flags. */
    public function index(Request $request)
    {
        $user = $request->user();

        $status = $request->query('status', 'approved');
        $type = $request->query('type', 'all');
        $categoryId = $request->query('category', 'all');
        $search = trim((string) $request->query('q', ''));

        $articles = KbArticle::query()
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($type !== 'all', fn ($q) => $q->where('type', $type))
            ->when($categoryId !== 'all', fn ($q) => $q->where('category_id', $categoryId))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $like = '%'.$search.'%';
                    $sub->where('title', 'like', $like)
                        ->orWhere('summary', 'like', $like)
                        ->orWhere('body', 'like', $like);
                });
            })
            ->orderByDesc('updated_at')
            ->limit(80)
            ->get([
                'id', 'title', 'summary', 'type', 'status', 'complexity', 'category_id',
                'product_id', 'is_general', 'avg_rating', 'views_count', 'helpful_count',
                'not_helpful_count', 'rating_count', 'updated_at',
            ]);

        $categories = Category::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name_ar', 'key', 'icon_name', 'color']);

        // Article counts per category (approved only) for the sidebar.
        $countsByCategory = DB::table('kb_articles')
            ->select('category_id', DB::raw('count(*) as total'))
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $kpis = [
            'total' => (int) DB::table('kb_articles')->count(),
            'approved' => (int) DB::table('kb_articles')->where('status', 'approved')->count(),
            'in_review' => (int) DB::table('kb_articles')->where('status', 'in_review')->count(),
            'draft' => (int) DB::table('kb_articles')->where('status', 'draft')->count(),
            'avg_rating' => round((float) DB::table('kb_articles')->where('rating_count', '>', 0)->avg('avg_rating'), 1),
        ];

        return Inertia::render('Kb/Index', [
            'articles' => $articles,
            'categories' => $categories,
            'countsByCategory' => $countsByCategory,
            'kpis' => $kpis,
            'filters' => [
                'status' => $status,
                'type' => $type,
                'category' => $categoryId,
                'q' => $search,
            ],
            'can' => [
                'author' => $user->hasCapability('kb.author'),
                'approve' => $user->hasCapability('kb.approve'),
                'manage' => $user->hasCapability('kb.manage'),
                'rate' => $user->hasCapability('kb.rate') || $user->isStaff(),
            ],
        ]);
    }

    /** Article view + increment views + current user rating. */
    public function show(Request $request, KbArticle $article)
    {
        $user = $request->user();

        // Increment views without touching updated_at.
        DB::table('kb_articles')->where('id', $article->id)->increment('views_count');

        $category = $article->category_id
            ? Category::find($article->category_id, ['id', 'name_ar'])
            : null;
        $product = $article->product_id
            ? Product::find($article->product_id, ['id', 'name_ar'])
            : null;

        $myRating = KbArticleRating::where('article_id', $article->id)
            ->where('user_id', $user->id)
            ->first(['rating', 'comment']);

        $myFeedback = KbArticleFeedback::where('article_id', $article->id)
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->first(['was_helpful']);

        $versions = DB::table('kb_article_versions')
            ->where('article_id', $article->id)
            ->orderByDesc('version_number')
            ->limit(20)
            ->get(['id', 'version_number', 'title', 'summary', 'change_note', 'created_at']);

        // Author / approver display names (identity: users.id == profiles.id).
        $personIds = array_values(array_filter([$article->author_id, $article->approver_id]));
        $people = $personIds
            ? DB::table('profiles')->whereIn('id', $personIds)->pluck('full_name', 'id')
            : collect();

        return Inertia::render('Kb/Show', [
            'article' => $article->fresh(),
            'category' => $category,
            'product' => $product,
            'myRating' => $myRating,
            'myFeedback' => $myFeedback,
            'versions' => $versions,
            'authorName' => $article->author_id ? ($people[$article->author_id] ?? null) : null,
            'approverName' => $article->approver_id ? ($people[$article->approver_id] ?? null) : null,
            'currentUserId' => $user->id,
            'can' => [
                'author' => $user->hasCapability('kb.author'),
                'approve' => $user->hasCapability('kb.approve'),
                'manage' => $user->hasCapability('kb.manage'),
                'rate' => $user->hasCapability('kb.rate') || $user->isStaff(),
            ],
        ]);
    }

    /** New article form. */
    public function create(Request $request)
    {
        return Inertia::render('Kb/New', $this->formOptions());
    }

    /** Persist a new article (draft or straight to review). */
    public function store(Request $request)
    {
        $data = $this->validateArticle($request);
        $data['status'] = $request->input('status') === 'in_review' ? 'in_review' : 'draft';
        $data['author_id'] = $request->user()->id;
        $data['slug'] = $this->makeSlug($data['title']);
        $data['current_version'] = 1;

        $article = KbArticle::create($data);

        $message = $data['status'] === 'in_review'
            ? 'تم إنشاء المقال وإرساله للمراجعة'
            : 'تم إنشاء المقال كمسودة';

        return redirect()
            ->route('kb.show', $article->id)
            ->with('success', $message);
    }

    /** Edit article form (author/manage). */
    public function edit(Request $request, KbArticle $article)
    {
        return Inertia::render('Kb/New', array_merge($this->formOptions(), [
            'article' => $article,
        ]));
    }

    /** Update article (author/manage). */
    public function update(Request $request, KbArticle $article)
    {
        $data = $this->validateArticle($request);
        if (empty($article->slug)) {
            $data['slug'] = $this->makeSlug($data['title']);
        }
        $article->update($data);

        return redirect()
            ->route('kb.show', $article->id)
            ->with('success', 'تم تحديث المقال');
    }

    /** KB admin — pending approvals + knowledge gaps. */
    public function manage(Request $request)
    {
        $user = $request->user();

        $pending = KbArticle::query()
            ->where('status', 'in_review')
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get(['id', 'title', 'summary', 'type', 'complexity', 'author_id', 'updated_at']);

        $gaps = KbGapReport::query()
            ->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")
            ->orderByDesc('occurrences')
            ->orderByDesc('created_at')
            ->limit(80)
            ->get();

        // Most-used approved articles (views + reuse).
        $topUsed = KbArticle::query()
            ->where('status', 'approved')
            ->orderByDesc('views_count')
            ->limit(8)
            ->get(['id', 'title', 'views_count', 'insert_solution_count', 'sent_to_customer_count']);

        // Lowest-rated approved articles that have been rated.
        $lowRated = KbArticle::query()
            ->where('status', 'approved')
            ->where('rating_count', '>', 0)
            ->orderBy('avg_rating')
            ->orderByDesc('not_helpful_count')
            ->limit(8)
            ->get(['id', 'title', 'avg_rating', 'rating_count', 'not_helpful_count']);

        $kpis = [
            'pending' => (int) DB::table('kb_articles')->where('status', 'in_review')->count(),
            'gaps_open' => (int) DB::table('kb_gap_reports')->where('status', 'open')->count(),
            'approved' => (int) DB::table('kb_articles')->where('status', 'approved')->count(),
            'total_views' => (int) DB::table('kb_articles')->sum('views_count'),
        ];

        return Inertia::render('Kb/Manage', [
            'pending' => $pending,
            'gaps' => $gaps,
            'topUsed' => $topUsed,
            'lowRated' => $lowRated,
            'kpis' => $kpis,
            'can' => [
                'approve' => $user->hasCapability('kb.approve'),
                'manage' => $user->hasCapability('kb.manage'),
            ],
        ]);
    }

    /** Approve / archive / send-back an article (kb.approve). */
    public function status(Request $request, KbArticle $article)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,in_review,approved,archived',
        ]);

        $status = $validated['status'];
        $patch = ['status' => $status];

        if ($status === 'approved') {
            $patch['approver_id'] = $request->user()->id;
            $patch['approved_at'] = now();
        } elseif ($status === 'archived') {
            $patch['archived_at'] = now();
        }

        $article->update($patch);

        return back()->with('success', 'تم تحديث حالة المقال');
    }

    /** Upsert the current user's article rating and recompute the aggregate. */
    public function rate(Request $request, KbArticle $article)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
        ]);

        $user = $request->user();

        KbArticleRating::updateOrCreate(
            ['article_id' => $article->id, 'user_id' => $user->id],
            ['rating' => $validated['rating'], 'comment' => $validated['comment'] ?? null]
        );

        $agg = DB::table('kb_article_ratings')
            ->where('article_id', $article->id)
            ->selectRaw('count(*) as c, avg(rating) as a')
            ->first();

        $article->update([
            'rating_count' => (int) $agg->c,
            'avg_rating' => round((float) $agg->a, 2),
        ]);

        return back()->with('success', 'تم حفظ التقييم');
    }

    /** Record helpful / not-helpful feedback. */
    public function feedback(Request $request, KbArticle $article)
    {
        $validated = $request->validate([
            'was_helpful' => 'required|boolean',
            'note' => 'nullable|string|max:2000',
        ]);

        KbArticleFeedback::create([
            'article_id' => $article->id,
            'user_id' => $request->user()->id,
            'was_helpful' => $validated['was_helpful'],
            'note' => $validated['note'] ?? null,
        ]);

        if ($validated['was_helpful']) {
            DB::table('kb_articles')->where('id', $article->id)->increment('helpful_count');
        } else {
            DB::table('kb_articles')->where('id', $article->id)->increment('not_helpful_count');
        }

        return back()->with('success', 'شكراً لملاحظتك');
    }

    /** Store a knowledge-gap report from the manage console (kb.manage). */
    public function storeGap(Request $request)
    {
        $validated = $request->validate([
            'topic' => 'required|string|max:255',
            'keywords' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:2000',
        ]);

        $keywords = collect(explode(',', (string) ($validated['keywords'] ?? '')))
            ->map(fn ($k) => trim($k))
            ->filter()
            ->values()
            ->all();

        KbGapReport::create([
            'topic' => $validated['topic'],
            'keywords' => $keywords,
            'occurrences' => 1,
            'related_request_ids' => [],
            'status' => 'open',
            'notes' => $validated['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'تم تسجيل الفجوة');
    }

    /** Mark a gap addressed / dismissed (kb.manage). */
    public function updateGap(Request $request, KbGapReport $gap)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,addressed,dismissed',
        ]);

        $gap->update(['status' => $validated['status']]);

        return back()->with('success', 'تم تحديث حالة الفجوة');
    }

    /** Shared select options for the article form. */
    private function formOptions(): array
    {
        return [
            'categories' => Category::where('active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name_ar']),
            'products' => Product::where('active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name_ar']),
        ];
    }

    /** Validate + normalise article input. */
    private function validateArticle(Request $request): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string|max:1000',
            'body' => 'required|string',
            'type' => 'required|in:faq,sop,known_issue,resolution,macro,policy,user_guide',
            'complexity' => 'required|in:beginner,intermediate,advanced',
            'category_id' => 'nullable|string|exists:categories,id',
            'product_id' => 'nullable|string|exists:products,id',
            'is_general' => 'boolean',
            'keywords' => 'nullable|string|max:1000',
            'prerequisites' => 'nullable|string|max:2000',
            'warnings' => 'nullable|string|max:2000',
        ]);

        $validated['keywords'] = collect(explode(',', (string) ($validated['keywords'] ?? '')))
            ->map(fn ($k) => trim($k))
            ->filter()
            ->values()
            ->all();

        $validated['is_general'] = (bool) ($validated['is_general'] ?? false);
        if ($validated['is_general']) {
            $validated['product_id'] = null;
        }

        return $validated;
    }

    private function makeSlug(string $title): string
    {
        $slug = Str::slug($title);
        if ($slug === '') {
            // Arabic titles: keep letters/numbers, dash the rest.
            $slug = trim(preg_replace('/[^\p{L}\p{N}]+/u', '-', $title), '-');
        }

        return Str::limit($slug, 80, '');
    }
}
