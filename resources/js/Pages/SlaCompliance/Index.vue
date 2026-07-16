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
import Badge from '@/Components/ui/Badge.vue';
import { Gauge, CheckCircle2, XCircle, AlertTriangle, Clock, Layers, Zap } from 'lucide-vue-next';

const props = defineProps({
    target: { type: Number, default: 80 },
    overall: { type: Object, default: () => ({}) },
    stage: { type: Object, default: () => ({}) },
    byPriority: { type: Array, default: () => [] },
    byCategory: { type: Array, default: () => [] },
    topDelayingStages: { type: Array, default: () => [] },
});

function fmtDelay(min) {
    const m = Math.round(min);
    if (m < 60) return `${m} د`;
    const h = Math.floor(m / 60);
    const rem = m % 60;
    return rem ? `${h} س ${rem} د` : `${h} س`;
}

function toneFor(pct) {
    if (pct === null || pct === undefined) return 'muted';
    if (pct >= props.target) return 'success';
    if (pct >= props.target - 20) return 'warning';
    return 'destructive';
}

const fillColor = {
    success: 'var(--success)',
    warning: 'var(--warning)',
    destructive: 'var(--destructive)',
    muted: 'var(--color-muted-foreground)',
};

const overallTone = computed(() => toneFor(props.overall.pct));
const overallLabel = computed(() =>
    props.overall.pct === null || props.overall.pct === undefined
        ? 'لا توجد بيانات كافية'
        : props.overall.pct >= props.target
            ? 'ضمن المستهدف'
            : 'دون المستهدف');
</script>

