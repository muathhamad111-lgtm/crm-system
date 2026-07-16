<script setup>
import { computed, ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import Select from '@/Components/ui/Select.vue';
import Separator from '@/Components/ui/Separator.vue';
import Badge from '@/Components/ui/Badge.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PriorityBadge from '@/Components/PriorityBadge.vue';
import { fmtFullDateTimeAr, timeAgoAr } from '@/lib/date';
import { REQUEST_STATUS, REQUEST_PRIORITY, SERVICE_STATUS, statusLabel } from '@/lib/labels';
import {
    ArrowRight, Tag, Package, Clock, Paperclip, Star, MessageSquare, Lock,
    GaugeCircle, UserCog, ArrowUpCircle, ListTodo, CheckCircle2, CircleDot,
    Undo2, Play, RotateCcw, ShieldCheck, Activity, Plus,
    ClipboardCheck, Upload, FileText, Trash2, Download, Zap,
} from 'lucide-vue-next';

const props = defineProps({
    request: { type: Object, required: true },
    fieldValues: { type: Array, default: () => [] },
    stages: { type: Array, default: () => [] },
    tasks: { type: Array, default: () => [] },
    staffCandidates: { type: Array, default: () => [] },
    sla: { type: Object, default: null },
    comments: { type: Array, default: () => [] },
    activity: { type: Array, default: () => [] },
    attachments: { type: Array, default: () => [] },
    rating: { type: Object, default: null },
    isStaff: { type: Boolean, default: false },
    isOwner: { type: Boolean, default: false },
    can: { type: Object, default: () => ({}) },
});

const r = computed(() => props.request);
const post = (action, data = {}, opts = {}) => router.post(`/requests/${r.value.id}/${action}`, data, { preserveScroll: true, ...opts });

// --- Workflow stages ---
const STAGES = [
    { i: 0, name: 'الاستلام' }, { i: 1, name: 'الفرز' }, { i: 2, name: 'التحليل' },
    { i: 3, name: 'التشخيص' }, { i: 4, name: 'المعالجة' }, { i: 5, name: 'الاعتماد' }, { i: 6, name: 'الإنجاز' },
];
const currentStage = computed(() => props.request.current_stage_index ?? 0);
function goStage(s) { post('transition-stage', { stage_index: s.i, stage_name: s.name }); }

// --- SLA display ---
const slaTierClass = computed(() => ({
    breach: 'sla-card-breach', critical: 'sla-card-critical', warn: 'sla-card-warn', ok: '',
}[props.sla?.tier] ?? ''));
const slaGlow = computed(() => ({
    breach: 'sla-glow-breach', critical: 'sla-glow-critical', warn: 'sla-glow-warn', ok: '',
}[props.sla?.tier] ?? ''));
const slaRemaining = computed(() => {
    if (!props.sla) return '';
    const m = props.sla.remaining_minutes;
    if (props.sla.paused) return 'موقوف مؤقتًا';
    const abs = Math.abs(m);
    const h = Math.floor(abs / 60), min = abs % 60;
    const txt = h > 0 ? `${h} س ${min} د` : `${min} د`;
    return m < 0 ? `تجاوز ${txt}` : `متبقٍ ${txt}`;
});

// --- Comment composer ---
const commentForm = useForm({ body: '', is_internal: false });
function addComment() {
    commentForm.post(`/requests/${r.value.id}/comments`, { preserveScroll: true, onSuccess: () => commentForm.reset('body') });
}

// --- Status / priority ---
const statusForm = useForm({ status: r.value.status, priority: r.value.priority });
function saveStatus() { statusForm.patch(`/requests/${r.value.id}`, { preserveScroll: true }); }

// --- Assignment ---
const assignForm = useForm({ assigned_to: r.value.assignee?.id ?? '' });
function reassign() { assignForm.patch(`/requests/${r.value.id}`, { preserveScroll: true }); }

// --- Tasks ---
const taskForm = useForm({ title: '', priority: 'medium' });
function addTask() { taskForm.post(`/requests/${r.value.id}/tasks`, { preserveScroll: true, onSuccess: () => taskForm.reset() }); }
function setTaskStatus(t, status) { router.patch(`/requests/${r.value.id}/tasks/${t.id}`, { status }, { preserveScroll: true }); }
function toggleItem(item) { post(`checklist/${item.id}`); }

// --- Dialogs (escalate / return / close) ---
const escalateOpen = ref(false); const escalateForm = useForm({ reason: '' });
function doEscalate() { escalateForm.post(`/requests/${r.value.id}/escalate`, { preserveScroll: true, onSuccess: () => { escalateOpen.value = false; escalateForm.reset(); } }); }

const returnOpen = ref(false); const returnForm = useForm({ reason: '' });
function doReturn() { returnForm.post(`/requests/${r.value.id}/return-to-customer`, { preserveScroll: true, onSuccess: () => { returnOpen.value = false; returnForm.reset(); } }); }

const closeOpen = ref(false); const closeForm = useForm({ closure_reason_code: 'resolved', closure_reason_public: '' });
function doClose() { closeForm.post(`/requests/${r.value.id}/close`, { preserveScroll: true, onSuccess: () => { closeOpen.value = false; } }); }

function reopen() { post('reopen', { reason: '' }); }
function resume() { post('resume'); }
function assignSelf() { post('assign-self'); }

// --- Tech-escalation bypass ---
const bypassOpen = ref(false);
const bypassForm = useForm({ reason_code: 'low_complexity', description: '' });
function doBypass() { bypassForm.post(`/requests/${r.value.id}/tech-bypass`, { preserveScroll: true, onSuccess: () => { bypassOpen.value = false; bypassForm.reset(); } }); }
function approveBypass() { post('tech-bypass/approve'); }

// --- Customer rating & verification ---
const rateForm = useForm({ stars: 5, notes: '' });
function submitRating() { rateForm.post(`/requests/${r.value.id}/rate`, { preserveScroll: true }); }
const verifyForm = useForm({ resolved: true, note: '' });
function verify(resolved) { verifyForm.resolved = resolved; verifyForm.post(`/requests/${r.value.id}/verify`, { preserveScroll: true }); }

// --- Attachments ---
const uploadForm = useForm({ file: null });
const fileInput = ref(null);
function onFile(e) { uploadForm.file = e.target.files[0]; if (uploadForm.file) upload(); }
function upload() {
    uploadForm.post(`/requests/${r.value.id}/attachments`, {
        forceFormData: true, preserveScroll: true,
        onSuccess: () => { uploadForm.reset(); if (fileInput.value) fileInput.value.value = ''; },
    });
}
function removeAttachment(a) {
    if (confirm('حذف المرفق؟')) router.delete(`/requests/${r.value.id}/attachments/${a.id}`, { preserveScroll: true });
}
function fileSize(bytes) {
    if (!bytes) return '';
    const kb = bytes / 1024;
    return kb > 1024 ? (kb / 1024).toFixed(1) + ' م.ب' : Math.round(kb) + ' ك.ب';
}

// --- Supervisor approval ---
const approvalForm = useForm({ note: '' });
function requestApproval() { approvalForm.post(`/requests/${r.value.id}/request-approval`, { preserveScroll: true, onSuccess: () => approvalForm.reset() }); }
const decideForm = useForm({ decision: 'approved', note: '' });
function decide(decision) { decideForm.decision = decision; decideForm.post(`/requests/${r.value.id}/decide-approval`, { preserveScroll: true, onSuccess: () => decideForm.reset() }); }
const approvalMeta = {
    pending: { label: 'بانتظار الموافقة', tone: 'warning' },
    approved: { label: 'معتمد', tone: 'success' },
    rejected: { label: 'مرفوض', tone: 'destructive' },
};

// --- Unified timeline (comments + activity merged) ---
const timeline = computed(() => {
    const c = props.comments.map((x) => ({ kind: 'comment', at: x.created_at, ...x }));
    const a = props.activity.map((x) => ({ kind: 'activity', at: x.created_at, ...x }));
    return [...c, ...a].sort((x, y) => new Date(y.at) - new Date(x.at));
});
const ageText = computed(() => timeAgoAr(r.value.created_at).replace('منذ ', ''));
const isTerminal = computed(() => ['closed', 'completed', 'rejected', 'cancelled'].includes(r.value.status));

const statusOptions = Object.entries(REQUEST_STATUS).map(([k, v]) => ({ k, label: v.label }));
const priorityOptions = Object.entries(REQUEST_PRIORITY).map(([k, v]) => ({ k, label: v.label }));
const taskStatusMeta = { todo: { label: 'قيد الانتظار', tone: 'muted' }, in_progress: { label: 'قيد التنفيذ', tone: 'accent' }, blocked: { label: 'معلّقة', tone: 'warning' }, done: { label: 'مكتملة', tone: 'success' }, cancelled: { label: 'ملغاة', tone: 'muted' } };
</script>

<template>
    <Head :title="request.request_number" />
    <AppShell>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-wrap items-center gap-3">
                <Button href="/requests" variant="ghost" size="icon-sm"><ArrowRight class="size-4" /></Button>
                <div class="min-w-0">
                    <h1 class="truncate text-xl font-bold">{{ request.title }}</h1>
                    <p class="text-sm text-muted-foreground tabular-nums">{{ request.request_number }}</p>
                </div>
                <div class="mr-auto flex flex-wrap items-center gap-2">
                    <Badge v-if="request.reopened_count" variant="warning" class="gap-1"><RotateCcw class="size-3" /> أُعيد فتحه ×{{ request.reopened_count }}</Badge>
                    <Badge v-if="request.escalation_level" variant="destructive" class="gap-1"><ArrowUpCircle class="size-3" /> مصعّد م{{ request.escalation_level }}</Badge>
                    <StatusBadge v-if="isStaff" :status="request.status" />
                    <Badge v-else :variant="statusLabel(SERVICE_STATUS, request.service_status).tone">{{ statusLabel(SERVICE_STATUS, request.service_status).label }}</Badge>
                    <PriorityBadge :priority="request.priority" />
                </div>
            </div>

            <!-- Progress -->
            <div v-if="request.progress != null" class="sla-progress-track">
                <div class="sla-progress-fill bg-primary" :style="{ width: request.progress + '%' }"></div>
            </div>

            <!-- KPI ribbon -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                <Card v-if="isStaff && sla" :class="['p-4', slaTierClass]">
                    <div class="flex items-center gap-2 text-muted-foreground text-xs"><GaugeCircle class="size-4" /> SLA</div>
                    <p :class="['mt-1 font-bold text-sm', slaGlow]">{{ slaRemaining }}</p>
                </Card>
                <Card class="p-4"><div class="flex items-center gap-2 text-muted-foreground text-xs"><CircleDot class="size-4" /> المرحلة</div>
                    <p class="mt-1 font-bold text-sm">{{ request.current_stage_name ?? STAGES[currentStage]?.name ?? '—' }}</p></Card>
                <Card class="p-4"><div class="flex items-center gap-2 text-muted-foreground text-xs"><Tag class="size-4" /> التصنيف</div>
                    <p class="mt-1 font-bold text-sm truncate">{{ request.category?.name_ar ?? '—' }}</p></Card>
                <Card class="p-4"><div class="flex items-center gap-2 text-muted-foreground text-xs"><Package class="size-4" /> المنتج</div>
                    <p class="mt-1 font-bold text-sm truncate">{{ request.product?.name_ar ?? '—' }}</p></Card>
                <Card class="p-4"><div class="flex items-center gap-2 text-muted-foreground text-xs"><Clock class="size-4" /> العمر</div>
                    <p class="mt-1 font-bold text-sm">{{ ageText }}</p></Card>
                <Card class="p-4"><div class="flex items-center gap-2 text-muted-foreground text-xs"><Paperclip class="size-4" /> المرفقات</div>
                    <p class="mt-1 font-bold tabular-nums">{{ request.attachments_count }}</p></Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Main column -->
                <div class="space-y-6 lg:col-span-2">
                    <Card>
                        <CardHeader><CardTitle>وصف الطلب</CardTitle></CardHeader>
                        <CardContent class="space-y-4">
                            <p class="whitespace-pre-wrap leading-relaxed text-foreground/90">{{ request.description }}</p>
                            <div v-if="fieldValues.length" class="grid gap-3 sm:grid-cols-2 border-t border-border pt-4">
                                <div v-for="(f, i) in fieldValues" :key="i">
                                    <p class="text-xs text-muted-foreground">{{ f.label }}</p>
                                    <p class="text-sm font-medium">{{ typeof f.value === 'object' ? JSON.stringify(f.value) : (f.value ?? '—') }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Attachments -->
                    <Card>
                        <CardHeader><CardTitle class="flex items-center gap-2"><Paperclip class="size-5" /> المرفقات <Badge variant="muted">{{ attachments.length }}</Badge></CardTitle></CardHeader>
                        <CardContent class="space-y-3">
                            <div v-for="a in attachments" :key="a.id" class="flex items-center gap-3 rounded-lg border border-border p-2.5">
                                <FileText class="size-5 shrink-0 text-muted-foreground" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium">{{ a.file_name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ fileSize(a.file_size) }}</p>
                                </div>
                                <a :href="a.file_url" target="_blank" class="rounded-md p-1.5 text-muted-foreground hover:bg-muted" title="تنزيل"><Download class="size-4" /></a>
                                <button v-if="isStaff" @click="removeAttachment(a)" class="rounded-md p-1.5 text-destructive hover:bg-muted" title="حذف"><Trash2 class="size-4" /></button>
                            </div>
                            <p v-if="!attachments.length" class="text-sm text-muted-foreground">لا توجد مرفقات.</p>
                            <div v-if="can.upload">
                                <input ref="fileInput" type="file" class="hidden" @change="onFile" />
                                <Button size="sm" variant="outline" class="w-full gap-1" :disabled="uploadForm.processing" @click="fileInput?.click()">
                                    <Upload class="size-4" /> {{ uploadForm.processing ? 'جارٍ الرفع…' : 'رفع مرفق' }}
                                </Button>
                                <p v-if="uploadForm.errors.file" class="mt-1 text-xs text-destructive">{{ uploadForm.errors.file }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Stage workflow (staff) -->
                    <Card v-if="isStaff">
                        <CardHeader><CardTitle class="flex items-center gap-2"><CircleDot class="size-5" /> مسار العمل</CardTitle></CardHeader>
                        <CardContent>
                            <div class="flex flex-wrap items-center gap-1">
                                <template v-for="(s, idx) in STAGES" :key="s.i">
                                    <button type="button" :disabled="!can.transition_stage || isTerminal" @click="goStage(s)"
                                        :class="['rounded-full px-3 py-1.5 text-xs font-medium transition-colors',
                                            s.i === currentStage ? 'bg-primary text-primary-foreground'
                                            : s.i < currentStage ? 'bg-success/15 text-success' : 'bg-muted text-muted-foreground hover:bg-secondary']">
                                        {{ s.name }}
                                    </button>
                                    <span v-if="idx < STAGES.length - 1" class="text-muted-foreground/40">←</span>
                                </template>
                            </div>
                            <p v-if="can.transition_stage && !isTerminal" class="mt-2 text-xs text-muted-foreground">اضغط أي مرحلة للانتقال إليها (يُعاد احتساب SLA حسب ساعات العمل).</p>
                        </CardContent>
                    </Card>

                    <!-- Tasks (staff) -->
                    <Card v-if="isStaff && can.manage_tasks">
                        <CardHeader><CardTitle class="flex items-center gap-2"><ListTodo class="size-5" /> المهام <Badge variant="muted">{{ tasks.length }}</Badge></CardTitle></CardHeader>
                        <CardContent class="space-y-3">
                            <div v-for="t in tasks" :key="t.id" class="rounded-lg border border-border p-3">
                                <div class="flex items-center gap-2">
                                    <CheckCircle2 :class="['size-4', t.status === 'done' ? 'text-success' : 'text-muted-foreground']" />
                                    <span :class="['flex-1 text-sm font-medium', t.status === 'done' && 'line-through text-muted-foreground']">{{ t.title }}</span>
                                    <Badge :variant="taskStatusMeta[t.status]?.tone ?? 'muted'">{{ taskStatusMeta[t.status]?.label ?? t.status }}</Badge>
                                    <Select :model-value="t.status" @update:model-value="(v) => setTaskStatus(t, v)" class="h-8 w-32 text-xs">
                                        <option v-for="(m, k) in taskStatusMeta" :key="k" :value="k">{{ m.label }}</option>
                                    </Select>
                                </div>
                                <div v-if="t.checklist?.length" class="mt-2 space-y-1 pr-6">
                                    <label v-for="item in t.checklist" :key="item.id" class="flex items-center gap-2 text-sm text-muted-foreground">
                                        <input type="checkbox" :checked="item.is_done" @change="toggleItem(item)" class="size-3.5 rounded border-input" />
                                        <span :class="item.is_done && 'line-through'">{{ item.label }}</span>
                                    </label>
                                </div>
                            </div>
                            <p v-if="!tasks.length" class="text-sm text-muted-foreground">لا توجد مهام.</p>
                            <Separator />
                            <div class="flex gap-2">
                                <Input v-model="taskForm.title" placeholder="مهمة جديدة…" class="flex-1" />
                                <Button size="sm" :disabled="taskForm.processing || !taskForm.title" @click="addTask"><Plus class="size-4" /> إضافة</Button>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Unified timeline -->
                    <Card>
                        <CardHeader><CardTitle class="flex items-center gap-2"><MessageSquare class="size-5" /> المحادثة والنشاط</CardTitle></CardHeader>
                        <CardContent class="space-y-4">
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
                            <Separator />
                            <div v-if="!timeline.length" class="text-sm text-muted-foreground">لا يوجد نشاط بعد.</div>
                            <div v-for="(ev, i) in timeline" :key="i" class="flex gap-3">
                                <div class="mt-1 flex size-7 shrink-0 items-center justify-center rounded-full"
                                    :class="ev.kind === 'comment' ? (ev.is_internal ? 'bg-warning/15 text-warning' : 'bg-primary-soft text-primary') : 'bg-muted text-muted-foreground'">
                                    <MessageSquare v-if="ev.kind === 'comment'" class="size-3.5" />
                                    <Activity v-else class="size-3.5" />
                                </div>
                                <div class="flex-1">
                                    <template v-if="ev.kind === 'comment'">
                                        <div class="flex items-center gap-2 text-sm">
                                            <span class="font-bold">{{ ev.author_name }}</span>
                                            <Badge v-if="ev.is_internal" variant="warning" class="gap-1"><Lock class="size-3" /> داخلي</Badge>
                                            <span class="mr-auto text-xs text-muted-foreground">{{ timeAgoAr(ev.at) }}</span>
                                        </div>
                                        <p class="mt-1 whitespace-pre-wrap rounded-lg bg-muted/30 p-2 text-sm">{{ ev.body }}</p>
                                    </template>
                                    <template v-else>
                                        <p class="text-sm"><span class="text-muted-foreground">{{ ev.action }}</span>
                                            <span v-if="ev.notes"> — {{ ev.notes }}</span></p>
                                        <p class="text-xs text-muted-foreground">{{ timeAgoAr(ev.at) }}</p>
                                    </template>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- SLA card (staff) -->
                    <Card v-if="isStaff && sla" :class="slaTierClass">
                        <CardHeader><CardTitle class="flex items-center gap-2"><GaugeCircle class="size-5" /> اتفاقية مستوى الخدمة</CardTitle></CardHeader>
                        <CardContent class="space-y-2 text-sm">
                            <div class="flex justify-between"><span class="text-muted-foreground">الموعد النهائي</span><span>{{ fmtFullDateTimeAr(sla.due_at) }}</span></div>
                            <div class="flex justify-between"><span class="text-muted-foreground">المتبقّي</span><span :class="['font-bold', slaGlow]">{{ slaRemaining }}</span></div>
                            <div v-if="sla.first_response_at" class="flex justify-between"><span class="text-muted-foreground">أول رد</span><span>{{ timeAgoAr(sla.first_response_at) }}</span></div>
                        </CardContent>
                    </Card>

                    <!-- Ownership (staff) -->
                    <Card v-if="isStaff">
                        <CardHeader><CardTitle class="flex items-center gap-2"><UserCog class="size-5" /> الإسناد</CardTitle></CardHeader>
                        <CardContent class="space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">المسند إليه</span>
                                <span class="font-medium">{{ request.assignee?.full_name ?? 'غير مُسند' }}</span>
                            </div>
                            <Button v-if="can.assign" size="sm" variant="outline" class="w-full" @click="assignSelf">إسناد إليّ</Button>
                            <template v-if="can.assign && staffCandidates.length">
                                <Select v-model="assignForm.assigned_to">
                                    <option value="">— اختر موظفًا —</option>
                                    <option v-for="s in staffCandidates" :key="s.id" :value="s.id">{{ s.full_name }}</option>
                                </Select>
                                <Button size="sm" class="w-full" :disabled="assignForm.processing" @click="reassign">إعادة الإسناد</Button>
                            </template>
                        </CardContent>
                    </Card>

                    <!-- Actions (staff) -->
                    <Card v-if="isStaff">
                        <CardHeader><CardTitle>الإجراءات</CardTitle></CardHeader>
                        <CardContent class="space-y-3">
                            <div v-if="can.update_status">
                                <Label>الحالة</Label>
                                <Select v-model="statusForm.status" class="mt-1"><option v-for="o in statusOptions" :key="o.k" :value="o.k">{{ o.label }}</option></Select>
                            </div>
                            <div v-if="can.change_priority">
                                <Label>الأولوية</Label>
                                <Select v-model="statusForm.priority" class="mt-1"><option v-for="o in priorityOptions" :key="o.k" :value="o.k">{{ o.label }}</option></Select>
                            </div>
                            <Button v-if="can.update_status || can.change_priority" size="sm" class="w-full" :disabled="statusForm.processing" @click="saveStatus">حفظ التغييرات</Button>
                            <Separator />
                            <Button v-if="can.escalate && !isTerminal" variant="destructive" size="sm" class="w-full gap-1" @click="escalateOpen = true"><ArrowUpCircle class="size-4" /> تصعيد</Button>
                            <Button v-if="can.tech_bypass" variant="outline" size="sm" class="w-full gap-1" @click="bypassOpen = true"><Zap class="size-4" /> تجاوز التصعيد التقني</Button>
                            <Button v-if="can.return_to_customer && !isTerminal" variant="warning" size="sm" class="w-full gap-1" @click="returnOpen = true"><Undo2 class="size-4" /> إعادة للعميل</Button>
                            <Button v-if="request.status === 'awaiting_customer' || request.status === 'awaiting_internal'" variant="outline" size="sm" class="w-full gap-1" @click="resume"><Play class="size-4" /> استئناف</Button>
                            <Button v-if="can.close && !isTerminal" variant="success" size="sm" class="w-full" @click="closeOpen = true">إغلاق الطلب</Button>
                            <Button v-if="can.reopen && isTerminal" variant="warning" size="sm" class="w-full gap-1" @click="reopen"><RotateCcw class="size-4" /> إعادة فتح</Button>
                        </CardContent>
                    </Card>

                    <!-- Supervisor approval -->
                    <Card v-if="isStaff && (request.approval_status || can.request_approval || can.approve)"
                        :class="request.approval_status === 'pending' && 'sla-card-warn'">
                        <CardHeader><CardTitle class="flex items-center gap-2"><ClipboardCheck class="size-5" /> الموافقة الإشرافية</CardTitle></CardHeader>
                        <CardContent class="space-y-3">
                            <div v-if="request.approval_status" class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">الحالة</span>
                                <Badge :variant="approvalMeta[request.approval_status]?.tone ?? 'muted'">{{ approvalMeta[request.approval_status]?.label ?? request.approval_status }}</Badge>
                            </div>
                            <p v-if="request.approval_notes" class="rounded-lg bg-muted/40 p-2 text-sm">{{ request.approval_notes }}</p>

                            <!-- Supervisor decides a pending approval -->
                            <template v-if="can.approve">
                                <Textarea v-model="decideForm.note" placeholder="ملاحظة القرار (اختياري)" class="min-h-16" />
                                <div class="flex gap-2">
                                    <Button size="sm" variant="success" class="flex-1" :disabled="decideForm.processing" @click="decide('approved')">اعتماد</Button>
                                    <Button size="sm" variant="destructive" class="flex-1" :disabled="decideForm.processing" @click="decide('rejected')">رفض</Button>
                                </div>
                            </template>

                            <!-- Assignee requests approval -->
                            <template v-else-if="can.request_approval">
                                <Textarea v-model="approvalForm.note" placeholder="سبب طلب الموافقة (اختياري)" class="min-h-16" />
                                <Button size="sm" variant="accent" class="w-full gap-1" :disabled="approvalForm.processing" @click="requestApproval">
                                    <ClipboardCheck class="size-4" /> رفع للموافقة الإشرافية
                                </Button>
                            </template>
                        </CardContent>
                    </Card>

                    <!-- Tech bypass state -->
                    <Card v-if="isStaff && request.tech_bypass_at" :class="!request.tech_bypass_approved && 'sla-card-warn'">
                        <CardHeader><CardTitle class="flex items-center gap-2"><Zap class="size-5" /> تجاوز التصعيد التقني</CardTitle></CardHeader>
                        <CardContent class="space-y-2 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">الحالة</span>
                                <Badge :variant="request.tech_bypass_approved ? 'success' : 'warning'">{{ request.tech_bypass_approved ? 'معتمد' : 'بانتظار الاعتماد' }}</Badge>
                            </div>
                            <p class="text-muted-foreground">{{ request.tech_bypass_reason_code }}</p>
                            <p v-if="request.tech_bypass_description">{{ request.tech_bypass_description }}</p>
                            <Button v-if="can.approve_tech_bypass" size="sm" variant="success" class="w-full" @click="approveBypass">اعتماد التجاوز</Button>
                        </CardContent>
                    </Card>

                    <!-- Customer info (staff) -->
                    <Card v-if="isStaff && request.customer">
                        <CardHeader><CardTitle>العميل</CardTitle></CardHeader>
                        <CardContent class="space-y-1 text-sm">
                            <p class="font-bold">{{ request.customer.full_name }}</p>
                            <p class="text-muted-foreground" dir="ltr">{{ request.customer.email }}</p>
                            <p class="text-muted-foreground" dir="ltr">{{ request.customer.phone ?? '' }}</p>
                        </CardContent>
                    </Card>

                    <!-- Customer verification -->
                    <Card v-if="can.verify" class="card-glow-animated">
                        <CardHeader><CardTitle class="flex items-center gap-2"><ShieldCheck class="size-5" /> هل تم حلّ طلبك؟</CardTitle></CardHeader>
                        <CardContent class="space-y-3">
                            <p class="text-sm text-muted-foreground">ساعدنا بتأكيد ما إذا تمت معالجة طلبك.</p>
                            <Textarea v-model="verifyForm.note" placeholder="ملاحظاتك (اختياري)" class="min-h-16" />
                            <div class="flex gap-2">
                                <Button size="sm" variant="success" class="flex-1" :disabled="verifyForm.processing" @click="verify(true)">نعم، تم الحل</Button>
                                <Button size="sm" variant="outline" class="flex-1" :disabled="verifyForm.processing" @click="verify(false)">لا، ما زالت قائمة</Button>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Rating -->
                    <Card v-if="can.rate && !rating">
                        <CardHeader><CardTitle class="flex items-center gap-2"><Star class="size-5" /> قيّم الخدمة</CardTitle></CardHeader>
                        <CardContent class="space-y-3">
                            <div class="flex gap-1">
                                <button v-for="n in 5" :key="n" type="button" @click="rateForm.stars = n">
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
                            <div class="flex gap-1"><Star v-for="n in 5" :key="n" :class="['size-6', n <= rating.stars ? 'fill-warning text-warning' : 'text-muted-foreground']" /></div>
                            <p v-if="rating.notes" class="mt-2 text-sm text-muted-foreground">{{ rating.notes }}</p>
                        </CardContent>
                    </Card>

                    <!-- Closure summary -->
                    <Card v-if="isTerminal && request.closure_reason_public" class="animate-pulse-glow">
                        <CardHeader><CardTitle class="flex items-center gap-2"><CheckCircle2 class="size-5 text-success" /> ملخّص الإغلاق</CardTitle></CardHeader>
                        <CardContent class="text-sm">
                            <p class="text-muted-foreground">{{ request.closure_reason_code }}</p>
                            <p class="mt-1">{{ request.closure_reason_public }}</p>
                        </CardContent>
                    </Card>

                    <!-- Meta -->
                    <Card><CardContent class="space-y-2 pt-6 text-sm">
                        <div class="flex justify-between"><span class="text-muted-foreground">أُنشئ</span><span>{{ fmtFullDateTimeAr(request.created_at) }}</span></div>
                        <div class="flex justify-between"><span class="text-muted-foreground">آخر تحديث</span><span>{{ timeAgoAr(request.updated_at) }}</span></div>
                        <div v-if="request.closed_at" class="flex justify-between"><span class="text-muted-foreground">أُغلق</span><span>{{ fmtFullDateTimeAr(request.closed_at) }}</span></div>
                    </CardContent></Card>
                </div>
            </div>
        </div>

        <!-- Dialogs -->
        <Dialog v-model:open="escalateOpen" title="تصعيد الطلب" description="سيُرفع الطلب لمستوى أعلى وتُبلّغ الإدارة.">
            <div class="space-y-3">
                <Textarea v-model="escalateForm.reason" placeholder="سبب التصعيد" class="min-h-20" />
                <div class="flex justify-end gap-2"><Button variant="outline" @click="escalateOpen = false">إلغاء</Button><Button variant="destructive" :disabled="escalateForm.processing" @click="doEscalate">تصعيد</Button></div>
            </div>
        </Dialog>
        <Dialog v-model:open="returnOpen" title="إعادة الطلب للعميل" description="سيُنتظر ردّ العميل، مع مهلة إغلاق تلقائي.">
            <div class="space-y-3">
                <Textarea v-model="returnForm.reason" placeholder="ما المطلوب من العميل؟" class="min-h-20" />
                <div class="flex justify-end gap-2"><Button variant="outline" @click="returnOpen = false">إلغاء</Button><Button variant="warning" :disabled="returnForm.processing || !returnForm.reason" @click="doReturn">إرسال للعميل</Button></div>
            </div>
        </Dialog>
        <Dialog v-model:open="bypassOpen" title="تجاوز التصعيد التقني" description="تخطّي خطوة التصعيد التقني بمبرّر موثّق.">
            <div class="space-y-3">
                <div><Label>سبب التجاوز</Label>
                    <Select v-model="bypassForm.reason_code" class="mt-1">
                        <option value="low_complexity">تعقيد منخفض</option>
                        <option value="known_issue">مشكلة معروفة/حل جاهز</option>
                        <option value="customer_urgency">إلحاح العميل</option>
                        <option value="already_diagnosed">تم التشخيص مسبقًا</option>
                        <option value="other">أخرى</option>
                    </Select></div>
                <Textarea v-model="bypassForm.description" placeholder="مبرّر التجاوز" class="min-h-20" />
                <p v-if="bypassForm.errors.description" class="text-xs text-destructive">{{ bypassForm.errors.description }}</p>
                <div class="flex justify-end gap-2"><Button variant="outline" @click="bypassOpen = false">إلغاء</Button><Button :disabled="bypassForm.processing || !bypassForm.description" @click="doBypass">تنفيذ</Button></div>
            </div>
        </Dialog>
        <Dialog v-model:open="closeOpen" title="إغلاق الطلب">
            <div class="space-y-3">
                <div><Label>سبب الإغلاق</Label>
                    <Select v-model="closeForm.closure_reason_code" class="mt-1">
                        <option value="resolved">تم الحل</option><option value="duplicate">مكرر</option>
                        <option value="not_reproducible">تعذّر إعادة الإنتاج</option><option value="wont_fix">لن يُعالج</option>
                        <option value="other">أخرى</option>
                    </Select></div>
                <Textarea v-model="closeForm.closure_reason_public" placeholder="ملخّص يظهر للعميل" class="min-h-20" />
                <div class="flex justify-end gap-2"><Button variant="outline" @click="closeOpen = false">إلغاء</Button><Button variant="success" :disabled="closeForm.processing" @click="doClose">إغلاق</Button></div>
            </div>
        </Dialog>
    </AppShell>
</template>
