<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import { ArrowRight, ThumbsUp, ThumbsDown, Star } from 'lucide-vue-next';

const props = defineProps({
  article: { type: Object, required: true },
  category: { type: Object, default: null },
  product: { type: Object, default: null },
  myRating: { type: Object, default: null },
  myFeedback: { type: Object, default: null },
  can: { type: Object, default: () => ({}) },
});
const a = props.article;
function feedback(helpful){ router.post(`/knowledge-base/${a.id}/feedback`, { was_helpful:helpful }, { preserveScroll:true }); }
function rate(n){ router.post(`/knowledge-base/${a.id}/rate`, { rating:n }, { preserveScroll:true }); }
</script>
<template>
  <Head :title="a.title" />
  <AppShell>
    <div class="mx-auto max-w-3xl space-y-6">
      <div class="flex items-center gap-3">
        <Button href="/knowledge-base" variant="ghost" size="icon-sm"><ArrowRight class="size-4" /></Button>
        <h1 class="text-2xl font-bold flex-1">{{ a.title }}</h1>
        <Badge variant="muted">{{ a.status }}</Badge>
        <Button v-if="can.author || can.manage" :href="`/knowledge-base/${a.id}/edit`" size="sm" variant="outline">تعديل</Button>
      </div>
      <p v-if="a.summary" class="text-muted-foreground">{{ a.summary }}</p>
      <Card><CardContent class="prose prose-sm max-w-none pt-6"><div class="whitespace-pre-wrap leading-relaxed text-foreground/90">{{ a.body }}</div></CardContent></Card>
      <Card><CardContent class="flex items-center gap-4 pt-6">
        <span class="text-sm text-muted-foreground">هل كان المقال مفيدًا؟</span>
        <Button size="sm" variant="outline" @click="feedback(true)"><ThumbsUp class="size-4" /> نعم</Button>
        <Button size="sm" variant="outline" @click="feedback(false)"><ThumbsDown class="size-4" /> لا</Button>
        <div class="mr-auto flex gap-0.5">
          <button v-for="n in 5" :key="n" @click="rate(n)" type="button"><Star :class="['size-5', n<=(myRating?.rating ?? 0)?'fill-warning text-warning':'text-muted-foreground']" /></button>
        </div>
      </CardContent></Card>
    </div>
  </AppShell>
</template>
