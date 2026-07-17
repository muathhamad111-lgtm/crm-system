<script setup>
import { computed, ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import Input from '@/Components/ui/Input.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import Select from '@/Components/ui/Select.vue';
import {
    Plus, X, Calendar, Clock, CheckCircle2, Hourglass, Inbox,
    ShieldCheck, Send, CalendarCheck, Sparkles, UserCheck, Repeat,
} from 'lucide-vue-next';
import { fmtDateAr } from '@/lib/date';

const props = defineProps({
    leaves: { type: [Object, Array], default: () => ({ data: [] }) },
    leaveTypes: { type: Array, default: () => [] },
    substitutes: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
});

const rows = computed(() => (Array.isArray(props.leaves) ? props.leaves : (props.leaves?.data ?? [])));

const STATUS_LABEL = {
    draft: 'مسودة', pending: 'بانتظار الاعتماد', approved: 'معتمدة', active: 'سارية',
    completed: 'منتهية', cancelled: 'ملغاة', rejected: 'مرفوضة',
};
const STATUS_PILL = {
    approved: 'bg-info/10 text-info border-info/20',
    active: 'bg-success/10 text-success border-success/25',
    pending: 'bg-warning/10 text-warning border-warning/25',
    rejected: 'bg-destructive/10 text-destructive border-destructive/25',
    cancelled: 'bg-muted text-muted-foreground border-border',
    completed: 'bg-muted text-muted-foreground border-border',
    draft: 'bg-muted text-muted-foreground border-border',
};
const STATUS_DOT = {
    approved: 'bg-info', active: 'bg-success', pending: 'bg-warning', rejected: 'bg-destructive',
    cancelled: 'bg-muted-foreground/40', completed: 'bg-muted-foreground/60', draft: 'bg-muted-foreground/40',
};
const COVERAGE_LABEL = {
    move_all: 'نقل كل المهام للبديل', move_critical_overdue: 'نقل الحرجة/المتأخرة',
    move_open: 'نقل المهام المفتوحة', manual: 'تغطية يدوية', none: 'بدون تغطية',
};

const HOW_STEPS = [
    { icon: ShieldCheck, title: 'اعتماد HR خارجي', body: 'تتم الموافقة على الإجازة عبر نظام الموارد البشرية للشركة قبل تسجيلها هنا.' },
    { icon: Send, title: 'سجّل الإجازة هنا', body: 'أدخل النوع والفترة وحدّد البديل وملاحظة موجزة. يُشعَر مشرفك المباشر تلقائياً.' },
    { icon: CalendarCheck, title: 'تفعيل وتغطية', body: 'تُفعَّل الإجازة في تاريخ بدايتها وتنتقل المهام للبديل وفق سياسة التغطية.' },
];

const statusOf = (l) => l.status?.value ?? l.status;
const isFutureApproved = (l) => statusOf(l) === 'approved' && l.start_date && new Date(l.start_date) > new Date(new Date().toDateString());

// --- Filter tabs ---
const filter = ref('all');
const TABS = [
    { key: 'all', label: 'الكل' },
    { key: 'active', label: 'سارية الآن' },
    { key: 'upcoming', label: 'قادمة' },
    { key: 'history', label: 'السجل' },
];
const tabCount = (key) => key === 'all' ? (props.stats.total ?? 0)
    : key === 'active' ? (props.stats.active ?? 0)
    : key === 'upcoming' ? (props.stats.upcoming ?? 0)
    : (props.stats.history ?? 0);

const filtered = computed(() => {
    const list = rows.value;
    if (filter.value === 'all') return list;
    if (filter.value === 'active') return list.filter((l) => statusOf(l) === 'active');
    if (filter.value === 'upcoming') return list.filter(isFutureApproved);
    return list.filter((l) => ['completed', 'cancelled'].includes(statusOf(l)));
});

const kpiChips = computed(() => [
    { icon: Hourglass, label: 'سارية الآن', value: props.stats.active ?? 0 },
    { icon: Clock, label: 'معتمدة/نشطة', value: props.stats.open ?? 0 },
    { icon: Calendar, label: 'قادمة', value: props.stats.upcoming ?? 0 },
    { icon: CheckCircle2, label: `أيام مُحتسبة ${props.stats.year ?? ''}`, value: props.stats.days_used ?? 0 },
]);

// --- Register dialog ---
const open = ref(false);
const form = useForm({ leave_type_id: '', start_date: '', end_date: '', reason: '', substitute_id: '', coverage_strategy: 'none' });
function save() {
    form.post('/leaves', { onSuccess: () => { open.value = false; form.reset(); }, preserveScroll: true });
}
function cancel(id) {
    router.post(`/leaves/${id}/cancel`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="إجازاتي" />
    <AppShell>
        <div class="space-y-4">
            <!-- Gradient hero -->
            <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-elevated" style="background-image: var(--gradient-hero);">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(closest-side at 75% 20%, rgba(255,255,255,.4), transparent);"></div>
                <div class="relative flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs text-white/60">إجازاتي · سجل الغيابات</p>
                        <h1 class="mt-1 text-2xl font-bold">إجازاتي</h1>
                        <p class="mt-1 max-w-xl text-sm text-white/80">سجّل إجازاتك المعتمدة من الموارد البشرية وتتبّع حالتها وأثرها على تغطية أعمالك.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="hidden items-center gap-2 rounded-full bg-white/15 px-3 py-1.5 text-xs backdrop-blur-sm tabular-nums md:inline-flex">
                            <Inbox class="size-3.5" /> {{ stats.total ?? 0 }} إجازة مسجّلة
                        </span>
                        <Button class="bg-white text-primary hover:bg-white/90" @click="open = true">
                            <Plus class="size-4" /> تسجيل إجازة معتمدة
                        </Button>
                    </div>
                </div>
                <!-- KPI chips -->
                <div class="relative mt-5 flex flex-wrap items-center gap-2">
                    <div v-for="(k, i) in kpiChips" :key="i" class="flex items-center gap-2 rounded-lg border border-white/15 bg-white/10 px-2.5 py-1.5 backdrop-blur-sm">
                        <span class="flex size-5 items-center justify-center rounded-md bg-white/20"><component :is="k.icon" class="size-3" /></span>
                        <span class="text-[11px]"><span class="font-semibold">{{ k.label }}</span> <span class="font-bold text-white/90 tabular-nums">{{ k.value }}</span></span>
                    </div>
                </div>
            </div>

            <!-- How it works -->
            <section class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div v-for="(step, i) in HOW_STEPS" :key="i" class="relative rounded-2xl border border-border/60 bg-card p-4 transition-all hover:border-primary/40 hover:shadow-md">
                    <div class="mb-2 flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-xl bg-primary/10 ring-1 ring-primary/15">
                            <component :is="step.icon" class="size-5 text-primary" />
                        </div>
                        <span class="text-[10px] font-bold tracking-widest text-muted-foreground/70">0{{ i + 1 }}</span>
                    </div>
                    <h3 class="mb-1 text-sm font-bold">{{ step.title }}</h3>
                    <p class="text-xs leading-relaxed text-muted-foreground">{{ step.body }}</p>
                </div>
            </section>

            <!-- Filter tabs -->
            <div class="flex flex-wrap items-center gap-1.5 rounded-xl border border-border bg-card p-1.5 shadow-sm">
                <button v-for="t in TABS" :key="t.key" @click="filter = t.key"
                    :class="['flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all', filter === t.key ? 'bg-primary text-primary-foreground shadow-sm' : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground']">
                    <span>{{ t.label }}</span>
                    <span :class="['rounded px-1.5 tabular-nums', filter === t.key ? 'bg-white/20' : 'bg-muted text-muted-foreground']">{{ tabCount(t.key) }}</span>
                </button>
            </div>

            <!-- List -->
            <div class="grid gap-2.5">
                <Card v-if="!filtered.length" class="flex flex-col items-center gap-3 border-dashed p-10 text-center">
                    <div class="flex size-14 items-center justify-center rounded-2xl bg-primary/10 text-primary"><Sparkles class="size-7" /></div>
                    <div>
                        <h3 class="text-base font-bold text-primary">لا توجد إجازات في هذا العرض</h3>
                        <p class="mx-auto mt-1 max-w-md text-xs text-muted-foreground">
                            {{ filter === 'all' ? 'لا توجد إجازات مسجّلة بعد. سجّل إجازتك المعتمدة من الموارد البشرية لتظهر هنا.' : 'غيّر التصفية أعلاه لعرض الإجازات الأخرى، أو سجّل إجازة جديدة.' }}
                        </p>
                    </div>
                    <Button class="mt-1" @click="open = true"><Plus class="size-4" /> تسجيل إجازة معتمدة</Button>
                </Card>

                <Card v-for="l in filtered" :key="l.id" class="p-3.5 transition-shadow hover:shadow-md">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-1 space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 text-[11px] font-semibold"
                                    :style="{ borderColor: l.leave_type?.color || 'var(--border)', color: l.leave_type?.color || 'var(--foreground)', background: `color-mix(in oklab, ${l.leave_type?.color || 'var(--muted)'} 8%, transparent)` }">
                                    {{ l.leave_type?.label_ar ?? '—' }}
                                </span>
                                <span :class="['inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 text-[11px] font-semibold', STATUS_PILL[statusOf(l)] ?? STATUS_PILL.draft]">
                                    <span :class="['size-1.5 rounded-full', STATUS_DOT[statusOf(l)] ?? STATUS_DOT.draft]"></span>
                                    {{ STATUS_LABEL[statusOf(l)] ?? statusOf(l) }}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-3 text-sm">
                                <span class="inline-flex items-center gap-1.5 font-semibold text-foreground">
                                    <Calendar class="size-3.5 text-primary" />
                                    {{ fmtDateAr(l.start_date) }} ← {{ fmtDateAr(l.end_date) }}
                                </span>
                                <span class="text-xs text-muted-foreground">({{ l.duration_days ?? 0 }} {{ Number(l.duration_days) === 1 ? 'يوم' : 'أيام' }})</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                                <span v-if="l.substitute" class="inline-flex items-center gap-1.5">
                                    <UserCheck class="size-3.5 text-accent" />
                                    <span class="font-semibold text-foreground">البديل:</span> {{ l.substitute.full_name }}
                                </span>
                                <span v-if="(l.coverage_strategy?.value ?? l.coverage_strategy) && (l.coverage_strategy?.value ?? l.coverage_strategy) !== 'none'" class="inline-flex items-center gap-1.5">
                                    <Repeat class="size-3.5 text-info" />
                                    <span class="font-semibold text-foreground">التغطية:</span> {{ COVERAGE_LABEL[l.coverage_strategy?.value ?? l.coverage_strategy] }}
                                </span>
                            </div>
                            <p v-if="l.reason" class="text-xs leading-relaxed text-muted-foreground">
                                <span class="ml-1 font-semibold text-foreground">السبب:</span>{{ l.reason }}
                            </p>
                        </div>
                        <Button v-if="isFutureApproved(l)" size="sm" variant="outline" class="shrink-0" @click="cancel(l.id)">
                            <X class="size-3.5" /> إلغاء
                        </Button>
                    </div>
                </Card>
            </div>

            <!-- Register dialog -->
            <Dialog v-model:open="open" title="تسجيل إجازة معتمدة" description="سجّل إجازة تمت الموافقة عليها من الموارد البشرية.">
                <div class="space-y-3">
                    <Select label="نوع الإجازة" v-model="form.leave_type_id">
                        <option value=""></option>
                        <option v-for="t in leaveTypes" :key="t.id" :value="t.id">{{ t.label_ar }}</option>
                    </Select>
                    <p v-if="form.errors.leave_type_id" class="text-xs text-destructive">{{ form.errors.leave_type_id }}</p>
                    <div class="grid grid-cols-2 gap-2">
                        <Input label="من" type="date" v-model="form.start_date" />
                        <Input label="إلى" type="date" v-model="form.end_date" />
                    </div>
                    <p v-if="form.errors.end_date" class="text-xs text-destructive">{{ form.errors.end_date }}</p>
                    <Select v-if="substitutes.length" label="البديل أثناء الإجازة" v-model="form.substitute_id">
                        <option value=""></option>
                        <option v-for="s in substitutes" :key="s.id" :value="s.id">{{ s.full_name }}</option>
                    </Select>
                    <Select label="سياسة التغطية" v-model="form.coverage_strategy">
                        <option value="none">بدون تغطية</option>
                        <option value="move_all">نقل كل المهام للبديل</option>
                        <option value="move_critical_overdue">نقل الحرجة/المتأخرة فقط</option>
                        <option value="move_open">نقل المهام المفتوحة</option>
                        <option value="manual">تغطية يدوية</option>
                    </Select>
                    <Textarea label="سبب/ملاحظة موجزة" v-model="form.reason" class="min-h-20" />
                    <div class="flex justify-end gap-2 pt-1">
                        <Button variant="outline" @click="open = false">إلغاء</Button>
                        <Button :disabled="form.processing" @click="save">حفظ الإجازة</Button>
                    </div>
                </div>
            </Dialog>
        </div>
    </AppShell>
</template>
