<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import KpiCard from '@/Components/KpiCard.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import Badge from '@/Components/ui/Badge.vue';
import Table from '@/Components/ui/Table.vue';
import TableHeader from '@/Components/ui/TableHeader.vue';
import TableBody from '@/Components/ui/TableBody.vue';
import TableRow from '@/Components/ui/TableRow.vue';
import TableHead from '@/Components/ui/TableHead.vue';
import SortableTh from '@/Components/ui/SortableTh.vue';
import TableCell from '@/Components/ui/TableCell.vue';
import { num } from '@/lib/utils';
import { fmtDateAr } from '@/lib/date';
import {
    Users, Search, Building2, User as UserIcon, Mail, Phone, Briefcase,
    CheckCircle2, ChevronLeft, Store, Clock,
} from 'lucide-vue-next';

const props = defineProps({
    customers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    kpis: { type: Object, default: () => ({}) },
});

const ENTITY_LABEL = {
    individual: 'فَرد',
    non_profit: 'قطاع غير ربحي',
    private_org: 'قطاع خاص',
};

const q = ref(props.filters.q ?? '');
const entity = ref(props.filters.entity ?? 'all');

let timer = null;
function reload(extra = {}) {
    router.get(route('tts.index'), {
        q: q.value, entity: entity.value,
        sort: props.filters.sort, dir: props.filters.dir, ...extra,
    }, {
        preserveState: true, replace: true, preserveScroll: true,
    });
}
watch(q, () => { clearTimeout(timer); timer = setTimeout(reload, 350); });
watch(entity, () => reload());

// --- Server-side sorting ---
const sortKey = computed(() => props.filters.sort ?? 'synced');
const sortDir = computed(() => props.filters.dir ?? 'desc');
function setSort(col) {
    const dir = sortKey.value === col && sortDir.value === 'desc' ? 'asc' : 'desc';
    reload({ sort: col, dir });
}

function entityLabel(e) {
    return ENTITY_LABEL[e] ?? e;
}
</script>

