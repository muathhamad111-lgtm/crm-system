<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import Select from '@/Components/ui/Select.vue';
import { BookOpen, ArrowRight, X, Save, Send } from 'lucide-vue-next';

const props = defineProps({
    article: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
});

const editing = !!props.article;

const TYPE_OPTIONS = [
    ['faq', 'أسئلة شائعة'], ['sop', 'إجراء تشغيلي (SOP)'], ['known_issue', 'مشكلة معروفة'],
    ['resolution', 'حل جاهز'], ['macro', 'رد جاهز (Macro)'], ['policy', 'سياسة'], ['user_guide', 'دليل استخدام'],
];
const COMPLEXITY_OPTIONS = [['beginner', 'مبتدئ'], ['intermediate', 'متوسط'], ['advanced', 'متقدم']];

const keywords = ref(Array.isArray(props.article?.keywords) ? [...props.article.keywords] : []);
const kwInput = ref('');

const form = useForm({
    title: props.article?.title ?? '',
    summary: props.article?.summary ?? '',
    body: props.article?.body ?? '',
    type: props.article?.type ?? 'faq',
    complexity: props.article?.complexity ?? 'beginner',
    category_id: props.article?.category_id ?? '',
    product_id: props.article?.product_id ?? '',
    is_general: props.article?.is_general ?? false,
    keywords: '',
    prerequisites: props.article?.prerequisites ?? '',
    warnings: props.article?.warnings ?? '',
    status: 'draft',
});

function addKw() {
    const k = kwInput.value.trim();
    if (!k) return;
    if (!keywords.value.includes(k)) keywords.value.push(k);
    kwInput.value = '';
}
function removeKw(k) {
    keywords.value = keywords.value.filter((x) => x !== k);
}

function submit(status) {
    form.status = status;
    form.keywords = keywords.value.join(',');
    form
        .transform((data) => ({
            ...data,
            category_id: data.category_id || null,
            product_id: data.is_general ? null : (data.product_id || null),
        }));
    if (editing) {
        form.put(`/knowledge-base/${props.article.id}`);
    } else {
        form.post('/knowledge-base');
    }
}
</script>

<template>
    <Head :title="editing ? 'تعديل مقال' : 'مقال جديد'" />
    <AppShell>
        <div class="glass-stage space-y-6">
            <PageHero
                :title="editing ? 'تعديل المقال' : 'مقال جديد'"
                :subtitle="editing ? 'حدّث محتوى المقال — التغييرات تُحفظ مباشرة' : 'أنشئ مقالاً جديداً في قاعدة المعرفة — سيبدأ كمسودة قابلة للتحرير قبل الاعتماد'"
                :icon="BookOpen">
                <template #actions>
                    <Button href="/knowledge-base" variant="outline"
                        class="bg-white/12 border-white/25 text-white hover:bg-white/20">
                        <ArrowRight class="size-4" /> عودة
                    </Button>
                </template>
            </PageHero>

            <!-- Basic info -->
            <Card>
                <CardHeader><CardTitle class="text-base">المعلومات الأساسية</CardTitle></CardHeader>
                <CardContent class="space-y-4">
                    <div>
                        <Input label="العنوان *" v-model="form.title" />
                        <p v-if="form.errors.title" class="mt-1 text-xs text-destructive">{{ form.errors.title }}</p>
                    </div>
                    <Textarea label="ملخص مختصر" v-model="form.summary" :rows="2" />
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        <Select label="نوع المعرفة" v-model="form.type">
                            <option v-for="t in TYPE_OPTIONS" :key="t[0]" :value="t[0]">{{ t[1] }}</option>
                        </Select>
                        <Select label="مستوى التعقيد" v-model="form.complexity">
                            <option v-for="c in COMPLEXITY_OPTIONS" :key="c[0]" :value="c[0]">{{ c[1] }}</option>
                        </Select>
                        <div class="flex items-end pb-1">
                            <label class="flex cursor-pointer items-center gap-2 text-sm">
                                <input type="checkbox" v-model="form.is_general"
                                    class="size-4 rounded border-input accent-primary" />
                                <span>مقال عام لجميع المنتجات</span>
                            </label>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <Select label="المنتج" v-model="form.product_id" :disabled="form.is_general">
                                <option value=""></option>
                                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name_ar }}</option>
                            </Select>
                            <p v-if="form.is_general" class="mt-1 text-xs text-muted-foreground">(معطّل - مقال عام)</p>
                        </div>
                        <Select label="التصنيف الرئيسي" v-model="form.category_id">
                            <option value=""></option>
                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name_ar }}</option>
                        </Select>
                    </div>
                    <div>
                        <div class="flex gap-2">
                            <Input label="الكلمات المفتاحية" v-model="kwInput"
                                @keydown.enter.prevent="addKw" />
                            <Button type="button" variant="outline" @click="addKw">إضافة</Button>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <span v-for="k in keywords" :key="k"
                                class="inline-flex items-center gap-1 rounded-md bg-muted px-2 py-1 text-xs">
                                {{ k }}
                                <button type="button" class="hover:text-destructive" @click="removeKw(k)">
                                    <X class="size-3" />
                                </button>
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Content -->
            <Card>
                <CardHeader><CardTitle class="text-base">المحتوى</CardTitle></CardHeader>
                <CardContent class="space-y-4">
                    <Textarea label="المتطلبات المسبقة" v-model="form.prerequisites" :rows="2" />
                    <Textarea label="التحذيرات والتنبيهات" v-model="form.warnings" :rows="2" />
                    <div>
                        <Textarea label="المحتوى الكامل *" v-model="form.body" :rows="14" class="font-mono text-sm" />
                        <p v-if="form.errors.body" class="mt-1 text-xs text-destructive">{{ form.errors.body }}</p>
                    </div>
                </CardContent>
            </Card>

            <div class="flex justify-end gap-2">
                <Button href="/knowledge-base" variant="outline">إلغاء</Button>
                <Button variant="outline" :disabled="form.processing" @click="submit('draft')">
                    <Save class="size-4" /> {{ editing ? 'حفظ' : 'حفظ كمسودة' }}
                </Button>
                <Button v-if="!editing" :disabled="form.processing" @click="submit('in_review')">
                    <Send class="size-4" /> إرسال للمراجعة
                </Button>
            </div>
        </div>
    </AppShell>
</template>
