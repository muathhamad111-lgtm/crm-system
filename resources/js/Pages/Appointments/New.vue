<script setup>
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Label from '@/Components/ui/Label.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import Badge from '@/Components/ui/Badge.vue';
import Select from '@/Components/ui/Select.vue';
import { fmtDateAr, fmtTimeAr, fmtFullDateTimeAr } from '@/lib/date';
import {
    CalendarDays, Phone, Video, MapPin, GraduationCap, Workflow, HelpCircle,
    Check, CheckCircle2, Info, Clock, Sunrise, Sun, Moon, Sparkles, ChevronLeft, Link2,
} from 'lucide-vue-next';

const props = defineProps({
    types: { type: Array, default: () => [] },
    reasons: { type: Array, default: () => [] },
    openRequests: { type: Array, default: () => [] },
    slotsByType: { type: Object, default: () => ({}) },
});

const MODE_ICON = { phone: Phone, video: Video, onsite: MapPin, training: GraduationCap, followup: Workflow, other: HelpCircle };
const modeIcon = (m) => MODE_ICON[m] ?? HelpCircle;

const STEPS = [
    { n: 1, l: 'النوع', icon: Sparkles },
    { n: 2, l: 'السبب', icon: Info },
    { n: 3, l: 'الوقت', icon: CalendarDays },
    { n: 4, l: 'تأكيد', icon: CheckCircle2 },
];

const step = ref(1);

const form = useForm({
    type_id: '',
    reason_code: '',
    reason_other: '',
    related_request_id: '',
    starts_at: '',
    customer_notes: '',
});

const selectedType = computed(() => props.types.find((t) => t.id === form.type_id) ?? null);
const selectedReason = computed(() => props.reasons.find((r) => r.value === form.reason_code) ?? null);
const slots = computed(() => props.slotsByType[form.type_id] ?? { morning: [], afternoon: [], evening: [] });
const hasSlots = computed(() => (slots.value.morning.length + slots.value.afternoon.length + slots.value.evening.length) > 0);

const GROUPS = [
    { key: 'morning', label: 'صباحاً', icon: Sunrise },
    { key: 'afternoon', label: 'ظهراً', icon: Sun },
    { key: 'evening', label: 'مساءً', icon: Moon },
];

const canNext = computed(() => {
    if (step.value === 1) return !!form.type_id;
    if (step.value === 2) {
        if (!form.reason_code) return false;
        if (form.reason_code === 'other' && !form.reason_other.trim()) return false;
        if (selectedType.value?.requires_request_link && !form.related_request_id) return false;
        return true;
    }
    if (step.value === 3) return !!form.starts_at;
    return true;
});

