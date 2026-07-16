<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHero from '@/Components/PageHero.vue';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import {
    ArrowRight, Edit, CheckCircle2, Archive, RotateCcw, Send, Star, Eye,
    ThumbsUp, ThumbsDown, History, BookOpen, AlertTriangle, ListChecks,
} from 'lucide-vue-next';
import { fmtFullDateTimeAr, fmtDateAr } from '@/lib/date';
import { num } from '@/lib/utils';

const props = defineProps({
    article: { type: Object, required: true },
    category: { type: Object, default: null },
    product: { type: Object, default: null },
    myRating: { type: Object, default: null },
    myFeedback: { type: Object, default: null },
    versions: { type: Array, default: () => [] },
    authorName: { type: String, default: null },
    approverName: { type: String, default: null },
    currentUserId: { type: String, default: null },
    can: { type: Object, default: () => ({}) },
});

const a = props.article;

const KB_TYPE_LABELS = {
    faq: 'أسئلة شائعة', sop: 'إجراء تشغيلي (SOP)', known_issue: 'مشكلة معروفة',
    resolution: 'حل جاهز', macro: 'رد جاهز (Macro)', policy: 'سياسة', user_guide: 'دليل استخدام',
};
const KB_STATUS = {
    draft: { label: 'مسودة', tone: 'muted' },
    in_review: { label: 'قيد المراجعة', tone: 'warning' },
    approved: { label: 'معتمد', tone: 'success' },
    archived: { label: 'مؤرشف', tone: 'muted' },
};
const KB_COMPLEXITY = { beginner: 'مبتدئ', intermediate: 'متوسط', advanced: 'متقدم' };

const ratingValue = ref(props.myRating?.rating ?? 0);
const ratingComment = ref(props.myRating?.comment ?? '');
const showVersions = ref(false);
const saving = ref(false);

const canEdit =
    (props.can.author && a.author_id === props.currentUserId && ['draft', 'in_review'].includes(a.status)) ||
    props.can.approve || props.can.manage;

function setStatus(status) {
    router.post(`/knowledge-base/${a.id}/status`, { status }, { preserveScroll: true });
}
function rate() {
    if (ratingValue.value < 1) return;
    saving.value = true;
    router.post(`/knowledge-base/${a.id}/rate`, { rating: ratingValue.value, comment: ratingComment.value || null }, {
        preserveScroll: true,
        onFinish: () => { saving.value = false; },
    });
}
function feedback(helpful) {
    router.post(`/knowledge-base/${a.id}/feedback`, { was_helpful: helpful }, { preserveScroll: true });
}
</script>

