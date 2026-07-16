<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import Label from '@/Components/ui/Label.vue';
import Select from '@/Components/ui/Select.vue';
import Separator from '@/Components/ui/Separator.vue';
import Badge from '@/Components/ui/Badge.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PriorityBadge from '@/Components/PriorityBadge.vue';
import { fmtFullDateTimeAr, timeAgoAr } from '@/lib/date';
import { REQUEST_STATUS, REQUEST_PRIORITY, SERVICE_STATUS, statusLabel } from '@/lib/labels';
import { ArrowRight, Tag, Package, Clock, Paperclip, Star, MessageSquare, Lock } from 'lucide-vue-next';

const props = defineProps({
    request: { type: Object, required: true },
    comments: { type: Array, default: () => [] },
    activity: { type: Array, default: () => [] },
    attachments: { type: Array, default: () => [] },
    rating: { type: Object, default: null },
    isStaff: { type: Boolean, default: false },
    isOwner: { type: Boolean, default: false },
    can: { type: Object, default: () => ({}) },
});

const commentForm = useForm({ body: '', is_internal: false });
function addComment() {
    commentForm.post(`/requests/${props.request.id}/comments`, {
        preserveScroll: true, onSuccess: () => commentForm.reset('body'),
    });
}

const statusForm = useForm({ status: props.request.status, priority: props.request.priority });
function saveStatus() {
    statusForm.patch(`/requests/${props.request.id}`, { preserveScroll: true });
}

const rateForm = useForm({ stars: 5, notes: '' });
function submitRating() {
    rateForm.post(`/requests/${props.request.id}/rate`, { preserveScroll: true });
}

function closeReq() {
    if (confirm('إغلاق هذا الطلب؟')) router.post(`/requests/${props.request.id}/close`, {}, { preserveScroll: true });
}
function reopenReq() {
    router.post(`/requests/${props.request.id}/reopen`, {}, { preserveScroll: true });
}

const statusOptions = Object.entries(REQUEST_STATUS).map(([k, v]) => ({ k, label: v.label }));
const priorityOptions = Object.entries(REQUEST_PRIORITY).map(([k, v]) => ({ k, label: v.label }));
</script>

