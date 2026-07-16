<script setup>
import { computed } from 'vue';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Badge from '@/Components/ui/Badge.vue';
import { HeartPulse, ShieldCheck, Shield, ShieldAlert } from 'lucide-vue-next';

const props = defineProps({
    stats: { type: Object, required: true },
});

const health = computed(() => {
    const s = props.stats;
    const req = s.requests ?? {};
    const sat = s.satisfaction ?? {};
    let score = 100;
    const reasons = [];

    if ((req.overdue ?? 0) > 0) {
        score -= Math.min(40, req.overdue * 10);
        reasons.push(`${req.overdue} طلب متأخر`);
    }
    if (req.sla_pct != null && req.sla_pct < 80) {
        score -= (80 - req.sla_pct) / 2;
        reasons.push(`التزام SLA ${req.sla_pct}%`);
    }
    if (sat.csat != null && sat.csat < 3.5) {
        score -= (3.5 - sat.csat) * 15;
        reasons.push(`متوسط الرضا ${sat.csat}/5`);
    }
    if ((sat.detractors ?? 0) > 0) {
        score -= Math.min(15, sat.detractors * 5);
        reasons.push(`${sat.detractors} تقييم غير راضٍ`);
    }
    if ((req.reopened ?? 0) > 0) {
        score -= Math.min(10, req.reopened * 2);
        reasons.push(`${req.reopened} طلب أُعيد فتحه`);
    }
    if ((s.active_subscriptions ?? 0) === 0 && (req.total ?? 0) > 0) {
        score -= 10;
        reasons.push('لا توجد اشتراكات نشطة');
    }
    score = Math.max(0, Math.min(100, Math.round(score)));
    const level = score >= 75 ? 'green' : score >= 50 ? 'yellow' : 'red';
    if (reasons.length === 0) reasons.push('لا توجد إشارات سلبية حالياً');
    return { score, level, reasons };
});

const LEVEL = {
    green: { label: 'ممتاز', icon: ShieldCheck, tone: 'from-success/15 to-success/5 border-success/40', text: 'text-success', bar: 'bg-success' },
    yellow: { label: 'يحتاج متابعة', icon: Shield, tone: 'from-warning/15 to-warning/5 border-warning/40', text: 'text-warning', bar: 'bg-warning' },
    red: { label: 'حرج', icon: ShieldAlert, tone: 'from-destructive/15 to-destructive/5 border-destructive/40', text: 'text-destructive', bar: 'bg-destructive' },
};
const meta = computed(() => LEVEL[health.value.level]);
</script>

<template>
    <Card :class="`relative overflow-hidden border bg-gradient-to-bl ${meta.tone}`">
        <CardHeader class="pb-3">
            <CardTitle class="flex items-center justify-between gap-2 text-base">
                <span class="flex items-center gap-2">
                    <span :class="`flex size-9 items-center justify-center rounded-xl bg-card/70 ${meta.text}`">
                        <HeartPulse class="size-4" />
                    </span>
                    صحة الحساب
                </span>
                <Badge variant="outline" :class="`gap-1 bg-card/60 border-current ${meta.text}`">
                    <component :is="meta.icon" class="size-3.5" /> {{ meta.label }}
                </Badge>
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-3">
            <div>
                <div class="mb-1 flex items-baseline justify-between">
                    <span class="text-xs text-muted-foreground">المؤشر العام</span>
                    <span :class="`text-2xl font-black tabular-nums ${meta.text}`">
                        {{ health.score }}<span class="text-sm font-normal text-muted-foreground">/100</span>
                    </span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-card/60">
                    <div :class="`h-full transition-all ${meta.bar}`" :style="{ width: `${health.score}%` }"></div>
                </div>
            </div>
            <div>
                <div class="mb-1.5 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">الإشارات</div>
                <ul class="space-y-1 text-xs">
                    <li v-for="(r, i) in health.reasons" :key="i" class="flex items-start gap-1.5">
                        <span :class="`mt-1.5 size-1.5 shrink-0 rounded-full ${meta.bar}`"></span>
                        <span class="text-foreground">{{ r }}</span>
                    </li>
                </ul>
            </div>
        </CardContent>
    </Card>
</template>
