<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import Select from '@/Components/ui/Select.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import SettingRow from '@/Components/admin/SettingRow.vue';
import SortableTh from '@/Components/ui/SortableTh.vue';
import { useClientSort } from '@/lib/useSort';
import {
    Settings, Layers, Package, Users, ShieldCheck, Bell, Plug, FileSearch,
    Gauge, Network, Menu, ChevronLeft, UserPlus, Copy, Search, Save, X,
    Mail, MessageSquare, Webhook, KeyRound, Sparkles, Phone, Send, CheckCircle2,
    Wrench, Pencil,
} from 'lucide-vue-next';
import { ROLE_LABELS, REQUEST_PRIORITY, statusLabel } from '@/lib/labels';
import { fmtFullDateTimeAr, timeAgoAr } from '@/lib/date';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    teams: { type: Array, default: () => [] },
    settings: { type: Object, default: () => ({}) },
    multipliers: { type: Array, default: () => [] },
    staff: { type: Array, default: () => [] },
    capabilityCounts: { type: Object, default: () => ({}) },
    rolePermissions: { type: Object, default: () => ({}) },
    capabilityMeta: { type: Array, default: () => [] },
    notificationTemplates: { type: Array, default: () => [] },
    integrationSettings: { type: Array, default: () => [] },
    systemIntegrations: { type: Array, default: () => [] },
    auditLog: { type: Array, default: () => [] },
    options: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
    teamNames: { type: Object, default: () => ({}) },
});

const page = usePage();

/* ----------------------------- Sections nav ----------------------------- */
const SECTIONS = [
    { key: 'categories', label: 'التصنيفات وسير العمل', desc: 'إدارة تصنيفات الطلبات ومستهدفات SLA والفرق والأولويات.', icon: Layers, group: 'configure' },
    { key: 'products', label: 'المنتجات والخدمات', desc: 'كتالوج المنتجات والخدمات التي تُربط بالطلبات.', icon: Package, group: 'configure' },
    { key: 'teams', label: 'الفرق', desc: 'فرق العمل داخل المؤسسة ومسؤوليها.', icon: Network, group: 'configure' },
    { key: 'system', label: 'إعدادات النظام', desc: 'الإعدادات العامة: المرفقات، الترميز، القنوات، وغيرها.', icon: Settings, group: 'configure' },
    { key: 'staff', label: 'فريق العمل', desc: 'إدارة الموظفين وأدوارهم وفرقهم.', icon: Users, group: 'people' },
    { key: 'permissions', label: 'الصلاحيات', desc: 'الأدوار وما يستطيع كل دور رؤيته أو تنفيذه.', icon: ShieldCheck, group: 'people' },
    { key: 'sla', label: 'SLA والأولويات', desc: 'معاملات الأولوية المؤثّرة على مواعيد الاستحقاق.', icon: Gauge, group: 'ops' },
    { key: 'templates', label: 'الإشعارات والقوالب', desc: 'قوالب الرسائل وقنوات إرسالها.', icon: Bell, group: 'ops' },
    { key: 'integrations', label: 'التكاملات التقنية', desc: 'ربط النظام بخدمات خارجية: بريد، رسائل، Webhooks.', icon: Plug, group: 'ops' },
    { key: 'audit', label: 'سجل التدقيق', desc: 'تتبّع إجراءات المستخدمين الإداريين.', icon: FileSearch, group: 'ops' },
];
const GROUPS = {
    configure: 'الإعدادات والتكوين',
    people: 'الأشخاص والصلاحيات',
    ops: 'العمليات والمراقبة',
};
const groupKeys = ['configure', 'people', 'ops'];
const active = ref('categories');
const mobileOpen = ref(false);
const activeMeta = computed(() => SECTIONS.find((s) => s.key === active.value) ?? SECTIONS[0]);
function pick(key) { active.value = key; mobileOpen.value = false; }

/* ------------------------------ Hero KPIs ------------------------------ */
const heroPills = computed(() => [
    { label: 'التصنيفات', value: props.stats.categories ?? props.categories.length, icon: Layers },
    { label: 'الموظفون', value: props.stats.staff ?? props.staff.length, icon: Users },
    { label: 'الأدوار', value: props.stats.roles ?? 0, icon: ShieldCheck },
    { label: 'الصلاحيات', value: props.stats.capabilities ?? props.capabilityMeta.length, icon: Gauge },
    { label: 'التكاملات', value: props.stats.integrations ?? 0, icon: Plug },
    { label: 'القوالب', value: props.stats.templates ?? props.notificationTemplates.length, icon: Bell },
]);

function roleLabel(r) { return ROLE_LABELS[r] ?? r; }

/* --------------------------- Add employee ----------------------------- */
const staffRoles = ['support_staff', 'support_supervisor', 'product_team', 'product_manager', 'tech_team', 'tech_manager', 'management team', 'system_admin'];
const addOpen = ref(false);
const staffForm = useForm({ name: '', email: '', role: 'support_staff' });
const setupLink = computed(() => page.props.flash?.setup_link ?? null);
function submitStaff() {
    staffForm.post('/admin/staff', {
        preserveScroll: true,
        onSuccess: () => staffForm.reset('name', 'email'),
    });
}
function copyLink() {
    if (setupLink.value) navigator.clipboard?.writeText(setupLink.value);
}

/* ------------------------- Category edit dialog ----------------------- */
const catOpen = ref(false);
const catForm = useForm({ id: null, name_ar: '', default_priority: 'medium', target_team: '', sla_hours: '', active: true });
function openCat(c) {
    catForm.clearErrors();
    catForm.id = c.id;
    catForm.name_ar = c.name_ar ?? '';
    catForm.default_priority = c.default_priority ?? 'medium';
    catForm.target_team = c.target_team ?? '';
    catForm.sla_hours = c.sla_hours ?? '';
    catForm.active = !!c.active;
    catOpen.value = true;
}
function saveCat() {
    catForm
        .transform((d) => ({
            name_ar: d.name_ar,
            default_priority: d.default_priority,
            target_team: d.target_team,
            sla_hours: d.sla_hours === '' || d.sla_hours === null ? null : Number(d.sla_hours),
            active: !!d.active,
        }))
        .post(`/admin/categories/${catForm.id}`, {
            preserveScroll: true,
            onSuccess: () => { catOpen.value = false; },
        });
}

