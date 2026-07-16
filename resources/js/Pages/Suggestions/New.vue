<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
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

const descLen = computed(() => form.description.trim().length);

function submit() {
    form.post(route('suggestions.store'));
}
</script>

<template>
    <Head title="مقترح جديد" />
    <AppShell>
        <div class="space-y-6">
            <PageHero
                title="إرسال مقترح"
                subtitle="شاركنا فكرتك أو اقتراحك لتحسين الخدمة — كل مقترح يُراجَع بعناية"
                :icon="Lightbulb">
                <template #actions>
                    <Button :href="route('suggestions.mine')" variant="outline" size="sm">
                        <ArrowRight class="size-4" /> رجوع للمقترحات
                    </Button>
                </template>
            </PageHero>

            <form class="space-y-6" @submit.prevent="submit">
                <!-- Category -->
                <Card>
                    <CardHeader>
                        <CardTitle>تصنيف المقترح</CardTitle>
                        <p class="text-sm text-muted-foreground">اختر التصنيف الأنسب لمقترحك — يساعد الفريق المختص على مراجعته بسرعة</p>
                    </CardHeader>
                    <CardContent>
                        <div v-if="categories.length" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <button v-for="c in categories" :key="c.id" type="button"
                                @click="form.category_id = c.id"
                                class="relative rounded-2xl border p-4 text-right transition-all"
                                :class="form.category_id === c.id
                                    ? 'border-primary bg-primary-soft'
                                    : 'border-border bg-card hover:border-primary/40'">
                                <span v-if="form.category_id === c.id"
                                    class="absolute left-3 top-3 flex size-5 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                    <Check class="size-3" />
                                </span>
                                <div class="mb-1 font-bold text-foreground">{{ c.name_ar }}</div>
                                <div v-if="c.description_ar" class="line-clamp-2 text-xs leading-relaxed text-muted-foreground">
                                    {{ c.description_ar }}
                                </div>
                            </button>
                        </div>
                        <p v-else class="py-8 text-center text-sm text-muted-foreground">
                            لا توجد تصنيفات متاحة للمقترحات حالياً.
                        </p>
                        <InputError class="mt-2" :message="form.errors.category_id" />
                    </CardContent>
                </Card>

                <!-- Details -->
                <Card>
                    <CardHeader>
                        <CardTitle>تفاصيل المقترح</CardTitle>
                        <p class="text-sm text-muted-foreground">قدّم وصفاً واضحاً يوضح فكرتك والأثر المتوقع من تطبيقها</p>
                    </CardHeader>
                    <CardContent class="space-y-5">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="space-y-1.5 md:col-span-2">
                                <Label>عنوان المقترح <span class="text-accent">*</span></Label>
                                <Input v-model="form.title" maxlength="200" placeholder="ملخص موجز وواضح للفكرة" />
                                <div class="flex justify-between text-xs text-muted-foreground">
                                    <span>اكتب عنواناً وصفياً بين 10-200 حرف</span>
                                    <span class="font-mono tabular-nums">{{ form.title.length }}/200</span>
                                </div>
                                <InputError :message="form.errors.title" />
                            </div>
                            <div class="space-y-1.5">
                                <Label>المنتج / الخدمة</Label>
                                <Select v-model="form.product_id">
                                    <option value="">— لا ينطبق —</option>
                                    <option v-for="p in products" :key="p.id" :value="p.id">
                                        {{ p.name_ar }} ({{ p.type === 'service' ? 'خدمة' : 'منتج' }})
                                    </option>
                                </Select>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <Label>الوصف التفصيلي <span class="text-accent">*</span></Label>
                            <Textarea v-model="form.description" class="min-h-[220px] leading-relaxed"
                                placeholder="اشرح مقترحك بالتفصيل... اذكر المشكلة الحالية، الفكرة المقترحة، الأثر المتوقع، وأي تفاصيل تفيد التقييم" />
                            <div class="flex justify-between text-xs text-muted-foreground">
                                <span>الحد الأدنى 10 أحرف — كلما زادت التفاصيل، أسرعت المراجعة</span>
                                <span class="font-mono tabular-nums">{{ descLen }} حرف</span>
                            </div>
                            <InputError :message="form.errors.description" />
                        </div>
                    </CardContent>
                </Card>

                <div class="flex items-center justify-end gap-2">
                    <Button :href="route('suggestions.mine')" variant="outline">إلغاء</Button>
                    <Button type="submit" :disabled="form.processing" size="lg">
                        {{ form.processing ? 'جارٍ الإرسال...' : 'إرسال المقترح' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppShell>
</template>
