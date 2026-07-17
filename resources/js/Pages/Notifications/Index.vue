<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import {
    Bell, CheckCheck, Inbox, AlertTriangle, MessageSquare, FileCheck2,
    CalendarClock, ClipboardList, ShieldCheck, Lightbulb, Search, RotateCcw, Filter,
} from 'lucide-vue-next';
import { timeAgoAr } from '@/lib/date';
import { num } from '@/lib/utils';

const props = defineProps({
    notifications: { type: Object, default: () => ({ data: [], links: [], total: 0 }) },
    filters: { type: Object, default: () => ({ filter: 'all', type: 'all', priority: 'all', period: 'all', q: '' }) },
    unreadCount: { type: Number, default: 0 },
    totalCount: { type: Number, default: 0 },
    typeCounts: { type: Object, default: () => ({}) },
});

const TYPE_LABEL = {
    request: 'طلبات', appointment: 'مواعيد', comment: 'تعليقات', escalation: 'تصعيدات',
    sla: 'SLA', task: 'مهام', system: 'نظام', permission: 'صلاحيات', suggestion: 'مقترحات',
};
const TYPE_META = {
    request: { icon: ClipboardList, tone: 'bg-primary/10 text-primary', border: 'border-r-primary' },
    appointment: { icon: CalendarClock, tone: 'bg-accent/10 text-accent', border: 'border-r-accent' },
    comment: { icon: MessageSquare, tone: 'bg-accent/10 text-accent', border: 'border-r-accent' },
    escalation: { icon: AlertTriangle, tone: 'bg-destructive/10 text-destructive', border: 'border-r-destructive' },
    sla: { icon: AlertTriangle, tone: 'bg-destructive/10 text-destructive', border: 'border-r-destructive' },
    task: { icon: FileCheck2, tone: 'bg-primary/10 text-primary', border: 'border-r-primary' },
    permission: { icon: ShieldCheck, tone: 'bg-primary/10 text-primary', border: 'border-r-primary' },
    suggestion: { icon: Lightbulb, tone: 'bg-accent/10 text-accent', border: 'border-r-accent' },
    system: { icon: Bell, tone: 'bg-muted text-muted-foreground', border: 'border-r-muted-foreground/40' },
};
const metaOf = (t) => TYPE_META[t] ?? TYPE_META.system;
const cleanTitle = (t) => (t ?? '').replace(/^[🚨⚠️💬✅🎉📩⭐🔒📅]+\s*/u, '').trim();

const q = ref(props.filters.q ?? '');
let timer = null;
function reload(extra = {}) {
    router.get('/notifications', {
        filter: props.filters.filter, type: props.filters.type,
        priority: props.filters.priority, period: props.filters.period,
        q: q.value, ...extra,
    }, { preserveState: true, replace: true, preserveScroll: true });
}
watch(q, () => { clearTimeout(timer); timer = setTimeout(() => reload(), 350); });

const activeFilters = computed(() =>
    (props.filters.filter !== 'all' ? 1 : 0) + (props.filters.type !== 'all' ? 1 : 0)
    + (props.filters.priority !== 'all' ? 1 : 0) + (props.filters.period !== 'all' ? 1 : 0));
function resetFilters() {
    q.value = '';
    reload({ filter: 'all', type: 'all', priority: 'all', period: 'all', q: '' });
}

function open(n) {
    const target = n.link_path || (n.request_id ? `/requests/${n.request_id}` : null);
    if (!n.read_at) {
        router.post(`/notifications/${n.id}/read`, {}, {
            preserveScroll: true, preserveState: true,
            onFinish: () => { if (target) router.visit(target); },
        });
    } else if (target) {
        router.visit(target);
    }
}
function toggleRead(n) {
    if (!n.read_at) router.post(`/notifications/${n.id}/read`, {}, { preserveScroll: true, preserveState: true });
}
function markAll() { router.post('/notifications/read-all', {}, { preserveScroll: true }); }

