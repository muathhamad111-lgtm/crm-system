<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import { Bell, CheckCheck } from 'lucide-vue-next';
import { timeAgoAr } from '@/lib/date';

const props = defineProps({
  notifications: { type: Object, default: () => ({ data: [] }) },
  filter: { type: String, default: 'all' },
  unreadCount: { type: Number, default: 0 },
});
function setFilter(f){ router.get('/notifications', { filter: f }, { preserveState:true, replace:true }); }
function markRead(n){ router.post(`/notifications/${n.id}/read`, {}, { preserveScroll:true }); }
function markAll(){ router.post('/notifications/read-all', {}, { preserveScroll:true }); }
</script>
<template>
  <Head title="الإشعارات" />
  <AppShell>
    <div class="mx-auto max-w-3xl space-y-6">
      <PageHero title="الإشعارات" :subtitle="`لديك ${unreadCount} إشعار غير مقروء`" :icon="Bell">
        <template #actions><Button variant="outline" @click="markAll"><CheckCheck class="size-4" /> تحديد الكل كمقروء</Button></template>
      </PageHero>
      <div class="flex gap-2">
        <button v-for="f in [['all','الكل'],['unread','غير المقروء']]" :key="f[0]" @click="setFilter(f[0])"
          :class="['rounded-full px-3.5 py-1.5 text-sm font-medium', filter===f[0]?'bg-primary text-primary-foreground':'bg-muted text-muted-foreground']">{{ f[1] }}</button>
      </div>
      <div class="space-y-2">
        <Card v-for="n in notifications.data" :key="n.id" :class="['p-4 flex items-start gap-3', !n.read_at && 'border-r-4 border-r-accent']">
          <div class="flex-1">
            <p class="font-bold text-sm">{{ n.title }}</p>
            <p v-if="n.body" class="text-sm text-muted-foreground mt-0.5">{{ n.body }}</p>
            <p class="text-xs text-muted-foreground mt-1">{{ timeAgoAr(n.created_at) }}</p>
          </div>
          <a v-if="n.link_path" :href="n.link_path" class="text-xs text-primary hover:underline">عرض</a>
          <Button v-if="!n.read_at" size="sm" variant="ghost" @click="markRead(n)">تحديد كمقروء</Button>
        </Card>
        <Card v-if="!notifications.data.length" class="p-10 text-center text-muted-foreground">لا توجد إشعارات.</Card>
      </div>
    </div>
  </AppShell>
</template>
