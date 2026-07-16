<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Badge from '@/Components/ui/Badge.vue';
import {
    Inbox, PlusCircle, Search, ExternalLink, Paperclip, Star, RotateCcw,
    Sparkles, Loader2, Hourglass, AlertTriangle, Clock, CheckCircle2, LayoutGrid,
} from 'lucide-vue-next';
import { timeAgoAr } from '@/lib/date';
import { REQUEST_PRIORITY, SERVICE_STATUS, statusLabel } from '@/lib/labels';

const props = defineProps({
    requests: { type: Object, default: () => ({ data: [], total: 0 }) },
    counts: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    isStaff: { type: Boolean, default: false },
    options: { type: Object, default: null },
});

const q = ref(props.filters.q ?? '');

function apply(patch = {}) {
    router.get('/requests', { ...props.filters, q: q.value, ...patch },
        { preserveState: true, replace: true, preserveScroll: true });
}
let t;
watch(q, () => { clearTimeout(t); t = setTimeout(() => apply(), 350); });

// KPI status pills (Lovable parity).
const pills = computed(() => [
    { key: 'all', label: 'الكل', icon: LayoutGrid, tone: 'primary' },
    { key: 'new', label: 'جديدة', icon: Sparkles, tone: 'info' },
    { key: 'in_progress', label: 'قيد المعالجة', icon: Loader2, tone: 'accent' },
    { key: 'awaiting_customer', label: 'بانتظار العميل', icon: Hourglass, tone: 'warning' },
    { key: 'escalated', label: 'مُصعّدة', icon: AlertTriangle, tone: 'destructive' },
    { key: 'overdue', label: 'متأخرة', icon: Clock, tone: 'destructive' },
    { key: 'completed', label: 'مكتملة', icon: CheckCircle2, tone: 'success' },
    { key: 'reopened', label: 'أعيد فتحها', icon: RotateCcw, tone: 'warning' },
]);
const activeStatus = computed(() => props.filters.status ?? 'all');

const pillTones = {
    primary: 'data-[active=true]:bg-primary data-[active=true]:text-primary-foreground',
    info: 'data-[active=true]:bg-info data-[active=true]:text-info-foreground',
    accent: 'data-[active=true]:bg-accent data-[active=true]:text-accent-foreground',
    warning: 'data-[active=true]:bg-warning data-[active=true]:text-warning-foreground',
    destructive: 'data-[active=true]:bg-destructive data-[active=true]:text-destructive-foreground',
    success: 'data-[active=true]:bg-success data-[active=true]:text-success-foreground',
};

const priorityRank = { urgent: 1, high: 2, medium: 3, low: 4 };
const priorityBar = { urgent: 'bg-destructive', high: 'bg-warning', medium: 'bg-info', low: 'bg-muted-foreground/40' };

const SLA = {
    out: { label: 'خارج', class: 'text-destructive' },
    warn: { label: 'إغلاق وشيك', class: 'text-warning' },
    in: { label: 'ضمن', class: 'text-success' },
    paused: { label: 'موقوف', class: 'text-muted-foreground' },
    none: { label: '—', class: 'text-muted-foreground' },
};

const title = computed(() => (props.isStaff ? 'صندوق الطلبات' : 'طلباتي'));
const subtitle = computed(() => (props.isStaff ? 'تابع الطلبات، أسندها، وراقب مؤشّرات SLA والأولوية في عرض واحد.' : 'متابعة طلباتك وتذاكرك في مكان واحد.'));
</script>

