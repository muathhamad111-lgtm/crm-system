<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import SortableTh from '@/Components/ui/SortableTh.vue';
import { num } from '@/lib/utils';
import { fmtDateAr } from '@/lib/date';
import {
    Users, Search, Star, FileText, ChevronLeft, Briefcase, AlertTriangle, Activity,
} from 'lucide-vue-next';

const props = defineProps({
    customers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    fields: { type: Array, default: () => [] },
    kpis: { type: Object, default: () => ({}) },
});

const q = ref(props.filters.q ?? '');

function apply(patch = {}) {
    router.get(route('customers.index'),
        { q: q.value, field: props.filters.field, sort: props.filters.sort, dir: props.filters.dir, ...patch },
        { preserveState: true, replace: true, preserveScroll: true });
}
let timer = null;
watch(q, () => { clearTimeout(timer); timer = setTimeout(() => apply(), 350); });

// --- Server-side sorting ---
const sortKey = computed(() => props.filters.sort ?? 'requests');
const sortDir = computed(() => props.filters.dir ?? 'desc');
function setSort(col) {
    const dir = sortKey.value === col && sortDir.value === 'desc' ? 'asc' : 'desc';
    apply({ sort: col, dir });
}

const kpiRibbon = computed(() => [
    { key: 'total_customers', label: 'عدد العملاء', value: num(props.kpis.total_customers ?? 0), icon: Users, tone: 'bg-primary/10 text-primary', num: 'text-primary' },
    { key: 'total_requests', label: 'إجمالي الطلبات', value: num(props.kpis.total_requests ?? 0), icon: FileText, tone: 'bg-accent/10 text-accent', num: 'text-accent' },
    { key: 'avg_csat', label: 'متوسط الرضا', value: props.kpis.avg_csat != null ? `${props.kpis.avg_csat}/5` : '—', icon: Star, tone: 'bg-warning/15 text-warning', num: 'text-warning' },
    { key: 'open_requests', label: 'طلبات مفتوحة', value: num(props.kpis.open_requests ?? 0), icon: Activity, tone: 'bg-info/15 text-info', num: 'text-info' },
    { key: 'overdue_requests', label: 'طلبات متأخرة', value: num(props.kpis.overdue_requests ?? 0), icon: AlertTriangle, tone: 'bg-destructive/15 text-destructive', num: 'text-destructive' },
]);
</script>

