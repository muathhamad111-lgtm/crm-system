<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Card from '@/Components/ui/Card.vue';
import KpiCard from '@/Components/KpiCard.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import Table from '@/Components/ui/Table.vue';
import TableHeader from '@/Components/ui/TableHeader.vue';
import TableBody from '@/Components/ui/TableBody.vue';
import TableRow from '@/Components/ui/TableRow.vue';
import TableCell from '@/Components/ui/TableCell.vue';
import SortableTh from '@/Components/ui/SortableTh.vue';
import {
    Star, Users, ThumbsUp, ThumbsDown, TrendingUp, Search,
    MessageSquare, ChevronLeft,
} from 'lucide-vue-next';
import { fmtDateAr } from '@/lib/date';
import { useClientSort } from '@/lib/useSort';

const props = defineProps({
    ratings: { type: [Object, Array], default: () => ({ data: [] }) },
    stats: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    businessFields: { type: Array, default: () => [] },
    isStaff: { type: Boolean, default: false },
});

// The controller sends a plain array; tolerate a paginator-like shape too.
const rows = computed(() => (Array.isArray(props.ratings) ? props.ratings : (props.ratings?.data ?? [])));

// --- Client-side filters (mirror the Lovable page: instant, no round-trip) ---
const q = ref(props.filters?.q ?? '');
const starsFilter = ref(props.filters?.stars ?? 'all');
const fieldFilter = ref(props.filters?.field ?? 'all');

const filtered = computed(() => rows.value.filter((r) => {
    if (starsFilter.value !== 'all' && Number(r.stars) !== Number(starsFilter.value)) return false;
    if (fieldFilter.value !== 'all' && r.business_field !== fieldFilter.value) return false;
    const s = q.value.trim();
    if (s) {
        const hay = `${r.customer_name ?? ''} ${r.customer_email ?? ''} ${r.request_number ?? ''} ${r.title ?? ''} ${r.category_name ?? ''}`;
        if (!hay.includes(s)) return false;
    }
    return true;
}));

// Sorting on the filtered set — KEEP SortableTh + useClientSort.
const { sorted, sortKey, sortDir, toggle } = useClientSort(() => filtered.value, 'date', 'desc', {
    number: (r) => r.request_number ?? '',
    stars: 'stars',
    customer: (r) => r.customer_name ?? r.customer_email ?? '',
    field: 'business_field',
    category: 'category_name',
    notes: 'notes',
    date: 'created_at',
});

const colCount = computed(() => (props.isStaff ? 7 : 5));

function openRequest(r) {
    if (r.request_id) router.visit(`/requests/${r.request_id}`);
}
</script>

