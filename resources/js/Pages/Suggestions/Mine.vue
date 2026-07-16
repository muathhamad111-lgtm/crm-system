<script setup>
import { Head } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import Card from '@/Components/ui/Card.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import { Lightbulb, PlusCircle } from 'lucide-vue-next';
import { timeAgoAr } from '@/lib/date';
import { IDEA_STAGE, statusLabel } from '@/lib/labels';

const props = defineProps({ suggestions: { type: Object, default: () => ({ data: [] }) } });
const rows = () => Array.isArray(props.suggestions) ? props.suggestions : (props.suggestions.data ?? []);
</script>
<template>
  <Head title="مقترحاتي" />
  <AppShell>
    <div class="space-y-6">
      <PageHero title="مقترحاتي" subtitle="متابعة مقترحاتك المقدّمة" :icon="Lightbulb">
        <template #actions><Button href="/suggestions/new" variant="accent"><PlusCircle class="size-4" /> مقترح جديد</Button></template>
      </PageHero>
      <div class="grid gap-3 sm:grid-cols-2">
        <Card v-for="s in rows()" :key="s.id" class="p-5">
          <div class="flex items-start justify-between gap-2">
            <a :href="`/suggestions/${s.id}`" class="font-bold hover:text-primary">{{ s.title }}</a>
            <Badge :variant="statusLabel(IDEA_STAGE, s.idea_stage).tone">{{ statusLabel(IDEA_STAGE, s.idea_stage).label }}</Badge>
          </div>
          <p class="mt-1 text-xs text-muted-foreground tabular-nums">{{ s.request_number }}</p>
          <p class="mt-2 text-sm text-muted-foreground line-clamp-2">{{ s.description }}</p>
          <p class="mt-2 text-xs text-muted-foreground">{{ timeAgoAr(s.created_at) }}</p>
        </Card>
        <Card v-if="!rows().length" class="p-10 text-center text-muted-foreground sm:col-span-2">لم تقدّم أي مقترح بعد.</Card>
      </div>
    </div>
  </AppShell>
</template>