<template>
    <Head title="عملاء المتجر" />
    <AppShell>
        <div class="space-y-5">
            <!-- Gradient hero -->
            <div class="relative overflow-hidden rounded-2xl p-6 sm:p-8 text-white shadow-elevated" style="background-image: var(--gradient-hero);">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(closest-side at 75% 20%, rgba(255,255,255,.4), transparent);"></div>
                <div class="relative flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="flex size-12 items-center justify-center rounded-xl bg-white/15 backdrop-blur-sm">
                            <Store class="size-6" />
                        </div>
                        <div>
                            <p class="text-xs text-white/60">متجر التحول التقني · مزامنة المشتركين</p>
                            <h1 class="mt-0.5 text-2xl sm:text-3xl font-bold tracking-tight">عملاء المتجر</h1>
                            <p class="mt-1 max-w-xl text-sm text-white/80">جميع العملاء المُزامَنون من متجر التحول التقني — أفراد، قطاع غير ربحي، وقطاع خاص. اضغط على أي صف لفتح الملف.</p>
                        </div>
                    </div>
                    <span class="rounded-full bg-white/15 px-3 py-1.5 text-xs backdrop-blur-sm tabular-nums">{{ num(customers.total ?? 0) }} عميل</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <KpiCard label="إجمالي العملاء" :value="kpis.total_customers ?? 0" :hint="`أفراد: ${num(kpis.individuals ?? 0)}`" :icon="Users" tone="primary" />
                <KpiCard label="منظمات" :value="kpis.organizations ?? 0" :hint="`غير ربحي ${num(kpis.non_profit ?? 0)} · خاص ${num(kpis.private_org ?? 0)}`" :icon="Building2" tone="accent" />
                <KpiCard label="اشتراكات نشطة" :value="kpis.active_subscriptions ?? 0" :icon="CheckCircle2" tone="success" />
                <KpiCard label="موظفون ومفوّضون" :value="kpis.contacts ?? 0" :icon="UserIcon" tone="muted" />
            </div>

            <Card>
                <CardContent class="flex flex-wrap items-center gap-2 p-3">
                    <div class="relative min-w-[220px] flex-1">
                        <Search class="absolute left-3 top-1/2 z-10 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="q" label="ابحث بالاسم، البريد، السجل التجاري، الرقم الضريبي..." />
                    </div>
                    <Select v-model="entity" class="w-[190px]">
                        <option value="all">جميع الأَنواع</option>
                        <option value="individual">{{ ENTITY_LABEL.individual }}</option>
                        <option value="non_profit">{{ ENTITY_LABEL.non_profit }}</option>
                        <option value="private_org">{{ ENTITY_LABEL.private_org }}</option>
                    </Select>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-0">
                    <Table v-if="customers.data.length">
                        <TableHeader>
                            <TableRow>
                                <SortableTh col="name" :sort-key="sortKey" :sort-dir="sortDir" @sort="setSort">العميل</SortableTh>
                                <SortableTh col="entity" :sort-key="sortKey" :sort-dir="sortDir" @sort="setSort">النوع</SortableTh>
                                <SortableTh col="contact" :sort-key="sortKey" :sort-dir="sortDir" @sort="setSort">التواصل</SortableTh>
                                <SortableTh col="active" align="center" :sort-key="sortKey" :sort-dir="sortDir" @sort="setSort">اشتراكات نشطة</SortableTh>
                                <SortableTh col="contacts" align="center" :sort-key="sortKey" :sort-dir="sortDir" @sort="setSort">جهات</SortableTh>
                                <SortableTh col="synced" align="center" :sort-key="sortKey" :sort-dir="sortDir" @sort="setSort">آخر مزامنة</SortableTh>
                                <TableHead></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="c in customers.data" :key="c.id"
                                class="cursor-pointer"
                                @click="router.visit(route('tts.show', c.id))">
                                <TableCell>
                                    <div class="flex items-center gap-3">
                                        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary-soft text-primary">
                                            <component :is="c.entity_type === 'individual' ? UserIcon : Building2" class="size-5" />
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-bold text-foreground truncate">{{ c.full_name || 'بدون اسم' }}</div>
                                            <div v-if="c.business_field" class="inline-flex items-center gap-1 text-xs text-muted-foreground">
                                                <Briefcase class="size-3" /> {{ c.business_field }}
                                            </div>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline">{{ entityLabel(c.entity_type) }}</Badge>
                                    <Badge v-if="c.status !== 'active'" variant="destructive" class="mr-1">{{ c.status }}</Badge>
                                </TableCell>
                                <TableCell class="text-xs text-muted-foreground">
                                    <div v-if="c.email" class="inline-flex items-center gap-1" dir="ltr"><Mail class="size-3" /> {{ c.email }}</div>
                                    <div v-if="c.phone" class="inline-flex items-center gap-1" dir="ltr"><Phone class="size-3" /> {{ c.phone }}</div>
                                    <span v-if="!c.email && !c.phone">—</span>
                                </TableCell>
                                <TableCell class="text-center font-bold tabular-nums text-primary">{{ num(c.active_subscriptions_count ?? 0) }}</TableCell>
                                <TableCell class="text-center font-bold tabular-nums text-accent">{{ num(c.contacts_count ?? 0) }}</TableCell>
                                <TableCell class="text-center text-xs text-muted-foreground whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1"><Clock class="size-3" /> {{ c.last_synced_at ? fmtDateAr(c.last_synced_at) : '—' }}</span>
                                </TableCell>
                                <TableCell class="text-muted-foreground"><ChevronLeft class="mx-auto size-4" /></TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <div v-else class="p-12 text-center text-muted-foreground">
                        <Store class="mx-auto mb-3 size-10 opacity-40" />
                        <p class="text-sm">لا يوجد عملاء مُطابِقون. تأكَّد من تَفعيل المزامنة من متجر التحول التقني.</p>
                    </div>
                </CardContent>
            </Card>

            <div v-if="customers.data.length" class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-muted-foreground">
                    عرض {{ num(customers.from ?? 0) }}–{{ num(customers.to ?? 0) }} من {{ num(customers.total) }}
                </p>
                <div class="flex flex-wrap gap-1">
                    <Link v-for="link in customers.links" :key="link.label"
                        :href="link.url || '#'"
                        preserve-scroll
                        v-html="link.label"
                        class="min-w-9 rounded-md border border-border px-3 py-1.5 text-sm text-center transition-colors"
                        :class="[
                            link.active ? 'bg-primary text-primary-foreground border-primary' : 'bg-card hover:bg-muted',
                            !link.url ? 'pointer-events-none opacity-40' : '',
                        ]" />
                </div>
            </div>
        </div>
    </AppShell>
</template>
