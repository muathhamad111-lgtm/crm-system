<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Card from '@/Components/ui/Card.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import SortableTh from '@/Components/ui/SortableTh.vue';
import { timeAgoAr } from '@/lib/date';
import { REQUEST_STATUS, IDEA_STAGE, statusLabel } from '@/lib/labels';
import { useClientSort } from '@/lib/useSort';
import {
    Lightbulb, Plus, Search, Sparkles, Hourglass, CheckCircle2,
    Package, Clock, ChevronLeft, MessageSquare, Star,
} from 'lucide-vue-next';

const props = defineProps({
    suggestions: { type: Array, default: () => [] },
    counts: { type: Object, default: () => ({}) },
});

const HIDDEN = new Set(['awaiting_internal', 'escalated']);
const visStatus = (s) => (HIDDEN.has(s) ? 'in_progress' : s);

const search = ref('');
const kpi = ref('all');
const status = ref('all');

const kpiChips = computed(() => [
    { key: 'all', label: 'إجمالي', value: props.counts.total ?? 0, icon: Sparkles },
    { key: 'open', label: 'جديد', value: props.counts.open ?? 0, icon: Lightbulb },
    { key: 'in_progress', label: 'قيد المعالجة', value: props.counts.in_progress ?? 0, icon: Hourglass },
    { key: 'done', label: 'مكتمل', value: props.counts.done ?? 0, icon: CheckCircle2 },
]);

const filtered = computed(() => {
    let arr = props.suggestions;
    if (kpi.value === 'open') arr = arr.filter((r) => ['new', 'reopened', 'awaiting_customer'].includes(r.status));
    else if (kpi.value === 'in_progress') arr = arr.filter((r) => ['in_progress', 'under_review', 'awaiting_internal', 'escalated'].includes(r.status));
    else if (kpi.value === 'done') arr = arr.filter((r) => ['completed', 'closed'].includes(r.status));
    if (status.value !== 'all') arr = arr.filter((r) => visStatus(r.status) === status.value);
    const q = search.value.trim();
    if (q) arr = arr.filter((r) => r.title?.includes(q) || r.request_number?.includes(q) || r.category?.includes(q));
    return arr;
});

const statusOptions = computed(() =>
    Object.entries(REQUEST_STATUS).filter(([k]) => !HIDDEN.has(k)));

const { sorted, sortKey, sortDir, toggle } = useClientSort(() => filtered.value, 'updated', 'desc', {
    number: 'request_number',
    status: (r) => visStatus(r.status),
    stage: 'idea_stage',
    category: 'category',
    product: 'product',
    updated: (r) => r.updated_at ?? r.created_at,
});
</script>