<template>
    <Head title="التقييمات" />
    <AppShell>
        <div class="space-y-4">
            <!-- Gradient hero -->
            <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-elevated" style="background-image: var(--gradient-hero);">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(closest-side at 75% 20%, rgba(255,255,255,.4), transparent);"></div>
                <div class="relative flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs text-white/60">تقييمات العملاء · رضا العملاء</p>
                        <h1 class="mt-1 text-2xl font-bold">قائمة التقييمات</h1>
                        <p class="mt-1 max-w-xl text-sm text-white/80">انقر على أي تقييم لفتح بطاقة الطلب وعرض تفاصيل الملاحظات.</p>
                    </div>
                    <span class="rounded-full bg-white/15 px-3 py-1.5 text-xs backdrop-blur-sm tabular-nums">{{ filtered.length }} تقييم</span>
                </div>
            </div>

            <!-- CSAT KPI ribbon -->
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
                <KpiCard label="متوسط CSAT" :value="`${stats.avg ?? 0}/5`" :format-number="false" :icon="TrendingUp" tone="warning" hint="مجموع النجوم ÷ عدد التقييمات (1–5)" />
                <KpiCard label="إجمالي التقييمات" :value="stats.total ?? 0" :icon="Star" tone="primary" hint="ضمن نطاق العملاء الحالي" />
                <KpiCard label="راضون (4–5★)" :value="stats.promoters ?? 0" :icon="ThumbsUp" tone="success" hint="العملاء المؤيّدون" />
                <KpiCard label="غير راضين (1–2★)" :value="stats.detractors ?? 0" :icon="ThumbsDown" :tone="(stats.detractors ?? 0) > 0 ? 'destructive' : 'muted'" hint="راجع تعليقاتهم" />
                <KpiCard label="مؤشر NPS" :value="stats.nps ?? 0" :icon="Users" :tone="(stats.nps ?? 0) >= 0 ? 'accent' : 'destructive'" hint="نسبة المؤيّدين ناقص المنتقدين" />
            </div>

            <!-- Filter bar -->
            <Card class="p-3">
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="relative sm:col-span-2 lg:col-span-2">
                        <Search class="pointer-events-none absolute left-3 top-1/2 z-10 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="q" label="بحث باسم العميل، البريد، رقم الطلب، أو نوع المشكلة…" />
                    </div>
                    <Select label="عدد النجوم" v-model="starsFilter">
                        <option value="all">جميع التقييمات</option>
                        <option v-for="n in [5,4,3,2,1]" :key="n" :value="String(n)">{{ n }} ★</option>
                    </Select>
                    <Select v-if="isStaff && businessFields.length" label="المجال" v-model="fieldFilter">
                        <option value="all">جميع المجالات</option>
                        <option v-for="f in businessFields" :key="f" :value="f">{{ f }}</option>
                    </Select>
                </div>
            </Card>

            <!-- Table -->
            <Card class="p-0">
                <div v-if="!sorted.length" class="flex flex-col items-center gap-3 py-16 text-center text-muted-foreground">
                    <MessageSquare class="size-10 opacity-40" />
                    لا توجد تقييمات مطابقة.
                </div>
                <div v-else class="overflow-x-auto">
                    <Table>
                        <TableHeader><TableRow>
                            <SortableTh col="customer" v-if="isStaff" :sort-key="sortKey" :sort-dir="sortDir" @sort="toggle">العميل</SortableTh>
                            <SortableTh col="field" v-if="isStaff" :sort-key="sortKey" :sort-dir="sortDir" @sort="toggle">المجال</SortableTh>
                            <SortableTh col="category" :sort-key="sortKey" :sort-dir="sortDir" @sort="toggle">نوع المشكلة</SortableTh>
                            <SortableTh col="number" :sort-key="sortKey" :sort-dir="sortDir" @sort="toggle">الطلب</SortableTh>
                            <SortableTh col="stars" :sort-key="sortKey" :sort-dir="sortDir" @sort="toggle">التقييم</SortableTh>
                            <SortableTh col="notes" :sort-key="sortKey" :sort-dir="sortDir" @sort="toggle">الملاحظات</SortableTh>
                            <SortableTh col="date" :sort-key="sortKey" :sort-dir="sortDir" @sort="toggle">التاريخ</SortableTh>
                        </TableRow></TableHeader>
                        <TableBody>
                            <TableRow v-for="r in sorted" :key="r.id" class="cursor-pointer transition-colors hover:bg-muted/40" @click="openRequest(r)">
                                <TableCell v-if="isStaff">
                                    <div class="font-medium">{{ r.customer_name ?? '—' }}</div>
                                    <div v-if="r.customer_email" class="max-w-[12rem] truncate text-[11px] text-muted-foreground">{{ r.customer_email }}</div>
                                </TableCell>
                                <TableCell v-if="isStaff" class="text-xs">
                                    <span v-if="r.business_field" class="rounded bg-accent/10 px-2 py-0.5 text-accent">{{ r.business_field }}</span>
                                    <span v-else class="text-muted-foreground">—</span>
                                </TableCell>
                                <TableCell class="text-xs text-muted-foreground">{{ r.category_name ?? '—' }}</TableCell>
                                <TableCell>
                                    <div class="font-bold text-primary tabular-nums">{{ r.request_number ?? '—' }}</div>
                                    <div class="max-w-[13rem] truncate text-xs text-muted-foreground">{{ r.title }}</div>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-0.5">
                                        <Star v-for="n in 5" :key="n" :class="['size-4', n <= r.stars ? 'fill-warning text-warning' : 'text-muted-foreground/30']" />
                                        <span class="mr-1 text-xs text-muted-foreground tabular-nums">({{ r.stars }}/5)</span>
                                    </div>
                                    <div v-if="(r.dissatisfaction_reasons || []).length" class="mt-1 text-[10px] text-destructive">
                                        {{ (r.dissatisfaction_reasons || []).slice(0, 2).join('، ') }}
                                    </div>
                                </TableCell>
                                <TableCell class="max-w-[15rem] truncate text-sm text-muted-foreground">{{ r.notes || '—' }}</TableCell>
                                <TableCell class="whitespace-nowrap text-xs text-muted-foreground">
                                    <div class="flex items-center justify-between gap-2">
                                        <span>{{ fmtDateAr(r.created_at) }}</span>
                                        <Link v-if="r.request_id" :href="`/requests/${r.request_id}`" class="inline-flex items-center gap-0.5 text-primary hover:underline" @click.stop>
                                            فتح <ChevronLeft class="size-3" />
                                        </Link>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </Card>
        </div>
    </AppShell>
</template>
