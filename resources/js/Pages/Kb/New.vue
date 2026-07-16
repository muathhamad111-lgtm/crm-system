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
import Label from '@/Components/ui/Label.vue';
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
                        <Label>العنوان *</Label>
                        <Input v-model="form.title" class="mt-1" placeholder="مثال: كيفية تفعيل بوابة الدفع" />
                        <p v-if="form.errors.title" class="mt-1 text-xs text-destructive">{{ form.errors.title }}</p>
                    </div>
                    <div>
                        <Label>ملخص مختصر</Label>
                        <Textarea v-model="form.summary" :rows="2" class="mt-1" placeholder="جملة أو اثنتان توضح المحتوى" />
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        <div>
                            <Label>نوع المعرفة</Label>
                            <Select v-model="form.type" class="mt-1">
                                <option v-for="t in TYPE_OPTIONS" :key="t[0]" :value="t[0]">{{ t[1] }}</option>
                            </Select>
                        </div>
                        <div>
                            <Label>مستوى التعقيد</Label>
                            <Select v-model="form.complexity" class="mt-1">
                                <option v-for="c in COMPLEXITY_OPTIONS" :key="c[0]" :value="c[0]">{{ c[1] }}</option>
                            </Select>
                        </div>
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
                            <Label>المنتج
                                <span v-if="form.is_general" class="text-xs text-muted-foreground">(معطّل - مقال عام)</span>
                            </Label>
                            <Select v-model="form.product_id" class="mt-1" :disabled="form.is_general">
                                <option value="">— بدون منتج —</option>
                                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name_ar }}</option>
                            </Select>
                        </div>
                        <div>
                            <Label>التصنيف الرئيسي</Label>
                            <Select v-model="form.category_id" class="mt-1">
                                <option value="">— بدون تصنيف —</option>
                                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name_ar }}</option>
                            </Select>
                        </div>
                    </div>
                    <div>
                        <Label>الكلمات المفتاحية</Label>
                        <div class="mt-1 flex gap-2">
                            <Input v-model="kwInput" placeholder="أدخل كلمة واضغط Enter"
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
                    <div>
                        <Label>المتطلبات المسبقة</Label>
                        <Textarea v-model="form.prerequisites" :rows="2" class="mt-1" />
                    </div>
                    <div>
                        <Label>التحذيرات والتنبيهات</Label>
                        <Textarea v-model="form.warnings" :rows="2" class="mt-1" />
                    </div>
                    <div>
                        <Label>المحتوى الكامل *</Label>
                        <Textarea v-model="form.body" :rows="14" class="mt-1 font-mono text-sm"
                            placeholder="اكتب الشرح الكامل والخطوات هنا..." />
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
