<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Input from '@/Components/ui/Input.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import Select from '@/Components/ui/Select.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import { fmtTimeAr } from '@/lib/date';
import {
    CalendarDays, Plus, MapPin, Video, Clock, Bell, Users, Eye, EyeOff, CheckCircle2,
    ListChecks, Sun, CalendarClock, Link as LinkIcon, Trash2,
} from 'lucide-vue-next';

const props = defineProps({
    events: { type: [Array, Object], default: () => [] },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
});

const EVENT_TYPES = [
    { v: 'meeting', label: 'اجتماع', dot: 'bg-info', pill: 'secondary' },
    { v: 'visit', label: 'زيارة', dot: 'bg-primary', pill: 'default' },
    { v: 'call', label: 'مكالمة', dot: 'bg-success', pill: 'success' },
    { v: 'reminder', label: 'تذكير', dot: 'bg-warning', pill: 'warning' },
    { v: 'task', label: 'مهمة', dot: 'bg-accent', pill: 'accent' },
    { v: 'other', label: 'أخرى', dot: 'bg-muted-foreground', pill: 'muted' },
];
const typeMeta = (t) => EVENT_TYPES.find((e) => e.v === t) ?? EVENT_TYPES[5];

const AR = 'ar-SA-u-ca-gregory';
const TZ = 'Asia/Riyadh';
const fmt = (v, opts) => (v ? new Intl.DateTimeFormat(AR, { timeZone: TZ, ...opts }).format(new Date(v)) : '—');
const dayFull = (v) => fmt(v, { weekday: 'long', day: 'numeric', month: 'long' });
const dayNum = (v) => fmt(v, { day: 'numeric' });
function dayKey(iso) {
    const parts = new Intl.DateTimeFormat('en-CA', { timeZone: TZ, year: 'numeric', month: '2-digit', day: '2-digit' }).formatToParts(new Date(iso));
    const g = (t) => parts.find((p) => p.type === t).value;
    return `${g('year')}-${g('month')}-${g('day')}`;
}
const todayKey = dayKey(new Date().toISOString());

const rows = computed(() => (Array.isArray(props.events) ? props.events : (props.events.data ?? [])));

const grouped = computed(() => {
    const m = new Map();
    rows.value.slice().sort((a, b) => new Date(a.starts_at) - new Date(b.starts_at)).forEach((e) => {
        const k = dayKey(e.starts_at);
        if (!m.has(k)) m.set(k, []);
        m.get(k).push(e);
    });
    return Array.from(m.entries());
});

const KPIS = computed(() => [
    { icon: ListChecks, label: 'الإجمالي', value: props.stats.total ?? 0, tone: 'bg-primary-soft text-primary' },
    { icon: Sun, label: 'اليوم', value: props.stats.today ?? 0, tone: 'bg-accent/10 text-accent' },
    { icon: CalendarClock, label: 'مجدولة', value: props.stats.scheduled ?? 0, tone: 'bg-info/10 text-info' },
    { icon: CheckCircle2, label: 'مكتملة', value: props.stats.completed ?? 0, tone: 'bg-success/10 text-success' },
    { icon: CalendarDays, label: 'قادمة', value: props.stats.upcoming ?? 0, tone: 'bg-warning/15 text-warning' },
]);

function setType(v) {
    router.get(route('calendar.index'), { type: v === 'all' ? undefined : v, status: props.filters.status !== 'all' ? props.filters.status : undefined },
        { preserveState: true, replace: true, preserveScroll: true });
}

/* ---------- dialog ---------- */
const open = ref(false);
const editingId = ref(null);
const form = useForm({
    title: '', event_type: 'meeting', status: 'scheduled', visibility: 'internal',
    starts_at: '', ends_at: '', location: '', meeting_url: '', reminder_minutes_before: 15, description: '',
});