const statusChips = [['all', 'الكل'], ['unread', 'غير مقروءة'], ['read', 'المقروءة']];
const priorityChips = [['all', 'الكل'], ['urgent', 'عاجل'], ['important', 'مهم'], ['normal', 'عادي']];
const periodChips = [['all', 'الكل'], ['today', 'اليوم'], ['7d', '7 أيام'], ['30d', '30 يوماً']];
</script>

<template>
    <Head title="الإشعارات" />
    <AppShell>
        <div class="mx-auto max-w-4xl space-y-4">
            <!-- Gradient hero -->
            <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-elevated" style="background-image: var(--gradient-hero);">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(closest-side at 75% 20%, rgba(255,255,255,.4), transparent);"></div>
                <div class="relative flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="flex size-12 items-center justify-center rounded-xl bg-white/15 backdrop-blur-sm">
                            <Bell class="size-6" />
                        </div>
                        <div>
                            <p class="text-xs text-white/60">مركز التنبيهات</p>
                            <h1 class="mt-0.5 text-2xl font-bold">الإشعارات</h1>
                            <p class="mt-1 text-sm text-white/80">
                                {{ unreadCount > 0 ? `${num(unreadCount)} إشعار غير مقروء` : 'تمت قراءة جميع الإشعارات' }}
                                <span class="text-white/60">· إجمالي {{ num(totalCount) }}</span>
                            </p>
                        </div>
                    </div>
                    <Button v-if="unreadCount > 0" @click="markAll" class="bg-white/15 text-white hover:bg-white/25 backdrop-blur-sm">
                        <CheckCheck class="size-4" /> تعليم الكل كمقروء
                    </Button>
                </div>
            </div>

            <!-- Filter panel -->
            <Card class="space-y-2.5 p-3">
                <div class="relative">
                    <Search class="pointer-events-none absolute left-3 top-1/2 z-10 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="q" label="ابحث في العنوان أو النص…" />
                </div>
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-muted-foreground"><Filter class="size-3" /> الحالة:</span>
                    <button v-for="[k, l] in statusChips" :key="k" type="button" @click="reload({ filter: k })"
                        :class="['h-6 rounded-full px-2.5 text-[11px] font-bold transition-colors',
                            filters.filter === k ? 'bg-primary text-primary-foreground' : 'border border-border bg-card text-muted-foreground hover:text-foreground']">{{ l }}</button>
                </div>
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-[11px] font-bold text-muted-foreground">النوع:</span>
                    <button type="button" @click="reload({ type: 'all' })"
                        :class="['h-6 rounded-full px-2.5 text-[11px] font-bold transition-colors',
                            filters.type === 'all' ? 'bg-primary text-primary-foreground' : 'border border-border bg-card text-muted-foreground hover:text-foreground']">الكل</button>
                    <button v-for="(l, k) in TYPE_LABEL" :key="k" type="button" @click="reload({ type: k })"
                        :class="['inline-flex h-6 items-center gap-1 rounded-full px-2.5 text-[11px] font-bold transition-colors',
                            filters.type === k ? 'bg-primary text-primary-foreground' : 'border border-border bg-card text-muted-foreground hover:text-foreground']">
                        {{ l }}<span v-if="typeCounts[k]" class="tabular-nums opacity-70">({{ typeCounts[k] }})</span>
                    </button>
                </div>
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-[11px] font-bold text-muted-foreground">الأهمية:</span>
                    <button v-for="[k, l] in priorityChips" :key="k" type="button" @click="reload({ priority: k })"
                        :class="['h-6 rounded-full px-2.5 text-[11px] font-bold transition-colors',
                            filters.priority === k ? 'bg-primary text-primary-foreground' : 'border border-border bg-card text-muted-foreground hover:text-foreground']">{{ l }}</button>
                </div>
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-[11px] font-bold text-muted-foreground">الفترة:</span>
                    <button v-for="[k, l] in periodChips" :key="k" type="button" @click="reload({ period: k })"
                        :class="['h-6 rounded-full px-2.5 text-[11px] font-bold transition-colors',
                            filters.period === k ? 'bg-primary text-primary-foreground' : 'border border-border bg-card text-muted-foreground hover:text-foreground']">{{ l }}</button>
                </div>
                <button v-if="activeFilters > 0" type="button" @click="resetFilters"
                    class="inline-flex items-center gap-1 text-[11px] text-muted-foreground hover:text-foreground">
                    <RotateCcw class="size-3" /> إعادة تعيين ({{ activeFilters }})
                </button>
            </Card>

            <!-- Notification list -->
            <Card class="divide-y divide-border/60 overflow-hidden p-0">
                <template v-if="notifications.data.length">
                    <div v-for="n in notifications.data" :key="n.id"
                        class="group flex cursor-pointer gap-3 border-r-4 px-4 py-3 transition-colors"
                        :class="[metaOf(n.type).border, !n.read_at ? 'bg-primary/[0.03]' : 'opacity-75', 'hover:bg-muted/40']"
                        @click="open(n)">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl" :class="metaOf(n.type).tone">
                            <component :is="metaOf(n.type).icon" class="size-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="flex-1 text-sm leading-snug [overflow-wrap:anywhere]" :class="!n.read_at ? 'font-bold' : 'font-semibold text-foreground/80'">
                                    {{ cleanTitle(n.title) }}
                                </h3>
                                <div class="mt-0.5 flex shrink-0 items-center gap-1.5">
                                    <span v-if="!n.read_at" class="size-2 rounded-full" :class="n.priority === 'urgent' ? 'bg-destructive' : 'bg-primary'"></span>
                                    <span class="whitespace-nowrap text-[10px] tabular-nums text-muted-foreground">{{ timeAgoAr(n.created_at) }}</span>
                                </div>
                            </div>
                            <p v-if="n.body" class="mt-1 line-clamp-2 text-xs text-muted-foreground [overflow-wrap:anywhere]">{{ n.body }}</p>
                            <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                <span class="rounded bg-muted/70 px-1.5 py-0.5 text-[9px] font-bold text-muted-foreground">{{ TYPE_LABEL[n.type] ?? n.type }}</span>
                                <span v-if="n.priority === 'urgent'" class="rounded bg-destructive/10 px-1.5 py-0.5 text-[9px] font-bold text-destructive">عاجل</span>
                                <span v-else-if="n.priority === 'important'" class="rounded bg-primary/10 px-1.5 py-0.5 text-[9px] font-bold text-primary">مهم</span>
                            </div>
                        </div>
                        <button v-if="!n.read_at" type="button" title="تعليم كمقروء" @click.stop="toggleRead(n)"
                            class="flex size-8 shrink-0 items-center justify-center self-center rounded-full border border-border bg-card text-primary opacity-0 transition hover:bg-primary hover:text-primary-foreground group-hover:opacity-100">
                            <CheckCheck class="size-4" />
                        </button>
                    </div>
                </template>
                <div v-else class="py-16 text-center">
                    <Inbox class="mx-auto size-12 text-muted-foreground/40" />
                    <p class="mt-2 text-sm text-muted-foreground">{{ activeFilters || filters.q ? 'لا نتائج مطابقة' : 'لا توجد إشعارات' }}</p>
                </div>
            </Card>

            <div v-if="notifications.last_page > 1" class="flex flex-wrap justify-center gap-1">
                <Link v-for="link in notifications.links" :key="link.label" :href="link.url || '#'" v-html="link.label"
                    preserve-scroll
                    class="min-w-9 rounded-md border border-border px-3 py-1.5 text-center text-sm transition-colors"
                    :class="[link.active ? 'border-primary bg-primary text-primary-foreground' : 'bg-card hover:bg-muted', !link.url && 'pointer-events-none opacity-40']" />
            </div>
        </div>
    </AppShell>
</template>
