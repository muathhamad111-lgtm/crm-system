<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/PageHeader.vue';
import StatCard from '@/Components/ui/StatCard.vue';
import SectionCard from '@/Components/ui/SectionCard.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import IconChip from '@/Components/ui/IconChip.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PriorityBadge from '@/Components/PriorityBadge.vue';
import ServiceStatusPill from '@/Components/dashboard/ServiceStatusPill.vue';
import { num } from '@/lib/utils';
import { timeAgoAr } from '@/lib/date';
import { toneFill, toneSoft, toneText } from '@/lib/tone';
import { REQUEST_STATUS, REQUEST_PRIORITY, statusLabel } from '@/lib/labels';
import {
    Inbox, Clock, CheckCircle2, Star, AlertTriangle, ShieldCheck, Reply, CalendarClock,
    Lightbulb, PlusCircle, CalendarDays, ClipboardList, ArrowLeft, ChevronLeft, Bell,
    MessageCircle, Sparkles, Gauge, UserX, Flame, Phone, Mail, Users, Activity,
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
const ACTION_BTN = { destructive: 'destructive', warning: 'warning', accent: 'accent', primary: 'default' };

const summaryStats = computed(() => [
    { label: 'نشطة', value: props.stats.open ?? 0, tone: 'info' },
    { label: 'بانتظارك', value: props.stats.awaiting_you ?? 0, tone: 'warning' },
    { label: 'ضمن الوقت', value: props.stats.on_track ?? 0, tone: 'success' },
    { label: 'متأخرة', value: props.stats.overdue ?? 0, tone: 'destructive' },
    { label: 'مكتملة', value: props.stats.completed ?? 0, tone: 'muted' },
]);

const channels = computed(() => {
    const c = props.support?.support_channels ?? {};
    return [
        c.phone && { href: `tel:${c.phone}`, icon: Phone, label: 'هاتف الدعم', value: c.phone, tone: 'primary' },
        c.whatsapp && { href: `https://wa.me/${String(c.whatsapp).replace(/\D/g, '')}`, icon: MessageCircle, label: 'واتساب', value: c.whatsapp, tone: 'success' },
        c.email && { href: `mailto:${c.email}`, icon: Mail, label: 'البريد الإلكتروني', value: c.email, tone: 'info' },
    ].filter(Boolean);
});

const updateMeta = (kind) => ({
    staff_replied: { icon: MessageCircle, label: 'رد جديد', tone: 'info' },
    you_replied: { icon: Reply, label: 'ردّك', tone: 'muted' },
    status_change: { icon: Sparkles, label: 'تحديث حالة', tone: 'primary' },
}[kind] ?? { icon: Bell, label: 'تحديث', tone: 'muted' });

const statusUpdateLabel = (val) => statusLabel(REQUEST_STATUS, val).label;

function fmtApptDate(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('ar-SA-u-ca-gregory-nu-latn', {
        timeZone: 'Asia/Riyadh', weekday: 'short', day: 'numeric', month: 'short',
    });
}
function fmtApptTime(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleTimeString('ar-SA-u-ca-gregory-nu-latn', {
        timeZone: 'Asia/Riyadh', hour: '2-digit', minute: '2-digit',
    });
}

const customerShortcuts = [
    { to: '/requests/new', icon: PlusCircle, label: 'طلب جديد', tone: 'primary' },
    { to: '/requests', icon: ClipboardList, label: 'طلباتي', tone: 'info' },
    { to: '/appointments/new', icon: CalendarDays, label: 'حجز موعد', tone: 'primary' },
    { to: '/appointments', icon: CalendarClock, label: 'مواعيدي', tone: 'info' },
    { to: '/suggestions', icon: Lightbulb, label: 'المقترحات', tone: 'accent' },
    { to: '/suggestions/new', icon: Sparkles, label: 'شاركنا فكرتك', tone: 'accent' },
];

// ---- Staff helpers ----
const kpiCards = computed(() => [
    { label: 'طلبات نشطة', value: props.kpis.active ?? 0, icon: Inbox, tone: 'primary', href: '/requests' },
    { label: 'بانتظار المعالجة', value: props.kpis.pending ?? 0, icon: Clock, tone: 'warning', href: '/requests?status=new' },
    { label: 'متأخرة', value: props.kpis.overdue ?? 0, icon: AlertTriangle, tone: 'destructive', href: '/requests?status=overdue' },
    { label: 'مكتملة', value: props.kpis.completed ?? 0, icon: CheckCircle2, tone: 'success', href: '/requests?status=completed' },
    {
        label: 'رضا العملاء', value: props.kpis.csat ?? '—', icon: Star, tone: 'accent', num: false,
        hint: props.kpis.rating_count ? `${num(props.kpis.rating_count)} تقييم` : 'لا تقييمات', href: '/ratings',
    },
    {
        label: 'الالتزام بـ SLA', value: props.kpis.sla != null ? `${props.kpis.sla}%` : '—', icon: Gauge, num: false,
        tone: props.kpis.sla == null ? 'muted' : (props.kpis.sla >= 90 ? 'success' : props.kpis.sla >= 75 ? 'warning' : 'destructive'),
        href: '/sla-compliance',
    },
]);

const statusBars = computed(() => {
    const max = Math.max(1, ...props.statusBreakdown.map((s) => s.value));
    return props.statusBreakdown.map((s) => {
        const m = statusLabel(REQUEST_STATUS, s.key);
        return { key: s.key, label: m.label, value: s.value, pct: Math.round((s.value / max) * 100), tone: m.tone };
    });
});
const priorityBars = computed(() => {
    const max = Math.max(1, ...props.priorityBreakdown.map((p) => p.value));
    return props.priorityBreakdown.map((p) => {
        const m = statusLabel(REQUEST_PRIORITY, p.key);
        return { key: p.key, label: m.label, value: p.value, pct: Math.round((p.value / max) * 100), tone: m.tone };
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

const staffShortcuts = [
    { to: '/requests', icon: ClipboardList, label: 'صندوق الطلبات', tone: 'primary' },
    { to: '/requests/new', icon: PlusCircle, label: 'طلب جديد', tone: 'info' },
    { to: '/suggestions', icon: Lightbulb, label: 'المقترحات', tone: 'accent' },
    { to: '/appointments', icon: CalendarClock, label: 'المواعيد', tone: 'info' },
    { to: '/sla-compliance', icon: Gauge, label: 'الالتزام SLA', tone: 'success' },
    { to: '/reports', icon: Users, label: 'التقارير', tone: 'warning' },
];

const headerSubtitle = computed(() => {
    if (props.branch === 'customer') return 'ملخص حسابك، طلباتك النشطة، ومواعيدك القادمة.';
    return props.isAdmin
        ? 'مركز قرار مدير النظام — نظرة لحظية على أداء الطلبات وصحّة التشغيل.'
        : 'نظرة عامة على طلبات العملاء وما يحتاج تدخلك الآن.';
});
</script>

<template>
    <Head title="الرئيسية" />
    <AppShell>
        <!-- ============================ CUSTOMER ============================ -->
        <template v-if="branch === 'customer'">
            <PageHeader :title="`${greeting}، ${name}`" :subtitle="headerSubtitle" hide-breadcrumbs>
                <template #actions>
                    <Button href="/requests/new"><PlusCircle class="size-4" /> طلب جديد</Button>
                    <Button href="/appointments/new" variant="outline"><CalendarDays class="size-4" /> حجز موعد</Button>
                </template>
            </PageHeader>

            <div class="space-y-4">
                <!-- Action required -->
                <SectionCard title="يتطلب إجراء منك"
                    :description="actionCount ? `${num(actionCount)} عناصر بانتظار تصرّفك الآن` : 'كل شيء تحت السيطرة'"
                    :icon="actionCount ? AlertTriangle : CheckCircle2" :tone="actionCount ? 'destructive' : 'success'"
                    :border-tone="actionCount ? 'destructive' : ''" flush>
                    <template v-if="actionCount" #actions>
                        <Badge variant="destructive">{{ num(actionCount) }}</Badge>
                    </template>

                    <EmptyState v-if="!actionItems.length" :icon="ShieldCheck" tone="success" size="sm"
                        title="لا توجد إجراءات مطلوبة منك حالياً" description="جميع الأمور تسير بشكل جيد." />

                    <ul v-else class="divide-y divide-border">
                        <li v-for="it in actionItems" :key="it.key"
                            class="group flex flex-wrap items-center gap-3 px-4 py-3 transition-colors hover:bg-muted/40">
                            <IconChip :icon="ACTION_ICON[it.icon] ?? Bell" :tone="it.accent" />
                            <div class="min-w-0 flex-1">
                                <div class="mb-0.5 flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground">
                                    <span class="ref-chip">{{ it.ref }}</span>
                                    <span aria-hidden="true">·</span>
                                    <span class="truncate">{{ it.hint }}</span>
                                </div>
                                <p class="line-clamp-1 text-base font-semibold group-hover:text-primary">{{ it.title }}</p>
                            </div>
                            <Button :href="it.url" size="sm" :variant="ACTION_BTN[it.accent] ?? 'default'" class="shrink-0">
                                {{ it.cta }} <ChevronLeft class="size-3.5" />
                            </Button>
                        </li>
                    </ul>
                </SectionCard>

                <!-- Requests summary -->
                <SectionCard title="ملخص طلباتك" :icon="ClipboardList" flush>
                    <template #actions>
                        <Button href="/requests" variant="ghost-muted" size="sm">كل الطلبات <ArrowLeft class="size-3.5" /></Button>
                    </template>

                    <dl class="grid grid-cols-2 divide-x divide-y divide-border divide-x-reverse border-b border-border md:grid-cols-5 md:divide-y-0">
                        <div v-for="s in summaryStats" :key="s.label" class="px-4 py-3">
                            <dt class="flex items-center gap-1.5 text-xs font-semibold text-muted-foreground">
                                <span :class="['size-1.5 rounded-full', toneFill(s.tone)]" aria-hidden="true"></span>
                                {{ s.label }}
                            </dt>
                            <dd :class="['mt-1 text-2xl font-bold tabular-nums', s.tone === 'muted' ? 'text-foreground' : toneText(s.tone)]">
                                {{ num(s.value) }}
                            </dd>
                        </div>
                    </dl>

                    <EmptyState v-if="!last3.length" :icon="ClipboardList" title="لا توجد طلبات بعد"
                        description="أنشئ طلبك الأول وسيظهر هنا مع حالته المحدّثة.">
                        <Button href="/requests/new" size="sm"><PlusCircle class="size-4" /> أنشئ طلبك الأول</Button>
                    </EmptyState>

                    <ul v-else class="divide-y divide-border">
                        <li v-for="r in last3" :key="r.id">
                            <Link :href="`/requests/${r.id}`"
                                class="group grid grid-cols-12 items-center gap-3 px-4 py-3 transition-colors hover:bg-muted/40">
                                <div class="col-span-12 min-w-0 md:col-span-6">
                                    <div class="mb-1 flex items-center gap-1.5 text-xs text-muted-foreground">
                                        <span class="ref-chip">{{ r.request_number }}</span>
                                        <span v-if="r.category" class="truncate">· {{ r.category }}</span>
                                    </div>
                                    <p class="line-clamp-1 text-base font-semibold group-hover:text-primary">{{ r.title }}</p>
                                </div>
                                <div class="col-span-6 md:col-span-3"><ServiceStatusPill :status="r.service_status" /></div>
                                <div class="col-span-6 flex items-center justify-end gap-1 text-xs text-muted-foreground md:col-span-3 md:justify-start">
                                    <Clock class="size-3" aria-hidden="true" /> آخر تحديث {{ timeAgoAr(r.updated_at) }}
                                </div>
                            </Link>
                        </li>
                    </ul>
                </SectionCard>

                <!-- Highlight + appointments -->
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                    <SectionCard v-if="highlight" title="أهم طلب نشط لديك" :icon="Flame" border-tone="primary"
                        class="lg:col-span-7">
                        <div class="mb-3 flex flex-wrap items-center gap-1.5">
                            <span class="ref-chip">{{ highlight.request_number }}</span>
                            <Badge v-if="highlight.category" variant="outline">{{ highlight.category }}</Badge>
                            <ServiceStatusPill :status="highlight.service_status" />
                        </div>
                        <h3 class="line-clamp-2 text-lg font-bold leading-snug">{{ highlight.title }}</h3>

                        <dl class="mt-3 grid grid-cols-2 gap-2 md:grid-cols-3">
                            <div class="rounded-md border border-border bg-surface px-3 py-2">
                                <dt class="flex items-center gap-1 text-xs text-muted-foreground"><Sparkles class="size-3" /> الحالة</dt>
                                <dd class="mt-0.5 truncate text-base font-semibold">{{ statusUpdateLabel(highlight.status) }}</dd>
                            </div>
                            <div class="rounded-md border border-border bg-surface px-3 py-2">
                                <dt class="flex items-center gap-1 text-xs text-muted-foreground"><Clock class="size-3" /> آخر تحديث</dt>
                                <dd class="mt-0.5 truncate text-base font-semibold">{{ timeAgoAr(highlight.updated_at) }}</dd>
                            </div>
                            <div class="rounded-md border border-border bg-surface px-3 py-2">
                                <dt class="flex items-center gap-1 text-xs text-muted-foreground"><CalendarDays class="size-3" /> الإنشاء</dt>
                                <dd class="mt-0.5 truncate text-base font-semibold">{{ timeAgoAr(highlight.created_at) }}</dd>
                            </div>
                        </dl>

                        <template #footer>
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-xs text-muted-foreground">
                                    {{ highlight.status === 'awaiting_customer' ? 'المطلوب منك: استكمال البيانات' : 'فريقنا يعمل على طلبك حالياً' }}
                                </p>
                                <Button :href="`/requests/${highlight.id}`" size="sm">عرض التفاصيل <ChevronLeft class="size-3.5" /></Button>
                            </div>
                        </template>
                    </SectionCard>

                    <SectionCard title="مواعيدك القادمة" description="المؤكدة فقط · خلال 14 يوم" :icon="CalendarClock"
                        flush :class="highlight ? 'lg:col-span-5' : 'lg:col-span-12'">
                        <template #actions>
                            <Button href="/appointments" variant="ghost-muted" size="sm">الكل <ArrowLeft class="size-3" /></Button>
                        </template>

                        <EmptyState v-if="!appointments.length" :icon="CalendarDays" size="sm" title="لا توجد مواعيد قادمة"
                            description="احجز موعداً مع فريق الخدمة وسيظهر هنا.">
                            <Button href="/appointments/new" size="sm" variant="outline">احجز موعداً</Button>
                        </EmptyState>

                        <ul v-else class="space-y-2 p-3">
                            <li v-for="a in appointments" :key="a.id">
                                <Link :href="`/appointments/${a.id}`"
                                    class="group flex items-stretch gap-2.5 rounded-md border border-border p-2.5 transition-colors hover:border-primary/40 hover:bg-muted/40">
                                    <div class="flex w-14 shrink-0 flex-col items-center justify-center rounded-md bg-primary-soft py-1 text-primary">
                                        <span class="text-2xs font-bold leading-none">{{ fmtApptDate(a.starts_at).split(' ')[0] }}</span>
                                        <span class="my-0.5 text-lg font-bold leading-none tabular-nums">{{ new Date(a.starts_at).getDate() }}</span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <Badge variant="success">مؤكد</Badge>
                                        <p class="mt-1 line-clamp-1 text-base font-semibold group-hover:text-primary">{{ a.type }}</p>
                                        <p class="mt-0.5 flex flex-wrap items-center gap-1 text-xs text-muted-foreground">
                                            <Clock class="size-3" aria-hidden="true" />
                                            <span class="font-semibold tabular-nums text-foreground">{{ fmtApptTime(a.starts_at) }}</span>
                                            <span>· {{ fmtApptDate(a.starts_at) }}</span>
                                            <span v-if="a.meeting_url" class="text-primary">· عن بُعد</span>
                                        </p>
                                    </div>
                                </Link>
                            </li>
                        </ul>
                    </SectionCard>
                </div>

                <!-- Recent updates + support -->
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                    <SectionCard title="آخر التحديثات" description="آخر 30 يوم" :icon="Bell" tone="muted" flush
                        class="lg:col-span-8">
                        <EmptyState v-if="!recentUpdates.length" :icon="Bell" size="sm" title="لا توجد تحديثات حديثة"
                            description="ستظهر هنا ردود الفريق وتغييرات حالة طلباتك." />

                        <ol v-else class="divide-y divide-border">
                            <li v-for="u in recentUpdates" :key="u.id">
                                <Link :href="`/requests/${u.request_id}`"
                                    class="group flex items-start gap-3 px-4 py-3 transition-colors hover:bg-muted/40">
                                    <IconChip :icon="updateMeta(u.kind).icon" :tone="updateMeta(u.kind).tone" size="sm" class="mt-0.5" />
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground">
                                            <Badge variant="outline">{{ updateMeta(u.kind).label }}</Badge>
                                            <span class="ref-chip">{{ u.request_number }}</span>
                                            <span aria-hidden="true">·</span>
                                            <span>{{ timeAgoAr(u.created_at) }}</span>
                                        </div>
                                        <p class="mt-1 truncate text-base font-semibold group-hover:text-primary">{{ u.title }}</p>
                                        <p class="line-clamp-1 text-xs text-muted-foreground">
                                            {{ u.kind === 'status_change' ? `تحديث الحالة إلى «${statusUpdateLabel(u.summary)}»` : u.summary }}
                                        </p>
                                    </div>
                                </Link>
                            </li>
                        </ol>
                    </SectionCard>

                    <SectionCard title="معلومات الدعم" :icon="MessageCircle" class="lg:col-span-4" body-class="space-y-3">
                        <div v-if="support?.business_hours" class="rounded-md border border-border bg-surface p-3">
                            <p class="flex items-center gap-2 text-xs text-muted-foreground">
                                <CalendarClock class="size-3.5" aria-hidden="true" /> ساعات العمل
                            </p>
                            <p class="mt-1.5 flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">التوقيت</span>
                                <span class="font-semibold tabular-nums" dir="ltr">
                                    {{ support.business_hours.start ?? '—' }} – {{ support.business_hours.end ?? '—' }}
                                </span>
                            </p>
                        </div>

                        <p v-if="!channels.length" class="text-sm text-muted-foreground">لم تُضَف قنوات تواصل بعد.</p>
                        <ul v-else class="space-y-1.5">
                            <li v-for="(it, i) in channels" :key="i">
                                <a :href="it.href"
                                    class="group flex items-center gap-3 rounded-md border border-border p-2.5 transition-colors hover:border-primary/40 hover:bg-muted/40">
                                    <IconChip :icon="it.icon" :tone="it.tone" />
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-xs text-muted-foreground">{{ it.label }}</span>
                                        <span class="block truncate text-base font-semibold tabular-nums" dir="ltr">{{ it.value }}</span>
                                    </span>
                                    <ChevronLeft class="size-4 shrink-0 text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100" aria-hidden="true" />
                                </a>
                            </li>
                        </ul>
                    </SectionCard>
                </div>

                <!-- Shortcuts -->
                <SectionCard title="اختصارات سريعة" :icon="Sparkles" tone="accent" flush>
                    <div class="grid grid-cols-2 gap-2 p-3 md:grid-cols-3 lg:grid-cols-6">
                        <Link v-for="sc in customerShortcuts" :key="sc.to" :href="sc.to"
                            class="group flex flex-col items-center gap-2 rounded-md border border-border p-3 transition-colors hover:border-primary/40 hover:bg-muted/40">
                            <span :class="['flex size-10 items-center justify-center rounded-lg', toneSoft(sc.tone)]" aria-hidden="true">
                                <component :is="sc.icon" class="size-4" />
                            </span>
                            <span class="text-center text-xs font-semibold leading-tight group-hover:text-primary">{{ sc.label }}</span>
                        </Link>
                    </div>
                </SectionCard>
            </div>
        </template>

        <!-- ============================= STAFF ============================= -->
        <template v-else>
            <PageHeader :title="`أهلاً، ${name}`" :subtitle="headerSubtitle" hide-breadcrumbs>
                <template #badge>
                    <Badge variant="accent">{{ isAdmin ? 'مركز قرار مدير النظام' : 'لوحة عمليات الدعم' }}</Badge>
                </template>
                <template #actions>
                    <Button href="/requests"><Inbox class="size-4" /> صندوق الطلبات</Button>
                    <Button href="/reports" variant="outline"><Activity class="size-4" /> التقارير</Button>
                </template>
            </PageHeader>

            <div class="space-y-4">
                <!-- KPI ribbon -->
                <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
                    <StatCard v-for="k in kpiCards" :key="k.label" :label="k.label" :value="k.value" :icon="k.icon"
                        :tone="k.tone" :hint="k.hint ?? ''" :href="k.href" :format-number="k.num !== false" />
                </div>

                <!-- Requires action -->
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <SectionCard title="طلبات متأخرة" :icon="AlertTriangle" tone="destructive" border-tone="destructive" flush>
                        <template #actions><Badge variant="destructive">{{ num(kpis.overdue ?? 0) }}</Badge></template>
                        <EmptyState v-if="!requiresAction.overdue?.length" :icon="CheckCircle2" tone="success" size="sm"
                            title="لا توجد طلبات متأخرة" />
                        <ul v-else class="divide-y divide-border">
                            <li v-for="r in requiresAction.overdue" :key="r.id">
                                <Link :href="`/requests/${r.id}`"
                                    class="group flex items-center justify-between gap-3 px-4 py-3 transition-colors hover:bg-muted/40">
                                    <span class="min-w-0 flex-1">
                                        <span class="ref-chip">{{ r.request_number }}</span>
                                        <span class="mt-1 block truncate text-base font-semibold group-hover:text-primary">{{ r.title }}</span>
                                        <span class="mt-0.5 block truncate text-xs text-destructive">تأخر منذ {{ timeAgoAr(r.due_at) }}</span>
                                    </span>
                                    <PriorityBadge :priority="r.priority" />
                                </Link>
                            </li>
                        </ul>
                    </SectionCard>

                    <SectionCard title="غير مُسندة" :icon="UserX" tone="warning" border-tone="warning" flush>
                        <template #actions><Badge variant="warning">{{ num(kpis.unassigned ?? 0) }}</Badge></template>
                        <EmptyState v-if="!requiresAction.unassigned?.length" :icon="CheckCircle2" tone="success" size="sm"
                            title="كل الطلبات مُسندة" />
                        <ul v-else class="divide-y divide-border">
                            <li v-for="r in requiresAction.unassigned" :key="r.id">
                                <Link :href="`/requests/${r.id}`"
                                    class="group flex items-center justify-between gap-3 px-4 py-3 transition-colors hover:bg-muted/40">
                                    <span class="min-w-0 flex-1">
                                        <span class="ref-chip">{{ r.request_number }}</span>
                                        <span class="mt-1 block truncate text-base font-semibold group-hover:text-primary">{{ r.title }}</span>
                                        <span class="mt-0.5 block text-xs text-muted-foreground">منذ {{ timeAgoAr(r.created_at) }}</span>
                                    </span>
                                    <PriorityBadge :priority="r.priority" />
                                </Link>
                            </li>
                        </ul>
                    </SectionCard>

                    <SectionCard title="مُصعّدة" :icon="Flame" tone="destructive" border-tone="destructive" flush>
                        <template #actions><Badge variant="destructive">{{ num(kpis.escalated ?? 0) }}</Badge></template>
                        <EmptyState v-if="!requiresAction.escalated?.length" :icon="ShieldCheck" tone="success" size="sm"
                            title="لا يوجد تصعيد نشط" />
                        <ul v-else class="divide-y divide-border">
                            <li v-for="r in requiresAction.escalated" :key="r.id">
                                <Link :href="`/requests/${r.id}`"
                                    class="group flex items-center justify-between gap-3 px-4 py-3 transition-colors hover:bg-muted/40">
                                    <span class="min-w-0 flex-1">
                                        <span class="ref-chip">{{ r.request_number }}</span>
                                        <span class="mt-1 block truncate text-base font-semibold group-hover:text-primary">{{ r.title }}</span>
                                        <span class="mt-0.5 block text-xs text-muted-foreground">صُعّد {{ timeAgoAr(r.escalated_at) }}</span>
                                    </span>
                                    <PriorityBadge :priority="r.priority" />
                                </Link>
                            </li>
                        </ul>
                    </SectionCard>

                    <SectionCard title="تقييمات منخفضة" :icon="Star" tone="warning" border-tone="warning" flush>
                        <EmptyState v-if="!requiresAction.low_ratings?.length" :icon="Star" tone="success" size="sm"
                            title="لا توجد تقييمات منخفضة" />
                        <ul v-else class="divide-y divide-border">
                            <li v-for="r in requiresAction.low_ratings" :key="r.id">
                                <Link :href="`/requests/${r.request_id}`"
                                    class="group block px-4 py-3 transition-colors hover:bg-muted/40">
                                    <span class="flex items-center justify-between gap-2">
                                        <span class="flex items-center gap-0.5" :aria-label="`${r.stars} من 5`">
                                            <Star v-for="n in 5" :key="n"
                                                :class="['size-3.5', n <= r.stars ? 'fill-warning text-warning' : 'text-muted-foreground/30']" />
                                        </span>
                                        <span class="ref-chip">{{ r.request_number }}</span>
                                    </span>
                                    <span class="mt-1 block truncate text-base font-semibold group-hover:text-primary">{{ r.title }}</span>
                                    <span v-if="r.notes" class="mt-0.5 block truncate text-xs text-muted-foreground">{{ r.notes }}</span>
                                </Link>
                            </li>
                        </ul>
                    </SectionCard>
                </div>

                <!-- Breakdowns -->
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <SectionCard title="توزيع الحالات" :icon="ListChecks">
                        <EmptyState v-if="!statusBars.length" :icon="ListChecks" size="sm" title="لا توجد بيانات" />
                        <div v-else class="space-y-3">
                            <div v-for="s in statusBars" :key="s.key">
                                <div class="mb-1 flex items-center justify-between text-sm">
                                    <span class="font-semibold">{{ s.label }}</span>
                                    <span class="tabular-nums text-muted-foreground">{{ num(s.value) }}</span>
                                </div>
                                <div class="sla-progress-track">
                                    <div :class="['sla-progress-fill', toneFill(s.tone)]" :style="{ width: `${s.pct}%` }"></div>
                                </div>
                            </div>
                        </div>
                    </SectionCard>

                    <SectionCard title="الأولوية (مفتوحة)" :icon="Flame" tone="accent">
                        <EmptyState v-if="!priorityBars.length" :icon="Flame" size="sm" title="لا توجد بيانات" />
                        <div v-else class="space-y-3">
                            <div v-for="p in priorityBars" :key="p.key">
                                <div class="mb-1 flex items-center justify-between text-sm">
                                    <span class="font-semibold">{{ p.label }}</span>
                                    <span class="tabular-nums text-muted-foreground">{{ num(p.value) }}</span>
                                </div>
                                <div class="sla-progress-track">
                                    <div :class="['sla-progress-fill', toneFill(p.tone)]" :style="{ width: `${p.pct}%` }"></div>
                                </div>
                            </div>
                        </div>
                    </SectionCard>
                </div>

                <!-- Activity + recent requests -->
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                    <SectionCard title="النشاط الأخير" :icon="Activity" tone="muted" flush class="lg:col-span-5">
                        <EmptyState v-if="!activity.length" :icon="Activity" size="sm" title="لا يوجد نشاط حديث" />
                        <ol v-else class="divide-y divide-border">
                            <li v-for="a in activity" :key="a.id">
                                <Link :href="`/requests/${a.request_id}`"
                                    class="group flex items-start gap-3 px-4 py-3 transition-colors hover:bg-muted/40">
                                    <IconChip :icon="Activity" size="sm" class="mt-0.5" />
                                    <span class="min-w-0 flex-1">
                                        <span class="flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground">
                                            <span class="ref-chip">{{ a.request_number }}</span>
                                            <span aria-hidden="true">·</span>
                                            <span>{{ timeAgoAr(a.created_at) }}</span>
                                        </span>
                                        <span class="mt-1 block truncate text-base font-semibold group-hover:text-primary">{{ a.title }}</span>
                                        <span class="block text-xs text-muted-foreground">
                                            <template v-if="a.actor">{{ a.actor }} — </template>{{ actionLabel(a.action) }}
                                        </span>
                                    </span>
                                </Link>
                            </li>
                        </ol>
                    </SectionCard>

                    <SectionCard title="أحدث الطلبات" :icon="ClipboardList" flush class="lg:col-span-7">
                        <template #actions>
                            <Button href="/requests" variant="ghost-muted" size="sm">عرض الكل <ArrowLeft class="size-3.5" /></Button>
                        </template>
                        <EmptyState v-if="!recent.length" :icon="ClipboardList" size="sm" title="لا توجد طلبات" />
                        <ul v-else class="divide-y divide-border">
                            <li v-for="r in recent" :key="r.id">
                                <Link :href="`/requests/${r.id}`"
                                    class="group flex items-center justify-between gap-3 px-4 py-3 transition-colors hover:bg-muted/40">
                                    <span class="min-w-0 flex-1">
                                        <span class="mb-1 flex items-center gap-2">
                                            <span class="ref-chip">{{ r.request_number }}</span>
                                            <PriorityBadge :priority="r.priority" />
                                        </span>
                                        <span class="block truncate text-base font-semibold group-hover:text-primary">{{ r.title }}</span>
                                        <span class="mt-0.5 block text-xs text-muted-foreground">
                                            <template v-if="r.category">{{ r.category }} · </template>{{ timeAgoAr(r.updated_at) }}
                                        </span>
                                    </span>
                                    <StatusBadge :status="r.status" />
                                </Link>
                            </li>
                        </ul>
                    </SectionCard>
                </div>

                <!-- Shortcuts -->
                <SectionCard title="روابط سريعة" :icon="Sparkles" tone="accent" flush>
                    <div class="grid grid-cols-2 gap-2 p-3 md:grid-cols-3 lg:grid-cols-6">
                        <Link v-for="ln in staffShortcuts" :key="ln.to" :href="ln.to"
                            class="group flex flex-col items-center gap-2 rounded-md border border-border p-3 transition-colors hover:border-primary/40 hover:bg-muted/40">
                            <span :class="['flex size-10 items-center justify-center rounded-lg', toneSoft(ln.tone)]" aria-hidden="true">
                                <component :is="ln.icon" class="size-4" />
                            </span>
                            <span class="text-center text-xs font-semibold leading-tight group-hover:text-primary">{{ ln.label }}</span>
                        </Link>
                    </div>
                </SectionCard>
            </div>
        </template>
    </AppShell>
</template>
