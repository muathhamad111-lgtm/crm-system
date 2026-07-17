<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
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
import SortableTh from '@/Components/ui/SortableTh.vue';
import TableCell from '@/Components/ui/TableCell.vue';
import {
    Store, Users, Package, Mail, Phone, Building2, User as UserIcon, FileText,
    CheckCircle2, Calendar, Briefcase, Hash, ShieldCheck, Clock, ArrowLeft, Tag,
} from 'lucide-vue-next';
import { num } from '@/lib/utils';
import { fmtDateAr, fmtFullDateTimeAr } from '@/lib/date';
import { useClientSort } from '@/lib/useSort';

const props = defineProps({
    customer: { type: Object, default: () => ({}) },
    contacts: { type: Array, default: () => [] },
    subscriptions: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
});

const ENTITY_LABEL = { individual: 'فَرد', non_profit: 'قطاع غير ربحي', private_org: 'قطاع خاص' };
const ROLE_LABEL = {
    representative: 'مفوّض الجهة', it_lead: 'مسؤول تقني',
    finance_lead: 'مسؤول مالي', support_lead: 'مسؤول التواصل',
};
const SUB_TONE = { active: 'success', expired: 'warning', pending: 'info', cancelled: 'destructive', deleted: 'muted' };
const entityLabel = (e) => ENTITY_LABEL[e] ?? e;

// --- Client-side sorting (preserved) ---
const {
    sorted: sortedSubs, sortKey: subsSortKey, sortDir: subsSortDir, toggle: toggleSubs,
} = useClientSort(() => props.subscriptions, null, 'asc', {
    product: (r) => r.product_name || r.product_code, plan: 'package_name', status: 'status', start: 'start_date',
});
const {
    sorted: sortedContacts, sortKey: contactsSortKey, sortDir: contactsSortDir, toggle: toggleContacts,
} = useClientSort(() => props.contacts, null, 'asc', {
    name: 'full_name', role: 'role_type', mobile: 'mobile',
});
</script>