/* -------------------------- Product edit dialog ----------------------- */
const prodOpen = ref(false);
const prodForm = useForm({
    id: null,
    name_ar: '',
    type: 'product',
    description_ar: '',
    sla_hours: '',
    escalation_hours: '',
    color: '',
    sort_order: '',
    restricted: false,
    active: true,
});
function resetProdForm() {
    prodForm.id = null;
    prodForm.name_ar = '';
    prodForm.type = 'product';
    prodForm.description_ar = '';
    prodForm.sla_hours = '';
    prodForm.escalation_hours = '';
    prodForm.color = '';
    prodForm.sort_order = '';
    prodForm.restricted = false;
    prodForm.active = true;
}
function openProduct(p) {
    prodForm.clearErrors();
    if (p) {
        prodForm.id = p.id;
        prodForm.name_ar = p.name_ar ?? '';
        prodForm.type = p.type ?? 'product';
        prodForm.description_ar = p.description_ar ?? '';
        prodForm.sla_hours = p.sla_hours ?? '';
        prodForm.escalation_hours = p.escalation_hours ?? '';
        prodForm.color = p.color ?? '';
        prodForm.sort_order = p.sort_order ?? '';
        prodForm.restricted = !!p.restricted;
        prodForm.active = !!p.active;
    } else {
        resetProdForm();
    }
    prodOpen.value = true;
}
function saveProduct() {
    const url = prodForm.id ? `/admin/products/${prodForm.id}` : '/admin/products';
    prodForm
        .transform((d) => ({
            name_ar: d.name_ar,
            type: d.type,
            description_ar: d.description_ar === '' ? null : d.description_ar,
            sla_hours: d.sla_hours === '' || d.sla_hours === null ? null : Number(d.sla_hours),
            escalation_hours: d.escalation_hours === '' || d.escalation_hours === null ? null : Number(d.escalation_hours),
            color: d.color === '' ? null : d.color,
            sort_order: d.sort_order === '' || d.sort_order === null ? null : Number(d.sort_order),
            restricted: !!d.restricted,
            active: !!d.active,
        }))
        .post(url, {
            preserveScroll: true,
            onSuccess: () => { prodOpen.value = false; },
        });
}

/* ---------------------------- Staff roles ----------------------------- */
function addRole(userId, role) {
    if (!role) return;
    router.post('/admin/roles/assign', { user_id: userId, role }, { preserveScroll: true });
}
function removeRole(userId, role) {
    router.post('/admin/roles/remove', { user_id: userId, role }, { preserveScroll: true });
}
function availableRoles(s) {
    const have = new Set(s.role_keys ?? s.roles ?? []);
    return staffRoles.filter((r) => !have.has(r));
}

/* ------------------------- Priority multipliers ----------------------- */
const multForms = ref({});
props.multipliers.forEach((m) => { multForms.value[m.priority] = String(m.multiplier); });
function saveMultiplier(m) {
    router.post('/admin/priority-multiplier', {
        priority: m.priority,
        multiplier: Number(multForms.value[m.priority]),
        label_ar: m.label_ar ?? null,
    }, { preserveScroll: true });
}

/* --------------------------- Permissions ------------------------------ */
const permRoles = computed(() => props.options.roles ?? Object.keys(props.capabilityCounts));
const allowedByRole = computed(() => {
    const map = {};
    for (const role of Object.keys(props.rolePermissions ?? {})) {
        const rows = props.rolePermissions[role] ?? [];
        map[role] = new Set(rows.map((r) => r.capability));
    }
    return map;
});
function roleHas(role, cap) {
    return allowedByRole.value[role]?.has(cap) ?? false;
}
const capSearch = ref('');
const filteredCaps = computed(() => {
    const q = capSearch.value.trim().toLowerCase();
    if (!q) return props.capabilityMeta;
    return props.capabilityMeta.filter((c) =>
        (c.capability ?? '').toLowerCase().includes(q) || (c.description ?? '').toLowerCase().includes(q));
});
const totalCaps = computed(() => props.capabilityMeta.length);

const savingPerm = ref(null);
function permKey(role, cap) { return `${role}::${cap}`; }
function togglePerm(role, cap) {
    // system_admin is guarded server-side and always effectively allowed.
    if (role === 'system_admin') return;
    const key = permKey(role, cap);
    savingPerm.value = key;
    router.post('/admin/permissions', {
        role,
        capability: cap,
        allowed: !roleHas(role, cap),
    }, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => { if (savingPerm.value === key) savingPerm.value = null; },
    });
}

/* --------------------------- Notifications ---------------------------- */
const TEMPLATE_GROUPS = [
    { key: 'lifecycle', label: 'دورة حياة الطلب', desc: 'إنشاء الطلب، إسناده، وتغيّر حالته.', events: ['new_request', 'assigned', 'status_change'] },
    { key: 'sla', label: 'SLA والتصعيد', desc: 'تجاوز اتفاقية مستوى الخدمة والتصعيد التلقائي.', events: ['sla_breach', 'escalation_l1', 'escalation_l2', 'escalation_l3'] },
    { key: 'completion', label: 'الإنجاز والتقييم والإغلاق', desc: 'اكتمال الطلب، طلب التقييم، والإغلاق التلقائي.', events: ['csat_request', 'low_rating', 'auto_close_customer', 'auto_close_staff'] },
];
const knownEvents = new Set(TEMPLATE_GROUPS.flatMap((g) => g.events));
const groupedTemplates = computed(() => {
    const list = props.notificationTemplates;
    const grouped = TEMPLATE_GROUPS.map((g) => ({ ...g, items: list.filter((t) => g.events.includes(t.event_key)) }));
    const rest = list.filter((t) => !knownEvents.has(t.event_key));
    if (rest.length) grouped.push({ key: 'other', label: 'أخرى (غير مصنّفة)', desc: '', items: rest });
    return grouped.filter((g) => g.items.length);
});
const CHANNEL_LABELS = { sms: 'رسائل نصية', email: 'بريد', in_app: 'داخل المنصة', whatsapp: 'واتساب' };
const CHANNEL_OPTIONS = ['in_app', 'email', 'sms'];
const RECIPIENT_OPTIONS = ['customer', 'assigned_staff', 'team', 'supervisor', 'manager', 'admin'];
const TEMPLATE_VARS = ['{customer_name}', '{request_number}', '{staff_name}', '{status}', '{category}', '{team_name}'];

const tplOpen = ref(false);
const tplForm = useForm({
    id: null,
    name_ar: '',
    recipient_type: '',
    channels: [],
    title_template: '',
    body_template: '',
    active: true,
});
function openTpl(t) {
    tplForm.clearErrors();
    tplForm.id = t.id;
    tplForm.name_ar = t.name_ar ?? '';
    tplForm.recipient_type = t.recipient_type ?? '';
    tplForm.channels = Array.isArray(t.channels) ? [...t.channels] : [];
    tplForm.title_template = t.title_template ?? '';
    tplForm.body_template = t.body_template ?? '';
    tplForm.active = !!t.active;
    tplOpen.value = true;
}
function toggleChannel(ch) {
    const i = tplForm.channels.indexOf(ch);
    if (i === -1) tplForm.channels.push(ch);
    else tplForm.channels.splice(i, 1);
}
function saveTpl() {
    tplForm
        .transform((d) => ({
            name_ar: d.name_ar,
            recipient_type: d.recipient_type || null,
            channels: d.channels,
            title_template: d.title_template === '' ? null : d.title_template,
            body_template: d.body_template === '' ? null : d.body_template,
            active: !!d.active,
        }))
        .post(`/admin/templates/${tplForm.id}`, {
            preserveScroll: true,
            onSuccess: () => { tplOpen.value = false; },
        });
}

