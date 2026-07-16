<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import KpiCard from '@/Components/KpiCard.vue';
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
import { num } from '@/lib/utils';
import { REQUEST_PRIORITY, statusLabel } from '@/lib/labels';
import { fmtDateAr, fmtFullDateTimeAr, timeAgoAr } from '@/lib/date';
import {
    ArrowLeft, Mail, Phone, MapPin, Briefcase, Hash, FileText, Users,
    Package, Star, Activity, AlertTriangle, UserCircle2, CheckCircle2, ArrowLeftRight,
} from 'lucide-vue-next';

const props = defineProps({
    profile: { type: Object, required: true },
    requests: { type: Array, default: () => [] },
    contacts: { type: Array, default: () => [] },
    subscriptions: { type: Array, default: () => [] },
    activities: { type: Array, default: () => [] },
    ratings: { type: Array, default: () => [] },
    attachments: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    canManage: { type: Boolean, default: false },
});

const SUB_STATUS = {
    active: { label: 'ساري', tone: 'success' },
    trial: { label: 'تجريبي', tone: 'warning' },
    expired: { label: 'منتهٍ', tone: 'destructive' },
    cancelled: { label: 'ملغى', tone: 'muted' },
    suspended: { label: 'موقوف', tone: 'muted' },
};
function subStatus(s) {
    return SUB_STATUS[s] ?? { label: s ?? '—', tone: 'muted' };
}
function priorityLabel(p) {
    return statusLabel(REQUEST_PRIORITY, p).label;
}
</script>

