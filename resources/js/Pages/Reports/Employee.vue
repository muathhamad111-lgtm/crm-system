<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import KpiCard from '@/Components/KpiCard.vue';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Badge from '@/Components/ui/Badge.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PriorityBadge from '@/Components/PriorityBadge.vue';
import SortableTh from '@/Components/ui/SortableTh.vue';
import { useClientSort } from '@/lib/useSort';
import {
    UserCircle, ArrowRight, Mail, Phone, MapPin, Inbox, CheckCircle2, FolderOpen,
    AlertTriangle, RotateCcw, Star, Clock, Timer, GaugeCircle, Zap,
} from 'lucide-vue-next';
import { ROLE_LABELS } from '@/lib/labels';
import { fmtDateAr } from '@/lib/date';

const props = defineProps({
    profile: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
    recent: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

function slaTone(pct) {
    if (pct >= 80) return 'success';
    if (pct >= 50) return 'warning';
    return 'destructive';
}

const { sorted: recentSorted, sortKey: recentSortKey, sortDir: recentSortDir, toggle: recentToggle } = useClientSort(
    () => props.recent, 'created_at', 'desc',
    { request_number: 'request_number', title: 'title', status: 'status', priority: 'priority', created_at: 'created_at' },
);
</script>

<template>
    <Head :title="`تقرير أداء: ${profile.full_name ?? ''}`" />
    <AppShell>
        <div class="glass-stage space-y-6">
            <PageHero :title="profile.full_name ?? '—'" subtitle="تقرير أداء الموظف ومؤشرات الالتزام" :icon="UserCircle">
                <template #actions>
                    <a href="/reports"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-white/25 bg-white/12 px-3 py-2 text-xs font-semibold text-white backdrop-blur-sm transition hover:bg-white/20">
                        <ArrowRight class="size-4" /> عودة للتقارير
                    </a>
                </template>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-white/85" dir="ltr">
                    <span v-if="profile.email" class="inline-flex items-center gap-1.5"><Mail class="size-3.5" /> {{ profile.email }}</span>
                    <span v-if="profile.phone" class="inline-flex items-center gap-1.5"><Phone class="size-3.5" /> {{ profile.phone }}</span>
                    <span v-if="profile.city" class="inline-flex items-center gap-1.5">
                        <MapPin class="size-3.5" /> {{ profile.city }}<template v-if="profile.region"> — {{ profile.region }}</template>
                    </span>
                </div>
                <div v-if="(profile.roles ?? []).length" class="mt-3 flex flex-wrap gap-1.5">
                    <Badge v-for="r in profile.roles" :key="r" variant="outline"
                        class="border-white/25 bg-white/15 text-white">{{ ROLE_LABELS[r] ?? r }}</Badge>
                </div>
            </PageHero>

            <!-- Volume KPIs -->
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
                <KpiCard label="مُسند" :value="stats.assigned ?? 0" :icon="Inbox" tone="primary" />
                <KpiCard label="مكتمل" :value="stats.closed ?? 0" :icon="CheckCircle2" tone="success" />
                <KpiCard label="مفتوح" :value="stats.open ?? 0" :icon="FolderOpen" tone="warning" />
                <KpiCard label="متأخر" :value="stats.overdue ?? 0" :icon="AlertTriangle"
                    :tone="(stats.overdue ?? 0) > 0 ? 'destructive' : 'muted'" />
                <KpiCard label="إعادة فتح" :value="stats.reopened ?? 0" :icon="RotateCcw" tone="muted" />
                <KpiCard label="متوسط الرضا" :value="stats.csat ? stats.csat + '/5' : '—'" :format-number="false"
                    :icon="Star" tone="accent" />
            </div>

            <!-- Performance KPIs -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <KpiCard label="الالتزام بـ SLA (الحل)" :value="(stats.sla_compliance ?? 0) + '%'" :format-number="false"
                    :icon="GaugeCircle" :tone="slaTone(stats.sla_compliance ?? 0)" hint="المُنجزة قبل الاستحقاق" />
                <KpiCard label="الالتزام بـ SLA (الاستجابة)" :value="(stats.response_sla_compliance ?? 0) + '%'"
                    :format-number="false" :icon="Zap" :tone="slaTone(stats.response_sla_compliance ?? 0)"
                    hint="الردود الأولى ضمن المهلة" />
                <KpiCard label="متوسط زمن الحل" :value="(stats.avg_resolution_hours ?? 0) + ' س'" :format-number="false"
                    :icon="Clock" tone="warning" />
                <KpiCard label="متوسط الرد الأول" :value="(stats.avg_first_response_hours ?? 0) + ' س'"
                    :format-number="false" :icon="Timer" tone="primary" />
            </div>

            <!-- Recent assigned requests -->
            <Card>
                <CardHeader><CardTitle>أحدث الطلبات المُسندة</CardTitle></CardHeader>
                <CardContent class="p-0">
                    <div v-if="!recent.length" class="p-8 text-center text-sm text-muted-foreground">
                        لا توجد طلبات مسندة خلال الفترة المحددة.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-muted/40 text-xs text-muted-foreground">
                                <tr>
                                    <SortableTh col="request_number" align="right" :sort-key="recentSortKey" :sort-dir="recentSortDir" @sort="recentToggle">الرقم</SortableTh>
                                    <SortableTh col="title" align="right" :sort-key="recentSortKey" :sort-dir="recentSortDir" @sort="recentToggle">العنوان</SortableTh>
                                    <SortableTh col="status" align="center" :sort-key="recentSortKey" :sort-dir="recentSortDir" @sort="recentToggle">الحالة</SortableTh>
                                    <SortableTh col="priority" align="center" :sort-key="recentSortKey" :sort-dir="recentSortDir" @sort="recentToggle">الأولوية</SortableTh>
                                    <SortableTh col="created_at" align="center" :sort-key="recentSortKey" :sort-dir="recentSortDir" @sort="recentToggle">الإنشاء</SortableTh>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-for="r in recentSorted" :key="r.id" class="transition hover:bg-muted/20">
                                    <td class="p-3">
                                        <Link :href="`/requests/${r.id}`" class="font-mono text-xs text-primary hover:underline">
                                            {{ r.request_number }}
                                        </Link>
                                    </td>
                                    <td class="p-3">{{ r.title }}</td>
                                    <td class="p-3 text-center"><StatusBadge :status="r.status" /></td>
                                    <td class="p-3 text-center"><PriorityBadge :priority="r.priority" /></td>
                                    <td class="p-3 text-center text-xs text-muted-foreground whitespace-nowrap">
                                        {{ fmtDateAr(r.created_at) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppShell>
</template>
