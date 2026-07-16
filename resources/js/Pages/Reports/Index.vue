<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import Select from '@/Components/ui/Select.vue';
import KpiCard from '@/Components/KpiCard.vue';
import {
    BarChart3, Filter, Download, Inbox, CheckCircle2, Clock, GaugeCircle, Star,
    ListChecks, Smile, Frown, TrendingUp, Users, Lightbulb, Package, ShieldCheck,
    ThumbsUp, ThumbsDown, MessageSquare, Vote, Send,
} from 'lucide-vue-next';
import { REQUEST_STATUS, REQUEST_PRIORITY, IDEA_STAGE, statusLabel } from '@/lib/labels';
import { num } from '@/lib/utils';

const props = defineProps({
    filters: { type: Object, default: () => ({}) },
    options: { type: Object, default: () => ({}) },
    customer: { type: Object, default: () => ({}) },
    requests: { type: Object, default: () => ({}) },
    suggestions: { type: Object, default: () => ({}) },
    products: { type: Object, default: () => ({}) },
    team: { type: Array, default: () => [] },
    sla: { type: Object, default: () => ({}) },
});

/* ----------------------------- Tabs ----------------------------- */
const tabs = [
    { key: 'customer', label: 'تقارير العملاء', icon: Users },
    { key: 'requests', label: 'تقارير الطلبات', icon: Inbox },
    { key: 'suggestions', label: 'تقارير المقترحات', icon: Lightbulb },
    { key: 'products', label: 'المنتجات والخدمات', icon: Package },
    { key: 'team', label: 'تقارير الفريق', icon: Users },
    { key: 'sla', label: 'تحليلات SLA', icon: ShieldCheck },
];
const activeTab = ref('customer');

/* ----------------------------- Filters ----------------------------- */
const f = ref({
    range: props.filters.range ?? '30',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
    region: props.filters.region ?? '',
    city: props.filters.city ?? '',
    category: props.filters.category ?? '',
    sub_category: props.filters.sub_category ?? '',
    customer: props.filters.customer ?? '',
    product: props.filters.product ?? '',
    business_field: props.filters.business_field ?? '',
});

// Sub-categories narrow to the selected category.
const subCategoriesForCategory = computed(() => {
    const all = props.options.subCategories ?? [];
    if (!f.value.category) return all;
    return all.filter((s) => String(s.category_id) === String(f.value.category));
});

function cleanParams() {
    const p = {};
    for (const [k, v] of Object.entries(f.value)) {
        if (v !== '' && v !== null && v !== undefined) p[k] = v;
    }
    // Only send explicit dates when the range is custom.
    if (p.range !== 'custom') { delete p.from; delete p.to; }
    return p;
}

function apply(patch = {}) {
    Object.assign(f.value, patch);
    if (patch.category !== undefined) f.value.sub_category = '';
    if ((patch.from !== undefined || patch.to !== undefined)) f.value.range = 'custom';
    router.get('/reports', cleanParams(), {
        preserveState: true, preserveScroll: true, replace: true,
    });
}

const exportUrl = computed(() => {
    const qs = new URLSearchParams(cleanParams()).toString();
    return '/reports/export' + (qs ? `?${qs}` : '');
});

/* ----------------------------- Chart theming ----------------------------- */
const PALETTE = ['#7c3aed', '#db2777', '#f59e0b', '#10b981', '#3b82f6', '#ef4444', '#06b6d4', '#8b5cf6'];

function baseOpts(extra = {}) {
    return {
        chart: {
            toolbar: { show: false },
            fontFamily: 'Tajawal, sans-serif',
            foreColor: '#94a3b8',
            animations: { speed: 400 },
            ...(extra.chart ?? {}),
        },
        colors: PALETTE,
        grid: { borderColor: 'rgba(148,163,184,0.15)', ...(extra.grid ?? {}) },
        tooltip: { theme: 'dark' },
        legend: { labels: { colors: '#94a3b8' } },
        dataLabels: { enabled: false },
        ...Object.fromEntries(Object.entries(extra).filter(([k]) => !['chart', 'grid'].includes(k))),
    };
}

function donut(labels) {
    return baseOpts({
        labels,
        legend: { position: 'bottom', labels: { colors: '#94a3b8' } },
        plotOptions: { pie: { donut: { size: '62%' } } },
        stroke: { width: 0 },
    });
}