<template>
    <Head title="الالتزام بـ SLA" />
    <AppShell>
        <div class="glass-stage space-y-6">
            <PageHero
                title="الالتزام بمستوى الخدمة (SLA)"
                subtitle="نسبة الالتزام مقابل المستهدف والتفصيل حسب الأولوية والتصنيف"
                :icon="Gauge">
                <div class="flex flex-wrap items-center gap-3">
                    <Badge :variant="overallTone" class="text-sm">
                        المستهدف: {{ target }}%
                    </Badge>
                    <Badge variant="outline" class="bg-white/10 text-white border-white/25 text-sm">
                        {{ overallLabel }}
                    </Badge>
                </div>
            </PageHero>

            <!-- KPI ribbon -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <KpiCard
                    label="نسبة الالتزام (الحل)"
                    :value="overall.pct === null || overall.pct === undefined ? '—' : overall.pct + '%'"
                    :format-number="false"
                    :hint="`المستهدف ${target}%`"
                    :icon="Gauge"
                    :tone="overallTone" />
                <KpiCard label="ملتزمة" :value="overall.met ?? 0" :icon="CheckCircle2" tone="success" />
                <KpiCard label="متجاوِزة" :value="(overall.breached ?? 0) + (overall.overdue ?? 0)" :icon="XCircle" tone="destructive" />
                <KpiCard label="قيد التنفيذ" :value="overall.pending ?? 0" :icon="Clock" tone="warning" />
            </div>

            <!-- Overall gauge -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Gauge class="size-5 text-primary" />
                        الالتزام الإجمالي
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex items-baseline justify-between">
                        <span class="text-3xl font-bold tabular-nums text-foreground">
                            {{ overall.pct === null || overall.pct === undefined ? '—' : overall.pct + '%' }}
                        </span>
                        <span class="text-sm text-muted-foreground">
                            من أصل {{ overall.decided ?? 0 }} طلب مُحتسب ({{ overall.total ?? 0 }} إجمالاً)
                        </span>
                    </div>
                    <div class="sla-progress-track relative">
                        <div class="sla-progress-fill"
                            :style="{ width: (overall.pct ?? 0) + '%', backgroundColor: fillColor[overallTone] }"></div>
                        <div class="absolute inset-y-0 border-l-2 border-dashed border-foreground/40"
                            :style="{ insetInlineStart: target + '%' }"></div>
                    </div>
                    <div class="flex flex-wrap gap-4 text-xs text-muted-foreground">
                        <span class="inline-flex items-center gap-1"><CheckCircle2 class="size-3.5 text-success" /> ملتزمة {{ overall.met ?? 0 }}</span>
                        <span class="inline-flex items-center gap-1"><XCircle class="size-3.5 text-destructive" /> متجاوِزة {{ overall.breached ?? 0 }}</span>
                        <span class="inline-flex items-center gap-1"><AlertTriangle class="size-3.5 text-destructive" /> متأخرة {{ overall.overdue ?? 0 }}</span>
                        <span class="inline-flex items-center gap-1"><Clock class="size-3.5 text-warning" /> قيد التنفيذ {{ overall.pending ?? 0 }}</span>
                    </div>
                </CardContent>
            </Card>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- By priority -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Zap class="size-5 text-accent" />
                            الالتزام حسب الأولوية
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-5">
                        <div v-for="row in byPriority" :key="row.key">
                            <div class="mb-1.5 flex items-center justify-between text-sm">
                                <span class="font-medium text-foreground">{{ row.label }}</span>
                                <span class="tabular-nums" :class="`text-${toneFor(row.pct)}`">
                                    {{ row.pct === null ? '—' : row.pct + '%' }}
                                    <span class="text-muted-foreground">({{ row.met }}/{{ row.total }})</span>
                                </span>
                            </div>
                            <div class="sla-progress-track">
                                <div class="sla-progress-fill"
                                    :style="{ width: (row.pct ?? 0) + '%', backgroundColor: fillColor[toneFor(row.pct)] }"></div>
                            </div>
                        </div>
                        <p v-if="!byPriority.length" class="text-sm text-muted-foreground">لا توجد بيانات.</p>
                    </CardContent>
                </Card>

                <!-- By category -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Layers class="size-5 text-primary" />
                            الالتزام حسب التصنيف
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-5">
                        <div v-for="row in byCategory" :key="row.key">
                            <div class="mb-1.5 flex items-center justify-between text-sm">
                                <span class="font-medium text-foreground truncate">{{ row.label }}</span>
                                <span class="tabular-nums shrink-0" :class="`text-${toneFor(row.pct)}`">
                                    {{ row.pct === null ? '—' : row.pct + '%' }}
                                    <span class="text-muted-foreground">({{ row.met }}/{{ row.total }})</span>
                                </span>
                            </div>
                            <div class="sla-progress-track">
                                <div class="sla-progress-fill"
                                    :style="{ width: (row.pct ?? 0) + '%', backgroundColor: fillColor[toneFor(row.pct)] }"></div>
                            </div>
                        </div>
                        <p v-if="!byCategory.length" class="text-sm text-muted-foreground">لا توجد بيانات.</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Stage-level compliance -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Clock class="size-5 text-primary" />
                        التزام المراحل (سجل مراحل SLA)
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex items-baseline justify-between">
                        <span class="text-2xl font-bold tabular-nums text-foreground">
                            {{ stage.pct === null || stage.pct === undefined ? '—' : stage.pct + '%' }}
                        </span>
                        <span class="text-sm text-muted-foreground">
                            {{ stage.met ?? 0 }} ملتزمة / {{ stage.breached ?? 0 }} متجاوِزة من {{ stage.total ?? 0 }} مرحلة منتهية
                        </span>
                    </div>
                    <div class="sla-progress-track">
                        <div class="sla-progress-fill"
                            :style="{ width: (stage.pct ?? 0) + '%', backgroundColor: fillColor[toneFor(stage.pct)] }"></div>
                    </div>
                </CardContent>
            </Card>

            <!-- Top delaying stages -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <AlertTriangle class="size-5 text-destructive" />
                        أكثر المراحل تأخيراً
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <p v-if="!topDelayingStages.length" class="p-6 text-center text-sm text-muted-foreground">
                        لا توجد تجاوزات مسجّلة في المراحل حالياً.
                    </p>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-muted/40 text-xs text-muted-foreground">
                                <tr>
                                    <th class="p-3 text-right font-semibold">المرحلة</th>
                                    <th class="p-3 text-center font-semibold">عدد التجاوزات</th>
                                    <th class="p-3 text-center font-semibold">متوسط التأخير</th>
                                    <th class="p-3 text-center font-semibold">أقصى تأخير</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-for="(s, i) in topDelayingStages" :key="i" class="transition hover:bg-muted/20">
                                    <td class="p-3 font-medium text-foreground">{{ s.stage_name }}</td>
                                    <td class="p-3 text-center tabular-nums">
                                        <Badge variant="destructive">{{ s.breach_count }}</Badge>
                                    </td>
                                    <td class="p-3 text-center font-semibold tabular-nums text-destructive">
                                        +{{ fmtDelay(s.avg_breach_minutes) }}
                                    </td>
                                    <td class="p-3 text-center tabular-nums text-muted-foreground">
                                        +{{ fmtDelay(s.max_breach_minutes) }}
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
