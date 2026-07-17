<script setup>
import { computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import Label from '@/Components/ui/Label.vue';
import Select from '@/Components/ui/Select.vue';
import {
    Tag, Check, ArrowRight, CheckCircle2, ShieldAlert, X,
    MessageSquareWarning, FolderOpen, Heart, Package, Workflow,
    GraduationCap, Plug, CircleHelp, Bug, Sparkles, Settings,
} from 'lucide-vue-next';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    customers: { type: Array, default: () => [] },
    channels: { type: Array, default: () => [] },
    isStaff: { type: Boolean, default: false },
});

const ICONS = {
    Tag, MessageSquareWarning, FolderOpen, Heart, Package, Workflow,
    GraduationCap, Plug, CircleHelp, Bug, Sparkles, Settings,
};
const catIcon = (name) => ICONS[name] ?? Tag;

const form = useForm({
    category_id: '',
    sub_category_id: '',
    product_id: '',
    title: '',
    description: '',
    customer_id: '',
    channel: 'portal',
    fields: {},
});

const currentCategory = computed(() => props.categories.find((c) => c.id === form.category_id));

// Reset the sub-category whenever the category changes.
function pickCategory(id) {
    form.category_id = form.category_id === id ? '' : id;
    form.sub_category_id = '';
    form.fields = {};
}

// --- Progress chips (mirror Lovable's executive header) ---
const descLen = computed(() => form.description.trim().length);
const steps = computed(() => [
    { label: 'التصنيف', done: !!form.category_id, value: currentCategory.value?.name_ar ?? 'اختر تصنيفاً' },
    { label: 'العنوان', done: form.title.trim().length >= 5, value: form.title.trim() ? `${form.title.trim().length} حرف` : 'غير مكتمل' },
    { label: 'الوصف', done: descLen.value >= 10, value: descLen.value > 0 ? `${descLen.value} حرف` : 'غير مكتمل' },
]);
const completedSteps = computed(() => steps.value.filter((s) => s.done).length);
const canSubmit = computed(() =>
    !!form.category_id && form.title.trim().length >= 5 && descLen.value >= 10 && (!props.isStaff || !!form.customer_id));

const selectedCustomer = computed(() => props.customers.find((c) => c.id === form.customer_id));
const selectedProduct = computed(() => props.products.find((p) => p.id === form.product_id));
const selectedChannel = computed(() => props.channels.find((c) => c.key === form.channel));

function submit() {
    form.post('/requests');
}
</script>

