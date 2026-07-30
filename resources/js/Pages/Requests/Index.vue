<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import Badge from '@/Components/ui/Badge.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import SortableTh from '@/Components/ui/SortableTh.vue';
import TableHead from '@/Components/ui/TableHead.vue';
import ConfirmDialog from '@/Components/ui/ConfirmDialog.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import {
    PlusCircle, ExternalLink, Paperclip, Star, RotateCcw, Inbox,
    Sparkles, Loader2, Hourglass, AlertTriangle, Clock, CheckCircle2, LayoutGrid,
    MoreHorizontal, Eye, MessageSquare, Pencil, UserCheck, Copy, Trash2,
    ChevronLeft, ArrowUpDown, ArrowUp, ArrowDown, X,
} from 'lucide-vue-next';
import { num } from '@/lib/utils';
import { timeAgoAr } from '@/lib/date';
import { REQUEST_PRIORITY, SERVICE_STATUS, statusLabel } from '@/lib/labels';

const props = defineProps({
    requests: { type: Object, default: () => ({ data: [], total: 0 }) },
    counts: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    isStaff: { type: Boolean, default: false },
    isAdmin: { type: Boolean, default: false },
    options: { type: Object, default: null },
});

const q = ref(props.filters.q ?? '');

function apply(patch = {}) {
    router.get('/requests', { ...props.filters, q: q.value, ...patch },
        { preserveState: true, replace: true, preserveScroll: true });
}
let t;
watch(q, () => { clearTimeout(t); t = setTimeout(() => apply(), 350); });

// KPI status pills.
const pills = computed(() => [
    { key: 'all', label: 'الكل', icon: LayoutGrid, tone: 'primary' },
    { key: 'new', label: 'جديدة', icon: Sparkles, tone: 'info' },
    { key: 'in_progress', label: 'قيد المعالجة', icon: Loader2, tone: 'accent' },
    { key: 'awaiting_customer', label: 'بانتظار العميل', icon: Hourglass, tone: 'warning' },
    { key: 'escalated', label: 'مُصعّدة', icon: AlertTriangle, tone: 'destructive' },
    { key: 'overdue', label: 'متأخرة', icon: Clock, tone: 'destructive' },
    { key: 'completed', label: 'مكتملة', icon: CheckCircle2, tone: 'success' },
    { key: 'reopened', label: 'أعيد فتحها', icon: RotateCcw, tone: 'warning' },
]);
const activeStatus = computed(() => props.filters.status ?? 'all');
const pillTones = {
    primary: 'data-[active=true]:bg-primary data-[active=true]:text-primary-foreground',
    info: 'data-[active=true]:bg-info data-[active=true]:text-info-foreground',
    accent: 'data-[active=true]:bg-accent data-[active=true]:text-accent-foreground',
    warning: 'data-[active=true]:bg-warning data-[active=true]:text-warning-foreground',
    destructive: 'data-[active=true]:bg-destructive data-[active=true]:text-destructive-foreground',
    success: 'data-[active=true]:bg-success data-[active=true]:text-success-foreground',
};

const priorityBar = { urgent: 'bg-destructive', high: 'bg-warning', medium: 'bg-info', low: 'bg-muted-foreground/40' };
const SLA = {
    out: { label: 'خارج', class: 'text-destructive' }, warn: { label: 'إغلاق وشيك', class: 'text-warning' },
    in: { label: 'ضمن', class: 'text-success' }, paused: { label: 'موقوف', class: 'text-muted-foreground' },
    none: { label: '—', class: 'text-muted-foreground' },
};

// --- Sorting ---
const sort = computed(() => props.filters.sort ?? 'updated');
const dir = computed(() => props.filters.dir ?? 'desc');
function setSort(key) {
    const nextDir = sort.value === key && dir.value === 'desc' ? 'asc' : 'desc';
    apply({ sort: key, dir: nextDir });
}
function sortIcon(key) {
    if (sort.value !== key) return ArrowUpDown;
    return dir.value === 'asc' ? ArrowUp : ArrowDown;
}

