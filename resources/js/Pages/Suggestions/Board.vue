<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import { IDEA_STAGE, statusLabel } from '@/lib/labels';
import {
    Lightbulb, Plus, Inbox, Search, Star, MessageSquare, TrendingUp,
    ThumbsUp, Lock, ChevronLeft, Package, Sparkles, Vote,
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
    { n: '٠١', icon: Lightbulb, title: 'شارك فكرتك', body: 'اقترح تحسيناً أو خدمة جديدة عبر نموذج بسيط، يطّلع عليها فريقنا خلال أيام معدودة.' },
    { n: '٠٢', icon: Vote, title: 'صوّت وقيّم', body: 'أعطِ نجوماً واترك تعليقاً على المقترحات المنشورة لتُرشَّح الأفكار الأكثر تأثيراً.' },
    { n: '٠٣', icon: Sparkles, title: 'نُحوّلها إلى واقع', body: 'نختار المقترحات الأعلى تفاعلاً لتدخل ضمن خطّة التطوير، ونُعلمك بكل قرار.' },
];

function stageBadge(stage) {
    return statusLabel(IDEA_STAGE, stage);
}
function stars(v) {
    return [1, 2, 3, 4, 5].map((n) => n <= Math.round(v || 0));
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
                    <Button :href="route('suggestions.create')" class="bg-white/15 text-white hover:bg-white/25 backdrop-blur-sm" size="sm">
                        <Plus class="size-4" /> اقتراح جديد
                    </Button>
                    <Button v-if="isStaff" :href="route('suggestions.inbox')" variant="outline"
                        class="border-white/30 bg-white/10 text-white hover:bg-white/20" size="sm">
                        <Inbox class="size-4" /> صندوق الفريق
                    </Button>
                </template>

                <!-- Hero stat tiles -->
                <div class="grid max-w-2xl grid-cols-3 gap-3">
                    <div class="rounded-xl border border-white/15 bg-white/10 px-3 py-2.5 backdrop-blur-sm">
                        <div class="mb-1 flex items-center gap-1.5 text-[10px] font-medium uppercase tracking-wider text-white/70">
                            <TrendingUp class="size-3" /> مقترحات منشورة
                        </div>
                        <div class="text-lg font-bold leading-none tabular-nums text-white">{{ stats.total ?? 0 }}</div>
                    </div>
                    <div class="rounded-xl border border-white/15 bg-white/10 px-3 py-2.5 backdrop-blur-sm">
                        <div class="mb-1 flex items-center gap-1.5 text-[10px] font-medium uppercase tracking-wider text-white/70">
                            <Star class="size-3" /> متوسط التقييم
                        </div>
                        <div class="text-lg font-bold leading-none tabular-nums text-white">{{ stats.avg ? `${stats.avg} / 5` : '—' }}</div>
                    </div>
                    <div class="rounded-xl border border-white/15 bg-white/10 px-3 py-2.5 backdrop-blur-sm">
                        <div class="mb-1 flex items-center gap-1.5 text-[10px] font-medium uppercase tracking-wider text-white/70">
                            <MessageSquare class="size-3" /> تعليقات المجتمع
                        </div>
                        <div class="text-lg font-bold leading-none tabular-nums text-white">{{ stats.comments ?? 0 }}</div>
                    </div>
                </div>
            </PageHero>

            <!-- How it works -->
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div v-for="step in steps" :key="step.n"
                    class="rounded-2xl border border-border bg-card p-4 transition-all hover:border-primary/40 hover:shadow-md">
                    <div class="mb-2 flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-xl bg-primary-soft text-primary ring-1 ring-primary/15">
                            <component :is="step.icon" class="size-5" />
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
                    <Search class="absolute left-3 top-1/2 z-10 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" label="ابحث في المقترحات بالعنوان أو الوصف أو الرقم..." />
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
                    class="group cursor-pointer overflow-hidden transition-all hover:border-primary/40 hover:shadow-elevated"
                    @click="router.visit(route('suggestions.show', s.id))">
                    <div class="h-1 bg-gradient-to-r from-primary via-accent to-primary opacity-60 transition-opacity group-hover:opacity-100"></div>
                    <CardContent class="space-y-3 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="mb-1.5 flex flex-wrap items-center gap-2">
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
                            <ChevronLeft class="mt-1 size-4 shrink-0 text-muted-foreground/50 transition-all group-hover:-translate-x-0.5 group-hover:text-primary" />
                        </div>
                        <p class="line-clamp-2 text-xs leading-relaxed text-muted-foreground">{{ s.description }}</p>
                        <div v-if="s.product" class="flex items-center gap-1 text-[11px] text-accent">
                            <Package class="size-3" /> {{ s.product }}
                        </div>
                        <div class="flex items-center justify-between border-t border-border pt-2">
                            <div class="flex items-center gap-3 text-xs text-muted-foreground">
                                <span class="inline-flex items-center gap-0.5">
                                    <Star v-for="(on, i) in stars(s.avg_stars)" :key="i" class="size-3.5"
                                        :class="on ? 'fill-warning text-warning' : 'text-muted-foreground/30'" />
                                    <span class="mr-1 tabular-nums">{{ s.avg_stars ? s.avg_stars.toFixed(1) : '—' }}</span>
                                    <span class="text-muted-foreground/60">({{ s.ratings_count }})</span>
                                </span>
                                <span class="inline-flex items-center gap-1 tabular-nums">
                                    <ThumbsUp class="size-3.5" /> {{ s.support_count }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1 text-xs tabular-nums text-muted-foreground">
                                <MessageSquare class="size-3.5" /> {{ s.comments_count }}
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card v-else>
                <CardContent class="py-14 text-center">
                    <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl bg-primary-soft text-primary ring-1 ring-primary/15">
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
