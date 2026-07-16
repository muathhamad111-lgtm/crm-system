<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import KpiCard from '@/Components/KpiCard.vue';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import { BookOpen } from 'lucide-vue-next';

const props = defineProps({
  pending: { type: Array, default: () => [] },
  gaps: { type: Array, default: () => [] },
  kpis: { type: Object, default: () => ({}) },
  can: { type: Object, default: () => ({}) },
});
</script>
<template>
  <Head title="لوحة إدارة المعرفة" />
  <AppShell>
    <div class="space-y-6">
      <PageHero title="لوحة إدارة المعرفة" subtitle="الموافقات والفجوات المعرفية" :icon="BookOpen" />
      <div class="grid grid-cols-3 gap-4">
        <KpiCard label="بانتظار الاعتماد" :value="pending.length" tone="warning" />
        <KpiCard label="فجوات معرفية" :value="gaps.length" tone="destructive" />
        <KpiCard label="إجمالي المقالات" :value="kpis.total ?? 0" tone="primary" />
      </div>
      <Card><CardHeader><CardTitle>بانتظار الاعتماد</CardTitle></CardHeader>
        <CardContent class="space-y-2">
          <div v-for="a in pending" :key="a.id" class="flex items-center justify-between rounded-lg bg-muted/40 p-3">
            <a :href="`/knowledge-base/${a.id}`" class="font-medium hover:text-primary">{{ a.title }}</a>
          </div>
          <p v-if="!pending.length" class="text-sm text-muted-foreground">لا توجد مقالات بانتظار الاعتماد.</p>
        </CardContent></Card>
      <Card><CardHeader><CardTitle>الفجوات المعرفية</CardTitle></CardHeader>
        <CardContent class="space-y-2">
          <div v-for="g in gaps" :key="g.id" class="flex items-center justify-between rounded-lg bg-muted/40 p-3">
            <span>{{ g.topic }}</span><span class="text-xs text-muted-foreground tabular-nums">{{ g.occurrences }} مرة</span>
          </div>
          <p v-if="!gaps.length" class="text-sm text-muted-foreground">لا توجد فجوات مسجّلة.</p>
        </CardContent></Card>
    </div>
  </AppShell>
</template>
