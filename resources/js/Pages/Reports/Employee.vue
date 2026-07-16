<script setup>
import { Head } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import KpiCard from '@/Components/KpiCard.vue';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import { UserCircle } from 'lucide-vue-next';

const props = defineProps({
  profile: { type: Object, default: () => ({}) },
  stats: { type: Object, default: () => ({}) },
  recent: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
});
</script>
<template>
  <Head title="تقرير الموظف" />
  <AppShell>
    <div class="space-y-6">
      <PageHero :title="`تقرير أداء: ${profile.full_name ?? ''}`" subtitle="مؤشرات أداء الموظف" :icon="UserCircle" />
      <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <KpiCard label="الطلبات المسندة" :value="stats.assigned ?? 0" tone="primary" />
        <KpiCard label="المغلقة" :value="stats.closed ?? 0" tone="success" />
        <KpiCard label="المفتوحة" :value="stats.open ?? 0" tone="warning" />
        <KpiCard label="متوسط الرضا" :value="stats.csat ?? '—'" :format-number="false" tone="accent" />
      </div>
    </div>
  </AppShell>
</template>
