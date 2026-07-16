<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import KpiCard from '@/Components/KpiCard.vue';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import {
    BookOpen, ArrowRight, ClipboardList, TrendingUp, Star, AlertTriangle,
    Eye, ThumbsDown, CheckCircle2, X, Plus, FileCheck2,
} from 'lucide-vue-next';
import { fmtDateAr } from '@/lib/date';
import { num } from '@/lib/utils';

const props = defineProps({
    pending: { type: Array, default: () => [] },
    gaps: { type: Array, default: () => [] },
    topUsed: { type: Array, default: () => [] },
    lowRated: { type: Array, default: () => [] },
    kpis: { type: Object, default: () => ({}) },
    can: { type: Object, default: () => ({}) },
});

const openGaps = () => props.gaps.filter((g) => g.status === 'open');

const gapForm = useForm({ topic: '', keywords: '', notes: '' });
function addGap() {
    if (!gapForm.topic.trim()) return;
    gapForm.post('/knowledge-base/gaps', {
        preserveScroll: true,
        onSuccess: () => gapForm.reset(),
    });
}
function approve(id) {
    router.post(`/knowledge-base/${id}/status`, { status: 'approved' }, { preserveScroll: true });
}
function closeGap(id, status) {
    router.post(`/knowledge-base/gaps/${id}/status`, { status }, { preserveScroll: true });
}
</script>

