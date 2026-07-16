<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Badge from '@/Components/ui/Badge.vue';
import Tabs from '@/Components/ui/Tabs.vue';
import TabsList from '@/Components/ui/TabsList.vue';
import TabsTrigger from '@/Components/ui/TabsTrigger.vue';
import TabsContent from '@/Components/ui/TabsContent.vue';
import Table from '@/Components/ui/Table.vue';
import TableHeader from '@/Components/ui/TableHeader.vue';
import TableBody from '@/Components/ui/TableBody.vue';
import TableRow from '@/Components/ui/TableRow.vue';
import TableHead from '@/Components/ui/TableHead.vue';
import TableCell from '@/Components/ui/TableCell.vue';
import CustomerJourneyCard from '@/Components/customer/CustomerJourneyCard.vue';
import CustomerHealthCard from '@/Components/customer/CustomerHealthCard.vue';
import CustomerTimelineTab from '@/Components/customer/CustomerTimelineTab.vue';
import { num } from '@/lib/utils';
import { REQUEST_PRIORITY, IDEA_STAGE, statusLabel } from '@/lib/labels';
import { fmtDateAr, fmtFullDateTimeAr, timeAgoAr } from '@/lib/date';
import {
    ArrowLeft, Mail, Phone, MapPin, Briefcase, Hash, FileText, Users, Package, Star,
    Activity, AlertTriangle, UserCircle2, Gauge, Lightbulb, TrendingUp, Building2,
    Globe, UserCheck, Award, Compass, ThumbsUp, Calendar, Clock, Video, Paperclip,
    StickyNote, CheckCircle2,
} from 'lucide-vue-next';

const props = defineProps({
    profile: { type: Object, required: true },
    isStaff: { type: Boolean, default: false },
    requests: { type: Array, default: () => [] },
    suggestions: { type: Array, default: () => [] },
    contacts: { type: Array, default: () => [] },
    subscriptions: { type: Array, default: () => [] },
    activities: { type: Array, default: () => [] },
    activationTasks: { type: Array, default: () => [] },
    meetings: { type: Array, default: () => [] },
    ratings: { type: Array, default: () => [] },
    attachments: { type: Array, default: () => [] },
    topCategories: { type: Array, default: () => [] },
    topProducts: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
});

const initial = computed(() => (props.profile.full_name || '؟').charAt(0));
const activeSubs = computed(() => props.stats.active_subscriptions ?? 0);
const req = computed(() => props.stats.requests ?? {});
const sat = computed(() => props.stats.satisfaction ?? {});
const sug = computed(() => props.stats.suggestions ?? {});
const stats = props.stats;

const TAB_CLS = 'rounded-lg data-[state=active]:bg-primary data-[state=active]:text-primary-foreground data-[state=active]:shadow';

const heroChips = computed(() => [
    { label: 'اشتراكات نشطة', value: num(activeSubs.value), icon: Users, ring: 'bg-success text-success-foreground' },
    {
        label: 'طلبات متأخرة', value: num(req.value.overdue ?? 0), icon: AlertTriangle,
        ring: (req.value.overdue ?? 0) > 0 ? 'bg-destructive text-destructive-foreground' : 'bg-white/20 text-white',
    },
]);