function bar(categories, { horizontal = false, max = undefined, distributed = false } = {}) {
    return baseOpts({
        plotOptions: { bar: { horizontal, borderRadius: 6, distributed, columnWidth: '55%', barHeight: '65%' } },
        xaxis: { categories, labels: { style: { colors: '#94a3b8' } }, ...(max !== undefined ? { max } : {}) },
        yaxis: { labels: { style: { colors: '#94a3b8' } }, ...(max !== undefined && horizontal ? {} : {}) },
        legend: { show: false },
    });
}

/* ----------------------------- Helpers ----------------------------- */
function mapCounts(obj, labelMap) {
    // { key: count } -> [{label, value}] sorted desc
    return Object.entries(obj ?? {})
        .map(([k, v]) => ({ label: statusLabel(labelMap, k).label ?? k, value: Number(v) }))
        .sort((a, b) => b.value - a.value);
}
const hasAny = (arr) => Array.isArray(arr) && arr.length > 0 && arr.some((x) => (x.value ?? x) > 0);

/* ----------------------------- Customer tab ----------------------------- */
const c = computed(() => props.customer ?? {});
const custStatus = computed(() => mapCounts(c.value.byStatus, REQUEST_STATUS));
const custStatusChart = computed(() => ({
    options: donut(custStatus.value.map((x) => x.label)),
    series: custStatus.value.map((x) => x.value),
}));
const starsChart = computed(() => ({
    options: bar((c.value.starsDist ?? []).map((x) => x.label), { distributed: true }),
    series: [{ name: 'عدد', data: (c.value.starsDist ?? []).map((x) => x.value) }],
}));
const csatTrendChart = computed(() => {
    const t = c.value.csatTrend ?? [];
    return {
        options: baseOpts({
            chart: { type: 'line' },
            stroke: { width: [3, 2], curve: 'smooth', dashArray: [0, 5] },
            xaxis: { categories: t.map((x) => x.date), labels: { style: { colors: '#94a3b8' } } },
            yaxis: [
                { min: 0, max: 5, title: { text: 'متوسط الرضى', style: { color: '#94a3b8' } }, labels: { style: { colors: '#94a3b8' } } },
                { opposite: true, title: { text: 'عدد التقييمات', style: { color: '#94a3b8' } }, labels: { style: { colors: '#94a3b8' } } },
            ],
            legend: { position: 'top', labels: { colors: '#94a3b8' } },
            colors: ['#10b981', '#7c3aed'],
        }),
        series: [
            { name: 'متوسط الرضى', type: 'line', data: t.map((x) => x.csat) },
            { name: 'عدد التقييمات', type: 'line', data: t.map((x) => x.count) },
        ],
    };
});
const productSatChart = computed(() => {
    const d = c.value.avgSatPerProduct ?? [];
    return {
        options: { ...bar(d.map((x) => x.name), { horizontal: true, max: 5 }), colors: ['#db2777'] },
        series: [{ name: 'متوسط الرضى', data: d.map((x) => x.avg) }],
    };
});
const reasonsChart = computed(() => {
    const d = c.value.dissatisfactionReasons ?? [];
    return {
        options: { ...bar(d.map((x) => x.name), { horizontal: true }), colors: ['#ef4444'] },
        series: [{ name: 'عدد', data: d.map((x) => x.value) }],
    };
});
const subCatChart = computed(() => {
    const d = c.value.bySubCategory ?? [];
    return {
        options: { ...bar(d.map((x) => x.name), { horizontal: true }), colors: ['#3b82f6'] },
        series: [{ name: 'عدد الطلبات', data: d.map((x) => x.value) }],
    };
});
const completionRate = computed(() => {
    const t = c.value.kpis?.total ?? 0;
    return t > 0 ? Math.round(((c.value.kpis?.completed ?? 0) / t) * 100) : 0;
});

/* ----------------------------- Requests tab ----------------------------- */
const rq = computed(() => props.requests ?? {});
const rqStatus = computed(() => mapCounts(rq.value.byStatus, REQUEST_STATUS));
const rqStatusChart = computed(() => ({
    options: donut(rqStatus.value.map((x) => x.label)),
    series: rqStatus.value.map((x) => x.value),
}));
const rqPriority = computed(() => mapCounts(rq.value.byPriority, REQUEST_PRIORITY));
const rqPriorityChart = computed(() => ({
    options: bar(rqPriority.value.map((x) => x.label), { distributed: true }),
    series: [{ name: 'عدد', data: rqPriority.value.map((x) => x.value) }],
}));
const rqCategoryChart = computed(() => {
    const d = rq.value.byCategory ?? [];
    return {
        options: { ...bar(d.map((x) => x.name), { horizontal: true }), colors: ['#7c3aed'] },
        series: [{ name: 'عدد الطلبات', data: d.map((x) => x.value) }],
    };
});

