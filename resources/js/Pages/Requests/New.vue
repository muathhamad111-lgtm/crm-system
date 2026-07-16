<script setup>
import { computed } from 'vue';
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
import Label from '@/Components/ui/Label.vue';
import Select from '@/Components/ui/Select.vue';
import { PlusCircle } from 'lucide-vue-next';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    customers: { type: Array, default: () => [] },
    isStaff: { type: Boolean, default: false },
});

const form = useForm({
    category_id: '',
    sub_category_id: '',
    product_id: '',
    title: '',
    description: '',
    customer_id: '',
    fields: {},
});

const currentCategory = computed(() => props.categories.find((c) => c.id === form.category_id));

function submit() {
    form.post('/requests');
}
</script>

<template>
    <Head title="طلب جديد" />
    <AppShell>
        <div class="mx-auto max-w-3xl space-y-6">
            <PageHero :title="isStaff ? 'إضافة طلب نيابةً عن العميل' : 'طلب جديد'"
                subtitle="اختر التصنيف وعبّئ التفاصيل" :icon="PlusCircle" />

            <Card>
                <CardHeader><CardTitle>تفاصيل الطلب</CardTitle></CardHeader>
                <CardContent class="space-y-5">
                    <div v-if="isStaff">
                        <Label>العميل</Label>
                        <Select v-model="form.customer_id" class="mt-1.5">
                            <option value="">اختر العميل…</option>
                            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.full_name }} — {{ c.email }}</option>
                        </Select>
                        <p v-if="form.errors.customer_id" class="mt-1 text-xs text-destructive">{{ form.errors.customer_id }}</p>
                    </div>

                    <div>
                        <Label>التصنيف</Label>
                        <Select v-model="form.category_id" class="mt-1.5">
                            <option value="">اختر التصنيف…</option>
                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name_ar }}</option>
                        </Select>
                        <p v-if="form.errors.category_id" class="mt-1 text-xs text-destructive">{{ form.errors.category_id }}</p>
                    </div>

                    <div v-if="currentCategory?.sub_categories?.length">
                        <Label>التصنيف الفرعي</Label>
                        <Select v-model="form.sub_category_id" class="mt-1.5">
                            <option value="">—</option>
                            <option v-for="s in currentCategory.sub_categories" :key="s.id" :value="s.id">{{ s.name_ar }}</option>
                        </Select>
                    </div>

                    <div v-if="products.length">
                        <Label>المنتج / الخدمة</Label>
                        <Select v-model="form.product_id" class="mt-1.5">
                            <option value="">—</option>
                            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name_ar }}</option>
                        </Select>
                    </div>

                    <div>
                        <Label>عنوان الطلب</Label>
                        <Input v-model="form.title" class="mt-1.5" placeholder="عنوان مختصر يوضّح طلبك" />
                        <p v-if="form.errors.title" class="mt-1 text-xs text-destructive">{{ form.errors.title }}</p>
                    </div>

                    <div>
                        <Label>وصف الطلب</Label>
                        <Textarea v-model="form.description" class="mt-1.5 min-h-32" placeholder="اشرح طلبك بالتفصيل…" />
                        <p v-if="form.errors.description" class="mt-1 text-xs text-destructive">{{ form.errors.description }}</p>
                    </div>

                    <!-- Dynamic per-category fields -->
                    <template v-if="currentCategory?.fields?.length">
                        <div v-for="f in currentCategory.fields" :key="f.id">
                            <Label>{{ f.label }}<span v-if="f.required" class="text-destructive"> *</span></Label>
                            <p v-if="f.help_text" class="mb-1 text-xs text-muted-foreground">{{ f.help_text }}</p>
                            <Textarea v-if="f.field_type === 'textarea'" v-model="form.fields[f.id]" class="mt-1" />
                            <Select v-else-if="f.field_type === 'select'" v-model="form.fields[f.id]" class="mt-1">
                                <option value="">—</option>
                                <option v-for="opt in (f.options || [])" :key="opt.value ?? opt" :value="opt.value ?? opt">{{ opt.label ?? opt }}</option>
                            </Select>
                            <label v-else-if="f.field_type === 'checkbox'" class="mt-1 flex items-center gap-2 text-sm">
                                <input type="checkbox" v-model="form.fields[f.id]" class="size-4 rounded border-input" /> نعم
                            </label>
                            <Input v-else :type="f.field_type === 'number' ? 'number' : (f.field_type === 'date' ? 'date' : 'text')"
                                v-model="form.fields[f.id]" class="mt-1" />
                        </div>
                    </template>

                    <div class="flex justify-end gap-2 pt-2">
                        <Button href="/requests" variant="outline">إلغاء</Button>
                        <Button variant="accent" :disabled="form.processing" @click="submit">إرسال الطلب</Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppShell>
</template>