const kpis = computed(() => [
    { label: 'إجمالي الطلبات', value: num(req.value.total ?? 0), icon: FileText, chip: 'bg-primary/10 text-primary', numCls: 'text-primary' },
    { label: 'مفتوحة', value: num(req.value.open ?? 0), icon: Activity, chip: (req.value.open ?? 0) > 0 ? 'bg-warning/15 text-warning' : 'bg-muted text-muted-foreground', numCls: 'text-foreground' },
    { label: 'متأخرة', value: num(req.value.overdue ?? 0), icon: AlertTriangle, chip: (req.value.overdue ?? 0) > 0 ? 'bg-destructive/15 text-destructive' : 'bg-muted text-muted-foreground', numCls: (req.value.overdue ?? 0) > 0 ? 'text-destructive' : 'text-foreground' },
    { label: 'جهات الاتصال', value: num(stats.contacts ?? 0), icon: UserCircle2, chip: 'bg-accent/15 text-accent', numCls: 'text-accent' },
    { label: 'رضا العميل', value: sat.value.csat != null ? `${sat.value.csat}/5` : '—', sub: `${num(sat.value.count ?? 0)} تقييم`, icon: Star, chip: 'bg-warning/15 text-warning', numCls: 'text-warning' },
    { label: 'التزام SLA', value: req.value.sla_pct != null ? `${req.value.sla_pct}%` : '—', icon: Gauge, chip: (req.value.sla_pct ?? 0) >= 80 ? 'bg-success/15 text-success' : 'bg-warning/15 text-warning', numCls: (req.value.sla_pct ?? 0) >= 80 ? 'text-success' : 'text-warning' },
    { label: 'المقترحات', value: num(sug.value.total ?? 0), sub: `${num(sug.value.accepted ?? 0)} مقبول`, icon: Lightbulb, chip: 'bg-accent/15 text-accent', numCls: 'text-accent' },
]);

const SUB_STATUS = {
    active: { label: 'ساري', tone: 'success' },
    trial: { label: 'تجريبي', tone: 'warning' },
    expired: { label: 'منتهٍ', tone: 'destructive' },
    cancelled: { label: 'ملغى', tone: 'muted' },
    suspended: { label: 'موقوف', tone: 'muted' },
};
function subStatus(s) { return SUB_STATUS[s] ?? { label: s ?? '—', tone: 'muted' }; }
function priorityLabel(p) { return statusLabel(REQUEST_PRIORITY, p).label; }
function stageLabel(s) { return statusLabel(IDEA_STAGE, s).label; }

const ACCOUNT_TYPE = { company: 'شركة', association: 'جمعية', foundation: 'مؤسسة', individual: 'فرد', government: 'جهة حكومية', other: 'أخرى' };
const TIER = { vip: 'VIP', gold: 'ذهبي', silver: 'فضي', standard: 'قياسي' };
const ACCOUNT_STATUS = { active: { label: 'نشط', tone: 'success' }, suspended: { label: 'معلّق', tone: 'warning' }, archived: { label: 'مؤرشف', tone: 'muted' } };
function accountStatus() { return ACCOUNT_STATUS[props.profile.account_status] ?? { label: props.profile.account_status || 'نشط', tone: 'success' }; }

const EVENT_TYPE = { visit: 'زيارة', meeting: 'اجتماع', call: 'اتصال', reminder: 'تذكير', task: 'مهمة', other: 'أخرى' };
const EVENT_STATUS = { scheduled: { label: 'مجدول', tone: 'default' }, completed: { label: 'مكتمل', tone: 'success' }, cancelled: { label: 'ملغى', tone: 'muted' }, rescheduled: { label: 'أعيدت جدولته', tone: 'warning' } };
function eventStatus(s) { return EVENT_STATUS[s] ?? { label: s ?? '—', tone: 'muted' }; }

const now = Date.now();
const upcomingMeetings = computed(() => props.meetings.filter((m) => new Date(m.starts_at).getTime() >= now));
const pastMeetings = computed(() => props.meetings.filter((m) => new Date(m.starts_at).getTime() < now));

const summaryRows = computed(() => [
    { label: 'مغلقة', value: num(req.value.closed ?? 0) },
    { label: 'أُعيد فتحها', value: num(req.value.reopened ?? 0) },
    { label: 'متوسط زمن الحل', value: req.value.avg_hours != null ? `${req.value.avg_hours} ساعة` : '—' },
    { label: 'أول طلب', value: req.value.first_at ? fmtDateAr(req.value.first_at) : '—' },
    { label: 'آخر طلب', value: req.value.last_at ? fmtDateAr(req.value.last_at) : '—' },
    { label: 'المؤيدون (4-5★)', value: num(sat.value.promoters ?? 0) },
    { label: 'غير الراضين (1-2★)', value: num(sat.value.detractors ?? 0) },
]);
</script>