/* ----------------------------- Suggestions tab ----------------------------- */
const sg = computed(() => props.suggestions ?? {});
const sgStage = computed(() => mapCounts(sg.value.byStage, IDEA_STAGE));
const sgStageChart = computed(() => ({
    options: { ...bar(sgStage.value.map((x) => x.label), { horizontal: true }), colors: ['#db2777'] },
    series: [{ name: 'عدد المقترحات', data: sgStage.value.map((x) => x.value) }],
}));

/* ----------------------------- Products tab ----------------------------- */
const pr = computed(() => props.products ?? {});
const prReqChart = computed(() => {
    const d = pr.value.requestsPerProduct ?? [];
    return {
        options: { ...bar(d.map((x) => x.name), { horizontal: true }), colors: ['#7c3aed'] },
        series: [{ name: 'عدد الطلبات', data: d.map((x) => x.value) }],
    };
});
const prCsatChart = computed(() => {
    const d = pr.value.csatPerProduct ?? [];
    return {
        options: { ...bar(d.map((x) => x.name), { horizontal: true, max: 5 }), colors: ['#10b981'] },
        series: [{ name: 'متوسط الرضى', data: d.map((x) => x.avg) }],
    };
});

/* ----------------------------- Team tab ----------------------------- */
const teamTotals = computed(() => (props.team ?? []).reduce((a, r) => ({
    assigned: a.assigned + r.assigned,
    closed: a.closed + r.closed,
    sla_ok: a.sla_ok + r.sla_ok,
}), { assigned: 0, closed: 0, sla_ok: 0 }));
const teamLoadChart = computed(() => {
    const d = [...(props.team ?? [])].slice(0, 8);
    return {
        options: { ...bar(d.map((x) => x.name), { horizontal: true }), colors: ['#7c3aed'] },
        series: [{ name: 'طلبات مُسندة', data: d.map((x) => x.assigned) }],
    };
});

/* ----------------------------- SLA tab ----------------------------- */
const slaPriorityChart = computed(() => {
    const d = (props.sla.byPriority ?? []).map((x) => ({ ...x, label: statusLabel(REQUEST_PRIORITY, x.name).label ?? x.name }));
    return {
        options: { ...bar(d.map((x) => x.label), { max: 100, distributed: true }), colors: PALETTE },
        series: [{ name: 'نسبة الالتزام %', data: d.map((x) => x.pct) }],
    };
});
const slaCategoryChart = computed(() => {
    const d = props.sla.byCategory ?? [];
    return {
        options: { ...bar(d.map((x) => x.name), { horizontal: true, max: 100 }), colors: ['#10b981'] },
        series: [{ name: 'نسبة الالتزام %', data: d.map((x) => x.pct) }],
    };
});
const slaStagesChart = computed(() => {
    const d = props.sla.topStages ?? [];
    return {
        options: { ...bar(d.map((x) => x.name), { horizontal: true }), colors: ['#ef4444'] },
        series: [{ name: 'حالات تجاوز', data: d.map((x) => x.value) }],
    };
});

function slaTone(pct) {
    return pct >= 80 ? 'text-success' : pct >= 50 ? 'text-warning' : 'text-destructive';
}
</script>

