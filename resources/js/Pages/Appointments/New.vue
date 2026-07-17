<script setup>
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Label from '@/Components/ui/Label.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import Badge from '@/Components/ui/Badge.vue';
import { fmtTimeAr } from '@/lib/date';
import {
    CalendarDays, Phone, Video, MapPin, GraduationCap, Workflow, HelpCircle,
    Check, CheckCircle2, Info, Clock, Sunrise, Sun, Moon, Sparkles, ChevronLeft, ChevronRight,
    ArrowRight,
} from 'lucide-vue-next';

const props = defineProps({
    types: { type: Array, default: () => [] },
    reasons: { type: Array, default: () => [] },
    openRequests: { type: Array, default: () => [] },
    slotsByType: { type: Object, default: () => ({}) },
});

const MODE_ICON = { phone: Phone, video: Video, onsite: MapPin, training: GraduationCap, followup: Workflow, other: HelpCircle };
const modeIcon = (m) => MODE_ICON[m] ?? HelpCircle;

const AR = 'ar-SA-u-ca-gregory-nu-latn';
const TZ = 'Asia/Riyadh';
const fmt = (v, opts) => (v ? new Intl.DateTimeFormat(AR, { timeZone: TZ, ...opts }).format(new Date(v)) : '—');
const dayMonth = (v) => fmt(v, { weekday: 'long', day: 'numeric', month: 'long' });
const shortDate = (v) => fmt(v, { day: 'numeric', month: 'short' });

const STEPS = [
    { n: 1, l: 'النوع', icon: Sparkles },
    { n: 2, l: 'السبب', icon: Info },
    { n: 3, l: 'الوقت', icon: CalendarDays },
    { n: 4, l: 'تأكيد', icon: CheckCircle2 },
];
const GROUPS = [
    { key: 'morning', label: 'صباحاً', icon: Sunrise },
    { key: 'afternoon', label: 'ظهراً', icon: Sun },
    { key: 'evening', label: 'مساءً', icon: Moon },
];

const step = ref(1);
const form = useForm({
    type_id: '', reason_code: '', reason_other: '', related_request_id: '', starts_at: '', customer_notes: '',
});

const selectedType = computed(() => props.types.find((t) => t.id === form.type_id) ?? null);
const selectedReason = computed(() => props.reasons.find((r) => r.value === form.reason_code) ?? null);
const selectedRequest = computed(() => props.openRequests.find((r) => r.id === form.related_request_id) ?? null);
const slots = computed(() => props.slotsByType[form.type_id] ?? { morning: [], afternoon: [], evening: [] });
const hasSlots = computed(() => (slots.value.morning.length + slots.value.afternoon.length + slots.value.evening.length) > 0);

const reasonValid = computed(() => !!form.reason_code && (form.reason_code !== 'other' || form.reason_other.trim().length > 0));
const reqLinkValid = computed(() => !selectedType.value?.requires_request_link || !!form.related_request_id);
const canNext = computed(() => {
    if (step.value === 1) return !!form.type_id;
    if (step.value === 2) return reasonValid.value && reqLinkValid.value;
    if (step.value === 3) return !!form.starts_at;
    return true;
});

function pickType(id) { form.type_id = id; form.starts_at = ''; }
function next() { if (canNext.value && step.value < 4) step.value += 1; }
function back() { if (step.value > 1) step.value -= 1; }
function submit() {
    form.transform((d) => ({
        ...d,
        reason_other: d.reason_code === 'other' ? d.reason_other : null,
        related_request_id: d.related_request_id || null,
    })).post(route('appointments.store'));
}
</script>