<template>
    <Head :title="request.request_number" />
    <AppShell>
        <div class="space-y-6">
            <div class="flex items-center gap-3">
                <Button href="/requests" variant="ghost" size="icon-sm"><ArrowRight class="size-4" /></Button>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-bold">{{ request.title }}</h1>
                    </div>
                    <p class="text-sm text-muted-foreground tabular-nums">{{ request.request_number }}</p>
                </div>
                <div class="mr-auto flex items-center gap-2">
                    <StatusBadge v-if="isStaff" :status="request.status" />
                    <Badge v-else :variant="statusLabel(SERVICE_STATUS, request.service_status).tone">
                        {{ statusLabel(SERVICE_STATUS, request.service_status).label }}
                    </Badge>
                    <PriorityBadge :priority="request.priority" />
                </div>
            </div>

            <!-- KPI ribbon -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <Card class="p-4"><div class="flex items-center gap-2 text-muted-foreground text-xs"><Tag class="size-4" /> التصنيف</div>
                    <p class="mt-1 font-bold">{{ request.category?.name_ar ?? '—' }}</p></Card>
                <Card class="p-4"><div class="flex items-center gap-2 text-muted-foreground text-xs"><Package class="size-4" /> المنتج</div>
                    <p class="mt-1 font-bold">{{ request.product?.name_ar ?? '—' }}</p></Card>
                <Card class="p-4"><div class="flex items-center gap-2 text-muted-foreground text-xs"><Clock class="size-4" /> العمر</div>
                    <p class="mt-1 font-bold">{{ timeAgoAr(request.created_at) }}</p></Card>
                <Card class="p-4"><div class="flex items-center gap-2 text-muted-foreground text-xs"><Paperclip class="size-4" /> المرفقات</div>
                    <p class="mt-1 font-bold tabular-nums">{{ request.attachments_count }}</p></Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    <Card>
                        <CardHeader><CardTitle>وصف الطلب</CardTitle></CardHeader>
                        <CardContent>
                            <p class="whitespace-pre-wrap leading-relaxed text-foreground/90">{{ request.description }}</p>
                        </CardContent>
                    </Card>

                    <!-- Conversation / comments -->
                    <Card>
                        <CardHeader><CardTitle class="flex items-center gap-2"><MessageSquare class="size-5" /> المحادثة والنشاط</CardTitle></CardHeader>
                        <CardContent class="space-y-4">
                            <div v-if="!comments.length" class="text-sm text-muted-foreground">لا توجد تعليقات بعد.</div>
                            <div v-for="c in comments" :key="c.id"
                                :class="['rounded-lg border p-3', c.is_internal ? 'border-warning/40 bg-warning/5' : 'border-border bg-muted/30']">
                                <div class="mb-1 flex items-center gap-2 text-sm">
                                    <span class="font-bold">{{ c.author_name }}</span>
                                    <Badge v-if="c.is_internal" variant="warning" class="gap-1"><Lock class="size-3" /> داخلي</Badge>
                                    <span class="mr-auto text-xs text-muted-foreground">{{ timeAgoAr(c.created_at) }}</span>
                                </div>
                                <p class="whitespace-pre-wrap text-sm text-foreground/90">{{ c.body }}</p>
                            </div>

                            <Separator />
                            <div>
                                <Textarea v-model="commentForm.body" placeholder="اكتب ردًا…" class="min-h-20" />
                                <div class="mt-2 flex items-center justify-between">
                                    <label v-if="can.comment_internal" class="flex items-center gap-2 text-sm text-muted-foreground">
                                        <input type="checkbox" v-model="commentForm.is_internal" class="size-4 rounded border-input" /> ملاحظة داخلية
                                    </label>
                                    <span v-else></span>
                                    <Button size="sm" :disabled="commentForm.processing || !commentForm.body" @click="addComment">إرسال</Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="space-y-6">
                    <!-- Staff actions -->
                    <Card v-if="isStaff">
                        <CardHeader><CardTitle>إجراءات</CardTitle></CardHeader>
                        <CardContent class="space-y-3">
                            <div v-if="can.update_status">
                                <Label>الحالة</Label>
                                <Select v-model="statusForm.status" class="mt-1">
                                    <option v-for="o in statusOptions" :key="o.k" :value="o.k">{{ o.label }}</option>
                                </Select>
                            </div>
                            <div v-if="can.change_priority">
                                <Label>الأولوية</Label>
                                <Select v-model="statusForm.priority" class="mt-1">
                                    <option v-for="o in priorityOptions" :key="o.k" :value="o.k">{{ o.label }}</option>
                                </Select>
                            </div>
                            <Button v-if="can.update_status || can.change_priority" size="sm" class="w-full" :disabled="statusForm.processing" @click="saveStatus">حفظ التغييرات</Button>
                            <Separator v-if="can.close || can.reopen" />
                            <Button v-if="can.close" variant="success" size="sm" class="w-full" @click="closeReq">إغلاق الطلب</Button>
                            <Button v-if="can.reopen" variant="warning" size="sm" class="w-full" @click="reopenReq">إعادة فتح</Button>
                        </CardContent>
                    </Card>

                    <!-- Customer info (staff) -->
                    <Card v-if="isStaff && request.customer">
                        <CardHeader><CardTitle>العميل</CardTitle></CardHeader>
                        <CardContent class="space-y-1 text-sm">
                            <p class="font-bold">{{ request.customer.full_name }}</p>
                            <p class="text-muted-foreground">{{ request.customer.email }}</p>
                            <p class="text-muted-foreground" dir="ltr">{{ request.customer.phone ?? '' }}</p>
                        </CardContent>
                    </Card>

                    <!-- Rating (customer) -->
                    <Card v-if="can.rate && !rating">
                        <CardHeader><CardTitle class="flex items-center gap-2"><Star class="size-5" /> قيّم الخدمة</CardTitle></CardHeader>
                        <CardContent class="space-y-3">
                            <div class="flex gap-1">
                                <button v-for="n in 5" :key="n" @click="rateForm.stars = n" type="button">
                                    <Star :class="['size-7 transition', n <= rateForm.stars ? 'fill-warning text-warning' : 'text-muted-foreground']" />
                                </button>
                            </div>
                            <Textarea v-model="rateForm.notes" placeholder="ملاحظاتك (اختياري)" class="min-h-16" />
                            <Button size="sm" class="w-full" variant="accent" :disabled="rateForm.processing" @click="submitRating">إرسال التقييم</Button>
                        </CardContent>
                    </Card>
                    <Card v-else-if="rating">
                        <CardHeader><CardTitle class="flex items-center gap-2"><Star class="size-5" /> تقييمك</CardTitle></CardHeader>
                        <CardContent>
                            <div class="flex gap-1">
                                <Star v-for="n in 5" :key="n" :class="['size-6', n <= rating.stars ? 'fill-warning text-warning' : 'text-muted-foreground']" />
                            </div>
                            <p v-if="rating.notes" class="mt-2 text-sm text-muted-foreground">{{ rating.notes }}</p>
                        </CardContent>
                    </Card>

                    <!-- Meta -->
                    <Card>
                        <CardContent class="space-y-2 pt-6 text-sm">
                            <div class="flex justify-between"><span class="text-muted-foreground">أُنشئ</span><span>{{ fmtFullDateTimeAr(request.created_at) }}</span></div>
                            <div class="flex justify-between"><span class="text-muted-foreground">آخر تحديث</span><span>{{ timeAgoAr(request.updated_at) }}</span></div>
                            <div v-if="request.assignee" class="flex justify-between"><span class="text-muted-foreground">المسند إليه</span><span>{{ request.assignee.full_name }}</span></div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppShell>
</template>