// --- Bulk selection ---
const selected = ref(new Set());
function toggleRow(id) { const s = new Set(selected.value); s.has(id) ? s.delete(id) : s.add(id); selected.value = s; }
const pageIds = computed(() => props.requests.data.map((r) => r.id));
const allSelected = computed(() => pageIds.value.length > 0 && pageIds.value.every((id) => selected.value.has(id)));
function toggleAll() {
    const s = new Set(selected.value);
    if (allSelected.value) pageIds.value.forEach((id) => s.delete(id));
    else pageIds.value.forEach((id) => s.add(id));
    selected.value = s;
}
function clearSelection() { selected.value = new Set(); }
const selectedNumbers = computed(() => props.requests.data.filter((r) => selected.value.has(r.id)).map((r) => r.request_number));
function copyNumbers() { navigator.clipboard?.writeText(selectedNumbers.value.join('\n')); }
function bulkAssign() {
    router.post('/requests/bulk/assign-self', { ids: [...selected.value] }, { preserveScroll: true, onSuccess: clearSelection });
}
watch(() => props.requests.data, clearSelection);

// --- Row actions menu (teleported, fixed position so the scroll container never clips it) ---
const openMenu = ref(null);
const menuPos = ref({ top: 0, left: 0 });
const MENU_W = 208; // w-52
const MENU_H = 300;
function toggleMenu(id, ev) {
    if (openMenu.value === id) { openMenu.value = null; return; }
    const r = ev.currentTarget.getBoundingClientRect();
    // Open inward (RTL: the actions column sits on the left, so extend rightward).
    let left = r.left;
    if (left + MENU_W > window.innerWidth - 8) left = window.innerWidth - MENU_W - 8;
    if (left < 8) left = 8;
    let top = r.bottom + 4;
    if (top + MENU_H > window.innerHeight - 8) top = r.top - MENU_H; // flip up near the bottom
    menuPos.value = { top: Math.max(8, top), left };
    openMenu.value = id;
}
const openRequest = computed(() => props.requests.data.find((r) => r.id === openMenu.value) ?? null);
function open(id) { router.visit(`/requests/${id}`); }
function assignMe(id) { openMenu.value = null; router.post(`/requests/${id}/assign-self`, {}, { preserveScroll: true }); }
function copyNumber(n) { openMenu.value = null; navigator.clipboard?.writeText(n); }

// --- Delete (admin) ---
const delTarget = ref(null);
const delReason = ref('');
function askDelete(r) { openMenu.value = null; delTarget.value = r; delReason.value = ''; }
function doDelete() {
    router.delete(`/requests/${delTarget.value.id}`, {
        data: { reason: delReason.value }, preserveScroll: true,
        onSuccess: () => { delTarget.value = null; },
    });
}

const isClosed = (r) => ['closed', 'completed', 'rejected', 'cancelled'].includes(r.status);
const title = computed(() => (props.isStaff ? 'صندوق الطلبات' : 'طلباتي'));
const subtitle = computed(() => (props.isStaff ? 'تابع الطلبات، أسندها، وراقب مؤشّرات SLA والأولوية في عرض واحد.' : 'متابعة طلباتك وتذاكرك في مكان واحد.'));
const colCount = computed(() => (props.isStaff ? 11 : 6));
</script>

