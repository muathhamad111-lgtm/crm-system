<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import Label from '@/Components/ui/Label.vue';
import Badge from '@/Components/ui/Badge.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import { fmtFullDateTimeAr, fmtTimeAr } from '@/lib/date';
import { APPOINTMENT_STATUS, statusLabel } from '@/lib/labels';
import {
    ArrowRight, Clock, MapPin, Video, Phone, GraduationCap, Workflow, HelpCircle, CalendarDays,
    Hash, Info, FileText, User, RotateCcw, CheckCircle2, XCircle, CalendarClock, History,
    Share2, CalendarPlus, Copy, Mail, Download, MessageSquare,
} from 'lucide-vue-next';

const props = defineProps({
    appointment: { type: Object, required: true },
    activity: { type: Array, default: () => [] },
    slotsByType: { type: Object, default: () => ({}) },
    can: { type: Object, default: () => ({}) },
});

const a = computed(() => props.appointment);
const MODE_ICON = { phone: Phone, video: Video, onsite: MapPin, training: GraduationCap, followup: Workflow, other: HelpCircle };
const modeIcon = (m) => MODE_ICON[m] ?? HelpCircle;
const STATUS_DOT = {
    pending_confirmation: 'bg-warning', confirmed: 'bg-success', completed: 'bg-primary',
    cancelled: 'bg-destructive', no_show: 'bg-muted-foreground', rescheduled: 'bg-info',
};

const ACTION_META = {
    created: { label: 'أنشئ الموعد', icon: CalendarDays, cls: 'text-primary' },
    confirmed: { label: 'تأكيد الموعد', icon: CheckCircle2, cls: 'text-success' },
    reschedule_requested: { label: 'طلب إعادة جدولة', icon: CalendarClock, cls: 'text-warning' },
    rescheduled: { label: 'أعيدت الجدولة', icon: CalendarClock, cls: 'text-info' },
    cancelled: { label: 'إلغاء الموعد', icon: XCircle, cls: 'text-destructive' },
    rejected: { label: 'رفض الموعد', icon: XCircle, cls: 'text-destructive' },
    completed: { label: 'اكتمال الموعد', icon: CheckCircle2, cls: 'text-primary' },
    no_show: { label: 'عدم حضور', icon: XCircle, cls: 'text-muted-foreground' },
};
const actionMeta = (act) => ACTION_META[act] ?? { label: act, icon: History, cls: 'text-muted-foreground' };

const AR = 'ar-SA-u-ca-gregory-nu-latn';
const TZ = 'Asia/Riyadh';
const fmt = (v, opts) => (v ? new Intl.DateTimeFormat(AR, { timeZone: TZ, ...opts }).format(new Date(v)) : '—');
const dayMonth = (v) => fmt(v, { weekday: 'long', day: 'numeric', month: 'long' });
const shortDate = (v) => fmt(v, { day: 'numeric', month: 'short' });
const monthShort = (v) => fmt(v, { month: 'short' });
const dayNum = (v) => fmt(v, { day: 'numeric' });

/* calendar export */
const pad = (n) => String(n).padStart(2, '0');
const toICS = (iso) => { const d = new Date(iso); return `${d.getUTCFullYear()}${pad(d.getUTCMonth() + 1)}${pad(d.getUTCDate())}T${pad(d.getUTCHours())}${pad(d.getUTCMinutes())}${pad(d.getUTCSeconds())}Z`; };
const title = () => `${a.value.type?.name_ar ?? 'موعد'} — ${a.value.appointment_number}`;
const details = () => a.value.customer_notes || a.value.type?.name_ar || '';
function downloadICS() {
    const x = a.value;
    const lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//CRM//AR', 'BEGIN:VEVENT', `UID:${x.id}@crm`,
        `DTSTAMP:${toICS(new Date().toISOString())}`, `DTSTART:${toICS(x.starts_at)}`, `DTEND:${toICS(x.ends_at || x.starts_at)}`,
        `SUMMARY:${title()}`, `DESCRIPTION:${details()}`, x.location ? `LOCATION:${x.location}` : '', x.meeting_url ? `URL:${x.meeting_url}` : '',
        'END:VEVENT', 'END:VCALENDAR'].filter(Boolean);
    const blob = new Blob([lines.join('\r\n')], { type: 'text/calendar;charset=utf-8' });
    const url = URL.createObjectURL(blob); const link = document.createElement('a');
    link.href = url; link.download = `${a.value.appointment_number || 'appointment'}.ics`; link.click(); URL.revokeObjectURL(url);
}
function googleUrl() { const x = a.value; const p = new URLSearchParams({ action: 'TEMPLATE', text: title(), dates: `${toICS(x.starts_at)}/${toICS(x.ends_at || x.starts_at)}`, details: details(), location: x.location || '' }); return `https://calendar.google.com/calendar/render?${p}`; }
function outlookUrl() { const x = a.value; const p = new URLSearchParams({ path: '/calendar/action/compose', rru: 'addevent', subject: title(), startdt: x.starts_at, enddt: x.ends_at || x.starts_at, body: details(), location: x.location || '' }); return `https://outlook.live.com/calendar/0/deeplink/compose?${p}`; }
const appUrl = () => `${window.location.origin}/appointments/${a.value.id}`;
const shareText = () => [title(), `🗓️ ${dayMonth(a.value.starts_at)} — ${fmtTimeAr(a.value.starts_at)}`, a.value.meeting_url ? `🔗 ${a.value.meeting_url}` : null, a.value.location ? `📍 ${a.value.location}` : null, appUrl()].filter(Boolean).join('\n');

