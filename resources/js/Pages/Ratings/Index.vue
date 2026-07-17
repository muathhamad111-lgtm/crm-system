<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import KpiCard from '@/Components/KpiCard.vue';
import Card from '@/Components/ui/Card.vue';
import Table from '@/Components/ui/Table.vue';
import TableHeader from '@/Components/ui/TableHeader.vue';
import TableBody from '@/Components/ui/TableBody.vue';
import TableRow from '@/Components/ui/TableRow.vue';
import TableCell from '@/Components/ui/TableCell.vue';
import SortableTh from '@/Components/ui/SortableTh.vue';
import { Star, Users, ThumbsUp, ThumbsDown } from 'lucide-vue-next';
import { fmtDateAr } from '@/lib/date';
import { useClientSort } from '@/lib/useSort';

const props = defineProps({
  ratings: { type: [Object, Array], default: () => ({ data: [] }) },
  stats: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  businessFields: { type: Array, default: () => [] },
  isStaff: { type: Boolean, default: false },
});

// The controller sends a plain array; tolerate a paginator-like shape too.
const rows = computed(() => (Array.isArray(props.ratings) ? props.ratings : (props.ratings?.data ?? [])));

const { sorted, sortKey, sortDir, toggle } = useClientSort(() => rows.value, 'date', 'desc', {
  number: (r) => r.request_number ?? r.request?.request_number ?? '',
  stars: 'stars',
  customer: (r) => r.customer_name ?? r.customer_email ?? r.customer?.email ?? '',
  field: 'business_field',
  notes: 'notes',
  date: 'created_at',
});

const colCount = computed(() => (props.isStaff ? 6 : 4));
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
            <SortableTh col="number" :sort-key="sortKey" :sort-dir="sortDir" @sort="toggle">الطلب</SortableTh>
            <SortableTh col="stars" :sort-key="sortKey" :sort-dir="sortDir" @sort="toggle">التقييم</SortableTh>
            <SortableTh v-if="isStaff" col="customer" :sort-key="sortKey" :sort-dir="sortDir" @sort="toggle">العميل</SortableTh>
            <SortableTh v-if="isStaff" col="field" :sort-key="sortKey" :sort-dir="sortDir" @sort="toggle">مجال العمل</SortableTh>
            <SortableTh col="notes" :sort-key="sortKey" :sort-dir="sortDir" @sort="toggle">ملاحظات</SortableTh>
            <SortableTh col="date" :sort-key="sortKey" :sort-dir="sortDir" @sort="toggle">التاريخ</SortableTh>
          </TableRow></TableHeader>
          <TableBody>
            <TableRow v-for="r in sorted" :key="r.id">
              <TableCell class="font-bold text-primary tabular-nums">{{ r.request_number ?? r.request?.request_number ?? '—' }}</TableCell>
              <TableCell><div class="flex gap-0.5"><Star v-for="n in 5" :key="n" :class="['size-4', n<=r.stars?'fill-warning text-warning':'text-muted-foreground']" /></div></TableCell>
              <TableCell v-if="isStaff" class="text-sm text-muted-foreground">{{ r.customer_email ?? r.customer?.email ?? '—' }}</TableCell>
              <TableCell v-if="isStaff" class="text-sm text-muted-foreground">{{ r.business_field ?? '—' }}</TableCell>
              <TableCell class="text-sm text-muted-foreground max-w-xs truncate">{{ r.notes ?? '—' }}</TableCell>
              <TableCell class="text-xs text-muted-foreground">{{ fmtDateAr(r.created_at) }}</TableCell>
            </TableRow>
            <TableRow v-if="!sorted.length"><TableCell :colspan="colCount" class="py-10 text-center text-muted-foreground">لا توجد تقييمات.</TableCell></TableRow>
          </TableBody>
        </Table>
      </Card>
    </div>
  </AppShell>
</template>
