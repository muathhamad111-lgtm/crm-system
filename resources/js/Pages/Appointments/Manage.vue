<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import Label from '@/Components/ui/Label.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import SortableTh from '@/Components/ui/SortableTh.vue';
import { APPOINTMENT_STATUS, statusLabel } from '@/lib/labels';
import { fmtTimeAr } from '@/lib/date';
import { useClientSort } from '@/lib/useSort';
import {
    CalendarDays, Phone, Video, MapPin, GraduationCap, Workflow, HelpCircle, Clock,
    ListChecks, CheckCircle2, XCircle, UserX, TrendingUp, CalendarClock, RotateCcw, Hourglass,
} from 'lucide-vue-next';

const props = defineProps({
    appointments: { type: Array, default: () => [] },
    filter: { type: String, default: 'all' },
    slotsByType: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
});

const MODE_ICON = { phone: Phone, video: Video, onsite: MapPin, training: GraduationCap, followup: Workflow, other: HelpCircle };
const modeIcon = (m) => MODE_ICON[m] ?? HelpCircle;
const STATUS_DOT = {
    pending_confirmation: 'bg-warning', confirmed: 'bg-success', completed: 'bg-primary',
    cancelled: 'bg-destructive', no_show: 'bg-muted-foreground', rescheduled: 'bg-info',
};

const AR = 'ar-SA-u-ca-gregory';
const TZ = 'Asia/Riyadh';
const fmt = (v, opts) => (v ? new Intl.DateTimeFormat(AR, { timeZone: TZ, ...opts }).format(new Date(v)) : '—');
const dayTime = (v) => fmt(v, { weekday: 'long', day: 'numeric', month: 'short' });
const shortDate = (v) => fmt(v, { day: 'numeric', month: 'short' });

const TABS = [
    { v: 'all', l: 'الكل', icon: ListChecks },
    { v: 'pending_confirmation', l: 'بانتظار التأكيد', icon: Hourglass },
    { v: 'confirmed', l: 'المؤكدة', icon: CheckCircle2 },
    { v: 'completed', l: 'المكتملة', icon: CalendarDays },
    { v: 'cancelled', l: 'الملغاة', icon: XCircle },
    { v: 'no_show', l: 'لم يحضر', icon: UserX },
];
function setFilter(v) {
    router.get(route('appointments.manage'), { status: v === 'all' ? undefined : v }, { preserveState: true, replace: true, preserveScroll: true });
}

const KPIS = computed(() => [
    { icon: ListChecks, label: 'إجمالي المواعيد', value: props.stats.total ?? 0, tone: 'bg-primary-soft text-primary' },
    { icon: CheckCircle2, label: 'مكتملة', value: props.stats.completed ?? 0, tone: 'bg-success/10 text-success' },
    { icon: XCircle, label: 'ملغاة', value: props.stats.cancelled ?? 0, tone: 'bg-destructive/10 text-destructive' },
    { icon: UserX, label: 'لم يحضر', value: props.stats.no_show ?? 0, tone: 'bg-warning/15 text-warning' },
    { icon: TrendingUp, label: 'نسبة الإلغاء', value: (props.stats.cancel_rate ?? 0) + '%', tone: 'bg-accent/10 text-accent' },
    { icon: CalendarDays, label: 'الأكثر طلباً', value: props.stats.top_type ?? '—', tone: 'bg-info/10 text-info', small: true },
]);

const isActive = (a) => a.status === 'pending_confirmation' || a.status === 'confirmed';

// Client-side column sorting (list is served unpaginated).
const { sorted, sortKey, sortDir, toggle } = useClientSort(() => props.appointments, 'time', 'desc', {
    number: 'appointment_number',
    type: (a) => a.type?.name_ar ?? '',
    customer: (a) => a.customer?.full_name ?? '',
    time: 'starts_at',
    duration: (a) => a.duration_minutes ?? 0,
    status: 'status',
});

/* actions */
const processing = ref(false);
function post(a, action, data = {}, done) {
    processing.value = true;
    router.post(`/appointments/${a.id}/${action}`, data, { preserveScroll: true, onFinish: () => { processing.value = false; if (done) done(); } });
}