const toast = ref(''); let toastT;
function flash(m) { toast.value = m; clearTimeout(toastT); toastT = setTimeout(() => (toast.value = ''), 2200); }
async function copy(t, m) { try { await navigator.clipboard.writeText(t); flash(m); } catch { flash('تعذّر النسخ'); } }

/* dialogs + actions */
const cancelOpen = ref(false); const cancelReason = ref('');
const reschedOpen = ref(false); const reschedReason = ref(''); const reschedSlot = ref('');
const shareOpen = ref(false); const calOpen = ref(false);
const processing = ref(false);

const GROUPS = [
    { key: 'morning', label: 'صباحاً', icon: Clock },
    { key: 'afternoon', label: 'ظهراً', icon: Clock },
    { key: 'evening', label: 'مساءً', icon: Clock },
];
const slots = computed(() => props.slotsByType[a.value.type_id] ?? { morning: [], afternoon: [], evening: [] });
const hasSlots = computed(() => (slots.value.morning.length + slots.value.afternoon.length + slots.value.evening.length) > 0);

function post(action, data = {}, done) {
    processing.value = true;
    router.post(`/appointments/${a.value.id}/${action}`, data, {
        preserveScroll: true,
        onFinish: () => { processing.value = false; if (done) done(); },
    });
}
function submitCancel() { post('cancel', { reason: cancelReason.value || null }, () => { cancelOpen.value = false; cancelReason.value = ''; }); }
function submitReschedule() { if (!reschedSlot.value) return; post('reschedule', { starts_at: reschedSlot.value, reason: reschedReason.value || null }, () => { reschedOpen.value = false; reschedSlot.value = ''; reschedReason.value = ''; }); }
</script>