<template>
    <Head title="قائمة العملاء" />
    <AppShell>
        <div class="space-y-5">
            <!-- Gradient hero -->
            <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-elevated" style="background-image: var(--gradient-hero);">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(closest-side at 75% 20%, rgba(255,255,255,.4), transparent);"></div>
                <div class="relative flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs text-white/60">العملاء · لوحة المتابعة · {{ num(kpis.total_customers ?? 0) }} عميل</p>
                        <h1 class="mt-1 text-2xl font-bold">قائمة العملاء</h1>
                        <p class="mt-1 max-w-xl text-sm text-white/80">
                            إدارة جميع العملاء، متابعة طلباتهم ومستوى رضاهم — اضغط على أي صف لفتح الملف التفصيلي.
                        </p>
                    </div>
                    <span class="rounded-full bg-white/15 px-3 py-1.5 text-xs backdrop-blur-sm tabular-nums">{{ num(customers.total) }} عميل</span>
                </div>
            </div>

            <!-- KPI ribbon -->
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-5">
                <Card v-for="k in kpiRibbon" :key="k.key" class="p-4">
                    <div class="flex items-start gap-3">
                        <div :class="`flex size-10 shrink-0 items-center justify-center rounded-xl ${k.tone}`">
                            <component :is="k.icon" class="size-5" />
                        </div>
                        <div class="min-w-0">
                            <div class="text-[10px] font-medium uppercase tracking-wider text-muted-foreground">{{ k.label }}</div>
                            <div :class="`text-xl font-black tabular-nums ${k.num}`">{{ k.value }}</div>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Toolbar -->
            <Card>
                <CardContent class="flex flex-wrap items-center gap-2 p-3">
                    <div class="relative min-w-[220px] flex-1">
                        <Search class="pointer-events-none absolute left-3 top-1/2 z-10 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="q" label="ابحث بالاسم، البريد، الجوال، المدينة..." />
                    </div>
                    <Select :model-value="filters.field ?? 'all'" class="w-[180px]" @update:model-value="v => apply({ field: v })">
                        <option value="all">كل المجالات</option>
                        <option v-for="f in fields" :key="f" :value="f">{{ f }}</option>
                    </Select>
                    <Select :model-value="filters.sort ?? 'requests'" class="w-[180px]" @update:model-value="v => apply({ sort: v, dir: 'desc' })">
                        <option value="requests">الأكثر طلبات</option>
                        <option value="csat">الأعلى رضا</option>
                        <option value="recent">الأحدث نشاطاً</option>
                    </Select>
                </CardContent>
            </Card>

            <!-- Table -->
            <Card class="overflow-hidden p-0">
                <div v-if="customers.data.length" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/50 text-xs text-muted-foreground">
                            <tr class="border-b border-border">
                                <SortableTh col="name" :sort-key="sortKey" :sort-dir="sortDir" @sort="setSort">العميل</SortableTh>
                                <SortableTh col="field" :sort-key="sortKey" :sort-dir="sortDir" @sort="setSort">المجال</SortableTh>
                                <SortableTh col="region" :sort-key="sortKey" :sort-dir="sortDir" @sort="setSort">المنطقة</SortableTh>
                                <SortableTh col="requests" align="center" :sort-key="sortKey" :sort-dir="sortDir" @sort="setSort">الطلبات</SortableTh>
                                <SortableTh col="open" align="center" :sort-key="sortKey" :sort-dir="sortDir" @sort="setSort">مفتوحة</SortableTh>
                                <SortableTh col="overdue" align="center" :sort-key="sortKey" :sort-dir="sortDir" @sort="setSort">متأخرة</SortableTh>
                                <SortableTh col="csat" align="center" :sort-key="sortKey" :sort-dir="sortDir" @sort="setSort">الرضا</SortableTh>
                                <SortableTh col="recent" align="center" :sort-key="sortKey" :sort-dir="sortDir" @sort="setSort">آخر نشاط</SortableTh>
                                <th class="px-2 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in customers.data" :key="c.id"
                                class="group cursor-pointer border-b border-border transition-colors hover:bg-muted/40"
                                @click="router.visit(route('customers.show', c.id))">
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-3">
                                        <Avatar :name="c.full_name" />
                                        <div class="min-w-0">
                                            <div class="truncate font-bold text-foreground">{{ c.full_name || '—' }}</div>
                                            <div class="truncate text-[11px] text-muted-foreground">{{ c.email || '—' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    <span v-if="c.business_field" class="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs text-foreground/80">
                                        <Briefcase class="size-3" /> {{ c.business_field }}
                                    </span>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <td class="px-3 py-3 text-xs text-muted-foreground">
                                    {{ [c.city, c.region].filter(Boolean).join('، ') || '—' }}
                                </td>
                                <td class="px-3 py-3 text-center font-bold tabular-nums text-foreground">{{ num(c.requests_count ?? 0) }}</td>
                                <td class="px-3 py-3 text-center tabular-nums">
                                    <span :class="c.open_requests_count > 0 ? 'font-bold text-accent' : 'text-muted-foreground'">
                                        {{ num(c.open_requests_count ?? 0) }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center tabular-nums">
                                    <span v-if="c.overdue_requests_count > 0" class="inline-flex items-center gap-1 font-bold text-destructive">
                                        <AlertTriangle class="size-3" /> {{ num(c.overdue_requests_count) }}
                                    </span>
                                    <span v-else class="text-muted-foreground">0</span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span v-if="c.avg_csat != null" class="inline-flex items-center gap-1">
                                        <Star class="size-3.5 fill-warning text-warning" />
                                        <span class="font-medium tabular-nums">{{ c.avg_csat }}</span>
                                        <span class="text-[10px] text-muted-foreground">({{ num(c.rating_count ?? 0) }})</span>
                                    </span>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <td class="px-3 py-3 text-center text-[11px] text-muted-foreground whitespace-nowrap">
                                    {{ c.last_request_at ? fmtDateAr(c.last_request_at) : (c.last_contact_at ? fmtDateAr(c.last_contact_at) : '—') }}
                                </td>
                                <td class="px-2 py-3 text-muted-foreground transition group-hover:text-primary">
                                    <ChevronLeft class="mx-auto size-4" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="p-12 text-center text-muted-foreground">
                    <Users class="mx-auto mb-3 size-10 opacity-40" />
                    <p class="text-sm">لا يوجد عملاء مطابقون لمعايير البحث</p>
                </div>
            </Card>

            <!-- Pagination -->
            <div v-if="customers.data.length" class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-muted-foreground">
                    عرض {{ num(customers.from ?? 0) }}–{{ num(customers.to ?? 0) }} من {{ num(customers.total) }}
                </p>
                <div class="flex flex-wrap gap-1">
                    <Link v-for="link in customers.links" :key="link.label"
                        :href="link.url || '#'" preserve-scroll v-html="link.label"
                        class="min-w-9 rounded-md border border-border px-3 py-1.5 text-center text-sm transition-colors"
                        :class="[
                            link.active ? 'border-primary bg-primary text-primary-foreground' : 'bg-card hover:bg-muted',
                            !link.url ? 'pointer-events-none opacity-40' : '',
                        ]" />
                </div>
            </div>
        </div>
    </AppShell>
</template>