<template>
    <Head title="حجز موعد جديد" />
    <AppShell>
        <div class="space-y-4">
            <!-- Gradient hero -->
            <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-elevated" style="background-image: var(--gradient-hero);">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(closest-side at 75% 20%, rgba(255,255,255,.4), transparent);"></div>
                <div class="relative flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs text-white/60">حجز موعد · رحلة مبسّطة من 4 خطوات</p>
                        <h1 class="mt-1 text-2xl font-bold">احجز موعدك مع الفريق</h1>
                        <p class="mt-1 max-w-xl text-sm text-white/80">اختر نوع الموعد المناسب، حدّد الوقت من الأوقات المتاحة فعلياً، ونؤكّد لك إلكترونياً.</p>
                    </div>
                    <Button :href="route('appointments.index')" variant="outline" class="border-white/30 bg-white/10 text-white hover:bg-white/20">
                        <ArrowRight class="size-4" /> مواعيدي
                    </Button>
                </div>
            </div>

            <!-- Stepper -->
            <Card class="p-3">
                <div class="flex items-center justify-between gap-2">
                    <template v-for="(s, i) in STEPS" :key="s.n">
                        <div class="flex min-w-0 items-center gap-2">
                            <div :class="['flex size-9 shrink-0 items-center justify-center rounded-xl transition-all',
                                step > s.n ? 'bg-success text-success-foreground'
                                : step === s.n ? 'scale-110 bg-primary text-primary-foreground shadow-md'
                                : 'bg-muted text-muted-foreground']">
                                <Check v-if="step > s.n" class="size-4" />
                                <component :is="s.icon" v-else class="size-4" />
                            </div>
                            <div class="hidden min-w-0 sm:block">
                                <div class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground/70">خطوة {{ s.n }}</div>
                                <div :class="['truncate text-xs font-bold', step === s.n ? 'text-foreground' : step > s.n ? 'text-success' : 'text-muted-foreground']">{{ s.l }}</div>
                            </div>
                        </div>
                        <div v-if="i < STEPS.length - 1" :class="['h-0.5 flex-1 rounded-full', step > s.n ? 'bg-success' : 'bg-border']"></div>
                    </template>
                </div>
            </Card>

            <div class="grid grid-cols-1 items-start gap-4 lg:grid-cols-[1fr_320px]">
                <!-- Main -->
                <Card>
                    <CardContent class="space-y-6 p-5 md:p-6">
                        <!-- Step 1 -->
                        <div v-if="step === 1" class="space-y-4">
                            <div>
                                <Label class="text-base font-bold">اختر نوع الموعد</Label>
                                <p class="mt-1 text-xs text-muted-foreground">يحدّد كل نوع مدّة الموعد وطريقة التواصل وما إذا كان يحتاج موافقة الفريق.</p>
                            </div>
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <button v-for="t in types" :key="t.id" type="button" @click="pickType(t.id)"
                                    :class="['group relative rounded-2xl border-2 p-4 text-start transition-all hover:shadow-md',
                                        form.type_id === t.id ? 'border-primary bg-primary-soft shadow-md' : 'border-border bg-card hover:border-primary/30']">
                                    <div v-if="form.type_id === t.id" class="absolute left-2.5 top-2.5 flex size-5 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                        <Check class="size-3" />
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl"
                                            :style="{ background: (t.color || 'var(--primary)') + '1a', color: t.color || 'var(--primary)' }">
                                            <component :is="modeIcon(t.mode)" class="size-5" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="mb-1 flex flex-wrap items-center gap-1.5 text-sm font-bold">
                                                {{ t.name_ar }}
                                                <Badge v-if="t.requires_approval" variant="warning" class="text-[9px]">يحتاج موافقة</Badge>
                                                <Badge v-if="t.requires_request_link" variant="secondary" class="text-[9px]">ربط بطلب</Badge>
                                            </div>
                                            <p v-if="t.description" class="line-clamp-2 text-xs text-muted-foreground">{{ t.description }}</p>
                                            <div class="mt-1.5 flex items-center gap-1 text-[11px] text-muted-foreground">
                                                <Clock class="size-3" /> {{ t.duration_minutes }} دقيقة
                                            </div>
                                        </div>
                                    </div>
                                </button>
                                <div v-if="!types.length" class="rounded-xl bg-muted/30 py-8 text-center text-sm text-muted-foreground md:col-span-2">
                                    لا توجد أنواع مواعيد متاحة حالياً
                                </div>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div v-else-if="step === 2" class="space-y-5">
                            <div>
                                <Label class="text-base font-bold">ما سبب طلبك للموعد؟</Label>
                                <p class="mt-1 text-xs text-muted-foreground">يساعد ذلك الفريق على التحضير المسبق.</p>
                            </div>
                            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                                <button v-for="r in reasons" :key="r.value" type="button" @click="form.reason_code = r.value"
                                    :class="['rounded-xl border-2 p-3 text-start transition-all',
                                        form.reason_code === r.value ? 'border-primary bg-primary-soft' : 'border-border hover:border-primary/30']">
                                    <div class="flex items-center justify-between gap-2 text-sm font-semibold">
                                        {{ r.label }}
                                        <Check v-if="form.reason_code === r.value" class="size-4 shrink-0 text-primary" />
                                    </div>
                                    <div class="mt-0.5 text-[11px] text-muted-foreground">{{ r.desc }}</div>
                                </button>
                            </div>
                            <div v-if="form.reason_code === 'other'">
                                <Textarea label="اكتب سبب الموعد بإيجاز…" v-model="form.reason_other" rows="2" />
                            </div>

                            <div v-if="selectedType" class="space-y-3 border-t border-border pt-4">
                                <Label class="text-sm font-bold">
                                    ربط بطلب قائم
                                    <span v-if="selectedType.requires_request_link" class="text-destructive">*</span>
                                    <span v-else class="text-[11px] font-normal text-muted-foreground">(اختياري)</span>
                                </Label>
                                <div v-if="!openRequests.length" class="rounded-lg bg-muted/30 py-3 text-center text-xs text-muted-foreground">
                                    لا توجد طلبات مفتوحة لديك حالياً
                                </div>
                                <div v-else class="max-h-56 space-y-2 overflow-y-auto pr-1">
                                    <button type="button" @click="form.related_request_id = ''"
                                        :class="['w-full rounded-lg border-2 p-2.5 text-start text-xs transition-all',
                                            !form.related_request_id ? 'border-primary bg-primary-soft' : 'border-border hover:border-primary/30']">
                                        <div class="font-semibold">بدون ربط بطلب</div>
                                    </button>
                                    <button v-for="r in openRequests" :key="r.id" type="button" @click="form.related_request_id = r.id"
                                        :class="['w-full rounded-lg border-2 p-2.5 text-start transition-all',
                                            form.related_request_id === r.id ? 'border-primary bg-primary-soft' : 'border-border hover:border-primary/30']">
                                        <div class="text-[10px] tabular-nums text-muted-foreground">{{ r.request_number }}</div>
                                        <div class="truncate text-xs font-semibold">{{ r.title }}</div>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div v-else-if="step === 3" class="space-y-5">
                            <div>
                                <Label class="text-base font-bold">اختر الوقت المناسب</Label>
                                <p class="mt-1 text-xs text-muted-foreground">الأوقات المعروضة متاحة فعلياً ضمن جدول الفريق خلال الأيام الأربعة عشر القادمة.</p>
                            </div>
                            <div v-if="hasSlots" class="space-y-4">
                                <div v-for="g in GROUPS" :key="g.key" v-show="slots[g.key].length">
                                    <div class="mb-2 flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
                                        <component :is="g.icon" class="size-3.5" /> {{ g.label }}
                                        <span class="font-normal text-muted-foreground/60">({{ slots[g.key].length }})</span>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-5">
                                        <button v-for="s in slots[g.key]" :key="s.starts_at" type="button" @click="form.starts_at = s.starts_at"
                                            :class="['rounded-lg border-2 px-2 py-2 text-center text-sm font-bold transition-all',
                                                form.starts_at === s.starts_at ? 'border-primary bg-primary text-primary-foreground shadow-sm' : 'border-border hover:border-primary/30 hover:bg-muted']">
                                            <span class="block text-[10px] font-normal opacity-70">{{ shortDate(s.starts_at) }}</span>
                                            <span class="tabular-nums">{{ fmtTimeAr(s.starts_at) }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="flex items-center justify-center gap-2 rounded-xl bg-muted/30 py-6 text-sm text-muted-foreground">
                                <Info class="size-4" /> لا توجد أوقات متاحة حالياً لهذا النوع.
                            </div>

                            <div class="border-t border-border pt-4">
                                <Textarea label="ملاحظات للفريق (اختياري)" v-model="form.customer_notes" rows="3" />
                            </div>
                        </div>

                        <!-- Step 4 -->
                        <div v-else class="space-y-4">
                            <div class="flex items-center gap-2">
                                <div class="flex size-10 items-center justify-center rounded-xl bg-success/10 text-success ring-1 ring-success/30">
                                    <CheckCircle2 class="size-5" />
                                </div>
                                <div>
                                    <div class="text-base font-bold">راجع التفاصيل وأكّد الحجز</div>
                                    <div class="text-xs text-muted-foreground">يمكنك إلغاء الموعد لاحقاً من صفحة "مواعيدي".</div>
                                </div>
                            </div>
                            <div class="space-y-3 rounded-2xl border border-border bg-muted/30 p-4">
                                <div class="flex items-center gap-3 border-b border-border pb-3">
                                    <div class="flex size-11 items-center justify-center rounded-lg"
                                        :style="{ background: (selectedType?.color || 'var(--primary)') + '1a', color: selectedType?.color || 'var(--primary)' }">
                                        <component :is="modeIcon(selectedType?.mode)" class="size-5" />
                                    </div>
                                    <div>
                                        <p class="font-bold text-foreground">{{ selectedType?.name_ar }}</p>
                                        <p class="text-xs text-muted-foreground">{{ selectedReason?.label }}<span v-if="form.reason_code === 'other' && form.reason_other">: {{ form.reason_other }}</span></p>
                                    </div>
                                </div>
                                <div class="grid gap-2 text-sm">
                                    <div class="flex justify-between gap-3"><span class="text-muted-foreground">المدة</span><span class="font-semibold tabular-nums">{{ selectedType?.duration_minutes }} دقيقة</span></div>
                                    <div v-if="selectedRequest" class="flex justify-between gap-3"><span class="text-muted-foreground">طلب مرتبط</span><span class="truncate font-semibold">{{ selectedRequest.request_number }}</span></div>
                                    <div class="flex justify-between gap-3"><span class="text-muted-foreground">التاريخ والوقت</span><span class="font-semibold">{{ form.starts_at ? dayMonth(form.starts_at) + ' — ' + fmtTimeAr(form.starts_at) : '—' }}</span></div>
                                    <div v-if="form.customer_notes" class="flex justify-between gap-3"><span class="text-muted-foreground">ملاحظات</span><span class="text-left font-semibold">{{ form.customer_notes }}</span></div>
                                </div>
                            </div>
                            <p v-if="selectedType?.requires_approval" class="flex items-start gap-2 rounded-xl border border-warning/30 bg-warning/10 p-3 text-xs text-warning">
                                <Info class="size-4 shrink-0" /> هذا النوع يحتاج موافقة الفريق. سيصلك إشعار فور تأكيد الموعد.
                            </p>
                        </div>

                        <!-- Nav -->
                        <div class="flex items-center justify-between border-t border-border pt-4">
                            <Button type="button" variant="ghost" :disabled="step === 1" @click="back"><ChevronRight class="size-4" /> السابق</Button>
                            <Button v-if="step < 4" type="button" :disabled="!canNext" @click="next">التالي <ChevronLeft class="size-4" /></Button>
                            <Button v-else type="button" :disabled="form.processing || !form.starts_at" @click="submit">
                                <Check class="size-4" /> {{ form.processing ? 'جارٍ التأكيد…' : 'تأكيد الحجز' }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Summary sidebar -->
                <aside class="sticky top-4 hidden lg:block">
                    <Card class="border-primary/20">
                        <CardContent class="p-4">
                            <div class="mb-3 flex items-center gap-2 border-b border-border pb-3">
                                <Sparkles class="size-4 text-primary" />
                                <div class="text-sm font-bold">ملخص الحجز</div>
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <div class="mb-0.5 flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider">
                                        <span :class="['size-1.5 rounded-full', form.type_id ? 'bg-success' : 'bg-muted-foreground/30']"></span>
                                        <span :class="form.type_id ? 'text-success' : 'text-muted-foreground'">نوع الموعد</span>
                                    </div>
                                    <div :class="['pr-3.5 text-xs font-semibold', selectedType ? 'text-foreground' : 'text-muted-foreground/60']">{{ selectedType?.name_ar || 'لم يُحدّد بعد' }}</div>
                                </div>
                                <div>
                                    <div class="mb-0.5 flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider">
                                        <span :class="['size-1.5 rounded-full', reasonValid ? 'bg-success' : 'bg-muted-foreground/30']"></span>
                                        <span :class="reasonValid ? 'text-success' : 'text-muted-foreground'">السبب</span>
                                    </div>
                                    <div :class="['pr-3.5 text-xs font-semibold', selectedReason ? 'text-foreground' : 'text-muted-foreground/60']">{{ selectedReason?.label || 'لم يُحدّد بعد' }}</div>
                                </div>
                                <div v-if="selectedType?.requires_request_link">
                                    <div class="mb-0.5 flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider">
                                        <span :class="['size-1.5 rounded-full', form.related_request_id ? 'bg-success' : 'bg-muted-foreground/30']"></span>
                                        <span :class="form.related_request_id ? 'text-success' : 'text-muted-foreground'">طلب مرتبط</span>
                                    </div>
                                    <div :class="['pr-3.5 text-xs font-semibold', selectedRequest ? 'text-foreground' : 'text-muted-foreground/60']">{{ selectedRequest?.request_number || 'لم يُحدّد بعد' }}</div>
                                </div>
                                <div>
                                    <div class="mb-0.5 flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider">
                                        <span :class="['size-1.5 rounded-full', form.starts_at ? 'bg-success' : 'bg-muted-foreground/30']"></span>
                                        <span :class="form.starts_at ? 'text-success' : 'text-muted-foreground'">التاريخ والوقت</span>
                                    </div>
                                    <div :class="['pr-3.5 text-xs font-semibold', form.starts_at ? 'text-foreground' : 'text-muted-foreground/60']">{{ form.starts_at ? shortDate(form.starts_at) + ' — ' + fmtTimeAr(form.starts_at) : 'لم يُحدّد بعد' }}</div>
                                </div>
                            </div>
                            <div class="mt-4 flex items-start gap-1.5 border-t border-border pt-3 text-[11px] leading-relaxed text-muted-foreground">
                                <Info class="mt-0.5 size-3 shrink-0 text-primary" />
                                الأوقات المعروضة محدّثة. يمكنك إعادة الجدولة أو الإلغاء لاحقاً.
                            </div>
                        </CardContent>
                    </Card>
                </aside>
            </div>
        </div>
    </AppShell>
</template>
