<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Input from '@/Components/ui/Input.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import Label from '@/Components/ui/Label.vue';
import Select from '@/Components/ui/Select.vue';
import Button from '@/Components/ui/Button.vue';
import InputError from '@/Components/InputError.vue';
import { Lightbulb, ArrowRight, Check } from 'lucide-vue-next';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
});

const form = useForm({
    category_id: '',
    sub_category_id: '',
    product_id: '',
    title: '',
    description: '',
});

const selected = computed(() => props.categories.find((c) => c.id === form.category_id));
const descLen = computed(() => form.description.trim().length);

const steps = computed(() => [
    { label: 'التصنيف', done: !!form.category_id, value: selected.value?.name_ar ?? 'اختر تصنيفاً' },
    { label: 'العنوان', done: form.title.trim().length > 0, value: form.title.trim() ? `${form.title.trim().length} حرف` : 'غير مكتمل' },
    { label: 'الوصف', done: descLen.value >= 10, value: descLen.value > 0 ? `${descLen.value} حرف` : 'غير مكتمل' },
]);
const completed = computed(() => steps.value.filter((s) => s.done).length);

function submit() {
    form.post(route('suggestions.store'));
}
</script>

<template>
    <Head title="مقترح جديد" />
    <AppShell>
        <div class="space-y-4">
            <!-- Gradient hero with step chips -->
            <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-elevated" style="background-image: var(--gradient-hero);">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(closest-side at 75% 20%, rgba(255,255,255,.4), transparent);"></div>
                <div class="relative flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs text-white/60">مقترح جديد · صوتك يصنع الفرق</p>
                        <h1 class="mt-1 text-2xl font-bold">إرسال مقترح</h1>
                        <p class="mt-1 max-w-xl text-sm text-white/80">شاركنا فكرتك أو اقتراحك لتحسين الخدمة — كل مقترح يُراجَع بعناية.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-white/15 px-3 py-1.5 text-xs tabular-nums backdrop-blur-sm">{{ completed }}/{{ steps.length }} خطوات</span>
                        <Button :href="route('suggestions.mine')" class="border border-white/15 bg-white/10 text-white hover:bg-white/20 backdrop-blur-sm">
                            <ArrowRight class="size-4" /> رجوع للمقترحات
                        </Button>
                    </div>
                </div>
                <div class="relative mt-5 flex flex-wrap gap-2">
                    <div v-for="(st, i) in steps" :key="i"
                        :class="['flex items-center gap-2 rounded-xl border px-3 py-1.5 text-sm backdrop-blur-sm', st.done ? 'border-success/40 bg-success/20' : 'border-white/15 bg-white/10']">
                        <span :class="['flex size-5 items-center justify-center rounded-md text-[10px] font-bold', st.done ? 'bg-success text-success-foreground' : 'bg-white/20']">
                            <Check v-if="st.done" class="size-3" /><template v-else>{{ i + 1 }}</template>
                        </span>
                        <span class="text-[13px]"><span class="font-semibold">{{ st.label }}</span> · <span class="text-white/80">{{ st.value }}</span></span>
                    </div>
                </div>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <!-- Category -->
                <Card>
                    <div class="border-b border-border bg-gradient-to-l from-primary/[0.05] to-transparent p-5">
                        <div class="flex items-start gap-3">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-primary to-primary/80 text-sm font-bold text-primary-foreground shadow-sm">01</div>
                            <div>
                                <div class="text-[10px] font-bold uppercase tracking-[0.18em] text-primary/80">CLASSIFICATION</div>
                                <h2 class="text-lg font-bold text-foreground">تصنيف المقترح</h2>
                                <p class="mt-0.5 text-xs text-muted-foreground">اختر التصنيف الأنسب لمقترحك — يساعد الفريق المختص على مراجعته بسرعة.</p>
                            </div>
                        </div>
                    </div>
                    <CardContent class="pt-5">
                        <div v-if="categories.length" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <button v-for="c in categories" :key="c.id" type="button"
                                @click="form.category_id = c.id"
                                class="relative overflow-hidden rounded-2xl border p-4 text-right transition-all hover:-translate-y-0.5"
                                :class="form.category_id === c.id ? 'border-primary bg-primary-soft shadow-md' : 'border-border bg-card hover:border-primary/40'">
                                <span class="absolute inset-x-0 top-0 h-[3px]" :style="{ background: c.color || 'var(--primary)', opacity: form.category_id === c.id ? 1 : 0.25 }"></span>
                                <span v-if="form.category_id === c.id" class="absolute left-3 top-3 flex size-5 items-center justify-center rounded-full text-white" :style="{ background: c.color || 'var(--primary)' }">
                                    <Check class="size-3" />
                                </span>
                                <div class="flex items-start gap-3">
                                    <div class="flex size-11 shrink-0 items-center justify-center rounded-2xl text-white" :style="{ background: c.color || 'var(--primary)' }">
                                        <Lightbulb class="size-5" />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="mb-1 font-bold text-foreground">{{ c.name_ar }}</div>
                                        <div v-if="c.description_ar" class="line-clamp-2 text-xs leading-relaxed text-muted-foreground">{{ c.description_ar }}</div>
                                    </div>
                                </div>
                            </button>
                        </div>
                        <p v-else class="py-8 text-center text-sm text-muted-foreground">لا توجد تصنيفات متاحة للمقترحات حالياً.</p>
                        <InputError class="mt-2" :message="form.errors.category_id" />
                    </CardContent>
                </Card>

                <!-- Details -->
                <Card>
                    <div class="border-b border-border bg-gradient-to-l from-accent/[0.05] to-transparent p-5">
                        <div class="flex items-start gap-3">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-accent to-accent/80 text-sm font-bold text-accent-foreground shadow-sm">02</div>
                            <div>
                                <div class="text-[10px] font-bold uppercase tracking-[0.18em] text-accent/90">DETAILS</div>
                                <h2 class="text-lg font-bold text-foreground">تفاصيل المقترح</h2>
                                <p class="mt-0.5 text-xs text-muted-foreground">قدّم وصفاً واضحاً يوضح فكرتك والأثر المتوقع من تطبيقها.</p>
                            </div>
                        </div>
                    </div>
                    <CardContent class="space-y-5 pt-5">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="space-y-1.5 md:col-span-2">
                                <Input label="عنوان المقترح *" v-model="form.title" maxlength="200" />
                                <div class="flex justify-between text-xs text-muted-foreground">
                                    <span>اكتب عنواناً وصفياً بين 10-200 حرف</span>
                                    <span class="font-mono tabular-nums">{{ form.title.length }}/200</span>
                                </div>
                                <InputError :message="form.errors.title" />
                            </div>
                            <div class="space-y-1.5">
                                <Select label="المنتج / الخدمة" v-model="form.product_id">
                                    <option value="">— لا ينطبق —</option>
                                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name_ar }} ({{ p.type === 'service' ? 'خدمة' : 'منتج' }})</option>
                                </Select>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <Textarea label="الوصف التفصيلي *" v-model="form.description" class="min-h-[220px] leading-relaxed" />
                            <div class="flex justify-between text-xs text-muted-foreground">
                                <span>الحد الأدنى 10 أحرف — كلما زادت التفاصيل، أسرعت المراجعة</span>
                                <span class="font-mono tabular-nums">{{ descLen }} حرف</span>
                            </div>
                            <InputError :message="form.errors.description" />
                        </div>
                    </CardContent>
                </Card>

                <div class="flex items-center justify-between gap-3 rounded-2xl border border-border bg-gradient-to-l from-primary/[0.03] to-transparent p-4">
                    <div class="hidden items-center gap-2.5 text-sm md:flex">
                        <div class="flex size-9 items-center justify-center rounded-xl bg-primary/10 text-primary"><Check class="size-4" /></div>
                        <div>
                            <div class="font-semibold leading-tight text-foreground">جاهز للإرسال؟</div>
                            <div class="text-xs text-muted-foreground">{{ completed }}/{{ steps.length }} خطوات مكتملة — راجع المعلومات قبل الإرسال.</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 ms-auto">
                        <Button :href="route('suggestions.mine')" variant="outline">إلغاء</Button>
                        <Button type="submit" :disabled="form.processing" size="lg">{{ form.processing ? 'جارٍ الإرسال...' : 'إرسال المقترح' }}</Button>
                    </div>
                </div>
            </form>
        </div>
    </AppShell>
</template>
