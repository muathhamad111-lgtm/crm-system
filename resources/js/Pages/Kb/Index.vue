<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import KpiCard from '@/Components/KpiCard.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import Badge from '@/Components/ui/Badge.vue';
import { BookOpen, Plus, Search, BarChart3, Star, Eye, FileText, LayoutGrid, CheckCircle2, Clock } from 'lucide-vue-next';
import { fmtDateAr } from '@/lib/date';
import { num } from '@/lib/utils';

const props = defineProps({
    articles: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    countsByCategory: { type: Object, default: () => ({}) },
    kpis: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    can: { type: Object, default: () => ({}) },
});

const KB_TYPE_LABELS = {
    faq: 'أسئلة شائعة', sop: 'إجراء تشغيلي', known_issue: 'مشكلة معروفة',
    resolution: 'حل جاهز', macro: 'رد جاهز', policy: 'سياسة', user_guide: 'دليل استخدام',
};
const KB_STATUS = {
    draft: { label: 'مسودة', tone: 'muted' },
    in_review: { label: 'قيد المراجعة', tone: 'warning' },
    approved: { label: 'معتمد', tone: 'success' },
    archived: { label: 'مؤرشف', tone: 'muted' },
};
const KB_COMPLEXITY = { beginner: 'مبتدئ', intermediate: 'متوسط', advanced: 'متقدم' };
const TYPE_OPTIONS = Object.keys(KB_TYPE_LABELS);

const q = ref(props.filters.q ?? '');
const type = ref(props.filters.type ?? 'all');
const status = ref(props.filters.status ?? 'approved');
const category = ref(props.filters.category ?? 'all');

let debounce = null;
function apply() {
    router.get('/knowledge-base', {
        q: q.value || undefined,
        type: type.value !== 'all' ? type.value : undefined,
        status: status.value !== 'all' ? status.value : undefined,
        category: category.value !== 'all' ? category.value : undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
}
watch(q, () => { clearTimeout(debounce); debounce = setTimeout(apply, 300); });
watch([type, status, category], apply);

function pickCategory(id) {
    category.value = category.value === id ? 'all' : id;
}
</script>

<template>
    <Head title="قاعدة المعرفة" />
    <AppShell>
        <div class="glass-stage space-y-6">
            <PageHero
                title="قاعدة المعرفة"
                subtitle="مصدر موحد للأسئلة الشائعة، الإجراءات، والحلول التقنية المعتمدة"
                :icon="BookOpen">
                <template #actions>
                    <Button v-if="can.approve || can.manage" href="/knowledge-base/manage" variant="outline"
                        class="bg-white/12 border-white/25 text-white hover:bg-white/20">
                        <BarChart3 class="size-4" /> لوحة الإدارة
                    </Button>
                    <Button v-if="can.author" href="/knowledge-base/new"
                        class="bg-white text-primary hover:bg-white/90">
                        <Plus class="size-4" /> مقال جديد
                    </Button>
                </template>
            </PageHero>

            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <KpiCard label="إجمالي المقالات" :value="kpis.total ?? 0" :icon="FileText" tone="primary" />
                <KpiCard label="معتمدة" :value="kpis.approved ?? 0" :icon="CheckCircle2" tone="success" />
                <KpiCard label="قيد المراجعة" :value="kpis.in_review ?? 0" :icon="Clock" tone="warning" />
                <KpiCard label="متوسط التقييم" :value="kpis.avg_rating ?? 0" :icon="Star" tone="accent" />
            </div>

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-[220px_1fr]">
                <!-- Categories sidebar -->
                <Card class="h-fit">
                    <CardContent class="p-3">
                        <p class="px-2 pb-2 text-xs font-bold text-muted-foreground flex items-center gap-1.5">
                            <LayoutGrid class="size-3.5" /> التصنيفات
                        </p>
                        <button
                            class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm transition-colors"
                            :class="category === 'all' ? 'bg-primary text-primary-foreground font-semibold' : 'hover:bg-muted text-foreground'"
                            @click="category = 'all'">
                            <span>الكل</span>
                        </button>
                        <button v-for="c in categories" :key="c.id"
                            class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm transition-colors mt-0.5"
                            :class="category === c.id ? 'bg-primary text-primary-foreground font-semibold' : 'hover:bg-muted text-foreground'"
                            @click="pickCategory(c.id)">
                            <span class="truncate">{{ c.name_ar }}</span>
                            <span class="tabular-nums text-xs opacity-70">{{ num(countsByCategory[c.id] ?? 0) }}</span>
                        </button>
                    </CardContent>
                </Card>

                <div class="space-y-4">
                    <!-- Filters -->
                    <Card>
                        <CardContent class="p-4">
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                                <div class="relative md:col-span-2">
                                    <Search class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input v-model="q" placeholder="ابحث بالعنوان أو الملخص أو المحتوى..." class="pr-9" />
                                </div>
                                <Select v-model="type">
                                    <option value="all">كل الأنواع</option>
                                    <option v-for="t in TYPE_OPTIONS" :key="t" :value="t">{{ KB_TYPE_LABELS[t] }}</option>
                                </Select>
                                <Select v-model="status">
                                    <option value="all">كل الحالات</option>
                                    <option value="approved">معتمد</option>
                                    <option value="in_review">قيد المراجعة</option>
                                    <option value="draft">مسودة</option>
                                    <option value="archived">مؤرشف</option>
                                </Select>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Articles -->
                    <Card v-if="articles.length === 0">
                        <CardContent class="p-10 text-center text-sm text-muted-foreground">
                            لا توجد مقالات مطابقة للبحث الحالي.
                        </CardContent>
                    </Card>

                    <div class="grid gap-3">
                        <Link v-for="a in articles" :key="a.id" :href="`/knowledge-base/${a.id}`"
                            class="block rounded-xl border border-border bg-card p-4 shadow-card transition-shadow hover:shadow-elevated">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1 space-y-1.5">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <Badge variant="outline">{{ KB_TYPE_LABELS[a.type] ?? a.type }}</Badge>
                                        <Badge :variant="(KB_STATUS[a.status] ?? {}).tone ?? 'muted'">
                                            {{ (KB_STATUS[a.status] ?? {}).label ?? a.status }}
                                        </Badge>
                                        <span v-if="a.complexity" class="text-xs text-muted-foreground">
                                            {{ KB_COMPLEXITY[a.complexity] ?? a.complexity }}
                                        </span>
                                    </div>
                                    <h3 class="font-bold text-foreground truncate">{{ a.title }}</h3>
                                    <p v-if="a.summary" class="text-sm text-muted-foreground line-clamp-2">{{ a.summary }}</p>
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-4 border-t border-border pt-3 text-xs text-muted-foreground tabular-nums">
                                <span class="inline-flex items-center gap-1"><Eye class="size-3.5" /> {{ num(a.views_count ?? 0) }}</span>
                                <span class="inline-flex items-center gap-1"><Star class="size-3.5" /> {{ a.avg_rating ?? 0 }} ({{ num(a.rating_count ?? 0) }})</span>
                                <span class="inline-flex items-center gap-1 text-success">أفاد: {{ num(a.helpful_count ?? 0) }}</span>
                                <span class="mr-auto">آخر تحديث: {{ fmtDateAr(a.updated_at) }}</span>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppShell>
</template>