<template>
    <Head title="التقارير ومؤشرات الأداء" />
    <AppShell>
        <div class="space-y-5">
            <!-- Gradient hero -->
            <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-elevated" style="background-image: var(--gradient-hero);">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(closest-side at 75% 20%, rgba(255,255,255,.4), transparent);"></div>
                <div class="relative flex items-start gap-4">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-white/15 backdrop-blur-sm">
                        <BarChart3 class="size-6" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-white/60">التقارير · مؤشرات الأداء</p>
                        <h1 class="mt-1 text-2xl font-bold sm:text-3xl">التقارير ومؤشرات الأداء</h1>
                        <p class="mt-1 max-w-2xl text-sm text-white/80">تقارير قابلة للتخصيص لاتخاذ قرارات مبنية على البيانات</p>
                    </div>
                </div>
            </div>

            <!-- Tab strip -->
            <div class="flex flex-wrap gap-2 rounded-2xl border border-border bg-card p-1.5 shadow-sm">
                <button v-for="t in tabs" :key="t.key" @click="activeTab = t.key"
                    :class="['flex items-center gap-2 rounded-xl px-3.5 py-2 text-sm font-medium transition-all',
                        activeTab === t.key ? 'bg-primary text-primary-foreground shadow-elevated' : 'text-muted-foreground hover:bg-muted']">
                    <component :is="t.icon" class="size-4" />
                    <span>{{ t.label }}</span>
                </button>
            </div>

            <!-- Shared filters -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base"><Filter class="size-4" /> الفلاتر</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-3 md:grid-cols-3 lg:grid-cols-6">
                    <div>
                        <Label class="text-xs">المدة</Label>
                        <Select :model-value="f.range" @update:model-value="v => apply({ range: v })">
                            <option value="7">آخر 7 أيام</option>
                            <option value="30">آخر 30 يوم</option>
                            <option value="90">آخر 90 يوم</option>
                            <option value="all">كل الوقت</option>
                            <option value="custom">مخصص</option>
                        </Select>
                    </div>
                    <div>
                        <Label class="text-xs">من</Label>
                        <Input type="date" :model-value="f.from" @update:model-value="v => apply({ from: v })" />
                    </div>
                    <div>
                        <Label class="text-xs">إلى</Label>
                        <Input type="date" :model-value="f.to" @update:model-value="v => apply({ to: v })" />
                    </div>
                    <div>
                        <Label class="text-xs">المنطقة</Label>
                        <Select :model-value="f.region" @update:model-value="v => apply({ region: v })">
                            <option value="">الكل</option>
                            <option v-for="r in options.regions" :key="r" :value="r">{{ r }}</option>
                        </Select>
                    </div>
                    <div>
                        <Label class="text-xs">المدينة</Label>
                        <Select :model-value="f.city" @update:model-value="v => apply({ city: v })">
                            <option value="">الكل</option>
                            <option v-for="ci in options.cities" :key="ci" :value="ci">{{ ci }}</option>
                        </Select>
                    </div>
                    <div>
                        <Label class="text-xs">مجال العميل</Label>
                        <Select :model-value="f.business_field" @update:model-value="v => apply({ business_field: v })">
                            <option value="">الكل</option>
                            <option v-for="b in options.businessFields" :key="b" :value="b">{{ b }}</option>
                        </Select>
                    </div>
                    <div>
                        <Label class="text-xs">التصنيف</Label>
                        <Select :model-value="f.category" @update:model-value="v => apply({ category: v })">
                            <option value="">جميع التصنيفات</option>
                            <option v-for="cat in options.categories" :key="cat.id" :value="cat.id">{{ cat.name_ar }}</option>
                        </Select>
                    </div>
                    <div>
                        <Label class="text-xs">التصنيف الفرعي</Label>
                        <Select :model-value="f.sub_category" @update:model-value="v => apply({ sub_category: v })" :disabled="!f.category">
                            <option value="">الكل</option>
                            <option v-for="s in subCategoriesForCategory" :key="s.id" :value="s.id">{{ s.name_ar }}</option>
                        </Select>
                    </div>
                    <div class="lg:col-span-2">
                        <Label class="text-xs">العميل</Label>
                        <Select :model-value="f.customer" @update:model-value="v => apply({ customer: v })">
                            <option value="">جميع العملاء</option>
                            <option v-for="cu in options.customers" :key="cu.id" :value="cu.id">{{ cu.full_name }}{{ cu.email ? ' — ' + cu.email : '' }}</option>
                        </Select>
                    </div>
                    <div>
                        <Label class="text-xs">المنتج / الخدمة</Label>
                        <Select :model-value="f.product" @update:model-value="v => apply({ product: v })">
                            <option value="">الكل</option>
                            <option v-for="p in options.products" :key="p.id" :value="p.id">{{ p.name_ar }}</option>
                        </Select>
                    </div>
                    <div class="flex items-end">
                        <a :href="exportUrl"
                            class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary-deep">
                            <Download class="size-4" /> تصدير CSV
                        </a>
                    </div>
                </CardContent>
            </Card>

            <!-- ============ CUSTOMER TAB ============ -->
            <div v-if="activeTab === 'customer'" class="space-y-5">
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <KpiCard label="إجمالي الطلبات" :value="c.kpis?.total ?? 0" :icon="Inbox" tone="primary" />
                    <KpiCard label="المكتملة" :value="c.kpis?.completed ?? 0" :icon="CheckCircle2" tone="success" :hint="`نسبة الإنجاز ${completionRate}%`" />
                    <KpiCard label="متوسط الحل" :value="(c.kpis?.avg_resolution_hours ?? 0) + ' س'" :format-number="false" :icon="Clock" tone="warning" />
                    <KpiCard label="الالتزام بـ SLA" :value="(c.kpis?.sla_compliance ?? 0) + '%'" :format-number="false" :icon="GaugeCircle" tone="accent" />
                </div>
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
                    <KpiCard label="متوسط رضا العملاء" :value="c.kpis?.csat ? c.kpis.csat + '/5' : '—'" :format-number="false" :icon="Star" tone="accent" :hint="`من ${num(c.kpis?.ratings_count ?? 0)} تقييم`" />
                    <KpiCard label="عدد التقييمات" :value="c.kpis?.ratings_count ?? 0" :icon="ListChecks" tone="muted" />
                    <KpiCard label="عملاء راضون" :value="c.kpis?.satisfied ?? 0" :icon="Smile" tone="success" hint="تقييمات 4–5★" />
                    <KpiCard label="عملاء غير راضين" :value="c.kpis?.dissatisfied ?? 0" :icon="Frown" tone="destructive" hint="تقييمات 1–2★" />
                    <KpiCard label="مؤشر NPS" :value="c.kpis?.nps === null || c.kpis?.nps === undefined ? '—' : c.kpis.nps + '%'" :format-number="false" :icon="TrendingUp" tone="primary" />
                </div>

                <div class="grid gap-5 lg:grid-cols-2">
                    <Card>
                        <CardHeader><CardTitle>توزيع الحالات</CardTitle></CardHeader>
                        <CardContent>
                            <apexchart v-if="hasAny(custStatus)" type="donut" height="300" :options="custStatusChart.options" :series="custStatusChart.series" />
                            <p v-else class="py-12 text-center text-sm text-muted-foreground">لا توجد بيانات</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader><CardTitle>توزيع النجوم</CardTitle></CardHeader>
                        <CardContent>
                            <apexchart v-if="hasAny(c.starsDist)" type="bar" height="300" :options="starsChart.options" :series="starsChart.series" />
                            <p v-else class="py-12 text-center text-sm text-muted-foreground">لا توجد تقييمات</p>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader><CardTitle>اتجاه رضا العملاء عبر الزمن</CardTitle></CardHeader>
                    <CardContent>
                        <apexchart v-if="(c.csatTrend ?? []).length" type="line" height="320" :options="csatTrendChart.options" :series="csatTrendChart.series" />
                        <p v-else class="py-12 text-center text-sm text-muted-foreground">لا توجد تقييمات في هذه الفترة</p>
                    </CardContent>
                </Card>

                <div class="grid gap-5 lg:grid-cols-2">
                    <Card>
                        <CardHeader><CardTitle>متوسط الرضى لكل منتج</CardTitle></CardHeader>
                        <CardContent>
                            <apexchart v-if="(c.avgSatPerProduct ?? []).length" type="bar" height="320" :options="productSatChart.options" :series="productSatChart.series" />
                            <p v-else class="py-12 text-center text-sm text-muted-foreground">لا توجد بيانات</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader><CardTitle>أبرز أسباب عدم الرضى</CardTitle></CardHeader>
                        <CardContent>
                            <apexchart v-if="(c.dissatisfactionReasons ?? []).length" type="bar" height="320" :options="reasonsChart.options" :series="reasonsChart.series" />
                            <p v-else class="py-12 text-center text-sm text-muted-foreground">لا توجد بيانات</p>
                        </CardContent>
                    </Card>
                </div>

                <div class="grid gap-5 lg:grid-cols-2">
                    <Card>
                        <CardHeader><CardTitle class="flex items-center gap-2 text-success"><ThumbsUp class="size-5" /> أعلى 6 عملاء رضى</CardTitle></CardHeader>
                        <CardContent class="p-0">
                            <p v-if="!(c.topHappy ?? []).length" class="p-6 text-center text-sm text-muted-foreground">لا توجد تقييمات</p>
                            <div v-else class="divide-y divide-border">
                                <div v-for="(cust, i) in c.topHappy" :key="cust.id ?? i" class="flex items-center justify-between p-3">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="flex size-6 items-center justify-center rounded-full bg-success/15 text-xs font-bold text-success">{{ i + 1 }}</span>
                                        <span class="truncate text-sm">{{ cust.name }}</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-sm">
                                        <span class="text-xs text-muted-foreground">{{ cust.n }} تقييم</span>
                                        <span class="font-bold text-success tabular-nums">{{ cust.avg }}★</span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader><CardTitle class="flex items-center gap-2 text-destructive"><ThumbsDown class="size-5" /> أعلى 6 عملاء عدم رضى</CardTitle></CardHeader>
                        <CardContent class="p-0">
                            <p v-if="!(c.topUnhappy ?? []).length" class="p-6 text-center text-sm text-muted-foreground">لا توجد تقييمات</p>
                            <div v-else class="divide-y divide-border">
                                <div v-for="(cust, i) in c.topUnhappy" :key="cust.id ?? i" class="flex items-center justify-between p-3">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="flex size-6 items-center justify-center rounded-full bg-destructive/15 text-xs font-bold text-destructive">{{ i + 1 }}</span>
                                        <span class="truncate text-sm">{{ cust.name }}</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-sm">
                                        <span class="text-xs text-muted-foreground">{{ cust.n }} تقييم · {{ cust.neg }} سلبي</span>
                                        <span class="font-bold text-destructive tabular-nums">{{ cust.avg }}★</span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader><CardTitle>توزيع الطلبات حسب التصنيف الفرعي</CardTitle></CardHeader>
                    <CardContent>
                        <apexchart v-if="(c.bySubCategory ?? []).length" type="bar" :height="Math.max(280, (c.bySubCategory ?? []).length * 34)" :options="subCatChart.options" :series="subCatChart.series" />
                        <p v-else class="py-12 text-center text-sm text-muted-foreground">لا توجد بيانات</p>
                    </CardContent>
                </Card>
            </div>

            <!-- ============ REQUESTS TAB ============ -->
            <div v-else-if="activeTab === 'requests'" class="space-y-5">
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
                    <KpiCard label="إجمالي الطلبات" :value="rq.total ?? 0" :icon="Inbox" tone="primary" />
                    <KpiCard label="مفتوحة" :value="rq.open ?? 0" :icon="Clock" tone="info" />
                    <KpiCard label="مكتملة" :value="rq.completed ?? 0" :icon="CheckCircle2" tone="success" />
                    <KpiCard label="متأخرة" :value="rq.overdue ?? 0" :icon="Frown" tone="destructive" />
                    <KpiCard label="متوسط الحل" :value="(rq.avg_resolution_hours ?? 0) + ' س'" :format-number="false" :icon="GaugeCircle" tone="warning" />
                </div>
                <div class="grid gap-5 lg:grid-cols-2">
                    <Card>
                        <CardHeader><CardTitle>توزيع الحالات</CardTitle></CardHeader>
                        <CardContent>
                            <apexchart v-if="hasAny(rqStatus)" type="donut" height="300" :options="rqStatusChart.options" :series="rqStatusChart.series" />
                            <p v-else class="py-12 text-center text-sm text-muted-foreground">لا توجد بيانات</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader><CardTitle>توزيع الأولوية</CardTitle></CardHeader>
                        <CardContent>
                            <apexchart v-if="hasAny(rqPriority)" type="bar" height="300" :options="rqPriorityChart.options" :series="rqPriorityChart.series" />
                            <p v-else class="py-12 text-center text-sm text-muted-foreground">لا توجد بيانات</p>
                        </CardContent>
                    </Card>
                </div>
                <Card>
                    <CardHeader><CardTitle>التوزيع حسب التصنيف</CardTitle></CardHeader>
                    <CardContent>
                        <apexchart v-if="(rq.byCategory ?? []).length" type="bar" :height="Math.max(280, (rq.byCategory ?? []).length * 34)" :options="rqCategoryChart.options" :series="rqCategoryChart.series" />
                        <p v-else class="py-12 text-center text-sm text-muted-foreground">لا توجد بيانات</p>
                    </CardContent>
                </Card>
            </div>

            <!-- ============ SUGGESTIONS TAB ============ -->
            <div v-else-if="activeTab === 'suggestions'" class="space-y-5">
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
                    <KpiCard label="إجمالي المقترحات" :value="sg.total ?? 0" :icon="Lightbulb" tone="primary" />
                    <KpiCard label="منشورة للعملاء" :value="sg.published ?? 0" :icon="Send" tone="success" />
                    <KpiCard label="متوسط التقييم" :value="sg.rating_avg ? sg.rating_avg + '/5' : '—'" :format-number="false" :icon="Star" tone="accent" />
                    <KpiCard label="عدد التقييمات" :value="sg.rating_count ?? 0" :icon="ListChecks" tone="muted" />
                    <KpiCard label="عدد التعليقات" :value="sg.comments ?? 0" :icon="MessageSquare" tone="info" />
                    <KpiCard label="الأصوات" :value="sg.votes ?? 0" :icon="Vote" tone="warning" />
                </div>
                <div class="grid gap-5 lg:grid-cols-2">
                    <Card>
                        <CardHeader><CardTitle>التوزيع حسب المرحلة</CardTitle></CardHeader>
                        <CardContent>
                            <apexchart v-if="hasAny(sgStage)" type="bar" :height="Math.max(280, sgStage.length * 34)" :options="sgStageChart.options" :series="sgStageChart.series" />
                            <p v-else class="py-12 text-center text-sm text-muted-foreground">لا توجد بيانات</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader><CardTitle>أكثر العملاء تقديماً للمقترحات</CardTitle></CardHeader>
                        <CardContent class="p-0">
                            <p v-if="!(sg.topSubmitters ?? []).length" class="p-6 text-center text-sm text-muted-foreground">لا توجد بيانات</p>
                            <div v-else class="divide-y divide-border">
                                <div v-for="(s, i) in sg.topSubmitters" :key="s.id ?? i" class="flex items-center justify-between p-3">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="flex size-6 items-center justify-center rounded-full bg-primary-soft text-xs font-bold text-primary">{{ i + 1 }}</span>
                                        <span class="truncate text-sm">{{ s.name }}</span>
                                    </div>
                                    <span class="text-sm font-bold text-primary tabular-nums">{{ s.n }} مقترح</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- ============ PRODUCTS TAB ============ -->
            <div v-else-if="activeTab === 'products'" class="space-y-5">
                <div class="grid gap-5 lg:grid-cols-2">
                    <Card>
                        <CardHeader><CardTitle>عدد الطلبات لكل منتج / خدمة</CardTitle></CardHeader>
                        <CardContent>
                            <apexchart v-if="(pr.requestsPerProduct ?? []).length" type="bar" :height="Math.max(280, (pr.requestsPerProduct ?? []).length * 40)" :options="prReqChart.options" :series="prReqChart.series" />
                            <p v-else class="py-12 text-center text-sm text-muted-foreground">لا توجد بيانات</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader><CardTitle>متوسط الرضى لكل منتج / خدمة</CardTitle></CardHeader>
                        <CardContent>
                            <apexchart v-if="(pr.csatPerProduct ?? []).length" type="bar" :height="Math.max(280, (pr.csatPerProduct ?? []).length * 40)" :options="prCsatChart.options" :series="prCsatChart.series" />
                            <p v-else class="py-12 text-center text-sm text-muted-foreground">لا توجد بيانات</p>
                        </CardContent>
                    </Card>
                </div>
                <Card>
                    <CardHeader><CardTitle>تفاصيل الرضى لكل منتج</CardTitle></CardHeader>
                    <CardContent class="p-0">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-muted/50 text-xs text-muted-foreground">
                                    <tr class="border-b border-border">
                                        <th class="p-3 text-start">المنتج / الخدمة</th>
                                        <th class="p-3 text-center">متوسط الرضى</th>
                                        <th class="p-3 text-center">عدد التقييمات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(p, i) in pr.csatPerProduct" :key="i" class="border-b border-border">
                                        <td class="p-3 font-medium">{{ p.name }}</td>
                                        <td class="p-3 text-center font-bold tabular-nums">{{ p.avg }}★</td>
                                        <td class="p-3 text-center tabular-nums text-muted-foreground">{{ p.count }}</td>
                                    </tr>
                                    <tr v-if="!(pr.csatPerProduct ?? []).length"><td colspan="3" class="py-8 text-center text-muted-foreground">لا توجد بيانات</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- ============ TEAM TAB ============ -->
            <div v-else-if="activeTab === 'team'" class="space-y-5">
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <KpiCard label="عدد الموظفين" :value="team.length" :icon="Users" tone="primary" />
                    <KpiCard label="إجمالي المُسند" :value="teamTotals.assigned" :icon="Inbox" tone="info" />
                    <KpiCard label="إجمالي المُنجز" :value="teamTotals.closed" :icon="CheckCircle2" tone="success" />
                    <KpiCard label="معدل الالتزام" :value="`${teamTotals.closed ? Math.round((teamTotals.sla_ok / teamTotals.closed) * 100) : 0}%`" :format-number="false" :icon="GaugeCircle" tone="accent" />
                </div>
                <Card>
                    <CardHeader><CardTitle>أكثر الموظفين عبئاً</CardTitle></CardHeader>
                    <CardContent>
                        <apexchart v-if="team.length" type="bar" :height="Math.max(260, Math.min(team.length, 8) * 42)" :options="teamLoadChart.options" :series="teamLoadChart.series" />
                        <p v-else class="py-12 text-center text-sm text-muted-foreground">لا توجد بيانات</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader><CardTitle>أداء الموظفين</CardTitle></CardHeader>
                    <CardContent class="p-0">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-muted/50 text-xs text-muted-foreground">
                                    <tr class="border-b border-border">
                                        <th class="p-3 text-start">الموظف</th>
                                        <th class="p-3 text-center">مُسند</th>
                                        <th class="p-3 text-center">مُنجز</th>
                                        <th class="p-3 text-center">SLA %</th>
                                        <th class="p-3 text-center">متوسط الحل (س)</th>
                                        <th class="p-3 text-center">CSAT</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="r in team" :key="r.id" class="border-b border-border hover:bg-muted/30">
                                        <td class="p-3 font-medium">{{ r.name }}</td>
                                        <td class="p-3 text-center tabular-nums">{{ r.assigned }}</td>
                                        <td class="p-3 text-center tabular-nums">{{ r.closed }}</td>
                                        <td class="p-3 text-center"><span class="font-bold tabular-nums" :class="slaTone(r.sla_pct)">{{ r.sla_pct }}%</span></td>
                                        <td class="p-3 text-center tabular-nums">{{ r.avg_hours }}</td>
                                        <td class="p-3 text-center tabular-nums">{{ r.csat !== null ? `${r.csat} (${r.csat_n})` : '—' }}</td>
                                    </tr>
                                    <tr v-if="!team.length"><td colspan="6" class="py-8 text-center text-muted-foreground">لا توجد بيانات</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- ============ SLA TAB ============ -->
            <div v-else-if="activeTab === 'sla'" class="space-y-5">
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <KpiCard label="نسبة الالتزام بـ SLA" :value="(sla.compliance ?? 0) + '%'" :format-number="false" :icon="ShieldCheck" tone="success" />
                    <KpiCard label="الطلبات المُغلقة" :value="sla.closed_total ?? 0" :icon="CheckCircle2" tone="primary" />
                    <KpiCard label="ملتزمة بالموعد" :value="sla.compliant ?? 0" :icon="GaugeCircle" tone="accent" />
                    <KpiCard label="متأخرة" :value="(sla.closed_total ?? 0) - (sla.compliant ?? 0)" :icon="Clock" tone="destructive" />
                </div>
                <div class="grid gap-5 lg:grid-cols-2">
                    <Card>
                        <CardHeader><CardTitle>الالتزام حسب الأولوية</CardTitle></CardHeader>
                        <CardContent>
                            <apexchart v-if="(sla.byPriority ?? []).length" type="bar" height="300" :options="slaPriorityChart.options" :series="slaPriorityChart.series" />
                            <p v-else class="py-12 text-center text-sm text-muted-foreground">لا توجد بيانات</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader><CardTitle>الالتزام حسب التصنيف</CardTitle></CardHeader>
                        <CardContent>
                            <apexchart v-if="(sla.byCategory ?? []).length" type="bar" :height="Math.max(280, (sla.byCategory ?? []).length * 34)" :options="slaCategoryChart.options" :series="slaCategoryChart.series" />
                            <p v-else class="py-12 text-center text-sm text-muted-foreground">لا توجد بيانات</p>
                        </CardContent>
                    </Card>
                </div>
                <Card>
                    <CardHeader><CardTitle>أبرز المراحل المسبّبة للتأخير</CardTitle></CardHeader>
                    <CardContent>
                        <apexchart v-if="(sla.topStages ?? []).length" type="bar" :height="Math.max(280, (sla.topStages ?? []).length * 34)" :options="slaStagesChart.options" :series="slaStagesChart.series" />
                        <p v-else class="py-12 text-center text-sm text-muted-foreground">لا توجد بيانات</p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppShell>
</template>
