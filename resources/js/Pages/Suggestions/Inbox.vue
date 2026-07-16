<script setup>
import { ref, watch, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import Badge from '@/Components/ui/Badge.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PriorityBadge from '@/Components/PriorityBadge.vue';
import { timeAgoAr } from '@/lib/date';
import { IDEA_STAGE, statusLabel } from '@/lib/labels';
import {
    Inbox, Plus, Search, Sparkles, Loader2, Clock, AlertTriangle, CheckCircle2,
    RotateCcw, XCircle, MessageSquare, ThumbsUp, ChevronLeft, Star, Calendar,
    User as UserIcon, Package, ExternalLink,
} from 'lucide-vue-next';

const props = defineProps({
    suggestions: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    products: { type: Array, default: () => [] },
    teams: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
});

const q = ref(props.filters.q ?? '');
const customer = ref(props.filters.customer ?? '');

function apply(patch = {}) {
    router.get('/suggestions/inbox',
        { ...props.filters, q: q.value, customer: customer.value, ...patch },
        { preserveState: true, replace: true, preserveScroll: true });
}
let t;
watch([q, customer], () => { clearTimeout(t); t = setTimeout(() => apply(), 350); });

const activeStage = computed(() => props.filters.stage ?? 'all');
const overdue = computed(() => !!props.filters.overdue);
const reopened = computed(() => !!props.filters.reopened);

// KPI stat pills — stages + quick toggles.
const pills = computed(() => [
    { key: 'all', label: 'إجمالي المقترحات', value: props.stats.total ?? 0, icon: Inbox, tone: 'primary',
      active: activeStage.value === 'all' && !overdue.value && !reopened.value,
      go: () => apply({ stage: 'all', overdue: null, reopened: null }) },
    { key: 'received', label: 'مُستلَمة', value: props.stats.received ?? 0, icon: Sparkles, tone: 'info',
      active: activeStage.value === 'received', go: () => apply({ stage: 'received', overdue: null, reopened: null }) },
    { key: 'under_review', label: 'قيد الدراسة', value: props.stats.under_review ?? 0, icon: Loader2, tone: 'info',
      active: activeStage.value === 'under_review', go: () => apply({ stage: 'under_review', overdue: null, reopened: null }) },
    { key: 'accepted', label: 'مقبولة', value: props.stats.accepted ?? 0, icon: CheckCircle2, tone: 'success',
      active: activeStage.value === 'accepted', go: () => apply({ stage: 'accepted', overdue: null, reopened: null }) },
    { key: 'in_progress', label: 'قيد التنفيذ', value: props.stats.in_progress ?? 0, icon: Loader2, tone: 'accent',
      active: activeStage.value === 'in_progress', go: () => apply({ stage: 'in_progress', overdue: null, reopened: null }) },
    { key: 'implemented', label: 'مُنفّذة', value: props.stats.implemented ?? 0, icon: CheckCircle2, tone: 'success',
      active: activeStage.value === 'implemented', go: () => apply({ stage: 'implemented', overdue: null, reopened: null }) },
    { key: 'rejected', label: 'مرفوضة', value: props.stats.rejected ?? 0, icon: XCircle,
      tone: (props.stats.rejected ?? 0) > 0 ? 'destructive' : 'muted',
      active: activeStage.value === 'rejected', go: () => apply({ stage: 'rejected', overdue: null, reopened: null }) },
    { key: 'overdue', label: 'متأخرة (SLA)', value: props.stats.overdue ?? 0, icon: AlertTriangle,
      tone: (props.stats.overdue ?? 0) > 0 ? 'destructive' : 'muted',
      active: overdue.value, go: () => apply({ overdue: overdue.value ? null : 1, stage: 'all', reopened: null }) },
    { key: 'reopened', label: 'أُعيد فتحها', value: props.stats.reopened ?? 0, icon: RotateCcw, tone: 'warning',
      active: reopened.value, go: () => apply({ reopened: reopened.value ? null : 1, stage: 'all', overdue: null }) },
]);

const pillTones = {
    primary: 'data-[active=true]:bg-primary data-[active=true]:text-primary-foreground',
    info: 'data-[active=true]:bg-info data-[active=true]:text-info-foreground',
    accent: 'data-[active=true]:bg-accent data-[active=true]:text-accent-foreground',
    warning: 'data-[active=true]:bg-warning data-[active=true]:text-warning-foreground',
    destructive: 'data-[active=true]:bg-destructive data-[active=true]:text-destructive-foreground',
    success: 'data-[active=true]:bg-success data-[active=true]:text-success-foreground',
    muted: 'data-[active=true]:bg-muted-foreground data-[active=true]:text-background',
};

function stageBadge(stage) {
    return statusLabel(IDEA_STAGE, stage);
}
</script>

<template>
    <Head title="صندوق المقترحات" />
    <AppShell>
        <div class="space-y-4">
            <!-- Gradient hero -->
            <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-elevated" style="background-image: var(--gradient-hero);">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(closest-side at 75% 20%, rgba(255,255,255,.4), transparent);"></div>
                <div class="relative flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs text-white/60">صندوق المقترحات · فريق الدعم · {{ suggestions.length }} معروض</p>
                        <h1 class="mt-1 text-2xl font-bold">صندوق المقترحات</h1>
                        <p class="mt-1 max-w-xl text-sm text-white/80">تابع وعالج مقترحات العملاء — استخدم البطاقات السريعة للفلترة.</p>
                    </div>
                    <Button :href="route('suggestions.create')" class="bg-white/15 text-white hover:bg-white/25 backdrop-blur-sm">
                        <Plus class="size-4" /> مقترح جديد
                    </Button>
                </div>

                <!-- KPI stat pills -->
                <div class="relative mt-5 flex flex-wrap gap-2">
                    <button v-for="p in pills" :key="p.key" @click="p.go()" :data-active="p.active"
                        :class="['group flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-sm backdrop-blur-sm transition-all hover:bg-white/20', pillTones[p.tone]]">
                        <component :is="p.icon" class="size-4 opacity-80" />
                        <span>{{ p.label }}</span>
                        <span class="rounded-full bg-black/20 px-1.5 text-xs font-bold tabular-nums">{{ p.value }}</span>
                    </button>
                </div>
            </div>

            <!-- Filter bar -->
            <Card class="p-3">
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="relative sm:col-span-2 lg:col-span-1">
                        <Search class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="q" placeholder="ابحث برقم المقترح أو العنوان…" class="pr-9" />
                    </div>
                    <Select :model-value="filters.stage ?? 'all'" @update:model-value="v => apply({ stage: v })">
                        <option value="all">كل المراحل</option>
                        <option v-for="[k, m] in Object.entries(IDEA_STAGE)" :key="k" :value="k">{{ m.label }}</option>
                    </Select>
                    <Select :model-value="filters.product ?? 'all'" @update:model-value="v => apply({ product: v })">
                        <option value="all">كل المنتجات / الخدمات</option>
                        <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name_ar }}</option>
                    </Select>
                    <Select :model-value="filters.team ?? 'all'" @update:model-value="v => apply({ team: v })">
                        <option value="all">كل الفرق</option>
                        <option v-for="tm in teams" :key="tm.id" :value="tm.id">{{ tm.name_ar }}</option>
                    </Select>
                    <Select :model-value="filters.range ?? 'all'" @update:model-value="v => apply({ range: v })">
                        <option value="all">كل الفترات</option>
                        <option value="7d">آخر 7 أيام</option>
                        <option value="30d">آخر 30 يوم</option>
                        <option value="90d">آخر 90 يوم</option>
                        <option value="365d">آخر سنة</option>
                    </Select>
                    <div class="relative">
                        <UserIcon class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="customer" placeholder="بحث باسم العميل…" class="pr-9" />
                    </div>
                    <Button :variant="overdue ? 'destructive' : 'outline'" size="sm"
                        @click="apply({ overdue: overdue ? null : 1, stage: 'all', reopened: null })">
                        <AlertTriangle class="size-4" /> متأخرة
                    </Button>
                    <Button :variant="reopened ? 'warning' : 'outline'" size="sm"
                        @click="apply({ reopened: reopened ? null : 1, stage: 'all', overdue: null })">
                        <RotateCcw class="size-4" /> أُعيد فتحها
                    </Button>
                </div>
            </Card>

            <!-- List -->
            <Card class="overflow-hidden p-0">
                <ul v-if="suggestions.length" class="divide-y divide-border">
                    <li v-for="s in suggestions" :key="s.id"
                        class="group relative cursor-pointer transition-colors hover:bg-muted/40"
                        :class="s.overdue && 'bg-destructive/[0.03]'"
                        @click="router.visit(route('suggestions.show', s.id))">
                        <span v-if="s.overdue" class="absolute inset-y-0 right-0 w-1 bg-destructive/60"></span>
                        <div class="flex items-stretch gap-3 p-4">
                            <div class="min-w-0 flex-1">
                                <!-- Badges row -->
                                <div class="mb-1.5 flex flex-wrap items-center gap-2">
                                    <span class="rounded border border-primary/20 bg-primary/5 px-1.5 py-0.5 font-mono text-[11px] font-bold text-primary">{{ s.request_number }}</span>
                                    <StatusBadge :status="s.status" />
                                    <Badge :variant="stageBadge(s.idea_stage).tone" class="text-[10px]">{{ stageBadge(s.idea_stage).label }}</Badge>
                                    <PriorityBadge :priority="s.priority" />
                                    <Badge v-if="s.reopened_count > 0" variant="warning" class="gap-1 text-[10px]">
                                        <RotateCcw class="size-3" /> أُعيد فتحه ×{{ s.reopened_count }}
                                    </Badge>
                                    <Badge v-if="s.overdue" variant="destructive" class="gap-1 text-[10px]">
                                        <AlertTriangle class="size-3" /> متأخر
                                    </Badge>
                                </div>
                                <!-- Title -->
                                <div class="truncate text-[15px] font-semibold leading-tight text-foreground">{{ s.title }}</div>
                                <!-- Description -->
                                <p v-if="s.description" class="mt-1 line-clamp-2 text-xs leading-relaxed text-muted-foreground">{{ s.description }}</p>
                                <!-- Meta -->
                                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-muted-foreground">
                                    <span class="inline-flex items-center gap-1"><Calendar class="size-3" /> {{ timeAgoAr(s.created_at) }}</span>
                                    <span v-if="s.category" class="inline-flex items-center gap-1">
                                        <span class="size-1.5 rounded-full" :style="{ background: s.category_color || 'var(--muted-foreground)' }"></span>
                                        {{ s.category }}
                                    </span>
                                    <span v-if="s.product" class="inline-flex items-center gap-1 text-accent">
                                        <Package class="size-3" /> {{ s.product }}
                                        <a v-if="s.source_customer_url" :href="s.source_customer_url" target="_blank" rel="noopener"
                                            class="hover:opacity-80" @click.stop>
                                            <ExternalLink class="size-3" />
                                        </a>
                                    </span>
                                    <span v-if="s.customer_name" class="inline-flex items-center gap-1"><UserIcon class="size-3" /> {{ s.customer_name }}</span>
                                    <span v-if="s.avg_stars" class="inline-flex items-center gap-0.5 font-semibold text-warning">
                                        <Star class="size-3 fill-current" /> {{ s.avg_stars }}/5
                                    </span>
                                </div>
                            </div>
                            <!-- Side metrics -->
                            <div class="flex shrink-0 flex-col items-end justify-between gap-2">
                                <div class="flex items-center gap-1.5">
                                    <span class="inline-flex items-center gap-1 rounded-md border border-border bg-muted/60 px-2 py-1 text-[11px] font-semibold tabular-nums text-foreground/80" title="نقاط الدعم">
                                        <ThumbsUp class="size-3" /> {{ s.support_count ?? 0 }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 rounded-md border border-border bg-muted/60 px-2 py-1 text-[11px] font-semibold tabular-nums text-foreground/80" title="عدد التعليقات">
                                        <MessageSquare class="size-3" /> {{ s.comments_count ?? 0 }}
                                    </span>
                                </div>
                                <span class="inline-flex size-7 items-center justify-center rounded-md text-muted-foreground transition group-hover:bg-primary/5 group-hover:text-primary">
                                    <ChevronLeft class="size-4" />
                                </span>
                            </div>
                        </div>
                    </li>
                </ul>
                <div v-else class="p-12 text-center">
                    <div class="mb-3 text-muted-foreground">لا توجد مقترحات مطابقة للتصفية.</div>
                    <Button :href="route('suggestions.create')" variant="outline" size="sm">أنشئ أول مقترح</Button>
                </div>
            </Card>
        </div>
    </AppShell>
</template>
