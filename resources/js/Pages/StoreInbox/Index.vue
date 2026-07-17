<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import KpiCard from '@/Components/KpiCard.vue';
import Card from '@/Components/ui/Card.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import Select from '@/Components/ui/Select.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import Table from '@/Components/ui/Table.vue';
import TableHeader from '@/Components/ui/TableHeader.vue';
import TableBody from '@/Components/ui/TableBody.vue';
import TableRow from '@/Components/ui/TableRow.vue';
import TableHead from '@/Components/ui/TableHead.vue';
import SortableTh from '@/Components/ui/SortableTh.vue';
import TableCell from '@/Components/ui/TableCell.vue';
import {
    Inbox, Search, Mail, Phone, Building2, User as UserIcon, ExternalLink,
    CheckCircle2, Clock, Archive,
} from 'lucide-vue-next';
import { fmtFullDateTimeAr } from '@/lib/date';
import { num } from '@/lib/utils';

const props = defineProps({
    messages: { type: Object, default: () => ({ data: [], links: [], total: 0 }) },
    filters: { type: Object, default: () => ({ status: 'all', q: '', sort: 'date', dir: 'desc' }) },
    kpis: { type: Object, default: () => ({}) },
});

const STATUS_LABEL = { new: 'جديد', read: 'تم الاطلاع', handled: 'تم التعامل', archived: 'مؤرشف' };
const STATUS_TONE = { new: 'info', read: 'muted', handled: 'success', archived: 'muted' };
const SERVICE_TYPE_LABEL = {
    digital_services: 'الخدمات والمنتجات الرقمية',
    website_development: 'تطوير المواقع الإلكترونية',
    mobile_app_development: 'تطوير تطبيقات الهواتف',
    system_development: 'تطوير الأنظمة والمنصات',
    partnerships: 'الشراكات والمبادرات',
    other: 'أخرى',
};
const ORG_TYPE_LABEL = { individual: 'فرد', non_profit: 'قطاع غير ربحي', private_org: 'قطاع خاص' };
const serviceLabel = (s) => SERVICE_TYPE_LABEL[s] ?? s ?? '—';
const orgLabel = (o) => ORG_TYPE_LABEL[o] ?? o ?? '—';
const statusLabel = (s) => STATUS_LABEL[s] ?? s;

const q = ref(props.filters.q ?? '');
let timer = null;
function go(extra = {}) {
    router.get('/store-contact-inbox', {
        status: props.filters.status, q: q.value,
        sort: props.filters.sort, dir: props.filters.dir, ...extra,
    }, { preserveState: true, replace: true, preserveScroll: true });
}
watch(q, () => { clearTimeout(timer); timer = setTimeout(() => go(), 350); });

// --- Server-side sorting (preserved) ---
const sortKey = computed(() => props.filters.sort ?? 'date');
const sortDir = computed(() => props.filters.dir ?? 'desc');
function setSort(col) {
    const dir = sortKey.value === col && sortDir.value === 'desc' ? 'asc' : 'desc';
    go({ sort: col, dir });
}

const statusChips = [['all', 'الكل'], ['new', 'جديد'], ['read', 'تم الاطلاع'], ['handled', 'تم التعامل'], ['archived', 'مؤرشف']];

// --- Handle-message dialog ---
const open = ref(false);
const current = ref(null);
const form = useForm({ status: 'handled', internal_note: '' });
function view(m) {
    current.value = m;
    form.status = m.status === 'new' ? 'handled' : m.status;
    form.internal_note = m.internal_note ?? '';
    open.value = true;
    // Auto-mark a brand-new message as read on open.
    if (m.status === 'new') {
        router.post(`/store-contact-inbox/${m.id}/status`, { status: 'read' }, { preserveScroll: true, preserveState: true });
    }
}
function submit(status) {
    if (status) form.status = status;
    form.post(`/store-contact-inbox/${current.value.id}/status`, {
        preserveScroll: true,
        onSuccess: () => { open.value = false; },
    });
}
</script>