<template>
    <Head :title="customer.full_name ?? 'عميل المتجر'" />
    <AppShell>
        <div class="space-y-5">
            <!-- Gradient hero -->
            <div class="relative overflow-hidden rounded-2xl p-6 sm:p-8 text-white shadow-elevated" style="background-image: var(--gradient-hero);">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(closest-side at 75% 20%, rgba(255,255,255,.4), transparent);"></div>
                <div class="relative flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="flex size-12 items-center justify-center rounded-xl bg-white/15 backdrop-blur-sm">
                            <component :is="customer.entity_type === 'individual' ? UserIcon : Building2" class="size-6" />
                        </div>
                        <div>
                            <p class="text-xs text-white/60">عميل من المتجر · {{ entityLabel(customer.entity_type) }}</p>
                            <h1 class="mt-0.5 text-2xl sm:text-3xl font-bold tracking-tight">{{ customer.full_name ?? '—' }}</h1>
                            <p class="mt-1 text-sm text-white/80" dir="ltr">{{ customer.email || customer.phone || 'بيانات مزامنة من المتجر' }}</p>
                        </div>
                    </div>
                    <Link href="/tts-customers"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-white/20 bg-white/12 px-3 py-2 text-xs font-semibold text-white backdrop-blur-md transition hover:bg-white/20">
                        <ArrowLeft class="size-4" /> رجوع للقائمة
                    </Link>
                </div>
            </div>

            <!-- Customer data card -->
            <Card>
                <CardContent class="p-5">
                    <div class="flex flex-wrap items-start gap-4">
                        <div class="flex size-16 shrink-0 items-center justify-center rounded-2xl bg-primary-soft text-primary">
                            <component :is="customer.entity_type === 'individual' ? UserIcon : Building2" class="size-7" />
                        </div>
                        <div class="min-w-[240px] flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-lg font-bold text-foreground">{{ customer.full_name ?? '—' }}</h2>
                                <Badge variant="outline">{{ entityLabel(customer.entity_type) }}</Badge>
                                <Badge v-if="customer.status && customer.status !== 'active'" variant="destructive">{{ customer.status }}</Badge>
                            </div>
                            <dl class="mt-3 grid gap-x-6 gap-y-2 text-xs sm:grid-cols-2">
                                <div v-if="customer.email" class="flex items-center gap-1.5"><Mail class="size-3.5 text-muted-foreground" /><dt class="text-muted-foreground">البريد:</dt><dd class="font-medium" dir="ltr">{{ customer.email }}</dd></div>
                                <div v-if="customer.phone" class="flex items-center gap-1.5"><Phone class="size-3.5 text-muted-foreground" /><dt class="text-muted-foreground">الجوال:</dt><dd class="font-medium" dir="ltr">{{ customer.phone }}</dd></div>
                                <div v-if="customer.national_id" class="flex items-center gap-1.5"><Hash class="size-3.5 text-muted-foreground" /><dt class="text-muted-foreground">رقم الهوية:</dt><dd class="font-medium">{{ customer.national_id }}</dd></div>
                                <div v-if="customer.license_no" class="flex items-center gap-1.5"><ShieldCheck class="size-3.5 text-muted-foreground" /><dt class="text-muted-foreground">السجل التجاري:</dt><dd class="font-medium">{{ customer.license_no }}</dd></div>
                                <div v-if="customer.tax_no" class="flex items-center gap-1.5"><FileText class="size-3.5 text-muted-foreground" /><dt class="text-muted-foreground">الرقم الضريبي:</dt><dd class="font-medium">{{ customer.tax_no }}</dd></div>
                                <div v-if="customer.business_field" class="flex items-center gap-1.5"><Briefcase class="size-3.5 text-muted-foreground" /><dt class="text-muted-foreground">مجال العمل:</dt><dd class="font-medium">{{ customer.business_field }}</dd></div>
                                <div v-if="customer.foundation_date" class="flex items-center gap-1.5"><Calendar class="size-3.5 text-muted-foreground" /><dt class="text-muted-foreground">تاريخ التأسيس:</dt><dd class="font-medium">{{ fmtDateAr(customer.foundation_date) }}</dd></div>
                            </dl>
                        </div>
                        <!-- Stat pills -->
                        <div class="flex flex-wrap gap-3">
                            <div class="rounded-xl border border-success/30 bg-success/10 px-4 py-2 text-center">
                                <div class="text-lg font-bold tabular-nums text-success">{{ num(stats.active_subscriptions ?? 0) }}</div>
                                <div class="text-[10px] text-muted-foreground">اشتراك نشط</div>
                            </div>
                            <div class="rounded-xl border border-primary/20 bg-primary-soft px-4 py-2 text-center">
                                <div class="text-lg font-bold tabular-nums text-primary">{{ num(stats.subscriptions_count ?? subscriptions.length) }}</div>
                                <div class="text-[10px] text-muted-foreground">إجمالي الاشتراكات</div>
                            </div>
                            <div class="rounded-xl border border-accent/30 bg-accent/10 px-4 py-2 text-center">
                                <div class="text-lg font-bold tabular-nums text-accent">{{ num(stats.contacts_count ?? contacts.length) }}</div>
                                <div class="text-[10px] text-muted-foreground">موظف/مفوّض</div>
                            </div>
                        </div>
                    </div>

                    <!-- Sync info -->
                    <div class="mt-4 flex flex-wrap items-center gap-4 border-t border-border pt-4 text-[11px] text-muted-foreground">
                        <span class="inline-flex items-center gap-1"><Clock class="size-3" /> آخر مزامنة: {{ customer.last_synced_at ? fmtFullDateTimeAr(customer.last_synced_at) : '—' }}</span>
                        <span v-if="customer.external_id" class="inline-flex items-center gap-1"><Hash class="size-3" /> معرّف المتجر: {{ customer.external_id }}</span>
                        <span v-if="customer.source" class="inline-flex items-center gap-1"><Tag class="size-3" /> المصدر: {{ customer.source }}</span>
                    </div>
                </CardContent>
            </Card>

            <!-- Tabs: subscriptions + contacts -->
            <Tabs model-value="subscriptions">
                <TabsList>
                    <TabsTrigger value="subscriptions" class="data-[state=active]:bg-card data-[state=active]:text-foreground data-[state=active]:shadow-sm">
                        <Package class="size-3.5" /> الاشتراكات ({{ num(subscriptions.length) }})
                    </TabsTrigger>
                    <TabsTrigger value="contacts" class="data-[state=active]:bg-card data-[state=active]:text-foreground data-[state=active]:shadow-sm">
                        <Users class="size-3.5" /> الموظفون والمفوّضون ({{ num(contacts.length) }})
                    </TabsTrigger>
                </TabsList>

                <TabsContent value="subscriptions">
                    <Card>
                        <CardHeader><CardTitle class="text-sm">اشتراكات هذا العميل عبر منتجات التحول التقني</CardTitle></CardHeader>
                        <CardContent class="p-0">
                            <Table v-if="subscriptions.length">
                                <TableHeader><TableRow>
                                    <SortableTh col="product" :sort-key="subsSortKey" :sort-dir="subsSortDir" @sort="toggleSubs">المنتج</SortableTh>
                                    <SortableTh col="plan" :sort-key="subsSortKey" :sort-dir="subsSortDir" @sort="toggleSubs">الباقة</SortableTh>
                                    <SortableTh col="status" :sort-key="subsSortKey" :sort-dir="subsSortDir" @sort="toggleSubs">الحالة</SortableTh>
                                    <SortableTh col="start" :sort-key="subsSortKey" :sort-dir="subsSortDir" @sort="toggleSubs">بداية</SortableTh>
                                    <TableHead>نهاية</TableHead>
                                    <TableHead>رقم الاشتراك</TableHead>
                                </TableRow></TableHeader>
                                <TableBody>
                                    <TableRow v-for="s in sortedSubs" :key="s.id">
                                        <TableCell>
                                            <div class="flex items-center gap-2.5">
                                                <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary-soft text-primary"><Package class="size-4" /></div>
                                                <span class="font-bold">{{ s.product_name || s.product_code || '—' }}</span>
                                                <Badge v-if="s.is_demo" variant="secondary">تجريبي</Badge>
                                            </div>
                                        </TableCell>
                                        <TableCell>{{ s.package_name || '—' }}</TableCell>
                                        <TableCell><Badge :variant="SUB_TONE[s.status] ?? 'muted'">{{ s.status ?? '—' }}</Badge></TableCell>
                                        <TableCell class="whitespace-nowrap text-xs text-muted-foreground">{{ s.start_date ? fmtDateAr(s.start_date) : '—' }}</TableCell>
                                        <TableCell class="whitespace-nowrap text-xs text-muted-foreground">{{ s.end_date ? fmtDateAr(s.end_date) : '—' }}</TableCell>
                                        <TableCell class="text-xs text-muted-foreground" dir="ltr">{{ s.subscription_number || '—' }}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                            <div v-else class="p-8 text-center text-sm text-muted-foreground">لا توجد اشتراكات.</div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="contacts">
                    <Card>
                        <CardHeader><CardTitle class="text-sm">الموظفون والمفوّضون لهذه الجهة</CardTitle></CardHeader>
                        <CardContent class="p-0">
                            <Table v-if="contacts.length">
                                <TableHeader><TableRow>
                                    <SortableTh col="name" :sort-key="contactsSortKey" :sort-dir="contactsSortDir" @sort="toggleContacts">الاسم</SortableTh>
                                    <SortableTh col="role" :sort-key="contactsSortKey" :sort-dir="contactsSortDir" @sort="toggleContacts">الدور</SortableTh>
                                    <TableHead>المسمى الوظيفي</TableHead>
                                    <TableHead>البريد</TableHead>
                                    <SortableTh col="mobile" :sort-key="contactsSortKey" :sort-dir="contactsSortDir" @sort="toggleContacts">الجوال</SortableTh>
                                </TableRow></TableHeader>
                                <TableBody>
                                    <TableRow v-for="c in sortedContacts" :key="c.id">
                                        <TableCell>
                                            <div class="flex items-center gap-2.5">
                                                <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-accent/10 text-accent"><UserIcon class="size-4" /></div>
                                                <span class="font-bold">{{ c.full_name }}</span>
                                                <Badge v-if="c.delegate_confirmed" variant="success"><CheckCircle2 class="size-3" /> معتمد</Badge>
                                            </div>
                                        </TableCell>
                                        <TableCell><Badge variant="outline">{{ ROLE_LABEL[c.role_type] ?? c.role_type ?? '—' }}</Badge></TableCell>
                                        <TableCell class="text-xs text-muted-foreground">{{ c.job_title || '—' }}</TableCell>
                                        <TableCell class="text-xs text-muted-foreground" dir="ltr">{{ c.email || '—' }}</TableCell>
                                        <TableCell class="text-xs text-muted-foreground" dir="ltr">{{ c.mobile || '—' }}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                            <div v-else class="p-8 text-center text-sm text-muted-foreground">لا يوجد موظفون مسجّلون.</div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    </AppShell>
</template>
