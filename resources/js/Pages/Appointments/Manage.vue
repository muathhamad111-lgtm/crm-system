<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import KpiCard from '@/Components/KpiCard.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Table from '@/Components/ui/Table.vue';
import TableHeader from '@/Components/ui/TableHeader.vue';
import TableBody from '@/Components/ui/TableBody.vue';
import TableRow from '@/Components/ui/TableRow.vue';
import TableHead from '@/Components/ui/TableHead.vue';
import TableCell from '@/Components/ui/TableCell.vue';
import { CalendarClock } from 'lucide-vue-next';
import { fmtFullDateTimeAr } from '@/lib/date';
import { APPOINTMENT_STATUS, statusLabel } from '@/lib/labels';

const props = defineProps({
  appointments: { type: Object, default: () => ({ data: [] }) },
  filter: { type: String, default: 'all' },
  stats: { type: Object, default: () => ({}) },
});
function act(a, action){ router.post(`/appointments/${a.id}/${action}`, {}, { preserveScroll:true }); }
</script>
<template>
  <Head title="متابعة المواعيد" />
  <AppShell>
    <div class="space-y-6">
      <PageHero title="متابعة المواعيد" subtitle="تأكيد وإدارة مواعيد العملاء" :icon="CalendarClock" />
      <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <KpiCard label="بانتظار التأكيد" :value="stats.pending ?? 0" tone="warning" />
        <KpiCard label="مؤكدة" :value="stats.confirmed ?? 0" tone="success" />
        <KpiCard label="منتهية" :value="stats.completed ?? 0" tone="muted" />
        <KpiCard label="الإجمالي" :value="stats.total ?? 0" tone="primary" />
      </div>
      <Card class="p-4">
        <Table>
          <TableHeader><TableRow>
            <TableHead>الرقم</TableHead><TableHead>الموعد</TableHead><TableHead>الحالة</TableHead><TableHead></TableHead>
          </TableRow></TableHeader>
          <TableBody>
            <TableRow v-for="a in appointments.data" :key="a.id">
              <TableCell class="font-bold text-primary tabular-nums">{{ a.appointment_number ?? '—' }}</TableCell>
              <TableCell>{{ fmtFullDateTimeAr(a.starts_at) }}</TableCell>
              <TableCell><Badge :variant="statusLabel(APPOINTMENT_STATUS, a.status).tone">{{ statusLabel(APPOINTMENT_STATUS, a.status).label }}</Badge></TableCell>
              <TableCell class="flex gap-1">
                <Button size="sm" variant="success" @click="act(a,'confirm')">تأكيد</Button>
                <Button size="sm" variant="outline" @click="act(a,'reject')">رفض</Button>
              </TableCell>
            </TableRow>
            <TableRow v-if="!appointments.data.length"><TableCell colspan="4" class="py-10 text-center text-muted-foreground">لا توجد مواعيد.</TableCell></TableRow>
          </TableBody>
        </Table>
      </Card>
    </div>
  </AppShell>
</template>