<template>
    <Head :title="title" />
    <AppShell>
        <div @click="openMenu = null">
            <PageHeader :title="title" :subtitle="subtitle">
                <template #badge>
                    <Badge variant="muted">{{ num(requests.total) }} طلب</Badge>
                </template>
                <template #actions>
                    <Button href="/requests/new">
                        <PlusCircle class="size-4" /> {{ isStaff ? 'إضافة طلب لعميل' : 'طلب جديد' }}
                    </Button>
                </template>

                <!-- Status segments: the primary way people slice this list -->
                <div class="table-scroll -mx-1 px-1">
                    <div class="flex w-max min-w-full items-center gap-1.5">
                        <button v-for="p in pills" :key="p.key" type="button" @click="apply({ status: p.key })"
                            :data-active="activeStatus === p.key" :aria-pressed="activeStatus === p.key"
                            :class="['inline-flex shrink-0 items-center gap-2 rounded-md border border-border bg-card px-3 py-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:border-primary/40 hover:text-foreground data-[active=true]:border-transparent', pillTones[p.tone]]">
                            <component :is="p.icon" class="size-3.5" aria-hidden="true" />
                            <span>{{ p.label }}</span>
                            <span class="rounded-full bg-current/15 px-1.5 text-xs font-bold tabular-nums">{{ num(counts[p.key] ?? 0) }}</span>
                        </button>
                    </div>
                </div>
            </PageHeader>

            <div class="space-y-4">
            <!-- Filter bar -->
            <Card class="p-3">
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="sm:col-span-2 lg:col-span-1">
                        <Input v-model="q" label="بحث برقم الطلب أو العنوان…" />
                    </div>
                    <Select :model-value="filters.priority" header="مستوى الأولوية" @update:model-value="v => apply({ priority: v })">
                        <option value="all">كل الأولويات</option>
                        <option v-for="[k, m] in Object.entries(REQUEST_PRIORITY)" :key="k" :value="k" :data-dot="m.tone">{{ m.label }}</option>
                    </Select>
                    <template v-if="isStaff && options">
                        <Select :model-value="filters.category" @update:model-value="v => apply({ category: v })">
                            <option value="all">كل التصنيفات</option>
                            <option v-for="c in options.categories" :key="c.id" :value="c.id">{{ c.name_ar }}</option>
                        </Select>
                        <Select :model-value="filters.product" @update:model-value="v => apply({ product: v })">
                            <option value="all">كل المنتجات</option>
                            <option v-for="p in options.products" :key="p.id" :value="p.id">{{ p.name_ar }}</option>
                        </Select>
                        <Select :model-value="filters.assignee" @update:model-value="v => apply({ assignee: v })">
                            <option value="all">كل المسؤولين</option>
                            <option value="none">غير مُسند</option>
                            <option v-for="a in options.assignees" :key="a.id" :value="a.id">{{ a.full_name }}</option>
                        </Select>
                        <Select :model-value="filters.sla" @update:model-value="v => apply({ sla: v })">
                            <option value="all">كل حالات SLA</option>
                            <option value="out">خارج SLA</option>
                            <option value="warn">إغلاق وشيك</option>
                            <option value="in">ضمن SLA</option>
                            <option value="paused">موقوف</option>
                        </Select>
                        <Select :model-value="filters.rating" @update:model-value="v => apply({ rating: v })">
                            <option value="all">كل التقييمات</option>
                            <option value="high">تقييم ≥4★</option>
                            <option value="low">تقييم ≤2★</option>
                            <option value="none">بدون تقييم</option>
                        </Select>
                    </template>
                </div>
            </Card>

            <!-- Bulk action bar -->
            <Card v-if="isStaff && selected.size" class="flex flex-wrap items-center gap-2 border-primary/30 bg-primary-soft/50 p-3">
                <span class="text-base font-semibold">{{ num(selected.size) }} محدد</span>
                <div class="ms-auto flex flex-wrap gap-2">
                    <Button size="sm" variant="outline" @click="copyNumbers"><Copy class="size-4" /> نسخ الأرقام</Button>
                    <Button size="sm" variant="outline" @click="bulkAssign"><UserCheck class="size-4" /> إسناد إليّ</Button>
                    <Button size="sm" variant="ghost" @click="clearSelection"><X class="size-4" /> إلغاء التحديد</Button>
                </div>
            </Card>

            <!-- Table -->
            <Card class="overflow-hidden">
                <div class="table-scroll">
                    <table class="w-full border-collapse text-base">
                        <thead class="bg-muted/60">
                            <tr class="border-b border-border">
                                <th v-if="isStaff" class="w-10 px-2 py-2.5 text-center">
                                    <input type="checkbox" :checked="allSelected" @change="toggleAll" aria-label="تحديد كل الصفوف"
                                        class="size-4 rounded border-input align-middle accent-[var(--primary)]" />
                                </th>
                                <th class="w-12 px-2 py-2.5 text-center text-xs font-bold text-muted-foreground">#</th>
                                <SortableTh col="number" :sort-key="sort" :sort-dir="dir" @sort="setSort">الطلب</SortableTh>
                                <SortableTh col="status" :sort-key="sort" :sort-dir="dir" @sort="setSort">الحالة</SortableTh>
                                <TableHead>التصنيف</TableHead>
                                <TableHead>المنتج</TableHead>
                                <TableHead v-if="isStaff">العميل</TableHead>
                                <TableHead v-if="isStaff">المسؤول</TableHead>
                                <SortableTh v-if="isStaff" col="sla" :sort-key="sort" :sort-dir="dir" @sort="setSort">SLA</SortableTh>
                                <TableHead v-if="isStaff">التقييم</TableHead>
                                <SortableTh col="updated" :sort-key="sort" :sort-dir="dir" @sort="setSort">آخر تحديث</SortableTh>
                                <th class="w-10 px-2 py-2.5"><span class="sr-only">إجراءات</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(r, i) in requests.data" :key="r.id"
                                :data-state="selected.has(r.id) ? 'selected' : undefined"
                                class="group cursor-pointer border-b border-border transition-colors last:border-0 hover:bg-muted/50 data-[state=selected]:bg-primary-soft/60"
                                @click="open(r.id)">
                                <td v-if="isStaff" class="px-2 py-2.5 text-center" @click.stop>
                                    <input type="checkbox" :checked="selected.has(r.id)" @change="toggleRow(r.id)"
                                        :aria-label="`تحديد الطلب ${r.request_number}`"
                                        class="size-4 rounded border-input align-middle accent-[var(--primary)]" />
                                </td>
                                <td class="px-2 py-2.5">
                                    <span class="flex items-center gap-1.5">
                                        <span class="h-7 w-1 rounded-full" :class="priorityBar[r.priority] ?? 'bg-muted'"
                                            :title="statusLabel(REQUEST_PRIORITY, r.priority).label"></span>
                                        <span class="text-xs tabular-nums text-muted-foreground">{{ (requests.from ?? 1) + i }}</span>
                                    </span>
                                </td>
                                <td class="px-3 py-2.5">
                                    <span class="flex items-center gap-2">
                                        <span class="max-w-64 truncate font-semibold">{{ r.title }}</span>
                                        <RotateCcw v-if="r.reopened_count" class="size-3.5 shrink-0 text-warning" title="أُعيد فتحه" />
                                    </span>
                                    <span class="mt-1 flex items-center gap-2">
                                        <span class="ref-chip">{{ r.request_number }}</span>
                                        <span v-if="r.attachments_count" class="flex items-center gap-0.5 text-xs text-muted-foreground">
                                            <Paperclip class="size-3" />{{ num(r.attachments_count) }}
                                        </span>
                                    </span>
                                </td>
                                <td class="px-3 py-2.5">
                                    <StatusBadge v-if="isStaff" :status="r.status" />
                                    <Badge v-else :variant="statusLabel(SERVICE_STATUS, r.service_status).tone" dot>
                                        {{ statusLabel(SERVICE_STATUS, r.service_status).label }}
                                    </Badge>
                                </td>
                                <td class="px-3 py-2.5">
                                    <span class="flex items-center gap-1.5 text-sm">
                                        <span class="size-2 shrink-0 rounded-full" :style="{ background: r.category?.color || 'var(--muted-foreground)' }"></span>
                                        <span class="truncate">{{ r.category?.name_ar ?? '—' }}</span>
                                    </span>
                                </td>
                                <td class="px-3 py-2.5">
                                    <span v-if="r.product" class="flex items-center gap-1 text-sm text-muted-foreground">
                                        <ExternalLink class="size-3.5 shrink-0" /> <span class="truncate">{{ r.product.name_ar }}</span>
                                    </span>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <td v-if="isStaff" class="px-3 py-2.5">
                                    <span v-if="r.customer" class="flex items-center gap-2">
                                        <Avatar :name="r.customer.full_name" class="size-6 text-2xs" />
                                        <span class="max-w-32 truncate text-sm">{{ r.customer.full_name }}</span>
                                    </span>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <td v-if="isStaff" class="px-3 py-2.5">
                                    <span v-if="r.assignee" class="flex items-center gap-2">
                                        <Avatar :name="r.assignee.full_name" class="size-6 text-2xs" />
                                        <span class="max-w-32 truncate text-sm">{{ r.assignee.full_name }}</span>
                                    </span>
                                    <span v-else class="text-xs text-muted-foreground">غير مُسند</span>
                                </td>
                                <td v-if="isStaff" class="px-3 py-2.5">
                                    <span class="flex items-center gap-1 whitespace-nowrap text-xs font-bold" :class="SLA[r.sla_state]?.class">
                                        <span class="size-1.5 rounded-full bg-current"></span>{{ SLA[r.sla_state]?.label ?? '—' }}
                                    </span>
                                </td>
                                <td v-if="isStaff" class="px-3 py-2.5">
                                    <span v-if="r.rating" class="flex items-center gap-0.5 text-warning">
                                        <Star class="size-3.5 fill-current" /><span class="text-xs tabular-nums">{{ r.rating }}</span>
                                    </span>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5 text-xs text-muted-foreground">{{ timeAgoAr(r.updated_at) }}</td>

                                <!-- Row actions -->
                                <td class="px-2 py-2.5 text-center" @click.stop>
                                    <span class="flex items-center gap-0.5">
                                        <button type="button" aria-label="إجراءات الطلب" @click="toggleMenu(r.id, $event)"
                                            class="rounded-md p-1.5 text-muted-foreground opacity-70 transition-colors hover:bg-muted hover:text-foreground group-hover:opacity-100">
                                            <MoreHorizontal class="size-4" />
                                        </button>
                                        <ChevronLeft class="hidden size-4 text-muted-foreground/40 transition-colors group-hover:text-primary md:block" aria-hidden="true" />
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!requests.data.length">
                                <td :colspan="colCount">
                                    <EmptyState :icon="Inbox" title="لا توجد طلبات مطابقة"
                                        description="جرّب تعديل البحث أو عوامل التصفية أعلاه." />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>

            <Pagination :paginator="requests" />
            </div>
        </div>

        <!-- Row actions menu (teleported, never clipped, opens inward) -->
        <Teleport to="body">
            <div v-if="openRequest" class="fixed inset-0 z-40" @click="openMenu = null">
                <div dir="rtl" role="menu" class="fixed w-52 rounded-lg border border-border bg-popover p-1.5 text-start shadow-elevated"
                    :style="{ top: menuPos.top + 'px', left: menuPos.left + 'px' }" @click.stop>
                    <p class="px-2 py-1 text-2xs font-bold text-muted-foreground">إجراءات الطلب</p>
                    <button type="button" role="menuitem" class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-base hover:bg-muted" @click="open(openRequest.id)"><Eye class="size-4" /> فتح الطلب</button>
                    <button type="button" role="menuitem" class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-base hover:bg-muted" @click="open(openRequest.id)"><MessageSquare class="size-4" /> إضافة تعليق</button>
                    <template v-if="isStaff && !isClosed(openRequest)">
                        <button type="button" role="menuitem" class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-base hover:bg-muted" @click="open(openRequest.id)"><Pencil class="size-4" /> تعديل الحالة</button>
                        <button v-if="!openRequest.assignee" type="button" role="menuitem" class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-base hover:bg-muted" @click="assignMe(openRequest.id)"><UserCheck class="size-4" /> إسناد إليّ</button>
                    </template>
                    <div class="my-1 h-px bg-border"></div>
                    <button type="button" role="menuitem" class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-base hover:bg-muted" @click="copyNumber(openRequest.request_number)"><Copy class="size-4" /> نسخ رقم الطلب</button>
                    <template v-if="isAdmin">
                        <div class="my-1 h-px bg-border"></div>
                        <button type="button" role="menuitem" class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-base text-destructive hover:bg-destructive/10" @click="askDelete(openRequest)"><Trash2 class="size-4" /> حذف الطلب نهائيًا</button>
                    </template>
                </div>
            </div>
        </Teleport>

        <!-- Delete confirmation (admin) -->
        <ConfirmDialog :open="!!delTarget" title="حذف الطلب نهائيًا" confirm-label="حذف نهائيًا"
            :description="delTarget ? `سيتم حذف الطلب ${delTarget.request_number} وكل بياناته نهائيًا. لا يمكن التراجع.` : ''"
            @cancel="delTarget = null" @confirm="doDelete">
            <Textarea v-model="delReason" label="سبب الحذف (اختياري)" />
        </ConfirmDialog>
    </AppShell>
</template>
