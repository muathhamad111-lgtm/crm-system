<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import Input from '@/Components/ui/Input.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import Select from '@/Components/ui/Select.vue';
import Table from '@/Components/ui/Table.vue';
import TableHeader from '@/Components/ui/TableHeader.vue';
import TableBody from '@/Components/ui/TableBody.vue';
import TableRow from '@/Components/ui/TableRow.vue';
import TableHead from '@/Components/ui/TableHead.vue';
import TableCell from '@/Components/ui/TableCell.vue';
import { Plane, Plus } from 'lucide-vue-next';
import { fmtDateAr } from '@/lib/date';

const props = defineProps({
  leaves: { type: Object, default: () => ({ data: [] }) },
  leaveTypes: { type: Array, default: () => [] },
  substitutes: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({}) },
});
const rows = () => Array.isArray(props.leaves) ? props.leaves : (props.leaves.data ?? []);
const toneOf = (s)=>({ approved:'success', active:'accent', pending:'warning', rejected:'destructive', completed:'muted', draft:'muted', cancelled:'muted' }[s] ?? 'muted');
const open = ref(false);
const form = useForm({ leave_type_id:'', start_date:'', end_date:'', reason:'', substitute_id:'', coverage_strategy:'none' });
function save(){ form.post('/leaves', { onSuccess:()=>{ open.value=false; form.reset(); }, preserveScroll:true }); }
</script>
<template>
  <Head title="إجازاتي" />
  <AppShell>
    <div class="space-y-6">
      <PageHero title="إجازاتي" subtitle="تسجيل ومتابعة الإجازات والتغطية" :icon="Plane">
        <template #actions><Button variant="accent" @click="open=true"><Plus class="size-4" /> تسجيل إجازة</Button></template>
      </PageHero>
      <Card class="p-4">
        <Table>
          <TableHeader><TableRow><TableHead>النوع</TableHead><TableHead>من</TableHead><TableHead>إلى</TableHead><TableHead>الحالة</TableHead></TableRow></TableHeader>
          <TableBody>
            <TableRow v-for="l in rows()" :key="l.id">
              <TableCell>{{ l.leave_type?.label_ar ?? l.type_label ?? '—' }}</TableCell>
              <TableCell>{{ fmtDateAr(l.start_date) }}</TableCell>
              <TableCell>{{ fmtDateAr(l.end_date) }}</TableCell>
              <TableCell><Badge :variant="toneOf(l.status)">{{ l.status }}</Badge></TableCell>
            </TableRow>
            <TableRow v-if="!rows().length"><TableCell colspan="4" class="py-10 text-center text-muted-foreground">لا توجد إجازات.</TableCell></TableRow>
          </TableBody>
        </Table>
      </Card>
      <Dialog v-model:open="open" title="تسجيل إجازة">
        <div class="space-y-3">
          <Select label="نوع الإجازة" v-model="form.leave_type_id"><option value="">اختر…</option><option v-for="t in leaveTypes" :key="t.id" :value="t.id">{{ t.label_ar }}</option></Select>
          <div class="grid grid-cols-2 gap-2">
            <Input label="من" type="date" v-model="form.start_date" />
            <Input label="إلى" type="date" v-model="form.end_date" />
          </div>
          <Select v-if="substitutes.length" label="البديل" v-model="form.substitute_id"><option value="">—</option><option v-for="s in substitutes" :key="s.id" :value="s.id">{{ s.full_name }}</option></Select>
          <Textarea label="السبب" v-model="form.reason" class="min-h-16" />
          <div class="flex justify-end"><Button variant="accent" :disabled="form.processing" @click="save">حفظ</Button></div>
        </div>
      </Dialog>
    </div>
  </AppShell>
</template>
