<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import Select from '@/Components/ui/Select.vue';
import Label from '@/Components/ui/Label.vue';
import { BookOpen } from 'lucide-vue-next';

const props = defineProps({ article: { type: Object, default: null } });
const editing = !!props.article;
const form = useForm({
  title: props.article?.title ?? '',
  type: props.article?.type ?? 'faq',
  complexity: props.article?.complexity ?? 'beginner',
  summary: props.article?.summary ?? '',
  body: props.article?.body ?? '',
  keywords: (props.article?.keywords ?? []).join?.('، ') ?? '',
});
const types = [['faq','سؤال شائع'],['sop','إجراء تشغيلي'],['known_issue','مشكلة معروفة'],['resolution','حل'],['macro','رد جاهز'],['policy','سياسة'],['user_guide','دليل المستخدم']];
function submit(){ editing ? form.put(`/knowledge-base/${props.article.id}`) : form.post('/knowledge-base'); }
</script>
<template>
  <Head :title="editing ? 'تعديل مقال' : 'مقال جديد'" />
  <AppShell>
    <div class="mx-auto max-w-3xl space-y-6">
      <PageHero :title="editing ? 'تعديل مقال' : 'مقال جديد'" subtitle="قاعدة المعرفة" :icon="BookOpen" />
      <Card><CardContent class="space-y-4 pt-6">
        <div><Label>العنوان</Label><Input v-model="form.title" class="mt-1" /></div>
        <div class="grid grid-cols-2 gap-3">
          <div><Label>النوع</Label><Select v-model="form.type" class="mt-1"><option v-for="t in types" :key="t[0]" :value="t[0]">{{ t[1] }}</option></Select></div>
          <div><Label>المستوى</Label><Select v-model="form.complexity" class="mt-1"><option value="beginner">مبتدئ</option><option value="intermediate">متوسط</option><option value="advanced">متقدّم</option></Select></div>
        </div>
        <div><Label>ملخّص</Label><Textarea v-model="form.summary" class="mt-1 min-h-16" /></div>
        <div><Label>المحتوى</Label><Textarea v-model="form.body" class="mt-1 min-h-48" /></div>
        <div><Label>الكلمات المفتاحية</Label><Input v-model="form.keywords" class="mt-1" placeholder="مفصولة بفواصل" /></div>
        <div class="flex justify-end gap-2"><Button href="/knowledge-base" variant="outline">إلغاء</Button><Button variant="accent" :disabled="form.processing" @click="submit">حفظ</Button></div>
      </CardContent></Card>
    </div>
  </AppShell>
</template>