<template>
    <Head title="تفاصيل الموعد" />
    <AppShell>
        <div class="mx-auto max-w-5xl space-y-4">
            <Button :href="route('appointments.index')" variant="ghost" size="sm"><ArrowRight class="size-4" /> العودة لمواعيدي</Button>

            <!-- Header -->
            <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-elevated" style="background-image: var(--gradient-hero);">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(closest-side at 75% 20%, rgba(255,255,255,.4), transparent);"></div>
                <div class="relative flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs tabular-nums text-white/70">{{ a.appointment_number }}</p>
                        <h1 class="mt-1 text-2xl font-bold">{{ a.type?.name_ar ?? 'موعد' }}</h1>
                        <p class="mt-1 flex items-center gap-2 text-sm text-white/80"><Clock class="size-4" /> {{ fmtFullDateTimeAr(a.starts_at) }}</p>
                    </div>
                    <Badge :variant="statusLabel(APPOINTMENT_STATUS, a.status).tone" class="gap-1 bg-white/15 text-white">
                        <span :class="['size-1.5 rounded-full', STATUS_DOT[a.status]]"></span>
                        {{ statusLabel(APPOINTMENT_STATUS, a.status).label }}
                    </Badge>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <!-- Main info -->
                <Card class="lg:col-span-2">
                    <CardContent class="space-y-4 p-6">
                        <div class="flex items-center gap-3">
                            <div class="flex size-12 items-center justify-center rounded-xl"
                                :style="{ background: (a.type?.color || 'var(--primary)') + '1a', color: a.type?.color || 'var(--primary)' }">
                                <component :is="modeIcon(a.type?.mode)" class="size-6" />
                            </div>
                            <div>
                                <div class="text-xs text-muted-foreground">التاريخ والوقت</div>
                                <div class="font-semibold text-foreground">{{ dayMonth(a.starts_at) }} — {{ fmtTimeAr(a.starts_at) }}</div>
                                <div class="mt-0.5 text-xs tabular-nums text-muted-foreground">المدة: {{ a.duration_minutes }} دقيقة</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-2 border-t border-border pt-3 sm:grid-cols-2">
                            <div class="flex items-start gap-2 rounded-md bg-muted/30 p-2">
                                <Hash class="mt-0.5 size-3.5 shrink-0 text-muted-foreground" />
                                <div class="min-w-0"><div class="text-[10px] uppercase tracking-wider text-muted-foreground">رقم الموعد</div><div class="truncate text-xs font-medium tabular-nums">{{ a.appointment_number }}</div></div>
                            </div>
                            <div v-if="a.created_at" class="flex items-start gap-2 rounded-md bg-muted/30 p-2">
                                <CalendarDays class="mt-0.5 size-3.5 shrink-0 text-muted-foreground" />
                                <div class="min-w-0"><div class="text-[10px] uppercase tracking-wider text-muted-foreground">تاريخ الإنشاء</div><div class="truncate text-xs font-medium">{{ shortDate(a.created_at) }}</div></div>
                            </div>
                            <div v-if="a.reason_label" class="flex items-start gap-2 rounded-md bg-muted/30 p-2">
                                <Info class="mt-0.5 size-3.5 shrink-0 text-muted-foreground" />
                                <div class="min-w-0"><div class="text-[10px] uppercase tracking-wider text-muted-foreground">سبب الحجز</div><div class="truncate text-xs font-medium">{{ a.reason_code === 'other' && a.reason_other ? a.reason_label + ': ' + a.reason_other : a.reason_label }}</div></div>
                            </div>
                            <div v-if="a.customer" class="flex items-start gap-2 rounded-md bg-muted/30 p-2">
                                <User class="mt-0.5 size-3.5 shrink-0 text-muted-foreground" />
                                <div class="min-w-0"><div class="text-[10px] uppercase tracking-wider text-muted-foreground">العميل</div><div class="truncate text-xs font-medium">{{ a.customer.full_name }}</div></div>
                            </div>
                            <div v-if="a.reschedule_count" class="flex items-start gap-2 rounded-md bg-muted/30 p-2">
                                <RotateCcw class="mt-0.5 size-3.5 shrink-0 text-muted-foreground" />
                                <div class="min-w-0"><div class="text-[10px] uppercase tracking-wider text-muted-foreground">مرات إعادة الجدولة</div><div class="text-xs font-medium tabular-nums">{{ a.reschedule_count }}</div></div>
                            </div>
                        </div>

                        <div v-if="a.related_request" class="flex items-center gap-3 rounded-lg bg-muted/30 p-3">
                            <FileText class="size-4 text-primary" />
                            <Link :href="route('requests.show', a.related_request_id)" class="text-sm font-medium hover:underline">{{ a.related_request.request_number }} — {{ a.related_request.title }}</Link>
                        </div>
                        <a v-if="a.meeting_url" :href="a.meeting_url" target="_blank" rel="noreferrer" class="flex items-center gap-3 rounded-lg border border-info/20 bg-info/5 p-3 text-sm font-medium text-info hover:underline">
                            <Video class="size-4" /> انضم للاجتماع
                        </a>
                        <div v-if="a.location" class="flex items-center gap-3 rounded-lg bg-muted/30 p-3 text-sm">
                            <MapPin class="size-4 text-accent" /> {{ a.location }}
                        </div>
                        <div v-if="a.customer_notes">
                            <div class="mb-1 flex items-center gap-1 text-xs text-muted-foreground"><MessageSquare class="size-3.5" /> ملاحظات العميل</div>
                            <div class="whitespace-pre-wrap rounded-lg bg-muted/30 p-3 text-sm">{{ a.customer_notes }}</div>
                        </div>
                        <div v-if="a.staff_notes">
                            <div class="mb-1 text-xs text-muted-foreground">ملاحظات الفريق</div>
                            <div class="whitespace-pre-wrap rounded-lg border border-primary/20 bg-primary-soft p-3 text-sm">{{ a.staff_notes }}</div>
                        </div>
                        <div v-if="a.last_reschedule_reason">
                            <div class="mb-1 text-xs text-muted-foreground">سبب آخر طلب لإعادة الجدولة</div>
                            <div class="whitespace-pre-wrap rounded-lg border border-warning/20 bg-warning/5 p-3 text-sm">{{ a.last_reschedule_reason }}</div>
                        </div>
                        <div v-if="a.cancellation_reason">
                            <div class="mb-1 text-xs text-destructive">سبب الإلغاء</div>
                            <div class="rounded-lg border border-destructive/20 bg-destructive/5 p-3 text-sm">{{ a.cancellation_reason }}</div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Actions -->
                <Card>
                    <CardContent class="space-y-2 p-5">
                        <div class="mb-2 text-sm font-semibold">إجراءات</div>
                        <template v-if="can.reschedule || can.cancel || can.confirm || can.reject">
                            <Button v-if="can.confirm" variant="success" class="w-full justify-start" @click="post('confirm')"><CheckCircle2 class="size-4" /> تأكيد الموعد</Button>
                            <Button v-if="can.reject" variant="outline" class="w-full justify-start text-destructive" @click="post('reject')"><XCircle class="size-4" /> رفض الطلب</Button>
                            <Button v-if="can.reschedule" variant="outline" class="w-full justify-start" @click="reschedOpen = true"><CalendarClock class="size-4" /> إعادة الجدولة</Button>
                            <Button v-if="can.cancel" variant="outline" class="w-full justify-start text-destructive" @click="cancelOpen = true"><XCircle class="size-4" /> إلغاء الموعد</Button>
                        </template>
                        <div v-else class="rounded-md bg-muted/30 py-2 text-center text-xs text-muted-foreground">هذا الموعد مغلق — لا توجد إجراءات تغيير متاحة.</div>

                        <div class="my-2 border-t border-border"></div>
                        <Button variant="outline" class="w-full justify-start" @click="shareOpen = true"><Share2 class="size-4" /> مشاركة الموعد</Button>
                        <Button variant="outline" class="w-full justify-start" @click="calOpen = true"><CalendarPlus class="size-4" /> إضافة للتقويم</Button>
                        <div class="my-2 border-t border-border"></div>
                        <Button :href="route('appointments.create')" variant="ghost" class="w-full justify-start"><CalendarPlus class="size-4" /> حجز موعد آخر</Button>
                    </CardContent>
                </Card>
            </div>

            <!-- Activity log -->
            <Card>
                <CardContent class="p-5">
                    <div class="mb-3 flex items-center gap-2">
                        <History class="size-4 text-primary" />
                        <div class="text-sm font-bold">سجل الإجراءات</div>
                        <Badge variant="muted">{{ activity.length }}</Badge>
                    </div>
                    <p v-if="!activity.length" class="py-6 text-center text-xs text-muted-foreground">لا توجد إجراءات مسجّلة بعد.</p>
                    <ol v-else class="relative space-y-3.5 border-r-2 border-border pr-4">
                        <li v-for="ev in activity" :key="ev.id" class="relative">
                            <span :class="['absolute -right-[22px] top-0.5 size-3 rounded-full border-2 border-current bg-card', actionMeta(ev.action).cls]"></span>
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div :class="['flex items-center gap-1.5 text-xs font-bold', actionMeta(ev.action).cls]">
                                        <component :is="actionMeta(ev.action).icon" class="size-3.5" /> {{ actionMeta(ev.action).label }}
                                    </div>
                                    <div v-if="ev.notes" class="mt-0.5 whitespace-pre-wrap text-xs text-muted-foreground">{{ ev.notes }}</div>
                                    <div v-if="ev.old_status && ev.new_status && ev.old_status !== ev.new_status" class="mt-0.5 text-[10px] text-muted-foreground">
                                        {{ statusLabel(APPOINTMENT_STATUS, ev.old_status).label }} ← {{ statusLabel(APPOINTMENT_STATUS, ev.new_status).label }}
                                    </div>
                                </div>
                                <div class="shrink-0 whitespace-nowrap text-[10px] text-muted-foreground">{{ fmt(ev.created_at, { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) }}</div>
                            </div>
                        </li>
                    </ol>
                </CardContent>
            </Card>
        </div>

        <!-- Reschedule dialog -->
        <Dialog :open="reschedOpen" title="إعادة جدولة الموعد" description="اختر وقتاً جديداً من الأوقات المتاحة." class="max-w-lg" @update:open="v => reschedOpen = v">
            <div class="space-y-4">
                <div>
                    <Textarea label="سبب إعادة الجدولة (اختياري)" v-model="reschedReason" rows="2" />
                </div>
                <div>
                    <Label class="mb-1.5 block text-xs">الأوقات المتاحة</Label>
                    <div v-if="hasSlots" class="max-h-56 space-y-3 overflow-y-auto">
                        <div v-for="g in GROUPS" :key="g.key" v-show="slots[g.key].length">
                            <p class="mb-1.5 flex items-center gap-1.5 text-[11px] font-bold text-muted-foreground"><component :is="g.icon" class="size-3.5" /> {{ g.label }}</p>
                            <div class="flex flex-wrap gap-1.5">
                                <button v-for="s in slots[g.key]" :key="s.starts_at" @click="reschedSlot = s.starts_at"
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
                    <Button variant="ghost" @click="reschedOpen = false">إلغاء</Button>
                    <Button :disabled="!reschedSlot || processing" @click="submitReschedule">إرسال طلب الجدولة</Button>
                </div>
            </div>
        </Dialog>

        <!-- Cancel dialog -->
        <Dialog :open="cancelOpen" title="إلغاء الموعد" description="هذا الإجراء نهائي ولا يمكن التراجع عنه." class="max-w-md" @update:open="v => cancelOpen = v">
            <div class="space-y-3">
                <Textarea label="سبب الإلغاء (اختياري)" v-model="cancelReason" rows="2" />
                <div class="flex justify-end gap-2">
                    <Button variant="ghost" @click="cancelOpen = false">تراجع</Button>
                    <Button variant="destructive" :disabled="processing" @click="submitCancel">تأكيد الإلغاء</Button>
                </div>
            </div>
        </Dialog>

        <!-- Share dialog -->
        <Dialog :open="shareOpen" title="مشاركة الموعد" description="انسخ رابط الموعد أو شاركه." class="max-w-md" @update:open="v => shareOpen = v">
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <Input :model-value="appUrl()" readonly dir="ltr" class="text-xs" />
                    <Button size="icon" variant="outline" @click="copy(appUrl(), 'تم نسخ الرابط')"><Copy class="size-4" /></Button>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <a :href="`mailto:?subject=${encodeURIComponent(title())}&body=${encodeURIComponent(shareText())}`" class="flex items-center justify-center gap-1.5 rounded-lg border border-border bg-card py-2.5 text-sm font-medium hover:bg-muted"><Mail class="size-4" /> بريد</a>
                    <a :href="`https://wa.me/?text=${encodeURIComponent(shareText())}`" target="_blank" rel="noreferrer" class="flex items-center justify-center gap-1.5 rounded-lg border border-border bg-card py-2.5 text-sm font-medium hover:bg-muted"><Phone class="size-4" /> واتساب</a>
                </div>
                <div class="flex justify-end"><Button variant="ghost" @click="shareOpen = false">إغلاق</Button></div>
            </div>
        </Dialog>

        <!-- Add to calendar dialog -->
        <Dialog :open="calOpen" title="إضافة إلى التقويم" description="اختر تقويمك المفضل." class="max-w-md" @update:open="v => calOpen = v">
            <div class="space-y-2">
                <a :href="googleUrl()" target="_blank" rel="noreferrer" class="flex items-center gap-2 rounded-lg border border-border bg-card px-3 py-2.5 text-sm font-medium hover:bg-muted"><CalendarPlus class="size-4 text-primary" /> Google Calendar</a>
                <a :href="outlookUrl()" target="_blank" rel="noreferrer" class="flex items-center gap-2 rounded-lg border border-border bg-card px-3 py-2.5 text-sm font-medium hover:bg-muted"><CalendarPlus class="size-4 text-primary" /> Outlook</a>
                <button @click="downloadICS" class="flex w-full items-center gap-2 rounded-lg border border-border bg-card px-3 py-2.5 text-sm font-medium hover:bg-muted"><Download class="size-4 text-primary" /> ملف ICS (Apple / غيره)</button>
                <div class="flex justify-end pt-1"><Button variant="ghost" @click="calOpen = false">إغلاق</Button></div>
            </div>
        </Dialog>

        <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0 translate-y-2" leave-active-class="transition duration-150" leave-to-class="opacity-0">
            <div v-if="toast" class="fixed bottom-6 left-1/2 z-[60] -translate-x-1/2 rounded-lg bg-foreground px-4 py-2 text-sm font-medium text-background shadow-elevated">{{ toast }}</div>
        </Transition>
    </AppShell>
</template>
