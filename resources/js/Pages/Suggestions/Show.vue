<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import Select from '@/Components/ui/Select.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import Separator from '@/Components/ui/Separator.vue';
import Badge from '@/Components/ui/Badge.vue';
import {
    ArrowRight, ThumbsUp, MessageSquare, Star, Calculator, Eye, EyeOff, Lock,
    Users, Award, Calendar, Clock, User as UserIcon, Mail, Phone, MapPin, Building2,
    Package, Tag, Sparkles, History, Paperclip, GitMerge, Copy, Send,
} from 'lucide-vue-next';
import { timeAgoAr, fmtFullDateTimeAr } from '@/lib/date';
import { IDEA_STAGE, REQUEST_PRIORITY, statusLabel } from '@/lib/labels';

const props = defineProps({
    suggestion: { type: Object, required: true },
    metrics: { type: Object, default: () => ({}) },
    attachments: { type: Array, default: () => [] },
    duplicates: { type: Array, default: () => [] },
    customer: { type: Object, default: null },
    comments: { type: Array, default: () => [] },
    votes: { type: Object, default: () => ({ support: 0, strong_support: 0, against: 0, mine: null }) },
    ratings: { type: Object, default: () => ({ avg: 0, count: 0, mine: null }) },
    decisionsLog: { type: Array, default: () => [] },
    isStaff: { type: Boolean, default: false },
    canModerate: { type: Boolean, default: false },
});
const s = props.suggestion;

const STAGE_ORDER = ['received', 'under_review', 'accepted', 'scheduled', 'in_progress', 'implemented'];
const DECISION = {
    pending: { label: 'بانتظار القرار', tone: 'muted' },
    accepted: { label: 'مقبول', tone: 'success' },
    rejected: { label: 'مرفوض', tone: 'destructive' },
    postponed: { label: 'مؤجل', tone: 'warning' },
    merged: { label: 'مدموج', tone: 'accent' },
};
const KIND = {
    general: 'عام', use_case: 'حالة استخدام', feedback: 'ملاحظة',
    challenge: 'تحدٍّ', complementary_idea: 'فكرة مكملة',
};
const ACTION_LABELS = {
    stage_change: 'تغيير المرحلة', decision: 'قرار', rice_scored: 'تحديث تقييم RICE',
    published: 'نشر للعملاء', unpublished: 'إلغاء النشر',
};

const stageIdx = computed(() => STAGE_ORDER.indexOf(s.idea_stage));
const rejected = computed(() => s.idea_stage === 'rejected' || s.idea_stage === 'archived');

// Comment threads (root + children).
const roots = computed(() => props.comments.filter((c) => !c.parent_id));
const childrenOf = (id) => props.comments.filter((c) => c.parent_id === id);

// --- Customer engagement ---
function vote(v) {
    router.post(route('suggestions.vote', s.id), { vote: v }, { preserveScroll: true });
}
function rate(n) {
    router.post(route('suggestions.rate', s.id), { stars: n }, { preserveScroll: true });
}
const commentForm = useForm({ body: '', kind: 'general', parent_id: null });
function addComment() {
    commentForm.post(route('suggestions.comment', s.id), {
        preserveScroll: true,
        onSuccess: () => commentForm.reset('body'),
    });
}
const replyTo = ref(null);
const replyForm = useForm({ body: '', kind: 'general', parent_id: null });
function sendReply(parentId) {
    replyForm.parent_id = parentId;
    replyForm.post(route('suggestions.comment', s.id), {
        preserveScroll: true,
        onSuccess: () => { replyForm.reset('body'); replyTo.value = null; },
    });
}
const rateHover = ref(0);

// --- Staff management ---
const stageForm = useForm({
    idea_stage: s.idea_stage,
    decision: s.decision,
    reason: s.decision_reason ?? '',
});
function saveStage() { stageForm.post(route('suggestions.advance', s.id), { preserveScroll: true }); }
function decide(decision) {
    stageForm.decision = decision;
    stageForm.post(route('suggestions.advance', s.id), { preserveScroll: true });
}

