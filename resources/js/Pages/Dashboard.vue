<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import KpiCard from '@/Components/KpiCard.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PriorityBadge from '@/Components/PriorityBadge.vue';
import ServiceStatusPill from '@/Components/dashboard/ServiceStatusPill.vue';
import { num } from '@/lib/utils';
import { timeAgoAr } from '@/lib/date';
import { REQUEST_STATUS, REQUEST_PRIORITY, statusLabel } from '@/lib/labels';
import {
    Inbox, Clock, CheckCircle2, Star, AlertTriangle, ShieldCheck, Reply, CalendarClock,
    Lightbulb, PlusCircle, CalendarDays, ClipboardList, ArrowLeft, ChevronLeft, Bell,
    MessageCircle, Sparkles, Zap, Gauge, UserX, Flame, Phone, Mail, Users, Activity,
    ListChecks,
} from 'lucide-vue-next';

const props = defineProps({
    branch: { type: String, default: 'staff' },
    name: { type: String, default: '' },
    // customer
    stats: { type: Object, default: () => ({}) },
    actionItems: { type: Array, default: () => [] },
    actionCount: { type: Number, default: 0 },
    highlight: { type: Object, default: null },
    last3: { type: Array, default: () => [] },
    appointments: { type: Array, default: () => [] },
    recentUpdates: { type: Array, default: () => [] },
    support: { type: Object, default: () => ({}) },
    // staff
    isAdmin: { type: Boolean, default: false },
    kpis: { type: Object, default: () => ({}) },
    statusBreakdown: { type: Array, default: () => [] },
    priorityBreakdown: { type: Array, default: () => [] },
    requiresAction: { type: Object, default: () => ({}) },
    activity: { type: Array, default: () => [] },
    recent: { type: Array, default: () => [] },
});

const greeting = computed(() => {
    const h = new Date().getHours();
    if (h < 12) return 'صباح الخير';
    if (h < 18) return 'مساء النور';
    return 'مساء الخير';
});

// ---- Customer helpers ----
const ACTION_ICON = { reply: Reply, star: Star, 'calendar-clock': CalendarClock, lightbulb: Lightbulb };
const ACTION_CHIP = {
    destructive: 'bg-destructive/10 text-destructive border-destructive/25',
    warning: 'bg-warning/10 text-warning border-warning/30',
    accent: 'bg-accent/10 text-accent border-accent/25',
    primary: 'bg-primary/10 text-primary border-primary/25',
};
const ACTION_BTN = {
    destructive: 'destructive', warning: 'warning', accent: 'accent', primary: 'default',
};

const summaryStats = computed(() => [
    { label: 'نشطة', value: props.stats.open ?? 0, tone: 'text-info', dot: 'bg-info' },
    { label: 'بانتظارك', value: props.stats.awaiting_you ?? 0, tone: 'text-warning', dot: 'bg-warning', pulse: (props.stats.awaiting_you ?? 0) > 0 },
    { label: 'ضمن الوقت', value: props.stats.on_track ?? 0, tone: 'text-success', dot: 'bg-success' },
    { label: 'متأخرة', value: props.stats.overdue ?? 0, tone: 'text-destructive', dot: 'bg-destructive', pulse: (props.stats.overdue ?? 0) > 0 },
    { label: 'مكتملة', value: props.stats.completed ?? 0, tone: 'text-foreground', dot: 'bg-muted-foreground' },
]);

const channels = computed(() => {
    const c = props.support?.support_channels ?? {};
    return [
        c.phone && { href: `tel:${c.phone}`, icon: Phone, label: 'هاتف الدعم', value: c.phone, tone: 'bg-primary/10 text-primary' },
        c.whatsapp && { href: `https://wa.me/${String(c.whatsapp).replace(/\D/g, '')}`, icon: MessageCircle, label: 'واتساب', value: c.whatsapp, tone: 'bg-success/10 text-success' },
        c.email && { href: `mailto:${c.email}`, icon: Mail, label: 'البريد الإلكتروني', value: c.email, tone: 'bg-info/10 text-info' },
    ].filter(Boolean);
});

const updateMeta = (kind) => ({
    staff_replied: { icon: MessageCircle, label: 'رد جديد', tone: 'bg-info/10 text-info' },
    you_replied: { icon: Reply, label: 'ردّك', tone: 'bg-muted text-muted-foreground' },
    status_change: { icon: Sparkles, label: 'تحديث حالة', tone: 'bg-primary/10 text-primary' },
}[kind] ?? { icon: Bell, label: 'تحديث', tone: 'bg-muted text-muted-foreground' });

const statusUpdateLabel = (val) => statusLabel(REQUEST_STATUS, val).label;

function fmtApptDate(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('ar-SA-u-ca-gregory', {
        timeZone: 'Asia/Riyadh', weekday: 'short', day: 'numeric', month: 'short',
    });
}
function fmtApptTime(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleTimeString('ar-SA-u-ca-gregory', {
        timeZone: 'Asia/Riyadh', hour: '2-digit', minute: '2-digit',
    });
}

