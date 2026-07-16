<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import Select from '@/Components/ui/Select.vue';
import Separator from '@/Components/ui/Separator.vue';
import Badge from '@/Components/ui/Badge.vue';
import { ArrowRight, ThumbsUp, MessageSquare, Star } from 'lucide-vue-next';
import { timeAgoAr } from '@/lib/date';
import { IDEA_STAGE, statusLabel } from '@/lib/labels';

const props = defineProps({
  suggestion: { type: Object, required: true },
  comments: { type: Array, default: () => [] },
  votes: { type: Object, default: () => ({ count: 0, mine: null }) },
  myRating: { type: Object, default: null },
  isStaff: { type: Boolean, default: false },
  can: { type: Object, default: () => ({}) },
});
const s = props.suggestion;
function vote(v){ router.post(`/suggestions/${s.id}/vote`, { vote:v }, { preserveScroll:true }); }
const commentForm = useForm({ body:'' });
function addComment(){ commentForm.post(`/suggestions/${s.id}/comment`, { preserveScroll:true, onSuccess:()=>commentForm.reset('body') }); }
const stageForm = useForm({ idea_stage: s.idea_stage, decision: s.decision, decision_reason: s.decision_reason ?? '' });
function advance(){ stageForm.post(`/suggestions/${s.id}/advance`, { preserveScroll:true }); }
function publish(){ router.post(`/suggestions/${s.id}/publish`, {}, { preserveScroll:true }); }
</script>
<template>
  <Head :title="s.request_number ?? 'مقترح'" />
  <AppShell>
    <div class="space-y-6">
      <div class="flex items-center gap-3">
        <Button href="/suggestions" variant="ghost" size="icon-sm"><ArrowRight class="size-4" /></Button>
        <div><h1 class="text-xl font-bold">{{ s.title }}</h1><p class="text-sm text-muted-foreground tabular-nums">{{ s.request_number }}</p></div>
        <Badge class="mr-auto" :variant="statusLabel(IDEA_STAGE, s.idea_stage).tone">{{ statusLabel(IDEA_STAGE, s.idea_stage).label }}</Badge>
      </div>
      <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
          <Card><CardHeader><CardTitle>الوصف</CardTitle></CardHeader>
            <CardContent><p class="whitespace-pre-wrap leading-relaxed text-foreground/90">{{ s.description }}</p>
              <div class="mt-4 flex items-center gap-2">
                <Button size="sm" :variant="votes.mine==='support'?'accent':'outline'" @click="vote('support')"><ThumbsUp class="size-4" /> تأييد ({{ votes.count }})</Button>
              </div>
            </CardContent></Card>
          <Card><CardHeader><CardTitle class="flex items-center gap-2"><MessageSquare class="size-5" /> النقاش</CardTitle></CardHeader>
            <CardContent class="space-y-3">
              <div v-if="!comments.length" class="text-sm text-muted-foreground">لا توجد تعليقات.</div>
              <div v-for="c in comments" :key="c.id" class="rounded-lg bg-muted/30 border border-border p-3">
                <p class="text-sm whitespace-pre-wrap">{{ c.body }}</p>
                <p class="mt-1 text-xs text-muted-foreground">{{ timeAgoAr(c.created_at) }}</p>
              </div>
              <Separator />
              <Textarea v-model="commentForm.body" placeholder="أضف تعليقًا…" class="min-h-16" />
              <div class="flex justify-end"><Button size="sm" :disabled="commentForm.processing || !commentForm.body" @click="addComment">إرسال</Button></div>
            </CardContent></Card>
        </div>
        <div class="space-y-6">
          <Card v-if="isStaff"><CardHeader><CardTitle>إدارة المقترح</CardTitle></CardHeader>
            <CardContent class="space-y-3">
              <div><label class="text-sm text-muted-foreground">المرحلة</label>
                <Select v-model="stageForm.idea_stage" class="mt-1">
                  <option v-for="[k,v] in Object.entries(IDEA_STAGE)" :key="k" :value="k">{{ v.label }}</option>
                </Select></div>
              <Textarea v-model="stageForm.decision_reason" placeholder="سبب القرار (اختياري)" class="min-h-16" />
              <Button size="sm" class="w-full" :disabled="stageForm.processing" @click="advance">حفظ المرحلة</Button>
              <Separator />
              <Button size="sm" variant="accent" class="w-full" @click="publish">نشر للعملاء</Button>
              <div v-if="s.rice_score" class="rounded-lg bg-muted p-3 text-sm">
                <p class="text-muted-foreground">درجة RICE</p><p class="text-lg font-bold tabular-nums">{{ s.rice_score }}</p>
              </div>
            </CardContent></Card>
          <Card><CardContent class="space-y-2 pt-6 text-sm">
            <div class="flex justify-between"><span class="text-muted-foreground">أُنشئ</span><span>{{ timeAgoAr(s.created_at) }}</span></div>
            <div v-if="s.published_at" class="flex justify-between"><span class="text-muted-foreground">نُشر</span><span>{{ timeAgoAr(s.published_at) }}</span></div>
          </CardContent></Card>
        </div>
      </div>
    </div>
  </AppShell>
</template>
