<script setup>
import { Head } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import KpiCard from '@/Components/KpiCard.vue';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import { BarChart3, Inbox, CheckCircle2, Clock, GaugeCircle, Star, TrendingUp } from 'lucide-vue-next';
import { REQUEST_STATUS, REQUEST_PRIORITY, statusLabel } from '@/lib/labels';

const props = defineProps({
  stats: { type: Object, default: () => ({}) },
  byStatus: { type: Object, default: () => ({}) },
  byPriority: { type: Object, default: () => ({}) },
  teams: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
});
</script>
<template>
  <Head title="التقارير" />
  <AppShell>
    <div class="space-y-6">
      <PageHero title="التقارير ومؤشرات الأداء" subtitle="نظرة تحليلية على الأداء ورضا العملاء" :icon="BarChart3" />
      <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <KpiCard label="إجمالي الطلبات" :value="stats.total ?? 0" :icon="Inbox" tone="primary" />
        <KpiCard label="المكتملة" :value="stats.completed ?? 0" :icon="CheckCircle2" tone="success" />
        <KpiCard label="متوسط زمن الحل (ساعة)" :value="stats.avg_resolution_hours ?? 0" :icon="Clock" tone="warning" />
        <KpiCard label="التزام SLA" :value="(stats.sla_compliance ?? 0) + '%'" :format-number="false" :icon="GaugeCircle" tone="accent" />
        <KpiCard label="متوسط الرضا CSAT" :value="stats.csat ?? '—'" :format-number="false" :icon="Star" tone="accent" />
        <KpiCard label="عدد التقييمات" :value="stats.ratings_count ?? 0" :icon="Star" tone="muted" />
        <KpiCard label="مؤشر NPS" :value="stats.nps ?? 0" :icon="TrendingUp" tone="primary" />
        <KpiCard label="المفتوحة" :value="stats.open ?? 0" :icon="Inbox" tone="warning" />
      </div>
      <div class="grid gap-6 lg:grid-cols-2">
        <Card><CardHeader><CardTitle>الطلبات حسب الحالة</CardTitle></CardHeader>
          <CardContent class="space-y-2">
            <div v-for="(v,k) in byStatus" :key="k" class="flex items-center justify-between text-sm">
              <span>{{ statusLabel(REQUEST_STATUS, k).label }}</span><span class="font-bold tabular-nums">{{ v }}</span>
            </div>
            <p v-if="!Object.keys(byStatus).length" class="text-sm text-muted-foreground">لا توجد بيانات.</p>
          </CardContent></Card>
        <Card><CardHeader><CardTitle>الطلبات حسب الأولوية</CardTitle></CardHeader>
          <CardContent class="space-y-2">
            <div v-for="(v,k) in byPriority" :key="k" class="flex items-center justify-between text-sm">
              <span>{{ statusLabel(REQUEST_PRIORITY, k).label }}</span><span class="font-bold tabular-nums">{{ v }}</span>
            </div>
            <p v-if="!Object.keys(byPriority).length" class="text-sm text-muted-foreground">لا توجد بيانات.</p>
          </CardContent></Card>
      </div>
    </div>
  </AppShell>
</template>