// ---- Staff helpers ----
const kpiCards = computed(() => [
    { label: 'طلبات نشطة', value: props.kpis.active ?? 0, icon: Inbox, tone: 'primary' },
    { label: 'بانتظار المعالجة', value: props.kpis.pending ?? 0, icon: Clock, tone: 'warning' },
    { label: 'متأخرة', value: props.kpis.overdue ?? 0, icon: AlertTriangle, tone: 'destructive' },
    { label: 'مكتملة', value: props.kpis.completed ?? 0, icon: CheckCircle2, tone: 'success' },
    { label: 'رضا العملاء', value: props.kpis.csat ?? '—', icon: Star, tone: 'accent', num: false, hint: props.kpis.rating_count ? `${props.kpis.rating_count} تقييم` : 'لا تقييمات' },
    { label: 'الالتزام بـ SLA', value: props.kpis.sla != null ? `${props.kpis.sla}%` : '—', icon: Gauge, tone: props.kpis.sla == null ? 'muted' : (props.kpis.sla >= 90 ? 'success' : props.kpis.sla >= 75 ? 'warning' : 'destructive'), num: false },
]);

const toneBar = {
    info: 'bg-info', success: 'bg-success', warning: 'bg-warning',
    destructive: 'bg-destructive', accent: 'bg-accent', primary: 'bg-primary',
    muted: 'bg-muted-foreground/40',
};
const statusBars = computed(() => {
    const max = Math.max(1, ...props.statusBreakdown.map((s) => s.value));
    return props.statusBreakdown.map((s) => {
        const m = statusLabel(REQUEST_STATUS, s.key);
        return { key: s.key, label: m.label, value: s.value, pct: Math.round((s.value / max) * 100), bar: toneBar[m.tone] ?? toneBar.muted };
    });
});
const priorityBars = computed(() => {
    const max = Math.max(1, ...props.priorityBreakdown.map((p) => p.value));
    return props.priorityBreakdown.map((p) => {
        const m = statusLabel(REQUEST_PRIORITY, p.key);
        return { key: p.key, label: m.label, value: p.value, pct: Math.round((p.value / max) * 100), bar: toneBar[m.tone] ?? toneBar.muted };
    });
});

const ACTION_LABELS = {
    created: 'أنشأ الطلب', comment: 'أضاف تعليقاً', internal_comment: 'تعليق داخلي',
    status_change: 'غيّر الحالة', changed_status: 'غيّر الحالة', changed_priority: 'غيّر الأولوية',
    changed_assigned_to: 'أعاد الإسناد', assigned_self: 'أسند لنفسه', closed: 'أغلق الطلب',
    reopened: 'أعاد الفتح', escalated: 'صعّد الطلب', returned_to_customer: 'أعاد للعميل',
    resumed: 'استأنف المعالجة', stage_transition: 'انتقل لمرحلة', rated: 'قُيّم الطلب',
    attachment_added: 'أضاف مرفقاً', task_created: 'أضاف مهمة',
};
const actionLabel = (a) => ACTION_LABELS[a] ?? 'حدّث الطلب';

const heroSubtitle = computed(() => props.isAdmin
    ? 'مركز قرار مدير النظام — نظرة لحظية على أداء الطلبات وصحّة التشغيل.'
    : 'نظرة عامة على طلبات العملاء وما يحتاج تدخلك الآن.');
</script>

