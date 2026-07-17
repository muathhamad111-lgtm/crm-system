<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import Label from '@/Components/ui/Label.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import Badge from '@/Components/ui/Badge.vue';
import { APPOINTMENT_STATUS, statusLabel } from '@/lib/labels';
import { fmtTimeAr } from '@/lib/date';
import {
    CalendarDays, Plus, MapPin, Video, Phone, GraduationCap, Workflow, HelpCircle,
    Clock, CalendarClock, CheckCircle2, Hourglass, Search, Hash, RotateCcw, Share2,
    CalendarPlus, Link2, Copy, Mail, Download, ListChecks, FileText,
} from 'lucide-vue-next';

const props = defineProps({
    upcoming: { type: Array, default: () => [] },
    past: { type: Array, default: () => [] },
    slotsByType: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
});

const MODE_META = {
    phone: { icon: Phone, label: 'مكالمة هاتفية' },
    video: { icon: Video, label: 'اجتماع مرئي' },
    onsite: { icon: MapPin, label: 'حضوري' },
    training: { icon: GraduationCap, label: 'جلسة تدريبية' },
    followup: { icon: Workflow, label: 'متابعة' },
    other: { icon: HelpCircle, label: 'أخرى' },
};
const modeMeta = (m) => MODE_META[m] ?? MODE_META.other;

// status dot tones (parity with Lovable STATUS_META).
const STATUS_DOT = {
    pending_confirmation: 'bg-warning', confirmed: 'bg-success', completed: 'bg-primary',
    cancelled: 'bg-destructive', no_show: 'bg-muted-foreground', rescheduled: 'bg-info',
};

/* ---------- date helpers ---------- */
const AR = 'ar-SA-u-ca-gregory';
const TZ = 'Asia/Riyadh';
const fmt = (v, opts) => (v ? new Intl.DateTimeFormat(AR, { timeZone: TZ, ...opts }).format(new Date(v)) : '—');
const dayNum = (v) => fmt(v, { day: 'numeric' });
const monthShort = (v) => fmt(v, { month: 'short' });
const dayMonth = (v) => fmt(v, { weekday: 'long', day: 'numeric', month: 'long' });

function relativeWhen(iso) {
    const d = new Date(iso).getTime();
    const now = Date.now();
    if (d < now) return { label: 'انتهى', tone: 'past' };
    const hours = (d - now) / 3.6e6;
    const days = hours / 24;
    if (hours < 24) return { label: hours <= 1 ? 'خلال ساعة' : `خلال ${Math.round(hours)} ساعة`, tone: 'today' };
    if (days <= 3) return { label: `بعد ${Math.round(days)} يوم`, tone: 'soon' };
    return { label: dayMonth(iso), tone: 'future' };
}
const nextLabel = computed(() => (props.stats.next_at ? relativeWhen(props.stats.next_at).label : '—'));

/* ---------- filtering ---------- */
const allItems = computed(() => [...props.upcoming, ...props.past]);
const tab = ref('upcoming');
const search = ref('');
const TABS = [
    { v: 'upcoming', l: 'القادمة', icon: CalendarClock },
    { v: 'confirmed', l: 'المؤكدة', icon: CheckCircle2 },
    { v: 'pending', l: 'بانتظار التأكيد', icon: Hourglass },
    { v: 'past', l: 'السجل', icon: ListChecks },
    { v: 'all', l: 'الكل', icon: CalendarDays },
];
const upcomingIds = computed(() => new Set(props.upcoming.map((a) => a.id)));

const filtered = computed(() => {
    let list = allItems.value.slice();
    if (tab.value === 'upcoming') list = props.upcoming.slice();
    else if (tab.value === 'confirmed') list = props.upcoming.filter((a) => a.status === 'confirmed');
    else if (tab.value === 'pending') list = props.upcoming.filter((a) => a.status === 'pending_confirmation');
    else if (tab.value === 'past') list = props.past.slice();
    const q = search.value.trim().toLowerCase();
    if (q) {
        list = list.filter((a) => (a.appointment_number || '').toLowerCase().includes(q)
            || (a.type?.name_ar || '').toLowerCase().includes(q)
            || (a.related_request?.request_number || '').toLowerCase().includes(q));
    }
    return list.sort((a, b) => new Date(a.starts_at) - new Date(b.starts_at));
});

const isActive = (a) => a.status === 'pending_confirmation' || a.status === 'confirmed';

