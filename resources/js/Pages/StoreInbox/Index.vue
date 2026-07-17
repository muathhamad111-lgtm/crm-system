<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import Card from '@/Components/ui/Card.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import Select from '@/Components/ui/Select.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import Table from '@/Components/ui/Table.vue';
import TableHeader from '@/Components/ui/TableHeader.vue';
import TableBody from '@/Components/ui/TableBody.vue';
import TableRow from '@/Components/ui/TableRow.vue';
import TableHead from '@/Components/ui/TableHead.vue';
import SortableTh from '@/Components/ui/SortableTh.vue';
import TableCell from '@/Components/ui/TableCell.vue';
import { Mailbox } from 'lucide-vue-next';
import { fmtDateAr } from '@/lib/date';

const props = defineProps({
  messages: { type: Object, default: () => ({ data: [] }) },
  filters: { type: Object, default: () => ({ status: 'new' }) },
  kpis: { type: Object, default: () => ({}) },
});
const statuses = [['new','جديدة'],['handled','تمّت المعالجة'],['archived','مؤرشفة']];
const toneOf = (s) => ({ new:'warning', handled:'success', archived:'muted' }[s] ?? 'muted');
const labelOf = (s) => (statuses.find(x=>x[0]===s)?.[1] ?? s);
function go(extra = {}){
  router.get('/store-contact-inbox', {
    status: props.filters.status, sort: props.filters.sort, dir: props.filters.dir, ...extra,
  }, { preserveState:true, replace:true, preserveScroll:true });
}
function setStatus(s){ go({ status:s }); }

// --- Server-side sorting ---
const sortKey = computed(() => props.filters.sort ?? 'date');
const sortDir = computed(() => props.filters.dir ?? 'desc');
function setSort(col){
  const dir = sortKey.value === col && sortDir.value === 'desc' ? 'asc' : 'desc';
  go({ sort: col, dir });
}

const open = ref(false); const current = ref(null);
const form = useForm({ status:'handled', internal_note:'' });
function view(m){ current.value=m; form.status=m.status; form.internal_note=m.internal_note ?? ''; open.value=true; }
function save(){ form.post(`/store-contact-inbox/${current.value.id}/status`, { onSuccess:()=>open.value=false, preserveScroll:true }); }
</script>
<template>
  <Head title="صندوق رسائل المتجر" />
  <AppShell>
    <div class="space-y-6">
      <PageHero title="صندوق رسائل متجر التحول التقني" subtitle="رسائل الزوّار من نموذج تواصل معنا" :icon="Mailbox" />
      <div class="flex gap-2">
        <button v-for="s in statuses" :key="s[0]" @click="setStatus(s[0])"
          :class="['rounded-full px-3.5 py-1.5 text-sm font-medium', filters.status===s[0]?'bg-primary text-primary-foreground':'bg-muted text-muted-foreground']">
          {{ s[1] }}<span v-if="kpis[s[0]]" class="mr-1 tabular-nums opacity-80">({{ kpis[s[0]] }})</span>
        </button>
      </div>
      <Card class="p-4">
        <Table>
          <TableHeader><TableRow>
            <SortableTh col="sender" :sort-key="sortKey" :sort-dir="sortDir" @sort="setSort">المرسل</SortableTh>
            <SortableTh col="email" :sort-key="sortKey" :sort-dir="sortDir" @sort="setSort">البريد</SortableTh>
            <SortableTh col="service" :sort-key="sortKey" :sort-dir="sortDir" @sort="setSort">الخدمة</SortableTh>
            <SortableTh col="status" :sort-key="sortKey" :sort-dir="sortDir" @sort="setSort">الحالة</SortableTh>
            <SortableTh col="date" :sort-key="sortKey" :sort-dir="sortDir" @sort="setSort">التاريخ</SortableTh>
            <TableHead></TableHead>
          </TableRow></TableHeader>
          <TableBody>
            <TableRow v-for="m in messages.data" :key="m.id">
              <TableCell class="font-medium">{{ m.first_name }} {{ m.last_name }}</TableCell>
              <TableCell class="text-sm text-muted-foreground" dir="ltr">{{ m.email }}</TableCell>
              <TableCell class="text-sm">{{ m.service_type }}</TableCell>
              <TableCell><Badge :variant="toneOf(m.status)">{{ labelOf(m.status) }}</Badge></TableCell>
              <TableCell class="text-xs text-muted-foreground">{{ fmtDateAr(m.created_at) }}</TableCell>
              <TableCell><Button size="sm" variant="outline" @click="view(m)">عرض</Button></TableCell>
            </TableRow>
            <TableRow v-if="!messages.data.length"><TableCell colspan="6" class="py-10 text-center text-muted-foreground">لا توجد رسائل.</TableCell></TableRow>
          </TableBody>
        </Table>
      </Card>
      <Dialog v-model:open="open" title="تفاصيل الرسالة">
        <div v-if="current" class="space-y-3 text-sm">
          <p><span class="text-muted-foreground">الاسم:</span> {{ current.first_name }} {{ current.last_name }}</p>
          <p><span class="text-muted-foreground">البريد:</span> <span dir="ltr">{{ current.email }}</span></p>
          <p><span class="text-muted-foreground">الجوال:</span> <span dir="ltr">{{ current.mobile }}</span></p>
          <p v-if="current.company_name"><span class="text-muted-foreground">المنشأة:</span> {{ current.company_name }}</p>
          <p class="whitespace-pre-wrap rounded-lg bg-muted p-3">{{ current.description }}</p>
          <div><label class="text-muted-foreground">الحالة</label>
            <Select v-model="form.status" class="mt-1"><option v-for="s in statuses" :key="s[0]" :value="s[0]">{{ s[1] }}</option></Select></div>
          <Textarea v-model="form.internal_note" placeholder="ملاحظة داخلية" class="min-h-16" />
          <div class="flex justify-end"><Button variant="accent" :disabled="form.processing" @click="save">حفظ</Button></div>
        </div>
      </Dialog>
    </div>
  </AppShell>
</template>