<template>
    <Head title="طلب جديد" />
    <AppShell>
        <div class="space-y-4">
            <!-- Gradient hero with live progress chips -->
            <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-elevated" style="background-image: var(--gradient-hero);">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(closest-side at 75% 20%, rgba(255,255,255,.4), transparent);"></div>
                <div class="relative flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs text-white/60">{{ isStaff ? 'إنشاء داخلي · نيابةً عن عميل' : 'طلب جديد · ابدأ الخدمة' }}</p>
                        <h1 class="mt-1 text-2xl font-bold">{{ isStaff ? 'إضافة طلب نيابةً عن العميل' : 'طلب جديد' }}</h1>
                        <p class="mt-1 max-w-xl text-sm text-white/80">{{ isStaff ? 'اختر العميل ثم التصنيف وعبّئ التفاصيل.' : 'اختر التصنيف المناسب وقم بتعبئة التفاصيل.' }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="hidden items-center gap-2 rounded-full bg-white/15 px-3 py-1.5 text-xs backdrop-blur-sm tabular-nums md:inline-flex">
                            <CheckCircle2 class="size-3.5" /> {{ completedSteps }}/{{ steps.length }} خطوات مكتملة
                        </span>
                        <Button variant="ghost" class="bg-white/10 text-white hover:bg-white/20" @click="router.visit('/requests')">
                            <ArrowRight class="size-4" /> رجوع للطلبات
                        </Button>
                    </div>
                </div>
                <div class="relative mt-5 flex flex-wrap items-center gap-1.5">
                    <div v-for="(s, i) in steps" :key="i"
                        :class="['flex items-center gap-2 rounded-lg border px-2.5 py-1.5 backdrop-blur-sm transition-colors', s.done ? 'border-success/40 bg-success/20' : 'border-white/15 bg-white/10']">
                        <span :class="['flex size-5 items-center justify-center rounded-md', s.done ? 'bg-success text-success-foreground' : 'bg-white/20 text-white']">
                            <Check v-if="s.done" class="size-3" :stroke-width="3" />
                            <span v-else class="text-[10px] font-bold">{{ i + 1 }}</span>
                        </span>
                        <span class="text-[11px]"><span class="font-semibold">{{ s.label }}</span> <span class="text-white/70">·</span> <span class="text-white/80">{{ s.value }}</span></span>
                    </div>
                </div>
            </div>

            <!-- Staff: on-behalf notice + customer picker + channel -->
            <template v-if="isStaff">
                <div class="flex items-start gap-3 rounded-xl border-2 border-warning/40 bg-warning/10 p-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-warning/20"><ShieldAlert class="size-5 text-warning" /></div>
                    <div class="text-sm">
                        <div class="mb-1 font-semibold text-foreground">إنشاء طلب نيابةً عن العميل</div>
                        <div class="text-muted-foreground">سيُسجَّل هذا الطلب باسم العميل المختار وسيظهر ضمن «طلباتي» لديه، مع إشعار فوري له.</div>
                    </div>
                </div>
                <Card>
                    <CardHeader><CardTitle>العميل والقناة</CardTitle></CardHeader>
                    <CardContent class="grid gap-3 md:grid-cols-2">
                        <div>
                            <Select label="العميل *" v-model="form.customer_id">
                                <option value=""></option>
                                <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.full_name }} — {{ c.email }}</option>
                            </Select>
                            <p v-if="form.errors.customer_id" class="mt-1 text-xs text-destructive">{{ form.errors.customer_id }}</p>
                        </div>
                        <Select label="قناة استلام الطلب" v-model="form.channel">
                            <option v-for="ch in channels" :key="ch.key" :value="ch.key">{{ ch.icon }} {{ ch.label }}</option>
                        </Select>
                    </CardContent>
                </Card>
            </template>

            <!-- Category picker as rich cards -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between gap-2">
                    <div>
                        <CardTitle>تصنيف الطلب</CardTitle>
                        <p class="mt-1 text-xs text-muted-foreground">اختر التصنيف الأنسب لطلبك لتحديد المسار المناسب والفريق المختص.</p>
                    </div>
                    <span v-if="currentCategory" class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold text-white shadow-sm"
                        :style="{ background: `linear-gradient(135deg, ${currentCategory.color}, ${currentCategory.color}cc)` }">
                        <Check class="size-3" :stroke-width="3" /> {{ currentCategory.name_ar }}
                    </span>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
                        <button v-for="c in categories" :key="c.id" type="button" @click="pickCategory(c.id)"
                            class="group relative overflow-hidden rounded-2xl border p-4 text-right transition-all duration-200"
                            :class="form.category_id === c.id ? 'border-transparent shadow-lg -translate-y-0.5' : 'border-border/70 bg-card hover:border-primary/30 hover:shadow-md hover:-translate-y-0.5'"
                            :style="form.category_id === c.id ? { background: `linear-gradient(140deg, ${c.color}12, ${c.color}05 60%, transparent)`, boxShadow: `0 12px 32px -16px ${c.color}80, inset 0 0 0 1.5px ${c.color}55` } : {}">
                            <span class="absolute inset-x-0 top-0 h-1 transition-opacity" :style="{ background: c.color, opacity: form.category_id === c.id ? 1 : 0.25 }"></span>
                            <span v-if="form.category_id === c.id" class="absolute left-2 top-2 flex size-5 items-center justify-center rounded-full text-white shadow" :style="{ background: c.color }">
                                <Check class="size-3" :stroke-width="3" />
                            </span>
                            <div class="flex items-start gap-3">
                                <div class="flex size-11 shrink-0 items-center justify-center rounded-xl text-white shadow-sm transition-transform group-hover:scale-105"
                                    :style="{ background: `linear-gradient(135deg, ${c.color}, ${c.color}cc)`, boxShadow: `0 4px 12px -4px ${c.color}80` }">
                                    <component :is="catIcon(c.icon)" class="size-5" :stroke-width="2.1" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="mb-1 text-sm font-bold leading-tight">{{ c.name_ar }}</div>
                                    <div v-if="c.description_ar" class="line-clamp-3 text-[11px] leading-snug text-muted-foreground">{{ c.description_ar }}</div>
                                </div>
                            </div>
                        </button>
                    </div>
                    <p v-if="form.errors.category_id" class="mt-2 text-xs text-destructive">{{ form.errors.category_id }}</p>

                    <!-- Sub-category chips -->
                    <div v-if="currentCategory?.sub_categories?.length" class="mt-4 border-t border-dashed border-border/70 pt-4">
                        <div class="mb-2.5 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <div class="flex size-7 items-center justify-center rounded-lg text-white shadow-sm" :style="{ background: `linear-gradient(135deg, ${currentCategory.color}, ${currentCategory.color}cc)` }">
                                    <Tag class="size-3.5" />
                                </div>
                                <div>
                                    <div class="text-sm font-semibold leading-tight">التصنيف الفرعي</div>
                                    <div class="text-[11px] leading-tight text-muted-foreground">يُحسّن التوجيه ودقة التقارير (اختياري)</div>
                                </div>
                            </div>
                            <button v-if="form.sub_category_id" type="button" class="inline-flex items-center gap-1 text-[11px] text-muted-foreground transition-colors hover:text-foreground" @click="form.sub_category_id = ''">
                                <X class="size-3" /> مسح
                            </button>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <button v-for="s in currentCategory.sub_categories" :key="s.id" type="button"
                                @click="form.sub_category_id = form.sub_category_id === s.id ? '' : s.id"
                                class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-medium transition-all"
                                :class="form.sub_category_id === s.id ? 'border-transparent text-white shadow-md -translate-y-0.5' : 'border-border/70 bg-card text-foreground hover:border-primary/40 hover:bg-primary/5'"
                                :style="form.sub_category_id === s.id ? { background: `linear-gradient(135deg, ${currentCategory.color}, ${currentCategory.color}cc)` } : {}">
                                <Check v-if="form.sub_category_id === s.id" class="size-3" :stroke-width="3" />
                                {{ s.name_ar }}
                            </button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Details -->
            <Card>
                <CardHeader><CardTitle>تفاصيل الطلب</CardTitle></CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="md:col-span-2">
                            <Input label="عنوان الطلب *" v-model="form.title" />
                            <div class="mt-1 flex items-center justify-between text-[11px] text-muted-foreground">
                                <span>اكتب عنواناً وصفياً بين 5–200 حرف</span>
                                <span class="tabular-nums">{{ form.title.length }}/200</span>
                            </div>
                            <p v-if="form.errors.title" class="mt-1 text-xs text-destructive">{{ form.errors.title }}</p>
                        </div>
                        <div v-if="products.length">
                            <Select label="المنتج / الخدمة" v-model="form.product_id">
                                <option value="">— لا ينطبق —</option>
                                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name_ar }} ({{ p.type === 'service' ? 'خدمة' : 'منتج' }})</option>
                            </Select>
                        </div>
                    </div>

                    <div>
                        <Textarea label="الوصف التفصيلي *" v-model="form.description" class="min-h-40" />
                        <div class="mt-1 flex items-center justify-between text-[11px] text-muted-foreground">
                            <span>الحد الأدنى 10 أحرف — كلما زادت التفاصيل، أسرعت المعالجة</span>
                            <span class="tabular-nums">{{ descLen }} حرف</span>
                        </div>
                        <p v-if="form.errors.description" class="mt-1 text-xs text-destructive">{{ form.errors.description }}</p>
                    </div>

                    <!-- Dynamic per-category fields (floating labels) -->
                    <template v-if="currentCategory?.fields?.length">
                        <div class="flex items-center gap-2 pt-1">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">حقول إضافية</span>
                            <span class="h-px flex-1 bg-gradient-to-l from-border to-transparent"></span>
                        </div>
                        <div v-for="f in currentCategory.fields" :key="f.id">
                            <p v-if="f.help_text && f.field_type !== 'checkbox'" class="mb-1 text-xs text-muted-foreground">{{ f.help_text }}</p>
                            <Textarea v-if="f.field_type === 'textarea'" :label="f.label + (f.required ? ' *' : '')" v-model="form.fields[f.id]" />
                            <Select v-else-if="f.field_type === 'select'" :label="f.label + (f.required ? ' *' : '')" v-model="form.fields[f.id]">
                                <option value=""></option>
                                <option v-for="opt in (f.options || [])" :key="opt.value ?? opt" :value="opt.value ?? opt">{{ opt.label ?? opt }}</option>
                            </Select>
                            <template v-else-if="f.field_type === 'checkbox'">
                                <Label>{{ f.label }}<span v-if="f.required" class="text-destructive"> *</span></Label>
                                <p v-if="f.help_text" class="mb-1 text-xs text-muted-foreground">{{ f.help_text }}</p>
                                <label class="mt-1 flex items-center gap-2 text-sm">
                                    <input type="checkbox" v-model="form.fields[f.id]" class="size-4 rounded border-input" /> نعم
                                </label>
                            </template>
                            <Input v-else :label="f.label + (f.required ? ' *' : '')"
                                :type="f.field_type === 'number' ? 'number' : (f.field_type === 'date' ? 'date' : 'text')"
                                v-model="form.fields[f.id]" />
                            <p v-if="form.errors[`fields.${f.id}`]" class="mt-1 text-xs text-destructive">{{ form.errors[`fields.${f.id}`] }}</p>
                        </div>
                    </template>
                </CardContent>
            </Card>

            <!-- Confirmation summary + submit -->
            <Card class="border-primary/20 bg-gradient-to-l from-primary-soft/40 to-transparent">
                <CardContent class="space-y-3 p-4">
                    <div class="flex items-center gap-2">
                        <CheckCircle2 class="size-4 text-primary" />
                        <span class="text-sm font-semibold">مراجعة قبل الإرسال</span>
                    </div>
                    <dl class="grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
                        <div v-if="isStaff" class="flex justify-between gap-2 rounded-lg bg-card/60 px-3 py-2">
                            <dt class="text-muted-foreground">العميل</dt>
                            <dd class="truncate font-medium">{{ selectedCustomer?.full_name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-2 rounded-lg bg-card/60 px-3 py-2">
                            <dt class="text-muted-foreground">التصنيف</dt>
                            <dd class="truncate font-medium">{{ currentCategory?.name_ar ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-2 rounded-lg bg-card/60 px-3 py-2">
                            <dt class="text-muted-foreground">العنوان</dt>
                            <dd class="max-w-[60%] truncate font-medium">{{ form.title || '—' }}</dd>
                        </div>
                        <div v-if="selectedProduct" class="flex justify-between gap-2 rounded-lg bg-card/60 px-3 py-2">
                            <dt class="text-muted-foreground">المنتج</dt>
                            <dd class="truncate font-medium">{{ selectedProduct.name_ar }}</dd>
                        </div>
                        <div v-if="isStaff" class="flex justify-between gap-2 rounded-lg bg-card/60 px-3 py-2">
                            <dt class="text-muted-foreground">قناة الاستلام</dt>
                            <dd class="truncate font-medium">{{ selectedChannel ? `${selectedChannel.icon} ${selectedChannel.label}` : '—' }}</dd>
                        </div>
                    </dl>
                    <div class="flex items-center justify-between gap-3 pt-1">
                        <span class="text-[11px] text-muted-foreground">{{ completedSteps }}/{{ steps.length }} خطوات مكتملة — تأكد من المعلومات قبل الإرسال</span>
                        <div class="flex items-center gap-2">
                            <Button variant="outline" @click="router.visit('/requests')">إلغاء</Button>
                            <Button :disabled="form.processing || !canSubmit" @click="submit">
                                {{ form.processing ? 'جارٍ الإرسال…' : 'إرسال الطلب' }}
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppShell>
</template>
