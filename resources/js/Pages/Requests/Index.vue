<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Table from '@/Components/ui/Table.vue';
import TableHeader from '@/Components/ui/TableHeader.vue';
import TableBody from '@/Components/ui/TableBody.vue';
import TableRow from '@/Components/ui/TableRow.vue';
import TableHead from '@/Components/ui/TableHead.vue';
import TableCell from '@/Components/ui/TableCell.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PriorityBadge from '@/Components/PriorityBadge.vue';
import Badge from '@/Components/ui/Badge.vue';
import { Inbox, PlusCircle, Search } from 'lucide-vue-next';
import { timeAgoAr } from '@/lib/date';
import { REQUEST_STATUS, SERVICE_STATUS, statusLabel } from '@/lib/labels';

const props = defineProps({
    requests: { type: Object, default: () => ({ data: [] }) },
    counts: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({ status: 'all', q: '' }) },
    isStaff: { type: Boolean, default: false },
});

const q = ref(props.filters.q ?? '');
const activeStatus = ref(props.filters.status ?? 'all');

const tabs = [
    { key: 'all', label: 'الكل' },
    ...Object.entries(REQUEST_STATUS).map(([key, v]) => ({ key, label: v.label })),
];

function go() {
    router.get('/requests', { status: activeStatus.value, q: q.value },
        { preserveState: true, replace: true, preserveScroll: true });
}
function setStatus(k) { activeStatus.value = k; go(); }
let t;
watch(q, () => { clearTimeout(t); t = setTimeout(go, 350); });
</script>

<template>
    <Head :title="isStaff ? 'صندوق الطلبات' : 'طلباتي'" />
    <AppShell>
        <div class="space-y-6">
            <PageHero :title="isStaff ? 'صندوق الطلبات' : 'طلباتي'"
                :subtitle="isStaff ? 'إدارة ومتابعة جميع طلبات العملاء' : 'متابعة طلباتك وتذاكرك'"
                :icon="Inbox">
                <template #actions>
                    <Button href="/requests/new" variant="accent">
                        <PlusCircle class="size-4" /> طلب جديد
                    </Button>
                </template>
            </PageHero>

            <div class="flex flex-wrap items-center gap-2">
                <button v-for="tab in tabs" :key="tab.key" @click="setStatus(tab.key)"
                    :class="['rounded-full px-3.5 py-1.5 text-sm font-medium transition-colors',
                        activeStatus === tab.key ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-secondary']">
                    {{ tab.label }}
                    <span v-if="counts[tab.key]" class="mr-1 tabular-nums opacity-80">({{ counts[tab.key] }})</span>
                </button>
            </div>

            <Card class="p-4">
                <div class="relative mb-4 max-w-sm">
                    <Search class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="q" placeholder="بحث برقم الطلب أو العنوان…" class="pr-9" />
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>رقم الطلب</TableHead>
                            <TableHead>العنوان</TableHead>
                            <TableHead>التصنيف</TableHead>
                            <TableHead>الحالة</TableHead>
                            <TableHead>الأولوية</TableHead>
                            <TableHead v-if="isStaff">المسند إليه</TableHead>
                            <TableHead>آخر تحديث</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="r in requests.data" :key="r.id" class="cursor-pointer"
                            @click="router.visit(`/requests/${r.id}`)">
                            <TableCell class="font-bold text-primary tabular-nums whitespace-nowrap">{{ r.request_number }}</TableCell>
                            <TableCell class="max-w-xs truncate">{{ r.title }}</TableCell>
                            <TableCell class="text-muted-foreground text-sm">{{ r.category?.name_ar ?? '—' }}</TableCell>
                            <TableCell>
                                <StatusBadge v-if="isStaff" :status="r.status" />
                                <Badge v-else :variant="statusLabel(SERVICE_STATUS, r.service_status).tone">
                                    {{ statusLabel(SERVICE_STATUS, r.service_status).label }}
                                </Badge>
                            </TableCell>
                            <TableCell><PriorityBadge :priority="r.priority" /></TableCell>
                            <TableCell v-if="isStaff" class="text-sm">{{ r.assignee?.full_name ?? '—' }}</TableCell>
                            <TableCell class="text-muted-foreground text-xs whitespace-nowrap">{{ timeAgoAr(r.updated_at) }}</TableCell>
                        </TableRow>
                        <TableRow v-if="!requests.data.length">
                            <TableCell :colspan="isStaff ? 7 : 6" class="py-10 text-center text-muted-foreground">
                                لا توجد طلبات مطابقة.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <div v-if="requests.links && requests.last_page > 1" class="mt-4 flex justify-center gap-1">
                    <Link v-for="link in requests.links" :key="link.label" :href="link.url || '#'"
                        v-html="link.label"
                        :class="['rounded-md px-3 py-1.5 text-sm', link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted', !link.url && 'pointer-events-none opacity-40']" />
                </div>
            </Card>
        </div>
    </AppShell>
</template>
