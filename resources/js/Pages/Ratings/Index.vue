<script setup>
import { Head } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import KpiCard from '@/Components/KpiCard.vue';
import Card from '@/Components/ui/Card.vue';
import Table from '@/Components/ui/Table.vue';
import TableHeader from '@/Components/ui/TableHeader.vue';
import TableBody from '@/Components/ui/TableBody.vue';
import TableRow from '@/Components/ui/TableRow.vue';
import TableHead from '@/Components/ui/TableHead.vue';
import TableCell from '@/Components/ui/TableCell.vue';
import { Star, Users, ThumbsUp, ThumbsDown } from 'lucide-vue-next';
import { fmtDateAr } from '@/lib/date';

const props = defineProps({
  ratings: { type: Object, default: () => ({ data: [] }) },
  stats: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  businessFields: { type: Array, default: () => [] },
  isStaff: { type: Boolean, default: false },
});
</script>
<template>
  <Head title="التقييمات" />
  <AppShell>
    <div class="space-y-6">
      <PageHero title="قائمة التقييمات" subtitle="رضا العملاء عن الخدمة" :icon="Star" />
      <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <KpiCard label="متوسط الرضا" :value="stats.avg ?? '—'" :format-number="false" :icon="Star" tone="accent" />
        <KpiCard label="إجمالي التقييمات" :value="stats.total ?? 0" :icon="Users" tone="primary" />
        <KpiCard label="راضون" :value="stats.promoters ?? 0" :icon="ThumbsUp" tone="success" />
        <KpiCard label="غير راضين" :value="stats.detractors ?? 0" :icon="ThumbsDown" tone="destructive" />
      </div>
      <Card class="p-4">
        <Table>
          <TableHeader><TableRow>
            <TableHead>الطلب</TableHead><TableHead>التقييم</TableHead>
            <TableHead v-if="isStaff">العميل</TableHead><TableHead>ملاحظات</TableHead><TableHead>التاريخ</TableHead>
          </TableRow></TableHeader>
          <TableBody>
            <TableRow v-for="r in ratings.data" :key="r.id">
              <TableCell class="font-bold text-primary tabular-nums">{{ r.request_number ?? r.request?.request_number ?? '—' }}</TableCell>
              <TableCell><div class="flex gap-0.5"><Star v-for="n in 5" :key="n" :class="['size-4', n<=r.stars?'fill-warning text-warning':'text-muted-foreground']" /></div></TableCell>
              <TableCell v-if="isStaff" class="text-sm text-muted-foreground">{{ r.customer_email ?? r.customer?.email ?? '—' }}</TableCell>
              <TableCell class="text-sm text-muted-foreground max-w-xs truncate">{{ r.notes ?? '—' }}</TableCell>
              <TableCell class="text-xs text-muted-foreground">{{ fmtDateAr(r.created_at) }}</TableCell>
            </TableRow>
            <TableRow v-if="!ratings.data.length"><TableCell colspan="5" class="py-10 text-center text-muted-foreground">لا توجد تقييمات.</TableCell></TableRow>
          </TableBody>
        </Table>
      </Card>
    </div>
  </AppShell>
</template>