/* reschedule dialog */
const reschedTarget = ref(null); const reschedSlot = ref(''); const reschedReason = ref('');
const GROUPS = [
    { key: 'morning', label: 'صباحاً' }, { key: 'afternoon', label: 'ظهراً' }, { key: 'evening', label: 'مساءً' },
];
const reschedSlots = computed(() => props.slotsByType[reschedTarget.value?.type_id] ?? { morning: [], afternoon: [], evening: [] });
const reschedHasSlots = computed(() => { const s = reschedSlots.value; return (s.morning.length + s.afternoon.length + s.evening.length) > 0; });
function openReschedule(a) { reschedTarget.value = a; reschedSlot.value = ''; reschedReason.value = ''; }
function submitReschedule() { if (!reschedSlot.value) return; post(reschedTarget.value, 'reschedule', { starts_at: reschedSlot.value, reason: reschedReason.value || null }, () => { reschedTarget.value = null; }); }
</script>

<template>
    <Head title="متابعة المواعيد" />
    <AppShell>
        <div class="space-y-4">
            <!-- Gradient hero -->
            <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-elevated" style="background-image: var(--gradient-hero);">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(closest-side at 75% 20%, rgba(255,255,255,.4), transparent);"></div>
                <div class="relative flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs text-white/60">إدارة · متابعة المواعيد</p>
                        <h1 class="mt-1 text-2xl font-bold">متابعة المواعيد</h1>
                        <p class="mt-1 max-w-xl text-sm text-white/80">إدارة وتأكيد ومتابعة جميع المواعيد المحجوزة في عرض واحد.</p>
                    </div>
                    <span class="rounded-full bg-white/15 px-3 py-1.5 text-xs tabular-nums backdrop-blur-sm">{{ stats.total ?? 0 }} موعد</span>
                </div>

                <!-- filter pills -->
                <div class="relative mt-5 flex flex-wrap gap-2">
                    <button v-for="t in TABS" :key="t.v" @click="setFilter(t.v)"
                        :data-active="filter === t.v"
                        class="flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-sm backdrop-blur-sm transition-all hover:bg-white/20 data-[active=true]:bg-white data-[active=true]:text-primary">
                        <component :is="t.icon" class="size-4 opacity-80" /> {{ t.l }}
                    </button>
                </div>
            </div>

            <!-- KPI ribbon -->
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
                <Card v-for="k in KPIS" :key="k.label" class="p-3">
                    <div class="flex items-center gap-3">
                        <div :class="['flex size-9 shrink-0 items-center justify-center rounded-lg', k.tone]"><component :is="k.icon" class="size-4" /></div>
                        <div class="min-w-0">
                            <div class="truncate text-[10px] uppercase text-muted-foreground">{{ k.label }}</div>
                            <div :class="['truncate font-bold', k.small ? 'text-sm' : 'text-lg', 'tabular-nums']">{{ k.value }}</div>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- List -->
            <Card class="overflow-hidden p-0">
                <!-- Sort bar -->
                <div v-if="appointments.length" class="overflow-x-auto border-b border-border bg-muted/50">
                    <table class="w-full">
                        <thead><tr>
                            <SortableTh col="number" :sort-key="sortKey" :sort-dir="sortDir" @sort="toggle">الرقم</SortableTh>
                            <SortableTh col="type" :sort-key="sortKey" :sort-dir="sortDir" @sort="toggle">النوع</SortableTh>
                            <SortableTh col="customer" :sort-key="sortKey" :sort-dir="sortDir" @sort="toggle">العميل</SortableTh>
                            <SortableTh col="time" :sort-key="sortKey" :sort-dir="sortDir" @sort="toggle" class="whitespace-nowrap">وقت الموعد</SortableTh>
                            <SortableTh col="duration" :sort-key="sortKey" :sort-dir="sortDir" @sort="toggle">المدة</SortableTh>
                            <SortableTh col="status" :sort-key="sortKey" :sort-dir="sortDir" @sort="toggle">الحالة</SortableTh>
                        </tr></thead>
                    </table>
                </div>
                <div v-if="appointments.length" class="divide-y divide-border">
                    <div v-for="a in sorted" :key="a.id" class="flex items-start gap-3 p-4 transition-colors hover:bg-muted/30">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-lg"
                            :style="{ background: (a.type?.color || 'var(--primary)') + '1a', color: a.type?.color || 'var(--primary)' }">
                            <component :is="modeIcon(a.type?.mode)" class="size-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <button class="text-sm font-semibold hover:text-primary" @click="router.visit(route('appointments.show', a.id))">{{ a.type?.name_ar ?? 'موعد' }}</button>
                                <span class="text-[10px] tabular-nums text-muted-foreground">{{ a.appointment_number }}</span>
                                <Badge :variant="statusLabel(APPOINTMENT_STATUS, a.status).tone" class="gap-1">
                                    <span :class="['size-1.5 rounded-full', STATUS_DOT[a.status]]"></span>
                                    {{ statusLabel(APPOINTMENT_STATUS, a.status).label }}
                                </Badge>
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                <span v-if="a.customer" class="flex items-center gap-1.5">
                                    <Avatar :name="a.customer.full_name" class="size-5 text-[9px]" /> {{ a.customer.full_name }}
                                </span>
                                <span class="flex items-center gap-1"><Clock class="size-3" /> {{ dayTime(a.starts_at) }} — {{ fmtTimeAr(a.starts_at) }}</span>
                                <span class="tabular-nums">{{ a.duration_minutes }} دقيقة</span>
                                <span v-if="a.related_request" class="tabular-nums">{{ a.related_request.request_number }}</span>
                            </div>
                            <p v-if="a.customer_notes" class="mt-1 line-clamp-2 text-xs text-muted-foreground">{{ a.customer_notes }}</p>
                        </div>
                        <div class="flex shrink-0 flex-col gap-1">
                            <Button v-if="a.status === 'pending_confirmation'" size="sm" variant="success" :disabled="processing" @click="post(a, 'confirm')"><CheckCircle2 class="size-3.5" /> تأكيد</Button>
                            <Button v-if="a.status === 'pending_confirmation'" size="sm" variant="outline" class="text-destructive" :disabled="processing" @click="post(a, 'reject')"><XCircle class="size-3.5" /> رفض</Button>
                            <Button v-if="isActive(a)" size="sm" variant="outline" @click="openReschedule(a)"><RotateCcw class="size-3.5" /> إعادة جدولة</Button>
                            <Button v-if="isActive(a)" size="sm" variant="ghost" class="text-destructive" :disabled="processing" @click="post(a, 'cancel')">إلغاء</Button>
                        </div>
                    </div>
                </div>
                <div v-else class="py-12 text-center text-sm text-muted-foreground">لا توجد مواعيد.</div>
            </Card>
        </div>

        <!-- Reschedule dialog -->
        <Dialog :open="!!reschedTarget" title="إعادة جدولة الموعد" description="اختر وقتاً جديداً من الأوقات المتاحة. سيُعتمد فوراً." class="max-w-lg" @update:open="v => !v && (reschedTarget = null)">
            <div v-if="reschedTarget" class="space-y-4">
                <div class="rounded-lg border border-border bg-muted/40 p-3 text-sm">
                    <div class="font-semibold">{{ reschedTarget.type?.name_ar ?? 'موعد' }} · {{ reschedTarget.appointment_number }}</div>
                    <div class="mt-1 flex items-center gap-1.5 text-xs text-muted-foreground"><CalendarClock class="size-3.5" /> {{ dayTime(reschedTarget.starts_at) }} — {{ fmtTimeAr(reschedTarget.starts_at) }}</div>
                </div>
                <div>
                    <Label class="mb-1.5 block text-xs">سبب إعادة الجدولة (اختياري)</Label>
                    <Textarea v-model="reschedReason" rows="2" placeholder="اذكر السبب…" />
                </div>
                <div>
                    <Label class="mb-1.5 block text-xs">الأوقات المتاحة</Label>
                    <div v-if="reschedHasSlots" class="max-h-56 space-y-3 overflow-y-auto">
                        <div v-for="g in GROUPS" :key="g.key" v-show="reschedSlots[g.key].length">
                            <p class="mb-1.5 text-[11px] font-bold text-muted-foreground">{{ g.label }}</p>
                            <div class="flex flex-wrap gap-1.5">
                                <button v-for="s in reschedSlots[g.key]" :key="s.starts_at" @click="reschedSlot = s.starts_at"
                                    :class="['rounded-lg border px-2.5 py-1.5 text-xs tabular-nums transition-colors',
                                        reschedSlot === s.starts_at ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-card hover:bg-muted']">
                                    <span class="block text-[10px] opacity-70">{{ shortDate(s.starts_at) }}</span>{{ fmtTimeAr(s.starts_at) }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <p v-else class="rounded-lg border border-dashed border-border py-6 text-center text-xs text-muted-foreground">لا توجد أوقات متاحة حالياً.</p>
                </div>
                <div class="flex justify-end gap-2">
                    <Button variant="ghost" @click="reschedTarget = null">إلغاء</Button>
                    <Button :disabled="!reschedSlot || processing" @click="submitReschedule">حفظ الموعد الجديد</Button>
                </div>
            </div>
        </Dialog>
    </AppShell>
</template>
