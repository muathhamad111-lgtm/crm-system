<script setup>
import { computed } from 'vue';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Badge from '@/Components/ui/Badge.vue';
import { Compass, ShieldCheck, Shield, ShieldAlert, CheckCircle2, Circle } from 'lucide-vue-next';

const props = defineProps({
    profile: { type: Object, required: true },
    tasks: { type: Array, default: () => [] },
});

const STAGE = {
    new: { label: 'جديد', tone: 'default' },
    onboarding: { label: 'تأهيل', tone: 'warning' },
    active: { label: 'نشط', tone: 'success' },
    needs_follow_up: { label: 'بحاجة متابعة', tone: 'warning' },
    at_risk: { label: 'في خطر', tone: 'destructive' },
    expansion: { label: 'نمو / توسّع', tone: 'accent' },
    churned: { label: 'منتهٍ', tone: 'muted' },
};
const RISK = {
    low: { label: 'منخفض', icon: ShieldCheck, tone: 'success' },
    medium: { label: 'متوسط', icon: Shield, tone: 'warning' },
    high: { label: 'مرتفع', icon: ShieldAlert, tone: 'destructive' },
};

const stage = computed(() => STAGE[props.profile.journey_stage] ?? STAGE.new);
const risk = computed(() => RISK[props.profile.risk_level] ?? RISK.low);
const pct = computed(() => props.profile.activation_percent ?? 0);
const doneCount = computed(() => props.tasks.filter((t) => t.status === 'completed').length);
const activeTasks = computed(() => props.tasks.filter((t) => t.status !== 'skipped'));
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2 text-lg">
                <Compass class="size-5 text-primary" /> رحلة العميل والتأهيل
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-5">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="space-y-1.5">
                    <div class="text-xs text-muted-foreground">مرحلة الرحلة</div>
                    <Badge :variant="stage.tone">{{ stage.label }}</Badge>
                </div>
                <div class="space-y-1.5">
                    <div class="text-xs text-muted-foreground">مستوى المخاطر</div>
                    <Badge :variant="risk.tone" class="w-fit">
                        <component :is="risk.icon" class="size-3" /> {{ risk.label }}
                    </Badge>
                </div>
                <div class="space-y-1.5">
                    <div class="text-xs text-muted-foreground">نسبة التفعيل</div>
                    <div class="text-2xl font-bold tabular-nums text-primary">{{ pct }}%</div>
                </div>
            </div>

            <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                <div class="h-full bg-gradient-to-l from-primary to-accent transition-all" :style="{ width: `${pct}%` }"></div>
            </div>

            <div class="space-y-2">
                <div class="text-sm font-semibold">مهام التفعيل ({{ doneCount }}/{{ activeTasks.length }})</div>
                <p v-if="!tasks.length" class="text-xs text-muted-foreground">لا توجد مهام تفعيل بعد.</p>
                <div v-else class="space-y-1.5">
                    <div v-for="t in tasks" :key="t.id"
                        class="flex items-center gap-2 rounded-lg border border-border/60 bg-card/40 p-2">
                        <CheckCircle2 v-if="t.status === 'completed'" class="size-4 shrink-0 text-success" />
                        <Circle v-else class="size-4 shrink-0 text-muted-foreground/50" />
                        <span class="flex-1 text-sm" :class="t.status === 'completed' ? 'text-muted-foreground line-through' : ''">
                            {{ t.title }}
                        </span>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