<template>
    <Head title="لوحة إدارة المعرفة" />
    <AppShell>
        <div class="glass-stage space-y-6">
            <PageHero
                title="لوحة إدارة المعرفة"
                subtitle="مؤشرات الأداء، المقالات المعلقة للاعتماد، وفجوات المعرفة المكتشفة"
                :icon="BookOpen">
                <template #actions>
                    <Button href="/knowledge-base" variant="outline"
                        class="bg-white/12 border-white/25 text-white hover:bg-white/20">
                        <ArrowRight class="size-4" /> عودة
                    </Button>
                </template>
            </PageHero>

            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <KpiCard label="بانتظار الاعتماد" :value="kpis.pending ?? 0" :icon="ClipboardList" tone="warning" />
                <KpiCard label="فجوات مفتوحة" :value="kpis.gaps_open ?? 0" :icon="AlertTriangle" tone="destructive" />
                <KpiCard label="مقالات معتمدة" :value="kpis.approved ?? 0" :icon="FileCheck2" tone="success" />
                <KpiCard label="إجمالي المشاهدات" :value="kpis.total_views ?? 0" :icon="Eye" tone="primary" />
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <!-- Pending review -->
                <Card v-if="can.approve">
                    <CardHeader class="pb-2">
                        <CardTitle class="flex items-center gap-2 text-sm">
                            <ClipboardList class="size-4 text-warning" />
                            مقالات قيد المراجعة ({{ pending.length }})
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <p v-if="!pending.length" class="py-3 text-center text-xs text-muted-foreground">
                            لا توجد مقالات معلّقة.
                        </p>
                        <div v-for="a in pending" :key="a.id"
                            class="flex items-center justify-between gap-2 rounded-lg border border-border p-2.5">
                            <Link :href="`/knowledge-base/${a.id}`" class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium text-foreground hover:text-primary">{{ a.title }}</div>
                                <div class="mt-0.5 text-[10px] text-muted-foreground">
                                    آخر تحديث: {{ fmtDateAr(a.updated_at) }}
                                </div>
                            </Link>
                            <Button size="sm" @click="approve(a.id)">
                                <CheckCircle2 class="size-4" /> اعتماد
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Most used -->
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="flex items-center gap-2 text-sm">
                            <TrendingUp class="size-4 text-success" /> الأكثر استخداماً
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-1.5">
                        <p v-if="!topUsed.length" class="py-3 text-center text-xs text-muted-foreground">لا توجد بيانات بعد.</p>
                        <Link v-for="a in topUsed" :key="a.id" :href="`/knowledge-base/${a.id}`"
                            class="flex items-center justify-between gap-2 rounded-lg border border-border p-2 text-xs hover:bg-muted/50">
                            <span class="truncate font-medium text-foreground">{{ a.title }}</span>
                            <span class="inline-flex shrink-0 items-center gap-1 text-muted-foreground tabular-nums">
                                <Eye class="size-3" /> {{ num(a.views_count ?? 0) }}
                            </span>
                        </Link>
                    </CardContent>
                </Card>

                <!-- Lowest rated -->
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="flex items-center gap-2 text-sm">
                            <Star class="size-4 text-warning" /> الأقل تقييماً
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-1.5">
                        <p v-if="!lowRated.length" class="py-3 text-center text-xs text-muted-foreground">لا توجد مقالات مقيّمة بعد.</p>
                        <Link v-for="a in lowRated" :key="a.id" :href="`/knowledge-base/${a.id}`"
                            class="flex items-center justify-between gap-2 rounded-lg border border-border p-2 text-xs hover:bg-muted/50">
                            <span class="truncate font-medium text-foreground">{{ a.title }}</span>
                            <span class="inline-flex shrink-0 items-center gap-1 text-warning tabular-nums">
                                <Star class="size-3 fill-current" /> {{ a.avg_rating ?? 0 }}
                                <template v-if="a.not_helpful_count > 0">
                                    <span class="mx-0.5 text-muted-foreground">·</span>
                                    <ThumbsDown class="size-3 text-destructive" /> {{ num(a.not_helpful_count) }}
                                </template>
                            </span>
                        </Link>
                    </CardContent>
                </Card>

                <!-- Knowledge gaps -->
                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="flex items-center gap-2 text-sm">
                            <AlertTriangle class="size-4 text-destructive" /> فجوات المعرفة ({{ openGaps().length }})
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div v-if="can.manage" class="space-y-2 border-b border-border pb-3">
                            <Input v-model="gapForm.topic" placeholder="موضوع الفجوة (مثال: مشكلة X لا يوجد لها حل موثق)"
                                class="h-8 text-xs" />
                            <Input v-model="gapForm.keywords" placeholder="كلمات مفتاحية (مفصولة بفاصلة)" class="h-8 text-xs" />
                            <Textarea v-model="gapForm.notes" :rows="2" placeholder="ملاحظات..." class="text-xs" />
                            <Button size="sm" class="w-full" :disabled="gapForm.processing || !gapForm.topic.trim()" @click="addGap">
                                <Plus class="size-3.5" /> تسجيل فجوة
                            </Button>
                        </div>
                        <p v-if="!openGaps().length" class="py-3 text-center text-xs text-muted-foreground">
                            لا توجد فجوات مفتوحة.
                        </p>
                        <div v-for="g in openGaps()" :key="g.id" class="rounded-lg border border-border p-2 text-xs">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-foreground">{{ g.topic }}</div>
                                    <div v-if="g.notes" class="mt-0.5 text-muted-foreground">{{ g.notes }}</div>
                                    <div v-if="(g.keywords ?? []).length" class="mt-1 flex flex-wrap gap-1">
                                        <span v-for="k in g.keywords" :key="k"
                                            class="rounded bg-muted px-1.5 py-0.5 text-[10px] text-muted-foreground">{{ k }}</span>
                                    </div>
                                </div>
                                <div v-if="can.manage" class="flex shrink-0 gap-1">
                                    <button title="تم معالجتها" class="rounded p-1 text-success hover:bg-success/10"
                                        @click="closeGap(g.id, 'addressed')">
                                        <CheckCircle2 class="size-3.5" />
                                    </button>
                                    <button title="تجاهل" class="rounded p-1 text-destructive hover:bg-destructive/10"
                                        @click="closeGap(g.id, 'dismissed')">
                                        <X class="size-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppShell>
</template>
