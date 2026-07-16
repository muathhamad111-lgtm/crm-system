<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import { ArrowRight } from 'lucide-vue-next';
import { fmtFullDateTimeAr, timeAgoAr } from '@/lib/date';
import { APPOINTMENT_STATUS, statusLabel } from '@/lib/labels';

const props = defineProps({
  appointment: { type: Object, required: true },
  activity: { type: Array, default: () => [] },
  can: { type: Object, default: () => ({}) },
});
const a = props.appointment;
function act(action){ router.post(`/appointments/${a.id}/${action}`, {}, { preserveScroll:true }); }
</script>
<template>
  <Head title="تفاصيل الموعد" />
  <AppShell>
    <div class="mx-auto max-w-3xl space-y-6">
      <div class="flex items-center gap-3">
        <Button href="/appointments" variant="ghost" size="icon-sm"><ArrowRight class="size-4" /></Button>
        <h1 class="text-xl font-bold">تفاصيل الموعد</h1>
        <Badge class="mr-auto" :variant="statusLabel(APPOINTMENT_STATUS, a.status).tone">{{ statusLabel(APPOINTMENT_STATUS, a.status).label }}</Badge>
      </div>
      <Card><CardContent class="space-y-3 pt-6 text-sm">
        <div class="flex justify-between"><span class="text-muted-foreground">الرقم</span><span class="tabular-nums">{{ a.appointment_number }}</span></div>
        <div class="flex justify-between"><span class="text-muted-foreground">البداية</span><span>{{ fmtFullDateTimeAr(a.starts_at) }}</span></div>
        <div class="flex justify-between"><span class="text-muted-foreground">النهاية</span><span>{{ fmtFullDateTimeAr(a.ends_at) }}</span></div>
        <div v-if="a.location" class="flex justify-between"><span class="text-muted-foreground">المكان</span><span>{{ a.location }}</span></div>
        <div v-if="a.meeting_url" class="flex justify-between"><span class="text-muted-foreground">رابط الاجتماع</span><a :href="a.meeting_url" class="text-primary" dir="ltr">{{ a.meeting_url }}</a></div>
        <div v-if="a.customer_notes"><p class="text-muted-foreground mb-1">ملاحظات العميل</p><p class="rounded bg-muted p-2">{{ a.customer_notes }}</p></div>
      </CardContent></Card>
      <Card v-if="can.confirm || can.cancel"><CardHeader><CardTitle>إجراءات</CardTitle></CardHeader>
        <CardContent class="flex gap-2">
          <Button v-if="can.confirm" variant="success" @click="act('confirm')">تأكيد</Button>
          <Button v-if="can.cancel" variant="outline" @click="act('cancel')">إلغاء</Button>
        </CardContent></Card>
      <Card><CardHeader><CardTitle>سجل النشاط</CardTitle></CardHeader>
        <CardContent class="space-y-2">
          <div v-for="ev in activity" :key="ev.id" class="text-sm flex justify-between"><span>{{ ev.action }}</span><span class="text-muted-foreground text-xs">{{ timeAgoAr(ev.created_at) }}</span></div>
          <p v-if="!activity.length" class="text-sm text-muted-foreground">لا يوجد نشاط.</p>
        </CardContent></Card>
    </div>
  </AppShell>
</template>
