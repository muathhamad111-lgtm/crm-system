<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import KpiCard from '@/Components/KpiCard.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Input from '@/Components/ui/Input.vue';
import Table from '@/Components/ui/Table.vue';
import TableHeader from '@/Components/ui/TableHeader.vue';
import TableBody from '@/Components/ui/TableBody.vue';
import TableRow from '@/Components/ui/TableRow.vue';
import TableHead from '@/Components/ui/TableHead.vue';
import TableCell from '@/Components/ui/TableCell.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import { num } from '@/lib/utils';
import { fmtDateAr } from '@/lib/date';
import {
    Users, Search, Star, FileText, ChevronLeft, Briefcase, AlertTriangle,
} from 'lucide-vue-next';

const props = defineProps({
    customers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    kpis: { type: Object, default: () => ({}) },
});

const q = ref(props.filters.q ?? '');
let timer = null;
watch(q, (val) => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(route('customers.index'), { q: val }, {
            preserveState: true, replace: true, preserveScroll: true,
        });
    }, 350);
});
</script>

<template>
    <Head title="قائمة العملاء" />
    <AppShell>
        <div class="space-y-6">
            <PageHero
                title="قائمة العملاء"
                subtitle="إدارة جميع العملاء ومتابعة طلباتهم ومستوى رضاهم — اضغط على أي صف لفتح الملف التفصيلي"
                :icon="Users" />

            <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
                <KpiCard label="عدد العملاء" :value="kpis.total_customers ?? 0" :icon="Users" tone="primary" />
                <KpiCard label="إجمالي الطلبات" :value="kpis.total_requests ?? 0" :icon="FileText" tone="accent" />
                <KpiCard label="متوسط الرضا" :value="kpis.avg_csat != null ? `${kpis.avg_csat}/5` : '—'"
                    :format-number="false" :icon="Star" tone="warning" />
            </div>

            <Card>
                <CardContent class="p-3">
                    <div class="relative">
                        <Search class="absolute right-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="q" placeholder="ابحث بالاسم، البريد، الجوال، المدينة..." class="pr-9" />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-0">
                    <Table v-if="customers.data.length">
                        <TableHeader>
                            <TableRow>
                                <TableHead>العميل</TableHead>
                                <TableHead>المجال</TableHead>
                                <TableHead>المنطقة</TableHead>
                                <TableHead class="text-center">الطلبات</TableHead>
                                <TableHead class="text-center">مفتوحة</TableHead>
                                <TableHead class="text-center">الرضا</TableHead>
                                <TableHead class="text-center">آخر تواصل</TableHead>
                                <TableHead></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="c in customers.data" :key="c.id"
                                class="cursor-pointer"
                                @click="router.visit(route('customers.show', c.id))">
                                <TableCell>
                                    <div class="flex items-center gap-3">
                                        <Avatar :name="c.full_name" />
                                        <div class="min-w-0">
                                            <div class="font-bold text-foreground truncate">{{ c.full_name || '—' }}</div>
                                            <div class="text-xs text-muted-foreground truncate">{{ c.email || '—' }}</div>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <span v-if="c.business_field" class="inline-flex items-center gap-1 text-xs text-muted-foreground">
                                        <Briefcase class="size-3" /> {{ c.business_field }}
                                    </span>
                                    <span v-else class="text-muted-foreground">—</span>
                                </TableCell>
                                <TableCell class="text-xs text-muted-foreground">
                                    {{ [c.city, c.region].filter(Boolean).join('، ') || '—' }}
                                </TableCell>
                                <TableCell class="text-center font-bold tabular-nums">{{ num(c.requests_count ?? 0) }}</TableCell>
                                <TableCell class="text-center tabular-nums">
                                    <span :class="c.open_requests_count > 0 ? 'font-bold text-accent' : 'text-muted-foreground'">
                                        {{ num(c.open_requests_count ?? 0) }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-center">
                                    <span v-if="c.avg_csat != null" class="inline-flex items-center gap-1">
                                        <Star class="size-3.5 fill-warning text-warning" />
                                        <span class="font-medium tabular-nums">{{ c.avg_csat }}</span>
                                    </span>
                                    <span v-else class="text-muted-foreground">—</span>
                                </TableCell>
                                <TableCell class="text-center text-xs text-muted-foreground whitespace-nowrap">
                                    {{ c.last_contact_at ? fmtDateAr(c.last_contact_at) : '—' }}
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    <ChevronLeft class="mx-auto size-4" />
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <div v-else class="p-12 text-center text-muted-foreground">
                        <Users class="mx-auto mb-3 size-10 opacity-40" />
                        <p class="text-sm">لا يوجد عملاء مطابقون لمعايير البحث</p>
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