<template>
    <Head :title="title" />
    <AppShell>
        <div class="space-y-4">
            <!-- Gradient header -->
            <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-elevated" style="background-image: var(--gradient-hero);">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(closest-side at 75% 20%, rgba(255,255,255,.4), transparent);"></div>
                <div class="relative flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs text-white/60">صندوق الطلبات · لوحة العمليات</p>
                        <h1 class="mt-1 text-2xl font-bold">{{ title }}</h1>
                        <p class="mt-1 max-w-xl text-sm text-white/80">{{ subtitle }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-white/15 px-3 py-1.5 text-xs backdrop-blur-sm tabular-nums">{{ requests.total }} طلب</span>
                        <Button href="/requests/new" class="bg-white/15 text-white hover:bg-white/25 backdrop-blur-sm">
                            <PlusCircle class="size-4" /> {{ isStaff ? 'إضافة طلب لعميل' : 'طلب جديد' }}
                        </Button>
                    </div>
                </div>

                <!-- KPI status pills -->
                <div class="relative mt-5 flex flex-wrap gap-2">
                    <button v-for="p in pills" :key="p.key" @click="apply({ status: p.key })"
                        :data-active="activeStatus === p.key"
                        :class="['group flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-sm backdrop-blur-sm transition-all hover:bg-white/20', pillTones[p.tone]]">
                        <component :is="p.icon" class="size-4 opacity-80" />
                        <span>{{ p.label }}</span>
                        <span class="rounded-full bg-black/20 px-1.5 text-xs font-bold tabular-nums">{{ counts[p.key] ?? 0 }}</span>
                    </button>
                </div>
            </div>

            <!-- Filter bar -->
            <Card class="p-3">
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="relative sm:col-span-2 lg:col-span-1">
                        <Search class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="q" placeholder="ابحث برقم الطلب أو العنوان…" class="pr-9" />
                    </div>
                    <Select :model-value="filters.priority" @update:model-value="v => apply({ priority: v })">
                        <option value="all">كل الأولويات</option>
                        <option v-for="[k, m] in Object.entries(REQUEST_PRIORITY)" :key="k" :value="k">{{ m.label }}</option>
                    </Select>
                    <template v-if="isStaff && options">
                        <Select :model-value="filters.category" @update:model-value="v => apply({ category: v })">
                            <option value="all">كل التصنيفات</option>
                            <option v-for="c in options.categories" :key="c.id" :value="c.id">{{ c.name_ar }}</option>
                        </Select>
                        <Select :model-value="filters.product" @update:model-value="v => apply({ product: v })">
                            <option value="all">كل المنتجات</option>
                            <option v-for="p in options.products" :key="p.id" :value="p.id">{{ p.name_ar }}</option>
                        </Select>
                        <Select :model-value="filters.assignee" @update:model-value="v => apply({ assignee: v })">
                            <option value="all">كل المسؤولين</option>
                            <option value="none">غير مُسند</option>
                            <option v-for="a in options.assignees" :key="a.id" :value="a.id">{{ a.full_name }}</option>
                        </Select>
                        <Select :model-value="filters.sla" @update:model-value="v => apply({ sla: v })">
                            <option value="all">كل حالات SLA</option>
                            <option value="out">خارج SLA</option>
                            <option value="warn">إغلاق وشيك</option>
                            <option value="in">ضمن SLA</option>
                            <option value="paused">موقوف</option>
                        </Select>
                        <Select :model-value="filters.rating" @update:model-value="v => apply({ rating: v })">
                            <option value="all">كل التقييمات</option>
                            <option value="high">تقييم ≥4★</option>
                            <option value="low">تقييم ≤2★</option>
                            <option value="none">بدون تقييم</option>
                        </Select>
                    </template>
                </div>
            </Card>

            <!-- Table -->
            <Card class="overflow-hidden p-0">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-sm">
                        <thead class="bg-muted/50">
                            <tr class="border-b border-border text-xs text-muted-foreground">
                                <th class="w-10 px-2 py-3 text-center">#</th>
                                <th class="px-3 py-3 text-start">الطلب</th>
                                <th class="px-3 py-3 text-start">الحالة</th>
                                <th class="px-3 py-3 text-start">التصنيف</th>
                                <th class="px-3 py-3 text-start">المنتج</th>
                                <th v-if="isStaff" class="px-3 py-3 text-start">العميل</th>
                                <th v-if="isStaff" class="px-3 py-3 text-start">المسؤول</th>
                                <th v-if="isStaff" class="px-3 py-3 text-start">SLA</th>
                                <th v-if="isStaff" class="px-3 py-3 text-start">التقييم</th>
                                <th class="px-3 py-3 text-start whitespace-nowrap">آخر تحديث</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(r, i) in requests.data" :key="r.id"
                                class="cursor-pointer border-b border-border transition-colors hover:bg-muted/40"
                                @click="router.visit(`/requests/${r.id}`)">
                                <!-- rank + priority bar -->
                                <td class="px-2 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <span class="h-8 w-1 rounded-full" :class="priorityBar[r.priority] ?? 'bg-muted'"></span>
                                        <span class="text-xs tabular-nums text-muted-foreground">{{ (requests.from ?? 1) + i }}</span>
                                    </div>
                                </td>
                                <!-- request -->
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="max-w-[16rem] truncate font-medium">{{ r.title }}</span>
                                        <RotateCcw v-if="r.reopened_count" class="size-3.5 shrink-0 text-warning" />
                                    </div>
                                    <div class="mt-1 flex items-center gap-2">
                                        <span class="rounded bg-primary-soft px-1.5 py-0.5 text-[11px] font-bold tabular-nums text-primary">{{ r.request_number }}</span>
                                        <span v-if="r.attachments_count" class="flex items-center gap-0.5 text-[11px] text-muted-foreground">
                                            <Paperclip class="size-3" />{{ r.attachments_count }}
                                        </span>
                                        <Badge variant="muted" class="!px-1.5 !py-0 text-[10px]">{{ statusLabel(REQUEST_PRIORITY, r.priority).label }}</Badge>
                                    </div>
                                </td>
                                <!-- status -->
                                <td class="px-3 py-3">
                                    <StatusBadge v-if="isStaff" :status="r.status" />
                                    <Badge v-else :variant="statusLabel(SERVICE_STATUS, r.service_status).tone">{{ statusLabel(SERVICE_STATUS, r.service_status).label }}</Badge>
                                </td>
                                <!-- category -->
                                <td class="px-3 py-3">
                                    <span class="flex items-center gap-1.5 text-sm">
                                        <span class="size-2 rounded-full" :style="{ background: r.category?.color || 'var(--muted-foreground)' }"></span>
                                        {{ r.category?.name_ar ?? '—' }}
                                    </span>
                                </td>
                                <!-- product -->
                                <td class="px-3 py-3">
                                    <span v-if="r.product" class="flex items-center gap-1 text-sm text-muted-foreground">
                                        <ExternalLink class="size-3.5" /> {{ r.product.name_ar }}
                                    </span>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <!-- customer -->
                                <td v-if="isStaff" class="px-3 py-3">
                                    <div v-if="r.customer" class="flex items-center gap-2">
                                        <Avatar :name="r.customer.full_name" class="size-6 text-[10px]" />
                                        <span class="max-w-[8rem] truncate text-sm">{{ r.customer.full_name }}</span>
                                    </div>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <!-- assignee -->
                                <td v-if="isStaff" class="px-3 py-3">
                                    <div v-if="r.assignee" class="flex items-center gap-2">
                                        <Avatar :name="r.assignee.full_name" class="size-6 text-[10px]" />
                                        <span class="max-w-[8rem] truncate text-sm">{{ r.assignee.full_name }}</span>
                                    </div>
                                    <span v-else class="text-xs text-muted-foreground">غير مُسند</span>
                                </td>
                                <!-- SLA -->
                                <td v-if="isStaff" class="px-3 py-3">
                                    <span class="flex items-center gap-1 text-xs font-bold" :class="SLA[r.sla_state]?.class">
                                        <span class="size-1.5 rounded-full bg-current"></span>{{ SLA[r.sla_state]?.label ?? '—' }}
                                    </span>
                                </td>
                                <!-- rating -->
                                <td v-if="isStaff" class="px-3 py-3">
                                    <span v-if="r.rating" class="flex items-center gap-0.5 text-warning">
                                        <Star class="size-3.5 fill-current" /><span class="text-xs tabular-nums">{{ r.rating }}</span>
                                    </span>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <!-- updated -->
                                <td class="px-3 py-3 whitespace-nowrap text-xs text-muted-foreground">{{ timeAgoAr(r.updated_at) }}</td>
                            </tr>
                            <tr v-if="!requests.data.length">
                                <td :colspan="isStaff ? 10 : 6" class="py-12 text-center text-muted-foreground">لا توجد طلبات مطابقة.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>

            <!-- Pagination -->
            <div v-if="requests.last_page > 1" class="flex justify-center gap-1">
                <Link v-for="link in requests.links" :key="link.label" :href="link.url || '#'" v-html="link.label"
                    :class="['rounded-md px-3 py-1.5 text-sm', link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted', !link.url && 'pointer-events-none opacity-40']" />
            </div>
        </div>
    </AppShell>
</template>
