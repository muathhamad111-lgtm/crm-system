<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import KpiCard from '@/Components/KpiCard.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import { IDEA_STAGE, statusLabel } from '@/lib/labels';
import {
    Lightbulb, Plus, Inbox, Search, Star, MessageSquare, TrendingUp,
    ThumbsUp, Lock, ChevronLeft,
} from 'lucide-vue-next';

const props = defineProps({
    suggestions: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    isStaff: { type: Boolean, default: false },
});

const search = ref('');
const sort = ref('latest');

const filtered = computed(() => {
    const q = search.value.trim();
    let list = props.suggestions.filter((s) =>
        !q || s.title?.includes(q) || s.description?.includes(q) || s.request_number?.includes(q));
    if (sort.value === 'top_rated') {
        list = [...list].sort((a, b) => (b.avg_stars - a.avg_stars) || (b.ratings_count - a.ratings_count));
    } else if (sort.value === 'most_discussed') {
        list = [...list].sort((a, b) => b.comments_count - a.comments_count);
    }
    return list;
});

const steps = [
    { n: '٠١', title: 'شارك فكرتك', body: 'اقترح تحسيناً أو خدمة جديدة عبر نموذج بسيط، يطّلع عليها فريقنا خلال أيام معدودة.' },
    { n: '٠٢', title: 'صوّت وقيّم', body: 'أعطِ نجوماً واترك تعليقاً على المقترحات المنشورة لتُرشَّح الأفكار الأكثر تأثيراً.' },
    { n: '٠٣', title: 'نُحوّلها إلى واقع', body: 'نختار المقترحات الأعلى تفاعلاً لتدخل ضمن خطّة التطوير، ونُعلمك بكل قرار.' },
];

function stageBadge(stage) {
    return statusLabel(IDEA_STAGE, stage);
}
</script>

<template>
    <Head title="لوحة المقترحات" />
    <AppShell>
        <div class="space-y-6">
            <PageHero
                title="لوحة المقترحات"
                subtitle="شارك في تقييم المقترحات المنشورة والتعليق عليها لتشكيل مستقبل الخدمة معاً."
                :icon="Lightbulb">
                <template #actions>
                    <Button :href="route('suggestions.create')" variant="accent" size="sm">
                        <Plus class="size-4" /> اقتراح جديد
                    </Button>
                    <Button v-if="isStaff" :href="route('suggestions.inbox')" variant="outline" size="sm">
                        <Inbox class="size-4" /> صندوق الفريق
                    </Button>
                </template>
            </PageHero>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <KpiCard label="مقترحات منشورة" :value="stats.total ?? 0" :icon="TrendingUp" tone="primary" />
                <KpiCard label="متوسط التقييم" :value="stats.avg ? `${stats.avg} / 5` : '—'"
                    :format-number="false" :icon="Star" tone="warning" />
                <KpiCard label="تعليقات المجتمع" :value="stats.comments ?? 0" :icon="MessageSquare" tone="accent" />
            </div>

            <!-- How it works -->
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div v-for="step in steps" :key="step.n"
                    class="rounded-2xl border border-border bg-card p-4 transition-colors hover:border-primary/40">
                    <div class="mb-2 flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-xl bg-primary-soft text-primary">
                            <Lightbulb class="size-5" />
                        </div>
                        <span class="text-xs font-bold tracking-widest text-muted-foreground">{{ step.n }}</span>
                    </div>
                    <h3 class="mb-1 text-sm font-bold text-foreground">{{ step.title }}</h3>
                    <p class="text-xs leading-relaxed text-muted-foreground">{{ step.body }}</p>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="relative flex-1">
                    <Search class="absolute right-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" placeholder="ابحث في المقترحات بالعنوان أو الوصف أو الرقم..." class="pr-9" />
                </div>
                <Select v-model="sort" class="sm:w-56">
                    <option value="latest">الأحدث نشراً</option>
                    <option value="top_rated">الأعلى تقييماً</option>
                    <option value="most_discussed">الأكثر نقاشاً</option>
                </Select>
            </div>

            <!-- Results -->
            <div v-if="filtered.length" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <Card v-for="s in filtered" :key="s.id"
                    class="group cursor-pointer overflow-hidden transition-colors hover:border-primary/40"
                    @click="router.visit(route('suggestions.show', s.id))">
                    <CardContent class="space-y-3 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="mb-1.5 flex items-center gap-2">
                                    <Badge variant="outline" class="font-mono text-[10px]">{{ s.request_number }}</Badge>
                                    <Badge :variant="stageBadge(s.idea_stage).tone" class="text-[10px]">
                                        {{ stageBadge(s.idea_stage).label }}
                                    </Badge>
                                    <Badge v-if="s.comments_locked" variant="warning" class="gap-1 text-[10px]">
                                        <Lock class="size-3" /> مقفل
                                    </Badge>
                                </div>
                                <h3 class="line-clamp-2 text-sm font-bold leading-snug text-foreground group-hover:text-primary">
                                    {{ s.title }}
                                </h3>
                            </div>
                            <ChevronLeft class="mt-1 size-4 shrink-0 text-muted-foreground/50 group-hover:text-primary" />
                        </div>
                        <p class="line-clamp-2 text-xs leading-relaxed text-muted-foreground">{{ s.description }}</p>
                        <div class="flex items-center justify-between border-t border-border pt-2">
                            <div class="flex items-center gap-3 text-xs text-muted-foreground">
                                <span class="inline-flex items-center gap-1">
                                    <Star class="size-3.5 fill-warning text-warning" />
                                    <span class="tabular-nums">{{ s.avg_stars ? s.avg_stars.toFixed(1) : '—' }}</span>
                                    <span class="text-muted-foreground/60">({{ s.ratings_count }})</span>
                                </span>
                                <span class="inline-flex items-center gap-1 tabular-nums">
                                    <ThumbsUp class="size-3.5" /> {{ s.support_count }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1 text-xs text-muted-foreground tabular-nums">
                                <MessageSquare class="size-3.5" /> {{ s.comments_count }}
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card v-else>
                <CardContent class="py-14 text-center">
                    <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl bg-primary-soft text-primary">
                        <Lightbulb class="size-8" />
                    </div>
                    <p class="text-lg font-bold text-foreground">
                        {{ search ? 'لا توجد نتائج مطابقة' : 'لا توجد مقترحات منشورة حالياً' }}
                    </p>
                    <p class="mx-auto mt-2 max-w-md text-sm text-muted-foreground">
                        {{ search ? 'جرّب كلمات بحث مختلفة أو غيّر الترتيب.' : 'المقترحات تمر بمرحلة مراجعة قبل النشر. كن أول من يطرح فكرة!' }}
                    </p>
                    <div v-if="!search" class="mt-4">
                        <Button :href="route('suggestions.create')" size="sm">
                            <Plus class="size-4" /> اقتراح جديد
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppShell>
</template>