/* --------------------------- Integrations ----------------------------- */
const INTEG_ICONS = {
    email_smtp: Mail, sms_provider: MessageSquare, whatsapp_business: Send, ivr_local: Phone,
    webhook_outbound: Webhook, analytics_bi_webhook: Gauge, api_access: KeyRound, sentry: ShieldCheck,
    ai_web_chat: Sparkles,
};
function integIcon(key) { return INTEG_ICONS[key] ?? Plug; }
const allIntegrations = computed(() => {
    const sys = props.systemIntegrations.map((i) => ({
        id: `sys-${i.id}`, key: i.key, name: i.name_ar ?? i.key, enabled: !!i.active,
    }));
    const setgs = props.integrationSettings.map((i) => ({
        id: `set-${i.id}`, key: i.key, name: i.label ?? i.key, enabled: !!i.enabled,
    }));
    return [...sys, ...setgs];
});

/* ------------------------------ Audit --------------------------------- */
const ACTION_LABELS = {
    'setting.update': 'تحديث إعداد',
    'category.update': 'تعديل تصنيف',
    'priority_multiplier.update': 'تعديل معامل أولوية',
    'staff.create': 'إنشاء موظف',
    'role.assign': 'إسناد دور',
    'role.remove': 'إزالة دور',
    'template.update': 'تعديل قالب',
    'permission.set': 'تعديل صلاحية',
    'product.create': 'إضافة منتج',
    'product.update': 'تعديل منتج',
};
const ENTITY_LABELS = {
    system_settings: 'إعدادات', categories: 'تصنيف', priority_multipliers: 'معامل أولوية',
    users: 'مستخدم', user_roles: 'دور مستخدم', notification_templates: 'قالب إشعار',
    role_permissions: 'صلاحية', products: 'منتج',
};
function actionLabel(a) { return ACTION_LABELS[a] ?? a; }
function entityLabel(e) { return ENTITY_LABELS[e] ?? e; }
function summarizeDetails(d) {
    if (!d || typeof d !== 'object') return '';
    if (d.name_ar) return d.name_ar;
    if (d.role) return `${roleLabel(d.role)}${d.email ? ' — ' + d.email : ''}`;
    if (d.key) return d.key;
    return Object.keys(d).slice(0, 3).map((k) => `${k}: ${typeof d[k] === 'object' ? JSON.stringify(d[k]) : d[k]}`).join('، ');
}
const auditAction = ref('all');
const auditEntity = ref('all');
const auditActions = computed(() => [...new Set(props.auditLog.map((a) => a.action))]);
const auditEntities = computed(() => [...new Set(props.auditLog.map((a) => a.entity_type))]);
const filteredAudit = computed(() => props.auditLog.filter((a) =>
    (auditAction.value === 'all' || a.action === auditAction.value) &&
    (auditEntity.value === 'all' || a.entity_type === auditEntity.value)));

const settingKeys = computed(() => Object.keys(props.settings));

/* ------------------------------ Sorting ------------------------------- */
const { sorted: catSorted, sortKey: catSortKey, sortDir: catSortDir, toggle: catToggle } = useClientSort(
    () => props.categories, null, 'asc',
    { name: 'name_ar', team: 'target_team', priority: 'default_priority', sla: 'sla_hours', requests: 'requests_count', status: 'active' },
);
const { sorted: prodSorted, sortKey: prodSortKey, sortDir: prodSortDir, toggle: prodToggle } = useClientSort(
    () => props.products, null, 'asc',
    { name: 'name_ar', type: 'type', description: 'description_ar', sla: 'sla_hours', escalation: 'escalation_hours', restricted: 'restricted', status: 'active' },
);
const { sorted: staffSorted, sortKey: staffSortKey, sortDir: staffSortDir, toggle: staffToggle } = useClientSort(
    () => props.staff, null, 'asc',
    { name: 'full_name', email: 'email', team: 'team_name', status: (r) => (r.suspended ? 'موقوف' : (r.account_status ?? '')) },
);
const { sorted: multSorted, sortKey: multSortKey, sortDir: multSortDir, toggle: multToggle } = useClientSort(
    () => props.multipliers, null, 'asc',
    { priority: (m) => (m.label_ar ?? statusLabel(REQUEST_PRIORITY, m.priority).label ?? m.priority), multiplier: (m) => Number(m.multiplier) },
);
const { sorted: auditSorted, sortKey: auditSortKey, sortDir: auditSortDir, toggle: auditToggle } = useClientSort(
    () => filteredAudit.value, 'date', 'desc',
    { date: 'created_at', actor: 'actor_email', action: (a) => actionLabel(a.action), entity: (a) => entityLabel(a.entity_type) },
);
</script>

