<script setup>
import { Fragment, Text, computed, nextTick, onBeforeUnmount, ref, useId, useSlots, watch } from 'vue';
import { ChevronDown, Check } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

const props = defineProps({
    modelValue: { type: [String, Number, null], default: '' },
    label: { type: String, default: '' },
    header: { type: String, default: '' },
    invalid: { type: Boolean, default: false },
    id: { type: String, default: null },
    class: { type: [String, Array, Object], default: '' },
});
const emit = defineEmits(['update:modelValue']);
const slots = useSlots();

const uid = useId();
const fid = computed(() => props.id || `sel-${uid}`);

/* Map a design tone name to a CSS colour (falls back to a raw colour string). */
const TONE = {
    destructive: 'var(--destructive)', warning: 'var(--warning)', info: 'var(--info)',
    success: 'var(--success)', muted: 'var(--muted-foreground)', primary: 'var(--primary)',
    accent: 'var(--accent)', ring: 'var(--ring)',
};
const dotColor = (v) => (v == null ? null : (TONE[v] || v));

/* Flatten <option> VNodes (incl. v-for fragments) into plain items. */
function textOf(children) {
    if (children == null) return '';
    if (typeof children === 'string') return children;
    if (Array.isArray(children)) return children.map(textOf).join('');
    if (typeof children === 'object') {
        if (children.type === Text) return String(children.children ?? '');
        return textOf(children.children);
    }
    return String(children);
}
function collect(nodes, acc) {
    for (const vn of (Array.isArray(nodes) ? nodes : [nodes])) {
        if (vn == null || typeof vn !== 'object') continue;
        if (vn.type === 'option') {
            acc.push({
                value: String(vn.props?.value ?? ''),
                label: textOf(vn.children).trim(),
                dot: dotColor(vn.props?.['data-dot'] ?? null),
                disabled: vn.props?.disabled != null && vn.props.disabled !== false,
            });
        } else if (vn.type === Fragment || Array.isArray(vn.children)) {
            collect(vn.children, acc);
        }
    }
    return acc;
}
const items = computed(() => collect(slots.default ? slots.default() : [], []));

const valStr = computed(() => (props.modelValue == null ? '' : String(props.modelValue)));
const selected = computed(() => items.value.find((o) => o.value === valStr.value) || null);
const hasValue = computed(() => selected.value !== null && valStr.value !== '');

/* --- Popover state --- */
const open = ref(false);
const active = ref(-1);
const triggerEl = ref(null);
const panelEl = ref(null);
const panelStyle = ref({});
const isFloat = computed(() => open.value || hasValue.value);

function place() {
    const el = triggerEl.value;
    if (!el) return;
    const r = el.getBoundingClientRect();
    const vh = window.innerHeight;
    const maxH = 288;
    const below = vh - r.bottom - 8;
    const above = r.top - 8;
    const openUp = below < 220 && above > below;
    const h = Math.min(maxH, (openUp ? above : below));
    panelStyle.value = {
        position: 'fixed',
        width: `${r.width}px`,
        right: `${window.innerWidth - r.right}px`,
        maxHeight: `${h}px`,
        ...(openUp ? { bottom: `${vh - r.top + 6}px` } : { top: `${r.bottom + 6}px` }),
    };
}

async function openMenu() {
    if (open.value) return;
    open.value = true;
    active.value = Math.max(0, items.value.findIndex((o) => o.value === valStr.value));
    await nextTick();
    place();
    scrollActiveIntoView();
    document.addEventListener('pointerdown', onDocPointer, true);
    window.addEventListener('scroll', place, true);
    window.addEventListener('resize', place);
}
function closeMenu() {
    if (!open.value) return;
    open.value = false;
    document.removeEventListener('pointerdown', onDocPointer, true);
    window.removeEventListener('scroll', place, true);
    window.removeEventListener('resize', place);
    triggerEl.value?.focus();
}
function toggle() { open.value ? closeMenu() : openMenu(); }

function onDocPointer(e) {
    if (triggerEl.value?.contains(e.target) || panelEl.value?.contains(e.target)) return;
    open.value = false;
    document.removeEventListener('pointerdown', onDocPointer, true);
    window.removeEventListener('scroll', place, true);
    window.removeEventListener('resize', place);
}

function pick(o) {
    if (o.disabled) return;
    emit('update:modelValue', o.value);
    closeMenu();
}

function scrollActiveIntoView() {
    nextTick(() => panelEl.value?.querySelector('.ff-opt.is-active')?.scrollIntoView({ block: 'nearest' }));
}
function move(step) {
    const n = items.value.length;
    if (!n) return;
    let i = active.value;
    for (let k = 0; k < n; k++) {
        i = (i + step + n) % n;
        if (!items.value[i].disabled) break;
    }
    active.value = i;
    scrollActiveIntoView();
}
function onKeydown(e) {
    switch (e.key) {
        case 'ArrowDown': e.preventDefault(); open.value ? move(1) : openMenu(); break;
        case 'ArrowUp': e.preventDefault(); open.value ? move(-1) : openMenu(); break;
        case 'Enter':
        case ' ':
            e.preventDefault();
            if (open.value && items.value[active.value]) pick(items.value[active.value]);
            else openMenu();
            break;
        case 'Escape': if (open.value) { e.preventDefault(); closeMenu(); } break;
        case 'Tab': if (open.value) closeMenu(); break;
    }
}

watch(() => props.modelValue, () => { if (open.value) place(); });
onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', onDocPointer, true);
    window.removeEventListener('scroll', place, true);
    window.removeEventListener('resize', place);
});
</script>

<template>
    <div class="ff" :class="[isFloat && 'is-float', open && 'is-focus', invalid && 'ff-invalid']">
        <button :id="fid" ref="triggerEl" type="button" role="combobox" :aria-expanded="open"
            class="ff-control ff-trigger" :class="cn(props.class)" @click="toggle" @keydown="onKeydown">
            <span class="ff-value" :class="!hasValue && 'ff-value-empty'">
                <span v-if="selected?.dot" class="ff-dot" :style="{ background: selected.dot }" />
                {{ selected?.label || '' }}
            </span>
            <ChevronDown class="ff-caret size-4 shrink-0" :class="open && 'rotate-180'" />
        </button>
        <label v-if="label" :for="fid" class="ff-label">{{ label }}</label>
        <fieldset aria-hidden="true" class="ff-outline"><legend class="ff-legend"><span>{{ label }}</span></legend></fieldset>

        <Teleport to="body">
            <div v-if="open" ref="panelEl" class="ff-panel" role="listbox" :style="panelStyle" dir="rtl">
                <div v-if="header || label" class="ff-panel-head">{{ header || label }}</div>
                <button v-for="(o, i) in items" :key="o.value + i" type="button" role="option"
                    :aria-selected="o.value === valStr" :disabled="o.disabled"
                    class="ff-opt" :class="{ 'is-active': i === active, 'is-selected': o.value === valStr, 'is-disabled': o.disabled }"
                    @mouseenter="active = i" @click="pick(o)">
                    <span class="ff-opt-label">
                        <span v-if="o.dot" class="ff-dot" :style="{ background: o.dot }" />
                        {{ o.label }}
                    </span>
                    <span class="ff-check"><Check v-if="o.value === valStr" class="size-3.5" /></span>
                </button>
            </div>
        </Teleport>
    </div>
</template>