function pickType(id) {
    form.type_id = id;
    form.starts_at = '';
}
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
        <div class="glass-stage space-y-6">
            <PageHero title="حجز موعد جديد" subtitle="اختر النوع والسبب والوقت المناسب لك" :icon="CalendarDays" />

            <!-- Stepper -->
            <div class="flex items-center justify-between gap-2">
                <template v-for="(s, i) in STEPS" :key="s.n">
                    <div class="flex flex-1 flex-col items-center gap-1.5">
                        <div :class="['flex size-10 items-center justify-center rounded-full border-2 transition-colors',
                            step >= s.n ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-card text-muted-foreground']">
                            <Check v-if="step > s.n" class="size-5" />
                            <component :is="s.icon" v-else class="size-5" />
                        </div>
                        <span :class="['text-xs font-medium', step >= s.n ? 'text-foreground' : 'text-muted-foreground']">{{ s.l }}</span>
                    </div>
                    <div v-if="i < STEPS.length - 1" :class="['h-0.5 flex-1', step > s.n ? 'bg-primary' : 'bg-border']"></div>
                </template>
            </div>

            <Card>
                <CardContent class="p-6">
                    <!-- Step 1: type -->
                    <div v-if="step === 1" class="grid gap-3 sm:grid-cols-2">
                        <button v-for="t in types" :key="t.id" type="button" @click="pickType(t.id)"
                            :class="['flex items-start gap-3 rounded-xl border p-4 text-start transition-colors',
                                form.type_id === t.id ? 'border-primary bg-primary-soft' : 'border-border bg-card hover:bg-muted/40']">
                            <div class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-primary-soft text-primary">
                                <component :is="modeIcon(t.mode)" class="size-5" />
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-foreground">{{ t.name_ar }}</p>
                                <p v-if="t.description" class="mt-0.5 text-xs text-muted-foreground line-clamp-2">{{ t.description }}</p>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    <Badge variant="muted"><Clock class="size-3" /> {{ t.duration_minutes }} دقيقة</Badge>
                                    <Badge v-if="t.requires_request_link" variant="warning">يتطلب ربط طلب</Badge>
                                    <Badge v-if="t.requires_approval" variant="outline">يتطلب تأكيد</Badge>
                                </div>
                            </div>
                        </button>
                    </div>

                    <!-- Step 2: reason + linked request -->
                    <div v-else-if="step === 2" class="space-y-5">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <button v-for="r in reasons" :key="r.value" type="button" @click="form.reason_code = r.value"
                                :class="['rounded-xl border p-4 text-start transition-colors',
                                    form.reason_code === r.value ? 'border-primary bg-primary-soft' : 'border-border bg-card hover:bg-muted/40']">
                                <p class="font-bold text-foreground">{{ r.label }}</p>
                                <p class="mt-0.5 text-xs text-muted-foreground">{{ r.desc }}</p>
                            </button>
                        </div>

                        <div v-if="form.reason_code === 'other'">
                            <Label class="mb-1.5 block">اكتب السبب</Label>
                            <Textarea v-model="form.reason_other" placeholder="وضّح سبب الموعد..." />
                        </div>

                        <div v-if="openRequests.length || selectedType?.requires_request_link">
                            <Label class="mb-1.5 flex items-center gap-1.5">
                                <Link2 class="size-4" /> ربط بطلب قائم
                                <span v-if="selectedType?.requires_request_link" class="text-destructive">*</span>
                                <span v-else class="text-xs text-muted-foreground">(اختياري)</span>
                            </Label>
                            <Select v-model="form.related_request_id">
                                <option value="">بدون ربط</option>
                                <option v-for="r in openRequests" :key="r.id" :value="r.id">{{ r.request_number }} — {{ r.title }}</option>
                            </Select>
                        </div>
                    </div>

                    <!-- Step 3: slot pick -->
                    <div v-else-if="step === 3" class="space-y-5">
                        <p class="text-sm text-muted-foreground">اختر الوقت المناسب خلال الأيام الأربعة عشر القادمة.</p>
                        <div v-if="hasSlots" class="space-y-5">
                            <div v-for="g in GROUPS" :key="g.key" v-show="slots[g.key].length">
                                <p class="mb-2 flex items-center gap-2 text-sm font-bold text-foreground">
                                    <component :is="g.icon" class="size-4 text-primary" /> {{ g.label }}
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <button v-for="s in slots[g.key]" :key="s.starts_at" type="button" @click="form.starts_at = s.starts_at"
                                        :class="['rounded-lg border px-3 py-2 text-sm tabular-nums transition-colors',
                                            form.starts_at === s.starts_at ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-card hover:bg-muted/40']">
                                        <span class="block text-xs opacity-80">{{ fmtDateAr(s.starts_at) }}</span>
                                        {{ fmtTimeAr(s.starts_at) }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <Card v-else class="border-dashed">
                            <CardContent class="py-10 text-center text-sm text-muted-foreground">
                                لا توجد أوقات متاحة حالياً لهذا النوع. يُرجى المحاولة لاحقاً.
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Step 4: confirm -->
                    <div v-else class="space-y-4">
                        <div class="rounded-xl border border-border bg-muted/30 p-5 space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="flex size-11 items-center justify-center rounded-lg bg-primary-soft text-primary">
                                    <component :is="modeIcon(selectedType?.mode)" class="size-5" />
                                </div>
                                <div>
                                    <p class="font-bold text-foreground">{{ selectedType?.name_ar }}</p>
                                    <p class="text-xs text-muted-foreground">{{ selectedReason?.label }}</p>
                                </div>
                            </div>
                            <div class="grid gap-2 text-sm sm:grid-cols-2">
                                <p class="flex items-center gap-2"><CalendarDays class="size-4 text-muted-foreground" /> {{ fmtFullDateTimeAr(form.starts_at) }}</p>
                                <p class="flex items-center gap-2"><Clock class="size-4 text-muted-foreground" /> {{ selectedType?.duration_minutes }} دقيقة</p>
                            </div>
                        </div>

                        <div>
                            <Label class="mb-1.5 block">ملاحظات إضافية (اختياري)</Label>
                            <Textarea v-model="form.customer_notes" placeholder="أي تفاصيل تريد مشاركتها مع الفريق..." />
                        </div>

                        <p v-if="selectedType?.requires_approval" class="flex items-center gap-2 rounded-lg bg-warning/10 p-3 text-sm text-warning">
                            <Info class="size-4" /> سيبقى الموعد بانتظار تأكيد الفريق.
                        </p>
                    </div>

                    <!-- Nav -->
                    <div class="mt-6 flex items-center justify-between">
                        <Button v-if="step > 1" type="button" variant="outline" @click="back">السابق</Button>
                        <span v-else></span>
                        <Button v-if="step < 4" type="button" :disabled="!canNext" @click="next">
                            التالي <ChevronLeft class="size-4" />
                        </Button>
                        <Button v-else type="button" :disabled="form.processing || !form.starts_at" @click="submit">
                            <CheckCircle2 class="size-4" /> تأكيد الحجز
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppShell>
</template>
