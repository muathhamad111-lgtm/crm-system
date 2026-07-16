<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import Card from '@/Components/ui/Card.vue';
import Badge from '@/Components/ui/Badge.vue';
import Table from '@/Components/ui/Table.vue';
import TableHeader from '@/Components/ui/TableHeader.vue';
import TableBody from '@/Components/ui/TableBody.vue';
import TableRow from '@/Components/ui/TableRow.vue';
import TableHead from '@/Components/ui/TableHead.vue';
import TableCell from '@/Components/ui/TableCell.vue';
import { ListChecks } from 'lucide-vue-next';
import { timeAgoAr } from '@/lib/date';
import { IDEA_STAGE, statusLabel } from '@/lib/labels';

const props = defineProps({
  suggestions: { type: Object, default: () => ({ data: [] }) },
  filters: { type: Object, default: () => ({}) },
  products: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({}) },
});
</script>
<template>
  <Head title="صندوق المقترحات" />
  <AppShell>
    <div class="space-y-6">
      <PageHero title="صندوق المقترحات" subtitle="فرز ومتابعة مقترحات العملاء" :icon="ListChecks" />
      <Card class="p-4">
        <Table>
          <TableHeader><TableRow>
            <TableHead>الرقم</TableHead><TableHead>العنوان</TableHead><TableHead>المرحلة</TableHead><TableHead>آخر تحديث</TableHead>
          </TableRow></TableHeader>
          <TableBody>
            <TableRow v-for="s in suggestions.data" :key="s.id" class="cursor-pointer" @click="router.visit(`/suggestions/${s.id}`)">
              <TableCell class="font-bold text-primary tabular-nums">{{ s.request_number }}</TableCell>
              <TableCell class="max-w-md truncate">{{ s.title }}</TableCell>
              <TableCell><Badge :variant="statusLabel(IDEA_STAGE, s.idea_stage).tone">{{ statusLabel(IDEA_STAGE, s.idea_stage).label }}</Badge></TableCell>
              <TableCell class="text-xs text-muted-foreground">{{ timeAgoAr(s.updated_at) }}</TableCell>
            </TableRow>
            <TableRow v-if="!suggestions.data.length"><TableCell colspan="4" class="py-10 text-center text-muted-foreground">لا توجد مقترحات.</TableCell></TableRow>
          </TableBody>
        </Table>
      </Card>
    </div>
  </AppShell>
</template>