function toLocalInput(iso) {
    if (!iso) return '';
    const parts = new Intl.DateTimeFormat('en-CA', { timeZone: TZ, year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }).formatToParts(new Date(iso));
    const g = (t) => parts.find((p) => p.type === t).value;
    let hour = g('hour'); if (hour === '24') hour = '00';
    return `${g('year')}-${g('month')}-${g('day')}T${hour}:${g('minute')}`;
}
function addHour(local) {
    if (!local) return '';
    const d = new Date(local);
    d.setHours(d.getHours() + 1);
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function openCreate() {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    const now = toLocalInput(new Date().toISOString());
    form.starts_at = now;
    form.ends_at = addHour(now);
    open.value = true;
}
function openEdit(ev) {
    editingId.value = ev.id;
    form.clearErrors();
    form.title = ev.title;
    form.event_type = ev.event_type;
    form.status = ev.status ?? 'scheduled';
    form.visibility = ev.visibility ?? 'internal';
    form.starts_at = toLocalInput(ev.starts_at);
    form.ends_at = toLocalInput(ev.ends_at || ev.starts_at);
    form.location = ev.location ?? '';
    form.meeting_url = ev.meeting_url ?? '';
    form.reminder_minutes_before = ev.reminder_minutes_before ?? 15;
    form.description = ev.description ?? '';
    open.value = true;
}
function save() {
    if (!form.ends_at || new Date(form.ends_at) <= new Date(form.starts_at)) form.ends_at = addHour(form.starts_at);
    const opts = { preserveScroll: true, onSuccess: () => { open.value = false; form.reset(); } };
    if (editingId.value) form.patch(route('calendar.update', editingId.value), opts);
    else form.post(route('calendar.store'), opts);
}
function remove() {
    if (!editingId.value) return;
    router.delete(route('calendar.destroy', editingId.value), { preserveScroll: true, onSuccess: () => { open.value = false; } });
}
</script>

<template>
    <Head title="التقويم" />
    <AppShell>
        <div class="space-y-4">
            <!-- Gradient hero -->
            <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-elevated" style="background-image: var(--gradient-hero);">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(closest-side at 75% 20%, rgba(255,255,255,.4), transparent);"></div>
                <div class="relative flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs text-white/60">فريق العمل · التقويم</p>
                        <h1 class="mt-1 text-2xl font-bold">التقويم — الزيارات والاجتماعات</h1>
                        <p class="mt-1 max-w-xl text-sm text-white/80">الزيارات، الاجتماعات، التذكيرات، والمهام المجدولة في أجندة واحدة.</p>
                    </div>
                    <Button class="bg-white/15 text-white hover:bg-white/25 backdrop-blur-sm" @click="openCreate"><Plus class="size-4" /> حدث جديد</Button>
                </div>
            </div>

            <!-- KPI ribbon -->
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-5">
                <Card v-for="k in KPIS" :key="k.label" class="p-3">
                    <div class="flex items-center gap-3">
                        <div :class="['flex size-9 shrink-0 items-center justify-center rounded-lg', k.tone]"><component :is="k.icon" class="size-4" /></div>
                        <div class="min-w-0">
                            <div class="truncate text-[10px] uppercase text-muted-foreground">{{ k.label }}</div>
                            <div class="text-lg font-bold tabular-nums">{{ k.value }}</div>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Type filter + legend -->
            <div class="flex flex-wrap items-center gap-2">
                <button @click="setType('all')" :data-active="(filters.type ?? 'all') === 'all'"
                    class="rounded-lg border border-border bg-card px-3 py-1.5 text-xs font-semibold text-muted-foreground transition-colors hover:text-foreground data-[active=true]:bg-primary data-[active=true]:text-primary-foreground">
                    كل الأنواع
                </button>
                <button v-for="t in EVENT_TYPES" :key="t.v" @click="setType(t.v)" :data-active="filters.type === t.v"
                    class="flex items-center gap-1.5 rounded-lg border border-border bg-card px-3 py-1.5 text-xs font-semibold text-muted-foreground transition-colors hover:text-foreground data-[active=true]:bg-primary data-[active=true]:text-primary-foreground">
                    <span :class="['size-2 rounded-full', t.dot]"></span> {{ t.label }}
                </button>
            </div>

            <!-- Agenda -->
            <div v-if="grouped.length" class="space-y-4">
                <div v-for="[k, list] in grouped" :key="k" class="border-r-2 border-primary/30 pr-3">
                    <div class="mb-2 flex items-center gap-2">
                        <span :class="['inline-flex size-8 items-center justify-center rounded-full text-xs font-bold tabular-nums', k === todayKey ? 'bg-primary text-primary-foreground' : 'bg-muted text-foreground']">{{ dayNum(list[0].starts_at) }}</span>
                        <span class="text-sm font-bold">{{ dayFull(list[0].starts_at) }}</span>
                        <span v-if="k === todayKey" class="text-[10px] font-semibold text-primary">اليوم</span>
                        <Badge variant="muted" class="mr-auto">{{ list.length }}</Badge>
                    </div>
                    <div class="space-y-1.5">
                        <button v-for="ev in list" :key="ev.id" @click="openEdit(ev)"
                            class="flex w-full items-start gap-3 rounded-xl border border-border bg-card p-3 text-start transition-all hover:border-primary/40 hover:shadow-sm">
                            <span :class="['mt-1.5 size-2.5 shrink-0 rounded-full', typeMeta(ev.event_type).dot]"></span>
                            <div class="min-w-0 flex-1">
                                <div :class="['truncate text-sm font-semibold', ev.status === 'completed' && 'text-muted-foreground line-through']">{{ ev.title }}</div>
                                <div class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-muted-foreground">
                                    <span class="tabular-nums">{{ fmtTimeAr(ev.starts_at) }}<span v-if="ev.ends_at"> – {{ fmtTimeAr(ev.ends_at) }}</span></span>
                                    <span v-if="ev.location" class="flex items-center gap-1"><MapPin class="size-3" /> {{ ev.location }}</span>
                                    <span v-if="ev.meeting_url" class="flex items-center gap-1 text-info"><Video class="size-3" /> رابط</span>
                                    <span v-if="ev.visibility === 'shared'" class="flex items-center gap-1 text-info"><Eye class="size-3" /> مشترك</span>
                                    <span v-else class="flex items-center gap-1"><EyeOff class="size-3" /> داخلي</span>
                                    <span v-if="ev.status === 'completed'" class="flex items-center gap-1 text-success"><CheckCircle2 class="size-3" /> مكتمل</span>
                                </div>
                            </div>
                            <Badge :variant="typeMeta(ev.event_type).pill" class="shrink-0">{{ typeMeta(ev.event_type).label }}</Badge>
                        </button>
                    </div>
                </div>
            </div>
            <Card v-else>
                <CardContent class="py-16 text-center">
                    <CalendarDays class="mx-auto mb-3 size-10 text-muted-foreground/40" />
                    <p class="mb-4 text-sm text-muted-foreground">لا توجد أحداث مطابقة.</p>
                    <Button @click="openCreate"><Plus class="size-4" /> إضافة حدث</Button>
                </CardContent>
            </Card>
        </div>

        <!-- Create/Edit dialog -->
        <Dialog :open="open" :title="editingId ? 'تعديل حدث' : 'حدث جديد'" class="max-w-lg" @update:open="v => { open = v; if (!v) editingId = null; }">
            <div class="space-y-3">
                <div>
                    <Input label="العنوان *" v-model="form.title" />
                    <p v-if="form.errors.title" class="mt-1 text-xs text-destructive">{{ form.errors.title }}</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <Select label="النوع" v-model="form.event_type"><option v-for="t in EVENT_TYPES" :key="t.v" :value="t.v">{{ t.label }}</option></Select>
                    <Select label="الحالة" v-model="form.status">
                        <option value="scheduled">مجدول</option>
                        <option value="completed">مكتمل</option>
                        <option value="cancelled">ملغي</option>
                        <option value="rescheduled">معاد جدولته</option>
                    </Select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <Input label="من" type="datetime-local" v-model="form.starts_at" />
                    <Input label="إلى" type="datetime-local" v-model="form.ends_at" />
                </div>
                <Input label="الموقع" v-model="form.location" />
                <Input label="رابط الاجتماع" v-model="form.meeting_url" dir="ltr" />
                <div class="grid grid-cols-2 gap-3">
                    <Input label="تذكير قبل (دقيقة)" type="number" min="0" v-model="form.reminder_minutes_before" />
                    <Select label="الظهور" v-model="form.visibility">
                        <option value="internal">داخلي (للفريق فقط)</option>
                        <option value="shared">مشترك مع العميل</option>
                    </Select>
                </div>
                <Textarea label="الوصف" v-model="form.description" rows="3" />
                <div class="flex items-center justify-between gap-2 pt-1">
                    <Button v-if="editingId" variant="ghost" class="text-destructive" @click="remove"><Trash2 class="size-4" /> حذف</Button>
                    <span v-else></span>
                    <div class="flex gap-2">
                        <Button variant="outline" @click="open = false">إلغاء</Button>
                        <Button :disabled="form.processing || !form.title" @click="save">حفظ</Button>
                    </div>
                </div>
            </div>
        </Dialog>
    </AppShell>
</template>
