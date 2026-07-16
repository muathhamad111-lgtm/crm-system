<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import { timeAgoAr } from '@/lib/date';
import {
    Activity as ActivityIcon, Filter, FileText, Lightbulb, Star,
    Phone, Mail, MessageSquare, Calendar, MapPin, StickyNote, AlertTriangle,
} from 'lucide-vue-next';

const props = defineProps({
    activities: { type: Array, default: () => [] },
    requests: { type: Array, default: () => [] },
    suggestions: { type: Array, default: () => [] },
    ratings: { type: Array, default: () => [] },
});

const ACT = {
    call: { icon: Phone, label: 'مكالمة', tone: 'bg-primary/15 text-primary' },
    email: { icon: Mail, label: 'بريد', tone: 'bg-accent/15 text-accent' },
    whatsapp: { icon: MessageSquare, label: 'واتساب', tone: 'bg-success/15 text-success' },
    meeting: { icon: Calendar, label: 'اجتماع', tone: 'bg-warning/15 text-warning' },
    visit: { icon: MapPin, label: 'زيارة', tone: 'bg-muted text-muted-foreground' },
    note: { icon: StickyNote, label: 'ملاحظة', tone: 'bg-muted text-muted-foreground' },
    alert: { icon: AlertTriangle, label: 'تنبيه', tone: 'bg-destructive/15 text-destructive' },
};

const filter = ref('all');
const FILTERS = [
    { key: 'all', label: 'الكل' },
    { key: 'activity', label: 'تفاعلات' },
    { key: 'request', label: 'طلبات' },
    { key: 'suggestion', label: 'مقترحات' },
    { key: 'rating', label: 'تقييمات' },
];

const items = computed(() => {
    const out = [];
    (props.activities ?? []).forEach((a) => {
        const meta = ACT[a.activity_type] ?? ACT.note;
        out.push({
            kind: 'activity', at: a.occurred_at,
            title: `${meta.label}: ${a.subject}`,
            body: a.summary,
            meta: a.performed_by_name ? `بواسطة ${a.performed_by_name}` : null,
            icon: meta.icon, iconTone: meta.tone,
        });
    });
    (props.requests ?? []).forEach((r) => {
        out.push({
            kind: 'request', at: r.created_at, title: r.title,
            meta: [r.request_number, r.category_name, r.product_name].filter(Boolean).join(' · '),
            href: `/requests/${r.id}`, badge: 'طلب',
            icon: FileText, iconTone: 'bg-primary/15 text-primary',
        });
    });
    (props.suggestions ?? []).forEach((s) => {
        out.push({
            kind: 'suggestion', at: s.created_at, title: s.title,
            meta: [s.request_number, s.product_name].filter(Boolean).join(' · '),
            badge: 'مقترح',
            icon: Lightbulb, iconTone: 'bg-accent/15 text-accent',
        });
    });
    (props.ratings ?? []).forEach((rt) => {
        out.push({
            kind: 'rating', at: rt.created_at,
            title: `تقييم ${rt.stars}/5 — ${rt.request_title ?? ''}`,
            body: rt.notes, meta: rt.request_number,
            href: rt.request_id ? `/requests/${rt.request_id}` : null,
            icon: Star, iconTone: 'bg-warning/15 text-warning',
        });
    });
    return out
        .filter((i) => filter.value === 'all' || i.kind === filter.value)
        .sort((a, b) => new Date(b.at) - new Date(a.at));
});
</script>

<template>
    <Card>
        <CardHeader class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <CardTitle class="flex items-center gap-2 text-base">
                <span class="flex size-9 items-center justify-center rounded-xl bg-primary/15 text-primary">
                    <ActivityIcon class="size-4" />
                </span>
                الخط الزمني الموحد ({{ items.length }})
            </CardTitle>
            <div class="flex flex-wrap items-center gap-1">
                <Filter class="size-3.5 text-muted-foreground" />
                <Button v-for="f in FILTERS" :key="f.key" size="sm"
                    :variant="filter === f.key ? 'default' : 'outline'"
                    class="h-7 text-xs" @click="filter = f.key">
                    {{ f.label }}
                </Button>
            </div>
        </CardHeader>
        <CardContent>
            <div v-if="!items.length" class="py-10 text-center text-sm text-muted-foreground">لا توجد عناصر في الخط الزمني.</div>
            <ol v-else class="relative space-y-3 border-s border-border/60 ps-5">
                <li v-for="(i, idx) in items" :key="idx" class="relative">
                    <span class="absolute -start-[1.45rem] top-3 size-3 rounded-full bg-primary ring-4 ring-background"></span>
                    <component :is="i.href ? Link : 'div'" :href="i.href || undefined"
                        class="block rounded-xl border border-border/60 bg-card/70 p-3 transition hover:bg-card">
                        <div class="flex items-start gap-2">
                            <div :class="`flex size-8 shrink-0 items-center justify-center rounded-lg ${i.iconTone}`">
                                <component :is="i.icon" class="size-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="truncate text-sm font-medium text-foreground">{{ i.title }}</div>
                                    <Badge v-if="i.badge" variant="outline" class="text-[10px]">{{ i.badge }}</Badge>
                                </div>
                                <div v-if="i.body" class="mt-1 line-clamp-2 text-xs text-muted-foreground">{{ i.body }}</div>
                                <div class="mt-1 flex flex-wrap items-center gap-2 text-[10px] text-muted-foreground">
                                    <span>{{ timeAgoAr(i.at) }}</span>
                                    <span v-if="i.meta">· {{ i.meta }}</span>
                                </div>
                            </div>
                        </div>
                    </component>
                </li>
            </ol>
        </CardContent>
    </Card>
</template>
