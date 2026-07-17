<script setup>
import { Head } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import KpiCard from '@/Components/KpiCard.vue';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Table from '@/Components/ui/Table.vue';
import TableHeader from '@/Components/ui/TableHeader.vue';
import TableBody from '@/Components/ui/TableBody.vue';
import TableRow from '@/Components/ui/TableRow.vue';
import TableHead from '@/Components/ui/TableHead.vue';
import SortableTh from '@/Components/ui/SortableTh.vue';
import TableCell from '@/Components/ui/TableCell.vue';
import { Store, Users, Package } from 'lucide-vue-next';
import { useClientSort } from '@/lib/useSort';

const props = defineProps({
  customer: { type: Object, default: () => ({}) },
  contacts: { type: Array, default: () => [] },
  subscriptions: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({}) },
});

// --- Client-side sorting for the contacts + subscriptions tables ---
const {
  sorted: sortedContacts, sortKey: contactsSortKey, sortDir: contactsSortDir, toggle: toggleContacts,
} = useClientSort(() => props.contacts, null, 'asc', {
  name: 'full_name', mobile: 'mobile', title: 'job_title',
});
const {
  sorted: sortedSubs, sortKey: subsSortKey, sortDir: subsSortDir, toggle: toggleSubs,
} = useClientSort(() => props.subscriptions, null, 'asc', {
  product: (r) => r.product_name || r.product_code, plan: 'package_name', status: 'status',
});
</script>
<template>
  <Head :title="customer.full_name ?? 'عميل المتجر'" />
  <AppShell>
    <div class="space-y-6">
      <PageHero :title="customer.full_name ?? '—'" :subtitle="customer.business_field ?? 'عميل المتجر'" :icon="Store" />
      <div class="grid grid-cols-3 gap-4">
        <KpiCard label="جهات التواصل" :value="stats.contacts_count ?? contacts.length" :icon="Users" tone="primary" />
        <KpiCard label="الاشتراكات" :value="stats.subscriptions_count ?? subscriptions.length" :icon="Package" tone="accent" />
        <KpiCard label="اشتراكات نشطة" :value="stats.active_subscriptions ?? 0" :icon="Package" tone="success" />
      </div>
      <Card>
        <CardHeader><CardTitle>جهات التواصل</CardTitle></CardHeader>
        <CardContent>
          <Table>
            <TableHeader><TableRow>
              <SortableTh col="name" :sort-key="contactsSortKey" :sort-dir="contactsSortDir" @sort="toggleContacts">الاسم</SortableTh>
              <SortableTh col="mobile" :sort-key="contactsSortKey" :sort-dir="contactsSortDir" @sort="toggleContacts">الجوال</SortableTh>
              <SortableTh col="title" :sort-key="contactsSortKey" :sort-dir="contactsSortDir" @sort="toggleContacts">المسمى</SortableTh>
            </TableRow></TableHeader>
            <TableBody>
              <TableRow v-for="c in sortedContacts" :key="c.id"><TableCell>{{ c.full_name }}</TableCell><TableCell dir="ltr">{{ c.mobile ?? '—' }}</TableCell><TableCell>{{ c.job_title ?? '—' }}</TableCell></TableRow>
              <TableRow v-if="!contacts.length"><TableCell colspan="3" class="py-6 text-center text-muted-foreground">لا توجد جهات تواصل.</TableCell></TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
      <Card>
        <CardHeader><CardTitle>الاشتراكات</CardTitle></CardHeader>
        <CardContent>
          <Table>
            <TableHeader><TableRow>
              <SortableTh col="product" :sort-key="subsSortKey" :sort-dir="subsSortDir" @sort="toggleSubs">المنتج</SortableTh>
              <SortableTh col="plan" :sort-key="subsSortKey" :sort-dir="subsSortDir" @sort="toggleSubs">الباقة</SortableTh>
              <SortableTh col="status" :sort-key="subsSortKey" :sort-dir="subsSortDir" @sort="toggleSubs">الحالة</SortableTh>
            </TableRow></TableHeader>
            <TableBody>
              <TableRow v-for="s in sortedSubs" :key="s.id"><TableCell>{{ s.product_name ?? s.product_code }}</TableCell><TableCell>{{ s.package_name ?? '—' }}</TableCell><TableCell>{{ s.status }}</TableCell></TableRow>
              <TableRow v-if="!subscriptions.length"><TableCell colspan="3" class="py-6 text-center text-muted-foreground">لا توجد اشتراكات.</TableCell></TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppShell>
</template>
