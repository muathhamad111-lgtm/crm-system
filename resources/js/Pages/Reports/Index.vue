<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import KpiCard from '@/Components/KpiCard.vue';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import {
    BarChart3, Inbox, CheckCircle2, Clock, GaugeCircle, Star, TrendingUp,
    Smile, Frown, ListChecks, Flag,
} from 'lucide-vue-next';
import { REQUEST_STATUS, REQUEST_PRIORITY, statusLabel } from '@/lib/labels';
import { num } from '@/lib/utils';

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    byStatus: { type: Object, default: () => ({}) },
    byPriority: { type: Object, default: () => ({}) },
    teams: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const completionRate = computed(() => {
    const t = props.stats.total ?? 0;
    return t > 0 ? Math.round(((props.stats.completed ?? 0) / t) * 100) : 0;
});

function toBars(map, labelMap) {
    const entries = Object.entries(map ?? {});
    const total = entries.reduce((s, [, v]) => s + Number(v), 0);
    return entries
        .map(([k, v]) => ({
            key: k,
            label: statusLabel(labelMap, k).label ?? k,
            tone: statusLabel(labelMap, k).tone ?? 'muted',
            value: Number(v),
            pct: total > 0 ? Math.round((Number(v) / total) * 100) : 0,
        }))
        .sort((a, b) => b.value - a.value);
}

const statusBars = computed(() => toBars(props.byStatus, REQUEST_STATUS));
const priorityBars = computed(() => toBars(props.byPriority, REQUEST_PRIORITY));

const barColor = {
    primary: 'var(--primary)', accent: 'var(--accent)', success: 'var(--success)',
    warning: 'var(--warning)', destructive: 'var(--destructive)', info: 'var(--info)',
    muted: 'var(--color-muted-foreground)',
};
const cls = (t) => barColor[t] ?? barColor.muted;
</script>

<template>
    <Head title="التقارير ومؤشرات الأداء" />
    <AppShell>
        <div class="glass-stage space-y-6">
            <PageHero
                title="التقارير ومؤشرات الأداء"
                subtitle="نظرة تحليلية على الأداء والالتزام ورضا العملاء لاتخاذ قرارات مبنية على البيانات"
                :icon="BarChart3" />

            <!-- Operations tiles -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <KpiCard label="إجمالي الطلبات" :value="stats.total ?? 0" :icon="Inbox" tone="primary"
                    hint="كل الطلبات ضمن الفلاتر الحالية" />
                <KpiCard label="المكتملة" :value="stats.completed ?? 0" :icon="CheckCircle2" tone="success"
                    :hint="`نسبة الإنجاز ${completionRate}% من الإجمالي`" :format-number="true" />
                <KpiCard label="متوسط زمن الحل" :value="(stats.avg_resolution_hours ?? 0) + ' س'" :format-number="false"
                    :icon="Clock" tone="warning" hint="متوسط (الإغلاق − الإنشاء) للطلبات المغلقة" />
                <KpiCard label="الالتزام بـ SLA" :value="(stats.sla_compliance ?? 0) + '%'" :format-number="false"
                    :icon="GaugeCircle" tone="accent" hint="المُنجزة قبل الاستحقاق ÷ المكتملة" />
            </div>

            <!-- Satisfaction tiles -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
                <KpiCard label="متوسط رضا العملاء" :value="stats.csat ? stats.csat + '/5' : '—'" :format-number="false"
                    :icon="Star" tone="accent" :hint="`من ${num(stats.ratings_count ?? 0)} تقييم`" />
                <KpiCard label="عدد التقييمات" :value="stats.ratings_count ?? 0" :icon="ListChecks" tone="muted"
                    hint="إجمالي التقييمات المسجّلة" />
                <KpiCard label="عملاء راضون" :value="stats.satisfied ?? 0" :icon="Smile" tone="success"
                    hint="تقييمات 4–5 نجوم (مروّجون)" />
                <KpiCard label="عملاء غير راضين" :value="stats.dissatisfied ?? 0" :icon="Frown" tone="destructive"
                    hint="تقييمات 1–2 نجمة (منتقدون)" />
                <KpiCard label="مؤشر صافي الرضى (NPS)"
                    :value="stats.nps === null || stats.nps === undefined ? '—' : stats.nps + '%'"
                    :format-number="false" :icon="TrendingUp" tone="primary"
                    hint="(المروّجون − المنتقدون) ÷ الإجمالي × 100" />
            </div>

            <!-- Breakdowns with progress bars -->
            <div class="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Flag class="size-5 text-primary" /> التوزيع حسب الحالة
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-for="row in statusBars" :key="row.key">
                            <div class="mb-1.5 flex items-center justify-between text-sm">
                                <span class="font-medium text-foreground">{{ row.label }}</span>
                                <span class="tabular-nums text-muted-foreground">
                                    {{ num(row.value) }} <span class="text-xs">({{ row.pct }}%)</span>
                                </span>
                            </div>
                            <div class="sla-progress-track">
                                <div class="sla-progress-fill"
                                    :style="{ width: row.pct + '%', backgroundColor: cls(row.tone) }"></div>
                            </div>
                        </div>
                        <p v-if="!statusBars.length" class="text-sm text-muted-foreground">لا توجد بيانات.</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <BarChart3 class="size-5 text-accent" /> التوزيع حسب الأولوية
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-for="row in priorityBars" :key="row.key">
                            <div class="mb-1.5 flex items-center justify-between text-sm">
                                <span class="font-medium text-foreground">{{ row.label }}</span>
                                <span class="tabular-nums text-muted-foreground">
                                    {{ num(row.value) }} <span class="text-xs">({{ row.pct }}%)</span>
                                </span>
                            </div>
                            <div class="sla-progress-track">
                                <div class="sla-progress-fill"
                                    :style="{ width: row.pct + '%', backgroundColor: cls(row.tone) }"></div>
                            </div>
                        </div>
                        <p v-if="!priorityBars.length" class="text-sm text-muted-foreground">لا توجد بيانات.</p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppShell>
</template>