<template>
    <Head title="مقترحاتي" />
    <AppShell>
        <div class="space-y-4">
            <!-- Gradient hero -->
            <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-elevated" style="background-image: var(--gradient-hero);">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(closest-side at 75% 20%, rgba(255,255,255,.4), transparent);"></div>
                <div class="relative flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs text-white/60">مقترحاتي · لوحة المتابعة</p>
                        <h1 class="mt-1 text-2xl font-bold">مقترحاتي</h1>
                        <p class="mt-1 max-w-xl text-sm text-white/80">تابع الأفكار التي شاركتها، تفاعل الفريق معها، وحالة كل مقترح في مكان واحد.</p>
                    </div>
                    <Button :href="route('suggestions.create')" class="bg-white/15 text-white hover:bg-white/25 backdrop-blur-sm">
                        <Plus class="size-4" /> مقترح جديد
                    </Button>
                </div>
                <!-- KPI chips -->
                <div class="relative mt-5 flex flex-wrap gap-2">
                    <button v-for="k in kpiChips" :key="k.key" @click="kpi = k.key" :data-active="kpi === k.key"
                        class="group flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-sm backdrop-blur-sm transition-all hover:bg-white/20 data-[active=true]:bg-white data-[active=true]:text-primary">
                        <component :is="k.icon" class="size-4 opacity-80" />
                        <span>{{ k.label }}</span>
                        <span class="rounded-full bg-black/20 px-1.5 text-xs font-bold tabular-nums group-data-[active=true]:bg-primary/10 group-data-[active=true]:text-primary">{{ k.value }}</span>
                    </button>
                </div>
            </div>

            <!-- Filter bar -->
            <Card class="p-3">
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="relative">
                        <Search class="pointer-events-none absolute left-3 top-1/2 z-10 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="search" label="ابحث برقم المقترح أو العنوان…" />
                    </div>
                    <Select v-model="status">
                        <option value="all">جميع الحالات</option>
                        <option v-for="[k, m] in statusOptions" :key="k" :value="k">{{ m.label }}</option>
                    </Select>
                </div>
            </Card>

            <!-- Table -->
            <Card class="overflow-hidden p-0">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-sm">
                        <thead class="bg-muted/50">
                            <tr class="border-b border-border text-xs text-muted-foreground">
                                <SortableTh col="number" :sort-key="sortKey" :sort-dir="sortDir" @sort="toggle">المقترح</SortableTh>
                                <SortableTh col="status" :sort-key="sortKey" :sort-dir="sortDir" @sort="toggle">الحالة</SortableTh>
                                <SortableTh col="stage" :sort-key="sortKey" :sort-dir="sortDir" @sort="toggle">المرحلة</SortableTh>
                                <SortableTh col="category" :sort-key="sortKey" :sort-dir="sortDir" @sort="toggle">التصنيف</SortableTh>
                                <SortableTh col="product" :sort-key="sortKey" :sort-dir="sortDir" @sort="toggle">المنتج</SortableTh>
                                <SortableTh col="updated" :sort-key="sortKey" :sort-dir="sortDir" @sort="toggle" class="whitespace-nowrap">آخر تحديث</SortableTh>
                                <th class="w-10 px-2 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="r in sorted" :key="r.id"
                                class="group cursor-pointer border-b border-border transition-colors hover:bg-muted/40"
                                @click="router.visit(route('suggestions.show', r.id))">
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="w-1 self-stretch rounded-full" :style="{ background: r.category_color || 'var(--primary)', minHeight: '24px' }"></span>
                                        <span class="rounded bg-primary-soft px-1.5 py-0.5 text-[11px] font-bold tabular-nums text-primary">{{ r.request_number }}</span>
                                        <span class="max-w-[18rem] truncate font-medium group-hover:text-primary">{{ r.title }}</span>
                                    </div>
                                    <div class="mt-1 flex items-center gap-2 pr-4 text-[11px] text-muted-foreground">
                                        <span v-if="r.comments_count" class="inline-flex items-center gap-0.5"><MessageSquare class="size-3" /> {{ r.comments_count }}</span>
                                        <span v-if="r.avg_stars" class="inline-flex items-center gap-0.5 text-warning"><Star class="size-3 fill-current" /> {{ r.avg_stars }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    <Badge :variant="statusLabel(REQUEST_STATUS, visStatus(r.status)).tone">{{ statusLabel(REQUEST_STATUS, visStatus(r.status)).label }}</Badge>
                                </td>
                                <td class="px-3 py-3">
                                    <Badge :variant="statusLabel(IDEA_STAGE, r.idea_stage).tone" class="text-[10px]">{{ statusLabel(IDEA_STAGE, r.idea_stage).label }}</Badge>
                                </td>
                                <td class="px-3 py-3">
                                    <span v-if="r.category" class="inline-flex items-center gap-1.5 text-sm">
                                        <span class="size-2 rounded-full" :style="{ background: r.category_color || 'var(--muted-foreground)' }"></span>
                                        {{ r.category }}
                                    </span>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <td class="px-3 py-3">
                                    <span v-if="r.product" class="inline-flex items-center gap-1 text-sm text-muted-foreground"><Package class="size-3.5" /> {{ r.product }}</span>
                                    <span v-else class="text-muted-foreground">عام</span>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-xs text-muted-foreground">
                                    <span class="inline-flex items-center gap-1"><Clock class="size-3" /> {{ timeAgoAr(r.updated_at ?? r.created_at) }}</span>
                                </td>
                                <td class="px-2 py-3 text-left">
                                    <ChevronLeft class="size-4 text-muted-foreground/40 transition-all group-hover:-translate-x-0.5 group-hover:text-primary" />
                                </td>
                            </tr>
                            <tr v-if="!sorted.length">
                                <td colspan="7" class="py-14 text-center">
                                    <div class="mx-auto mb-3 flex size-14 items-center justify-center rounded-2xl bg-primary-soft text-primary">
                                        <Lightbulb class="size-7" />
                                    </div>
                                    <div class="font-medium text-foreground">{{ suggestions.length ? 'لا توجد مقترحات مطابقة' : 'لم تشارك أي مقترح بعد' }}</div>
                                    <p class="mt-1 text-sm text-muted-foreground">{{ suggestions.length ? 'جرّب تعديل التصفية.' : 'صوتك مهم — شاركنا فكرتك لتحسين الخدمة.' }}</p>
                                    <div v-if="!suggestions.length" class="mt-4">
                                        <Button :href="route('suggestions.create')" variant="outline" size="sm"><Plus class="size-4" /> أرسل أول مقترح</Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>
    </AppShell>
</template>