<template>
    <Head title="الرئيسية" />
    <AppShell>
        <!-- ============================ CUSTOMER ============================ -->
        <div v-if="branch === 'customer'" class="glass-stage space-y-5">
            <!-- 1. Welcome strip -->
            <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-elevated" style="background-image: var(--gradient-hero);">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(closest-side at 78% 20%, rgba(255,255,255,.4), transparent);"></div>
                <div class="relative flex flex-wrap items-end justify-between gap-4">
                    <div class="min-w-0">
                        <div class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-white/70">
                            <span class="relative flex size-1.5">
                                <span class="absolute inline-flex size-full animate-ping rounded-full bg-white/80"></span>
                                <span class="relative inline-flex size-1.5 rounded-full bg-white"></span>
                            </span>
                            لوحتك الشخصية
                        </div>
                        <h1 class="mt-1.5 text-2xl font-bold sm:text-3xl">{{ greeting }}، {{ name }}</h1>
                        <p class="mt-1 text-sm text-white/80">هذا ملخص حسابك ومواعيدك مع فريق العمل.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <Button href="/requests/new" class="bg-white/15 text-white backdrop-blur-sm hover:bg-white/25">
                            <PlusCircle class="size-4" /> طلب جديد
                        </Button>
                        <Button href="/appointments/new" variant="outline" class="border-white/30 bg-white/10 text-white backdrop-blur-sm hover:bg-white/20">
                            <CalendarDays class="size-4" /> حجز موعد
                        </Button>
                    </div>
                </div>
            </div>

            <!-- 2. Action required -->
            <Card class="overflow-hidden p-0">
                <div class="flex items-center justify-between gap-3 border-b border-border bg-gradient-to-l from-primary/[0.04] to-transparent px-4 py-3 md:px-5">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <span :class="['flex size-9 shrink-0 items-center justify-center rounded-xl', actionCount ? 'bg-destructive/10 text-destructive' : 'bg-success/10 text-success']">
                            <AlertTriangle v-if="actionCount" class="size-4" />
                            <CheckCircle2 v-else class="size-4" />
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-[15px] font-bold leading-tight">يتطلب إجراء منك</h2>
                            <p class="mt-0.5 text-[11.5px] text-muted-foreground">
                                {{ actionCount ? `${num(actionCount)} عناصر بانتظار تصرّفك الآن` : 'كل شيء تحت السيطرة — لا توجد إجراءات مطلوبة منك' }}
                            </p>
                        </div>
                    </div>
                    <Badge v-if="actionCount" variant="destructive" class="bg-destructive/10 text-destructive">{{ num(actionCount) }}</Badge>
                </div>

                <div v-if="!actionItems.length" class="px-6 py-8 text-center">
                    <span class="mb-3 inline-flex size-12 items-center justify-center rounded-2xl bg-success/10 text-success">
                        <ShieldCheck class="size-6" />
                    </span>
                    <p class="text-sm font-semibold text-foreground">لا توجد إجراءات مطلوبة منك حالياً</p>
                    <p class="mt-1 text-xs text-muted-foreground">جميع الأمور تسير بشكل جيد</p>
                </div>
                <ul v-else class="divide-y divide-border">
                    <li v-for="it in actionItems" :key="it.key" class="group flex items-center gap-3 px-4 py-3 transition hover:bg-muted/40 md:px-5">
                        <span :class="['flex size-9 shrink-0 items-center justify-center rounded-lg border', ACTION_CHIP[it.accent]]">
                            <component :is="ACTION_ICON[it.icon] ?? Bell" class="size-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="mb-0.5 flex flex-wrap items-center gap-1.5 text-[10.5px]">
                                <span :class="['rounded px-1.5 py-0.5 font-mono font-semibold', ACTION_CHIP[it.accent]]">{{ it.ref }}</span>
                                <span class="text-muted-foreground">·</span>
                                <span class="truncate text-muted-foreground">{{ it.hint }}</span>
                            </div>
                            <div class="line-clamp-1 text-[13.5px] font-semibold leading-snug transition group-hover:text-primary">{{ it.title }}</div>
                        </div>
                        <Button :href="it.url" size="sm" :variant="ACTION_BTN[it.accent]" class="shrink-0">
                            {{ it.cta }} <ChevronLeft class="size-3.5" />
                        </Button>
                    </li>
                </ul>
            </Card>

            <!-- 3. Requests summary -->
            <Card class="overflow-hidden p-0">
                <div class="flex items-center justify-between gap-3 border-b border-border px-4 py-3 md:px-5">
                    <div class="flex items-center gap-2.5">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary"><ClipboardList class="size-4" /></span>
                        <h2 class="text-[15px] font-bold">ملخص طلباتك</h2>
                    </div>
                    <Button href="/requests" variant="ghost" size="sm">كل الطلبات <ArrowLeft class="size-3.5" /></Button>
                </div>

                <div class="grid grid-cols-2 divide-x divide-x-reverse divide-y divide-border bg-muted/20 md:grid-cols-5 md:divide-y-0">
                    <div v-for="s in summaryStats" :key="s.label" class="px-4 py-3.5">
                        <div class="flex items-center gap-1.5 text-[10.5px] font-semibold uppercase tracking-wide text-muted-foreground">
                            <span :class="['size-1.5 rounded-full', s.dot, s.pulse ? 'animate-pulse' : '']"></span>
                            {{ s.label }}
                        </div>
                        <div :class="['mt-1 text-2xl font-extrabold tabular-nums', s.tone]">{{ num(s.value) }}</div>
                    </div>
                </div>

                <div v-if="!last3.length" class="px-6 py-10 text-center">
                    <p class="text-sm text-muted-foreground">لا توجد طلبات بعد</p>
                    <Button href="/requests/new" size="sm" class="mt-3"><PlusCircle class="size-4" /> أنشئ طلبك الأول</Button>
                </div>
                <ul v-else class="divide-y divide-border">
                    <li v-for="r in last3" :key="r.id">
                        <Link :href="`/requests/${r.id}`" class="group grid grid-cols-12 items-center gap-3 px-4 py-3 transition hover:bg-muted/40 md:px-5">
                            <div class="col-span-12 min-w-0 md:col-span-6">
                                <div class="mb-1 flex items-center gap-1.5 text-[10.5px]">
                                    <span class="rounded bg-muted px-1.5 py-0.5 font-mono font-semibold text-foreground/80">{{ r.request_number }}</span>
                                    <span v-if="r.category" class="truncate text-muted-foreground">· {{ r.category }}</span>
                                </div>
                                <div class="line-clamp-1 text-[13.5px] font-semibold leading-snug transition group-hover:text-primary">{{ r.title }}</div>
                            </div>
                            <div class="col-span-6 md:col-span-3"><ServiceStatusPill :status="r.service_status" /></div>
                            <div class="col-span-6 inline-flex items-center justify-end gap-1 text-[11px] text-muted-foreground md:col-span-3 md:justify-start">
                                <Clock class="size-3" /> آخر تحديث {{ timeAgoAr(r.updated_at) }}
                            </div>
                        </Link>
                    </li>
                </ul>
            </Card>

            <!-- 4. Highlight + appointments -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                <Card v-if="highlight" class="relative overflow-hidden border-primary/30 p-0 lg:col-span-7">
                    <div class="absolute inset-x-0 top-0 h-[3px] bg-gradient-to-l from-primary to-accent"></div>
                    <div class="p-4 md:p-5">
                        <div class="mb-3 inline-flex items-center gap-1.5 text-[10.5px] font-semibold uppercase tracking-[0.16em] text-primary">
                            <Zap class="size-3" /> أهم طلب نشط لديك
                        </div>
                        <div class="mb-3 flex flex-wrap items-center gap-1.5">
                            <span class="rounded bg-primary/10 px-2 py-0.5 font-mono text-[11px] font-bold text-primary">{{ highlight.request_number }}</span>
                            <Badge v-if="highlight.category" variant="outline">{{ highlight.category }}</Badge>
                            <ServiceStatusPill :status="highlight.service_status" />
                        </div>
                        <h3 class="line-clamp-2 text-[17px] font-bold leading-snug">{{ highlight.title }}</h3>

                        <div class="mt-3 grid grid-cols-2 gap-2 md:grid-cols-3">
                            <div class="rounded-lg border border-border bg-muted/40 px-3 py-2">
                                <div class="flex items-center gap-1 text-[10px] font-medium text-muted-foreground"><Sparkles class="size-3" /> الحالة</div>
                                <div class="mt-0.5 truncate text-[12.5px] font-semibold">{{ statusUpdateLabel(highlight.status) }}</div>
                            </div>
                            <div class="rounded-lg border border-border bg-muted/40 px-3 py-2">
                                <div class="flex items-center gap-1 text-[10px] font-medium text-muted-foreground"><Clock class="size-3" /> آخر تحديث</div>
                                <div class="mt-0.5 truncate text-[12.5px] font-semibold">{{ timeAgoAr(highlight.updated_at) }}</div>
                            </div>
                            <div class="rounded-lg border border-border bg-muted/40 px-3 py-2">
                                <div class="flex items-center gap-1 text-[10px] font-medium text-muted-foreground"><CalendarDays class="size-3" /> الإنشاء</div>
                                <div class="mt-0.5 truncate text-[12.5px] font-semibold">{{ timeAgoAr(highlight.created_at) }}</div>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-2 border-t border-border pt-3">
                            <span class="text-[11px] text-muted-foreground">
                                {{ highlight.status === 'awaiting_customer' ? 'المطلوب منك: استكمال البيانات' : 'فريقنا يعمل على طلبك حالياً' }}
                            </span>
                            <Button :href="`/requests/${highlight.id}`" size="sm">عرض التفاصيل <ChevronLeft class="size-3.5" /></Button>
                        </div>
                    </div>
                </Card>

                <Card :class="['overflow-hidden p-0', highlight ? 'lg:col-span-5' : 'lg:col-span-12']">
                    <div class="flex items-center justify-between gap-3 border-b border-border px-4 py-3">
                        <div class="flex items-center gap-2.5">
                            <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary"><CalendarClock class="size-4" /></span>
                            <div>
                                <h2 class="text-[14.5px] font-bold leading-tight">مواعيدك القادمة</h2>
                                <p class="mt-0.5 text-[10.5px] text-muted-foreground">المؤكدة فقط · خلال ١٤ يوم</p>
                            </div>
                        </div>
                        <Button href="/appointments" variant="ghost" size="sm">الكل <ArrowLeft class="size-3" /></Button>
                    </div>
                    <div v-if="!appointments.length" class="px-4 py-8 text-center">
                        <span class="mb-2 inline-flex size-10 items-center justify-center rounded-xl bg-muted text-muted-foreground"><CalendarDays class="size-4" /></span>
                        <p class="text-[12.5px] text-muted-foreground">لا توجد مواعيد قادمة</p>
                        <Button href="/appointments/new" variant="link" size="sm">احجز موعداً جديداً</Button>
                    </div>
                    <div v-else class="space-y-2 p-3">
                        <Link v-for="a in appointments" :key="a.id" :href="`/appointments/${a.id}`"
                            class="group flex items-stretch gap-2.5 rounded-lg border border-primary/20 bg-card p-2.5 transition hover:border-primary/60 hover:shadow-md">
                            <div class="flex w-14 shrink-0 flex-col items-center justify-center rounded-md bg-primary/10 py-1 text-primary">
                                <span class="text-[9px] font-bold uppercase leading-none">{{ fmtApptDate(a.starts_at).split(' ')[0] }}</span>
                                <span class="my-0.5 text-[15px] font-black leading-none tabular-nums">{{ new Date(a.starts_at).getDate() }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <Badge variant="success" class="bg-primary/15 text-primary">مؤكد</Badge>
                                <div class="mt-0.5 line-clamp-1 text-[12.5px] font-semibold leading-snug transition group-hover:text-primary">{{ a.type }}</div>
                                <div class="mt-0.5 flex items-center gap-1 text-[10.5px] text-muted-foreground">
                                    <Clock class="size-2.5" />
                                    <span class="font-semibold tabular-nums text-foreground/80">{{ fmtApptTime(a.starts_at) }}</span>
                                    <span>· {{ fmtApptDate(a.starts_at) }}</span>
                                    <span v-if="a.meeting_url" class="text-primary">· عن بُعد</span>
                                </div>
                            </div>
                        </Link>
                    </div>
                </Card>
            </div>

            <!-- 5. Recent updates + support -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                <Card class="overflow-hidden p-0 lg:col-span-8">
                    <div class="flex items-center gap-2.5 border-b border-border px-4 py-3 md:px-5">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-foreground/5 text-foreground"><Bell class="size-4" /></span>
                        <div>
                            <h2 class="text-[14.5px] font-bold leading-tight">آخر التحديثات</h2>
                            <p class="mt-0.5 text-[10.5px] text-muted-foreground">آخر ٣٠ يوم</p>
                        </div>
                    </div>
                    <div v-if="!recentUpdates.length" class="px-6 py-10 text-center">
                        <p class="text-sm text-muted-foreground">لا توجد تحديثات حديثة</p>
                    </div>
                    <ol v-else class="relative px-4 py-3 md:px-5">
                        <div class="absolute bottom-3 top-3 right-[2rem] w-px bg-border md:right-[2.4rem]"></div>
                        <li v-for="u in recentUpdates" :key="u.id" class="group relative py-2 pr-10">
                            <Link :href="`/requests/${u.request_id}`" class="block">
                                <span :class="['absolute right-3 top-2 flex size-7 items-center justify-center rounded-lg ring-4 ring-card', updateMeta(u.kind).tone]">
                                    <component :is="updateMeta(u.kind).icon" class="size-3.5" />
                                </span>
                                <div class="flex flex-wrap items-center gap-1.5 text-[10.5px] text-muted-foreground">
                                    <Badge variant="outline" class="px-1.5 py-0 text-[9px]">{{ updateMeta(u.kind).label }}</Badge>
                                    <span class="rounded bg-muted px-1 font-mono">{{ u.request_number }}</span>
                                    <span>·</span>
                                    <span>{{ timeAgoAr(u.created_at) }}</span>
                                </div>
                                <div class="mt-0.5 truncate text-[13px] font-semibold transition group-hover:text-primary">{{ u.title }}</div>
                                <div class="line-clamp-1 text-[11px] text-muted-foreground">
                                    {{ u.kind === 'status_change' ? `تحديث الحالة إلى «${statusUpdateLabel(u.summary)}»` : u.summary }}
                                </div>
                            </Link>
                        </li>
                    </ol>
                </Card>

                <div class="lg:col-span-4">
                    <Card class="overflow-hidden p-0">
                        <div class="flex items-center gap-2 border-b border-border bg-gradient-to-l from-primary/[0.04] to-transparent px-4 py-3">
                            <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary"><MessageCircle class="size-4" /></span>
                            <h2 class="text-[14.5px] font-bold">معلومات الدعم</h2>
                        </div>
                        <div class="space-y-3 p-3">
                            <div v-if="support?.business_hours" class="space-y-2 rounded-xl border border-border bg-muted/30 p-3">
                                <div class="flex items-center gap-2 text-[11px] text-muted-foreground"><CalendarClock class="size-3.5" /> ساعات العمل</div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-muted-foreground">التوقيت</span>
                                    <span class="font-mono font-semibold" dir="ltr">{{ support.business_hours.start ?? '—' }} – {{ support.business_hours.end ?? '—' }}</span>
                                </div>
                            </div>
                            <p v-if="!channels.length" class="px-1 text-xs text-muted-foreground">لم تُضَف قنوات تواصل بعد.</p>
                            <div v-else class="space-y-1.5">
                                <a v-for="(it, i) in channels" :key="i" :href="it.href"
                                    class="group flex items-center gap-3 rounded-xl border border-border p-2.5 transition hover:border-primary/30 hover:bg-muted/40">
                                    <span :class="['flex size-9 shrink-0 items-center justify-center rounded-lg', it.tone]"><component :is="it.icon" class="size-4" /></span>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-[11px] text-muted-foreground">{{ it.label }}</div>
                                        <div class="truncate font-mono text-sm font-semibold" dir="ltr">{{ it.value }}</div>
                                    </div>
                                    <ChevronLeft class="size-4 text-muted-foreground/50 opacity-0 transition group-hover:opacity-100" />
                                </a>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>

            <!-- 6. Quick shortcuts -->
            <Card class="p-0">
                <div class="border-b border-border px-4 py-3 md:px-5">
                    <h2 class="inline-flex items-center gap-2 text-[14.5px] font-bold"><Sparkles class="size-4 text-accent" /> اختصارات سريعة</h2>
                </div>
                <div class="grid grid-cols-2 gap-2 p-3 md:grid-cols-3 lg:grid-cols-6">
                    <Link v-for="sc in [
                        { to: '/requests/new', icon: PlusCircle, label: 'طلب جديد', tone: 'bg-primary/10 text-primary' },
                        { to: '/requests', icon: ClipboardList, label: 'طلباتي', tone: 'bg-info/10 text-info' },
                        { to: '/appointments/new', icon: CalendarDays, label: 'حجز موعد', tone: 'bg-primary/10 text-primary' },
                        { to: '/appointments', icon: CalendarClock, label: 'مواعيدي', tone: 'bg-info/10 text-info' },
                        { to: '/suggestions', icon: Lightbulb, label: 'المقترحات', tone: 'bg-accent/10 text-accent' },
                        { to: '/suggestions/new', icon: Sparkles, label: 'شاركنا فكرتك', tone: 'bg-accent/10 text-accent' },
                    ]" :key="sc.to" :href="sc.to"
                        class="group flex flex-col items-center gap-2 rounded-xl border border-border p-3 transition hover:-translate-y-0.5 hover:border-primary/40 hover:bg-muted/40">
                        <span :class="['flex size-10 items-center justify-center rounded-xl transition group-hover:scale-110', sc.tone]"><component :is="sc.icon" class="size-4" /></span>
                        <span class="text-center text-[11.5px] font-semibold leading-tight text-foreground/80 transition group-hover:text-primary">{{ sc.label }}</span>
                    </Link>
                </div>
            </Card>
        </div>

        <!-- ============================= STAFF ============================= -->
        <div v-else class="glass-stage space-y-6">
            <!-- Hero -->
            <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-elevated sm:p-8" style="background-image: var(--gradient-hero);">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(closest-side at 80% 20%, rgba(255,255,255,.4), transparent);"></div>
                <div class="relative flex flex-wrap items-start justify-between gap-6">
                    <div class="min-w-0">
                        <div class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-1 text-[10px] font-medium backdrop-blur-sm">
                            <Sparkles class="size-3" /> {{ isAdmin ? 'مركز قرار مدير النظام' : 'لوحة عمليات الدعم' }}
                        </div>
                        <h1 class="mt-3 flex items-center gap-2 text-2xl font-bold sm:text-3xl">
                            <Activity class="size-6 shrink-0" /> أهلاً، {{ name }}
                        </h1>
                        <p class="mt-1.5 max-w-xl text-sm text-white/80">{{ heroSubtitle }}</p>
                    </div>
                    <div class="grid grid-cols-3 gap-2 md:gap-3">
                        <div class="min-w-[110px] rounded-xl border border-white/20 bg-white/10 px-3 py-2.5 backdrop-blur-sm">
                            <div class="flex items-center gap-1.5 text-[10px] font-medium text-white/80"><Gauge class="size-3" /> الالتزام SLA</div>
                            <div class="mt-0.5 text-xl font-bold tabular-nums">{{ kpis.sla != null ? `${kpis.sla}%` : '—' }}</div>
                        </div>
                        <div class="min-w-[110px] rounded-xl border border-white/20 bg-white/10 px-3 py-2.5 backdrop-blur-sm">
                            <div class="flex items-center gap-1.5 text-[10px] font-medium text-white/80"><Star class="size-3" /> رضا العملاء</div>
                            <div class="mt-0.5 text-xl font-bold tabular-nums">{{ kpis.csat ?? '—' }}<span class="text-sm text-white/60">/5</span></div>
                        </div>
                        <div class="min-w-[110px] rounded-xl border border-white/20 bg-white/10 px-3 py-2.5 backdrop-blur-sm">
                            <div class="flex items-center gap-1.5 text-[10px] font-medium text-white/80"><AlertTriangle class="size-3" /> متأخرة</div>
                            <div class="mt-0.5 text-xl font-bold tabular-nums">{{ num(kpis.overdue ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI ribbon -->
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
                <KpiCard v-for="k in kpiCards" :key="k.label" :label="k.label" :value="k.value" :icon="k.icon" :tone="k.tone" :hint="k.hint ?? ''" :format-number="k.num !== false" />
            </div>

            <!-- Requires action -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <!-- Overdue -->
                <Card class="overflow-hidden border-destructive/30 p-0">
                    <div class="flex items-center gap-2 border-b border-border px-4 py-3">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-destructive/10 text-destructive"><AlertTriangle class="size-4" /></span>
                        <h2 class="text-[15px] font-bold">طلبات متأخرة</h2>
                        <Badge variant="destructive" class="bg-destructive/10 text-destructive">{{ num(kpis.overdue ?? 0) }}</Badge>
                    </div>
                    <div v-if="!requiresAction.overdue?.length" class="px-6 py-8 text-center text-sm text-muted-foreground">لا توجد طلبات متأخرة</div>
                    <ul v-else class="divide-y divide-border">
                        <li v-for="r in requiresAction.overdue" :key="r.id">
                            <Link :href="`/requests/${r.id}`" class="flex items-center justify-between gap-3 px-4 py-3 transition hover:bg-muted/40">
                                <div class="min-w-0 flex-1">
                                    <div class="font-mono text-xs text-muted-foreground">{{ r.request_number }}</div>
                                    <div class="truncate font-medium">{{ r.title }}</div>
                                    <div class="mt-0.5 truncate text-xs text-destructive">تأخر منذ {{ timeAgoAr(r.due_at) }}</div>
                                </div>
                                <PriorityBadge :priority="r.priority" />
                            </Link>
                        </li>
                    </ul>
                </Card>

                <!-- Unassigned -->
                <Card class="overflow-hidden border-warning/30 p-0">
                    <div class="flex items-center gap-2 border-b border-border px-4 py-3">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-warning/10 text-warning"><UserX class="size-4" /></span>
                        <h2 class="text-[15px] font-bold">غير مُسندة</h2>
                        <Badge variant="warning" class="bg-warning/15 text-warning">{{ num(kpis.unassigned ?? 0) }}</Badge>
                    </div>
                    <div v-if="!requiresAction.unassigned?.length" class="px-6 py-8 text-center text-sm text-muted-foreground">كل الطلبات مُسندة</div>
                    <ul v-else class="divide-y divide-border">
                        <li v-for="r in requiresAction.unassigned" :key="r.id">
                            <Link :href="`/requests/${r.id}`" class="flex items-center justify-between gap-3 px-4 py-3 transition hover:bg-muted/40">
                                <div class="min-w-0 flex-1">
                                    <div class="font-mono text-xs text-muted-foreground">{{ r.request_number }}</div>
                                    <div class="truncate font-medium">{{ r.title }}</div>
                                    <div class="mt-0.5 text-xs text-muted-foreground">منذ {{ timeAgoAr(r.created_at) }}</div>
                                </div>
                                <PriorityBadge :priority="r.priority" />
                            </Link>
                        </li>
                    </ul>
                </Card>

                <!-- Escalated -->
                <Card class="overflow-hidden border-destructive/30 p-0">
                    <div class="flex items-center gap-2 border-b border-border px-4 py-3">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-destructive/10 text-destructive"><Flame class="size-4" /></span>
                        <h2 class="text-[15px] font-bold">مُصعّدة</h2>
                        <Badge variant="destructive" class="bg-destructive/10 text-destructive">{{ num(kpis.escalated ?? 0) }}</Badge>
                    </div>
                    <div v-if="!requiresAction.escalated?.length" class="px-6 py-8 text-center text-sm text-muted-foreground">لا يوجد تصعيد نشط</div>
                    <ul v-else class="divide-y divide-border">
                        <li v-for="r in requiresAction.escalated" :key="r.id">
                            <Link :href="`/requests/${r.id}`" class="flex items-center justify-between gap-3 px-4 py-3 transition hover:bg-muted/40">
                                <div class="min-w-0 flex-1">
                                    <div class="font-mono text-xs text-muted-foreground">{{ r.request_number }}</div>
                                    <div class="truncate font-medium">{{ r.title }}</div>
                                    <div class="mt-0.5 text-xs text-muted-foreground">صُعّد {{ timeAgoAr(r.escalated_at) }}</div>
                                </div>
                                <PriorityBadge :priority="r.priority" />
                            </Link>
                        </li>
                    </ul>
                </Card>

                <!-- Low ratings -->
                <Card class="overflow-hidden border-warning/30 p-0">
                    <div class="flex items-center gap-2 border-b border-border px-4 py-3">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-warning/10 text-warning"><Star class="size-4" /></span>
                        <h2 class="text-[15px] font-bold">تقييمات منخفضة</h2>
                    </div>
                    <div v-if="!requiresAction.low_ratings?.length" class="px-6 py-8 text-center text-sm text-muted-foreground">لا توجد تقييمات منخفضة</div>
                    <ul v-else class="divide-y divide-border">
                        <li v-for="r in requiresAction.low_ratings" :key="r.id">
                            <Link :href="`/requests/${r.request_id}`" class="block px-4 py-3 transition hover:bg-muted/40">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-0.5">
                                        <Star v-for="n in 5" :key="n" :class="['size-3.5', n <= r.stars ? 'fill-warning text-warning' : 'text-muted-foreground/30']" />
                                    </div>
                                    <span class="font-mono text-xs text-muted-foreground">{{ r.request_number }}</span>
                                </div>
                                <div class="mt-1 truncate text-sm font-medium">{{ r.title }}</div>
                                <div v-if="r.notes" class="mt-0.5 truncate text-xs text-muted-foreground">{{ r.notes }}</div>
                            </Link>
                        </li>
                    </ul>
                </Card>
            </div>

            <!-- Breakdowns -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <Card class="p-0">
                    <div class="flex items-center gap-2 border-b border-border px-4 py-3">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary"><ListChecks class="size-4" /></span>
                        <h2 class="text-[15px] font-bold">توزيع الحالات</h2>
                    </div>
                    <div v-if="!statusBars.length" class="px-6 py-8 text-center text-sm text-muted-foreground">لا توجد بيانات</div>
                    <div v-else class="space-y-2.5 p-4">
                        <div v-for="s in statusBars" :key="s.key">
                            <div class="mb-1 flex items-center justify-between text-xs">
                                <span class="font-medium text-foreground/80">{{ s.label }}</span>
                                <span class="font-bold tabular-nums text-muted-foreground">{{ num(s.value) }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-muted">
                                <div :class="['h-full rounded-full', s.bar]" :style="{ width: `${s.pct}%` }"></div>
                            </div>
                        </div>
                    </div>
                </Card>

                <Card class="p-0">
                    <div class="flex items-center gap-2 border-b border-border px-4 py-3">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-accent/10 text-accent"><Flame class="size-4" /></span>
                        <h2 class="text-[15px] font-bold">الأولوية (مفتوحة)</h2>
                    </div>
                    <div class="space-y-2.5 p-4">
                        <div v-for="p in priorityBars" :key="p.key">
                            <div class="mb-1 flex items-center justify-between text-xs">
                                <span class="font-medium text-foreground/80">{{ p.label }}</span>
                                <span class="font-bold tabular-nums text-muted-foreground">{{ num(p.value) }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-muted">
                                <div :class="['h-full rounded-full', p.bar]" :style="{ width: `${p.pct}%` }"></div>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Activity timeline + recent requests -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                <Card class="overflow-hidden p-0 lg:col-span-5">
                    <div class="flex items-center gap-2 border-b border-border px-4 py-3">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-foreground/5 text-foreground"><Bell class="size-4" /></span>
                        <h2 class="text-[15px] font-bold">النشاط الأخير</h2>
                    </div>
                    <div v-if="!activity.length" class="px-6 py-8 text-center text-sm text-muted-foreground">لا يوجد نشاط حديث</div>
                    <ol v-else class="divide-y divide-border">
                        <li v-for="a in activity" :key="a.id">
                            <Link :href="`/requests/${a.request_id}`" class="group flex items-start gap-3 px-4 py-3 transition hover:bg-muted/40">
                                <span class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"><Activity class="size-3.5" /></span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-1.5 text-[10.5px] text-muted-foreground">
                                        <span class="rounded bg-muted px-1 font-mono">{{ a.request_number }}</span>
                                        <span>·</span>
                                        <span>{{ timeAgoAr(a.created_at) }}</span>
                                    </div>
                                    <div class="mt-0.5 truncate text-[13px] font-semibold transition group-hover:text-primary">{{ a.title }}</div>
                                    <div class="text-[11px] text-muted-foreground">
                                        <span v-if="a.actor">{{ a.actor }} — </span>{{ actionLabel(a.action) }}
                                    </div>
                                </div>
                            </Link>
                        </li>
                    </ol>
                </Card>

                <Card class="overflow-hidden p-0 lg:col-span-7">
                    <div class="flex items-center justify-between gap-2 border-b border-border px-4 py-3">
                        <div class="flex items-center gap-2">
                            <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary"><ClipboardList class="size-4" /></span>
                            <h2 class="text-[15px] font-bold">أحدث الطلبات</h2>
                        </div>
                        <Button href="/requests" variant="ghost" size="sm">عرض الكل <ArrowLeft class="size-3.5" /></Button>
                    </div>
                    <div v-if="!recent.length" class="px-6 py-8 text-center text-sm text-muted-foreground">لا توجد طلبات</div>
                    <ul v-else class="divide-y divide-border">
                        <li v-for="r in recent" :key="r.id">
                            <Link :href="`/requests/${r.id}`" class="group flex items-center justify-between gap-3 px-4 py-3 transition hover:bg-muted/40">
                                <div class="min-w-0 flex-1">
                                    <div class="mb-1 flex items-center gap-2">
                                        <span class="font-mono text-xs text-muted-foreground">{{ r.request_number }}</span>
                                        <PriorityBadge :priority="r.priority" />
                                    </div>
                                    <div class="truncate font-medium transition group-hover:text-primary">{{ r.title }}</div>
                                    <div class="mt-1 text-xs text-muted-foreground">
                                        <span v-if="r.category">{{ r.category }} · </span>{{ timeAgoAr(r.updated_at) }}
                                    </div>
                                </div>
                                <StatusBadge :status="r.status" />
                            </Link>
                        </li>
                    </ul>
                </Card>
            </div>

            <!-- Quick links -->
            <Card class="p-0">
                <div class="border-b border-border px-4 py-3">
                    <h2 class="inline-flex items-center gap-2 text-[14.5px] font-bold"><Sparkles class="size-4 text-accent" /> روابط سريعة</h2>
                </div>
                <div class="grid grid-cols-2 gap-2 p-3 md:grid-cols-3 lg:grid-cols-6">
                    <Link v-for="ln in [
                        { to: '/requests', icon: ClipboardList, label: 'صندوق الطلبات', tone: 'bg-primary/10 text-primary' },
                        { to: '/requests/new', icon: PlusCircle, label: 'طلب جديد', tone: 'bg-info/10 text-info' },
                        { to: '/suggestions', icon: Lightbulb, label: 'المقترحات', tone: 'bg-accent/10 text-accent' },
                        { to: '/appointments', icon: CalendarClock, label: 'المواعيد', tone: 'bg-info/10 text-info' },
                        { to: '/sla-compliance', icon: Gauge, label: 'الالتزام SLA', tone: 'bg-success/10 text-success' },
                        { to: '/reports', icon: Users, label: 'التقارير', tone: 'bg-warning/10 text-warning' },
                    ]" :key="ln.to" :href="ln.to"
                        class="group flex flex-col items-center gap-2 rounded-xl border border-border p-3 transition hover:-translate-y-0.5 hover:border-primary/40 hover:bg-muted/40">
                        <span :class="['flex size-10 items-center justify-center rounded-xl transition group-hover:scale-110', ln.tone]"><component :is="ln.icon" class="size-4" /></span>
                        <span class="text-center text-[11.5px] font-semibold leading-tight text-foreground/80 transition group-hover:text-primary">{{ ln.label }}</span>
                    </Link>
                </div>
            </Card>
        </div>
    </AppShell>
</template>