<template>
    <Head :title="profile.full_name || 'ملف العميل'" />
    <AppShell>
        <div class="space-y-5">
            <!-- Hero -->
            <section class="relative overflow-hidden rounded-2xl p-6 text-white shadow-elevated"
                style="background-image: var(--gradient-hero);">
                <div class="absolute inset-0 opacity-20"
                    style="background-image: radial-gradient(closest-side, rgba(255,255,255,.4), transparent);"></div>
                <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex size-16 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-2xl font-black backdrop-blur-sm">
                            {{ (profile.full_name || '؟').charAt(0) }}
                        </div>
                        <div class="min-w-0">
                            <p class="mb-1.5 text-[10px] font-medium uppercase tracking-[0.18em] text-white/75">
                                ملف العميل · بطاقة شاملة
                            </p>
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">{{ profile.full_name || 'ملف العميل' }}</h1>
                                <Badge v-if="profile.account_number" variant="outline"
                                    class="border-white/30 bg-white/15 font-mono text-white">
                                    <Hash class="size-3" /> {{ profile.account_number }}
                                </Badge>
                                <Badge v-if="profile.suspended" variant="destructive">موقوف</Badge>
                                <Badge v-if="stats.active_subscriptions > 0" class="border border-success/40 bg-success/25 text-white">مشترك نشط</Badge>
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
                    <Link :href="route('customers.index')"
                        class="inline-flex items-center gap-1.5 self-start rounded-xl border border-white/20 bg-white/12 px-3 py-2 text-xs font-semibold text-white transition hover:bg-white/20">
                        <ArrowLeft class="size-4" /> رجوع للعملاء
                    </Link>
                </div>
            </section>

            <!-- KPI ribbon -->
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
                <KpiCard label="إجمالي الطلبات" :value="stats.requests_total ?? 0" :icon="FileText" tone="primary" />
                <KpiCard label="مفتوحة" :value="stats.requests_open ?? 0" :icon="Activity"
                    :tone="stats.requests_open > 0 ? 'warning' : 'muted'" />
                <KpiCard label="متأخرة" :value="stats.requests_overdue ?? 0" :icon="AlertTriangle"
                    :tone="stats.requests_overdue > 0 ? 'destructive' : 'muted'" />
                <KpiCard label="جهات التواصل" :value="stats.contacts_count ?? 0" :icon="UserCircle2" tone="accent" />
                <KpiCard label="رضا العميل" :value="stats.csat != null ? `${stats.csat}/5` : '—'"
                    :format-number="false" :hint="`${stats.csat_count ?? 0} تقييم`" :icon="Star" tone="warning" />
                <KpiCard label="اشتراكات نشطة" :value="stats.active_subscriptions ?? 0" :icon="Users" tone="success" />
            </div>

            <Tabs model-value="overview" class="space-y-4">
                <TabsList class="flex flex-wrap">
                    <TabsTrigger value="overview">نظرة عامة</TabsTrigger>
                    <TabsTrigger value="account">بيانات العميل</TabsTrigger>
                    <TabsTrigger value="contacts">جهات التواصل ({{ num(contacts.length) }})</TabsTrigger>
                    <TabsTrigger value="subscriptions">الاشتراكات ({{ num(subscriptions.length) }})</TabsTrigger>
                    <TabsTrigger value="requests">الطلبات ({{ num(stats.requests_total ?? 0) }})</TabsTrigger>
                    <TabsTrigger value="ratings">التقييمات ({{ num(ratings.length) }})</TabsTrigger>
                    <TabsTrigger value="activity">سجل النشاط</TabsTrigger>
                </TabsList>

                <!-- Overview -->
                <TabsContent value="overview">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <Card>
                            <CardHeader><CardTitle class="text-base">ملخص الأداء</CardTitle></CardHeader>
                            <CardContent class="space-y-2 text-sm">
                                <div class="flex items-center justify-between border-b border-dashed pb-1.5">
                                    <span class="text-muted-foreground">إجمالي الطلبات</span>
                                    <span class="font-bold tabular-nums">{{ num(stats.requests_total ?? 0) }}</span>
                                </div>
                                <div class="flex items-center justify-between border-b border-dashed pb-1.5">
                                    <span class="text-muted-foreground">مفتوحة</span>
                                    <span class="font-bold tabular-nums">{{ num(stats.requests_open ?? 0) }}</span>
                                </div>
                                <div class="flex items-center justify-between border-b border-dashed pb-1.5">
                                    <span class="text-muted-foreground">مغلقة</span>
                                    <span class="font-bold tabular-nums">{{ num(stats.requests_closed ?? 0) }}</span>
                                </div>
                                <div class="flex items-center justify-between border-b border-dashed pb-1.5">
                                    <span class="text-muted-foreground">متأخرة</span>
                                    <span class="font-bold tabular-nums">{{ num(stats.requests_overdue ?? 0) }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-muted-foreground">متوسط الرضا</span>
                                    <span class="font-bold tabular-nums">{{ stats.csat != null ? `${stats.csat}/5` : '—' }}</span>
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader><CardTitle class="text-base">حالة الحساب</CardTitle></CardHeader>
                            <CardContent class="space-y-2 text-sm">
                                <div class="flex items-center justify-between border-b border-dashed pb-1.5">
                                    <span class="text-muted-foreground">الحالة</span>
                                    <Badge :variant="profile.suspended ? 'destructive' : 'success'">
                                        {{ profile.suspended ? 'موقوف' : (profile.account_status || 'نشط') }}
                                    </Badge>
                                </div>
                                <div class="flex items-center justify-between border-b border-dashed pb-1.5">
                                    <span class="text-muted-foreground">اشتراكات نشطة</span>
                                    <span class="font-bold tabular-nums">{{ num(stats.active_subscriptions ?? 0) }}</span>
                                </div>
                                <div class="flex items-center justify-between border-b border-dashed pb-1.5">
                                    <span class="text-muted-foreground">جهات التواصل</span>
                                    <span class="font-bold tabular-nums">{{ num(stats.contacts_count ?? 0) }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-muted-foreground">آخر تواصل</span>
                                    <span class="font-medium">{{ profile.last_contact_at ? fmtDateAr(profile.last_contact_at) : '—' }}</span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <!-- Account details -->
                <TabsContent value="account">
                    <Card>
                        <CardHeader><CardTitle class="text-base">بيانات العميل</CardTitle></CardHeader>
                        <CardContent>
                            <dl class="grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                                <div><dt class="text-xs text-muted-foreground">الاسم الكامل</dt><dd class="font-medium">{{ profile.full_name || '—' }}</dd></div>
                                <div><dt class="text-xs text-muted-foreground">رقم الحساب</dt><dd class="font-mono">{{ profile.account_number || '—' }}</dd></div>
                                <div><dt class="text-xs text-muted-foreground">البريد الإلكتروني</dt><dd dir="ltr" class="truncate">{{ profile.email || '—' }}</dd></div>
                                <div><dt class="text-xs text-muted-foreground">الجوال</dt><dd dir="ltr">{{ profile.phone || '—' }}</dd></div>
                                <div><dt class="text-xs text-muted-foreground">المجال</dt><dd>{{ profile.business_field || '—' }}</dd></div>
                                <div><dt class="text-xs text-muted-foreground">المدينة / المنطقة</dt><dd>{{ [profile.city, profile.region].filter(Boolean).join('، ') || '—' }}</dd></div>
                                <div><dt class="text-xs text-muted-foreground">الشريحة</dt><dd>{{ profile.tier || '—' }}</dd></div>
                                <div><dt class="text-xs text-muted-foreground">مرحلة الرحلة</dt><dd>{{ profile.journey_stage || '—' }}</dd></div>
                                <div v-if="profile.website"><dt class="text-xs text-muted-foreground">الموقع</dt><dd dir="ltr" class="truncate">{{ profile.website }}</dd></div>
                                <div><dt class="text-xs text-muted-foreground">عضو منذ</dt><dd>{{ fmtDateAr(profile.created_at) }}</dd></div>
                            </dl>
                            <div v-if="canManage && profile.internal_notes" class="mt-4 rounded-lg border border-warning/30 bg-warning/5 p-3 text-sm">
                                <div class="mb-1 text-xs font-bold text-warning">ملاحظات داخلية</div>
                                <p class="whitespace-pre-wrap text-muted-foreground">{{ profile.internal_notes }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Contacts -->
                <TabsContent value="contacts">
                    <Card>
                        <CardHeader><CardTitle class="text-base">جهات التواصل</CardTitle></CardHeader>
                        <CardContent class="p-0">
                            <Table v-if="contacts.length">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>الاسم</TableHead>
                                        <TableHead>المسمى</TableHead>
                                        <TableHead>البريد</TableHead>
                                        <TableHead>الجوال</TableHead>
                                        <TableHead class="text-center">أساسي</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="ct in contacts" :key="ct.id">
                                        <TableCell class="font-medium">{{ ct.full_name }}</TableCell>
                                        <TableCell class="text-muted-foreground">{{ ct.job_title || '—' }}</TableCell>
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
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <Package class="size-5 text-primary" /> الاشتراكات في المنتجات
                            </CardTitle>
                        </CardHeader>
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
                                        <TableCell class="text-center">
                                            <Badge :variant="subStatus(s.status).tone">{{ subStatus(s.status).label }}</Badge>
                                        </TableCell>
                                        <TableCell class="text-center text-xs">{{ fmtDateAr(s.start_date) }}</TableCell>
                                        <TableCell class="text-center text-xs">{{ s.end_date ? fmtDateAr(s.end_date) : '—' }}</TableCell>
                                        <TableCell class="text-center text-xs text-muted-foreground">{{ s.source || '—' }}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                            <div v-else class="p-8 text-center text-sm text-muted-foreground">
                                لا توجد اشتراكات مسجّلة لهذا العميل.
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Requests -->
                <TabsContent value="requests">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <FileText class="size-5 text-primary" /> أحدث الطلبات
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="p-0">
                            <Table v-if="requests.length">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>الرقم</TableHead>
                                        <TableHead>العنوان</TableHead>
                                        <TableHead>التصنيف</TableHead>
                                        <TableHead class="text-center">الأولوية</TableHead>
                                        <TableHead class="text-center">الحالة</TableHead>
                                        <TableHead class="text-center">التاريخ</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="r in requests" :key="r.id">
                                        <TableCell class="font-mono text-xs text-muted-foreground">{{ r.request_number }}</TableCell>
                                        <TableCell class="font-medium">
                                            <div class="truncate max-w-[280px]">{{ r.title }}</div>
                                            <div v-if="r.product_name" class="text-xs text-muted-foreground">📦 {{ r.product_name }}</div>
                                        </TableCell>
                                        <TableCell class="text-xs text-muted-foreground">{{ r.category_name || '—' }}</TableCell>
                                        <TableCell class="text-center text-xs">{{ priorityLabel(r.priority) }}</TableCell>
                                        <TableCell class="text-center"><StatusBadge :status="r.status" /></TableCell>
                                        <TableCell class="text-center text-xs text-muted-foreground whitespace-nowrap">{{ timeAgoAr(r.created_at) }}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                            <div v-else class="p-8 text-center text-sm text-muted-foreground">لا توجد طلبات.</div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Ratings -->
                <TabsContent value="ratings">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <Star class="size-5 text-warning" /> تقييمات العميل
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <div v-if="!ratings.length" class="p-8 text-center text-sm text-muted-foreground">لم يقم العميل بأي تقييم بعد.</div>
                            <div v-for="r in ratings" :key="r.id" class="rounded-lg border border-border bg-card/50 p-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-1">
                                        <Star v-for="i in 5" :key="i"
                                            :class="i <= r.stars ? 'fill-warning text-warning' : 'text-muted-foreground/30'"
                                            class="size-4" />
                                        <span class="mr-2 text-sm font-bold tabular-nums">{{ r.stars }}/5</span>
                                    </div>
                                    <span class="font-mono text-xs text-muted-foreground">{{ r.request_number }}</span>
                                </div>
                                <div class="mt-1 text-xs text-muted-foreground">{{ r.request_title }}</div>
                                <div v-if="r.notes" class="mt-1.5 rounded bg-muted/40 p-2 text-sm">{{ r.notes }}</div>
                                <div class="mt-1 text-[10px] text-muted-foreground">{{ fmtFullDateTimeAr(r.created_at) }}</div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Activity timeline -->
                <TabsContent value="activity">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <ArrowLeftRight class="size-5 text-primary" /> سجل النشاط
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="!activities.length" class="p-8 text-center text-sm text-muted-foreground">لا يوجد نشاط مُسجّل.</div>
                            <ol v-else class="relative space-y-4 border-r border-border pr-4">
                                <li v-for="a in activities" :key="a.id" class="relative">
                                    <span class="absolute -right-[21px] top-1.5 size-2.5 rounded-full bg-primary"></span>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-medium text-foreground">{{ a.subject }}</span>
                                        <Badge variant="muted" class="text-[10px]">{{ a.activity_type }}</Badge>
                                    </div>
                                    <p v-if="a.summary" class="mt-0.5 text-sm text-muted-foreground">{{ a.summary }}</p>
                                    <div class="mt-0.5 text-[11px] text-muted-foreground">{{ fmtFullDateTimeAr(a.occurred_at) }}</div>
                                </li>
                            </ol>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>

            <!-- Attachments (staff) -->
            <Card v-if="canManage && attachments.length">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <CheckCircle2 class="size-5 text-primary" /> المرفقات
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-2">
                    <div v-for="at in attachments" :key="at.id"
                        class="flex items-center justify-between rounded-lg border border-border p-2.5 text-sm">
                        <div class="min-w-0">
                            <div class="truncate font-medium">{{ at.file_name }}</div>
                            <div class="text-xs text-muted-foreground">{{ at.category }}<span v-if="at.description"> · {{ at.description }}</span></div>
                        </div>
                        <span class="text-xs text-muted-foreground whitespace-nowrap">{{ fmtDateAr(at.created_at) }}</span>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppShell>
</template>