<template>
    <Head :title="a.title" />
    <AppShell>
        <div class="glass-stage space-y-6">
            <PageHero :title="a.title" :subtitle="a.summary || undefined" :icon="BookOpen">
                <template #actions>
                    <Button href="/knowledge-base" variant="outline"
                        class="bg-white/12 border-white/25 text-white hover:bg-white/20">
                        <ArrowRight class="size-4" /> عودة
                    </Button>
                    <Button v-if="canEdit" :href="`/knowledge-base/${a.id}/edit`" variant="outline"
                        class="bg-white/12 border-white/25 text-white hover:bg-white/20">
                        <Edit class="size-4" /> تعديل
                    </Button>
                    <Button v-if="can.approve && a.status === 'in_review'" class="bg-white text-primary hover:bg-white/90"
                        @click="setStatus('approved')">
                        <CheckCircle2 class="size-4" /> اعتماد
                    </Button>
                    <Button v-if="can.approve && a.status === 'approved'" variant="outline"
                        class="bg-white/12 border-white/25 text-white hover:bg-white/20" @click="setStatus('archived')">
                        <Archive class="size-4" /> أرشفة
                    </Button>
                    <Button v-if="can.approve && a.status === 'archived'" variant="outline"
                        class="bg-white/12 border-white/25 text-white hover:bg-white/20" @click="setStatus('draft')">
                        <RotateCcw class="size-4" /> استعادة
                    </Button>
                    <Button v-if="can.author && a.status === 'draft' && a.author_id === currentUserId"
                        variant="outline" class="bg-white/12 border-white/25 text-white hover:bg-white/20"
                        @click="setStatus('in_review')">
                        <Send class="size-4" /> إرسال للمراجعة
                    </Button>
                </template>
            </PageHero>

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-[1fr_320px]">
                <!-- Main content -->
                <div class="min-w-0 space-y-4">
                    <Card>
                        <CardContent class="space-y-4 p-5">
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <Badge variant="outline">{{ KB_TYPE_LABELS[a.type] ?? a.type }}</Badge>
                                <Badge :variant="(KB_STATUS[a.status] ?? {}).tone ?? 'muted'">
                                    {{ (KB_STATUS[a.status] ?? {}).label ?? a.status }}
                                </Badge>
                                <Badge variant="muted">{{ KB_COMPLEXITY[a.complexity] ?? a.complexity }}</Badge>
                                <span class="text-muted-foreground">· الإصدار {{ a.current_version }}</span>
                            </div>

                            <div v-if="a.prerequisites"
                                class="rounded-lg border border-info/30 bg-info/10 p-3">
                                <div class="mb-1 flex items-center gap-1.5 text-xs font-semibold text-info">
                                    <ListChecks class="size-3.5" /> المتطلبات المسبقة
                                </div>
                                <div class="whitespace-pre-wrap text-sm text-foreground/90">{{ a.prerequisites }}</div>
                            </div>

                            <div v-if="a.warnings"
                                class="rounded-lg border border-warning/40 bg-warning/10 p-3">
                                <div class="mb-1 flex items-center gap-1.5 text-xs font-semibold text-warning">
                                    <AlertTriangle class="size-3.5" /> تحذيرات وتنبيهات
                                </div>
                                <div class="whitespace-pre-wrap text-sm text-foreground/90">{{ a.warnings }}</div>
                            </div>

                            <div class="prose prose-sm max-w-none whitespace-pre-wrap text-sm leading-7 text-foreground/90">
                                <template v-if="a.body">{{ a.body }}</template>
                                <span v-else class="text-muted-foreground">— لا يوجد محتوى —</span>
                            </div>

                            <div v-if="(a.keywords ?? []).length" class="border-t border-border pt-3">
                                <div class="mb-1.5 text-xs text-muted-foreground">كلمات مفتاحية</div>
                                <div class="flex flex-wrap gap-1.5">
                                    <span v-for="k in a.keywords" :key="k"
                                        class="rounded bg-muted px-2 py-0.5 text-xs text-muted-foreground">{{ k }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Helpful feedback + rating -->
                    <Card v-if="can.rate">
                        <CardHeader class="pb-2"><CardTitle class="text-sm">هل كان هذا المقال مفيداً؟</CardTitle></CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <Button size="sm" variant="outline"
                                    :class="myFeedback && myFeedback.was_helpful ? 'border-success text-success' : ''"
                                    @click="feedback(true)">
                                    <ThumbsUp class="size-4" /> نعم، أفادني
                                </Button>
                                <Button size="sm" variant="outline"
                                    :class="myFeedback && myFeedback.was_helpful === false ? 'border-destructive text-destructive' : ''"
                                    @click="feedback(false)">
                                    <ThumbsDown class="size-4" /> لم يفدني
                                </Button>
                            </div>

                            <div class="border-t border-border pt-3">
                                <div class="mb-2 flex items-center gap-2">
                                    <button v-for="n in 5" :key="n" type="button" class="p-0.5" @click="ratingValue = n">
                                        <Star class="size-6 transition-colors"
                                            :class="n <= ratingValue ? 'fill-warning text-warning' : 'text-muted-foreground'" />
                                    </button>
                                    <span class="mr-1 text-xs text-muted-foreground">
                                        {{ myRating ? 'قيّمت هذا المقال' : 'قيّم هذا المقال' }}
                                    </span>
                                </div>
                                <Textarea v-model="ratingComment" :rows="2" placeholder="ملاحظة اختيارية..." class="text-sm" />
                                <Button size="sm" class="mt-2" :disabled="saving || ratingValue === 0" @click="rate">
                                    حفظ التقييم
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Versions -->
                    <Card v-if="showVersions">
                        <CardHeader class="pb-2">
                            <CardTitle class="flex items-center gap-2 text-sm">
                                <History class="size-4" /> سجل الإصدارات
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <p v-if="!versions.length" class="text-xs text-muted-foreground">لا توجد إصدارات سابقة.</p>
                            <div v-for="v in versions" :key="v.id" class="rounded border border-border p-2 text-xs">
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="font-semibold">الإصدار {{ v.version_number }}</span>
                                    <span class="text-muted-foreground">{{ fmtFullDateTimeAr(v.created_at) }}</span>
                                </div>
                                <div class="font-medium text-foreground">{{ v.title }}</div>
                                <div v-if="v.change_note" class="mt-1 text-muted-foreground">{{ v.change_note }}</div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <aside class="space-y-3">
                    <Card>
                        <CardContent class="space-y-3 p-4 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">المشاهدات</span>
                                <span class="inline-flex items-center gap-1 font-semibold tabular-nums">
                                    <Eye class="size-3.5" /> {{ num(a.views_count ?? 0) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">إدراج كحل</span>
                                <span class="font-semibold tabular-nums">{{ num(a.insert_solution_count ?? 0) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">إرسال للعميل</span>
                                <span class="font-semibold tabular-nums">{{ num(a.sent_to_customer_count ?? 0) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">التقييم</span>
                                <span class="inline-flex items-center gap-1 font-semibold tabular-nums">
                                    <Star class="size-3.5 fill-warning text-warning" />
                                    {{ a.avg_rating ?? 0 }} ({{ num(a.rating_count ?? 0) }})
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">أفاد الحل</span>
                                <span class="inline-flex items-center gap-1 font-semibold text-success tabular-nums">
                                    <ThumbsUp class="size-3.5" /> {{ num(a.helpful_count ?? 0) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">لم يفد</span>
                                <span class="inline-flex items-center gap-1 font-semibold text-destructive tabular-nums">
                                    <ThumbsDown class="size-3.5" /> {{ num(a.not_helpful_count ?? 0) }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="space-y-2 p-4 text-xs">
                            <div>
                                <span class="text-muted-foreground">المنتج: </span>
                                <span class="font-medium text-foreground">
                                    {{ a.is_general ? 'عام (جميع المنتجات)' : (product?.name_ar ?? '— لا يوجد —') }}
                                </span>
                            </div>
                            <div v-if="category">
                                <span class="text-muted-foreground">التصنيف: </span>
                                <span class="font-medium text-foreground">{{ category.name_ar }}</span>
                            </div>
                            <div v-if="authorName">
                                <span class="text-muted-foreground">الكاتب: </span>
                                <span class="font-medium text-foreground">{{ authorName }}</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground">آخر تحديث: </span>{{ fmtDateAr(a.updated_at) }}
                            </div>
                            <div v-if="a.approved_at">
                                <span class="text-muted-foreground">اعتُمد: </span>{{ fmtDateAr(a.approved_at) }}
                                <span v-if="approverName" class="text-muted-foreground"> · {{ approverName }}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Button variant="outline" size="sm" class="w-full" @click="showVersions = !showVersions">
                        <History class="size-4" />
                        {{ showVersions ? 'إخفاء الإصدارات' : `عرض الإصدارات (${a.current_version})` }}
                    </Button>
                </aside>
            </div>
        </div>
    </AppShell>
</template>