/* ---------- calendar export helpers (client-side) ---------- */
const pad = (n) => String(n).padStart(2, '0');
function toICS(iso) {
    const d = new Date(iso);
    return `${d.getUTCFullYear()}${pad(d.getUTCMonth() + 1)}${pad(d.getUTCDate())}T${pad(d.getUTCHours())}${pad(d.getUTCMinutes())}${pad(d.getUTCSeconds())}Z`;
}
function apptTitle(a) { return `${a.type?.name_ar ?? 'موعد'} — ${a.appointment_number}`; }
function apptDetails(a) { return a.customer_notes || a.type?.name_ar || ''; }
function downloadICS(a) {
    const lines = [
        'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//CRM//Appointments//AR', 'BEGIN:VEVENT',
        `UID:${a.id}@crm`, `DTSTAMP:${toICS(new Date().toISOString())}`,
        `DTSTART:${toICS(a.starts_at)}`, `DTEND:${toICS(a.ends_at || a.starts_at)}`,
        `SUMMARY:${apptTitle(a)}`, `DESCRIPTION:${apptDetails(a)}`,
        a.location ? `LOCATION:${a.location}` : '', a.meeting_url ? `URL:${a.meeting_url}` : '',
        'END:VEVENT', 'END:VCALENDAR',
    ].filter(Boolean);
    const blob = new Blob([lines.join('\r\n')], { type: 'text/calendar;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `${a.appointment_number || 'appointment'}.ics`;
    link.click();
    URL.revokeObjectURL(url);
}
function googleUrl(a) {
    const p = new URLSearchParams({
        action: 'TEMPLATE', text: apptTitle(a),
        dates: `${toICS(a.starts_at)}/${toICS(a.ends_at || a.starts_at)}`,
        details: apptDetails(a), location: a.location || '',
    });
    return `https://calendar.google.com/calendar/render?${p.toString()}`;
}
function outlookUrl(a) {
    const p = new URLSearchParams({
        path: '/calendar/action/compose', rru: 'addevent', subject: apptTitle(a),
        startdt: a.starts_at, enddt: a.ends_at || a.starts_at,
        body: apptDetails(a), location: a.location || '',
    });
    return `https://outlook.live.com/calendar/0/deeplink/compose?${p.toString()}`;
}
function shareUrl(a) { return `${window.location.origin}/appointments/${a.id}`; }
function shareText(a) {
    return [apptTitle(a), `🗓️ ${dayMonth(a.starts_at)} — ${fmtTimeAr(a.starts_at)}`,
        a.meeting_url ? `🔗 ${a.meeting_url}` : null, a.location ? `📍 ${a.location}` : null,
        shareUrl(a)].filter(Boolean).join('\n');
}
async function copy(text, msg) {
    try { await navigator.clipboard.writeText(text); flash(msg); }
    catch { flash('تعذّر النسخ'); }
}

/* ---------- flash toast (lightweight) ---------- */
const toast = ref('');
let toastT;
function flash(m) { toast.value = m; clearTimeout(toastT); toastT = setTimeout(() => (toast.value = ''), 2200); }

/* ---------- dialogs ---------- */
const cancelTarget = ref(null);
const cancelReason = ref('');
const shareTarget = ref(null);
const calTarget = ref(null);
const reschedTarget = ref(null);
const reschedReason = ref('');
const reschedSlot = ref('');
const processing = ref(false);

const GROUPS = [
    { key: 'morning', label: 'صباحاً', icon: CalendarClock },
    { key: 'afternoon', label: 'ظهراً', icon: Clock },
    { key: 'evening', label: 'مساءً', icon: CalendarDays },
];
const reschedSlots = computed(() => props.slotsByType[reschedTarget.value?.type_id] ?? { morning: [], afternoon: [], evening: [] });
const reschedHasSlots = computed(() => {
    const s = reschedSlots.value;
    return (s.morning.length + s.afternoon.length + s.evening.length) > 0;
});

function openReschedule(a) { reschedTarget.value = a; reschedSlot.value = ''; reschedReason.value = ''; }
function submitReschedule() {
    if (!reschedTarget.value || !reschedSlot.value) return;
    processing.value = true;
    router.post(`/appointments/${reschedTarget.value.id}/reschedule`,
        { starts_at: reschedSlot.value, reason: reschedReason.value || null },
        { preserveScroll: true, onFinish: () => { processing.value = false; reschedTarget.value = null; } });
}
function submitCancel() {
    if (!cancelTarget.value) return;
    processing.value = true;
    router.post(`/appointments/${cancelTarget.value.id}/cancel`,
        { reason: cancelReason.value || null },
        { preserveScroll: true, onFinish: () => { processing.value = false; cancelTarget.value = null; cancelReason.value = ''; } });
}
</script>

<template>
    <Head title="مواعيدي" />
    <AppShell>
        <div class="space-y-4">
            <!-- Gradient hero -->
            <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-elevated" style="background-image: var(--gradient-hero);">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(closest-side at 75% 20%, rgba(255,255,255,.4), transparent);"></div>
                <div class="relative flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs text-white/60">بوابة العميل · مواعيدك</p>
                        <h1 class="mt-1 text-2xl font-bold">مواعيدي</h1>
                        <p class="mt-1 max-w-xl text-sm text-white/80">إدارة كاملة لمواعيدك مع الفريق: اعرض، أعد الجدولة، ألغِ، وزامنها مع تقويمك المفضّل.</p>
                    </div>
                    <Button :href="route('appointments.create')" class="bg-white/15 text-white hover:bg-white/25 backdrop-blur-sm">
                        <Plus class="size-4" /> حجز موعد جديد
                    </Button>
                </div>

                <!-- Hero stats -->
                <div class="relative mt-5 grid grid-cols-2 gap-2.5 sm:grid-cols-4 max-w-3xl">
                    <div v-for="s in [
                        { icon: CalendarClock, label: 'قادمة', value: stats.upcoming ?? 0 },
                        { icon: CheckCircle2, label: 'مؤكدة', value: stats.confirmed ?? 0 },
                        { icon: Hourglass, label: 'بانتظار التأكيد', value: stats.pending ?? 0 },
                        { icon: Clock, label: 'أقرب موعد', value: nextLabel },
                    ]" :key="s.label" class="flex items-center gap-2.5 rounded-xl border border-white/15 bg-white/10 px-3 py-2.5 backdrop-blur-sm">
                        <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-white/15">
                            <component :is="s.icon" class="size-4" />
                        </div>
                        <div class="min-w-0">
                            <div class="truncate text-[10px] font-bold uppercase tracking-wider text-white/70">{{ s.label }}</div>
                            <div class="truncate text-sm font-bold tabular-nums">{{ s.value }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="flex flex-col gap-2 lg:flex-row lg:items-center">
                <div class="relative min-w-0 flex-1">
                    <Search class="pointer-events-none absolute left-3 top-1/2 z-10 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" label="ابحث برقم الموعد أو نوعه أو رقم الطلب…" />
                </div>
                <div class="flex flex-wrap gap-1 rounded-lg border border-border bg-card p-0.5">
                    <button v-for="t in TABS" :key="t.v" @click="tab = t.v"
                        :class="['flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-semibold transition-colors',
                            tab === t.v ? 'bg-primary text-primary-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground']">
                        <component :is="t.icon" class="size-3.5" /> {{ t.l }}
                    </button>
                </div>
            </div>

            <!-- Cards -->
            <div v-if="filtered.length" class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <div v-for="a in filtered" :key="a.id"
                    class="group relative flex overflow-hidden rounded-2xl border border-border bg-card shadow-sm transition-all hover:border-primary/30 hover:shadow-elevated">
                    <!-- date strip -->
                    <button @click="router.visit(route('appointments.show', a.id))"
                        class="flex w-20 shrink-0 flex-col items-center justify-center text-white sm:w-24"
                        :style="{ background: a.type?.color || 'var(--primary)' }">
                        <span class="mb-0.5 text-[10px] font-light opacity-80">{{ monthShort(a.starts_at) }}</span>
                        <span class="mb-1 text-3xl font-bold leading-none tracking-tighter tabular-nums">{{ dayNum(a.starts_at) }}</span>
                        <span class="rounded-full bg-white/20 px-2 py-0.5 text-[10px] font-medium tabular-nums">{{ fmtTimeAr(a.starts_at) }}</span>
                    </button>

                    <!-- body -->
                    <div class="flex min-w-0 flex-1 flex-col p-3 sm:p-4">
                        <div class="mb-3 flex items-start justify-between gap-2">
                            <div class="flex min-w-0 items-center gap-2">
                                <div class="flex size-8 shrink-0 items-center justify-center rounded-lg"
                                    :style="{ background: (a.type?.color || 'var(--primary)') + '1f', color: a.type?.color || 'var(--primary)' }">
                                    <component :is="modeMeta(a.type?.mode).icon" class="size-4" />
                                </div>
                                <div class="min-w-0">
                                    <button @click="router.visit(route('appointments.show', a.id))"
                                        class="block truncate text-sm font-bold text-foreground transition-colors hover:text-primary">
                                        {{ a.type?.name_ar ?? 'موعد' }}
                                    </button>
                                    <span class="mt-0.5 flex items-center gap-1 text-[10px] tabular-nums text-muted-foreground">
                                        <Hash class="size-2.5" />{{ a.appointment_number }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-1">
                                <Badge :variant="statusLabel(APPOINTMENT_STATUS, a.status).tone" class="gap-1">
                                    <span :class="['size-1.5 rounded-full', STATUS_DOT[a.status], a.status === 'pending_confirmation' && 'animate-pulse']"></span>
                                    {{ statusLabel(APPOINTMENT_STATUS, a.status).label }}
                                </Badge>
                                <span class="text-[10px] font-medium text-muted-foreground">{{ relativeWhen(a.starts_at).label }}</span>
                            </div>
                        </div>

                        <!-- details -->
                        <div class="mb-3 flex flex-wrap gap-x-5 gap-y-2">
                            <div class="flex flex-col">
                                <span class="mb-0.5 text-[9px] uppercase tracking-wide text-muted-foreground">اليوم</span>
                                <span class="text-[11px] font-semibold text-foreground">{{ dayMonth(a.starts_at) }}</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="mb-0.5 text-[9px] uppercase tracking-wide text-muted-foreground">المدة</span>
                                <span class="text-[11px] font-semibold text-foreground tabular-nums">{{ a.duration_minutes }} دقيقة</span>
                            </div>
                            <div v-if="a.related_request" class="flex flex-col">
                                <span class="mb-0.5 text-[9px] uppercase tracking-wide text-muted-foreground">الطلب المرتبط</span>
                                <span class="inline-flex w-fit items-center gap-1 rounded-md border border-border bg-muted px-1.5 py-0.5 text-[10px] font-medium tabular-nums text-muted-foreground">
                                    <FileText class="size-2.5" />{{ a.related_request.request_number }}
                                </span>
                            </div>
                            <div v-if="a.location" class="flex flex-col">
                                <span class="mb-0.5 text-[9px] uppercase tracking-wide text-muted-foreground">الموقع</span>
                                <a :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(a.location)}`" target="_blank" rel="noreferrer"
                                    class="inline-flex items-center gap-1 text-[10px] font-semibold text-warning hover:underline">
                                    <MapPin class="size-3 shrink-0" /><span class="max-w-[180px] truncate">{{ a.location }}</span>
                                </a>
                            </div>
                            <div v-if="a.type?.mode === 'video' && a.meeting_url" class="flex flex-col">
                                <span class="mb-0.5 text-[9px] uppercase tracking-wide text-muted-foreground">الاجتماع المرئي</span>
                                <a :href="a.meeting_url" target="_blank" rel="noreferrer" class="inline-flex items-center gap-1 text-[10px] font-semibold text-info hover:underline">
                                    <Link2 class="size-3" /> انضمام للاجتماع
                                </a>
                            </div>
                        </div>

                        <!-- actions -->
                        <div class="mt-auto flex items-center justify-between gap-2 border-t border-border pt-2.5">
                            <div v-if="isActive(a)" class="flex gap-1.5">
                                <Button size="sm" @click="openReschedule(a)"><RotateCcw class="size-3" /> إعادة جدولة</Button>
                                <Button size="icon-sm" variant="outline" title="مشاركة" @click="shareTarget = a"><Share2 class="size-3.5" /></Button>
                                <Button size="icon-sm" variant="outline" title="إضافة إلى التقويم" @click="calTarget = a"><CalendarPlus class="size-3.5" /></Button>
                            </div>
                            <span v-else></span>
                            <button v-if="isActive(a)" @click="cancelTarget = a"
                                class="px-2 py-1.5 text-[11px] font-medium text-muted-foreground transition-colors hover:text-destructive">
                                إلغاء
                            </button>
                            <Button v-else :href="route('appointments.show', a.id)" variant="ghost" size="sm">التفاصيل</Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty -->
            <Card v-else>
                <CardContent class="py-16 text-center">
                    <div class="mx-auto mb-3 flex size-14 items-center justify-center rounded-2xl bg-primary-soft text-primary">
                        <CalendarDays class="size-7" />
                    </div>
                    <p class="mb-4 text-sm text-muted-foreground">
                        {{ allItems.length ? 'لا توجد مواعيد ضمن هذا التصنيف' : 'لا توجد مواعيد محجوزة بعد' }}
                    </p>
                    <Button :href="route('appointments.create')"><Plus class="size-4" /> احجز موعدك الأول</Button>
                </CardContent>
            </Card>
        </div>

        <!-- Reschedule dialog -->
        <Dialog :open="!!reschedTarget" title="إعادة جدولة الموعد" description="اختر وقتاً جديداً من الأوقات المتاحة. سيُرسل الطلب لفريق العمل للتأكيد." class="max-w-lg" @update:open="v => !v && (reschedTarget = null)">
            <div v-if="reschedTarget" class="space-y-4">
                <div class="space-y-1.5 rounded-lg border border-border bg-muted/40 p-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="truncate text-sm font-semibold">{{ reschedTarget.type?.name_ar ?? 'موعد' }}</span>
                        <span class="shrink-0 text-[11px] tabular-nums text-muted-foreground">{{ reschedTarget.appointment_number }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <CalendarDays class="size-3.5" /> {{ dayMonth(reschedTarget.starts_at) }} — {{ fmtTimeAr(reschedTarget.starts_at) }}
                    </div>
                </div>
                <div>
                    <Textarea label="سبب إعادة الجدولة (اختياري)" v-model="reschedReason" rows="2" />
                </div>
                <div>
                    <Label class="mb-1.5 block text-xs">الأوقات المتاحة</Label>
                    <div v-if="reschedHasSlots" class="max-h-56 space-y-3 overflow-y-auto">
                        <div v-for="g in GROUPS" :key="g.key" v-show="reschedSlots[g.key].length">
                            <p class="mb-1.5 flex items-center gap-1.5 text-[11px] font-bold text-muted-foreground">
                                <component :is="g.icon" class="size-3.5" /> {{ g.label }}
                            </p>
                            <div class="flex flex-wrap gap-1.5">
                                <button v-for="s in reschedSlots[g.key]" :key="s.starts_at" @click="reschedSlot = s.starts_at"
                                    :class="['rounded-lg border px-2.5 py-1.5 text-xs tabular-nums transition-colors',
                                        reschedSlot === s.starts_at ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-card hover:bg-muted']">
                                    <span class="block text-[10px] opacity-70">{{ monthShort(s.starts_at) }} {{ dayNum(s.starts_at) }}</span>
                                    {{ fmtTimeAr(s.starts_at) }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <p v-else class="rounded-lg border border-dashed border-border py-6 text-center text-xs text-muted-foreground">لا توجد أوقات متاحة حالياً.</p>
                </div>
                <div class="flex items-start gap-2 rounded-lg border border-warning/30 bg-warning/10 p-3 text-xs text-warning">
                    <Hourglass class="size-4 shrink-0" /> الموعد الجديد بحاجة إلى تأكيد من فريق العمل قبل اعتماده.
                </div>
                <div class="flex justify-end gap-2">
                    <Button variant="ghost" @click="reschedTarget = null">إلغاء</Button>
                    <Button :disabled="!reschedSlot || processing" @click="submitReschedule">إرسال الطلب</Button>
                </div>
            </div>
        </Dialog>

        <!-- Cancel dialog -->
        <Dialog :open="!!cancelTarget" title="إلغاء الموعد" class="max-w-md" @update:open="v => !v && (cancelTarget = null)">
            <div v-if="cancelTarget" class="space-y-3">
                <p class="text-sm text-muted-foreground">
                    سيتم إلغاء الموعد <span class="font-bold tabular-nums">{{ cancelTarget.appointment_number }}</span>. يُبلَّغ الفريق بذلك فوراً.
                </p>
                <div>
                    <Textarea label="سبب الإلغاء (اختياري)" v-model="cancelReason" rows="2" />
                </div>
                <div class="flex justify-end gap-2">
                    <Button variant="ghost" @click="cancelTarget = null">تراجع</Button>
                    <Button variant="destructive" :disabled="processing" @click="submitCancel">تأكيد الإلغاء</Button>
                </div>
            </div>
        </Dialog>

        <!-- Share dialog -->
        <Dialog :open="!!shareTarget" title="مشاركة الموعد" description="انسخ رابط الموعد أو شارك تفاصيله." class="max-w-md" @update:open="v => !v && (shareTarget = null)">
            <div v-if="shareTarget" class="space-y-4">
                <div class="space-y-1.5 rounded-lg border border-border bg-muted/40 p-3">
                    <div class="truncate text-sm font-semibold">{{ shareTarget.type?.name_ar ?? 'موعد' }}</div>
                    <div class="flex items-center gap-1.5 text-xs text-muted-foreground"><CalendarDays class="size-3.5" /> {{ dayMonth(shareTarget.starts_at) }} — {{ fmtTimeAr(shareTarget.starts_at) }}</div>
                </div>
                <div>
                    <Label class="mb-1.5 block text-xs">رابط الموعد</Label>
                    <div class="flex items-center gap-2">
                        <Input :model-value="shareUrl(shareTarget)" readonly dir="ltr" class="text-xs" />
                        <Button size="icon" variant="outline" @click="copy(shareUrl(shareTarget), 'تم نسخ الرابط')"><Copy class="size-4" /></Button>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <a :href="`https://wa.me/?text=${encodeURIComponent(shareText(shareTarget))}`" target="_blank" rel="noreferrer"
                        class="flex flex-col items-center gap-1.5 rounded-lg border border-border bg-card py-3 text-xs font-medium hover:bg-muted">
                        <Phone class="size-4 text-primary" /> واتساب
                    </a>
                    <a :href="`mailto:?subject=${encodeURIComponent(apptTitle(shareTarget))}&body=${encodeURIComponent(shareText(shareTarget))}`"
                        class="flex flex-col items-center gap-1.5 rounded-lg border border-border bg-card py-3 text-xs font-medium hover:bg-muted">
                        <Mail class="size-4 text-primary" /> بريد
                    </a>
                    <button @click="copy(shareText(shareTarget), 'تم نسخ التفاصيل')"
                        class="flex flex-col items-center gap-1.5 rounded-lg border border-border bg-card py-3 text-xs font-medium hover:bg-muted">
                        <Copy class="size-4 text-primary" /> نسخ
                    </button>
                </div>
                <div class="flex justify-end"><Button variant="ghost" @click="shareTarget = null">إغلاق</Button></div>
            </div>
        </Dialog>

        <!-- Add to calendar dialog -->
        <Dialog :open="!!calTarget" title="إضافة إلى التقويم" description="اختر التقويم المفضّل لإضافة هذا الموعد." class="max-w-md" @update:open="v => !v && (calTarget = null)">
            <div v-if="calTarget" class="space-y-4">
                <div class="space-y-1.5 rounded-lg border border-border bg-muted/40 p-3">
                    <div class="truncate text-sm font-semibold">{{ calTarget.type?.name_ar ?? 'موعد' }}</div>
                    <div class="flex items-center gap-1.5 text-xs text-muted-foreground"><Clock class="size-3.5" /> {{ dayMonth(calTarget.starts_at) }} — {{ fmtTimeAr(calTarget.starts_at) }}</div>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <a :href="googleUrl(calTarget)" target="_blank" rel="noreferrer"
                        class="flex flex-col items-center gap-2 rounded-lg border border-border bg-card py-4 text-xs font-medium hover:bg-muted">
                        <CalendarPlus class="size-5 text-primary" /> Google
                    </a>
                    <a :href="outlookUrl(calTarget)" target="_blank" rel="noreferrer"
                        class="flex flex-col items-center gap-2 rounded-lg border border-border bg-card py-4 text-xs font-medium hover:bg-muted">
                        <CalendarPlus class="size-5 text-primary" /> Outlook
                    </a>
                    <button @click="downloadICS(calTarget)"
                        class="flex flex-col items-center gap-2 rounded-lg border border-border bg-card py-4 text-xs font-medium hover:bg-muted">
                        <Download class="size-5 text-primary" /> ICS / Apple
                    </button>
                </div>
                <div class="flex justify-end"><Button variant="ghost" @click="calTarget = null">إغلاق</Button></div>
            </div>
        </Dialog>

        <!-- toast -->
        <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0 translate-y-2" leave-active-class="transition duration-150" leave-to-class="opacity-0">
            <div v-if="toast" class="fixed bottom-6 left-1/2 z-[60] -translate-x-1/2 rounded-lg bg-foreground px-4 py-2 text-sm font-medium text-background shadow-elevated">
                {{ toast }}
            </div>
        </Transition>
    </AppShell>
</template>