<template>
    <Head :title="profile.full_name || 'ملف العميل'" />
    <AppShell>
        <div class="space-y-5">
            <!-- Immersive hero -->
            <section class="relative overflow-hidden rounded-3xl p-6 text-white shadow-elevated" style="background-image: var(--gradient-hero);">
                <div class="pointer-events-none absolute -right-16 -top-24 size-80 rounded-full bg-accent/30 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-20 left-1/3 size-72 rounded-full bg-primary/50 blur-3xl"></div>
                <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex min-w-0 flex-1 items-start gap-4">
                        <div class="flex size-20 shrink-0 items-center justify-center rounded-2xl border border-white/25 bg-white/15 text-3xl font-black backdrop-blur-md">
                            {{ initial }}
                        </div>
                        <div class="min-w-0">
                            <div class="mb-2 inline-flex items-center gap-2 text-[10px] font-medium uppercase tracking-[0.18em] text-white/75">
                                <span class="relative flex size-2">
                                    <span v-if="!profile.suspended" class="absolute inline-flex size-full animate-ping rounded-full bg-success/70"></span>
                                    <span :class="`relative inline-flex size-2 rounded-full ${profile.suspended ? 'bg-destructive' : 'bg-success'}`"></span>
                                </span>
                                ملف العميل · بطاقة شاملة
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">{{ profile.full_name || 'ملف العميل' }}</h1>
                                <Badge v-if="profile.account_number" variant="outline" class="border-white/30 bg-white/15 font-mono text-white">
                                    <Hash class="size-3" /> {{ profile.account_number }}
                                </Badge>
                                <Badge v-if="profile.tier && profile.tier !== 'standard'" variant="warning" class="uppercase">{{ TIER[profile.tier] ?? profile.tier }}</Badge>
                                <Badge v-if="profile.suspended" variant="destructive">موقوف</Badge>
                                <Badge v-if="activeSubs > 0" class="border border-success/40 bg-success/25 text-white">مشترك نشط</Badge>
                                <Badge v-else variant="outline" class="border-white/20 bg-white/10 text-white/80">بدون اشتراك نشط</Badge>
                            </div>
                            <div class="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-white/80 sm:text-sm" dir="ltr">
                                <a v-if="profile.email" :href="`mailto:${profile.email}`" class="flex items-center gap-1.5 hover:text-white">
                                    <Mail class="size-3.5" /> {{ profile.email }}
                                </a>
                                <a v-if="profile.phone" :href="`tel:${profile.phone}`" class="flex items-center gap-1.5 hover:text-white">
                                    <Phone class="size-3.5" /> {{ profile.phone }}
                                </a>
                                <span v-if="profile.city || profile.region" class="flex items-center gap-1.5">
                                    <MapPin class="size-3.5" /> {{ [profile.city, profile.region].filter(Boolean).join('، ') }}
                                </span>
                            </div>
                            <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-white/65">
                                <span v-if="profile.business_field" class="inline-flex items-center gap-1">
                                    <Briefcase class="size-3" /> {{ profile.business_field }}
                                </span>
                                <span>عضو منذ {{ fmtDateAr(profile.created_at) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-wrap gap-2.5">
                        <div v-for="chip in heroChips" :key="chip.label"
                            class="flex min-w-[150px] items-center gap-3 rounded-2xl border border-white/15 bg-white/12 px-3.5 py-2.5 backdrop-blur-md">
                            <span :class="`flex size-9 shrink-0 items-center justify-center rounded-xl shadow-inner ${chip.ring}`">
                                <component :is="chip.icon" class="size-4" />
                            </span>
                            <div class="min-w-0">
                                <div class="text-[10px] uppercase tracking-wider text-white/70">{{ chip.label }}</div>
                                <div class="mt-0.5 text-lg font-extrabold tabular-nums text-white">{{ chip.value }}</div>
                            </div>
                        </div>
                        <Link :href="route('customers.index')"
                            class="inline-flex items-center gap-1.5 self-start rounded-xl border border-white/20 bg-white/12 px-3 py-2 text-xs font-semibold text-white transition hover:bg-white/20">
                            <ArrowLeft class="size-4" /> رجوع للعملاء
                        </Link>
                    </div>
                </div>
            </section>

            <!-- KPI ribbon -->
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-7">
                <Card v-for="k in kpis" :key="k.label" class="p-3">
                    <div class="flex items-start gap-3">
                        <div :class="`flex size-10 shrink-0 items-center justify-center rounded-xl ${k.chip}`">
                            <component :is="k.icon" class="size-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-[10px] font-medium uppercase tracking-wider text-muted-foreground">{{ k.label }}</div>
                            <div :class="`truncate text-xl font-black leading-tight tabular-nums ${k.numCls}`">{{ k.value }}</div>
                            <div v-if="k.sub" class="mt-0.5 text-[10px] text-muted-foreground">{{ k.sub }}</div>
                        </div>
                    </div>
                </Card>
            </div>

            <Tabs model-value="overview" class="space-y-4">
                <TabsList class="flex flex-wrap gap-1 border border-border/60 bg-card p-1">
                    <TabsTrigger value="overview" :class="TAB_CLS">نظرة عامة</TabsTrigger>
                    <TabsTrigger value="account" :class="TAB_CLS">بيانات العميل</TabsTrigger>
                    <TabsTrigger value="contacts" :class="TAB_CLS">جهات التواصل ({{ num(contacts.length) }})</TabsTrigger>
                    <TabsTrigger value="subscriptions" :class="TAB_CLS">المنتجات والاشتراكات ({{ num(subscriptions.length) }})</TabsTrigger>
                    <TabsTrigger value="requests" :class="TAB_CLS">الطلبات ({{ num(req.total ?? 0) }})</TabsTrigger>
                    <TabsTrigger value="suggestions" :class="TAB_CLS">المقترحات ({{ num(sug.total ?? 0) }})</TabsTrigger>
                    <TabsTrigger value="meetings" :class="TAB_CLS">المواعيد</TabsTrigger>
                    <TabsTrigger v-if="isStaff" value="internal_notes" :class="TAB_CLS">الملاحظات الداخلية</TabsTrigger>
                    <TabsTrigger v-if="isStaff" value="attachments" :class="TAB_CLS">المرفقات ({{ num(attachments.length) }})</TabsTrigger>
                    <TabsTrigger value="timeline" :class="TAB_CLS">سجل النشاط</TabsTrigger>
                    <TabsTrigger value="ratings" :class="TAB_CLS">التقييمات ({{ num(sat.count ?? 0) }})</TabsTrigger>
                </TabsList>

                <!-- Overview -->
                <TabsContent value="overview">
                    <div class="space-y-4">
                        <CustomerJourneyCard :profile="profile" :tasks="activationTasks" />
                        <CustomerHealthCard :stats="stats" />
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <Card>
                                <CardHeader class="pb-3">
                                    <CardTitle class="flex items-center gap-2 text-base">
                                        <span class="flex size-9 items-center justify-center rounded-xl bg-primary/15 text-primary"><TrendingUp class="size-4" /></span>
                                        ملخص الأداء
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-2 text-sm">
                                    <div v-for="row in summaryRows" :key="row.label"
                                        class="flex items-center justify-between border-b border-dashed pb-1.5 last:border-0">
                                        <span class="text-muted-foreground">{{ row.label }}</span>
                                        <span class="font-semibold tabular-nums text-foreground">{{ row.value }}</span>
                                    </div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader class="pb-3">
                                    <CardTitle class="flex items-center gap-2 text-base">
                                        <span class="flex size-9 items-center justify-center rounded-xl bg-accent/15 text-accent"><Briefcase class="size-4" /></span>
                                        أكثر التصنيفات والمنتجات
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-3">
                                    <div>
                                        <div class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">التصنيفات</div>
                                        <div v-if="!topCategories.length" class="text-xs italic text-muted-foreground">لا توجد بيانات</div>
                                        <div v-else class="flex flex-wrap gap-1.5">
                                            <Badge v-for="c in topCategories" :key="c.name" variant="outline" class="gap-1 border-primary/20 bg-primary/5">
                                                {{ c.name }} <span class="font-bold text-primary">{{ num(c.n) }}</span>
                                            </Badge>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">المنتجات</div>
                                        <div v-if="!topProducts.length" class="text-xs italic text-muted-foreground">لا توجد بيانات</div>
                                        <div v-else class="flex flex-wrap gap-1.5">
                                            <Badge v-for="p in topProducts" :key="p.name" variant="outline" class="gap-1 border-accent/20 bg-accent/5">
                                                <Package class="size-3" /> {{ p.name }} <span class="font-bold text-accent">{{ num(p.n) }}</span>
                                            </Badge>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </TabsContent>

                <!-- Account details -->
                <TabsContent value="account">
                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle class="flex items-center gap-2 text-base">
                                <span class="flex size-9 items-center justify-center rounded-xl bg-primary/15 text-primary"><Building2 class="size-4" /></span>
                                بيانات الحساب
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2.5 text-sm">
                            <div class="flex items-center justify-between gap-3 border-b border-dashed pb-1.5">
                                <span class="flex items-center gap-1.5 text-muted-foreground"><Hash class="size-3.5" /> رقم العميل</span>
                                <span class="font-mono font-semibold text-foreground">{{ profile.account_number || '—' }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3 border-b border-dashed pb-1.5">
                                <span class="text-muted-foreground">نوع الحساب</span>
                                <span class="font-semibold text-foreground">{{ ACCOUNT_TYPE[profile.account_type] ?? '—' }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3 border-b border-dashed pb-1.5">
                                <span class="text-muted-foreground">حالة الحساب</span>
                                <Badge :variant="accountStatus().tone">{{ accountStatus().label }}</Badge>
                            </div>
                            <div class="flex items-center justify-between gap-3 border-b border-dashed pb-1.5">
                                <span class="text-muted-foreground">التصنيف</span>
                                <Badge variant="outline" class="gap-1"><Award class="size-3" /> {{ TIER[profile.tier] ?? profile.tier ?? '—' }}</Badge>
                            </div>
                            <div class="flex items-center justify-between gap-3 border-b border-dashed pb-1.5">
                                <span class="text-muted-foreground">القطاع</span>
                                <span class="font-semibold text-foreground">{{ profile.business_field || '—' }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3 border-b border-dashed pb-1.5">
                                <span class="text-muted-foreground">المدينة / المنطقة</span>
                                <span class="font-semibold text-foreground">{{ [profile.city, profile.region].filter(Boolean).join('، ') || '—' }}</span>
                            </div>
                            <div v-if="profile.website" class="flex items-center justify-between gap-3 border-b border-dashed pb-1.5">
                                <span class="flex items-center gap-1.5 text-muted-foreground"><Globe class="size-3.5" /> الموقع الإلكتروني</span>
                                <a :href="profile.website" target="_blank" rel="noreferrer" dir="ltr" class="truncate font-semibold text-primary hover:underline">{{ profile.website }}</a>
                            </div>
                            <div class="flex items-center justify-between gap-3 border-b border-dashed pb-1.5">
                                <span class="flex items-center gap-1.5 text-muted-foreground"><UserCheck class="size-3.5" /> مدير الحساب</span>
                                <span class="font-semibold text-foreground">{{ profile.account_manager_name || 'غير معيّن' }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-muted-foreground">آخر تواصل</span>
                                <span class="font-semibold text-foreground">{{ profile.last_contact_at ? fmtDateAr(profile.last_contact_at) : '—' }}</span>
                            </div>
                            <div v-if="isStaff && profile.internal_notes" class="mt-2 border-t border-dashed pt-2">
                                <div class="mb-1 flex items-center gap-1 text-[10px] uppercase tracking-wider text-muted-foreground">
                                    <StickyNote class="size-3" /> ملاحظات داخلية (للموظفين فقط)
                                </div>
                                <div class="whitespace-pre-wrap rounded-lg border border-warning/20 bg-warning/5 p-2 text-sm text-foreground">{{ profile.internal_notes }}</div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Contacts -->
                <TabsContent value="contacts">
                    <Card>
                        <CardHeader><CardTitle class="flex items-center gap-2 text-base"><UserCircle2 class="size-5 text-primary" /> جهات التواصل</CardTitle></CardHeader>
                        <CardContent class="p-0">
                            <Table v-if="contacts.length">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>الاسم</TableHead>
                                        <TableHead>المسمى</TableHead>
                                        <TableHead>القسم</TableHead>
                                        <TableHead>البريد</TableHead>
                                        <TableHead>الجوال</TableHead>
                                        <TableHead class="text-center">أساسي</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="ct in contacts" :key="ct.id">
                                        <TableCell class="font-medium">{{ ct.full_name }}</TableCell>
                                        <TableCell class="text-muted-foreground">{{ ct.job_title || '—' }}</TableCell>
                                        <TableCell class="text-muted-foreground">{{ ct.department || '—' }}</TableCell>
                                        <TableCell dir="ltr" class="text-xs">{{ ct.email || '—' }}</TableCell>
                                        <TableCell dir="ltr" class="text-xs">{{ ct.mobile || ct.phone || '—' }}</TableCell>
                                        <TableCell class="text-center">
                                            <Badge v-if="ct.is_primary" variant="accent">أساسي</Badge>
                                            <span v-else class="text-muted-foreground">—</span>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                            <div v-else class="p-8 text-center text-sm text-muted-foreground">لا توجد جهات تواصل مسجّلة.</div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Subscriptions -->
                <TabsContent value="subscriptions">
                    <Card>
                        <CardHeader><CardTitle class="flex items-center gap-2 text-base"><Package class="size-5 text-primary" /> الاشتراكات في المنتجات</CardTitle></CardHeader>
                        <CardContent class="p-0">
                            <Table v-if="subscriptions.length">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>المنتج</TableHead>
                                        <TableHead>الباقة</TableHead>
                                        <TableHead class="text-center">الحالة</TableHead>
                                        <TableHead class="text-center">البداية</TableHead>
                                        <TableHead class="text-center">النهاية</TableHead>
                                        <TableHead class="text-center">المصدر</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="s in subscriptions" :key="s.id">
                                        <TableCell class="font-medium">{{ s.product_name }}</TableCell>
                                        <TableCell class="text-muted-foreground">{{ s.plan_name || '—' }}</TableCell>
                                        <TableCell class="text-center"><Badge :variant="subStatus(s.status).tone">{{ subStatus(s.status).label }}</Badge></TableCell>
                                        <TableCell class="text-center text-xs">{{ fmtDateAr(s.start_date) }}</TableCell>
                                        <TableCell class="text-center text-xs">{{ s.end_date ? fmtDateAr(s.end_date) : '—' }}</TableCell>
                                        <TableCell class="text-center text-xs text-muted-foreground">{{ s.source || '—' }}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                            <div v-else class="p-8 text-center text-sm text-muted-foreground">
                                لا توجد اشتراكات مسجّلة لهذا العميل. ستظهر هنا تلقائياً عند ربط منصة العملاء.
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Requests -->
                <TabsContent value="requests">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between">
                            <CardTitle class="flex items-center gap-2 text-base"><FileText class="size-5 text-primary" /> أحدث الطلبات</CardTitle>
                            <Link :href="`/requests?customer=${profile.id}`" class="text-xs text-primary hover:underline">عرض كل الطلبات ←</Link>
                        </CardHeader>
                        <CardContent class="p-0">
                            <div v-if="!requests.length" class="p-8 text-center text-sm text-muted-foreground">لا توجد طلبات.</div>
                            <div v-else class="divide-y divide-border">
                                <Link v-for="r in requests" :key="r.id" :href="`/requests/${r.id}`"
                                    class="flex items-start gap-3 p-3 transition hover:bg-muted/30">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-mono text-xs text-muted-foreground">{{ r.request_number }}</span>
                                            <span class="truncate font-medium text-foreground">{{ r.title }}</span>
                                        </div>
                                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                            <span v-if="r.category_name">📂 {{ r.category_name }}</span>
                                            <span v-if="r.product_name">📦 {{ r.product_name }}</span>
                                            <span v-if="r.assigned_name">👤 {{ r.assigned_name }}</span>
                                            <span>· {{ timeAgoAr(r.created_at) }}</span>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 flex-col items-end gap-1">
                                        <StatusBadge :status="r.status" />
                                        <span class="text-[10px] text-muted-foreground">{{ priorityLabel(r.priority) }}</span>
                                    </div>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Suggestions -->
                <TabsContent value="suggestions">
                    <Card>
                        <CardHeader><CardTitle class="flex items-center gap-2 text-base"><Lightbulb class="size-5 text-accent" /> المقترحات المُقدّمة</CardTitle></CardHeader>
                        <CardContent class="p-0">
                            <div v-if="!suggestions.length" class="p-8 text-center text-sm text-muted-foreground">لم يقدّم العميل أي مقترح بعد.</div>
                            <div v-else class="divide-y divide-border">
                                <Link v-for="s in suggestions" :key="s.id" :href="`/suggestions/${s.id}`"
                                    class="flex items-start gap-3 p-3 transition hover:bg-muted/30">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-mono text-xs text-muted-foreground">{{ s.request_number }}</span>
                                            <span class="truncate font-medium text-foreground">{{ s.title }}</span>
                                            <Badge v-if="s.published_to_customers" variant="outline" class="text-[10px]">منشور</Badge>
                                        </div>
                                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                            <span v-if="s.product_name">📦 {{ s.product_name }}</span>
                                            <span>· {{ timeAgoAr(s.created_at) }}</span>
                                            <span class="inline-flex items-center gap-0.5">· <ThumbsUp class="size-3" /> {{ num(s.votes ?? 0) }} مؤيد</span>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 flex-col items-end gap-1">
                                        <Badge variant="outline">{{ stageLabel(s.idea_stage) }}</Badge>
                                        <span v-if="s.decision && s.decision !== 'pending'" class="text-[10px] text-muted-foreground">{{ s.decision }}</span>
                                    </div>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Meetings -->
                <TabsContent value="meetings">
                    <Card>
                        <CardHeader><CardTitle class="flex items-center gap-2 text-base"><Calendar class="size-5 text-primary" /> المواعيد والاجتماعات</CardTitle></CardHeader>
                        <CardContent class="space-y-5">
                            <div v-if="!meetings.length" class="py-8 text-center text-sm text-muted-foreground">لا توجد مواعيد مسجلة لهذا العميل.</div>
                            <template v-else>
                                <div v-for="section in [{ title: 'القادمة', items: upcomingMeetings }, { title: 'السابقة', items: pastMeetings }]" :key="section.title">
                                    <div class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                                        {{ section.title }} ({{ num(section.items.length) }})
                                    </div>
                                    <div v-if="!section.items.length" class="px-2 py-3 text-xs italic text-muted-foreground">لا توجد مواعيد.</div>
                                    <ul v-else class="space-y-2">
                                        <li v-for="m in section.items" :key="m.id" class="rounded-xl border border-border/60 bg-card p-3 transition hover:bg-muted/20">
                                            <div class="flex flex-wrap items-start justify-between gap-3">
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <Badge variant="outline" class="text-[10px]">{{ EVENT_TYPE[m.event_type] ?? m.event_type }}</Badge>
                                                        <span class="truncate font-semibold text-foreground">{{ m.title }}</span>
                                                    </div>
                                                    <p v-if="m.description" class="mt-1 line-clamp-2 text-xs text-muted-foreground">{{ m.description }}</p>
                                                    <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-muted-foreground">
                                                        <span class="inline-flex items-center gap-1"><Clock class="size-3" /> {{ fmtFullDateTimeAr(m.starts_at) }}</span>
                                                        <span v-if="m.location" class="inline-flex items-center gap-1"><MapPin class="size-3" /> {{ m.location }}</span>
                                                        <a v-if="m.meeting_url" :href="m.meeting_url" target="_blank" rel="noreferrer" class="inline-flex items-center gap-1 text-primary hover:underline">
                                                            <Video class="size-3" /> رابط الاجتماع
                                                        </a>
                                                        <span v-if="m.assigned_name" class="inline-flex items-center gap-1"><UserCheck class="size-3" /> {{ m.assigned_name }}</span>
                                                    </div>
                                                </div>
                                                <Badge :variant="eventStatus(m.status).tone">{{ eventStatus(m.status).label }}</Badge>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </template>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Internal notes (staff) -->
                <TabsContent v-if="isStaff" value="internal_notes">
                    <Card>
                        <CardHeader><CardTitle class="flex items-center gap-2 text-base"><StickyNote class="size-5 text-warning" /> الملاحظات الداخلية</CardTitle></CardHeader>
                        <CardContent>
                            <div v-if="profile.internal_notes" class="whitespace-pre-wrap rounded-lg border border-warning/20 bg-warning/5 p-3 text-sm text-foreground">{{ profile.internal_notes }}</div>
                            <div v-else class="p-8 text-center text-sm text-muted-foreground">لا توجد ملاحظات داخلية مسجّلة.</div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Attachments (staff) -->
                <TabsContent v-if="isStaff" value="attachments">
                    <Card>
                        <CardHeader><CardTitle class="flex items-center gap-2 text-base"><Paperclip class="size-5 text-primary" /> المرفقات</CardTitle></CardHeader>
                        <CardContent class="space-y-2">
                            <div v-if="!attachments.length" class="p-8 text-center text-sm text-muted-foreground">لا توجد مرفقات.</div>
                            <div v-for="at in attachments" :key="at.id" class="flex items-center justify-between gap-3 rounded-lg border border-border p-2.5 text-sm">
                                <div class="flex min-w-0 items-center gap-2">
                                    <CheckCircle2 class="size-4 shrink-0 text-primary" />
                                    <div class="min-w-0">
                                        <div class="truncate font-medium">{{ at.file_name }}</div>
                                        <div class="text-xs text-muted-foreground">{{ at.category }}<span v-if="at.description"> · {{ at.description }}</span></div>
                                    </div>
                                </div>
                                <span class="whitespace-nowrap text-xs text-muted-foreground">{{ fmtDateAr(at.created_at) }}</span>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Unified timeline -->
                <TabsContent value="timeline">
                    <CustomerTimelineTab :activities="activities" :requests="requests" :suggestions="suggestions" :ratings="ratings" />
                </TabsContent>

                <!-- Ratings -->
                <TabsContent value="ratings">
                    <Card>
                        <CardHeader><CardTitle class="flex items-center gap-2 text-base"><Star class="size-5 text-warning" /> تقييمات العميل</CardTitle></CardHeader>
                        <CardContent class="space-y-2">
                            <div v-if="!ratings.length" class="p-8 text-center text-sm text-muted-foreground">لم يقم العميل بأي تقييم بعد.</div>
                            <div v-for="r in ratings" :key="r.id" class="rounded-lg border border-border bg-card/50 p-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-1">
                                        <Star v-for="i in 5" :key="i" :class="i <= r.stars ? 'fill-warning text-warning' : 'text-muted-foreground/30'" class="size-4" />
                                        <span class="mr-2 text-sm font-bold tabular-nums">{{ r.stars }}/5</span>
                                    </div>
                                    <Link :href="`/requests/${r.request_id}`" class="font-mono text-xs text-primary hover:underline">{{ r.request_number }}</Link>
                                </div>
                                <div class="mt-1 text-xs text-muted-foreground">{{ r.request_title }}</div>
                                <div v-if="r.notes" class="mt-1.5 rounded bg-muted/40 p-2 text-sm">{{ r.notes }}</div>
                                <div class="mt-1 text-[10px] text-muted-foreground">{{ fmtFullDateTimeAr(r.created_at) }}</div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    </AppShell>
</template>