<template>
    <Head title="صندوق رسائل المتجر" />
    <AppShell>
        <div class="space-y-4">
            <!-- Gradient hero -->
            <div class="relative overflow-hidden rounded-2xl p-6 sm:p-8 text-white shadow-elevated" style="background-image: var(--gradient-hero);">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(closest-side at 75% 20%, rgba(255,255,255,.4), transparent);"></div>
                <div class="relative flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="flex size-12 items-center justify-center rounded-xl bg-white/15 backdrop-blur-sm">
                            <Inbox class="size-6" />
                        </div>
                        <div>
                            <p class="text-xs text-white/60">متجر التحول التقني · تواصل معنا</p>
                            <h1 class="mt-0.5 text-2xl sm:text-3xl font-bold tracking-tight">صندوق رسائل المتجر</h1>
                            <p class="mt-1 max-w-xl text-sm text-white/80">نموذج «تواصل معنا» العام — رسائل من زوّار بدون حساب. هذه ليست تذاكر دعم ولا تخضع لـ SLA.</p>
                        </div>
                    </div>
                    <span class="rounded-full bg-white/15 px-3 py-1.5 text-xs backdrop-blur-sm tabular-nums">{{ num(kpis.total ?? 0) }} رسالة</span>
                </div>
            </div>

            <!-- KPI ribbon -->
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                <KpiCard label="إجمالي الرسائل" :value="kpis.total ?? 0" :icon="Inbox" tone="primary" />
                <KpiCard label="جديدة" :value="kpis.new ?? 0" :icon="Clock" tone="info" />
                <KpiCard label="تم التعامل" :value="kpis.handled ?? 0" :icon="CheckCircle2" tone="success" />
                <KpiCard label="مؤرشفة" :value="kpis.archived ?? 0" :icon="Archive" tone="muted" />
            </div>

            <!-- Toolbar -->
            <Card class="p-4">
                <div class="mb-3 flex flex-col gap-3 md:flex-row md:items-center">
                    <div class="relative flex-1">
                        <Search class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="q" placeholder="ابحث بالاسم، البريد، الجوال، أو الوصف…" class="pr-9" />
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <button v-for="[k, l] in statusChips" :key="k" type="button" @click="go({ status: k })"
                            :class="['inline-flex items-center gap-1 rounded-full px-3 py-1.5 text-xs font-bold transition-colors',
                                filters.status === k ? 'bg-primary text-primary-foreground' : 'border border-border bg-card text-muted-foreground hover:text-foreground']">
                            {{ l }}<span v-if="k !== 'all' && kpis[k]" class="tabular-nums opacity-70">({{ kpis[k] }})</span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-lg border border-border">
                    <Table>
                        <TableHeader><TableRow>
                            <SortableTh col="sender" :sort-key="sortKey" :sort-dir="sortDir" @sort="setSort">المرسل</SortableTh>
                            <SortableTh col="email" :sort-key="sortKey" :sort-dir="sortDir" @sort="setSort">التواصل</SortableTh>
                            <SortableTh col="service" :sort-key="sortKey" :sort-dir="sortDir" @sort="setSort">نوع الخدمة</SortableTh>
                            <TableHead>الجهة</TableHead>
                            <SortableTh col="date" :sort-key="sortKey" :sort-dir="sortDir" @sort="setSort">التاريخ</SortableTh>
                            <SortableTh col="status" :sort-key="sortKey" :sort-dir="sortDir" @sort="setSort">الحالة</SortableTh>
                            <TableHead></TableHead>
                        </TableRow></TableHeader>
                        <TableBody>
                            <TableRow v-for="m in messages.data" :key="m.id" class="cursor-pointer" @click="view(m)">
                                <TableCell>
                                    <div class="font-medium">{{ m.first_name }} {{ m.last_name }}</div>
                                    <div v-if="m.company_name" class="inline-flex items-center gap-1 text-xs text-muted-foreground">
                                        <Building2 class="size-3" /> {{ m.company_name }}
                                    </div>
                                </TableCell>
                                <TableCell class="text-xs text-muted-foreground">
                                    <div class="inline-flex items-center gap-1" dir="ltr"><Mail class="size-3" /> {{ m.email }}</div>
                                    <div v-if="m.mobile" class="inline-flex items-center gap-1" dir="ltr"><Phone class="size-3" /> {{ m.mobile }}</div>
                                </TableCell>
                                <TableCell class="text-sm">
                                    <div>{{ serviceLabel(m.service_type) }}</div>
                                    <div v-if="m.product_code" class="text-xs text-muted-foreground">{{ m.product_code }}</div>
                                </TableCell>
                                <TableCell class="text-sm">{{ orgLabel(m.org_type) }}</TableCell>
                                <TableCell class="whitespace-nowrap text-xs text-muted-foreground">{{ fmtFullDateTimeAr(m.created_at) }}</TableCell>
                                <TableCell><Badge :variant="STATUS_TONE[m.status] ?? 'muted'">{{ statusLabel(m.status) }}</Badge></TableCell>
                                <TableCell class="text-muted-foreground"><ExternalLink class="size-4" /></TableCell>
                            </TableRow>
                            <TableRow v-if="!messages.data.length">
                                <TableCell colspan="7" class="py-12 text-center text-muted-foreground">لا توجد رسائل مطابقة.</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </Card>

            <div v-if="messages.last_page > 1" class="flex flex-wrap justify-center gap-1">
                <Link v-for="link in messages.links" :key="link.label" :href="link.url || '#'" v-html="link.label"
                    preserve-scroll
                    class="min-w-9 rounded-md border border-border px-3 py-1.5 text-center text-sm transition-colors"
                    :class="[link.active ? 'border-primary bg-primary text-primary-foreground' : 'bg-card hover:bg-muted', !link.url && 'pointer-events-none opacity-40']" />
            </div>
        </div>

        <!-- Handle-message dialog -->
        <Dialog v-model:open="open" class="max-w-2xl">
            <div v-if="current" class="space-y-4">
                <div class="flex flex-wrap items-center gap-2 border-b border-border pb-3">
                    <UserIcon class="size-5 text-muted-foreground" />
                    <h2 class="text-lg font-bold text-foreground">{{ current.first_name }} {{ current.last_name }}</h2>
                    <Badge :variant="STATUS_TONE[current.status] ?? 'muted'">{{ statusLabel(current.status) }}</Badge>
                </div>

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div><div class="text-xs text-muted-foreground">البريد الإلكتروني</div><div dir="ltr">{{ current.email }}</div></div>
                    <div><div class="text-xs text-muted-foreground">الجوال</div><div dir="ltr">{{ current.mobile }}</div></div>
                    <div><div class="text-xs text-muted-foreground">نوع الجهة</div><div>{{ orgLabel(current.org_type) }}</div></div>
                    <div v-if="current.company_name"><div class="text-xs text-muted-foreground">اسم الجهة</div><div>{{ current.company_name }}</div></div>
                    <div><div class="text-xs text-muted-foreground">نوع الخدمة</div><div>{{ serviceLabel(current.service_type) }}</div></div>
                    <div v-if="current.product_code"><div class="text-xs text-muted-foreground">المنتج</div><div>{{ current.product_code }}</div></div>
                    <div class="col-span-2"><div class="text-xs text-muted-foreground">التاريخ</div><div>{{ fmtFullDateTimeAr(current.created_at) }}</div></div>
                </div>

                <div class="border-t border-border pt-3">
                    <div class="mb-1 text-xs text-muted-foreground">الوصف</div>
                    <p class="whitespace-pre-wrap rounded-lg bg-muted p-3 text-sm">{{ current.description }}</p>
                </div>

                <Select label="الحالة" v-model="form.status">
                    <option value="new">جديد</option>
                    <option value="read">تم الاطلاع</option>
                    <option value="handled">تم التعامل</option>
                    <option value="archived">مؤرشف</option>
                </Select>
                <Textarea label="ملاحظات داخلية (اختياري)" v-model="form.internal_note" class="min-h-20" />

                <div class="flex flex-row justify-between gap-2">
                    <Button variant="outline" :disabled="form.processing" @click="submit('archived')">
                        <Archive class="size-4" /> أرشفة
                    </Button>
                    <div class="flex gap-2">
                        <Button variant="ghost" :disabled="form.processing" @click="submit()">حفظ</Button>
                        <Button :disabled="form.processing" @click="submit('handled')">
                            <CheckCircle2 class="size-4" /> تم التعامل
                        </Button>
                    </div>
                </div>
            </div>
        </Dialog>
    </AppShell>
</template>