const REASON_TEMPLATES = [
    'متوافق مع خارطة المنتج — سيتم الجدولة في الإصدار القادم.',
    'مكرر لمقترح سابق — تم الدمج مع المقترح ذي الأولوية الأعلى.',
    'خارج نطاق المنتج حالياً — سيُعاد النظر لاحقاً.',
    'قيمة عالية وجهد منخفض — موافقة فورية.',
];

const riceForm = useForm({
    reach: s.reach ?? 100,
    value_score: s.value_score ?? 2,
    confidence: s.confidence ?? 0.8,
    effort: s.effort ?? 2,
});
const liveRice = computed(() => {
    const { reach, value_score, confidence, effort } = riceForm;
    if (!reach || !value_score || !confidence || !effort) return '—';
    return Math.round((reach * value_score * confidence / effort) * 10) / 10;
});
function saveScore() { riceForm.post(route('suggestions.score', s.id), { preserveScroll: true }); }

function publish() {
    router.post(route('suggestions.publish', s.id), { publish: !s.published }, { preserveScroll: true });
}

const stars = [1, 2, 3, 4, 5];
</script>

<template>
    <Head :title="s.request_number ?? 'مقترح'" />
    <AppShell>
        <div class="space-y-4">
            <!-- Back -->
            <Link :href="isStaff ? route('suggestions.inbox') : route('suggestions.board')"
                class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                <ArrowRight class="size-4" /> {{ isStaff ? 'العودة لصندوق المقترحات' : 'العودة للمقترحات' }}
            </Link>

            <!-- Hero card -->
            <Card class="overflow-hidden">
                <div class="relative bg-gradient-to-l from-primary/15 via-primary/5 to-transparent p-5 sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-6">
                        <div class="min-w-0 flex-1 space-y-2.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <Badge variant="outline" class="font-mono text-[10px]">{{ s.request_number }}</Badge>
                                <Badge :variant="statusLabel(IDEA_STAGE, s.idea_stage).tone" class="text-[10px]">
                                    {{ statusLabel(IDEA_STAGE, s.idea_stage).label }}
                                </Badge>
                                <Badge :variant="DECISION[s.decision]?.tone ?? 'muted'" class="text-[10px]">
                                    {{ DECISION[s.decision]?.label ?? s.decision }}
                                </Badge>
                                <Badge v-if="s.published" variant="success" class="gap-1 text-[10px]"><Eye class="size-3" /> منشور</Badge>
                                <Badge v-else variant="outline" class="gap-1 text-[10px]"><EyeOff class="size-3" /> غير منشور</Badge>
                                <Badge v-if="s.comments_locked" variant="muted" class="gap-1 text-[10px]"><Lock class="size-3" /> تعليقات مقفلة</Badge>
                            </div>
                            <h1 class="text-2xl font-bold leading-tight tracking-tight text-foreground sm:text-3xl">{{ s.title }}</h1>
                            <div class="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                                <span v-if="customer?.full_name" class="inline-flex items-center gap-1"><UserIcon class="size-3.5" /> {{ customer.full_name }}</span>
                                <span v-if="s.product" class="inline-flex items-center gap-1"><Package class="size-3.5" /> {{ s.product }}</span>
                                <span class="inline-flex items-center gap-1" :title="fmtFullDateTimeAr(s.created_at)"><Calendar class="size-3.5" /> أُنشئ {{ timeAgoAr(s.created_at) }}</span>
                                <span v-if="s.updated_at" class="inline-flex items-center gap-1"><Clock class="size-3.5" /> آخر تحديث {{ timeAgoAr(s.updated_at) }}</span>
                            </div>
                        </div>
                        <!-- Metric tiles -->
                        <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                            <div class="min-w-[84px] rounded-xl border border-border bg-card/80 px-3 py-2.5 text-center backdrop-blur">
                                <div class="flex items-center justify-center gap-1 text-[11px] text-muted-foreground"><Star class="size-3" /> تقييم</div>
                                <div class="text-lg font-bold tabular-nums">{{ (metrics.avg_stars ?? 0).toFixed(1) }}</div>
                                <div class="text-[10px] text-muted-foreground">{{ metrics.ratings_count ?? 0 }} صوت</div>
                            </div>
                            <div class="min-w-[84px] rounded-xl border border-border bg-card/80 px-3 py-2.5 text-center backdrop-blur">
                                <div class="flex items-center justify-center gap-1 text-[11px] text-muted-foreground"><MessageSquare class="size-3" /> نقاش</div>
                                <div class="text-lg font-bold tabular-nums">{{ metrics.comments_count ?? 0 }}</div>
                            </div>
                            <div class="min-w-[84px] rounded-xl border border-border bg-card/80 px-3 py-2.5 text-center backdrop-blur">
                                <div class="flex items-center justify-center gap-1 text-[11px] text-muted-foreground"><Users class="size-3" /> مشاركون</div>
                                <div class="text-lg font-bold tabular-nums">{{ metrics.unique_voters ?? 0 }}</div>
                            </div>
                            <div class="min-w-[84px] rounded-xl border border-border bg-card/80 px-3 py-2.5 text-center backdrop-blur">
                                <div class="flex items-center justify-center gap-1 text-[11px] text-muted-foreground"><Award class="size-3" /> RICE</div>
                                <div class="text-lg font-bold tabular-nums">{{ metrics.rice_score ?? '—' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Staff publish / lock bar -->
                    <div v-if="isStaff" class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border bg-card/90 p-3 backdrop-blur">
                        <div class="flex items-center gap-3">
                            <div :class="['flex size-9 items-center justify-center rounded-lg', s.published ? 'bg-success/15 text-success' : 'bg-muted text-muted-foreground']">
                                <component :is="s.published ? Eye : EyeOff" class="size-4" />
                            </div>
                            <div class="text-sm">
                                <div class="font-semibold leading-tight">{{ s.published ? 'المقترح منشور للعملاء' : 'المقترح غير منشور للعملاء' }}</div>
                                <div class="text-[11px] text-muted-foreground">
                                    <template v-if="s.published">نُشر {{ s.published_at ? timeAgoAr(s.published_at) : '—' }}<template v-if="s.published_by_name"> بواسطة {{ s.published_by_name }}</template></template>
                                    <template v-else>بمجرد النشر، سيتمكّن العملاء المشتركون من التصويت والتعليق والتقييم.</template>
                                </div>
                            </div>
                        </div>
                        <Button size="sm" :variant="s.published ? 'outline' : 'default'" @click="publish">
                            <component :is="s.published ? EyeOff : Send" class="size-4" />
                            {{ s.published ? 'إلغاء النشر' : 'نشر للعملاء الآن' }}
                        </Button>
                    </div>
                </div>
            </Card>

            <!-- Two-column workspace -->
            <div class="grid gap-4 lg:grid-cols-3">
                <!-- Main column -->
                <div class="space-y-4 lg:col-span-2">
                    <!-- Description + pipeline -->
                    <Card>
                        <CardHeader><CardTitle>وصف المقترح</CardTitle></CardHeader>
                        <CardContent class="space-y-4">
                            <p class="whitespace-pre-wrap leading-relaxed text-foreground/90">{{ s.description }}</p>
                            <div class="rounded-lg border border-border bg-muted/30 p-3">
                                <div class="mb-2 text-xs font-semibold text-muted-foreground">مسار الفكرة</div>
                                <div class="flex items-center gap-1 overflow-x-auto pb-1">
                                    <template v-for="(st, i) in STAGE_ORDER" :key="st">
                                        <div :class="['shrink-0 rounded-md border px-2.5 py-1 text-[11px] font-medium',
                                            !rejected && i === stageIdx ? 'border-primary bg-primary text-primary-foreground'
                                            : !rejected && i < stageIdx ? 'border-primary/30 bg-primary/10 text-primary'
                                            : 'border-border bg-muted text-muted-foreground']">
                                            {{ statusLabel(IDEA_STAGE, st).label }}
                                        </div>
                                        <div v-if="i < STAGE_ORDER.length - 1" :class="['h-px w-4', !rejected && i < stageIdx ? 'bg-primary' : 'bg-border']"></div>
                                    </template>
                                    <Badge v-if="rejected" variant="destructive" class="mr-2">{{ statusLabel(IDEA_STAGE, s.idea_stage).label }}</Badge>
                                </div>
                                <div v-if="s.decision && s.decision !== 'pending' && s.decision_reason" class="mt-2 text-xs text-muted-foreground">
                                    <span class="font-medium text-foreground">سبب القرار:</span> {{ s.decision_reason }}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Engagement (vote / rate) -->
                    <Card>
                        <CardHeader><CardTitle class="text-base">خيارات التفاعل</CardTitle></CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <div class="text-xs font-medium text-muted-foreground">التصويت (إجمالي {{ metrics.votes_total ?? 0 }})</div>
                                <div class="flex flex-wrap gap-2">
                                    <Button size="sm" :variant="votes.mine === 'support' ? 'default' : 'outline'" @click="vote('support')">
                                        <ThumbsUp class="size-4" /> أؤيد ({{ votes.support }})
                                    </Button>
                                    <Button size="sm" :variant="votes.mine === 'strong_support' ? 'default' : 'outline'" @click="vote('strong_support')">
                                        أؤيد بشدة ({{ votes.strong_support }})
                                    </Button>
                                    <Button size="sm" :variant="votes.mine === 'against' ? 'destructive' : 'outline'" @click="vote('against')">
                                        لا أؤيد ({{ votes.against }})
                                    </Button>
                                </div>
                            </div>
                            <Separator />
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="text-xs text-muted-foreground">تقييم نجوم:</span>
                                <div class="flex items-center gap-1" @mouseleave="rateHover = 0">
                                    <button v-for="n in stars" :key="n" type="button" @click="rate(n)" @mouseenter="rateHover = n">
                                        <Star class="size-5" :class="n <= (rateHover || ratings.mine || 0) ? 'fill-warning text-warning' : 'text-muted-foreground/40'" />
                                    </button>
                                </div>
                                <span class="text-xs text-muted-foreground">المتوسط {{ (ratings.avg ?? 0).toFixed(1) }} ({{ ratings.count ?? 0 }})</span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Comments -->
                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle class="flex items-center gap-2 text-base"><MessageSquare class="size-4" /> التعليقات ({{ roots.length }})</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div v-if="s.comments_locked" class="flex items-center gap-2 rounded-lg bg-muted p-3 text-sm text-muted-foreground">
                                <Lock class="size-4" /> تم قفل التعليقات على هذا المقترح.
                            </div>
                            <div v-else class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <Select label="نوع التعليق" v-model="commentForm.kind" class="w-48">
                                        <option v-for="[k, v] in Object.entries(KIND)" :key="k" :value="k">{{ v }}</option>
                                    </Select>
                                </div>
                                <Textarea label="شارك رأيك..." v-model="commentForm.body" maxlength="2000" class="min-h-16" />
                                <div class="flex justify-end">
                                    <Button size="sm" :disabled="commentForm.processing || !commentForm.body.trim()" @click="addComment">إضافة تعليق</Button>
                                </div>
                            </div>

                            <div v-if="!roots.length" class="py-6 text-center text-sm text-muted-foreground">لا توجد تعليقات بعد.</div>
                            <div v-for="c in roots" :key="c.id" class="space-y-2">
                                <div class="rounded-lg border border-border bg-card p-3">
                                    <div class="mb-1 flex items-center justify-between gap-3">
                                        <span class="text-sm font-medium">{{ c.author_name ?? 'مستخدم' }}</span>
                                        <span class="text-[11px] text-muted-foreground">{{ timeAgoAr(c.created_at) }}</span>
                                    </div>
                                    <div v-if="c.deleted_at" class="text-sm italic text-muted-foreground">[تم حذف هذا التعليق]</div>
                                    <div v-else class="whitespace-pre-wrap text-sm">{{ c.body }}</div>
                                    <div v-if="!c.deleted_at && !s.comments_locked" class="mt-2">
                                        <Button size="sm" variant="ghost" class="h-7 text-xs" @click="replyTo = replyTo === c.id ? null : c.id">رد</Button>
                                    </div>
                                    <div v-if="replyTo === c.id" class="mt-2 space-y-2">
                                        <Textarea label="اكتب ردك..." v-model="replyForm.body" maxlength="2000" class="min-h-12" />
                                        <div class="flex items-center gap-2">
                                            <Button size="sm" :disabled="replyForm.processing || !replyForm.body.trim()" @click="sendReply(c.id)">إرسال الرد</Button>
                                            <Button size="sm" variant="ghost" @click="replyTo = null">إلغاء</Button>
                                        </div>
                                    </div>
                                </div>
                                <div v-for="k in childrenOf(c.id)" :key="k.id" class="mr-6 rounded-lg border border-border bg-muted/30 p-3">
                                    <div class="mb-1 flex items-center justify-between gap-3">
                                        <span class="text-sm font-medium">{{ k.author_name ?? 'مستخدم' }}</span>
                                        <span class="text-[11px] text-muted-foreground">{{ timeAgoAr(k.created_at) }}</span>
                                    </div>
                                    <div v-if="k.deleted_at" class="text-sm italic text-muted-foreground">[تم حذف هذا التعليق]</div>
                                    <div v-else class="whitespace-pre-wrap text-sm">{{ k.body }}</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Side column -->
                <div class="space-y-4">
                    <!-- Classification -->
                    <Card>
                        <CardHeader class="pb-2"><CardTitle class="flex items-center gap-2 text-sm"><Tag class="size-4" /> التصنيف</CardTitle></CardHeader>
                        <CardContent class="space-y-2 text-sm">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-muted-foreground">التصنيف</span>
                                <span class="inline-flex items-center gap-1.5 font-medium">
                                    <span v-if="s.category" class="size-2 rounded-full" :style="{ background: s.category_color || 'var(--muted-foreground)' }"></span>
                                    {{ s.category ?? '—' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between gap-2"><span class="text-muted-foreground">التصنيف الفرعي</span><span class="font-medium">{{ s.sub_category ?? '—' }}</span></div>
                            <div class="flex items-center justify-between gap-2"><span class="text-muted-foreground">المنتج / الخدمة</span><span class="font-medium">{{ s.product ?? '—' }}</span></div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-muted-foreground">الأولوية</span>
                                <Badge :variant="statusLabel(REQUEST_PRIORITY, s.priority).tone">{{ statusLabel(REQUEST_PRIORITY, s.priority).label }}</Badge>
                            </div>
                            <div class="flex items-center justify-between gap-2"><span class="text-muted-foreground">إصدار مستهدف</span><span class="font-medium">{{ s.target_release ?? '—' }}</span></div>
                        </CardContent>
                    </Card>

                    <!-- Customer (staff) -->
                    <Card v-if="isStaff && customer">
                        <CardHeader class="pb-2"><CardTitle class="flex items-center gap-2 text-sm"><UserIcon class="size-4" /> بيانات العميل</CardTitle></CardHeader>
                        <CardContent class="space-y-1.5 text-sm">
                            <div class="inline-flex items-center gap-1.5"><UserIcon class="size-3 text-muted-foreground" /> {{ customer.full_name ?? '—' }}</div>
                            <div class="inline-flex items-center gap-1.5"><Mail class="size-3 text-muted-foreground" /> {{ customer.email ?? '—' }}</div>
                            <div class="inline-flex items-center gap-1.5" dir="ltr"><Phone class="size-3 text-muted-foreground" /> {{ customer.phone ?? '—' }}</div>
                            <div class="inline-flex items-center gap-1.5"><MapPin class="size-3 text-muted-foreground" /> {{ customer.region ?? customer.city ?? '—' }}</div>
                            <div v-if="customer.business_field" class="inline-flex items-center gap-1.5 text-muted-foreground"><Building2 class="size-3" /> {{ customer.business_field }}</div>
                        </CardContent>
                    </Card>

                    <!-- Possible duplicates -->
                    <Card v-if="duplicates.length">
                        <CardHeader class="pb-2"><CardTitle class="flex items-center gap-2 text-sm"><Copy class="size-4" /> مقترحات مشابهة</CardTitle></CardHeader>
                        <CardContent class="space-y-2">
                            <Link v-for="d in duplicates" :key="d.id" :href="route('suggestions.show', d.id)"
                                class="flex items-center justify-between gap-2 rounded-lg border border-border bg-muted/20 p-2 text-xs transition-colors hover:border-primary/40">
                                <span class="min-w-0 flex-1 truncate">{{ d.title }}</span>
                                <Badge :variant="statusLabel(IDEA_STAGE, d.idea_stage).tone" class="shrink-0 text-[9px]">{{ statusLabel(IDEA_STAGE, d.idea_stage).label }}</Badge>
                            </Link>
                        </CardContent>
                    </Card>

                    <!-- Staff: decision + stage -->
                    <Card v-if="isStaff">
                        <CardHeader class="pb-2"><CardTitle class="flex items-center gap-2 text-sm"><Sparkles class="size-4 text-primary" /> القرار والمرحلة</CardTitle></CardHeader>
                        <CardContent class="space-y-3">
                            <div>
                                <Select label="المرحلة" v-model="stageForm.idea_stage">
                                    <option v-for="[k, v] in Object.entries(IDEA_STAGE)" :key="k" :value="k">{{ v.label }}</option>
                                </Select>
                            </div>
                            <div class="flex flex-wrap gap-1">
                                <button v-for="(t, i) in REASON_TEMPLATES" :key="i" type="button"
                                    class="rounded-md border border-dashed border-border px-2 py-1 text-[11px] text-muted-foreground transition-colors hover:border-primary hover:text-foreground"
                                    @click="stageForm.reason = t">
                                    {{ t.length > 32 ? t.slice(0, 32) + '…' : t }}
                                </button>
                            </div>
                            <Textarea label="سبب القرار / ملاحظات للفريق" v-model="stageForm.reason" maxlength="2000" class="min-h-16" />
                            <Button size="sm" class="w-full" :disabled="stageForm.processing" @click="saveStage">حفظ المرحلة</Button>
                            <Separator />
                            <div class="grid grid-cols-2 gap-2">
                                <Button size="sm" variant="success" :disabled="stageForm.processing" @click="decide('accepted')">قبول</Button>
                                <Button size="sm" variant="destructive" :disabled="stageForm.processing" @click="decide('rejected')">رفض</Button>
                                <Button size="sm" variant="outline" :disabled="stageForm.processing" @click="decide('postponed')">تأجيل</Button>
                                <Button size="sm" variant="outline" :disabled="stageForm.processing" @click="decide('merged')"><GitMerge class="size-4" /> دمج</Button>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Staff: RICE -->
                    <Card v-if="isStaff">
                        <CardHeader class="pb-2"><CardTitle class="flex items-center gap-2 text-sm"><Calculator class="size-4" /> تسجيل RICE</CardTitle></CardHeader>
                        <CardContent class="space-y-3">
                            <div class="grid grid-cols-2 gap-2">
                                <div><Input label="الوصول (Reach)" type="number" min="0" v-model="riceForm.reach" /></div>
                                <div>
                                    <Select label="الأثر (Impact)" v-model.number="riceForm.value_score">
                                        <option :value="0.25">ضئيل (0.25)</option><option :value="0.5">منخفض (0.5)</option>
                                        <option :value="1">متوسط (1)</option><option :value="2">مرتفع (2)</option><option :value="3">هائل (3)</option>
                                    </Select>
                                </div>
                                <div>
                                    <Select label="الثقة (Confidence)" v-model.number="riceForm.confidence">
                                        <option :value="0.5">50%</option><option :value="0.8">80%</option><option :value="1">100%</option>
                                    </Select>
                                </div>
                                <div><Input label="الجهد (Effort)" type="number" min="0.1" step="0.5" v-model="riceForm.effort" /></div>
                            </div>
                            <div class="rounded-lg bg-primary-soft p-3 text-center">
                                <p class="text-xs text-primary/70">درجة RICE المحتسبة</p>
                                <p class="text-2xl font-bold tabular-nums text-primary">{{ liveRice }}</p>
                            </div>
                            <p v-if="riceForm.errors.confidence" class="text-xs text-destructive">{{ riceForm.errors.confidence }}</p>
                            <Button size="sm" class="w-full" :disabled="riceForm.processing" @click="saveScore">حفظ التقييم</Button>
                        </CardContent>
                    </Card>

                    <!-- Attachments -->
                    <Card v-if="attachments.length">
                        <CardHeader class="pb-2"><CardTitle class="flex items-center gap-2 text-sm"><Paperclip class="size-4" /> المرفقات ({{ attachments.length }})</CardTitle></CardHeader>
                        <CardContent class="space-y-1.5">
                            <a v-for="a in attachments" :key="a.id" :href="a.file_url" target="_blank" rel="noopener"
                                class="flex items-center gap-2 rounded-lg border border-border bg-muted/20 px-2.5 py-1.5 text-xs transition-colors hover:border-primary/40">
                                <Paperclip class="size-3 shrink-0" /><span class="truncate">{{ a.file_name }}</span>
                            </a>
                        </CardContent>
                    </Card>

                    <!-- Decisions log (staff) -->
                    <Card v-if="isStaff && decisionsLog.length">
                        <CardHeader class="pb-2"><CardTitle class="flex items-center gap-2 text-sm"><History class="size-4" /> سجل القرارات ({{ decisionsLog.length }})</CardTitle></CardHeader>
                        <CardContent>
                            <ol class="relative space-y-3 border-r-2 border-primary/20 pr-4">
                                <li v-for="l in decisionsLog" :key="l.id" class="relative">
                                    <span class="absolute -right-[22px] top-1 size-3 rounded-full border-2 border-background bg-primary"></span>
                                    <div class="rounded-lg border border-border bg-card p-2.5 text-xs">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-medium">
                                                {{ ACTION_LABELS[l.action] ?? l.action }}
                                                <template v-if="l.action === 'stage_change'">: {{ statusLabel(IDEA_STAGE, l.from_value).label }} ← {{ statusLabel(IDEA_STAGE, l.to_value).label }}</template>
                                                <template v-else-if="l.action === 'decision'">: {{ DECISION[l.to_value]?.label ?? l.to_value }}</template>
                                            </span>
                                            <span class="text-[11px] text-muted-foreground">{{ timeAgoAr(l.created_at) }}</span>
                                        </div>
                                        <div class="mt-0.5 text-muted-foreground">بواسطة: {{ l.actor_name ?? 'النظام' }}</div>
                                        <div v-if="l.notes" class="mt-1 italic text-muted-foreground">"{{ l.notes }}"</div>
                                    </div>
                                </li>
                            </ol>
                        </CardContent>
                    </Card>

                    <!-- Meta -->
                    <Card>
                        <CardContent class="space-y-2 pt-6 text-sm">
                            <div class="flex justify-between"><span class="text-muted-foreground">أُنشئ</span><span>{{ timeAgoAr(s.created_at) }}</span></div>
                            <div v-if="s.published_at" class="flex justify-between"><span class="text-muted-foreground">نُشر</span><span>{{ timeAgoAr(s.published_at) }}</span></div>
                            <div v-if="s.decision_at" class="flex justify-between"><span class="text-muted-foreground">تاريخ القرار</span><span>{{ timeAgoAr(s.decision_at) }}</span></div>
                            <div v-if="s.decision_by_name" class="flex justify-between"><span class="text-muted-foreground">القرار بواسطة</span><span>{{ s.decision_by_name }}</span></div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppShell>
</template>
