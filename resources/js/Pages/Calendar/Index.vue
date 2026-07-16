<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import KpiCard from '@/Components/KpiCard.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import Input from '@/Components/ui/Input.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import Select from '@/Components/ui/Select.vue';
import Label from '@/Components/ui/Label.vue';
import { CalendarDays, Plus } from 'lucide-vue-next';
import { fmtFullDateTimeAr } from '@/lib/date';

const props = defineProps({
  events: { type: Object, default: () => ({ data: [] }) },
  filters: { type: Object, default: () => ({}) },
  stats: { type: Object, default: () => ({}) },
});
const rows = () => Array.isArray(props.events) ? props.events : (props.events.data ?? props.events);
const types = [['meeting','اجتماع'],['visit','زيارة'],['call','مكالمة'],['reminder','تذكير'],['task','مهمة'],['other','أخرى']];
const open = ref(false);
const form = useForm({ title:'', event_type:'meeting', starts_at:'', ends_at:'', description:'', meeting_url:'' });
function save(){ form.post('/calendar', { onSuccess:()=>{ open.value=false; form.reset(); }, preserveScroll:true }); }
</script>
<template>
  <Head title="التقويم" />
  <AppShell>
    <div class="space-y-6">
      <PageHero title="التقويم — الزيارات والاجتماعات" subtitle="إدارة أحداث الفريق" :icon="CalendarDays">
        <template #actions><Button variant="accent" @click="open=true"><Plus class="size-4" /> حدث جديد</Button></template>
      </PageHero>
      <div class="grid grid-cols-3 gap-4">
        <KpiCard label="الإجمالي" :value="stats.total ?? 0" tone="primary" />
        <KpiCard label="مجدولة" :value="stats.scheduled ?? 0" tone="accent" />
        <KpiCard label="قادمة" :value="stats.upcoming ?? 0" tone="warning" />
      </div>
      <div class="space-y-2">
        <Card v-for="e in rows()" :key="e.id" class="p-4 flex items-center gap-3">
          <div class="flex-1"><p class="font-bold">{{ e.title }}</p><p class="text-xs text-muted-foreground">{{ fmtFullDateTimeAr(e.starts_at) }}</p></div>
          <Badge variant="muted">{{ (types.find(t=>t[0]===e.event_type)||[])[1] ?? e.event_type }}</Badge>
        </Card>
        <Card v-if="!rows().length" class="p-10 text-center text-muted-foreground">لا توجد أحداث.</Card>
      </div>
      <Dialog v-model:open="open" title="حدث جديد">
        <div class="space-y-3">
          <div><Label>العنوان</Label><Input v-model="form.title" class="mt-1" /></div>
          <div><Label>النوع</Label><Select v-model="form.event_type" class="mt-1"><option v-for="t in types" :key="t[0]" :value="t[0]">{{ t[1] }}</option></Select></div>
          <div class="grid grid-cols-2 gap-2">
            <div><Label>البداية</Label><Input type="datetime-local" v-model="form.starts_at" class="mt-1" /></div>
            <div><Label>النهاية</Label><Input type="datetime-local" v-model="form.ends_at" class="mt-1" /></div>
          </div>
          <div><Label>رابط الاجتماع</Label><Input v-model="form.meeting_url" class="mt-1" dir="ltr" /></div>
          <Textarea v-model="form.description" placeholder="وصف" class="min-h-16" />
          <div class="flex justify-end"><Button variant="accent" :disabled="form.processing" @click="save">حفظ</Button></div>
        </div>
      </Dialog>
    </div>
  </AppShell>
</template>