<template>
    <Head title="إدارة النظام" />
    <AppShell>
        <div class="space-y-5">
            <!-- Gradient hero -->
            <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-elevated" style="background-image: var(--gradient-hero);">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(closest-side at 75% 20%, rgba(255,255,255,.4), transparent);"></div>
                <div class="relative flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs text-white/60">إدارة النظام · لوحة التحكم</p>
                        <h1 class="mt-1 text-2xl font-bold">إدارة النظام</h1>
                        <p class="mt-1 max-w-xl text-sm text-white/80">تحكّم كامل بتصنيفات الطلبات، الأدوار، الإشعارات، والتكاملات.</p>
                    </div>
                    <Button class="bg-white/15 text-white hover:bg-white/25 backdrop-blur-sm lg:hidden" @click="mobileOpen = !mobileOpen">
                        <Menu class="size-4" /> الأقسام
                    </Button>
                </div>
                <!-- KPI ribbon -->
                <div class="relative mt-5 flex flex-wrap gap-2">
                    <div v-for="p in heroPills" :key="p.label"
                        class="flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-sm backdrop-blur-sm">
                        <component :is="p.icon" class="size-4 opacity-80" />
                        <span>{{ p.label }}</span>
                        <span class="rounded-full bg-black/20 px-1.5 text-xs font-bold tabular-nums">{{ p.value }}</span>
                    </div>
                </div>
            </div>

            <div class="grid gap-5 lg:grid-cols-[260px_1fr]">
                <!-- Sidebar -->
                <aside :class="[mobileOpen ? 'block' : 'hidden', 'lg:block lg:sticky lg:top-4 lg:self-start']">
                    <nav class="space-y-4 rounded-2xl border border-border bg-card p-2">
                        <div v-for="g in groupKeys" :key="g">
                            <div class="px-3 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">{{ GROUPS[g] }}</div>
                            <ul class="space-y-0.5">
                                <li v-for="s in SECTIONS.filter((x) => x.group === g)" :key="s.key">
                                    <button type="button" @click="pick(s.key)"
                                        :class="['flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition-all',
                                            active === s.key ? 'bg-primary font-semibold text-primary-foreground shadow-sm' : 'text-foreground/80 hover:bg-muted hover:text-foreground']">
                                        <component :is="s.icon" :class="['size-4 shrink-0', active === s.key ? 'text-primary-foreground' : 'text-muted-foreground']" />
                                        <span class="flex-1 text-right">{{ s.label }}</span>
                                        <ChevronLeft v-if="active === s.key" class="size-3.5 opacity-70" />
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </nav>
                </aside>

                <!-- Panel -->
                <main class="min-w-0">
                    <Card class="p-4 md:p-6">
                        <div class="mb-5 flex items-start gap-3 border-b border-border pb-4">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <component :is="activeMeta.icon" class="size-5" />
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-foreground md:text-xl">{{ activeMeta.label }}</h2>
                                <p class="mt-0.5 text-sm text-muted-foreground">{{ activeMeta.desc }}</p>
                            </div>
                        </div>

                        <!-- ============ CATEGORIES ============ -->
                        <div v-show="active === 'categories'" class="overflow-x-auto">
                            <table class="w-full border-collapse text-sm">
                                <thead class="bg-muted/50 text-xs text-muted-foreground">
                                    <tr class="border-b border-border">
                                        <SortableTh col="name" :sort-key="catSortKey" :sort-dir="catSortDir" @sort="catToggle">التصنيف</SortableTh>
                                        <SortableTh col="team" :sort-key="catSortKey" :sort-dir="catSortDir" @sort="catToggle">الفريق المستهدف</SortableTh>
                                        <SortableTh col="priority" :sort-key="catSortKey" :sort-dir="catSortDir" @sort="catToggle">الأولوية الافتراضية</SortableTh>
                                        <SortableTh col="sla" :sort-key="catSortKey" :sort-dir="catSortDir" @sort="catToggle">SLA (ساعة)</SortableTh>
                                        <SortableTh col="requests" :sort-key="catSortKey" :sort-dir="catSortDir" @sort="catToggle">الطلبات</SortableTh>
                                        <SortableTh col="status" :sort-key="catSortKey" :sort-dir="catSortDir" @sort="catToggle">الحالة</SortableTh>
                                        <th class="px-3 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="c in catSorted" :key="c.id" class="border-b border-border hover:bg-muted/40">
                                        <td class="px-3 py-3">
                                            <div class="flex items-center gap-2">
                                                <span class="size-2.5 rounded-full" :style="{ background: c.color || 'var(--muted-foreground)' }"></span>
                                                <div>
                                                    <div class="font-medium">{{ c.name_ar }}</div>
                                                    <div class="font-mono text-[11px] text-muted-foreground" dir="ltr">{{ c.key }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 text-sm text-muted-foreground">{{ c.target_team }}</td>
                                        <td class="px-3 py-3">
                                            <Badge :variant="statusLabel(REQUEST_PRIORITY, c.default_priority).tone">{{ statusLabel(REQUEST_PRIORITY, c.default_priority).label }}</Badge>
                                        </td>
                                        <td class="px-3 py-3 tabular-nums">{{ c.sla_hours ?? '—' }}</td>
                                        <td class="px-3 py-3 tabular-nums text-muted-foreground">{{ c.requests_count }}</td>
                                        <td class="px-3 py-3"><Badge :variant="c.active ? 'success' : 'muted'">{{ c.active ? 'مفعّل' : 'معطّل' }}</Badge></td>
                                        <td class="px-3 py-3 text-left">
                                            <Button size="sm" variant="outline" @click="openCat(c)"><Pencil class="size-3.5" /> تعديل</Button>
                                        </td>
                                    </tr>
                                    <tr v-if="!categories.length"><td colspan="7" class="py-10 text-center text-muted-foreground">لا توجد تصنيفات.</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- ============ PRODUCTS ============ -->
                        <div v-show="active === 'products'">
                            <div class="mb-3 flex items-center justify-between">
                                <p class="text-sm text-muted-foreground">إجمالي المنتجات والخدمات: <span class="font-bold tabular-nums text-foreground">{{ products.length }}</span></p>
                                <Button variant="accent" @click="openProduct(null)"><Package class="size-4" /> إضافة منتج</Button>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse text-sm">
                                    <thead class="bg-muted/50 text-xs text-muted-foreground">
                                        <tr class="border-b border-border">
                                            <SortableTh col="name" :sort-key="prodSortKey" :sort-dir="prodSortDir" @sort="prodToggle">المنتج / الخدمة</SortableTh>
                                            <SortableTh col="type" :sort-key="prodSortKey" :sort-dir="prodSortDir" @sort="prodToggle">النوع</SortableTh>
                                            <SortableTh col="description" :sort-key="prodSortKey" :sort-dir="prodSortDir" @sort="prodToggle">الوصف</SortableTh>
                                            <SortableTh col="sla" :sort-key="prodSortKey" :sort-dir="prodSortDir" @sort="prodToggle">SLA</SortableTh>
                                            <SortableTh col="escalation" :sort-key="prodSortKey" :sort-dir="prodSortDir" @sort="prodToggle">التصعيد</SortableTh>
                                            <SortableTh col="restricted" :sort-key="prodSortKey" :sort-dir="prodSortDir" @sort="prodToggle">مقيّد</SortableTh>
                                            <SortableTh col="status" :sort-key="prodSortKey" :sort-dir="prodSortDir" @sort="prodToggle">الحالة</SortableTh>
                                            <th class="px-3 py-3"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="p in prodSorted" :key="p.id" class="border-b border-border hover:bg-muted/40">
                                            <td class="px-3 py-3">
                                                <div class="flex items-center gap-2">
                                                    <span class="size-2.5 rounded-full" :style="{ background: p.color || 'var(--muted-foreground)' }"></span>
                                                    <span class="font-medium">{{ p.name_ar }}</span>
                                                </div>
                                            </td>
                                            <td class="px-3 py-3"><Badge variant="secondary">{{ p.type }}</Badge></td>
                                            <td class="px-3 py-3 max-w-[18rem] truncate text-sm text-muted-foreground">{{ p.description_ar ?? '—' }}</td>
                                            <td class="px-3 py-3 tabular-nums">{{ p.sla_hours ?? '—' }}</td>
                                            <td class="px-3 py-3 tabular-nums">{{ p.escalation_hours ?? '—' }}</td>
                                            <td class="px-3 py-3"><Badge :variant="p.restricted ? 'warning' : 'muted'">{{ p.restricted ? 'نعم' : 'لا' }}</Badge></td>
                                            <td class="px-3 py-3"><Badge :variant="p.active ? 'success' : 'muted'">{{ p.active ? 'مفعّل' : 'معطّل' }}</Badge></td>
                                            <td class="px-3 py-3 text-left">
                                                <Button size="sm" variant="outline" @click="openProduct(p)"><Pencil class="size-3.5" /> تعديل</Button>
                                            </td>
                                        </tr>
                                        <tr v-if="!products.length"><td colspan="8" class="py-10 text-center text-muted-foreground">لا توجد منتجات.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- ============ TEAMS ============ -->
                        <div v-show="active === 'teams'" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <div v-for="t in teams" :key="t.id" class="rounded-xl border border-border bg-card p-4">
                                <div class="flex items-center gap-2">
                                    <span class="size-4 rounded-full" :style="{ background: t.color || 'var(--primary)' }"></span>
                                    <span class="font-semibold">{{ t.name_ar }}</span>
                                    <Badge v-if="!t.active" variant="muted" class="ms-auto text-[10px]">معطّل</Badge>
                                </div>
                                <p v-if="t.description" class="mt-2 text-xs text-muted-foreground">{{ t.description }}</p>
                                <p class="mt-2 font-mono text-[11px] text-muted-foreground" dir="ltr">{{ t.key }}</p>
                            </div>
                            <p v-if="!teams.length" class="text-muted-foreground">لا توجد فرق.</p>
                        </div>

                        <!-- ============ SYSTEM SETTINGS ============ -->
                        <div v-show="active === 'system'">
                            <div v-if="settingKeys.length" class="grid gap-3 md:grid-cols-2">
                                <SettingRow v-for="k in settingKeys" :key="k" :setting-key="k" :value="settings[k]" />
                            </div>
                            <p v-else class="py-10 text-center text-muted-foreground">لا توجد إعدادات محفوظة.</p>
                        </div>

                        <!-- ============ STAFF ============ -->
                        <div v-show="active === 'staff'">
                            <div class="mb-3 flex items-center justify-between">
                                <p class="text-sm text-muted-foreground">إجمالي الموظفين: <span class="font-bold tabular-nums text-foreground">{{ staff.length }}</span></p>
                                <Button variant="accent" @click="addOpen = true"><UserPlus class="size-4" /> إضافة موظف</Button>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse text-sm">
                                    <thead class="bg-muted/50 text-xs text-muted-foreground">
                                        <tr class="border-b border-border">
                                            <SortableTh col="name" :sort-key="staffSortKey" :sort-dir="staffSortDir" @sort="staffToggle">الاسم</SortableTh>
                                            <SortableTh col="email" :sort-key="staffSortKey" :sort-dir="staffSortDir" @sort="staffToggle">البريد</SortableTh>
                                            <SortableTh col="team" :sort-key="staffSortKey" :sort-dir="staffSortDir" @sort="staffToggle">الفريق</SortableTh>
                                            <SortableTh col="status" :sort-key="staffSortKey" :sort-dir="staffSortDir" @sort="staffToggle">الحالة</SortableTh>
                                            <th class="px-3 py-3 text-start">الأدوار</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="s in staffSorted" :key="s.id" class="border-b border-border align-top hover:bg-muted/40">
                                            <td class="px-3 py-3">
                                                <div class="flex items-center gap-2">
                                                    <Avatar :name="s.full_name" class="size-7 text-[10px]" />
                                                    <span class="font-medium">{{ s.full_name }}</span>
                                                </div>
                                            </td>
                                            <td class="px-3 py-3 text-sm text-muted-foreground" dir="ltr">{{ s.email }}</td>
                                            <td class="px-3 py-3 text-sm text-muted-foreground">{{ s.team_name ?? '—' }}</td>
                                            <td class="px-3 py-3">
                                                <Badge :variant="s.suspended ? 'destructive' : (s.account_status === 'active' ? 'success' : 'muted')">
                                                    {{ s.suspended ? 'موقوف' : (s.account_status === 'active' ? 'نشط' : (s.account_status ?? '—')) }}
                                                </Badge>
                                            </td>
                                            <td class="px-3 py-3">
                                                <div class="flex flex-wrap items-center gap-1.5">
                                                    <span v-for="r in (s.role_keys ?? s.roles ?? [])" :key="r"
                                                        class="group inline-flex items-center gap-1 rounded-full border border-border bg-primary-soft px-2 py-0.5 text-xs font-medium text-primary">
                                                        {{ roleLabel(r) }}
                                                        <button v-if="(s.role_keys ?? s.roles ?? []).length > 1" type="button"
                                                            class="opacity-50 transition-opacity hover:text-destructive hover:opacity-100"
                                                            @click="removeRole(s.id, r)" aria-label="إزالة">
                                                            <X class="size-3" />
                                                        </button>
                                                    </span>
                                                    <Select v-if="availableRoles(s).length" :model-value="''"
                                                        class="h-7 w-auto min-w-[110px] px-2 text-xs"
                                                        @update:model-value="(v) => addRole(s.id, v)">
                                                        <option value="" disabled>+ دور</option>
                                                        <option v-for="r in availableRoles(s)" :key="r" :value="r">{{ roleLabel(r) }}</option>
                                                    </Select>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="!staff.length"><td colspan="5" class="py-10 text-center text-muted-foreground">لا يوجد موظفون.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- ============ PERMISSIONS ============ -->
                        <div v-show="active === 'permissions'" class="space-y-5">
                            <!-- role summary cards -->
                            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                <div v-for="role in permRoles" :key="role" class="rounded-lg border border-border bg-card p-3">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="flex items-center gap-1.5 text-sm font-semibold"><ShieldCheck class="size-4 text-primary" /> {{ roleLabel(role) }}</span>
                                        <span class="font-mono text-xs tabular-nums text-muted-foreground">{{ capabilityCounts[role] ?? (allowedByRole[role]?.size ?? 0) }}/{{ totalCaps }}</span>
                                    </div>
                                    <div class="mt-2 h-1.5 overflow-hidden rounded bg-muted">
                                        <div class="h-full bg-primary/70" :style="{ width: totalCaps ? ((capabilityCounts[role] ?? (allowedByRole[role]?.size ?? 0)) / totalCaps * 100) + '%' : '0%' }"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- matrix -->
                            <div>
                                <div class="mb-2 flex items-center justify-between gap-2">
                                    <h3 class="text-sm font-semibold">مصفوفة الصلاحيات</h3>
                                    <div class="relative w-56">
                                        <Search class="pointer-events-none absolute left-3 top-1/2 z-10 size-4 -translate-y-1/2 text-muted-foreground" />
                                        <Input v-model="capSearch" label="ابحث في الصلاحيات…" class="h-9 text-sm" />
                                    </div>
                                </div>
                                <div class="overflow-x-auto rounded-lg border border-border">
                                    <table class="w-full border-collapse text-xs">
                                        <thead class="bg-muted/50">
                                            <tr class="border-b border-border">
                                                <th class="sticky right-0 z-10 min-w-[200px] bg-muted/50 px-3 py-2 text-start">الصلاحية</th>
                                                <th v-for="role in permRoles" :key="role" class="min-w-[90px] px-2 py-2 text-center">{{ roleLabel(role) }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="cap in filteredCaps" :key="cap.capability" class="border-b border-border hover:bg-muted/30">
                                                <td class="sticky right-0 z-10 bg-card px-3 py-2">
                                                    <div class="font-mono text-[11px] text-foreground" dir="ltr">{{ cap.capability }}</div>
                                                    <div v-if="cap.description" class="mt-0.5 max-w-[220px] truncate text-[10px] text-muted-foreground">{{ cap.description }}</div>
                                                </td>
                                                <td v-for="role in permRoles" :key="role" class="px-2 py-2 text-center">
                                                    <input type="checkbox"
                                                        class="size-4 cursor-pointer align-middle accent-primary disabled:cursor-not-allowed"
                                                        :class="savingPerm === permKey(role, cap.capability) ? 'opacity-40' : ''"
                                                        :checked="role === 'system_admin' ? true : roleHas(role, cap.capability)"
                                                        :disabled="role === 'system_admin' || savingPerm === permKey(role, cap.capability)"
                                                        :title="role === 'system_admin' ? 'مدير النظام يملك جميع الصلاحيات' : ''"
                                                        @change="togglePerm(role, cap.capability)" />
                                                </td>
                                            </tr>
                                            <tr v-if="!filteredCaps.length"><td :colspan="permRoles.length + 1" class="py-8 text-center text-muted-foreground">لا توجد صلاحيات مطابقة.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- ============ SLA & PRIORITIES ============ -->
                        <div v-show="active === 'sla'" class="overflow-x-auto">
                            <table class="w-full border-collapse text-sm">
                                <thead class="bg-muted/50 text-xs text-muted-foreground">
                                    <tr class="border-b border-border">
                                        <SortableTh col="priority" :sort-key="multSortKey" :sort-dir="multSortDir" @sort="multToggle">الأولوية</SortableTh>
                                        <SortableTh col="multiplier" :sort-key="multSortKey" :sort-dir="multSortDir" @sort="multToggle">المضاعف</SortableTh>
                                        <th class="px-3 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="m in multSorted" :key="m.priority" class="border-b border-border hover:bg-muted/40">
                                        <td class="px-3 py-3">
                                            <Badge :variant="statusLabel(REQUEST_PRIORITY, m.priority).tone">{{ m.label_ar ?? statusLabel(REQUEST_PRIORITY, m.priority).label }}</Badge>
                                        </td>
                                        <td class="px-3 py-3">
                                            <Input v-model="multForms[m.priority]" type="number" step="0.1" class="h-9 w-28 text-sm" dir="ltr" />
                                        </td>
                                        <td class="px-3 py-3 text-left">
                                            <Button size="sm" variant="accent" @click="saveMultiplier(m)"><Save class="size-3.5" /> حفظ</Button>
                                        </td>
                                    </tr>
                                    <tr v-if="!multipliers.length"><td colspan="3" class="py-10 text-center text-muted-foreground">لا توجد معاملات.</td></tr>
                                </tbody>
                            </table>
                            <p class="mt-3 text-xs text-muted-foreground">يُضرب زمن SLA الأساسي في معامل الأولوية لحساب موعد الاستحقاق الفعلي.</p>
                        </div>

                        <!-- ============ TEMPLATES ============ -->
                        <div v-show="active === 'templates'" class="space-y-4">
                            <div v-for="g in groupedTemplates" :key="g.key" class="rounded-xl border border-border">
                                <div class="border-b border-border bg-muted/30 px-4 py-3">
                                    <h3 class="text-sm font-bold text-primary">{{ g.label }}</h3>
                                    <p v-if="g.desc" class="mt-0.5 text-xs text-muted-foreground">{{ g.desc }}</p>
                                </div>
                                <div class="divide-y divide-border">
                                    <div v-for="t in g.items" :key="t.id ?? t.event_key" class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                                        <div class="min-w-0">
                                            <div class="font-medium">{{ t.name_ar }}</div>
                                            <div class="font-mono text-[11px] text-muted-foreground" dir="ltr">{{ t.event_key }}</div>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <Badge v-for="ch in (Array.isArray(t.channels) ? t.channels : [])" :key="ch" variant="secondary" class="text-[10px]">{{ CHANNEL_LABELS[ch] ?? ch }}</Badge>
                                            <Badge :variant="t.active ? 'success' : 'muted'">{{ t.active ? 'مفعّل' : 'معطّل' }}</Badge>
                                            <Button size="sm" variant="outline" @click="openTpl(t)"><Pencil class="size-3.5" /> تعديل</Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p v-if="!groupedTemplates.length" class="py-10 text-center text-muted-foreground">لا توجد قوالب.</p>
                        </div>

                        <!-- ============ INTEGRATIONS ============ -->
                        <div v-show="active === 'integrations'" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <div v-for="i in allIntegrations" :key="i.id" class="rounded-xl border border-border bg-card p-4">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                        <component :is="integIcon(i.key)" class="size-5" />
                                    </div>
                                    <Badge :variant="i.enabled ? 'success' : 'muted'" class="gap-1">
                                        <CheckCircle2 v-if="i.enabled" class="size-3" /><Wrench v-else class="size-3" />
                                        {{ i.enabled ? 'مفعّل' : 'يحتاج إعداد' }}
                                    </Badge>
                                </div>
                                <div class="mt-3 font-medium">{{ i.name }}</div>
                                <div class="font-mono text-[11px] text-muted-foreground" dir="ltr">{{ i.key }}</div>
                            </div>
                            <p v-if="!allIntegrations.length" class="text-muted-foreground">لا توجد تكاملات.</p>
                        </div>

                        <!-- ============ AUDIT ============ -->
                        <div v-show="active === 'audit'">
                            <div class="mb-3 flex flex-wrap items-center gap-2">
                                <Select v-model="auditAction" class="h-9 w-auto min-w-[150px] text-sm">
                                    <option value="all">كل الإجراءات</option>
                                    <option v-for="a in auditActions" :key="a" :value="a">{{ actionLabel(a) }}</option>
                                </Select>
                                <Select v-model="auditEntity" class="h-9 w-auto min-w-[150px] text-sm">
                                    <option value="all">كل الأنواع</option>
                                    <option v-for="e in auditEntities" :key="e" :value="e">{{ entityLabel(e) }}</option>
                                </Select>
                                <span class="text-xs text-muted-foreground">{{ filteredAudit.length }} عملية</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse text-sm">
                                    <thead class="bg-muted/50 text-xs text-muted-foreground">
                                        <tr class="border-b border-border">
                                            <SortableTh col="date" :sort-key="auditSortKey" :sort-dir="auditSortDir" @sort="auditToggle">التاريخ</SortableTh>
                                            <SortableTh col="actor" :sort-key="auditSortKey" :sort-dir="auditSortDir" @sort="auditToggle">المستخدم</SortableTh>
                                            <SortableTh col="action" :sort-key="auditSortKey" :sort-dir="auditSortDir" @sort="auditToggle">الإجراء</SortableTh>
                                            <SortableTh col="entity" :sort-key="auditSortKey" :sort-dir="auditSortDir" @sort="auditToggle">النوع</SortableTh>
                                            <th class="px-3 py-3 text-start">التفاصيل</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="a in auditSorted" :key="a.id" class="border-b border-border hover:bg-muted/40">
                                            <td class="whitespace-nowrap px-3 py-3 text-xs text-muted-foreground" :title="fmtFullDateTimeAr(a.created_at)">{{ timeAgoAr(a.created_at) }}</td>
                                            <td class="px-3 py-3 text-xs text-muted-foreground" dir="ltr">{{ a.actor_email ?? '—' }}</td>
                                            <td class="px-3 py-3"><span class="rounded bg-primary-soft px-2 py-1 text-xs text-primary">{{ actionLabel(a.action) }}</span></td>
                                            <td class="px-3 py-3 text-xs">{{ entityLabel(a.entity_type) }}</td>
                                            <td class="max-w-md truncate px-3 py-3 text-xs text-muted-foreground" :title="JSON.stringify(a.details)">{{ summarizeDetails(a.details) }}</td>
                                        </tr>
                                        <tr v-if="!filteredAudit.length"><td colspan="5" class="py-10 text-center text-muted-foreground">لا يوجد سجل.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </Card>
                </main>
            </div>

            <!-- Add employee dialog (kept) -->
            <Dialog v-model:open="addOpen" title="إضافة موظف جديد" description="أنشئ حساب موظف بالاسم والبريد والدور فقط — دون كلمة مرور.">
                <div class="space-y-3">
                    <div v-if="setupLink" class="space-y-3">
                        <div class="rounded-lg bg-success/10 p-3 text-sm text-success">تم إنشاء الحساب بنجاح.</div>
                        <div>
                            <Label>رابط تعيين كلمة المرور (شاركه مع الموظف)</Label>
                            <div class="mt-1.5 flex gap-2">
                                <Input :model-value="setupLink" readonly class="text-xs" dir="ltr" />
                                <Button size="icon" variant="outline" @click="copyLink" title="نسخ"><Copy class="size-4" /></Button>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">أو يمكن للموظف الدخول مباشرةً عبر Google إن كان بريده ضمن نطاق المؤسسة.</p>
                        </div>
                        <div class="flex justify-end"><Button variant="outline" @click="addOpen = false">إغلاق</Button></div>
                    </div>
                    <template v-else>
                        <div>
                            <Input label="الاسم الكامل" v-model="staffForm.name" />
                            <p v-if="staffForm.errors.name" class="mt-1 text-xs text-destructive">{{ staffForm.errors.name }}</p>
                        </div>
                        <div>
                            <Input label="البريد الإلكتروني" v-model="staffForm.email" type="email" dir="ltr" />
                            <p v-if="staffForm.errors.email" class="mt-1 text-xs text-destructive">{{ staffForm.errors.email }}</p>
                        </div>
                        <div>
                            <Select label="الدور" v-model="staffForm.role">
                                <option v-for="r in staffRoles" :key="r" :value="r">{{ roleLabel(r) }}</option>
                            </Select>
                            <p v-if="staffForm.errors.role" class="mt-1 text-xs text-destructive">{{ staffForm.errors.role }}</p>
                        </div>
                        <div class="flex justify-end gap-2 pt-1">
                            <Button variant="outline" @click="addOpen = false">إلغاء</Button>
                            <Button variant="accent" :disabled="staffForm.processing" @click="submitStaff">إنشاء الحساب</Button>
                        </div>
                    </template>
                </div>
            </Dialog>

            <!-- Category edit dialog -->
            <Dialog v-model:open="catOpen" title="تعديل التصنيف" description="عدّل الاسم، الفريق المستهدف، الأولوية الافتراضية، ومستهدف SLA.">
                <div class="space-y-3">
                    <div>
                        <Input label="اسم التصنيف" v-model="catForm.name_ar" />
                        <p v-if="catForm.errors.name_ar" class="mt-1 text-xs text-destructive">{{ catForm.errors.name_ar }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <Select label="الأولوية الافتراضية" v-model="catForm.default_priority">
                                <option v-for="pr in (options.priorities ?? ['urgent','high','medium','low'])" :key="pr" :value="pr">{{ statusLabel(REQUEST_PRIORITY, pr).label }}</option>
                            </Select>
                        </div>
                        <div>
                            <Input label="SLA (ساعة)" v-model="catForm.sla_hours" type="number" min="0" dir="ltr" />
                            <p v-if="catForm.errors.sla_hours" class="mt-1 text-xs text-destructive">{{ catForm.errors.sla_hours }}</p>
                        </div>
                    </div>
                    <div>
                        <Select label="الفريق المستهدف" v-model="catForm.target_team">
                            <option value="" disabled>اختر فريقاً</option>
                            <option v-for="tm in (options.teams ?? [])" :key="tm" :value="tm">{{ tm }}</option>
                            <option v-if="catForm.target_team && !(options.teams ?? []).includes(catForm.target_team)" :value="catForm.target_team">{{ catForm.target_team }}</option>
                        </Select>
                        <p v-if="catForm.errors.target_team" class="mt-1 text-xs text-destructive">{{ catForm.errors.target_team }}</p>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-border px-3 py-2">
                        <span class="text-sm">الحالة</span>
                        <button type="button" @click="catForm.active = !catForm.active"
                            :class="['relative inline-flex h-6 w-11 items-center rounded-full transition-colors', catForm.active ? 'bg-success' : 'bg-muted']">
                            <span :class="['inline-block size-4 transform rounded-full bg-white transition-transform', catForm.active ? '-translate-x-1' : '-translate-x-6']"></span>
                        </button>
                    </div>
                    <div class="flex justify-end gap-2 pt-1">
                        <Button variant="outline" @click="catOpen = false">إلغاء</Button>
                        <Button variant="accent" :disabled="catForm.processing" @click="saveCat"><Save class="size-4" /> حفظ التغييرات</Button>
                    </div>
                </div>
            </Dialog>

            <!-- Product add/edit dialog -->
            <Dialog v-model:open="prodOpen" :title="prodForm.id ? 'تعديل المنتج' : 'إضافة منتج'" description="عرّف المنتج أو الخدمة ومستهدفات الخدمة والتصعيد.">
                <div class="space-y-3">
                    <div>
                        <Input label="الاسم" v-model="prodForm.name_ar" />
                        <p v-if="prodForm.errors.name_ar" class="mt-1 text-xs text-destructive">{{ prodForm.errors.name_ar }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <Select label="النوع" v-model="prodForm.type">
                                <option value="product">منتج</option>
                                <option value="service">خدمة</option>
                            </Select>
                            <p v-if="prodForm.errors.type" class="mt-1 text-xs text-destructive">{{ prodForm.errors.type }}</p>
                        </div>
                        <div>
                            <Input label="اللون" v-model="prodForm.color" dir="ltr" />
                            <p v-if="prodForm.errors.color" class="mt-1 text-xs text-destructive">{{ prodForm.errors.color }}</p>
                        </div>
                    </div>
                    <div>
                        <Textarea label="الوصف" v-model="prodForm.description_ar" rows="3" />
                        <p v-if="prodForm.errors.description_ar" class="mt-1 text-xs text-destructive">{{ prodForm.errors.description_ar }}</p>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <Input label="SLA (ساعة)" v-model="prodForm.sla_hours" type="number" min="0" dir="ltr" />
                            <p v-if="prodForm.errors.sla_hours" class="mt-1 text-xs text-destructive">{{ prodForm.errors.sla_hours }}</p>
                        </div>
                        <div>
                            <Input label="التصعيد (ساعة)" v-model="prodForm.escalation_hours" type="number" min="0" dir="ltr" />
                            <p v-if="prodForm.errors.escalation_hours" class="mt-1 text-xs text-destructive">{{ prodForm.errors.escalation_hours }}</p>
                        </div>
                        <div>
                            <Input label="الترتيب" v-model="prodForm.sort_order" type="number" min="0" dir="ltr" />
                            <p v-if="prodForm.errors.sort_order" class="mt-1 text-xs text-destructive">{{ prodForm.errors.sort_order }}</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-border px-3 py-2">
                        <span class="text-sm">مقيّد (صلاحية خاصة)</span>
                        <button type="button" @click="prodForm.restricted = !prodForm.restricted"
                            :class="['relative inline-flex h-6 w-11 items-center rounded-full transition-colors', prodForm.restricted ? 'bg-warning' : 'bg-muted']">
                            <span :class="['inline-block size-4 transform rounded-full bg-white transition-transform', prodForm.restricted ? '-translate-x-1' : '-translate-x-6']"></span>
                        </button>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-border px-3 py-2">
                        <span class="text-sm">الحالة</span>
                        <button type="button" @click="prodForm.active = !prodForm.active"
                            :class="['relative inline-flex h-6 w-11 items-center rounded-full transition-colors', prodForm.active ? 'bg-success' : 'bg-muted']">
                            <span :class="['inline-block size-4 transform rounded-full bg-white transition-transform', prodForm.active ? '-translate-x-1' : '-translate-x-6']"></span>
                        </button>
                    </div>
                    <div class="flex justify-end gap-2 pt-1">
                        <Button variant="outline" @click="prodOpen = false">إلغاء</Button>
                        <Button variant="accent" :disabled="prodForm.processing" @click="saveProduct"><Save class="size-4" /> {{ prodForm.id ? 'حفظ التغييرات' : 'إضافة' }}</Button>
                    </div>
                </div>
            </Dialog>

            <!-- Template edit dialog -->
            <Dialog v-model:open="tplOpen" title="تعديل القالب" description="عدّل نص العنوان والمحتوى، القنوات، ونوع المستلم.">
                <div class="space-y-3">
                    <div>
                        <Input label="اسم القالب" v-model="tplForm.name_ar" />
                        <p v-if="tplForm.errors.name_ar" class="mt-1 text-xs text-destructive">{{ tplForm.errors.name_ar }}</p>
                    </div>
                    <div>
                        <Select label="نوع المستلم" v-model="tplForm.recipient_type">
                            <option value=""></option>
                            <option v-for="r in RECIPIENT_OPTIONS" :key="r" :value="r">{{ r }}</option>
                            <option v-if="tplForm.recipient_type && !RECIPIENT_OPTIONS.includes(tplForm.recipient_type)" :value="tplForm.recipient_type">{{ tplForm.recipient_type }}</option>
                        </Select>
                        <p v-if="tplForm.errors.recipient_type" class="mt-1 text-xs text-destructive">{{ tplForm.errors.recipient_type }}</p>
                    </div>
                    <div>
                        <Label>قنوات الإرسال</Label>
                        <div class="mt-1.5 flex flex-wrap gap-2">
                            <button v-for="ch in CHANNEL_OPTIONS" :key="ch" type="button" @click="toggleChannel(ch)"
                                :class="['rounded-full border px-3 py-1 text-xs font-medium transition-colors',
                                    tplForm.channels.includes(ch) ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-card text-muted-foreground hover:bg-muted']">
                                {{ CHANNEL_LABELS[ch] ?? ch }}
                            </button>
                        </div>
                    </div>
                    <div>
                        <Input label="نص العنوان" v-model="tplForm.title_template" />
                        <p v-if="tplForm.errors.title_template" class="mt-1 text-xs text-destructive">{{ tplForm.errors.title_template }}</p>
                    </div>
                    <div>
                        <Textarea label="نص المحتوى" v-model="tplForm.body_template" rows="4" />
                        <p v-if="tplForm.errors.body_template" class="mt-1 text-xs text-destructive">{{ tplForm.errors.body_template }}</p>
                    </div>
                    <div class="rounded-lg border border-border bg-muted/30 px-3 py-2">
                        <p class="text-xs text-muted-foreground">المتغيرات المتاحة:</p>
                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                            <code v-for="v in TEMPLATE_VARS" :key="v" class="rounded bg-card px-1.5 py-0.5 font-mono text-[11px] text-primary" dir="ltr">{{ v }}</code>
                        </div>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-border px-3 py-2">
                        <span class="text-sm">الحالة</span>
                        <button type="button" @click="tplForm.active = !tplForm.active"
                            :class="['relative inline-flex h-6 w-11 items-center rounded-full transition-colors', tplForm.active ? 'bg-success' : 'bg-muted']">
                            <span :class="['inline-block size-4 transform rounded-full bg-white transition-transform', tplForm.active ? '-translate-x-1' : '-translate-x-6']"></span>
                        </button>
                    </div>
                    <div class="flex justify-end gap-2 pt-1">
                        <Button variant="outline" @click="tplOpen = false">إلغاء</Button>
                        <Button variant="accent" :disabled="tplForm.processing" @click="saveTpl"><Save class="size-4" /> حفظ التغييرات</Button>
                    </div>
                </div>
            </Dialog>
        </div>
    </AppShell>
</template>
